<?php
require_once 'config.php';
$title = "Purchase Orders";
include 'header.php';

// --- Handle search/filter ---
$where = [];
$params = [];

if (!empty($_GET['po_number'])) {
    $where[] = "po.po_number LIKE ?";
    $params[] = "%" . $_GET['po_number'] . "%";
}
if (!empty($_GET['supplier_id'])) {
    $where[] = "po.supplier_id = ?";
    $params[] = $_GET['supplier_id'];
}

// Fetch purchase orders with item count
$sql = "
    SELECT 
        po.po_id, 
        po.po_number, 
        po.po_date, 
        po.expected_date,
        s.supplier_name,
        COALESCE(items.item_count, 0) AS item_count
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN (
        SELECT po_id, COUNT(*) AS item_count
        FROM purchase_order_items
        GROUP BY po_id
    ) items ON po.po_id = items.po_id
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY po.po_number ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch suppliers for filter
$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Purchase Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

  <style>
    body.container {
    max-width: 100% !important;  /* previously Bootstrap limits around 960–1140px */
}
    /* --- Animated Heading --- */
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

    @keyframes textShine {
        0% { background-position: 200% center; }
        100% { background-position: -200% center; }
    }

    @keyframes fadeSlideIn {
        0% { opacity: 0; transform: translateY(25px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    /* --- Card Style --- */
    .custom-card {
        background: #ffffff;
        border: none; /* ✅ No border */
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        padding: 25px;
    }

    /* --- Table Header --- */
    thead.table-dark th {
        background-color: #003366 !important;
        color: white !important;
        text-align: center;
    }
  </style>
</head>
<body class="container mt-4">

  <!-- Animated Heading -->
  <div class="text-center my-4">
      <h2 class="product-heading fw-bold">PURCHASE ORDERS</h2>
  </div>

  <!-- Card Container -->
  <div class="custom-card mb-5">

      <!-- Button + Filters Row -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
          <!-- Left side: New Purchase Order -->
          <div>
              <a href="insert_po.php" class="btn btn-primary" 
                 style="background: linear-gradient(135deg, #007bff, #0ac5ebff); border:none;">
                 ➕ New Purchase Order
              </a>
          </div>

          <!-- Right side: Search / Filter Form -->
          <form method="get" class="d-flex flex-wrap align-items-center gap-2">
              <input type="text" name="po_number" 
                     value="<?= htmlspecialchars($_GET['po_number'] ?? '') ?>" 
                     class="form-control" placeholder="Search PO Number" style="width:180px;">
              
              <select name="supplier_id" class="form-select" style="width:180px;">
                  <option value="">-- Filter by Supplier --</option>
                  <?php foreach ($suppliers as $s): ?>
                      <option value="<?= $s['supplier_id'] ?>" 
                              <?= (($_GET['supplier_id'] ?? '')==$s['supplier_id'])?'selected':'' ?>>
                          <?= htmlspecialchars($s['supplier_name']) ?>
                      </option>
                  <?php endforeach; ?>
              </select>

              <button type="submit" class="btn btn-success" style="background: linear-gradient(135deg, #28a745, #60d394); 
          color: white; 
          border: none;">🔍 Search</button>
              <a href="purchase_list.php" class="btn btn-secondary" style="background: linear-gradient(135deg, #6c757d, #adb5bd); border:none;">Reset</a>
          </form>
      </div>

      <!-- Table Section -->
      <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle">
              <thead class="table-dark text-center">
                  <tr>
                      <th>PO Number</th>
                      <th>PO Date</th>
                      <th>Supplier</th>
                      <th>Expected Delivery</th>
                      <th>Items</th>
                      <th>Actions</th>
                  </tr>
              </thead>
              <tbody>
                  <?php if ($orders): ?>
                      <?php foreach ($orders as $o): ?>
                          <tr>
                              <td><?= htmlspecialchars($o['po_number']) ?></td>
                              <td><?= date('d-m-Y', strtotime($o['po_date'])) ?></td>
                              <td><?= htmlspecialchars($o['supplier_name']) ?></td>
                              <td><?= $o['expected_date'] ? date('d-m-Y', strtotime($o['expected_date'])) : '-' ?></td>
                              <td><?= $o['item_count'] ?></td>
                              <td>
                                  <button class="btn btn-outline-success btn-sm" onclick="showPrint(<?= $o['po_id'] ?>)">🖨</button>
                                  <a href="edit_po.php?id=<?= $o['po_id'] ?>" class="btn btn-outline-warning btn-sm">✏</a>
                                  <a href="delete_po.php?id=<?= $o['po_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this PO?')">🗑</a>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <tr>
                          <td colspan="7" class="text-center">No purchase orders found.</td>
                      </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>

  </div> <!-- end card -->

  <!-- Hidden Print Area -->
  <div id="printArea" style="display:none; margin-top:30px;"></div>

  <script>
  function showPrint(po_id){
      fetch('purchase_order_print.php?id=' + po_id)
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