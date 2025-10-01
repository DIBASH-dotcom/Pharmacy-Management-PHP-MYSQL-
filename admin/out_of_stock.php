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
    header("Location: out_of_stock.php");
    exit();
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Only show out-of-stock medicines
$where_sql = "WHERE quantity = 0";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM medicines $where_sql";
$count_result = $con->query($count_sql);
$total_rows = $count_result->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

// Fetch paginated data
$sql = "SELECT * FROM medicines $where_sql ORDER BY expiry_date ASC LIMIT $limit OFFSET $offset";
$result = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Out of Stock Medicines - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: left; }
        .expired { background-color: #f8d7da; }
        .near-expiry { background-color: #fff3cd; }
        .pagination a { padding: 5px 10px; margin: 2px; background: #eee; text-decoration: none; }
        .pagination .active { font-weight: bold; background: #ccc; }
    </style>
</head>
<body>

<h1>Out of Stock Medicines</h1>
<p>
    <a href="dashboard.php">← Dashboard</a> |
    <a href="add_medicine.php">Add New Medicine</a> |
    <a href="medicines.php">All Medicines</a> |
    <a href="../logout.php">Logout</a>
</p>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Quantity</th>
            <th>Price ($)</th>
            <th>Expiry Date</th>
            <th>Category</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $today = new DateTime();
        $threshold = new DateInterval('P30D');

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $expiry = new DateTime($row['expiry_date']);
                $class = '';

                if ($expiry < $today) {
                    $class = 'expired';
                } elseif ($expiry <= (clone $today)->add($threshold)) {
                    $class = 'near-expiry';
                }

                echo "<tr class='$class'>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . nl2br(htmlspecialchars($row['description'])) . "</td>";
                echo "<td>" . intval($row['quantity']) . "</td>";
                echo "<td>" . number_format($row['price'], 2) . "</td>";
                echo "<td>" . htmlspecialchars($row['expiry_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                echo "<td>
                    <a href='edit_medicine.php?id={$row['medicine_id']}'>Edit</a> |
                    <a href='out_of_stock.php?delete={$row['medicine_id']}' onclick='return confirm(\"Delete this medicine?\");'>Delete</a>
                </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No out-of-stock medicines found.</td></tr>";
        }
        ?>
    </tbody>
</table>

<p><small>Legend: <span style="background:#f8d7da;padding:3px;">Expired</span>, <span style="background:#fff3cd;padding:3px;">Near Expiry (30 days)</span></small></p>

<!-- Pagination -->
<div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>

</body>
</html>
