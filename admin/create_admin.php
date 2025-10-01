<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../includes/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = 'admin'; // fixed role admin

    if ($username && $password && $full_name && $email) {
        // Hash password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $con->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $username, $hashed_password, $full_name, $email, $role);

        if ($stmt->execute()) {
            $message = "New admin user created successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $message = "Please fill in all fields.";
    }
}
include '../admin/side.php';
include '../admin/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Admin - System Dashboard</title>
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
            --border: #dee2e6;
            --success: #4cc9f0;
            --danger: #e63946;
            --warning: #ff9e00;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 200px;
            background: white;
            color: white;
            padding: 20px 0;
            transition: var(--transition);
            box-shadow: 3px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .logo i {
            font-size: 28px;
            margin-right: 12px;
            color: blue;
        }

        .logo h1 {
            font-size: 22px;
            font-weight: 600;
        }

        .nav-links {
            list-style: none;
            padding: 0 15px;
        }

        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            border-radius: 6px;
            transition: var(--transition);
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }

        .nav-links a i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
            transition: var(--transition);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
        }

        .user-role {
            font-size: 14px;
            color: var(--gray);
        }

        /* Admin Creation Section */
        .admin-creation {
            max-width: 800px;
            margin: 0 auto;
            animation: fadeIn 0.6s ease-out;
        }

        .admin-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .admin-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        .admin-header i {
            font-size: 32px;
            color: var(--primary);
            margin-right: 15px;
        }

        .admin-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        .admin-header p {
            color: var(--gray);
            margin-top: 5px;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
        }

        .form-label.required::after {
            content: "*";
            color: var(--danger);
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background: var(--light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .form-icon {
            position: absolute;
            right: 15px;
            top: 44px;
            color: var(--gray);
            font-size: 18px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            gap: 10px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.2);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #6506a5;
            transform: translateY(-2px);
        }

       

        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Status Messages */
        .status-message {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.4s ease-out;
        }

        .status-message i {
            font-size: 22px;
        }

        .error-message {
            background: rgba(230, 57, 70, 0.1);
            color: var(--danger);
            border: 1px solid rgba(230, 57, 70, 0.2);
        }

        .success-message {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
            border: 1px solid rgba(76, 201, 240, 0.2);
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 8px;
            height: 6px;
            border-radius: 3px;
            background: var(--light-gray);
            overflow: hidden;
            position: relative;
        }

        .strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
            background: var(--danger);
        }

        .strength-text {
            font-size: 13px;
            margin-top: 5px;
            color: var(--gray);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: var(--gray);
            font-size: 14px;
            margin-top: 30px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .main-content {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-info {
                align-self: flex-end;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .admin-card {
                padding: 20px 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: flex-end;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            
        </div>
        
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
          
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        

        <!-- Admin Creation Card -->
        <section class="admin-creation">
            <div class="admin-card">
                <div class="admin-header">
                    <i class="fas fa-user-shield"></i>
                    <div>
                        <h2>Add New Administrator</h2>
                        <p>Create a new admin account with elevated privileges</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="status-message <?= strpos($message, 'Error') !== false ? 'error-message' : 'success-message' ?>">
                        <i class="fas <?= strpos($message, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                        <div><?= htmlspecialchars($message) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="adminForm" autocomplete="off">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username" class="form-label required">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required 
                                   placeholder="Enter unique username" autocomplete="new-username">
                            <i class="fas fa-user form-icon"></i>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label required">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required 
                                   placeholder="Create strong password" autocomplete="new-password">
                            <i class="fas fa-lock form-icon"></i>
                            <div class="password-strength">
                                <div class="strength-meter" id="strengthMeter"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Password strength indicator</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name" class="form-label required">Full Name</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" required 
                                   placeholder="Enter full name">
                            <i class="fas fa-id-card form-icon"></i>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label required">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" required 
                                   placeholder="Enter valid email">
                            <i class="fas fa-envelope form-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create Admin Account
                        </button>
                        
                        <a href="dashboard.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </button>
                        
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-redo"></i> Reset Form
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Admin Privileges Info -->
           
            <!-- Footer -->
            
        </section>
    </main>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthMeter = document.getElementById('strengthMeter');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            let strength = 0;
            let text = '';
            
            // Check password length
            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 25;
            
            // Check for mixed case
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 15;
            
            // Check for numbers
            if (password.match(/\d/)) strength += 15;
            
            // Check for special characters
            if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
            
            // Update strength meter
            strengthMeter.style.width = strength + '%';
            
            // Update text
            if (password.length === 0) {
                text = 'Password strength indicator';
                strengthMeter.style.backgroundColor = '';
            } else if (strength < 40) {
                text = 'Weak password';
                strengthMeter.style.backgroundColor = 'var(--danger)';
            } else if (strength < 70) {
                text = 'Moderate password';
                strengthMeter.style.backgroundColor = 'var(--warning)';
            } else {
                text = 'Strong password';
                strengthMeter.style.backgroundColor = 'var(--success)';
            }
            
            strengthText.textContent = text;
        });
        
        // Form validation
        document.getElementById('adminForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            let valid = true;
            
            // Check password strength
            if (password.length < 8) {
                valid = false;
                alert('Password must be at least 8 characters long');
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
        
        // Form reset functionality
        document.querySelector('button[type="reset"]').addEventListener('click', function() {
            strengthMeter.style.width = '0';
            strengthText.textContent = 'Password strength indicator';
        });
    </script>
</body>
</html>