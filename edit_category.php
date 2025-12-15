<?php
require_once 'config.php';

$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($category_id <= 0) die("Invalid category ID");

// Fetch category
$stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$category) die("Category not found!");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category_name']);
    if ($name === '') {
        $message = "Category name cannot be empty!";
        $isSuccess = false;
    } else {
        $stmt = $pdo->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
        $stmt->execute([$name, $category_id]);
        $message = "Category updated successfully!";
        $isSuccess = true; // flag for green/red toast
        // No redirect here
        $category['category_name'] = $name; // update input value
    }
}

$title = "Edit Category";
include 'header.php';
?>



  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-lg border-0 rounded-4" style="background: linear-gradient(135deg, #e3f2fd, #ffffff);">
        <div class="card-body p-4">
          <h3 class="text-center mb-4 fw-bold text-primary">
            Edit Category
          </h3>

         <div class="container py-5">
  <?php if ($message): ?>
<div id="toastMessage" style="
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: <?= ($isSuccess ?? false) ? '#28a745' : '#f44336' ?>;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    z-index: 9999;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.5s ease;
"><?= htmlspecialchars($message) ?></div>

<script>
const toast = document.getElementById('toastMessage');
setTimeout(() => { 
    toast.style.opacity = '1'; 
    toast.style.transform = 'translateX(0)'; 
}, 100);

setTimeout(() => { 
    toast.style.opacity = '0'; 
    toast.style.transform = 'translateX(100%)'; 
}, 3100);
</script>
<?php endif; ?>

          <form method="post" class="needs-validation" novalidate>
            <div class="mb-4">
              <label class="form-label fw-semibold text-dark">Category Name</label>
              <input type="text" name="category_name" 
                     class="form-control form-control-lg border-0 shadow-sm" 
                     placeholder="Enter category name..." 
                     required
                     value="<?= htmlspecialchars($category['category_name']) ?>"
                     style="border-radius: 12px;">
              <div class="invalid-feedback">Please enter a category name.</div>
            </div>

            <div class="d-flex justify-content-between">
              <a href="category.php" class="btn btn-outline-secondary px-4 py-2 rounded-3">
                <i class="fa fa-arrow-left me-2"></i>Back
              </a>
              <button type="submit" class="btn btn-outline-primary px-4 py-2 rounded-3 shadow">
                <i class="fa fa-save me-2"></i>Update
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Bootstrap form validation
  (() => {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
  })()
</script>

<?php include 'footer.php'; ?>
