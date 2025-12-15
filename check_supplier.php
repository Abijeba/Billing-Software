<?php
require_once 'config.php';

if (isset($_POST['supplier_name'])) {
    $name = trim($_POST['supplier_name']);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers WHERE supplier_name = ?");
    $stmt->execute([$name]);
    echo ($stmt->fetchColumn() > 0) ? "exists" : "ok";
}
?>
