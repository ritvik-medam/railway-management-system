<?php 
session_start();

if (!isset($_SESSION['id'])) {
    // Redirect to login if not logged in
    header("Location: login.php");
    exit;
}
// DB connection
$host = 'localhost';
$dbname = 'railway';
$user = 'root';
$pass = 'root';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}



// Get parameters from URL
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';
$passenger_count = $_GET['passengers'] ?? 1; // NEW: Get passenger count

if (empty($date)) {
    echo "<p>Error: Journey date is required</p>";
    exit;
}

// Validate date format
$dateObj = DateTime::createFromFormat('Y-m-d', $date);
if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
    echo "<p>Error: Invalid date format. Please use YYYY-MM-DD format.</p>";
    exit;
}

// Fetch trains (existing query remains perfect)
$sql = "SELECT t.train_id, t.train_name, s1.name AS source, s2.name AS destination,
        sch.departure_time, sch.arrival_time, sch.available_seats 
        FROM trains t
        JOIN stations s1 ON t.source_station_id = s1.station_id
        JOIN stations s2 ON t.destination_station_id = s2.station_id
        JOIN schedules sch ON t.train_id = sch.train_id
        WHERE s1.name = :from AND s2.name = :to AND sch.journey_date = :date";

$stmt = $conn->prepare($sql);
$stmt->execute(['from' => $from, 'to' => $to, 'date' => $date]);
$trains = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Your existing head section remains unchanged -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Train Results - Indian Railways</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* Your existing CSS remains unchanged */
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
    body {
      background: url('https://i.pinimg.com/736x/df/ee/4e/dfee4efb05453172eaae20a8766a5446.jpg') no-repeat center center fixed;
      background-size: cover;
      color: #1d1d1f;
      padding: 2rem;
      font-size: 16px;
    }
    .results-container {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 16px;
      padding: 2rem;
      max-width: 900px;
      margin: auto;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    h1 {
      text-align: center;
      color: #0044cc;
      margin-bottom: 1.5rem;
      font-size: 2rem;
    }
    .train-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1rem;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s ease;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.95rem;
    }
    .train-card:hover { transform: scale(1.01); }
    .train-name {
      font-size: 1.2rem;
      font-weight: 600;
      color: #0044cc;
    }
    .details {
      margin-top: 0.5rem;
      color: #555;
    }
    .book-button {
      background: #00aa88;
      color: white;
      padding: 0.6rem 1rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      display: inline-block;
      margin-top: 0.8rem;
      transition: background 0.3s ease;
    }
    .book-button:hover {
      background: #008a70;
    }
    .train-card .train-info { flex: 1; }
  </style>
</head>
<body>
  <div class="results-container">
    <h1>Available Trains</h1>

    <?php if (count($trains) > 0): ?>
      <?php foreach ($trains as $train): ?>
        <div class="train-card">
          <div class="train-info">
            <div class="train-name"><?= htmlspecialchars($train['train_name']) ?> (<?= $train['train_id'] ?>)</div>
            <div class="details">
              From: <?= htmlspecialchars($train['source']) ?> |
              To: <?= htmlspecialchars($train['destination']) ?> |
              Departure: <?= date("h:i A", strtotime($train['departure_time'])) ?> |
              Arrival: <?= date("h:i A", strtotime($train['arrival_time'])) ?> |
              Seats: <?= $train['available_seats'] ?>
            </div>
          </div>
          <!-- NEW: Updated Book Now button with passenger count and storage -->
          <a href="seatselection.php?train_id=<?= $train['train_id'] ?>&date=<?= $date ?>&passengers=<?= $passenger_count ?>" 
             class="book-button"
             onclick="storeTrainSelection(<?= $train['train_id'] ?>, '<?= $date ?>', <?= $passenger_count ?>)">
             Book Now
          </a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align:center; font-size: 1.1rem; color:#555;">🚫 No trains found for your search.</p>
    <?php endif; ?>
  </div>

  <!-- NEW: JavaScript to store critical data -->
  <script>
    const userId = <?php echo json_encode($_SESSION['id']); ?>;
    console.log("Logged-in User ID:", userId); // Optional for debug
    function storeTrainSelection(trainId, date, passengers) {
      localStorage.setItem('selectedTrainId', trainId);
      localStorage.setItem('selectedJourneyDate', date);
      localStorage.setItem('passengerCount', passengers);
      
      // Store search params for back navigation
      const urlParams = new URLSearchParams(window.location.search);
      localStorage.setItem('searchParams', JSON.stringify({
        from: urlParams.get('from'),
        to: urlParams.get('to'),
        date: date,
        passengers: passengers
      }));
    }

    // Your existing goToResults function remains unchanged
    function goToResults() {
      const from = document.getElementById('fromStation').value;
      const to = document.getElementById('toStation').value;
      const date = document.querySelector('input[type="date"]').value;
      
      if (!date) {
        alert("Please select a journey date.");
        return;
      }

      if (!from || !to) {
        alert("Please select both 'From' and 'To' stations.");
        return;
      }

      window.location.href = `availabletrains.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&date=${encodeURIComponent(date)}`;
    }
  </script>
</body>
</html>