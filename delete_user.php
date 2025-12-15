<?php
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $pdo->prepare("DELETE FROM adminlist WHERE id = :id");
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        // Reset AUTO_INCREMENT if table is empty
        $count = $pdo->query("SELECT COUNT(*) FROM adminlist")->fetchColumn();
        if ($count == 0) {
            $pdo->exec("ALTER TABLE adminlist AUTO_INCREMENT = 1");
        }
        header("Location: admin_list.php?deleted=1");
        exit;
    } else {
        header("Location: admin_list.php?deleted=0");
        exit;
    }
}
?>
