<?php
require_once '../config.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_users.php");
    exit;
}
csrf_verify();

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Prevent an admin from deleting their own account mid-session
    if ($id != $_SESSION['admin_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header("Location: manage_users.php");
exit;
