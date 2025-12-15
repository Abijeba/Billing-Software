<?php
require_once "config.php";
$title = "Sales Return";

// --- Handle Form Submission ---
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO sales_returns 
            (return_no, invoice_no, invoice_date, customer_id, state, tax_mode, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['return_no'],
            $_POST['bill_no'],
            $_POST['bill_date'],
            $_POST['customer_id'],
            $_POST['state'],
            $_POST['tax_mode'],
            $_POST['description']
        ]);

        $sr_id = $pdo->lastInsertId();

        $columns = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'")->fetch();
        $has_stock = $columns ? true : false;

        if (!empty($_POST['product_id'])) {
            foreach ($_POST['product_id'] as $i => $pid) {
                if (!$pid) continue;
                $qty = $_POST['quantity'][$i];
                $rate = $_POST['rate'][$i];
                $disc = $_POST['discount'][$i];
                $gst = $_POST['gst'][$i];
                $line_total = $_POST['line_total'][$i];

                $stmt_item = $pdo->prepare("INSERT INTO sales_return_items 
                    (sr_id, product_id, quantity, rate, discount, gst, line_total) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_item->execute([$sr_id, $pid, $qty, $rate, $disc, $gst, $line_total]);

                if ($has_stock) {
                    $pdo->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?")
                        ->execute([$qty, $pid]);
                }
            }
        }

        $pdo->commit();
        header("Location: sales_return.php?success=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "❌ Error: " . $e->getMessage();
    }
}

// --- Generate New Return Number ---
$last_sr = $pdo->query("SELECT return_no FROM sales_returns ORDER BY return_id DESC LIMIT 1")->fetchColumn();
if ($last_sr) {
    $num = (int)substr($last_sr, strrpos($last_sr, "/") + 1);
    $return_no = "SR/25-26/" . str_pad($num + 1, 4, "0", STR_PAD_LEFT);
} else {
    $return_no = "SR/25-26/0001";
}

$columns = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'")->fetch();
$has_stock = $columns ? true : false;

$product_query = "SELECT product_id, product_name, rate, gst, discount";
if ($has_stock) $product_query .= ", stock";
$product_query .= " FROM products ORDER BY product_name";
$products = $pdo->query($product_query)->fetchAll(PDO::FETCH_ASSOC);
$customers = $pdo->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['success'])) {
    $message = "✅ Sales Return Saved Successfully!";
}

include "header.php";
?>

<div class="container mt-4">
    <div class="text-center my-4">
        <h2 class="fw-bold text-uppercase" style="color:#0d47a1; letter-spacing:1px; font-size:2rem;">
            SALES RETURN
        </h2>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info text-center fw-bold"><?= $message ?></div>
    <?php endif; ?>

    <!-- ✨ Gradient Card -->
    <div class="card shadow-lg border-0" style="background: linear-gradient(35deg, #cce7ff, #f4f6f7ff 100%); border-radius: 15px;">
        <div class="card-body">
            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-3">
                         <label>SR Number</label>
                        <input type="text" name="return_no" class="form-control" value="<?= $return_no ?>" readonly placeholder="SR Number">
                    </div>
                    <div class="col-md-3">
                         <label>Bill Number</label>
                        <input type="text" name="bill_no" class="form-control" placeholder="Bill Number" required>
                    </div>
                    <div class="col-md-3">
                         <label>Date</label>
                        <input type="date" name="bill_date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                         <label>State</label>
                        <input type="text" name="state" class="form-control" value="Tamil Nadu" placeholder="State / Place of Supply">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                         <label>Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">-- Select Customer --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 text-start mt-2">
                          <label>Tax Mode:</label><br>
                        <input type="radio" name="tax_mode" value="Inclusive" checked onclick="updateAllLineTotals()"> Inclusive
                        <input type="radio" name="tax_mode" value="Exclusive" onclick="updateAllLineTotals()"> Exclusive
                    </div>
                </div>
<div class="table-responsive">
                <h5 class="mt-4 fw-bold text-primary">Return Items</h5>
                <table id="itemsTable" class="table table-bordered table-striped">
                    <thead class="text-center table-dark">
                        <tr>
                            <th>Product</th>
                            <?php if ($has_stock) echo "<th>Stock</th>"; ?>
                            <th>Rate</th>
                            <th>Qty</th>
                            <th>Discount %</th>
                            <th>GST %</th>
                            <th>Line Total</th>
                            <th><button type="button" class="btn btn-outline-light btn-sm" onclick="addRow()">➕</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-select" onchange="fillProductDetails(this)">
                                    <option value="">-- Select Product --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['product_id'] ?>"
                                            data-rate="<?= $p['rate'] ?>"
                                            data-gst="<?= $p['gst'] ?>"
                                            data-discount="<?= $p['discount'] ?>"
                                            data-stock="<?= $has_stock ? $p['stock'] : 0 ?>">
                                            <?= htmlspecialchars($p['product_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <?php if ($has_stock): ?>
                                <td><input type="text" name="stock[]" class="form-control" readonly></td>
                            <?php endif; ?>
                            <td><input type="number" name="rate[]" class="form-control" readonly></td>
                            <td><input type="number" name="quantity[]" value="1" class="form-control" oninput="updateLineTotal(this)"></td>
                            <td><input type="number" name="discount[]" class="form-control" readonly></td>
                            <td><input type="number" name="gst[]" class="form-control" readonly></td>
                            <td><input type="number" name="line_total[]" class="form-control" readonly></td>
                            <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(this)">x</button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="<?= $has_stock ? '6' : '5' ?>" class="text-end">Net Amount</th>
                            <th><input type="number" id="grandTotal" class="form-control" readonly></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                            </div>

                <div class="mb-3">
                    <textarea name="description" class="form-control" placeholder="Description..."></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-gradient-green">Save</button>
                    <a href="sales_return_list.php" class="btn btn-gradient-gray">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.btn-gradient-green {
    background: linear-gradient(135deg, #28a745, #60d394);
    color: #fff;
    border: none;
    transition: 0.3s;
}
.btn-gradient-green:hover {
    transform: scale(1.03);
}
.btn-gradient-gray {
    background: linear-gradient(135deg, #adb5bd, #6c757d);
    color: #fff;
    border: none;
    transition: 0.3s;
}
.btn-gradient-gray:hover {
    transform: scale(1.03);
}
thead.table-dark th {
    background-color: #003366 !important;
    color: #fff !important;
    text-align: center;
}
</style>

<script>
function addRow() {
    const tbody = document.querySelector("#itemsTable tbody");
    const newRow = tbody.rows[0].cloneNode(true);
    newRow.querySelectorAll("input").forEach(inp => inp.value = inp.name === "quantity[]" ? 1 : "");
    newRow.querySelectorAll("select").forEach(sel => sel.selectedIndex = 0);
    tbody.appendChild(newRow);
}

function removeRow(btn) {
    const tbody = document.querySelector("#itemsTable tbody");
    if (tbody.rows.length > 1) btn.closest("tr").remove();
    calculateGrandTotal();
}

function fillProductDetails(el) {
    const row = el.closest("tr");
    const opt = el.selectedOptions[0];
    if (!opt) return;
    row.querySelector("input[name='rate[]']").value = opt.dataset.rate || 0;
    row.querySelector("input[name='discount[]']").value = opt.dataset.discount || 0;
    row.querySelector("input[name='gst[]']").value = opt.dataset.gst || 0;
    const stockField = row.querySelector("input[name='stock[]']");
    if (stockField) stockField.value = opt.dataset.stock || 0;
    updateLineTotal(row.querySelector("input[name='quantity[]']"));
}

function getTaxMode() {
    const mode = document.querySelector("input[name='tax_mode']:checked");
    return mode ? mode.value : "Exclusive";
}

function updateLineTotal(el) {
    const row = el.closest("tr");
    const qty = parseFloat(row.querySelector("input[name='quantity[]']").value) || 0;
    const rate = parseFloat(row.querySelector("input[name='rate[]']").value) || 0;
    const disc = parseFloat(row.querySelector("input[name='discount[]']").value) || 0;
    const gst = parseFloat(row.querySelector("input[name='gst[]']").value) || 0;

    let total = qty * rate;
    total -= total * (disc / 100);
    if (getTaxMode() === "Exclusive") total += total * (gst / 100);

    row.querySelector("input[name='line_total[]']").value = total.toFixed(2);
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let total = 0;
    document.querySelectorAll("input[name='line_total[]']").forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    document.getElementById("grandTotal").value = total.toFixed(2);
}
</script>
