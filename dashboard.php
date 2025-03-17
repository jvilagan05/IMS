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

// Fetch total products
$query_total_products = "SELECT COUNT(*) AS total_products FROM products";
$result_total_products = mysqli_query($conn, $query_total_products);
$row_total_products = mysqli_fetch_assoc($result_total_products);
$total_products = $row_total_products['total_products'];

// Fetch total value of inventory
$query_total_value = "SELECT SUM(price * quantity) AS total_value FROM products";
$result_total_value = mysqli_query($conn, $query_total_value);
$row_total_value = mysqli_fetch_assoc($result_total_value);
$total_value = $row_total_value['total_value'] ?? 0;

// Fetch low stock count
$query_low_stock = "SELECT COUNT(*) AS low_stock FROM products WHERE quantity < 5";
$result_low_stock = mysqli_query($conn, $query_low_stock);
$row_low_stock = mysqli_fetch_assoc($result_low_stock);
$low_stock_items = $row_low_stock['low_stock'];

// Filter values
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_quantity = isset($_GET['filter_quantity']) ? $_GET['filter_quantity'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'item_no';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Ensure only valid sorting options
$allowed_sort = ['item_no', 'item_name', 'quantity', 'price'];
$allowed_order = ['ASC', 'DESC'];

if (!in_array($sort_by, $allowed_sort)) $sort_by = 'item_no';
if (!in_array($order, $allowed_order)) $order = 'ASC';

// Pagination setup
$limit = 10; // Items per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Build query conditions
$whereConditions = "WHERE quantity < 5";
if (!empty($search)) {
    $whereConditions .= " AND item_name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
}
if (!empty($filter_quantity)) {
    $whereConditions .= " AND quantity = " . intval($filter_quantity);
}

// Get total filtered items for pagination
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM products $whereConditions");
$totalRow = $totalQuery->fetch_assoc();
$totalPages = ceil($totalRow['total'] / $limit);

// Fetch low-stock product details with filters and pagination
$query_low_stock_items = "SELECT item_no, item_name, quantity, price 
                         FROM products 
                         $whereConditions 
                         ORDER BY $sort_by $order 
                         LIMIT $start, $limit";

$result_low_stock_items = mysqli_query($conn, $query_low_stock_items);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>DASHBOARD</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- External CSS File -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">  
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="ejmt.png">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰ Menu</button>
    <h2 class="mt-3">EJMT TRADING</h2>
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="nav-item"><a href="add_product.php" class="nav-link"><i class="fas fa-plus"></i> Add Product</a></li>
            <li class="nav-item"><a href="view_products.php" class="nav-link"><i class="fas fa-box"></i> View Inventory</a></li>
            <li class="nav-item"><a href="analytics.php" class="nav-link"><i class="fas fa-chart-line"></i> Analytics</a></li>
            <li class="nav-item"><a href="view_audit_logs.php" class="nav-link"><i class="fas fa-file-alt"></i> Audit Logs</a></li>
            <li class="nav-item"><a href="Tools.php" class="nav-link"><i class="fas fa-tools"></i> Tools</a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</div>

<!-- Small Button for Showing Sidebar When Hidden -->
<button class="small-toggle-btn" onclick="toggleSidebar()">☰</button>


    <!-- Main Content -->
    <div class="container-fluid content">
        <div class="header text-center">
            <img src="LOGO.PNG" alt="EJMT Trading Logo">
            <h1 class="text-danger"><strong>EJMT TRADING</strong></h1>
            <p><i>Provider of Security & Systems Solution</i></p>
            <p>Address: Lot 43 Block 56 Phase 1 Mary Cris Complex Pasong Camachile II General Trias Cavite / 626A Blumentritt Road Sampaloc Manila</p>
            <p>Telephone Number: (02) 253-4903 / 998-6904</p>
            <p>Mobile Number: 0928-636-8392 / 0915-644-1778</p>
            <p>Email: <a href="mailto:info@ejmtech.com">info@ejmtech.com</a> | <a href="mailto:jay@ejmtech.com">jay@ejmtech.com</a></p>
            <p class="text-danger"><strong>VAT-REG TIN: 248-423-658-000</strong></p>
        </div>

        <h1 class="text-danger text-center">EJMT Trading Inventory Dashboard</h1>

        <?php if ($low_stock_items > 0): ?>
        <div class="alert alert-warning text-center">
            <strong>Warning!</strong> <?= $low_stock_items ?> products have low stock.
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card bg-danger text-white text-center p-3">
                    <h5>Total Products</h5>
                    <h2><?= $total_products ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white text-center p-3">
                    <h5>Total Inventory Value</h5>
                    <h2>₱<?= number_format($total_value, 2) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white text-center p-3">
                    <h5>Low Stock Items</h5>
                    <h2><?= $low_stock_items ?></h2>
                </div>
            </div>
        </div>


        <div class="card mt-4">
            <h4 class="p-3">Low Stock Products</h4>


    <!-- Filter Form -->
    <form method="GET" class="mb-3" id="filterForm">
    <div class="row">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control filter-input" placeholder="Search by name" value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div class="col-md-3">
            <select name="sort_by" class="form-control filter-input">
                <option value="item_name" <?= ($sort_by == 'item_name') ? 'selected' : '' ?>>Sort by Item Name</option>
                <option value="price" <?= ($sort_by == 'price') ? 'selected' : '' ?>>Sort by Price</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="order" class="form-control filter-input">
                <option value="ASC" <?= ($order == 'ASC') ? 'selected' : '' ?>>Ascending</option>
                <option value="DESC" <?= ($order == 'DESC') ? 'selected' : '' ?>>Descending</option>
            </select>
        </div>
        <div class="col-md-12 mt-2 text-center">
            <a href="dashboard.php" class="btn btn-secondary">Reset</a>
        </div>
    </div>
</form>

<script>
// Auto-submit form when filter values change
document.querySelectorAll('.filter-input').forEach(input => {
    input.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});
</script>


    <!-- Pagination Buttons (Above the Table) -->
    <div class="pagination-container">
        <nav>
        <ul class="pagination justify-content-center">
    <?php if ($page > 1): ?>
        <li class="page-item">
            <a class="page-link" href="dashboard.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_quantity=<?= urlencode($filter_quantity) ?>&sort_by=<?= urlencode($sort_by) ?>&order=<?= urlencode($order) ?>">Previous</a>
        </li>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
            <a class="page-link" href="dashboard.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter_quantity=<?= urlencode($filter_quantity) ?>&sort_by=<?= urlencode($sort_by) ?>&order=<?= urlencode($order) ?>"><?= $i ?></a>
        </li>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <li class="page-item">
            <a class="page-link" href="dashboard.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_quantity=<?= urlencode($filter_quantity) ?>&sort_by=<?= urlencode($sort_by) ?>&order=<?= urlencode($order) ?>">Next</a>
        </li>
    <?php endif; ?>
</ul>
        </nav>
    </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>Item No</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_low_stock_items)): ?>
                        <tr>
                            <td><?= $row['item_no'] ?></td>
                            <td><?= $row['item_name'] ?></td>
                            <td class="text-danger"><?= $row['quantity'] ?></td>
                            <td>₱<?= number_format($row['price'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="ejmt-footer">
        <p>© 2025 EJMT Trading. All Rights Reserved.</p>
    </footer>

    <script>
function toggleSidebar() {
    let sidebar = document.querySelector(".sidebar");
    let content = document.querySelector(".content");
    let smallBtn = document.querySelector(".small-toggle-btn");

    if (sidebar.classList.contains("hidden")) {
        sidebar.classList.remove("hidden");
        content.style.marginLeft = "250px";
        content.style.width = "calc(100% - 250px)";
        smallBtn.classList.remove("show");
    } else {
        sidebar.classList.add("hidden");
        content.style.marginLeft = "0";
        content.style.width = "100%";
        smallBtn.classList.add("show");
    }
}
    </script>

</body>
</html>

