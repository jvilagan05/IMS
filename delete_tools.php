<?php
session_start();
include 'db.php'; // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// If deletion is requested
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tool_id'])) {
    $tool_id = $_POST['tool_id'];

    // Secure deletion using prepared statement
    $deleteQuery = "DELETE FROM tools WHERE id = :id";
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleteStmt->bindParam(':id', $tool_id, PDO::PARAM_INT);

    if ($deleteStmt->execute()) {
        $_SESSION['message'] = "Tool successfully deleted.";
    } else {
        $_SESSION['message'] = "Error deleting tool. Please try again.";
    }

    // ✅ Redirect to tools.php instead of delete_tools.php
    header("Location: tools.php");
    exit();
}

// Fetch tools from database for selection
$query = "SELECT id, tool_name FROM tools";
$statement = $pdo->prepare($query);
$statement->execute();
$tools = $statement->fetchAll(PDO::FETCH_ASSOC);
?>
