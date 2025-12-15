<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config.php";
include "header.php";

if (empty($_GET['id'])) {
    die("❌ Invalid Request");
}

$id = (int)$_GET['id'];

// Fetch return header
$sql = "
    SELECT 
        sr.return_id,
        sr.return_no,
        sr.invoice_no,
        sr.invoice_date AS bill_date,
        c.customer_name,
        sr.state,
        sr.tax_mode,
        sr.description
    FROM sales_returns sr
    LEFT JOIN customers c ON sr.customer_id = c.customer_id
    WHERE sr.return_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$return = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$return) {
    die("❌ Sales return not found");
}

// Fetch return items
$sql = "
    SELECT 
        p.product_name AS item_name,
        sri.quantity,
        sri.rate,
        sri.discount,
        sri.gst,
        sri.line_total
    FROM sales_return_items sri
    LEFT JOIN products p ON sri.product_id = p.product_id
    WHERE sri.sr_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Return Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Heading Style with Animation */
        .page-heading {
            text-align: center;
            text-transform: uppercase;
            color: #0d47a1;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 30px;
            animation: fadeInDown 1s ease;
        }

        @keyframes fadeInDown {
            0% { opacity: 0; transform: translateY(-30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Card Style */
        .card-custom {
            background: linear-gradient(135deg, #e8f0ff, #ffffff);
            border: none;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: fadeIn 1.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Table Styles */
        table th, table td {
            text-align: center;
            vertical-align: middle;
        }
        thead th {
            background-color: #140d77 !important;
            color: #fff !important;
        }

        /* Gradient Back Button */
        .btn-gradient-back {
            background: linear-gradient(135deg, #007bff, #003366);
            color: white;
            border: none;
            font-size: 0.9rem;
            padding: 6px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-gradient-back:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #0056b3, #001f4d);
        }

        /* Responsive Table */
        .table-responsive {
            margin-top: 15px;
        }
    </style>
</head>
<body class="container mt-4">

    <!-- Animated Heading -->
    <h2 class="page-heading">Sales Return Details</h2>

    <div class="card card-custom">

        <!-- Header Info Table -->
        <table class="table table-bordered">
            <tr><th>Return No</th><td><?= htmlspecialchars($return['return_no']) ?></td></tr>
            <tr><th>Bill No</th><td><?= htmlspecialchars($return['invoice_no']) ?></td></tr>
            <tr><th>Bill Date</th><td><?= date('d-m-Y', strtotime($return['bill_date'])) ?></td></tr>
            <tr><th>Customer</th><td><?= htmlspecialchars($return['customer_name']) ?></td></tr>
            <tr><th>State</th><td><?= htmlspecialchars($return['state']) ?></td></tr>
            <tr><th>Tax Mode</th><td><?= htmlspecialchars($return['tax_mode']) ?></td></tr>
            <tr><th>Description</th><td><?= htmlspecialchars($return['description']) ?></td></tr>
        </table>

        <!-- Returned Items Table -->
        <h4 class="fw-bold text-primary mt-4">Returned Items</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Discount %</th>
                        <th>GST %</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items): ?>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?= htmlspecialchars($it['item_name']) ?></td>
                                <td><?= $it['quantity'] ?></td>
                                <td><?= number_format($it['rate'], 2) ?></td>
                                <td><?= $it['discount'] ?></td>
                                <td><?= $it['gst'] ?></td>
                                <td><?= number_format($it['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No items found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Back Button -->
        <div class="text-end mt-3">
            <a href="sales_return_list.php" class="btn btn-gradient-back">⬅ Back</a>
        </div>

    </div>
</body>
</html>
