<?php
require_once 'config.php';

$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($category_id <= 0) {
    die("Invalid category ID");
}

try {
    // Step 1: Check if category is used in products
    $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $check->execute([$category_id]);
    $count = $check->fetchColumn();

    if ($count > 0) {
        // Stop deletion if products exist
        header("Location: category.php?msg=Cannot delete category — products are linked to it.");
        exit;
    }

    // Step 2: Safe to delete
    $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->execute([$category_id]);

    header("Location: category.php?msg=Category deleted successfully");
    exit;

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
