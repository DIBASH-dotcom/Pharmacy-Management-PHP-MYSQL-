<?php
// Start session safely and check admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$full_name = $_SESSION['full_name'] ?? 'Admin';
$profile_pic = 'default.png';

// Fetch profile pic & full name
if ($user_id) {
    $stmt = $con->prepare("SELECT profile_pic, full_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($db_profile_pic, $db_full_name);
    if ($stmt->fetch()) {
        $full_name = $db_full_name ?: 'Admin';
        if (!empty($db_profile_pic)) {
            $profile_pic = $db_profile_pic;
        }
    }
    $stmt->close();
}

// Filters from GET
$senderName = trim($_GET['sender_name'] ?? '');
$batchNumber = trim($_GET['batch_number'] ?? '');

// Build query with filters using prepared statements
$query = "SELECT n.*, m.name AS med_name, m.batch_number, u.full_name AS sender_name
          FROM notifications n
          JOIN medicines m ON n.medicine_id = m.medicine_id
          JOIN users u ON n.sender_id = u.user_id
          WHERE 1=1 ";

$params = [];
$types = "";

if ($senderName !== '') {
    $query .= " AND u.full_name LIKE ?";
    $params[] = "%$senderName%";
    $types .= "s";
}
if ($batchNumber !== '') {
    $query .= " AND m.batch_number LIKE ?";
    $params[] = "%$batchNumber%";
    $types .= "s";
}

$query .= " ORDER BY n.created_at DESC";

$stmt = $con->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($con->error));
}

if ($params) {
    $bind_names[] = $types;
    foreach ($params as $key => $value) {
        $bind_name = 'bind' . $key;
        $$bind_name = $value;
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

$stmt->execute();
$result = $stmt->get_result();

$activePage = basename(__FILE__);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Notifications</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
  body {
    font-family: Arial, sans-serif;
    margin:0; padding:0; background:#f8f9fa;
  }
  nav.header-nav {
    position: fixed; top:0; left:0; right:0; height:64px; background:#f9fafb;
    display: flex; justify-content: space-between; align-items: center;
    padding: 0 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index:1000;
  }
  .logo {
    font-size: 1.5rem; color:#007bff; display:flex; align-items:center; gap:10px;
  }
  #sidebarToggle {
    display: none; background:#007bff; color:#fff; border:none; padding:6px 10px; border-radius:5px; cursor:pointer;
  }
  .user-info {
    display: flex; align-items:center; gap:12px;
  }
  .user-info img {
    width:44px; height:44px; border-radius:50%; object-fit:cover; border:2px solid #ddd;
  }
  .user-info .user-text {
    font-size:14px; text-align:right;
  }
  #sidebar {
    position: fixed; top: 64px; left:0; width:220px; height: calc(100vh - 64px);
    background: linear-gradient(#007bff, #0056b3); color:#fff; transition: transform 0.3s ease; z-index: 999;
  }
  #sidebar ul {
    list-style:none; margin:0; padding:0;
  }
  #sidebar ul li {
    padding: 15px 20px; cursor:pointer;
  }
  #sidebar ul li.active, #sidebar ul li:hover {
    background: rgba(255,255,255,0.2);
  }
  #sidebar ul li a {
    color: white; text-decoration:none; display:block;
  }
  #mainContent {
    margin-left: 220px; padding: 80px 20px 20px; transition: margin-left 0.3s ease;
  }
  table {
    width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 5px rgba(0,0,0,0.1);
  }
  th, td {
    padding: 12px; border-bottom: 1px solid #ddd; text-align: left;
  }
  th {
    background: #f5f7ff; color: #4361ee;
  }
  tr:hover {
    background: #f1f4ff;
  }
  .status-unread {
    background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 12px; font-weight: 600;
  }
  .status-read {
    background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 12px; font-weight: 600;
  }
  .btn-primary {
    background: #4361ee; color: white; border:none; padding: 6px 12px; border-radius:5px; cursor:pointer; font-weight:600;
  }
  .btn-primary:hover {
    background: #3a56d4;
  }
  .filter-section {
    margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;
  }
  .filter-section input[type="text"] {
    padding: 8px; border: 1px solid #ccc; border-radius: 5px; flex: 1 1 200px;
  }
  .filter-section button {
    padding: 8px 16px; border:none; border-radius:5px; background:#4361ee; color:#fff; cursor:pointer; font-weight:600;
  }
  .filter-section button:hover {
    background:#3a56d4;
  }
  .filter-section a.reset-link {
    color: #4361ee; text-decoration:none; font-weight:600; align-self: center; margin-left: 10px;
  }
  .filter-section a.reset-link:hover {
    text-decoration: underline;
  }
  @media(max-width: 992px) {
    #sidebar {
      transform: translateX(-220px);
    }
    #sidebar.active {
      transform: translateX(0);
    }
    #sidebarToggle {
      display: inline-block;
    }
    #mainContent {
      margin-left: 0;
    }
  }
</style>
</head>
<body>

<nav class="header-nav">
  <div style="display:flex; align-items:center; gap:10px;">
    <button id="sidebarToggle" onclick="toggleSidebar()">☰</button>
    <div class="logo"><i class="fas fa-prescription-bottle-medical"></i> PharmaFlow</div>
  </div>
  <div class="user-info">
    <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture of <?= htmlspecialchars($full_name) ?>">
    <div class="user-text">
      <div><?= htmlspecialchars($full_name) ?> <span>(Admin)</span></div>
      <a href="../logout.php">Logout</a>
    </div>
  </div>
</nav>

<div id="sidebar">
  <ul>
    <li class="<?= $activePage === 'dashboard.php' ? 'active' : '' ?>"><a href="dashboard.php">Dashboard</a></li>
    <li class="<?= $activePage === 'medicines.php' ? 'active' : '' ?>"><a href="medicines.php">Medicines</a></li>
    <li class="<?= $activePage === 'add_medicine.php' ? 'active' : '' ?>"><a href="add_medicine.php">Add Medicine</a></li>
    <li class="<?= $activePage === 'reports.php' ? 'active' : '' ?>"><a href="reports.php">Reports</a></li>
    <li class="<?= $activePage === basename(__FILE__) ? 'active' : '' ?>"><a href="<?= basename(__FILE__) ?>">Notifications</a></li>
    <li><a href="../logout.php">Logout</a></li>
  </ul>
</div>

<div id="mainContent">
  <h2>Admin Notifications</h2>

  <form method="GET" action="" class="filter-section" role="search" aria-label="Filter notifications form">
    <input type="text" name="sender_name" placeholder="Filter by Sender Name" value="<?= htmlspecialchars($senderName) ?>" aria-label="Sender Name filter" />
    <input type="text" name="batch_number" placeholder="Filter by Batch Number" value="<?= htmlspecialchars($batchNumber) ?>" aria-label="Batch Number filter" />
    <button type="submit">Filter</button>
    <a href="<?= basename(__FILE__) ?>" class="reset-link" aria-label="Reset filters">Reset</a>
  </form>

  <?php if ($result && $result->num_rows > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Medicine</th>
        <th>Batch Number</th>
        <th>Message</th>
        <th>Sender</th>
        <th>Status</th>
        <th>Created At</th>
        <th>Action</th>
        <th>IT</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['med_name']) ?></td>
        <td><?= htmlspecialchars($row['batch_number']) ?></td>
        <td><?= htmlspecialchars($row['message']) ?></td>
        <td><?= htmlspecialchars($row['sender_name']) ?></td>
        <td>
          <?php if (strtolower($row['status']) === 'unread'): ?>
            <span class="status-unread">Unread</span>
          <?php else: ?>
            <span class="status-read">Read</span>
          <?php endif; ?>
        </td>
        <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
        <td>
          <?php if (strtolower($row['status']) === 'unread'): ?>
            <form method="POST" action="mark_read.php" style="display:inline;">
              <input type="hidden" name="notification_id" value="<?= (int)$row['id'] ?>">
              <button type="submit" class="btn-primary">Mark as Read</button>
            </form>
          <?php else: ?>
            <em>--</em>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
  <p>No notifications found.</p>
  <?php endif; ?>
</div>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
  }
</script>

</body>
</html>
