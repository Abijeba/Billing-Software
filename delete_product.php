<?php
require_once 'config.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id) {
    $pdo->prepare("DELETE FROM products WHERE product_id=?")->execute([$product_id]);
}

header("Location: products.php");
exit;
