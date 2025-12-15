
<?php
require_once 'config.php';
$title = "New Sales Order";
include 'header.php';

// --- Auto-generate SO Number ---
function generateSONumber($pdo) {
    // Financial year calculation (April–March)
    $year1 = date("y");
    $month = date("n"); // 1–12

    if ($month >= 4) {
        // April or later → next year
        $year2 = $year1 + 1;
    } else {
        // Jan–Mar → move FY back
        $year2 = $year1;
        $year1 = $year1 - 1;
    }
    $fy = $year1 . "-" . $year2; // e.g. 24-25

    // Get last SO number for this FY
    $stmt = $pdo->prepare("SELECT so_number FROM sales_orders 
                           WHERE so_number LIKE ? 
                           ORDER BY so_id DESC LIMIT 1");
    $stmt->execute(["SO/$fy/%"]);
    $last_so = $stmt->fetchColumn();

    if ($last_so) {
        preg_match("/(\d{4})$/", $last_so, $matches);
        $num = isset($matches[1]) ? (int)$matches[1] : 0;
        $next = $num + 1;
    } else {
        $next = 1;
    }

    return "SO/$fy/" . str_pad($next, 4, "0", STR_PAD_LEFT);
}

$so_number = generateSONumber($pdo);

$selected_customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$viewMode = isset($_GET['view']) && $_GET['view'] == 1;
$so_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$categories = $pdo->query("SELECT category_name FROM categories ORDER BY category_name")
                  ->fetchAll(PDO::FETCH_COLUMN);

if ($viewMode && $so_id > 0) {
    // Fetch Sales Order details
    $stmt = $pdo->prepare("SELECT * FROM sales_orders WHERE so_id = ?");
    $stmt->execute([$so_id]);
    $so = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch Sales Order items
    $itemStmt = $pdo->prepare("SELECT * FROM sales_order_items WHERE so_id = ?");
    $itemStmt->execute([$so_id]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $so = [];
    $items = [];
}

// --- Fetch Customers & Products ---
$customers = $pdo->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name")
                 ->fetchAll(PDO::FETCH_ASSOC);

$products = $pdo->query("SELECT product_name, category, rate, discount, gst, stock_quantity FROM products")
                ->fetchAll(PDO::FETCH_ASSOC);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Insert Sales Order
        $stmt = $pdo->prepare("INSERT INTO sales_orders 
            (so_number, so_date, delivery_date, customer_id, notes, tax_mode, terms) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['so_number'],
            $_POST['so_date'],
            $_POST['delivery_date'],
            $_POST['customer_id'],
            $_POST['notes'],
            $_POST['tax_mode'],
            $_POST['terms']
        ]);
        $so_id = $pdo->lastInsertId();
        $grandTotal = 0;

        // Insert Items
        if (!empty($_POST['product_name'])) {
            foreach ($_POST['product_name'] as $i => $prod) {
                if (trim($prod) === '') continue;

                $qty  = $_POST['quantity'][$i];
                $rate = $_POST['rate'][$i];
                $disc = $_POST['discount'][$i];
                $gst  = $_POST['gst'][$i];
                $tax_mode = $_POST['tax_mode'];

                $line_total = $qty * $rate;
                $line_total -= $line_total * ($disc/100);
                if ($tax_mode === "Exclusive") {
                    $line_total += $line_total * ($gst/100);
                }

                $stmt_item = $pdo->prepare("INSERT INTO sales_order_items 
                    (so_id, category, product_name, quantity, rate, discount, gst, line_total, tax_mode) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_item->execute([
                    $so_id,
                    $_POST['category'][$i],
                    $prod,
                    $qty,
                    $rate,
                    $disc,
                    $gst,
                    $line_total,
                    $tax_mode
                ]);

                /* Update stock (reduce quantity)
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_name = ?")
                    ->execute([$qty, $prod]);
                    // Add to grand total*/
                $grandTotal += $line_total;
            }
        }
        // Update the sales order with grand total
        $updateTotal = $pdo->prepare("UPDATE sales_orders SET grand_total = ? WHERE so_id = ?");
        $updateTotal->execute([$grandTotal, $so_id]);


        $pdo->commit();

        // Regenerate SO number after save
        $so_number = generateSONumber($pdo);

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <h2>Create Sales Order</h2>
    <?php if($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="row mb-3">
            <div class="col-md-3">
                <label>SO Number</label>
                <input type="text" name="so_number" class="form-control" value="<?= $so_number ?>" readonly>
            </div>
            <div class="col-md-3">
                <label>SO Date</label>
                <input type="date" name="so_date" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label>Delivery Date</label>
                <input type="date" name="delivery_date" class="form-control">
            </div>
            <div class="col-md-3">
                <label>Customer Bill No</label>
                <input type="text" name="customer_bill_no" class="form-control">
            </div>
        </div>

        <div class="mb-3 row">
            <div class="col-md-6">
                <label>Customer</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach($customers as $c): ?>
                        <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Invoice Items Table -->
        <h5 class="mt-4">Invoice Items</h5>
        <div class="mb-3">
            <label>Tax Mode:</label><br>
            <input type="radio" name="tax_mode" value="Inclusive" checked onchange="updateAllLineTotals()"> Inclusive
            <input type="radio" name="tax_mode" value="Exclusive" onchange="updateAllLineTotals()"> Exclusive
        </div>
<div class="table-responsive">
        <div class="table-dark">
            <table class="table table-bordered table-striped table-hover custom-blue" id="itemsTable">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Rate</th>
                        <th>Discount %</th>
                        <th>GST %</th>
                        <th>Line Total</th>
                        <th><button type="button" class="btn btn-outline-success btn-sm" onclick="addRow()">+</button></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="category[]" class="form-select" onchange="updateProductOptions(this)">
                                <option value="">-- Select Category --</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="product_name[]" class="form-select" onchange="fillProductDetails(this)">
                                <option value="">-- Select Product --</option>
                            </select>
                        </td>
                        <td><input type="number" name="quantity[]" value="1" class="form-control" oninput="updateLineTotal(this)"></td>
                        <td><input type="number" name="rate[]" class="form-control" readonly></td>
                        <td><input type="number" name="discount[]" class="form-control" readonly></td>
                        <td><input type="number" name="gst[]" class="form-control" readonly></td>
                        <td><input type="number" name="line_total[]" class="form-control" readonly></td>
                        <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(this)">x</button></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="text-end">Grand Total:</th>
                        <th><input type="number" id="grandTotal" class="form-control" readonly></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
</div>

        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label>Terms & Conditions</label>
            <textarea name="terms" class="form-control" rows="3"></textarea>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-outline-success">
            <i class="fa fa-save"></i> Save
          </button>
          <a href="sales_list.php" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Back
          </a>
        </div>
    </form>
</div>

<script>
// Copy all JavaScript functions from purchase_entry.php (addRow, removeRow, updateProductOptions, fillProductDetails, updateLineTotal, calculateGrandTotal, etc.)
let products = <?php echo json_encode($products); ?>;

function addRow(){
    let table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    let newRow = table.rows[0].cloneNode(true);
    newRow.querySelectorAll('input').forEach(input => input.value='');
    newRow.querySelectorAll('select').forEach(select => select.selectedIndex=0);
    table.appendChild(newRow);
}

function removeRow(btn){
    let row = btn.closest('tr');
    let table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    if(table.rows.length>1) row.remove();
    calculateGrandTotal();
}

function updateProductOptions(el){
    let row = el.closest('tr');
    let productSelect = row.querySelector('select[name="product_name[]"]');
    productSelect.innerHTML = '<option value="">-- Select Product --</option>';
    products.forEach(p => {
        if(p.category === el.value){
            let opt = document.createElement('option');
            opt.value = p.product_name;
            opt.text = p.product_name;
            opt.dataset.rate = p.rate;
            opt.dataset.gst = p.gst;
            opt.dataset.discount = p.discount;
            productSelect.appendChild(opt);
        }
    });
}

function fillProductDetails(el){
    let row = el.closest('tr');
    let product = products.find(p => p.product_name === el.value);
    if(product){
        row.querySelector('input[name="rate[]"]').value = product.rate || 0;
        row.querySelector('input[name="discount[]"]').value = product.discount || 0;
        row.querySelector('input[name="gst[]"]').value = product.gst || 0;
    }
    updateLineTotal(row.querySelector('input[name="quantity[]"]'));
}

function getTaxMode() {
    let mode = document.querySelector('input[name="tax_mode"]:checked');
    return mode ? mode.value : 'Exclusive';
}

function updateLineTotal(el) {
    let row = el.closest('tr');
    let qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
    let rate = parseFloat(row.querySelector('input[name="rate[]"]').value) || 0;
    let disc = parseFloat(row.querySelector('input[name="discount[]"]').value) || 0;
    let gst = parseFloat(row.querySelector('input[name="gst[]"]').value) || 0;

    let total = qty * rate;
    total -= total * (disc / 100);
    if (getTaxMode() === 'Exclusive') {
        total += total * (gst / 100);
    }
    row.querySelector('input[name="line_total[]"]').value = total.toFixed(2);
    calculateGrandTotal();
}

function updateAllLineTotals() {
    document.querySelectorAll('input[name="quantity[]"]').forEach(input => {
        updateLineTotal(input);
    });
}

function calculateGrandTotal(){
    let total = 0;
    document.querySelectorAll('input[name="line_total[]"]').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('grandTotal').value = total.toFixed(2);
}
function loadPOData(po_id) {
    if (!po_id) return;

    fetch("get_po_details.php?po_id=" + po_id)
    .then(res => res.json())
    .then(data => {
        if (!data.po) return;

        // Fill Supplier & Bill No
        document.querySelector("select[name='supplier_id']").value = data.po.supplier_id;
        document.querySelector("input[name='supplier_bill_no']").value = data.po.supplier_bill_no || "";

        // Tax Mode
        if (data.po.tax_mode === "Inclusive") {
            document.querySelector("input[name='tax_mode'][value='Inclusive']").checked = true;
        } else {
            document.querySelector("input[name='tax_mode'][value='Exclusive']").checked = true;
        }

        // Clear existing rows
        let tbody = document.querySelector("#itemsTable tbody");
        tbody.innerHTML = "";

        // Insert PO Items into Invoice table
        data.items.forEach(item => {
            let row = document.createElement("tr");
            row.innerHTML = `
                <td><input type="text" name="category[]" class="form-control" value="${item.category}" readonly></td>
                <td><input type="text" name="product_name[]" class="form-control" value="${item.product_name}" readonly></td>
                <td><input type="number" name="quantity[]" class="form-control" value="${item.quantity}" oninput="updateLineTotal(this)"></td>
                <td><input type="number" name="rate[]" class="form-control" value="${item.rate}"></td>
                <td><input type="number" name="discount[]" class="form-control" value="${item.discount}"></td>
                <td><input type="number" name="gst[]" class="form-control" value="${item.gst}"></td>
                <td><input type="number" name="line_total[]" class="form-control" readonly></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(this)">x</button></td>
            `;
            tbody.appendChild(row);

            // update totals
            updateLineTotal(row.querySelector("input[name='quantity[]']"));
        });
        calculateGrandTotal();
    })
    .catch(err => console.error(err));
}
</script>
<style>
.container h2 {
    width: 100%;
    text-align: center;
    text-transform: uppercase;
    color: #6f42c1;
    margin: 20px 0;
    font-weight: bold;
    font-size: 30px;
}

/* --- Card Style for Form --- */
form {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* --- Table Header --- */
.table thead th {
    background-color: #002b80 !important;
    color: white;
    text-align: center;
    vertical-align: middle;
}

/* --- Center Table Data --- */
.table td, .table th {
    text-align: center;
    vertical-align: middle;
}

/* --- Save Button: Green Gradient --- */
.btn-outline-success {
    background: linear-gradient(90deg, #00c851, #007e33);
    color: white;
    border: none;
    transition: 0.3s ease;
}
.btn-outline-success:hover {
    opacity: 0.9;
}

/* --- Back Button: Gray Gradient --- */
.btn-outline-secondary {
    background: linear-gradient(90deg, #9e9e9e, #616161);
    color: white;
    border: none;
    transition: 0.3s ease;
}
.btn-outline-secondary:hover {
    opacity: 0.9;
}


</style>


</body>
</html>
