<?php
// Connection settings
$servername = "localhost"; // or "127.0.0.1"
$username = "root";        // your DB username
$password = "root";            // your DB password
$database = "railway"; // UPDATED database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $lost_date = $_POST['lost_date'];
    $location = $_POST['location'];
    $contact_info = $_POST['contact_info'];

    $sql = "INSERT INTO lost_items (item_name, description, lost_date, location, contact_info) 
            VALUES ('$item_name', '$description', '$lost_date', '$location', '$contact_info')";

    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>Record added successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error: " . $sql . "<br>" . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lost and Found Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f4f4f4;
        }
        form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            margin: auto;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<h2 style="text-align: center;">Lost and Found Item Submission</h2>

<form method="POST" action="">
    <label for="item_name">Item Name:</label>
    <input type="text" id="item_name" name="item_name" required>

    <label for="description">Item Description:</label>
    <textarea id="description" name="description" rows="4" required></textarea>

    <label for="lost_date">Date Lost:</label>
    <input type="date" id="lost_date" name="lost_date" required>

    <label for="location">Location Lost:</label>
    <input type="text" id="location" name="location" required>

    <label for="contact_info">Your Contact Info:</label>
    <input type="text" id="contact_info" name="contact_info" required>

    <button type="submit">Submit</button>
</form>

</body>
</html>