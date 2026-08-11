<?php
require_once '../config.php';
$admin = requireAdmin($pdo);

// Fetch recent movies for display
$moviesStmt = $pdo->prepare("SELECT * FROM movies ORDER BY created_at DESC LIMIT 8");
$moviesStmt->execute();
$recentMovies = $moviesStmt->fetchAll();

// Get statistics
$totalMoviesStmt = $pdo->query("SELECT COUNT(*) as count FROM movies");
$totalMovies = $totalMoviesStmt->fetch()['count'];

$totalUsersStmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'");
$totalUsers = $totalUsersStmt->fetch()['count'];

$totalShowsStmt = $pdo->query("SELECT COUNT(*) as count FROM shows");
$totalShows = $totalShowsStmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cinema Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ff4757;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: fadeInLeft 1s ease;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 100;
        }

        .nav-links a:before {
            content: '';
            position: absolute;
            top: 0;
            left: -10%;
            width: 80%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-links a:hover:before {
            left: 10%;
        }

        .nav-links a:hover {
            background: rgba(255, 71, 87, 0.2);
            transform: translateY(-2px);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
            animation: fadeInRight 1s ease;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #ff4757, #ff6b7a);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            animation: pulse 2s infinite;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9));
            padding: 120px 2rem 60px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: float 6s ease-in-out infinite;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            animation: fadeInUp 1s ease 0.3s both;
            background: linear-gradient(45deg, #fff, #ff4757);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease 0.6s both;
            opacity: 0.9;
        }

        .welcome-text {
            font-size: 1.1rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            border-radius: 50px;
            display: inline-block;
            animation: fadeInUp 1s ease 0.9s both;
            backdrop-filter: blur(10px);
        }

        /* Stats Cards */
        .stats-section {
            padding: 1rem 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 1rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transform: translateY(20px);
            opacity: 0;
            animation: slideInUp 0.8s ease forwards;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .stat-card:nth-child(2) {
            animation-delay: 0.3s;
        }

        .stat-card:nth-child(3) {
            animation-delay: 0.5s;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 71, 87, 0.1), transparent);
            transition: left 0.5s;
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #ff4757, #ff6b7a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: bounce 2s infinite;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }

        /* Movies Section */
        .movies-section {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: white;
            margin-bottom: 3rem;
            animation: fadeInUp 1s ease;
        }

        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 2rem;
        }

        .movie-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            transform: translateY(20px);
            opacity: 0;
            animation: slideInUp 0.8s ease forwards;
        }

        .movie-card:nth-child(odd) {
            animation-delay: 0.2s;
        }

        .movie-card:nth-child(even) {
            animation-delay: 0.4s;
        }

        .movie-card:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .movie-card:hover {
            opacity: 1;
        }

        .movie-info {
            padding: 1.5rem;
        }

        .movie-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .movie-genre {
            color: #666;
            font-size: 0.9rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 2rem;
            padding: 2rem 2rem;
            flex-wrap: wrap;
        }

        .action-btn {
            background: linear-gradient(45deg, #ff4757, #ff6b7a);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(255, 71, 87, 0.3);
            position: relative;
            overflow: hidden;
        }

        .action-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .action-btn:hover:before {
            left: 100%;
        }

        .action-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(255, 71, 87, 0.4);
        }

        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.9);
            color: white;
            text-align: center;
            padding: 3rem 2rem;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .footer h3 {
            margin-bottom: 1rem;
            color: #ff4757;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #ff4757;
        }

        /* Animations */
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

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-10px);
            }

            60% {
                transform: translateY(-5px);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .nav-links {
                display: none;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .movies-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
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
    <!-- Loading Screen -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="#" class="logo">
                <i class="fas fa-film"></i>
                CinemaFlex
            </a>
            <ul class="nav-links">
                <li><a href="manage_movies.php"><i class="fas fa-video"></i> Movies</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="manage_shows.php"><i class="fas fa-calendar"></i> Shows</a></li>
                <li><a href="see_bookings.php"><i class="fas fa-ticket-alt"></i> Bookings</a></li>
            </ul>

            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($admin['full_name'] ?? $admin['username'], 0, 1)) ?>
                </div>
                <span>Welcome, <?= htmlspecialchars($admin['full_name'] ?? $admin['username']) ?></span>
                <a href="logout.php" style="color: #ff4757; margin-left: 1rem;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="dashboard">
        <div class="hero-content">
            <h1><i class="fas fa-crown"></i> Admin Panel</h1>
            <p>Manage your cinema with powerful tools and insights</p>
            <div class="welcome-text">
                <i class="fas fa-user-shield"></i>
                Welcome back, <?= htmlspecialchars($admin['full_name'] ?? $admin['username']) ?>!
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-film"></i>
                </div>
                <div class="stat-number"><?= $totalMovies ?></div>
                <div class="stat-label">Total Movies</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= $totalUsers ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-number"><?= $totalShows ?></div>
                <div class="stat-label">Active Shows</div>
            </div>
        </div>
    </section>

    <!-- Action Buttons -->
    <section class="action-buttons">
        <a href="manage_movies.php" class="action-btn">
            <i class="fas fa-video"></i> Manage Movies
        </a>
        <a href="manage_users.php" class="action-btn">
            <i class="fas fa-users"></i> Manage Users
        </a>
        <a href="manage_shows.php" class="action-btn">
            <i class="fas fa-calendar"></i> Manage Shows
        </a>
    </section>

    <!-- Recent Movies Section -->
    <section class="movies-section">
        <h2 class="section-title"><i class="fas fa-star"></i> Recent Movies</h2>
        <div class="movies-grid">
            <?php if (empty($recentMovies)): ?>
                <div style="grid-column: 1/-1; text-align: center; color: white; font-size: 1.2rem;">
                    <i class="fas fa-film" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No movies added yet. Start by adding your first movie!</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentMovies as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-info">
                            <div class="movie-title"><?= htmlspecialchars($movie['title']) ?></div>
                            <div class="movie-genre"><?= htmlspecialchars($movie['genre'] ?? 'General') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>


    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <h3><i class="fas fa-film"></i> CinemaFlex Admin</h3>
            <p>Powerful cinema management system</p>
            <div class="footer-links">
                <a href="#dashboard">Dashboard</a>
                <a href="manage_movies.php">Movies</a>
                <a href="manage_users.php">Users</a>
                <a href="manage_shows.php">Shows</a>
                <a href="logout.php">Logout</a>
            </div>
            <p>&copy; 2024 CinemaFlex. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Loading screen
        window.addEventListener('load', function () {
            setTimeout(function () {
                document.getElementById('loading').style.opacity = '0';
                setTimeout(function () {
                    document.getElementById('loading').style.display = 'none';
                }, 500);
            }, 1000);
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to navbar
        window.addEventListener('scroll', function () {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(0, 0, 0, 0.98)';
                navbar.style.boxShadow = '0 5px 20px rgba(0, 0, 0, 0.3)';
            } else {
                navbar.style.background = 'rgba(0, 0, 0, 0.95)';
                navbar.style.boxShadow = 'none';
            }
        });

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.stat-card, .movie-card').forEach(el => {
            observer.observe(el);
        });

        // Add click effects
        document.querySelectorAll('.action-btn, .stat-card').forEach(btn => {
            btn.addEventListener('click', function (e) {
                const ripple = document.createElement('div');
                ripple.style.position = 'absolute';
                ripple.style.borderRadius = '50%';
                ripple.style.background = 'rgba(255, 255, 255, 0.3)';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple 0.6s linear';
                ripple.style.left = (e.clientX - e.target.offsetLeft) + 'px';
                ripple.style.top = (e.clientY - e.target.offsetTop) + 'px';
                ripple.style.width = ripple.style.height = '10px';

                e.target.style.position = 'relative';
                e.target.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>