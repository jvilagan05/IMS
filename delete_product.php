<?php
session_start();
include 'db.php'; // Include your database connection file

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    try {
        $product_id = $_GET['id'];

        // Fetch old values before deletion
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $old_product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($old_product) {
            // Convert old values to JSON format for logging
            $old_value = json_encode($old_product);

            // Log deletion in audit_log
            $logStmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_value, new_value, ip_address) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
            $logStmt->execute([
                $_SESSION['user_id'],  // Current user performing the action
                "DELETE",
                "products",
                $product_id,
                $old_value,
                "DELETED",
                $_SERVER['REMOTE_ADDR']
            ]);

            // Prepare and execute the delete statement
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);

            // Redirect with a success message
            header("Location: view_products.php?message=Product deleted successfully.");
            exit();
        } else {
            header("Location: view_products.php?error=Product not found.");
            exit();
        }
    } catch (PDOException $e) {
        die("Error deleting product: " . $e->getMessage());
    }
} else {
    header("Location: view_products.php");
    exit();
}
?>
