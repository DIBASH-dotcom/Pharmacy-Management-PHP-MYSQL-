<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../includes/database.php';

// Fetch distinct staff names from users who have sales
$staff_list = [];
$staff_res = $con->query("
    SELECT DISTINCT u.full_name 
    FROM sales s
    JOIN users u ON s.user_id = u.user_id
    ORDER BY u.full_name ASC
");
while ($row = $staff_res->fetch_assoc()) {
    $staff_list[] = $row['full_name'];
}

// Handle filters
$filter_month = $_GET['month'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_staff = $_GET['staff'] ?? '';

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

// Calculate stats for the dashboard
$total_sales = 0;
$sales_count = 0;
$sales_data = [];
$monthly_data = array_fill(1, 12, 0); // Initialize monthly data array

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $total_sales += $row['total_amount'];
        $sales_count++;
        $sales_data[] = $row;
        
        $month = date('n', strtotime($row['sale_date']));
        $monthly_data[$month] += $row['total_amount'];
    }
}
$average_sale = $sales_count > 0 ? $total_sales / $sales_count : 0;

// Reset pointer for later use
$result->data_seek(0);
include 'side.php';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border: #dee2e6;
            --success: #4cc9f0;
            --danger: #e63946;
            --warning: #ff9e00;
            --info: #4895ef;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 200px;
            background: white;
            color: white;
            padding: 20px 0;
            transition: var(--transition);
            box-shadow: 3px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
            
        }

        .logo i {
            font-size: 28px;
            margin-right: 12px;
            color: blue;
        }

        .logo h1 {
            font-size: 22px;
            font-weight: 600;
        }

        .nav-links {
            list-style: none;
            padding: 0 15px;
        }

        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            border-radius: 6px;
            transition: var(--transition);
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }

        .nav-links a i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
            transition: var(--transition);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
        }

        .user-role {
            font-size: 14px;
            color: var(--gray);
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
        }

        .card-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .card-1 .card-icon { background: var(--primary); }
        .card-2 .card-icon { background: var(--accent); }
        .card-3 .card-icon { background: var(--success); }
        .card-4 .card-icon { background: var(--warning); }

        .card-title {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .card-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .card-info {
            font-size: 14px;
            color: var(--gray);
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background: var(--light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #6506a5;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        /* Chart Section */
        .chart-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .chart-container {
            height: 300px;
            margin-top: 20px;
            position: relative;
        }

        /* Table Section */
        .table-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            overflow-x: auto;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 800px;
        }

        thead {
            background: var(--primary);
            color: white;
        }

        th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
        }

        th:first-child {
            border-top-left-radius: 8px;
        }

        th:last-child {
            border-top-right-radius: 8px;
        }

        tbody tr {
            transition: var(--transition);
        }

        tbody tr:nth-child(even) {
            background: var(--light);
        }

        tbody tr:hover {
            background: rgba(67, 97, 238, 0.05);
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            color: var(--dark);
        }

        tfoot td {
            font-weight: 700;
            background: var(--light-gray);
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-completed {
            background: rgba(76, 201, 240, 0.15);
            color: var(--success);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: var(--gray);
            font-size: 14px;
            margin-top: 30px;
            border-top: 1px solid var(--border);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .main-content {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-info {
                align-self: flex-end;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .card {
                padding: 15px;
            }
            
            .filter-section, .chart-section, .table-section {
                padding: 20px 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: flex-end;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card, .filter-section, .chart-section, .table-section {
            animation: fadeIn 0.5s ease-out;
        }

        .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.2s; }
        .card:nth-child(4) { animation-delay: 0.3s; }
        .filter-section { animation-delay: 0.4s; }
        .chart-section { animation-delay: 0.5s; }
        .table-section { animation-delay: 0.6s; }

        /* Print Button */
        .print-button {
            background: var(--warning);
        }

        .print-button:hover {
            background: #e08d00;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
                <h1>Sales Analytics</h1>
            </div>
            
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div>
                    
                </div>
                <div class="user-info">
                    
                    <div class="user-details">
                        
                    </div>
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card card-1">
                    <div class="card-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="card-title">Total Sales</div>
                    <div class="card-value">RS <?= number_format($total_sales, 2) ?></div>
                    <div class="card-info">All-time revenue</div>
                </div>
                
                <div class="card card-2">
                    <div class="card-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="card-title">Transactions</div>
                    <div class="card-value"><?= $sales_count ?></div>
                    <div class="card-info">Completed orders</div>
                </div>
                
                <div class="card card-3">
                    <div class="card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="card-title">Avg. Sale</div>
                    <div class="card-value">RS <?= number_format($average_sale, 2) ?></div>
                    <div class="card-info">Per transaction</div>
                </div>
                
                <div class="card card-4">
                    <div class="card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="card-title">Staff</div>
                    <div class="card-value"><?= count($staff_list) ?></div>
                    <div class="card-info">Active sales staff</div>
                </div>
            </div>

            <!-- Filter Section -->
            <section class="filter-section">
                <h2 class="section-title"><i class="fas fa-filter"></i> Filter Reports</h2>
                
                <form method="GET">
                    <div class="filter-form">
                        <div class="form-group">
                            <label for="staff">Staff Member</label>
                            <select name="staff" id="staff" class="form-control">
                                <option value="">All Staff</option>
                                <?php foreach ($staff_list as $staff): ?>
                                    <option value="<?= htmlspecialchars($staff) ?>" <?= ($staff === $filter_staff) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($staff) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="month">Month</label>
                            <select name="month" id="month" class="form-control">
                                <option value="">All Months</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= ($m == $filter_month) ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="year">Year</label>
                            <select name="year" id="year" class="form-control">
                                <option value="">All Years</option>
                                <?php
                                $current_year = date('Y');
                                for ($y = $current_year; $y >= $current_year - 5; $y--): ?>
                                    <option value="<?= $y ?>" <?= ($y == $filter_year) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        
                        <button type="button" class="btn print-button" onclick="printReport()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-redo"></i> Reset Filters
                        </button>
                    </div>
                </form>
            </section>

            <!-- Sales Table -->
            <section class="table-section">
                <h2 class="section-title"><i class="fas fa-table"></i> Sales Records</h2>
                <p>Detailed transaction data based on current filters</p>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Staff</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()): 
                                    $formatted_date = date("M d, Y", strtotime($row['sale_date']));
                            ?>
                            <tr>
                                <td>#<?= htmlspecialchars($row['sale_id']) ?></td>
                                <td><?= $formatted_date ?></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= htmlspecialchars($row['staff_name']) ?></td>
                                <td><strong>Rs <?= number_format($row['total_amount'], 2) ?></strong></td>
                            </tr>
                            <?php endwhile;
                            } else {
                                echo "<tr><td colspan='5' style='text-align:center; padding:30px;'>No sales records found with current filters</td></tr>";
                            }
                            ?>
                        </tbody>
                        <?php if ($total_sales > 0): ?>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:right; font-weight:700;">Grand Total:</td>
                                <td style="font-weight:700;">Rs <?= number_format($total_sales, 2) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </section>
            
            <!-- Footer -->
            <div class="footer">
                <p>&copy; <?= date('Y') ?> Sales Analytics Dashboard. All rights reserved.</p>
            </div>
        </main>
    </div>

    <script>
        function printReport() {
            const params = new URLSearchParams(window.location.search);
            const printWindow = window.open('reports_print.php?' + params.toString(), '_blank');
            printWindow.focus();
        }
        
        // Reset form functionality
        document.querySelector('button[type="reset"]').addEventListener('click', function() {
            document.getElementById('staff').value = '';
            document.getElementById('month').value = '';
            document.getElementById('year').value = '';
        });
        
        // Simple animation for table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
            });
        });
    </script>
</body>
</html>