<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once __DIR__ . '/../includes/database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Please log in.");
}

// Fetch current admin info
$stmt = $con->prepare("SELECT full_name, dob, location, profile_pic, password FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validate full name
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    // Validate DOB format (optional)
    if (!empty($dob) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        $errors[] = "Date of Birth must be in YYYY-MM-DD format.";
    }

    // Password change verification
    if (!empty($password) || !empty($password_confirm)) {
        if (empty($old_password)) {
            $errors[] = "Old password is required to change your password.";
        } elseif (!password_verify($old_password, $user['password'])) {
            $errors[] = "Old password is incorrect.";
        }
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $errors[] = "New password must be at least 6 characters.";
            }
            if ($password !== $password_confirm) {
                $errors[] = "New password confirmation does not match.";
            }
        }
    }

    // Profile picture upload handling
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $allowed_exts = ['jpg', 'jpeg', 'png'];

        if ($_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading image.";
        } elseif (!in_array($_FILES['profile_pic']['type'], $allowed_types)) {
            $errors[] = "Only JPG and PNG images are allowed.";
        } else {
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_exts)) {
                $errors[] = "Only JPG and PNG image files are allowed.";
            }
            $image_info = getimagesize($_FILES['profile_pic']['tmp_name']);
            if ($image_info === false || !in_array($image_info['mime'], $allowed_types)) {
                $errors[] = "Uploaded file is not a valid JPG or PNG image.";
            }
        }
        if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Image size must be less than 2MB.";
        }
    }

    if (empty($errors)) {
        $profile_pic_path = null;

        // Save uploaded profile picture (same folder as this script)
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $new_filename = 'profile_' . $user_id . '.' . $ext;
            $upload_path = __DIR__ . '/' . $new_filename;

            if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to save profile picture.";
            } else {
                $profile_pic_path = $new_filename;
            }
        }

        if (empty($errors)) {
            $params = [];
            $types = '';

            $update_fields = "full_name = ?, dob = ?, location = ?";
            $params[] = &$full_name;
            $params[] = &$dob;
            $params[] = &$location;
            $types .= 'sss';

            if ($profile_pic_path !== null) {
                $update_fields .= ", profile_pic = ?";
                $params[] = &$profile_pic_path;
                $types .= 's';
            }

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_fields .= ", password = ?";
                $params[] = &$hashed_password;
                $types .= 's';
            }

            $update_fields .= " WHERE user_id = ?";
            $params[] = &$user_id;
            $types .= 'i';

            $update_query = "UPDATE users SET $update_fields";

            $stmt = $con->prepare($update_query);
            if ($stmt === false) {
                $errors[] = "Prepare failed: " . $con->error;
            } else {
                array_unshift($params, $types);
                call_user_func_array([$stmt, 'bind_param'], $params);

                if ($stmt->execute()) {
                    $success = "Profile updated successfully.";
                    $_SESSION['full_name'] = $full_name;

                    // Refresh user data
                    $stmt->close();
                    $stmt = $con->prepare("SELECT full_name, dob, location, profile_pic, password FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();
                } else {
                    $errors[] = "Failed to update profile: " . $stmt->error;
                }
            }
        }
    }
}
include 'side.php';
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56e4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e7f4 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 200px;
            background: white;
            color: white;
            padding: 1.5rem 0;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
            transition: var(--transition);
        }

        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-header i {
            font-size: 1.8rem;
            background: rgba(255, 255, 255, 0.15);
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 1.5rem;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left: 4px solid var(--success);
        }

        .sidebar-menu a i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 i {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--gray);
            color: var(--gray);
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background: #fafbfc;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            background: white;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle i {
            position: absolute;
            right: 15px;
            top: 42px;
            color: var(--gray);
            cursor: pointer;
        }

        .profile-pic-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            background: var(--light);
        }

        .file-upload {
            position: relative;
            display: inline-block;
        }

        .file-upload-label {
            padding: 8px 16px;
            background: var(--light-gray);
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .file-upload-label:hover {
            background: #e2e6ea;
        }

        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .section-divider {
            height: 1px;
            background: var(--light-gray);
            margin: 2rem 0;
            position: relative;
        }

        .section-divider::after {
            content: "Change Password";
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 0 15px;
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Messages */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.4s ease;
        }

        .message.error {
            background: rgba(247, 37, 133, 0.1);
            border: 1px solid rgba(247, 37, 133, 0.2);
            color: var(--danger);
        }

        .message.success {
            background: rgba(76, 201, 240, 0.1);
            border: 1px solid rgba(76, 201, 240, 0.2);
            color: #0a9396;
        }

        .message i {
            font-size: 1.3rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1rem 0;
            }
            
            .sidebar-menu {
                display: flex;
                overflow-x: auto;
                padding: 1rem 0;
            }
            
            .sidebar-menu li {
                margin: 0;
            }
            
            .sidebar-menu a {
                padding: 10px 1rem;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .sidebar-menu a:hover, 
            .sidebar-menu a.active {
                border-left: none;
                border-bottom: 3px solid var(--success);
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .user-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 1.5rem;
            }
            
            .btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
            
            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-lock"></i>
                <h2>Admin Dashboard</h2>
            </div>
            <ul class="sidebar-menu">
                
               
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                
                <div class="user-actions">

                    
                </div>
            </div>

            <!-- Messages -->
            <?php if ($errors): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Please fix the following issues:</strong>
                        <ul>
                            <?php foreach ($errors as $e): ?>
                                <li><?=htmlspecialchars($e)?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <div><?=htmlspecialchars($success)?></div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-user-edit"></i> Edit Profile Information</h2>
                </div>
                
                <form method="post" enctype="multipart/form-data" novalidate>
                    <div class="profile-pic-container">
                        <?php if (!empty($user['profile_pic'])): ?>
                            <img src="<?=htmlspecialchars($user['profile_pic'])?>?t=<?=time()?>" class="profile-pic" alt="Profile Picture">
                        <?php else: ?>
                            <div class="profile-pic" style="display: flex; align-items: center; justify-content: center; background: #e0e7ff; color: var(--primary); font-size: 3rem;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div class="file-upload">
                            <label for="profile-upload" class="file-upload-label">
                                <i class="fas fa-upload"></i> Change Profile Photo
                            </label>
                            <input type="file" id="profile-upload" name="profile_pic" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name"><i class="fas fa-signature"></i> Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?=htmlspecialchars($user['full_name'] ?? '')?>" required>
                        </div>

                        <div class="form-group">
                            <label for="dob"><i class="fas fa-birthday-cake"></i> Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="form-control" 
                                   value="<?=htmlspecialchars($user['dob'] ?? '')?>">
                        </div>

                        <div class="form-group">
                            <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" id="location" name="location" class="form-control" 
                                   value="<?=htmlspecialchars($user['location'] ?? '')?>">
                        </div>
                    </div>

                    <div class="section-divider"></div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="old_password"><i class="fas fa-lock"></i> Current Password</label>
                            <div class="password-toggle">
                                <input type="password" id="old_password" name="old_password" class="form-control" 
                                       autocomplete="current-password">
                                <i class="fas fa-eye toggle-password" data-target="old_password"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password"><i class="fas fa-key"></i> New Password</label>
                            <div class="password-toggle">
                                <input type="password" id="password" name="password" class="form-control" 
                                       autocomplete="new-password">
                                <i class="fas fa-eye toggle-password" data-target="password"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm"><i class="fas fa-check-circle"></i> Confirm New Password</label>
                            <div class="password-toggle">
                                <input type="password" id="password_confirm" name="password_confirm" class="form-control" 
                                       autocomplete="new-password">
                                <i class="fas fa-eye toggle-password" data-target="password_confirm"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const toggleButtons = document.querySelectorAll('.toggle-password');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Toggle eye icon
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            });
            
            // Profile picture preview
            const profileUpload = document.getElementById('profile-upload');
            if (profileUpload) {
                profileUpload.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        const profilePic = document.querySelector('.profile-pic');
                        
                        reader.onload = function(e) {
                            if (profilePic.tagName === 'IMG') {
                                profilePic.src = e.target.result;
                            } else {
                                // Replace placeholder with actual image
                                const newImg = document.createElement('img');
                                newImg.src = e.target.result;
                                newImg.classList.add('profile-pic');
                                newImg.alt = "Profile Preview";
                                profilePic.parentNode.replaceChild(newImg, profilePic);
                            }
                        }
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
            
            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const passwordConfirm = document.getElementById('password_confirm').value;
                
                if (password || passwordConfirm) {
                    if (password.length < 6) {
                        e.preventDefault();
                        alert('New password must be at least 6 characters long.');
                    } else if (password !== passwordConfirm) {
                        e.preventDefault();
                        alert('New password and confirmation do not match.');
                    }
                }
            });
        });
    </script>
</body>
</html>