<?php
require_once 'config.php';
$title = "Edit Supplier";
include 'header.php';

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$id = (int)$_GET['id'];

// Fetch supplier details
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$supplier) {
    die("Supplier not found!");
}

$message = "";

// Update supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);

    // 🔒 Backend Validation for 10-digit phone
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $message = "⚠️ Phone number must be exactly 10 digits.";
    } else {
        $stmt = $pdo->prepare("UPDATE suppliers 
                               SET supplier_name=?, address=?, phone=?, email=? 
                               WHERE supplier_id=?");
        $stmt->execute([
            $_POST['supplier_name'],
            $_POST['address'],
            $phone,
            $_POST['email'],
            $id
        ]);
        $message = "✅ Supplier updated successfully!";
        
        // Refresh supplier data
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
        $stmt->execute([$id]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<style>
body {
    background: #f5f7fa;
    overflow-x: hidden;
}

/* Card container centered */
.form-container {
    width: 100%;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 50px;
}

/* Card */
.form-card {
    width: 55%;
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(111, 66, 193, 0.3);
    position: relative;
}

/* Heading and buttons inside card */
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

/* Glow heading */
.glow-heading {
    font-size: 2rem;
    font-weight: bold;
    color: #562ba5ff;
    animation: glow-title 2s ease-in-out infinite alternate;
    margin: 0;
}
@keyframes glow-title {
    from { text-shadow: 0 0 5px white; }
    to { text-shadow: 0 0 20px white; }
}

/* Inputs */
.form-control:focus {
    border-color: #6f42c1;
    box-shadow: 0 0 8px rgba(111, 66, 193, 0.6);
    transition: all 0.3s ease-in-out;
}

/* Labels */
.form-label {
    font-weight: 500;
}

/* Buttons inside card */
.btn-glow {
    font-weight: bold;
    border: none;
    border-radius: 5px;
    padding: 8px 18px;
    transition: all 0.3s ease;
}
.btn-glow:hover {
    box-shadow: 0 0 12px rgba(111, 66, 193, 0.7);
    transform: translateY(-2px);
}

/* Toast message */
.toast-message {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #4edb65ff;
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
.fixed-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-bottom: 20px;
}
</style>

<?php if ($message): ?>
<div id="toastMessage" class="toast-message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="fixed-buttons">
    <button type="submit" form="editSupplierForm" class="btn btn-success btn-glow"
        style="background: linear-gradient(135deg, #28a745, #60d394); color: white; border: none;">
        Update
    </button>
    <a href="insert_supplier.php" class="btn btn-secondary btn-glow"
        style="background: linear-gradient(135deg, #6c757d, #adb5bd); color: white; border: none;">
        Back
    </a>
</div>

<div class="form-container">
    <div class="form-card">
        <h2 class="glow-heading mb-4 text-center">Edit Supplier</h2>

        <!-- Form -->
        <form id="editSupplierForm" method="post">
           <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Supplier Name</label>
                    <input type="text" name="supplier_name" class="form-control"
                           value="<?= htmlspecialchars($supplier['supplier_name']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control"><?= htmlspecialchars($supplier['address']) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" maxlength="10" 
                           pattern="\d{10}" 
                           title="Please enter a 10-digit phone number"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10);" 
                           class="form-control"
                           value="<?= htmlspecialchars($supplier['phone']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($supplier['email']) ?>">
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
        setTimeout(() => { toast.classList.remove("show"); }, 3000);
    }
});
</script>

<?php include 'footer.php'; ?>