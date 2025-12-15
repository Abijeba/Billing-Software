<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo 'unknown';
    exit;
}

$si_id = (int)$_GET['id'];

// Fetch category based on the invoice’s product
$sql = "SELECT c.category_name 
        FROM sales_invoice_items sii
        JOIN products p ON sii.product_id = p.product_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE sii.si_id = ? 
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$si_id]);
$category = $stmt->fetchColumn();

echo $category ?: 'unknown';
