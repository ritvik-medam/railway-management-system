<?php
session_start();

if (!isset($_SESSION['id'])) {
    // Redirect to login if not logged in
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Indian Railways - Book Train Tickets</title>
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
    }
    header {
      background: #ffffff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .logo {
      display: flex;
      align-items: center;
    }
    .logo img {
      height: 32px;
      margin-right: 0.5rem;
    }
    .logo h1 {
      font-size: 1.5rem;
      font-weight: 600;
    }
    .hero {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 2rem;
    }
    .search-box {
      background: rgba(255, 255, 255, 0.8);
      border-radius: 24px;
      padding: 2rem;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 420px;
      backdrop-filter: blur(10px);
    }
    .search-box input, .search-box select {
      width: 100%;
      padding: 0.75rem;
      margin-top: 0.5rem;
      margin-bottom: 1rem;
      border: 1px solid #ddd;
      border-radius: 10px;
      font-size: 1rem;
    }
    .swap-button {
      display: block;
      margin: -0.5rem auto 1rem auto;
      background: #00aa88;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      font-size: 1rem;
      transition: background 0.3s ease;
    }
    .swap-button:hover {
      background: #008a70;
    }
    .tabs {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 1.5rem;
    }
    .tabs button {
      flex: 1;
      padding: 0.75rem;
      border: none;
      border-radius: 10px;
      background: #e0e0e0;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s ease;
    }
    .tabs button:hover {
      background: #d5d5d5;
    }
    .tabs button.active {
      background: #0044cc;
      color: white;
    }
    .cta-button {
      background: #0044cc;
      color: white;
      padding: 1rem;
      border: none;
      width: 100%;
      border-radius: 12px;
      font-weight: bold;
      font-size: 1.1rem;
      cursor: pointer;
      text-align: center;
      display: block;
      text-decoration: none;
    }
    .cta-button:hover {
      background: #0033a0;
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">
      <img src="https://img.icons8.com/ios-filled/50/000000/train.png" alt="Train Icon">
      <h1 style="color: #00aa88;">Indian <span style="color:#0044cc;">Railways</span></h1>
    </div>
  </header>

  <section class="hero">
    <div class="search-box">
      <div class="tabs">
        <button class="active">One Way</button>
        <button>Round Trip</button>
      </div>
      <form id="searchForm" action="availabletrains.php" method="GET">
      <select id="fromStation" name="from">
        <option value="">From</option>
        <option>New Delhi</option>
        <option>Mumbai Central</option>
        <option>Chennai Central</option>
        <option>Kolkata Howrah</option>
        <option>Secunderabad</option>
        <option>Bangalore City</option>
        <option>Ahmedabad Junction</option>
        <option>Jaipur Junction</option>
        <option> Pune Junction</option>
        <option>Lucknow NR</option>
        <option>Bhopal Junction</option>
        <option>Patna Junction</option>
        <option>Guwahati</option>
        <option>Thiruvananthapuram Central</option>
        <option>Visakhapatnam</option>
        <option>Nagpur Junction</option>
        <option>Surat</option>
        <option>Varanasi Junction</option>
        <option>Coimbatore Junction</option>
        <option>Amritsar Junction</option>
      </select>

      <select id="toStation" name="to">
        <option value="">To</option>
        <option>New Delhi</option>
        <option>Mumbai Central</option>
        <option>Chennai Central</option>
        <option>Kolkata Howrah</option>
        <option>Secunderabad</option>
        <option>Banglore City</option>
        <option>Ahmedabad Junction</option>
        <option>Jaipur Junction</option>
        <option> Pune Junction</option>
        <option>Lucknow NR</option>
        <option>Bhopal Junction</option>
        <option>Patna Junction</option>
        <option>Guwahati</option>
        <option>Thiruvananthapuram Central</option>
        <option>Visakhapatnam</option>
        <option>Nagpur Junction</option>
        <option>Surat</option>
        <option>Varanasi Junction</option>
        <option>Coimbatore Junction</option>
        <option>Amritsar Junction</option>
      </select>

      <button type="button" class="swap-button" onclick="swapStations()">⇄ Swap</button>
      <input type="date" name="date" id="journeyDate">
      <input type="number" name="passengers" placeholder="No. of passengers" min="1" value="1">
      <button type="submit" class="cta-button" id="trainButton">Get times & tickets</button>
      </form>
    </div>
  </section>
  
  <script>
  const tabs = document.querySelectorAll('.tabs button');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
    });
  });

  function swapStations() {
    const from = document.getElementById('fromStation');
    const to = document.getElementById('toStation');
    const temp = from.value;
    from.value = to.value;
    to.value = temp;
  }

  document.getElementById("searchForm").addEventListener("submit", function(event) {
    const from = document.getElementById('fromStation').value;
    const to = document.getElementById('toStation').value;
    const date = document.getElementById('journeyDate').value;
    const passengers = document.querySelector('input[type="number"]').value;

    if (!from || !to || !date || !passengers) {
      event.preventDefault(); // Stop the form submission
      alert("Please fill all fields: From, To, Date, and Passengers");
      return false;
    }
    
    try {
        const userId = <?php echo json_encode($_SESSION['id']); ?>;
        console.log("Logged-in User ID:", userId); // Optional for debug
      
        // Store in localStorage for multi-page flow
        localStorage.setItem('searchParams', JSON.stringify({
            from: from,
            to: to,
            journey_date: date,
            passenger_count: passengers
        }));
    } catch(e) {
        console.error("Error storing data:", e);
    }
    
    // Form will submit naturally
    return true;
  });
  </script>
</body>
</html>