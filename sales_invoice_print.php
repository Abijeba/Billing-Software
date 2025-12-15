<?php
require_once 'config.php'; // $pdo connection


// Get Sales Invoice ID from URL
$si_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($si_id <= 0) {
    die("Invalid Sales Invoice ID");
}

// Fetch invoice header
$stmt = $pdo->prepare("
  SELECT si.*,
         c.customer_name, 
         c.address AS customer_address, 
         c.phone   AS customer_phone, 
         c.email   AS customer_email
  FROM sales_invoices si
  JOIN customers c ON si.customer_id = c.customer_id
  WHERE si.si_id = ?
");

$stmt->execute([$si_id]);
$si = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$si) {
    die("Sales Invoice not found.");
}

// Fetch invoice items
$itemStmt = $pdo->prepare("
    SELECT i.category, i.product_name, i.quantity, i.rate, i.discount, i.gst, i.tax_mode
    FROM sales_invoice_items i
    WHERE i.si_id = ?
");

$itemStmt->execute([$si_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$sub_total = 0;
$tax_total = 0;

foreach ($items as $item) {
    $line_total = ($item['quantity'] * $item['rate']);
    $line_total -= $line_total * ($item['discount'] / 100);

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
  <title>Sales Invoice <?= htmlspecialchars($si['si_number']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; }
    .invoice-header h2 { color: #003366; margin-bottom: 0; }
    .invoice-header small { font-size: 14px; color: #555; }
    .section-title { font-weight: bold; margin-top: 20px; margin-bottom: 10px; }
    .table th, .table td { vertical-align: middle; }
    .totals-table td { font-weight: bold; }
    .footer { margin-top: 40px; font-size: 13px; }
  </style>
</head>
<body>
<div class="container mt-4">
  <div class="text-center mb-4">
    <h4>STS</h4>
    <p>
      123 Business Rd, Thoothukudi, TamilNadu 628002 <br>
      Phone: (123) 456-7890 | Email: info@sts.com
    </p>
  </div>

  <div class="invoice-header text-center mb-4">
    <h2>SALES INVOICE</h2>
    <small>
      <strong>Invoice : <?= htmlspecialchars($si['si_number']) ?></strong><br>
      Date: <?= date('d-m-Y', strtotime($si['si_date'])) ?> | 
      Due Date: <?= $si['due_date'] ? date('d-m-Y', strtotime($si['due_date'])) : '-' ?>
    </small>
  </div>

  <div class="row mb-4">
    <div class="col-md-6">
      <p class="section-title">Customer</p>
      <p>
        Name: <?= htmlspecialchars($si['customer_name']) ?><br>
        Address: <?= nl2br(htmlspecialchars($si['customer_address'])) ?><br>
        Phone: <?= htmlspecialchars($si['customer_phone']) ?><br>
        Email: <?= htmlspecialchars($si['customer_email']) ?>
      </p>
    </div>
  </div>

  <h5 class="section-title">Invoice Items</h5>
  <table class="table table-bordered">
      <thead class="table-light">
          <tr>
              <th>ID</th>
              <th>Category</th>
              <th>Product</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Rate (₹)</th>
              <th class="text-end">Discount %</th>
              <th class="text-end">GST %</th>
              <th class="text-end">Amount (₹)</th>
          </tr>
      </thead>
      <tbody>
          <?php foreach ($items as $i => $item): 
             $line_total = ($item['quantity'] * $item['rate']);
             $line_total -= $line_total * ($item['discount'] / 100);
             $gst_amount = $line_total * $item['gst'] / 100;
             if ($item['tax_mode'] === 'Exclusive') {
                 $line_total += $gst_amount;
             }
          ?>
          <tr>
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($item['category']) ?></td>
              <td><?= htmlspecialchars($item['product_name']) ?></td>
              <td class="text-end"><?= $item['quantity'] ?></td>
              <td class="text-end">₹ <?= number_format($item['rate'], 2) ?></td>
              <td class="text-end"><?= $item['discount'] ?>%</td>
              <td class="text-end"><?= $item['gst'] ?>%</td>
              <td class="text-end">₹ <?= number_format($line_total, 2) ?></td>
          </tr>
          <?php endforeach; ?>
      </tbody>
  </table>

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

  <div class="mt-4">
    <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($si['notes'])) ?></p>
    <p><strong>Terms and Conditions:</strong><br><?= nl2br(htmlspecialchars($si['terms'])) ?></p>
  </div>

  <div class="footer text-end">
    Generated on: <?= date('d-m-Y') ?>
  </div>
</div>
</body>
</html>
