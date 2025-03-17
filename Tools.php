<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Prevent going back to dashboard after logout
header("Cache-Control: no-cache, must-revalidate, no-store, private");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch tools data
$query = "SELECT * FROM tools";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

$tools = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tools[] = $row;
}

// Fetch borrowed tools data
$borrowedQuery = "SELECT b.borrower_id, b.tool_id, t.tool_name, t.brand, 
                         b.borrower_name, b.quantity, b.site, 
                         b.date_borrowed, b.status 
                  FROM borrowed_tools b
                  JOIN tools t ON t.id = b.tool_id
                  WHERE b.status = 'Borrowed'";

$borrowedResult = mysqli_query($conn, $borrowedQuery);

if (!$borrowedResult) {
    die("Query Failed: " . mysqli_error($conn));
}

$borrowedTools = [];
while ($row = mysqli_fetch_assoc($borrowedResult)) {
    $borrowedTools[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOOLS INVENTORY</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="tools.css">
    <link rel="icon" type="image/png" href="ejmt.png">
    <!-- Add jQuery for handling events -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <header class="text-center bg-danger text-white py-3 rounded">
            <h2>TOOLS INVENTORY: BORROW AND RETURN</h2>
        </header>

        <div class="main-content p-4 bg-light rounded shadow-lg">

            <!-- Add, Edit & Delete Buttons -->
            <div class="d-flex justify-content-start gap-1 mb-3">
                <a href="add_tools.php" class="btn btn-primary">Add Tool</a>
                <a href="edit_tools.php" class="btn btn-secondary">Edit Tool</a>
                <button class="btn btn-third" data-bs-toggle="modal" data-bs-target="#deleteToolModal">Delete Tool</button>
                <a href="borrowed_tools.php" class="btn btn-fourth btn-lg">View Borrowed Tools</a>
                <div class="ms-auto d-flex gap-2">
    <input type="text" id="searchBar" class="form-control" placeholder="Search...">
    
    <select id="filterID" class="form-select">
        <option value="">Filter by ID</option>
        <?php foreach ($tools as $tool) : ?>
            <option value="<?= htmlspecialchars($tool['id']); ?>"><?= htmlspecialchars($tool['id']); ?></option>
        <?php endforeach; ?>
    </select>

    <select id="filterTool" class="form-select">
        <option value="">Filter by Tool</option>
        <?php foreach ($tools as $tool) : ?>
            <option value="<?= htmlspecialchars($tool['tool_name']); ?>"><?= htmlspecialchars($tool['tool_name']); ?></option>
        <?php endforeach; ?>
    </select>

    <select id="filterBrand" class="form-select">
        <option value="">Filter by Brand</option>
        <?php foreach ($tools as $tool) : ?>
            <option value="<?= htmlspecialchars($tool['brand']); ?>"><?= htmlspecialchars($tool['brand']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
</div>
            </div>

            <!-- Tools Inventory Table -->
            <?php
// Set limit per page
$limit = 10;

// Get the current page from URL, default to 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Ensure page is at least 1

// Calculate the offset for SQL query
$offset = ($page - 1) * $limit;

// Fetch total number of tools
$totalToolsQuery = $conn->query("SELECT COUNT(*) as total FROM tools");
$totalTools = $totalToolsQuery->fetch_assoc()['total'];
$totalPages = ceil($totalTools / $limit);

// Fetch tools for the current page
$toolsQuery = $conn->query("SELECT * FROM tools LIMIT $limit OFFSET $offset");
$tools = $toolsQuery->fetch_all(MYSQLI_ASSOC);
?>


<br>
<!-- Tools Inventory Table -->
<table class="table table-bordered text-center align-middle">
    <thead class="table-dark">
        <!-- Table header row -->
        <tr>
            <th>ID</th>
            <th>Tool & Brand</th>
            <th>Image</th>
            <th>Quantity</th>
            <th>Item Condition</th>
            <th>Action</th>
        </tr>    
    </thead>
    <tbody>
    <?php foreach ($tools as $row) : ?>
        <tr>
            <td><?= htmlspecialchars($row['id']); ?></td>
            <td><?= htmlspecialchars($row['tool_name'] . ' - ' . $row['brand']); ?></td>
            <td>
                <?php if (!empty($row['image'])) { ?>
                    <button class="btn btn-sixth view-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-img="<?= htmlspecialchars($row['image']); ?>">View</button>
                <?php } else { ?>
                    <span class="text-muted" title="No image available">No Image</span>
                <?php } ?>
            </td>
            <td><?= htmlspecialchars($row['quantity']); ?></td>
            <td><?= htmlspecialchars($row['item_condition'] ?? ''); ?></td>
            <td>
                
                <a href="borrow_btn.php?tool_id=<?= $row['id']; ?>&tool_name=<?= urlencode($row['tool_name']); ?>&brand=<?= urlencode($row['brand']); ?>">Borrow</a>
                <a href="return.php?tool_id=<?= $row['id']; ?>&tool_name=<?= urlencode($row['tool_name']); ?>&brand=<?= urlencode($row['brand']); ?>">Return</a>
            </td>
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

            <!-- Modal for Image View -->
            <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="imageModalLabel">Tool Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <img id="modal-image" src="" alt="Tool Image" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>

          

            

            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-fifth btn-lg">Back to Dashboard</a>
            </div>
        </div>
    </div>

    

    <!-- Delete Tool Modal -->
    <div class="modal fade" id="deleteToolModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Delete a Tool</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="delete_tools.php" onsubmit="return confirm('Are you sure you want to delete this tool?');">
                        <div class="mb-3">
                            <label for="tool_id" class="form-label">Select Tool:</label>
                            <select name="tool_id" id="tool_id" class="form-select" required>
                                <option value="" disabled selected>Select a tool</option>
                                <?php foreach ($tools as $tool) : ?>
                                    <option value="<?= htmlspecialchars($tool['id']); ?>">
                                        <?= htmlspecialchars($tool['id'] . ' - ' . $tool['tool_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
    // Ensure that the correct image is loaded in the modal
    $(document).ready(function() {
        $('.view-image').on('click', function() {
            var imageSrc = $(this).data('img');  // Get the image source from the data-img attribute
            $('#modal-image').attr('src', imageSrc);  // Set the src attribute of the modal image
        });
    });

</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
$(document).ready(function() {
    function filterTools() {
        let search = $("#searchBar").val();
        let toolID = $("#filterID").val();
        let toolName = $("#filterTool").val();
        let brand = $("#filterBrand").val();

        $.ajax({
            url: "filter_tools.php",
            type: "GET",
            data: {
                search: search,
                tool_id: toolID,
                tool_name: toolName,
                brand: brand
            },
            success: function(response) {
                $("tbody").html(response);
            }
        });
    }

    $("#searchBar, #filterID, #filterTool, #filterBrand").on("input change", filterTools);
});
</script>
</body>
</html>