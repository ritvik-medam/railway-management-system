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
    // Modified query to match the actual schema
    $booking_stmt = $conn->prepare("
        SELECT b.*, t.train_name, s1.name as source, s2.name as destination
        FROM bookings b
        JOIN trains t ON b.train_id = t.train_id
        JOIN stations s1 ON t.source_station_id = s1.station_id
        JOIN stations s2 ON t.destination_station_id = s2.station_id
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
        SELECT * FROM passengers WHERE booking_id = ?
    ");
    $passenger_stmt->bind_param("i", $booking_id);
    $passenger_stmt->execute();
    $passenger_result = $passenger_stmt->get_result();
    $passengers = [];
    while ($passenger = $passenger_result->fetch_assoc()) {
        $passengers[] = $passenger;
    }
    $passenger_stmt->close();
} else {
    die("Invalid booking ID");
}

// Calculate total amount
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

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get payment details from POST
    $payment_method = isset($_POST['payment_method']) ? $conn->real_escape_string($_POST['payment_method']) : '';
    $payment_details = [];
    
    switch ($payment_method) {
        case 'upi':
            $payment_details = [
                'app' => $_POST['upi_app'] ?? '',
                'id' => $_POST['upi_id'] ?? ''
            ];
            break;
        case 'card':
            $payment_details = [
                'number' => $_POST['card_number'] ?? '',
                'expiry' => $_POST['card_expiry'] ?? ''
            ];
            break;
        case 'netbanking':
            $payment_details = [
                'bank' => $_POST['bank_name'] ?? ''
            ];
            break;
        case 'wallet':
            $payment_details = [
                'wallet' => $_POST['wallet_name'] ?? ''
            ];
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid payment method']);
            exit;
    }
    
    // Generate transaction ID
    $transaction_id = 'TXN' . time() . rand(1000, 9999);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert payment record - MODIFIED to match your actual payment table structure
        $payment_details_json = json_encode($payment_details);
        
        // Check if payment_status column exists in the payments table
        $column_check = $conn->query("SHOW COLUMNS FROM payments LIKE 'payment_status'");
        
        if ($column_check->num_rows > 0) {
            // If payment_status column exists
            $payment_stmt = $conn->prepare("
                INSERT INTO payments (booking_id, user_id, amount, payment_method, payment_details, transaction_id, payment_status)
                VALUES (?, ?, ?, ?, ?, ?, 'completed')
            ");
            $payment_stmt->bind_param("iidsss", $booking_id, $current_user_id, $total_amount, $payment_method, $payment_details_json, $transaction_id);
        } else {
            // If payment_status column doesn't exist, use a simpler query
            $payment_stmt = $conn->prepare("
                INSERT INTO payments (booking_id, user_id, amount, payment_method, payment_details, transaction_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $payment_stmt->bind_param("iidsss", $booking_id, $current_user_id, $total_amount, $payment_method, $payment_details_json, $transaction_id);
        }
        
        $payment_stmt->execute();
        $payment_id = $conn->insert_id;
        $payment_stmt->close();
        
        // Update booking status - adjust fields if they exist in your schema
        $column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'");
        
        if ($column_check->num_rows > 0) {
            $update_stmt = $conn->prepare("
                UPDATE bookings SET payment_status = 'paid' WHERE booking_id = ?
            ");
            $update_stmt->bind_param("i", $booking_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        // Generate PNR number
        $pnr = 'PNR' . date('ymd') . rand(10000, 99999);
        $pnr_stmt = $conn->prepare("UPDATE bookings SET group_id = ? WHERE booking_id = ?");
        $pnr_stmt->bind_param("si", $pnr, $booking_id);
        $pnr_stmt->execute();
        $pnr_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'payment_id' => $payment_id,
            'transaction_id' => $transaction_id,
            'pnr' => $pnr,
            'redirect_url' => "ticket.php?booking_id=$booking_id"
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Payment - Indian Railways</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
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
    h1, h2 {
      color: #0044cc;
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .section {
      margin-bottom: 2rem;
    }
    .review-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1.5rem;
    }
    .review-table td, .review-table th {
      border: 1px solid #ddd;
      padding: 0.8rem;
    }
    .review-table th {
      background-color: #f2f2f2;
      text-align: left;
    }
    .passengers-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }
    .passengers-table th, .passengers-table td {
      border: 1px solid #ddd;
      padding: 0.5rem;
      text-align: left;
    }
    .passengers-table th {
      background-color: #f8f8f8;
    }
    .payment-option {
      margin-bottom: 1rem;
    }
    .dynamic-fields {
      display: none;
      margin-top: 1rem;
      padding: 1rem;
      border: 1px solid #eee;
      border-radius: 8px;
      background-color: #fafafa;
    }
    label {
      font-weight: 600;
      display: block;
      margin-bottom: 0.5rem;
    }
    select, input[type="text"], input[type="number"], input[type="password"] {
      width: 100%;
      padding: 0.7rem;
      margin-bottom: 1rem;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    .confirm-button {
      width: 100%;
      background: #00aa88;
      color: white;
      padding: 1rem;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .confirm-button:hover {
      background: #008a70;
    }
    .amount-display {
      text-align: right;
      font-size: 1.3rem;
      font-weight: 600;
      margin: 1rem 0;
    }
    .status-message {
      padding: 1rem;
      border-radius: 8px;
      margin: 1rem 0;
      display: none;
    }
    .success {
      background-color: #d4edda;
      color: #155724;
    }
    .error {
      background-color: #f8d7da;
      color: #721c24;
    }
    .user-info {
      background-color: #e9f3ff;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }
    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
      background-color: #fefefe;
      margin: 15% auto;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      width: 80%;
      max-width: 500px;
      animation: modalopen 0.3s;
    }
    @keyframes modalopen {
      from {opacity: 0; transform: translateY(-20px);}
      to {opacity: 1; transform: translateY(0);}
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #eee;
      margin-bottom: 15px;
      padding-bottom: 10px;
    }
    .modal-header h3 {
      margin: 0;
      color: #0044cc;
    }
    .close {
      color: #aaa;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    .close:hover {
      color: #333;
    }
    .modal-body {
      margin-bottom: 20px;
    }
    .modal-footer {
      display: flex;
      justify-content: space-between;
      border-top: 1px solid #eee;
      padding-top: 15px;
    }
    .modal-button {
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
    }
    .cancel-button {
      background-color: #f8f9fa;
      color: #6c757d;
    }
    .cancel-button:hover {
      background-color: #e9ecef;
    }
    .confirm-modal-button {
      background-color: #00aa88;
      color: white;
    }
    .confirm-modal-button:hover {
      background-color: #008a70;
    }
    .payment-success {
      text-align: center;
      margin: 20px 0;
    }
    .payment-success i {
      font-size: 50px;
      color: #28a745;
      margin-bottom: 15px;
    }
    .payment-success h3 {
      margin-bottom: 15px;
      color: #155724;
    }
    .ticket-button {
      display: inline-block;
      background-color: #0044cc;
      color: white;
      padding: 12px 25px;
      text-decoration: none;
      border-radius: 8px;
      font-weight: bold;
      margin-top: 10px;
      transition: background-color 0.3s;
    }
    .ticket-button:hover {
      background-color: #0033a0;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Confirm Booking & Payment</h1>
    
    <div class="user-info">
      Logged in as: User ID <?php echo $current_user_id; ?>
    </div>

    <div id="statusMessage" class="status-message"></div>

    <!-- Booking Review Section -->
    <div class="section">
      <h2>Booking Details</h2>
      <table class="review-table">
        <tr><th>Booking ID</th><td><?php echo $booking_id; ?></td></tr>
        <tr><th>Train</th><td><?php echo $booking['train_name'] ?? 'N/A'; ?> (<?php echo $booking['train_id']; ?>)</td></tr>
        <tr><th>Route</th><td><?php echo ($booking['source'] ?? 'N/A') . ' to ' . ($booking['destination'] ?? 'N/A'); ?></td></tr>
        <tr><th>Journey Date</th><td><?php echo $booking['journey_date']; ?></td></tr>
        <tr><th>Coach Type</th><td><?php echo $booking['coach_type']; ?></td></tr>
      </table>
      
      <h3>Passenger Details</h3>
      <table class="passengers-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Seat</th>
            <th>Berth</th>
            <th>Type</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($passengers as $passenger): ?>
          <tr>
            <td><?php echo htmlspecialchars($passenger['name']); ?></td>
            <td><?php echo $passenger['age']; ?></td>
            <td><?php echo htmlspecialchars($passenger['gender']); ?></td>
            <td><?php echo htmlspecialchars($passenger['seat_number']); ?></td>
            <td><?php echo htmlspecialchars($passenger['berth_type']); ?></td>
            <td><?php echo htmlspecialchars($passenger['passenger_type']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      
      <div class="amount-display">
        Total Amount: ₹<span id="totalAmount"><?php echo $total_amount; ?></span>
      </div>
    </div>

    <!-- Payment Section -->
    <div class="section">
      <h2>Select Payment Method</h2>
      <form id="paymentForm">
        <div class="payment-option">
          <label><input type="radio" name="payment" value="upi"> UPI</label>
        </div>
        <div class="payment-option">
          <label><input type="radio" name="payment" value="card"> Debit / Credit Card</label>
        </div>
        <div class="payment-option">
          <label><input type="radio" name="payment" value="netbanking"> Net Banking</label>
        </div>
        <div class="payment-option">
          <label><input type="radio" name="payment" value="wallet"> Wallet</label>
        </div>

        <!-- Dynamic Inputs -->
        <div class="dynamic-fields" id="upiFields">
          <label for="upiApp">Choose UPI App</label>
          <select id="upiApp" name="upi_app">
            <option>Google Pay</option>
            <option>PhonePe</option>
            <option>Paytm</option>
          </select>
          <label for="upiId">Enter UPI ID</label>
          <input type="text" id="upiId" name="upi_id" placeholder="example@upi" required />
        </div>

        <div class="dynamic-fields" id="cardFields">
          <label>Card Number</label>
          <input type="text" id="cardNumber" name="card_number" placeholder="XXXX XXXX XXXX XXXX" required />
          <label>CVV</label>
          <input type="password" id="cardCvv" name="card_cvv" maxlength="3" placeholder="123" required />
          <label>Expiry Date</label>
          <input type="text" id="cardExpiry" name="card_expiry" placeholder="MM/YY" required />
        </div>

        <div class="dynamic-fields" id="netbankingFields">
          <label>Select Bank</label>
          <select id="bankName" name="bank_name">
            <option>SBI</option>
            <option>HDFC</option>
            <option>ICICI</option>
            <option>Axis</option>
            <option>PNB</option>
          </select>
        </div>

        <div class="dynamic-fields" id="walletFields">
          <label>Choose Wallet</label>
          <select id="walletName" name="wallet_name">
            <option>Paytm</option>
            <option>Mobikwik</option>
            <option>Freecharge</option>
          </select>
        </div>

        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
        <input type="hidden" name="amount" value="<?php echo $total_amount; ?>">
        <button type="button" id="proceedBtn" class="confirm-button">Proceed to Pay</button>
      </form>
    </div>
  </div>

  <!-- Confirmation Modal -->
  <div id="confirmModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Confirm Payment</h3>
        <span class="close">&times;</span>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to proceed with the payment of ₹<span id="modalAmount"><?php echo $total_amount; ?></span>?</p>
      </div>
      <div class="modal-footer">
        <button class="modal-button cancel-button" id="cancelBtn">Cancel</button>
        <button class="modal-button confirm-modal-button" id="confirmBtn">Confirm Payment</button>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div id="successModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Payment Successful</h3>
        <span class="close">&times;</span>
      </div>
      <div class="modal-body">
        <div class="payment-success">
          <i class="fas fa-check-circle"></i>
          <h3>Your payment was successful!</h3>
          <p>Transaction ID: <span id="transactionId"></span></p>
          <p>PNR Number: <span id="pnrNumber"></span></p>
          <a href="#" id="ticketLink" class="ticket-button">Get Your Ticket</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Helper function to show status messages
    function showStatusMessage(message, isError = false) {
      const statusElement = document.getElementById('statusMessage');
      statusElement.textContent = message;
      statusElement.style.display = 'block';
      statusElement.className = 'status-message ' + (isError ? 'error' : 'success');
    }

    // Payment Option Logic
    const radios = document.querySelectorAll('input[name="payment"]');
    const upi = document.getElementById("upiFields");
    const card = document.getElementById("cardFields");
    const netbanking = document.getElementById("netbankingFields");
    const wallet = document.getElementById("walletFields");

    radios.forEach(radio => {
      radio.addEventListener("change", function () {
        upi.style.display = "none";
        card.style.display = "none";
        netbanking.style.display = "none";
        wallet.style.display = "none";
        if (this.value === "upi") upi.style.display = "block";
        if (this.value === "card") card.style.display = "block";
        if (this.value === "netbanking") netbanking.style.display = "block";
        if (this.value === "wallet") wallet.style.display = "block";
      });
    });

    // Modal functionality
    const confirmModal = document.getElementById("confirmModal");
    const successModal = document.getElementById("successModal");
    const proceedBtn = document.getElementById("proceedBtn");
    const cancelBtn = document.getElementById("cancelBtn");
    const confirmBtn = document.getElementById("confirmBtn");
    const closeBtns = document.querySelectorAll(".close");

    // Open confirmation modal when "Proceed to Pay" is clicked
    proceedBtn.addEventListener("click", function() {
      const selectedMethod = document.querySelector('input[name="payment"]:checked');
      if (!selectedMethod) {
        showStatusMessage("Please select a payment method!", true);
        return;
      }
      
      // Show the confirmation modal
      confirmModal.style.display = "block";
    });

    // Close modal when 'x' or 'Cancel' is clicked
    closeBtns.forEach(btn => {
      btn.addEventListener("click", function() {
        confirmModal.style.display = "none";
        successModal.style.display = "none";
      });
    });

    cancelBtn.addEventListener("click", function() {
      confirmModal.style.display = "none";
    });

    // Handle payment when 'Confirm Payment' is clicked
    confirmBtn.addEventListener("click", async function() {
      // Hide confirmation modal
      confirmModal.style.display = "none";
      
      const selectedMethod = document.querySelector('input[name="payment"]:checked');
      const formData = new FormData(document.getElementById("paymentForm"));
      formData.append('payment_method', selectedMethod.value);
      
      try {
        showStatusMessage("Processing payment...");
        
        const response = await fetch(window.location.href, {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        
        if (result.status === 'success') {
          showStatusMessage("Payment successful!");
          
          // Set values in success modal
          document.getElementById("transactionId").textContent = result.transaction_id;
          document.getElementById("pnrNumber").textContent = result.pnr;
          document.getElementById("ticketLink").href = result.redirect_url;
          
          // Show success modal
          successModal.style.display = "block";
          
          // Store transaction details in session storage for confirmation page
          sessionStorage.setItem('transaction_id', result.transaction_id);
          sessionStorage.setItem('payment_id', result.payment_id);
          sessionStorage.setItem('pnr', result.pnr);
        } else {
          showStatusMessage("Payment failed: " + result.message, true);
        }
      } catch (error) {
        showStatusMessage("Error processing payment: " + error.message, true);
        console.error("Error:", error);
      }
    });

    // Close modals when clicking outside
    window.addEventListener("click", function(event) {
      if (event.target === confirmModal) {
        confirmModal.style.display = "none";
      }
      if (event.target === successModal) {
        successModal.style.display = "none";
      }
    });
  </script>
</body>
</html>