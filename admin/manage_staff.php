<?php
require_once '../includes/auth_check.php';
requireRole('admin');
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Handle delete staff via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    if ($delete_id !== $_SESSION['user_id']) { // Prevent self-deletion
        $stmt = $con->prepare("DELETE FROM users WHERE user_id = ? AND role = 'staff'");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_staff.php");
    exit();
}

// Fetch all staff
$stmt = $con->prepare("SELECT user_id, username, full_name, email, created_at FROM users WHERE role = 'staff' ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

include '../admin/side.php';
include '../admin/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Manage Staff - Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
        --max-content-width: 1200px;
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
        max-width: var(--max-content-width);
        margin: 0 auto;
        width: 100%;
        box-shadow: var(--card-shadow);
        border-radius: var(--border-radius);
        overflow: hidden;
        background: white;
    }

    /* Sidebar Styles */
    .sidebar {
        width: 260px;
        background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        padding: 1.5rem 0;
        box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
        z-index: 100;
        transition: var(--transition);
        height: 100vh;
        overflow-y: auto;
    }

    /* Your included sidebar styles assumed from side.php */

    /* Main Content Styles */
    main.main-content {
        flex: 1;
        padding: 2rem 2.5rem;
        overflow-y: auto;
        min-height: 100vh;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--light-gray);
        flex-wrap: wrap;
        gap: 1rem;
    }

    .header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
        flex-grow: 1;
        min-width: 220px;
    }

    .header h1 i {
        color: var(--primary);
        background: rgba(67, 97, 238, 0.1);
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
    }

    .user-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 10px 22px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        font-size: 1rem;
        user-select: none;
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
        border: 1.8px solid var(--gray);
        color: var(--gray);
        font-weight: 600;
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
        animation: fadeIn 0.5s ease-out;
        max-width: 100%;
        overflow-x: auto;
    }

    .card:hover {
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 10px;
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
        font-size: 1.5rem;
    }

    .staff-count {
        font-size: 1rem;
        font-weight: 500;
        color: var(--gray);
    }

    .staff-count .badge {
        background: var(--success);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
    }

    /* Table Styles */
    .staff-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 820px;
    }

    .staff-table thead {
        background: var(--primary);
        color: white;
    }

    .staff-table th, .staff-table td {
        padding: 14px 18px;
        text-align: left;
        vertical-align: middle;
        font-size: 0.95rem;
    }

    .staff-table tbody tr {
        border-bottom: 1px solid var(--light-gray);
        transition: var(--transition);
    }

    .staff-table tbody tr:hover {
        background: rgba(67, 97, 238, 0.04);
        cursor: default;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    .actions {
        display: flex;
        gap: 10px;
    }

    .action-btn {
        padding: 7px 14px;
        border-radius: 7px;
        font-size: 0.85rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: var(--transition);
        user-select: none;
        border: 1px solid transparent;
    }

    .edit-btn {
        background: rgba(72, 149, 239, 0.12);
        color: var(--info);
        border-color: rgba(72, 149, 239, 0.3);
    }

    .edit-btn:hover {
        background: rgba(72, 149, 239, 0.22);
    }

    .delete-btn {
        background: rgba(247, 37, 133, 0.12);
        color: var(--danger);
        border-color: rgba(247, 37, 133, 0.3);
        cursor: pointer;
        border: 1px solid transparent;
    }

    .delete-btn:hover {
        background: rgba(247, 37, 133, 0.22);
    }

    .delete-btn:disabled,
    .action-btn[disabled] {
        background: rgba(0,0,0,0.05);
        color: var(--gray);
        cursor: default;
        border-color: transparent;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--gray);
        user-select: none;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--light-gray);
    }

    /* Confirmation Modal */
    .modal {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1500;
        opacity: 0;
        visibility: hidden;
        transition: var(--transition);
        padding: 1rem;
    }

    .modal.active {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        width: 100%;
        max-width: 480px;
        padding: 2rem 2.5rem;
        transform: translateY(20px);
        transition: var(--transition);
        text-align: center;
        position: relative;
    }

    .modal.active .modal-content {
        transform: translateY(0);
    }

    .modal-header {
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        font-size: 1.5rem;
        color: var(--danger);
    }

    .modal-header i {
        font-size: 2rem;
    }

    .modal p {
        font-size: 1.1rem;
        margin: 10px 0 0;
        color: var(--dark);
    }

    .modal-actions {
        display: flex;
        gap: 15px;
        margin-top: 2rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .modal-btn {
        padding: 12px 28px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 1rem;
        transition: var(--transition);
        user-select: none;
        min-width: 120px;
    }

    .cancel-btn {
        background: var(--light-gray);
        color: var(--dark);
    }

    .cancel-btn:hover {
        background: #e2e6ea;
    }

    .confirm-btn {
        background: var(--danger);
        color: white;
    }

    .confirm-btn:hover {
        background: #e11d48;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .dashboard-container {
            flex-direction: column;
            max-width: 100%;
            border-radius: 0;
            box-shadow: none;
        }
        
        .sidebar {
            width: 100%;
            height: auto;
            padding: 1rem 0;
        }
        
        main.main-content {
            padding: 1.5rem 1.5rem 2rem;
            min-height: auto;
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
            justify-content: flex-start;
            gap: 10px;
        }
        
        .btn {
            font-size: 0.9rem;
            padding: 8px 16px;
        }
        
        .staff-table thead {
            display: none;
        }
        
        .staff-table tbody tr {
            display: block;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1rem;
            background: white;
        }
        
        .staff-table tbody tr:hover {
            background: white;
            transform: none;
            box-shadow: var(--card-shadow);
        }
        
        .staff-table tbody tr td {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.95rem;
        }
        
        .staff-table tbody tr td::before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--gray);
        }
        
        .actions {
            justify-content: flex-start;
            gap: 8px;
        }
    }

    @media (max-width: 480px) {
        .btn {
            padding: 7px 14px;
            font-size: 0.85rem;
        }
        
        .modal-actions {
            flex-direction: column;
        }
        
        .modal-btn {
            width: 100%;
            min-width: unset;
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInTableRow {
        from { opacity: 0; transform: translateX(-10px); }
        to { opacity: 1; transform: translateX(0); }
    }
</style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <!-- Assuming your ../admin/side.php outputs the sidebar -->

    <!-- Main Content -->
    <main class="main-content" role="main" aria-label="Manage Staff Accounts">
        <div class="header">
           
        </div>

        <div class="card" role="region" aria-live="polite" aria-relevant="all" aria-label="List of staff members">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">
             Staff Members
        </h2>
        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Dashboard
            </a>
            <a href="add_staff.php" class="btn btn-primary">
                <i class="fas fa-user-plus" aria-hidden="true"></i> Add Staff
            </a>
            <span class="badge bg-info text-dark" aria-atomic="true">
                <?= $result->num_rows ?> staff member<?= $result->num_rows !== 1 ? 's' : '' ?>
            </span>
        </div>
    </div>
</div>


            <?php if ($result->num_rows > 0): ?>
                <table class="staff-table" role="table" aria-describedby="staffTableDesc">
                    <caption id="staffTableDesc" class="sr-only">Staff member details including name, username, email, joined date, and actions</caption>
                    <thead>
                        <tr>
                            <th scope="col">Full Name</th>
                            <th scope="col">Username</th>
                            <th scope="col">Email</th>
                            <th scope="col">Joined On</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $rowCount = 0;
                    while ($row = $result->fetch_assoc()):
                        $rowCount++;
                    ?>
                        <tr style="animation: fadeInTableRow <?= $rowCount * 0.1 ?>s ease-out;">
                            <td data-label="Full Name"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td data-label="Username"><?= htmlspecialchars($row['username']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                            <td data-label="Joined On"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td class="actions" data-label="Actions">
                                <?php if ($row['user_id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" class="delete-form" style="display:inline;" data-name="<?= htmlspecialchars($row['full_name']) ?>">
                                        <input type="hidden" name="delete_id" value="<?= $row['user_id'] ?>">
                                        <button type="button" class="action-btn delete-btn" aria-label="Delete <?= htmlspecialchars($row['full_name']) ?>">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i> Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="action-btn" style="background: rgba(0,0,0,0.05); color: var(--gray);" aria-disabled="true" title="You cannot delete yourself">
                                        <i class="fas fa-user-shield" aria-hidden="true"></i> Self
                                    </span>
                                <?php endif; ?>
                                <a href="edit_staff.php?user_id=<?= $row['user_id'] ?>" class="action-btn edit-btn" aria-label="Edit <?= htmlspecialchars($row['full_name']) ?>">
                                    <i class="fas fa-edit" aria-hidden="true"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state" role="alert" aria-live="polite">
                    <i class="fas fa-user-slash"></i>
                    <p>No staff members found.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalDesc" tabindex="-1">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <h3 id="modalTitle">Confirm Deletion</h3>
        </div>
        <p id="modalDesc">Are you sure you want to delete <strong id="staffName"></strong>?</p>
        <div class="modal-actions">
            <button id="cancelBtn" class="modal-btn cancel-btn" type="button">Cancel</button>
            <button id="confirmBtn" class="modal-btn confirm-btn" type="button">Delete</button>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('confirmModal');
    const staffNameElem = document.getElementById('staffName');
    const cancelBtn = document.getElementById('cancelBtn');
    const confirmBtn = document.getElementById('confirmBtn');
    let currentForm = null;

    // Open modal with staff name and form reference
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', () => {
            const form = button.closest('form.delete-form');
            const staffName = form.dataset.name;
            staffNameElem.textContent = staffName;
            currentForm = form;
            modal.classList.add('active');
            modal.focus();
        });
    });

    // Cancel modal
    cancelBtn.addEventListener('click', () => {
        modal.classList.remove('active');
        currentForm = null;
    });

    // Confirm deletion - submit form
    confirmBtn.addEventListener('click', () => {
        if (currentForm) {
            currentForm.submit();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            modal.classList.remove('active');
            currentForm = null;
        }
    });

    // Accessibility: trap focus inside modal while active
    modal.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
            const focusableElements = modal.querySelectorAll('button');
            const firstEl = focusableElements[0];
            const lastEl = focusableElements[focusableElements.length - 1];

            if (e.shiftKey) {
                if (document.activeElement === firstEl) {
                    e.preventDefault();
                    lastEl.focus();
                }
            } else {
                if (document.activeElement === lastEl) {
                    e.preventDefault();
                    firstEl.focus();
                }
            }
        }
    });
</script>
</body>
</html>
