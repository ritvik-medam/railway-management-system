<?php
// Start session and check if user is logged in

include 'db_connect.php';
include 'user_id_loader.php';

// Redirect to login if no user is logged in
if (!isset($current_user_id)) {
    header("Location: login.php?redirect=meal_history");
    exit();
}

// Get user information
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get meal orders for the current user
$stmt = $conn->prepare("SELECT * FROM meals WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$current_user_id]);
$meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Meal Order History - Indian Railways</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      background: #f5f5f7;
      color: #1d1d1f;
    }
    header {
      background: #ffffff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .logo h1 {
      color: #00aa88;
      font-size: 1.5rem;
      font-weight: bold;
    }
    .logo span {
      color: #0044cc;
    }
    .user-info {
      display: flex;
      align-items: center;
    }
    .user-info p {
      margin-right: 15px;
    }
    .back-btn, .logout-btn {
      background: transparent;
      color: #0044cc;
      border: 1px solid #0044cc;
      padding: 0.5rem 1rem;
      border-radius: 5px;
      cursor: pointer;
      text-decoration: none;
      margin-left: 10px;
    }

    .container {
      max-width: 900px;
      background-color: #ffffff;
      margin: 2rem auto;
      padding: 2rem;
      border-radius: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #0044cc;
    }

    .order-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1.5rem;
    }

    .order-table th {
      background-color: #f2f2f2;
      text-align: left;
      padding: 12px;
    }

    .order-table td {
      padding: 12px;
      border-bottom: 1px solid #eee;
    }

    .status-pending {
      color: #ff9800;
    }
    .status-confirmed {
      color: #2196F3;
    }
    .status-delivered {
      color: #4CAF50;
    }
    .status-cancelled {
      color: #F44336;
    }

    .no-orders {
      text-align: center;
      padding: 2rem;
      color: #666;
    }

    .actions {
      display: flex;
      justify-content: space-between;
      margin-top: 2rem;
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">
      <h1>Indian <span>Railways</span></h1>
    </div>
    <div class="user-info">
      <p>Welcome, <?php echo htmlspecialchars($user['username']); ?></p>
      <a href="meal.php" class="back-btn">Order Meal</a>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <div class="container">
    <h2>Your Meal Order History</h2>

    <?php if(count($meals) > 0): ?>
      <table class="order-table">
        <thead>
          <tr>
            <th>Order Date</th>
            <th>Train</th>
            <th>Seat</th>
            <th>Meal Type</th>
            <th>Preference</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($meals as $meal): ?>
            <tr>
              <td><?php echo date('d M Y, h:i A', strtotime($meal['order_date'])); ?></td>
              <td><?php echo htmlspecialchars($meal['train_number']); ?></td>
              <td><?php echo htmlspecialchars($meal['coach']) . '-' . htmlspecialchars($meal['seat_number']); ?></td>
              <td><?php echo ucfirst(htmlspecialchars($meal['meal_type'])); ?></td>
              <td><?php echo ucfirst(htmlspecialchars($meal['meal_preference'])); ?></td>
              <td class="status-<?php echo htmlspecialchars($meal['status']); ?>">
                <?php echo ucfirst(htmlspecialchars($meal['status'])); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="no-orders">
        <p>You haven't placed any meal orders yet.</p>
      </div>
    <?php endif; ?>

    <div class="actions">
      <a href="meal.php" class="back-btn">Place New Order</a>
    </div>
  </div>
</body>
</html>