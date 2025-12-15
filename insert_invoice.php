<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
$title = "New Purchase Invoice";
include 'header.php';

function generatePINumber($pdo) {
    $last_pi = $pdo->query("SELECT pi_number FROM purchase_invoices ORDER BY pi_id DESC LIMIT 1")->fetchColumn();
    if ($last_pi) {
        $num = (int)substr($last_pi, strrpos($last_pi, "/")+1);
        return "PI/25-26/" . str_pad($num + 1, 4, "0", STR_PAD_LEFT);
    } else {
        return "PI/25-26/0001";
    }
}

$pi_number = generatePINumber($pdo);
$selected_po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
$po = null;
$items = [];

if ($selected_po_id) {
    $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE po_id = ?");
    $stmt->execute([$selected_po_id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);

    $itemStmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
    $itemStmt->execute([$selected_po_id]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($po) {
        $selected_supplier_id = $po['supplier_id'];
    }
}

$selected_supplier_id = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
$viewMode = isset($_GET['view']) && $_GET['view'] == 1;
$pi_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$categories = $pdo->query("SELECT category_name FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_COLUMN);

if ($viewMode && $pi_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM purchase_invoices WHERE pi_id = ?");
    $stmt->execute([$pi_id]);
    $pi = $stmt->fetch(PDO::FETCH_ASSOC);

    $itemStmt = $pdo->prepare("SELECT * FROM purchase_invoice_items WHERE pi_id = ?");
    $itemStmt->execute([$pi_id]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $pi = [];
    $items = [];
}

$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT product_name, category, rate, discount, gst, stock_quantity FROM products")->fetchAll(PDO::FETCH_ASSOC);

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // --- Insert Purchase Invoice ---
        $stmt = $pdo->prepare("INSERT INTO purchase_invoices 
            (pi_number, pi_date, due_date, supplier_id, supplier_bill_no, notes, tax_mode, terms) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['pi_number'],
            $_POST['pi_date'],
            $_POST['due_date'],
            $_POST['supplier_id'],
            $_POST['supplier_bill_no'],
            $_POST['notes'],
            $_POST['tax_mode'],
            $_POST['terms']
        ]);

        $pi_id = $pdo->lastInsertId();
        $grandTotal = 0;

        foreach ($_POST['product_name'] as $i => $prod) {
            if (trim($prod) === '') continue;

            $qty = $_POST['quantity'][$i];
            $rate = $_POST['rate'][$i];
            $disc = $_POST['discount'][$i];
            $gst = $_POST['gst'][$i];
            $tax_mode = $_POST['tax_mode'];

            $line_total = $qty * $rate;
            $line_total -= $line_total * ($disc / 100);
            if ($tax_mode === "Exclusive") $line_total += $line_total * ($gst / 100);

            $stmt_item = $pdo->prepare("INSERT INTO purchase_invoice_items 
                (pi_id, category, product_name, quantity, rate, discount, gst, line_total, tax_mode) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_item->execute([
                $pi_id,
                $_POST['category'][$i],
                $prod,
                $qty,
                $rate,
                $disc,
                $gst,
                $line_total,
                $tax_mode
            ]);

            // --- Update stock quantity ---
            $updateStock = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_name = ?");
            $updateStock->execute([$prod]);
            $currentStock = $updateStock->fetchColumn();

            if ($currentStock !== false) {
                $currentStock = max(0, floatval($currentStock));
                $newStock = $currentStock + floatval($qty);

                $stmtStockUpdate = $pdo->prepare("
                    UPDATE products 
                    SET stock_quantity = ? 
                    WHERE product_name = ?
                ");
                $stmtStockUpdate->execute([$newStock, $prod]);
            }

            $grandTotal += $line_total;
        }

        $updateTotal = $pdo->prepare("UPDATE purchase_invoices SET grand_total = ? WHERE pi_id = ?");
        $updateTotal->execute([$grandTotal, $pi_id]);

        $pdo->commit();
        $pi_number = generatePINumber($pdo);
        $message = "Purchase Invoice saved successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <!-- 🔹 Animated Heading -->
    <div class="text-center my-4">
        <h2 class="fw-bold text-uppercase animate__animated animate__fadeInUp" 
            style="color:#0d47a1; letter-spacing:1px;">
            PURCHASE INVOICE
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

    <!-- 🟢 Gradient Blue Card -->
    <div class="card shadow-lg border-0" 
        style="background: linear-gradient(35deg, #cce7ff, #f4f6f7ff 100%); border-radius: 15px;">
        <div class="card-body">
            <!-- 🟢 Form Starts -->
            <form method="post" id="invoiceForm">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>PI Number</label>
                        <input type="text" name="pi_number" class="form-control" value="<?= $pi_number ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label>Invoice Date</label>
                        <input type="date" name="pi_date" class="form-control" required>
                        <div class="pi-date-error text-danger" style="font-size: 12px; display:none; margin-top: 2px;">
                            Please select invoice date
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Supplier Bill No</label>
                        <input type="text" name="supplier_bill_no" class="form-control" required>
                        <div class="supplier-bill-error text-danger" style="font-size: 12px; display:none; margin-top: 2px;">
                            Please enter supplier bill no
                        </div>
                    </div>
                </div>

                <div class="mb-3 row">
                    <?php
                    $purchase_orders = $pdo->query("
                        SELECT po.po_id, po.po_number, s.supplier_name 
                        FROM purchase_orders po
                        JOIN suppliers s ON po.supplier_id = s.supplier_id
                        ORDER BY po.po_id DESC
                    ")->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div class="col-md-3">
                        <label>PO Number</label>
                        <select name="po_id" id="po_id" class="form-select">
                            <option value="">-- Select PO --</option>
                            <?php foreach($purchase_orders as $po): ?>
                                <option value="<?= $po['po_id'] ?>"><?= htmlspecialchars($po['po_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach($suppliers as $s): ?>
                                <option value="<?= $s['supplier_id'] ?>" <?= (isset($selected_supplier_id) && $s['supplier_id'] == $selected_supplier_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['supplier_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="supplier-error text-danger" style="font-size: 12px; display:none; margin-top: 2px;">
                            Please select supplier
                        </div>
                    </div>
                </div>

                <h5 class="mt-4 fw-bold text-primary">Invoice Items</h5>
                <div class="mb-3">
                    <label>Tax Mode:</label><br>
                    <input type="radio" name="tax_mode" value="Inclusive" checked onchange="updateAllLineTotals()"> Inclusive
                    <input type="radio" name="tax_mode" value="Exclusive" onchange="updateAllLineTotals()"> Exclusive
                </div>
<div class="table-responsive">
                <table class="table table-bordered table-striped" id="itemsTable">
                    <thead class="text-center table-dark">
                        <tr>
                            <th>Category</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Rate</th>
                            <th>Discount %</th>
                            <th>GST %</th>
                            <th>Line Total</th>
                            <th>
                                <button type="button" class="btn btn-outline-light btn-sm" onclick="addRow()">➕</button>
                            </th>
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
                                <div class="category-error text-danger" style="font-size: 12px; display:none; margin-top: 2px;">
                                    Please select category
                                </div>
                            </td>
                            <td>
                                <select name="product_name[]" class="form-select" onchange="fillProductDetails(this)">
                                    <option value="">-- Select Product --</option>
                                </select>
                                <small class="text-muted stock-info" style="font-size: 12px;"></small>
                                <div class="product-error text-danger" style="font-size: 12px; display:none; margin-top: 2px;">
                                    Please select product
                                </div>
                            </td>
                            <td>
                                <input type="number" name="quantity[]" value="1" class="form-control" oninput="validateQuantity(this)" required>
                                <div class="qty-error text-danger" style="font-size: 12px; display:none; margin-top: 2px;">
                                    Please enter valid quantity
                                </div>
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
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label>Terms & Conditions</label>
                    <textarea name="terms" class="form-control" rows="3"></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-gradient" style="background: linear-gradient(135deg, #28a745, #60d394); color: white; border: none;">
                        <i class="fa fa-save"></i> Save
                    </button>
                    <a href="purchase_invoice_list.php" class="btn btn-secondary" style="background: linear-gradient(135deg, #adb5bd, #6c757d); color: white;">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Gradient Button Style -->
<style>
.btn-gradient {
    background: linear-gradient(45deg, #007bff, #00c6ff);
    color: white !important;
    border: none;
    transition: all 0.3s ease;
}
.btn-gradient:hover {
    background: linear-gradient(45deg, #0056b3, #0099cc);
    transform: scale(1.03);
}
thead.table-dark th {
    background-color: #003366 !important;
    color: white !important;
    text-align: center;
}
</style>

<script>
let products = <?php echo json_encode($products); ?>;

// ✅ Populate product dropdowns when page loads
document.addEventListener("DOMContentLoaded", function() {
    const productSelects = document.querySelectorAll('select[name="product_name[]"]');
    productSelects.forEach(select => {
        select.innerHTML = '<option value="">-- Select Product --</option>';
        products.forEach(p => {
            const option = document.createElement('option');
            option.value = p.product_name;
            option.text = p.product_name;
            select.appendChild(option);
        });
    });
    
    // Add template row
    const templateRow = document.querySelector("#itemsTable tbody tr").cloneNode(true);
    templateRow.id = "templateRow";
    templateRow.style.display = "none";
    document.querySelector("#itemsTable tbody").prepend(templateRow);
});

// ✅ Form Validation Functions
function validateForm() {
    let isValid = true;
    let hasValidRow = false;
    
    // Clear previous error messages
    document.querySelectorAll('.category-error, .product-error, .qty-error, .pi-date-error, .supplier-bill-error, .supplier-error').forEach(el => {
        el.style.display = 'none';
    });
    
    // Validate basic form fields
    const piDate = document.querySelector('input[name="pi_date"]');
    const supplierBillNo = document.querySelector('input[name="supplier_bill_no"]');
    const supplierId = document.querySelector('select[name="supplier_id"]');
    
    // Validate Invoice Date
    if (!piDate.value) {
        document.querySelector('.pi-date-error').style.display = 'block';
        isValid = false;
    }
    
    // Validate Supplier Bill No
    if (!supplierBillNo.value.trim()) {
        document.querySelector('.supplier-bill-error').style.display = 'block';
        isValid = false;
    }
    
    // Validate Supplier
    if (!supplierId.value) {
        document.querySelector('.supplier-error').style.display = 'block';
        isValid = false;
    }
    
    // Check all rows
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    
    rows.forEach((row) => {
        if (row.id === 'templateRow' || row.style.display === 'none') return;
        
        const categorySelect = row.querySelector('select[name="category[]"]');
        const productSelect = row.querySelector('select[name="product_name[]"]');
        const quantityInput = row.querySelector('input[name="quantity[]"]');
        
        // Check if row has any data
        const hasCategory = categorySelect.value.trim() !== '';
        const hasProduct = productSelect.value.trim() !== '';
        const hasQuantity = quantityInput.value && parseFloat(quantityInput.value) > 0;
        
        let rowHasData = hasCategory || hasProduct || hasQuantity;
        let rowHasErrors = false;
        
        if (rowHasData) {
            // Validate category
            if (!hasCategory) {
                row.querySelector('.category-error').style.display = 'block';
                isValid = false;
                rowHasErrors = true;
            }
            
            // Validate product
            if (!hasProduct) {
                row.querySelector('.product-error').style.display = 'block';
                isValid = false;
                rowHasErrors = true;
            }
            
            // Validate quantity
            if (!hasQuantity) {
                row.querySelector('.qty-error').style.display = 'block';
                isValid = false;
                rowHasErrors = true;
            }
            
            // If all required fields are filled and no errors, mark as valid row
            if (hasCategory && hasProduct && hasQuantity && !rowHasErrors) {
                hasValidRow = true;
            }
        }
    });
    
    // Check if we have at least one valid row
    if (!hasValidRow && isValid) {
        // No rows with data at all - show error on first row
        const firstRow = document.querySelector('#itemsTable tbody tr:not(#templateRow)');
        if (firstRow) {
            firstRow.querySelector('.category-error').style.display = 'block';
            firstRow.querySelector('.product-error').style.display = 'block';
            firstRow.querySelector('.qty-error').style.display = 'block';
        }
        isValid = false;
    }
    
    return isValid;
}

// ✅ Form submit validation
document.getElementById("invoiceForm").addEventListener("submit", function(e) {
    if (!validateForm()) {
        e.preventDefault();
        return;
    }
});

// ✅ Clear errors when user inputs data
document.querySelector('input[name="pi_date"]').addEventListener('change', function() {
    if (this.value) {
        document.querySelector('.pi-date-error').style.display = 'none';
    }
});

document.querySelector('input[name="supplier_bill_no"]').addEventListener('input', function() {
    if (this.value.trim()) {
        document.querySelector('.supplier-bill-error').style.display = 'none';
    }
});

document.querySelector('select[name="supplier_id"]').addEventListener('change', function() {
    if (this.value) {
        document.querySelector('.supplier-error').style.display = 'none';
    }
});

// ✅ Existing Functions
function selectPO(po_id) {
    if (po_id) {
        window.location.href = window.location.pathname + "?po_id=" + po_id;
    } else {
        window.location.href = window.location.pathname;
    }
}

// PO change event
document.getElementById("po_id").addEventListener("change", function(e){
    e.preventDefault();
    if (this.value) loadPOData(this.value);
    else window.location.href = window.location.pathname;
});

function addRow() {
    const table = document.querySelector('#itemsTable tbody');
    const template = document.getElementById("templateRow");
    const newRow = template.cloneNode(true);
    newRow.id = "";
    newRow.style.display = "";
    
    // Clear values and errors
    newRow.querySelector('select[name="category[]"]').value = '';
    newRow.querySelector('select[name="product_name[]"]').value = '';
    newRow.querySelector('select[name="product_name[]"]').innerHTML = '<option value="">-- Select Product --</option>';
    newRow.querySelector('input[name="quantity[]"]').value = '1';
    newRow.querySelector('input[name="rate[]"]').value = '';
    newRow.querySelector('input[name="discount[]"]').value = '';
    newRow.querySelector('input[name="gst[]"]').value = '';
    newRow.querySelector('input[name="line_total[]"]').value = '';
    newRow.querySelector('.stock-info').textContent = '';
    
    // Clear error displays
    newRow.querySelector('.category-error').style.display = 'none';
    newRow.querySelector('.product-error').style.display = 'none';
    newRow.querySelector('.qty-error').style.display = 'none';
    
    // Populate products
    const productSelect = newRow.querySelector('select[name="product_name[]"]');
    products.forEach(p => {
        const option = document.createElement('option');
        option.value = p.product_name;
        option.text = p.product_name;
        productSelect.appendChild(option);
    });
    
    table.appendChild(newRow);
}

function removeRow(btn) {
    const row = btn.closest('tr');
    const table = document.querySelector('#itemsTable tbody');
    const visibleRows = table.querySelectorAll('tr:not(#templateRow)');
    if (visibleRows.length > 1) {
        row.remove();
        calculateGrandTotal();
    }
}

function updateProductOptions(el) {
    const row = el.closest('tr');
    const productSelect = row.querySelector('select[name="product_name[]"]');
    productSelect.innerHTML = '<option value="">-- Select Product --</option>';
    
    // Clear category error when category is selected
    row.querySelector('.category-error').style.display = 'none';
    
    products.forEach(p => {
        if (p.category === el.value) {
            const opt = document.createElement('option');
            opt.value = p.product_name;
            opt.text = p.product_name;
            productSelect.appendChild(opt);
        }
    });
}

function fillProductDetails(el) {
    const row = el.closest('tr');
    const product = products.find(p => p.product_name === el.value);
    const stockInfo = row.querySelector('.stock-info');
    
    // Clear product error when product is selected
    row.querySelector('.product-error').style.display = 'none';
    
    if (product) {
        row.querySelector('input[name="rate[]"]').value = product.rate || 0;
        row.querySelector('input[name="discount[]"]').value = product.discount || 0;
        row.querySelector('input[name="gst[]"]').value = product.gst || 0;
        stockInfo.textContent = "Stock: " + (product.stock_quantity || 0);
        stockInfo.style.color = (product.stock_quantity <= 0) ? "red" : "#6c757d";
        row.querySelector('input[name="quantity[]"]').setAttribute('data-stock', product.stock_quantity);
    } else {
        stockInfo.textContent = "";
    }
    updateLineTotal(row.querySelector('input[name="quantity[]"]'));
}

function getTaxMode() {
    const mode = document.querySelector('input[name="tax_mode"]:checked');
    return mode ? mode.value : 'Exclusive';
}

function updateLineTotal(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
    const rate = parseFloat(row.querySelector('input[name="rate[]"]').value) || 0;
    const disc = parseFloat(row.querySelector('input[name="discount[]"]').value) || 0;
    const gst = parseFloat(row.querySelector('input[name="gst[]"]').value) || 0;
    let total = qty * rate;
    total -= total * (disc / 100);
    if (getTaxMode() === 'Exclusive') total += total * (gst / 100);
    row.querySelector('input[name="line_total[]"]').value = total.toFixed(2);
    calculateGrandTotal();
}

function updateAllLineTotals() {
    document.querySelectorAll('input[name="quantity[]"]').forEach(input => updateLineTotal(input));
}

function calculateGrandTotal() {
    let total = 0;
    document.querySelectorAll('input[name="line_total[]"]').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('grandTotal').value = total.toFixed(2);
}

function validateQuantity(el) {
    const stock = parseFloat(el.getAttribute('data-stock')) || 0;
    const qty = parseFloat(el.value) || 0;
    const errorMsg = el.parentNode.querySelector('.qty-error');
    
    // Clear quantity error when valid quantity is entered
    if (qty > 0) {
        errorMsg.style.display = 'none';
    } else {
        errorMsg.textContent = 'Please enter valid quantity';
        errorMsg.style.display = 'block';
    }
    
    updateLineTotal(el);
}

function loadPOData(po_id) {
    if (!po_id) return;
    fetch("load_po.php?po_id=" + po_id)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            document.querySelector("select[name='supplier_id']").value = data.po.supplier_id;
            const tbody = document.querySelector("#itemsTable tbody");
            const template = document.getElementById("templateRow");
            tbody.innerHTML = "";
            tbody.appendChild(template);
            data.items.forEach(item => {
                const row = template.cloneNode(true);
                row.id = "";
                row.style.display = "";
                const categorySelect = row.querySelector("select[name='category[]']");
                categorySelect.value = item.category;
                updateProductOptions(categorySelect);
                const productSelect = row.querySelector("select[name='product_name[]']");
                productSelect.value = item.product_name;
                row.querySelector("input[name='quantity[]']").value = item.quantity;
                row.querySelector("input[name='rate[]']").value = item.rate;
                row.querySelector("input[name='discount[]']").value = item.discount;
                row.querySelector("input[name='gst[]']").value = item.gst;
                const stockInfo = row.querySelector(".stock-info");
                const product = products.find(p => p.product_name === item.product_name);
                if (product) {
                    stockInfo.textContent = "Stock: " + (product.stock_quantity || 0);
                    stockInfo.style.color = (product.stock_quantity <= 0) ? "red" : "#6c757d";
                    row.querySelector("input[name='quantity[]']").setAttribute("data-stock", product.stock_quantity);
                }
                tbody.appendChild(row);
            });
            updateAllLineTotals();
        });
}
</script>