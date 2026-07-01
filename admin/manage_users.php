<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
// Fetch all users
$stmt = $pdo->query("SELECT id, username, email, full_name, role, created_at FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            animation: backgroundShift 10s ease-in-out infinite alternate;
        }

        @keyframes backgroundShift {
            0% {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }

            100% {
                background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            animation: slideInUp 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .header h2 {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: fadeInLeft 1s ease-out;
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .back-btn {
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            animation: fadeInRight 1s ease-out;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .back-btn:hover {
            background: linear-gradient(45deg, #ff5252, #ff7575);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .stats-bar {
            background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
            animation: slideInDown 0.8s ease-out 0.3s both;
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: zoomIn 0.8s ease-out 0.5s both;
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            font-weight: 500;
            vertical-align: middle;
        }

        tr {
            transition: all 0.3s ease;
            animation: fadeInRow 0.6s ease-out forwards;
            opacity: 0;
        }

        @keyframes fadeInRow {
            to {
                opacity: 1;
            }
        }

        tr:nth-child(1) {
            animation-delay: 0.1s;
        }

        tr:nth-child(2) {
            animation-delay: 0.2s;
        }

        tr:nth-child(3) {
            animation-delay: 0.3s;
        }

        tr:nth-child(4) {
            animation-delay: 0.4s;
        }

        tr:nth-child(5) {
            animation-delay: 0.5s;
        }

        tr:nth-child(n+6) {
            animation-delay: 0.6s;
        }

        tbody tr:hover {
            background: linear-gradient(45deg, #f8f9ff, #e8f2ff);
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        .role-admin {
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            color: white;
        }

        .role-user {
            background: linear-gradient(45deg, #4ecdc4, #44b89d);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-edit {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }

        .btn-edit:hover {
            background: linear-gradient(45deg, #00f2fe, #4facfe);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4);
        }

        .btn-delete {
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-delete:hover {
            background: linear-gradient(45deg, #ff5252, #ff7575);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .user-id {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
            min-width: 30px;
            text-align: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .created-date {
            color: #6c757d;
            font-size: 0.9rem;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            table {
                font-size: 0.85rem;
            }

            th,
            td {
                padding: 10px 8px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }

        .loading-animation {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>
                <i class="fas fa-users"></i>
                Manage Users
            </h2>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <div class="stats-bar">
            <i class="fas fa-chart-bar"></i>
            Total Users: <?= count($users) ?>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-user"></i> User Info</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-shield-alt"></i> Role</th>
                        <th><i class="fas fa-calendar"></i> Created</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user): ?>
                        <tr style="animation-delay: <?= $index * 0.1 ?>s;">
                            <td>
                                <span class="user-id"><?= $user['id'] ?></span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #2c3e50;">
                                            <?= htmlspecialchars($user['full_name'] ?? 'N/A') ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: #6c757d;">
                                            @<?= htmlspecialchars($user['username']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <i class="fas fa-envelope" style="color: #667eea; margin-right: 8px;"></i>
                                <?= htmlspecialchars($user['email']) ?>
                            </td>
                            <td>
                                <span class="role-badge role-<?= strtolower($user['role']) ?>">
                                    <i class="fas fa-<?= $user['role'] === 'admin' ? 'crown' : 'user' ?>"></i>
                                    <?= $user['role'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="created-date">
                                    <i class="fas fa-clock" style="margin-right: 5px;"></i>
                                    <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </a>
                                    <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-delete"
                                        onclick="return confirmDelete('<?= htmlspecialchars($user['username']) ?>');">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function confirmDelete(username) {
            return confirm(`Are you sure you want to delete user "${username}"?\n\nThis action cannot be undone.`);
        }

        // Add loading animation on button clicks
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                if (this.classList.contains('btn-delete')) {
                    if (!confirmDelete(this.closest('tr').querySelector('td:nth-child(2) div div').textContent.trim())) {
                        e.preventDefault();
                        return;
                    }
                }

                const loadingSpinner = document.createElement('div');
                loadingSpinner.className = 'loading-animation';
                this.appendChild(loadingSpinner);

                setTimeout(() => {
                    this.style.pointerEvents = 'none';
                }, 100);
            });
        });

        // Smooth scroll animation
        window.addEventListener('load', function () {
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                document.body.style.overflow = 'auto';
            }, 1000);
        });

        // Enhanced hover effects
        document.querySelectorAll('tbody tr').forEach((row, index) => {
            row.addEventListener('mouseenter', function () {
                this.style.transform = 'scale(1.02)';
                this.style.zIndex = '5';
            });

            row.addEventListener('mouseleave', function () {
                this.style.transform = 'scale(1)';
                this.style.zIndex = '1';
            });
        });
    </script>
</body>

</html>