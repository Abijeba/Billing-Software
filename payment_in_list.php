<?php
require_once 'config.php';
$title = "Payment In";
include 'header.php';

// --- Handle search/filter ---
$where = [];
$params = [];

if (!empty($_GET['pi_number'])) {
    $where[] = "pi.pi_number LIKE ?";
    $params[] = "%" . $_GET['pi_number'] . "%";
}
if (!empty($_GET['customer_id'])) {
    $where[] = "pi.party_id = ?";
    $params[] = $_GET['customer_id'];
}

// --- Fetch payments in ---
$sql = "
    SELECT 
        pi.id, 
        pi.pi_number, 
        pi.date, 
        COALESCE(c.customer_name, l.ledger_name) AS customer_name,
        pi.amount,
        pi.payment_mode,
        pi.notes
    FROM payments_in pi
    LEFT JOIN customers c ON pi.party_id = c.customer_id
    LEFT JOIN ledgers l ON pi.party_id = l.id
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY pi.pi_number ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$customers = $pdo->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ✨ Custom Styles and Animations -->
<style>
.payment-container {
    animation: fadeInUp 0.8s ease;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 25px;
}
.page-heading {
    font-size: 2.5rem;
    font-weight: 700;
    text-transform: uppercase;
    background: linear-gradient(90deg, #140d77, #4a3aff, #140d77);
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
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
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
.btn:hover {
    box-shadow: 0 0 10px rgba(20, 13, 119, 0.4);
    transform: scale(1.05);
    transition: 0.2s;
}
.table-responsive {
    max-height: 400px;
    overflow-y: auto;
    border-radius: 10px;
}
form input, form select {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
/* Delete button style */
.btn-outline-danger {
    border: 1px solid #d9534f;
    color: #d9534f;
}
.btn-outline-danger:hover {
    background-color: #d9534f;
    color: white;
}

/* Toast Message */
.toast-msg {
    position: fixed;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, #140d77, #3a30d3);
    color: white;
    padding: 12px 22px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    font-weight: 600;
    z-index: 9999;
    opacity: 1;
    transition: opacity 0.8s ease;
    animation: slideIn 0.5s ease;
}
@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- 🌟 Page Title -->
<div class="text-center my-4">
    <h2 class="page-heading">Payment In List</h2>
</div>

<div class="payment-container">

    <!-- ✅ Toast Message for Delete Success -->
    <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div id="toast-msg" class="toast-msg">✅ Payment deleted successfully!</div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast-msg');
                if (toast) toast.style.opacity = '0';
            }, 3000);
        </script>
    <?php endif; ?>

    <!-- 🔍 Filter Form -->
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" name="pi_number" 
                   value="<?= htmlspecialchars($_GET['pi_number'] ?? '') ?>" 
                   placeholder="Search PI Number" 
                   class="form-control shadow-sm">
        </div>
        <div class="col-md-4">
            <select name="customer_id" class="form-select shadow-sm">
                <option value="">-- Filter by Customer --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['customer_id'] ?>" <?= (($_GET['customer_id'] ?? '')==$c['customer_id'])?'selected':'' ?>>
                        <?= htmlspecialchars($c['customer_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-center justify-content-center gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary btn-sm" 
                    style="background: linear-gradient(135deg, #140d77, #3a30d3); border: none;">
                <i class="fa fa-search"></i> Search
            </button>
            <a href="payment_in_list.php" class="btn btn-secondary btn-sm"
                  style="background: linear-gradient(135deg, #140d77, #3a30d3); border: none;">
                <i class="fa fa-refresh"></i> Reset
            </a>
            <a href="payment_in.php" class="btn btn-sm text-white shadow-sm"
               style="background: linear-gradient(135deg, #140d77, #3a30d3); border: none;">
                <i class="fa fa-plus"></i> New Payment In
            </a>
        </div>
    </form>

    <!-- 📋 Payment Table -->
    <div class="table-responsive shadow-sm">
        <table class="table table-bordered table-striped align-middle text-center">
            <thead>
                <tr>
                    <th>PI Number</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Mode</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['pi_number']) ?></td>
                            <td><?= date('d-m-Y', strtotime($p['date'])) ?></td>
                            <td><?= htmlspecialchars($p['customer_name']) ?></td>
                            <td>₹<?= number_format($p['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($p['payment_mode']) ?></td>
                            <td><?= htmlspecialchars($p['notes']) ?></td>
                            <td>
                                <a href="delete_payment_in.php?id=<?= $p['id'] ?>" 
                                   class="btn btn-outline-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this payment?');"
                                   title="Delete">
                                   <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-muted py-4">No payments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>