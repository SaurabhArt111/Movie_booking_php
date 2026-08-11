<?php
// config.php
// Core bootstrap: session, security headers, DB connection, shared helpers.
// IMPORTANT: this file must not output any HTML. Every page includes it
// before it knows whether it needs to redirect (login, admin guard, etc.),
// and any output here would make header()/redirect() calls fail.

/* ---------------- Secure session setup ---------------- */
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,   // cookie only sent over HTTPS when the site is served over HTTPS
        'httponly' => true,     // not readable from JavaScript — blocks session-cookie theft via XSS
        'samesite' => 'Lax',    // not sent on most cross-site requests — CSRF hardening
    ]);
    session_start();
}

/* ---------------- Security headers ---------------- */
if (!headers_sent()) {
    header('X-Frame-Options: DENY');            // page can't be embedded in an <iframe> (clickjacking)
    header('X-Content-Type-Options: nosniff');   // browser won't guess content types
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'unsafe-inline'; " .
        "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
        "font-src https://cdnjs.cloudflare.com data:; " .
        "img-src 'self' data:;");
}

/* ---------------- Database configuration ---------------- */
// Update DB credentials if needed
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'movie_booking');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // real prepared statements
    ]);
} catch (PDOException $e) {
    // Never echo raw DB errors to visitors — log them, show a generic message.
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die("We're having trouble connecting right now. Please try again shortly.");
}

/* ==================== General helpers ==================== */

function isLoggedIn()
{
    return !empty($_SESSION['user_id']);
}

function redirect($url)
{
    if (!headers_sent()) {
        header("Location: {$url}");
        exit;
    } else {
        echo "<script>window.location.href='" . htmlspecialchars($url, ENT_QUOTES) . "';</script>";
        exit;
    }
}

/** Flash alert helpers */
function flash($message, $type = 'info')
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash()
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function showAlert($message, $type = 'info')
{
    // legacy compatibility
    flash($message, $type);
}

function displayAlert()
{
    $f = getFlash();
    if ($f) {
        // Pages style .alert-success / .alert-error — keep the class name in
        // sync with what's actually defined, or error messages render with
        // no color at all.
        $type = in_array($f['type'], ['success', 'error'], true) ? $f['type'] : 'info';
        echo "<div class='alert alert-{$type}'>" . e($f['message']) . "</div>";
    }
}

/**
 * Trims input. Kept the name `sanitize` for compatibility with the rest of
 * the codebase, but it no longer HTML-encodes on the way in — encoding data
 * before it's stored just corrupts it (e.g. an apostrophe becomes `&#039;`
 * in the database) and doesn't actually protect anything, since every page
 * already escapes on the way out with e() / htmlspecialchars(). Escaping at
 * output time is what actually prevents XSS.
 */
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return trim((string) ($data ?? ''));
}

/** Shorthand for escaping any value for safe HTML output. */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* ==================== CSRF protection ==================== */

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Echo this inside every <form method="POST">. */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Call at the top of every POST handler before touching the database. */
function csrf_verify()
{
    $token = $_POST['csrf_token'] ?? '';
    if ($token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Your session has expired or this request could not be verified. Please refresh the page and try again.');
    }
}

/* ==================== Login throttling ==================== */
// Slows down password-guessing: after a handful of wrong attempts for the
// same account+IP combination, further tries are blocked for a cooldown
// period rather than checked instantly.

define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

function loginIdentifier($usernameOrEmail)
{
    return strtolower(trim($usernameOrEmail)) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function loginIsThrottled(PDO $pdo, $identifier)
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS c FROM login_attempts
         WHERE identifier = ? AND success = 0
           AND attempted_at > (NOW() - INTERVAL " . (int) LOGIN_LOCKOUT_MINUTES . " MINUTE)"
    );
    $stmt->execute([$identifier]);
    return (int) $stmt->fetch()['c'] >= LOGIN_MAX_ATTEMPTS;
}

function recordLoginAttempt(PDO $pdo, $identifier, $success)
{
    $stmt = $pdo->prepare("INSERT INTO login_attempts (identifier, success) VALUES (?, ?)");
    $stmt->execute([$identifier, $success ? 1 : 0]);
    if ($success) {
        // A successful login clears the account's own failed-attempt history.
        $pdo->prepare("DELETE FROM login_attempts WHERE identifier = ? AND success = 0")->execute([$identifier]);
    }
}

/* ==================== Admin access guard ==================== */

/**
 * Confirms the current session belongs to a real, still-active admin
 * account. Re-checks the role in the database on every call instead of only
 * trusting the session flag, so an account that's demoted mid-session loses
 * admin access immediately. A regular user session — even a logged-in one —
 * never satisfies this check; admin access is only ever granted by
 * admin/login.php after verifying credentials against role = 'admin'.
 * Call this at the top of every admin/*.php page.
 */
function requireAdmin(PDO $pdo)
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || $admin['role'] !== 'admin') {
        unset($_SESSION['admin_id'], $_SESSION['admin_name']);
        header('Location: login.php');
        exit;
    }
    return $admin;
}

/* ==================== Seat map ==================== */
// Seats are generated deterministically from a show's total_seats rather
// than stored in a separate layout table, so the same show always produces
// the same grid. Which of those generated seats are actually taken lives in
// the booked_seats table (see movie_booking.sql).

define('SEATS_PER_ROW', 10);
define('MAX_SEATS_PER_BOOKING', 10);

/** 0,1,2,... -> A,B,C,...,Z,AA,AB,... */
function rowLetter($index)
{
    $letter = '';
    $index++;
    while ($index > 0) {
        $index--;
        $letter = chr(65 + ($index % 26)) . $letter;
        $index = intdiv($index, 26);
    }
    return $letter;
}

/** Returns an array of rows: [ ['label' => 'A', 'seats' => ['A1','A2',...]], ... ] */
function buildSeatMap($totalSeats, $perRow = SEATS_PER_ROW)
{
    $totalSeats = max(0, (int) $totalSeats);
    $rows = [];
    $remaining = $totalSeats;
    $r = 0;
    while ($remaining > 0) {
        $inRow = min($perRow, $remaining);
        $label = rowLetter($r);
        $seats = [];
        for ($c = 1; $c <= $inRow; $c++) {
            $seats[] = $label . $c;
        }
        $rows[] = ['label' => $label, 'seats' => $seats];
        $remaining -= $inRow;
        $r++;
    }
    return $rows;
}

/** Confirms a seat label actually belongs to the grid generated for this show. */
function isValidSeatForShow($seatLabel, $totalSeats, $perRow = SEATS_PER_ROW)
{
    if (!preg_match('/^[A-Z]+[0-9]+$/', $seatLabel)) {
        return false;
    }
    foreach (buildSeatMap($totalSeats, $perRow) as $row) {
        if (in_array($seatLabel, $row['seats'], true)) {
            return true;
        }
    }
    return false;
}

/** Seat labels already booked for a show. */
function getBookedSeats(PDO $pdo, $showId)
{
    $stmt = $pdo->prepare("SELECT seat_label FROM booked_seats WHERE show_id = ?");
    $stmt->execute([$showId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/* ==================== Secure image upload ==================== */

/**
 * Validates an uploaded poster image and moves it into place with a random
 * filename (never the browser-supplied name), after checking its real
 * content type — not just the extension the client claims — so a renamed
 * script can't slip through as an "image". Throws RuntimeException with a
 * user-facing message on any problem; returns the web-relative path to
 * store in the database on success.
 */
function handleUploadedPoster(array $file, string $uploadDir, string $webPath)
{
    $allowedMimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $maxBytes = 5 * 1024 * 1024; // 5MB

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Poster upload failed. Please try again.');
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('Poster image is too large (max 5MB).');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid upload.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedMimeToExt[$mime])) {
        throw new RuntimeException('Only JPG, PNG, GIF, or WEBP images are allowed.');
    }

    // getimagesize() additionally confirms the file actually decodes as an
    // image (defense in depth beyond the MIME sniff above).
    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimeToExt[$mime];
    $target = rtrim($uploadDir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Could not save the uploaded poster.');
    }

    return rtrim($webPath, '/') . '/' . $filename;
}
