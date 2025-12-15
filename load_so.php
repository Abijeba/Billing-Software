<?php
require_once "config.php";

$so_id = isset($_GET['so_id']) ? intval($_GET['so_id']) : 0;
$response = ["success" => false, "deleted_count" => 0];

if($so_id > 0){
    // Fetch SO details with customer name
    $stmt = $pdo->prepare("
        SELECT so.*, c.customer_name 
        FROM sales_orders so 
        LEFT JOIN customers c ON so.customer_id = c.customer_id 
        WHERE so.so_id = ?
    ");
    $stmt->execute([$so_id]);
    $so = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch SO items
    $itemStmt = $pdo->prepare("SELECT * FROM sales_order_items WHERE so_id = ?");
    $itemStmt->execute([$so_id]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    // Compare requested vs fetched items
    $deleted_count = 0;
    if($so){
        // Optional: if you store expected item count in SO table
        // $deleted_count = $so['total_items'] - count($items);

        // Or just count items missing (example: maybe from previous saved list)
        // Here we just send items as is, frontend will handle message
    }

    $response["success"] = true;
    $response["so"] = $so;
    $response["items"] = $items;
    $response["deleted_count"] = $deleted_count; // currently 0
}

header("Content-Type: application/json");
echo json_encode($response);
?>