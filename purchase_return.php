<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config.php";
$title = "Purchase Return";
include "header.php";

// --- Generate PR Number ---
function generatePRNumber($pdo) {
    $last_pr = $pdo->query("SELECT return_no FROM purchase_returns ORDER BY return_id DESC LIMIT 1")->fetchColumn();
    if ($last_pr) {
        $num = (int)substr($last_pr, strrpos($last_pr, "/") + 1);
        return "PR/25-26/" . str_pad($num + 1, 4, "0", STR_PAD_LEFT);
    } else {
        return "PR/25-26/0001";
    }
}

$return_no = generatePRNumber($pdo);

// --- Fetch Products and Suppliers ---
$columns = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'")->fetch();
$has_stock = $columns ? true : false;

$product_query = "SELECT product_id, product_name, rate, gst, discount";
if ($has_stock) $product_query .= ", stock";
$product_query .= " FROM products ORDER BY product_name";
$products = $pdo->query($product_query)->fetchAll(PDO::FETCH_ASSOC);

$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
$purchase_invoices = $pdo->query("SELECT pi_id, pi_number, supplier_bill_no, supplier_id FROM purchase_invoices ORDER BY pi_id DESC")->fetchAll(PDO::FETCH_ASSOC);

// --- Handle Form Submission ---
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Validate at least one item selected
        $has_item = false;
        if (!empty($_POST['product_id'])) {
            foreach ($_POST['product_id'] as $pid) {
                if (!empty($pid)) $has_item = true;
            }
        }
        if (!$has_item) throw new Exception("Please select at least one product to return.");

        // Insert Purchase Return
        $stmt = $pdo->prepare("INSERT INTO purchase_returns 
            (return_no, pi_id, bill_no, bill_date, supplier_id, state, tax_mode, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['return_no'],
            $_POST['pi_id'],
            $_POST['bill_no'],
            $_POST['bill_date'],
            $_POST['supplier_id'],
            $_POST['state'],
            $_POST['tax_mode'],
            $_POST['description']
        ]);
        $pr_id = $pdo->lastInsertId();

        // Insert Items + update stock
        if (!empty($_POST['product_id'])) {
    foreach ($_POST['product_id'] as $i => $pid) {
        if (!$pid) continue; // skip empty product rows

        $qty = floatval($_POST['quantity'][$i]);
        $rate = floatval($_POST['rate'][$i]);
        $disc = floatval($_POST['discount'][$i]);
        $gst = floatval($_POST['gst'][$i]);
        $line_total = floatval($_POST['line_total'][$i]);

        $stmt_item = $pdo->prepare("INSERT INTO purchase_return_items 
            (pr_id, product_id, quantity, rate, discount, gst, line_total) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_item->execute([$pr_id, $pid, $qty, $rate, $disc, $gst, $line_total]);

        // Reduce stock
        if ($has_stock) {
            $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?")
                ->execute([$qty, $pid]);
        }
    }
}


        $pdo->commit();
        $message = "✅ Purchase Return Saved Successfully!";
        $return_no = generatePRNumber($pdo); // regenerate next PR number
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "❌ Error: " . $e->getMessage();
    }
}
?>
<div class="container mt-4">
    <!-- 🔹 Animated Center Heading -->
    <div class="text-center my-4">
        <h2 class="fw-bold text-uppercase "
            style="color:#0d47a1; letter-spacing:1px; font-size:2rem; font-weight:500;">
            PURCHASE RETURN
        </h2>
    </div>

    <?php if($message): ?>
        <?php
            $isError = stripos($message, 'Error') !== false || stripos($message, 'greater than') !== false;
            $bgColor = $isError ? '#ff9800' : '#28a745';
        ?>
        <div id="toastMessage" style="
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: <?= $bgColor ?>;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 9999;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.5s ease;
        ">
            <?= htmlspecialchars($message) ?>
        </div>
        <script>
            const toast = document.getElementById('toastMessage');
            setTimeout(() => { toast.style.opacity = '1'; toast.style.transform = 'translateX(0)'; }, 100);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
            }, 3000);
        </script>
    <?php endif; ?>

    <!-- 🟢 White Card -->
    <div class="card shadow-lg border-0" style="background: linear-gradient(35deg, #cce7ff, #f4f6f7ff 100%); border-radius: 15px;">
        <div class="card-body">

            <!-- 🧾 Purchase Return Form -->
            <form method="post" id="prForm">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>PR Number</label>
                        <input type="text" name="return_no" class="form-control" value="<?= $return_no ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label>PI Number</label>
                        <select name="pi_id" id="pi_id" class="form-select" onchange="loadPIData(this)">
                            <option value="">-- Select PI Number --</option>
                            <?php foreach ($purchase_invoices as $pi): ?>
                                <option value="<?= $pi['pi_id'] ?>"
                                        data-bill-no="<?= htmlspecialchars($pi['supplier_bill_no']) ?>"
                                        data-supplier-id="<?= $pi['supplier_id'] ?>">
                                    <?= htmlspecialchars($pi['pi_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Bill Number</label>
                        <input type="text" name="bill_no" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label>Bill Date</label>
                        <input type="date" name="bill_date" class="form-control" required>
                    </div>

                    <div class="col-md-3 mt-2">
                        <label>State / Place of Supply</label>
                        <input type="text" name="state" class="form-control" value="Tamil Nadu">
                    </div>

                    <div class="col-md-6 mt-2">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-select" required id="supplierSelect">
                            <option value="">-- Select Supplier --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['supplier_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mt-2">
                        <label>Tax Mode</label><br>
                        <input type="radio" name="tax_mode" value="Inclusive" checked onclick="updateAllLineTotals()"> Inclusive
                        <input type="radio" name="tax_mode" value="Exclusive" onclick="updateAllLineTotals()"> Exclusive
                    </div>
                </div>

                <!-- 🧾 Return Items Table -->
                <h5 class="mt-4 fw-bold text-primary">Return Items</h5>
                <table id="itemsTable" class="table table-bordered table-striped">
                    <thead class="text-center table-dark">
                        <tr>
                            <th>Product</th>
                            <th>Rate</th>
                            <th>Qty</th>
                            <th>Discount %</th>
                            <th>GST %</th>
                            <th>Line Total</th>
                            <th>
                                <button type="button" class="btn btn-outline-light btn-sm" onclick="addRow()">➕</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="templateRow" style="display:none;">
                            <td>
                                <select name="product_id[]" class="form-select" onchange="fillProductDetails(this)">
                                    <option value="">-- Select Product --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['product_id'] ?>"
                                                data-rate="<?= $p['rate'] ?>"
                                                data-gst="<?= $p['gst'] ?>"
                                                data-discount="<?= $p['discount'] ?>">
                                            <?= htmlspecialchars($p['product_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
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
                            <th colspan="5" class="text-end">Net Amount</th>
                            <th><input type="number" id="grandTotal" class="form-control" readonly></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>

                <!-- 📝 Description -->
                <div class="mb-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>

                <!-- 🟢 Buttons -->
                <div class="text-end">
                    <button type="submit" class="btn btn-gradient-green">
                         Save
                    </button>
                    <a href="purchase_return_list.php" class="btn btn-gradient-gray">
                         Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 🎨 Styles -->
<style>
@keyframes fadeInUp {
  from {opacity: 0; transform: translateY(20px);}
  to {opacity: 1; transform: translateY(0);}
}

.animate__fadeInUp {
  animation: fadeInUp 1s ease forwards;
}

/* Gradient Buttons */
.btn-gradient-green {
    background: linear-gradient(135deg, #28a745, #60d394);
    color: #fff;
    border: none;
    transition: all 0.3s ease;
}
.btn-gradient-green:hover {
    background: linear-gradient(135deg, #218838, #4bb543);
    transform: scale(1.03);
}

.btn-gradient-gray {
    background: linear-gradient(135deg, #adb5bd, #6c757d);
    color: #fff;
    border: none;
    transition: all 0.3s ease;
}
.btn-gradient-gray:hover {
    background: linear-gradient(135deg, #868e96, #495057);
    transform: scale(1.03);
}

/* Table Header Dark Blue */
thead.table-dark th {
    background-color: #003366 !important;
    color: white !important;
    text-align: center;
}
</style>


<script>
let products = <?php echo json_encode($products); ?>;

// ✅ Add new row manually
function addRow() {
    const tbody = document.querySelector("#itemsTable tbody");
    const template = document.getElementById("templateRow");

    if (!template) {
        console.error("❌ Template row not found");
        return;
    }

    const newRow = template.cloneNode(true);
    newRow.id = ""; // remove id
    newRow.style.display = ""; // show row

    // Reset all inputs
    newRow.querySelectorAll("input").forEach(inp => {
        if (inp.name === "quantity[]") inp.value = 1;
        else inp.value = "";
    });
    newRow.querySelectorAll("select").forEach(sel => sel.selectedIndex = 0);

    tbody.appendChild(newRow);
}

// ✅ Remove row
function removeRow(btn) {
    const tbody = document.querySelector("#itemsTable tbody");
    if (tbody.querySelectorAll("tr:not(#templateRow)").length > 1) {
        btn.closest("tr").remove();
    }
    calculateGrandTotal();
}

// ✅ Fill product details when selected
function fillProductDetails(el) {
    const row = el.closest("tr");
    const opt = el.selectedOptions[0];
    if (!opt) return;

    const stockField = row.querySelector("input[name='stock[]']");
    if (stockField) stockField.value = opt.dataset.stock || 0;

    row.querySelector("input[name='rate[]']").value = opt.dataset.rate || 0;
    row.querySelector("input[name='discount[]']").value = opt.dataset.discount || 0;
    row.querySelector("input[name='gst[]']").value = opt.dataset.gst || 0;
    updateLineTotal(row.querySelector("input[name='quantity[]']"));
}

// ✅ Tax mode (Inclusive / Exclusive)
function getTaxMode() {
    const mode = document.querySelector("input[name='tax_mode']:checked");
    return mode ? mode.value : "Exclusive";
}

// ✅ Update line total
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

// ✅ Calculate grand total
function calculateGrandTotal() {
    let total = 0;
    document.querySelectorAll("input[name='line_total[]']").forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    const gt = document.getElementById("grandTotal");
    if (gt) gt.value = total.toFixed(2);
}

// ✅ Fetch purchase invoice items
function loadPIData(select) {
    const pi_id = select.value;
    if (!pi_id) return;

    const opt = select.selectedOptions[0];
    document.querySelector("input[name='bill_no']").value = opt.dataset.billNo || "";
    document.getElementById("supplierSelect").value = opt.dataset.supplierId || "";

    fetch('fetch_pi_items.php?pi_id=' + pi_id)
        .then(res => res.json())
        .then(items => {
            const tbody = document.querySelector("#itemsTable tbody");
            const template = document.getElementById("templateRow");

            // Remove old rows except template
            tbody.querySelectorAll("tr:not(#templateRow)").forEach(tr => tr.remove());

            if (!Array.isArray(items) || items.length === 0) {
                addRow();
                return;
            }

            items.forEach(item => {
                const newRow = template.cloneNode(true);
                newRow.id = "";
                newRow.style.display = "";

                const productSelect = newRow.querySelector("select[name='product_id[]']");

                // Populate product options using product_name as value
                productSelect.innerHTML = '<option value="">-- Select Product --</option>';
                products.forEach(p => {
                    const opt = document.createElement('option');
                
                    opt.value = p.product_name; // use name instead of id
                    opt.textContent = p.product_name;
                    opt.dataset.rate = p.rate;
                    opt.dataset.discount = p.discount;
                    opt.dataset.gst = p.gst;
                    opt.dataset.stock = p.stock || 0;
                    productSelect.appendChild(opt);
                });

                // Set selected product by name
                productSelect.value = item.product_name;
                

                // Fill other fields
                newRow.querySelector("input[name='rate[]']").value = item.rate;
                newRow.querySelector("input[name='quantity[]']").value = item.quantity;
                newRow.querySelector("input[name='discount[]']").value = item.discount;
                newRow.querySelector("input[name='gst[]']").value = item.gst;

                const stockField = newRow.querySelector("input[name='stock[]']");
                if (stockField) stockField.value = item.stock_quantity || 0;

                tbody.appendChild(newRow);
                updateLineTotal(newRow.querySelector("input[name='quantity[]']"));
            });

            calculateGrandTotal();
        })
        .catch(err => console.error("❌ Fetch error:", err));
}


// ✅ When page loads: show one blank row automatically
document.addEventListener("DOMContentLoaded", function() {
    addRow(); // Add one blank row on page load
});

// PI change event
document.getElementById("pi_id").addEventListener("change", function(e){
    const pi_id = this.value;
    if (pi_id) {
        loadPIData(this); // fetch items from selected PI
    } else {
        // Clear items table and add one blank row
        const tbody = document.querySelector("#itemsTable tbody");
        tbody.querySelectorAll("tr:not(#templateRow)").forEach(tr => tr.remove());
        addRow(); // ensure at least one visible row
        document.querySelector("input[name='bill_no']").value = "";
        document.getElementById("supplierSelect").value = "";
        calculateGrandTotal();
    }
});
document.getElementById("prForm").addEventListener("submit", function(e){
    let invalid = false;
    document.querySelectorAll("#itemsTable tbody tr:not(#templateRow)").forEach(row => {
        const sel = row.querySelector("select[name='product_id[]']");
        if (!sel || !sel.value) invalid = true;
    });
    if (invalid) {
        e.preventDefault();
        alert("Please select a product for all rows!","error");
    }
});
</script>
