<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../includes/database.php';

$error = '';
$success = '';
$printData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? '';
    $manufacture_date = $_POST['manufacture_date'] ?? '';
    $batch_number = trim($_POST['batch_number'] ?? '');
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($name === '' || $quantity <= 0 || $price <= 0 || !$expiry_date || !$manufacture_date) {
        $error = "Please fill all required fields correctly.";
    } elseif (strtotime($expiry_date) <= strtotime(date('Y-m-d'))) {
        $error = "Expiry date must be a future date (after today).";
    } elseif (strtotime($manufacture_date) > strtotime(date('Y-m-d'))) {
        $error = "Manufacture date cannot be a future date.";
    } else {
        $stmt = $con->prepare("INSERT INTO medicines 
            (name, description, quantity, price, expiry_date, batch_number, manufacturer, category, location, manufacture_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $error = "Database error: " . $con->error;
        } else {
            $stmt->bind_param("ssidssssss", $name, $description, $quantity, $price, $expiry_date, $batch_number, $manufacturer, $category, $location, $manufacture_date);
            if ($stmt->execute()) {
                $success = "Medicine added successfully.";
                $printData = compact('name', 'description', 'quantity', 'price', 'expiry_date', 'manufacture_date', 'batch_number', 'manufacturer', 'category', 'location');
                $name = $description = $batch_number = $manufacturer = $category = $location = '';
                $quantity = $price = $expiry_date = $manufacture_date = '';
            } else {
                $error = "Error adding medicine: " . $stmt->error;
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
<meta charset="UTF-8" />
<title>Add Medicine - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        --success: #4caf50;
        --error: #f44336;
        --warning: #ff9800;
        --info: #2196f3;
        --radius: 12px;
        --shadow-sm: 0 2px 8px rgba(0,0,0,0.1);
        --shadow-md: 0 4px 12px rgba(0,0,0,0.15);
        --transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
        color: var(--dark);
        line-height: 1.6;
        min-height: 100vh;
        padding: 0;
        display: flex;
        flex-direction: column;
    }

    .app-container {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar styling */
    .sidebar {
        width: 260px;
        background: linear-gradient(160deg, #2c3e50 0%, #1a2530 100%);
        color: white;
        padding: 20px 0;
        height: 100vh;
        position: fixed;
        overflow-y: auto;
        box-shadow: 3px 0 15px rgba(0,0,0,0.1);
        z-index: 100;
        transition: var(--transition);
    }

    .sidebar-header {
        padding: 0 20px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 20px;
    }

    .sidebar-header h2 {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.4rem;
        color: white;
    }

    .sidebar-header i {
        background: var(--primary);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .nav-item {
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: var(--transition);
        border-left: 3px solid transparent;
    }

    .nav-item:hover, .nav-item.active {
        background: rgba(255,255,255,0.1);
        color: white;
        border-left: 3px solid var(--accent);
    }

    .nav-item i {
        width: 24px;
        text-align: center;
    }

    /* Main content */
    .main-content {
        flex: 1;
        margin-left: 260px;
        padding: 30px;
        transition: var(--transition);
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--light-gray);
    }

    .header h1 {
        font-size: 2rem;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header h1 i {
        color: var(--primary);
        background: rgba(67, 97, 238, 0.1);
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--primary);
        font-weight: 500;
        padding: 8px 16px;
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
        text-decoration: none;
    }

    .back-link:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .back-link i {
        transition: var(--transition);
    }

    /* Form container */
    .form-container {
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        margin-bottom: 30px;
        animation: fadeIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .form-header {
        background: linear-gradient(to right, var(--primary), var(--secondary));
        color: white;
        padding: 20px;
    }

    .form-header h2 {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .form-body {
        padding: 30px;
    }

    /* Form grid */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-group label .required {
        color: var(--accent);
    }

    .input-group {
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
    }

    .form-control {
        width: 100%;
        padding: 14px 14px 14px 40px;
        border: 2px solid var(--light-gray);
        border-radius: var(--radius);
        font-size: 1rem;
        transition: var(--transition);
    }

    .form-control:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    textarea.form-control {
        min-height: 120px;
        padding-left: 14px;
    }

    select.form-control {
        padding-left: 14px;
        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
        appearance: none;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 28px;
        font-size: 1rem;
        font-weight: 600;
        border-radius: var(--radius);
        border: none;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(to right, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 14px rgba(67, 97, 238, 0.4);
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 18px rgba(67, 97, 238, 0.5);
    }

    .btn-lg {
        padding: 16px 32px;
        font-size: 1.1rem;
    }

    .btn-block {
        display: block;
        width: 100%;
    }

    .btn-print {
        background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
        color: white;
    }

    /* Messages */
    .message {
        padding: 16px 20px;
        border-radius: var(--radius);
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.4s ease-out;
    }

    .message i {
        font-size: 1.4rem;
    }

    .error {
        background: rgba(244, 67, 54, 0.1);
        border-left: 4px solid var(--error);
        color: var(--error);
    }

    .success {
        background: rgba(76, 175, 80, 0.1);
        border-left: 4px solid var(--success);
        color: var(--success);
    }

    /* Printable section */
    #printable {
        display: none;
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow-md);
        padding: 30px;
        margin-top: 30px;
        animation: fadeIn 0.5s ease;
    }

    .print-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--light-gray);
    }

    .print-header h2 {
        color: var(--primary);
        font-size: 1.8rem;
        margin-bottom: 10px;
    }

    .print-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .detail-item {
        padding: 15px;
        background: var(--light);
        border-radius: var(--radius);
    }

    .detail-item strong {
        display: block;
        color: var(--gray);
        font-size: 0.9rem;
        margin-bottom: 5px;
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

    /* Responsive design */
    @media (max-width: 992px) {
        .sidebar {
            width: 80px;
            overflow: hidden;
        }
        
        .sidebar-header h2 span, .nav-item span {
            display: none;
        }
        
        .main-content {
            margin-left: 80px;
        }
    }

    @media (max-width: 768px) {
        .main-content {
            padding: 20px;
            margin-left: 0;
        }
        
        .sidebar {
            transform: translateX(-100%);
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .mobile-menu-btn {
            display: block;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 99;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
        }
        
        .header {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }
        
        .form-body {
            padding: 20px;
        }
    }

    @media print {
        body * { 
            visibility: hidden; 
        }
        #printable, #printable * { 
            visibility: visible; 
        }
        #printable { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            background: white;
            box-shadow: none;
        }
    }

    /* Floating action button */
    .fab {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent), #e11d8f);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 6px 16px rgba(247, 37, 133, 0.4);
        z-index: 99;
        cursor: pointer;
        transition: var(--transition);
        border: none;
    }

    .fab:hover {
        transform: translateY(-5px) rotate(10deg);
        box-shadow: 0 8px 20px rgba(247, 37, 133, 0.6);
    }

    /* Hide mobile menu button by default */
    .mobile-menu-btn {
        display: none;
    }
</style>
</head>
<body>
<div class="app-container">
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
    
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-pills"></i> <span>PharmaSys</span></h2>
        </div>
        
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-home"></i> <span>Dashboard</span>
        </a>
        
        <a href="medicines.php" class="nav-item">
            <i class="fas fa-pills"></i> <span>Medicines</span>
        </a>
        <a href="add_medicine.php" class="nav-item">
            <i class="fas fa-boxes"></i> <span>Add Medicine</span>
        </a>
        <a href="reports.php" class="nav-item">
            <i class="fas fa-shopping-cart"></i> <span>Reporst</span>
        </a>
        <a href="edit_profile.php" class="nav-item">
            <i class="fas fa-users"></i> <span>Edit Profile</span>
        </a>
       
        <a href="add_staff.php" class="nav-item">
            <i class="fas fa-users"></i> <span>Add Staff</span>
        </a>
        <a href="manage_staff.php" class="nav-item">
            <i class="fas fa-cog"></i> <span>Manage Staff</span>
        </a>

        <a href="create_admin.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i> <span>Create Admin</span>
        </a>

        <a href="admin_notifications.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i> <span>Notifications</span>
        </a>
        <a href="../login.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>

        
        
    </aside>
    
    <div class="main-content">
        <div class="header">
            
            </a>
        </div>
        
        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?=htmlspecialchars($error)?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <div><?=htmlspecialchars($success)?></div>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="form-header">
                <h1><i class="fas fa-plus-circle"></i> Add New Medicine</h1>
            <a href="medicines.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Medicines
            </a>
            </div>
            
            <div class="form-body">
                <form method="POST" action="add_medicine.php" autocomplete="off" id="medicineForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Name <span class="required">*</span></label>
                            <div class="input-group">
                                <i class="fas fa-tag input-icon"></i>
                                <input type="text" name="name" id="name" class="form-control" required value="<?=htmlspecialchars($name ?? '')?>" placeholder="Enter medicine name">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Quantity <span class="required">*</span></label>
                            <div class="input-group">
                                <i class="fas fa-cubes input-icon"></i>
                                <input type="number" name="quantity" id="quantity" class="form-control" min="1" required value="<?=intval($quantity ?? 0) ?: ''?>" placeholder="Enter quantity">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" placeholder="Enter medicine description"><?=htmlspecialchars($description ?? '')?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (rs) <span class="required">*</span></label>
                            <div class="input-group">
                                <i class="fas fa-rupee-sign input-icon"></i>
                                <input type="number" step="0.01" name="price" id="price" class="form-control" min="0.01" required value="<?=number_format(floatval($price ?? 0), 2, '.', '') ?: ''?>" placeholder="Enter price">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date <span class="required">*</span></label>
                            <div class="input-group">
                                <i class="fas fa-calendar-times input-icon"></i>
                                <input type="date" name="expiry_date" id="expiry_date" class="form-control" required 
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                                       value="<?=htmlspecialchars($expiry_date ?? '')?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="manufacture_date">Manufacture Date <span class="required">*</span></label>
                            <div class="input-group">
                                <i class="fas fa-calendar-alt input-icon"></i>
                                <input type="date" name="manufacture_date" id="manufacture_date" class="form-control" required
                                       max="<?= date('Y-m-d') ?>"
                                       value="<?=htmlspecialchars($manufacture_date ?? '')?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="batch_number">Batch Number</label>
                            <div class="input-group">
                                <i class="fas fa-barcode input-icon"></i>
                                <input type="text" name="batch_number" id="batch_number" class="form-control" value="<?=htmlspecialchars($batch_number ?? '')?>" placeholder="Enter batch number">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="manufacturer">Manufacturer</label>
                            <div class="input-group">
                                <i class="fas fa-industry input-icon"></i>
                                <input type="text" name="manufacturer" id="manufacturer" class="form-control" value="<?=htmlspecialchars($manufacturer ?? '')?>" placeholder="Enter manufacturer name">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" id="category" class="form-control">
                                <option value="">Select Category</option>
                                <option value="Painkiller" <?= (isset($category) && $category === 'Painkiller') ? 'selected' : '' ?>>Painkiller</option>
                                <option value="Antibiotic" <?= (isset($category) && $category === 'Antibiotic') ? 'selected' : '' ?>>Antibiotic</option>
                                <option value="Vitamin" <?= (isset($category) && $category === 'Vitamin') ? 'selected' : '' ?>>Vitamin</option>
                                <option value="Antihistamine" <?= (isset($category) && $category === 'Antihistamine') ? 'selected' : '' ?>>Antihistamine</option>
                                <option value="Antidepressant" <?= (isset($category) && $category === 'Antidepressant') ? 'selected' : '' ?>>Antidepressant</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location</label>
                            <div class="input-group">
                                <i class="fas fa-map-marker-alt input-icon"></i>
                                <input type="text" name="location" id="location" class="form-control" value="<?=htmlspecialchars($location ?? '')?>" placeholder="Enter storage location">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-plus-circle"></i> Add Medicine
                    </button>
                </form>
            </div>
        </div>
        
        <?php if ($success && !empty($printData)): ?>
            <button onclick="printRecord()" class="btn btn-print">
                <i class="fas fa-print"></i> Print This Record
            </button>
            
            <div id="printable">
                <div class="print-header">
                    <h2><i class="fas fa-pills"></i> Medicine Details</h2>
                    <p>Record added on <?= date('F j, Y, g:i a') ?></p>
                </div>
                
                <div class="print-details">
                    <div class="detail-item">
                        <strong>Name</strong>
                        <div><?=htmlspecialchars($printData['name'])?></div>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Description</strong>
                        <div><?=htmlspecialchars($printData['description'])?></div>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Quantity</strong>
                        <div><?=$printData['quantity']?></div>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Price</strong>
                        <div>RS<?=number_format($printData['price'], 2)?></div>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Expiry Date</strong>
                        <div><?=htmlspecialchars(date('d/m/Y', strtotime($printData['expiry_date'])))?></div>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Manufacture Date</strong>
                        <div><?=htmlspecialchars(date('d/m/Y', strtotime($printData['manufacture_date'])))?></div>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Batch Number</strong>
                        <div><?=htmlspecialchars($printData['batch_number'])?></div>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Manufacturer</strong>
                        <div><?=htmlspecialchars($printData['manufacturer'])?></div>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Category</strong>
                        <div><?=htmlspecialchars($printData['category'])?></div>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Location</strong>
                        <div><?=htmlspecialchars($printData['location'])?></div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px; font-style: italic; color: var(--gray);">
                    This is an automatically generated record from PharmaSys
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<button class="fab" id="fab">
    <i class="fas fa-plus"></i>
</button>

<script>
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    
    mobileMenuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
    
    // Scroll to form when FAB is clicked
    const fab = document.getElementById('fab');
    const form = document.getElementById('medicineForm');
    
    fab.addEventListener('click', () => {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Add animation effect
        fab.style.animation = 'pulse 0.5s';
        setTimeout(() => {
            fab.style.animation = '';
        }, 500);
    });
    
    // Form validation
    const medicineForm = document.getElementById('medicineForm');
    const expiryDate = document.getElementById('expiry_date');
    const manufactureDate = document.getElementById('manufacture_date');
    
    medicineForm.addEventListener('submit', (e) => {
        const today = new Date().toISOString().split('T')[0];
        const expiry = new Date(expiryDate.value);
        const manufacture = new Date(manufactureDate.value);
        
        // Reset custom validity
        expiryDate.setCustomValidity('');
        manufactureDate.setCustomValidity('');
        
        if (expiryDate.value && expiry <= new Date()) {
            expiryDate.setCustomValidity("Expiry date must be a future date (after today).");
            expiryDate.reportValidity();
            e.preventDefault();
            return;
        }
        
        if (manufactureDate.value && manufacture > new Date()) {
            manufactureDate.setCustomValidity("Manufacture date cannot be a future date.");
            manufactureDate.reportValidity();
            e.preventDefault();
            return;
        }
    });
    
    // Print function
    function printRecord() {
        const printable = document.getElementById('printable');
        printable.style.display = 'block';
        
        // Add a slight delay to ensure the element is visible before printing
        setTimeout(() => {
            window.print();
            printable.style.display = 'none';
        }, 100);
    }
    
    // Add pulse animation to FAB
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(247, 37, 133, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 12px rgba(247, 37, 133, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(247, 37, 133, 0); }
        }
    `;
    document.head.appendChild(style);
    
    // Input focus effects
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.parentElement.style.transform = 'translateY(-3px)';
        });
        
        input.addEventListener('blur', () => {
            input.parentElement.parentElement.style.transform = '';
        });
    });
</script>
</body>
</html>