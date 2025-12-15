<?php
require_once 'config.php';

header('Content-Type: application/json');

$party_id = isset($_GET['party_id']) ? intval($_GET['party_id']) : 0;

if ($party_id <= 0) {
    echo json_encode(['balance' => 0]);
    exit;
}

// --- Calculate total sales from line totals ---
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(sii.line_total), 0) AS total_sales
    FROM sales_invoices si
    JOIN sales_invoice_items sii ON si.si_id = sii.si_id
    WHERE si.customer_id = ?
");
$stmt->execute([$party_id]);
$total_sales = $stmt->fetchColumn();

// --- Calculate total received ---
$stmt2 = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_received
    FROM payments_in
    WHERE party_id = ?
");
$stmt2->execute([$party_id]);
$total_received = $stmt2->fetchColumn();

// --- Balance (To Receive) ---
$balance = $total_sales - $total_received;

// Send formatted JSON
echo json_encode(['balance' => number_format($balance, 2)]);
?>
