<?php
require_once 'config.php';
$title = "Categories";
include 'header.php';

$msg = "";

// --- Handle CSV Import ---
if (isset($_POST['import']) && !empty($_FILES['file']['tmp_name'])) {
    $fileName = $_FILES['file']['name'];
    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $fileMime = mime_content_type($fileTmp);

    if ($fileExt !== "csv") {
        $msg = "Only CSV files are allowed!";
    } elseif (($handle = fopen($fileTmp, "r")) !== false) {
        $row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $row++;
            if ($row == 1) continue; // skip header

            $category_name = trim($data[0]);
            if ($category_name) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ?");
                $stmt->execute([$category_name]);
                if ($stmt->fetchColumn() == 0) {
                    $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)")
                        ->execute([$category_name]);
                }
            }
        }
        fclose($handle);
        $msg = "Categories imported successfully!";
    } else {
        $msg = "Failed to read CSV file.";
    }
}

// --- Handle Search ---
$searchTerm = $_GET['search'] ?? '';
$where = $searchTerm ? "WHERE category_name LIKE ?" : "";
$params = $searchTerm ? ["%{$searchTerm}%"] : [];

$categories = $pdo->prepare("SELECT * FROM categories {$where} ORDER BY category_name ASC");
$categories->execute($params);
$categories = $categories->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Custom Styles and Animations -->
<style>
/* Card style */
.category-container {
    animation: fadeInUp 0.8s ease;
    background: #ffffff;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 25px;
}

/* Table Scroll */
.table-responsive {
    max-height: 300px; /* Around 5 rows visible */
    overflow-y: auto;
    border-radius: 10px;
}

/* Table Hover Effect */
.table tbody tr:hover {
    background-color: #f9f9ff;
    transition: background-color 0.3s ease;
}

/* Subtle fade-in animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Buttons hover glow */
.btn:hover {
    box-shadow: 0 0 10px rgba(0, 123, 255, 0.4);
    transform: scale(1.05);
    transition: 0.2s;
}
</style>
<div class="text-center my-4">
    <h2 class="category-heading fw-bold">
        Category Management
    </h2>
</div>
 
<style>
.category-heading {
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
thead.table-dark th {
        background-color: #003366 !important;
        color: white !important;
        text-align: center;
    }



</style>

<div class="mb-3 d-flex align-items-center justify-content-end gap-2 flex-wrap">
    
    <?php if ($msg): ?>
        <div class="custom-alert alert-dismissible fade show mb-0" role="alert">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <a href="add_category.php" class="btn btn-sm shadow-sm" 
   style="background: linear-gradient(135deg, #1e90ff, #00bfff); 
          color: white; 
          border: none;">
    <i class="fa fa-plus"></i> Add Category
</a>
<a href="dashboard.php" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #1e90ff, #00bfff); 
          color: white; 
          border: none;">
        <i class="fa fa-arrow-left"></i> Back
    </a>
</div>

    <!-- Search Form -->
    <form method="get" class="mb-3 d-flex justify-content-center align-items-center gap-2">
        <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" 
               placeholder="Search category..." class="form-control w-50 shadow-sm">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i></button>
        <a href="category.php" class="btn btn-secondary btn-sm"><i class="fa fa-refresh"></i></a>
    </form>

    <!-- Upload CSV Form -->
    <div class="mb-4 text-center">
        <form method="post" enctype="multipart/form-data" class="d-inline-flex align-items-center gap-2">
            <input type="file" name="file" accept=".csv" class="form-control form-control-sm" required>
            <button type="submit" name="import" class="btn btn-info btn-sm" style="background: linear-gradient(135deg, #1e90ff, #00bfff); 
          color: white; 
          border: none;">
                <i class="fa fa-upload"></i> Import CSV
            </button>
        </form>
        <div class="small text-muted mt-1">
            Upload CSV with one column: <b>Category Name</b>
        </div>
    </div>

    <!-- Category Table -->
    <div class="table-responsive shadow-sm">
        <table class="table table-bordered table-striped align-middle text-center ">
            <thead class="table-dark">
                <tr>
                    <th>Category Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= htmlspecialchars($cat['category_name']) ?></td>
                            <td>
                                <a href="edit_category.php?id=<?= $cat['category_id'] ?>" 
                                   class="btn btn-outline-primary btn-sm me-1">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="delete_category.php?id=<?= $cat['category_id'] ?>" 
                                   onclick="return confirm('Delete this category?')" 
                                   class="btn btn-outline-danger btn-sm">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2">No categories found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>