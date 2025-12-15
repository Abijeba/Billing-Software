<?php
require_once 'config.php';
//include 'header.php';

$po_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($po_id > 0) {
    try {
        $pdo->beginTransaction();

        // Delete items first
        $pdo->prepare("DELETE FROM purchase_order_items WHERE po_id=?")->execute([$po_id]);

        // Then delete the purchase order
        $pdo->prepare("DELETE FROM purchase_orders WHERE po_id=?")->execute([$po_id]);

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error deleting PO: " . $e->getMessage());
    }
}

header("Location: purchase_list.php");
exit;
