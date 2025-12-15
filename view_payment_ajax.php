<?php
require_once 'config.php';

$customer_id = $_GET['customer_id'] ?? '';
$si_number = $_GET['si_number'] ?? '';

if (empty($customer_id)) {
    echo "<p class='text-danger'>Invalid request.</p>";
    exit;
}

// Fetch customer name
$stmt = $pdo->prepare("SELECT customer_name FROM customers WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Fetch payments ---
$sql = "
    SELECT 
        id,
        pi_number,
        date,
        payment_mode,
        amount,
        notes,
        created_at
    FROM payments_in
    WHERE party_id = ?
    ORDER BY date DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Total Paid ---
$total_paid = array_sum(array_column($payments, 'amount'));

// --- Fetch total invoice amount ---
$stmt = $pdo->prepare("
    SELECT SUM(items.line_total) AS total_invoice
    FROM sales_invoices si
    JOIN sales_invoice_items items ON si.si_id = items.si_id
    WHERE si.customer_id = ?
");
$stmt->execute([$customer_id]);
$total_invoice = (float)($stmt->fetchColumn() ?? 0);

// --- Calculate balance ---
$balance = $total_invoice - $total_paid;
?>

<style>
/* --- Card Custom Style --- */
.payment-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    overflow: hidden;
}

/* --- Table Header Color --- */
table thead tr th {
    background-color: #140d77 !important;
    color: white !important;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    transition: all 0.3s ease;
}
.table tbody tr:hover {
    background-color: #f9f9ff;
    transition: background-color 0.3s ease;
}

/* --- Table Scroll --- */
.table-responsive {
    max-height: 400px;
    overflow-y: auto;
    border-radius: 10px;
}

/* --- Summary Card --- */
.summary-card {
    background-color: #140d77;
    color: #fff;
    border-radius: 10px;
    margin-top: 20px;
    padding: 15px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.summary-card h6 {
    margin: 0;
    font-weight: 600;
    font-size: 1rem;
}
.summary-card .amount {
    font-size: 1.1rem;
    font-weight: 700;
}

/* --- Text Colors --- */
.text-green { color: #28a745 !important; }
.text-red { color: #ff4c4c !important; }

/* --- Form Input Shadow --- */
form input, form select {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
</style>

<div class="card payment-card">
  <div class="card-body">
    <div class="text-center mb-3">
      <h5 class="fw-bold text-primary">💰 Payment Details</h5>
    </div>

    <p><strong>Customer:</strong> <?= htmlspecialchars($customer['customer_name']) ?></p>
    <p><strong>Invoice:</strong> <?= htmlspecialchars($si_number) ?></p>

    <?php if ($payments): ?>
      <div class="table-responsive shadow-sm">
        <table class="table table-bordered table-striped align-middle text-center">
          <thead>
            <tr>
              <th>📅 Date</th>
              <th>Reference</th>
              <th>💳 Payment Mode</th>
              <th>💰 Amount (₹)</th>
              <th>📝 Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td><?= date('d M Y', strtotime($p['date'])) ?></td>
                <td><?= htmlspecialchars($p['pi_number'] ?: '-') ?></td>
                <td>
                  <span class="badge bg-success px-3 py-2">
                    <?= htmlspecialchars(ucfirst($p['payment_mode'])) ?>
                  </span>
                </td>
                <td class="text-success fw-bold">₹<?= number_format($p['amount'], 2) ?></td>
                <td><?= htmlspecialchars($p['notes'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-center text-muted mb-0">No payments found for this customer.</p>
    <?php endif; ?>

    <!-- Summary Card (always visible) -->
    <div class="summary-card mt-4">
      <div class="row text-center">
        <div class="col-md-4 col-12 mb-2 mb-md-0">
          <h6>Total Invoice Amount</h6>
          <div class="amount">₹<?= number_format($total_invoice, 2) ?></div>
        </div>
        <div class="col-md-4 col-12 mb-2 mb-md-0">
          <h6>Total Paid Amount</h6>
          <div class="amount text-warning">₹<?= number_format($total_paid, 2) ?></div>
        </div>
        <div class="col-md-4 col-12">
          <h6>Remaining Balance</h6>
          <div class="amount <?= ($balance <= 0) ? 'text-green' : 'text-red' ?>">
            ₹<?= number_format($balance, 2) ?>
          </div>
        </div>
      </div>
    </div>

    <div class="text-end mt-2 text-muted small">
      Showing <?= count($payments) ?> payment transaction<?= count($payments)>1?'s':'' ?>.
    </div>
  </div>
</div>