<?php
require_once 'config.php'; // $pdo connection
$title = "Purchase Orders";


// Get PO ID from URL
$po_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch PO details
$stmt = $pdo->prepare("
  SELECT po.*, s.supplier_name, s.address, s.phone, s.email
  FROM purchase_orders po
  JOIN suppliers s ON po.supplier_id = s.supplier_id
  WHERE po.po_id = ?
");
$stmt->execute([$po_id]);
$po = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$po) {
    die("Purchase Order not found.");
}

// Fetch order items
$itemStmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
$itemStmt->execute([$po_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$sub_total = 0;
$tax_total = 0;

foreach ($items as $item) {
    $line_total = ($item['quantity'] * $item['rate']);
    $line_total -= $line_total * ($item['discount'] / 100);

    // Calculate GST amount for this line
    $gst_amount = $line_total * $item['gst'] / 100;
    $tax_total += $gst_amount;

    // If Exclusive → add GST on top of the line
    if ($item['tax_mode'] === 'Exclusive') {
        $line_total += $gst_amount;
    }

    $sub_total += $line_total;
}

$grand_total = $sub_total; // already includes GST if Exclusive

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Purchase Order <?= htmlspecialchars($po['po_number']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; }
    .po-header h2 { color: #003366; margin-bottom: 0; }
    .po-header small { font-size: 14px; color: #555; }
    .section-title { font-weight: bold; margin-top: 20px; margin-bottom: 10px; }
    .table th, .table td { vertical-align: middle; }
    .totals-table td { font-weight: bold; }
    .footer { margin-top: 40px; font-size: 13px; }
  </style>
</head>
<body>
<div class="container mt-4">

  <!-- Print Button -->
 
  <!-- Company Header -->
  <div class="text-center mb-4">
    <h4>STS</h4>
    <p>
      123 Business Rd, Thoothukudi, TamilNadu 628002 <br>
      Phone: (123) 456-7890 | Email: info@sts.com
    </p>
  </div>

  <!-- Purchase Order Info -->
  <div class="po-header text-center mb-4">
    <h2>PURCHASE ORDER</h2>
    <small>
      <strong>PO : <?= htmlspecialchars($po['po_number']) ?></strong><br>
      Date: <?= date('d-m-Y', strtotime($po['po_date'])) ?> | 
      Expected Delivery: <?= date('d-m-Y', strtotime($po['expected_date'])) ?>
    </small>
  </div>

  <!-- Supplier Info -->
  <div class="row mb-4">
    <div class="col-md-6">
      <p class="section-title">Supplier</p>
      <p>
        Name: <?= htmlspecialchars($po['supplier_name']) ?><br>
        Address: <?= htmlspecialchars($po['address']) ?><br>
        Phone: <?= htmlspecialchars($po['phone']) ?><br>
        Email: <?= htmlspecialchars($po['email']) ?><br>
        
      </p>
    </div>
  </div>

  <!-- Order Items -->
    <!-- Order Items -->
 <!-- Order Items -->
<h5 class="section-title">Invoice Items</h5>
<table class="table table-bordered">
    <thead class="table-light">
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Rate (₹)</th>
            <th>Discount %</th>
            <th>GST %</th>
            <th>Amount (₹)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): 
            $line_total = ($item['quantity'] * $item['rate']);
$line_total -= $line_total * ($item['discount'] / 100);
$gst_amount = $line_total * $item['gst'] / 100;

if ($item['tax_mode'] === 'Exclusive') {
    $line_total += $gst_amount;
}

        ?>
        <tr>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td>₹ <?= number_format($item['rate'], 2) ?></td>
            <td><?= $item['discount'] ?>%</td>
            <td><?= $item['gst'] ?>%</td>
            <td>₹ <?= number_format($line_total, 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>



  <!-- Totals Section -->
  <table class="table totals-table">
    <tbody>
      <tr>
        <td class="text-end">Sub Total:</td>
        <td style="width:150px;">₹ <?= number_format($sub_total, 2) ?></td>
      </tr>
      <tr>
        <td class="text-end">Tax (GST):</td>
        <td>₹ <?= number_format($tax_total, 2) ?></td>
      </tr>
      <tr>
        <td class="text-end">Grand Total:</td>
        <td>₹ <?= number_format($grand_total, 2) ?></td>
      </tr>
    </tbody>
  </table>

  <!-- Notes & Terms -->
  <div class="mt-4">
    <p><strong>Notes:</strong><br><?= htmlspecialchars($po['notes']) ?></p>
    <p><strong>Terms and Conditions:</strong><br><?= nl2br(htmlspecialchars($po['terms'])) ?></p>
  </div>

  <!-- Footer -->
  <div class="footer text-end">
    Generated on: <?= date('d-m-Y') ?>
  </div>

</div>
<script>
function printDiv(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    location.reload(); // reload to restore events & JS
}
</script>



</body>
</html>
