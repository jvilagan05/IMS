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


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $product_id = $_POST['id'];

        // Fetch old values before update
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $old_product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($old_product) {
            // Convert old values to JSON format for logging
            $old_value = json_encode($old_product);

            // Prepare update query
            $stmt = $pdo->prepare("UPDATE products SET
                item_no = ?, item_name = ?, description = ?, area = ?,
                shelf_bin = ?, quantity = ?, unit = ?, remarks = ?,
                quantity2 = ?, pullout_to = ?, client_name = ?,
                date_pullout = ?, pullout_by = ?, remaining_qty = ?, price = ?
                WHERE id = ?");

            $stmt->execute([
                $_POST['item_no'],
                $_POST['item_name'],
                $_POST['description'],
                $_POST['area'],
                $_POST['shelf_bin'],
                $_POST['quantity'],
                $_POST['unit'],
                $_POST['remarks'],
                $_POST['quantity2'],
                $_POST['pullout_to'],
                $_POST['client_name'],
                $_POST['date_pullout'],
                $_POST['pullout_by'],
                $_POST['remaining_qty'],
                $_POST['price'],
                $product_id
            ]);

            // Fetch new values after update
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $new_product = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_value = json_encode($new_product);

            // Insert audit log
            $logStmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_value, new_value, ip_address) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
            $logStmt->execute([1, "UPDATE", "products", $product_id, $old_value, $new_value, $_SERVER['REMOTE_ADDR']]);

            header("Location: view_products.php");
            exit();
        } else {
            die("Error: Product not found.");
        }
    } catch (PDOException $e) {
        die("Error updating product: " . $e->getMessage());
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching product: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDIT PRODUCT</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        @media (max-width: 768px) {
            .form-group {
                margin-bottom: 1rem;
            }
        }
    </style>

<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="edit_product.css"> <!-- Add this line to link your custom CSS -->
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Product</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $product['id'] ?>">
            
            <div class="row">
                <div class="col-md-4 col-sm-12">
                    <div class="form-group">
                        <label>Item Number*</label>
                        <input type="text" name="item_no" class="form-control" 
                               value="<?= htmlspecialchars($product['item_no']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Item Name*</label>
                        <input type="text" name="item_name" class="form-control"
                               value="<?= htmlspecialchars($product['item_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control"><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>
                </div>

                <div class="col-md-4 col-sm-12">
                    <div class="form-group">
                        <label>Storage Area</label>
                        <input type="text" name="area" class="form-control"
                               value="<?= htmlspecialchars($product['area']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Shelf/Bin</label>
                        <input type="text" name="shelf_bin" class="form-control"
                               value="<?= htmlspecialchars($product['shelf_bin']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Quantity*</label>
                        <input type="number" name="quantity" class="form-control"
                               value="<?= htmlspecialchars($product['quantity']) ?>" required>
                    </div>
                </div>

                <div class="col-md-4 col-sm-12">
                    <div class="form-group">
                        <label>Unit</label>
                        <select name="unit" class="form-control">
                            <option value="">Select Unit</option>
                            <option <?= $product['unit'] == 'Pieces' ? 'selected' : '' ?>>Pieces</option>
                            <option <?= $product['unit'] == 'Boxes' ? 'selected' : '' ?>>Boxes</option>
                            <option <?= $product['unit'] == 'Kg' ? 'selected' : '' ?>>Kg</option>
                            <option <?= $product['unit'] == 'Liters' ? 'selected' : '' ?>>Liters</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Secondary Quantity</label>
                        <input type="number" name="quantity2" class="form-control"
                               value="<?= htmlspecialchars($product['quantity2']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Price*</label>
                        <input type="number" step="0.01" name="price" class="form-control"
                               value="<?= htmlspecialchars($product['price']) ?>" required>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <h4>Pullout Details</h4>
                    <div class="row">
                        <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label>Pullout To</label>
                                <input type="text" name="pullout_to" class="form-control"
                                       value="<?= htmlspecialchars($product['pullout_to']) ?>">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label>Client Name</label>
                                <input type="text" name="client_name" class="form-control"
                                       value="<?= htmlspecialchars($product['client_name']) ?>">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label>Date of Pullout</label>
                                <input type="date" name="date_pullout" class="form-control"
                                       value="<?= htmlspecialchars($product['date_pullout']) ?>">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label>Pullout By</label>
                                <input type="text" name="pullout_by" class="form-control"
                                       value="<?= htmlspecialchars($product['pullout_by']) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control"><?= htmlspecialchars($product['remarks']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Update Product</button>
            <a href="view_products.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
