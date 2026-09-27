<?php
session_start();

$patient_id = $_SESSION['patient_id'] ?? null;
$page_title = "Patient Panel";

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Karachi');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vaxify";

// Initialize variables
$error = '';
$success = '';
$appointments = [];
$total_appointments = 0;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle AJAX requests FIRST, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Security token invalid']);
        exit();
    }
    
    try {
        // Create PDO connection for AJAX requests
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        switch ($_POST['action']) {
            case 'cancel':
                handleCancelAppointment($pdo, $patient_id);
                break;
                
            case 'reschedule':
                handleRescheduleAppointment($pdo, $patient_id);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit();
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Regular page load (non-AJAX)
try {
    // Create PDO connection for regular page load
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch appointments with filters
    $appointments = fetchAppointments($pdo, $patient_id, $current_page, $items_per_page);
    $total_appointments = countTotalAppointments($pdo, $patient_id);
    
} catch (PDOException $e) {
    $error = "Database connection failed: " . htmlspecialchars($e->getMessage());
}

// Include header after handling AJAX to avoid output issues
include 'includes/patient_header.php';

/**
 * Fetch appointments with pagination and filters
 */
function fetchAppointments($pdo, $patient_id, $page, $per_page) {
    $offset = ($page - 1) * $per_page;
    
    // Build base query
    $sql = "SELECT a.*, h.name AS hospital_name, h.address, h.contact_numbers, 
                   h.opening_time, h.closing_time, h.services 
            FROM appointments a 
            JOIN hospitals h ON a.hospital_id = h.id 
            WHERE a.patient_id = :patient_id";
    
    // Add filters if provided
    $params = [':patient_id' => $patient_id];
    
    if (!empty($_GET['date_from'])) {
        $sql .= " AND DATE(a.appointment_date) >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }
    
    if (!empty($_GET['date_to'])) {
        $sql .= " AND DATE(a.appointment_date) <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }
    
    if (!empty($_GET['hospital_name'])) {
        $sql .= " AND h.name LIKE :hospital_name";
        $params[':hospital_name'] = '%' . $_GET['hospital_name'] . '%';
    }
    
    if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
        $sql .= " AND a.status = :status";
        $params[':status'] = $_GET['status'];
    }
    
    // Add ordering and pagination
    $sql .= " ORDER BY a.appointment_date DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $per_page;
    $params[':offset'] = $offset;
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count total appointments for pagination
 */
function countTotalAppointments($pdo, $patient_id) {
    $sql = "SELECT COUNT(*) FROM appointments a 
            JOIN hospitals h ON a.hospital_id = h.id 
            WHERE a.patient_id = :patient_id";
    
    $params = [':patient_id' => $patient_id];
    
    if (!empty($_GET['date_from'])) {
        $sql .= " AND DATE(a.appointment_date) >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }
    
    if (!empty($_GET['date_to'])) {
        $sql .= " AND DATE(a.appointment_date) <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }
    
    if (!empty($_GET['hospital_name'])) {
        $sql .= " AND h.name LIKE :hospital_name";
        $params[':hospital_name'] = '%' . $_GET['hospital_name'] . '%';
    }
    
    if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
        $sql .= " AND a.status = :status";
        $params[':status'] = $_GET['status'];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * Handle appointment cancellation
 */
function handleCancelAppointment($pdo, $patient_id) {
    if (empty($_POST['appointment_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
        return;
    }
    
    $appointment_id = intval($_POST['appointment_id']);
    
    try {
        $pdo->beginTransaction();
        
        // Check if appointment exists and belongs to patient
        $stmt = $pdo->prepare("SELECT status, appointment_date, appointment_time FROM appointments 
                              WHERE id = :id AND patient_id = :patient_id");
        $stmt->execute([':id' => $appointment_id, ':patient_id' => $patient_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            throw new Exception("Appointment not found or you don't have permission to cancel it");
        }
        
        // Check if cancellation is allowed
        $allowed_statuses = ['pending', 'scheduled'];
        if (!in_array($appointment['status'], $allowed_statuses)) {
            throw new Exception("Cannot cancel appointment with status: " . $appointment['status']);
        }
        
        // Check if appointment is in the future
        $appointment_datetime = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
        if ($appointment_datetime < time()) {
            throw new Exception("Cannot cancel past appointments");
        }
        
        // UPDATE the appointment status to 'cancelled'
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = :id");
        $stmt->execute([':id' => $appointment_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Appointment cancelled successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Cancellation failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Handle appointment rescheduling
 */
function handleRescheduleAppointment($pdo, $patient_id) {
    if (empty($_POST['appointment_id']) || empty($_POST['new_date']) || empty($_POST['new_time'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    $appointment_id = intval($_POST['appointment_id']);
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];
    
    try {
        $pdo->beginTransaction();
        
        // Get current appointment details
        $stmt = $pdo->prepare("SELECT a.*, h.opening_time, h.closing_time 
                              FROM appointments a 
                              JOIN hospitals h ON a.hospital_id = h.id 
                              WHERE a.id = :id AND a.patient_id = :patient_id");
        $stmt->execute([':id' => $appointment_id, ':patient_id' => $patient_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            throw new Exception("Appointment not found");
        }
        
        // Validate new date/time
        $new_datetime = $new_date . ' ' . $new_time;
        $new_timestamp = strtotime($new_datetime);
        
        if ($new_timestamp < time()) {
            throw new Exception("Cannot schedule appointment in the past");
        }
        
        // Check hospital operating hours
        $hospital_opening = strtotime($new_date . ' ' . $appointment['opening_time']);
        $hospital_closing = strtotime($new_date . ' ' . $appointment['closing_time']);
        
        if ($new_timestamp < $hospital_opening || $new_timestamp > $hospital_closing) {
            throw new Exception("Selected time is outside hospital operating hours");
        }
        
        // Check for scheduling conflicts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments 
                              WHERE hospital_id = :hospital_id 
                              AND appointment_date = :appointment_date 
                              AND appointment_time = :appointment_time 
                              AND status IN ('pending', 'scheduled', 'approved')
                              AND id != :id");
        $stmt->execute([
            ':hospital_id' => $appointment['hospital_id'],
            ':appointment_date' => $new_date,
            ':appointment_time' => $new_time,
            ':id' => $appointment_id
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Time slot is already booked");
        }
        
        // Update appointment
        $stmt = $pdo->prepare("UPDATE appointments 
                              SET appointment_date = :appointment_date, 
                                  appointment_time = :appointment_time,
                                  status = 'scheduled'
                              WHERE id = :id");
        $stmt->execute([
            ':appointment_date' => $new_date,
            ':appointment_time' => $new_time,
            ':id' => $appointment_id
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Appointment rescheduled successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Rescheduling failed: ' . $e->getMessage()
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Vaxify</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --background-color: #ffffff;
            --default-color: #2c3031;
            --heading-color: #18444c; 
            --accent-color: #049ebb; 
            --surface-color: #ffffff; 
            --contrast-color: #ffffff; 
        }

        .page-title {
            color: var(--heading-color);
            font-size: 1.7rem;
            margin-bottom: 30px;
            text-align: left;
        }
        .filters {
            background: var(--surface-color);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .form-group {
            margin-bottom: 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--heading-color);
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            background: var(--accent-color);
            color: var(--contrast-color);
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #037e98;
        }
        .appointments-section {
            background: var(--surface-color);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: var(--heading-color);
            color: var(--contrast-color);
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-scheduled { background: #d1ecf1; color: #0c5460; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled, .status-rejected { background: #f8d7da; color: #721c24; }
        .action-btn {
            padding: 6px 12px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        .btn-view { background: #17a2b8; color: white; }
        .btn-cancel { background: #dc3545; color: white; }
        .btn-reschedule { background: #ffc107; color: #212529; }
        .btn-print { background: #28a745; color: white; }
        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal.show {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 16px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
            width: 95%;
            max-width: 520px;
            padding: 30px;
            position: relative;
            animation: slideUp 0.35s ease forwards;
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--heading-color);
        }

        .close-btn {
            background: transparent;
            border: none;
            font-size: 1.8rem;
            color: #6c757d;
            cursor: pointer;
            transition: transform 0.2s ease, color 0.3s ease;
            padding: 0;           
            line-height: 1;     
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;            
            height: 32px;         
            border-radius: 50%;     
        }

        .close-btn:hover {
            color: var(--accent-color);
            background-color: rgba(0, 0, 0, 0.05); 
            transform: rotate(90deg);
        }


        .modal-body {
            font-size: 0.95rem;
            color: var(--default-color);
            line-height: 1.6;
            max-height: 65vh;
            overflow-y: auto;
            padding-right: 5px;
        }
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }
        .modal-body::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        .detail-item {
            background: #f1faff;
            border-left: 4px solid var(--accent-color);
            border-radius: 8px;
            padding: 10px 12px;
        }
        .detail-item strong {
            display: block;
            color: var(--heading-color);
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 0.95rem;
        }
        .modal-content input[type="date"],
        .modal-content input[type="time"],
        .modal-content select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            transition: all 0.3s ease;
        }
        .modal-content input:focus,
        .modal-content select:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 6px rgba(4, 158, 187, 0.2);
        }
        .modal-content button[type="submit"],
        .cancel-actions .btn-confirm,
        .cancel-actions .btn-cancel {
            width: 100%;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        #rescheduleSubmit {
            background: var(--accent-color);
            color: white;
        }
        #rescheduleSubmit:hover {
            background: #037e98;
        }
        .cancel-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .cancel-actions .btn-confirm {
            background: #dc3545;
            color: #fff;
        }
        .cancel-actions .btn-cancel {
            background: #6c757d;
            color: #fff;
        }
        .cancel-actions button:hover {
            opacity: 0.9;
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: white;
            z-index: 2100;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.4s ease;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .toast.success { background: #28a745; }
        .toast.error { background: #dc3545; }
        .appointment-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: none;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .card-title {
            font-weight: bold;
            color: var(--heading-color);
        }
        .card-detail {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        .card-detail strong {
            min-width: 100px;
        }
        .card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }
        .card-actions .action-btn {
            flex: 1;
            min-width: 80px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 10px;
        }
        .page-btn {
            padding: 8px 16px;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .page-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--accent-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            .page-title {
                font-size: 1.5rem;
            }
            .table-responsive {
                display: none;
            }
            .appointment-card {
                display: block;
            }
            .modal-content {
                padding: 20px;
                max-width: 90%;
            }
            .modal-title {
                font-size: 1.3rem;
            }
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .card-detail {
                flex-direction: column;
            }
            .card-detail strong {
                min-width: auto;
                margin-bottom: 2px;
            }
            .card-actions .action-btn {
                min-width: 70px;
                font-size: 11px;
                padding: 5px 8px;
            }
            .modal-content {
                padding: 18px;
                border-radius: 12px;
            }
            .close-btn {
                font-size: 1.5rem;
            }
            .modal-body {
                font-size: 0.9rem;
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
    <!-- Filter Section -->   
    <div class="filters">
        <form method="GET" id="filterForm">
            <h1 class="page-title">My Appointments</h1>
            <div class="filter-grid">
                <div class="form-group">
                    <label for="date_from">From Date</label>
                    <input type="date" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="date_to">To Date</label>
                    <input type="date" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="hospital_name">Hospital Name</label>
                    <input type="text" id="hospital_name" name="hospital_name" 
                           value="<?php echo htmlspecialchars($_GET['hospital_name'] ?? ''); ?>" 
                           placeholder="Enter hospital name">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="all">All Statuses</option>
                        <option value="pending" <?php echo (($_GET['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="scheduled" <?php echo (($_GET['status'] ?? '') === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="approved" <?php echo (($_GET['status'] ?? '') === 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="completed" <?php echo (($_GET['status'] ?? '') === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo (($_GET['status'] ?? '') === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit">Apply Filters</button>
                </div>
                <div class="form-group">
                    <button type="button" id="resetFilters">Reset</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Error/Success Messages -->
    <?php if ($error): ?>
        <div class="toast error show" id="errorToast">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Appointments Section -->
    <div class="appointments-section">
        <!-- Desktop Table View -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Hospital</th>
                        <th>Test Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No appointments found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr class="appointment-row" data-id="<?php echo $appointment['id']; ?>">
                                <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                                <td><?php echo htmlspecialchars(date('M j, Y', strtotime($appointment['appointment_date']))); ?></td>
                                <td><?php echo htmlspecialchars(date('g:i A', strtotime($appointment['appointment_time']))); ?></td>
                                <td><?php echo htmlspecialchars($appointment['hospital_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['test_type'] ?? 'COVID-19 Test'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($appointment['status']); ?>">
                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn btn-view" onclick="openDetailsModal(<?php echo $appointment['id']; ?>)">
                                        View
                                    </button>
                                    <?php if (in_array($appointment['status'], ['pending', 'scheduled'])): ?>
                                        <button class="action-btn btn-cancel" onclick="openCancelModal(<?php echo $appointment['id']; ?>)">
                                            Cancel
                                        </button>
                                        <button class="action-btn btn-reschedule" onclick="openRescheduleModal(<?php echo $appointment['id']; ?>)">
                                            Reschedule
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="card-view">
            <?php foreach ($appointments as $appointment): ?>
                <div class="appointment-card" data-appointment='<?php echo htmlspecialchars(json_encode($appointment), ENT_QUOTES, 'UTF-8'); ?>'>
                    <div class="card-header">
                        <div class="card-title">Appointment #<?php echo htmlspecialchars($appointment['id']); ?></div>
                        <span class="status-badge status-<?php echo htmlspecialchars($appointment['status']); ?>">
                            <?php echo htmlspecialchars($appointment['status']); ?>
                        </span>
                    </div>
                    <div class="card-detail">
                        <strong>Date:</strong> 
                        <span><?php echo htmlspecialchars(date('M j, Y', strtotime($appointment['appointment_date']))); ?></span>
                    </div>
                    <div class="card-detail">
                        <strong>Time:</strong> 
                        <span><?php echo htmlspecialchars(date('g:i A', strtotime($appointment['appointment_time']))); ?></span>
                    </div>
                    <div class="card-detail">
                        <strong>Hospital:</strong> 
                        <span><?php echo htmlspecialchars($appointment['hospital_name']); ?></span>
                    </div>
                    <div class="card-detail">
                        <strong>Test Type:</strong> 
                        <span><?php echo htmlspecialchars($appointment['test_type'] ?? 'COVID-19 Test'); ?></span>
                    </div>
                    <div class="card-actions">
                        <button class="action-btn btn-view" onclick="openDetailsModal(<?php echo $appointment['id']; ?>)">
                            View Details
                        </button>
                        <?php if (in_array($appointment['status'], ['pending', 'scheduled'])): ?>
                            <button class="action-btn btn-cancel" onclick="openCancelModal(<?php echo $appointment['id']; ?>)">
                                Cancel
                            </button>
                            <button class="action-btn btn-reschedule" onclick="openRescheduleModal(<?php echo $appointment['id']; ?>)">
                                Reschedule
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        

    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Appointment Details</h2>
                <button class="close-btn" onclick="closeDetailsModal()">&times;</button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Details will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Reschedule Appointment</h2>
                <button class="close-btn" onclick="closeRescheduleModal()">&times;</button>
            </div>
            <form id="rescheduleForm">
                <input type="hidden" id="rescheduleAppointmentId" name="appointment_id">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="reschedule">
                <div class="form-group">
                    <label for="new_date">New Date</label>
                    <input type="date" id="new_date" name="new_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="new_time">New Time</label>
                    <input type="time" id="new_time" name="new_time" required>
                </div>
                <div class="form-group">
                    <button type="submit" id="rescheduleSubmit">
                        Reschedule Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div id="cancelModal" class="modal cancel-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Cancel Appointment</h2>
                <button class="close-btn" onclick="closeCancelModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this appointment?</p>
                <p class="appointment-details" id="cancelAppointmentDetails"></p>
                <div class="cancel-actions">
                    <button class="btn-confirm" id="confirmCancel">Yes, Cancel</button>
                    <button class="btn-cancel" onclick="closeCancelModal()">No, Keep</button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

<script>
    let currentAppointmentId = null;

    // Details Modal Functions
    function openDetailsModal(appointmentId) {
        currentAppointmentId = appointmentId;
        let appointmentData = null;
        
        const desktopRow = document.querySelector(`.appointment-row[data-id="${appointmentId}"]`);
        if (desktopRow) {
            const cells = desktopRow.cells;
            appointmentData = {
                id: appointmentId,
                date: cells[1].textContent,
                time: cells[2].textContent,
                hospital: cells[3].textContent,
                testType: cells[4].textContent,
                status: cells[5].querySelector('.status-badge').textContent.trim()
            };
        }
        
        const mobileCards = document.querySelectorAll('.appointment-card[data-appointment]');
        for (let card of mobileCards) {
            try {
                const cardData = JSON.parse(card.getAttribute('data-appointment'));
                if (cardData.id == appointmentId) {
                    appointmentData = cardData;
                    break;
                }
            } catch (e) {
                console.error('Error parsing appointment data:', e);
            }
        }
        
        let detailsHtml = '';
        if (appointmentData) {
            detailsHtml = `
                <div class="details-grid">
                    <div class="detail-item">
                        <strong>Appointment ID:</strong> ${appointmentData.id}
                    </div>
                    <div class="detail-item">
                        <strong>Date:</strong> ${appointmentData.date || (appointmentData.appointment_date ? new Date(appointmentData.appointment_date).toLocaleDateString() : 'N/A')}
                    </div>
                    <div class="detail-item">
                        <strong>Time:</strong> ${appointmentData.time || (appointmentData.appointment_time ? new Date('1970-01-01T' + appointmentData.appointment_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'N/A')}
                    </div>
                    <div class="detail-item">
                        <strong>Hospital:</strong> ${appointmentData.hospital || appointmentData.hospital_name}
                    </div>
                    <div class="detail-item">
                        <strong>Test Type:</strong> ${appointmentData.testType || appointmentData.test_type}
                    </div>
                    <div class="detail-item">
                        <strong>Status:</strong> <span class="status-badge status-${appointmentData.status}">${appointmentData.status}</span>
                    </div>
                </div>
            `;
        } else {
            detailsHtml = '<p>Unable to load appointment details.</p>';
        }
        
        document.getElementById('detailsModalBody').innerHTML = detailsHtml;
        document.getElementById('detailsModal').classList.add('show');
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').classList.remove('show');
    }

    function openCancelModal(appointmentId) {
        currentAppointmentId = appointmentId;
        
        let appointmentDetails = '';
        const desktopRow = document.querySelector(`.appointment-row[data-id="${appointmentId}"]`);
        if (desktopRow) {
            const cells = desktopRow.cells;
            appointmentDetails = `Appointment on ${cells[1].textContent} at ${cells[2].textContent} with ${cells[3].textContent}`;
        }
        
        document.getElementById('cancelAppointmentDetails').textContent = appointmentDetails;
        document.getElementById('cancelModal').classList.add('show');
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').classList.remove('show');
    }

    // SIMPLIFIED Cancel Appointment Function
    async function cancelAppointment() {
        const submitBtn = document.getElementById('confirmCancel');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = '<div class="loading"></div> Cancelling...';
        submitBtn.disabled = true;

        try {
            // Use URLSearchParams instead of FormData for better compatibility
            const params = new URLSearchParams();
            params.append('appointment_id', currentAppointmentId);
            params.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            params.append('action', 'cancel');

            const response = await fetch('', {
                method: 'POST',
                body: params,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                closeCancelModal();
                
                // Reload the page to see changes
                setTimeout(() => {
                    location.reload();
                }, 1500);
                
            } else {
                showToast(result.message, 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Request failed. Please try again.', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    function openRescheduleModal(appointmentId) {
        currentAppointmentId = appointmentId;
        document.getElementById('rescheduleAppointmentId').value = appointmentId;
        document.getElementById('rescheduleModal').classList.add('show');
        
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('new_date').min = today;
    }

    function closeRescheduleModal() {
        document.getElementById('rescheduleModal').classList.remove('show');
        document.getElementById('rescheduleForm').reset();
    }

    // Reschedule Form Handler
    document.getElementById('rescheduleForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('rescheduleSubmit');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<div class="loading"></div> Rescheduling...';
        submitBtn.disabled = true;

        try {
            const params = new URLSearchParams(new FormData(this));
            
            const response = await fetch('', {
                method: 'POST',
                body: params,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                closeRescheduleModal();
                
                setTimeout(() => {
                    location.reload();
                }, 1500);
                
            } else {
                showToast(result.message, 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Request failed. Please try again.', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    function changePage(page) {
        const url = new URL(window.location);
        url.searchParams.set('page', page);
        window.location.href = url.toString();
    }

    function showToast(message, type) {
        const toastContainer = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 5000);
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('resetFilters').addEventListener('click', function() {
            window.location.href = window.location.pathname;
        });

        document.getElementById('confirmCancel').addEventListener('click', cancelAppointment);

        window.addEventListener('click', function(e) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (e.target === modal) {
                    if (modal.id === 'detailsModal') closeDetailsModal();
                    else if (modal.id === 'rescheduleModal') closeRescheduleModal();
                    else if (modal.id === 'cancelModal') closeCancelModal();
                }
            });
        });

        const existingToast = document.getElementById('errorToast');
        if (existingToast) {
            setTimeout(() => {
                existingToast.classList.remove('show');
                setTimeout(() => {
                    if (existingToast.parentNode) {
                        existingToast.parentNode.removeChild(existingToast);
                    }
                }, 300);
            }, 5000);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('detailsModal').classList.contains('show')) {
                closeDetailsModal();
            } else if (document.getElementById('rescheduleModal').classList.contains('show')) {
                closeRescheduleModal();
            } else if (document.getElementById('cancelModal').classList.contains('show')) {
                closeCancelModal();
            }
        }
    });
</script>
</body>
</html>

<?php include 'includes/patient_footer.php'; ?>