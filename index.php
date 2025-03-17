<?php
session_start(); // Start the session at the very beginning

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Replace with your actual authentication logic
    if ($username == "admin" && $password == "@dmin12345") {
        $_SESSION['user_id'] = 1; // Set session upon successful login
        header("Location: dashboard.php"); // Redirect to dashboard
        exit(); // Ensure no further code is executed
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EJMT Inventory</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" type="image/png" href="ejmt.png">
</head>
<body>
    
  <div class="page-container">
    
    <!-- Company Details Section -->
    <div class="company-details card">
      <img src="ejmt.png" alt="Company Logo" class="company-logo">
      <h1>EJMT TRADING</h1>
      <p class="tagline">Provider of Security & Systems Solution</p>
      <div class="contact-info">
        <p><strong>Address:</strong> Lot 43 Block 56 Phase 1 Mary Cris Complex Pasong Camachile II General Trias Cavite / 626A Blumentritt Road Sampaloc Manila </p>
        <p><strong>Telephone Number:</strong> (02) 253-4903 / 998-6904</p>
        <p><strong>Mobile Number:</strong> 0928-636-8392 / 0915-644-1778</p>
        <p>
          <strong>Email:</strong>
          <a href="mailto:info@ejmtech.com">info@ejmtech.com</a> |
          <a href="mailto:jay@ejmtech.com">jay@ejmtech.com</a>
        </p>
        <p class="text-danger"><strong>VAT-REG TIN:</strong> 248-423-658-000</p>
      </div>
    </div>
    
    <!-- Login Form Section -->
    <div class="login-container card">
      <h3>LOGIN TO EJMT INVENTORY</h3>

      <?php if (isset($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
          <!-- Username Input -->
          <div class="input-container">
              <i class="fas fa-user"></i>
              <input type="text" name="username" placeholder="Username" required>
          </div>

          <!-- Password Input -->
          <div class="input-container">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" placeholder="Password" required>
          </div>

          <!-- Animated Login Button -->
          <button type="submit" class="login-button">Login</button>
      </form>
    </div>
  </div>
  
  <!-- FontAwesome Icons -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>