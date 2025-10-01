<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth_check.php';
requireRole('admin'); // Admin role required

$user_id = $_SESSION['user_id'] ?? null;
$full_name = $_SESSION['full_name'] ?? 'Admin';
$profile_pic = 'default.png'; // Keep same image path as your new code

if ($user_id) {
    require_once __DIR__ . '/../includes/database.php';

    $stmt = $con->prepare("SELECT profile_pic, full_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($db_profile_pic, $db_full_name);
    if ($stmt->fetch()) {
        $full_name = $db_full_name ?: 'Admin';
        if (!empty($db_profile_pic)) {
            $profile_pic = $db_profile_pic; // Do NOT prepend "../"
        }
    }
    $stmt->close();
}
?>

<style>
  nav.header-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem 1.5rem;
    background-color: #f9fafb;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 64px;
    z-index: 1100;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
  }

  nav.header-nav .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: bold;
    font-size: 1.5rem;
    color: #007bff;
  }

  nav.header-nav .logo i {
    font-size: 1.8rem;
  }

  #sidebarToggle {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 12px;
    font-size: 20px;
    border-radius: 5px;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
  }

  @media (max-width: 992px) {
    #sidebarToggle {
      display: flex;
      margin-right: 10px;
    }
  }

  nav.header-nav .user-info {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  nav.header-nav .user-info img {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ddd;
    transition: transform 0.3s;
    cursor: pointer;
  }

  nav.header-nav .user-info img:hover {
    transform: scale(1.1);
    border-color: #007bff;
  }

  nav.header-nav .user-info .user-text {
    text-align: right;
    font-size: 14px;
    color: #111;
  }

  nav.header-nav .user-info .user-text span {
    font-weight: bold;
  }

  nav.header-nav .user-info .user-text a {
    font-size: 12px;
    color: #007bff;
    text-decoration: none;
    margin-top: 2px;
    display: inline-block;
  }

  nav.header-nav .user-info .user-text a:hover {
    text-decoration: underline;
  }
</style>

<nav class="header-nav" role="navigation" aria-label="Main header navigation">
  <div style="display: flex; align-items: center; gap: 10px;">
    <button id="sidebarToggle" aria-label="Toggle sidebar menu" onclick="toggleSidebar()">☰</button>
    <div class="logo" aria-label="Site logo">
      <i class="fas fa-prescription-bottle-medical" aria-hidden="true"></i>
      <span>PharmaFlow</span>
    </div>
  </div>

  <div class="user-info" aria-label="User profile info">
    <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture of <?= htmlspecialchars($full_name) ?>" />
    <div class="user-text">
      <div><?= htmlspecialchars($full_name) ?> <span>(Admin)</span></div>
      <a href="../logout.php" aria-label="Logout">Logout</a>
    </div>
  </div>
</nav>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
      sidebar.classList.toggle('active');
    }
  }
</script>
