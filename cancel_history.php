<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$user_id = $_SESSION['id'];

$stmt = $conn->prepare("SELECT * FROM cancellations WHERE user_id = :user_id ORDER BY cancellation_date DESC");
$stmt->execute(['user_id' => $user_id]);
$cancellations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>My Cancellation History</title>
  <style>
    body { font-family: Arial; background: #f4f4f4; padding: 20px; }
    h2 { text-align: center; color: #333; }
    table { width: 100%; border-collapse: collapse; background: white; }
    th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
    th { background-color: #333; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
  </style>
</head>
<body>

<h2>Cancellation History</h2>
<table>
  <tr><th>PNR</th><th>Reason</th><th>Refunded</th><th>Remarks</th></tr>
<?php foreach ($cancellations as $row): ?>
  <tr>
    <td><?= htmlspecialchars($row['pnr']) ?></td>
    <td><?= htmlspecialchars($row['reason']) ?></td>
    <td>₹<?= htmlspecialchars($row['amount_refunded']) ?></td>
    <td><?= $row['remarks'] ? htmlspecialchars($row['remarks']) : "<span style='color:orange;'>Pending</span>" ?></td>
  </tr>
<?php endforeach; ?>
</table>

</body>
</html>
