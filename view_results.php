<?php
session_start();

$patient_id = $_SESSION['patient_id'];
$page_title = "Patient Panel";
include 'includes/patient_header.php';

// Set timezone
date_default_timezone_set('Asia/Karachi');

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=vaxify;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize filter variables
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$result_filter = $_GET['result_filter'] ?? 'All';
$hospital_name = $_GET['hospital_name'] ?? '';

// Build query with filters for covid_reports table
$query = "SELECT cr.*, h.name as hospital_name 
          FROM covid_reports cr 
          JOIN hospitals h ON cr.hospital_id = h.id 
          WHERE cr.patient_id = :patient_id";

$params = [':patient_id' => $_SESSION['patient_id']];

if (!empty($start_date)) {
    $query .= " AND cr.test_date >= :start_date";
    $params[':start_date'] = $start_date;
}

if (!empty($end_date)) {
    $query .= " AND cr.test_date <= :end_date";
    $params[':end_date'] = $end_date;
}

if ($result_filter !== 'All') {
    $query .= " AND cr.result = :result";
    $params[':result'] = $result_filter;
}

if (!empty($hospital_name)) {
    $query .= " AND h.name LIKE :hospital_name";
    $params[':hospital_name'] = "%$hospital_name%";
}

$query .= " ORDER BY cr.test_date DESC, cr.created_at DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$covid_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Also fetch appointment results for comprehensive view
$appointment_query = "SELECT a.*, h.name as hospital_name 
                     FROM appointments a 
                     JOIN hospitals h ON a.hospital_id = h.id 
                     WHERE a.patient_id = :patient_id 
                     AND a.test_result IS NOT NULL 
                     AND a.test_result != 'pending' 
                     ORDER BY a.appointment_date DESC";

$appointment_stmt = $pdo->prepare($appointment_query);
$appointment_stmt->execute([':patient_id' => $_SESSION['patient_id']]);
$appointment_results = $appointment_stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine both results for comprehensive view
$all_results = [];

// Process covid_reports results
foreach ($covid_results as $result) {
    $all_results[] = [
        'type' => 'covid_report',
        'hospital_name' => $result['hospital_name'],
        'test_date' => $result['test_date'],
        'test_type' => $result['test_type'],
        'result' => $result['result'],
        'severity' => $result['severity'],
        'doctor_name' => $result['doctor_name'],
        'notes' => $result['notes'],
        'created_at' => $result['created_at']
    ];
}

// Process appointment results
foreach ($appointment_results as $result) {
    $all_results[] = [
        'type' => 'appointment',
        'hospital_name' => $result['hospital_name'],
        'test_date' => $result['appointment_date'],
        'test_type' => $result['test_type'],
        'result' => ucfirst($result['test_result']), // Convert to match covid_reports format
        'severity' => 'N/A', // Appointments table doesn't have severity
        'doctor_name' => 'N/A', // Appointments table doesn't have doctor_name
        'notes' => 'Test conducted via appointment',
        'created_at' => $result['created_at']
    ];
}

// Sort all results by date (newest first)
usort($all_results, function($a, $b) {
    return strtotime($b['test_date']) - strtotime($a['test_date']);
});

// Apply filters to combined results
$filtered_results = $all_results;

if (!empty($start_date)) {
    $filtered_results = array_filter($filtered_results, function($result) use ($start_date) {
        return $result['test_date'] >= $start_date;
    });
}

if (!empty($end_date)) {
    $filtered_results = array_filter($filtered_results, function($result) use ($end_date) {
        return $result['test_date'] <= $end_date;
    });
}

if ($result_filter !== 'All') {
    $filtered_results = array_filter($filtered_results, function($result) use ($result_filter) {
        return $result['result'] === $result_filter;
    });
}

if (!empty($hospital_name)) {
    $filtered_results = array_filter($filtered_results, function($result) use ($hospital_name) {
        return stripos($result['hospital_name'], $hospital_name) !== false;
    });
}

// Re-index array after filtering
$filtered_results = array_values($filtered_results);

// Function to get suggestion based on result
function getSuggestion($result) {
    switch ($result) {
        case 'Positive':
        case 'positive':
            return "Consult your doctor and stay isolated. Follow quarantine guidelines.";
        case 'Negative':
        case 'negative':
            return "You can take vaccination from registered hospitals. Continue following safety measures.";
        case 'Inconclusive':
        case 'inconclusive':
            return "Retake test after 48 hours. Consult with healthcare provider.";
        default:
            return "Please consult with your healthcare provider for guidance.";
    }
}

// Function to get badge class based on result
function getBadgeClass($result) {
    $result = strtolower($result);
    switch ($result) {
        case 'positive':
            return "badge-positive";
        case 'negative':
            return "badge-negative";
        case 'inconclusive':
            return "badge-inconclusive";
        default:
            return "badge-inconclusive";
    }
}

// Get patient information for display
$patient_query = "SELECT name, email, phone, vaccination_status, covid_result 
                  FROM patients 
                  WHERE id = :patient_id";
$patient_stmt = $pdo->prepare($patient_query);
$patient_stmt->execute([':patient_id' => $_SESSION['patient_id']]);
$patient_info = $patient_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View COVID-19 Test Results - Vaxify</title>
    <style>
        :root { 
            --background-color: #ffffff;
            --default-color: #2c3031;
            --heading-color: #18444c; 
            --accent-color: #049ebb; 
            --surface-color: #ffffff; 
            --contrast-color: #ffffff; 
            --nav-color: #496268;  
            --nav-hover-color: #049ebb; 
            --nav-mobile-background-color: #ffffff; 
            --nav-dropdown-background-color: #ffffff; 
            --nav-dropdown-color: #496268; 
            --nav-dropdown-hover-color: #049ebb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--default-color);
            background-color: #f5f7fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: left;
            margin-bottom: 40px;
            padding: 30px 0;
            color: black;
            border-radius: 10px;
        }

        h1 {
            color: var(--heading-color);
            font-size: 1.7rem;
            margin-bottom: 40px;
            font-weight: 700;
        }

        .filters-section {
            background-color: var(--surface-color);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--heading-color);
        }

        .filter-group input,
        .filter-group select {
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(4, 158, 187, 0.1);
        }

        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px; /* Better touch target */
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: var(--contrast-color);
        }

        .btn-primary:hover {
            background-color: var(--heading-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(4, 158, 187, 0.3);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: var(--contrast-color);
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .results-section {
            background-color: var(--surface-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        thead {
            background-color: var(--heading-color);
            color: var(--contrast-color);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        th {
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            transition: background-color 0.3s ease;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-positive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-negative {
            background-color: #d1edff;
            color: #155724;
        }

        .badge-inconclusive {
            background-color: #fff3cd;
            color: #856404;
        }

        .no-results {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .suggestion {
            font-style: italic;
            color: var(--accent-color);
            font-weight: 500;
        }

        .result-source {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Mobile Cards View - Hidden by default */
        .mobile-cards {
            display: none;
        }

        .result-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--accent-color);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-title {
            font-weight: 600;
            color: var(--heading-color);
            font-size: 1.1rem;
            margin: 0;
        }

        .card-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-label {
            font-weight: 600;
            color: var(--heading-color);
        }

        .detail-value {
            text-align: right;
        }

        .suggestion-card {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8fdff;
            border-radius: 8px;
            border-left: 4px solid var(--accent-color);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                padding: 15px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 0.9rem;
            }
            
            /* Switch to cards view on mobile */
            .table-container {
                display: none;
            }
            
            .mobile-cards {
                display: block;
                padding: 15px;
            }
            
            .result-card {
                padding: 15px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-title {
                font-size: 1rem;
            }
            
            .detail-row {
                flex-direction: column;
                border-bottom: none;
                padding: 5px 0;
            }
            
            .detail-label {
                margin-bottom: 3px;
            }
            
            .detail-value {
                text-align: left;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            .filters-section {
                padding: 20px 15px;
            }
            
            h1 {
                font-size: 1.3rem;
                margin-bottom: 25px;
            }
            
            .filter-group input,
            .filter-group select {
                padding: 10px 12px;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 0.95rem;
            }
            
            .mobile-cards {
                padding: 10px;
            }
            
            .result-card {
                padding: 12px;
            }
            
            .suggestion-card {
                padding: 12px;
            }
        }

        @media print {
            .filters-section, .button-group, .footer, .patient-summary {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="filters-section">
            <form method="GET" action="">
                <h1>View COVID-19 Test Results</h1>
                
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="start_date">From Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="end_date">To Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="result_filter">Result</label>
                        <select id="result_filter" name="result_filter">
                            <option value="All" <?= $result_filter === 'All' ? 'selected' : '' ?>>All Results</option>
                            <option value="Positive" <?= $result_filter === 'Positive' ? 'selected' : '' ?>>Positive</option>
                            <option value="Negative" <?= $result_filter === 'Negative' ? 'selected' : '' ?>>Negative</option>
                            <option value="Inconclusive" <?= $result_filter === 'Inconclusive' ? 'selected' : '' ?>>Inconclusive</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="hospital_name">Hospital Name</label>
                        <input type="text" id="hospital_name" name="hospital_name" 
                               placeholder="Enter hospital name..." value="<?= htmlspecialchars($hospital_name) ?>">
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <span>Apply Filters</span>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                        <span>Reset Filters</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="results-section">
            <?php if (count($filtered_results) > 0): ?>
                <!-- Desktop Table View -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Hospital</th>
                                <th>Test Date</th>
                                <th>Test Type</th>
                                <th>Result</th>
                                <th>Severity</th>
                                <th>Doctor</th>
                                <th>Notes</th>
                                <th>Suggestion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered_results as $row): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($row['hospital_name']) ?>
                                        <div class="result-source">
                                            Source: <?= $row['type'] === 'covid_report' ? 'Detailed Report' : 'Appointment' ?>
                                        </div>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($row['test_date'])) ?></td>
                                    <td><?= htmlspecialchars($row['test_type']) ?></td>
                                    <td>
                                        <span class="badge <?= getBadgeClass($row['result']) ?>">
                                            <?= htmlspecialchars($row['result']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['severity']) ?></td>
                                    <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                                    <td><?= htmlspecialchars($row['notes'] ?? 'N/A') ?></td>
                                    <td class="suggestion"><?= getSuggestion($row['result']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Cards View -->
                <div class="mobile-cards">
                    <?php foreach ($filtered_results as $row): ?>
                        <div class="result-card">
                            <div class="card-header">
                                <h3 class="card-title"><?= htmlspecialchars($row['hospital_name']) ?></h3>
                                <span class="badge <?= getBadgeClass($row['result']) ?>">
                                    <?= htmlspecialchars($row['result']) ?>
                                </span>
                            </div>
                            
                            <div class="card-details">
                                <div class="detail-row">
                                    <span class="detail-label">Test Date:</span>
                                    <span class="detail-value"><?= date('M j, Y', strtotime($row['test_date'])) ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Test Type:</span>
                                    <span class="detail-value"><?= htmlspecialchars($row['test_type']) ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Severity:</span>
                                    <span class="detail-value"><?= htmlspecialchars($row['severity']) ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Doctor:</span>
                                    <span class="detail-value"><?= htmlspecialchars($row['doctor_name']) ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Notes:</span>
                                    <span class="detail-value"><?= htmlspecialchars($row['notes'] ?? 'N/A') ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Source:</span>
                                    <span class="detail-value"><?= $row['type'] === 'covid_report' ? 'Detailed Report' : 'Appointment' ?></span>
                                </div>
                            </div>
                            
                            <div class="suggestion-card">
                                <strong>Suggestion:</strong> <?= getSuggestion($row['result']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <div>📋</div>
                    <h3>No test results found</h3>
                    <p>No COVID-19 test reports match your current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function resetFilters() {
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('result_filter').value = 'All';
            document.getElementById('hospital_name').value = '';
            document.querySelector('form').submit();
        }

        function printResults() {
            window.print();
        }

        // Add today's date as max for date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').max = today;
            document.getElementById('end_date').max = today;
        });
    </script>
</body>
</html>

<?php include 'includes/patient_footer.php'; ?>