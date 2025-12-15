<?php

require_once 'config.php'; // $pdo connection
$terms = "Net 30 Days";// 1. Get the Invoice ID



/*$host = "localhost";
$user = "root";       // default in XAMPP
$pass = "";           // default is empty
$db   = "web";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}*/

$invoice_id = $_GET['id'] ?? die("Error: Invoice ID not specified.");
// --- FETCH COMPANY DETAILS ---
$stmt_company = $pdo->prepare("SELECT company_name, gstin, phone_number, address_line_1 ,bank_name,account_number,ifsc_code FROM company_settings WHERE id = 1");
$stmt_company->execute();


$company_data = $stmt_company->fetch();

if ($company_data) {
    // Data successfully fetched into the $company_data array

    // Assign data to individual variables for cleaner use in HTML:
    $your_company_name = $company_data['company_name'];
    $your_gstin = $company_data['gstin'];
    $your_phone = $company_data['phone_number'];
    $your_address = $company_data['address_line_1'];
    $bank_name = $company_data['bank_name'];
    $account_number = $company_data['account_number'];
    $ifsc_code = $company_data['ifsc_code'];

} else {
    // Handle the case where the data is not found
    $your_company_name = "Company Not Found";
    $your_gstin = "";
    $your_phone = "";
    $your_address = "";
    $bank_name = "";
    $account_number = "";
    $ifsc_code = "";
}

//$stmt_company->close();


// --- Query 1: Fetch Main Invoice & Customer Data ---
$stmt_main = $pdo->prepare("
    SELECT 
        i.si_date,i.si_number as si_number,i.terms as terms,
        c.customer_name AS customer_name, c.address, c.gstin AS customer_gstin,c.ship_address
    FROM sales_invoices i
    JOIN customers c ON i.customer_id = c.customer_id
    WHERE i.si_id = ?
");   //i.sub_total, i.tax_amount, i.grand_total, i.payment_terms, 

$stmt_main->execute([$invoice_id]); 

// Fetch the single result row
$invoice_header = $stmt_main->fetch(); 

if (!$invoice_header) {
    die("Invoice not found.");
}


// --- Query 2: Fetch ALL Line Items ---
$stmt_items = $pdo->prepare("
   SELECT 
    si.product_name, si.quantity,si.rate, si.discount, si.gst, si.line_total,si.tax_mode, p.hsn_code 
FROM sales_invoice_items si JOIN products p on si.product_name=p.product_name
WHERE si.si_id = ?
");
// Pass the parameter as a simple indexed array.
$stmt_items->execute([$invoice_id]);

// 3. Fetch all result rows into an associative array
// PDO::FETCH_ASSOC is the default, matching MYSQLI_ASSOC behavior.
$items_array = $stmt_items->fetchAll();// Fetch all items into an array
//calculations
$sub_total = 0;
$tax_total = 0;

foreach ($items_array as $item) {
    $line_total = ($item['quantity'] * $item['rate']);
    $line_total -= $line_total * ($item['discount'] / 100);

    $gst_amount = $line_total * $item['gst'] / 100;
    $tax_total += $gst_amount;

    if ($item['tax_mode'] === 'Exclusive') {
        $line_total += $gst_amount;
    }

    $sub_total += $line_total;
}
$shipping = 50.00;
$grand_total = $sub_total+$shipping;
// --- Final Calculations ---
require 'convertor.php';
$amount_in_words = numberToWords($grand_total);

 

?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?php echo $invoice_header['si_number']; ?></title>
    <!-- <link rel="stylesheet" href="screen.css" media="screen"> -->
    <?php
   
   // Dynamically get the current directory URL (e.g., http://yourdomain.com/path/to/app/)
  /*  $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']) . '/';
    
    // Clean up the path
    $base_url = $protocol . "://" . $host . str_replace(['//', ':/'], ['/', '://'], $path);
    ?>

    <base href="<?php echo htmlspecialchars($base_url); ?>">
    
    <link rel="stylesheet" href="print.css">*/
   
   
    // Generate a unique version number based on the current time
   // $cache_buster = time(); 
    ?>

    <!-- <link rel="stylesheet" href="print.css?v=1.1" media="print"> -->
     <style>
  @media print {
    /* ========================================================== */
    /* GLOBAL PRINT RESET (MUST be at the top to clear Bootstrap) */
    /* ========================================================== */
    * {
        margin: 0 !important;
        padding: 0 !important;
        box-sizing: content-box !important;
        box-shadow: none !important;
        background: #fff !important;
        color: #000 !important;
        float: none !important; /* Important for clearing Bootstrap column floats */
    }
    
    /* Force Bootstrap layout containers to be ignored */
    .container, .container-fluid, .row, [class*="col-"] {
        width: auto !important;
        max-width: none !important;
        min-width: 0 !important;
        display: block !important;
        flex: 0 0 auto !important;
    }
    
    /* 1. PAPER/PAGE SETUP */
    @page {
        size: A4;
        margin: 10mm;
    }
    
    /* Apply Font/Body styles with power */
    body {
        font-family: Arial, sans-serif !important; /* Force Font Change */
        font-size: 10pt !important;
        margin: 0 !important;
        padding: 0 !important;
        color: #000 !important;
    }
    
    /* 2. HIDE UNNECESSARY SCREEN ELEMENTS */
    .no-print, .print-button {
        display: none !important;
    }

    /* 3. LAYOUT & STRUCTURE */
    body .invoice-wrapper {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Header Spacing */
    body .header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        border-bottom: 2px solid #333 !important;
        padding-bottom: 10px !important;
        margin-bottom: 10px !important;
    }
    body .logo-box h2 {
        font-size: 16pt !important;
        margin: 0 !important;
    }
    body .company-info {
        text-align: right !important;
         font-size: 9pt  !important;
        font-style: normal !important;
    }
    body .invoice-title {
        text-align: center !important;
        font-size: 14pt    !important;
        margin: 10px 0 !important;
    }
    
    /* DETAILS SECTION - CONSOLIDATED & CORRECTED */
    body .details-section {
        display: flex !important;
        justify-content: space-between !important;
        border: 1px solid #ccc !important;
        padding: 5px !important;
        margin-bottom: 15px !important;
    }

    body .address-box, body .invoice-info {
        width: 30% !important; /* Correctly sets 3-column layout */
        padding: 5px !important;
    }
    body .details-section h3 {
        margin: 0 0 5px 0 !important;
         font-size: 10pt  !important;
        font-weight: bold  !important;
        border-bottom: 1px dashed #ccc !important;
    }
    
   
body .item-table {
    width: 99.5% !important;
    /* border-collapse: collapse !important; */
    margin-bottom: 15px !important;
     table-layout: fixed !important; 
     border-collapse: collapse !important; /* ✅ Use separate instead of collapse */
    border-spacing: 0 !important;  
}

body .item-table th,
body .item-table td {
    display: table-cell !important; 
    border: 1px solid #000 !important;
    padding: 5px 8px !important;
    text-align: left !important;
}





    /* Column widths and text alignment */
    body .col-sno { width: 5% !important; }
    body .col-hsn { width: 10% !important; }
    body .col-qty, body .col-unit { width: 8% !important; }
    body .col-price, body .col-amount { width: 12% !important; text-align: right !important; }
    body .col-gst-amount { width: 15% !important; } 
    body .col-linetotal { width: 15% !important; } 
    body .col-item { width: 20% !important; } 
    body .text-right { text-align: right !important; }

       /* Page Break Control */
    body .invoice-item-row {
        page-break-inside: auto !important;
    }
    body .item-table {
        page-break-inside: auto !important;
    }
    
    /* 5. SUMMARY & TOTALS */
    body .summary-section {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-end !important;
       
    }
    body .summary-totals {
        width: 35% !important;
        /* border: 1px solid #000 !important; */
        margin-bottom: 10px !important;
         border-collapse: collapse !important; 
           table-layout: fixed !important; 
        
    }
    body .total-table {
        width: 98% !important;
         border-collapse: collapse !important; 
        border: 1px solid #000 !important; 
    }
    body .total-table td {
        padding: 4px 8px !important;
    }
    body .grand-total-row td {
        font-weight: bold !important;
        background-color: #eee !important;
        border-top: 1px solid #000 !important;
        font-size: 11pt;
    }
    body .amount-in-words {
         font-weight: bold !important;
        margin-bottom: 20px !important;
        align-self: flex-start !important;
    }

    /* 6. FOOTER */
    body footer {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-end !important;
        margin-top: 30px !important;
    }
    body .terms-bank {
        width: 65% !important;
        /* padding: 0 !important; */
        /* margin: 0 !important; */
        font-size:8pt !important;
    }
    body .signature {
        width: auto !important; /* Use auto/min-content for compact signature */
        text-align: center !important;
        border-top: 1px solid #000 !important;
        padding-top: 5px !important;
         font-size: 9pt !important;
    } 
}
</style>
    
</head>

<body>

<div class="invoice-wrapper">
   <header class="header">
    <div class="logo-box">
        <?php if (!empty($your_logo_path)): ?>
            <img src="<?php echo $your_logo_path; ?>" alt="<?php echo $your_company_name; ?> Logo">
        <?php else: ?>
            <h2><?php echo $your_company_name; ?></h2>
        <?php endif; ?>
    </div>
    <address class="company-info">
        <p><?php echo $your_address; ?></p>
        <p>GSTIN:<strong> <?php echo $your_gstin; ?></strong><p>PHONE NUMBER : <strong><?php echo $your_phone; ?></strong></p>
    </address>
</header>
  

    <h1 class="invoice-title">TAX INVOICE</h1>

    <section class="details-section">
        <div class="bill-to">
            <h3>Bill To:</h3>
            <p><strong><?php echo $invoice_header['customer_name']; ?></strong></p>
            <p><?php echo $invoice_header['address']; ?></p>
            <p>Customer GSTIN: <?php echo $invoice_header['customer_gstin']; ?></p>
        </div>
        <div class="address-box ship-to">
        <h3>Ship To:</h3>
        
        <?php if (!empty($ship_to_name)): ?>
            <p><strong> <?php echo $invoice_header['ship_address']; ?></strong></p>                 
            <p>address</p>                                      
            <?php else: ?>
            <p><?php echo $invoice_header['address']; ?></p>
        <?php endif; ?>
    </div>
        <div class="invoice-info">
            <p><strong>Invoice No:</strong> <?php echo $invoice_header['si_number']; ?></p>
            <p><strong>Date:</strong> <?php echo $invoice_header['si_date']; ?></p>
            <p><strong>Payment Terms:</strong> <?php echo $terms; ?></p>
        </div>
    </section>

    <section class="item-section">
        <table class="item-table ">
            <thead>
                <tr>
                    <th class="col-sno">S.No.</th>
                    <th class="col-item">Description</th>
                    <th class="col-hsn">HSN/SAC</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-unit">Unit</th> 
                    <th class="col-price">Rate</th>
                    <th class="col-amount">Discount</th>
                    <th class="col-gst-amount">GST Amount</th>
                    <th class="col-linetotal">LineTotal</th>
                </tr>
            </thead>
           <tbody>
            
            <?php 
            $sno = 1;
            foreach ($items_array as $item): 
            ?>
            <tr class="invoice-item-row">
                <td><?php echo $sno++; ?></td>
                <td><?php echo $item['product_name']; ?></td>
                <td><?php echo $item['hsn_code'] ?? 'N/A'; ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td><?php echo $item['unit'] ?? 'No.'; ?></td>
                <td class="text-right"><?php echo number_format($item['rate'], 2); ?></td>
                <td class="text-right"><?php echo number_format($item['discount'], 2); ?></td>
                <td class="text-right"><?php echo $item['gst']; ?></td>
                <td class="text-right"><?php echo number_format($item['line_total'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        
                
                <!-- <tr><td colspan="7" class="empty-row"></td></tr> -->
            </tbody>
        </table>
    </section>

    <section class="summary-section">
        <div class="summary-totals">
            <table class="total-table">
                <tr><td>Sub Total:</td><td class="text-right"><?php echo number_format($sub_total, 2); ?></td></tr>
                <tr><td>Tax (<?php echo  $item['gst'] * 100; ?>%):</td><td class="text-right"><?php echo number_format($gst_amount, 2); ?></td></tr>
                <tr><td>Shipping:</td><td class="text-right"><?php echo number_format($shipping, 2); ?></td></tr>
                <tr class="grand-total-row"><td><strong>GRAND TOTAL:</strong></td><td class="text-right"><strong><?php echo number_format($grand_total, 2); ?></Strong></td></tr>
            </table>
        </div>
        
        <p class="amount-in-words"><strong>Amount Chargeable (in words): </strong><?php echo $amount_in_words; ?></p>
    </section>

    <footer>
        <div class="terms-bank">
            <h3>Terms & Conditions:</h3>
            <p><?php echo $invoice_header['terms']; ?> </p>
           
           <div class="bankdetails"> <p><strong>Bank Transfer Details:</strong><br>
            <strong>Bank Name:<strong> <?php echo $bank_name; ?><br>
            <strong>A/C Number:<strong> <?php echo $account_number; ?><br>
            <strong>IFSC Code:<strong> <?php echo $ifsc_code; ?>
        </p></div>
        </div>

        <div class="signature">
            <p>For <strong><?php echo $your_company_name; ?><strong></p>
            <br><br>
            <p>(Authorized Signatory)</p>
        </div>
        
        <p class="powered-by no-print">Powered by Your Awesome Billing Software</p>
    </footer>
</div>

<!-- <button onclick="window.print()" class="print-button no-print">Print Invoice</button> 
<script>
        // Auto-print script
        window.onload = function() {
            window.print();
        }
    </script>-->

</body>

</html>