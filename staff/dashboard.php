<?php
// dashboard.php

// Include header and sidebar
include 'header.php';
include 'side.php';
require '../includes/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'staff';

// Fetch total medicines
$total_meds = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM medicines"))['count'] ?? 0;

// Fetch total sales
$total_sales = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM sales"))['count'] ?? 0;

// Handle search query
$search_query = '';
$medicines = null;

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $search_param = "%" . $search_query . "%";

    $stmt = $con->prepare("SELECT * FROM medicines WHERE name LIKE ? ORDER BY name");
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $medicines = $stmt->get_result();
} else {
    $medicines = mysqli_query($con, "SELECT * FROM medicines ORDER BY name");
}

// Fetch out-of-stock medicines
$out_of_stock = mysqli_query($con, "SELECT * FROM medicines WHERE quantity = 0 ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <style>
:root {
    --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-600: #6c757d;
            --gray-800: #343a40;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --radius: 8px;
            --sidebar-width: 240px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, var(--darker), var(--dark));
    color: var(--text-light);
    min-height: 100vh;
}

.dashboard-container {
    display: flex;
    min-height: 100vh;
    
}

.main-content {
    flex: 1;
    margin-left: 220px;
    padding: 80px 20px 20px;
    transition: var(--transition);
}

.dashboard-header {
  
   
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.welcome-section h1 {
    font-size: 2.2rem;
    background: linear-gradient(90deg, var(--success), var(--primary));
    -webkit-background-clip: text;
    color: transparent;
    margin-bottom: 5px;
}

.user-role {
    background: var(--card-bg);
    color: var(--success);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 25px;
    display: flex;
    flex-direction: column;
    transition: var(--transition);
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: var(--primary);
}

.card-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: var(--success);
}

.card-title {
    font-size: 1.1rem;
    color: var(--text-lighter);
    margin-bottom: 10px;
    font-weight: 500;
}

.card-value {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--text-lighter);
    margin: 5px 0;
}

.search-section,
.table-section,
.outofstock-section {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: var(--shadow);
}

.search-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.search-input {
    flex: 1;
    min-width: 250px;
    padding: 14px 20px;
    border: 1px solid transparent;
    border-radius: 50px;
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-lighter);
    font-size: 1rem;
    transition: var(--transition);
}

.search-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.12);
    border-color: var(--primary);
}

.search-input::placeholder {
    color: red;
    border:2px solid black;;
}

.section-title {
    font-size: 1.5rem;
    margin-bottom: 20px;
    color: var(--text-lighter);
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title i {
    color: var(--success);
}

.btn {
    padding: 14px 28px;
    border: none;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--secondary);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-lighter);
    backdrop-filter: blur(10px);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
}

.table-container {
    overflow-x: auto;
}

.medicines-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    min-width: 800px;
}

.medicines-table th {
    background: rgba(67, 97, 238, 0.2);
    text-align: left;
    padding: 16px 20px;
    font-weight: 600;
    color: var(--success);
    cursor: pointer;
    user-select: none;
    position: relative;
}

.medicines-table th:hover {
    background: rgba(67, 97, 238, 0.3);
}

.medicines-table th::after {
    content: '↕';
    position: absolute;
    right: 15px;
    opacity: 0.5;
    transition: var(--transition);
}

.medicines-table th.sort-asc::after {
    content: '↑';
    opacity: 1;
}

.medicines-table th.sort-desc::after {
    content: '↓';
    opacity: 1;
}

.medicines-table td {
    padding: 14px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text-light);
}

.medicines-table tr:last-child td {
    border-bottom: none;
}

.medicines-table tr:hover td {
    background: rgba(255, 255, 255, 0.03);
}

.stock-warning {
    color: #ff6b6b;
    font-weight: 600;
}

.no-results {
    text-align: center;
    padding: 30px;
    color: var(--gray);
}

.outofstock-section {
    background: rgba(247, 37, 133, 0.1);
    border: 1px solid rgba(247, 37, 133, 0.2);
}

.outofstock-section .section-title,
.outofstock-section .section-title i {
    color: #ff6b6b;
}

.notification-btn {
    background: var(--warning);
    color: ;
    padding: 8px 16px;
    border-radius: 50px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.notification-btn:hover {
    background: #e1156d;
    transform: translateY(-2px);
}

.notification-btn:disabled {
    background: var(--gray);
    cursor: not-allowed;
}

.notification-btn i {
    font-size: 0.9rem;
}

.flash-message {
    padding: 15px 20px;
    border-radius: var(--border-radius);
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(76, 201, 240, 0.15);
    border-left: 4px solid var(--success);
    animation: fadeIn 0.5s ease;
}

.flash-message i {
    color: var(--success);
    font-size: 1.2rem;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Media Queries */
@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
        padding: 100px 15px 20px;
    }

    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .search-form,
    .search-input {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 20px;
    }

    .btn {
        padding: 12px 20px;
        width: 100%;
        justify-content: center;
    }
}
</style>

</head>
<body>
    <div class="dashboard-container">
        <div class="main-content">
            <div class="dashboard-header">
                <div class="welcome-section">
                    <h1>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></h1>
                    <span class="user-role"><?= ucfirst(htmlspecialchars($user_role)) ?></span>
                </div>
            </div>

            <!-- Flash message -->
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="flash-message">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($_SESSION['msg']) ?>
                </div>
                <?php unset($_SESSION['msg']); ?>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="card">
                    <i class="fas fa-capsules card-icon"></i>
                    <h3 class="card-title">Total Medicines</h3>
                    <div class="card-value"><?= $total_meds ?></div>
                </div>
                
                <div class="card">
                    <i class="fas fa-receipt card-icon"></i>
                    <h3 class="card-title">Total Sales</h3>
                    <div class="card-value"><?= $total_sales ?></div>
                </div>
            </div>

            <!-- Search Form -->
            <section class="search-section">
                <h2 class="section-title"><i class="fas fa-search"></i> Medicine Search</h2>
                <form method="GET" action="dashboard.php" class="search-form">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input"
                        placeholder="Search medicine by name..." 
                        value="<?= htmlspecialchars($search_query) ?>"
                        aria-label="Search medicine by name"
                    >
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if ($search_query): ?>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </section>

            <!-- Medicines Table -->
            <section class="table-section">
                <h2 class="section-title"><i class="fas fa-pills"></i> All Medicines</h2>
                <div class="table-container">
                    <table class="medicines-table" aria-describedby="medicines-description">
                        <thead>
                            <tr>
                                <th scope="col" data-sort="medicine_id">ID</th>
                                <th scope="col" data-sort="name">Name</th>
                                <th scope="col" data-sort="quantity">Quantity</th>
                                <th scope="col" data-sort="price">Price</th>
                                <th scope="col" data-sort="expiry_date">Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($medicines && mysqli_num_rows($medicines) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($medicines)): ?>
                            <tr>
                                <td><?= $row['medicine_id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td class="<?= $row['quantity'] == 0 ? 'stock-warning' : '' ?>">
                                    <?= $row['quantity'] ?>
                                </td>
                                <td>RS<?= number_format($row['price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['expiry_date']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-results">No medicines found</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Out of Stock Medicines -->
            <section class="outofstock-section">
                <h2 class="section-title"><i class="fas fa-exclamation-triangle"></i> Out of Stock Medicines</h2>
                <div class="table-container">
                    <table class="medicines-table" aria-describedby="outofstock-description">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Category</th>
                                <th scope="col">Batch</th>
                                <th scope="col">Notify Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($out_of_stock) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($out_of_stock)):
                                $medicine_id = $row['medicine_id'];
                                $sender_id = $_SESSION['user_id'];

                                // Check if notification already exists
                                $check_notify = mysqli_query($con, "SELECT * FROM notifications WHERE medicine_id = $medicine_id AND sender_id = $sender_id AND status = 'unread'");
                                $already_notified = mysqli_num_rows($check_notify) > 0;
                            ?>
                            <tr>
                                <td><?= $medicine_id ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['batch_number']) ?></td>
                                <td>
                                    <?php if ($already_notified): ?>
                                        <button class="notification-btn" disabled>
                                            <i class="fas fa-check"></i> Notified
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="notify_admin.php" style="display:inline;">
                                            <input type="hidden" name="medicine_id" value="<?= $medicine_id ?>">
                                            <button 
                                                type="submit" 
                                                class="notification-btn"
                                                onclick="return confirm('Notify admin about this out-of-stock medicine?');"
                                            >
                                                <i class="fas fa-bell"></i> Notify Admin
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-results">All medicines are in stock</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script>
        // Table sorting functionality
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.querySelector('.medicines-table');
            const headers = table.querySelectorAll('th[data-sort]');
            
            headers.forEach(header => {
                header.addEventListener('click', function() {
                    const sortKey = this.getAttribute('data-sort');
                    const isAscending = this.classList.contains('sort-asc');
                    
                    // Reset all headers
                    headers.forEach(h => {
                        h.classList.remove('sort-asc', 'sort-desc');
                    });
                    
                    // Set new sort direction
                    this.classList.toggle('sort-asc', !isAscending);
                    this.classList.toggle('sort-desc', isAscending);
                    
                    // Sort table
                    sortTable(table, sortKey, isAscending);
                });
            });
            
            function sortTable(table, column, reverse) {
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                rows.sort((a, b) => {
                    const aValue = a.cells[columnIndex(a, column)].textContent.trim();
                    const bValue = b.cells[columnIndex(b, column)].textContent.trim();
                    
                    // Numeric sorting for quantity and price
                    if (column === 'quantity' || column === 'price') {
                        return (parseFloat(aValue) - parseFloat(bValue)) * (reverse ? -1 : 1);
                    }
                    
                    // Date sorting for expiry_date
                    if (column === 'expiry_date') {
                        return (new Date(aValue) - new Date(bValue)) * (reverse ? -1 : 1);
                    }
                    
                    // Default string sorting
                    return aValue.localeCompare(bValue) * (reverse ? -1 : 1);
                });
                
                // Remove existing rows
                while (tbody.firstChild) {
                    tbody.removeChild(tbody.firstChild);
                }
                
                // Add sorted rows
                rows.forEach(row => tbody.appendChild(row));
            }
            
            function columnIndex(row, columnName) {
                const headers = Array.from(table.querySelectorAll('th[data-sort]'));
                return headers.findIndex(h => h.getAttribute('data-sort') === columnName);
            }
            
            // Real-time search filter
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('.medicines-table tbody tr');
                    
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        const rowText = Array.from(cells).map(cell => cell.textContent.toLowerCase()).join(' ');
                        row.style.display = rowText.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>