<?php
require_once 'config.php';

$category_id = $_GET['category_id'] ?? 0;

$stmt = $pdo->prepare("SELECT product_id, product_name, rate FROM products WHERE category_id = ?");
$stmt->execute([$category_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($products);
