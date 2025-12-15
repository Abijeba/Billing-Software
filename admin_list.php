<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}
require_once 'config.php';
$title = "Admin List";
include 'header.php'; 

if (isset($_GET['deleted'])):
    if ($_GET['deleted'] == 1): ?>
        <div id="deleteAlert" class="alert alert-success text-center">User deleted successfully!</div>
    <?php else: ?>
        <div id="deleteAlert" class="alert alert-danger text-center">Failed to delete user.</div>
    <?php endif;
endif;

$search = $_GET['search'] ?? '';

$sql = "SELECT id, username, email, role, status FROM adminlist";
$params = [];

if ($search !== '') {
    $sql .= " WHERE username LIKE :search1 OR email LIKE :search2 OR role LIKE :search3";
    $params = [
        ':search1' => "%$search%",
        ':search2' => "%$search%",
        ':search3' => "%$search%"
    ];
}

$sql .= " ORDER BY role='Admin' DESC, id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="container-fluid py-4 animate__animated animate__fadeInUp">
  <div class="card border-0 rounded-4 card-lightblue">
    <div class="card-body p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <h3 class="fw-semibold text-primary animate__animated animate__fadeInLeft d-flex align-items-center gap-2 mb-0">
        <i class="bi bi-people-fill"></i> User Management
      </h3>

      <form class="d-flex flex-wrap align-items-center gap-2  w-md-auto" method="GET" action="">
        <div class="input-group shadow-sm flex-grow-1 search-small" style="min-width: 220px;">
          <input type="text" name="search" class="form-control rounded-start-pill py-1 px-2" placeholder="Search user..." value="<?= htmlspecialchars($search) ?>">
          <button class="btn btn-primary rounded-end-pill px-2 py-1" type="submit">
            <i class="bi bi-search"></i>
          </button>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
          <button type="button" id="clearSearch" class="btn btn-secondary btn-sm shadow-sm px-3 py-1">Reset</button>
          <a href="add_user.php" class="btn btn-primary btn-sm shadow-sm px-3 py-1">
            <i class="bi bi-person-plus-fill me-1"></i> Add User
          </a>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="table-responsive animate__animated animate__fadeInUp">
      <table class="table table-bordered align-middle text-center mb-0">
        <thead class="table-dark">
          <tr>
            <th>User ID</th>
            <th>User Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-light">
          <?php if (!empty($users)): ?>
            <?php foreach ($users as $index => $user): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td class="text-break"><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td>
                  <?php if ($user['status'] === 'Active'): ?>
                    <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">Active</span>
                  <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger fw-semibold px-3 py-2 rounded-pill">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 me-2 mb-1">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?');"
                     class="btn btn-outline-danger btn-sm rounded-pill px-3 mb-1">
                    <i class="bi bi-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-muted py-3">No users found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    </div>
  </div>
</div>

<script>
  window.addEventListener('DOMContentLoaded', () => {
    const alert = document.getElementById('deleteAlert');
    if(alert){
      setTimeout(() => alert.style.display = 'none', 3000);
    }

    const clearBtn = document.getElementById('clearSearch');
    if(clearBtn){
      clearBtn.addEventListener('click', () => window.location.href = 'admin_list.php');
    }
  });
</script>

<style>
.card-lightblue {
  background: linear-gradient(135deg, #cce0ff, #e6f0ff);
  border-radius: 20px;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2),
              0 6px 10px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card-lightblue:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25),
              0 10px 20px rgba(0, 0, 0, 0.15);
}

table {
  border-radius: 12px;
  overflow: hidden;
}
tbody tr:hover {
  background-color: #f8f9fa;
  transition: 0.3s;
}
thead.table-dark th {
  background-color: #003366 !important;
  color: #fff !important;
  text-align: center;
}

.btn-outline-primary:hover,
.btn-outline-danger:hover,
.btn-primary:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 10px rgba(13,110,253,0.3);
  transition: all 0.3s ease;
}

/* 🔹 Smaller Buttons & Search Bar */
.btn-sm, .search-small input, .search-small button {
  font-size: 0.85rem !important;
  padding: 4px 8px !important;
}
.search-small .form-control {
  height: 32px !important;
  width:fit-content;
}
.search-small .btn {
  height: 32px !important;
}

/* Focus effect */
input[type="text"]:focus {
  box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
  border-color: #0d6efd;
  transition: all 0.2s ease;
}

.badge {
  font-size: 0.85rem;
  border-radius: 12px;
  letter-spacing: 0.3px;
}

/* 🔹 Mobile Responsive Adjustments */
@media (max-width: 768px) {
  .card-body {
    padding: 1rem !important;
  }
  h3 {
    font-size: 1.25rem;
  }
  table {
    font-size: 0.9rem;
  }
  .table-responsive {
    overflow-x: auto;
  }
  .btn {
    font-size: 0.8rem !important;
    padding: 4px 8px !important;
  }
  .input-group {
    width: 100% !important;
  }
}
</style>
