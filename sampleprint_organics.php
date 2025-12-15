<?php
require_once 'config.php';

if (empty($_GET['id'])) die("Invalid request.");
$si_id = (int)$_GET['id'];

// --- Fetch invoice header ---
$sql = "SELECT si.*, c.customer_name, c.address, c.phone 
        FROM sales_invoices si
        JOIN customers c ON si.customer_id = c.customer_id
        WHERE si.si_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$si_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$invoice) die("Invoice not found.");

// --- Fetch items ---
$sql_items = "SELECT category, product_name, quantity, rate, discount, gst, line_total, tax_mode
              FROM sales_invoice_items WHERE si_id = ?";
$stmt = $pdo->prepare($sql_items);
$stmt->execute([$si_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Redirect if not organics ---
$isOrganics = true;
foreach ($items as $it) {
    if (strtolower(trim($it['category'])) !== 'organics') {
        $isOrganics = false;
        break;
    }
}
if (!$isOrganics) {
    header("Location: sampleprint2.php?id=" . $si_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice</title>
<style>
@page { size: 80mm auto; margin: 0; }

body {
  width: 80mm;
  margin: 0 auto;
  font-family: "Poppins", Arial, sans-serif;
  font-size: 11.5px;
  color: #000;
  line-height: 1.3;
}

.wrapper { width: 94%; margin: 0 auto; }

/* --- Header --- */
h3 {
  color: #0a8f3e;
  font-weight: 700;
  font-size: 15px;
  margin: 3px 0 1px;
  text-align: center;
  letter-spacing: 0.2px;
}

.header-info {
  text-align: center;
  font-size: 10.5px;
  line-height: 1.3;
}

.contact {
  text-align: center;
  font-size: 10.5px;
  font-weight: 600;
  margin: 3px 0;
}

hr {
  border: none;
  border-top: 1px dashed #000;
  margin: 4px 0;
}

/* --- Customer Info --- */
.customer-table {
  width: 100%;
  font-size: 11px;
}

.customer-table td {
  vertical-align: top;
  padding: 1px 0;
}

/* --- Items Table --- */
.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 11.2px;
  margin-top: 4px;
}

.table th {
  border-bottom: 1px solid #000;
  font-weight: 700;
  text-align: left;
  padding: 3px 0 2px 8px;
}

.table td {
  border-bottom: 1px dashed #bbb;
  padding: 2px 0;
}

/* Align Product Name heading & content same as TO customer name */
.table th:first-child,
.table td:first-child {
  text-align: left;
  padding-left: 0; /* Remove old 8px */
}

.customer-table td:first-child {
  padding-left: 0;
}


.table td:nth-child(2) { text-align: center; width: 15%; }
/* Align Rate column neatly */
/* Rate column alignment */
/* --- Rate Column --- */
.table th:nth-child(3),
.table td:nth-child(3) {
  text-align: center;
  width: 15%;
  padding: 0;
}

/* Bigger "Rate" heading */
.table th:nth-child(3) {
  font-size: 12.5px;
  font-weight: 700;
  padding-top: 2px;
}

/* Smaller Rate values */
.table td:nth-child(3) {
  font-size: 10.5px;  /* 👈 slightly smaller than normal */
  font-weight: 500;
}



.table td:nth-child(4) { text-align: right; width: 21%; padding-right: 15px; }

/* --- Totals Section --- */
.summary {
  width: 100%;
  border-top: 1px solid #000;
  font-size: 11.2px;
  margin-top: 3px;
  border-collapse: collapse;
}

.summary td {
  padding: 2px 0;
  border-bottom: 1px dashed #bbb;
}

.summary td:nth-child(1) {
  text-align: left;
  width: 50%;
  font-weight: 600;
  padding-left: 0;
}

.summary td:nth-child(2) {
  text-align: right;
  width: 50%;
  padding-right: 6px;
  font-weight: 600;
}

/* --- Tax Table --- */
.tax-table {
  width: 100%;
  border-top: 1px solid #000;
  font-size: 11.2px;
  margin-top: 3px;
  border-collapse: collapse;
}

.tax-table th, .tax-table td {
  text-align: center;
  padding: 2px 0;
  border-bottom: 1px dashed #bbb;
}

.tax-table th:first-child,
.tax-table td:first-child {
  text-align: left;
  padding-left: 0;
}

/* --- QR Section --- */
.qr {
  text-align: center;
  margin-top: 6px;
}

.qr p {
  font-size: 10.8px;
  margin: 3px 0;
  font-weight: 500;
  letter-spacing: 0.2px;
}

.qr img {
  width: 120px;
  height: auto;
}

/* --- Footer --- */
.footer {
  text-align: center;
  font-size: 11.5px;
  font-weight: 700;
  margin-top: 8px;
  padding-top: 5px;
  border-top: 1px dashed #000;
  letter-spacing: 0.2px;
}

@media print {
  body { -webkit-print-color-adjust: exact; }
}
</style>
</head>
<body onload="window.print()">
<div class="wrapper">

  <h3>Kalpaka Organics</h3>
  <div class="header-info">
    4/133X, Muthammal Colony, 3rd Street,<br>Thoothukudi 628 002
  </div>
  <div class="contact">
    📞 8072263604 &nbsp;&nbsp; 🌐 kalpakaorganics.com
  </div>
  <hr>

  <h4 style="text-align:center; font-size:12.5px; margin:3px 0; text-decoration:underline;">INVOICE</h4>
  <hr>

  <table class="customer-table">
    <tr>
      <td style="text-align:left; vertical-align:middle; padding-top:10px;">
      <div style="line-height:1.4;">
        <b style="display:inline-block; width:25px;">TO</b><br>
        <?= htmlspecialchars($invoice['customer_name']) ?>
      </div>
    </td>
      <td style="text-align:right; vertical-align:top;">
        <table style="float:right; text-align:left; font-size:11px; line-height:1.5;">
          <tr>
            <td style="font-weight:600;">Bill No</td>
            <td style="padding:0 4px;">:</td>
            <td><?= htmlspecialchars($invoice['customer_bill_no']) ?></td>
          </tr>
          <tr>
            <td style="font-weight:600;">Bill Date</td>
           <td style="padding:0 4px;">:</td>
            <td><?= date('d-m-Y', strtotime($invoice['si_date'])) ?></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>


  <hr>

  <table class="table">
    <thead>
      <tr>
        <th>Product Name</th>
        <th>Qty</th>
        <th>Rate</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody>
    <?php
      $total = 0;
      foreach ($items as $it):
          $amt = $it['line_total'];
          $total += $amt;
    ?>
      <tr>
        <td><?= htmlspecialchars($it['product_name']) ?></td>
        <td><?= $it['quantity'] ?></td>
        <td><?= number_format($it['rate'], 2) ?></td>
        <td><?= number_format($amt, 2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <table class="summary">
    <tr>
      <td>Items: <?= count($items) ?></td>
      <td>Total: ₹<?= number_format($total, 2) ?></td>
    </tr>
  </table>

  <table class="tax-table">
    <thead>
      <tr>
        <th>Tax</th>
        <th>Taxable Amount</th>
        <th>CGST</th>
        <th>SGST</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>0%</td>
        <td><?= number_format($total, 2) ?></td>
        <td>0.00</td>
        <td>0.00</td>
        <td><?= number_format($total, 2) ?></td>
      </tr>
    </tbody>
  </table>

  <div class="qr">
    <p>Scan and Pay using below QR Code</p>
    <img src="images/po.jpeg" alt="QR Code">
  </div>

  <div class="footer">THANK YOU VISIT AGAIN</div>

</div>
</body>
</html>
