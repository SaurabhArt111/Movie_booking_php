<?php
require_once '../config.php';
requireAdmin($pdo);

// Deleting is a state change — POST + CSRF only, never a plain GET link
// (a GET link can be triggered from another site just by loading an image).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_movies.php");
    exit;
}
csrf_verify();

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Clean up the poster file too, if there is one under our uploads dir.
    $stmt = $pdo->prepare("SELECT poster_url FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    $movie = $stmt->fetch();
    if ($movie && !empty($movie['poster_url'])) {
        $path = realpath('../' . $movie['poster_url']);
        $uploadsRoot = realpath('../uploads/movies/');
        if ($path && $uploadsRoot && strpos($path, $uploadsRoot) === 0 && is_file($path)) {
            @unlink($path);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: manage_movies.php");
exit;
