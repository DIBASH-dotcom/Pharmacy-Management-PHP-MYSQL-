<?php
require '../includes/database.php';

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

$error = '';
$success = '';
$medicine = [
    'medicine_id' => '',
    'name' => '',
    'description' => '',
    'quantity' => 0,
    'price' => 0,
    'expiry_date' => '',
    'batch_number' => '',
    'manufacturer' => '',
    'category' => '',
    'location' => '',
    'manufacture_date' => ''
];

// Check if editing
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM medicines WHERE medicine_id = $id");
    if ($res && $res->num_rows === 1) {
        $medicine = $res->fetch_assoc();
    } else {
        die("Medicine not found");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input
    $medicine_id = isset($_POST['medicine_id']) ? intval($_POST['medicine_id']) : null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $expiry_date = $_POST['expiry_date'];
    $batch_number = trim($_POST['batch_number']);
    $manufacturer = trim($_POST['manufacturer']);
    $category = trim($_POST['category']);
    $location = trim($_POST['location']);
    $manufacture_date = $_POST['manufacture_date'] ?: null;
    $stock_change = isset($_POST['stock_change']) ? intval($_POST['stock_change']) : 0;

    // Validate required fields
    if ($name === '' || $quantity < 0 || $price < 0 || !validateDate($expiry_date)) {
        $error = "Please fill all required fields with valid values.";
    } else {
        $today = date('Y-m-d');
        if ($expiry_date <= $today) {
            $error = "Expiry date must be a future date.";
        } elseif ($manufacture_date && !validateDate($manufacture_date)) {
            $error = "Manufacture date is invalid.";
        } else {
            if ($medicine_id) {
                // Update medicine

                // Get current quantity to update stock properly
                $resQty = $conn->query("SELECT quantity FROM medicines WHERE medicine_id = $medicine_id");
                if (!$resQty || $resQty->num_rows !== 1) {
                    $error = "Medicine not found.";
                } else {
                    $row = $resQty->fetch_assoc();
                    $newQuantity = $row['quantity'] + $stock_change;

                    if ($newQuantity < 0) {
                        $error = "Resulting stock quantity cannot be negative.";
                    } else {
                        // Prepare update statement
                        $stmt = $conn->prepare("UPDATE medicines SET name=?, description=?, quantity=?, price=?, expiry_date=?, batch_number=?, manufacturer=?, category=?, location=?, manufacture_date=? WHERE medicine_id=?");

                        $stmt->bind_param(
                            "ssidssssssi",
                            $name,
                            $description,
                            $newQuantity,
                            $price,
                            $expiry_date,
                            $batch_number,
                            $manufacturer,
                            $category,
                            $location,
                            $manufacture_date,
                            $medicine_id
                        );

                        if ($stmt->execute()) {
                            $success = "Medicine updated successfully.";
                            // Reload medicine data
                            $res = $conn->query("SELECT * FROM medicines WHERE medicine_id = $medicine_id");
                            $medicine = $res->fetch_assoc();
                        } else {
                            $error = "Update failed: " . $stmt->error;
                        }
                    }
                }
            } else {
                // Insert new medicine
                $stmt = $conn->prepare("INSERT INTO medicines (name, description, quantity, price, expiry_date, batch_number, manufacturer, category, location, manufacture_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "ssidssssss",
                    $name,
                    $description,
                    $quantity,
                    $price,
                    $expiry_date,
                    $batch_number,
                    $manufacturer,
                    $category,
                    $location,
                    $manufacture_date
                );
                if ($stmt->execute()) {
                    $success = "New medicine added successfully.";
                    // Clear form after insert
                    $medicine = [
                        'medicine_id' => '',
                        'name' => '',
                        'description' => '',
                        'quantity' => 0,
                        'price' => 0,
                        'expiry_date' => '',
                        'batch_number' => '',
                        'manufacturer' => '',
                        'category' => '',
                        'location' => '',
                        'manufacture_date' => ''
                    ];
                } else {
                    $error = "Insert failed: " . $stmt->error;
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo $medicine['medicine_id'] ? "Edit Medicine" : "Add Medicine"; ?></title>
<style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 2rem auto; padding: 1rem; }
    label { display: block; margin-top: 10px; font-weight: bold; }
    input[type=text], input[type=number], input[type=date], textarea { width: 100%; padding: 8px; margin-top: 4px; }
    .error { color: red; }
    .success { color: green; }
    button { margin-top: 15px; padding: 10px 20px; }
</style>
</head>
<body>
<h1><?php echo $medicine['medicine_id'] ? "Edit Medicine" : "Add Medicine"; ?></h1>

<?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post" action="">
    <input type="hidden" name="medicine_id" value="<?= htmlspecialchars($medicine['medicine_id']) ?>">

    <label for="name">Name *</label>
    <input type="text" name="name" id="name" required maxlength="100" value="<?= htmlspecialchars($medicine['name']) ?>" />

    <label for="description">Description</label>
    <textarea name="description" id="description"><?= htmlspecialchars($medicine['description']) ?></textarea>

    <label for="quantity">Quantity *</label>
    <input type="number" name="quantity" id="quantity" required min="0" value="<?= htmlspecialchars($medicine['quantity']) ?>" />

    <?php if ($medicine['medicine_id']): ?>
    <label for="stock_change">Change Stock (use negative to reduce)</label>
    <input type="number" name="stock_change" id="stock_change" value="0" />
    <?php endif; ?>

    <label for="price">Price *</label>
    <input type="number" step="0.01" min="0" name="price" id="price" required value="<?= htmlspecialchars($medicine['price']) ?>" />

    <label for="expiry_date">Expiry Date *</label>
    <input type="date" name="expiry_date" id="expiry_date" required value="<?= htmlspecialchars($medicine['expiry_date']) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" />

    <label for="batch_number">Batch Number</label>
    <input type="text" name="batch_number" id="batch_number" maxlength="100" value="<?= htmlspecialchars($medicine['batch_number']) ?>" />

    <label for="manufacturer">Manufacturer</label>
    <input type="text" name="manufacturer" id="manufacturer" maxlength="255" value="<?= htmlspecialchars($medicine['manufacturer']) ?>" />

    <label for="category">Category</label>
    <input type="text" name="category" id="category" maxlength="100" value="<?= htmlspecialchars($medicine['category']) ?>" />

    <label for="location">Location</label>
    <input type="text" name="location" id="location" maxlength="100" value="<?= htmlspecialchars($medicine['location']) ?>" />

    <label for="manufacture_date">Manufacture Date</label>
    <input type="date" name="manufacture_date" id="manufacture_date" value="<?= htmlspecialchars($medicine['manufacture_date']) ?>" max="<?= date('Y-m-d') ?>" />

    <button type="submit"><?= $medicine['medicine_id'] ? "Update Medicine" : "Add Medicine" ?></button>
</form>

<p><a href="medicine_list.php">Back to medicine list</a></p>
</body>
</html>
