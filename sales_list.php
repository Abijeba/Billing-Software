<?php
require_once 'config.php';
$title = "Sales Orders";
include 'header.php';

// --- Handle search/filter ---
$where = [];
$params = [];

if (!empty($_GET['so_number'])) {
    $where[] = "so.so_number LIKE ?";
    $params[] = "%" . $_GET['so_number'] . "%";
}
if (!empty($_GET['customer_id'])) {
    $where[] = "so.customer_id = ?";
    $params[] = $_GET['customer_id'];
}

// Fetch sales orders with item count
$sql = "
    SELECT 
        so.so_id, 
        so.so_number, 
        so.so_date, 
        so.delivery_date,
        c.customer_name,
        COALESCE(items.item_count, 0) AS item_count
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    LEFT JOIN (
        SELECT so_id, COUNT(*) AS item_count
        FROM sales_order_items
        GROUP BY so_id
    ) items ON so.so_id = items.so_id
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY so.so_number ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch customers for filter
$customers = $pdo->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Sales Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    /* 🔹 Animated Gradient Heading */
    h2 {
      text-align: center;
      font-size: 2.5rem;
      font-weight: 700;
      text-transform: uppercase;
      background: linear-gradient(90deg, #007bff, #00c3ff, #007bff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-size: 200%;
      animation: textShine 4s ease-in-out infinite, fadeSlideIn 1s ease forwards;
      margin-bottom: 30px;
    }

    @keyframes textShine {
      0% { background-position: 0% 50%; }
      100% { background-position: 100% 50%; }
    }

    @keyframes fadeSlideIn {
      from { opacity: 0; transform: translateY(25px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* 🔹 Card Container */
    .content-card {
      background: #fff;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    /* 🔹 Table Header Styling */
    thead.table-dark th {
        background-color: #003366 !important;
        color: white !important;
        text-align: center;
    }

    tbody td {
      text-align: center;
      vertical-align: middle;
    }

    /* 🔹 Gradient Buttons */
    .btn-new {
      background: linear-gradient(90deg, #007bff, #00c3ff);
      color: white !important;
      border: none;
    }

    .btn-new:hover {
      opacity: 0.9;
    }

    .btn-search {
      background: linear-gradient(90deg, #11998e, #38ef7d);
      color: white !important;
      border: none;
    }

    .btn-reset {
      background: linear-gradient(90deg, #757f9a, #d7dde8);
      color: white !important;
      border: none;
    }
  </style>
</head>

<body class="container mt-4">

  <h2>Sales Orders</h2>

  <div class="content-card">
    <!-- 🔹 New Sales Order + Search Row -->
    <div class="row g-3 align-items-center mb-3">
      <div class="col-md-3">
        <a href="insert_sales.php" class="btn btn-new w-100">➕ New Sales Order</a>
      </div>

      <div class="col-md-3">
        <input type="text" name="so_number" value="<?= htmlspecialchars($_GET['so_number'] ?? '') ?>" class="form-control" placeholder="Search SO Number">
      </div>

      <div class="col-md-3">
        <select name="customer_id" class="form-select">
          <option value="">-- Filter by Customer --</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['customer_id'] ?>" <?= (($_GET['customer_id'] ?? '')==$c['customer_id'])?'selected':'' ?>>
              <?= htmlspecialchars($c['customer_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3 text-end">
        <button type="submit" class="btn btn-search">🔍 Search</button>
        <a href="sales_list.php" class="btn btn-reset">Reset</a>
      </div>
    </div>
<div class="table-responsive">
    <!-- 🔹 Results Table -->
    <table class="table table-bordered table-striped mt-3">
      <thead class="table-dark">
        <tr>
          <th>SO Number</th>
          <th>SO Date</th>
          <th>Customer</th>
          <th>Delivery Date</th>
          <th>Items</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($orders): ?>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= htmlspecialchars($o['so_number']) ?></td>
              <td><?= date('d-m-Y', strtotime($o['so_date'])) ?></td>
              <td><?= htmlspecialchars($o['customer_name']) ?></td>
              <td><?= $o['delivery_date'] ? date('d-m-Y', strtotime($o['delivery_date'])) : '-' ?></td>
              <td><?= $o['item_count'] ?></td>
              <td>
                <button class="btn btn-outline-success btn-sm" onclick="showPrint(<?= $o['so_id'] ?>)">🖨 </button>
                <a href="edit_sales.php?id=<?= $o['so_id'] ?>" class="btn btn-outline-warning btn-sm">✏ </a>
                <a href="delete_sales.php?id=<?= $o['so_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this SO?')">🗑 </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center">No sales orders found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  </div>

  <!-- Hidden Print Area -->
  <div id="printArea" style="display:none; margin-top:30px;"></div>

  <script>
  function showPrint(so_id){
      fetch('sales_order_print.php?id=' + so_id)
      .then(response => response.text())
      .then(html => {
          let printArea = document.getElementById('printArea');
          printArea.innerHTML = html;
          printArea.style.display = 'block';
          printDiv('printArea');
      })
      .catch(err => console.error(err));
  }

  function printDiv(divId) {
      var printContents = document.getElementById(divId).innerHTML;
      var originalContents = document.body.innerHTML;

      document.body.innerHTML = printContents;
      window.print();
      document.body.innerHTML = originalContents;
      location.reload();
  }
  </script>

</body>
</html>
