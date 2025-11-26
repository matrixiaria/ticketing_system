<?php
session_start();

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$technicianName = $_SESSION['technician_name'] ?? 'Technician';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Ticketing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <span class="navbar-brand">Ticketing Dashboard</span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50">Welcome, <?php echo htmlspecialchars($technicianName, ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Ticket Handling Flow</h5>
                    </div>
                    <div class="card-body">
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item">Search for the device in the system.
                                <div class="text-muted small">If the device is missing, register it in `device_tracking`.</div>
                            </li>
                            <li class="list-group-item">Create a ticket once the device exists (`ticket_intake`).</li>
                            <li class="list-group-item">Assign the ticket to a technician (`technician_assignment`).</li>
                            <li class="list-group-item">Work on the issue and determine if replacement parts are needed.</li>
                            <li class="list-group-item">If parts are used, record them (`parts_usage`).</li>
                            <li class="list-group-item">Capture post-service feedback to close the job (`post_service_feedback`).</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted">Quick Actions</h6>
                        <p class="mb-3 text-muted small">Start a new ticket workflow</p>
                        <div class="d-grid gap-2">
                            <a href="check_device.php" class="btn btn-primary">Check Device / Start Ticket</a>
                            <a href="view_tickets.php" class="btn btn-outline-primary">View My Tickets</a>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted">Need to log out?</h6>
                        <p class="mb-3">End your session when sharing this workstation.</p>
                        <a href="logout.php" class="btn btn-danger w-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

