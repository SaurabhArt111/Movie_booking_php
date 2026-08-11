<?php
require_once '../config.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_shows.php");
    exit;
}
csrf_verify();

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("DELETE FROM shows WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: manage_shows.php");
exit;
