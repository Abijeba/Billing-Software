
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}
$name     = htmlspecialchars($_SESSION['user'] ?? 'User');
$role     = htmlspecialchars($_SESSION['role'] ?? 'User');
$company  = htmlspecialchars($_SESSION['company'] ?? 'N/A');
$gender   = strtolower($_SESSION['gender'] ?? 'female');

$profilePic = ($gender === 'female' || $gender === 'girl' || $gender === 'f') ?
    "https://cdn-icons-png.flaticon.com/512/4140/4140051.png" :
    "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
$title = isset($title) ? $title : "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
  body {
  background-color: #E9EEFF;
}

      font-family: "Poppins", sans-serif;
      margin: 0;
      padding: 0;
    }

    /* Sidebar */
    .app-sidebar {
      width: 260px;
      height: 100vh;
      background: #140d77;
      color: white;
      position: fixed;
      top: 0;
      left: 0;
      overflow-y: auto;
      transition: all 0.4s ease;
      box-shadow: 0 0 20px rgba(13, 71, 161, 0.7);
      z-index: 1000;
    }

    .app-sidebar .logo {
      font-size: 22px;
      font-weight: bold;
      text-align: center;
      margin: 20px 0;
      color: white;
      text-shadow: 0px 0px 6px rgba(255,255,255,0.7);
    }

    .app-sidebar ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .app-sidebar .nav-link {
      color: #e3f2fd;
      padding: 12px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 15px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.3s ease;
      border-radius: 8px;
      margin: 4px 10px;
    }

    .app-sidebar .nav-link:hover,
    .app-sidebar .nav-link.active {
      background: rgba(255, 255, 255, 0.2);
      color: #fff;
      text-shadow: 0px 0px 10px rgba(255,255,255,1);
    }

    .submenu {
      padding-left: 20px;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.4s ease;
    }

    .submenu.show {
      max-height: 500px;
        transform: translateX(10px); /* 🔹 Moves slightly to the right */
  opacity: 1;
    }

    .rotate-icon {
      margin-left: auto;
      transition: transform 0.3s ease;
    }

    [aria-expanded="true"] .rotate-icon {
      transform: rotate(90deg);
    }

    .main-content {
      margin-left: 260px;
      padding: 25px;
      transition: all 0.4s ease;
    }

    /* Scrollbar */
    .app-sidebar::-webkit-scrollbar { width: 6px; }
    .app-sidebar::-webkit-scrollbar-thumb {
      background-color: rgba(255,255,255,0.3);
      border-radius: 3px;
    }

    /* Responsive */
    @media (max-width: 992px) {
      .app-sidebar {
        left: -260px;
      }
      .app-sidebar.show {
        left: 0;
      }
      .main-content {
        margin-left: 0;
      }
    }

    .toggle-btn {
      background: transparent;
      border: none;
      color: #0d47a1;
      font-size: 22px;
      position: fixed;
      top: 15px;
      left: 15px;
      z-index: 1100;
      cursor: pointer;
    }
    /* ==============================
   🖨️ PRINT TABLE STYLING
   ============================== */
@media print {
  body {
    background: white !important;
    color: black !important;
     /* keep table header color */
    font-size: 13px;
  }

  .app-sidebar,
  .mobile-header,
  .btn,
  .nav,
  .alert,
  .offcanvas,
  .no-print {
    display: none !important;
  }

  .main-content {
    margin: 0 !important;
    padding: 0 !important;
    width: 100%;
  }

  table {
    border-collapse: collapse !important;
    width: 100% !important;
    font-size: 13px;
  }

  table th,
  table td {
    border: 1px solid #140d77 !important;
    padding: 6px !important;
    text-align: center;
  }

  table thead th {
    background: White !important;
    color: Black !important;
  }

  /* Optional: Page break between large tables */
  .page-break {
    page-break-before: always;
  }
  .topbar h5 {
  font-size: 1.1rem;
}
}
@media (min-width: 992px) {
    /* --- DEFAULT EXPANDED STATE (250px) --- */
    #sidebar {
        width: 250px; 
        transition: width 0.3s ease;
        overflow-x: hidden;
    }
    #main-content {
        margin-left: 250px;
        transition: margin-left 0.3s ease;
    }

    /* 1. Header (.logo) Default State */
    #sidebar .logo {
        display: flex;
        align-items: center;
        white-space: nowrap;
        padding: 20px 15px;
        font-size: 1.25rem;
        transition: all 0.3s ease;
    }

    /* 2. Menu Link/Item Default State (Use flex on the link) */
    #sidebar ul.nav > li > a {
        display: flex;
        align-items: center;
        white-space: nowrap;
        transition: padding 0.3s ease;
    }

    /* 3. Menu Text (The element we control for the collapse) */
    #sidebar .menu-text {
        opacity: 1;
        width: 170px; /* Space the text occupies */
        overflow: hidden;
        transition: opacity 0.3s ease, width 0.3s ease;
    }


    /* --- COLLAPSED STATE (80px) --- */
    #sidebar.collapsed {
        width: 80px; 
    }
    #main-content.sidebar-collapsed { 
        margin-left: 80px;
    }

    /* 1. Header (.logo) Collapsed Fix */
    #sidebar.collapsed .logo {
        justify-content: center;
        font-size: 0; /* Hide all text content */
        opacity: 0; /* Hide all text content smoothly */
        padding-left: 0;
        padding-right: 0;
    }
    #sidebar.collapsed .logo i {
        font-size: 1.25rem; /* Restore icon size */
        opacity: 1; /* Restore icon visibility */
        margin-right: 0 !important; /* Remove bootstrap spacing */
    }

    /* 2. Menu Link/Item Collapsed Fix */
    #sidebar.collapsed ul.nav > li > a {
        justify-content: center; /* Centers the remaining icon */
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    /* 3. Menu Text Collapsed Fix */
    #sidebar.collapsed .menu-text {
        width: 0; /* Shrink space smoothly */
        opacity: 0; /* Fade out smoothly */
        pointer-events: none;
    }
}

  </style>
</head>

<body>
<!-- Toggle Button for Mobile -->
<button class="toggle-btn" id="menuToggle"><i class="fa fa-bars"></i></button>

<div class="app-sidebar" id="sidebar">
  <div class="logo"><i class="fa-solid fa-layer-group me-2"></i><span class="menu-text"> Menu</span></div>
  <ul class="nav flex-column">

    <li><a href="dashboard.php" class="nav-link"><i class="fa fa-home"></i><span class="menu-text"> Dashboard</span></a></li>

    <!-- Master -->
    <li>
      <a href="#masterMenu" class="nav-link d-flex align-items-center" data-bs-toggle="collapse">
        <i class="fa fa-database"></i><span class="menu-text"> Master </span><span><i class="fa fa-chevron-right rotate-icon"></i></span> 
      </a>
      <ul class="collapse submenu" id="masterMenu">
          <li><a href="category.php" class="nav-link"><i class="fa fa-tags me-2"></i><span class="menu-text"> Category</span></a></li>
    <li><a href="products.php" class="nav-link"><i class="fa fa-box-open me-2"></i><span class="menu-text"> Products</span></a></li>
    <li><a href="customer.php" class="nav-link"><i class="fa fa-user-friends me-2"></i><span class="menu-text"> Customer</span></a></li>
    <li><a href="insert_supplier.php" class="nav-link"><i class="fa fa-truck me-2"></i><span class="menu-text"> Supplier</span></a></li>
    <li><a href="ledger.php" class="nav-link"><i class="fa fa-book me-2"></i><span class="menu-text"> Ledger</span></a></li>
      </ul>
    </li>

    <!-- Transaction -->
    <li>
      <a href="#transactionMenu" class="nav-link d-flex align-items-center" data-bs-toggle="collapse">
        <i class="fa fa-random"></i><span class="menu-text"> Transaction </span><span> <i class="fa fa-chevron-right rotate-icon"></i></span> 
      </a>
      <ul class="collapse submenu" id="transactionMenu">

        <!-- Purchase -->
        <li>
          <a href="#purchaseMenu" class="nav-link d-flex align-items-center" data-bs-toggle="collapse">
            <i class="fa fa-shopping-cart"></i><span class="menu-text"> Purchase </span><span><i class="fa fa-chevron-right rotate-icon"></i></span>
          </a>
          <ul class="collapse submenu" id="purchaseMenu">
            <li><a href="purchase_list.php" class="nav-link"><i class="fa fa-shopping-cart me-2"></i><span class="menu-text"> Purchase Order</span></a></li>
<li><a href="purchase_invoice_list.php" class="nav-link"><i class="fa fa-file-invoice me-2"></i><span class="menu-text"> Purchase Invoice</span></a></li>
<li><a href="purchase_return_list.php" class="nav-link"><i class="fa fa-undo me-2"></i><span class="menu-text"> Purchase Return</span></a></li>
          </ul>
        </li>

        <!-- Sales -->
        <li>
          <a href="#salesMenu" class="nav-link d-flex align-items-center" data-bs-toggle="collapse">
            <i class="fa fa-cash-register"></i><span class="menu-text"> Sales </span><span><i class="fa fa-chevron-right rotate-icon"></i></span>
          </a>
          <ul class="collapse submenu" id="salesMenu">
           <li><a href="sales_list.php" class="nav-link"><i class="fa fa-shopping-bag me-2"></i><span class="menu-text"> Sales Order</span></a></li>
<li><a href="sales_invoice_list.php" class="nav-link"><i class="fa fa-file-invoice me-2"></i><span class="menu-text"> Sales Invoice</span></a></li>
<li><a href="sales_return_list.php" class="nav-link"><i class="fa fa-undo me-2"></i><span class="menu-text"> Sales Return</span></a></li>
          </ul>
        </li>

        <li><a href="payment_out_list.php" class="nav-link"><i class="fa fa-wallet"></i><span class="menu-text"> Payment Out</span></a></li>
        <li><a href="payment_in_list.php" class="nav-link"><i class="fa fa-money-bill"></i><span class="menu-text"> Payment In</span></a></li>
      </ul>
    </li>

    <!-- Reports -->
    <li>
      <a href="#reportsMenu" class="nav-link d-flex align-items-center" data-bs-toggle="collapse">
        <i class="fa fa-chart-line"></i><span class="menu-text"> Reports </span><span><i class="fa fa-chevron-right rotate-icon"></i></span>
      </a>
      <ul class="collapse submenu" id="reportsMenu">
      <li><a href="stock_summary.php" class="nav-link"><i class="fa fa-warehouse me-2"></i><span class="menu-text"> Stock Summary</span></a></li>
<li><a href="sales_summary.php" class="nav-link"><i class="fa fa-chart-line me-2"></i><span class="menu-text"> Sales Summary</span></a></li>
      </ul>
    </li>

    <li><a href="profile.php" class="nav-link"><i class="fa fa-user"></i><span class="menu-text"> Profile</span></a></li>
    
      <!-- Only show Users menu for Admin -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
  <li><a href="admin_list.php" class="nav-link"><i class="fa fa-users"></i><span class="menu-text"> Users</span></a></li>
<?php endif; ?>

    <li><a href="logout.php" class="nav-link "><i class="fa fa-sign-out-alt"></i><span class="menu-text"> Logout</span></a></li>
  </ul>
</div>

<div class="main-content" id="main-content">
  <!-- Topbar / Header -->
<div class="topbar shadow-sm bg-white d-flex justify-content-between align-items-center px-4 py-3 mb-4" style="border-bottom: 1px solid #eee;">
    <!-- Left: Welcome -->
    <div>
        <h5 class="mb-0 fw-bold text-primary">Welcome, <?= $name ?></h5>
        <small class="text-muted">Here’s a summary of today’s operational status</small>
    </div>

    <!-- Right: Profile -->
    <div class="d-flex align-items-center">
        <div class="text-end me-3">
            <strong><?= $name ?></strong><br>
            <small class="text-secondary">Role: <?= $role ?></small><br>
            <small class="text-secondary">Company: <?= $company ?></small>
        </div>
        <img src="<?= $profilePic ?>" alt="Profile" class="rounded-circle border border-2 border-primary" width="50" height="50">
    </div>
</div>
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("main-content");
  const toggleBtn = document.getElementById("menuToggle");

  // ✅ Mobile sidebar toggle
  if (toggleBtn) {
    toggleBtn.addEventListener("click", function () {
      sidebar.classList.toggle("show");
      sidebar.classList.toggle("collapsed");
      mainContent.classList.toggle("sidebar-collapsed");
    });
  }

  // ✅ Sidebar hover expand/collapse (Desktop)
  sidebar.addEventListener("mouseenter", () => {
    sidebar.classList.remove("collapsed");
    mainContent.classList.remove("sidebar-collapsed");
  });
  sidebar.addEventListener("mouseleave", () => {
    sidebar.classList.add("collapsed");
    mainContent.classList.add("sidebar-collapsed");
  });

  // ✅ Keep submenu open if active link is inside
  document.querySelectorAll(".submenu a").forEach(link => {
    if (window.location.href.includes(link.getAttribute("href"))) {
      const parentCollapse = link.closest(".collapse");
      if (parentCollapse) new bootstrap.Collapse(parentCollapse, { toggle: true });
      link.classList.add("active");
    }
  });

  // ✅ Only one MAIN MENU (Master / Transaction / Reports) open at a time
  const mainMenus = document.querySelectorAll('#sidebar > ul.nav > li > .collapse');
  mainMenus.forEach(menu => {
    menu.addEventListener('show.bs.collapse', function () {
      mainMenus.forEach(otherMenu => {
        if (otherMenu !== menu) {
          new bootstrap.Collapse(otherMenu, { toggle: false }).hide();
        }
      });
    });
  });

  // ✅ Only one SUBMENU (Purchase / Sales) open under Transaction
  const transactionSubmenus = document.querySelectorAll('#transactionMenu .collapse');
  transactionSubmenus.forEach(sub => {
    sub.addEventListener('show.bs.collapse', function () {
      transactionSubmenus.forEach(otherSub => {
        if (otherSub !== sub) {
          new bootstrap.Collapse(otherSub, { toggle: false }).hide();
        }
      });
    });
  });
});
</script>

</body>
</html>
