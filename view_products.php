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
    // Items per page
    $items_per_page = 10;

    // Get current page, default to 1 if not set
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $items_per_page;

    // Initialize search query
    $search = isset($_GET['search']) ? trim($_GET['search']) : "";
    $sort_column = isset($_GET['sort']) ? $_GET['sort'] : "item_name";
    $sort_order = isset($_GET['order']) && $_GET['order'] === "desc" ? "DESC" : "ASC";

   // Allowed columns for sorting
   $allowed_columns = ["item_no", "item_name", "description", "area", "quantity", "unit", "price"];
   if (!in_array($sort_column, $allowed_columns)) {
       $sort_column = "item_no"; // Default sorting column
   }

   if ($search !== "") {
    // Search query with sorting and pagination
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE item_name LIKE ? OR description LIKE ? OR item_no LIKE ? OR area LIKE ? OR quantity LIKE ? OR unit LIKE ? OR price LIKE ? 
        ORDER BY $sort_column $sort_order 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);} 
    
    
    else {
    // Fetch products with sorting and pagination
    $stmt = $pdo->prepare("SELECT * FROM products ORDER BY $sort_column $sort_order LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the total number of products for pagination
    if ($search !== "") {
        // Search query for total count
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE item_name LIKE ? OR description LIKE ? OR item_no LIKE ? OR area LIKE ? OR quantity LIKE ? OR unit LIKE ? OR price LIKE ? OR pullout_to LIKE ? OR client_name LIKE ? OR remarks LIKE ?");
        $stmt_count->execute(["%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
    } 
    else {
        // Count all products
        $stmt_count = $pdo->query("SELECT COUNT(*) FROM products");
    }

    $total_products = $stmt_count->fetchColumn();
    $total_pages = ceil($total_products / $items_per_page);


} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}

$queryString = $_GET;
unset($queryString['page']); // Remove existing page parameter
$queryString = http_build_query($queryString);
$pageUrl = !empty($queryString) ? "?$queryString&page=" : "?page=";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIEW PRODUCTS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="view_products.css">
    <link rel="icon" type="image/png" href="ejmt.png">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Product Inventory</h2>

 <!-- Search and Sorting -->
 <div class="row mb-3">
            <div class="col-md-6">
                <input type="text" id="search" placeholder="Search products..." class="form-control " value="<?= htmlspecialchars($search) ?>">
          
            </div>
            <div class="col-md-3">
                <select id="sortColumn" class="form-control">
                    <option value="item_name">Item Name</option>
                    <option value="description">Description</option>
                    <option value="area">Storage</option>
                    <option value="quantity">Quantity</option>
                    <option value="unit">Unit</option>
                    <option value="price">Price</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="sortOrder" class="form-control">
                    <option value="asc">Ascending</option>
                    <option value="desc">Descending</option>
                </select>
            </div>
        </div>

        <!-- Buttons -->
        <div class="btn-group mb-3">
            <button onclick="window.location.href='add_product.php'" class="btn btn-add-product">Add New Product</button>
            <button onclick="window.location.href='dashboard.php'" class="btn btn-back-dashboard">Back to Dashboard</button>
            <button id="export-btn" class="btn btn-custom" onclick="openExportModal()">Export to CSV</button>
            <a href="view_products.php" class="btn btn-secondary">Reset</a>
        </div>

        <!-- Products Table -->
        <table class="table table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Item No</th>
                    <th>Item Name</th>
                    <th>Description</th>
                    <th>Storage</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Pullout Details</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="productTable">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['item_no']) ?></td>
                        <td><?= htmlspecialchars($product['item_name']) ?></td>
                        <td><?= htmlspecialchars($product['description']) ?></td>
                        <td><?= htmlspecialchars($product['area']) ?><br><small><?= htmlspecialchars($product['shelf_bin']) ?></small></td>
                        <td><?= htmlspecialchars($product['quantity']) ?></td>
                        <td><?= htmlspecialchars($product['unit']) ?></td>
                        <td><?= number_format($product['price'], 2) ?></td>
                        <td>
                            <?php if (!empty($product['pullout_to'])): ?>
                                <strong>To:</strong> <?= htmlspecialchars($product['pullout_to']) ?><br>
                                <strong>Client:</strong> <?= htmlspecialchars($product['client_name']) ?><br>
                                <strong>Date:</strong> <?= htmlspecialchars($product['date_pullout']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($product['remarks']) ?></td>
                        <td>
                            <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_product.php?id=<?= $product['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center text-danger font-weight-bold">No results found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
             <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                 <a class="page-link" href="<?= $pageUrl . ($page - 1) ?>">Previous</a>
                 </li>
         <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= $pageUrl . $i ?>"><?= $i ?></a>
                    </li>
                  <?php endfor; ?>
                     <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $pageUrl . ($page + 1) ?>">Next</a>
                 </li>
                </ul>
                </nav>
    </div>

    <!-- Modal for File Name Input -->
    <div class="modal" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Enter File Name</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <label for="fileNameInput">File Name:</label>
                    <input type="text" id="fileNameInput" class="form-control" placeholder="Enter file name" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="exportCSV()">Export</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Real-time search
            $('#search').on('input', function() {
                var searchValue = $(this).val().toLowerCase();

                if (searchValue === "reset") {
                    // Reset to default data if "reset" is typed in the search bar
                    window.location.href = "view_products.php"; // Reload the page without search
                } else {
                    // Filter table rows based on search input
                    var rows = $('#productTable tr');
                    var found = false;
                    rows.each(function() {
                        var row = $(this);
                        var rowData = row.text().toLowerCase();
                        if (rowData.indexOf(searchValue) !== -1) {
                            row.show();
                            found = true;
                        } else {
                            row.hide();
                        }
                    });

                    // Show "No results found" if no rows match the search
                    if (!found) {
                        $('#productTable').html('<tr><td colspan="10" class="text-center text-danger font-weight-bold">No results found.</td></tr>');
                    }
                }
            });
        });

        // Open modal for file name input
        function openExportModal() {
            $('#exportModal').modal('show');
        }

        // Export function
        function exportCSV() {
            let fileName = $('#fileNameInput').val().trim();

            if (!fileName) {
                alert("Please enter a valid file name.");
                return;
            }

            // Collect table data
            let tableData = [];
            $('#productTable tr').each(function() {
                let rowData = [];
                $(this).find('td').each(function() {
                    rowData.push($(this).text().trim());
                });
                tableData.push(rowData);
            });

            if (tableData.length === 0) {
                alert("No data available to export!");
                return;
            }

            // Create CSV string
            let csvContent = "Item No, Item Name, Description, Storage, Quantity, Unit, Price, Pullout Details, Remarks\n";

            tableData.forEach(function(row) {
                csvContent += row.join(",") + "\n";
            });

            // Trigger file download with the entered file name
            let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            let a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = fileName + ".csv"; // Use entered file name
            a.click();

            // Close the modal
            $('#exportModal').modal('hide');
        }
        $(document).ready(function() {
            $('#sortColumn').val("<?= $sort_column ?>");
            $('#sortOrder').val("<?= strtolower($sort_order) ?>");

            $('#sortColumn, #sortOrder').change(function() {
                let search = $('#search').val();
                let sort = $('#sortColumn').val();
                let order = $('#sortOrder').val();
                window.location.href = view_products.php?search=${search}&sort=${sort}&order=${order};
            });
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>