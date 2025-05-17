<?php
session_start();
if (!isset($_SESSION['email']) && !isset($_SESSION['phone'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$contact = $_SESSION['email'] ?? $_SESSION['phone'];

$stmt = $conn->prepare("SELECT * FROM lost_items WHERE contact_info = :contact ORDER BY created_at DESC");
$stmt->execute(['contact' => $contact]);
$lost_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Lost & Found History</title>
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

<h2>My Lost Item Reports</h2>
<table>
  <tr><th>Item</th><th>Description</th><th>Date</th><th>Location</th><th>Status</th></tr>
<?php foreach ($lost_items as $row): ?>
  <tr>
    <td><?= htmlspecialchars($row['item_name']) ?></td>
    <td><?= htmlspecialchars($row['description']) ?></td>
    <td><?= htmlspecialchars($row['lost_date']) ?></td>
    <td><?= htmlspecialchars($row['location']) ?></td>
    <td>
      <span style="color:<?= $row['status'] == 'resolved' ? 'green' : ($row['status'] == 'found' ? 'blue' : 'orange') ?>">
        <?= ucfirst(htmlspecialchars($row['status'])) ?>
      </span>
    </td>
  </tr>
<?php endforeach; ?>
</table>

</body>
</html>
