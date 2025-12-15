<?php
require_once 'config.php'; // $pdo connection
$title = "Sales Order Print";

// Get SO ID from URL
$so_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch SO details
$stmt = $pdo->prepare("
  SELECT so.*, c.customer_name, c.address, c.phone, c.email
  FROM sales_orders so
  JOIN customers c ON so.customer_id = c.customer_id
  WHERE so.so_id = ?
");
$stmt->execute([$so_id]);
$so = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$so) {
    die("Sales Order not found.");
}

// Fetch order items
$itemStmt = $pdo->prepare("SELECT * FROM sales_order_items WHERE so_id = ?");
$itemStmt->execute([$so_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$sub_total = 0;
$tax_total = 0;

foreach ($items as $item) {
    $line_total = ($item['quantity'] * $item['rate']);
    $line_total -= $line_total * ($item['discount'] / 100);

    // GST
    $gst_amount = $line_total * $item['gst'] / 100;
    $tax_total += $gst_amount;

    if ($item['tax_mode'] === 'Exclusive') {
        $line_total += $gst_amount;
    }

    $sub_total += $line_total;
}

$grand_total = $sub_total;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sales Order <?= htmlspecialchars($so['so_number']) ?></title>
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
<div class="container mt-4" id="printableArea">

  <!-- Company Header -->
  <div class="text-center mb-4">
    <h4>STS</h4>
    <p>
      123 Business Rd, Thoothukudi, TamilNadu 628002 <br>
      Phone: (123) 456-7890 | Email: info@sts.com
    </p>
  </div>

  <!-- Sales Order Info -->
  <div class="po-header text-center mb-4">
    <h2>SALES ORDER</h2>
    <small>
      <strong>SO : <?= htmlspecialchars($so['so_number']) ?></strong><br>
      Date: <?= date('d-m-Y', strtotime($so['so_date'])) ?> | 
      Delivery Date: <?= date('d-m-Y', strtotime($so['delivery_date'])) ?>
    </small>
  </div>

  <!-- Customer Info -->
  <div class="row mb-4">
    <div class="col-md-6">
      <p class="section-title">Customer</p>
      <p>
        Name: <?= htmlspecialchars($so['customer_name']) ?><br>
        Address: <?= htmlspecialchars($so['address']) ?><br>
        Phone: <?= htmlspecialchars($so['phone']) ?><br>
        Email: <?= htmlspecialchars($so['email']) ?><br>
        Customer Bill No: <?= htmlspecialchars($so['customer_bill_no']) ?>
      </p>
    </div>
  </div>

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

  <!-- Totals -->
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
    <p><strong>Notes:</strong><br><?= htmlspecialchars($so['notes']) ?></p>
    <p><strong>Terms and Conditions:</strong><br><?= nl2br(htmlspecialchars($so['terms'])) ?></p>
  </div>

  <!-- Footer -->
  <div class="footer text-end">
    Generated on: <?= date('d-m-Y') ?>
  </div>
</div>

<!-- Print Button -->
<div class="text-center mt-4">
    <button class="btn btn-primary" onclick="printDiv('printableArea')">Print Sales Order</button>
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
