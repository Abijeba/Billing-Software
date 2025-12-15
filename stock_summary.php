<?php
require_once 'config.php';
include 'header.php';

// Current date
$today = date('d-m-Y');

// 🔹 Fetch Stock Summary including purchase returns and sales returns
$query = "
SELECT
    pr.product_name,
    IFNULL(pur.total_purchased,0) AS total_purchased,
    IFNULL(prr.total_pr,0) AS total_purchase_return,
    IFNULL(sal.total_sold,0) AS total_sold,
    IFNULL(srr.total_sr,0) AS total_sales_return,
    (IFNULL(pur.total_purchased,0) - IFNULL(prr.total_pr,0) - IFNULL(sal.total_sold,0) + IFNULL(srr.total_sr,0)) AS stock_balance
FROM
    (SELECT DISTINCT product_name FROM purchase_invoice_items
     UNION
     SELECT DISTINCT product_name FROM sales_invoice_items) pr

LEFT JOIN
    (SELECT product_name, SUM(quantity) AS total_purchased FROM purchase_invoice_items GROUP BY product_name) pur
ON pr.product_name = pur.product_name

LEFT JOIN
    (SELECT p.product_name, SUM(pri.quantity) AS total_pr
     FROM purchase_return_items pri
     JOIN products p ON pri.product_id = p.product_id
     GROUP BY p.product_name) prr
ON pr.product_name = prr.product_name

LEFT JOIN
    (SELECT product_name, SUM(quantity) AS total_sold FROM sales_invoice_items GROUP BY product_name) sal
ON pr.product_name = sal.product_name

LEFT JOIN
    (SELECT p.product_name, SUM(sri.quantity) AS total_sr
     FROM sales_return_items sri
     JOIN products p ON sri.product_id = p.product_id
     GROUP BY p.product_name) srr
ON pr.product_name = srr.product_name

ORDER BY pr.product_name;
";
$stmt = $pdo->query($query);
$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Stock Summary</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
  <h2 class="mb-1">📦 Stock Summary</h2>
  <p class="text-muted mb-3">As on Date: <?= $today ?></p> <!-- Added current date -->

  <div class="card">
    <div class="card-body">
      <table class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th>Product Name</th>
            <th>Total Purchased</th>
            <th>Purchase Return</th>
            <th>Total Sold</th>
            <th>Sales Return</th>
            <th>Stock Balance</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($stocks)): ?>
            <?php foreach ($stocks as $stock): ?>
              <tr>
                <td><?= htmlspecialchars($stock['product_name']) ?></td>
                <td><?= htmlspecialchars($stock['total_purchased']) ?></td>
                <td><?= htmlspecialchars($stock['total_purchase_return']) ?></td>
                <td><?= htmlspecialchars($stock['total_sold']) ?></td>
                <td><?= htmlspecialchars($stock['total_sales_return']) ?></td>
                <td class="<?= ($stock['stock_balance'] <= 0) ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
                  <?= htmlspecialchars($stock['stock_balance']) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center">No data found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function loadStock() {
    fetch('stock_table.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('stockTable').innerHTML = html;
        });
}

// Initial load
loadStock();

// Refresh every 10 seconds
setInterval(loadStock, 10000);
</script>

</body>
</html>
