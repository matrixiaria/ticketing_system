<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$technicianName = $_SESSION['technician_name'] ?? 'Technician';
$message = '';
$device = null;
$searchPerformed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $serialNumber = trim($_POST['serial_number'] ?? '');
    
    if ($serialNumber === '') {
        $message = '<div class="alert alert-warning">Please enter a serial number.</div>';
    } else {
        $stmt = $conn->prepare('SELECT * FROM device_tracking WHERE serial_number = ? LIMIT 1');
        $stmt->bind_param('s', $serialNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $device = $result->fetch_assoc();
        $searchPerformed = true;
        
        if ($device) {
            $message = '<div class="alert alert-success">Device found in system!</div>';
        } else {
            $message = '<div class="alert alert-info">Device not found. You can register it now.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Device | Ticketing System</title>
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
                        <h5 class="mb-0">Check Device</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Enter the device serial number to check if it exists in the system.</p>
                        
                        <?php echo $message; ?>
                        
                        <form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label for="serial_number" class="form-label">Serial Number</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                       value="<?php echo htmlspecialchars($_POST['serial_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                       required autofocus>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="search" class="btn btn-primary">Search Device</button>
                            </div>
                        </form>

                        <?php if ($device) : ?>
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Device Information</h6>
                                    <p class="mb-1"><strong>Serial Number:</strong> <?php echo htmlspecialchars($device['serial_number'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="mb-1"><strong>Model:</strong> <?php echo htmlspecialchars($device['model'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($device['location'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="mb-1"><strong>OS:</strong> <?php echo htmlspecialchars($device['OS'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="mb-0"><strong>Date Issued:</strong> <?php echo htmlspecialchars($device['date_issued'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            <div class="d-grid">
                                <a href="create_ticket.php?device_id=<?php echo $device['id_device_tracking']; ?>" class="btn btn-success">Create Ticket for This Device</a>
                            </div>
                        <?php elseif ($searchPerformed && !$device) : ?>
                            <div class="d-grid">
                                <a href="register_device.php?serial_number=<?php echo urlencode($_POST['serial_number'] ?? ''); ?>" class="btn btn-primary">Register This Device</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

