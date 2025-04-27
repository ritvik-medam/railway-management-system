<?php
include 'db_connect.php';
session_start();
if (!isset($_SESSION['id'])) {
  // Redirect to login if not logged in
  header("Location: login.php");
  exit;
}

function getTrainName($conn, $train_id) {
    $stmt = $conn->prepare("SELECT train_name FROM trains WHERE train_id = ?");
    $stmt->execute([$train_id]);
    return $stmt->fetchColumn() ?: 'Unknown Train';
}

$train_id = $_GET['train_id'] ?? null;
$journey_date = $_GET['date'] ?? null;
$passenger_count = $_GET['passengers'] ?? 1;
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Seat Selection - Indian Railways</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
    }
    body {
      background: url('https://wallpapercave.com/wp/wp3981788.jpg') no-repeat center center fixed;
      background-size: cover;
      color: #1d1d1f;
      padding: 2rem;
    }
    .selection-container {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 2rem;
      max-width: 700px;
      margin: auto;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    h1 {
      text-align: center;
      color: #0044cc;
      margin-bottom: 1.5rem;
    }
    label {
      font-weight: 600;
      display: block;
      margin-top: 1rem;
      margin-bottom: 0.3rem;
    }
    select, input {
      width: 100%;
      padding: 0.6rem;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-bottom: 1rem;
    }
    .submit-btn {
      background: #00aa88;
      color: white;
      padding: 0.7rem 1.2rem;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: bold;
      cursor: pointer;
      display: block;
      margin: auto;
    }
    .submit-btn:hover {
      background-color: #008a70;
    }
    .nav-btn {
      background: #0044cc;
      color: white;
      padding: 0.7rem 1.2rem;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: bold;
      cursor: pointer;
      display: block;
      margin: 1rem auto;
    }
    .nav-btn:hover {
      background-color: #003399;
    }
  </style>
    
</head>
<body>
  <div class="selection-container">
    <h1>Seat Selection</h1>
    
    <!-- Display journey info -->
    <div class="journey-info" style="margin-bottom: 1.5rem; padding: 1rem; background: #f5f5f5; border-radius: 8px;">
      <p><strong>Train:</strong> <?= htmlspecialchars(getTrainName($conn, $train_id)) ?></p>
      <p><strong>Date:</strong> <?= htmlspecialchars($journey_date) ?></p>
      <p><strong>Passengers:</strong> <?= $passenger_count ?></p>
    </div>

    <label for="group-id">Group ID (if booking for a group)</label>
    <input type="text" id="group-id" placeholder="Enter Group ID (optional)">

    <label for="coach-type">Coach Type</label>
    <select id="coach-type" onchange="generateSeatFields()">
      <option value="3AC">3AC</option>
      <option value="2AC">2AC</option>
      <option value="1AC">1AC</option>
      <option value="Sleeper">Sleeper</option>
    </select>

    <!-- Passenger count is now fixed from previous page -->
    <input type="hidden" id="num-passengers" value="<?= $passenger_count ?>">

    <div id="passenger-seats-section"></div>

    <button class="nav-btn" id="proceed-btn">Proceed to Passenger Info</button>
  </div>

  <script>
    // Corrected JavaScript - Only changed what's absolutely necessary
    const userId = <?php echo json_encode($_SESSION['id']); ?>;
    console.log("Logged-in User ID:", userId); // Optional for debug
    const passengerCount = <?= $passenger_count ?>;
    const seatData = {
      '3AC': { seats: 72, berths: ['Lower', 'Middle', 'Upper', 'Side Lower', 'Side Upper'] },
      '2AC': { seats: 54, berths: ['Lower', 'Upper', 'Side Lower', 'Side Upper'] },
      '1AC': { seats: 24, berths: ['Lower', 'Upper'] },
      'Sleeper': { seats: 72, berths: ['Lower', 'Middle', 'Upper', 'Side Lower', 'Side Upper'] }
    };

    // Generate seat selection fields
    function generateSeatFields() {
      const container = document.getElementById('passenger-seats-section');
      const coachType = document.getElementById("coach-type").value;
      container.innerHTML = "";

      for (let i = 1; i <= passengerCount; i++) {
        const div = document.createElement("div");
        div.innerHTML = `
          <h3>Passenger ${i}</h3>
          <label>Seat Number</label>
          <select id="seat-${i}" required></select>

          <label>Berth Type</label>
          <select id="berth-${i}" required></select>

          <label>Passenger Type</label>
          <select id="type-${i}" required>
            <option value="">Select</option>
            <option value="Adult">Adult</option>
            <option value="Child">Child</option>
            <option value="Senior">Senior Citizen</option>
            <option value ="Women">Women</option>
          </select>
          <hr>
        `;
        container.appendChild(div);

        // Populate seats
        const seatSelect = document.getElementById(`seat-${i}`);
        for (let j = 1; j <= seatData[coachType].seats; j++) {
          const option = new Option(j, j);
          seatSelect.add(option);
        }

        // Populate berths
        const berthSelect = document.getElementById(`berth-${i}`);
        seatData[coachType].berths.forEach(berth => {
          const option = new Option(berth, berth);
          berthSelect.add(option);
        });
      }
    }

    // Initialize on page load
    window.onload = generateSeatFields;

    // Proceed to next page
    document.getElementById('proceed-btn').addEventListener('click', function() {
      // Validate all selections
      let allValid = true;
      for (let i = 1; i <= passengerCount; i++) {
        if (!document.getElementById(`seat-${i}`).value || 
            !document.getElementById(`berth-${i}`).value ||
            !document.getElementById(`type-${i}`).value) {
          alert(`Please complete all selections for Passenger ${i}`);
          allValid = false;
          break;
        }
      }

      if (allValid) {
        // Prepare data
        const bookingData = {
          train_id: <?= $train_id ?>,
          journey_date: '<?= $journey_date ?>',
          coach_type: document.getElementById("coach-type").value,
          group_id: document.getElementById("group-id").value,
          passengers: []
        };

        // Add passenger data
        for (let i = 1; i <= passengerCount; i++) {
          bookingData.passengers.push({
            seat_number: document.getElementById(`seat-${i}`).value,
            berth_type: document.getElementById(`berth-${i}`).value,
            passenger_type: document.getElementById(`type-${i}`).value
          });
        }

        // Store in localStorage
        localStorage.setItem('booking_data', JSON.stringify(bookingData));
        
        // Redirect
        window.location.href = "passengers.php";
      }
    });
  </script>
</body>
</html>