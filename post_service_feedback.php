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
$existingFeedback = null;

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
    } else {
        // Check for existing feedback
        $feedbackStmt = $conn->prepare('SELECT * FROM post_service_feedback WHERE id_technician_assignment = ? AND id_ticket_intake = ? LIMIT 1');
        $feedbackStmt->bind_param('ii', $technicianId, $ticketId);
        $feedbackStmt->execute();
        $feedbackResult = $feedbackStmt->get_result();
        $existingFeedback = $feedbackResult->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $dateSolved = $_POST['date_solved'] ?? date('Y-m-d');
    
    if ($ticketId === 0 || $remarks === '' || $status === '') {
        $errors[] = 'All fields are required.';
    } else {
        if ($existingFeedback) {
            // Update existing feedback
            $stmt = $conn->prepare('UPDATE post_service_feedback SET remarks = ?, status = ?, date_solved = ? WHERE id_post_service_feedback = ?');
            $stmt->bind_param('sssi', $remarks, $status, $dateSolved, $existingFeedback['id_post_service_feedback']);
        } else {
            // Create new feedback
            $stmt = $conn->prepare('INSERT INTO post_service_feedback (id_technician_assignment, id_ticket_intake, remarks, status, date_solved) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iisss', $technicianId, $ticketId, $remarks, $status, $dateSolved);
        }
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = 'Failed to save feedback. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Service Feedback | Ticketing System</title>
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
                        <h5 class="mb-0">Post Service Feedback</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success) : ?>
                            <div class="alert alert-success">
                                <strong>Success!</strong> Feedback recorded successfully. Ticket is now completed.
                            </div>
                            <div class="d-grid gap-2">
                                <a href="view_tickets.php" class="btn btn-primary">View All Tickets</a>
                                <a href="check_device.php" class="btn btn-outline-primary">Create New Ticket</a>
                            </div>
                        <?php else : ?>
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

                                <?php if ($existingFeedback) : ?>
                                    <div class="alert alert-info">
                                        <strong>Note:</strong> You have already submitted feedback for this ticket. You can update it below.
                                    </div>
                                <?php endif; ?>

                                <form method="POST">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id_ticket_intake']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="remarks" class="form-label">Remarks <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="remarks" name="remarks" rows="4" required><?php echo htmlspecialchars($existingFeedback['remarks'] ?? ($_POST['remarks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        <small class="text-muted">Describe the work performed and any additional notes.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="">-- Select Status --</option>
                                            <option value="Resolved" <?php echo (($existingFeedback['status'] ?? ($_POST['status'] ?? '')) === 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                                            <option value="Completed" <?php echo (($existingFeedback['status'] ?? ($_POST['status'] ?? '')) === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Fixed" <?php echo (($existingFeedback['status'] ?? ($_POST['status'] ?? '')) === 'Fixed') ? 'selected' : ''; ?>>Fixed</option>
                                            <option value="Finished" <?php echo (($existingFeedback['status'] ?? ($_POST['status'] ?? '')) === 'Finished') ? 'selected' : ''; ?>>Finished</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="date_solved" class="form-label">Date Solved <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="date_solved" name="date_solved" 
                                               value="<?php echo htmlspecialchars($existingFeedback['date_solved'] ?? ($_POST['date_solved'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8'); ?>" 
                                               required>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success"><?php echo $existingFeedback ? 'Update' : 'Submit'; ?> Feedback</button>
                                        <a href="view_tickets.php" class="btn btn-outline-secondary">Back to Tickets</a>
                                    </div>
                                </form>
                            <?php else : ?>
                                <div class="alert alert-warning">
                                    Ticket not found. Please select a valid ticket.
                                </div>
                                <a href="view_tickets.php" class="btn btn-primary">View My Tickets</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

