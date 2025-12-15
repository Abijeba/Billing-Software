<?php
require_once 'config.php';
$title = "Purchase Invoices";
include 'header.php';

// --- Handle search/filter ---
$where = [];
$params = [];

if (!empty($_GET['pi_number'])) {
    $where[] = "pi.pi_number LIKE ?";
    $params[] = "%" . $_GET['pi_number'] . "%";
}
if (!empty($_GET['supplier_id'])) {
    $where[] = "pi.supplier_id = ?";
    $params[] = $_GET['supplier_id'];
}

// Fetch invoices with item count
$sql = "
    SELECT 
        pi.pi_id, 
        pi.pi_number, 
        pi.pi_date, 
        pi.due_date,
        s.supplier_name,
        COALESCE(items.item_count, 0) AS item_count,
        COALESCE(items.total_amount, 0) AS total_amount
    FROM purchase_invoices pi
    JOIN suppliers s ON pi.supplier_id = s.supplier_id
    LEFT JOIN (
        SELECT 
            pi_id, 
            COUNT(*) AS item_count, 
            SUM(line_total) AS total_amount
        FROM purchase_invoice_items
        GROUP BY pi_id
    ) items ON pi.pi_id = items.pi_id
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY pi.pi_number ASC";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch suppliers for filter
$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Purchase Invoices</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

  <style>
    /* --- Animated Heading --- */
    .page-heading {
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
        background: white;
        border: none;
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

    /* --- Buttons --- */
    .btn-gradient-primary {
        background: linear-gradient(135deg, #007bff, #00c3ff);
        border: none;
        color: white;
    }
    .btn-gradient-success {
        background: linear-gradient(135deg, #28a745, #60d394);
        border: none;
        color: white;
    }
    .btn-gradient-secondary {
        background: linear-gradient(135deg, #6c757d, #adb5bd);
        border: none;
        color: white;
    }
  </style>
</head>
<body class="container mt-4">

  <!-- Heading -->
  <div class="text-center my-4">
      <h2 class="page-heading fw-bold">PURCHASE INVOICES</h2>
  </div>

  <!-- Card Container -->
  <div class="card custom-card">

      <!-- Row: Button + Search Filters -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">

          <!-- Left: New Invoice Button -->
          <a href="insert_invoice.php" class="btn btn-gradient-primary">➕ New Invoice</a>

          <!-- Right: Search Form -->
          <form method="get" class="d-flex flex-wrap align-items-center gap-2">
              <input type="text" name="pi_number" value="<?= htmlspecialchars($_GET['pi_number'] ?? '') ?>" 
                     class="form-control" placeholder="Search Invoice Number" style="width:180px;">

              <select name="supplier_id" class="form-select" style="width:180px;">
                  <option value="">-- Filter by Supplier --</option>
                  <?php foreach ($suppliers as $s): ?>
                      <option value="<?= $s['supplier_id'] ?>" <?= (($_GET['supplier_id'] ?? '')==$s['supplier_id'])?'selected':'' ?>>
                          <?= htmlspecialchars($s['supplier_name']) ?>
                      </option>
                  <?php endforeach; ?>
              </select>

              <button type="submit" class="btn btn-gradient-success">🔍 Search</button>
              <a href="purchase_invoice_list.php" class="btn btn-gradient-secondary">Reset</a>
          </form>
      </div>

      <!-- Table -->
      <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle">
              <thead class="table-dark">
                  <tr>
                      <th>PI Number</th>
                      <th>Invoice Date</th>
                      <th>Supplier</th>
                      <th>Due Date</th>
                      <th>Items</th>
                      <th>Total Amount (₹)</th>
                      <th>Actions</th>
                  </tr>
              </thead>
              <tbody>
                  <?php if ($invoices): ?>
                      <?php foreach ($invoices as $inv): ?>
                          <tr>
                              <td><?= htmlspecialchars($inv['pi_number']) ?></td>
                              <td><?= date('d-m-Y', strtotime($inv['pi_date'])) ?></td>
                              <td><?= htmlspecialchars($inv['supplier_name']) ?></td>
                              <td><?= $inv['due_date'] ? date('d-m-Y', strtotime($inv['due_date'])) : '-' ?></td>
                              <td><?= $inv['item_count'] ?></td>
                              <td>₹ <?= number_format($inv['total_amount'], 2) ?></td>
                              <td>
                                  <button class="btn btn-outline-success btn-sm" onclick="showPrint(<?= $inv['pi_id'] ?>)">🖨</button>
                                  <a href="edit_invoice.php?id=<?= $inv['pi_id'] ?>" class="btn btn-outline-warning btn-sm">✏</a>
                                  <a href="delete_invoice.php?id=<?= $inv['pi_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this invoice?')">🗑</a>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <tr><td colspan="7" class="text-center">No invoices found.</td></tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>

  </div>

  <!-- Hidden Print Area -->
  <div id="printArea" style="display:none; margin-top:30px;"></div>

  <script>
  function showPrint(pi_id){
      fetch('purchase_invoice_print.php?id=' + pi_id)
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
