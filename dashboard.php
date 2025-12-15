<?php
session_start();

// Redirect to login if user not logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Handle logout directly from dashboard if needed
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<?php 
require_once 'config.php'; 
include 'header.php'; 

// Fetch counts
$totalPurchaseInvoice = $pdo->query("SELECT COUNT(*) FROM purchase_invoices")->fetchColumn();
$totalSalesInvoice    = $pdo->query("SELECT COUNT(*) FROM sales_invoices")->fetchColumn();
$totalPurchaseOrders  = $pdo->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn();
$totalSalesOrders     = $pdo->query("SELECT COUNT(*) FROM sales_orders")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body {
      background: #f4f9ff;
      font-family: "Segoe UI", Tahoma, sans-serif;
    }
    h2 {
      font-weight: 700;
      color: #0d47a1;
    }
    .stat-card {
      border-radius: 15px;
      text-align: center;
      padding: 20px;
      background: #ffffff;
      border: 1px solid #e3f2fd;
      box-shadow: 0 4px 10px rgba(13,71,161,0.08);
      transition: all 0.3s ease;
      cursor: pointer;
      text-decoration: none;
      display: block;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(13,71,161,0.2);
    }
    .stat-title {
      font-size: 1rem;
      font-weight: 600;
      color: #140d77;
      margin-bottom: 10px;
    }
    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: #140d77;
    }
    @media (max-width: 768px) {
      .stat-card {
        padding: 25px;
      }
      .stat-title {
        font-size: 1.1rem;
      }
      .stat-value {
        font-size: 2rem;
      }
    }
    canvas {
      max-height: 300px; /* same height for both charts */
    }
  </style>
</head>
<body>

<div class="container mt-4">
  <h2 class="mb-4">Dashboard</h2>

  <!-- Stat Cards -->
  <div class="row g-3">
    <div class="col-12 col-sm-6 col-lg-3">
      <a href="purchase_invoice_list.php" class="stat-card">
        <div class="stat-title">Purchase Invoices</div>
        <div class="stat-value"><?= $totalPurchaseInvoice ?></div>
      </a>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <a href="sales_invoice_list.php" class="stat-card">
        <div class="stat-title">Sales Invoices</div>
        <div class="stat-value"><?= $totalSalesInvoice ?></div>
      </a>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <a href="purchase_list.php" class="stat-card">
        <div class="stat-title">Purchase Orders</div>
        <div class="stat-value"><?= $totalPurchaseOrders ?></div>
      </a>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <a href="sales_list.php" class="stat-card">
        <div class="stat-title">Sales Orders</div>
        <div class="stat-value"><?= $totalSalesOrders ?></div>
      </a>
    </div>
  </div>

  <!-- Charts -->
  <div class="row mt-4 g-4">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Purchase & Sales Overview</h5>
          <canvas id="overviewChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Monthly Purchase & Sales Trends</h5>
          <canvas id="trendChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Charts -->
<script>
const overviewCtx = document.getElementById('overviewChart').getContext('2d');
new Chart(overviewCtx, {
    type: 'doughnut',
    data: {
        labels: ['Purchase Orders', 'Sales Orders','Purchase Invoices',  'Sales Invoices'],
        datasets: [{
            data: [ <?= $totalPurchaseOrders ?>,<?= $totalSalesOrders ?>, <?= $totalPurchaseInvoice ?>,  <?= $totalSalesInvoice ?> ],
            backgroundColor: ['#1768c5ff','#06e286ff','#7abffcff','#90f9b3ff'],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
        datasets: [
            {
                label: "Purchase Orders",
                data: [12, 19, 8, 14, 10, 20, 25, 18, 22, 30, 26, 28],
                borderColor: "#1565c0",
                backgroundColor: "rgba(21,101,192,0.2)",
                tension: 0.3,
                fill: true
            },
            {
                label: "Sales Orders",
                data: [15, 25, 14, 20, 16, 26, 30, 24, 28, 35, 32, 40],
                borderColor: "#42a5f5",
                backgroundColor: "rgba(66,165,245,0.2)",
                tension: 0.3,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

</body>
</html>
