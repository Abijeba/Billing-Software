<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config.php";
include "header.php";

if (empty($_GET['id'])) {
    die("❌ Invalid Request");
}

$pr_id = (int)$_GET['id'];

// --- Fetch return header ---
$sql = "SELECT pr.return_no, pr.bill_date, s.supplier_name 
        FROM purchase_returns pr
        JOIN suppliers s ON pr.supplier_id = s.supplier_id
        WHERE pr.return_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$pr_id]);
$return = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$return) {
    die("❌ Purchase Return not found");
}

// --- Fetch return items ---
$sql_items = "SELECT ri.quantity, ri.rate, ri.discount, ri.gst, ri.line_total, 
                     p.product_name
              FROM purchase_return_items ri
              JOIN products p ON ri.product_id = p.product_id
              WHERE ri.pr_id = ?";
$stmt_items = $pdo->prepare($sql_items);
$stmt_items->execute([$pr_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>View Purchase Return</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Center Heading, Dark Blue & Capital */
        .page-heading {
            text-align: center;
            text-transform: uppercase;
            color: #0d47a1;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 30px;
            
        }

        /* Card style without border */
        .card-custom {
            background: linear-gradient(35deg, #cce7ff, #f4f6f7);
            border: none; /* Removed border */
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 20px;
        }

        /* Table header dark blue and center all data */
        table th, table td {
            text-align: center;
            vertical-align: middle;
        }
        thead.table-dark th {
            background-color: #003366 !important;
            color: white !important;
        }

        /* Gradient Back Button */
        .btn-gradient-back {
            background: linear-gradient(135deg, #adb5bd, #6c757d);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-gradient-back:hover {
            background: linear-gradient(135deg, #868e96, #495057);
            transform: scale(1.03);
        }
        
    </style>
</head>
<body class="container mt-4">

    <!-- Heading -->
    <h2 class="page-heading">Purchase Return Details</h2>

    <!-- Card Container -->
    <div class="card card-custom">
        <table class="table table-bordered w-100">
            <tr>
                <th>Return No</th>
                <td><?= htmlspecialchars($return['return_no']) ?></td>
            </tr>
            <tr>
                <th>Bill Date</th>
                <td><?= date('d-m-Y', strtotime($return['bill_date'])) ?></td>
            </tr>
            <tr>
                <th>Supplier</th>
                <td><?= htmlspecialchars($return['supplier_name']) ?></td>
            </tr>
        </table>
<div class="table-responsive">
        <h4 class="mt-4 fw-bold text-primary">Returned Items</h4>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Product</th>
                    <th>Rate</th>
                    <th>Quantity</th>
                    <th>Discount %</th>
                    <th>GST %</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items): ?>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars($it['product_name']) ?></td>
                            <td><?= number_format($it['rate'], 2) ?></td>
                            <td><?= $it['quantity'] ?></td>
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
        <!-- Back Button Bottom Right -->
        <div class="text-end mt-3">
            <a href="purchase_return_list.php" class="btn btn-gradient-back"> Back</a>
        </div>
    </div>

</body>
</html>


