<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$technicianId = $_SESSION['technician_id'];
$technicianName = $_SESSION['technician_name'] ?? 'Technician';

// Get all tickets assigned to this technician
$stmt = $conn->prepare('
    SELECT 
        ti.id_ticket_intake,
        ti.reported_by,
        ti.issues_description,
        ti.date,
        dt.serial_number,
        dt.model,
        dt.location,
        dt.OS,
        (SELECT COUNT(*) FROM parts_usage WHERE id_ticket_intake = ti.id_ticket_intake) as has_parts,
        (SELECT COUNT(*) FROM post_service_feedback WHERE id_technician_assignment = ti.id_technician_assignment AND id_ticket_intake = ti.id_ticket_intake) as has_feedback
    FROM ticket_intake ti
    INNER JOIN device_tracking dt ON ti.id_device_tracking = dt.id_device_tracking
    WHERE ti.id_technician_assignment = ?
    ORDER BY ti.date DESC, ti.id_ticket_intake DESC
');
$stmt->bind_param('i', $technicianId);
$stmt->execute();
$tickets = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets | Ticketing System</title>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>My Tickets</h4>
            <a href="check_device.php" class="btn btn-primary">Create New Ticket</a>
        </div>

        <?php if ($tickets->num_rows === 0) : ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <p class="text-muted mb-0">No tickets assigned to you yet.</p>
                    <a href="check_device.php" class="btn btn-primary mt-3">Create Your First Ticket</a>
                </div>
            </div>
        <?php else : ?>
            <div class="row g-4">
                <?php while ($ticket = $tickets->fetch_assoc()) : ?>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Ticket #<?php echo $ticket['id_ticket_intake']; ?></h6>
                                    <small class="text-muted">Date: <?php echo htmlspecialchars($ticket['date'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </div>
                                <div>
                                    <?php if ($ticket['has_feedback'] > 0) : ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php elseif ($ticket['has_parts'] > 0) : ?>
                                        <span class="badge bg-warning">Parts Recorded</span>
                                    <?php else : ?>
                                        <span class="badge bg-primary">In Progress</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted small">Device Information</h6>
                                        <p class="mb-1"><strong>Serial:</strong> <?php echo htmlspecialchars($ticket['serial_number'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="mb-1"><strong>Model:</strong> <?php echo htmlspecialchars($ticket['model'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($ticket['location'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="mb-0"><strong>OS:</strong> <?php echo htmlspecialchars($ticket['OS'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted small">Ticket Details</h6>
                                        <p class="mb-1"><strong>Reported By:</strong> <?php echo htmlspecialchars($ticket['reported_by'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="mb-0"><strong>Issue:</strong> <?php echo htmlspecialchars($ticket['issues_description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <?php if ($ticket['has_feedback'] == 0) : ?>
                                        <a href="parts_usage.php?ticket_id=<?php echo $ticket['id_ticket_intake']; ?>" class="btn btn-sm btn-outline-primary">Record Parts / Continue</a>
                                    <?php endif; ?>
                                    <?php if ($ticket['has_parts'] > 0 && $ticket['has_feedback'] == 0) : ?>
                                        <a href="post_service_feedback.php?ticket_id=<?php echo $ticket['id_ticket_intake']; ?>" class="btn btn-sm btn-success">Complete Feedback</a>
                                    <?php endif; ?>
                                    <?php if ($ticket['has_feedback'] > 0) : ?>
                                        <a href="post_service_feedback.php?ticket_id=<?php echo $ticket['id_ticket_intake']; ?>" class="btn btn-sm btn-outline-secondary">View Feedback</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

