<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pi_number    = $_POST['pi_number'] ?? '';
    $date         = $_POST['date'] ?? '';
    $party_id  = $_POST['party_id'] ?? '';
    $amount       = (float)$_POST['amount'];
    $payment_mode = $_POST['payment_mode'] ?? '';
    $notes        = $_POST['notes'] ?? '';

    // --- Validate Customer Balance ---
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(sii.line_total), 0) - COALESCE((
            SELECT SUM(pi.amount) 
            FROM payments_in pi 
            WHERE pi.party_id = si.customer_id
        ), 0) AS balance
        FROM sales_invoices si
        JOIN sales_invoice_items sii ON si.si_id = sii.si_id
        WHERE si.customer_id = ?
        GROUP BY si.customer_id
    ");
    $stmt->execute([$party_id]);
    $balance = (float)$stmt->fetchColumn();

    if ($amount > $balance) {
        die("<h3 style='color:red;'>❌ Error: Payment amount exceeds customer balance (₹" . number_format($balance, 2) . ")</h3>");
    }

    // --- Insert Payment ---
    $stmt = $pdo->prepare("
        INSERT INTO payments_in (pi_number, date, party_id, amount, payment_mode, notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$pi_number, $date, $party_id, $amount, $payment_mode, $notes]);

    header("Location: payment_in_list.php");
    exit;
}
?>
