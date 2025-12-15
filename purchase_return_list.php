<?php
require_once 'config.php';
$title = "Purchase Returns";
include 'header.php';

// --- Handle search/filter ---
$where = [];
$params = [];

if (!empty($_GET['return_no'])) {
    $where[] = "pr.return_no LIKE ?";
    $params[] = "%" . $_GET['return_no'] . "%";
}
if (!empty($_GET['supplier_id'])) {
    $where[] = "pr.supplier_id = ?";
    $params[] = $_GET['supplier_id'];
}

// Fetch purchase returns with item count
$sql = "
    SELECT 
        pr.return_id, 
        pr.return_no, 
        pr.bill_date, 
        s.supplier_name,
        COALESCE(items.item_count, 0) AS item_count
    FROM purchase_returns pr
    JOIN suppliers s ON pr.supplier_id = s.supplier_id
    LEFT JOIN (
        SELECT pr_id, COUNT(*) AS item_count
        FROM purchase_return_items
        GROUP BY pr_id
    ) items ON pr.return_id = items.pr_id
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY pr.return_no DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch suppliers for filter
$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Purchase Returns</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* ✨ Animated Gradient Heading */
    @keyframes textShine {
      0% { background-position: 0% 50%; }
      100% { background-position: 100% 50%; }
    }
    @keyframes fadeSlideIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .product-heading {
        font-size: 2.5rem;
        font-weight: 700;
        text-transform: uppercase;
        background: linear-gradient(90deg, #007bff, #00c3ff, #007bff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-size: 200%;
        letter-spacing: 1.5px;
        animation: textShine 4s ease-in-out infinite, fadeSlideIn 1s ease forwards;
        display: inline-block;
        margin: 0 auto;
    }

    /* 🟦 Card + Table */
    thead.table-darkblue th {
      background-color: #003366 !important;
      color: #fff;
    }
    .card {
      background-color: #fff;
      border: none;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

   
  </style>
</head>
<body class="container mt-4">

  <!-- Animated Heading -->
  <div class="text-center mb-4">
    <h2 class="product-heading">Purchase Returns</h2>
  </div>

  <!-- Card -->
  <div class="card shadow-lg border-0">
    <div class="card-body">

      <!-- Top Row -->
      <form method="get" class="row g-3 align-items-center mb-4">

        <!-- New Purchase Return Button -->
        <div class="col-md-3">
          <a href="purchase_return.php" class="btn btn-primary" style="background: linear-gradient(135deg, #007bff, #0ac5ebff); border:none;">➕ New Purchase Return</a>
        </div>

        <!-- Search Return No -->
        <div class="col-md-3">
          <input type="text" name="return_no" value="<?= htmlspecialchars($_GET['return_no'] ?? '') ?>" class="form-control" placeholder="Search Return No">
        </div>

        <!-- Supplier Filter -->
        <div class="col-md-3">
          <select name="supplier_id" class="form-select">
            <option value="">-- Filter by Supplier --</option>
            <?php foreach ($suppliers as $s): ?>
              <option value="<?= $s['supplier_id'] ?>" <?= (($_GET['supplier_id'] ?? '')==$s['supplier_id'])?'selected':'' ?>>
                <?= htmlspecialchars($s['supplier_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Search + Reset Buttons -->
        <div class="col-md-3 text-end">
          <button type="submit" class="btn btn-success" style="background: linear-gradient(135deg, #28a745, #60d394); 
          color: white; 
          border: none;">🔍 Search</button>
          <a href="purchase_return_list.php" class="btn btn-secondary" style="background: linear-gradient(135deg, #6c757d, #adb5bd); border:none;">Reset</a>
        </div>

      </form>

      <!-- Table -->
      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead class="table-darkblue text-center">
            <tr>
              <th>Return No</th>
              <th>Bill Date</th>
              <th>Supplier</th>
              <th>Items</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($returns): ?>
              <?php foreach ($returns as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['return_no']) ?></td>
                  <td><?= date('d-m-Y', strtotime($r['bill_date'])) ?></td>
                  <td><?= htmlspecialchars($r['supplier_name']) ?></td>
                  <td><?= $r['item_count'] ?></td>
                  <td class="text-center">
                    <a href="view_purchase_return.php?id=<?= $r['return_id'] ?>" class="btn btn-outline-primary btn-sm">👁</a>
                    <a href="delete_purchase_return.php?id=<?= $r['return_id'] ?>" 
                       onclick="return confirm('Delete this purchase return?')" 
                       class="btn btn-outline-danger btn-sm">🗑</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center">No purchase returns found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</body>
</html>

