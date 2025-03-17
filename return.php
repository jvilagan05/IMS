<?php
session_start();
include 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Auto-fill tool details from GET request
$tool_id = isset($_GET['tool_id']) ? (int)$_GET['tool_id'] : 0;
$borrower_id = "";
$borrower_name = "";
$quantity_borrowed = 1; // Default minimum

// Fetch borrower and tool details
if ($tool_id > 0) {
    $query = "
        SELECT bt.borrower_id, bt.borrower_name, bt.quantity, 
               t.tool_name, t.brand 
        FROM borrowed_tools bt
        JOIN tools t ON bt.tool_id = t.id
        WHERE bt.tool_id = ? AND bt.status = 'Borrowed'
        LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $tool_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if ($data) {
            $borrower_id = "";  // Let user input borrower ID
            $borrower_name = ""; // Let user input borrower name
            $quantity_borrowed = $data['quantity'];
            $tool_name = $data['tool_name'];
            $brand = $data['brand'];
        } else {
            echo "<script>alert('No active borrow record for this tool!'); window.location.href='tools.php';</script>";
            exit();
        }
    } else {
        die("SQL Error: " . $conn->error);
    }
}

// Process return
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['borrower_id']) || empty($_POST['tool_id']) || empty($_POST['quantity'])) {
        echo "<script>alert('Please fill in all required fields!'); window.history.back();</script>";
        exit();
    }
    
    $borrower_id = (int)$_POST['borrower_id'];
    $borrower_name = trim($_POST['borrower_name']);
    $tool_id = (int)$_POST['tool_id'];
    $quantity = (int)$_POST['quantity'];
    $site = trim($_POST['site']); // New variable for site
    $date_returned = date('Y-m-d H:i:s');

    // Start transaction
    $conn->begin_transaction();
    try {
        // Get current tool quantity
        $query = "SELECT quantity FROM tools WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $tool_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tool = $result->fetch_assoc();
        if (!$tool) throw new Exception("Tool not found!");

        // Get borrow record
        $borrow_query = "SELECT quantity FROM borrowed_tools WHERE borrower_id = ? AND tool_id = ?";
        $borrow_stmt = $conn->prepare($borrow_query);
        $borrow_stmt->bind_param("ii", $borrower_id, $tool_id);
        $borrow_stmt->execute();
        $borrow_result = $borrow_stmt->get_result();
        $borrow = $borrow_result->fetch_assoc();
        if (!$borrow) throw new Exception("Borrow record not found!");

        // Ensure return quantity is valid
        if ($quantity > $borrow['quantity']) {
            throw new Exception("Cannot return more than borrowed!");
        }

        // Update tool quantity
        $new_quantity = $tool['quantity'] + $quantity;
        $update_query = "UPDATE tools SET quantity = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $new_quantity, $tool_id);
        $update_stmt->execute();

        // Update borrowed tools table
        $return_query = "UPDATE borrowed_tools SET date_returned = ?, status = 'Returned', site = ? WHERE borrower_id = ? AND tool_id = ?";
        $return_stmt = $conn->prepare($return_query);
        if (!$return_stmt) {
            die("Error preparing query: " . $conn->error); // Debugging output
        }
        $return_stmt->bind_param("ssii", $date_returned, $site, $borrower_id, $tool_id);
        $return_stmt->execute();
                
        // Commit transaction
        $conn->commit();

        // Success message and redirect
        echo "<script>alert('Return successful!'); window.location.href='tools.php';</script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error processing return: " . $e->getMessage() . "'); window.history.back();</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RETURN TOOLS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="tools.css">
    <link rel="icon" type="image/png" href="ejmt.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>


<h2>RETURN TOOL</h2>

<div class ="form-return">
<form method="POST" action="" class ="form-cardr">
    <label for="borrower_id">Borrower ID:</label>
    <input type="number" id="borrower_id" name="borrower_id" required><br><br>

    <label for="tool_id">Tool ID:</label>
    <input type="number" id="tool_id" name="tool_id" value="<?= htmlspecialchars($tool_id); ?>" readonly required><br><br>

    <label for="tool_name">Tool Name:</label>
    <input type="text" id="tool_name" name="tool_name" value="<?= htmlspecialchars($tool_name ?? ""); ?>" readonly required><br><br>

    <label for="brand">Brand:</label>
    <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($brand ?? ""); ?>" readonly required><br><br>

    <label for="date_returned">Date Returned:</label>
    <input type="date" id="date_returned" name="date_returned" value="<?= date('Y-m-d'); ?>" required><br><br>

    <label for="quantity">Quantity:</label>
    <input type="number" id="quantity" name="quantity" min="1" required><br><br>

    <button type="submit">Return</button>
    <button type="button" onclick="window.location.href='tools.php'">Cancel</button>
</form>

</div>

<script>
    document.getElementById("date_returned").setAttribute("max", new Date().toISOString().split("T")[0]);
</script>

</body>
</html>
