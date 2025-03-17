<?php
include 'db.php';

header('Content-Type: application/json');

try {
    // Validate input parameters
    $metric = $_GET['metric'] ?? 'total_products';
    $filter = $_GET['filter'] ?? 'all';
    
    // Define time conditions
    $date_conditions = [
        'today' => [
            'condition' => "created_at >= CURDATE()",
            'group_format' => "%H:00"
        ],
        'week' => [
            'condition' => "created_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)",
            'group_format' => "%Y-%m-%d"
        ],
        'month' => [
            'condition' => "created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)",
            'group_format' => "%Y-%m-%d"
        ],
        'year' => [
            'condition' => "created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)",
            'group_format' => "%Y-%m"
        ],
        'all' => [
            'condition' => "1=1",
            'group_format' => "%Y-%m"
        ]
    ];

    // Validate filter
    if (!array_key_exists($filter, $date_conditions)) {
        throw new Exception("Invalid filter parameter");
    }

    $time_condition = $date_conditions[$filter]['condition'];
    $group_format = $date_conditions[$filter]['group_format'];

    // Define metric configurations
    $metric_config = [
        'total_products' => [
            'title' => 'Products Added Over Time',
            'sql' => "SELECT 
                        DATE_FORMAT(created_at, '$group_format') AS period,
                        COUNT(*) AS value
                      FROM products
                      WHERE $time_condition
                      GROUP BY period
                      ORDER BY created_at"
        ],
        'total_value' => [
            'title' => 'Inventory Value Over Time',
            'sql' => "SELECT 
                        DATE_FORMAT(created_at, '$group_format') AS period,
                        SUM(quantity * price) AS value
                      FROM products
                      WHERE $time_condition
                      GROUP BY period
                      ORDER BY created_at"
        ],
        'low_stock' => [
            'title' => 'Low Stock Items Over Time',
            'sql' => "SELECT 
                        DATE_FORMAT(created_at, '$group_format') AS period,
                        COUNT(*) AS value
                      FROM products
                      WHERE quantity < 10 AND $time_condition
                      GROUP BY period
                      ORDER BY created_at"
        ],
        'avg_price' => [
            'title' => 'Average Price Over Time',
            'sql' => "SELECT 
                        DATE_FORMAT(created_at, '$group_format') AS period,
                        AVG(price) AS value
                      FROM products
                      WHERE $time_condition
                      GROUP BY period
                      ORDER BY created_at"
        ]
    ];

    // Validate metric
    if (!array_key_exists($metric, $metric_config)) {
        throw new Exception("Invalid metric parameter");
    }

    // Execute query
    $stmt = $pdo->prepare($metric_config[$metric]['sql']);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response
    $response = [
        'title' => $metric_config[$metric]['title'],
        'labels' => array_column($result, 'period'),
        'data' => array_column($result, 'value')
    ];

    // Handle empty data
    if (empty($response['labels'])) {
        $response['labels'] = [];
        $response['data'] = [];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}