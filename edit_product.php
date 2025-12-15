<?php
ob_start();
session_start();
require_once 'config.php';
$title = "Edit Product";
include 'header.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ✅ Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id=?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found!");
}

// ✅ Fetch categories
$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);


$message = "";

// ✅ Update process
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $stmt = $pdo->prepare("
    UPDATE products SET 
    category_id=?, category=?, product_name=?, hsn_code=?, rate=?, gst=?, stock_quantity=?, mrp=?, discount=?, reorder_level=?
    WHERE product_id=?
");
;
    if ($stmt->execute([
         $_POST['category_id'],
        $_POST['category'],
        $_POST['product_name'],
        $_POST['hsn_code'],
        $_POST['rate'],
        $_POST['gst'],
        $_POST['stock_quantity'],
        $_POST['mrp'],
        $_POST['discount'],
        $_POST['reorder_level'],
        $product_id
    ])) {
        // ✅ Redirect to clear old POST data & show success message
        header("Location: edit_product.php?id=$product_id&updated=1");
        exit;
    } else {
        $message = "❌ Error updating product.";
    }
}

// ✅ If redirected after update, show success message & clear fields
if (isset($_GET['updated'])) {
    $message = "✅ Product updated successfully!";

    // Empty the form fields
    $product = [
        'category' => '',
        'product_name' => '',
        'hsn_code' => '',
        'rate' => '',
        'gst' => '',
        'stock_quantity' => '',
        'mrp' => '',
        'discount' => '',
        'reorder_level' => ''
    ];
}
?>

<style>
body {
    background: #f5f7fa;
    overflow: hidden;
}

/* Page heading glow */
.glow-heading {
    font-size: 2.2rem;
    font-weight: bold;
    color: #140d77;
    animation: glow-title 2s ease-in-out infinite alternate;
}
@keyframes glow-title {
    from { text-shadow: 0 0 5px white; }
    to { text-shadow: 0 0 20px white; }
}

/* Container */
.form-container {
    width: 100%;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 40px;
}

/* Card */
.form-card {
    width: 75%;
    background: #fff;
    padding: 45px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(20, 13, 119, 0.3);
}

/* Fixed Buttons */
.fixed-buttons {
    position: absolute;
    top: 140px;
    right: 40px;
    display: flex;
    gap: 15px;
    z-index: 9999;
}

.fixed-buttons .btn {
    border-radius: 5px;
    font-weight: bold;
    box-shadow: 0 0 10px rgba(20, 13, 119, 0.5);
}

/* Input focus */
.form-control:focus, .form-select:focus {
    border-color: #140d77;
    box-shadow: 0 0 8px rgba(20, 13, 119, 0.6);
    transition: all 0.3s ease-in-out;
}

/* Button glow */
.btn-glow {
    border-radius: 5px;
    font-weight: bold;
    transition: all 0.3s ease;
}
.btn-glow:hover {
    box-shadow: 0 0 15px rgba(20, 13, 119, 0.8);
    transform: translateY(-2px);
}

/* Labels */
.form-label {
    font-weight: 500;
}

/* Toast */
.toast-message {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #140d77;
    color: #fff;
    padding: 12px 25px;
    border-radius: 6px;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    opacity: 0;
    transform: translateY(-20px);
    transition: opacity 0.5s ease, transform 0.5s ease;
    z-index: 20000;
}
.toast-message.show {
    opacity: 1;
    transform: translateY(0);
}
</style>

<!-- ✅ Toast Message -->
<?php if ($message): ?>
<div id="toastMessage" class="toast-message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Fixed Buttons -->
<div class="fixed-buttons">
    <button type="submit" form="editProductForm" class="btn btn-success btn-glow" style="background: linear-gradient(135deg, #28a745, #60d394); 
          color: white; 
          border: none;">Update Product</button>
    <a href="products.php" class="btn btn-secondary btn-glow"  style="background: linear-gradient(135deg, #6c757d, #adb5bd); 
          color: white; 
          border: none;">Back</a>
</div>

<div class="form-container">
    <div class="form-card">
        <h2 class="glow-heading mb-4 text-center">Edit Product</h2>

        <form id="editProductForm" method="post">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category" id="categorySelect" class="form-select" required>
    <option value="">-- Select Category --</option>
    <?php foreach ($categories as $cat): ?>
        <option 
            value="<?= htmlspecialchars($cat['category_name']) ?>" 
            data-id="<?= $cat['category_id'] ?>" 
            <?= $cat['category_name'] == $product['category'] ? "selected" : "" ?>>
            <?= htmlspecialchars($cat['category_name']) ?>
        </option>
    <?php endforeach; ?>
</select>

<!-- Hidden input for category_id -->
<input type="hidden" name="category_id" id="categoryId" value="<?= htmlspecialchars($product['category_id'] ?? '') ?>">

                </div>

                <div class="col-md-4">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">HSN Code</label>
                    <input type="text" name="hsn_code" class="form-control" value="<?= htmlspecialchars($product['hsn_code']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Rate</label>
                    <input type="number" step="0.01" name="rate" class="form-control" value="<?= htmlspecialchars($product['rate']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">GST (%)</label>
                    <input type="number" step="0.01" name="gst" class="form-control" value="<?= htmlspecialchars($product['gst']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Stock Quantity</label>
                    <input type="number" step="0.01" name="stock_quantity" class="form-control" value="<?= htmlspecialchars($product['stock_quantity']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">MRP</label>
                    <input type="number" step="0.01" name="mrp" class="form-control" value="<?= htmlspecialchars($product['mrp']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Discount (%)</label>
                    <input type="number" step="0.01" name="discount" class="form-control" value="<?= htmlspecialchars($product['discount']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Reorder Level</label>
                    <input type="number" step="0.01" name="reorder_level" class="form-control" value="<?= htmlspecialchars($product['reorder_level']) ?>">
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const toast = document.getElementById("toastMessage");

    if (toast) {
        toast.classList.add("show");
        setTimeout(() => {
            toast.classList.remove("show");
        }, 3000);
    }
});
document.addEventListener("DOMContentLoaded", function() {
    const select = document.getElementById("categorySelect");
    const hidden = document.getElementById("categoryId");

    // Set current category_id when editing
    if (select && hidden) {
        const selected = select.options[select.selectedIndex];
        hidden.value = selected.getAttribute("data-id") || "";

        // Update hidden input whenever category changes
        select.addEventListener("change", function() {
            const selected = select.options[select.selectedIndex];
            hidden.value = selected.getAttribute("data-id") || "";
        });
    }
});
</script>

<?php include 'footer.php'; ?>