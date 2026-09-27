<?php
session_start();

$page_title = "Hospital Panel";
include 'includes/hospital_header.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_id'])) {
    header("Location: hospital_login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'vaxify';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$covid_result = $_GET['covid_result'] ?? '';

// Build query
$query = "SELECT * FROM patients WHERE hospital_id = :hospital_id AND status = 'Approved'";
$params = [':hospital_id' => $_SESSION['hospital_id']];

if (!empty($search)) {
    $query .= " AND (name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($covid_result) && $covid_result !== 'all') {
    $query .= " AND covid_result = :covid_result";
    $params[':covid_result'] = $covid_result;
}

$query .= " ORDER BY created_at DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total patients
$count_query = "SELECT COUNT(*) as total FROM patients WHERE hospital_id = :hospital_id AND status = 'Approved'";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute([':hospital_id' => $_SESSION['hospital_id']]);
$total_patients = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Patients - COVID-19 Tests</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: var(--surface-color);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .header h1 {
            color: var(--heading-color);
            margin-bottom: 10px;
        }

        .stats-card {
            background: var(--accent-color);
            color: var(--contrast-color);
            padding: 15px;
            border-radius: 8px;
            display: inline-block;
            font-weight: bold;
        }

        .filters {
            background: var(--surface-color);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
        }

        .btn {
            background: var(--accent-color);
            color: var(--contrast-color);
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: var(--nav-hover-color);
        }

        .table-container {
            background: var(--surface-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .patients-table {
            width: 100%;
            border-collapse: collapse;
        }

        .patients-table th {
            background: var(--heading-color);
            color: var(--contrast-color);
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .patients-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .patients-table tr:hover {
            background: #f8f9fa;
        }

        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 14px;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-vaccinated {
            background: #d4edda;
            color: #155724;
        }

        .badge-partial {
            background: #fff3cd;
            color: #856404;
        }

        .badge-not-vaccinated {
            background: #f8d7da;
            color: #721c24;
        }

        .result-positive {
            color: #dc3545;
            font-weight: 600;
        }

        .result-negative {
            color: #28a745;
            font-weight: 600;
        }

        .result-inconclusive {
            color: #6c757d;
            font-weight: 600;
        }

        .view-btn {
            background: transparent;
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .view-btn:hover {
            background: var(--accent-color);
            color: var(--contrast-color);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: var(--heading-color);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .detail-label {
            font-weight: 600;
            color: var(--heading-color);
            min-width: 120px;
        }

        .detail-value {
            flex: 1;
        }

        /* Responsive Design Fixes */
@media (max-width: 768px) {
    .filters {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }

    .filters .search-box,
    .filters .filter-select,
    .filters .btn {
        width: 100%;
    }

    .table-view {
        display: none;
    }

    .card-view {
        display: block;
    }

    .patients-table th,
    .patients-table td {
        padding: 8px 6px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 10px;
    }

    .header h1 {
        font-size: 1.4rem;
    }

    .card-avatar {
        width: 40px;
        height: 40px;
        font-size: 16px;
    }

    .card-info {
        font-size: 14px;
    }

    .view-btn {
        font-size: 12px;
        padding: 6px 10px;
    }

    .filters {
        padding: 15px;
    }
}

/* Ensure card view is hidden on desktop */
.card-view {
    display: none;
}

/* Make modal responsive */
.modal-content {
    width: 95%;
    max-width: 450px;
    max-height: 90vh;
}

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Approved Patients for COVID-19 Tests</h1>
            <div class="stats-card">
                Total Approved Patients: <?php echo $total_patients; ?>
            </div>
        </div>

        <div class="filters">
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
            <select name="covid_result" class="filter-select" onchange="this.form.submit()" form="filterForm">
                <option value="all" <?php echo $covid_result === 'all' ? 'selected' : ''; ?>>All Results</option>
                <option value="Positive" <?php echo $covid_result === 'Positive' ? 'selected' : ''; ?>>Positive</option>
                <option value="Negative" <?php echo $covid_result === 'Negative' ? 'selected' : ''; ?>>Negative</option>
                <option value="Inconclusive" <?php echo $covid_result === 'Inconclusive' ? 'selected' : ''; ?>>Inconclusive</option>
            </select>
            <form id="filterForm" method="GET" style="display: none;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>">
            </form>
            <button type="button" class="btn" onclick="document.getElementById('filterForm').submit()">Apply Filters</button>
        </div>

        <div class="table-container">
            <!-- Desktop Table View -->
            <table class="patients-table table-view">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Test Type</th>
                        <th>Vaccination Status</th>
                        <th>COVID Result</th>
                        <th>Registration Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="patient-avatar">
                                    <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($patient['name']); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($patient['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                        <td><?php echo htmlspecialchars($patient['age']); ?></td>
                        <td><?php echo htmlspecialchars($patient['test_type']); ?></td>
                        <td>
                            <?php
                            $vaccination_status = $patient['vaccination_status'];
                            $badge_class = '';
                            if ($vaccination_status === 'Fully Vaccinated') {
                                $badge_class = 'badge-vaccinated';
                            } elseif ($vaccination_status === 'Partially Vaccinated') {
                                $badge_class = 'badge-partial';
                            } else {
                                $badge_class = 'badge-not-vaccinated';
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo htmlspecialchars($vaccination_status); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $result_class = '';
                            if ($patient['covid_result'] === 'Positive') {
                                $result_class = 'result-positive';
                            } elseif ($patient['covid_result'] === 'Negative') {
                                $result_class = 'result-negative';
                            } else {
                                $result_class = 'result-inconclusive';
                            }
                            ?>
                            <span class="<?php echo $result_class; ?>">
                                <?php echo htmlspecialchars($patient['covid_result']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></td>
                        <td>
                            <button class="view-btn" onclick="openModal(<?php echo htmlspecialchars(json_encode($patient)); ?>)">
                                View Details
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($patients)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            No approved patients found.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Mobile Card View -->
            <div class="card-view">
                <?php foreach ($patients as $patient): ?>
                <div class="patient-card">
                    <div class="card-header">
                        <div class="card-avatar">
                            <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                        </div>
                        <div class="card-info">
                            <div style="font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($patient['name']); ?></div>
                            <div style="font-size: 14px; color: #666;"><?php echo htmlspecialchars($patient['email']); ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-row">
                            <span class="card-label">Gender:</span>
                            <span><?php echo htmlspecialchars($patient['gender']); ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Age:</span>
                            <span><?php echo htmlspecialchars($patient['age']); ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Test Type:</span>
                            <span><?php echo htmlspecialchars($patient['test_type']); ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Vaccination:</span>
                            <span>
                                <?php
                                $vaccination_status = $patient['vaccination_status'];
                                $badge_class = '';
                                if ($vaccination_status === 'Fully Vaccinated') {
                                    $badge_class = 'badge-vaccinated';
                                } elseif ($vaccination_status === 'Partially Vaccinated') {
                                    $badge_class = 'badge-partial';
                                } else {
                                    $badge_class = 'badge-not-vaccinated';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($vaccination_status); ?>
                                </span>
                            </span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">COVID Result:</span>
                            <span class="<?php
                                if ($patient['covid_result'] === 'Positive') {
                                    echo 'result-positive';
                                } elseif ($patient['covid_result'] === 'Negative') {
                                    echo 'result-negative';
                                } else {
                                    echo 'result-inconclusive';
                                }
                            ?>">
                                <?php echo htmlspecialchars($patient['covid_result']); ?>
                            </span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Registered:</span>
                            <span><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></span>
                        </div>
                        <div style="text-align: center; margin-top: 15px;">
                            <button class="view-btn" onclick="openModal(<?php echo htmlspecialchars(json_encode($patient)); ?>)">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($patients)): ?>
                <div style="text-align: center; padding: 40px;">
                    No approved patients found.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="patientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Patient Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Patient details will be inserted here by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function openModal(patient) {
            const modalBody = document.getElementById('modalBody');
            
            // Format vaccination status badge
            let vaccinationBadge = '';
            if (patient.vaccination_status === 'Fully Vaccinated') {
                vaccinationBadge = '<span class="badge badge-vaccinated">Fully Vaccinated</span>';
            } else if (patient.vaccination_status === 'Partially Vaccinated') {
                vaccinationBadge = '<span class="badge badge-partial">Partially Vaccinated</span>';
            } else {
                vaccinationBadge = '<span class="badge badge-not-vaccinated">Not Vaccinated</span>';
            }

            // Format COVID result
            let covidResultClass = '';
            if (patient.covid_result === 'Positive') {
                covidResultClass = 'result-positive';
            } else if (patient.covid_result === 'Negative') {
                covidResultClass = 'result-negative';
            } else {
                covidResultClass = 'result-inconclusive';
            }

            modalBody.innerHTML = `
                <div class="detail-row">
                    <div class="detail-label">Name:</div>
                    <div class="detail-value">${patient.name}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value">${patient.email}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Phone:</div>
                    <div class="detail-value">${patient.phone || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Gender:</div>
                    <div class="detail-value">${patient.gender}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date of Birth:</div>
                    <div class="detail-value">${patient.dob ? new Date(patient.dob).toLocaleDateString() : 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Age:</div>
                    <div class="detail-value">${patient.age}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Address:</div>
                    <div class="detail-value">${patient.address || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Test Type:</div>
                    <div class="detail-value">${patient.test_type}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Vaccination Status:</div>
                    <div class="detail-value">${vaccinationBadge}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">COVID Result:</div>
                    <div class="detail-value"><span class="${covidResultClass}">${patient.covid_result}</span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">${patient.status}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Registration Date:</div>
                    <div class="detail-value">${new Date(patient.created_at).toLocaleDateString()}</div>
                </div>
            `;

            document.getElementById('patientModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('patientModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('patientModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

<?php include 'includes/hospital_footer.php'; ?>