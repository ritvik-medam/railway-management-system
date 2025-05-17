<?php
session_start();
include 'db_connect.php';

$section = $_GET['section'] ?? 'meals';

// Handle POST updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_meal'])) {
        $id = $_POST['id'];
        $status = $_POST['new_status'];
        $stmt = $conn->prepare("UPDATE meals SET status = :status WHERE meal_id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }
    elseif (isset($_POST['update_lost'])) {
        $id = $_POST['lost_id'];
        $status = $_POST['lost_status'];
        $stmt = $conn->prepare("UPDATE lost_items SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }
    elseif (isset($_POST['update_cancel'])) {
        $id = $_POST['cancel_id'];
        $remark = $_POST['cancel_remarks'];
        $stmt = $conn->prepare("UPDATE cancellations SET remarks = :remarks WHERE cancellation_id = :id");
        $stmt->execute(['remarks' => $remark, 'id' => $id]);
    }
    header("Location: staff_portal.php?section=$section");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Staff Portal</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f2f2f2;
      margin: 0; padding: 20px;
    }
    h2 { text-align: center; color: #333; }
    form select, form input[type="text"], form button {
      padding: 5px;
      margin: 5px;
    }
    table {
      width: 100%; border-collapse: collapse;
      background: white;
      margin-top: 20px;
    }
    th, td {
      padding: 10px; border: 1px solid #ddd;
      text-align: center;
    }
    th { background: #333; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
  </style>
</head>
<body>

<h2>Staff Management Portal</h2>

<form method="GET">
  <label>Select Section: </label>
  <select name="section" onchange="this.form.submit()">
    <option value="meals" <?= $section == 'meals' ? 'selected' : '' ?>>Meals</option>
    <option value="lost" <?= $section == 'lost' ? 'selected' : '' ?>>Lost Items</option>
    <option value="cancel" <?= $section == 'cancel' ? 'selected' : '' ?>>Cancellations</option>
  </select>
</form>

<?php if ($section == 'meals'):
    $stmt = $conn->query("SELECT * FROM meals ORDER BY order_date DESC");
    $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<table>
  <tr><th>ID</th><th>Passenger</th><th>Train</th><th>Status</th><th>Update</th></tr>
  <?php foreach ($meals as $row): ?>
    <tr>
      <td><?= htmlspecialchars($row['meal_id']) ?></td>
      <td><?= htmlspecialchars($row['passenger_name']) ?></td>
      <td><?= htmlspecialchars($row['train_number']) ?></td>
      <td><?= htmlspecialchars($row['status']) ?></td>
      <td>
        <form method="POST">
          <input type="hidden" name="id" value="<?= $row['meal_id'] ?>">
          <select name="new_status">
            <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="confirmed" <?= $row['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
            <option value="delivered" <?= $row['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
          </select>
          <button name="update_meal">Update</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php elseif ($section == 'lost'):
    $stmt = $conn->query("SELECT * FROM lost_items ORDER BY created_at DESC");
    $lost_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<table>
  <tr><th>ID</th><th>Item</th><th>Location</th><th>Status</th><th>Update</th></tr>
  <?php foreach ($lost_items as $row): ?>
    <tr>
      <td><?= htmlspecialchars($row['id']) ?></td>
      <td><?= htmlspecialchars($row['item_name']) ?></td>
      <td><?= htmlspecialchars($row['location']) ?></td>
      <td><?= htmlspecialchars($row['status']) ?></td>
      <td>
        <form method="POST">
          <input type="hidden" name="lost_id" value="<?= $row['id'] ?>">
          <select name="lost_status">
            <option value="reported" <?= $row['status'] == 'reported' ? 'selected' : '' ?>>Reported</option>
            <option value="found" <?= $row['status'] == 'found' ? 'selected' : '' ?>>Found</option>
            <option value="resolved" <?= $row['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
          </select>
          <button name="update_lost">Update</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php elseif ($section == 'cancel'):
    $stmt = $conn->query("SELECT * FROM cancellations ORDER BY cancellation_date DESC");
    $cancellations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<table>
  <tr><th>ID</th><th>PNR</th><th>Reason</th><th>Remarks</th><th>Update</th></tr>
  <?php foreach ($cancellations as $row): ?>
    <tr>
      <td><?= htmlspecialchars($row['cancellation_id']) ?></td>
      <td><?= htmlspecialchars($row['pnr']) ?></td>
      <td><?= htmlspecialchars($row['reason']) ?></td>
      <td><?= htmlspecialchars($row['remarks']) ?></td>
      <td>
        <form method="POST">
          <input type="hidden" name="cancel_id" value="<?= $row['cancellation_id'] ?>">
          <input type="text" name="cancel_remarks" placeholder="Add remark" value="<?= htmlspecialchars($row['remarks']) ?>">
          <button name="update_cancel">Update</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

</body>
</html>
