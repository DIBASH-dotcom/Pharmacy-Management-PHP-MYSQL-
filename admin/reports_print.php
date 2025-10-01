<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../includes/database.php';

// Handle filters from GET
$filter_month = isset($_GET['month']) ? $_GET['month'] : '';
$filter_year = isset($_GET['year']) ? $_GET['year'] : '';
$filter_staff = isset($_GET['staff']) ? $_GET['staff'] : '';

$where = [];
$params = [];
$types = '';

if ($filter_month !== '') {
    $where[] = 'MONTH(s.sale_date) = ?';
    $params[] = $filter_month;
    $types .= 'i';
}
if ($filter_year !== '') {
    $where[] = 'YEAR(s.sale_date) = ?';
    $params[] = $filter_year;
    $types .= 'i';
}
if ($filter_staff !== '') {
    $where[] = 'u.full_name = ?';
    $params[] = $filter_staff;
    $types .= 's';
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT s.sale_id, s.sale_date, s.customer_name, s.total_amount, u.full_name AS staff_name 
    FROM sales s
    JOIN users u ON s.user_id = u.user_id
    $where_sql
    ORDER BY s.sale_date DESC
";

$stmt = $con->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Printable Sales Report</title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    h1 { text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    @media print {
        button { display: none; }
    }
</style>
</head>
<body>

<h1>PHARMANCY</h1>
<button onclick="window.print()">Print this page</button>

<table>
    <thead>
        <tr>
            <th>Sale ID</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Staff</th>
            <th>Total Amount (RS)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $grand_total = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $grand_total += $row['total_amount'];
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['sale_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['sale_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['staff_name']) . "</td>";
                echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No sales records found.</td></tr>";
        }
        ?>
    </tbody>
    <?php if ($grand_total > 0): ?>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align:right;">Grand Total:</th>
                <th>RS <?= number_format($grand_total, 2) ?></th>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>

</body>
</html>
