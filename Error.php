<?php
// 404.php
http_response_code(404); // Set HTTP status to 404
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>404 Not Found</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6f9;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      text-align: center;
    }

    .error-container {
      max-width: 500px;
      background: #fff;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.1);
      animation: fadeIn 0.8s ease-in-out;
    }

    h1 {
      font-size: 100px;
      margin: 0;
      color: #e74c3c;
    }

    h2 {
      margin: 10px 0;
      font-size: 28px;
      color: #333;
    }

    p {
      font-size: 18px;
      color: #555;
    }

    a {
      display: inline-block;
      margin-top: 20px;
      padding: 12px 24px;
      font-size: 16px;
      color: #fff;
      background: #3498db;
      border-radius: 8px;
      text-decoration: none;
      transition: 0.3s;
    }

    a:hover {
      background: #2980b9;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>

<body>
  <div class="error-container">
    <h1>404</h1>
    <h2>Oops! Page Not Found</h2>
    <p>Sorry, the page you're looking for doesn't exist or has been moved.</p>
    <a href="index.php">Back to Home</a>
  </div>
</body>

</html>