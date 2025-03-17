<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['logged_in'])) {
    die("Access denied. Please log in.");
}

// Default CSV filename (Customizable)
$filename = "inventory_export_" . date("Ymd_His") . ".csv";

// Handle filtering (Date Range, Category, Low Stock)
$whereClauses = [];
$params = [];

if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $whereClauses[] = "created_at BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $_GET['start_date'] . " 00:00:00";
    $params[':end_date'] = $_GET['end_date'] . " 23:59:59";
}

if (!empty($_GET['category'])) {
    $whereClauses[] = "category = :category";
    $params[':category'] = $_GET['category'];
}

if (!empty($_GET['low_stock']) && $_GET['low_stock'] == "yes") {
    $whereClauses[] = "quantity < 10";
}

// Build SQL Query with Filters
$query = "SELECT item_no, item_name, quantity, price, area, shelf_bin, created_at FROM products";
if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}
$query .= " ORDER BY created_at DESC";

// Prepare and execute statement
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output CSV Headers
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen("php://output", "w");
fputcsv($output, ['Item No', 'Item Name', 'Quantity', 'Price', 'Storage Area', 'Shelf Bin', 'Added On']); // Header Row

// Write Data to CSV (Sanitized)
foreach ($products as $row) {
    fputcsv($output, [
        $row['item_no'],
        htmlspecialchars($row['item_name']),  // Prevent XSS
        (int)$row['quantity'],  // Ensure number format
        number_format($row['price'], 2), // Format as currency
        htmlspecialchars($row['area']),
        htmlspecialchars($row['shelf_bin']),
        date('Y-m-d H:i:s', strtotime($row['created_at']))
    ]);
}

fclose($output);
exit;
?>
