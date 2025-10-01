<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../includes/database.php';

if (!isset($_GET['id'])) {
    header("Location: medicines.php");
    exit();
}

$medicine_id = intval($_GET['id']);
$error = '';
$success = '';

// Fetch current medicine data
$stmt = $con->prepare("SELECT * FROM medicines WHERE medicine_id = ?");
$stmt->bind_param("i", $medicine_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: medicines.php");
    exit();
}
$medicine = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $expiry_date = $_POST['expiry_date'];
    $batch_number = trim($_POST['batch_number']);
    $manufacturer = trim($_POST['manufacturer']);
    $category = trim($_POST['category']);
    $location = trim($_POST['location']);

    // Validate inputs
    if (
        $name === '' || $quantity < 0 || $price < 0 || !$expiry_date ||
        strtotime($expiry_date) <= strtotime(date('Y-m-d'))
    ) {
        $error = "Please fill all required fields correctly. Expiry date must be a future date.";
    } else {
        $stmt = $con->prepare("UPDATE medicines SET name=?, description=?, quantity=?, price=?, expiry_date=?, batch_number=?, manufacturer=?, category=?, location=? WHERE medicine_id=?");
        $stmt->bind_param("ssidsssssi", $name, $description, $quantity, $price, $expiry_date, $batch_number, $manufacturer, $category, $location, $medicine_id);

        if ($stmt->execute()) {
            $success = "Medicine updated successfully.";
            $medicine = array_merge($medicine, $_POST); // Update displayed values
        } else {
            $error = "Error updating medicine: " . $stmt->error;
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medicine - Pharmacy Admin</title>
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
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
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
            color: var(--accent);
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

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            max-width: 800px;
            margin: 0 auto;
            animation: fadeIn 0.6s ease-out;
        }

        .form-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        .form-header i {
            font-size: 32px;
            color: var(--primary);
            margin-right: 15px;
        }

        .form-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        .form-header p {
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
            min-width: 150px;
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

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
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
            
            .form-container {
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

        /* Pill Icon */
        .pill-icon {
            display: inline-block;
            width: 40px;
            height: 25px;
            background: var(--primary);
            border-radius: 15px;
            position: relative;
            margin-right: 10px;
            vertical-align: middle;
        }

        .pill-icon::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--accent);
            top: 2px;
            left: 2px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-pills"></i>
            <h1>PharmaAdmin</h1>
        </div>
        
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="medicines.php" class="active"><i class="fas fa-pills"></i> Medicines</a></li>
            <li><a href="#"><i class="fas fa-file-invoice-dollar"></i> Sales</a></li>
            <li><a href="#"><i class="fas fa-users"></i> Customers</a></li>
            <li><a href="#"><i class="fas fa-user-tie"></i> Staff</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <div>
               
            </div>
            <div class="user-info">
                <div class="user-avatar">AD</div>
                <div class="user-details">
                    <div class="user-name">Admin User</div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-pills"></i>
                <div>
                    <h2>Edit Medicine Details</h2>
                    <p>Update the information for <?= htmlspecialchars($medicine['name']) ?></p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="status-message error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="status-message success-message">
                    <i class="fas fa-check-circle"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="edit_medicine.php?id=<?= $medicine_id ?>" autocomplete="off">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name" class="form-label required">Medicine Name</label>
                        <input type="text" name="name" id="name" class="form-control" required 
                               value="<?= htmlspecialchars($medicine['name']) ?>">
                        <i class="fas fa-pills form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity" class="form-label required">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="0" required 
                               value="<?= intval($medicine['quantity']) ?>">
                        <i class="fas fa-cubes form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="price" class="form-label required">Price (RS)</label>
                        <input type="number" step="0.01" name="price" id="price" class="form-control" min="0.01" required 
                               value="<?= number_format($medicine['price'], 2, '.', '') ?>">
                       

                    </div>

                    <div class="form-group">
                            <label for="manufacture_date">Manufacture Date <span class="required">*</span></label>
                            <div class="input-group">
                                
                                <input type="date" name="manufacture_date" id="manufacture_date" class="form-control" required
                                       max="<?= date('Y-m-d') ?>"
                                       value="<?=htmlspecialchars($manufacture_date ?? '')?>">
                            </div>
                        </div>
                        
                    
                    <div class="form-group">
                        <label for="expiry_date" class="form-label required">Expiry Date</label>
                        <input type="date" name="expiry_date" id="expiry_date" class="form-control" required 
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                               value="<?= htmlspecialchars($medicine['expiry_date']) ?>">
                       
                    </div>
                    
                    <div class="form-group">
                        <label for="batch_number" class="form-label">Batch Number</label>
                        <input type="text" name="batch_number" id="batch_number" class="form-control" 
                               value="<?= htmlspecialchars($medicine['batch_number']) ?>">
                        <i class="fas fa-barcode form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="manufacturer" class="form-label">Manufacturer</label>
                        <input type="text" name="manufacturer" id="manufacturer" class="form-control" 
                               value="<?= htmlspecialchars($medicine['manufacturer']) ?>">
                        <i class="fas fa-industry form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="category" class="form-label">Category</label>
                        <select name="category" id="category" class="form-control">
                            <option value="">Select Category</option>
                            <option value="Painkiller" <?= $medicine['category'] === 'Painkiller' ? 'selected' : '' ?>>Painkiller</option>
                            <option value="Antibiotic" <?= $medicine['category'] === 'Antibiotic' ? 'selected' : '' ?>>Antibiotic</option>
                            <option value="Vitamin" <?= $medicine['category'] === 'Vitamin' ? 'selected' : '' ?>>Vitamin</option>
                            <option value="Antihistamine" <?= $medicine['category'] === 'Antihistamine' ? 'selected' : '' ?>>Antihistamine</option>
                            <option value="Antacid" <?= $medicine['category'] === 'Antacid' ? 'selected' : '' ?>>Antacid</option>
                        </select>
                        <i class="fas fa-tag form-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">Storage Location</label>
                        <input type="text" name="location" id="location" class="form-control" 
                               value="<?= htmlspecialchars($medicine['location']) ?>">
                        <i class="fas fa-map-marker-alt form-icon"></i>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4"><?= htmlspecialchars($medicine['description']) ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Medicine
                    </button>
                    
                    <a href="medicines.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Medicines
                    </a>
                    
                    <button type="reset" class="btn btn-outline">
                        <i class="fas fa-redo"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> PharmaAdmin Inventory System. All rights reserved.</p>
        </div>
    </main>

    <script>
        // Form reset functionality
        document.querySelector('button[type="reset"]').addEventListener('click', function() {
            document.querySelectorAll('.form-control').forEach(input => {
                input.classList.add('reset-animation');
                setTimeout(() => {
                    input.classList.remove('reset-animation');
                }, 1000);
            });
        });
        
        // Date picker enhancement
        const expiryDateInput = document.getElementById('expiry_date');
        if (expiryDateInput) {
            // Set min date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const minDate = tomorrow.toISOString().split('T')[0];
            expiryDateInput.min = minDate;
        }
        
        // Add visual feedback for required fields
        document.querySelectorAll('.form-control[required]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.classList.add('error-highlight');
                } else {
                    this.classList.remove('error-highlight');
                }
            });
        });
        
        // Validate form before submission
        document.querySelector('form').addEventListener('submit', function(e) {
            let valid = true;
            
            // Check required fields
            document.querySelectorAll('.form-control[required]').forEach(input => {
                if (input.value.trim() === '') {
                    input.classList.add('error-highlight');
                    valid = false;
                }
            });
            
            // Check expiry date
            const expiryDate = new Date(expiryDateInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (expiryDate <= today) {
                expiryDateInput.classList.add('error-highlight');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly. Expiry date must be a future date.');
            }
        });
    </script>
</body>
</html>