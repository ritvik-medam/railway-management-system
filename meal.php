<?php
// Start session and check if user is logged i
session_start();
include 'db_connect.php';
include 'user_id_loader.php';

// Redirect to login if no user is logged in
if (!isset($current_user_id)) {
    header("Location: login.php?redirect=meal_order");
    exit();
}

// Get user information
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Process form submission
$orderMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $passengerName = $_POST['passengerName'];
        $trainNo = $_POST['trainNo'];
        $coach = $_POST['coach'];
        $seat = $_POST['seat'];
        $mealType = $_POST['mealType'];
        $mealPref = $_POST['mealPref'];
        $addons = $_POST['addons'];
        $allergyNotes = $_POST['allergyNotes'];
        
        // Debug info
        $debug = false; // Set to true to see debug information
        if ($debug) {
            echo "<pre>";
            echo "SESSION DATA: ";
            print_r($_SESSION);
            echo "\nCURRENT USER ID: " . $current_user_id;
            echo "</pre>";
        }
        
        // Validate train ID - must be between 1 and 20
        if (!is_numeric($trainNo) || $trainNo < 1 || $trainNo > 20) {
            $orderMessage = '<div class="error-message">Enter a valid train ID between 1 and 20.</div>';
        } else {
            // Double check that we have a valid user ID
            if (empty($current_user_id)) {
                $orderMessage = '<div class="error-message">Session error. Please log in again.</div>';
            } else {
                // Insert meal order into database with the user's ID
                $stmt = $conn->prepare("INSERT INTO meals (user_id, passenger_name, train_number, coach, seat_number, meal_type, meal_preference, addons, allergy_notes) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $current_user_id, // This ensures the meal is linked to the logged-in user
                    $passengerName,
                    $trainNo,
                    $coach,
                    $seat,
                    $mealType,
                    $mealPref,
                    $addons,
                    $allergyNotes
                ]);
                
                // Get the new meal ID for confirmation
                $mealId = $conn->lastInsertId();
                
                $orderMessage = '<div class="success-message">Meal order #' . $mealId . ' submitted successfully!</div>';
            }
        }
    } catch (PDOException $e) {
        $orderMessage = '<div class="error-message">Error: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Meal Selection - Indian Railways</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      background: url('https://png.pngtree.com/background/20250115/original/pngtree-delicious-indian-food-on-wooden-table-with-copy-space-picture-image_16031499.jpg') no-repeat center center fixed;
      background-size: cover;
      backdrop-filter: blur(0.5px);
      color: #1d1d1f;
    }
    header {
      background: #ffffffd6;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .logo h1 {
      color: #00aa88;
      font-size: 1.5rem;
      font-weight: bold;
    }
    .logo span {
      color: #0044cc;
    }
    .user-info {
      display: flex;
      align-items: center;
    }
    .user-info p {
      margin-right: 15px;
    }
    .logout-btn {
      background: transparent;
      color: #0044cc;
      border: 1px solid #0044cc;
      padding: 0.5rem 1rem;
      border-radius: 5px;
      cursor: pointer;
    }
    .food-icon {
      height: 40px;
    }

    .container {
      max-width: 700px;
      background-color: #ffffffeb;
      margin: 2rem auto;
      padding: 2rem;
      border-radius: 20px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #0044cc;
    }

    label {
      font-weight: 500;
      margin-top: 1rem;
      display: block;
    }

    input, select, textarea {
      width: 100%;
      padding: 0.7rem;
      margin-top: 0.5rem;
      border-radius: 10px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    .row {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
    }

    .row > div {
      flex: 1;
    }

    .sample-menu {
      background: #f7f7f7;
      border-radius: 10px;
      padding: 1rem;
      margin-top: 1rem;
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .submit-btn {
      margin-top: 2rem;
      background-color: #0044cc;
      color: white;
      font-size: 1rem;
      font-weight: bold;
      padding: 1rem;
      border: none;
      width: 100%;
      border-radius: 12px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .submit-btn:hover {
      background-color: #003399;
    }

    .error-message {
      color: red;
      background: #ffeeee;
      padding: 10px;
      border-radius: 5px;
      margin: 10px 0;
      text-align: center;
    }
    
    .success-message {
      color: green;
      background: #eeffee;
      padding: 10px;
      border-radius: 5px;
      margin: 10px 0;
      text-align: center;
    }

    .order-history {
      margin-top: 20px;
      text-align: center;
    }
    
    .order-history a {
      color: #0044cc;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">
      <h1>Indian <span>Railways</span></h1>
    </div>
    <div class="user-info">
      <p>Welcome, <?php echo htmlspecialchars($user['username']); ?></p>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    <img src="https://cdn-icons-png.flaticon.com/512/1046/1046784.png" class="food-icon" alt="Meal Icon">
  </header>

  <div class="container">
    <h2>Meal Selection Form</h2>
    
    <?php echo $orderMessage; ?>

    <form id="mealForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <label for="passengerName">Passenger Name</label>
      <input type="text" id="passengerName" name="passengerName" placeholder="Enter your name" value="<?php echo htmlspecialchars($user['username']); ?>" required />

      <div class="row">
        <div>
          <label for="trainNo">Train ID</label>
          <input type="number" id="trainNo" name="trainNo" placeholder="Enter ID (1-20)" min="1" max="20" required />
        </div>
        <div>
          <label for="coach">Coach</label>
          <input type="text" id="coach" name="coach" placeholder="e.g., B3" required />
        </div>
        <div>
          <label for="seat">Seat Number</label>
          <input type="text" id="seat" name="seat" placeholder="e.g., 42" required />
        </div>
      </div>

      <label for="mealType">Meal Type</label>
      <select id="mealType" name="mealType" required>
        <option value="">--Select--</option>
        <option value="breakfast">Breakfast</option>
        <option value="lunch">Lunch</option>
        <option value="dinner">Dinner</option>
        <option value="snacks">Snacks</option>
      </select>

      <label for="mealPref">Meal Preference</label>
      <select id="mealPref" name="mealPref" required>
        <option value="">--Select--</option>
        <option value="veg">Vegetarian</option>
        <option value="nonveg">Non-Vegetarian</option>
        <option value="jain">Jain</option>
        <option value="custom">Custom</option>
      </select>

      <label for="addons">Add-ons</label>
      <select id="addons" name="addons">
        <option value="Water Bottle">Water Bottle</option>
        <option value="Extra Roti">Extra Roti</option>
        <option value="Snacks Box">Snacks Box</option>
        <option value="No Add-ons">No Add-ons</option>
      </select>

      <label for="allergyNotes">Health/Allergy Notes (Optional)</label>
      <textarea id="allergyNotes" name="allergyNotes" rows="3" placeholder="e.g., No dairy, gluten-free..."></textarea>

      <div class="sample-menu">
        <strong>Sample Menu:</strong><br>
        • Veg: Rice, Dal, Mixed Veg Curry, Chapati, Curd<br>
        • Non-Veg: Rice, Chicken Curry, Salad, Chapati<br>
        • Jain: Jeera Rice, Dal, Subzi (No onion/garlic)<br>
      </div>

      <button type="submit" class="submit-btn">Submit Meal Preference</button>
    </form>
    
    <div class="order-history">
      <a href="meal_history.php">View Your Previous Orders</a>
    </div>
  </div>
</body>
</html>