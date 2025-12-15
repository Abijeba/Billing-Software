<?php
require_once 'config.php';

if (isset($_POST['customer_name'])) {
    $name = trim($_POST['customer_name']);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE LOWER(customer_name) = LOWER(?)");
    $stmt->execute([$name]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo 'exists';
    } else {
        echo 'available';
    }
}
?>
