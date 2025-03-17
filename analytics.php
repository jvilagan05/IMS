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

try {
    // Time filter setup
    $filter = $_GET['filter'] ?? 'all';
    $conditions = [
        'today' => "DATE(created_at) = CURDATE()",
        'week' => "YEARWEEK(created_at) = YEARWEEK(CURDATE())",
        'month' => "MONTH(created_at) = MONTH(CURDATE())",
        'year' => "YEAR(created_at) = YEAR(CURDATE())",
        'all' => "1=1"
    ];
    $time_condition = $conditions[$filter] ?? '1=1';

    // Key Metrics
    $metrics = [
        'total_products' => $pdo->query("SELECT COUNT(*) FROM products WHERE $time_condition")->fetchColumn(),
        'total_value' => $pdo->query("SELECT SUM(quantity * price) FROM products WHERE $time_condition")->fetchColumn(),
        'low_stock' => $pdo->query("SELECT COUNT(*) FROM products WHERE quantity < 10 AND $time_condition")->fetchColumn(),
        'avg_price' => $pdo->query("SELECT AVG(price) FROM products WHERE $time_condition")->fetchColumn()
    ];

    // Chart Data
    $chart_data = $pdo->query("
        SELECT item_name, quantity, area 
        FROM products 
        WHERE $time_condition
        ORDER BY created_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Inventory Aging Filtering and Pagination
    $aging_filter = $_GET['aging_filter'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Base query
    $query_inventory_aging = "
        SELECT 
            item_no, 
            item_name, 
            quantity, 
            DATEDIFF(CURDATE(), created_at) AS days_in_stock,
            CASE 
                WHEN DATEDIFF(CURDATE(), created_at) <= 30 THEN '0-30 days'
                WHEN DATEDIFF(CURDATE(), created_at) <= 60 THEN '31-60 days'
                WHEN DATEDIFF(CURDATE(), created_at) <= 90 THEN '61-90 days'
                ELSE '91+ days'
            END AS aging_category
        FROM products
        WHERE $time_condition
    ";

    if ($aging_filter) {
        $query_inventory_aging .= " HAVING aging_category = '$aging_filter'";
    }

    $query_inventory_aging .= " ORDER BY days_in_stock LIMIT $limit OFFSET $offset";

    $result_inventory_aging = mysqli_query($conn, $query_inventory_aging);

    // Total records for pagination
    $total_query = "
        SELECT COUNT(*) AS total FROM (
            SELECT 
                item_no, 
                item_name, 
                quantity, 
                DATEDIFF(CURDATE(), created_at) AS days_in_stock,
                CASE 
                    WHEN DATEDIFF(CURDATE(), created_at) <= 30 THEN '0-30 days'
                    WHEN DATEDIFF(CURDATE(), created_at) <= 60 THEN '31-60 days'
                    WHEN DATEDIFF(CURDATE(), created_at) <= 90 THEN '61-90 days'
                    ELSE '91+ days'
                END AS aging_category
            FROM products
            WHERE $time_condition
        ) AS aging_table
    ";

    if ($aging_filter) {
        $total_query .= " WHERE aging_category = '$aging_filter'";
    }

    $total_result = mysqli_query($conn, $total_query);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_records = $total_row['total'];
    $total_pages = ceil($total_records / $limit);

} catch (PDOException $e) {
    die("Analytics error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>INVENTORY ANALYTICS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="analytics.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="ejmt.png">
</head>
<body>
    
    <div class="container py-5">
        <h1 class="text-danger mb-4">Inventory Analytics</h1>
        
        <div class="mb-3">
    <a href="dashboard.php" class="btn btn-outline-danger">← Back to Dashboard</a>
</div>


        <!-- Time Filter -->
        <div class="mb-4">
            <div class="btn-group">
                <?php foreach(['today', 'week', 'month', 'year', 'all'] as $period): ?>
                    <a href="?filter=<?= $period ?>" 
                       class="btn btn-<?= $filter === $period ? 'danger' : 'outline-danger' ?>">
                        <?= ucfirst($period) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="row mb-5">
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body">
                        <h5>Total Products</h5>
                        <h2><?= number_format($metrics['total_products']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5>Total Value</h5>
                        <h2>₱<?= number_format($metrics['total_value'], 2) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5>Low Stock Items</h5>
                        <h2><?= number_format($metrics['low_stock']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5>Avg. Price</h5>
                        <h2>₱<?= number_format($metrics['avg_price'], 2) ?></h2>
                    </div>
                </div>
            </div>
        </div>

<div class="col-md-6">
    <div class="card mb-4 chart-card"> <!-- Added class "chart-card" -->
        <div class="card-body">
            <h5 class="card-title">Stock Levels</h5>
            <canvas id="stockChart"></canvas>
        </div>
    </div>
</div>

        </div>
            </div>
        </div>
    </div>

    <div class="container-fluid content">
    <!-- Existing content like header and metrics cards -->

    <!-- Inventory Aging Analysis Section -->
    <div class="card mt-4">
        <h4 class="p-3">Inventory Aging Analysis</h4>
    <!-- Filter Form -->
    <form method="GET" class="p-3">
        <div class="row">
            <div class="col-md-4">
                <select name="aging_filter" class="form-control">
                    <option value="">Filter by Aging Category</option>
                    <option value="0-30 days" <?= ($aging_filter == '0-30 days') ? 'selected' : '' ?>>0-30 days</option>
                    <option value="31-60 days" <?= ($aging_filter == '31-60 days') ? 'selected' : '' ?>>31-60 days</option>
                    <option value="61-90 days" <?= ($aging_filter == '61-90 days') ? 'selected' : '' ?>>61-90 days</option>
                    <option value="91+ days" <?= ($aging_filter == '91+ days') ? 'selected' : '' ?>>91+ days</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="analytics.php" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Item No</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Days in Stock</th>
                        <th>Aging Category</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result_inventory_aging)): ?>
                    <tr>
                        <td><?= $row['item_no'] ?></td>
                        <td><?= $row['item_name'] ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><?= $row['days_in_stock'] ?></td>
                        <td><?= $row['aging_category'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

     <!-- Pagination -->
     <nav class="p-3">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&aging_filter=<?= $aging_filter ?>">Previous</a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&aging_filter=<?= $aging_filter ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&aging_filter=<?= $aging_filter ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

    <!-- Recommendations Section -->
    <div class="mt-4">
        <h5>Recommendations:</h5>
        <ul>
            <li>Consider discounting items in the "91+ days" category to clear out old stock.</li>
            <li>Review stock levels for items in the "61-90 days" category.</li>
            <li>Monitor sales closely for new stock (0-30 days) to ensure they are moving as expected.</li>
        </ul>
    </div>
</div>

    <script>
        // Stock Levels Chart
        new Chart(document.getElementById('stockChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($chart_data, 'item_name')) ?>,
                datasets: [{
                    label: 'Quantity in Stock',
                    data: <?= json_encode(array_column($chart_data, 'quantity')) ?>,
                    backgroundColor: '#dc3545'
                }]
            }
        });

        // Storage Distribution Chart
        new Chart(document.getElementById('storageChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_unique(array_column($chart_data, 'area'))) ?>,
                datasets: [{
                    data: <?= json_encode(array_count_values(array_column($chart_data, 'area'))) ?>,
                    backgroundColor: ['#dc3545', '#ffc107', '#28a745', '#17a2b8']
                }]
            }
        });
    </script>
</body>
</html>