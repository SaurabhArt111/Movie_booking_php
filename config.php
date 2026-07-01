<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Database configuration — update DB credentials if needed
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'movie_booking');
define('DB_USER', 'root');
define('DB_PASS', '');


// Create PDO connection with charset
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // In production, don't echo errors — log them instead
    die("DB Connection failed: " . $e->getMessage());
}

/* Helpers */
function isLoggedIn()
{
    return !empty($_SESSION['user_id']);
}

function redirect($url)
{
    // If headers already sent, fall back to JS
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
        $type = $f['type'] === 'success' ? 'success' : ($f['type'] === 'error' ? 'danger' : 'info');
        echo "<div class='alert alert-{$type}'>{$f['message']}</div>";
    }
}

function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Booking</title>
    <style>
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: red;
            border-radius: 5px;
        }
        h1, h2, h3 {
            text-transform: capitalize;
        }
    </style>
</head>
<body>
    
</body>
</html>