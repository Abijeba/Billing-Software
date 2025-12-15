<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
$title = "New Purchase Order";
include 'header.php';

// Function to generate PO number
function generatePONumber($pdo) {
    $last_po = $pdo->query("SELECT po_number FROM purchase_orders ORDER BY po_id DESC LIMIT 1")->fetchColumn();
    if ($last_po) {
        $num = (int)substr($last_po, strrpos($last_po, "/")+1);
        return "PO/25-26/".str_pad($num+1, 4, "0", STR_PAD_LEFT);
    } else {
        return "PO/25-26/0001";
    }
}

$po_number = generatePONumber($pdo);

$query = "
    SELECT 
        COALESCE(SUM(total_amount), 0) AS total_purchase,
        COALESCE((
            SELECT SUM(amount) 
            FROM payment_out 
            WHERE party_id = suppliers.id
        ), 0) AS total_paid
    FROM suppliers
    LEFT JOIN purchase_orders ON suppliers.id = purchase_orders.supplier_id
    WHERE suppliers.id = :supplier_id
";

$toPayAmount = 0;
if (!empty($_GET['supplier_id'])) {
    $stmt = $pdo->prepare($query);
    $stmt->execute(['supplier_id' => $_GET['supplier_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $toPayAmount = $result['total_purchase'] - $result['total_paid'];
}

$viewMode = isset($_GET['view']) && $_GET['view'] == 1;
$po_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$categories = $pdo->query("SELECT category_name FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_COLUMN);

if ($viewMode && $po_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE po_id = ?");
    $stmt->execute([$po_id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);

    $itemStmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
    $itemStmt->execute([$po_id]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $po = [];
    $items = [];
}

$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("
    SELECT product_name, category, rate, discount, gst, stock_quantity 
    FROM products
")->fetchAll(PDO::FETCH_ASSOC);

$message = "";

// === POST Handler ===

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Validate quantities before saving ---
    // $errors = [];
    // if (!empty($_POST['product_name'])) {
    //     foreach ($_POST['product_name'] as $i => $prod) {
    //         if (trim($prod) === '') continue;
            
    //         $qty = (float)$_POST['quantity'][$i];

    //         // Get stock from DB
    //         $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_name = ?");
    //         $stmt->execute([$prod]);
    //         $stock = (float)$stmt->fetchColumn();

    //         if ($qty > $stock) {
    //             $errors[] = "Quantity for <strong>" . htmlspecialchars($prod) . "</strong> ($qty) exceeds available stock ($stock).";
    //         }
    //     }
    // }

    // // --- If any stock errors, show message and stop saving ---
    // if (!empty($errors)) {
    //     $message = '
    //         <div class="alert alert-danger text-center" style="font-weight:bold; font-size:15px;">
    //             Cannot Save Purchase Order!<br>' . implode('<br>', $errors) . '
    //         </div>
    //     ';
    // } else {
        try {
            $pdo->beginTransaction();

        

        // Insert Purchase Order
        $stmt = $pdo->prepare("INSERT INTO purchase_orders 
    (po_number, po_date, expected_date, supplier_id, notes, tax_mode, terms) 
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([
    $_POST['po_number'],
    $_POST['po_date'],
    $_POST['expected_date'],
    $_POST['supplier_id'],
    $_POST['notes'],
    $_POST['tax_mode'],
    $_POST['terms']
]);


        $po_id = $pdo->lastInsertId();

        if (!empty($_POST['product_name'])) {
            foreach ($_POST['product_name'] as $i => $prod) {
                if(trim($prod) === '') continue;

                $qty = $_POST['quantity'][$i];
                $rate = $_POST['rate'][$i];
                $disc = $_POST['discount'][$i];
                $gst = $_POST['gst'][$i];
                $tax_mode = $_POST['tax_mode'];

                $line_total = $qty * $rate;
                $line_total -= $line_total * ($disc/100);
                if($tax_mode === "Exclusive") $line_total += $line_total * ($gst/100);

                $stmt_item = $pdo->prepare("INSERT INTO purchase_order_items 
                    (po_id, category, product_name, quantity, rate, discount, gst, line_total, tax_mode) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt_item->execute([
                    $po_id,
                    $_POST['category'][$i],
                    $prod,
                    $qty,
                    $rate,
                    $disc,
                    $gst,
                    $line_total,
                    $tax_mode
                ]);
            }
        }

        // === Calculate Grand Total ===
        $grand_total = 0;
        if (!empty($_POST['line_total']) && is_array($_POST['line_total'])) {
            foreach ($_POST['line_total'] as $line_total) {
                $grand_total += floatval($line_total);
            }
        }

        // Update Purchase Order total_amount
        $updateStmt = $pdo->prepare("UPDATE purchase_orders SET total_amount = ? WHERE po_id = ?");
        $updateStmt->execute([$grand_total, $po_id]);

        $pdo->commit();
        $message = "Purchase Order Saved Successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: ".$e->getMessage();
    }
      
    }
    // Regenerate PO number after saving
    $po_number = generatePONumber($pdo);

        
?>

<div class="container mt-4">
    <!-- Centered, animated violet heading -->
    <div class="text-center mb-4">
        <h2 class="fw-bold text-uppercase animate__animated animate__fadeInUp" style="color:#6f42c1;">
             Purchase Order
        </h2>
    </div>

    <!-- Card container -->
   <div class="card shadow-lg border-0" style="
    background: linear-gradient(135deg, #cce7ff, #f4f6f7ff 100%);
    border-radius: 15px;
">
 <div class="card-body">
        <?php if($message): ?>
            <?php
                $isError = stripos($message, 'Cannot Save Purchase Order') !== false;
                $bgColor = $isError ? '#ff9800' : '#28a745';
            ?>
            <div id="saveMessage" style="
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
                font-size: 15px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.5s ease;
            ">
                <?= $message ?>
            </div>

            <script>
                const msgBox = document.getElementById('saveMessage');
                setTimeout(() => { msgBox.style.opacity = '1'; msgBox.style.transform = 'translateX(0)'; }, 100);
                setTimeout(() => { msgBox.style.opacity = '0'; msgBox.style.transform = 'translateX(100%)'; 
                    setTimeout(() => { window.location.href = window.location.pathname; }, 600);
                }, 3000);
            </script>
        <?php endif; ?>

        <form method="post">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label>PO Number</label>
                    <input type="text" name="po_number" class="form-control" value="<?= $po_number ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label>PO Date</label>
                    <input type="date" name="po_date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label>Expected Delivery</label>
                    <input type="date" name="expected_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label>Supplier</label>
                    <select name="supplier_id" class="form-select" id="supplierSelect" onchange="fetchSupplierBalance(this.value)" required>
                        <option value="">-- Select Supplier --</option>
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['supplier_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h5 class="mt-4">Invoice Items</h5>
            <div class="mb-3">
                <label>Tax Mode:</label><br>
                <input type="radio" name="tax_mode" value="Inclusive" checked onchange="updateAllLineTotals()"> Inclusive
                <input type="radio" name="tax_mode" value="Exclusive" onchange="updateAllLineTotals()"> Exclusive
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="itemsTable">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Category</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Rate</th>
                            <th>Discount %</th>
                            <th>GST %</th>
                            <th>Line Total</th>
                            <th>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="addRow()">
                                    <i class="bi bi-plus"></i> +
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="category[]" class="form-select" onchange="updateProductOptions(this)" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="product_name[]" class="form-select" onchange="fillProductDetails(this)" required>
                                    <option value="">-- Select Product --</option>
                                </select>
                                <small class="text-muted stock-info"></small>
                            </td>
                            <td>
                                <input type="number" name="quantity[]" value="1" class="form-control" oninput="updateLineTotal(this)" required>
                                <!-- <small class="text-danger qty-warning"></small> -->
                            </td>
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

            <div class="mb-3">
                <label class="text-dark">Notes</label>
                <textarea name="notes" class="form-control bg-light text-dark" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label class="text-dark">Terms & Conditions</label>
                <textarea name="terms" class="form-control bg-light text-dark" rows="3"></textarea>
            </div>

            <!-- Save / Back buttons -->
            <div class="text-end">
                <button type="submit" class="btn" style="background: linear-gradient(135deg, #28a745, #60d394); 
          color: white; 
          border: none;">
                    <i class="fa fa-save"></i> Save
                </button>
                <a href="purchase_list.php" class="btn" style="background: linear-gradient(135deg, #adb5bd, #6c757d); color: white;">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
</div>

<script>


let products = <?php echo json_encode($products); ?>;

// Add / remove rows
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

// Update products when category changes
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

    row.querySelector('input[name="rate[]"]').value = '';
    row.querySelector('input[name="discount[]"]').value = '';
    row.querySelector('input[name="gst[]"]').value = '';
    row.querySelector('input[name="line_total[]"]').value = '';
}
function fillProductDetails(el){
    let row = el.closest('tr');
    let selectedProductName = el.value;
    let product = products.find(p => p.product_name === selectedProductName);
    let stockInfo = row.querySelector('.stock-info');

    if(product){
        row.dataset.stock = product.stock_quantity || 0; // store stock in row
        row.querySelector('input[name="rate[]"]').value = product.rate || 0;
        row.querySelector('input[name="discount[]"]').value = product.discount || 0;
        row.querySelector('input[name="gst[]"]').value = product.gst || 0;
       

        // show below product dropdown
        stockInfo.textContent = "Current Stock: " + product.stock_quantity;
    } else {
        row.dataset.stock = 0;
        row.querySelector('input[name="rate[]"]').value = 0;
        row.querySelector('input[name="discount[]"]').value = 0;
        row.querySelector('input[name="gst[]"]').value = 0;
        stockInfo.textContent = "";
    }

    let qtyInput = row.querySelector('input[name="quantity[]"]');
    updateLineTotal(qtyInput);
}

// Calculate line total on quantity, rate, discount, gst or tax mode change
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
    let stock = parseFloat(row.dataset.stock || 0);
    // let warning = row.querySelector('.qty-warning');

    // ⚠ Show warning if exceeds stock
    // if (qty > stock && stock > 0) {
    //     warning.textContent = "⚠ Quantity exceeds stock!";
    // } else {
    //     warning.textContent = "";
    // }

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

// Recalculate grand total
function calculateGrandTotal(){
    let total = 0;
    document.querySelectorAll('input[name="line_total[]"]').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('grandTotal').value = total.toFixed(2);
}


// Event listeners for dynamic rows
document.addEventListener('input', function(e){
    if(e.target.matches('input[name="quantity[]"], input[name="rate[]"], input[name="discount[]"], input[name="gst[]"]')){
        updateLineTotal(e.target);
    }
});

document.addEventListener('change', function(e){
    if(e.target.matches('select[name="product_name[]"]')){
        fillProductDetails(e.target);
    } else if(e.target.matches('select[name="tax_mode[]"]')){
        let row = e.target.closest('tr');
        let qtyInput = row.querySelector('input[name="quantity[]"]');
        updateLineTotal(qtyInput);
    }
});

</script>
<style>
    /* Prevent table from moving when stock or warning text appears */
.stock-info,
.qty-warning {
    display: block;
    height: 16px; /* reserve fixed space */
    font-size: 12px;
    line-height: 16px;
    overflow: hidden;
    white-space: nowrap;
}
.text-danger.qty-warning {
    color: #dc3545; /* red for warning */
}
.text-muted.stock-info {
    color: #6c757d; /* gray for stock info */
}
thead.table-dark th {
        background-color: #003366 !important;
        color: white !important;
        text-align: center;}

</style>

</body>
</html>