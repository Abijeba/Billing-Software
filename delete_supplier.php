<?php
require_once 'config.php';

if (empty($_GET['id'])) {
    header("Location:  insert_supplier.php?message=❌ Invalid request");
    exit;
}

$supplier_id = (int)$_GET['id'];

try {
    // Check if supplier exists
    $check_stmt = $pdo->prepare("SELECT supplier_name FROM suppliers WHERE supplier_id = ?");
    $check_stmt->execute([$supplier_id]);
    $supplier = $check_stmt->fetch();
    
    if (!$supplier) {
        header("Location:  insert_supplier.php?message=❌ Supplier not found");
        exit;
    }
    
    $supplier_name = $supplier['supplier_name'];
    
    // Check for related records
    $errors = [];
    
    // Check purchase orders
    $check_orders = $pdo->prepare("SELECT COUNT(*) as count FROM purchase_orders WHERE supplier_id = ?");
    $check_orders->execute([$supplier_id]);
    $order_count = $check_orders->fetch()['count'];
    
    if ($order_count > 0) {
        $errors[] = "$order_count purchase order(s)";
    }
    
    // Check purchase returns
    $check_returns = $pdo->prepare("SELECT COUNT(*) as count FROM purchase_returns WHERE supplier_id = ?");
    $check_returns->execute([$supplier_id]);
    $return_count = $check_returns->fetch()['count'];
    
    if ($return_count > 0) {
        $errors[] = "$return_count purchase return(s)";
    }
    
    // If there are related records, show error
    if (!empty($errors)) {
        $error_message = "❌ Cannot delete supplier '$supplier_name'. There are " . implode(', ', $errors) . " associated with this supplier.";
        header("Location:  insert_supplier.php?message=" . urlencode($error_message));
        exit;
    }
    
    // Delete the supplier
    $delete_stmt = $pdo->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
    $delete_stmt->execute([$supplier_id]);
    
    header("Location:  insert_supplier.php?message=✅ Supplier '$supplier_name' deleted successfully");
    exit;
    
} catch (Exception $e) {
    header("Location: insert_supplier.php?message=❌ Error: " . urlencode($e->getMessage()));
    exit;
}
?>