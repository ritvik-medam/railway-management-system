<?php
// Include the user_id_loader.php which already starts the session
require_once 'user_id_loader.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php?error=session_expired");
    exit;
}

// Initialize variables for response
$message = "";
$status = "";
$ticketDetails = null;

// Database connection
$conn = new mysqli("localhost", "root", "NaNdu@79#05", "railway");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $email_phone = isset($_POST['email_phone']) ? $_POST['email_phone'] : '';
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    $feedback = isset($_POST['feedback']) ? $_POST['feedback'] : [];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
    $user_id = $_SESSION['id']; // Get user ID from session

    // Begin transaction
    $conn->begin_transaction();

    try {
        // First, check if the booking exists in the confirmed table and belongs to the user
        $check_stmt = $conn->prepare("
            SELECT c.*, b.train_id, t.train_name, s1.name as source, s2.name as destination 
            FROM confirmed c
            JOIN bookings b ON c.booking_id = b.booking_id
            JOIN trains t ON b.train_id = t.train_id
            JOIN stations s1 ON t.source_station_id = s1.station_id
            JOIN stations s2 ON t.destination_station_id = s2.station_id
            WHERE c.booking_id = ? AND c.user_id = ? AND c.status = 'confirmed'
        ");
        $check_stmt->bind_param("ii", $booking_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Booking found, get details for confirmation
            $ticketDetails = $result->fetch_assoc();
            
            // Insert into cancellations table for record keeping
            $cancel_stmt = $conn->prepare("
                INSERT INTO cancellations (
                    booking_id, user_id, train_id, journey_date, 
                    reason, feedback, remarks, amount_refunded, pnr
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $feedback_str = implode(", ", $feedback);
            $amount_refunded = $ticketDetails['total_amount']; // Full refund for simplicity
            
            $cancel_stmt->bind_param(
                "iiissssds", 
                $booking_id, $user_id, $ticketDetails['train_id'], 
                $ticketDetails['journey_date'], $reason, $feedback_str, 
                $remarks, $amount_refunded, $ticketDetails['pnr']
            );
            $cancel_stmt->execute();
            
            // Delete from confirmed table - implementing DELETE operation
            $delete_stmt = $conn->prepare("DELETE FROM confirmed WHERE booking_id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $booking_id, $user_id);
            $delete_stmt->execute();
            
            // REMOVED: Update the status in bookings table to 'cancelled' - since status column doesn't exist
            // Instead, we'll just keep the record in bookings table as is
            
            // Free up the seats in trainseats table by setting is_booked = 0 (if this table is being used)
            $seats_stmt = $conn->prepare("
                UPDATE trainseats 
                SET is_booked = 0 
                WHERE train_id = ? AND journey_date = ? 
                AND seat_number IN (
                    SELECT seat_number FROM passengers WHERE booking_id = ?
                )
            ");
            $seats_stmt->bind_param("isi", $ticketDetails['train_id'], $ticketDetails['journey_date'], $booking_id);
            $seats_stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            $status = "success";
            $message = "Your ticket has been successfully cancelled and a refund of ₹" . number_format($amount_refunded, 2) . " has been initiated to your original payment method.";
        } else {
            // Booking not found or not confirmed
            $status = "error";
            $message = "No confirmed booking found with this ID for your account. Please check the booking ID and try again.";
        }
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        $status = "error";
        $message = "An error occurred during cancellation: " . $e->getMessage();
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cancel Ticket - Indian Railways</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      box-sizing: border-box;
    }

    body {
      background-color: #f2f2f2;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .container {
      background-color: #fff;
      padding: 30px;
      width: 460px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .container img {
      position: absolute;
      top: 15px;
      right: 15px;
      width: 50px;
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #333;
    }

    label {
      font-weight: 600;
      margin-top: 15px;
      display: block;
      color: #555;
    }

    input, textarea, select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
    }

    textarea {
      resize: vertical;
      height: 80px;
    }

    .btn-group {
      display: flex;
      justify-content: space-between;
      margin-top: 25px;
    }

    .btn {
      flex: 1;
      background-color: #0056b3;
      color: white;
      padding: 12px;
      margin-right: 10px;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .btn:last-child {
      margin-right: 0;
      background-color: #6c757d;
    }

    .btn:hover {
      background-color: #004a99;
    }

    .btn:last-child:hover {
      background-color: #5a6268;
    }

    .footer {
      text-align: center;
      margin-top: 25px;
      font-size: 0.9rem;
      color: #444;
    }

    .footer i {
      color: #007bff;
      margin-left: 5px;
      cursor: pointer;
    }

    .mcq {
      margin-top: 15px;
    }

    .mcq label {
      font-weight: normal;
      margin-left: 5px;
    }

    .mcq input {
      width: auto;
      margin-right: 5px;
    }
    
    /* Styles for success/error messages */
    .message {
      padding: 15px;
      margin: 20px 0;
      border-radius: 6px;
      font-weight: 500;
    }
    
    .success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    /* Styles for confirmation details */
    .confirmation-details {
      background-color: #f8f9fa;
      padding: 20px;
      border-radius: 6px;
      margin-top: 20px;
      border: 1px solid #dee2e6;
    }
    
    .confirmation-details h3 {
      color: #0056b3;
      margin-bottom: 15px;
      font-size: 1.1rem;
    }
    
    .confirmation-row {
      display: flex;
      justify-content: space-between;
      border-bottom: 1px solid #dee2e6;
      padding: 10px 0;
    }
    
    .confirmation-label {
      font-weight: 600;
      color: #495057;
    }
    
    .confirmation-value {
      font-weight: 500;
      color: #0056b3;
    }
  </style>
</head>
<body>
  <div class="container">
    <img src="https://cdn-icons-png.flaticon.com/512/3774/3774293.png" alt="Train Logo">
    <h2>Cancel Your Ticket</h2>
    
    <?php if ($status === "success"): ?>
    <!-- Success message and ticket details -->
    <div class="message success">
      <i class="fas fa-check-circle"></i> <?php echo $message; ?>
    </div>
    
    <div class="confirmation-details">
      <h3>Cancelled Ticket Details</h3>
      
      <div class="confirmation-row">
        <div class="confirmation-label">PNR Number</div>
        <div class="confirmation-value"><?php echo htmlspecialchars($ticketDetails['pnr']); ?></div>
      </div>
      
      <div class="confirmation-row">
        <div class="confirmation-label">Train</div>
        <div class="confirmation-value"><?php echo htmlspecialchars($ticketDetails['train_name']); ?></div>
      </div>
      
      <div class="confirmation-row">
        <div class="confirmation-label">Journey</div>
        <div class="confirmation-value">
          <?php echo htmlspecialchars($ticketDetails['source']); ?> to 
          <?php echo htmlspecialchars($ticketDetails['destination']); ?>
        </div>
      </div>
      
      <div class="confirmation-row">
        <div class="confirmation-label">Journey Date</div>
        <div class="confirmation-value">
          <?php echo date('d M Y', strtotime($ticketDetails['journey_date'])); ?>
        </div>
      </div>
      
      <div class="confirmation-row">
        <div class="confirmation-label">Refund Amount</div>
        <div class="confirmation-value">₹<?php echo number_format($ticketDetails['total_amount'], 2); ?></div>
      </div>
      
      <div class="confirmation-row">
        <div class="confirmation-label">Refund Status</div>
        <div class="confirmation-value">Processing (2-7 business days)</div>
      </div>
    </div>
    
    <div class="btn-group">
      <button class="btn" onclick="window.location.href='dashboard.php'">
        Return to Dashboard
      </button>
    </div>
    
    <?php elseif ($status === "error"): ?>
    <!-- Error message -->
    <div class="message error">
      <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
    </div>
    
    <div class="btn-group">
      <button class="btn" onclick="window.location.href='cancellation.php'">
        Try Again
      </button>
    </div>
    
    <?php else: ?>
    <!-- Cancellation form -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <label for="booking_id">Booking ID / Ticket Number</label>
      <input type="text" id="booking_id" name="booking_id" placeholder="Enter your ticket ID" required>

      <label for="email_phone">Email / Phone Number</label>
      <input type="text" id="email_phone" name="email_phone" placeholder="Registered Email or Phone" required>

      <label for="cancelDate">Cancellation Date</label>
      <input type="date" id="cancelDate" name="cancelDate" value="<?php echo date('Y-m-d'); ?>" required>

      <label for="reason">Select Reason for Cancellation</label>
      <select id="reason" name="reason" required>
        <option value="">--Select Reason--</option>
        <option value="Plan Changed">Plan Changed</option>
        <option value="Train Delayed">Train Delayed</option>
        <option value="Booked by Mistake">Booked by Mistake</option>
        <option value="Opted for Another Transport">Opted for Another Transport</option>
        <option value="Others">Others</option>
      </select>

      <div class="mcq">
        <p style="margin-top: 10px;"><strong>Additional Feedback:</strong></p>
        <input type="checkbox" id="late" name="feedback[]" value="Train was running late">
        <label for="late">Train was running late</label><br>
        <input type="checkbox" id="service" name="feedback[]" value="Poor Service">
        <label for="service">Poor Service</label><br>
        <input type="checkbox" id="clean" name="feedback[]" value="Unclean Coach">
        <label for="clean">Unclean Coach</label><br>
        <input type="checkbox" id="overcrowd" name="feedback[]" value="Too Crowded">
        <label for="overcrowd">Too Crowded</label>
      </div>

      <label for="remarks">Any Other Comments (Optional)</label>
      <textarea id="remarks" name="remarks" placeholder="Write here if any..."></textarea>

      <div class="btn-group">
        <button type="submit" class="btn">Request Cancellation</button>
        <button type="button" class="btn" onclick="history.back()">Go Back</button>
      </div>
    </form>
    <?php endif; ?>

    <div class="footer">
      Need help? Call <strong>1800-111-999</strong> or click chat <i class="fas fa-comment-dots"></i>
    </div>
  </div>
  
  <script>
    // If there's a form submission error, scroll to the error message
    document.addEventListener('DOMContentLoaded', function() {
      const errorMessage = document.querySelector('.error');
      if (errorMessage) {
        errorMessage.scrollIntoView({ behavior: 'smooth' });
      }
      
      // Set today's date as default
      document.getElementById('cancelDate').valueAsDate = new Date();
    });
  </script>
</body>
</html>