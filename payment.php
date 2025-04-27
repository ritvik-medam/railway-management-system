<?php
// Include the user_id_loader.php which already starts the session
require_once 'user_id_loader.php';
// Now $current_user_id is available everywhere from user_id_loader.php

// Database connection
$conn = new mysqli("localhost", "root", "NaNdu@79#05", "railway");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");
    
    // Verify user is logged in
    if (!isset($_SESSION['id'])) {
        echo json_encode([
            "status" => "error", 
            "message" => "User not logged in",
            "session_status" => session_status(),
            "session_id" => session_id()
        ]);
        exit;
    }

    // Get JSON input
    $rawData = file_get_contents("php://input");
    error_log("Received payment data: " . $rawData);
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
    $user_id = $current_user_id;
    $booking_id = isset($data['booking_id']) ? intval($data['booking_id']) : 0;
    $amount = isset($data['amount']) ? floatval($data['amount']) : 0;
    $payment_method = isset($data['payment_method']) ? $conn->real_escape_string($data['payment_method']) : '';
    $payment_details = isset($data['payment_details']) ? $conn->real_escape_string(json_encode($data['payment_details'])) : '';
    
    // Generate a transaction ID
    $transaction_id = 'TXN' . time() . rand(1000, 9999);
    
    // Validate data
    $errors = [];
    if ($user_id <= 0) $errors[] = "Invalid user_id";
    if ($booking_id <= 0) $errors[] = "Invalid booking_id";
    if ($amount <= 0) $errors[] = "Invalid amount";
    if (empty($payment_method)) $errors[] = "Payment method is required";

    if (!empty($errors)) {
        echo json_encode([
            "status" => "error",
            "message" => "Validation failed",
            "errors" => $errors
        ]);
        exit;
    }

    // First, check if the booking belongs to the current user
    $check_stmt = $conn->prepare("SELECT user_id FROM bookings WHERE booking_id = ?");
    $check_stmt->bind_param("i", $booking_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $booking = $result->fetch_assoc();
    $check_stmt->close();
    
    if (!$booking || $booking['user_id'] != $user_id) {
        echo json_encode([
            "status" => "error",
            "message" => "Booking not found or does not belong to current user"
        ]);
        exit;
    }

    // Insert payment record
    $stmt = $conn->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_method, payment_details, transaction_id, payment_status) VALUES (?, ?, ?, ?, ?, ?, 'completed')");
    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Prepare failed: " . $conn->error
        ]);
        exit;
    }
    
    $stmt->bind_param("iidsss", $booking_id, $user_id, $amount, $payment_method, $payment_details, $transaction_id);
    
    if (!$stmt->execute()) {
        echo json_encode([
            "status" => "error",
            "message" => "Execute failed: " . $stmt->error
        ]);
        exit;
    }
    
    $payment_id = $conn->insert_id;
    $stmt->close();

    // Update booking status if needed
    $update_stmt = $conn->prepare("UPDATE bookings SET payment_status = 'paid' WHERE booking_id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param("i", $booking_id);
        $update_stmt->execute();
        $update_stmt->close();
    }

    echo json_encode([
        "status" => "success", 
        "payment_id" => $payment_id,
        "transaction_id" => $transaction_id,
        "message" => "Payment processed successfully"
    ]);
    
    $conn->close();
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
    }
    .review-table td, .review-table th {
      border: 1px solid #ddd;
      padding: 0.8rem;
    }
    .review-table th {
      background-color: #f2f2f2;
      text-align: left;
    }
    .payment-option {
      margin-bottom: 1rem;
    }
    .dynamic-fields {
      display: none;
      margin-top: 1rem;
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
  </style>
</head>
<body>
  <div class="container">
    <h1>Confirm Booking & Payment</h1>

    <div id="statusMessage" class="status-message"></div>

    <!-- Booking Review Section -->
    <div class="section">
      <h2>Booking Details</h2>
      <table class="review-table" id="reviewTable">
        <tr><th>Booking ID</th><td id="bookingId"></td></tr>
        <tr><th>Train ID</th><td id="trainId"></td></tr>
        <tr><th>Journey Date</th><td id="journeyDate"></td></tr>
        <tr><th>Coach Type</th><td id="coachType"></td></tr>
        <tr><th>Number of Passengers</th><td id="passengerCount"></td></tr>
      </table>
      
      <div class="amount-display">
        Total Amount: ₹<span id="totalAmount">750</span>
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
          <select id="upiApp">
            <option>Google Pay</option>
            <option>PhonePe</option>
            <option>Paytm</option>
          </select>
          <label for="upiId">Enter UPI ID</label>
          <input type="text" id="upiId" placeholder="example@upi" required />
        </div>

        <div class="dynamic-fields" id="cardFields">
          <label>Card Number</label>
          <input type="text" id="cardNumber" placeholder="XXXX XXXX XXXX XXXX" required />
          <label>CVV</label>
          <input type="password" id="cardCvv" maxlength="3" placeholder="123" required />
          <label>Expiry Date</label>
          <input type="text" id="cardExpiry" placeholder="MM/YY" required />
        </div>

        <div class="dynamic-fields" id="netbankingFields">
          <label>Select Bank</label>
          <select id="bankName">
            <option>SBI</option>
            <option>HDFC</option>
            <option>ICICI</option>
            <option>Axis</option>
            <option>PNB</option>
          </select>
        </div>

        <div class="dynamic-fields" id="walletFields">
          <label>Choose Wallet</label>
          <select id="walletName">
            <option>Paytm</option>
            <option>Mobikwik</option>
            <option>Freecharge</option>
          </select>
        </div>

        <button type="submit" class="confirm-button">Proceed to Pay</button>
      </form>
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

    // Get URL parameters
    function getUrlParameter(name) {
      name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
      const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
      const results = regex.exec(location.search);
      return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

    // Get booking details
    const bookingId = getUrlParameter('booking_id');
    document.getElementById("bookingId").textContent = bookingId || "N/A";
    
    // Get booking data from localStorage
    const storedData = localStorage.getItem('booking_data');
    let bookingData = {};
    
    if (storedData) {
      try {
        bookingData = JSON.parse(storedData);
        document.getElementById("trainId").textContent = bookingData.train_id || "N/A";
        document.getElementById("journeyDate").textContent = bookingData.journey_date || "N/A";
        document.getElementById("coachType").textContent = bookingData.coach_type || "N/A";
        document.getElementById("passengerCount").textContent = bookingData.passengers ? bookingData.passengers.length : "N/A";
        
        // Calculate fare based on passenger count and coach type
        // This is a simplified calculation - you'd normally get this from the backend
        let farePerPassenger = 0;
        switch(bookingData.coach_type) {
          case "1AC": farePerPassenger = 1500; break;
          case "2AC": farePerPassenger = 1200; break;
          case "3AC": farePerPassenger = 750; break;
          case "SL": farePerPassenger = 350; break;
          default: farePerPassenger = 200;
        }
        
        const totalFare = farePerPassenger * (bookingData.passengers ? bookingData.passengers.length : 1);
        document.getElementById("totalAmount").textContent = totalFare;
      } catch (e) {
        console.error("Error parsing stored data:", e);
      }
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

    // Form Submission
    document.getElementById("paymentForm").addEventListener("submit", async function (e) {
      e.preventDefault();
      const selectedMethod = document.querySelector('input[name="payment"]:checked');
      if (!selectedMethod) {
        showStatusMessage("Please select a payment method!", true);
        return;
      }

      const paymentMethod = selectedMethod.value;
      let paymentDetails = {};
      
      // Get details based on payment method
      switch(paymentMethod) {
        case "upi":
          paymentDetails = {
            app: document.getElementById("upiApp").value,
            id: document.getElementById("upiId").value
          };
          break;
        case "card":
          paymentDetails = {
            number: document.getElementById("cardNumber").value,
            expiry: document.getElementById("cardExpiry").value
          };
          break;
        case "netbanking":
          paymentDetails = {
            bank: document.getElementById("bankName").value
          };
          break;
        case "wallet":
          paymentDetails = {
            wallet: document.getElementById("walletName").value
          };
          break;
      }

      // Prepare payment data
      const paymentData = {
        booking_id: parseInt(bookingId),
        amount: parseFloat(document.getElementById("totalAmount").textContent),
        payment_method: paymentMethod,
        payment_details: paymentDetails
      };

      try {
        // Send payment request to server
        const response = await fetch('process_payment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(paymentData)
        });

        const result = await response.json();
        
        if (result.status === 'success') {
          showStatusMessage("Payment successful! Redirecting to confirmation page...");
          
          // Store transaction ID in session storage for confirmation page
          sessionStorage.setItem('transaction_id', result.transaction_id);
          sessionStorage.setItem('payment_id', result.payment_id);
          
          // Redirect to confirmation page after a delay
          setTimeout(() => {
            window.location.href = `confirmation.php?booking_id=${bookingId}&payment_id=${result.payment_id}`;
          }, 2000);
        } else {
          showStatusMessage("Payment failed: " + result.message, true);
        }
      } catch (error) {
        showStatusMessage("Error processing payment: " + error.message, true);
        console.error("Error:", error);
      }
    });
  </script>
</body>
</html>