<?php
require_once 'config.php';
// Function to calculate the Opening Stock (Closing Stock of the day BEFORE the start date)
function get_opening_stock($pdo, $product_name, $start_date) {
    // 1. Get the fixed initial stock from the master products table
    $sql_initial = "SELECT stock_quantity FROM products WHERE product_name = ?";
    $stmt_initial = $pdo->prepare($sql_initial);
    $stmt_initial->execute([$product_name]);
    $initial_master_stock = $stmt_initial->fetchColumn() ?: 0.00; 
    
    // We will use the original $start_date directly in the query, 
    // but change the SQL operator from '<=' to '<'
    $report_start_date = $start_date; // Use this variable for binding

    // The complex SQL to calculate Historical Movement 
    // (transactions BEFORE the report date starts)
    $sql_opening = "
        SELECT
            (
                IFNULL(pur.total_purchased, 0) 
                - IFNULL(prr.total_pr, 0) 
                - IFNULL(sal.total_sold, 0) 
                + IFNULL(srr.total_sr, 0)
            ) AS historical_movement
        FROM
            (SELECT DISTINCT product_name FROM purchase_invoice_items UNION SELECT DISTINCT product_name FROM sales_invoice_items) pr

        LEFT JOIN
            (SELECT pii.product_name, SUM(pii.quantity) AS total_purchased 
             FROM purchase_invoice_items pii
             JOIN purchase_invoices pi ON pii.pi_id = pi.pi_id  
             WHERE pi.pi_date < ? AND pii.product_name = ?  /* CHANGED: <= to < */
             GROUP BY pii.product_name) pur ON pr.product_name = pur.product_name

        LEFT JOIN
            (SELECT p.product_name, SUM(pri.quantity) AS total_pr
             FROM purchase_return_items pri
             JOIN purchase_returns prt ON pri.pr_id = prt.return_id 
             JOIN products p ON pri.product_id = p.product_id
             WHERE prt.bill_date < ? AND p.product_name = ?  /* CHANGED: <= to < */
             GROUP BY p.product_name) prr ON pr.product_name = prr.product_name

        LEFT JOIN
            (SELECT sii.product_name, SUM(sii.quantity) AS total_sold 
             FROM sales_invoice_items sii
             JOIN sales_invoices si ON sii.si_id = si.si_id  
             WHERE si.si_date < ? AND sii.product_name = ?  /* CHANGED: <= to < */
             GROUP BY sii.product_name) sal ON pr.product_name = sal.product_name

        LEFT JOIN
            (SELECT p.product_name, SUM(sri.quantity) AS total_sr
             FROM sales_return_items sri
             JOIN sales_returns srt ON sri.sr_id = srt.return_id 
             JOIN products p ON sri.product_id = p.product_id
             WHERE srt.return_date < ? AND p.product_name = ?  /* CHANGED: <= to < */
             GROUP BY p.product_name) srr ON pr.product_name = srr.product_name
        
        WHERE pr.product_name = ?
    ";

    try {
        $stmt_open = $pdo->prepare($sql_opening);
        
        // Bind 4 dates (now all are $start_date) and 5 product names
        $bind_array_open = [
            $report_start_date, $product_name, // Purchase
            $report_start_date, $product_name, // P. Return
            $report_start_date, $product_name, // Sales
            $report_start_date, $product_name, // S. Return
            $product_name // Main WHERE clause
        ];
        
        $stmt_open->execute($bind_array_open);
        $result = $stmt_open->fetch(PDO::FETCH_ASSOC);

        // Extract the historical movement.
        $historical_movement = $result ? $result['historical_movement'] : 0.00;

        // SUM the two parts: Initial Master Stock (100) + Historical Movement (transactions before today)
        $final_opening_stock = $initial_master_stock + $historical_movement;
        
        return $final_opening_stock;

    } catch (PDOException $e) {
        error_log("Opening Stock Query Error: " . $e->getMessage());
        return 0.00; // Return 0 on error
    }
}
?>