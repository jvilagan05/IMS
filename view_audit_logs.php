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

// Function to fetch logs based on search, filters, and pagination
function fetchLogs($pdo, $search = '', $filterColumn = '', $sortOrder = 'DESC', $limit = 10, $offset = 0) {
    $query = "SELECT audit_log.id, users.username, audit_log.action, audit_log.table_name, 
                      audit_log.record_id, audit_log.change_time, audit_log.old_value, audit_log.new_value, audit_log.ip_address 
               FROM audit_log
               JOIN users ON audit_log.user_id = users.id
               WHERE (users.username LIKE :search OR
                      audit_log.action LIKE :search OR
                      audit_log.table_name LIKE :search OR
                      audit_log.record_id LIKE :search OR
                      DATE_FORMAT(audit_log.change_time, '%Y-%m-%d %H:%i:%s') LIKE :search)
               ORDER BY " . (!empty($filterColumn) ? "$filterColumn $sortOrder" : "audit_log.change_time $sortOrder") . "
               LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to count total logs for pagination
function countLogs($pdo, $search = '') {
    $query = "SELECT COUNT(*) as total FROM audit_log
              JOIN users ON audit_log.user_id = users.id
              WHERE (users.username LIKE :search OR
                     audit_log.action LIKE :search OR
                     audit_log.table_name LIKE :search OR
                     audit_log.record_id LIKE :search OR
                     DATE_FORMAT(audit_log.change_time, '%Y-%m-%d %H:%i:%s') LIKE :search)";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}

// Handle AJAX request for search/filter/pagination
if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
    $search = $_POST['search'] ?? '';
    $filterColumn = $_POST['filterColumn'] ?? '';
    $sortOrder = $_POST['sortOrder'] ?? 'DESC';
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $logs = fetchLogs($pdo, $search, $filterColumn, $sortOrder, $limit, $offset);
    $totalLogs = countLogs($pdo, $search);
    $totalPages = ceil($totalLogs / $limit);

    if (empty($logs)) {
        echo "<tr><td colspan='8' class='text-center'>No audit logs found.</td></tr>";
    } else {
        foreach ($logs as $log) {
            echo "<tr>
                    <td>" . htmlspecialchars($log['username']) . "</td>
                    <td>" . htmlspecialchars($log['action']) . "</td>
                    <td>" . htmlspecialchars($log['table_name']) . "</td>
                    <td>" . htmlspecialchars($log['record_id']) . "</td>
                    <td>" . date('F j, Y - h:i A', strtotime($log['change_time'])) . "</td>
                    <td>";
                    
                    // Display Old Value
                    $old_value = json_decode($log['old_value'], true);
                    if ($old_value) {
                        foreach ($old_value as $key => $value) {
                            echo "<strong>{$key}:</strong> " . htmlspecialchars($value) . "<br>";
                        }
                    }
            echo "</td>
                    <td>";
                    
                    // Display New Value
                    $new_value = json_decode($log['new_value'], true);
                    if ($new_value) {
                        foreach ($new_value as $key => $value) {
                            echo "<strong>{$key}:</strong> " . htmlspecialchars($value) . "<br>";
                        }
                    }
            echo "</td>
                    <td>" . htmlspecialchars($log['ip_address']) . "</td>
                </tr>";
        }
    }

    // Pagination buttons
    echo '<tr><td colspan="8" class="text-center">';
    for ($i = 1; $i <= $totalPages; $i++) {
        echo "<button class='btn btn-sm " . ($i == $page ? "btn-danger" : "btn-outline-danger") . "' onclick='fetchLogs($i)'>$i</button> ";
    }
    echo '</td></tr>';
    exit;
}

// Fetch initial logs for page load
$logs = fetchLogs($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUDIT LOGS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="view_audit_logs.css">

    <link rel="icon" type="image/png" href="ejmt.png">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-3">Audit Logs</h2>

        <!-- Search and Filter Section -->
        <div class="d-flex justify-content-between">
            <input type="text" id="search" class="form-control" placeholder="Search by User, Action, Table, Record ID, Change Time" onkeyup="fetchLogs(1)">
            
            <select id="filterColumn" class="form-control mx-2" onchange="fetchLogs(1)">
                <option value="">Filter by</option>
                <option value="user_id">User ID</option>
                <option value="action">Action</option>
                <option value="table_name">Table Name</option>
                <option value="record_id">Record ID</option>
                <option value="change_time">Change Time</option>
            </select>
            
            <select id="sortOrder" class="form-control" onchange="fetchLogs(1)">
                <option value="ASC">Ascending</option>
                <option value="DESC" selected>Descending</option>
            </select>
        </div>

        <!-- Back to Dashboard Button -->
        <div class="text-center mb-3 mt-3">
            <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
        </div>

        <!-- Audit Log Table -->
        <table class="table table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>User ID</th>
                    <th>Action</th>
                    <th>Table Name</th>
                    <th>Record ID</th>
                    <th>Change Time</th>
                    <th>Old Value</th>
                    <th>New Value</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody id="log-rows">
                <!-- Logs will be loaded here via AJAX -->
            </tbody>
        </table>
    </div>

    <script>
    function fetchLogs(page = 1) {
        let search = $("#search").val();
        let filterColumn = $("#filterColumn").val();
        let sortOrder = $("#sortOrder").val();

        $.ajax({
            url: "view_audit_logs.php",
            method: "POST",
            data: {
                ajax: "true",
                search: search,
                filterColumn: filterColumn,
                sortOrder: sortOrder,
                page: page
            },
            success: function (response) {
                $("#log-rows").html(response);
            }
        });
    }

    $(document).ready(function() {
        fetchLogs();
    });
    </script>
</body>
</html>
