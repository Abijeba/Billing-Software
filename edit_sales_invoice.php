<?php
require_once 'config.php';
$title = "Edit Sales Invoice";
include 'header.php';

$si_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($si_id <= 0) {
    die("Invalid Invoice ID");
}

// Fetch invoice header
$stmt = $pdo->prepare("SELECT * FROM sales_invoices WHERE si_id = ?");
$stmt->execute([$si_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$invoice) {
    die("Invoice not found");
}

// Fetch invoice items
$stmt_items = $pdo->prepare("SELECT * FROM sales_invoice_items WHERE si_id = ?");
$stmt_items->execute([$si_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Fetch customers
$customers = $pdo->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name")->fetchAll(PDO::FETCH_ASSOC);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $tax_mode = $_POST['tax_mode'] ?? ($invoice['tax_mode'] ?? 'Inclusive');

        // Update invoice header
        $stmt = $pdo->prepare("UPDATE sales_invoices 
            SET si_date=?, due_date=?, customer_id=?, customer_bill_no=?, notes=?, terms=?, tax_mode=? 
            WHERE si_id=?");
        $stmt->execute([
            $_POST['si_date'],
            $_POST['due_date'],
            $_POST['customer_id'],
            $_POST['customer_bill_no'],
            $_POST['notes'],
            $_POST['terms'],
            $tax_mode,
            $si_id
        ]);

        // Delete old items
        $pdo->prepare("DELETE FROM sales_invoice_items WHERE si_id=?")->execute([$si_id]);

        // Insert items
        if (!empty($_POST['product_name'])) {
            foreach ($_POST['product_name'] as $i => $prod) {
                if (trim($prod) === '') continue;

                $stmt_item = $pdo->prepare("INSERT INTO sales_invoice_items 
                    (si_id, category, product_name, quantity, rate, discount, gst, line_total, tax_mode) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_item->execute([
                    $si_id,
                    $_POST['category'][$i],
                    $prod,
                    $_POST['quantity'][$i],
                    $_POST['rate'][$i],
                    $_POST['discount'][$i],
                    $_POST['gst'][$i],
                    $_POST['line_total'][$i],
                    $tax_mode
                ]);
            }
        }

        $pdo->commit();
        $message = "✅ Sales Invoice updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "❌ Error: " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <h2>Edit Sales Invoice</h2>

    <?php if ($message): ?>
        <?php
            $isError = str_contains($message, 'Error');
            $bgColor = $isError ? '#f44336' : '#140d77 ';
        ?>
        <div id="toastMessage" style="
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: <?= $bgColor ?>;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            z-index: 9999;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.5s ease;
        ">
            <?= htmlspecialchars($message) ?>
        </div>
        <script>
            const toast = document.getElementById('toastMessage');
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            }, 100);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 600);
            }, 3000);
        </script>
    <?php endif; ?>

    <form method="post">
        <div class="row mb-3">
            <div class="col-md-3">
                <label>Invoice Number</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($invoice['si_number']) ?>" readonly>
            </div>
            <div class="col-md-3">
                <label>Invoice Date</label>
                <input type="date" name="si_date" value="<?= $invoice['si_date'] ?>" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label>Due Date</label>
                <input type="date" name="due_date" value="<?= $invoice['due_date'] ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label>Customer Bill No</label>
                <input type="text" name="customer_bill_no" value="<?= htmlspecialchars($invoice['customer_bill_no']) ?>" class="form-control">
            </div>
        </div>

        <div class="mb-3 row">
            <div class="col-md-6">
                <label>Customer</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['customer_id'] ?>" <?= $c['customer_id'] == $invoice['customer_id'] ? "selected" : "" ?>>
                            <?= htmlspecialchars($c['customer_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label>Tax Mode:</label><br>
            <input type="radio" name="tax_mode" value="Inclusive" <?= ($invoice['tax_mode'] ?? 'Inclusive') == 'Inclusive' ? 'checked' : '' ?>> Inclusive
            <input type="radio" name="tax_mode" value="Exclusive" <?= ($invoice['tax_mode'] ?? '') == 'Exclusive' ? 'checked' : '' ?>> Exclusive
        </div>

        <h5>Invoice Items</h5>
<table class="table table-bordered" id="itemsTable">
  <thead>
    <tr>
      <th>Category</th>
      <th>Product</th>
      <th>Quantity</th>
      <th>Rate</th>
      <th>Discount %</th>
      <th>GST %</th>
      <th>Line Total</th>
      <th><button type="button" class="btn btn-outline-success btn-sm" onclick="addRow()">➕</button></th>
    </tr>
  </thead>
  <tbody>
    <?php
    $categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
    $products = $pdo->query("SELECT product_id, product_name, category_id, rate, gst FROM products ORDER BY product_name")->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php foreach ($items as $item): ?>
      <tr>
        <td>
          <select name="category[]" class="form-select" onchange="filterProducts(this)">
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat['category_name']) ?>" <?= $cat['category_name'] == $item['category'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <select name="product_name[]" class="form-select" onchange="setProductDetails(this)">
            <option value="">-- Select Product --</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= htmlspecialchars($p['product_name']) ?>" data-category="<?= $p['category_id'] ?>" data-rate="<?= $p['rate'] ?>" data-gst="<?= $p['gst'] ?>"
                <?= $p['product_name'] == $item['product_name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['product_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input type="number" name="quantity[]" value="<?= $item['quantity'] ?>" class="form-control" oninput="updateLineTotal(this)"></td>
        <td><input type="number" name="rate[]" value="<?= $item['rate'] ?>" class="form-control" oninput="updateLineTotal(this)"></td>
        <td><input type="number" name="discount[]" value="<?= $item['discount'] ?>" class="form-control" oninput="updateLineTotal(this)"></td>
        <td><input type="number" name="gst[]" value="<?= $item['gst'] ?>" class="form-control" oninput="updateLineTotal(this)"></td>
        <td><input type="number" name="line_total[]" value="<?= $item['line_total'] ?>" class="form-control" readonly></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(this)">❌</button></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control"><?= htmlspecialchars($invoice['notes']) ?></textarea>
        </div>
        <div class="mb-3">
            <label>Terms & Conditions</label>
            <textarea name="terms" class="form-control"><?= htmlspecialchars($invoice['terms']) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-update">Update Invoice</button>
            <a href="sales_invoice_list.php" class="btn btn-back">Back</a>
        </div>
    </form>
</div>

<style>
h2 {
  text-align: center;
  text-transform: uppercase;
  color: #6f42c1;
  margin: 20px 0;
  font-weight: bold;
  font-size: 30px;
}
.container form {
  background: linear-gradient(135deg, #e3f2fd, #bbdefb);
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
thead th {
  background-color: #003366 !important;
  color: #fff !important;
  text-align: center;
}
tbody td { text-align: center; vertical-align: middle; }
.btn-update {
  background: linear-gradient(90deg, #56ccf2, #2f80ed);
  color: white !important;
  border: none;
  font-weight: 600;
}

.btn-back {
  background: linear-gradient(90deg, #56ccf2, #2f80ed);
  color: white !important;
  border: none;
  font-weight: 600;
}
.form-actions { text-align: right; margin-top: 20px; }
/* ✅ Make Invoice Items Table Mobile Responsive */
@media (max-width: 768px) {
  #itemsTable {
    display: block;
    width: 100%;
    overflow-x: auto;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 1rem;
  }

  #itemsTable th,
  #itemsTable td {
    font-size: 13px;
    padding: 6px 8px;
  }

  #itemsTable input {
    width: 80px;
    text-align: center;
  }

  /* Optional scrollbar styling */
  #itemsTable::-webkit-scrollbar {
    height: 6px;
  }
  #itemsTable::-webkit-scrollbar-thumb {
    background-color: #ccc;
    border-radius: 3px;
  }

  /* Make add/remove buttons smaller on mobile */
  #itemsTable button {
    padding: 2px 6px;
    font-size: 13px;
  }
}

</style>

<script>
const allProducts = <?= json_encode($products) ?>;
const allCategories = <?= json_encode($categories) ?>;

// 🔹 Filter products by selected category
function filterProducts(select) {
  const row = select.closest("tr");
  const categoryName = select.value;
  const productSelect = row.querySelector('select[name="product_name[]"]');
  productSelect.innerHTML = '<option value="">-- Select Product --</option>';

  // Match by category name
  allProducts.forEach(p => {
    const cat = allCategories.find(c => c.category_id == p.category_id);
    if (cat && cat.category_name === categoryName) {
      const opt = document.createElement('option');
      opt.value = p.product_name;
      opt.textContent = p.product_name;
      opt.dataset.rate = p.rate;
      opt.dataset.gst = p.gst;
      productSelect.appendChild(opt);
    }
  });
}

// 🔹 Auto-fill rate and GST when product selected
function setProductDetails(select) {
  const row = select.closest("tr");
  const selected = select.options[select.selectedIndex];
  if (selected) {
    row.querySelector('input[name="rate[]"]').value = selected.dataset.rate || 0;
    row.querySelector('input[name="gst[]"]').value = selected.dataset.gst || 0;
    updateLineTotal(row.querySelector('input[name="rate[]"]'));
  }
}

// 🔹 Add new row dynamically (fixed version)
function addRow() {
  const table = document.querySelector("#itemsTable tbody");
  const newRow = document.createElement("tr");

  let catOptions = `<option value="">-- Select Category --</option>`;
  allCategories.forEach(c => {
    catOptions += `<option value="${c.category_name}">${c.category_name}</option>`;
  });

  newRow.innerHTML = `
    <td><select name="category[]" class="form-select" onchange="filterProducts(this)">${catOptions}</select></td>
    <td><select name="product_name[]" class="form-select" onchange="setProductDetails(this)">
        <option value="">-- Select Product --</option>
    </select></td>
    <td><input type="number" name="quantity[]" value="1" min="1" class="form-control" oninput="updateLineTotal(this)"></td>
    <td><input type="number" name="rate[]" value="0" class="form-control" oninput="updateLineTotal(this)"></td>
    <td><input type="number" name="discount[]" value="0" class="form-control" oninput="updateLineTotal(this)"></td>
    <td><input type="number" name="gst[]" value="0" class="form-control" oninput="updateLineTotal(this)"></td>
    <td><input type="number" name="line_total[]" value="0" class="form-control" readonly></td>
    <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(this)">❌</button></td>
  `;
  table.appendChild(newRow);
}

// 🔹 Remove a row
function removeRow(btn) {
  btn.closest("tr").remove();
}

// 🔹 Recalculate line total
function updateLineTotal(input) {
  const row = input.closest('tr');
  const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
  const rate = parseFloat(row.querySelector('input[name="rate[]"]').value) || 0;
  const discount = parseFloat(row.querySelector('input[name="discount[]"]').value) || 0;
  const gst = parseFloat(row.querySelector('input[name="gst[]"]').value) || 0;

  let total = qty * rate;
  total -= total * (discount / 100);
  total += total * (gst / 100);

  row.querySelector('input[name="line_total[]"]').value = total.toFixed(2);
}
</script>
