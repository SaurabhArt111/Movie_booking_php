<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$movie_id = $_GET['movie'] ?? null;

// Handle booking
if ($_POST['action'] ?? '' === 'book_ticket') {
    $show_id = (int) $_POST['show_id'];
    $seats = (int) $_POST['seats'];

    if ($seats <= 0) {
        showAlert('Please select valid number of seats!', 'error');
    } else {
        // Get show details
        $stmt = $pdo->prepare("SELECT * FROM shows WHERE id = ?");
        $stmt->execute([$show_id]);
        $show = $stmt->fetch();

        if ($show && $show['available_seats'] >= $seats) {
            $total_amount = $show['price'] * $seats;

            try {
                $pdo->beginTransaction();

                // Create booking
                $stmt = $pdo->prepare("INSERT INTO bookings (user_id, show_id, seats_booked, total_amount) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $show_id, $seats, $total_amount]);

                // Update available seats
                $stmt = $pdo->prepare("UPDATE shows SET available_seats = available_seats - ? WHERE id = ?");
                $stmt->execute([$seats, $show_id]);

                $pdo->commit();
                showAlert("Booking successful! Total amount: ₹" . number_format($total_amount, 2), 'success');
            } catch (Exception $e) {
                $pdo->rollBack();
                showAlert('Booking failed. Please try again.', 'error');
            }
        } else {
            showAlert('Not enough seats available!', 'error');
        }
    }
}

// Get movies
if ($movie_id) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ? AND status = 'active'");
    $stmt->execute([$movie_id]);
    $movie = $stmt->fetch();

    if ($movie) {
        // Get shows for this movie
        $stmt = $pdo->prepare("
            SELECT s.*, t.name as theater_name, t.location 
            FROM shows s 
            JOIN theaters t ON s.theater_id = t.id 
            WHERE s.movie_id = ? AND s.show_date >= CURDATE()
            ORDER BY s.show_date, s.show_time
        ");
        $stmt->execute([$movie_id]);
        $shows = $stmt->fetchAll();
    }
} else {
    $stmt = $pdo->query("SELECT * FROM movies WHERE status = 'active' ORDER BY title");
    $movies = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies - MovieBook</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0a0a;
            color: #fff;
            overflow-x: hidden;
            position: relative;
        }

        /* DYNAMIC BACKGROUND */
        .background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            z-index: -2;
        }

        .background-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.1;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #ff6b35;
            border-radius: 50%;
            animation: particleDrift 10s infinite linear;
        }

        @keyframes particleDrift {
            from {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            to {
                transform: translateY(-10px) translateX(50px);
                opacity: 0;
            }
        }

        /* NAVBAR */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 107, 53, 0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(0, 0, 0, 0.95);
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.5);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #ff6b35;
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.5);
            animation: logoGlow 3s ease-in-out infinite alternate;
        }

        @keyframes logoGlow {
            from {
                text-shadow: 0 0 20px rgba(255, 107, 53, 0.5);
            }

            to {
                text-shadow: 0 0 30px rgba(255, 107, 53, 0.8);
            }
        }

        .nav-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-menu a {
            color: #fff;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
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
            transition: left 0.6s;
        }

        .nav-menu a:hover::before {
            left: 100%;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.4);
        }

        /* MAIN CONTAINER */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 100px 40px 40px;
            min-height: 100vh;
        }

        /* HERO SECTION FOR SINGLE MOVIE */
        .movie-hero {
            position: relative;
            min-height: 70vh;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 50%, #2c3e50 100%);
            border-radius: 25px;
            margin-bottom: 50px;
            overflow: hidden;
            display: flex;
            align-items: center;
            animation: heroSlideIn 1s ease-out;
        }

        @keyframes heroSlideIn {
            from {
                opacity: 0;
                transform: translateY(50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .movie-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(255, 107, 53, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(52, 152, 219, 0.3) 0%, transparent 50%);
            animation: backgroundShift 8s ease-in-out infinite;
        }

        @keyframes backgroundShift {

            0%,
            100% {
                transform: scale(1) rotate(0deg);
            }

            50% {
                transform: scale(1.1) rotate(2deg);
            }
        }

        .movie-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 60px;
            width: 100%;
            padding: 60px;
            z-index: 1;
            position: relative;
        }

        .movie-poster-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .movie-poster-frame {
            width: 300px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 2px solid rgba(255, 107, 53, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            animation: posterFloat 6s ease-in-out infinite;
        }

        @keyframes posterFloat {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .movie-poster-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 18px;
        }

        .movie-info {
            animation: fadeInRight 1.2s ease-out;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .movie-title {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            background: linear-gradient(45deg, #fff, #ff6b35);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(255, 107, 53, 0.5);
        }

        .movie-meta {
            display: flex;
            gap: 30px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .meta-item {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 25px;
            border-radius: 15px;
            border: 1px solid rgba(255, 107, 53, 0.2);
            text-align: center;
            transition: all 0.3s ease;
        }

        .meta-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(255, 107, 53, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }

        .meta-label {
            font-size: 0.8rem;
            color: #ff6b35;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 1.1rem;
            font-weight: bold;
        }

        .movie-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 30px;
        }

        /* BACK BUTTON */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: #fff;
            text-decoration: none;
            padding: 15px 25px;
            border-radius: 15px;
            font-weight: 600;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 107, 53, 0.2);
        }

        .back-button:hover {
            transform: translateX(-5px);
            box-shadow: 0 10px 30px rgba(52, 73, 94, 0.4);
            background: linear-gradient(135deg, #2c3e50, #34495e);
        }

        /* SECTION STYLING */
        .section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 107, 53, 0.1);
            animation: sectionFadeIn 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.1), transparent);
            transition: left 1.5s;
        }

        .section:hover::before {
            left: 100%;
        }

        @keyframes sectionFadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-title {
            font-size: 2.5rem;
            margin-bottom: 30px;
            color: #fff;
            text-align: center;
            background: linear-gradient(45deg, #fff, #ff6b35);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #ff6b35, #f7931e);
            border-radius: 2px;
        }

        /* MOVIES GRID */
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            animation: gridFadeIn 1s ease-out;
        }

        @keyframes gridFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .movie-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255, 107, 53, 0.2);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .movie-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.1), transparent);
            transition: left 0.8s;
        }

        .movie-card:hover::before {
            left: 100%;
        }

        .movie-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4), 0 0 40px rgba(255, 107, 53, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }

        .movie-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .movie-card p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        /* SHOWS GRID */
        .shows-grid {
            display: grid;
            gap: 25px;
        }

        .show-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 107, 53, 0.2);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .show-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.1), transparent);
            transition: left 1s;
        }

        .show-card:hover::before {
            left: 100%;
        }

        .show-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3), 0 0 30px rgba(255, 107, 53, 0.2);
            background: rgba(255, 255, 255, 0.12);
        }

        .show-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .theater-info h3 {
            font-size: 1.4rem;
            color: #fff;
            margin-bottom: 5px;
        }

        .theater-location {
            color: #ff6b35;
            font-weight: 600;
        }

        .price-tag {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: #fff;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
            box-shadow: 0 5px 20px rgba(255, 107, 53, 0.4);
        }

        .show-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .detail-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255, 107, 53, 0.1);
        }

        .detail-label {
            font-size: 0.8rem;
            color: #ff6b35;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: #fff;
        }

        /* BOOKING FORM */
        .booking-section {
            background: rgba(255, 107, 53, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            border: 1px solid rgba(255, 107, 53, 0.3);
        }

        .booking-form {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-weight: 600;
            color: #ff6b35;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid rgba(255, 107, 53, 0.3);
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            font-size: 16px;
            width: 100px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
            background: rgba(0, 0, 0, 0.7);
        }

        /* BUTTONS */
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 15px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(52, 152, 219, 0.4);
            background: linear-gradient(135deg, #2980b9, #3498db);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: #fff;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(39, 174, 96, 0.4);
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: #fff;
            border: 1px solid rgba(149, 165, 166, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(149, 165, 166, 0.4);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* ALERTS */
        .alert {
            padding: 20px;
            margin: 20px 0;
            border-radius: 15px;
            font-weight: 600;
            border: 1px solid;
            animation: alertSlideIn 0.5s ease-out;
        }

        @keyframes alertSlideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            border-color: rgba(39, 174, 96, 0.3);
            color: #fff;
        }

        .alert-error {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border-color: rgba(231, 76, 60, 0.3);
            color: #fff;
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 768px) {
            .nav-container {
                padding: 15px 20px;
            }

            .nav-menu {
                gap: 15px;
            }

            .nav-menu a {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            .container {
                padding: 100px 20px 40px;
            }

            .movie-content {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 40px 20px;
            }

            .movie-title {
                font-size: 2.5rem;
            }

            .movie-meta {
                justify-content: center;
            }

            .movies-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }

            .booking-form {
                justify-content: center;
            }
        }

        /* LOADING ANIMATION */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #ff6b35;
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
    <!-- DYNAMIC BACKGROUND -->
    <div class="background-overlay"></div>
    <div class="background-particles" id="particles"></div>

    <!-- NAVBAR -->
    <div class="navbar" id="navbar">
        <div class="nav-container">
            <div class="logo">🎬 MOVIEBOOK</div>
            <div class="nav-menu">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="dashboard.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="movies.php" class="active"><i class="fas fa-film"></i> Browse Movies</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php displayAlert(); ?>

        <?php if ($movie_id && isset($movie)): ?>
            <a href="movies.php" class="back-button">
                ← Back to Movies
            </a>

            <!-- MOVIE HERO SECTION -->
            <div class="movie-hero">
                <div class="movie-content">
                    <div class="movie-poster-section">
                        <div class="movie-poster-frame">
                            <?php if (!empty($movie['poster_url'])): ?>
                                <img src="<?= htmlspecialchars($movie['poster_url']) ?>"
                                    alt="<?= htmlspecialchars($movie['title']) ?>">
                            <?php else: ?>
                                <div style="text-align: center; color: #ff6b35;">
                                    <div style="font-size: 3rem; margin-bottom: 10px;">🎬</div>
                                    <div>Movie Poster</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="movie-info">
                        <h1 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h1>

                        <div class="movie-meta">
                            <div class="meta-item">
                                <div class="meta-label">Genre</div>
                                <div class="meta-value"><?= htmlspecialchars($movie['genre']) ?></div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Duration</div>
                                <div class="meta-value"><?= $movie['duration'] ?> min</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Rating</div>
                                <div class="meta-value">⭐ <?= $movie['rating'] ?>/10</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Release</div>
                                <div class="meta-value"><?= date('Y', strtotime($movie['release_date'])) ?></div>
                            </div>
                        </div>

                        <div class="movie-description">
                            <h3 style="color: #ff6b35; margin-bottom: 15px;">ABOUT THE MOVIE</h3>
                            <p><?= htmlspecialchars($movie['description']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SHOWS SECTION -->
            <div class="section">
                <h2 class="section-title">🎟️ AVAILABLE SHOWS</h2>
                <?php if (empty($shows)): ?>
                    <div style="text-align: center; padding: 60px; color: rgba(255,255,255,0.7);">
                        <div style="font-size: 4rem; margin-bottom: 20px;">🎭</div>
                        <h3>No Shows Available</h3>
                        <p>Check back later for upcoming showtimes</p>
                    </div>
                <?php else: ?>
                    <div class="shows-grid">
                        <?php foreach ($shows as $show): ?>
                            <div class="show-card">
                                <div class="show-header">
                                    <div class="theater-info">
                                        <h3><?= htmlspecialchars($show['theater_name']) ?></h3>
                                        <div class="theater-location">📍 <?= htmlspecialchars($show['location']) ?></div>
                                    </div>
                                    <div class="price-tag">₹<?= number_format($show['price'], 2) ?></div>
                                </div>

                                <div class="show-details">
                                    <div class="detail-item">
                                        <div class="detail-label">Date</div>
                                        <div class="detail-value"><?= date('M d, Y', strtotime($show['show_date'])) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Time</div>
                                        <div class="detail-value"><?= date('h:i A', strtotime($show['show_time'])) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Available Seats</div>
                                        <div class="detail-value"><?= $show['available_seats'] ?></div>
                                    </div>
                                </div>

                                <?php if ($show['available_seats'] > 0): ?>
                                    <div class="booking-section">
                                        <form method="POST" class="booking-form">
                                            <input type="hidden" name="action" value="book_ticket">
                                            <input type="hidden" name="show_id" value="<?= $show['id'] ?>">
                                            <div class="form-group">
                                                <label class="form-label">Seats</label>
                                                <input type="number" name="seats" min="1" max="<?= min(10, $show['available_seats']) ?>"
                                                    value="1" class="form-control">
                                            </div>
                                            <button type="submit" class="btn btn-success">🎫 Book Now</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="booking-section">
                                        <button class="btn btn-secondary" disabled>❌ Sold Out</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- MOVIES BROWSE SECTION -->
            <div class="section">
                <h2 class="section-title">🎬 AVAILABLE MOVIES</h2>
                <div class="movies-grid">
                    <?php foreach ($movies as $movie): ?>
                        <div class="movie-card" style="background-image: url();">
                            <h3><?= htmlspecialchars($movie['title']) ?></h3>
                            <div style="margin: 20px 0;">
                                <p><strong>🎭 Genre:</strong> <?= htmlspecialchars($movie['genre']) ?></p>
                                <p><strong>⏱️ Duration:</strong> <?= $movie['duration'] ?> minutes</p>
                                <p><strong>⭐ Rating:</strong> <?= $movie['rating'] ?>/10</p>
                                <p><strong>📅 Released:</strong> <?= date('M Y', strtotime($movie['release_date'])) ?></p>
                            </div>
                            <div style="margin: 20px 0; color: rgba(255,255,255,0.8); font-size: 0.9rem;">
                                <?= htmlspecialchars(substr($movie['description'], 0, 120)) ?>...
                            </div>
                            <a href="movies.php?movie=<?= $movie['id'] ?>" class="btn btn-primary">🎬 View Shows</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // PARTICLE SYSTEM
        function createParticles() {
            const particleContainer = document.getElementById('particles');

            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 10 + 's';
                particle.style.animationDuration = (Math.random() * 5 + 5) + 's';
                particleContainer.appendChild(particle);
            }
        }

        // NAVBAR SCROLL EFFECT
        window.addEventListener('scroll', function () {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // INTERSECTION OBSERVER FOR ANIMATIONS
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'sectionFadeIn 0.8s ease-out forwards';
                }
            });
        }, observerOptions);

        // FORM LOADING STATES
        function handleFormSubmit(form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<div class="loading"></div> Processing...';
                submitBtn.disabled = true;

                // Re-enable after 3 seconds (fallback)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        }

        // SMOOTH SCROLLING
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

        // DYNAMIC SEAT VALIDATION
        function validateSeats(input) {
            const max = parseInt(input.getAttribute('max'));
            const value = parseInt(input.value);

            if (value > max) {
                input.value = max;
            } else if (value < 1) {
                input.value = 1;
            }

            // Update total price display if exists
            updateTotalPrice(input);
        }

        function updateTotalPrice(seatInput) {
            const showCard = seatInput.closest('.show-card');
            const priceTag = showCard.querySelector('.price-tag');
            const seats = parseInt(seatInput.value);

            if (priceTag) {
                const priceText = priceTag.textContent;
                const price = parseFloat(priceText.replace('₹', '').replace(',', ''));
                const total = price * seats;

                // Create or update total display
                let totalDisplay = showCard.querySelector('.total-display');
                if (!totalDisplay) {
                    totalDisplay = document.createElement('div');
                    totalDisplay.className = 'total-display';
                    totalDisplay.style.cssText = `
                        margin-top: 10px;
                        padding: 10px;
                        background: rgba(255, 107, 53, 0.2);
                        border-radius: 8px;
                        text-align: center;
                        font-weight: bold;
                        color: #ff6b35;
                    `;
                    seatInput.closest('.form-group').appendChild(totalDisplay);
                }
                totalDisplay.textContent = `Total: ₹${total.toFixed(2)}`;
            }
        }

        // INITIALIZE
        document.addEventListener('DOMContentLoaded', function () {
            // Create particles
            createParticles();

            // Observe sections for animations
            document.querySelectorAll('.section, .movie-card, .show-card').forEach(element => {
                observer.observe(element);
            });

            // Add form submit handlers
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    handleFormSubmit(form);
                });
            });

            // Add seat validation
            document.querySelectorAll('input[name="seats"]').forEach(input => {
                input.addEventListener('input', function () {
                    validateSeats(this);
                });

                // Initialize total price display
                updateTotalPrice(input);
            });

            // Add hover effects to movie cards
            document.querySelectorAll('.movie-card').forEach(card => {
                card.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-15px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add ripple effect to buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function (e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 600ms linear;
                        background-color: rgba(255, 255, 255, 0.6);
                        left: ${x}px;
                        top: ${y}px;
                        width: ${size}px;
                        height: ${size}px;
                    `;

                    this.appendChild(ripple);

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .btn {
                position: relative;
                overflow: hidden;
            }
        `;
        document.head.appendChild(style);

        // PERFORMANCE OPTIMIZATION
        let ticking = false;

        function updateOnScroll() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                requestAnimationFrame(updateOnScroll);
                ticking = true;
            }
        });
    </script>
</body>

</html>