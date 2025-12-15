<?php
require_once 'config.php';

if (empty($_GET['id'])) {
    die("❌ Invalid Request");
}

$id = (int)$_GET['id'];

// --- Restore stock if column exists ---
$columns = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'")->fetch();
$has_stock = $columns ? true : false;

if ($has_stock) {
    $items = $pdo->prepare("SELECT product_id, quantity FROM sales_return_items WHERE sr_id = ?");
    $items->execute([$id]);
    $items = $items->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?")
            ->execute([$item['quantity'], $item['product_id']]);
    }
}

// --- Delete child items ---
$stmt = $pdo->prepare("DELETE FROM sales_return_items WHERE sr_id = ?");
$stmt->execute([$id]);

// --- Delete parent return ---
$stmt = $pdo->prepare("DELETE FROM sales_returns WHERE return_id = ?");
$stmt->execute([$id]);

// Redirect back to list
header("Location: sales_return_list.php?msg=deleted");
exit;
