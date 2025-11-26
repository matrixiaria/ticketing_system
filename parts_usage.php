<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$technicianId = $_SESSION['technician_id'];
$technicianName = $_SESSION['technician_name'] ?? 'Technician';
$ticketId = $_GET['ticket_id'] ?? null;
$errors = [];
$success = false;
$ticket = null;
$partsNeeded = null;

// Get ticket information
if ($ticketId) {
    $stmt = $conn->prepare('
        SELECT ti.*, dt.serial_number, dt.model
        FROM ticket_intake ti
        INNER JOIN device_tracking dt ON ti.id_device_tracking = dt.id_device_tracking
        WHERE ti.id_ticket_intake = ? AND ti.id_technician_assignment = ?
        LIMIT 1
    ');
    $stmt->bind_param('ii', $ticketId, $technicianId);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    
    if (!$ticket) {
        $errors[] = 'Ticket not found or not assigned to you.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    $partsNeeded = $_POST['parts_needed'] ?? '';
    
    if ($ticketId === 0) {
        $errors[] = 'Invalid ticket ID.';
    } elseif ($partsNeeded === '') {
        $errors[] = 'Please select if parts are needed.';
    } else {
        if ($partsNeeded === 'yes') {
            // Redirect to add parts
            header('Location: add_parts.php?ticket_id=' . $ticketId);
            exit;
        } else {
            // No parts needed, go directly to feedback
            header('Location: post_service_feedback.php?ticket_id=' . $ticketId);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parts Usage | Ticketing System</title>
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
                        <h5 class="mb-0">Parts Usage</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)) : ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error) : ?>
                                    <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($ticket) : ?>
                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <h6 class="card-title">Ticket #<?php echo $ticket['id_ticket_intake']; ?></h6>
                                    <p class="mb-1"><strong>Device:</strong> <?php echo htmlspecialchars($ticket['serial_number'] . ' - ' . $ticket['model'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="mb-0"><strong>Issue:</strong> <?php echo htmlspecialchars($ticket['issues_description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id_ticket_intake']; ?>">
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Are there any parts needed for this repair? <span class="text-danger">*</span></label>
                                    <div class="mt-3">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="parts_needed" id="parts_yes" value="yes" required>
                                            <label class="form-check-label" for="parts_yes">
                                                Yes, parts are needed
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="parts_needed" id="parts_no" value="no" required>
                                            <label class="form-check-label" for="parts_no">
                                                No, no parts needed
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Continue</button>
                                    <a href="view_tickets.php" class="btn btn-outline-secondary">Back to Tickets</a>
                                </div>
                            </form>
                        <?php else : ?>
                            <div class="alert alert-warning">
                                Ticket not found. Please select a valid ticket.
                            </div>
                            <a href="view_tickets.php" class="btn btn-primary">View My Tickets</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

