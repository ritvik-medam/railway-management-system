<?php
// Database connection
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "NaNdu@79#05";      // Replace with your database password
$dbname = "railway";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all trains for dropdown
$trainsQuery = "SELECT train_id, train_name FROM trains ORDER BY train_name";
$trainsResult = $conn->query($trainsQuery);

// Function to get station name by ID
function getStationName($conn, $stationId) {
    $stationQuery = "SELECT name FROM stations WHERE station_id = $stationId";
    $stationResult = $conn->query($stationQuery);
    if ($stationResult->num_rows > 0) {
        $station = $stationResult->fetch_assoc();
        return $station['name'];
    }
    return "Unknown Station";
}

// Handle AJAX request for train info
if (isset($_POST['action']) && $_POST['action'] == 'getTrainInfo') {
    $trainId = $_POST['trainId'];
    
    // Get train details
    $trainQuery = "SELECT t.train_id, t.train_name, 
                  t.source_station_id, t.destination_station_id,
                  src.name as source_name, dst.name as destination_name
                  FROM trains t 
                  JOIN stations src ON t.source_station_id = src.station_id
                  JOIN stations dst ON t.destination_station_id = dst.station_id
                  WHERE t.train_id = $trainId";
    
    $trainResult = $conn->query($trainQuery);
    
    // Get schedule details
    $scheduleQuery = "SELECT schedule_id, journey_date, departure_time, arrival_time, available_seats 
                    FROM schedules 
                    WHERE train_id = $trainId 
                    ORDER BY journey_date DESC 
                    LIMIT 1";
    
    $scheduleResult = $conn->query($scheduleQuery);
    
    $responseData = array();
    
    if ($trainResult->num_rows > 0 && $scheduleResult->num_rows > 0) {
        $train = $trainResult->fetch_assoc();
        $schedule = $scheduleResult->fetch_assoc();
        
        // Sample platform data (in real scenario, you'd get this from a platforms table)
        $platformNumber = rand(1, 5); // Random platform between 1-5 for demo
        
        $responseData = array(
            'found' => true,
            'trainId' => $train['train_id'],
            'trainName' => $train['train_name'],
            'sourceStation' => $train['source_name'],
            'destStation' => $train['destination_name'],
            'platformNum' => $platformNumber,
            'journeyDate' => $schedule['journey_date'],
            'departureTime' => $schedule['departure_time'],
            'arrivalTime' => $schedule['arrival_time'],
            'availableSeats' => $schedule['available_seats']
        );
    } else {
        $responseData = array('found' => false);
    }
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($responseData);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Railway Train Information</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1593951461309-d2d6ee1a7897?auto=format&fit=crop&w=1470&q=80') no-repeat center center fixed;
      background-size: cover;
      color: #fff;
      padding: 30px;
      margin: 0;
    }
    .container {
      max-width: 800px;
      background-color: rgba(0, 0, 0, 0.7);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      margin: auto;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #f8f9fa;
    }
    label, select, input, button {
      display: block;
      width: 100%;
      margin-bottom: 15px;
    }
    select, input {
      padding: 12px;
      border-radius: 8px;
      border: none;
      background-color: #f8f9fa;
      font-size: 16px;
      color: #333;
    }
    button {
      padding: 14px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      font-size: 16px;
      transition: background-color 0.3s;
    }
    button:hover {
      background-color: #3e8e41;
    }
    .info {
      background-color: #2c2c2c;
      padding: 20px;
      border-radius: 10px;
      margin-top: 25px;
    }
    .info-item {
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;
    }
    .info-label {
      font-weight: bold;
      color: #4CAF50;
    }
    .info-value {
      color: #f8f9fa;
    }
    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    .logo {
      width: 60px;
      height: auto;
    }
    .nav-buttons {
      margin-top: 25px;
      display: flex;
      justify-content: space-between;
    }
    a {
      color: #fff;
      text-decoration: none;
      background-color: #007bff;
      padding: 12px 20px;
      border-radius: 8px;
      transition: background-color 0.3s;
      font-weight: bold;
    }
    a:hover {
      background-color: #0056b3;
    }
    .search-options {
      display: flex;
      gap: 15px;
      margin-bottom: 15px;
    }
    .search-options label {
      display: inline-flex;
      align-items: center;
      cursor: pointer;
      width: auto;
    }
    .search-options input[type="radio"] {
      width: auto;
      margin-right: 5px;
    }
    #trainNumberInput {
      display: none;
    }
    .loading {
      text-align: center;
      padding: 20px;
      display: none;
    }
    .error-message {
      color: #ff6b6b;
      text-align: center;
      margin-top: 10px;
      display: none;
    }
    .station-info {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }
    .station {
      flex: 1;
      padding: 15px;
      background-color: rgba(0, 123, 255, 0.1);
      border-radius: 8px;
      text-align: center;
      position: relative;
    }
    .station:first-child {
      margin-right: 10px;
    }
    .station:last-child {
      margin-left: 10px;
    }
    .station:before {
      content: '';
      position: absolute;
      top: 50%;
      right: -30px;
      width: 50px;
      height: 2px;
      background-color: #4CAF50;
    }
    .station:first-child:before {
      display: block;
    }
    .station:last-child:before {
      display: none;
    }
    .station-title {
      font-weight: bold;
      color: #4CAF50;
      margin-bottom: 5px;
    }
    .route-arrow {
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: #4CAF50;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="top-bar">
      <h2>Railway Train Information System</h2>
      <img src="https://img.freepik.com/premium-vector/train-logo-concept-icon-illustration_683738-2658.jpg" class="logo" alt="Train Logo" />
    </div>

    <div class="search-options">
      <label><input type="radio" name="searchType" value="name" checked> Search by Train Name</label>
      <label><input type="radio" name="searchType" value="number"> Search by Train Number</label>
    </div>

    <select id="trainNameSelect">
      <option value="">Select a Train</option>
      <?php
      if ($trainsResult->num_rows > 0) {
          while($train = $trainsResult->fetch_assoc()) {
              echo '<option value="'.$train['train_id'].'">'.$train['train_name'].'</option>';
          }
      }
      ?>
    </select>

    <input type="text" id="trainNumberInput" placeholder="Enter Train Number (e.g., 12627)" />
    <button onclick="searchTrain()">Get Train Information</button>
    
    <div id="loading" class="loading">
      <p>Fetching train information...</p>
    </div>
    
    <div id="errorMessage" class="error-message">
      No data found. Please try another train.
    </div>

    <div id="trainInfo" class="info" style="display: none;">
      <div class="info-item">
        <span class="info-label">Train ID:</span>
        <span class="info-value" id="trainId"></span>
      </div>
      <div class="info-item">
        <span class="info-label">Train Name:</span>
        <span class="info-value" id="trainName"></span>
      </div>
      
      <div class="station-info">
        <div class="station">
          <div class="station-title">Source Station</div>
          <div id="sourceStation"></div>
        </div>
        <div class="route-arrow">→</div>
        <div class="station">
          <div class="station-title">Destination Station</div>
          <div id="destStation"></div>
        </div>
      </div>
      
      <div class="info-item">
        <span class="info-label">Platform Number:</span>
        <span class="info-value" id="platformNum"></span>
      </div>
      <div class="info-item">
        <span class="info-label">Journey Date:</span>
        <span class="info-value" id="journeyDate"></span>
      </div>
      <div class="info-item">
        <span class="info-label">Departure Time:</span>
        <span class="info-value" id="departureTime"></span>
      </div>
      <div class="info-item">
        <span class="info-label">Arrival Time:</span>
        <span class="info-value" id="arrivalTime"></span>
      </div>
      <div class="info-item">
        <span class="info-label">Available Seats:</span>
        <span class="info-value" id="availableSeats"></span>
      </div>
    </div>

    <div class="nav-buttons">
      <a href="index.php">Home Page</a>
      <a href="javascript:history.back()">Go Back</a>
    </div>
  </div>

  <script>
    // Format time from 24-hour to 12-hour format
    function formatTime(time) {
      const [hours, minutes, seconds] = time.split(':');
      const hour = parseInt(hours, 10);
      const suffix = hour >= 12 ? 'PM' : 'AM';
      const displayHour = ((hour + 11) % 12 + 1);
      return `${displayHour}:${minutes} ${suffix}`;
    }

    // Toggle between train name and train number search
    document.querySelectorAll('input[name="searchType"]').forEach(radio => {
      radio.addEventListener('change', function() {
        if (this.value === 'name') {
          document.getElementById('trainNameSelect').style.display = 'block';
          document.getElementById('trainNumberInput').style.display = 'none';
        } else {
          document.getElementById('trainNameSelect').style.display = 'none';
          document.getElementById('trainNumberInput').style.display = 'block';
        }
      });
    });

    function searchTrain() {
      const searchType = document.querySelector('input[name="searchType"]:checked').value;
      let trainId;
      
      if (searchType === 'name') {
        trainId = document.getElementById('trainNameSelect').value;
      } else {
        // In a real application, you would look up the train ID by number
        // For this demo, we'll just use the input value as the ID if it's numeric
        const trainNumber = document.getElementById('trainNumberInput').value;
        if (!isNaN(trainNumber) && trainNumber.trim() !== '') {
          trainId = parseInt(trainNumber);
        }
      }
      
      if (!trainId) {
        document.getElementById('errorMessage').style.display = 'block';
        document.getElementById('trainInfo').style.display = 'none';
        return;
      }
      
      // Show loading indicator
      document.getElementById('loading').style.display = 'block';
      document.getElementById('trainInfo').style.display = 'none';
      document.getElementById('errorMessage').style.display = 'none';
      
      // Create form data for AJAX request
      const formData = new FormData();
      formData.append('action', 'getTrainInfo');
      formData.append('trainId', trainId);
      
      // Make AJAX request to get train info
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        // Hide loading indicator
        document.getElementById('loading').style.display = 'none';
        
        if (data.found) {
          // Update train info display
          document.getElementById('trainId').textContent = data.trainId;
          document.getElementById('trainName').textContent = data.trainName;
          document.getElementById('sourceStation').textContent = data.sourceStation;
          document.getElementById('destStation').textContent = data.destStation;
          document.getElementById('platformNum').textContent = data.platformNum;
          document.getElementById('journeyDate').textContent = data.journeyDate;
          document.getElementById('departureTime').textContent = formatTime(data.departureTime);
          document.getElementById('arrivalTime').textContent = formatTime(data.arrivalTime);
          document.getElementById('availableSeats').textContent = data.availableSeats;
          
          // Show train info
          document.getElementById('trainInfo').style.display = 'block';
        } else {
          // Show error message
          document.getElementById('errorMessage').style.display = 'block';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('errorMessage').style.display = 'block';
      });
    }
  </script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>