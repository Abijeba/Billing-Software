<?php
require_once 'config.php';
$title = "Add Product";
include 'header.php';

$categories = $pdo->query("SELECT category_name FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_COLUMN);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $last_id = $pdo->query("SELECT product_id FROM products ORDER BY product_id DESC LIMIT 1")->fetchColumn();
        $product_id = $last_id ? $last_id + 1 : 1;

        $stmt = $pdo->prepare("
            INSERT INTO products 
            (product_id, category_id,category, product_name, hsn_code, rate, gst, stock_quantity, mrp, discount, reorder_level) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $product_id,
             $_POST['category_id'] ?? null,
            $_POST['category'],
            $_POST['product_name'],
            $_POST['hsn_code'],
            $_POST['rate'],
            $_POST['gst'],
            $_POST['stock_quantity'],
            $_POST['mrp'],
            $_POST['discount'],
            $_POST['reorder_level']
        ]);

        $message = "✅ Product added successfully!";
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
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

/* Full page form container */
.form-container {
    width: 100%;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 40px;
}

/* Form card */
.form-card {
    width: 75%;
    background: #fff;
    padding: 45px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(20, 13, 119, 0.3);
}

.fixed-buttons {
    position: absolute;
    top: 140px; /* Adjust based on your header height */
    right: 40px; /* 👈 Positions buttons towards the right side */
    display: flex;
    flex-direction: row;
    gap: 15px;
    z-index: 9999;
}

.fixed-buttons .btn {
    border-radius: 5px;
    font-weight: bold;
    box-shadow: 0 0 10px rgba(20, 13, 119, 0.5);
}

/* Input glow on focus */
.form-control:focus, .form-select:focus {
    border-color: #140d77;
    box-shadow: 0 0 8px rgba(20, 13, 119, 0.6);
    transition: all 0.3s ease-in-out;
}

/* Buttons glow */
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

/* ✅ Toast Message */
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

<!-- ✅ Toast message container -->
<?php if ($message): ?>
<div id="toastMessage" class="toast-message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Fixed Buttons -->
<div class="fixed-buttons">
    <button type="submit" form="addProductForm" class="btn btn-success btn-glow"  style="background: linear-gradient(135deg, #28a745, #60d394); 
          color: white; 
          border: none;">Add Product</button>
    <a href="products.php" class="btn btn-secondary btn-glow"  style="background: linear-gradient(135deg, #6c757d, #adb5bd); 
          color: white; 
          border: none;">Back</a>
</div>

<div class="form-container">
   <div class="form-card">
        <h2 class="glow-heading mb-4 text-center">Add New Product</h2>

        <form id="addProductForm" method="post">
            <div class="row g-4">
                <!-- Row 1 -->
               <div class="col-md-4">
    <label class="form-label">Category</label>
    <select name="category" id="categorySelect" class="form-select" required>
        <option value="">-- Select Category --</option>
        <?php
        $categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();
        foreach ($categories as $cat):
        ?>
            <option value="<?= htmlspecialchars($cat['category_name']) ?>" data-id="<?= $cat['category_id'] ?>">
                <?= htmlspecialchars($cat['category_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="category_id" id="categoryId">
</div>


                <div class="col-md-4">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="product_name" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">HSN Code</label>
                    <input type="text" name="hsn_code" class="form-control">
                </div>

                <!-- Row 2 -->
                <div class="col-md-4">
                    <label class="form-label">Rate</label>
                    <input type="number" step="0.01" name="rate" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">GST (%)</label>
                    <input type="number" step="0.01" name="gst" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Stock Quantity</label>
                    <input type="number" step="0.01" name="stock_quantity" class="form-control">
                </div>

                <!-- Row 3 -->
                <div class="col-md-4">
                    <label class="form-label">MRP</label>
                    <input type="number" step="0.01" name="mrp" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Discount (%)</label>
                    <input type="number" step="0.01" name="discount" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Reorder Level</label>
                    <input type="number" step="0.01" name="reorder_level" class="form-control">
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const toast = document.getElementById("toastMessage");
    if (toast) {
        // Show message
        toast.classList.add("show");
        // Hide after 3 seconds
        setTimeout(() => {
            toast.classList.remove("show");
        }, 3000);
    }
});
document.addEventListener("DOMContentLoaded", function() {
    const select = document.getElementById("categorySelect");
    const hiddenInput = document.getElementById("categoryId");

    select.addEventListener("change", function() {
        const selectedOption = select.options[select.selectedIndex];
        hiddenInput.value = selectedOption.getAttribute("data-id") || "";
    });
});
</script>

<?php include 'footer.php'; ?>