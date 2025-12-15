<?php
require_once 'config.php';

if (empty($_GET['id'])) {
    die("Invalid request.");
}

$id = (int)$_GET['id'];

// --- Delete Query ---
$stmt = $pdo->prepare("DELETE FROM payments_out WHERE id = ?");
if ($stmt->execute([$id])) {
    header("Location: payment_out_list.php?msg=deleted");
    exit;
} else {
    echo "❌ Error deleting record.";
}
?>