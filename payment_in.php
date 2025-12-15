<?php
require_once 'config.php';
include 'header.php';

// --- Auto-generate Payment-In Number ---
$last_num = false;
$prefix = 'PI';

try {
    $last_num = $pdo->query("SELECT pi_number FROM payments_in ORDER BY id DESC LIMIT 1")->fetchColumn();
} catch (PDOException $e) {
    try {
        $last_num = $pdo->query("SELECT pm_number FROM payments_in ORDER BY id DESC LIMIT 1")->fetchColumn();
        $prefix = 'PM';
    } catch (PDOException $e2) {
        $last_num = false;
    }
}

if ($last_num) {
    $num = (int) substr($last_num, strrpos($last_num, "/") + 1);
    $pi_number = $prefix . "/25-26/" . str_pad($num + 1, 4, "0", STR_PAD_LEFT);
} else {
    $pi_number = $prefix . "/25-26/0001";
}

// --- Get customers ---
$customers = $pdo->query("SELECT customer_id AS id, customer_name AS name FROM customers ORDER BY customer_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$ledgerCustomers = $pdo->query("SELECT id, ledger_name AS name FROM ledgers WHERE type='Customer' ORDER BY ledger_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$parties = array_merge($customers, $ledgerCustomers);

// --- Auto-select customer if coming from Sales Invoice List ---
$selected_customer = $_GET['customer_id'] ?? '';
?>

<style>
body {
  background-color: #f7f9fc;
}

/* ===== HEADING ===== */
.page-heading {
  font-size: 2.5rem;
  font-weight: 700;
  text-transform: uppercase;
  background: linear-gradient(90deg, #140d77, #4a3aff, #140d77);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-size: 200%;
  letter-spacing: 1.5px;
  animation: textShine 4s ease-in-out infinite, fadeSlideIn 1s ease forwards;
  display: inline-block;
}

@keyframes textShine {
  0% { background-position: 0% center; }
  100% { background-position: 200% center; }
}
@keyframes fadeSlideIn {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ===== CARD STYLE ===== */
.card-modern {
  background: linear-gradient(135deg, #e3f2fd, #ffffff);
  border: none;
  border-radius: 16px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.15);
  animation: fadeInUp 0.9s ease;
  transition: all 0.3s ease;
}
.card-modern:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 22px rgba(0,0,0,0.25);
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ===== BUTTONS ===== */
.btn-green {
  background: linear-gradient(90deg, #2ecc71, #27ae60, #2ecc71);
  background-size: 300%;
  color: #fff;
  border: none;
  font-weight: 600;
  padding: 6px 18px;
  border-radius: 8px;
  transition: all 0.4s ease;
  box-shadow: 0 4px 10px rgba(39,174,96,0.4);
  font-size: 0.9rem;
}
.btn-green:hover {
  background-position: right center;
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(39,174,96,0.6);
}

.btn-blue {
  background: linear-gradient(90deg, #2196f3, #3d5afe, #2196f3);
  background-size: 300%;
  color: #fff;
  border: none;
  font-weight: 600;
  padding: 6px 18px;
  border-radius: 8px;
  transition: all 0.4s ease;
  box-shadow: 0 4px 10px rgba(63,81,181,0.4);
  font-size: 0.9rem;
}
.btn-blue:hover {
  background-position: right center;
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(63,81,181,0.6);
}

/* ===== TABLE HEADER ===== */
.table thead th {
  background-color: #140d77 !important;
  color: white !important;
}
</style>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-10">

      <!-- PAGE TITLE -->
      <div class="text-center mb-4">
        <h2 class="page-heading"><i class="fa fa-hand-holding-usd"></i> Payment In</h2>
      </div>

      <!-- FORM CARD -->
      <div class="card card-modern">
        <div class="card-body p-4">

          <form action="save_payment_in.php" method="POST" onsubmit="return validateForm()">
            <div class="row mb-3">
              <div class="col-md-4">
                <label>PI Number</label>
                <input type="text" name="pi_number" class="form-control form-control-lg shadow-sm"
                       value="<?= htmlspecialchars($pi_number) ?>" readonly style="border-radius:10px;">
              </div>
              <div class="col-md-4">
                <label>Date</label>
                <input type="date" name="date" class="form-control form-control-lg shadow-sm"
                       required value="<?= date('Y-m-d') ?>" style="border-radius:10px;">
              </div>
              <div class="col-md-4">
                <label>Party (Customer)</label>
                <select name="party_id" id="partySelect" class="form-select form-select-lg shadow-sm"
                        required onchange="fetchCustomerBalance(this.value)" style="border-radius:10px;">
                  <option value="">-- Select Customer --</option>
                  <?php foreach ($parties as $party): ?>
                    <option value="<?= htmlspecialchars($party['id']) ?>" <?= ($selected_customer == $party['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($party['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- TO RECEIVE -->
            <div class="row mb-3" id="toReceiveContainer" style="display:none;">
              <div class="col-md-4">
                <label><strong>To Receive:</strong></label>
                <span id="toReceiveAmount" style="font-weight:bold; color:green;">₹ 0.00</span>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control form-control-lg shadow-sm"
                       required id="paymentAmount" oninput="checkAmount()" style="border-radius:10px;">
                <span id="amountError" style="color:red; display:none;">Payment exceeds balance!</span>
              </div>
              <div class="col-md-4">
                <label>Payment Mode</label>
                <select name="payment_mode" class="form-select form-select-lg shadow-sm" required style="border-radius:10px;">
                  <option value="">-- Select Mode --</option>
                  <option value="Cash A/c">Cash A/c</option>
                  <option value="Paytm">Paytm</option>
                  <option value="Google Pay">Google Pay</option>
                  <option value="UPI">UPI</option>
                </select>
              </div>
              <div class="col-md-4">
                <label>Notes</label>
                <input type="text" name="notes" class="form-control form-control-lg shadow-sm"
                       placeholder="Optional notes" style="border-radius:10px;">
              </div>
            </div>

            <!-- BUTTONS -->
            <div class="text-end mt-4">
              <button type="submit" class="btn btn-green shadow">
                <i class="fa fa-save me-1"></i> Save
              </button>
              <a href="payment_in_list.php" class="btn btn-blue ms-2 shadow">
                <i class="fa fa-arrow-left me-1"></i> Back
              </a>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- ✅ SUCCESS TOAST -->
<?php if (!empty($_GET['success'])): ?>
<div id="successToast" style="
  position: fixed;
  top: 25px;
  right: 25px;
  background: linear-gradient(90deg, #2ecc71, #27ae60, #2ecc71);
  color: #fff;
  padding: 14px 26px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 1rem;
  box-shadow: 0 6px 16px rgba(39,174,96,0.4);
  z-index: 9999;
  opacity: 0;
  transform: translateX(120%);
  transition: all 0.6s ease;
">
  <i class="fa fa-check-circle me-2"></i> Payment Saved Successfully!
</div>
<script>
const toast = document.getElementById('successToast');
setTimeout(() => { 
  toast.style.opacity = '1'; 
  toast.style.transform = 'translateX(0)'; 
}, 100);
setTimeout(() => { 
  toast.style.opacity = '0'; 
  toast.style.transform = 'translateX(120%)'; 
}, 3500);
</script>
<?php endif; ?>

<script>
function fetchCustomerBalance(partyId) {
    const toReceiveContainer = document.getElementById("toReceiveContainer");
    const toReceiveAmount = document.getElementById("toReceiveAmount");

    if (!partyId) {
        toReceiveContainer.style.display = "none";
        toReceiveAmount.textContent = "₹ 0.00";
        return;
    }

    fetch("get_customer_balance.php?party_id=" + partyId)
        .then(response => response.json())
        .then(data => {
            toReceiveContainer.style.display = "block";
            toReceiveAmount.textContent = "₹ " + data.balance;
        })
        .catch(err => {
            console.error(err);
            toReceiveContainer.style.display = "block";
            toReceiveAmount.textContent = "Error";
        });
}

function validateForm() {
    const balance = parseFloat(document.getElementById("toReceiveAmount").textContent.replace(/[₹,]/g, '').trim()) || 0;
    const amount = parseFloat(document.getElementById("paymentAmount").value) || 0;

    if (amount > balance) {
        alert("Payment amount cannot exceed To Receive balance!");
        return false;
    }
    return true;
}

function checkAmount() {
    const balanceText = document.getElementById("toReceiveAmount").textContent.replace(/[₹,]/g, '').trim();
    const balance = parseFloat(balanceText) || 0;
    const amountField = document.getElementById("paymentAmount");
    const errorSpan = document.getElementById("amountError");
    const amount = parseFloat(amountField.value) || 0;

    if (amount > balance) {
        errorSpan.style.display = "block";
        amountField.value = "";
        amountField.focus();
    } else {
        errorSpan.style.display = "none";
    }
}

// ✅ Auto-select customer & fetch balance if opened from Sales Invoice
document.addEventListener("DOMContentLoaded", function() {
    const selectedCustomer = "<?= $selected_customer ?>";
    if (selectedCustomer) {
        const partySelect = document.getElementById("partySelect");
        partySelect.value = selectedCustomer;
        fetchCustomerBalance(selectedCustomer);
    }
});
</script>