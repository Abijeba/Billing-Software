<?php
require_once 'config.php';

if (empty($_GET['id'])) {
    header("Location: customer.php?message=❌ Invalid request");
    exit;
}

$customer_id = (int)$_GET['id'];

try {
    // Check if customer exists
    $check_stmt = $pdo->prepare("SELECT customer_name FROM customers WHERE customer_id = ?");
    $check_stmt->execute([$customer_id]);
    $customer = $check_stmt->fetch();
    
    if (!$customer) {
        header("Location: customer.php?message=❌ Customer not found");
        exit;
    }
    
    $customer_name = $customer['customer_name'];
    
    // Check for related records
    $errors = [];
    
    // Check sales invoices
    $check_invoices = $pdo->prepare("SELECT COUNT(*) as count FROM sales_invoices WHERE customer_id = ?");
    $check_invoices->execute([$customer_id]);
    $invoice_count = $check_invoices->fetch()['count'];
    
    if ($invoice_count > 0) {
        $errors[] = "$invoice_count sales invoice(s)";
    }
    
    // Check payments
    $check_payments = $pdo->prepare("SELECT COUNT(*) as count FROM payments_in WHERE party_id = ?");
    $check_payments->execute([$customer_id]);
    $payment_count = $check_payments->fetch()['count'];
    
    if ($payment_count > 0) {
        $errors[] = "$payment_count payment(s)";
    }
    
    // Check sales orders
    $check_orders = $pdo->prepare("SELECT COUNT(*) as count FROM sales_orders WHERE customer_id = ?");
    $check_orders->execute([$customer_id]);
    $order_count = $check_orders->fetch()['count'];
    
    if ($order_count > 0) {
        $errors[] = "$order_count sales order(s)";
    }
    
    // If there are related records, show error
    if (!empty($errors)) {
        $error_message = "❌ Cannot delete customer '$customer_name'. There are " . implode(', ', $errors) . " associated with this customer.";
        header("Location: customer.php?message=" . urlencode($error_message));
        exit;
    }
    
    // Delete the customer
    $delete_stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id = ?");
    $delete_stmt->execute([$customer_id]);
    
    header("Location: customer.php?message=✅ Customer '$customer_name' deleted successfully");
    exit;
    
} catch (Exception $e) {
    header("Location: customer.php?message=❌ Error: " . urlencode($e->getMessage()));
    exit;
}
?>