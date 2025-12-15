<?php
require_once 'config.php';
$title = "Sales Returns";
include 'header.php';

// --- Handle search/filter ---
$where = [];
$params = [];

if (!empty($_GET['return_no'])) {
    $where[] = "sr.return_no LIKE ?";
    $params[] = "%" . $_GET['return_no'] . "%";
}
if (!empty($_GET['customer_id'])) {
    $where[] = "sr.customer_id = ?";
    $params[] = $_GET['customer_id'];
}
if (!empty($_GET['return_date'])) {
    $where[] = "DATE(sr.invoice_date) = ?";
    $params[] = $_GET['return_date'];
}

// --- Fetch sales returns ---
$sql = "
    SELECT sr.return_id, sr.return_no, sr.invoice_date AS bill_date, 
           c.customer_name, 
           COALESCE(items.item_count, 0) AS item_count, 
           COALESCE(items.total_amount, 0) AS total_amount
    FROM sales_returns sr
    LEFT JOIN customers c ON sr.customer_id = c.customer_id
    LEFT JOIN (
        SELECT sr_id, COUNT(*) AS item_count, SUM(line_total) AS total_amount 
        FROM sales_return_items 
        GROUP BY sr_id
    ) items ON sr.return_id = items.sr_id
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY sr.return_no DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch customers ---
$customers = $pdo->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= $title ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(135deg, #eef2ff, #ffffff);
  min-height: 100vh;
}

/* 🌟 Heading */
h2.page-title {
  color: #140d77;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1px;
  text-align: center;
  margin-bottom: 20px;
}

/* 💳 Card */
.card-custom {
  border: none;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  padding: 25px;
}

/* 🧭 Table */
.table thead th {
  background: linear-gradient(90deg, #140d77, #3f51b5);
  color: #fff;
  text-align: center;
  position: sticky;
  top: 0;
  z-index: 10;
}
.table tbody td {
  text-align: center;
  vertical-align: middle;
}

/* 🧾 Scrollable table body (3 rows visible) */
.table-scroll {
  max-height: 250px; /* ~3 rows visible */
  overflow-y: auto;
  border-radius: 8px;
  scrollbar-width: thin;
  scrollbar-color: #140d77 #f1f1f1;
}
.table-scroll::-webkit-scrollbar {
  width: 8px;
}
.table-scroll::-webkit-scrollbar-thumb {
  background: #140d77;
  border-radius: 10px;
}

/* 🔘 Buttons */
.btn-blue {
  background: linear-gradient(90deg, #140d77, #3f51b5);
  border: none;
  color: white;
  padding: 6px 12px;
  font-size: 14px;
  border-radius: 6px;
  transition: 0.3s;
}
.btn-blue:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 10px rgba(20,13,119,0.4);
}

.btn-green {
  background: linear-gradient(90deg, #00c851, #007e33);
  border: none;
  color: white;
  padding: 6px 12px;
  font-size: 14px;
  border-radius: 6px;
  transition: 0.3s;
}
.btn-green:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 10px rgba(0,126,51,0.4);
}

.btn-outline-blue {
  border: 2px solid #140d77;
  color: #140d77;
  font-size: 14px;
  padding: 6px 12px;
  border-radius: 6px;
  transition: 0.3s;
}
.btn-outline-blue:hover {
  background: linear-gradient(90deg, #140d77, #3f51b5);
  color: #fff;
}

/* 📱 Responsive */
@media (max-width: 768px) {
  .search-row {
    flex-direction: column;
    gap: 10px;
  }
}
</style>
</head>
<body>

<div class="container py-5">
  <h2 class="page-title">Sales Returns</h2>

  <div class="card card-custom">
    <form method="get" class="row align-items-end g-3 search-row mb-4">
      <div class="col-md-2">
        <input type="text" name="return_no" value="<?= htmlspecialchars($_GET['return_no'] ?? '') ?>" class="form-control" placeholder="Return No">
      </div>
      <div class="col-md-3">
        <select name="customer_id" class="form-select">
          <option value="">All Customers</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['customer_id'] ?>" <?= ($_GET['customer_id'] ?? '') == $c['customer_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['customer_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="return_date" value="<?= htmlspecialchars($_GET['return_date'] ?? '') ?>" class="form-control">
      </div>
      <div class="col-md-3 d-flex justify-content-start gap-2">
        <button type="submit" class="btn btn-blue"><i class="fa fa-search me-1"></i>Search</button>
        <a href="sales_return_list.php" class="btn btn-blue"><i class="fa fa-refresh me-1"></i>Reset</a>
      </div>
      <div class="col-md-2 text-end">
        <a href="sales_return.php" class="btn btn-green w-100"><i class="fa fa-plus me-1"></i>New</a>
      </div>
    </form>

    <div class="table-responsive table-scroll">
      <table class="table table-bordered table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Return No</th>
            <th>Bill Date</th>
            <th>Customer</th>
            <th>Items</th>
            <th>Total Amount</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($returns): ?>
            <?php foreach ($returns as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['return_no']) ?></td>
                <td><?= date('d-m-Y', strtotime($r['bill_date'])) ?></td>
                <td><?= htmlspecialchars($r['customer_name']) ?></td>
                <td><?= $r['item_count'] ?></td>
                <td><?= number_format($r['total_amount'], 2) ?></td>
                <td>
                  <a href="view_sales_return.php?id=<?= $r['return_id'] ?>" class="btn btn-outline-blue btn-sm"><i class="fa fa-eye"></i></a>
                  <a href="delete_sales_return.php?id=<?= $r['return_id'] ?>" onclick="return confirm('Delete this sales return?')" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center">No sales returns found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</body>
</html>
