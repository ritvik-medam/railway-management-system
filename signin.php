<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  // Find user by email
  $sql = "SELECT * FROM users WHERE email = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($user = $result->fetch_assoc()) {
    // Verify hashed password
    if (password_verify($password, $user['password'])) {
      // Store session info
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['name']    = $user['name'];
      $_SESSION['email']   = $user['email'];

      // Optional: Track login time
      $login_time = date("Y-m-d H:i:s");
      $log_stmt = $conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
      $log_stmt->bind_param("si", $login_time, $user['id']);
      $log_stmt->execute();
      $log_stmt->close();

      header("Location: dashboard.php");
      exit();
    } else {
      echo "<script>alert('Incorrect password.'); window.history.back();</script>";
    }
  } else {
    echo "<script>alert('User not found.'); window.history.back();</script>";
  }

  $stmt->close();
  $conn->close();
}
?>
