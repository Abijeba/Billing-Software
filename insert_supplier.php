<?php
require_once 'config.php';
$title = "Suppliers";
include 'header.php';

$message = "";

// Insert supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_name = trim($_POST['supplier_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $message = "❌ Phone number must be exactly 10 digits.";
    } else {
        try {
            // Check if name already exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM suppliers WHERE LOWER(supplier_name) = LOWER(?)");
            $check->execute([$supplier_name]);
            if ($check->fetchColumn() > 0) {
                $message = "⚠️ This supplier name already exists!";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO suppliers (supplier_name, address, phone, email)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$supplier_name, $address, $phone, $email]);
                $message = "✅ Supplier Added Successfully!";
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY supplier_id ASC")
                 ->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
body {
    background: #f5f7fa;
}

/* Glow heading */
.glow-heading {
    font-size: 1.8rem;
    font-weight: bold;
    color: #140d77; /* violet */
    animation: glow-title 2s ease-in-out infinite alternate;
}
@keyframes glow-title {
    from { text-shadow: 0 0 5px white; }
    to { text-shadow: 0 0 20px white; }
}

/* Layout */
.split-container {
    display: flex;
    gap: 20px;
    align-items: stretch;
    flex-wrap: nowrap;
}

.table-card {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(106, 13, 173, 0.2);
    flex: 2;
    min-width: 600px;
    overflow: hidden;
}

.form-card {
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(106, 13, 173, 0.3);
    flex: 0.6;
    min-width: 250px;
    max-width: 320px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}

.table th, .table td {
    padding: 10px;
    text-align: left;
    vertical-align: middle;
}
.table-hover tbody tr:hover {
    background-color: rgba(106, 13, 173, 0.1);
}

.form-control:focus {
    border-color: #140d77;
    box-shadow: 0 0 8px rgba(20, 13, 119, 0.6);
}

.btn-glow {
    border-radius: 5px;
    font-weight: bold;
    transition: all 0.3s ease;
}
.btn-glow:hover {
    box-shadow: 0 0 15px rgba(106, 13, 173, 0.8);
    transform: translateY(-2px);
}

.alert {
    font-weight: bold;
}

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
@keyframes textShine {
    0% { background-position: 200% center; }
    100% { background-position: -200% center; }
}
@keyframes fadeSlideIn {
    0% { opacity: 0; transform: translateY(25px); }
    100% { opacity: 1; transform: translateY(0); }
}

@media (max-width: 992px) {
    .split-container {
        flex-wrap: wrap;
    }
    .table-card, .form-card {
        min-width: 100%;
        flex: 1 1 100%;
    }
}

/* Duplicate name message */
#name_error {
    color: red;
    font-size: 14px;
    margin-top: 5px;
    display: block;
}

/* Limit table height to show approx 3 rows with scroll */
.table-responsive {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
}

/* Fixed table header */
.table thead {
    position: sticky;
    top: 0;
    z-index: 100;
}

.table thead th {
    background-color: #003366 !important;
    color: white !important;
    text-align: center;
    position: sticky;
    top: 0;
    border-bottom: 2px solid #dee2e6;
}

</style>

<div class="text-center my-4">
    <h2 class="product-heading fw-bold">
        Supplier Management
    </h2>
</div>

<?php 
// Check for URL message parameter
$url_message = $_GET['message'] ?? '';
if ($message || $url_message): 
    $display_message = $message ?: $url_message;
    
    // Determine background color based on message content
    if (strpos($display_message, '❌') !== false || strpos($display_message, 'Error') !== false || strpos($display_message, 'Cannot delete') !== false) {
        $bgColor = '#dc3545'; // Red for errors
    } elseif (strpos($display_message, '✅') !== false || strpos($display_message, 'successfully') !== false) {
        $bgColor = '#28a745'; // Green for success
    } elseif (strpos($display_message, '⚠️') !== false) {
        $bgColor = '#ffc107'; // Yellow for warnings
        $textColor = '#000'; // Black text for yellow background
    } else {
        $bgColor = '#17a2b8'; // Blue for info
        $textColor = '#fff';
    }
?>
    <div id="toastMessage" style="
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: <?= $bgColor ?>;
        color: <?= $textColor ?? '#fff' ?>;
        padding: 12px 20px;
        border-radius: 6px;
        font-weight: bold;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 9999;
        font-size: 14px;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.5s ease;
    ">
        <?= htmlspecialchars($display_message) ?>
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
        }, 4000);
    </script>
<?php endif; ?>

<div class="split-container">
    <!-- Existing Suppliers Table -->
    <div class="table-card">
        <h4 class="glow-heading mb-3">Existing Suppliers</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($suppliers)): ?>
                    <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['supplier_name']) ?></td>
                        <td><?= htmlspecialchars($s['address']) ?></td>
                        <td><?= htmlspecialchars($s['phone']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td>
                            <a href="edit_supplier.php?id=<?= $s['supplier_id'] ?>" class="btn btn-outline-primary btn-sm btn-glow me-1">
                                <i class="fa fa-edit"></i>
                            </a>
                            <a href="delete_supplier.php?id=<?= $s['supplier_id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete this supplier?')" 
                               class="btn btn-outline-danger btn-sm btn-glow">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted">No suppliers found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Supplier Entry Form -->
    <div class="form-card">
        <h4 class="glow-heading mb-3">Add Supplier</h4>
        <form method="post" onsubmit="return validatePhone()" id="supplierForm">
            <div class="mb-3">
                <label class="form-label">Supplier Name</label>
                <input type="text" name="supplier_name" id="supplier_name" class="form-control" required autocomplete="off">
                <span id="name_error"></span>
            </div>
           <div class="mb-3">
    <label class="form-label">Phone</label>
    <input type="text" name="phone" id="phone" class="form-control"
           maxlength="10" pattern="[0-9]{10}" required
           placeholder="Enter 10-digit phone no"
           onkeypress="return isNumberKey(event)"
           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
</div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control">
            </div>
            <button type="submit" class="btn btn-success btn-glow" id="saveBtn" style="background: linear-gradient(135deg, #28a745, #60d394); 
          color: white; 
          border: none;">Save Supplier</button>
            <a href="dashboard.php" class="btn btn-secondary btn-glow" style="background: linear-gradient(135deg, #6c757d, #adb5bd); 
          color: white; 
          border: none;">Back</a>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Validate phone number
function validatePhone() {
    const phone = document.getElementById("phone").value;
    if (!/^[0-9]{10}$/.test(phone)) {
        alert("Phone number must be exactly 10 digits.");
        return false;
    }
    return true;
}

// Duplicate name check
$(document).ready(function(){
    $('#supplier_name').on('keyup', function(){
        var name = $(this).val().trim();

        if(name !== ''){
            $.ajax({
                url: 'check_supplier.php',
                type: 'POST',
                data: { supplier_name: name },
                success: function(response){
                    if(response === 'exists'){
                        $('#name_error').text('⚠️ This supplier name already exists');
                        $('#saveBtn').prop('disabled', true);
                    } else {
                        $('#name_error').text('');
                        $('#saveBtn').prop('disabled', false);
                    }
                }
            });
        } else {
            $('#name_error').text('');
            $('#saveBtn').prop('disabled', false);
        }
    });
});
// Allow only numbers in phone field
function isNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
}
</script>

<?php include 'footer.php'; ?>