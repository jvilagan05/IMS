<?php
include 'db.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$toolID = isset($_GET['tool_id']) ? $_GET['tool_id'] : '';
$toolName = isset($_GET['tool_name']) ? $_GET['tool_name'] : '';
$brand = isset($_GET['brand']) ? $_GET['brand'] : '';

$query = "SELECT * FROM tools WHERE 1";

if (!empty($search)) {
    $query .= " AND (tool_name LIKE '%$search%' OR brand LIKE '%$search%' OR id LIKE '%$search%')";
}
if (!empty($toolID)) {
    $query .= " AND id = '$toolID'";
}
if (!empty($toolName)) {
    $query .= " AND tool_name = '$toolName'";
}
if (!empty($brand)) {
    $query .= " AND brand = '$brand'";
}

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['tool_name']} - {$row['brand']}</td>
        <td>" . (!empty($row['image']) ? "<button class='btn btn-warning view-image' data-bs-toggle='modal' data-bs-target='#imageModal' data-img='{$row['image']}'>View</button>" : "<span class='text-muted'>No Image</span>") . "</td>
        <td>{$row['quantity']}</td>
        <td>{$row['item_condition']}</td>
        <td>
            <a href='borrow_btn.php?tool_id={$row['id']}&tool_name=" . urlencode($row['tool_name']) . "&brand=" . urlencode($row['brand']) . "'>Borrow</a>
            <a href='return.php?tool_id={$row['id']}&tool_name=" . urlencode($row['tool_name']) . "&brand=" . urlencode($row['brand']) . "'>Return</a>
        </td>
    </tr>";
}
?>