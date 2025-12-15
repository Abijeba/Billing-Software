<?php
require_once 'config.php';

$message = "";
$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category_name']);
    if ($name !== "") {
        $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->execute([$name]);

        $message = "Category added successfully!";
        $isSuccess = true;
    } else {
        $message = "Category name cannot be empty.";
        $isSuccess = false;
    }
}

include 'header.php';
?>


<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-lg border-0 rounded-4" style="background: linear-gradient(135deg, #e3f2fd, #ffffff);">
        <div class="card-body p-4">
          <h3 class="text-center mb-4 fw-bold text-primary">
            Add Category
          </h3>

        <?php if ($message): ?>
<div id="toastMessage" style="
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: <?= $isSuccess ? '#28a745' : '#f44336' ?>;
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
              <input type="text" name="category_name" class="form-control form-control-lg border-0 shadow-sm" 
                     placeholder="Enter category name..." required 
                     style="border-radius: 12px;">
              <div class="invalid-feedback">Please enter a category name.</div>
            </div>

            <div class="d-flex justify-content-between">
              <a href="category.php" class="btn btn-outline-secondary px-4 py-2 rounded-3">
                <i class="fa fa-arrow-left me-2"></i>Back
              </a>
              <button type="submit" class="btn btn-outline-primary px-4 py-2 rounded-3 shadow" 
           
                <i class="fa fa-save me-2"></i>Add
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