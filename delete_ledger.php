<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = (int) $_POST['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM ledgers WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount()) {
            echo "success";
        } else {
            echo "Ledger not found";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request";
}
