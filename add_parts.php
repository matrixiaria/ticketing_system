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
    $partName = trim($_POST['part_name'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $cost = trim($_POST['cost'] ?? '');
    $date = $_POST['date'] ?? date('Y-m-d');
    
    if ($ticketId === 0 || $partName === '' || $quantity === '' || $cost === '') {
        $errors[] = 'All fields are required.';
    } else {
        $stmt = $conn->prepare('INSERT INTO parts_usage (id_ticket_intake, part_name, quantity, cost, date) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $ticketId, $partName, $quantity, $cost, $date);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = 'Failed to record part usage. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Parts Usage | Ticketing System</title>
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
                        <h5 class="mb-0">Record Parts Usage</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success) : ?>
                            <div class="alert alert-success">
                                <strong>Success!</strong> Part usage recorded successfully.
                            </div>
                            <div class="d-grid gap-2">
                                <a href="add_parts.php?ticket_id=<?php echo $ticketId; ?>" class="btn btn-outline-primary">Add Another Part</a>
                                <a href="post_service_feedback.php?ticket_id=<?php echo $ticketId; ?>" class="btn btn-success">Continue to Feedback</a>
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

                                <form method="POST">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id_ticket_intake']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="part_name" class="form-label">Part Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="part_name" name="part_name" 
                                               value="<?php echo htmlspecialchars($_POST['part_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                               required autofocus>
                                    </div>
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="quantity" name="quantity" 
                                               value="<?php echo htmlspecialchars($_POST['quantity'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                               required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="cost" class="form-label">Cost <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="cost" name="cost" 
                                               value="<?php echo htmlspecialchars($_POST['cost'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                               placeholder="e.g., 25.50" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="date" name="date" 
                                               value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" 
                                               required>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">Record Part Usage</button>
                                        <a href="parts_usage.php?ticket_id=<?php echo $ticket['id_ticket_intake']; ?>" class="btn btn-outline-secondary">Back</a>
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

