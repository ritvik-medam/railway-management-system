<?php
// Include the user_id_loader.php which already starts the session
require_once 'user_id_loader.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php?error=session_expired");
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "NaNdu@79#05", "railway");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get booking id from URL
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$current_user_id = $_SESSION['id']; // Get user ID from session

// Check if booking ID is valid and belongs to current user
if ($booking_id > 0) {
    // Get booking details with train, station, and schedule information
    $booking_stmt = $conn->prepare("
        SELECT b.*, t.train_name, t.train_id, 
               s1.name as source, s2.name as destination, 
               p.transaction_id, p.payment_date, p.payment_method, p.amount, p.payment_id,
               sch.departure_time, sch.arrival_time
        FROM bookings b
        JOIN trains t ON b.train_id = t.train_id
        JOIN stations s1 ON t.source_station_id = s1.station_id
        JOIN stations s2 ON t.destination_station_id = s2.station_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        LEFT JOIN schedules sch ON b.train_id = sch.train_id AND b.journey_date = sch.journey_date
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $booking_stmt->bind_param("ii", $booking_id, $current_user_id);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    $booking = $booking_result->fetch_assoc();
    $booking_stmt->close();

    if (!$booking) {
        die("Booking not found or does not belong to you");
    }

    // Get passenger details
    $passenger_stmt = $conn->prepare("
        SELECT * FROM passengers WHERE booking_id = ? ORDER BY passenger_id ASC
    ");
    $passenger_stmt->bind_param("i", $booking_id);
    $passenger_stmt->execute();
    $passenger_result = $passenger_stmt->get_result();
    $passengers = [];
    while ($passenger = $passenger_result->fetch_assoc()) {
        $passengers[] = $passenger;
    }
    $passenger_stmt->close();

    // Get seat details from trainseats table
    $seats_stmt = $conn->prepare("
        SELECT ts.* 
        FROM trainseats ts
        JOIN passengers p ON ts.seat_number = p.seat_number
        WHERE p.booking_id = ? AND ts.train_id = ? AND ts.journey_date = ? AND ts.coach_type = ?
    ");
    $seats_stmt->bind_param("iiss", $booking_id, $booking['train_id'], $booking['journey_date'], $booking['coach_type']);
    $seats_stmt->execute();
    $seats_result = $seats_stmt->get_result();
    $seats = [];
    while ($seat = $seats_result->fetch_assoc()) {
        $seats[] = $seat;
    }
    $seats_stmt->close();

    // Get user details
    $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $current_user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    $user_stmt->close();

    // Generate PNR if not already set
    if (empty($booking['group_id'])) {
        $pnr = "PNR" . date('ymd') . rand(10000, 99999);
        $update_pnr = $conn->prepare("UPDATE bookings SET group_id = ? WHERE booking_id = ?");
        $update_pnr->bind_param("si", $pnr, $booking_id);
        $update_pnr->execute();
        $update_pnr->close();
        $booking['group_id'] = $pnr;
    }
} else {
    die("Invalid booking ID");
}

// Calculate total amount if not already set in payment
$total_amount = isset($booking['amount']) ? $booking['amount'] : 0;
if ($total_amount == 0) {
    $fare_rates = [
        '1AC' => 1500,
        '2AC' => 1200,
        '3AC' => 750, 
        'SL' => 350,
        'Sleeper' => 350,
        'General' => 200
    ];

    $fare_per_passenger = isset($fare_rates[$booking['coach_type']]) ? $fare_rates[$booking['coach_type']] : 200;
    $total_amount = $fare_per_passenger * count($passengers);
} else {
    $fare_per_passenger = $total_amount / count($passengers);
}

// Format the departure and arrival times
function formatTime($time) {
    if (!$time) return 'TBD';
    return date('h:i A', strtotime($time));
}

$departure_time = isset($booking['departure_time']) ? formatTime($booking['departure_time']) : 'TBD';
$arrival_time = isset($booking['arrival_time']) ? formatTime($booking['arrival_time']) : 'TBD';

// Format journey date
$journey_date = date('d M Y', strtotime($booking['journey_date']));

// Format payment date
$payment_date = isset($booking['payment_date']) ? date('d M Y, h:i A', strtotime($booking['payment_date'])) : 'TBD';

// Count number of passengers (dynamic)
$num_passengers = count($passengers);

// Check if this booking has already been confirmed
$confirmation_check = $conn->prepare("SELECT * FROM confirmed WHERE booking_id = ?");
$confirmation_check->bind_param("i", $booking_id);
$confirmation_check->execute();
$confirmation_result = $confirmation_check->get_result();
$is_confirmed = $confirmation_result->num_rows > 0;
$confirmation_check->close();

// If payment exists but booking is not yet added to confirmed table, insert it
if (isset($booking['payment_id']) && !$is_confirmed) {
    $confirm_stmt = $conn->prepare("
        INSERT INTO confirmed (
            booking_id, user_id, train_id, journey_date, coach_type, 
            num_passengers, total_amount, payment_id, pnr, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
    ");
    $confirm_stmt->bind_param(
        "iiissidis", 
        $booking_id, $current_user_id, $booking['train_id'], 
        $booking['journey_date'], $booking['coach_type'], $num_passengers, 
        $total_amount, $booking['payment_id'], $booking['group_id']
    );
    $confirm_stmt->execute();
    $confirm_stmt->close();
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-Ticket - Indian Railways</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }
    body {
      background: linear-gradient(to bottom right, #f0f4f8, #ffffff);
      margin: 0;
      padding: 2rem;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }
    .container {
      background: #fff;
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 900px;
    }
    .ticket-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #0044cc;
      padding-bottom: 1rem;
      margin-bottom: 2rem;
    }
    .logo {
      display: flex;
      align-items: center;
    }
    .logo img {
      width: 50px;
      margin-right: 1rem;
    }
    .ticket-title {
      color: #0044cc;
      margin: 0;
    }
    .e-ticket-label {
      background-color: #e9f3ff;
      color: #0044cc;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-weight: bold;
    }
    .train-info {
      display: flex;
      justify-content: space-between;
      background-color: #f8f9fa;
      padding: 1.5rem;
      border-radius: 10px;
      margin-bottom: 2rem;
    }
    .train-details h2 {
      color: #0044cc;
      margin-top: 0;
      margin-bottom: 0.5rem;
    }
    .train-number {
      color: #6c757d;
      font-weight: 600;
    }
    .journey-date {
      font-weight: 600;
      color: #0044cc;
      margin-top: 0.5rem;
    }
    .pnr-section {
      text-align: right;
    }
    .pnr-box {
      background-color: #e9f3ff;
      padding: 0.8rem;
      border-radius: 8px;
      margin-bottom: 0.5rem;
    }
    .pnr-label {
      font-size: 0.9rem;
      color: #6c757d;
      margin: 0;
    }
    .pnr-value {
      font-size: 1.2rem;
      font-weight: bold;
      color: #0044cc;
      margin: 0.3rem 0 0 0;
    }
    .route-info {
      display: flex;
      justify-content: space-between;
      margin-bottom: 2rem;
    }
    .station {
      flex: 1;
      text-align: center;
    }
    .station-name {
      font-weight: 700;
      font-size: 1.2rem;
      margin-bottom: 0.3rem;
    }
    .station-time {
      font-weight: 600;
      color: #0044cc;
    }
    .route-line {
      flex: 2;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    .line {
      width: 80%;
      height: 2px;
      background-color: #0044cc;
    }
    .passenger-info {
      margin-bottom: 2rem;
    }
    .section-title {
      color: #0044cc;
      margin-bottom: 1rem;
    }
    .passengers-table {
      width: 100%;
      border-collapse: collapse;
    }
    .passengers-table th, .passengers-table td {
      border: 1px solid #dee2e6;
      padding: 0.8rem;
      text-align: left;
    }
    .passengers-table th {
      background-color: #f8f9fa;
    }
    .coach-info {
      display: flex;
      justify-content: space-between;
      margin-bottom: 2rem;
    }
    .coach-box {
      flex: 1;
      margin: 0 0.5rem;
      padding: 1rem;
      background-color: #f8f9fa;
      border-radius: 8px;
      text-align: center;
    }
    .coach-label {
      font-size: 0.9rem;
      color: #6c757d;
      margin-bottom: 0.3rem;
    }
    .coach-value {
      font-size: 1.1rem;
      font-weight: bold;
      color: #0044cc;
    }
    .payment-info {
      background-color: #f8f9fa;
      padding: 1.5rem;
      border-radius: 10px;
      margin-bottom: 2rem;
    }
    .payment-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
    }
    .payment-label {
      font-weight: 600;
    }
    .payment-value {
      text-align: right;
    }
    .total-amount {
      font-size: 1.2rem;
      font-weight: bold;
      color: #0044cc;
      border-top: 1px solid #dee2e6;
      padding-top: 0.5rem;
      margin-top: 0.5rem;
    }
    .actions {
      display: flex;
      justify-content: space-between;
      margin-top: 2rem;
    }
    .action-button {
      padding: 0.8rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
    }
    .action-button i {
      margin-right: 0.5rem;
    }
    .print-button {
      background-color: #0044cc;
      color: white;
    }
    .print-button:hover {
      background-color: #0033a0;
    }
    .download-button {
      background-color: #00aa88;
      color: white;
    }
    .download-button:hover {
      background-color: #008a70;
    }
    .important-notice {
      background-color: #fff3cd;
      border-left: 4px solid #ffc107;
      padding: 1rem;
      margin-top: 2rem;
      font-size: 0.9rem;
    }
    .important-notice h4 {
      margin-top: 0;
      color: #856404;
    }
    .qr-code {
      text-align: center;
      margin-bottom: 1rem;
    }
    .qr-code img {
      width: 120px;
      height: 120px;
    }
    .ticket-footer {
      text-align: center;
      margin-top: 2rem;
      color: #6c757d;
      font-size: 0.9rem;
      border-top: 1px solid #dee2e6;
      padding-top: 1rem;
    }
    .confirmation-badge {
      background-color: #28a745;
      color: white;
      padding: 0.4rem 0.8rem;
      border-radius: 8px;
      font-weight: bold;
      display: inline-block;
      margin-top: 1rem;
    }
    @media print {
      body {
        background: white;
        padding: 0;
      }
      .container {
        box-shadow: none;
        max-width: 100%;
      }
      .actions, .important-notice {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="ticket-header">
      <div class="logo">
        <img src="https://upload.wikimedia.org/wikipedia/en/thumb/8/83/Indian_Railways.svg/1200px-Indian_Railways.svg.png" alt="Indian Railways Logo">
        <h1 class="ticket-title">Indian Railways</h1>
      </div>
      <div class="e-ticket-label">
        E-TICKET
      </div>
    </div>

    <div class="train-info">
      <div class="train-details">
        <h2><?php echo htmlspecialchars($booking['train_name']); ?></h2>
        <div class="train-number">Train ID: <?php echo htmlspecialchars($booking['train_id']); ?></div>
        <div class="journey-date"><?php echo $journey_date; ?></div>
      </div>
      <div class="pnr-section">
        <div class="pnr-box">
          <p class="pnr-label">PNR Number</p>
          <p class="pnr-value"><?php echo htmlspecialchars($booking['group_id']); ?></p>
        </div>
        <div class="qr-code">
          <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($booking['group_id']); ?>&size=120x120" alt="PNR QR Code">
        </div>
      </div>
    </div>

    <div class="route-info">
      <div class="station">
        <div class="station-name"><?php echo htmlspecialchars($booking['source']); ?></div>
        <div class="station-time"><?php echo $departure_time; ?></div>
      </div>
      <div class="route-line">
        <div class="line"></div>
      </div>
      <div class="station">
        <div class="station-name"><?php echo htmlspecialchars($booking['destination']); ?></div>
        <div class="station-time"><?php echo $arrival_time; ?></div>
      </div>
    </div>

    <div class="coach-info">
      <div class="coach-box">
        <div class="coach-label">Coach Type</div>
        <div class="coach-value"><?php echo htmlspecialchars($booking['coach_type']); ?></div>
      </div>
      <div class="coach-box">
        <div class="coach-label">Booking Status</div>
        <div class="coach-value">Confirmed</div>
      </div>
      <div class="coach-box">
        <div class="coach-label">Booking ID</div>
        <div class="coach-value"><?php echo htmlspecialchars($booking['booking_id']); ?></div>
      </div>
      <div class="coach-box">
        <div class="coach-label">Passengers</div>
        <div class="coach-value"><?php echo $num_passengers; ?></div>
      </div>
    </div>

    <div class="passenger-info">
      <h3 class="section-title">Passenger Details</h3>
      <table class="passengers-table">
        <thead>
          <tr>
            <th>S.No</th>
            <th>Name</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Seat No.</th>
            <th>Berth</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($passengers as $index => $passenger): ?>
          <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($passenger['name']); ?></td>
            <td><?php echo $passenger['age']; ?></td>
            <td><?php echo htmlspecialchars($passenger['gender']); ?></td>
            <td><?php echo $passenger['seat_number']; ?></td>
            <td><?php echo htmlspecialchars($passenger['berth_type']); ?></td>
            <td>Confirmed</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="payment-info">
      <h3 class="section-title">Payment Information</h3>
      <div class="payment-row">
        <div class="payment-label">Transaction ID</div>
        <div class="payment-value"><?php echo isset($booking['transaction_id']) ? htmlspecialchars($booking['transaction_id']) : 'TBD'; ?></div>
      </div>
      <div class="payment-row">
        <div class="payment-label">Payment Method</div>
        <div class="payment-value"><?php echo isset($booking['payment_method']) ? htmlspecialchars(ucfirst($booking['payment_method'])) : 'TBD'; ?></div>
      </div>
      <div class="payment-row">
        <div class="payment-label">Payment Date</div>
        <div class="payment-value"><?php echo $payment_date; ?></div>
      </div>
      <div class="payment-row">
        <div class="payment-label">Fare per passenger</div>
        <div class="payment-value">₹<?php echo number_format($fare_per_passenger, 2); ?></div>
      </div>
      <div class="payment-row">
        <div class="payment-label">Number of passengers</div>
        <div class="payment-value"><?php echo $num_passengers; ?></div>
      </div>
      <div class="payment-row total-amount">
        <div class="payment-label">Total Amount</div>
        <div class="payment-value">₹<?php echo number_format($total_amount, 2); ?></div>
      </div>
      
      <?php if (isset($booking['payment_id'])): ?>
      <div class="confirmation-badge">PAYMENT CONFIRMED</div>
      <?php endif; ?>
    </div>

    <div class="important-notice">
      <h4>Important Information</h4>
      <ul>
        <li>This e-ticket is valid along with a government-issued photo ID in original. Please carry the ID proof during the journey.</li>
        <li>Passengers are advised to reach the station at least 30 minutes before the departure time.</li>
        <li>For any assistance, call our helpline at 139 or visit the official website.</li>
      </ul>
    </div>

    <div class="actions">
      <button class="action-button print-button" onclick="window.print()">
        <i class="fas fa-print"></i> Print Ticket
      </button>
      <button class="action-button download-button" onclick="downloadTicket()">
        <i class="fas fa-download"></i> Download PDF
      </button>
    </div>

    <div class="ticket-footer">
      <p>This is a computer-generated ticket and does not require a signature.</p>
      <p>&copy; <?php echo date('Y'); ?> Indian Railways. All rights reserved.</p>
    </div>
  </div>

  <script>
    // Function to download ticket as PDF (would require a PDF library in real implementation)
    function downloadTicket() {
      alert("Download functionality would be implemented using a PDF generation library like FPDF or TCPDF.");
      // In a real implementation, this would create and download a PDF
    }
  </script>
</body>
</html>