<?php
require_once 'config.php';
$title = "Customers";
include 'header.php';

$message = "";

// Insert customer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $message = "❌ Phone number must be exactly 10 digits.";
    } else {
        try {
            $check = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE LOWER(customer_name) = LOWER(?)");
            $check->execute([$customer_name]);
            if ($check->fetchColumn() > 0) {
                $message = "⚠️ This customer name already exists!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO customers (customer_name, address, phone, email) VALUES (?, ?, ?, ?)");
                $stmt->execute([$customer_name, $address, $phone, $email]);
                $message = "✅ Customer Added Successfully!";
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

$customers = $pdo->query("SELECT * FROM customers ORDER BY customer_id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- 🌟 Custom Styles -->
<style>
body {
    background-color: #f5f7fa;
}

.customer-container {
    background: #ffffff;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 25px;
    animation: fadeInUp 0.8s ease;
    margin-top: 20px;
}

.customer-heading {
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
}

/* --- Animations --- */
@keyframes textShine {
    0% { background-position: 200% center; }
    100% { background-position: -200% center; }
}
@keyframes fadeSlideIn {
    0% { opacity: 0; transform: translateY(25px); }
    100% { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Buttons */
.btn-custom {
    background: linear-gradient(135deg, #1e90ff, #00bfff);
    color: #fff !important;
    border: none;
    font-weight: 600;
}
.btn-custom:hover {
    transform: scale(1.05);
    box-shadow: 0 0 12px rgba(0, 123, 255, 0.4);
}

/* Table Styling (3 visible rows + scroll) */
.table-responsive {
    max-height: 220px; /* Approx. 3 rows visible */
    overflow-y: auto;
    border-radius: 10px;
    scrollbar-width: thin;
    scrollbar-color: #007bff #f1f1f1;
}
.table-responsive::-webkit-scrollbar {
    width: 6px;
}
.table-responsive::-webkit-scrollbar-thumb {
    background: #007bff;
    border-radius: 6px;
}
.table thead th {
    background-color: #003366 !important;
    color: #fff !important;
    text-align: center;
    position: sticky;
    top: 0;
    z-index: 2;
}
.table tbody tr:hover {
    background-color: #f9f9ff;
    transition: background-color 0.3s ease;
}

.form-control:focus {
    border-color: #1e90ff;
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.4);
}
</style>

<div class="text-center my-4">
    <h2 class="customer-heading fw-bold">Customer Management</h2>
</div>

<div class="customer-container">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="add_customer.php" class="btn btn-sm btn-custom">
            <i class="fa fa-plus"></i> Add Customer
        </a>
        <a href="dashboard.php" class="btn btn-sm btn-custom">
            <i class="fa fa-arrow-left"></i> Back
        </a>
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

    <div class="table-responsive shadow-sm">
        <table class="table table-bordered table-striped align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>Customer Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($customers)): ?>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['customer_name']) ?></td>
                        <td><?= htmlspecialchars($c['address']) ?></td>
                        <td><?= htmlspecialchars($c['phone']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td>
                            <a href="edit_customer.php?id=<?= $c['customer_id'] ?>" class="btn btn-outline-primary btn-sm me-1">
                                <i class="fa fa-edit"></i>
                            </a>
                            <a href="delete_customer.php?id=<?= $c['customer_id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete this customer?')" 
                               class="btn btn-outline-danger btn-sm">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted">No customers found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
