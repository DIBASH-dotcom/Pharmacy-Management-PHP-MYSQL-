<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../includes/database.php';

// Fetch medicine count
$med_total = $con->query("SELECT COUNT(*) FROM medicines")->fetch_row()[0];

// Fetch staff count
$staff_total = $con->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetch_row()[0];

// Fetch total sales
$sales_total = 0;
$sales_query = $con->query("SELECT SUM(total_amount) FROM sales");
if ($sales_query) {
    $row = $sales_query->fetch_row();
    $sales_total = $row[0] ?? 0;
}

// Fetch out of stock count
$out_of_stock_total = $con->query("SELECT COUNT(*) FROM medicines WHERE quantity = 0")->fetch_row()[0];

// Load profile picture
$user_id = $_SESSION['user_id'];
$profile_pic = 'default.png';
$stmt = $con->prepare("SELECT profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($db_profile_pic);
if ($stmt->fetch() && $db_profile_pic) {
    $profile_pic = $db_profile_pic;
}
$stmt->close();

// Fetch recent sales
$recent_sales = [];
$sales_res = $con->query("SELECT sale_id, total_amount, sale_date FROM sales ORDER BY sale_date DESC LIMIT 5");
if ($sales_res) {
    while ($row = $sales_res->fetch_assoc()) {
        $recent_sales[] = $row;
    }
}

// Fetch recent added medicines
$recent_medicines = [];
$med_res = $con->query("SELECT name, created_at FROM medicines ORDER BY created_at DESC LIMIT 5");
if ($med_res) {
    while ($row = $med_res->fetch_assoc()) {
        $recent_medicines[] = $row;
    }
}
include 'side.php';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - PharmaSys</title>
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
            --success: #4caf50;
            --warning: #ff9800;
            --error: #f44336;
            --info: #2196f3;
            --radius: 12px;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar styling */
        .sidebar {
            width: 260px;
            background: linear-gradient(160deg, #2c3e50 0%, #1a2530 100%);
            color: white;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            box-shadow: 3px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
            transition: var(--transition);
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4rem;
            color: white;
        }

        .sidebar-header i {
            background: var(--primary);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid var(--accent);
        }

        .nav-item i {
            width: 24px;
            text-align: center;
        }

        /* Main content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
            transition: var(--transition);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 i {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 8px 16px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .user-profile:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .user-profile span {
            font-weight: 600;
        }

        /* Dashboard stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }

        .stat-card:nth-child(1)::before { background: var(--primary); }
        .stat-card:nth-child(2)::before { background: var(--secondary); }
        .stat-card:nth-child(3)::before { background: var(--success); }
        .stat-card:nth-child(4)::before { background: var(--warning); }

        .stat-card .icon {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            opacity: 0.2;
          
        }

        .stat-card:nth-child(1) .icon { background: darkpurple; }
        .stat-card:nth-child(2) .icon { background: darkpurple; }
        .stat-card:nth-child(3) .icon { background: darkpurple; }
        .stat-card:nth-child(4) .icon { background: darkpurple }

        .stat-card h3 {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card .label {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Charts and activity */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-container, .activity-container {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow-md);
            animation: fadeIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .chart-header, .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h2, .activity-header h2 {
            font-size: 1.3rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-canvas {
            width: 100%;
            height: 250px;
            position: relative;
        }

        /* Activity timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
            margin-top: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 10px;
            height: calc(100% - 20px);
            width: 2px;
            background: var(--light-gray);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding-left: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            z-index: 1;
        }

        .timeline-item:nth-child(2)::before { background: var(--secondary); }
        .timeline-item:nth-child(3)::before { background: var(--success); }
        .timeline-item:nth-child(4)::before { background: var(--warning); }
        .timeline-item:nth-child(5)::before { background: var(--accent); }

        .timeline-content {
            background: var(--light);
            border-radius: var(--radius);
            padding: 15px;
            box-shadow: var(--shadow-sm);
        }

        .timeline-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .timeline-date {
            font-size: 0.85rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Recent tables */
        .tables-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }

        .recent-table {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow-md);
            animation: fadeIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h2 {
            font-size: 1.3rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px 15px;
            background: var(--light);
            color: var(--gray);
            font-weight: 600;
            font-size: 0.9rem;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: rgba(67, 97, 238, 0.03);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        /* Responsive design */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                overflow: hidden;
            }
            
            .sidebar-header h2 span, .nav-item span {
                display: none;
            }
            
            .main-content {
                margin-left: 80px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
                margin-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 99;
                background: var(--primary);
                color: white;
                border: none;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: var(--shadow-md);
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .dashboard-grid, .tables-container {
                grid-template-columns: 1fr;
            }
        }

        /* Hide mobile menu button by default */
        .mobile-menu-btn {
            display: none;
        }
    </style>
</head>
<body>
<div class="app-container">
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
    
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
          <h2><i class="fas fa-user-gear"></i> <span>Admin Dashboard</span></h2>


        </div>
        
        <a href="dashboard.php" class="nav-item active">
            <i class="fas fa-home"></i> <span>Dashboard</span>
        </a>
        <a href="medicines.php" class="nav-item">
            <i class="fas fa-pills"></i> <span>Medicines</span>
        </a>
        <a href="add_medicine.php" class="nav-item">
            <i class="fas fa-boxes"></i> <span>Add Medicine</span>
        </a>
        <a href="reports.php" class="nav-item">
            <i class="fas fa-shopping-cart"></i> <span>Reporst</span>
        </a>
        <a href="edit_profile.php" class="nav-item">
            <i class="fas fa-users"></i> <span>Edit Profile</span>
        </a>
       
        <a href="add_staff.php" class="nav-item">
            <i class="fas fa-users"></i> <span>Add Staff</span>
        </a>
        <a href="manage_staff.php" class="nav-item">
            <i class="fas fa-cog"></i> <span>Manage Staff</span>
        </a>

        <a href="create_admin.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i> <span>Create Admin</span>
        </a>

        <a href="admin_notifications.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i> <span>Notifications</span>
        </a>
        <a href="../login.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </aside>
    
    <div class="main-content">
        <div class="header">
           
        </div>
        
        <div class="stats-grid">
            <a href="medicines.php" class="stat-card">
                <div class="icon">
                    <i class="fas fa-pills"></i>
                </div>
                <h3>TOTAL MEDICINES</h3>
                <div class="value"><?= $med_total ?></div>
                <div class="label">Available in inventory</div>
            </a>
            
            <a href="manage_staff.php" class="stat-card">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>TOTAL STAFF</h3>
                <div class="value"><?= $staff_total ?></div>
                <div class="label">Registered users</div>
            </a>
            
            <a href="reports.php" class="stat-card">
                <div class="icon">
                 <i class="fas fa-rupee-sign"></i>

                </div>
                <h3>TOTAL SALES</h3>
                <div class="value">RS <?= number_format($sales_total, 2) ?></div>
                <div class="label">All-time revenue</div>
            </a>
            
            <a href="medicines.php?filter=out_of_stock" class="stat-card">
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>OUT OF STOCK</h3>
                <div class="value"><?= $out_of_stock_total ?></div>
                <div class="label">Requires attention</div>
            </a>
        </div>
        
        <div class="dashboard-grid">
            <div class="chart-container">
                <div class="chart-header">
                    <h2><i class="fas fa-chart-pie"></i> Inventory Overview</h2>
                </div>
                <div class="chart-canvas">
                    <canvas id="inventoryChart" width="400" height="250"></canvas>
                </div>
            </div>
            
            <div class="activity-container">
                <div class="activity-header">
                    <h2><i class="fas fa-history"></i> Recent Activity</h2>
                </div>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <div class="timeline-title">Dashboard accessed</div>
                            <div class="timeline-date">
                                <i class="fas fa-clock"></i> Just now
                            </div>
                        </div>
                    </div>
                    
                    <?php if (count($recent_sales) > 0): ?>
                        <?php foreach ($recent_sales as $sale): ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="timeline-title">New Sale: #<?= htmlspecialchars($sale['sale_id']) ?></div>
                                <div class="timeline-date">
                                    <i class="fas fa-clock"></i> <?= htmlspecialchars(date('M d, Y', strtotime($sale['sale_date']))) ?>
                                </div>
                                <div class="timeline-value">RS <?= number_format($sale['total_amount'], 2) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="tables-container">
            <div class="recent-table">
                <div class="table-header">
                    <h2><i class="fas fa-shopping-cart"></i> Recent Sales</h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_sales) > 0): ?>
                                <?php foreach ($recent_sales as $sale): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($sale['sale_id']) ?></td>
                                    <td><?= htmlspecialchars(date('M d, Y', strtotime($sale['sale_date']))) ?></td>
                                    <td><strong>Rs <?= number_format($sale['total_amount'], 2) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center;">No recent sales found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="recent-table">
                <div class="table-header">
                    <h2><i class="fas fa-pills"></i> Recently Added Medicines</h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Date Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_medicines) > 0): ?>
                                <?php foreach ($recent_medicines as $med): ?>
                                <tr>
                                    <td><?= htmlspecialchars($med['name']) ?></td>
                                    <td><?= htmlspecialchars(date('M d, Y', strtotime($med['created_at']))) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" style="text-align: center;">No recent medicines found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    
    mobileMenuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
    
    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
        // Inventory chart
        const inventoryCtx = document.getElementById('inventoryChart').getContext('2d');
        const inventoryChart = new Chart(inventoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Medicines', 'Staff', 'Sales', 'Out of Stock'],
                datasets: [{
                    data: [<?= $med_total ?>, <?= $staff_total ?>, <?= $out_of_stock_total ?>, <?= $out_of_stock_total ?>],
                    backgroundColor: [
                        'rgba(67, 97, 238, 0.8)',
                        'rgba(114, 9, 183, 0.8)',
                        'rgba(76, 175, 80, 0.8)',
                        'rgba(255, 152, 0, 0.8)'
                    ],
                    borderColor: [
                        'rgba(67, 97, 238, 1)',
                        'rgba(114, 9, 183, 1)',
                        'rgba(76, 175, 80, 1)',
                        'rgba(255, 152, 0, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        
        // Add pulse animation to stat cards on load
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.animation = 'pulse 0.5s ease';
                setTimeout(() => {
                    card.style.animation = '';
                }, 500);
            }, index * 100);
        });
        
        // Add hover effects to table rows
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.transform = 'translateX(5px)';
            });
            row.addEventListener('mouseleave', () => {
                row.style.transform = '';
            });
        });
    });
</script>

<!-- Font Awesome for icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>