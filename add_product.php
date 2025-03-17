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

$error_message = ""; // To store validation error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Check if item_no already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE item_no = ?");
        $checkStmt->execute([$_POST['item_no']]);
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            $error_message = "Error: Item number already exists!";
        } else {
            // Prepare insert query
            $stmt = $pdo->prepare("INSERT INTO products (item_no, item_name, description, area, shelf_bin, quantity, unit, remarks, pullout_to, client_name, date_pullout, pullout_by, price, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

            $stmt->execute([
                $_POST['item_no'],
                $_POST['item_name'],
                $_POST['description'],
                $_POST['area'],
                $_POST['shelf_bin'],
                $_POST['quantity'],
                $_POST['unit'],
                $_POST['remarks'],
                $_POST['pullout_to'],
                $_POST['client_name'],
                $_POST['date_pullout'],
                $_POST['pullout_by'],
                $_POST['price']
            ]);

            // Get last inserted ID
            $product_id = $pdo->lastInsertId();

            // Prepare new value for audit log
            $new_value = json_encode([
                'id' => $product_id,
                'item_no' => $_POST['item_no'],
                'item_name' => $_POST['item_name'],
                'description' => $_POST['description'],
                'area' => $_POST['area'],
                'shelf_bin' => $_POST['shelf_bin'],
                'quantity' => $_POST['quantity'],
                'unit' => $_POST['unit'],
                'remarks' => $_POST['remarks'],
                'pullout_to' => $_POST['pullout_to'],
                'client_name' => $_POST['client_name'],
                'date_pullout' => $_POST['date_pullout'],
                'pullout_by' => $_POST['pullout_by'],
                'price' => $_POST['price'],
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ]);

            // Insert audit log
            $logStmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_value, new_value, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $logStmt->execute([
                $_SESSION['user_id'],
                "INSERT",
                "products",
                $product_id,
                $new_value, // Old value is the same as new value for new records
                $new_value,
                $_SERVER['REMOTE_ADDR']
            ]);

            header("Location: view_products.php");
            exit();
        }
    } catch (PDOException $e) {
        $error_message = "Error adding product: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>ADD PRODUCT</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="ejmt.png">
</head>

<body>

    <div class="add-product-container mt-5">
        <div class="add-product-card">
            <div class="add-product-header text-center">
                <h2>Add New Product</h2>
            </div>

             <!-- Show error message if item_no already exists -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger text-center">
                    <?php echo $error_message; ?>
                 </div>
            <?php endif; ?>


            <form method="POST" class="add-product-form">
                <!-- Product Information -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Item Number*</label>
                            <input type="text" name="item_no" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Item Name*</label>
                            <input type="text" name="item_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Storage Area</label>
                            <input type="text" name="area" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Shelf/Bin</label>
                            <input type="text" name="shelf_bin" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Quantity*</label>
                            <input type="number" name="quantity" class="form-control" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Unit</label>
                            <select name="unit" class="form-control">
                                <option value="">Select Unit</option>
                                <option>Pieces</option>
                                <option>Boxes</option>
                                <option>Kg</option>
                                <option>Liters</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price*</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                    </div>
                </div>

                <!-- Pullout Information -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h4 class="text-center">Pullout Details</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Pullout To</label>
                                    <input type="text" name="pullout_to" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Client Name</label>
                                    <input type="text" name="client_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Date of Pullout</label>
                                    <input type="date" name="date_pullout" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Pullout By</label>
                                    <input type="text" name="pullout_by" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control"></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Add Product</button>
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
