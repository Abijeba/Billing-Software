<?php
require_once 'config.php';
include 'header.php';

// ===== Filter type and manual dates =====
$filterType = $_GET['filter'] ?? 'day';
$manualStart = $_GET['start_date'] ?? '';
$manualEnd   = $_GET['end_date'] ?? '';

// ===== Determine date range =====
if (!empty($manualStart) && !empty($manualEnd)) {
    $start_date = $manualStart;
    $end_date   = $manualEnd;
} else {
    // Default ranges
    if ($filterType === 'day') {
        $start_date = date('Y-m-d');
        $end_date   = date('Y-m-d');
    } elseif ($filterType === 'week') {
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date   = date('Y-m-d', strtotime('sunday this week'));
    } elseif ($filterType === 'month') {
        $start_date = date('Y-m-01');
        $end_date   = date('Y-m-t');
    }
}

// ===== Fetch sales summary =====
$query = "
SELECT 
    sii.product_name,
    SUM(sii.quantity) AS total_sales
FROM sales_invoice_items sii
JOIN sales_invoices si ON sii.si_id = si.si_id
WHERE si.si_date BETWEEN :start_date AND :end_date
GROUP BY sii.product_name
ORDER BY total_sales DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([
    ':start_date' => $start_date,
    ':end_date'   => $end_date
]);
$salesSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sales Summary</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <h2 class="mb-4">💰 Sales Summary</h2>

  <!-- Filter -->
  <form method="get" class="row g-3 mb-4">
    <div class="col-md-3">
      <label>Filter</label>
      <select name="filter" class="form-select" onchange="this.form.submit()">
        <option value="day" <?= $filterType==='day'?'selected':'' ?>>Day</option>
        <option value="week" <?= $filterType==='week'?'selected':'' ?>>Week</option>
        <option value="month" <?= $filterType==='month'?'selected':'' ?>>Month</option>
      </select>
    </div>
    <div class="col-md-3">
      <label>Start Date</label>
      <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
    </div>
    <div class="col-md-3">
      <label>End Date</label>
      <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
  </form>

  <!-- Sales Table -->
  <div class="card">
    <div class="card-body">
      <table class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th>Product Name</th>
            <th>Total Sales</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($salesSummary)): ?>
            <?php foreach ($salesSummary as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= htmlspecialchars($row['total_sales']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="2" class="text-center">No data found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
