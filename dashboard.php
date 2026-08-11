<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Get user's booking history
$stmt = $pdo->prepare("
    SELECT b.*, m.title, t.name as theater_name, s.show_date, s.show_time,
           (SELECT GROUP_CONCAT(seat_label ORDER BY seat_label SEPARATOR ', ')
              FROM booked_seats WHERE booking_id = b.id) AS seat_labels
    FROM bookings b 
    JOIN shows s ON b.show_id = s.id 
    JOIN movies m ON s.movie_id = m.id 
    JOIN theaters t ON s.theater_id = t.id 
    WHERE b.user_id = ? 
    ORDER BY b.booking_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

// Get available movies
$stmt = $pdo->query("SELECT * FROM movies WHERE status = 'active' ORDER BY title");
$movies = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - MovieBook</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            color: #fff;
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Loading Animation */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeOut 0.8s ease-in-out 2s forwards;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid #333;
            border-top: 5px solid #e50914;
            border-radius: 50%;
            animation: spin 0.3s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        /* Floating particles background */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #e50914;
            border-radius: 50%;
            animation: float 8s infinite ease-in-out;
            opacity: 0.3;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(100vh) scale(0);
            }

            50% {
                transform: translateY(-10px) scale(1);
            }
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.1) 100%);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(229, 9, 20, 0.3);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            animation: slideDown 0.8s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(45deg, #e50914, #ff6b6b);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 2px;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                filter: drop-shadow(0 0 5px #e50914);
            }

            to {
                filter: drop-shadow(0 0 15px #e50914);
            }
        }

        .nav-menu {
            display: flex;
            gap: 10px;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 25px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .nav-menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-menu a:hover::before {
            left: 100%;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: linear-gradient(45deg, #e50914, #ff3838);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(229, 9, 20, 0.3);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 10px auto;
            padding: 0 20px;
            animation: fadeInUp 1s ease-out 0.5s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, rgba(229, 9, 20, 0.1), rgba(255, 56, 56, 0.05));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 9, 20, 0.2);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: welcomeFloat 0.8s ease-out 0.7s both;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(transparent, rgba(229, 9, 20, 0.1), transparent 30%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes welcomeFloat {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .welcome-section h1 {
            color: #fff;
            margin-bottom: 15px;
            font-size: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .welcome-section p {
            color: #ccc;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
            animation: fadeInUp 1s ease-out 1s both;
        }

        .stat-card {
            background: linear-gradient(145deg, rgba(26, 26, 46, 0.8), rgba(45, 45, 45, 0.8));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(229, 9, 20, 0.1), transparent);
            transition: left 0.6s;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(229, 9, 20, 0.2);
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: bold;
            background: linear-gradient(45deg, #e50914, #ff6b6b);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            animation: countUp 2s ease-out;
        }

        @keyframes countUp {
            from {
                transform: scale(0.5);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Sections */
        .section {
            background: linear-gradient(145deg, rgba(31, 31, 31, 0.8), rgba(45, 45, 45, 0.8));
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideInLeft 0.8s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .section-title {
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: #fff;
            position: relative;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 3px;
            background: linear-gradient(90deg, #e50914, transparent);
            border-radius: 3px;
            animation: expandLine 0.8s ease-out;
        }

        @keyframes expandLine {
            from {
                width: 0;
            }

            to {
                width: 100%;
            }
        }

        /* Movies Grid */
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .movie-card {
            background: linear-gradient(145deg, rgba(42, 42, 42, 0.9), rgba(60, 60, 60, 0.9));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: fadeInScale 0.6s ease-out;
            animation-fill-mode: both;
        }

        .movie-card:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 25px 50px rgba(229, 9, 20, 0.3);
        }

        .movie-card img {
            width: 100%;
            height: 380px;
            object-fit: cover;
            align-items: start;
            transition: all 0.4s ease;
        }

        .movie-card:hover img {
            transform: scale(1.1);
        }

        .movie-card-content {
            padding: 20px;
        }

        .movie-card h3 {
            margin: 0 0 10px;
            color: #fff;
            font-size: 1.2rem;
        }

        .movie-card p {
            color: #bbb;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.8);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Buttons */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 15px;
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(45deg, #e50914, #ff3838);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(45deg, #c62828, #e74c3c);
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(229, 9, 20, 0.4);
        }

        /* Enhanced Table */
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: linear-gradient(145deg, rgba(42, 42, 42, 0.9), rgba(60, 60, 60, 0.9));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .bookings-table th,
        .bookings-table td {
            padding: 18px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .bookings-table th {
            background: linear-gradient(45deg, rgba(229, 9, 20, 0.3), rgba(255, 56, 56, 0.3));
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .bookings-table tr {
            transition: all 0.3s ease;
        }

        .bookings-table tr:hover {
            background: rgba(229, 9, 20, 0.1);
            transform: scale(1.00);
        }

        .status-confirmed {
            color: #27ae60;
            font-weight: bold;
            padding: 5px 12px;
            background: rgba(39, 174, 96, 0.2);
            border-radius: 15px;
        }

        .status-cancelled {
            color: #e74c3c;
            font-weight: bold;
            padding: 5px 12px;
            background: rgba(231, 76, 60, 0.2);
            border-radius: 15px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
                gap: 5px;
            }

            .welcome-section {
                padding: 25px;
            }

            .welcome-section h1 {
                font-size: 2rem;
            }

            .movies-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }

            .bookings-table {
                font-size: 0.9rem;
            }
        }

        /* Scroll animations */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.6s ease-out;
        }

        .scroll-reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }


        /* Tablets (≤ 1024px) */
        @media (max-width: 1024px) {
            .nav-container {
                padding: 0 15px;
            }

            .movies-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }

            .stat-card {
                padding: 20px;
            }

            .section {
                padding: 25px;
            }
        }

        /* Medium devices (≤ 768px) */
        @media (max-width: 768px) {

            /* Navbar */
            .nav-menu {
                display: none;
                flex-direction: column;
                background: rgba(0, 0, 0, 0.95);
                position: absolute;
                top: 100%;
                right: 0;
                width: 220px;
                padding: 10px;
                border-radius: 8px;
            }

            .nav-menu.active {
                display: flex;
            }

            .menu-toggle {
                display: block;
                cursor: pointer;
                font-size: 24px;
                color: #fff;
            }

            /* Welcome section */
            .welcome-section {
                padding: 25px;
            }

            .welcome-section h1 {
                font-size: 2rem;
            }

            /* Movies grid */
            .movies-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            /* Stats grid */
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }

            /* Table - scroll mode */
            .bookings-table-wrapper {
                width: 100%;
                overflow-x: auto;
            }

            .bookings-table {
                min-width: 750px;
                font-size: 0.9rem;
            }
        }

        /* Small devices (≤ 600px) */
        @media (max-width: 600px) {

            /* Collapse bookings table into cards */
            .bookings-table thead {
                display: none;
            }

            .bookings-table,
            .bookings-table tbody,
            .bookings-table tr,
            .bookings-table td {
                display: block;
                width: 100%;
            }

            .bookings-table tr {
                margin-bottom: 15px;
                background: rgba(42, 42, 42, 0.9);
                border-radius: 12px;
                padding: 10px;
            }

            .bookings-table td {
                padding: 8px 10px;
                text-align: right;
                border-bottom: none;
                position: relative;
            }

            .bookings-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                font-weight: bold;
                text-transform: uppercase;
                color: #e50914;
            }

            /* Movies grid single column */
            .movies-grid {
                grid-template-columns: 1fr;
            }

            /* Stats grid single column */
            .stats-grid {
                grid-template-columns: 1fr;
            }

            /* Section padding */
            .section {
                padding: 20px;
            }
        }
        /* Flash alerts */
        .alert {
            padding: 18px 24px;
            margin-bottom: 25px;
            border-radius: 15px;
            font-weight: 600;
            border: 1px solid;
            animation: alertSlideIn 0.4s ease-out;
        }

        @keyframes alertSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            border-color: rgba(39, 174, 96, 0.3);
            color: #fff;
        }

        .alert-error {
            background: linear-gradient(135deg, #e50914, #ff6b6b);
            border-color: rgba(229, 9, 20, 0.3);
            color: #fff;
        }

        button.btn {
            font-family: inherit;
            font-size: inherit;
        }
    </style>
</head>

<body>
    <!-- Loading Screen -->
    <div class="page-loader">
        <div class="loader"></div>
    </div>

    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="left: 20%; animation-delay: 0.5s;"></div>
        <div class="particle" style="left: 30%; animation-delay: 1s;"></div>
        <div class="particle" style="left: 40%; animation-delay: 1.5s;"></div>
        <div class="particle" style="left: 50%; animation-delay: 2s;"></div>
        <div class="particle" style="left: 60%; animation-delay: 2.5s;"></div>
        <div class="particle" style="left: 70%; animation-delay: 3s;"></div>
        <div class="particle" style="left: 80%; animation-delay: 3.5s;"></div>
        <div class="particle" style="left: 90%; animation-delay: 4s;"></div>
    </div>

    <div class="navbar">
        <div class="nav-container">
            <div class="logo">🎬 MovieBook</div>
            <div class="nav-menu">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="movies.php"><i class="fas fa-film"></i> Browse Movies</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php displayAlert(); ?>
        <div class="welcome-section scroll-reveal">
            <h1>Hello, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h1>
            <p>Book your favorite movies and manage your tickets all in one place.</p>
        </div>

        <?php
        // Calculate stats
        $total_bookings = count($bookings);
        $total_spent = array_sum(array_column($bookings, 'total_amount'));
        $active_bookings = count(array_filter($bookings, function ($b) {
            return $b['booking_status'] === 'confirmed' && strtotime($b['show_date'] . ' ' . $b['show_time']) > time();
        }));
        ?>

        <div class="stats-grid scroll-reveal">
            <div class="stat-card">
                <div class="stat-number"><?= $total_bookings ?></div>
                <div><i class="fas fa-ticket-alt"></i> Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₹<?= number_format($total_spent, 2) ?></div>
                <div><i class="fas fa-rupee-sign"></i> Total Spent</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $active_bookings ?></div>
                <div><i class="fas fa-clock"></i> Upcoming Shows</div>
            </div>
        </div>

        <div class="section scroll-reveal">
            <h2 class="section-title"><i class="fas fa-film"></i> Available Movies</h2>
            <div class="movies-grid">
                <?php foreach ($movies as $index => $movie): ?>
                    <div class="movie-card" style="animation-delay: <?= $index * 0.1 ?>s;">
                        <?php if (!empty($movie['poster_url'])): ?>
                            <img src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>"
                                onerror="this.src='Error.php'">
                        <?php else: ?>
                            <div style="width:100%; 
                                height:350px; 
                                background: linear-gradient(45deg, #333, #555); 
                                display:flex; 
                                align-items:center; 
                                justify-content:center; 
                                font-size: 2rem;">
                                🎬 No Image
                            </div>
                        <?php endif; ?>

                        <div class="movie-card-content">
                            <h3><?= htmlspecialchars($movie['title']) ?></h3>
                            <p><i class="fas fa-clock"></i> <strong>Duration:</strong> <?= $movie['duration'] ?> mins</p>
                            <p><i class="fas fa-star"></i> <strong>Rating:</strong> <?= $movie['rating'] ?>/10</p>
                            <p><?= htmlspecialchars(substr($movie['description'], 0, 80)) ?>...</p>
                            <a href="movies.php?movie=<?= $movie['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-ticket-alt"></i> Book Tickets
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section scroll-reveal">
            <h2 class="section-title"><i class="fas fa-history"></i> My Bookings</h2>

            <?php if (empty($bookings)): ?>
                <p style="text-align: center; padding: 40px; font-size: 1.2rem; color: #888;">
                    <i class="fas fa-ticket-alt" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i>
                    You haven't booked any tickets yet.
                </p>
            <?php else: ?>
                <div style="overflow-x: auto;">

                    <table class="bookings-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-film"></i> Movie</th>
                                <th><i class="fas fa-building"></i> Theater</th>
                                <th><i class="fas fa-calendar-alt"></i> Show Date & Time</th>
                                <th><i class="fas fa-chair"></i> Seats</th>
                                <th><i class="fas fa-rupee-sign"></i> Amount</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                                <th><i class="fas fa-cog"></i> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['title']) ?></td>
                                    <td><?= htmlspecialchars($b['theater_name']) ?></td>
                                    <td><?= date("M d, Y", strtotime($b['show_date'])) . " | " . date("h:i A", strtotime($b['show_time'])) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($b['seats_booked']) ?>
                                        <?php if (!empty($b['seat_labels'])): ?>
                                            <div style="font-size: 0.8rem; color: #aaa; margin-top: 4px;">
                                                <?= htmlspecialchars($b['seat_labels']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>₹<?= number_format($b['total_amount'], 2) ?></td>
                                    <td>
                                        <?php if ($b['booking_status'] === 'confirmed'): ?>
                                            <span class="status-confirmed"><i class="fas fa-check-circle"></i> Confirmed</span>
                                        <?php else: ?>
                                            <span class="status-cancelled"><i class="fas fa-times-circle"></i> Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (
                                            $b['booking_status'] === 'confirmed' &&
                                            strtotime($b['show_date'] . ' ' . $b['show_time']) > time()
                                        ): ?>
                                            <form method="POST" action="cancel_booking.php" style="display:inline;"
                                                onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#888;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Scroll reveal animation
        function revealOnScroll() {
            const reveals = document.querySelectorAll('.scroll-reveal');

            reveals.forEach(element => {
                const windowHeight = window.innerHeight;
                const elementTop = element.getBoundingClientRect().top;
                const revealPoint = 150;

                if (elementTop < windowHeight - revealPoint) {
                    element.classList.add('revealed');
                }
            });
        }

        // Counter animation for stats
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');

            counters.forEach(counter => {
                const target = counter.innerText.replace(/[₹,]/g, '');
                const numericTarget = parseFloat(target) || parseInt(target);

                if (!isNaN(numericTarget)) {
                    let count = 0;
                    const increment = numericTarget / 100;
                    const timer = setInterval(() => {
                        count += increment;
                        if (count >= numericTarget) {
                            clearInterval(timer);
                            count = numericTarget;
                        }

                        if (counter.innerText.includes('₹')) {
                            counter.innerText = '₹' + count.toFixed(2);
                        } else {
                            counter.innerText = Math.floor(count);
                        }
                    }, 20);
                }
            });
        }

        // Initialize animations
        window.addEventListener('scroll', revealOnScroll);
        window.addEventListener('load', () => {
            revealOnScroll();
            setTimeout(animateCounters, 1000);
        });

        // Add staggered animation delays to movie cards
        document.querySelectorAll('.movie-card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });

        // Table row hover effects
        document.querySelectorAll('.bookings-table tr').forEach(row => {
            row.addEventListener('mouseenter', function () {
                this.style.transform = 'scale(1.02)';
                this.style.boxShadow = '0 5px 15px rgba(229, 9, 20, 0.3)';
            });

            row.addEventListener('mouseleave', function () {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>

</html>