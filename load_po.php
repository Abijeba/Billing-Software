<?php
require_once "config.php";

$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
$response = ["success" => false];

if($po_id > 0){
    // Fetch PO details
    $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE po_id = ?");
    $stmt->execute([$po_id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch PO items
    $itemStmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
    $itemStmt->execute([$po_id]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    if($po){
        $response["success"] = true;
        $response["po"] = $po;
        $response["items"] = $items;
    }
}
header("Content-Type: application/json");
echo json_encode($response);
