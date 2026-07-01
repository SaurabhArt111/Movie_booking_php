<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Handle login
if ($_POST['action'] ?? '' === 'login') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            redirect('admin/dashboard.php');
        } else {
            redirect('index.php');
        }
    } else {
        showAlert('Invalid username or password!', 'error');
    }
}

// Handle registration
if ($_POST['action'] ?? '' === 'register') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $full_name, $phone]);
        showAlert('Registration successful! Please login.', 'success');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            showAlert('Username or email already exists!', 'error');
        } else {
            showAlert('Registration failed!', 'error');
        }
    }
}

// Fetch movies
$stmt = $pdo->query("SELECT * FROM movies WHERE status='active' ORDER BY created_at DESC LIMIT 12");
$movies = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieBook - Premium Cinema Experience</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #ff0080;
            --secondary: #7928ca;
            --accent: #00d4ff;
            --dark: #0a0a0f;
            --darker: #050508;
            --light: #ffffff;
            --gray: #8b8b9a;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--dark);
            color: var(--light);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Animated Background */
        .bg-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.15;
        }

        .gradient-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.5;
            animation: float 20s infinite ease-in-out;
            z-index: -1;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--primary), transparent);
            top: -200px;
            left: -200px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--secondary), transparent);
            top: 50%;
            right: -150px;
            animation-delay: 7s;
        }

        .orb-3 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, var(--accent), transparent);
            bottom: -100px;
            left: 30%;
            animation-delay: 14s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(100px, -100px) scale(1.1);
            }

            66% {
                transform: translate(-50px, 100px) scale(0.9);
            }
        }

        /* Progress Bar */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            z-index: 9999;
            transition: width 0.1s ease;
            box-shadow: 0 0 20px var(--primary);
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 1.5rem 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar.scrolled {
            background: rgba(10, 10, 15, 0.9);
            backdrop-filter: blur(20px);
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar.hidden {
            transform: translateY(-100%);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo i {
            -webkit-text-fill-color: var(--primary);
            animation: spin 10s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            position: relative;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--light);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-btn {
            padding: 0.7rem 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: transparent;
            color: var(--light);
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .nav-btn:hover {
            border-color: var(--primary);
            box-shadow: 0 0 20px rgba(255, 0, 128, 0.3);
            transform: translateY(-2px);
        }

        .nav-btn.primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
        }

        .nav-btn.primary:hover {
            box-shadow: 0 5px 25px rgba(255, 0, 128, 0.5);
            transform: translateY(-2px) scale(1.05);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 8rem 2rem 4rem;
        }

        .hero-content {
            max-width: 900px;
            text-align: center;
            z-index: 2;
            animation: fadeInUp 1s ease;
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

        .hero h1 {
            font-size: clamp(3rem, 8vw, 5.5rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--light), var(--gray));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -2px;
            animation: fadeInUp 1s ease 0.2s backwards;
        }

        .hero p {
            font-size: 1.3rem;
            color: var(--gray);
            margin-bottom: 3rem;
            line-height: 1.8;
            animation: fadeInUp 1s ease 0.4s backwards;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease 0.6s backwards;
        }

        .btn {
            padding: 1.2rem 2.5rem;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn span,
        .btn i {
            position: relative;
            z-index: 1;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 10px 40px rgba(255, 0, 128, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 50px rgba(255, 0, 128, 0.5);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--accent);
            box-shadow: 0 10px 40px rgba(0, 212, 255, 0.3);
        }

        /* Movies Section */
        .movies-section {
            padding: 6rem 2rem;
            position: relative;
        }

        .section-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            background: linear-gradient(135deg, var(--light), var(--gray));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .trending-badge {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, rgba(255, 0, 128, 0.2), rgba(121, 40, 202, 0.2));
            border: 2px solid var(--primary);
            border-radius: 50px;
            font-weight: 700;
            animation: pulse 2s infinite;
        }

        .fire-icon {
            color: var(--primary);
            font-size: 1.3rem;
            animation: flame 1.5s infinite;
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

        @keyframes flame {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2.5rem;
        }

        .movie-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeIn 0.6s ease backwards;
            animation-play-state: paused;
        }

        .movie-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .movie-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .movie-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .movie-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        .movie-card:nth-child(5) {
            animation-delay: 0.5s;
        }

        .movie-card:nth-child(6) {
            animation-delay: 0.6s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .movie-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: var(--primary);
            box-shadow: 0 20px 60px rgba(255, 0, 128, 0.3);
            background: rgba(255, 255, 255, 0.05);
        }

        .movie-poster {
            position: relative;
            width: 100%;
            height: 400px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(255, 0, 128, 0.1), rgba(121, 40, 202, 0.1));
        }

        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .movie-card:hover .movie-poster img {
            transform: scale(1.1);
        }

        .movie-info {
            padding: 1.5rem;
        }

        .movie-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            color: var(--light);
            line-height: 1.3;
        }

        .movie-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .movie-rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #ffd700;
            font-weight: 700;
        }

        .movie-description {
            color: var(--gray);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            padding: 8rem 2rem;
            background: linear-gradient(135deg, rgba(255, 0, 128, 0.1), rgba(0, 212, 255, 0.1));
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .cta-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--light), var(--gray));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .cta-description {
            font-size: 1.2rem;
            color: var(--gray);
            margin-bottom: 2.5rem;
            line-height: 1.8;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }

        .modal-content {
            background: rgba(20, 20, 30, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            padding: 3rem;
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: modalSlide 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
        }

        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: var(--light);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: var(--primary);
            transform: rotate(90deg);
        }

        .modal-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--gray);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1.2rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            color: var(--light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 20px rgba(255, 0, 128, 0.2);
        }

        .modal-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray);
        }

        .modal-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .modal-link a:hover {
            color: var(--accent);
        }

        /* Alert */
        .alert {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: rgba(20, 20, 30, 0.98);
            backdrop-filter: blur(20px);
            padding: 1.5rem 2.5rem;
            border-radius: 20px;
            border: 2px solid var(--primary);
            color: var(--light);
            font-weight: 600;
            z-index: 20000;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: alertPop 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes alertPop {
            from {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.8);
            }

            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        /* Loading */
        .loading {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--light);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* Footer */
        .footer {
            background: rgba(10, 10, 15, 0.8);
            backdrop-filter: blur(20px);
            padding: 3rem 2rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-links {
            max-width: 1400px;
            margin: 0 auto 2rem;
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--gray);
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            text-align: center;
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                gap: 1rem;
            }

            .nav-links a {
                display: none;
            }

            .hero {
                padding: 6rem 1.5rem 3rem;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .hero-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .movies-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .modal-content {
                padding: 2rem;
                margin: 1rem;
            }

            .footer-links {
                gap: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Animated Background -->
    <div class="gradient-orb orb-1"></div>
    <div class="gradient-orb orb-2"></div>
    <div class="gradient-orb orb-3"></div>

    <!-- Progress Bar -->
    <div class="progress-bar" id="progressBar"></div>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="logo" onclick="scrollToTop()">
                <i class="fas fa-film"></i> MovieBook
            </div>
            <div class="nav-links">
                <?php if (isLoggedIn()): ?>
                    <a href="index.php">Home</a>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="movies.php">Book Show</a>
                    <a href="profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="index.php">Home</a>
                    <a href="#trending">Trending</a>
                    <button class="nav-btn" onclick="openModal('loginModal')">Login</button>
                    <button class="nav-btn primary" onclick="openModal('registerModal')">Sign Up</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>
                <?= isLoggedIn() ? 'Welcome Back, ' . htmlspecialchars($_SESSION['full_name']) : 'Cinematic Excellence Awaits' ?>
            </h1>
            <p>
                <?= isLoggedIn() ? 'Your next movie adventure is just a click away. Discover and book premium cinema experiences.' : 'Immerse yourself in a world of premium entertainment. Book tickets, explore movies, and create unforgettable memories.' ?>
            </p>
            <div class="hero-buttons">
                <?php if (isLoggedIn()): ?>
                    <a href="movies.php" class="btn btn-primary">
                        <i class="fas fa-ticket-alt"></i> Book Tickets
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-chart-line"></i> My Dashboard
                    </a>
                <?php else: ?>
                    <button onclick="openModal('registerModal')" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Start Journey
                    </button>
                    <button onclick="openModal('loginModal')" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Movies Section -->
    <section class="movies-section" id="trending">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title">Trending Now</h2>
                <div class="trending-badge">
                    <i class="fas fa-fire fire-icon"></i>
                    Hot Picks
                </div>
            </div>

            <div class="movies-grid">
                <?php foreach ($movies as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <img src="<?= htmlspecialchars($movie['poster_url']) ?>"
                                alt="<?= htmlspecialchars($movie['title']) ?>" onerror="this.src='Error.php'">
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>
                            <div class="movie-meta">
                                <span><?= htmlspecialchars($movie['genre']) ?></span>
                                <div class="movie-rating">
                                    <i class="fas fa-star"></i>
                                    <?= number_format($movie['rating'], 1) ?>
                                </div>
                            </div>
                            <p class="movie-description">
                                <?= htmlspecialchars(substr($movie['description'], 0, 100)) ?>...
                            </p>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #4CAF50; font-weight: 600;">
                                    <?= $movie['duration'] ?> mins
                                </span>
                                <?php if (isLoggedIn()): ?>
                                    <a href="movies.php?movie=<?= $movie['id'] ?>" class="btn btn-primary"
                                        style="padding: 0.7rem 1.5rem; font-size: 0.95rem;">
                                        <i class="fas fa-ticket-alt"></i> Book Now
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-primary" style="padding: 0.7rem 1.5rem; font-size: 0.95rem;"
                                        onclick="showLoginAlert()">
                                        <i class="fas fa-ticket-alt"></i> Book Now
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2 class="cta-title">Experience Cinema Like Never Before</h2>
            <p class="cta-description">
                Join millions of movie enthusiasts worldwide. Discover blockbusters, indie gems, and everything in
                between. Your next great cinematic adventure starts here.
            </p>
            <?php if (!isLoggedIn()): ?>
                <button onclick="openModal('registerModal')" class="btn btn-primary">
                    <i class="fas fa-star"></i> Join MovieBook Today
                </button>
            <?php endif; ?>
        </div>
    </section>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('loginModal')">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="modal-title">Welcome Back</h2>
            <?php displayAlert(); ?>
            <form method="POST" onsubmit="showLoading(this)">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="login_username">Username or Email</label>
                    <input type="text" id="login_username" name="username" placeholder="Enter your credentials"
                        required>
                </div>
                <div class="form-group">
                    <label for="login_password">Password</label>
                    <input type="password" id="login_password" name="password" placeholder="Enter your password"
                        required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            <p class="modal-link">
                New to MovieBook? <a href="#" onclick="switchModal('loginModal', 'registerModal')">Create account</a>
            </p>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('registerModal')">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="modal-title">Join MovieBook</h2>
            <?php displayAlert(); ?>
            <form method="POST" onsubmit="showLoading(this)">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label for="reg_fullname">Full Name</label>
                    <input type="text" id="reg_fullname" name="full_name" placeholder="Your full name" required>
                </div>
                <div class="form-group">
                    <label for="reg_username">Username</label>
                    <input type="text" id="reg_username" name="username" placeholder="Choose a username" required>
                </div>
                <div class="form-group">
                    <label for="reg_email">Email Address</label>
                    <input type="email" id="reg_email" name="email" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label for="reg_phone">Phone Number</label>
                    <input type="tel" id="reg_phone" name="phone" placeholder="Your phone number">
                </div>
                <div class="form-group">
                    <label for="reg_password">Password</label>
                    <input type="password" id="reg_password" name="password" placeholder="Create a secure password"
                        required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            <p class="modal-link">
                Already have an account? <a href="#" onclick="switchModal('registerModal', 'loginModal')">Sign in</a>
            </p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-links">
            <a href="#">About Us</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Contact</a>
            <a href="#">Help Center</a>
            <a href="#">Careers</a>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> MovieBook. All rights reserved. Elevating your cinema experience.</p>
        </div>
    </footer>

    <script>
        // Progress Bar
        function updateProgressBar() {
            const scrollTop = window.pageYOffset;
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = (scrollTop / scrollHeight) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
        }

        // Navbar Scroll Effect
        let lastScrollTop = 0;
        function handleNavbarScroll() {
            const navbar = document.getElementById('navbar');
            const scrollTop = window.pageYOffset;

            if (scrollTop > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            if (scrollTop > lastScrollTop && scrollTop > 200) {
                navbar.classList.add('hidden');
            } else {
                navbar.classList.remove('hidden');
            }

            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        }

        // Combined scroll handler
        window.addEventListener('scroll', function () {
            updateProgressBar();
            handleNavbarScroll();
        });

        // Modal Functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
            document.body.style.overflow = 'hidden';

            setTimeout(() => {
                const firstInput = modal.querySelector('input:not([type="hidden"])');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';

            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function switchModal(closeId, openId) {
            closeModal(closeId);
            setTimeout(() => openModal(openId), 300);
        }

        // Alert Functions
        function showLoginAlert() {
            const alert = document.createElement('div');
            alert.className = 'alert';
            alert.innerHTML = '<i class="fas fa-lock"></i> Please sign in to book tickets';
            document.body.appendChild(alert);

            setTimeout(() => {
                alert.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 3000);
        }

        function showLoading(form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading"></div>';
            submitBtn.disabled = true;

            setTimeout(() => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }, 5000);
        }

        // Scroll to top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Close modal on outside click
        window.addEventListener('click', function (e) {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target.id);
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    closeModal(openModal.id);
                }
            }
        });

        // Smooth scroll for anchor links
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

        // Intersection Observer for animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, { threshold: 0.1 });

        // Observe movie cards for animation
        document.querySelectorAll('.movie-card').forEach(card => {
            observer.observe(card);
        });

        // Add fadeOut keyframe for alerts
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                to { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
            }
        `;
        document.head.appendChild(style);

        // Initialize page
        document.addEventListener('DOMContentLoaded', function () {
            const heroContent = document.querySelector('.hero-content');
            if (heroContent) {
                heroContent.style.opacity = '0';
                setTimeout(() => {
                    heroContent.style.transition = 'opacity 1s ease';
                    heroContent.style.opacity = '1';
                }, 100);
            }
        });
    </script>
</body>

</html>