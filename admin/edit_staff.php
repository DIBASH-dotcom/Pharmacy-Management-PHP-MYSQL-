<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../includes/database.php';

if (!isset($_GET['id'])) {
    header("Location: manage_staff.php");
    exit();
}

$staff_id = intval($_GET['id']);
$error = '';
$success = '';

// Fetch staff user data
$stmt = $con->prepare("SELECT username, full_name, email FROM users WHERE user_id = ? AND role = 'staff'");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: manage_staff.php");
    exit();
}
$staff = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    if ($full_name === '' || $email === '') {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        $stmt = $con->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ? AND role = 'staff'");
        $stmt->bind_param("ssi", $full_name, $email, $staff_id);

        if ($stmt->execute()) {
            $success = "Staff details updated successfully.";
            $staff['full_name'] = $full_name;
            $staff['email'] = $email;
        } else {
            $error = "Error updating staff: " . $stmt->error;
        }
        $stmt->close();
    }
}
include 'side.php';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Edit Staff - Admin</title>
<link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
<h1>Edit Staff User</h1>
<p><a href="manage_staff.php">← Back to Staff List</a></p>

<?php if ($error): ?>
    <p class="error"><?=htmlspecialchars($error)?></p>
<?php endif; ?>
<?php if ($success): ?>
    <p class="success"><?=htmlspecialchars($success)?></p>
<?php endif; ?>

<form method="POST" action="edit_staff.php?id=<?= $staff_id ?>" autocomplete="off">
    <p><strong>Username: <?=htmlspecialchars($staff['username'])?></strong></p>

    <label for="full_name">Full Name *</label><br>
    <input type="text" name="full_name" id="full_name" required value="<?=htmlspecialchars($staff['full_name'])?>" /><br><br>

    <label for="email">Email *</label><br>
    <input type="email" name="email" id="email" required value="<?=htmlspecialchars($staff['email'])?>" /><br><br>

    <button type="submit">Update Staff Details</button>
</form>

</body>
</html>
