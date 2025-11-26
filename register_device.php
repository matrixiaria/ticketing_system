<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$technicianName = $_SESSION['technician_name'] ?? 'Technician';
$errors = [];
$success = false;
$prefillSerial = $_GET['serial_number'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serialNumber = trim($_POST['serial_number'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $os = trim($_POST['OS'] ?? '');
    $dateIssued = $_POST['date_issued'] ?? date('Y-m-d');
    
    if ($serialNumber === '' || $model === '' || $location === '' || $os === '') {
        $errors[] = 'All fields are required.';
    } else {
        // Check if serial number already exists
        $checkStmt = $conn->prepare('SELECT id_device_tracking FROM device_tracking WHERE serial_number = ? LIMIT 1');
        $checkStmt->bind_param('s', $serialNumber);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'A device with this serial number already exists.';
        } else {
            $stmt = $conn->prepare('INSERT INTO device_tracking (serial_number, model, location, OS, date_issued) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $serialNumber, $model, $location, $os, $dateIssued);
            
            if ($stmt->execute()) {
                $success = true;
                $deviceId = $conn->insert_id;
            } else {
                $errors[] = 'Failed to register device. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Device | Ticketing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Ticketing System</a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50">Welcome, <?php echo htmlspecialchars($technicianName, ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Register New Device</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success) : ?>
                            <div class="alert alert-success">
                                <strong>Success!</strong> Device registered successfully.
                            </div>
                            <div class="d-grid gap-2">
                                <a href="create_ticket.php?device_id=<?php echo $deviceId; ?>" class="btn btn-success">Create Ticket for This Device</a>
                                <a href="check_device.php" class="btn btn-outline-secondary">Check Another Device</a>
                            </div>
                        <?php else : ?>
                            <?php if (!empty($errors)) : ?>
                                <div class="alert alert-danger">
                                    <?php foreach ($errors as $error) : ?>
                                        <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label for="serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                           value="<?php echo htmlspecialchars($prefillSerial ?: ($_POST['serial_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" 
                                           required autofocus>
                                </div>
                                <div class="mb-3">
                                    <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="model" name="model" 
                                           value="<?php echo htmlspecialchars($_POST['model'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                           required>
                                </div>
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo htmlspecialchars($_POST['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                           required>
                                </div>
                                <div class="mb-3">
                                    <label for="OS" class="form-label">Operating System <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="OS" name="OS" 
                                           value="<?php echo htmlspecialchars($_POST['OS'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                           required>
                                </div>
                                <div class="mb-3">
                                    <label for="date_issued" class="form-label">Date Issued <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date_issued" name="date_issued" 
                                           value="<?php echo htmlspecialchars($_POST['date_issued'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" 
                                           required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Register Device</button>
                                    <a href="check_device.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

