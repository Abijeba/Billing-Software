<?php
require_once "config.php";

if (!isset($_GET['pi_id'])) {
    echo json_encode([]);
    exit;
}

$pi_id = intval($_GET['pi_id']);

// Fetch items of the selected PI
$stmt = $pdo->prepare("SELECT pii.*, p.product_name, p.stock_quantity
                       FROM purchase_invoice_items pii
                       JOIN products p ON pii.product_name = p.product_name
                       WHERE pii.pi_id = ?");
$stmt->execute([$pi_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($items);
