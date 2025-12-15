<?php
require_once 'config.php';


if (empty($_GET['id'])) {
    die("❌ Invalid Request");
}

$id = (int)$_GET['id'];

// Delete child items first
$stmt = $pdo->prepare("DELETE FROM purchase_return_items WHERE pr_id = ?");
$stmt->execute([$id]);

// Delete parent return
$stmt = $pdo->prepare("DELETE FROM purchase_returns WHERE return_id = ?");
$stmt->execute([$id]);

// Redirect (no HTML before this line!)
header("Location: purchase_return_list.php?msg=deleted");
exit;
