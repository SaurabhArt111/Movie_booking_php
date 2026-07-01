<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch shows
$stmt = $pdo->query("
    SELECT s.id, m.title AS movie_title, t.name AS theater_name, s.show_date, s.show_time 
    FROM shows s
    JOIN movies m ON s.movie_id = m.id
    JOIN theaters t ON s.theater_id = t.id
    ORDER BY s.show_date, s.show_time
");
$shows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shows - Cinema Admin</title>
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
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
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
            flex-wrap: wrap;
            gap: 15px;
        }

        .title {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #333;
        }

        .title h2 {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .title i {
            font-size: 2.5rem;
            color: #667eea;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 8px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(40, 167, 69, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            box-shadow: 0 8px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(108, 117, 125, 0.4);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        th {
            padding: 18px 15px;
            color: white;
            font-weight: 600;
            text-align: left;
            font-size: 1rem;
            letter-spacing: 0.5px;
        }

        tbody tr {
            transition: all 0.3s ease;
            animation: tableRowSlide 0.6s ease-out;
        }

        @keyframes tableRowSlide {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        tbody tr:nth-child(even) {
            background: rgba(102, 126, 234, 0.05);
        }

        tbody tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 0.95rem;
        }

        .movie-title {
            font-weight: 600;
            color: #333;
        }

        .theater-name {
            color: #667eea;
            font-weight: 500;
        }

        .show-date {
            background: linear-gradient(135deg, #ffecd2, #fcb69f);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #d63384;
            display: inline-block;
        }

        .show-time {
            background: linear-gradient(135deg, #a8edea, #fed6e3);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #6f42c1;
            display: inline-block;
        }

        .action-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .edit-link {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }

        .edit-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(23, 162, 184, 0.3);
        }

        .delete-link {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .delete-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(220, 53, 69, 0.3);
        }

        .show-id {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            min-width: 30px;
            text-align: center;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }

            .header {
                flex-direction: column;
                align-items: stretch;
            }

            .title h2 {
                font-size: 1.8rem;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }

            .action-buttons {
                justify-content: center;
            }
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="title">
                <i class="fas fa-calendar-alt"></i>
                <h2>Manage Shows</h2>
            </div>
            <div class="action-buttons">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <a href="add_show.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add New Show
                </a>
            </div>
        </div>

        <div class="table-container">
            <?php if (empty($shows)): ?>
                <div class="empty-state">
                    <i class="fas fa-theater-masks"></i>
                    <h3>No Shows Found</h3>
                    <p>Start by adding your first show to the system.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-film"></i> Movie</th>
                            <th><i class="fas fa-building"></i> Theater</th>
                            <th><i class="fas fa-calendar"></i> Date</th>
                            <th><i class="fas fa-clock"></i> Time</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shows as $index => $show): ?>
                            <tr style="animation-delay: <?= $index * 0.1 ?>s">
                                <td><span class="show-id"><?= $show['id'] ?></span></td>
                                <td class="movie-title"><?= htmlspecialchars($show['movie_title']) ?></td>
                                <td class="theater-name"><?= htmlspecialchars($show['theater_name']) ?></td>
                                <td><span class="show-date"><?= date('M d, Y', strtotime($show['show_date'])) ?></span></td>
                                <td><span class="show-time"><?= date('g:i A', strtotime($show['show_time'])) ?></span></td>
                                <td>
                                    <div class="action-links">
                                        <a href="edit_show.php?id=<?= $show['id'] ?>" class="action-link edit-link">
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </a>
                                        <a href="delete_show.php?id=<?= $show['id'] ?>" class="action-link delete-link"
                                            onclick="return confirmDelete('<?= htmlspecialchars($show['movie_title']) ?>');">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Enhanced delete confirmation
        function confirmDelete(movieTitle) {
            return confirm(`Are you sure you want to delete the show for "${movieTitle}"?\n\nThis action cannot be undone.`);
        }

        // Add loading animation to buttons when clicked
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function (e) {
                if (!this.classList.contains('delete-link')) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<div class="loading"></div> Loading...';

                    // Reset after navigation or timeout
                    setTimeout(() => {
                        this.innerHTML = originalText;
                    }, 2000);
                }
            });
        });

        // Add subtle animations on scroll
        function animateOnScroll() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                const rect = row.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    row.style.animation = `tableRowSlide 0.6s ease-out ${index * 0.05}s both`;
                }
            });
        }

        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);

        // Add hover sound effect (optional)
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function () {
                this.style.transform = 'translateX(5px) scale(1.01)';
            });

            row.addEventListener('mouseleave', function () {
                this.style.transform = 'translateX(0) scale(1)';
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                // Focus back to main container
                document.querySelector('.container').focus();
            }
        });
    </script>
</body>

</html>