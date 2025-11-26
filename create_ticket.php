<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$technicianId = $_SESSION['technician_id'];
$technicianName = $_SESSION['technician_name'] ?? 'Technician';
$errors = [];
$success = false;
$deviceId = $_GET['device_id'] ?? null;
$device = null;

// Get device information if device_id is provided
if ($deviceId) {
    $stmt = $conn->prepare('SELECT * FROM device_tracking WHERE id_device_tracking = ? LIMIT 1');
    $stmt->bind_param('i', $deviceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $device = $result->fetch_assoc();
    
    if (!$device) {
        $errors[] = 'Device not found.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceId = intval($_POST['device_id'] ?? 0);
    $reportedBy = trim($_POST['reported_by'] ?? '');
    $issuesDescription = trim($_POST['issues_description'] ?? '');
    $date = $_POST['date'] ?? date('Y-m-d');
    
    if ($deviceId === 0 || $reportedBy === '' || $issuesDescription === '') {
        $errors[] = 'All fields are required.';
    } else {
        // Verify device exists
        $checkStmt = $conn->prepare('SELECT id_device_tracking FROM device_tracking WHERE id_device_tracking = ? LIMIT 1');
        $checkStmt->bind_param('i', $deviceId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = 'Device not found.';
        } else {
            // Create ticket with technician assignment
            $stmt = $conn->prepare('INSERT INTO ticket_intake (id_device_tracking, id_technician_assignment, reported_by, issues_description, date) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iisss', $deviceId, $technicianId, $reportedBy, $issuesDescription, $date);
            
            if ($stmt->execute()) {
                $ticketId = $conn->insert_id;
                $success = true;
            } else {
                $errors[] = 'Failed to create ticket. Please try again.';
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
    <title>Create Ticket | Ticketing System</title>
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
                        <h5 class="mb-0">Create Ticket</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success) : ?>
                            <div class="alert alert-success">
                                <strong>Success!</strong> Ticket created successfully. Ticket ID: <?php echo $ticketId; ?>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="view_tickets.php" class="btn btn-primary">View My Tickets</a>
                                <a href="check_device.php" class="btn btn-outline-secondary">Create Another Ticket</a>
                            </div>
                        <?php else : ?>
                            <?php if (!empty($errors)) : ?>
                                <div class="alert alert-danger">
                                    <?php foreach ($errors as $error) : ?>
                                        <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($device) : ?>
                                <div class="card bg-light mb-4">
                                    <div class="card-body">
                                        <h6 class="card-title">Device Information</h6>
                                        <p class="mb-1"><strong>Serial Number:</strong> <?php echo htmlspecialchars($device['serial_number'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="mb-1"><strong>Model:</strong> <?php echo htmlspecialchars($device['model'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($device['location'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="mb-0"><strong>OS:</strong> <?php echo htmlspecialchars($device['OS'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($deviceId ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                
                                <?php if (!$deviceId) : ?>
                                    <div class="mb-3">
                                        <label for="device_id_select" class="form-label">Select Device <span class="text-danger">*</span></label>
                                        <select class="form-select" id="device_id_select" name="device_id" required>
                                            <option value="">-- Select Device --</option>
                                            <?php
                                            $devicesStmt = $conn->query('SELECT id_device_tracking, serial_number, model FROM device_tracking ORDER BY serial_number');
                                            while ($d = $devicesStmt->fetch_assoc()) :
                                            ?>
                                                <option value="<?php echo $d['id_device_tracking']; ?>" <?php echo ($deviceId == $d['id_device_tracking']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($d['serial_number'] . ' - ' . $d['model'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="reported_by" class="form-label">Reported By <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="reported_by" name="reported_by" 
                                           value="<?php echo htmlspecialchars($_POST['reported_by'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                           required>
                                </div>
                                <div class="mb-3">
                                    <label for="issues_description" class="form-label">Issues Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="issues_description" name="issues_description" rows="4" required><?php echo htmlspecialchars($_POST['issues_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" 
                                           required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Create Ticket</button>
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

