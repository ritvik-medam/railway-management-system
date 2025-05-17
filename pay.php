<?php
// Include the user_id_loader.php which already starts the session
require_once 'user_id_loader.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php?error=session_expired");
    exit;
}

// Get current user ID
$current_user_id = $_SESSION['id'];

// Database connection
$conn = new mysqli("localhost", "root", "NaNdu@79#05", "railway");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all bookings for the current user with payment information
$bookings_stmt = $conn->prepare("
    SELECT b.booking_id, b.train_id, b.journey_date, b.coach_type, b.group_id as pnr,
           t.train_name, 
           s1.name as source, s2.name as destination,
           sch.departure_time, sch.arrival_time,
           p.payment_id, p.amount, p.payment_method, p.transaction_id, p.payment_status, p.payment_date,
           c.confirmation_id, c.status as booking_status
    FROM bookings b
    JOIN trains t ON b.train_id = t.train_id
    JOIN stations s1 ON t.source_station_id = s1.station_id
    JOIN stations s2 ON t.destination_station_id = s2.station_id
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    LEFT JOIN schedules sch ON b.train_id = sch.train_id AND b.journey_date = sch.journey_date
    LEFT JOIN confirmed c ON b.booking_id = c.booking_id
    WHERE b.user_id = ?
    ORDER BY b.booking_time DESC
");

$bookings_stmt->bind_param("i", $current_user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();

// Fetch all bookings
$bookings = [];
while ($booking = $bookings_result->fetch_assoc()) {
    // Get passenger count for this booking
    $passenger_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM passengers WHERE booking_id = ?");
    $passenger_count_stmt->bind_param("i", $booking['booking_id']);
    $passenger_count_stmt->execute();
    $count_result = $passenger_count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    $booking['passenger_count'] = $count_data['count'];
    $passenger_count_stmt->close();
    
    // Add to bookings array
    $bookings[] = $booking;
}
$bookings_stmt->close();

// Get cancellation history
$cancellations_stmt = $conn->prepare("
    SELECT c.*, t.train_name, s1.name as source, s2.name as destination
    FROM cancellations c
    JOIN trains t ON c.train_id = t.train_id
    JOIN stations s1 ON t.source_station_id = s1.station_id
    JOIN stations s2 ON t.destination_station_id = s2.station_id
    WHERE c.user_id = ?
    ORDER BY c.cancellation_date DESC
");
$cancellations_stmt->bind_param("i", $current_user_id);
$cancellations_stmt->execute();
$cancellations_result = $cancellations_stmt->get_result();

// Fetch all cancellations
$cancellations = [];
while ($cancellation = $cancellations_result->fetch_assoc()) {
    $cancellations[] = $cancellation;
}
$cancellations_stmt->close();

// Close connection
$conn->close();

// Helper function to format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Helper function to format time
function formatTime($time) {
    if (!$time) return 'TBD';
    return date('h:i A', strtotime($time));
}

// Helper function to determine card color based on payment status
function getStatusColor($payment_status, $booking_status, $journey_date) {
    // Check if journey date is in the past
    $past_journey = strtotime($journey_date) < strtotime(date('Y-m-d'));
    
    if ($past_journey) {
        return "completed-journey";
    } elseif ($booking_status == 'confirmed') {
        return "confirmed";
    } elseif ($payment_status == 'completed') {
        return "completed";
    } elseif ($payment_status == 'pending') {
        return "pending";
    } else {
        return "not-paid";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment History - Train Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root {
      --primary-color: #2c3e50;
      --primary-light: #34495e;
      --accent-color: #0044cc;
      --accent-light: #e9f3ff;
      --success-color: #28a745;
      --warning-color: #ffc107;
      --danger-color: #dc3545;
      --gray-light: #f8f9fa;
      --gray-medium: #dee2e6;
      --text-dark: #343a40;
      --text-muted: #6c757d;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
    }
    
    body {
      background-color: #f4f4f4;
      color: var(--text-dark);
      display: flex;
      min-height: 100vh;
    }
    
    .sidebar {
      width: 250px;
      background-color: var(--primary-color);
      color: #fff;
      display: flex;
      flex-direction: column;
      padding: 20px 0;
      position: fixed;
      height: 100vh;
    }
    
    .sidebar h2 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 20px;
    }
    
    .sidebar a {
      padding: 12px 20px;
      color: white;
      text-decoration: none;
      display: block;
      transition: background 0.3s;
    }
    
    .sidebar a:hover, .sidebar a.active {
      background-color: var(--primary-light);
    }
    
    .sidebar a.active {
      border-left: 4px solid var(--accent-color);
    }
    
    .main-content {
      flex: 1;
      padding: 30px;
      margin-left: 250px;
    }
    
    .header {
      margin-bottom: 30px;
    }
    
    .header h1 {
      font-size: 28px;
      font-weight: 700;
      color: var(--accent-color);
      margin-bottom: 10px;
    }
    
    .subheader {
      font-size: 16px;
      color: var(--text-muted);
    }
    
    .booking-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    
    .booking-card {
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .booking-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
      padding: 15px;
      border-bottom: 1px solid var(--gray-medium);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .card-header h3 {
      font-size: 16px;
      font-weight: 600;
      margin: 0;
    }
    
    .journey-date {
      font-size: 14px;
      color: var(--accent-color);
      font-weight: 500;
    }
    
    .card-body {
      padding: 15px;
    }
    
    .train-route {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .station {
      flex: 1;
    }
    
    .station-name {
      font-weight: 600;
      margin-bottom: 4px;
    }
    
    .station-time {
      font-size: 14px;
      color: var(--text-muted);
    }
    
    .route-line {
      flex: 1;
      height: 2px;
      background-color: var(--gray-medium);
      position: relative;
      margin: 0 10px;
    }
    
    .booking-details {
      margin-bottom: 15px;
    }
    
    .detail-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
      font-size: 14px;
    }
    
    .detail-label {
      color: var(--text-muted);
    }
    
    .pnr {
      font-weight: 600;
      color: var(--accent-color);
    }
    
    .card-footer {
      padding: 15px;
      background-color: var(--gray-light);
      border-top: 1px solid var(--gray-medium);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .payment-status {
      font-size: 13px;
      font-weight: 600;
      padding: 4px 8px;
      border-radius: 4px;
    }
    
    .status-completed {
      background-color: #e6f4ea;
      color: #1e7e34;
    }
    
    .status-confirmed {
      background-color: #e8f3ff;
      color: #0062cc;
    }
    
    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .status-not-paid {
      background-color: #f8d7da;
      color: #721c24;
    }

    .status-completed-journey {
      background-color: #d6d8db;
      color: #383d41;
    }
    
    .action-buttons {
      display: flex;
      gap: 10px;
    }
    
    .btn {
      padding: 6px 12px;
      border-radius: 5px;
      font-size: 13px;
      font-weight: 500;
      text-decoration: none;
      cursor: pointer;
      border: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.2s;
    }
    
    .btn i {
      margin-right: 4px;
    }
    
    .btn-primary {
      background-color: var(--accent-color);
      color: white;
    }
    
    .btn-primary:hover {
      background-color: #0033a0;
    }
    
    .btn-outline {
      background-color: transparent;
      border: 1px solid var(--gray-medium);
      color: var(--text-muted);
    }
    
    .btn-outline:hover {
      background-color: var(--gray-light);
    }
    
    .btn-success {
      background-color: var(--success-color);
      color: white;
    }
    
    .btn-success:hover {
      background-color: #218838;
    }
    
    .section-divider {
      margin: 30px 0;
      text-align: center;
      position: relative;
    }
    
    .section-divider::before {
      content: "";
      position: absolute;
      left: 0;
      top: 50%;
      width: 100%;
      height: 1px;
      background-color: var(--gray-medium);
      z-index: 0;
    }
    
    .section-title {
      background-color: #f4f4f4;
      padding: 0 15px;
      display: inline-block;
      position: relative;
      z-index: 1;
      font-size: 18px;
      color: var(--text-muted);
    }
    
    .cancellation-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
      background-color: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .cancellation-table th,
    .cancellation-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid var(--gray-medium);
    }
    
    .cancellation-table th {
      background-color: var(--gray-light);
      font-weight: 600;
      color: var(--text-dark);
    }
    
    .cancellation-table tr:last-child td {
      border-bottom: none;
    }
    
    .refund-amount {
      font-weight: 600;
      color: var(--success-color);
    }
    
    .empty-state {
      text-align: center;
      padding: 40px;
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .empty-state i {
      font-size: 48px;
      color: var(--gray-medium);
      margin-bottom: 15px;
    }
    
    .empty-state h3 {
      font-size: 20px;
      margin-bottom: 10px;
      color: var(--text-dark);
    }
    
    .empty-state p {
      color: var(--text-muted);
      margin-bottom: 20px;
    }
    
    .card-confirmed {
      border-left: 4px solid var(--accent-color);
    }
    
    .card-completed {
      border-left: 4px solid var(--success-color);
    }
    
    .card-pending {
      border-left: 4px solid var(--warning-color);
    }
    
    .card-not-paid {
      border-left: 4px solid var(--danger-color);
    }
    
    .card-completed-journey {
      border-left: 4px solid var(--text-muted);
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
      .booking-cards {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      }
    }
    
    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
      }
      
      .main-content {
        margin-left: 0;
      }
      
      body {
        flex-direction: column;
      }
      
      .booking-cards {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>Dashboard</h2>
    <a href="train info.html">Train Info</a>
    <a href="booktickets.php">Book Tickets</a>
    <a href="cancel.php">Cancel Ticket</a>
    <a href="lost.php">Report Lost Item</a>
    <a href="meal.php">Meal Menu</a>
    <a href="train-review .html">Give Feedback</a>
    <a href="payment_history.php" class="active">Payment History</a>
  </div>

  <div class="main-content">
    <div class="header">
      <h1>Your Booking History</h1>
      <p class="subheader">View all your bookings, payments, and ticket details in one place</p>
    </div>

    <?php if (empty($bookings)): ?>
    <div class="empty-state">
      <i class="fas fa-ticket-alt"></i>
      <h3>No Bookings Found</h3>
      <p>You haven't made any bookings yet. Start planning your journey today!</p>
      <a href="booktickets.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Book a Ticket
      </a>
    </div>
    <?php else: ?>

    <div class="booking-cards">
      <?php foreach ($bookings as $booking): 
        $status_class = getStatusColor(
          isset($booking['payment_status']) ? $booking['payment_status'] : 'not-paid',
          isset($booking['booking_status']) ? $booking['booking_status'] : '',
          $booking['journey_date']
        );
      ?>
      <div class="booking-card card-<?php echo $status_class; ?>">
        <div class="card-header">
          <h3><?php echo htmlspecialchars($booking['train_name']); ?></h3>
          <span class="journey-date"><?php echo formatDate($booking['journey_date']); ?></span>
        </div>
        <div class="card-body">
          <div class="train-route">
            <div class="station">
              <div class="station-name"><?php echo htmlspecialchars($booking['source']); ?></div>
              <div class="station-time"><?php echo formatTime($booking['departure_time']); ?></div>
            </div>
            <div class="route-line"></div>
            <div class="station">
              <div class="station-name"><?php echo htmlspecialchars($booking['destination']); ?></div>
              <div class="station-time"><?php echo formatTime($booking['arrival_time']); ?></div>
            </div>
          </div>
          
          <div class="booking-details">
            <div class="detail-row">
              <span class="detail-label">Booking ID:</span>
              <span><?php echo $booking['booking_id']; ?></span>
            </div>
            <?php if (!empty($booking['pnr'])): ?>
            <div class="detail-row">
              <span class="detail-label">PNR Number:</span>
              <span class="pnr"><?php echo htmlspecialchars($booking['pnr']); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
              <span class="detail-label">Coach Type:</span>
              <span><?php echo htmlspecialchars($booking['coach_type']); ?></span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Passengers:</span>
              <span><?php echo $booking['passenger_count']; ?></span>
            </div>
            <?php if (isset($booking['amount'])): ?>
            <div class="detail-row">
              <span class="detail-label">Amount:</span>
              <span>₹<?php echo number_format($booking['amount'], 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($booking['transaction_id'])): ?>
            <div class="detail-row">
              <span class="detail-label">Transaction ID:</span>
              <span><?php echo htmlspecialchars($booking['transaction_id']); ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>
        
        <div class="card-footer">
          <?php
            $status_text = "Not Paid";
            $status_class = "not-paid";
            
            if (isset($booking['booking_status']) && $booking['booking_status'] == 'confirmed') {
              $status_text = "Confirmed";
              $status_class = "confirmed";
            } elseif (isset($booking['payment_status'])) {
              if ($booking['payment_status'] == 'completed') {
                $status_text = "Payment Completed";
                $status_class = "completed";
              } elseif ($booking['payment_status'] == 'pending') {
                $status_text = "Payment Pending";
                $status_class = "pending";
              }
            }
            
            // Check if journey date is in the past
            if (strtotime($booking['journey_date']) < strtotime(date('Y-m-d'))) {
              $status_text = "Journey Completed";
              $status_class = "completed-journey";
            }
          ?>
          <span class="payment-status status-<?php echo $status_class; ?>">
            <?php echo $status_text; ?>
          </span>
          
          <div class="action-buttons">
            <?php if (isset($booking['payment_id']) || isset($booking['confirmation_id'])): ?>
            <a href="ticket.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary">
              <i class="fas fa-ticket-alt"></i> View Ticket
            </a>
            <?php elseif (!isset($booking['payment_id']) && strtotime($booking['journey_date']) >= strtotime(date('Y-m-d'))): ?>
            <a href="payment.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-success">
              <i class="fas fa-credit-card"></i> Pay Now
            </a>
            <?php endif; ?>
            
            <button class="btn btn-outline view-details">
              <i class="fas fa-info-circle"></i> Details
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($cancellations)): ?>
    <div class="section-divider">
      <span class="section-title">Cancellation History</span>
    </div>

    <table class="cancellation-table">
      <thead>
        <tr>
          <th>Train</th>
          <th>Journey Date</th>
          <th>Route</th>
          <th>PNR</th>
          <th>Cancellation Date</th>
          <th>Reason</th>
          <th>Refund Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cancellations as $cancellation): ?>
        <tr>
          <td><?php echo htmlspecialchars($cancellation['train_name']); ?></td>
          <td><?php echo formatDate($cancellation['journey_date']); ?></td>
          <td><?php echo htmlspecialchars($cancellation['source'] . ' to ' . $cancellation['destination']); ?></td>
          <td><?php echo htmlspecialchars($cancellation['pnr']); ?></td>
          <td><?php echo date('d M Y, h:i A', strtotime($cancellation['cancellation_date'])); ?></td>
          <td><?php echo htmlspecialchars($cancellation['reason']); ?></td>
          <td class="refund-amount">₹<?php echo number_format($cancellation['amount_refunded'], 2); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
    
    <?php endif; ?>
  </div>

  <script>
    // Toggle details view (would expand in a real implementation)
    document.querySelectorAll('.view-details').forEach(button => {
      button.addEventListener('click', function() {
        alert('In a full implementation, this would show more details about the booking in a modal or expanded view.');
      });
    });
  </script>
</body>
</html>