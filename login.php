<<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['id'] = $user['id'];  // not user_id
    
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.html");
            exit();
        } else {
            echo "Incorrect password.";
        }
    } else {
        echo "User not found.";
    }
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In - Indian Railways</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
    }
    body {
      background: url('https://i.pinimg.com/736x/df/ee/4e/dfee4efb05453172eaae20a8766a5446.jpg') no-repeat center center fixed;
      background-size: cover;
      color: #1d1d1f;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .container {
      background: rgba(255, 255, 255, 0.9);
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
    }
    h1 {
      font-size: 2rem;
      margin-bottom: 1.5rem;
      text-align: center;
      color: #0044cc;
    }
    input {
      width: 100%;
      padding: 1rem;
      margin-bottom: 1rem;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
    }
    button {
      background: #00aa88;
      color: white;
      padding: 1rem;
      border-radius: 8px;
      width: 100%;
      font-size: 1.1rem;
      border: none;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    button:hover {
      background: #008a70;
    }
    .back-link {
      display: block;
      text-align: center;
      margin-top: 1rem;
      font-size: 0.9rem;
    }
    .back-link a {
      text-decoration: none;
      color: #0044cc;
    }
    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Sign In</h1>
    <form id="loginForm" action="login.php" method="POST">
        <input type="email" placeholder="Email" name="email" required>
        <input type="password" placeholder="Password" name="password" required>
        <button type="submit">Sign In</button>
      </form>
      
    <div class="back-link">
      <p>Don't have an account? <a href="register.php" class="register">Register</a></p>
    </div>
  </div>
</body>
</html>