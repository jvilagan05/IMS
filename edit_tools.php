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

// Fetch tools for dropdown
$query = "SELECT * FROM tools";
$result = mysqli_query($conn, $query);
$tools = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tools[] = $row;
}

// Handle tool selection
$selected_tool = null;
if (isset($_GET['tool_id'])) {
    $tool_id = $_GET['tool_id'];
    $query = "SELECT * FROM tools WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tool_id);
    $stmt->execute();
    $selected_tool = $stmt->get_result()->fetch_assoc();
}

// Handle form submission (Updating tool)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $tool_name = $_POST['tool_name'];
    $brand = $_POST['brand'];
    $quantity = $_POST['quantity'];
    $item_condition = $_POST['item_condition'];
    $image_path = $_POST['existing_image']; // Keep existing image by default

    // Handle Image Upload
    if (!empty($_FILES["image"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $image_name = basename($_FILES["image"]["name"]);
        $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array(strtolower($image_extension), $allowed_extensions)) {
            die("Error: Invalid file type! Allowed: JPG, JPEG, PNG, GIF.");
        }

        $target_file = $target_dir . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $image_name);

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        } else {
            die("Error: Failed to upload image.");
        }
    }

    // Update tool in database
    $query = "UPDATE tools SET tool_name=?, brand=?, quantity=?, item_condition=?, image=? WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssissi", $tool_name, $brand, $quantity, $item_condition, $image_path, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Tool updated successfully!'); window.location.href='tools.php';</script>";
    } else {
        die("Error updating database: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDIT TOOL</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="tools.css">
    <link rel="icon" type="image/png" href="ejmt.png">
</head>
<body>

    <div class="container d-flex justify-content-center mt-5">
        <div class="form-container w-100"> 
            <!-- Full width for better landscape layout -->
            <div class="card shadow-lg p-4">
                <h2 class="text-center text-danger">EDIT TOOL</h2>

                <!-- Tool Selection -->
                <form method="GET" action="edit_tools.php" class="mb-3">
                    <label for="tool_id" class="form-label">Select Tool:</label>
                    <select name="tool_id" id="tool_id" class="form-select" required onchange="this.form.submit()">
                        <option value="" disabled selected>Select a tool</option>
                        <?php foreach ($tools as $tool) : ?>
                            <option value="<?= $tool['id']; ?>" <?= isset($selected_tool) && $selected_tool['id'] == $tool['id'] ? 'selected' : ''; ?>>
                                <?= $tool['id'] . ' - ' . $tool['tool_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <style>
    .form-container {
        border: 2px solid red; /* Red border */
        border-radius: 10px; /* Optional: rounded corners */
        padding: 1px;
    }
</style>


                <?php if ($selected_tool) : ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $selected_tool['id']; ?>">
                        <input type="hidden" name="existing_image" value="<?= $selected_tool['image']; ?>">

                        <div class="row g-3"> <!-- Added better spacing -->
                            <!-- First Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tool Name:</label>
                                    <input type="text" name="tool_name" class="form-control" 
                                        value="<?= htmlspecialchars($selected_tool['tool_name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Brand:</label>
                                    <input type="text" name="brand" class="form-control" 
                                        value="<?= htmlspecialchars($selected_tool['brand']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Quantity:</label>
                                    <input type="number" name="quantity" class="form-control" 
                                        value="<?= htmlspecialchars($selected_tool['quantity']); ?>" min="1" required>
                                </div>
                            </div>

                            <!-- Second Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Item Condition:</label>
                                    <input type="text" name="item_condition" class="form-control" 
                                        value="<?= htmlspecialchars($selected_tool['item_condition'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Current Image:</label><br>
                                    <?php if (!empty($selected_tool['image']) && file_exists($selected_tool['image'])) : ?>
                                        <img src="<?= htmlspecialchars($selected_tool['image']); ?>" alt="Tool Image" 
                                            class="img-fluid mb-2" width="200">
                                    <?php else : ?>
                                        <p class="text-muted">No Image Available</p>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Upload New Image (Optional):</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <a href="tools.php" class="btn btn-secondary">Back</a>
                            <button type="submit" class="btn btn-secondary">Update Tool</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
