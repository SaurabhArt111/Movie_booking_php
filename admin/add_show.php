<?php
require_once '../config.php';
requireAdmin($pdo);

// Fetch movies and theaters
$movies = $pdo->query("SELECT id, title FROM movies ORDER BY title")->fetchAll();
$theaters = $pdo->query("SELECT id, name FROM theaters ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $movie_id = intval($_POST['movie_id']);
    $theater_id = intval($_POST['theater_id']);
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];
    $price = floatval($_POST['price']);
    $total_seats = intval($_POST['total_seats']);

    $validMovie = in_array($movie_id, array_column($movies, 'id'));
    $validTheater = in_array($theater_id, array_column($theaters, 'id'));
    $validDate = (bool) DateTime::createFromFormat('Y-m-d', $show_date);
    $validTime = (bool) DateTime::createFromFormat('H:i', $show_time) || (bool) DateTime::createFromFormat('H:i:s', $show_time);

    if (!$validMovie || !$validTheater) {
        $error = 'Please choose a valid movie and theater.';
    } elseif (!$validDate || !$validTime) {
        $error = 'Please enter a valid date and time.';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than zero.';
    } elseif ($total_seats <= 0) {
        $error = 'Total seats must be greater than zero.';
    } else {
        // Initially, available seats = total seats
        $available_seats = $total_seats;

        $stmt = $pdo->prepare("
            INSERT INTO shows (movie_id, theater_id, show_date, show_time, price, total_seats, available_seats)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$movie_id, $theater_id, $show_date, $show_time, $price, $total_seats, $available_seats]);

        header("Location: manage_shows.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Show - Cinema Management</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            animation: backgroundShift 10s ease-in-out infinite;
        }

        @keyframes backgroundShift {

            0%,
            100% {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }

            50% {
                background: linear-gradient(135deg, #764ba2 0%, #f093fb 100%);
            }
        }

        .container {
            max-width: 550px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            transform: translateY(30px);
            opacity: 0;
            animation: slideIn 0.8s ease-out forwards;
        }

        @keyframes slideIn {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h2 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            animation: fadeInDown 1s ease-out 0.2s both;
        }

        .header p {
            color: #666;
            font-size: 14px;
            animation: fadeInDown 1s ease-out 0.4s both;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 25px;
            animation: fadeInUp 1s ease-out both;
        }

        .form-group:nth-child(1) {
            animation-delay: 0.1s;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.2s;
        }

        .form-group:nth-child(3) {
            animation-delay: 0.3s;
        }

        .form-group:nth-child(4) {
            animation-delay: 0.4s;
        }

        .form-group:nth-child(5) {
            animation-delay: 0.5s;
        }

        .form-group:nth-child(6) {
            animation-delay: 0.6s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            position: relative;
        }

        label::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            opacity: 0;
            animation: dotAppear 0.5s ease-out forwards;
        }

        @keyframes dotAppear {
            to {
                opacity: 1;
                left: -15px;
            }
        }

        input,
        select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        input:hover,
        select:hover {
            border-color: #999;
            transform: translateY(-1px);
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
            animation: fadeInUp 1s ease-out 0.7s both;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .icon {
            display: inline-block;
            margin-right: 8px;
            animation: bounce 2s infinite;
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

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-element {
            position: absolute;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 60%;
            left: 85%;
            animation-delay: -2s;
        }

        .floating-element:nth-child(3) {
            top: 80%;
            left: 20%;
            animation-delay: -4s;
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

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
                margin: 10px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .header h2 {
                font-size: 24px;
            }
        }

        /* Loading animation for form submission */
        .loading {
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>

    <div class="container">
        <div class="header">
            <h2><span class="icon">🎬</span>Add New Show</h2>
            <p>Schedule a new movie show for your theater</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: linear-gradient(45deg, #e74c3c, #c0392b); color: #fff; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                ⚠️ <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" id="showForm">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="movie_id">🍿 Movie</label>
                <select name="movie_id" id="movie_id" required>
                    <option value="">Select a movie...</option>
                    <?php foreach ($movies as $movie): ?>
                        <option value="<?= $movie['id'] ?>"><?= htmlspecialchars($movie['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="theater_id">🏛️ Theater</label>
                <select name="theater_id" id="theater_id" required>
                    <option value="">Select a theater...</option>
                    <?php foreach ($theaters as $theater): ?>
                        <option value="<?= $theater['id'] ?>"><?= htmlspecialchars($theater['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="show_date">📅 Date</label>
                    <input type="date" name="show_date" id="show_date" required min="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label for="show_time">🕐 Time</label>
                    <input type="time" name="show_time" id="show_time" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">💰 Price (₹)</label>
                    <input type="number" step="0.01" name="price" id="price" required min="0" placeholder="150.00">
                </div>

                <div class="form-group">
                    <label for="total_seats">🪑 Total Seats</label>
                    <input type="number" name="total_seats" id="total_seats" value="100" required min="1" max="500">
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <span class="btn-text">🎭 Add Show</span>
            </button>
        </form>
    </div>

    <script>
        document.getElementById('showForm').addEventListener('submit', function (e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.querySelector('.btn-text').textContent = 'Adding Show...';
            submitBtn.disabled = true;
        });

        // Add pulse animation to focused inputs
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('focus', function () {
                this.style.animation = 'pulse 0.5s ease-in-out';
            });

            input.addEventListener('blur', function () {
                this.style.animation = '';
            });
        });

        // Set minimum date to today
        document.getElementById('show_date').min = new Date().toISOString().split('T')[0];

        // Add validation feedback
        inputs.forEach(input => {
            input.addEventListener('invalid', function () {
                this.style.borderColor = '#ff4757';
                this.style.boxShadow = '0 0 0 3px rgba(255, 71, 87, 0.1)';
            });

            input.addEventListener('input', function () {
                if (this.checkValidity()) {
                    this.style.borderColor = '#2ed573';
                    this.style.boxShadow = '0 0 0 3px rgba(46, 213, 115, 0.1)';
                }
            });
        });
    </script>
</body>

</html>