<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$section = $_GET['section'] ?? 'trains';

// Handle updates
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_train'])) {
            $stmt = $conn->prepare("UPDATE trains SET train_name = :name, source_station_id = :source, destination_station_id = :destination WHERE train_id = :id");
            $stmt->execute([
                'name' => $_POST['train_name'],
                'source' => $_POST['source_station_id'],
                'destination' => $_POST['destination_station_id'],
                'id' => $_POST['train_id']
            ]);
        } elseif (isset($_POST['update_station'])) {
            $stmt = $conn->prepare("UPDATE stations SET name = :name WHERE station_id = :id");
            $stmt->execute([
                'name' => $_POST['station_name'],
                'id' => $_POST['station_id']
            ]);
        } elseif (isset($_POST['update_schedule'])) {
            $stmt = $conn->prepare("UPDATE schedules SET train_id = :train_id, journey_date = :date, departure_time = :departure, arrival_time = :arrival, available_seats = :seats WHERE schedule_id = :id");
            $stmt->execute([
                'train_id' => $_POST['train_id'],
                'date' => $_POST['journey_date'],
                'departure' => $_POST['departure_time'],
                'arrival' => $_POST['arrival_time'],
                'seats' => $_POST['available_seats'],
                'id' => $_POST['schedule_id']
            ]);
        }
        header("Location: admin_portal.php?section=$section");
        exit();
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

$stationList = $conn->query("SELECT station_id, name FROM stations")->fetchAll(PDO::FETCH_ASSOC);
$trainList = $conn->query("SELECT train_id, train_name FROM trains")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Portal</title>
    <style>
        body { font-family: Arial; background: #f9fafb; margin: 0; padding: 20px; }
        h2 { text-align: center; color: #2c3e50; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background-color: #2563eb; color: white; }
        select, input[type="text"], input[type="date"], input[type="time"], input[type="number"], button {
            padding: 5px; border-radius: 5px; border: 1px solid #ccc; margin: 2px;
        }
        button {
            background-color: #2563eb; color: white; font-weight: bold; cursor: pointer; border: none;
        }
        button:hover { background-color: #1e40af; }
        .top-buttons { position: absolute; top: 20px; right: 30px; }
        .top-buttons a {
            background: #2563eb; color: white; padding: 8px 14px; text-decoration: none; margin-left: 10px;
            border-radius: 5px; font-weight: 600;
        }
        .top-buttons a:hover { background: #1e40af; }
    </style>
</head>
<body>
<div class="top-buttons">
    <a href="staff_portal.php">Staff</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <h2>Admin Portal</h2>

    <form method="GET">
        <label>Select Section: </label>
        <select name="section" onchange="this.form.submit()">
            <option value="trains" <?= $section == 'trains' ? 'selected' : '' ?>>Trains</option>
            <option value="stations" <?= $section == 'stations' ? 'selected' : '' ?>>Stations</option>
            <option value="schedules" <?= $section == 'schedules' ? 'selected' : '' ?>>Schedules</option>
        </select>
    </form>

    <?php if ($section == 'trains'): ?>
        <?php
        $rows = $conn->query("SELECT * FROM trains")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <table>
            <tr><th>ID</th><th>Name</th><th>Source Station</th><th>Destination Station</th><th>Action</th></tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <form method="POST">
                        <td><?= $row['train_id'] ?></td>
                        <td><input type="text" name="train_name" value="<?= htmlspecialchars($row['train_name']) ?>"></td>
                        <td>
                            <select name="source_station_id">
                                <?php foreach ($stationList as $station): ?>
                                    <option value="<?= $station['station_id'] ?>" <?= $station['station_id'] == $row['source_station_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($station['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="destination_station_id">
                                <?php foreach ($stationList as $station): ?>
                                    <option value="<?= $station['station_id'] ?>" <?= $station['station_id'] == $row['destination_station_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($station['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="hidden" name="train_id" value="<?= $row['train_id'] ?>">
                            <button name="update_train">Update</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </table>

    <?php elseif ($section == 'stations'): ?>
        <?php
        $rows = $conn->query("SELECT * FROM stations")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <table>
            <tr><th>ID</th><th>Name</th><th>Action</th></tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <form method="POST">
                        <td><?= $row['station_id'] ?></td>
                        <td><input type="text" name="station_name" value="<?= htmlspecialchars($row['name']) ?>"></td>
                        <td>
                            <input type="hidden" name="station_id" value="<?= $row['station_id'] ?>">
                            <button name="update_station">Update</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </table>

    <?php elseif ($section == 'schedules'): ?>
        <?php
        $rows = $conn->query("SELECT * FROM schedules")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <table>
            <tr><th>ID</th><th>Train</th><th>Date</th><th>Departure</th><th>Arrival</th><th>Seats</th><th>Action</th></tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <form method="POST">
                        <td><?= $row['schedule_id'] ?></td>
                        <td>
                            <select name="train_id">
                                <?php foreach ($trainList as $train): ?>
                                    <option value="<?= $train['train_id'] ?>" <?= $train['train_id'] == $row['train_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($train['train_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="date" name="journey_date" value="<?= $row['journey_date'] ?>"></td>
                        <td><input type="time" name="departure_time" value="<?= $row['departure_time'] ?>"></td>
                        <td><input type="time" name="arrival_time" value="<?= $row['arrival_time'] ?>"></td>
                        <td><input type="number" name="available_seats" value="<?= $row['available_seats'] ?>"></td>
                        <td>
                            <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                            <button name="update_schedule">Update</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
