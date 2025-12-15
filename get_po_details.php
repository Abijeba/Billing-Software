<?php
require_once 'config.php';

$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
$response = [];

if ($po_id > 0) {
    // Fetch PO header
    $stmt = $pdo->prepare("SELECT po_number, supplier_id, tax_mode, supplier_bill_no 
                           FROM purchase_orders WHERE po_id = ?");
    $stmt->execute([$po_id]);
    $response['po'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch PO items
    $stmtItems = $pdo->prepare("SELECT category, product_name, quantity, rate, discount, gst 
                                FROM purchase_order_items WHERE po_id = ?");
    $stmtItems->execute([$po_id]);
    $response['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode($response);
