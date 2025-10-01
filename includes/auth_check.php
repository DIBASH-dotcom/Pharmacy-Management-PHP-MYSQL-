<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Role check function
function requireRole($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        // Redirect user based on their role
        if ($_SESSION['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } elseif ($_SESSION['role'] === 'staff') {
            header("Location: ../staff/dashboard.php");
        } else {
            header("Location: ../login.php");
        }
        exit();
    }
}
