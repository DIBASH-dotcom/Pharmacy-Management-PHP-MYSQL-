<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
  /* Sidebar styles */
  #sidebar {
    position: fixed;
    top: 64px; /* below header */
    left: 0;
    width: 220px;
    height: calc(100vh - 64px);
    background: linear-gradient(180deg, #007bff, #2a4ad3);
    color: white;
    overflow-y: auto;
    padding-top: 20px;
    box-sizing: border-box;
    transition: transform 0.3s ease;
    z-index: 1050;
    transform: translateX(0);
  }
  /* Hide sidebar on mobile by default */
  @media (max-width: 992px) {
    #sidebar {
      transform: translateX(-100%);
    }
    #sidebar.active {
      transform: translateX(0);
      box-shadow: 2px 0 8px rgba(0,0,0,0.3);
    }
  }
  /* Nav list */
  #sidebar nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  #sidebar nav ul li {
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
  }
  #sidebar nav ul li a {
    display: block;
    padding: 14px 20px;
    color: #cfd8dc;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.2s ease, color 0.2s ease;
  }
  #sidebar nav ul li a:hover,
  #sidebar nav ul li a.active {
    background-color: #0056b3;
    color: white;
  }
  #sidebar nav ul li a i {
    margin-right: 10px;
  }
  /* Sidebar header */
  #sidebar .header {
    display: flex;
    align-items: center;
    padding: 0 20px 20px 20px;
    font-size: 1.5rem;
    font-weight: bold;
    gap: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
  }
  #sidebar .header i {
    font-size: 1.8rem;
  }
</style>

<div id="sidebar" role="navigation" aria-label="Sidebar navigation">
  <header class="header">
    <i class="fas fa-user-shield" aria-hidden="true"></i>
    <span>Admin Panel</span>
  </header>

  <nav>
    <ul>
      <li><a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
      <li><a href="medicines.php" class="<?= $current_page === 'medicines.php' ? 'active' : '' ?>"><i class="fas fa-pills"></i> Medicines</a></li>
      <li><a href="add_medicine.php" class="<?= $current_page === 'add_medicine.php' ? 'active' : '' ?>"><i class="fas fa-plus-square"></i> Add Medicine</a></li>
      <li><a href="reports.php" class="<?= $current_page === 'reports.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Reports</a></li>
      <li><a href="edit_profile.php" class="<?= $current_page === 'edit_profile.php' ? 'active' : '' ?>"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
      <li><a href="add_staff.php" class="<?= $current_page === 'add_staff.php' ? 'active' : '' ?>"><i class="fas fa-user-plus"></i> Add Staff</a></li>
      <li><a href="manage_staff.php" class="<?= $current_page === 'manage_staff.php' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> Manage Staff</a></li>
      <li><a href="create_admin.php" class="<?= $current_page === 'create_admin.php' ? 'active' : '' ?>"><i class="fas fa-user-shield"></i> Create Admin</a></li>
      <li><a href="admin_notifications.php" class="<?= $current_page === 'admin_notifications.php' ? 'active' : '' ?>"><i class="fas fa-bell"></i> Notifications</a></li>
      <li><a href="../login.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </nav>
</div>

<script>
  // Sidebar toggle is handled by header.php toggleSidebar() function
  // But if you want to ensure toggle here, define it too
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
  }

  // Optional: Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    // If click is outside sidebar and toggle button, close sidebar on mobile
    if (
      sidebar.classList.contains('active') &&
      !sidebar.contains(event.target) &&
      toggleBtn &&
      !toggleBtn.contains(event.target) &&
      window.innerWidth <= 992
    ) {
      sidebar.classList.remove('active');
    }
  });
</script>
