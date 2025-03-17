<?php
include 'db.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$borrowerID = isset($_GET['borrower_id']) ? $_GET['borrower_id'] : '';
$toolName = isset($_GET['tool_name']) ? $_GET['tool_name'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build the query dynamically
$query = "SELECT b.borrower_id, b.tool_id, t.tool_name, t.brand, 
                 b.borrower_name, b.quantity, b.site, 
                 b.date_borrowed, b.status 
          FROM borrowed_tools b
          JOIN tools t ON t.id = b.tool_id
          WHERE 1=1";

// Apply filters
if (!empty($search)) {
    $query .= " AND (b.borrower_id LIKE '%$search%' 
                OR b.borrower_name LIKE '%$search%'
                OR t.tool_name LIKE '%$search%'
                OR t.brand LIKE '%$search%'
                OR b.site LIKE '%$search%')";
}

if (!empty($borrowerID)) {
    $query .= " AND b.borrower_id = '$borrowerID'";
}

if (!empty($toolName)) {
    $query .= " AND t.tool_name = '$toolName'";
}

if (!empty($status)) {
    $query .= " AND b.status = '$status'";
}

$query .= " ORDER BY b.date_borrowed DESC"; // Sort by latest borrowed tools

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
                <td>" . htmlspecialchars($row['borrower_id']) . "</td>
                <td>" . htmlspecialchars($row['borrower_name']) . "</td>
                <td>" . htmlspecialchars($row['tool_id']) . "</td>
                <td>" . htmlspecialchars($row['tool_name']) . "</td>
                <td>" . htmlspecialchars($row['brand']) . "</td>
                <td>" . htmlspecialchars($row['quantity']) . "</td>
                <td>" . htmlspecialchars($row['site']) . "</td>
                <td>" . date("Y-m-d H:i:s", strtotime($row['date_borrowed'])) . "</td>
                <td>" . htmlspecialchars($row['status']) . "</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='9' class='text-center text-danger'>No matching records found</td></tr>";
}

mysqli_close($conn);
?>
