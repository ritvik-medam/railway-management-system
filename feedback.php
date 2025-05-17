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

// Get all trains from the database
$trainsQuery = "SELECT t.train_id, t.train_name, s1.name as source, s2.name as destination 
                FROM trains t 
                JOIN stations s1 ON t.source_station_id = s1.station_id 
                JOIN stations s2 ON t.destination_station_id = s2.station_id
                ORDER BY t.train_name";
$trainsResult = $conn->query($trainsQuery);

// Initialize variables for form submission
$message = "";
$success = false;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user ID if logged in (modify based on your authentication system)
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
    
    // Get form data
    $passenger_name = $_POST['passenger_name'];
    $train_id = $_POST['train_id'];
    $train_number = $_POST['train_number'];
    $journey_date = $_POST['journey_date'];
    $overall_rating = intval(substr($_POST['overall_rating'], 0, 1)); // Extract the number of stars
    $punctuality = $_POST['q1'];
    $cleanliness = $_POST['q2'];
    $staff_behavior = $_POST['q3'];
    $seat_comfort = $_POST['q4'];
    $restroom_clean = $_POST['q5'];
    $food_service = $_POST['q6'];
    $lighting_safety = $_POST['q7'];
    $wifi_charging = $_POST['q8'];
    $security_feeling = $_POST['q9'];
    $recommendation = $_POST['q10'];
    $additional_feedback = !empty($_POST['additional_feedback']) ? $_POST['additional_feedback'] : '';
    $complaint_type = !empty($_POST['complaint_type']) ? $_POST['complaint_type'] : NULL;
    $complaint_details = !empty($_POST['complaint_details']) ? $_POST['complaint_details'] : NULL;
    $contact_info = !empty($_POST['contact_info']) ? $_POST['contact_info'] : NULL;
    $website_experience = $_POST['website_experience'];
    $website_feedback = !empty($_POST['website_feedback']) ? $_POST['website_feedback'] : '';
    
    // Direct approach without prepare/bind for debugging
    try {
        $sql = "INSERT INTO feedback (
                    passenger_name, train_id, train_number, journey_date, 
                    overall_rating, punctuality, cleanliness, staff_behavior, seat_comfort, 
                    restroom_clean, food_service, lighting_safety, wifi_charging, security_feeling, 
                    recommendation, additional_feedback, complaint_type, complaint_details, 
                    contact_info, website_experience, website_feedback
                ) VALUES (
                    '$passenger_name', $train_id, '$train_number', '$journey_date', 
                    $overall_rating, '$punctuality', '$cleanliness', '$staff_behavior', '$seat_comfort', 
                    '$restroom_clean', '$food_service', '$lighting_safety', '$wifi_charging', '$security_feeling', 
                    '$recommendation', '$additional_feedback', " . 
                    ($complaint_type ? "'$complaint_type'" : "NULL") . ", " . 
                    ($complaint_details ? "'$complaint_details'" : "NULL") . ", " . 
                    ($contact_info ? "'$contact_info'" : "NULL") . ", " . 
                    "'$website_experience', '$website_feedback'
                )";
                
        if ($conn->query($sql)) {
            $success = true;
            $message = "Thank you for your feedback! Your input helps us improve our services.";
            
            // Send email to admin (keeping this part unchanged)
            $admin_email = "admin@railway-team.com"; // Replace with your team email
            $subject = "New Train Review Submission";
            
            // Get train name
            $trainNameQuery = "SELECT train_name FROM trains WHERE train_id = $train_id";
            $trainResult = $conn->query($trainNameQuery);
            $trainRow = $trainResult->fetch_assoc();
            $train_name = $trainRow['train_name'];
            
            $email_body = "
                <html>
                <head>
                    <title>New Train Review Submission</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { max-width: 600px; margin: 0 auto; }
                        .header { background: #0044cc; color: white; padding: 10px; text-align: center; }
                        .content { padding: 20px; }
                        .rating { font-weight: bold; }
                        .footer { background: #f1f1f1; padding: 10px; text-align: center; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>New Feedback Received</h2>
                        </div>
                        <div class='content'>
                            <p><strong>Passenger:</strong> $passenger_name</p>
                            <p><strong>Train:</strong> $train_name (#$train_number)</p>
                            <p><strong>Journey Date:</strong> $journey_date</p>
                            <p><strong>Overall Rating:</strong> <span class='rating'>$overall_rating ⭐</span></p>
                            <p><strong>Additional Feedback:</strong> $additional_feedback</p>
                            " . ($complaint_type ? "<p><strong>Complaint Type:</strong> $complaint_type</p>" : "") . "
                            " . ($complaint_details ? "<p><strong>Complaint Details:</strong> $complaint_details</p>" : "") . "
                            " . ($contact_info ? "<p><strong>Contact Info:</strong> $contact_info</p>" : "") . "
                            <p><strong>Website Experience:</strong> $website_experience</p>
                            <p><strong>Website Feedback:</strong> $website_feedback</p>
                            <p><a href='http://yourwebsite.com/admin/feedback-details.php?id=" . $conn->insert_id . "'>View Full Details</a></p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message from Railway Feedback System</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            // Here you would typically add code to send the email
            // For example using mail() function or a library like PHPMailer
        }
    } catch (Exception $e) {
        $success = false;
        $message = "Error submitting feedback: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Train Review & Complaint Form</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0; padding: 0;
      font-family: 'Inter', sans-serif;
      box-sizing: border-box;
    }
    body {
      background: linear-gradient(to right, #dfe9f3, #ffffff);
      color: #333;
    }
    header {
      background: #0044cc;
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    header h1 {
      font-size: 1.5rem;
    }
    .top-logo img {
      height: 40px;
    }
    .container {
      max-width: 900px;
      background-color: white;
      margin: 2rem auto;
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    h2 {
      color: #0044cc;
      margin-bottom: 1rem;
    }
    form input, form select, form textarea {
      width: 100%;
      padding: 0.8rem;
      margin: 0.5rem 0 1rem;
      border-radius: 10px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }
    label {
      font-weight: 500;
    }
    .question {
      margin: 1rem 0;
    }
    .question p {
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    .question label {
      display: block;
      margin-left: 10px;
      margin-bottom: 0.4rem;
    }
    .submit-btn {
      width: 100%;
      background-color: #0044cc;
      color: white;
      border: none;
      padding: 1rem;
      border-radius: 12px;
      font-weight: bold;
      font-size: 1rem;
      cursor: pointer;
      transition: 0.3s ease;
    }
    .submit-btn:hover {
      background-color: #002d80;
    }
    .section {
      margin-top: 2.5rem;
    }
    .sample-review {
      background-color: #f0f4ff;
      padding: 1rem;
      border-left: 5px solid #0044cc;
      margin-bottom: 2rem;
      border-radius: 10px;
    }
    .sample-review h4 {
      margin-bottom: 0.5rem;
      color: #333;
    }
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 10px;
    }
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    .form-row {
      display: flex;
      gap: 15px;
      margin-bottom: 1rem;
    }
    .form-col {
      flex: 1;
    }
    @media (max-width: 768px) {
      .form-row {
        flex-direction: column;
        gap: 0;
      }
    }
  </style>
</head>
<body>

  <header>
    <h1>Train Review & Complaint Form</h1>
    <div class="top-logo">
      <img src="https://img.icons8.com/ios-filled/50/train.png" alt="Train Logo">
    </div>
  </header>

  <div class="container">
    <?php if (!empty($message)): ?>
      <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>
    
    <h2>🚆 Passenger & Train Details</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <input type="text" name="passenger_name" placeholder="Passenger Name" required />
      
      <div class="form-row">
        <div class="form-col">
          <select name="train_id" id="train_id" required onchange="updateTrainNumber()">
            <option value="">Select Train Name</option>
            <?php 
            if ($trainsResult->num_rows > 0) {
                while($row = $trainsResult->fetch_assoc()) {
                    echo '<option value="' . $row["train_id"] . '" data-number="' . $row["train_id"] . '">' 
                        . $row["train_name"] . ' (' . $row["source"] . ' to ' . $row["destination"] . ')</option>';
                }
            }
            ?>
          </select>
        </div>
        <div class="form-col">
          <input type="text" name="train_number" id="train_number" placeholder="Train Number" readonly required />
        </div>
      </div>
      
      <input type="date" name="journey_date" required />

      <h2>⭐ Rate Your Journey</h2>

      <select name="overall_rating" required>
        <option value="">Overall Journey Rating</option>
        <option>⭐</option>
        <option>⭐⭐</option>
        <option>⭐⭐⭐</option>
        <option>⭐⭐⭐⭐</option>
        <option>⭐⭐⭐⭐⭐</option>
      </select>

      <div class="question"><p>1. Was the train punctual?</p>
        <label><input type="radio" name="q1" value="Yes" required> Yes</label>
        <label><input type="radio" name="q1" value="No"> No</label>
      </div>

      <div class="question"><p>2. Was the coach clean?</p>
        <label><input type="radio" name="q2" value="Very Clean" required> Very Clean</label>
        <label><input type="radio" name="q2" value="Moderate"> Moderate</label>
        <label><input type="radio" name="q2" value="Unclean"> Unclean</label>
      </div>

      <div class="question"><p>3. How was the staff behavior?</p>
        <label><input type="radio" name="q3" value="Friendly" required> Friendly</label>
        <label><input type="radio" name="q3" value="Neutral"> Neutral</label>
        <label><input type="radio" name="q3" value="Rude"> Rude</label>
      </div>

      <div class="question"><p>4. Seat comfort level?</p>
        <label><input type="radio" name="q4" value="Excellent" required> Excellent</label>
        <label><input type="radio" name="q4" value="Good"> Good</label>
        <label><input type="radio" name="q4" value="Average"> Average</label>
        <label><input type="radio" name="q4" value="Poor"> Poor</label>
      </div>

      <div class="question"><p>5. Were the restrooms clean?</p>
        <label><input type="radio" name="q5" value="Yes" required> Yes</label>
        <label><input type="radio" name="q5" value="No"> No</label>
      </div>

      <div class="question"><p>6. Was food service available?</p>
        <label><input type="radio" name="q6" value="Yes" required> Yes</label>
        <label><input type="radio" name="q6" value="No"> No</label>
      </div>

      <div class="question"><p>7. Was the coach lighting/safety good?</p>
        <label><input type="radio" name="q7" value="Yes" required> Yes</label>
        <label><input type="radio" name="q7" value="No"> No</label>
      </div>

      <div class="question"><p>8. Was Wi-Fi or charging available?</p>
        <label><input type="radio" name="q8" value="Yes" required> Yes</label>
        <label><input type="radio" name="q8" value="No"> No</label>
      </div>

      <div class="question"><p>9. Did you feel secure during travel?</p>
        <label><input type="radio" name="q9" value="Yes" required> Yes</label>
        <label><input type="radio" name="q9" value="No"> No</label>
      </div>

      <div class="question"><p>10. Would you recommend this train?</p>
        <label><input type="radio" name="q10" value="Definitely" required> Definitely</label>
        <label><input type="radio" name="q10" value="Maybe"> Maybe</label>
        <label><input type="radio" name="q10" value="No"> No</label>
      </div>

      <textarea name="additional_feedback" rows="4" placeholder="Additional Feedback (optional)"></textarea>

      <div class="section">
        <h2>📬 Complaint Box (Optional)</h2>
        <select name="complaint_type">
          <option value="">Select Complaint Type</option>
          <option>Train Delay</option>
          <option>Unclean Coaches</option>
          <option>Misbehavior</option>
          <option>Food Quality</option>
          <option>Safety Concern</option>
          <option>Other</option>
        </select>
        <textarea name="complaint_details" rows="4" placeholder="Describe your issue..."></textarea>
        <input type="text" name="contact_info" placeholder="Contact (Phone/Email)" />
      </div>
      
      <div class="section">
        <h2>💻 Website Experience</h2>
        <div class="question"><p>How was your experience using our website?</p>
          <label><input type="radio" name="website_experience" value="Excellent" required> Excellent</label>
          <label><input type="radio" name="website_experience" value="Good"> Good</label>
          <label><input type="radio" name="website_experience" value="Average"> Average</label>
          <label><input type="radio" name="website_experience" value="Poor"> Poor</label>
        </div>
        <textarea name="website_feedback" rows="3" placeholder="How can we improve our website? (optional)"></textarea>
      </div>

      <div class="section sample-review">
        <h4>📌 Sample Review</h4>
        <p>
          "I had a pleasant journey. The coach was clean and the staff were cooperative. Seats were comfortable and the train was on time. Would love to travel again!"
        </p>
      </div>

      <button type="submit" class="submit-btn">Submit Review</button>
    </form>
  </div>

  <script>
    function updateTrainNumber() {
      const trainSelect = document.getElementById('train_id');
      const trainNumberInput = document.getElementById('train_number');
      
      if (trainSelect.value) {
        const selectedOption = trainSelect.options[trainSelect.selectedIndex];
        trainNumberInput.value = selectedOption.getAttribute('data-number');
      } else {
        trainNumberInput.value = '';
      }
    }
  </script>

</body>
</html>
<?php
// Close the database connection
$conn->close();
?>