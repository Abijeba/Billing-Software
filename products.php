<?php
require_once 'config.php';
$title = "Products";
include 'header.php';

// --- CSV Import ---
if (!empty($_FILES['product_csv']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['product_csv']['name'], PATHINFO_EXTENSION));
    if ($ext !== "csv") {
        echo "<div class='alert alert-danger'>Only CSV files allowed.</div>";
    } elseif (($handle = fopen($_FILES['product_csv']['tmp_name'], "r")) !== false) {
        $errors = [];
        fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 9) {
                $errors[] = "Row missing columns.";
                continue;
            }
            [$cat, $name, $hsn, $rate, $gst, $stock, $mrp, $disc, $reorder] = array_map('trim', $data);

            $pdo->prepare("INSERT IGNORE INTO categories (category_name) VALUES (?)")->execute([$cat]);

            $pdo->prepare("
                INSERT INTO products 
                (category, product_name, hsn_code, rate, gst, stock_quantity, mrp, discount, reorder_level) 
                VALUES (?,?,?,?,?,?,?,?,?)
            ")->execute([$cat, $name, $hsn, $rate, $gst, $stock, $mrp, $disc, $reorder]);
        }
        fclose($handle);
        echo empty($errors) 
            ? "<div class='alert alert-success'>CSV imported successfully.</div>"
            : "<div class='alert alert-warning'>CSV imported with errors: <br>" . implode("<br>", $errors) . "</div>";
    }
}

function getCurrentStock($pdo, $product_name) {
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_name = ?");
    $stmt->execute([$product_name]);
    return $stmt->fetchColumn();
}

$where = [];
$params = [];
if (!empty($_GET['category'])) {
    $where[] = "p.category = ?";
    $params[] = $_GET['category'];
}
if (!empty($_GET['product_name'])) {
    $where[] = "p.product_name LIKE ?";
    $params[] = "%" . $_GET['product_name'] . "%";
}

$sql = "
    SELECT p.product_id, p.product_name, p.rate, p.discount, p.gst, p.stock_quantity, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category = c.category_name
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY p.product_name ASC";

$products = $pdo->prepare($sql);
$products->execute($params);
$products = $products->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT category_name FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
/* Page Heading Glow Animation */
.glow-heading {
    font-size: 2rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #333;
    text-align: center;
    position: relative;
    animation: glow 2s ease-in-out infinite alternate;
}
@keyframes glow {
    from { text-shadow: 0 0 5px rgba(0, 255, 255, 0.6); }
    to { text-shadow: 0 0 20px rgba(0, 255, 255, 1); }
}

/* Table Scroll & Glow */
.table-wrapper {
    max-height: 320px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0, 255, 255, 0.1);
    animation: fadeIn 1s ease-in;
}
.table thead {
    position: sticky;
    top: 0;
    background: #343a40;
    color: #fff;
}

/* Row hover glow effect */
.table tbody tr:hover {
    background: rgba(0, 255, 255, 0.1);
    transition: background 0.3s ease;
}

/* Button hover glow */
.btn-custom {
    border-radius: 5px;
    transition: all 0.3s ease;
}
.btn-custom:hover {
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.6);
    transform: translateY(-2px);
}

/* Dropdown styling */
.form-select {
    background: #fff !important;
}

/* Scrollbar styling */
.table-wrapper::-webkit-scrollbar {
    width: 8px;
}
.table-wrapper::-webkit-scrollbar-thumb {
    background: rgba(0, 123, 255, 0.5);
    border-radius: 4px;
}


</style>

<div class="text-center my-4">
    <h2 class="product-heading fw-bold">
        Product List
    </h2>
</div>
<style>
    .product-heading {
    font-size: 2.5rem;
    font-weight: 700;
    text-transform: uppercase;
    background: linear-gradient(90deg, #007bff, #00c3ff, #007bff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-size: 200%;
    letter-spacing: 1.5px;
    animation: textShine 4s ease-in-out infinite, fadeSlideIn 1s ease forwards;
    display: inline-block;
    margin: 0 auto;
}

/* --- Gradient shimmer animation --- */
@keyframes textShine {
    0% {
        background-position: 200% center;
    }
    100% {
        background-position: -200% center;
    }
}

/* --- Smooth fade and slide-up effect --- */
@keyframes fadeSlideIn {
    0% {
        opacity: 0;
        transform: translateY(25px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
              <div class="mb-3 d-flex justify-content-between flex-wrap gap-2">
   <a href="add_product.php" class="btn btn-sm shadow-sm" 
   style="background: linear-gradient(135deg, #28a745, #60d394); 
          color: white; 
          border: none;">
    <i class="fa fa-plus"></i> Add Product
</a>
<a href="category.php" class="btn btn-primary btn-sm btn-custom" style="background: linear-gradient(135deg, #1e90ff, #00bfff); 
          color: white; 
          border: none;">Back</a>
            </div>

            <!-- Search/Filter -->
            <form method="get" class="row g-3 mb-4">
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">-- All Categories --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($_GET['category'] ?? '') == $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="product_name" value="<?= htmlspecialchars($_GET['product_name'] ?? '') ?>" class="form-control" placeholder="Product name...">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-custom flex-fill" style="background: linear-gradient(135deg, #1e90ff, #00bfff); 
          color: white; 
          border: none;">Search</button>
                   <a href="products.php" class="btn btn-sm shadow-sm flex-fill" 
   style="background: linear-gradient(135deg, #6c757d, #adb5bd); 
          color: white; 
          border: none;">
    Reset
</a>
</div>
            </form>

            <!-- CSV Upload -->
            <form method="post" enctype="multipart/form-data" class="mb-4">
                <div class="input-group">
                    <input type="file" name="product_csv" accept=".csv" class="form-control" required onchange="showCSVHint(this)">
                    <button type="submit" class="btn btn-info btn-custom" style="background: linear-gradient(135deg, #1e90ff, #00bfff); 
          color: white; 
          border: none;">Import CSV</button>
                </div>
                <small class="text-muted mt-2 d-block" id="csv-hint">
                    Expected CSV: <b>Category, Product Name, HSN Code, Rate, GST, Stock Quantity, MRP, Discount, Reorder Level</b>
                </small>
            </form>

            <!-- Scrollable Table -->
            <div class="table-wrapper">
                <table class="table table-bordered table-striped align-middle mb-0">

                    <thead class="table-dark">
                        <tr>
                            <th>Category</th>
                            <th>Product Name</th>
                            <th>Rate</th>
                            <th>GST (%)</th>
                            <th>Discount (%)</th>
                            <th>Stock</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products): foreach ($products as $p):
                            $current_stock = getCurrentStock($pdo, $p['product_name']);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($p['category_name']) ?></td>
                                <td><?= htmlspecialchars($p['product_name']) ?></td>
                                <td><?= number_format($p['rate'], 2) ?></td>
                                <td><?= number_format($p['gst'], 2) ?></td>
                                <td><?= number_format($p['discount'], 2) ?></td>
                                <td class="<?= ($current_stock <= 0) ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
                                    <?= number_format($current_stock, 2) ?>
                                </td>
                                <td>
                                    <a href="edit_product.php?id=<?= $p['product_id'] ?>" class="btn btn-outline-primary btn-sm btn-custom"><i class="fa fa-edit"></i></a>
                                    <a href="delete_product.php?id=<?= $p['product_id'] ?>" class="btn btn-outline-danger btn-sm btn-custom" onclick="return confirm('Are you sure?');"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center text-muted">No products found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
function showCSVHint(input) {
    const hint = document.getElementById("csv-hint");
    hint.style.display = input.files.length ? "block" : "none";
}
</script>

<?php include 'footer.php'; ?>