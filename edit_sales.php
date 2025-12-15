<?php
require_once 'config.php';
$title = "Edit Sales Order";
include 'header.php';

$so_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($so_id <= 0) die("Invalid SO ID");

// Fetch Sales Order
$stmt = $pdo->prepare("SELECT * FROM sales_orders WHERE so_id=?");
$stmt->execute([$so_id]);
$so = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$so) die("Sales Order not found");

// Fetch Items for this Sales Order
$stmt = $pdo->prepare("SELECT * FROM sales_order_items WHERE so_id=?");
$stmt->execute([$so_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Customers
$customers = $pdo->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Products
$products = $pdo->query("SELECT product_name, category, rate, discount, gst FROM products ORDER BY product_name")->fetchAll(PDO::FETCH_ASSOC);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Update Sales Order
        $stmt = $pdo->prepare("UPDATE sales_orders SET so_date=?, delivery_date=?, customer_id=?, customer_bill_no=?, notes=?, tax_mode=?, terms=? WHERE so_id=?");
        $stmt->execute([
            $_POST['so_date'],
            $_POST['delivery_date'],
            $_POST['customer_id'],
            $_POST['customer_bill_no'],
            $_POST['notes'],
            $_POST['tax_mode'],
            $_POST['terms'],
            $so_id
        ]);

        // Delete old items
        $pdo->prepare("DELETE FROM sales_order_items WHERE so_id=?")->execute([$so_id]);

        // Insert new items
        if (!empty($_POST['product_name'])) {
            foreach ($_POST['product_name'] as $i => $prod) {
                if (trim($prod) === '') continue;

                $qty   = $_POST['quantity'][$i];
                $rate  = $_POST['rate'][$i];
                $disc  = $_POST['discount'][$i];
                $gst   = $_POST['gst'][$i];
                $tax_mode = $_POST['tax_mode'];

                $line_total = $qty * $rate;
                $line_total -= $line_total * ($disc/100);
                if ($tax_mode === "Exclusive") $line_total += $line_total * ($gst/100);

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
            }
        }

        $pdo->commit();
        $message = "✅ Sales Order Updated!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "❌ Error: " . $e->getMessage();
    }
}
?>
<div class="container mt-4">
    <h2>Edit Sales Order</h2>
    <?php if($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="row mb-3">
            <div class="col-md-3">
                <label>SO Number</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($so['so_number']) ?>" readonly>
            </div>
            <div class="col-md-3">
                <label>SO Date</label>
                <input type="date" name="so_date" class="form-control" 
                       value="<?= !empty($so['so_date']) ? date('Y-m-d', strtotime($so['so_date'])) : '' ?>" required>
            </div>
            <div class="col-md-3">
                <label>Delivery Date</label>
                <input type="date" name="delivery_date" class="form-control" 
                       value="<?= !empty($so['delivery_date']) ? date('Y-m-d', strtotime($so['delivery_date'])) : '' ?>">
            </div>
            <div class="col-md-3">
                <label>Customer Bill No</label>
                <input type="text" name="customer_bill_no" class="form-control" value="<?= htmlspecialchars($so['customer_bill_no']) ?>">
            </div>
        </div>

        <div class="mb-3 row">
            <div class="col-md-6">
                <label>Customer</label>
                <select name="customer_id" class="form-select" required>
                    <?php foreach($customers as $c): ?>
                        <option value="<?= $c['customer_id'] ?>" <?= ($c['customer_id']==$so['customer_id'])?'selected':'' ?>>
                            <?= htmlspecialchars($c['customer_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label>Tax Mode:</label><br>
            <input type="radio" name="tax_mode" value="Inclusive" <?= ($so['tax_mode']=="Inclusive")?'checked':'' ?>> Inclusive
            <input type="radio" name="tax_mode" value="Exclusive" <?= ($so['tax_mode']=="Exclusive")?'checked':'' ?>> Exclusive
        </div>
<div class="table-responsive">
        <h4>Items</h4>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Category</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Discount (%)</th>
                    <th>GST (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td>
                            <input type="text" name="category[]" class="form-control" value="<?= htmlspecialchars($item['category']) ?>" readonly>
                        </td>
                        <td>
                            <input type="text" name="product_name[]" class="form-control" value="<?= htmlspecialchars($item['product_name']) ?>" readonly>
                        </td>
                        <td><input type="number" name="quantity[]" class="form-control" value="<?= $item['quantity'] ?>"></td>
                        <td><input type="number" step="0.01" name="rate[]" class="form-control" value="<?= $item['rate'] ?>"></td>
                        <td><input type="number" step="0.01" name="discount[]" class="form-control" value="<?= $item['discount'] ?>"></td>
                        <td><input type="number" step="0.01" name="gst[]" class="form-control" value="<?= $item['gst'] ?>"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
</div>
        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control"><?= htmlspecialchars($so['notes']) ?></textarea>
        </div>
        <div class="mb-3">
            <label>Terms & Conditions</label>
            <textarea name="terms" class="form-control"><?= htmlspecialchars($so['terms']) ?></textarea>
        </div>
<div class="button-container">
        <button type="submit" class="btn btn-outline-primary">Update </button>
        <a href="sales_list.php" class="btn btn-outline-secondary">Back</a>
        </div>
    </form>
</div>
<style>
/* 🌸 Centered Violet Heading (No Animation) */
h2 {
  text-align: center;
  text-transform: uppercase;
  font-weight: bold;
  font-size: 30px;
  color: #7b2cbf;
  letter-spacing: 2px;
  margin-bottom: 25px;
}

/* 💎 Card Style for the Form */
form {
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  position: relative;
}

/* 💙 Table Styling */
thead.table-dark th {
        background-color: #003366 !important;
        color: white !important;
        text-align: center;
    }

.table th, .table td {
  vertical-align: middle;
  text-align: center;
}

/* 🎨 Button Area (inside the card, right-aligned) */
.button-container {
  text-align: right;
  margin-top: 15px;
}

/* ✅ Update (Green Gradient) Button */
.btn-outline-primary {
  background: linear-gradient(135deg, #4ade80, #22c55e);
  border: none;
  color: white;
  font-weight: 500;
  transition: 0.3s;
}

.btn-outline-primary:hover {
  background: linear-gradient(135deg, #16a34a, #15803d);
  transform: scale(1.05);
}

/* ⚙️ Back (Gray Gradient) Button */
.btn-outline-secondary {
  background: linear-gradient(135deg, #d1d5db, #9ca3af);
  border: none;
  color: white;
  font-weight: 500;
  transition: 0.3s;
}

.btn-outline-secondary:hover {
  background: linear-gradient(135deg, #6b7280, #4b5563);
  transform: scale(1.05);
}
</style>
