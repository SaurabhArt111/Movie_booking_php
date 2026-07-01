<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid login details!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MovieBook</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated background particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(1) {
            width: 10px;
            height: 10px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 15px;
            height: 15px;
            top: 60%;
            left: 80%;
            animation-delay: -2s;
        }

        .particle:nth-child(3) {
            width: 8px;
            height: 8px;
            top: 80%;
            left: 20%;
            animation-delay: -4s;
        }

        .particle:nth-child(4) {
            width: 12px;
            height: 12px;
            top: 30%;
            left: 70%;
            animation-delay: -1s;
        }

        .particle:nth-child(5) {
            width: 6px;
            height: 6px;
            top: 70%;
            left: 50%;
            animation-delay: -3s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.3;
            }

            33% {
                transform: translateY(-20px) rotate(120deg);
                opacity: 0.6;
            }

            66% {
                transform: translateY(20px) rotate(240deg);
                opacity: 0.9;
            }
        }

        /* Main login container */
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 10;
            transform: translateY(50px);
            opacity: 0;
            animation: slideInUp 1s ease-out forwards;
        }

        @keyframes slideInUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Header styling */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 1s ease-out 0.3s both;
        }

        .login-header h3 {
            color: #333;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
        }

        .login-header .subtitle {
            color: #666;
            font-size: 16px;
            font-weight: 300;
        }

        .movie-icon {
            font-size: 48px;
            animation: bounceIn 1.5s ease-out 0.5s both;
            display: inline-block;
            margin-bottom: 15px;
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

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3) translateY(-50px);
            }

            50% {
                opacity: 1;
                transform: scale(1.1);
            }

            70% {
                transform: scale(0.9);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Form styling */
        .login-form {
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 15px;
            transition: color 0.3s ease;
        }

        .input-wrapper {
            position: relative;
            overflow: hidden;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            position: relative;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
            background: white;
        }

        input[type="email"]:hover,
        input[type="password"]:hover {
            border-color: #999;
            transform: translateY(-1px);
        }

        /* Input icons */
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            transition: color 0.3s ease;
            pointer-events: none;
        }

        input:focus+.input-icon {
            color: #667eea;
        }

        /* Login button */
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 18px 30px;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:active {
            transform: translateY(-1px);
        }

        .login-btn.loading {
            pointer-events: none;
        }

        .login-btn.loading::after {
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

        /* Error message styling */
        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: center;
            position: relative;
            animation: shakeError 0.6s ease-in-out, fadeInDown 0.5s ease-out;
            border: none;
        }

        @keyframes shakeError {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
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

        /* Responsive design */
        @media (max-width: 600px) {
            .login-container {
                padding: 40px 25px;
                margin: 10px;
                border-radius: 20px;
            }

            .login-header h3 {
                font-size: 28px;
            }

            .movie-icon {
                font-size: 42px;
            }
        }

        /* Decorative elements */
        .decorative-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: pulse 3s ease-in-out infinite;
        }

        .decorative-circle:nth-child(1) {
            width: 100px;
            height: 100px;
            top: -50px;
            right: -50px;
            animation-delay: 0s;
        }

        .decorative-circle:nth-child(2) {
            width: 80px;
            height: 80px;
            bottom: -40px;
            left: -40px;
            animation-delay: 1s;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.3;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.6;
            }
        }

        /* Success animation */
        .success-checkmark {
            display: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #2ed573;
            position: relative;
            margin: 20px auto;
            animation: successPop 0.6s ease-out;
        }

        .success-checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        @keyframes successPop {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="login-container">
        <div class="decorative-circle"></div>
        <div class="decorative-circle"></div>

        <div class="login-header">
            <div class="movie-icon">🎬</div>
            <h3>Admin Login</h3>
            <p class="subtitle">Access your MovieBook dashboard</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="login-form" id="loginForm">
            <div class="form-group">
                <label for="email">📧 Email Address</label>
                <div class="input-wrapper">
                    <input type="email" name="email" id="email" required
                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    <span class="input-icon">@</span>
                </div>
            </div>

            <div class="form-group">
                <label for="password">🔒 Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" required>
                    <span class="input-icon" id="togglePassword" style="cursor: pointer;">👁️</span>
                </div>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <span class="btn-text">🚀 Login to Dashboard</span>
            </button>
        </form>

        <div class="success-checkmark" id="successCheck"></div>
    </div>

    <script>
        // Form submission animation
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const loginBtn = document.getElementById('loginBtn');
            const btnText = loginBtn.querySelector('.btn-text');

            loginBtn.classList.add('loading');
            btnText.style.opacity = '0';
            loginBtn.disabled = true;
        });

        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });

        // Input focus animations
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function () {
                this.parentNode.parentNode.querySelector('label').style.color = '#667eea';
                this.style.animation = 'none';
                setTimeout(() => {
                    this.style.animation = 'pulse 0.6s ease-in-out';
                }, 10);
            });

            input.addEventListener('blur', function () {
                this.parentNode.parentNode.querySelector('label').style.color = '#333';
                this.style.animation = '';
            });

            // Real-time validation feedback
            input.addEventListener('input', function () {
                if (this.checkValidity()) {
                    this.style.borderColor = '#2ed573';
                    this.style.boxShadow = '0 0 0 4px rgba(46, 213, 115, 0.1)';
                } else {
                    this.style.borderColor = '#ff6b6b';
                    this.style.boxShadow = '0 0 0 4px rgba(255, 107, 107, 0.1)';
                }
            });
        });

        // Simulate successful login animation (for demo purposes)
        function showSuccessAnimation() {
            const successCheck = document.getElementById('successCheck');
            successCheck.style.display = 'block';

            setTimeout(() => {
                document.querySelector('.login-container').style.transform = 'scale(0.95)';
                document.querySelector('.login-container').style.opacity = '0.5';
            }, 1000);
        }

        // Add subtle hover effects to the container
        const container = document.querySelector('.login-container');
        container.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-5px)';
        });

        container.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
        });
    </script>
</body>

</html>