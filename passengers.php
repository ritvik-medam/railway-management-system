<?php
// First include the user_id_loader.php which already starts the session
require_once 'user_id_loader.php';
// Now $current_user_id is available everywhere from user_id_loader.php

error_log("Script accessed - Session ID: " . session_id());
error_log("Session contents: " . print_r($_SESSION, true));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");
    
    // Verify user is logged in - Use the consistent ID key from user_id_loader.php
    if (!isset($_SESSION['id'])) {
        echo json_encode([
            "status" => "error", 
            "message" => "User not logged in",
            "session_status" => session_status(),
            "session_id" => session_id()
        ]);
        exit;
    }

    // Database connection
    $conn = new mysqli("localhost", "root", "NaNdu@79#05", "railway");
    if ($conn->connect_error) {
        echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
        exit;
    }

    // Get JSON input
    $rawData = file_get_contents("php://input");
    error_log("Received raw data: " . $rawData);
    $data = json_decode($rawData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            "status" => "error", 
            "message" => "Invalid JSON: " . json_last_error_msg(),
            "raw_data" => $rawData
        ]);
        exit;
    }

    // Process data with proper validation
    // Use the consistent $current_user_id from user_id_loader.php
    $user_id = $current_user_id;
    $train_id = isset($data['train_id']) ? intval($data['train_id']) : 0;
    $journey_date = isset($data['journey_date']) ? $conn->real_escape_string($data['journey_date']) : '';
    $coach_type = isset($data['coach_type']) ? $conn->real_escape_string($data['coach_type']) : '';
    $group_id = isset($data['group_id']) ? $conn->real_escape_string($data['group_id']) : '';
    $passengers = isset($data['passengers']) && is_array($data['passengers']) ? $data['passengers'] : [];

    // Validate data with detailed error reporting
    $errors = [];
    if ($user_id <= 0) $errors[] = "Invalid user_id (must be positive integer)";
    if ($train_id <= 0) $errors[] = "Invalid train_id (must be positive integer)";
    if (empty($journey_date)) $errors[] = "Journey date is required";
    if (empty($coach_type)) $errors[] = "Coach type is required";
    if (empty($passengers)) $errors[] = "At least one passenger is required";

    if (!empty($errors)) {
        echo json_encode([
            "status" => "error",
            "message" => "Validation failed",
            "errors" => $errors,
            "received_data" => [
                "user_id" => $user_id,
                "train_id" => $train_id,
                "journey_date" => $journey_date,
                "coach_type" => $coach_type,
                "passengers_count" => count($passengers)
            ]
        ]);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert booking
        $booking_time = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, train_id, journey_date, coach_type, group_id, booking_time) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iissss", $user_id, $train_id, $journey_date, $coach_type, $group_id, $booking_time);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $booking_id = $conn->insert_id;
        $stmt->close();

        // Insert passengers
        $passenger_stmt = $conn->prepare("INSERT INTO passengers (booking_id, name, age, gender, passenger_type, seat_number, berth_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$passenger_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        foreach ($passengers as $index => $p) {
            if (!isset($p['name'], $p['age'], $p['gender'], $p['passenger_type'], $p['seat_number'], $p['berth_type'])) {
                throw new Exception("Missing required passenger fields for passenger $index");
            }

            $name = $conn->real_escape_string($p['name']);
            $age = intval($p['age']);
            $gender = $conn->real_escape_string($p['gender']);
            $passenger_type = $conn->real_escape_string($p['passenger_type']);
            $seat_number = $conn->real_escape_string($p['seat_number']);
            $berth_type = $conn->real_escape_string($p['berth_type']);

            $passenger_stmt->bind_param("isissss", $booking_id, $name, $age, $gender, $passenger_type, $seat_number, $berth_type);

            if (!$passenger_stmt->execute()) {
                throw new Exception("Failed to insert passenger $index: " . $passenger_stmt->error);
            }

            // Mark seat as booked
            $update = $conn->prepare("UPDATE trainseats SET is_booked = 1 WHERE train_id = ? AND journey_date = ? AND coach_type = ? AND seat_number = ?");
            if (!$update) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $update->bind_param("isss", $train_id, $journey_date, $coach_type, $seat_number);
            
            if (!$update->execute()) {
                throw new Exception("Failed to update seat status: " . $update->error);
            }
            
            $update->close();
        }
        
        $passenger_stmt->close();
        $conn->commit();

        echo json_encode([
            "status" => "success", 
            "booking_id" => $booking_id,
            "passengers_count" => count($passengers)
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage(),
            "trace" => $e->getTraceAsString()
        ]);
    }
    
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Passenger & Group Details</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
    }
    body {
      background: url('https://i.pinimg.com/736x/df/ee/4e/dfee4efb05453172eaae20a8766a5446.jpg') no-repeat center center fixed;
      background-size: cover;
      color: #1d1d1f;
      padding: 2rem;
    }
    .container {
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
    }
    .input-group {
      margin-bottom: 1.5rem;
    }
    label {
      display: block;
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    input, select {
      width: 100%;
      padding: 0.8rem;
      border-radius: 8px;
      border: 1px solid #ddd;
      font-size: 1rem;
      margin-top: 0.5rem;
    }
    .button-group {
      text-align: center;
      margin-top: 2rem;
    }
    button {
      background-color: #00aa88;
      color: white;
      padding: 1rem 2rem;
      border-radius: 8px;
      font-weight: 600;
      border: none;
      cursor: pointer;
    }
    button:hover {
      background-color: #008a70;
    }
    .add-passenger-btn {
      background-color: #0044cc;
      margin-top: 1rem;
      font-weight: 600;
    }
    .seat-info {
      background-color: #f0f8ff;
      padding: 0.5rem;
      margin-top: 0.5rem;
      border-radius: 8px;
      border: 1px solid #b0d8ff;
    }
    .debug-section {
      background: #f0f0f0;
      padding: 10px;
      margin-top: 20px;
      border-radius: 8px;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
<div style="background:yellow; padding:10px; margin-bottom:20px;">
    Current User ID: <?php echo $current_user_id ?? 'NOT LOGGED IN'; ?>
</div>
  <div class="container">
    <h1>Passenger & Group Details</h1>

    <div class="journey-info" style="margin-bottom: 1.5rem; padding: 1rem; background: #f5f5f5; border-radius: 8px;" id="journey-info">
      <!-- This will be populated by JavaScript -->
    </div>

    <!-- Group Booking Section -->
    <div class="input-group">
      <label for="group-id">Group ID (if applicable)</label>
      <input type="text" id="group-id" name="group-id" placeholder="Enter your group ID">
    </div>

    <!-- Passenger Details Section -->
    <div id="passenger-section">
      <!-- This will be populated by JavaScript -->
    </div>

    <!-- Submit Button -->
    <div class="button-group">
      <button type="submit" id="submit-btn">Proceed to Payment</button>
    </div>
    
    <!-- Debug section - can be removed in production -->
    <div class="debug-section" id="debug-info">
      <!-- Debug info will be shown here -->
    </div>
  </div>

  <script>
  // Initialize variables
  let bookingData = {};
  let passengerData = [];
  
  // On page load, retrieve booking data from localStorage
  window.addEventListener('DOMContentLoaded', function() {
    // Get booking data from localStorage (set in seatselection.php)
    const storedData = localStorage.getItem('booking_data');
    if (storedData) {
      try {
        bookingData = JSON.parse(storedData);
        document.getElementById('debug-info').innerHTML = `<p>Retrieved data from localStorage: ${JSON.stringify(bookingData)}</p>`;
        
        // Display journey info
        displayJourneyInfo();
        
        // Fill group ID if exists
        if (bookingData.group_id) {
          document.getElementById('group-id').value = bookingData.group_id;
        }
        
        // Generate passenger fields
        generatePassengerFields();
      } catch (e) {
        document.getElementById('debug-info').innerHTML = `<p>Error parsing stored data: ${e.message}</p>`;
      }
    } else {
      document.getElementById('debug-info').innerHTML = '<p>No booking data found in localStorage!</p>';
    }
  });
  
  // Display journey information
  function displayJourneyInfo() {
    const journeyInfo = document.getElementById('journey-info');
    journeyInfo.innerHTML = `
      <p><strong>Train ID:</strong> ${bookingData.train_id}</p>
      <p><strong>Journey Date:</strong> ${bookingData.journey_date}</p>
      <p><strong>Coach Type:</strong> ${bookingData.coach_type}</p>
      <p><strong>Passengers:</strong> ${bookingData.passengers ? bookingData.passengers.length : 0}</p>
    `;
  }
  
  // Generate passenger input fields
  function generatePassengerFields() {
    const passengerSection = document.getElementById('passenger-section');
    passengerSection.innerHTML = ''; // Clear existing fields
    
    if (!bookingData.passengers || bookingData.passengers.length === 0) {
      passengerSection.innerHTML = '<p>Error: No passenger data found!</p>';
      return;
    }
    
    bookingData.passengers.forEach((passenger, index) => {
      const div = document.createElement('div');
      div.className = 'input-group passenger';
      div.innerHTML = `
        <h3>Passenger ${index + 1}</h3>
        
        <label for="name-${index}">Full Name</label>
        <input type="text" id="name-${index}" name="name-${index}" placeholder="Enter passenger's full name" required>

        <label for="age-${index}">Age</label>
        <input type="number" id="age-${index}" name="age-${index}" placeholder="Enter passenger's age" required>

        <label for="gender-${index}">Gender</label>
        <select id="gender-${index}" name="gender-${index}" required>
          <option value="">Select</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </select>

        <label for="type-${index}">Passenger Type</label>
        <select id="type-${index}" name="type-${index}" required>
          <option value="${passenger.passenger_type}" selected>${passenger.passenger_type}</option>
        </select>

        <div class="seat-info">
          <label>Selected Seat & Berth:</label>
          <p><strong>Seat Number:</strong> ${passenger.seat_number}</p>
          <p><strong>Berth Type:</strong> ${passenger.berth_type}</p>
          
          <input type="hidden" id="seat-${index}" name="seat-${index}" value="${passenger.seat_number}">
          <input type="hidden" id="berth-${index}" name="berth-${index}" value="${passenger.berth_type}">
        </div>

        <label for="email-${index}">Email Address</label>
        <input type="email" id="email-${index}" name="email-${index}" placeholder="Enter email address" required>

        <label for="phone-${index}">Phone Number</label>
        <input type="tel" id="phone-${index}" name="phone-${index}" placeholder="Enter phone number" pattern="[0-9]{10}" required>
      `;
      
      passengerSection.appendChild(div);
    });
  }

  // Submit event handler
  document.getElementById('submit-btn').addEventListener('click', async function(e) {
    e.preventDefault();
    
    // Validate all fields
    const passengerCount = bookingData.passengers ? bookingData.passengers.length : 0;
    let isValid = true;
    let passengers = [];
    
    for (let i = 0; i < passengerCount; i++) {
      const name = document.getElementById(`name-${i}`).value;
      const age = document.getElementById(`age-${i}`).value;
      const gender = document.getElementById(`gender-${i}`).value;
      const type = document.getElementById(`type-${i}`).value;
      const seat = document.getElementById(`seat-${i}`).value;
      const berth = document.getElementById(`berth-${i}`).value;
      
      if (!name || !age || !gender || !type || !seat || !berth) {
        alert(`Please fill in all required fields for Passenger ${i+1}`);
        isValid = false;
        break;
      }
      
      passengers.push({
        name: name,
        age: parseInt(age),
        gender: gender,
        passenger_type: type,
        seat_number: seat,
        berth_type: berth
      });
    }
    
    if (!isValid) return;
    
    // Debug data being sent
    console.log("Submitting data:", {
      train_id: bookingData.train_id,
      journey_date: bookingData.journey_date,
      coach_type: bookingData.coach_type,
      group_id: document.getElementById('group-id').value,
      passengers: passengers
    });
    
    try {
      const response = await fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          train_id: bookingData.train_id,
          journey_date: bookingData.journey_date,
          coach_type: bookingData.coach_type,
          group_id: document.getElementById('group-id').value,
          passengers: passengers
        })
      });
      
      const result = await response.json();
      console.log("Server response:", result);
      
      if (result.status === 'success') {
        // Clear localStorage since we're done with this booking
        localStorage.removeItem('booking_data');
        window.location.href = `payment.php?booking_id=${result.booking_id}`;
      } else {
        alert("Error: " + result.message);
        document.getElementById('debug-info').innerHTML = `<p>Error details: ${JSON.stringify(result)}</p>`;
      }
    } catch (error) {
      alert('Error: ' + error.message);
      console.error(error);
    }
  });
  </script>
</body>
</html>