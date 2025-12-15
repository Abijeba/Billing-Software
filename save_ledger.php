<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type            = $_POST['type'] ?? '';
    $ledger_name     = $_POST['ledger_name'] ?? '';
    $account_group   = $_POST['account_group'] ?? '';
    $contact         = $_POST['contact'] ?? '';
    $email           = $_POST['email'] ?? '';
   $opening_amount = isset($_POST['opening_amount']) && $_POST['opening_amount'] !== '' 
    ? (float) $_POST['opening_amount'] 
    : 0;
    $opening_type    = $_POST['opening_type'] ?? 'To Receive'; // New dropdown
    $gst_details     = $_POST['gst_details'] ?? '';
    $billing_address = $_POST['billing_address'] ?? '';
    $shipping_address= $_POST['shipping_address'] ?? '';

    try {
        $sql = "INSERT INTO ledgers 
                (type, ledger_name, account_group, contact, email, opening_amount, opening_type, gst_details, billing_address, shipping_address) 
                VALUES 
                (:type, :ledger_name, :account_group, :contact, :email, :opening_amount, :opening_type, :gst_details, :billing_address, :shipping_address)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':type' => $type,
            ':ledger_name' => $ledger_name,
            ':account_group' => $account_group,
            ':contact' => $contact,
            ':email' => $email,
            ':opening_amount' => $opening_amount,
            ':opening_type' => $opening_type,
            ':gst_details' => $gst_details,
            ':billing_address' => $billing_address,
            ':shipping_address' => $shipping_address
        ]);

        // Redirect back to ledger page with success message
        header("Location: ledger.php?success=1");
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ledger.php");
    exit;
}
