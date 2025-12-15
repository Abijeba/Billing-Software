<?php
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM ledgers WHERE id = ?");
    $stmt->execute([$id]);
    $ledger = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ledger):
?>
  <!-- Header with ledger name + action buttons -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><?= htmlspecialchars($ledger['ledger_name']) ?></h4>

    <!-- ✅ Action buttons -->
    <div class="d-flex gap-2">
      <?php if ($ledger['type'] === "Supplier"): ?>
        <a href="insert_invoice.php?supplier_id=<?= $ledger['id'] ?>" 
          class="btn btn-primary btn-sm">
          <i class="fa fa-shopping-cart"></i> Purchase
        </a>

        <a href="payment_out.php?party_id=<?= $ledger['id'] ?>" class="btn btn-warning btn-sm">
          <i class="fa fa-money-bill"></i> Pay Out
        </a>

        <!-- Add Button -->
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
          <i class="fa fa-plus"></i> Add
        </button>

      <?php elseif ($ledger['type'] === "Customer"): ?>
        <a href="insert_sales_invoice.php?customer_id=<?= $ledger['id'] ?>" 
          class="btn btn-primary btn-sm">
          <i class="fa fa-shopping-bag"></i> Sales
        </a>

        <a href="insert_payment in.php?party_id=<?= $ledger['id'] ?>" class="btn btn-warning btn-sm">
          <i class="fa fa-hand-holding-usd"></i> Pay In
        </a>

        <!-- Add Button -->
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
          <i class="fa fa-plus"></i> Add
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- ✅ Supplier Modal -->
  <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addSupplierLabel">Add Transaction (Supplier)</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Purchase Section -->
          <h6 class="text-muted">Purchase</h6>
          <ul class="list-group mb-3">
            <li><a class="list-group-item" href="insert_po.php?party_id=<?= $ledger['id'] ?>">Purchase Order</a></li>
            <li><a class="list-group-item" href="insert_invoice.php?party_id=<?= $ledger['id'] ?>">Purchase Invoice</a></li>
          </ul>

          <!-- Account Books -->
          <h6 class="text-muted">Account Books</h6>
          <ul class="list-group">
            <li><a class="list-group-item" href="payment_out.php?party_id=<?= $ledger['id'] ?>">Payment Out</a></li>
          </ul>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ✅ Customer Modal -->
  <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="addCustomerLabel">Add Transaction (Customer)</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Sales Section -->
          <h6 class="text-muted">Sales</h6>
          <ul class="list-group mb-3">
            <li><a class="list-group-item" href="insert_sales.php?party_id=<?= $ledger['id'] ?>">Sales Order</a></li>
            <li><a class="list-group-item" href="insert_sales_invoice.php?party_id=<?= $ledger['id'] ?>">Sales Invoice</a></li>
          </ul>

          <!-- Account Books -->
          <h6 class="text-muted">Account Books</h6>
          <ul class="list-group">
            <li><a class="list-group-item" href="insert_payment in.php?party_id=<?= $ledger['id'] ?>">Payment In</a></li>
          </ul>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ✅ Tabs -->
  <ul class="nav nav-tabs mb-3" id="ledgerTabs" role="tablist">
   
    <li class="nav-item">
      <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">Details</button>
    </li>
  </ul>

  <!-- ✅ Tab Content -->
  <div class="tab-content" id="ledgerTabContent">
    <!-- Activity Tab -->
    

    <!-- Details Tab -->
    <div class="tab-pane fade" id="details" role="tabpanel">
      <p><strong>Type:</strong> <?= htmlspecialchars($ledger['type']) ?></p>
      <p><strong>Account Group:</strong> <?= htmlspecialchars($ledger['account_group']) ?></p>
      <p><strong>Opening Balance:</strong> ₹ <?= number_format((float)$ledger['opening_amount'], 2) ?> (<?= htmlspecialchars($ledger['opening_type']) ?>)</p>
      <p><strong>Contact:</strong> <?= htmlspecialchars($ledger['contact']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($ledger['email']) ?></p>
      <p><strong>GST:</strong> <?= htmlspecialchars($ledger['gst_details']) ?></p>
      <p><strong>Billing Address:</strong><br><?= nl2br(htmlspecialchars($ledger['billing_address'])) ?></p>
      <p><strong>Shipping Address:</strong><br><?= nl2br(htmlspecialchars($ledger['shipping_address'])) ?></p>

      <!-- Delete button -->
      <div class="mt-3">
        <button class="btn btn-danger btn-sm delete-ledger-detail" data-id="<?= $ledger['id'] ?>">
          <i class="fa fa-trash"></i> Delete
        </button>
      </div>
    </div>
  </div>

<?php
    else:
      echo "<p>Ledger not found.</p>";
    endif;
}
?>
