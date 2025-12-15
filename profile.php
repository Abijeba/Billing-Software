<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$title = "My Profile";
include "header.php";

// --- Profile photo logic ---
$gender = strtolower(trim($_SESSION['gender'] ?? 'female'));
$profilePic = "https://cdn-icons-png.flaticon.com/512/4140/4140051.png"; // 👩 Female



// --- Use the correct session variables ---
$name = htmlspecialchars($_SESSION['user'] ?? 'User'); // ✅ session variable is 'user'
$role     = htmlspecialchars($_SESSION['role'] ?? 'User');
$company  = htmlspecialchars($_SESSION['company'] ?? 'N/A');
$email    = htmlspecialchars($_SESSION['email'] ?? '');
?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 90vh;">
  <div class="card border-0 shadow-lg p-4 text-center" style="width: 360px; border-radius: 20px; animation: fadeIn 1s ease-in-out;">
    <div class="d-flex justify-content-center mb-3">
      <img src="<?= $profilePic ?>" 
           alt="Profile Photo" 
           class="rounded-circle border border-3 border-primary shadow-sm" 
           width="120" height="120" 
           style="animation: bounce 2s infinite ease-in-out;">
    </div>

    <h4 class="fw-bold text-dark mb-1"><?= $name ?></h4>
    <p class="text-muted mb-3"><?= $role ?></p>

    <div class="text-start mt-4">
      <div class="mb-3">
        <i class="fa fa-building text-primary me-2"></i>
        <span class="fw-semibold">Company:</span>
        <span><?= $company ?></span>
      </div>
      <div class="mb-3">
        <i class="fa fa-envelope text-danger me-2"></i>
        <span class="fw-semibold">Email:</span>
        <span><?= $email ?></span>
      </div>
    </div>

    <div class="mt-4 d-flex justify-content-center gap-2">
      <a href="dashboard.php" class="btn btn-outline-secondary">
        <i class="fa fa-arrow-left"></i> Back
      </a>
      <a href="index.php?logout=1" class="btn btn-danger">
        <i class="fa fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>
</div>

<style>
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes bounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
</style>
