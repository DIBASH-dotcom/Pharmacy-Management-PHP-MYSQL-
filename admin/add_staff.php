<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../includes/database.php';
require_once '../includes/functions.php'; // contains sendStaffCredentialsEmail()

$error = '';
$success = '';

function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    if ($full_name === '' || $email === '') {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        $stmt = $con->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            $base_username = strtolower(preg_replace('/\s+/', '', $full_name));
            do {
                $username_candidate = $base_username . rand(100, 999);
                $stmt->close();
                $stmt = $con->prepare("SELECT user_id FROM users WHERE username = ?");
                $stmt->bind_param("s", $username_candidate);
                $stmt->execute();
                $stmt->store_result();
                $exists = $stmt->num_rows > 0;
            } while ($exists);

            $username = $username_candidate;
            $password = generateRandomPassword(10);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();

            $stmt = $con->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, 'staff')");
            $stmt->bind_param("ssss", $username, $password_hash, $full_name, $email);

            if ($stmt->execute()) {
                $sendEmail = sendStaffCredentialsEmail($email, $full_name, $username, $password);
                if ($sendEmail === true) {
                    $success = "Staff user added successfully. Credentials sent to email.";
                    $full_name = $email = '';
                } else {
                    $error = "Staff added but failed to send email: " . htmlspecialchars($sendEmail);
                }
            } else {
                $error = "Error adding staff: " . $stmt->error;
            }
            $stmt->close();
        }
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
    <title>Add Staff - Admin Dashboard</title>
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
            max-width: 600px;
            animation: fadeIn 0.5s ease-out;
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
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            position: relative;
        }

        .form-group label::after {
            content: '*';
            color: var(--danger);
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
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

        .info-note {
            background: rgba(67, 97, 238, 0.05);
            border-left: 4px solid var(--primary);
            padding: 14px;
            border-radius: 6px;
            margin: 1.5rem 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .info-note i {
            color: var(--primary);
            font-size: 1.2rem;
            margin-top: 2px;
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

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
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
            
            .header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                
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
            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-user-plus"></i> Add New Staff Member</h1>
                    
                </div>
                
                <form method="POST" action="add_staff.php" autocomplete="off">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?= isset($full_name) ? htmlspecialchars($full_name) : '' ?>" required
                               placeholder="Enter staff member's full name">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required
                               placeholder="Enter staff member's email">
                    </div>

                    <div class="info-note">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <p><strong>Note:</strong> Username and password will be auto-generated and securely emailed to the staff member.</p>
                            <p>Gmail addresses are recommended for better deliverability.</p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Create Staff Account
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                const fullName = document.getElementById('full_name').value.trim();
                const email = document.getElementById('email').value.trim();
                
                // Reset previous error highlights
                document.querySelectorAll('.form-control').forEach(input => {
                    input.style.borderColor = '';
                });
                
                let hasErrors = false;
                
                if (!fullName) {
                    highlightError('full_name');
                    hasErrors = true;
                }
                
                if (!email) {
                    highlightError('email');
                    hasErrors = true;
                } else if (!validateEmail(email)) {
                    highlightError('email');
                    hasErrors = true;
                }
                
                if (hasErrors) {
                    e.preventDefault();
                }
            });
            
            function highlightError(fieldId) {
                const field = document.getElementById(fieldId);
                field.style.borderColor = 'var(--danger)';
                field.focus();
                
                // Add animation
                field.animate([
                    { transform: 'translateX(0)' },
                    { transform: 'translateX(-5px)' },
                    { transform: 'translateX(5px)' },
                    { transform: 'translateX(0)' }
                ], {
                    duration: 400,
                    iterations: 2
                });
            }
            
            function validateEmail(email) {
                const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(String(email).toLowerCase());
            }
            
            // Success message animation
            if (document.querySelector('.message.success')) {
                const successMsg = document.querySelector('.message.success');
                successMsg.animate([
                    { opacity: 0, transform: 'translateY(-20px)' },
                    { opacity: 1, transform: 'translateY(0)' }
                ], {
                    duration: 500,
                    fill: 'forwards'
                });
            }
        });
    </script>
</body>
</html>