<?php
session_start();
include 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM staff WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($staff && $staff['password'] === $password) {
        $_SESSION['staff_id'] = $staff['id'];
        $_SESSION['staff_name'] = $staff['name'];
        header("Location: staff.php");
        exit();
    } else {
        $error = "Invalid credentials.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Staff Login</title>
  <style>
    body { font-family: Arial; background: #f2f2f2; padding: 50px; text-align: center; }
    form {
      display: inline-block;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    input {
      padding: 10px; margin: 10px;
      width: 250px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    button {
      padding: 10px 20px;
      background-color: #2563eb;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
    }
    button:hover {
      background-color: #1e40af;
    }
    .error { color: red; font-weight: bold; }
  </style>
</head>
<body>
  <h2>Staff Login</h2>
  <form method="POST">
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
    <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
  </form>
</body>
</html>
