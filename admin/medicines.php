<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../includes/database.php';

// Handle delete request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $con->prepare("DELETE FROM medicines WHERE medicine_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: medicines.php");
    exit();
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$expiry_filter = isset($_GET['expiry']) ? $_GET['expiry'] : 'all';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "name LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= 's';
}
if ($category !== '') {
    $where[] = "category = ?";
    $params[] = $category;
    $types .= 's';
}
if ($expiry_filter === 'expired') {
    $where[] = "expiry_date < CURDATE()";
} elseif ($expiry_filter === 'near') {
    $where[] = "expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
} elseif ($expiry_filter === 'valid') {
    $where[] = "expiry_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}
elseif ($expiry_filter === 'outofstock') {
    $where[] = "quantity = 0";
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM medicines $where_sql";
$count_stmt = $con->prepare($count_sql);
if (!$count_stmt) {
    die("Count SQL Error: " . $con->error);
}
if ($types) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_stmt->bind_result($total_rows);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_rows / $limit);

// Fetch filtered and paginated data
$sql = "SELECT * FROM medicines $where_sql ORDER BY expiry_date ASC LIMIT ? OFFSET ?";
$stmt = $con->prepare($sql);
if (!$stmt) {
    die("Query SQL Error: " . $con->error);
}

if ($types) {
    $types_full = $types . 'ii';
    $params_full = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($types_full, ...$params_full);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Ensure safe defaults
$search = $search ?? '';
$category = $category ?? '';
$expiry_filter = $expiry_filter ?? 'all';
$result = $result ?? null;
$total_pages = $total_pages ?? 1;
include '../admin/side.php';
include '../admin/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Medicines - PharmaSys</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
      --success: #4caf50;
      --error: #f44336;
      --warning: #ff9800;
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

    .action-links {
      display: flex;
      gap: 15px;
      margin-bottom: 25px;
    }

    .action-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      background: white;
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
      color: var(--primary);
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition);
    }

    .action-link:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
      background: var(--primary);
      color: white;
    }

    .action-link i {
      transition: var(--transition);
    }

    /* Filter section */
    .filter-card {
      background: white;
      border-radius: var(--radius);
      padding: 25px;
      box-shadow: var(--shadow-md);
      margin-bottom: 30px;
      animation: fadeIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .filter-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .filter-header h2 {
      font-size: 1.3rem;
      color: var(--dark);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .filter-form {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--dark);
    }

    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid var(--light-gray);
      border-radius: var(--radius);
      font-size: 1rem;
      transition: var(--transition);
    }

    .form-control:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 20px;
      font-size: 1rem;
      font-weight: 600;
      border-radius: var(--radius);
      border: none;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
    }

    .btn-primary {
      background: linear-gradient(to right, var(--primary), var(--primary-dark));
      color: white;
      box-shadow: 0 4px 14px rgba(67, 97, 238, 0.4);
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 18px rgba(67, 97, 238, 0.5);
    }

    /* Medicine table */
    .table-container {
      background: white;
      border-radius: var(--radius);
      padding: 25px;
      box-shadow: var(--shadow-md);
      overflow: hidden;
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

    .table-wrapper {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }

    th {
      text-align: left;
      padding: 15px;
      background: var(--light);
      color: var(--gray);
      font-weight: 600;
      font-size: 0.9rem;
    }

    td {
      padding: 15px;
      border-bottom: 1px solid var(--light-gray);
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr:hover td {
      background: rgba(67, 97, 238, 0.03);
    }

    /* Status indicators */
    .status-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .expired {
      background: rgba(244, 67, 54, 0.15);
      color: var(--error);
    }

    .near-expiry {
      background: rgba(255, 152, 0, 0.15);
      color: var(--warning);
    }

    .qty-zero {
      background: rgba(244, 67, 54, 0.15);
      color: var(--error);
    }

    .qty-low {
      background: rgba(255, 152, 0, 0.15);
      color: var(--warning);
    }

    .qty-ok {
      background: rgba(76, 175, 80, 0.15);
      color: var(--success);
    }

    /* Action buttons */
    .action-buttons {
      display: flex;
      gap: 10px;
    }

    .action-btn {
      padding: 8px 15px;
      border-radius: var(--radius);
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: var(--transition);
    }

    .btn-edit {
      background: rgba(33, 150, 243, 0.15);
      color: var(--info);
    }

    .btn-edit:hover {
      background: var(--info);
      color: white;
    }

    .btn-delete {
      background: rgba(244, 67, 54, 0.15);
      color: var(--error);
    }

    .btn-delete:hover {
      background: var(--error);
      color: white;
    }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-top: 30px;
    }

    .pagination a, .pagination span {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition);
    }

    .pagination a {
      background: var(--light);
      color: var(--dark);
    }

    .pagination a:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-3px);
    }

    .pagination .active {
      background: var(--primary);
      color: white;
    }

    /* Legend */
    .legend {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 25px;
      padding: 15px;
      background: var(--light);
      border-radius: var(--radius);
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .legend-color {
      width: 16px;
      height: 16px;
      border-radius: 4px;
    }

    .legend-expired { background: rgba(244, 67, 54, 0.15); }
    .legend-near { background: rgba(255, 152, 0, 0.15); }
    .legend-zero { background: rgba(244, 67, 54, 0.15); }
    .legend-low { background: rgba(255, 152, 0, 0.15); }
    .legend-ok { background: rgba(76, 175, 80, 0.15); }

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
      
      .filter-form {
        grid-template-columns: 1fr;
      }
      
      .action-links {
        flex-wrap: wrap;
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
            <h2><i class="fas fa-pills"></i> <span>PharmaSys</span></h2>
        </div>
        
        <a href="dashboard.php" class="nav-item">
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
        
        <div class="action-links">
            <a href="dashboard.php" class="action-link">
               <h1><i class="fas fa-pills"></i> Manage Medicines</h1>
            </a>
            <a href="add_medicine.php" class="action-link">
                <i class="fas fa-plus-circle"></i> Add New Medicine
            </a>
        </div>
        
        <div class="filter-card">
            <div class="filter-header">
                <h2><i class="fas fa-filter"></i> Filter Medicines</h2>
            </div>
            <form method="GET" class="filter-form" role="search">
                <div class="form-group">
                    <label for="search">Search by Name</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Enter medicine name..." value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" class="form-control">
                        <option value="">All Categories</option>
                        <option value="Painkiller" <?= $category === 'Painkiller' ? 'selected' : '' ?>>Painkiller</option>
                        <option value="Antibiotic" <?= $category === 'Antibiotic' ? 'selected' : '' ?>>Antibiotic</option>
                        <option value="Vitamin" <?= $category === 'Vitamin' ? 'selected' : '' ?>>Vitamin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="expiry">Expiry Status</label>
                    <select name="expiry" id="expiry" class="form-control">
                        <option value="all" <?= $expiry_filter === 'all' ? 'selected' : '' ?>>All Medicines</option>
                        <option value="expired" <?= $expiry_filter === 'expired' ? 'selected' : '' ?>>Expired</option>
                        <option value="near" <?= $expiry_filter === 'near' ? 'selected' : '' ?>>Near Expiry (≤30 days)</option>
                        <option value="valid" <?= $expiry_filter === 'valid' ? 'selected' : '' ?>>Valid (>30 days)</option>
                            <option value="outofstock" <?= $expiry_filter === 'outofstock' ? 'selected' : '' ?>>Out of Stock</option>
</select>

                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
        
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-table"></i> Medicine Inventory</h2>
                <div class="results-count">
                    Showing <?= min($limit, $result->num_rows) ?> of <?= $total_rows ?> medicines
                </div>
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Expiry Date</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $today = new DateTime();
                        $threshold = new DateInterval('P30D');
                        
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $expiry = new DateTime($row['expiry_date']);
                                $expiry_class = '';
                                
                                if ($expiry < $today) {
                                    $expiry_class = 'expired';
                                } elseif ($expiry <= (clone $today)->add($threshold)) {
                                    $expiry_class = 'near-expiry';
                                }
                                
                                $qty = intval($row['quantity']);
                                $qty_class = '';
                                
                                if ($qty === 0) {
                                    $qty_class = 'qty-zero';
                                } elseif ($qty < 10) {
                                    $qty_class = 'qty-low';
                                } else {
                                    $qty_class = 'qty-ok';
                                }
                                
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . nl2br(htmlspecialchars(substr($row['description'], 0, 80) . (strlen($row['description']) > 80 ? '...' : ''))) . "</td>";
                                echo "<td><span class='status-badge $qty_class'>" . $qty . "</span></td>";
                                echo "<td>RS " . number_format($row['price'], 2) . "</td>";
                                echo "<td><span class='status-badge $expiry_class'>" . htmlspecialchars($row['expiry_date']) . "</span></td>";
                                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                                echo "<td class='action-buttons'>
                                    <a href='edit_medicine.php?id={$row['medicine_id']}' class='action-btn btn-edit'>
                                        <i class='fas fa-edit'></i> Edit
                                    </a>
                                    <a href='medicines.php?delete={$row['medicine_id']}' class='action-btn btn-delete' onclick='return confirm(\"Are you sure you want to delete this medicine?\");'>
                                        <i class='fas fa-trash-alt'></i> Delete
                                    </a>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align: center; padding: 30px; color: var(--gray);'>No medicines found matching your criteria</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color legend-expired"></div>
                    <span>Expired Medicine</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color legend-near"></div>
                    <span>Near Expiry (≤30 days)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color legend-zero"></div>
                    <span>Out of Stock (0)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color legend-low"></div>
                    <span>Low Stock (&lt;10)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color legend-ok"></div>
                    <span>Sufficient Stock (&ge;10)</span>
                </div>
            </div>
            
            <nav class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a>';
                    if ($start_page > 2) echo '<span>...</span>';
                }
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                       class="<?= $i === $page ? 'active' : '' ?>">
                       <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    if ($end_page < $total_pages - 1) echo '<span>...</span>';
                    echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a>';
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</div>

<script>
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    
    mobileMenuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
    
    // Add animations to table rows
    document.addEventListener('DOMContentLoaded', () => {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            setTimeout(() => {
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, 100 * index);
        });
        
        // Add hover effect to action buttons
        const actionBtns = document.querySelectorAll('.action-btn');
        actionBtns.forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'scale(1.05)';
            });
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'scale(1)';
            });
        });
    });
    
    // Confirm before deleting
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to delete this medicine? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
</script>

<!-- Font Awesome for icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>