<?php
session_start();
include 'db.php'; // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Prevent going back to dashboard after logout
header("Cache-Control: no-cache, must-revalidate, no-store, private");
header("Pragma: no-cache");
header("Expires: 0");

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

try {
    if ($search !== "") {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE item_name LIKE ? OR description LIKE ? OR item_no LIKE ? OR area LIKE ? OR quantity LIKE ? OR unit LIKE ? OR price LIKE ? OR pullout_to LIKE ? OR client_name LIKE ? OR remarks LIKE ?");
        $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM products");
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        echo "<tr>
                <td>" . htmlspecialchars($product['item_no']) . "</td>
                <td>" . htmlspecialchars($product['item_name']) . "</td>
                <td>" . htmlspecialchars($product['description']) . "</td>
                <td>" . htmlspecialchars($product['area']) . "<br><small>" . htmlspecialchars($product['shelf_bin']) . "</small></td>
                <td>" . htmlspecialchars($product['quantity']) . (!empty($product['quantity2']) ? "/ " . htmlspecialchars($product['quantity2']) : '') . "</td>
                <td>" . htmlspecialchars($product['unit']) . "</td>
                <td>" . number_format($product['price'], 2) . "</td>
                <td>";
        if (!empty($product['pullout_to'])) {
            echo "<strong>To:</strong> " . htmlspecialchars($product['pullout_to']) . "<br>
                  <strong>Client:</strong> " . htmlspecialchars($product['client_name']) . "<br>
                  <strong>Date:</strong> " . htmlspecialchars($product['date_pullout']);
        }
        echo "</td>
                <td>" . htmlspecialchars($product['remarks']) . "</td>
                <td>
                    <a href='edit_product.php?id=" . $product['id'] . "' class='btn btn-warning btn-sm'>Edit</a>
                    <a href='delete_product.php?id=" . $product['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\");'>Delete</a>
                </td>
              </tr>";
    }
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}
?>