<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manage_shows.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch the show
$stmt = $pdo->prepare("SELECT * FROM shows WHERE id=?");
$stmt->execute([$id]);
$show = $stmt->fetch();

if (!$show) {
    header("Location: manage_shows.php");
    exit;
}

// Fetch movies and theaters
$movies = $pdo->query("SELECT id, title FROM movies ORDER BY title")->fetchAll();
$theaters = $pdo->query("SELECT id, name FROM theaters ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id = intval($_POST['movie_id']);
    $theater_id = intval($_POST['theater_id']);
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];
    $price = floatval($_POST['price']);
    $total_seats = intval($_POST['total_seats']);

    // Adjust available seats if total_seats changed
    $booked = $show['total_seats'] - $show['available_seats']; // tickets already booked
    $available_seats = max(0, $total_seats - $booked);

    $stmt = $pdo->prepare("
        UPDATE shows
        SET movie_id=?, theater_id=?, show_date=?, show_time=?, price=?, total_seats=?, available_seats=?
        WHERE id=?
    ");
    $stmt->execute([$movie_id, $theater_id, $show_date, $show_time, $price, $total_seats, $available_seats, $id]);

    header("Location: manage_shows.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Show - Cinema Admin</title>
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
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background elements */
        .bg-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        .shape1 {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape2 {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .shape3 {
            width: 100px;
            height: 100px;
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.7;
            }

            25% {
                transform: translateY(-20px) rotate(90deg);
                opacity: 1;
            }

            50% {
                transform: translateY(-40px) rotate(180deg);
                opacity: 0.8;
            }

            75% {
                transform: translateY(-20px) rotate(270deg);
                opacity: 0.9;
            }
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            animation: slideInUp 0.8s ease-out;
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

        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            animation: slideInLeft 0.8s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .back-link:hover {
            color: #fff;
            transform: translateX(-5px);
        }

        .back-link i {
            margin-right: 8px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 1s ease-out 0.2s both;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header h1 {
            color: #fff;
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .header .subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: scaleIn 0.8s ease-out 0.4s both;
            position: relative;
            overflow: hidden;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .form-card:hover::before {
            left: 100%;
        }

        .show-info {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(102, 126, 234, 0.2);
            animation: slideInRight 0.8s ease-out 0.6s both;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .show-info h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 600;
            color: #333;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            opacity: 0;
            animation: slideInUp 0.6s ease-out forwards;
        }

        .form-group:nth-child(1) {
            animation-delay: 0.8s;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.9s;
        }

        .form-group:nth-child(3) {
            animation-delay: 1s;
        }

        .form-group:nth-child(4) {
            animation-delay: 1.1s;
        }

        .form-group:nth-child(5) {
            animation-delay: 1.2s;
        }

        .form-group:nth-child(6) {
            animation-delay: 1.3s;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group label i {
            margin-right: 8px;
            color: #667eea;
            width: 20px;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e6ed;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            position: relative;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-control:hover {
            border-color: #764ba2;
            transform: translateY(-1px);
        }

        .form-control option {
            padding: 10px;
        }

        .price-input {
            position: relative;
        }

        .price-input::before {
            content: '₹';
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-weight: bold;
            z-index: 1;
        }

        .price-input .form-control {
            padding-left: 40px;
        }

        .seats-info {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            padding: 8px 12px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 6px;
            border-left: 3px solid #667eea;
        }

        .btn-submit {
            width: 100%;
            padding: 18px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: bounceIn 0.8s ease-out 1.4s both;
            margin-top: 20px;
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }

            50% {
                transform: scale(1.05);
            }

            70% {
                transform: scale(0.9);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        /* Loading animation */
        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }

        .spinner {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }

            .form-card {
                padding: 25px;
                margin: 10px 0;
            }

            .header h1 {
                font-size: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Success message animation */
        .success-message {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            animation: slideInDown 0.5s ease-out;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>

<body>
    <!-- Animated background elements -->
    <div class="bg-elements">
        <div class="floating-shape shape1"></div>
        <div class="floating-shape shape2"></div>
        <div class="floating-shape shape3"></div>
    </div>

    <div class="container">
        <a href="manage_shows.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Show Management
        </a>

        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> Edit Show</h1>
            <p class="subtitle">Update show details and scheduling information</p>
        </div>

        <div class="form-card">
            <!-- Current show information -->
            <div class="show-info">
                <h3><i class="fas fa-info-circle"></i> Current Show Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Available Seats</div>
                        <div class="info-value"><?= $show['available_seats'] ?> / <?= $show['total_seats'] ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Booked Tickets</div>
                        <div class="info-value"><?= $show['total_seats'] - $show['available_seats'] ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Show Status</div>
                        <div class="info-value">
                            <?= $show['available_seats'] > 0 ? 'Available' : 'Sold Out' ?>
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" id="showForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="movie_id">
                            <i class="fas fa-film"></i>
                            Movie
                        </label>
                        <select name="movie_id" id="movie_id" class="form-control" required>
                            <option value="">Select a movie</option>
                            <?php foreach ($movies as $movie): ?>
                                <option value="<?= $movie['id'] ?>" <?= $movie['id'] == $show['movie_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($movie['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="theater_id">
                            <i class="fas fa-building"></i>
                            Theater
                        </label>
                        <select name="theater_id" id="theater_id" class="form-control" required>
                            <option value="">Select a theater</option>
                            <?php foreach ($theaters as $theater): ?>
                                <option value="<?= $theater['id'] ?>" <?= $theater['id'] == $show['theater_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($theater['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="show_date">
                            <i class="fas fa-calendar"></i>
                            Show Date
                        </label>
                        <input type="date" id="show_date" name="show_date" class="form-control"
                            value="<?= $show['show_date'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="show_time">
                            <i class="fas fa-clock"></i>
                            Show Time
                        </label>
                        <input type="time" id="show_time" name="show_time" class="form-control"
                            value="<?= $show['show_time'] ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">
                            <i class="fas fa-rupee-sign"></i>
                            Ticket Price
                        </label>
                        <div class="price-input">
                            <input type="number" step="0.01" id="price" name="price" class="form-control"
                                value="<?= $show['price'] ?>" placeholder="150.00" min="0" required>
                        </div>
                    </div>

                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i>
                    Update Show
                </button>
            </form>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Updating show...</p>
            </div>
        </div>
    </div>

    <script>
        // Form submission with loading animation
        document.getElementById('showForm').addEventListener('submit', function (e) {
            const submitBtn = document.querySelector('.btn-submit');
            const loading = document.getElementById('loading');

            submitBtn.style.display = 'none';
            loading.style.display = 'block';
        });

        // Add smooth focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function () {
                this.parentNode.style.transform = 'translateX(5px)';
            });

            input.addEventListener('blur', function () {
                this.parentNode.style.transform = 'translateX(0)';
            });
        });

        // Total seats validation and warning
        const totalSeatsInput = document.getElementById('total_seats');
        const currentTotalSeats = <?= $show['total_seats'] ?>;
        const bookedSeats = <?= $show['total_seats'] - $show['available_seats'] ?>;

        totalSeatsInput.addEventListener('input', function () {
            const newTotal = parseInt(this.value);
            const seatsInfo = this.nextElementSibling;

            if (newTotal < bookedSeats) {
                seatsInfo.style.background = 'rgba(220, 53, 69, 0.1)';
                seatsInfo.style.borderColor = '#dc3545';
                seatsInfo.style.color = '#dc3545';
                seatsInfo.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Warning: Total seats cannot be less than already booked seats (' + bookedSeats + ')';
                this.setCustomValidity('Total seats must be at least ' + bookedSeats);
            } else {
                seatsInfo.style.background = 'rgba(102, 126, 234, 0.1)';
                seatsInfo.style.borderColor = '#667eea';
                seatsInfo.style.color = '#666';
                const newAvailable = newTotal - bookedSeats;
                seatsInfo.innerHTML = '<i class="fas fa-info-circle"></i> Available seats will be: ' + newAvailable;
                this.setCustomValidity('');
            }
        });

        // Add date validation (no past dates)
        const dateInput = document.getElementById('show_date');
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;

        // Enhanced select animations
        document.querySelectorAll('select.form-control').forEach(select => {
            select.addEventListener('change', function () {
                this.style.background = 'rgba(102, 126, 234, 0.05)';
                setTimeout(() => {
                    this.style.background = 'rgba(255, 255, 255, 0.9)';
                }, 300);
            });
        });

        // Price input formatting
        const priceInput = document.getElementById('price');
        priceInput.addEventListener('input', function () {
            const value = parseFloat(this.value);
            if (value && value > 0) {
                this.style.color = '#28a745';
            } else {
                this.style.color = '#333';
            }
        });

        // Add typing effect for placeholders
        const inputs = document.querySelectorAll('input[placeholder]');
        inputs.forEach(input => {
            const placeholder = input.getAttribute('placeholder');
            input.setAttribute('placeholder', '');

            let i = 0;
            const typeInterval = setInterval(() => {
                input.setAttribute('placeholder', placeholder.substring(0, i));
                i++;
                if (i > placeholder.length) {
                    clearInterval(typeInterval);
                }
            }, 100);
        });
    </script>
</body>

</html>