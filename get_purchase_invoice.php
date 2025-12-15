<?php
require_once "config.php";
header('Content-Type: application/json');

if (!isset($_GET['pi_id'])) {
    echo json_encode(["error" => "Missing pi_id"]);
    exit;
}

$pi_id = intval($_GET['pi_id']);

// ✅ Fetch invoice header
$stmt = $pdo->prepare("SELECT pi_id, pi_no, bill_no, supplier_id FROM purchase_invoices WHERE pi_id = ?");
$stmt->execute([$pi_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    echo json_encode(["error" => "Invoice not found"]);
    exit;
}

// ✅ Fetch invoice items
$stmt2 = $pdo->prepare("
    SELECT pii.product_id, p.product_name, p.rate, p.gst, p.discount,
           pii.quantity, pii.line_total, p.stock
    FROM purchase_invoice_items pii
    JOIN products p ON pii.product_id = p.product_id
    WHERE pii.pi_id = ?
");
$stmt2->execute([$pi_id]);
$items = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["invoice" => $invoice, "items" => $items]);
