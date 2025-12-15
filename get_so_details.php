<?php
require_once 'config.php';

$so_id = isset($_GET['so_id']) ? intval($_GET['so_id']) : 0;

$response = ['so' => null, 'items' => []];

if ($so_id > 0) {
    // Get SO Header
    $stmt = $pdo->prepare("SELECT * FROM sales_orders WHERE so_id = ?");
    $stmt->execute([$so_id]);
    $so = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($so) {
        $response['so'] = $so;

        // Get SO Items
        $stmtItems = $pdo->prepare("SELECT * FROM sales_order_items WHERE so_id = ?");
        $stmtItems->execute([$so_id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $response['items'] = $items;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
