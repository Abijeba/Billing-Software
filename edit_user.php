<?php
require_once 'config.php';
$title = "Edit User";
include 'header.php';

// Get user ID from URL
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: admin_list.php");
    exit;
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM adminlist WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: admin_list.php");
    exit;
}

// Initialize variables
$username = $user['username'];
$email = $user['email'];
$company = $user['company'];
$role = $user['role'];
$status = $user['status'];
$success = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $company = trim($_POST['company']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role'] ?? 'User';
    $status = $_POST['status'] ?? 'Active';

    if (empty($username) || empty($email)) {
        $error = "Please fill in required fields.";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Determine if password should be updated
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE adminlist SET username=:username, email=:email, company=:company, password=:password, role=:role, status=:status WHERE id=:id";
        } else {
            $sql = "UPDATE adminlist SET username=:username, email=:email, company=:company, role=:role, status=:status WHERE id=:id";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':company', $company);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);

        // Only bind password if updating
        if (!empty($password)) {
            $stmt->bindParam(':password', $hashed_password);
        }

        if ($stmt->execute()) {
            $success = "User updated successfully!";
        } else {
            $error = "Failed to update user.";
        }
    }
}

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="container-fluid py-4 animate__animated animate__fadeInUp">
  <div class="card card-lightblue p-4 rounded-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <h3 class="fw-semibold text-primary animate__animated animate__fadeInLeft d-flex align-items-center gap-2 mb-0">
        <i class="bi bi-pencil-square"></i> Edit User
      </h3>
      <a href="admin_list.php" class="btn btn-primary btn-md shadow-sm px-4 py-2">
        <i class="bi bi-people-fill me-1"></i> Back to Users
      </a>
    </div>

    <!-- Success/Error Alerts -->
    <?php if ($success): ?>
      <div id="successAlert" class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div id="errorAlert" class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form class="row g-3" method="POST" action="">
      <div class="col-md-4">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Company Name (Optional)</label>
        <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($company) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
      </div>

      <div class="col-md-4">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Leave blank to keep current password">
      </div>

      <div class="col-md-2">
        <label class="form-label">User Role</label>
        <select name="role" class="form-select">
          <option value="User" <?= ($role==='User')?'selected':'' ?>>User</option>
          <option value="Admin" <?= ($role==='Admin')?'selected':'' ?>>Admin</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Active</label>
        <select name="status" class="form-select">
          <option value="Active" <?= ($status==='Active')?'selected':'' ?>>Active</option>
          <option value="Inactive" <?= ($status==='Inactive')?'selected':'' ?>>Inactive</option>
        </select>
      </div>

      <div class="col-12 text-end">
        <button type="submit" class="btn btn-primary btn-md px-4 py-2">
          <i class="bi bi-check-circle me-1"></i> Update
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Alert auto-hide script -->
<script>
window.addEventListener('DOMContentLoaded', () => {
    const success = document.getElementById('successAlert');
    if(success){
        setTimeout(() => { success.style.display = 'none'; }, 3000);
    }
    const error = document.getElementById('errorAlert');
    if(error){
        setTimeout(() => { error.style.display = 'none'; }, 3000);
    }
});
</script>

<style>
.card-lightblue {
  /* Light blue gradient */
  background: linear-gradient(135deg, #cce0ff, #e6f0ff);
  border-radius: 20px;
  padding: 20px;

  /* Shadow effect */
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2), 
              0 6px 10px rgba(0, 0, 0, 0.1);

  /* Smooth transition */
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card-lightblue:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25), 
              0 10px 20px rgba(0, 0, 0, 0.15);
}

.fw-semibold.text-primary i { color: #0d6efd; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{transform:scale(1);opacity:0.8;}50%{transform:scale(1.2);opacity:1;} }
.btn-primary.btn-md { border-radius: 8px; padding: 8px 20px; font-weight: 500; font-size: 1rem; background: linear-gradient(135deg,#0d6efd,#0a58ca); border:none; color:#fff; transition: all 0.3s ease; }
.btn-primary.btn-md:hover { background: linear-gradient(135deg,#0a58ca,#084298); transform: scale(1.05); }
.form-control, .form-select { border-radius: 10px; transition: all 0.2s; }
.form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25); }
.animate__animated { animation-duration: 0.6s; animation-timing-function: ease-in-out; }
</style>
