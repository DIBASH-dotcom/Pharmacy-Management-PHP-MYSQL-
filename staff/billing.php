<?php
require_once '../includes/auth_check.php';
requireRole('staff');
require_once '../includes/database.php';

$error = '';
$success = '';

// Fetch available medicines
$stmt = $con->prepare("
    SELECT medicine_id, name, price, quantity, expiry_date, batch_number, category, manufacturer
    FROM medicines
    ORDER BY category ASC, name ASC
");
$stmt->execute();
$medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['items'] ?? [];
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    $customer_year = intval($_POST['customer_year'] ?? 0);

    if (empty($customer_name) || empty($customer_address) || $customer_year <= 0) {
        $error = "Customer Name, Address, and Age are required.";
    } elseif (empty($items)) {
        $error = "Please select at least one medicine.";
    } else {
        $valid_items = [];
        $total_amount = 0.0;

        foreach ($items as $medicine_id => $item) {
            $medicine_id = intval($medicine_id);
            $quantity = intval($item['quantity']);

            if ($medicine_id <= 0 || $quantity <= 0) {
                continue;
            }

            $stmt = $con->prepare("SELECT price, quantity FROM medicines WHERE medicine_id = ?");
            $stmt->bind_param("i", $medicine_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows !== 1) {
                $stmt->close();
                continue;
            }
            $med = $result->fetch_assoc();
            $stmt->close();

            if ($quantity > $med['quantity']) {
                $error = "Insufficient stock for medicine ID $medicine_id.";
                break;
            }

            $price = $med['price'];
            $subtotal = $price * $quantity;
            $total_amount += $subtotal;

            $valid_items[] = [
                'medicine_id' => $medicine_id,
                'quantity' => $quantity,
                'price' => $price
            ];
        }

        if (!$error && count($valid_items) > 0) {
            $con->begin_transaction();
            try {
                $stmt = $con->prepare("INSERT INTO sales (user_id, total_amount, customer_name, customer_address, customer_year, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $user_id = $_SESSION['user_id'];
                $stmt->bind_param("idssi", $user_id, $total_amount, $customer_name, $customer_address, $customer_year);
                $stmt->execute();
                $sale_id = $stmt->insert_id;
                $stmt->close();

                $stmt_item = $con->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt_update = $con->prepare("UPDATE medicines SET quantity = quantity - ? WHERE medicine_id = ?");

                foreach ($valid_items as $vi) {
                    $stmt_item->bind_param("iiid", $sale_id, $vi['medicine_id'], $vi['quantity'], $vi['price']);
                    $stmt_item->execute();

                    $stmt_update->bind_param("ii", $vi['quantity'], $vi['medicine_id']);
                    $stmt_update->execute();
                }

                $stmt_item->close();
                $stmt_update->close();

                $con->commit();

                // ✅ Only pass sale_id to invoice
                $success = "Sale completed successfully. <a href='print_invoice.php?sale_id=$sale_id' target='_blank'>Print Invoice</a>";
            } catch (Exception $e) {
                $con->rollback();
                $error = "Transaction failed: " . $e->getMessage();
            }
        } elseif (!$error) {
            $error = "No valid items selected.";
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
<title>Pharmacy Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-600: #6c757d;
            --gray-800: #343a40;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --radius: 8px;
            --sidebar-width: 240px;
        }

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, var(--darker), var(--dark));
    color: var(--text-light);
    min-height: 100vh;
    padding: 0;
    margin: 0;
    margin-left: 220px; /* width of sidebar */
}

.dashboard-container {
    display: flex;
    min-height: 100vh;
}

.main-content {
    flex: 1;
    padding: 30px;
    padding-top: 30px;
    transition: var(--transition);
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.welcome-section h1 {
    font-size: 2.2rem;
    background: linear-gradient(90deg, var(--success), var(--primary));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 5px;
}

.user-role {
    background: var(--card-bg);
    color: var(--success);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-block;
}

.nav-links {
    display: flex;
    gap: 15px;
    margin: 20px 0;
}

.nav-links a {
    color: var(--success);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    border-radius: 30px;
    background: rgba(76, 201, 240, 0.1);
    transition: var(--transition);
}

.nav-links a:hover {
    background: rgba(76, 201, 240, 0.2);
    transform: translateY(-2px);
}

.billing-form {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.section-title {
    font-size: 1.5rem;
    margin-bottom: 20px;
    color: var(--text-lighter);
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title i {
    color: var(--success);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    
}

.form-group label {
    font-weight: 600;
    color: var(--text-lighter);
}

.form-group input {
    padding: 14px;
    border: none;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-lighter);
    font-size: 1rem;
    transition: var(--transition);
    border: 2px solid black;
    color: red;
}

.form-group input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.12);
    border-color: var(--primary);
}

.form-group input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.mandatory {
    color: #ff6b6b;
    font-weight: bold;
}

.search-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 25px;
}

.search-box {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.search-box input {
    flex: 1;
    min-width: 250px;
    padding: 14px 20px;
    border: none;
    border-radius: 50px;
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-lighter);
    font-size: 1rem;
    transition: var(--transition);
    border: 1px solid transparent;
}

.search-box input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.12);
    border-color: var(--primary);
}

.search-box input::placeholder {
    color: blue;
   
}

.table-section {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 25px;
    margin-bottom: 30px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

.table-container {
    overflow-x: auto;
    max-height: 400px;
    overflow-y: auto;
}

.medicines-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    min-width: 800px;
}

.medicines-table th {
    background: rgba(67, 97, 238, 0.2);
    text-align: left;
    padding: 16px 20px;
    font-weight: 600;
    color: var(--success);
    position: sticky;
    top: 0;
}

.medicines-table td {
    padding: 14px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text-light);
}

.medicines-table tr:last-child td {
    border-bottom: none;
}

.medicines-table tr:hover td {
    background: rgba(255, 255, 255, 0.03);
}

.stock-warning {
    color: #ff6b6b;
    font-weight: 600;
}

.qty-input {
    width: 80px;
    padding: 10px;
    border-radius: 8px;
    border: none;
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-lighter);
    text-align: center;
    transition: var(--transition);
}

.qty-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.12);
    border-color: var(--primary);
}

.subtotal {
    font-weight: 600;
    color: var(--text-lighter);
}

.total-section {
    background: rgba(76, 201, 240, 0.1);
    padding: 20px;
    border-radius: var(--border-radius);
    margin: 20px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-label {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-lighter);
}

.total-price {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--success);
}

.btn-submit {
    width: 100%;
    padding: 16px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.btn-submit:hover {
    background: var(--secondary);
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.flash-message {
    padding: 15px 20px;
    border-radius: var(--border-radius);
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(76, 201, 240, 0.15);
    border-left: 4px solid var(--success);
    animation: fadeIn 0.5s ease;
}

.flash-error {
    background: rgba(247, 37, 133, 0.15);
    border-left: 4px solid var(--warning);
}

.flash-message i {
    font-size: 1.2rem;
}

.flash-success i {
    color: var(--success);
}

.flash-error i {
    color: var(--warning);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Mobile Responsiveness */
@media (max-width: 992px) {
    body {
        margin-left: 0;
        padding: 15px;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .main-content {
        padding: 15px;
    }
    
    .billing-form, .search-section, .table-section {
        padding: 20px;
    }
    
    .nav-links {
        flex-direction: column;
    }
    
    .search-box input {
        width: 100%;
    }
}
</style>
</head>
<body>
    <div class="dashboard-container">
        <div class="main-content">
<div class="dashboard-header">
                <div class="welcome-section">
                  
                </div>
            </div>
            <div class="nav-links">
            <h1 style="color: white; background-color: blue; font-size: 1.5rem; padding: 10px 15px; border-radius: 5px; display: inline-block;">
  Pharmacy Billing System
</h1>
</div>

            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="flash-message flash-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <!-- Customer Info -->
            <form method="POST" action="billing.php" autocomplete="off" class="billing-form">
                <h2 class="section-title"><i class="fas fa-user"></i> Customer Information</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_name">Customer Name <span class="mandatory">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" maxlength="100" required placeholder="Enter customer name">
                    </div>
                    <div class="form-group">
                        <label for="customer_address">Customer Address <span class="mandatory">*</span></label>
                        <input type="text" name="customer_address" id="customer_address" maxlength="255" required placeholder="Enter customer address">
                    </div>
                    <div class="form-group">
                        <label for="customer_year">Age <span class="mandatory">*</span></label>
                        <input type="number" name="customer_year" id="customer_year" min="1" max="120" required placeholder="Enter age">
                    </div>
                </div>
                
                <p><strong><span class="mandatory">*</span> Customer information is mandatory and will appear on the invoice.</strong></p>

                <!-- Medicine Selection -->
                <h2 class="section-title" style="margin-top: 30px;"><i class="fas fa-pills"></i> Medicine Selection</h2>
                
                <div class="search-section">
                    <div class="search-box" style="display: flex; border-radius: 8px; border: 1px solid #ccc; overflow: hidden;">
                        <input type="text" id="search-medicine" placeholder="Search medicines by name, category, or batch..." autocomplete="off" style="border: none; padding: 8px; flex-grow: 1;">
                        <button type="button" id="clear-search" style="background: transparent; border: none; color: var(--success); cursor: pointer; padding: 8px 12px;">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>

                <div class="table-section">
                    <div class="table-container">
                        <table class="medicines-table" aria-describedby="medicines-description">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Batch</th>
                                    <th>Price (RS)</th>
                                    <th>Stock</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Subtotal (RS)</th>
                                </tr>
                            </thead>
                            <tbody id="medicines-body"></tbody>
                        </table>
                    </div>
                </div>

                <div class="total-section">
                    <div class="total-label">TOTAL AMOUNT:</div>
                    <div class="total-price">RS <span id="total-price">0.00</span></div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-check-circle"></i> Complete Sale
                </button>
            </form>
        </div>
    </div>

<script>
const medicines = <?= json_encode($medicines) ?>;

function renderTable(filtered = medicines) {
    const tbody = document.getElementById('medicines-body');
    tbody.innerHTML = '';

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 30px; color: var(--warning);">
                    <i class="fas fa-exclamation-triangle"></i> No medicines found
                </td>
            </tr>
        `;
        return;
    }

    filtered.forEach(med => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${med.name}</td>
            <td>${med.batch_number}</td>
            <td>RS ${parseFloat(med.price).toFixed(2)}</td>
            <td class="${med.quantity <= 5 ? 'stock-warning' : ''}">${med.quantity}</td>
            <td>${med.category || ''}</td>
            <td>
                <input type="number" name="items[${med.medicine_id}][quantity]"
                       min="0" max="${med.quantity}" data-price="${med.price}" data-stock="${med.quantity}"
                       class="qty-input" value="0" />
            </td>
            <td class="subtotal">Rs 0.00</td>
        `;
        tbody.appendChild(row);
    });

    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('input', () => {
            const price = parseFloat(input.dataset.price);
            const stock = parseInt(input.dataset.stock);
            let qty = parseInt(input.value) || 0;

            if (qty > stock) {
                alert(`Stock limit exceeded. Max allowed: ${stock}`);
                qty = stock;
                input.value = stock;
            } else if (qty < 0) {
                qty = 0;
                input.value = 0;
            }

            const subtotal = price * qty;
            input.closest('tr').querySelector('.subtotal').textContent = `Rs ${subtotal.toFixed(2)}`;
            updateTotal();
        });
    });

    updateTotal();
}

function updateTotal() {
    let total = 0.0;
    document.querySelectorAll('.qty-input').forEach(input => {
        const qty = parseInt(input.value) || 0;
        const price = parseFloat(input.dataset.price);
        total += qty * price;
    });
    document.getElementById('total-price').textContent = total.toFixed(2);
}

document.addEventListener('DOMContentLoaded', () => {
    renderTable();

    const searchInput = document.getElementById('search-medicine');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const term = searchInput.value.toLowerCase();
            const filtered = medicines.filter(med => {
                const text = (med.name + med.batch_number + med.category + med.manufacturer).toLowerCase();
                return text.includes(term);
            });
            renderTable(filtered);
        });
    }

    document.getElementById('clear-search').addEventListener('click', () => {
        searchInput.value = '';
        renderTable();
    });

    document.getElementById('customer_name').focus();
});
</script>
</body>
</html>
