<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Invoice ID");
}

$si_id = intval($_GET['id']);

try {
    $pdo->beginTransaction();

    // Delete invoice items first
    $pdo->prepare("DELETE FROM sales_invoice_items WHERE si_id=?")->execute([$si_id]);

    // Delete invoice header
    $pdo->prepare("DELETE FROM sales_invoices WHERE si_id=?")->execute([$si_id]);

    $pdo->commit();

    // Redirect to list page with success message
    header("Location: sales_invoice_list.php?msg=deleted");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error deleting invoice: " . $e->getMessage());
}
