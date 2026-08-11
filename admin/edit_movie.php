<?php
require_once '../config.php';
requireAdmin($pdo);

if (!isset($_GET['id'])) {
    header("Location: manage_movies.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch movie
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$id]);
$movie = $stmt->fetch();

if (!$movie) {
    header("Location: manage_movies.php");
    exit;
}

// Update movie
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $title = sanitize($_POST['title']);
    $genre = sanitize($_POST['genre']);
    $duration = intval($_POST['duration']);
    $rating = floatval($_POST['rating']);
    $description = sanitize($_POST['description']);
    $posterPath = $movie['poster_url'];
    $error = null;

    if (!empty($_FILES['poster']['name'])) {
        try {
            $posterPath = handleUploadedPoster($_FILES['poster'], "../uploads/movies/", "uploads/movies/");
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("UPDATE movies 
            SET title=?, genre=?, duration=?, rating=?, description=?, poster_url=? 
            WHERE id=?");
        $stmt->execute([$title, $genre, $duration, $rating, $description, $posterPath, $id]);

        header("Location: manage_movies.php");
        exit;
    }

    // Re-fetch so the form reflects what's actually in the DB after a failed upload.
    $movie = array_merge($movie, compact('title', 'genre', 'duration', 'rating', 'description'));
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Movie - Cinema Admin</title>
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

        /* Animated background particles */
        .bg-animation {
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
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(1) {
            left: 20%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            left: 40%;
            animation-delay: 2s;
        }

        .particle:nth-child(3) {
            left: 60%;
            animation-delay: 4s;
        }

        .particle:nth-child(4) {
            left: 80%;
            animation-delay: 1s;
        }

        .particle:nth-child(5) {
            left: 10%;
            animation-delay: 3s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(100vh) scale(0);
            }

            10% {
                transform: translateY(90vh) scale(1);
            }

            90% {
                transform: translateY(10vh) scale(1);
            }

            100% {
                transform: translateY(0vh) scale(0);
            }
        }

        .container {
            max-width: 800px;
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

        .form-group {
            margin-bottom: 25px;
            opacity: 0;
            animation: slideInRight 0.6s ease-out forwards;
        }

        .form-group:nth-child(1) {
            animation-delay: 0.6s;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.7s;
        }

        .form-group:nth-child(3) {
            animation-delay: 0.8s;
        }

        .form-group:nth-child(4) {
            animation-delay: 0.9s;
        }

        .form-group:nth-child(5) {
            animation-delay: 1s;
        }

        .form-group:nth-child(6) {
            animation-delay: 1.1s;
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

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border: 2px dashed #667eea;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(102, 126, 234, 0.05);
            color: #667eea;
            font-weight: 600;
        }

        .file-input-label:hover {
            background: rgba(102, 126, 234, 0.1);
            border-color: #764ba2;
            transform: translateY(-2px);
        }

        .file-input-label i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .current-poster {
            margin-top: 15px;
            text-align: center;
            animation: fadeIn 1s ease-out;
        }

        .current-poster img {
            max-width: 150px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .current-poster img:hover {
            transform: scale(1.05) rotate(2deg);
        }

        .current-poster-label {
            display: block;
            margin-bottom: 10px;
            color: #666;
            font-size: 0.9rem;
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
            animation: bounceIn 0.8s ease-out 1.2s both;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
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
        }
        .error-message {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
    </style>
</head>

<body>
    <!-- Animated background -->
    <div class="bg-animation">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="container">
        <a href="manage_movies.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Movie Management
        </a>

        <div class="header">
            <h1><i class="fas fa-film"></i> Edit Movie</h1>
            <p class="subtitle">Update movie information and poster</p>
        </div>

        <div class="form-card">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" id="movieForm">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="title">
                        <i class="fas fa-video"></i>
                        Movie Title
                    </label>
                    <input type="text" id="title" name="title" class="form-control"
                        value="<?= htmlspecialchars($movie['title']) ?>" placeholder="Enter movie title" required>
                </div>

                <div class="form-group">
                    <label for="genre">
                        <i class="fas fa-tags"></i>
                        Genre
                    </label>
                    <input type="text" id="genre" name="genre" class="form-control"
                        value="<?= htmlspecialchars($movie['genre']) ?>" placeholder="e.g., Action, Drama, Comedy"
                        required>
                </div>

                <div class="form-group">
                    <label for="duration">
                        <i class="fas fa-clock"></i>
                        Duration (minutes)
                    </label>
                    <input type="number" id="duration" name="duration" class="form-control"
                        value="<?= $movie['duration'] ?>" placeholder="120" min="1" required>
                </div>

                <div class="form-group">
                    <label for="rating">
                        <i class="fas fa-star"></i>
                        Rating (0.0 - 10.0)
                    </label>
                    <input type="number" id="rating" name="rating" class="form-control" value="<?= $movie['rating'] ?>"
                        step="0.1" min="0" max="10" placeholder="7.5" required>
                </div>

                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-align-left"></i>
                        Description
                    </label>
                    <textarea id="description" name="description" class="form-control"
                        placeholder="Enter movie description..."
                        required><?= htmlspecialchars($movie['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-image"></i>
                        Movie Poster
                    </label>
                    <div class="file-input-wrapper">
                        <input type="file" id="poster" name="poster" class="file-input" accept="image/*"
                            onchange="previewImage(this)">
                        <label for="poster" class="file-input-label">
                            <i class="fas fa-upload"></i>
                            Choose new poster (optional)
                        </label>
                    </div>

                    <?php if (!empty($movie['poster_url'])): ?>
                        <div class="current-poster">
                            <span class="current-poster-label">Current Poster:</span>
                            <img src="../<?= htmlspecialchars($movie['poster_url']) ?>" alt="Current poster"
                                id="currentPoster">
                        </div>
                    <?php endif; ?>

                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i>
                    Update Movie
                </button>
            </form>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Updating movie...</p>
            </div>
        </div>
    </div>

    <script>
        // Form submission with loading animation
        document.getElementById('movieForm').addEventListener('submit', function (e) {
            const submitBtn = document.querySelector('.btn-submit');
            const loading = document.getElementById('loading');

            submitBtn.style.display = 'none';
            loading.style.display = 'block';
        });

        // Image preview function
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                const currentPoster = document.getElementById('currentPoster');

                reader.onload = function (e) {
                    if (currentPoster) {
                        currentPoster.src = e.target.result;
                        currentPoster.style.transform = 'scale(1.1)';
                        setTimeout(() => {
                            currentPoster.style.transform = 'scale(1)';
                        }, 300);
                    } else {
                        // Create new image preview if none exists
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'current-poster';
                        previewDiv.innerHTML = `
                            <span class="current-poster-label">Preview:</span>
                            <img src="${e.target.result}" alt="Preview" style="max-width: 150px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                        `;
                        input.parentNode.appendChild(previewDiv);
                    }
                };

                reader.readAsDataURL(input.files[0]);

                // Update label text
                const label = document.querySelector('.file-input-label');
                label.innerHTML = `<i class="fas fa-check"></i> File selected: ${input.files[0].name}`;
                label.style.background = 'rgba(40, 167, 69, 0.1)';
                label.style.borderColor = '#28a745';
                label.style.color = '#28a745';
            }
        }

        // Add smooth scrolling and focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function () {
                this.parentNode.style.transform = 'translateX(5px)';
            });

            input.addEventListener('blur', function () {
                this.parentNode.style.transform = 'translateX(0)';
            });
        });

        // Add typing effect to placeholder text
        const inputs = document.querySelectorAll('input[placeholder], textarea[placeholder]');
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
            }, 50);
        });
    </script>
</body>

</html>