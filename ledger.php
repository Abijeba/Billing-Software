<?php
require_once 'config.php';
include 'header.php';

// Fetch all ledgers for left side list
$sql = "SELECT * FROM ledgers ORDER BY id DESC";
$stmt = $pdo->query($sql);
$ledgers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ledgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background: #f8f9fa; }
    .ledger-card {
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 12px 16px;
      margin-bottom: 10px;
      cursor: pointer;
      background: #fff;
      transition: 0.2s;
    }
    .ledger-card:hover { background: #f1f1f1; }
    .ledger-name { font-weight: bold; font-size: 15px; }
    .ledger-type { font-size: 13px; color: #666; }
    .ledger-balance { font-size: 14px; font-weight: bold; }
    .balance-positive { color: green; }
    .balance-negative { color: red; }
    .ledger-list { height: calc(100vh - 100px); overflow-y: auto; }
    .ledger-details {
      border-left: 1px solid #ddd;
      padding: 20px;
      background: #fff;
      height: calc(100vh - 100px);
      overflow-y: auto;
    }
    .empty-details {
      display: flex; align-items: center; justify-content: center;
      height: 100%; color: #888;
      flex-direction: column;
    }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    
    <!-- Left: Ledger List -->
    <div class="col-md-4 p-3 ledger-list">
      <div class="d-flex justify-content-between mb-3">
        <h4>Ledgers</h4>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLedgerModal">
          <i class="fa fa-plus"></i> Add
        </button>
      </div>
      <?php if (!empty($ledgers)): ?>
        <?php foreach($ledgers as $l): ?>
          <div class="ledger-card" data-id="<?= $l['id'] ?>">
            <div class="ledger-name"><?= htmlspecialchars($l['ledger_name']) ?></div>
            <div class="ledger-type"><?= htmlspecialchars($l['type']) ?></div>
            <div class="ledger-balance <?= ($l['opening_amount'] >= 0) ? 'balance-positive' : 'balance-negative' ?>">
              ₹ <?= number_format($l['opening_amount'], 2) ?> (<?= htmlspecialchars($l['opening_type']) ?>)
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="alert alert-warning">No ledgers found</div>
      <?php endif; ?>
    </div>

    <!-- Right: Ledger Details -->
    <div class="col-md-8 ledger-details" id="ledgerDetails">
      <div class="empty-details">
        <i class="fa fa-list fa-3x mb-3"></i>
        <p>Select an item to get details</p>
      </div>
    </div>
  </div>
</div>

<!-- Add Ledger Modal -->
<div class="modal fade" id="addLedgerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="save_ledger.php">
        <div class="modal-header">
          <h5 class="modal-title">Add Ledger</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          
          <!-- Ledger Type -->
          <div class="mb-3">
            <label>Type</label><br>
            <input type="radio" name="type" value="Customer" checked class="ledger-type"> Customer
            <input type="radio" name="type" value="Supplier" class="ledger-type"> Supplier
            <input type="radio" name="type" value="Other" class="ledger-type"> Other
          </div>

          <!-- Ledger Name + Account Group -->
          <div class="row">
            <div class="col-md-6 mb-3">
              <label id="ledgerNameLabel">Customer Name</label>
              <input type="text" name="ledger_name" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Account Group</label>
              <select name="account_group" id="accountGroup" class="form-control" required>
                <option value="">-- Select Group --</option>
               
              </select>
            </div>
          </div>

          <!-- Common Opening Balance + Type -->
          <div class="row">
            <div class="col-md-6 mb-3">
              <label>Opening Balance</label>
              <input type="number" step="0.01" name="opening_amount" class="form-control" value="">
            </div>
            <div class="col-md-6 mb-3">
              <label>Opening Type</label>
              <select name="opening_type" class="form-control">
                <option value="To Receive">To Receive</option>
                <option value="To Pay">To Pay</option>
              </select>
            </div>
          </div>

          <!-- Customer & Supplier Extra Fields -->
          <div id="customerSupplierFields">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label>Contact Name</label>
                <input type="text" name="contact" class="form-control">
              </div>
              <div class="col-md-6 mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control">
              </div>
            </div>

            <div class="mb-3">
              <label>GST Details</label>
              <input type="text" name="gst_details" class="form-control">
            </div>

            <div class="mb-3">
              <label>Billing Address</label>
              <textarea name="billing_address" class="form-control"></textarea>
            </div>

            <div class="mb-3">
              <label>Shipping Address</label>
              <textarea name="shipping_address" class="form-control"></textarea>
            </div>
          </div>

          <!-- Other Fields -->
          <div id="otherFields" style="display:none;">
            <p class="text-muted">No extra fields for Other type.</p>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){

  // ===== Load ledger details when clicking card =====
  $(".ledger-card").click(function(){
    var id = $(this).data("id");
    $.get("ledger_details.php", {id: id}, function(data){
      $("#ledgerDetails").html(data);
    });
  });

  // ===== Populate Account Group dynamically =====
  function populateAccountGroup(type) {
    let accountGroup = $("#accountGroup");
    accountGroup.empty(); // clear old options

    if (type === "Customer") {
      accountGroup.append('<option value="Sundry Debtors">Sundry Debtors</option>');
      accountGroup.append('<option value="Add New Item">Add New Item</option>');
    } 
    else if (type === "Supplier") {
      accountGroup.append('<option value="Sundry Creditors">Sundry Creditors</option>');
      accountGroup.append('<option value="Add New Item">Add New Item</option>');
    } 
    else if (type === "Other") {
      accountGroup.append('<option value="Sundry Debtors">Sundry Debtors</option>');
      accountGroup.append('<option value="Sundry Creditors">Sundry Creditors</option>');
      accountGroup.append('<option value="Advance Against Salary">Advance Against Salary</option>');
      accountGroup.append('<option value="Cash-in-hand">Cash-in-hand</option>');
      accountGroup.append('<option value="Bank Account">Bank Account</option>');
      accountGroup.append('<option value="Direct Expenses">Direct Expenses</option>');
      accountGroup.append('<option value="Indirect Expenses">Indirect Expenses</option>');
      accountGroup.append('<option value="Sales Account">Sales Account</option>');
      accountGroup.append('<option value="Purchase Account">Purchase Account</option>');
      accountGroup.append('<option value="Add New Item">Add New Item</option>');
    }
  }

  // ===== Show/hide fields =====
  function toggleFields(type){
    if(type === "Other"){
      $("#customerSupplierFields").hide();
      $("#otherFields").show();
      $("#ledgerNameLabel").text("Ledger Name");
    } else {
      $("#customerSupplierFields").show();
      $("#otherFields").hide();
      $("#ledgerNameLabel").text(type + " Name");
    }
    populateAccountGroup(type); // refresh account group options
  }

  // ===== Type change event =====
  $(".ledger-type").change(function(){
    toggleFields($(this).val());
  });

  // ===== Default on page load =====
  let defaultType = $("input[name='type']:checked").val();
  toggleFields(defaultType);

  // ===== Delete from details panel =====
  $(document).on("click", ".delete-ledger-detail", function(){
    let id = $(this).data("id");

    if(confirm("Are you sure you want to delete this ledger?")) {
      $.post("delete_ledger.php", {id: id}, function(response){
        if(response.trim() === "success") {
          alert("Ledger deleted successfully");
          location.reload(); // reload to refresh list
        } else {
          alert("Error deleting ledger: " + response);
        }
      });
    }
  });

});
</script>

</body>
</html>