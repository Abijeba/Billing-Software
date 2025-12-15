<?php
require_once 'config.php';
include 'header.php';

// Auto-generate PM Number
$last_pm = $pdo->query("SELECT pm_number FROM payments_out ORDER BY id DESC LIMIT 1")->fetchColumn();
if ($last_pm) {
    $num = (int)substr($last_pm, strrpos($last_pm, "/")+1);
    $pm_number = "PM/25-26/" . str_pad($num+1, 4, "0", STR_PAD_LEFT);
} else {
    $pm_number = "PM/25-26/0001";
}

// Get suppliers from both tables
$suppliers = $pdo->query("SELECT supplier_id AS id, supplier_name AS name FROM suppliers ORDER BY supplier_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$ledgerSuppliers = $pdo->query("SELECT id, ledger_name AS name FROM ledgers WHERE type='Supplier' ORDER BY ledger_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$parties = array_merge($suppliers, $ledgerSuppliers);
?>

<div class="container mt-4">
  <div class="card shadow">
    <div class="card-header" style="background-color: #140d77; color: white;">

      <h5><i class="fa fa-money-bill"></i> Payment Out</h5>
    </div>
    <div class="card-body">
      <form action="save_payment_out.php" method="POST">
        <div class="row mb-3">
          <div class="col-md-4">
            <label>PM Number</label>
            <input type="text" name="pm_number" class="form-control" value="<?= $pm_number ?>" readonly>
          </div>
          <div class="col-md-4">
            <label>Date</label>
            <input type="date" name="date" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>
       <div class="col-md-4">
            <label>Party (Supplier)</label>
            <select name="party_id" id="partySelect" class="form-select" required onchange="fetchSupplierBalance(this.value)">
              <option value="">-- Select Supplier --</option>
              <?php foreach ($parties as $party): ?>
                <option value="<?= htmlspecialchars($party['id']) ?>"><?= htmlspecialchars($party['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- TO PAY DISPLAY -->
        <div class="row mb-3" id="toPayContainer" style="display:none;">
          <div class="col-md-4">
            <label><strong>To Pay:</strong></label>
            <span id="toPayAmount" style="font-weight:bold; color:red;">₹ 0.00</span>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label>Amount</label>
            <input type="number" step="0.01" name="amount" class="form-control" required id="paymentAmount" oninput="checkAmount()">
            <span id="amountError" style="color:red; display:none;">Payment exceeds balance!</span>
          </div>
          <div class="col-md-4">
            <label>Payment Mode</label>
            <select name="payment_mode" class="form-select" required>
              <option value="">-- Select Mode --</option>
              <option value="Cash A/c">Cash A/c</option>
              <option value="Paytm">Paytm</option>
              <option value="Google Pay">Google Pay</option>
              <option value="UPI">UPI</option>
            </select>
          </div>
          <div class="col-md-4">
            <label>Notes</label>
            <input type="text" name="notes" class="form-control" placeholder="Optional notes">
          </div>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-outline-success">Save</button>
          <a href="payment_out_list.php" class="btn btn-outline-secondary">Back</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function fetchSupplierBalance(partyId) {
    const toPayContainer = document.getElementById("toPayContainer");
    const toPayAmount = document.getElementById("toPayAmount");

    if (!partyId) {
        toPayContainer.style.display = "none";
        toPayAmount.textContent = "₹ 0.00";
        return;
    }

    fetch("get_supplier_balance.php?party_id=" + partyId)
        .then(response => response.json())
        .then(data => {
            toPayContainer.style.display = "block";
            toPayAmount.textContent = "₹ " + data.balance;
        })
        .catch(err => {
            console.error(err);
            toPayContainer.style.display = "block";
            toPayAmount.textContent = "Error";
        });
}

function validateForm() {
    const balance = parseFloat(document.getElementById("toPayAmount").textContent.replace(/[₹,]/g, '').trim()) || 0;
    const amount = parseFloat(document.getElementById("paymentAmount").value) || 0;

    if (amount > balance) {
        alert("Payment amount cannot exceed To Pay balance!");
        return false; // stop form submission
    }
    return true;
}
function checkAmount() {
    const balanceText = document.getElementById("toPayAmount").textContent.replace(/[₹,]/g, '').trim();
    const balance = parseFloat(balanceText) || 0;
    const amountField = document.getElementById("paymentAmount");
    const errorSpan = document.getElementById("amountError");
    const amount = parseFloat(amountField.value) || 0;

    // If amount exceeds balance, show error and clear invalid input
    if (amount > balance) {
        errorSpan.style.display = "block";
        amountField.value = "";  // clear field
        amountField.focus();     // refocus the field
    } else {
        errorSpan.style.display = "none";
    }
}

</script>
