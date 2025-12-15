<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pm_number = $_POST['pm_number'];
    $date = $_POST['date'];
    $party_id = $_POST['party_id'];
    $amount = (float)$_POST['amount'];
    $payment_mode = $_POST['payment_mode'];
    $notes = $_POST['notes'];

    // --- Validate Supplier Balance ---
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(pii.line_total), 0) - COALESCE((
                SELECT SUM(po.amount) 
                FROM payments_out po 
                WHERE po.party_id = pi.supplier_id
            ), 0) AS balance
        FROM purchase_invoices pi
        JOIN purchase_invoice_items pii ON pi.pi_id = pii.pi_id
        WHERE pi.supplier_id = ?
        GROUP BY pi.supplier_id
    ");
    $stmt->execute([$party_id]);
    $balance = (float)$stmt->fetchColumn();

    // --- Check for overpayment ---
    if ($amount > $balance) {
        die("<h3 style='color:red;'>❌ Error: Payment amount exceeds supplier balance (₹" . number_format($balance, 2) . ")</h3>");
    }

    // --- Insert Payment ---
    $stmt = $pdo->prepare("
        INSERT INTO payments_out (pm_number, date, party_id, amount, payment_mode, notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$pm_number, $date, $party_id, $amount, $payment_mode, $notes]);

    header("Location: payment_out_list.php");
    exit;
}
?>
