<?php
require "config.php";

try {
    // Hash the password
    $hash = password_hash("admin123", PASSWORD_DEFAULT);

    // Insert admin user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute(["Admin", "admin@example.com", $hash]);

    echo "✅ Admin user created successfully!<br>";
    echo "👉 Email: admin@example.com<br>";
    echo "👉 Password: admin123";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}