<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Invoice ID");
}

$pi_id = intval($_GET['id']);

try {
    $pdo->beginTransaction();

    // 🟡 Step 1: Fetch all items before deleting
    $stmt = $pdo->prepare("SELECT product_name, quantity FROM purchase_invoice_items WHERE pi_id = ?");
    $stmt->execute([$pi_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🟡 Step 2: Reduce stock for each product
    foreach ($items as $item) {
        $product = $item['product_name'];
        $qty = floatval($item['quantity']);

        $stmtStock = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_name = ?");
        $stmtStock->execute([$product]);
        $currentStock = $stmtStock->fetchColumn();

        if ($currentStock !== false) {
            $newStock = max(0, $currentStock - $qty); // prevent negative stock
            $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE product_name = ?")
                ->execute([$newStock, $product]);
        }
    }

    // 🟢 Step 3: Delete items and invoice
    $pdo->prepare("DELETE FROM purchase_invoice_items WHERE pi_id=?")->execute([$pi_id]);
    $pdo->prepare("DELETE FROM purchase_invoices WHERE pi_id=?")->execute([$pi_id]);

    $pdo->commit();
    header("Location: purchase_invoice_list.php?msg=deleted");
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error deleting invoice: " . $e->getMessage());
}
?>
