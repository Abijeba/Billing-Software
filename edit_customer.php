<?php
ob_start();
session_start();
require_once 'config.php';
$title = "Edit Customer";
include 'header.php';

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($customer_id <= 0) {
    die("Invalid customer ID");
}

$message = "";
$cleared = false;

// ✅ Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $company_name = trim($_POST['company_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $gstin = trim($_POST['gstin']);
    $pan = trim($_POST['pan']);
    $address = trim($_POST['address']);
    $country = trim($_POST['country']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zipcode = trim($_POST['zipcode']);
    $ship_address = trim($_POST['ship_address']);
    $ship_country = trim($_POST['ship_country']);
    $ship_city = trim($_POST['ship_city']);
    $ship_state = trim($_POST['ship_state']);
    $ship_zipcode = trim($_POST['ship_zipcode']);

    if (empty($customer_name) || empty($gstin) || empty($zipcode)) {
        $message = "<div class='alert alert-warning text-center'>⚠️ Please fill all mandatory fields!</div>";
    } else {
        $stmt = $pdo->prepare("
            UPDATE customers SET
                customer_name=?, company_name=?, phone=?, email=?, gstin=?, pan=?, 
                address=?, country=?, city=?, state=?, zipcode=?,
                ship_address=?, ship_country=?, ship_city=?, ship_state=?, ship_zipcode=?
            WHERE customer_id=?
        ");

        if ($stmt->execute([
            $customer_name, $company_name, $phone, $email, $gstin, $pan,
            $address, $country, $city, $state, $zipcode,
            $ship_address, $ship_country, $ship_city, $ship_state, $ship_zipcode, $customer_id
        ])) {
            $_SESSION['update_msg'] = "✅ Customer updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $customer_id);
            exit;
        } else {
            $message = "<div class='alert alert-danger text-center'>❌ Failed to update customer. Try again.</div>";
        }
    }
}

// ✅ Fetch Customer Details
$stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id=?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customer) die("Customer not found!");

// ✅ Success Toast
if (isset($_SESSION['update_msg'])) {
    $message = $_SESSION['update_msg'];
    unset($_SESSION['update_msg']);
}
?>

<style>
body { background: #f5f7fa; }

/* --- Title Animation --- */
.product-heading {
    font-size: 2.5rem;
    font-weight: 700;
    text-transform: uppercase;
    background: linear-gradient(90deg, #007bff, #00c3ff, #007bff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-size: 200%;
    animation: textShine 4s ease-in-out infinite, fadeSlideIn 1s ease forwards;
    letter-spacing: 1.5px;
    margin: 0 auto;
    display: inline-block;
}
@keyframes textShine { 0%{background-position:200% center;} 100%{background-position:-200% center;} }
@keyframes fadeSlideIn { 0%{opacity:0;transform:translateY(25px);} 100%{opacity:1;transform:translateY(0);} }

/* --- Card --- */
.card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    padding: 25px;
    margin-bottom: 25px;
    transition: transform 0.3s ease;
}
.card:hover { transform: translateY(-5px); }
.card h4 {
    font-weight: 700; color: #4B0082; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;
}
.btn-glow {
    background: linear-gradient(90deg, #6a11cb, #2575fc);
    color: white; border: none; border-radius: 8px; padding: 10px 22px; transition: 0.3s;
}
.btn-glow:hover {
    background: linear-gradient(90deg, #2575fc, #6a11cb);
    box-shadow: 0 0 10px rgba(100,100,255,0.7);
}
.btn-back {
    background: linear-gradient(90deg, #ff416c, #ff4b2b);
    color: white; border: none; border-radius: 8px; padding: 10px 22px; transition: 0.3s;
}
.btn-back:hover {
    background: linear-gradient(90deg, #ff4b2b, #ff416c);
    box-shadow: 0 0 10px rgba(255,100,100,0.7);
}
label { font-weight: 600; color: #333; }
.mandatory { color: red; }

/* Toast */
#toastMessage {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 22px;
    border-radius: 8px;
    color: #fff;
    background: linear-gradient(90deg, #28a745, #34d058);
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.5s ease;
    z-index: 9999;
}

</style>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="product-heading">Edit Customer</h2>
    </div>

    <?php if ($message): ?>
    <div id="toastMessage" style="background-color:#28a745;">
        <?= strip_tags($message) ?>
    </div>
    <script>
    const toast = document.getElementById('toastMessage');
    // Show toast (fade in)
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 100);

    // Hide toast after 3 seconds (fade out)
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
    }, 3100);
    </script>
<?php endif; ?>


    <form method="POST">
        <!-- Contact Information -->
        <div class="card">
            <h4>Contact Information</h4>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Customer Name <span class="mandatory">*</span></label>
                    <input type="text" name="customer_name" class="form-control"
                           value="<?= htmlspecialchars($customer['customer_name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Company Name</label>
                    <input type="text" name="company_name" class="form-control"
                           value="<?= htmlspecialchars($customer['company_name']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Mobile Number</label>
                    <input type="text" name="phone" maxlength="10" pattern="[0-9]{10}"
                           class="form-control"
                           value="<?= htmlspecialchars($customer['phone']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($customer['email']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>GSTIN <span class="mandatory">*</span></label>
                    <input type="text" name="gstin" class="form-control"
                           value="<?= htmlspecialchars($customer['gstin']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>PAN</label>
                    <input type="text" name="pan" class="form-control"
                           value="<?= htmlspecialchars($customer['pan']) ?>">
                </div>
            </div>
        </div>

        <!-- Billing Information -->
        <div class="card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4>Billing Information</h4>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="sameAsBilling">
                    <label class="form-check-label" for="sameAsBilling">Shipping same as Billing</label>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Address</label>
                    <textarea name="address" id="address" class="form-control"><?= htmlspecialchars($customer['address']) ?></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Country</label>
                    <input type="text" name="country" id="country" class="form-control"
                           value="<?= htmlspecialchars($customer['country']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>City</label>
                    <input type="text" name="city" id="city" class="form-control"
                           value="<?= htmlspecialchars($customer['city']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>State</label>
                    <input type="text" name="state" id="state" class="form-control"
                           value="<?= htmlspecialchars($customer['state']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Zipcode</label>
                    <input type="text" name="zipcode" id="zipcode" class="form-control"
                           value="<?= htmlspecialchars($customer['zipcode']) ?>">
                </div>
            </div>
        </div>

        <!-- Shipping Information -->
        <div class="card" id="shippingCard">
            <h4>Shipping Information</h4>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Address</label>
                    <textarea name="ship_address" id="ship_address" class="form-control"><?= htmlspecialchars($customer['ship_address']) ?></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Country</label>
                    <input type="text" name="ship_country" id="ship_country" class="form-control"
                           value="<?= htmlspecialchars($customer['ship_country']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>City</label>
                    <input type="text" name="ship_city" id="ship_city" class="form-control"
                           value="<?= htmlspecialchars($customer['ship_city']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>State</label>
                    <input type="text" name="ship_state" id="ship_state" class="form-control"
                           value="<?= htmlspecialchars($customer['ship_state']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Zipcode</label>
                    <input type="text" name="ship_zipcode" id="ship_zipcode" class="form-control"
                           value="<?= htmlspecialchars($customer['ship_zipcode']) ?>">
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="d-flex justify-content-end gap-3 mb-4">
            <button type="submit" class="btn btn-sm shadow-sm" style="background: linear-gradient(135deg, #28a745, #60d394); 
          color: white; 
          border: none;">Update Customer</button>
            <a href="customer.php" class="btn btn-primary  btn-custom" onclick="window.location.href='customer.php'" style="background: linear-gradient(135deg, #1e90ff, #00bfff); 
          color: white; 
          border: none;">Back</a>
        </div>
    </form>
</div>

<script>
// ✅ Copy Billing → Shipping
document.getElementById('sameAsBilling').addEventListener('change', function() {
    const shippingCard = document.getElementById('shippingCard');
    if (this.checked) {
        document.getElementById('ship_address').value = document.getElementById('address').value;
        document.getElementById('ship_country').value = document.getElementById('country').value;
        document.getElementById('ship_city').value = document.getElementById('city').value;
        document.getElementById('ship_state').value = document.getElementById('state').value;
        document.getElementById('ship_zipcode').value = document.getElementById('zipcode').value;
        shippingCard.style.display = 'none';
    } else {
        shippingCard.style.display = 'block';
    }
});
</script>

<?php include 'footer.php'; ob_end_flush(); ?>
