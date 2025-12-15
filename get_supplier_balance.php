<?php
require_once 'config.php';

header('Content-Type: application/json');

$party_id = isset($_GET['party_id']) ? intval($_GET['party_id']) : 0;

if ($party_id <= 0) {
    echo json_encode(['balance' => 0]);
    exit;
}

// --- Calculate total purchase from line totals ---
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(pii.line_total),0) AS total_purchase
    FROM purchase_invoices pi
    JOIN purchase_invoice_items pii ON pi.pi_id = pii.pi_id
    WHERE pi.supplier_id = ?
");
$stmt->execute([$party_id]);
$total_purchase = $stmt->fetchColumn();

// --- Calculate total paid ---
$stmt2 = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0) AS total_paid
    FROM payments_out
    WHERE party_id = ?
");
$stmt2->execute([$party_id]);
$total_paid = $stmt2->fetchColumn();

// --- Balance ---
$balance = $total_purchase - $total_paid;

echo json_encode(['balance' => number_format($balance, 2)]);
