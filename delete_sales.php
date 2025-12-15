<?php
require_once 'config.php';

$so_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($so_id > 0) {
    try {
        $pdo->beginTransaction();

        // Delete invoice items of invoices related to this sales order
        $stmt = $pdo->prepare("SELECT si_id FROM sales_invoices WHERE so_id = ?");
        $stmt->execute([$so_id]);
        $si_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($si_ids as $si_id) {
            $pdo->prepare("DELETE FROM sales_invoice_items WHERE si_id = ?")->execute([$si_id]);
        }

        // Delete sales invoices
        $pdo->prepare("DELETE FROM sales_invoices WHERE so_id = ?")->execute([$so_id]);

        // Delete sales order items
        $pdo->prepare("DELETE FROM sales_order_items WHERE so_id = ?")->execute([$so_id]);

        // Delete sales order
        $pdo->prepare("DELETE FROM sales_orders WHERE so_id = ?")->execute([$so_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error deleting Sales Order: " . $e->getMessage());
    }
}

header("Location: sales_list.php");
exit;
?>