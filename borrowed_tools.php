<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Set limit per page
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Fetch total number of borrowed tools
$totalBorrowedQuery = $conn->query("SELECT COUNT(*) as total FROM borrowed_tools WHERE status = 'Borrowed'");
$totalBorrowed = $totalBorrowedQuery->fetch_assoc()['total'];
$totalPages = ceil($totalBorrowed / $limit);

// Fetch borrowed tools for the current page
$borrowedQuery = "SELECT b.borrower_id, b.tool_id, t.tool_name, t.brand, 
                         b.borrower_name, b.quantity, b.site, 
                         b.date_borrowed, b.status 
                  FROM borrowed_tools b
                  JOIN tools t ON t.id = b.tool_id
                  WHERE b.status = 'Borrowed'
                  LIMIT $limit OFFSET $offset";

$borrowedResult = mysqli_query($conn, $borrowedQuery);
$borrowedTools = mysqli_fetch_all($borrowedResult, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BORROWED TOOLS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="tools.css">
    <link rel="icon" type="image/png" href="ejmt.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <header class="text-center bg-danger text-white py-3 rounded">
            <h2>BORROWED TOOLS</h2>
        </header>

        <div class="main-content p-4 bg-light rounded shadow-lg">
            <!-- Search and Filters -->
            <div class="d-flex gap-2 mb-3">
                <input type="text" id="searchBar" class="form-control" placeholder="Search...">

                <select id="filterBorrower" class="form-select">
                    <option value="">Filter by Borrower ID</option>
                    <?php foreach ($borrowedTools as $tool) : ?>
                        <option value="<?= htmlspecialchars($tool['borrower_id']); ?>"><?= htmlspecialchars($tool['borrower_id']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filterTool" class="form-select">
                    <option value="">Filter by Tool</option>
                    <?php foreach ($borrowedTools as $tool) : ?>
                        <option value="<?= htmlspecialchars($tool['tool_name']); ?>"><?= htmlspecialchars($tool['tool_name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filterStatus" class="form-select">
                    <option value="">Filter by Status</option>
                    <option value="Borrowed">Borrowed</option>
                    <option value="Returned">Returned</option>
                </select>
            </div>

            <!-- Borrowed Tools Table -->
            <table class="table table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Borrower ID</th>
                        <th>Borrower Name</th>
                        <th>Tool ID</th>
                        <th>Tool Name</th>
                        <th>Brand</th>
                        <th>Quantity</th>
                        <th>Site</th>
                        <th>Date Borrowed</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="borrowedToolsTable">
                    <?php foreach ($borrowedTools as $row) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['borrower_id']); ?></td>
                            <td><?= htmlspecialchars($row['borrower_name']); ?></td>
                            <td><?= htmlspecialchars($row['tool_id']); ?></td>
                            <td><?= htmlspecialchars($row['tool_name']); ?></td>
                            <td><?= htmlspecialchars($row['brand']); ?></td>
                            <td><?= htmlspecialchars($row['quantity']); ?></td>
                            <td><?= htmlspecialchars($row['site']); ?></td>
                            <td><?= date("Y-m-d H:i:s", strtotime($row['date_borrowed'])); ?></td>
                            <td><?= htmlspecialchars($row['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-seventh btn-lg">Back to Dashboard</a>
                <a href="tools.php" class="btn btn-eight btn-lg">Back to Tools Inventory</a>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        function filterBorrowedTools() {
            let search = $("#searchBar").val();
            let borrowerID = $("#filterBorrower").val();
            let toolName = $("#filterTool").val();
            let status = $("#filterStatus").val();

            $.ajax({
                url: "filter_borrowed_tools.php",
                type: "GET",
                data: {
                    search: search,
                    borrower_id: borrowerID,
                    tool_name: toolName,
                    status: status
                },
                success: function(response) {
                    $("#borrowedToolsTable").html(response);
                }
            });
        }

        $("#searchBar, #filterBorrower, #filterTool, #filterStatus").on("input change", filterBorrowedTools);
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
