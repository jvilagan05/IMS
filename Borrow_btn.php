<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get tool details from URL
$tool_id = isset($_GET['tool_id']) ? (int)$_GET['tool_id'] : '';
$tool_name = isset($_GET['tool_name']) ? urldecode($_GET['tool_name']) : '';
$brand = isset($_GET['brand']) ? urldecode($_GET['brand']) : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $borrower_name = mysqli_real_escape_string($conn, $_POST['borrower_name']);
    $site_location = mysqli_real_escape_string($conn, $_POST['site_location']);
    $tool_id = (int) $_POST['tool_id'];
    $quantity = (int) $_POST['quantity'];
    $date_borrowed = !empty($_POST['date_borrowed']) ? $_POST['date_borrowed'] : date("Y-m-d");

    // Ensure all required fields are filled
    if (empty($borrower_name) || empty($site_location) || empty($quantity)) {
        echo "<script>alert('Please fill in all required fields!'); window.history.back();</script>";
        exit;
    }

    // Fetch current tool details
    $query = "SELECT quantity FROM tools WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) { die("Query Error: " . $conn->error); }
    $stmt->bind_param("i", $tool_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tool = $result->fetch_assoc();

    if ($tool) {
        if ($quantity > $tool['quantity']) {
            echo "<script>alert('Not enough tools available!'); window.location.href='tools.php';</script>";
            exit;
        }

        // Update quantity in tools table
        $new_quantity = $tool['quantity'] - $quantity;
        $update_query = "UPDATE tools SET quantity = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        if (!$update_stmt) { die("Update Query Error: " . $conn->error); }
        $update_stmt->bind_param("ii", $new_quantity, $tool_id);
        $update_stmt->execute();

        // Insert borrowing transaction into borrowed_tools table
        $insert_query = "INSERT INTO borrowed_tools (borrower_id, borrower_name,tool_id, brand, quantity, site, date_borrowed, status) 
        VALUES (?, ?, ?, ?, ?, NOW(), 'Borrowed')";
        $insert_stmt = $conn->prepare($insert_query);
        if (!$insert_stmt) { die("Insert Query Error: " . $conn->error); }
        $insert_stmt->bind_param("isiss", $tool_id, $borrower_name, $quantity, $site_location, $date_borrowed);
        $insert_stmt->execute();

        echo "<script>alert('Borrowing successful!'); window.location.href='tools.php';</script>";
    } else {
        echo "<script>alert('Tool not found!'); window.location.href='tools.php';</script>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BORROW TOOLS</title>
    <link rel="stylesheet" href="tools.css">
    <link rel="icon" type="image/png" href="ejmt.png">
</head>
<body>
<h2 class="borrow-btn-header">Borrow Tool</h2>
<form method="POST" action="" class="borrow-tools-form">
    <label for="borrower_name" class="form-label">Borrower Name:</label>
    <input type="text" id="borrower_name" name="borrower_name" class="form-input" required><br><br>
    
    <label for="tool_id" class="form-label">Tool ID:</label>
    <input type="number" id="tool_id" name="tool_id" value="<?php echo $tool_id; ?>" class="form-input" readonly><br><br>

    <label for="tool_name" class="form-label">Tool Name:</label>
    <input type="text" id="tool_name" name="tool_name" value="<?php echo htmlspecialchars($tool_name); ?>" class="form-input" readonly><br><br>

    <label for="brand" class="form-label">Brand:</label>
    <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($brand); ?>" class="form-input" readonly><br><br>

    <label for="quantity" class="form-label">Quantity:</label>
    <input type="number" id="quantity" name="quantity" class="form-input" min="1" required><br><br>

    <label for="site_location" class="form-label">Site:</label>
    <input type="text" id="site_location" name="site_location" class="form-input" required><br><br>

    <label for="date_borrowed" class="form-label">Date Borrowed:</label>
    <input type="date" id="date_borrowed" name="date_borrowed" class="form-input" required><br><br>

    <button type="submit" class="form-button">Borrow</button>
    <button type="button" class="form-button" onclick="window.location.href='tools.php'">Cancel</button>
</form>


<script>
    // Get today's date in YYYY-MM-DD format
    let today = new Date().toISOString().split("T")[0];
    
    // Set max attribute to today (future dates disabled)
    document.getElementById("date_borrowed").setAttribute("max", today);
</script>
</body>
</html>