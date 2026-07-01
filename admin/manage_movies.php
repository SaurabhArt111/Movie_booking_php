<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Add movie
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $genre = sanitize($_POST['genre']);
    $duration = intval($_POST['duration']);
    $rating = floatval($_POST['rating']);
    $description = sanitize($_POST['description']);
    $posterPath = null;

    if (!empty($_FILES['poster']['name'])) {
        $uploadDir = "../uploads/movies/";
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $filename = time() . "_" . basename($_FILES['poster']['name']);
        $target = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['poster']['tmp_name'], $target)) {
            $posterPath = "uploads/movies/" . $filename;
        }
    }
    $stmt = $pdo->prepare("INSERT INTO movies (title, genre, duration, rating, description, poster_url, status) 
                       VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$title, $genre, $duration, $rating, $description, $posterPath]);

    $success = "✅ Movie added successfully!";
}

// Fetch movies
$stmt = $pdo->query("SELECT * FROM movies ORDER BY created_at DESC");
$movies = $stmt->fetchAll();

// Get admin info
$adminStmt = $pdo->prepare("SELECT username, full_name FROM users WHERE id = ?");
$adminStmt->execute([$_SESSION['admin_id']]);
$admin = $adminStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies - Cinema Admin</title>
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
            color: #333;
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
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
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
        }

        .nav-links a:hover {
            background: rgba(255, 71, 87, 0.2);
            transform: translateY(-2px);
        }

        .nav-links a.active {
            background: #ff4757;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
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
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 100px auto 2rem;
            padding: 0 2rem;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            text-align: center;
            color: white;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, #fff, #ff4757);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Success Message */
        .success {
            background: linear-gradient(45deg, #00d084, #00b894);
            color: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 208, 132, 0.3);
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInDown 0.5s ease;
        }

        /* Form Section */
        .form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.6s ease;
        }

        .form-section h2 {
            color: #333;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.8rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .form-left {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-right {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .input-group label {
            font-weight: 600;
            color: #555;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e1e5e9;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #ff4757;
            box-shadow: 0 0 0 3px rgba(255, 71, 87, 0.1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.1rem;
        }

        textarea {
            padding: 1rem;
            min-height: 120px;
            resize: vertical;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            border: 2px dashed #ddd;
            border-radius: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #fafafa;
        }

        .file-upload:hover {
            border-color: #ff4757;
            background: #fff5f5;
        }

        .file-upload.dragover {
            border-color: #ff4757;
            background: #fff5f5;
            transform: scale(1.02);
        }

        .file-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            padding: 0;
        }

        .upload-content {
            text-align: center;
        }

        .upload-icon {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .upload-text {
            color: #666;
            font-size: 1rem;
        }

        /* Image Preview */
        .image-preview {
            max-width: 200px;
            margin: 1rem auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .preview-img {
            width: 100%;
            height: auto;
            display: block;
        }

        .remove-image {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4757;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .remove-image:hover {
            background: #e73c7e;
            transform: scale(1.1);
        }

        /* Submit Button */
        .submit-btn {
            background: linear-gradient(45deg, #ff4757, #ff6b7a);
            color: white;
            padding: 1rem 3rem;
            border: none;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(255, 71, 87, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem auto 0;
            min-width: 200px;
        }

        .submit-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(255, 71, 87, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px) scale(1.02);
        }

        /* Movies Grid */
        .movies-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.8s ease;
        }

        .movies-section h2 {
            color: #333;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 1.8rem;
        }

        .movies-count {
            background: #ff4757;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
        }

        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .movie-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            transition: transform 0.35s ease, box-shadow 0.35s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .movie-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.25);
        }

        /* Poster */
        .movie-poster {
            width: 100%;
            height: 420px;
            position: relative;
            overflow: hidden;
        }

        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
            border-bottom: 3px solid rgba(0, 0, 0, 0.1);
        }

        .movie-card:hover .movie-poster img {
            transform: scale(1.08);
        }

        .no-poster {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #74ebd5, #9face6);
            color: white;
            font-size: 2.5rem;
            font-weight: 600;
            height: 100%;
        }

        /* Info section */
        .movie-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .movie-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #222;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .movie-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            color: #555;
        }

        .movie-genre {
            background: #ffe8e8;
            color: #e63946;
            padding: 0.3rem 0.9rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .movie-rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-weight: 700;
            color: #f39c12;
        }

        .movie-duration {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.85rem;
            color: #444;
        }

        .movie-description {
            height: 1.3rem;
            color: #555;
            font-size: 0.92rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Actions */
        .movie-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .edit-btn {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
        }

        .edit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.35);
        }

        .delete-btn {
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            color: white;
        }

        .delete-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 65, 108, 0.35);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Loading Animation */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #ff4757;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Animations */
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

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
                margin-top: 80px;
            }

            .nav-links {
                display: none;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .movies-grid {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .movie-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .movies-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .movie-card {
                margin: 0;
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
            <a href="dashboard.php" class="logo">
                <i class="fas fa-film"></i>
                CinemaFlex
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_movies.php" class="active"><i class="fas fa-video"></i> Movies</a></li>
                <li><a href="#all_movies"><i class="fas fa-video"></i> All movies</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="manage_shows.php"><i class="fas fa-calendar"></i> Shows</a></li>
            </ul>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($admin['full_name'] ?? $admin['username'], 0, 1)) ?>
                </div>
                <span><?= htmlspecialchars($admin['full_name'] ?? $admin['username']) ?></span>
                <a href="logout.php" style="color: #ff4757; margin-left: 1rem;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-film"></i> Manage Movies</h1>
            <p>Add, edit, and organize your movie collection</p>
        </div>

        <!-- Success Message -->
        <?php if (!empty($success)): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <?= $success ?>
            </div>
        <?php endif; ?>

        <!-- Add Movie Form -->
        <div class="form-section">
            <h2><i class="fas fa-plus-circle"></i> Add New Movie</h2>
            <form method="post" enctype="multipart/form-data" id="movieForm">
                <div class="form-grid">
                    <div class="form-left">
                        <div class="input-group">
                            <label for="title"><i class="fas fa-film"></i> Movie Title</label>
                            <div class="input-wrapper">
                                <i class="fas fa-film input-icon"></i>
                                <input type="text" id="title" name="title" placeholder="Enter movie title" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="genre"><i class="fas fa-tags"></i> Genre</label>
                            <div class="input-wrapper">
                                <i class="fas fa-tags input-icon"></i>
                                <input type="text" id="genre" name="genre" placeholder="e.g., Action, Comedy, Drama"
                                    required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="duration"><i class="fas fa-clock"></i> Duration (minutes)</label>
                            <div class="input-wrapper">
                                <i class="fas fa-clock input-icon"></i>
                                <input type="number" id="duration" name="duration" placeholder="120" min="1" max="500"
                                    required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="rating"><i class="fas fa-star"></i> Rating (1-10)</label>
                            <div class="input-wrapper">
                                <i class="fas fa-star input-icon"></i>
                                <input type="number" id="rating" name="rating" step="0.1" min="1" max="10"
                                    placeholder="8.5" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-right">
                        <div class="input-group">
                            <label for="description"><i class="fas fa-align-left"></i> Description</label>
                            <textarea id="description" name="description" placeholder="Enter movie description..."
                                required></textarea>
                        </div>

                        <div class="input-group">
                            <label><i class="fas fa-image"></i> Movie Poster</label>
                            <div class="file-upload" id="fileUpload">
                                <input type="file" name="poster" id="poster" accept="image/*">
                                <div class="upload-content">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="upload-text">
                                        <strong>Click to upload</strong> or drag and drop<br>
                                        <small>PNG, JPG, GIF up to 10MB</small>
                                    </div>
                                </div>
                            </div>
                            <div class="image-preview" id="imagePreview" style="display: none;">
                                <img src="" alt="Preview" class="preview-img" id="previewImg">
                                <button type="button" class="remove-image" id="removeImage">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-plus"></i>
                    Add Movie
                </button>
            </form>
        </div>

        <!-- Movies List -->
        <div class="movies-section" id="all_movies">
            <h2>
                <span><i class="fas fa-list"></i> All Movies</span>
                <span class="movies-count"><?= count($movies) ?> Movies</span>
            </h2>

            <?php if (empty($movies)): ?>
                <div class="empty-state">
                    <i class="fas fa-film"></i>
                    <h3>No movies found</h3>
                    <p>Add your first movie using the form above!</p>
                </div>
            <?php else: ?>
                <div class="movies-grid">
                    <?php foreach ($movies as $movie): ?>
                        <div class="movie-card">
                            <div class="movie-poster">
                                <?php if (!empty($movie['poster_url'])): ?>
                                    <img src="../<?= htmlspecialchars($movie['poster_url']) ?>"
                                        alt="<?= htmlspecialchars($movie['title']) ?>">
                                <?php else: ?>
                                    <div class="no-poster">
                                        <i class="fas fa-film"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="movie-info">
                                <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>
                                <div class="movie-meta">
                                    <span class="movie-genre"><?= htmlspecialchars($movie['genre']) ?></span>
                                    <span class="movie-rating">
                                        <i class="fas fa-star"></i>
                                        <?= $movie['rating'] ?>/10
                                    </span>
                                </div>
                                <div class="movie-duration">
                                    <i class="fas fa-clock"></i>
                                    <?= $movie['duration'] ?> minutes
                                </div>
                                <p class="movie-description"><?= htmlspecialchars($movie['description']) ?></p>
                                <div class="movie-actions">
                                    <a href="edit_movie.php?id=<?= $movie['id'] ?>" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </a>
                                    <button
                                        onclick="deleteMovie(<?= $movie['id'] ?>, '<?= htmlspecialchars($movie['title']) ?>')"
                                        class="action-btn delete-btn">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Image upload and preview functionality
        const fileInput = document.getElementById('poster');
        const fileUpload = document.getElementById('fileUpload');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const removeImageBtn = document.getElementById('removeImage');

        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                showImagePreview(file);
            }
        });

        // Drag and drop functionality
        fileUpload.addEventListener('dragover', function (e) {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });

        fileUpload.addEventListener('dragleave', function (e) {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
        });

        fileUpload.addEventListener('drop', function (e) {
            e.preventDefault();
            fileUpload.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    fileInput.files = files;
                    showImagePreview(file);
                }
            }
        });

        function showImagePreview(file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                imagePreview.style.display = 'block';
                fileUpload.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }

        removeImageBtn.addEventListener('click', function () {
            fileInput.value = '';
            imagePreview.style.display = 'none';
            fileUpload.style.display = 'flex';
        });

        // Form validation and submission
        document.getElementById('movieForm').addEventListener('submit', function (e) {
            const title = document.getElementById('title').value.trim();
            const genre = document.getElementById('genre').value.trim();
            const duration = document.getElementById('duration').value;
            const rating = document.getElementById('rating').value;
            const description = document.getElementById('description').value.trim();

            if (!title || !genre || !duration || !rating || !description) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }

            if (rating < 1 || rating > 10) {
                e.preventDefault();
                alert('Rating must be between 1 and 10.');
                return;
            }

            if (duration < 1 || duration > 500) {
                e.preventDefault();
                alert('Duration must be between 1 and 500 minutes.');
                return;
            }

            // Show loading
            document.getElementById('loading').style.display = 'flex';
        });

        // Delete movie function
        function deleteMovie(movieId, movieTitle) {
            if (confirm(`Are you sure you want to delete "${movieTitle}"? This action cannot be undone.`)) {
                // Show loading
                document.getElementById('loading').style.display = 'flex';

                // Redirect to delete script
                window.location.href = `delete_movie.php?id=${movieId}`;
            }
        }

        // Rating input validation
        document.getElementById('rating').addEventListener('input', function () {
            const value = parseFloat(this.value);
            if (value > 10) this.value = 10;
            if (value < 0) this.value = 0;
        });

        // Duration input validation
        document.getElementById('duration').addEventListener('input', function () {
            const value = parseInt(this.value);
            if (value > 500) this.value = 500;
            if (value < 1 && this.value !== '') this.value = 1;
        });

        // Smooth scroll to form after successful submission
        <?php if (!empty($success)): ?>
            setTimeout(function () {
                document.querySelector('.movies-section').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 1000);
        <?php endif; ?>

        // Auto-resize textarea
        const textarea = document.getElementById('description');
        textarea.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Add animation classes on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe movie cards
        document.querySelectorAll('.movie-card').forEach(card => {
            card.style.opacity = '0.3';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });

        // Hide loading screen after page load
        window.addEventListener('load', function () {
            setTimeout(function () {
                document.getElementById('loading').style.display = 'none';
            }, 500);
        });

        // Enhanced file validation
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                // Check file size (10MB limit)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size must be less than 10MB');
                    this.value = '';
                    return;
                }

                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF)');
                    this.value = '';
                    return;
                }
            }
        });

        // Add search functionality
        function createSearchBox() {
            const moviesSection = document.querySelector('.movies-section h2');
            const searchContainer = document.createElement('div');
            searchContainer.innerHTML = `
                <div style="margin: 1rem 0; position: relative;">
                    <input type="text" id="movieSearch" placeholder="Search movies..." 
                           style="padding: 0.75rem 3rem 0.75rem 1rem; border: 2px solid #e1e5e9; border-radius: 25px; width: 100%; max-width: 400px; font-size: 1rem;">
                    <i class="fas fa-search" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: #999;"></i>
                </div>
            `;

            moviesSection.parentNode.insertBefore(searchContainer, moviesSection.nextSibling);

            // Search functionality
            document.getElementById('movieSearch').addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const movieCards = document.querySelectorAll('.movie-card');

                movieCards.forEach(card => {
                    const title = card.querySelector('.movie-title').textContent.toLowerCase();
                    const genre = card.querySelector('.movie-genre').textContent.toLowerCase();

                    if (title.includes(searchTerm) || genre.includes(searchTerm)) {
                        card.style.display = 'block';
                        card.style.opacity = '1';
                        card.style.transform = 'scale(1)';
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Update count
                const visibleCards = document.querySelectorAll('.movie-card[style*="display: block"], .movie-card:not([style*="display: none"])');
                document.querySelector('.movies-count').textContent = `${visibleCards.length} Movies`;
            });
        }

        // Add search box if movies exist
        if (document.querySelectorAll('.movie-card').length > 0) {
            createSearchBox();
        }

        // Form reset after successful submission
        <?php if (!empty($success)): ?>
            // Clear form
            document.getElementById('movieForm').reset();
            // Hide image preview
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('fileUpload').style.display = 'flex';
        <?php endif; ?>

        // Add tooltips for form inputs
        const tooltips = {
            'title': 'Enter the official movie title',
            'genre': 'e.g., Action, Comedy, Drama, Thriller, Romance',
            'duration': 'Movie length in minutes (1-500)',
            'rating': 'Rate from 1.0 to 10.0 (decimals allowed)',
            'description': 'Brief description or plot summary of the movie'
        };

        Object.keys(tooltips).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.title = tooltips[id];
            }
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            // Ctrl/Cmd + Enter to submit form
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                document.getElementById('movieForm').submit();
            }

            // Escape to clear search
            if (e.key === 'Escape') {
                const searchBox = document.getElementById('movieSearch');
                if (searchBox) {
                    searchBox.value = '';
                    searchBox.dispatchEvent(new Event('input'));
                }
            }
        });

        // Add genre suggestions
        const genreInput = document.getElementById('genre');
        const commonGenres = [
            'Action', 'Adventure', 'Animation', 'Biography', 'Comedy',
            'Crime', 'Documentary', 'Drama', 'Family', 'Fantasy',
            'History', 'Horror', 'Musical', 'Mystery', 'Romance',
            'Sci-Fi', 'Sport', 'Thriller', 'War', 'Western'
        ];

        genreInput.addEventListener('input', function () {
            const value = this.value.toLowerCase();
            if (value.length > 1) {
                const suggestions = commonGenres.filter(genre =>
                    genre.toLowerCase().includes(value)
                );

                // You can implement a dropdown here if needed
                if (suggestions.length > 0 && !commonGenres.includes(this.value)) {
                    this.style.borderColor = '#ff4757';
                } else {
                    this.style.borderColor = '#e1e5e9';
                }
            }
        });

        // Progressive enhancement for older browsers
        if (!window.FileReader) {
            document.querySelector('.file-upload').innerHTML = `
                <input type="file" name="poster" accept="image/*" style="padding: 1rem;">
                <p>Image upload (drag & drop not supported in this browser)</p>
            `;
        }

        // Add confirmation for navigation away with unsaved changes
        let formChanged = false;
        const formElements = document.querySelectorAll('#movieForm input, #movieForm textarea');

        formElements.forEach(element => {
            element.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', function (e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Reset form change tracking after submission
        document.getElementById('movieForm').addEventListener('submit', function () {
            formChanged = false;
        });

        // Add success animation for newly added movies
        <?php if (!empty($success)): ?>
            setTimeout(function () {
                const firstMovieCard = document.querySelector('.movie-card');
                if (firstMovieCard) {
                    firstMovieCard.style.animation = 'pulse 2s ease-in-out';
                    firstMovieCard.style.border = '2px solid #00d084';

                    setTimeout(function () {
                        firstMovieCard.style.animation = '';
                        firstMovieCard.style.border = '';
                    }, 3000);
                }
            }, 1500);
        <?php endif; ?>
    </script>
</body>

</html>