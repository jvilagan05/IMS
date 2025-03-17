<?php
session_start();
include 'db.php'; // Database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Prevent caching after logout
header("Cache-Control: no-cache, must-revalidate, no-store, private");
header("Pragma: no-cache");
header("Expires: 0");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tool_name = trim($_POST['tool_name']);
    $brand = trim($_POST['brand']);
    $quantity = $_POST['quantity'];
    $item_condition = trim($_POST['item_condition']);
    
  

    // Image Upload Handling (Optional)
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_path = null;
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $image_name = basename($_FILES["image"]["name"]);
        $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array(strtolower($image_extension), $allowed_extensions)) {
            echo "<script>alert('Error: Invalid file type! Allowed: JPG, JPEG, PNG, GIF.'); window.location.href='add_tools.php';</script>";
            exit();
        }

        $image_path = $target_dir . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $image_name);
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $image_path)) {
            echo "<script>alert('Error: Failed to upload image!'); window.location.href='add_tools.php';</script>";
            exit();
        }
    }

    // Insert into database
    $query = "INSERT INTO tools (tool_name, brand, quantity, item_condition, image) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssiss", $tool_name, $brand, $quantity, $item_condition, $image_path);

    if ($stmt->execute()) {
        echo "<script>alert('Tool added successfully!'); window.location.href='tools.php';</script>";
    } else {
        echo "<script>alert('Error inserting into database: " . $stmt->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADD TOOL</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="tools.css">
    <link rel="icon" type="image/png" href="ejmt.png">

</head>
<body>
    

     <div class="container d-flex justify-content-center mt-5">
        <div class="form-container">
            <div class="card shadow-lg p-4">
                <h2 class="text-center text-danger">Add New Tool</h2>
                <form method="POST" enctype="multipart/form-data" class="needs-validation">
    <div class="mb-3">
        <label class="form-label">Tool Name:</label>
        <input type="text" name="tool_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Brand:</label>
        <input type="text" name="brand" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Quantity:</label>
        <input type="number" name="quantity" class="form-control" min="1" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Item Condition:</label>
        <textarea name="item_condition" class="form-control" id="item_condition" rows="3"></textarea>
        <small id="charCount" class="text-muted">0/200</small>
    </div>
    <div class="mb-3">
        <label class="form-label">Image (Optional):</label>
        <input type="file" name="image" class="form-control" accept="image/*">
    </div>
    <div class="d-grid">
        
        <button type="submit" class="btn btn-danger fw-bold">Add Tool</button>
        <a href="tools.php" class="btn btn-secondary">Back</a>
    </div>
</form>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Character count for item condition
        document.getElementById('item_condition').addEventListener('input', function() {
            let charCount = this.value.length;
            document.getElementById('charCount').textContent = charCount + "/200";
        });
    </script>
</body>
</html>
