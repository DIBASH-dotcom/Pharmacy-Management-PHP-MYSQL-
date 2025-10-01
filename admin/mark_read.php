<?php
session_start();
require '../includes/database.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../staff/dashboard.php');
    exit();
}

// Check notification_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);

    // Update notification status to 'read'
    $stmt = $con->prepare("UPDATE notifications SET status = 'read' WHERE id = ?");
    $stmt->bind_param("i", $notification_id);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Notification marked as read.";
    } else {
        $_SESSION['msg'] = "Failed to update notification status.";
    }
} else {
    $_SESSION['msg'] = "Invalid request.";
}

header("Location: admin_notifications.php");
exit();
