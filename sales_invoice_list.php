<?php
require_once 'config.php';
$title = "Sales Invoices List";
include 'header.php';

// --- Handle search/filter ---
$where = [];
$params = [];

if (!empty($_GET['si_number'])) {
    $where[] = "si.si_number LIKE ?";
    $params[] = "%" . $_GET['si_number'] . "%";
}
if (!empty($_GET['customer_id'])) {
    $where[] = "si.customer_id = ?";
    $params[] = $_GET['customer_id'];
}

// --- Fetch sales invoices ---
$sql = "
    SELECT 
        si.si_id, 
        si.si_number, 
        si.si_date, 
        si.due_date,
        si.customer_id,
        c.customer_name,
        COALESCE(items.item_count, 0) AS item_count,
        COALESCE(items.total_amount, 0) AS total_amount,
        COALESCE(payments.total_paid, 0) AS paid_amount
    FROM sales_invoices si
    JOIN customers c ON si.customer_id = c.customer_id
    LEFT JOIN (
        SELECT si_id, COUNT(*) AS item_count, SUM(line_total) AS total_amount
        FROM sales_invoice_items GROUP BY si_id
    ) items ON si.si_id = items.si_id
    LEFT JOIN (
        SELECT party_id, SUM(amount) AS total_paid
        FROM payments_in GROUP BY party_id
    ) payments ON si.customer_id = payments.party_id
";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY si.si_number ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch customers
$customers = $pdo->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
body { background-color: #f8f9fa; }
body.container {
    max-width: 100% !important;  /* previously Bootstrap limits around 960–1140px */
}
/* --- Animated Heading --- */
.page-heading {
    font-size: 2.3rem;
    font-weight: 700;
    text-transform: uppercase;
    background: linear-gradient(90deg, #140d77, #3a30d3, #140d77);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-size: 200%;
    letter-spacing: 1.2px;
    animation: textShine 4s ease-in-out infinite, fadeSlideIn 1s ease forwards;
    margin: 20px auto 30px;
    display: inline-block;
}
@keyframes textShine {
  0% { background-position: 200% center; }
  100% { background-position: -200% center; }
}
@keyframes fadeSlideIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* --- Main Card --- */
.sales-container {
    background: #fff;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    animation: fadeInUp 0.8s ease;
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* --- Table --- */
table thead th {
    background-color: #140d77 !important;
    color: white !important;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.table tbody tr:hover {
    background-color: #f8f8ff;
    transition: all 0.2s ease-in;
}

/* --- Filter Form --- */
form input, form select {
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.btn-primary {
    background: linear-gradient(135deg, #140d77, #3a30d3);
    border: none;
}
.btn-primary:hover { box-shadow: 0 0 10px rgba(20,13,119,0.3); }

/* --- DataTables --- */
.dataTables_wrapper .dataTables_filter { display: none !important; }
.dataTables_wrapper .dataTables_info { display: none !important; }
.dataTables_wrapper .dataTables_length {
  float: left;
  margin-top: 10px;
}
.dataTables_wrapper .dataTables_length label {
  font-weight: 600;
  color: #140d77;
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 1rem;
}
.dataTables_wrapper .dataTables_length select {
  border: 2px solid #140d77;
  border-radius: 10px;
  padding: 8px 16px;
  background-color: #f9f9ff;
  color: #140d77;
  font-weight: 600;
  font-size: 1rem;
  box-shadow: 0 2px 8px rgba(20, 13, 119, 0.15);
  transition: all 0.3s ease;
  cursor: pointer;
  min-width: 100px;
}
.dataTables_wrapper .dataTables_length select:hover {
  background-color: #ebe9ff;
  box-shadow: 0 0 10px rgba(20, 13, 119, 0.3);
  transform: scale(1.03);
}
.dataTables_wrapper .dataTables_paginate {
  float: right;
  margin-top: 5px;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
  border: none !important;
  background: none !important;
  border-radius: 50% !important;
  color: #6c757d !important;
  transition: all 0.2s ease;
  padding: 6px 10px !important;
  margin: 0 3px;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
  background-color: #f1f1f1 !important;
  color: #140d77 !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
  background-color: #140d77 !important;
  color: #fff !important;
}

/* 🌟 Stylish Action Modal */
.action-modal-content {
  border-radius: 15px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.15);
  animation: fadeUp 0.4s ease;
}
@keyframes fadeUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
.action-tile {
  width: 100%;
  background: #f8f9ff;
  border: 2px solid #140d77;
  border-radius: 12px;
  padding: 20px 10px;
  font-size: 1.2rem;
  font-weight: 600;
  color: #140d77;
  text-align: center;
  transition: all 0.25s ease;
  box-shadow: 0 4px 12px rgba(20, 13, 119, 0.1);
}
.action-tile:hover {
  background: linear-gradient(135deg, #140d77, #3a30d3);
  color: #fff;
  transform: scale(1.05);
  box-shadow: 0 6px 20px rgba(20,13,119,0.3);
}
.action-tile span {
  display: block;
  margin-top: 6px;
  font-size: 0.95rem;
}
</style>
</head>

<body class="container mt-4">

<div class="text-center">
  <h2 class="page-heading">Sales Invoice List</h2>
</div>

<div class="sales-container">
  <!-- Filter Form -->
  <form method="get" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="si_number" value="<?= htmlspecialchars($_GET['si_number'] ?? '') ?>" class="form-control" placeholder="Search Invoice Number">
    </div>
    <div class="col-md-4">
        <select name="customer_id" class="form-select">
            <option value="">-- Filter by Customer --</option>
            <?php foreach ($customers as $c): ?>
                <option value="<?= $c['customer_id'] ?>" <?= (($_GET['customer_id'] ?? '')==$c['customer_id'])?'selected':'' ?>>
                    <?= htmlspecialchars($c['customer_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4 d-flex align-items-center justify-content-center gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
        <a href="sales_invoice_list.php" class="btn btn-primary btn-sm">Reset</a>
        <a href="insert_sales_invoice.php" class="btn btn-primary btn-sm text-white"><i class="fa fa-plus"></i> New Invoice</a>
    </div>
  </form>

  <!-- Table -->
  <div class="table-responsive">
  <table class="table table-bordered table-striped align-middle">
    <thead>
        <tr>
            <th>SI Number</th>
            <th>Invoice Date</th>
            <th>Customer</th>
            <th>Due Date</th>
            <th>Items</th>
            <th>Total (₹)</th>
            <th>Paid (₹)</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($invoices): ?>
        <?php foreach ($invoices as $inv): 
            $total = (float)$inv['total_amount'];
            $paid = (float)$inv['paid_amount'];
            if ($total == 0) $status = '<span class="badge bg-secondary">No Amount</span>';
            elseif ($paid >= $total) $status = '<span class="badge bg-success">Fully Paid</span>';
            elseif ($paid > 0) $status = '<span class="badge bg-warning text-dark">Partially Paid</span>';
            else $status = '<span class="badge bg-danger">Unpaid</span>';
        ?>
        <tr>
            <td><?= htmlspecialchars($inv['si_number']) ?></td>
            <td><?= date('d-m-Y', strtotime($inv['si_date'])) ?></td>
            <td><?= htmlspecialchars($inv['customer_name']) ?></td>
            <td><?= $inv['due_date'] ? date('d-m-Y', strtotime($inv['due_date'])) : '-' ?></td>
            <td><?= $inv['item_count'] ?></td>
            <td><?= number_format($total, 2) ?></td>
            <td><?= number_format($paid, 2) ?></td>
            <td><?= $status ?></td>
            <td class="text-center">
                <button class="btn btn-outline-primary btn-sm action-btn" 
                        data-si="<?= $inv['si_id'] ?>" 
                        data-customer="<?= $inv['customer_id'] ?>" 
                        data-si-number="<?= htmlspecialchars($inv['si_number'], ENT_QUOTES) ?>">
                    ⚙ Actions
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No invoices found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- 💰 Payment History Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#140d77;color:#fff;">
        <h5 class="modal-title">💰 Payment History</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="paymentContent" style="min-height:200px;">
        <div class="text-center text-muted p-4">Loading payment details...</div>
      </div>
    </div>
  </div>
</div>

<!-- 🌟 Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content action-modal-content text-center">
      <div class="modal-header" style="background:linear-gradient(135deg,#140d77,#3a30d3);color:white;">
        <h5 class="modal-title w-100">Select an Action</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3 justify-content-center">
          <div class="col-6 col-md-4"><button class="action-tile" data-action="print">🖨<br><span>Print</span></button></div>
          <div class="col-6 col-md-4"><button class="action-tile" data-action="edit">✏<br><span>Edit</span></button></div>
          <div class="col-6 col-md-4"><button class="action-tile" data-action="delete">🗑<br><span>Delete</span></button></div>
          <div class="col-6 col-md-4"><button class="action-tile" data-action="payments">💰<br><span>View Payments</span></button></div>
          <div class="col-6 col-md-4"><button class="action-tile" data-action="addpayment">💵<br><span>Add Payment</span></button></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
  $('table').DataTable({
      "pageLength": 3,
      "lengthMenu": [3, 5, 10, 25, 50, 100],
      "dom": '<"d-flex justify-content-between align-items-center mb-2"l><"table-responsive"t><"d-flex justify-content-end mt-2"p>',
      "language": { "lengthMenu": "Show _MENU_ entries", "paginate": { "previous": "‹", "next": "›" } }
  });

  let selectedData = {};

  $(document).on('click', '.action-btn', function() {
    selectedData = {
      si_id: $(this).data('si'),
      customer_id: $(this).data('customer'),
      si_number: $(this).data('si-number')
    };
    const modal = new bootstrap.Modal(document.getElementById('actionModal'));
    modal.show();
  });

  $(document).on('click', '.action-tile', function() {
    const action = $(this).data('action');
    const { si_id, customer_id, si_number } = selectedData;
    const modalEl = bootstrap.Modal.getInstance(document.getElementById('actionModal'));
    modalEl.hide();

    if (action === 'print') showPrint(si_id);
    else if (action === 'edit') window.location.href = 'edit_sales_invoice.php?id=' + si_id;
    else if (action === 'delete') {
      if (confirm('Are you sure you want to delete this invoice?'))
        window.location.href = 'delete_sales_invoice.php?id=' + si_id;
    } 
    else if (action === 'payments') showPayments(customer_id, si_number);
    else if (action === 'addpayment') window.location.href = 'payment_in.php?customer_id=' + customer_id;
  });
});

function printDiv(htmlContent) {
  const iframe = document.createElement('iframe');
  iframe.style.display = 'none';
  document.body.appendChild(iframe);
  const frameWindow = iframe.contentWindow;
  frameWindow.document.open();
  frameWindow.document.write(htmlContent);
  frameWindow.document.close();
  frameWindow.focus();
  frameWindow.print();
  setTimeout(() => document.body.removeChild(iframe), 1000);
}

function showPrint(si_id) {
  // Remove any old iframe before creating new one
  const oldIframe = document.getElementById('printFrame');
  if (oldIframe) oldIframe.remove();

  // Create a new hidden iframe for printing
  const iframe = document.createElement('iframe');
  iframe.style.display = 'none';
  iframe.id = 'printFrame';
  iframe.src = 'sampleprint_organics.php?id=' + si_id;
  document.body.appendChild(iframe);

  iframe.onload = function() {
    iframe.contentWindow.focus();
    iframe.contentWindow.print();

    // Detect when print dialog closes (cancel or done)
    const removeIframe = () => {
      iframe.removeEventListener('afterprint', removeIframe);
      setTimeout(() => iframe.remove(), 1000);
    };

    iframe.contentWindow.addEventListener('afterprint', removeIframe);
  };
}



function showPayments(customer_id, si_number) {
  const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
  const content = document.getElementById('paymentContent');
  content.innerHTML = "<div class='text-center text-muted p-4'>Loading payment details...</div>";
  modal.show();
  fetch(`view_payment_ajax.php?customer_id=${customer_id}&si_number=${encodeURIComponent(si_number)}`)
      .then(r => r.text())
      .then(html => content.innerHTML = html)
      .catch(() => content.innerHTML = "<p class='text-danger text-center'>Failed to load payments.</p>");
}
</script>
</body>
</html>