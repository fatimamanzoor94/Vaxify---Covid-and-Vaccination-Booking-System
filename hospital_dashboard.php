<?php
session_start();
$page_title = "Hospital Panel";
include 'includes/hospital_header.php';

// ✅ Database connection start
$host = 'localhost';
$dbname = 'vaxify';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
// ✅ Database connection end

// ✅ Get current hospital ID from session
$hospital_id = $_SESSION['hospital_id'] ?? null;

if (!$hospital_id) {
    header("Location: hospital_login.php");
    exit();
}

// ✅ Fetch hospital data
$stmt = $pdo->prepare("SELECT * FROM hospitals WHERE id = ?");
$stmt->execute([$hospital_id]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Fetch statistics
$totalPatients = $pdo->prepare("SELECT COUNT(*) as count FROM patients WHERE hospital_id = ?");
$totalPatients->execute([$hospital_id]);
$totalPatients = $totalPatients->fetch(PDO::FETCH_ASSOC)['count'];

$totalAppointments = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE hospital_id = ?");
$totalAppointments->execute([$hospital_id]);
$totalAppointments = $totalAppointments->fetch(PDO::FETCH_ASSOC)['count'];

$pendingAppointments = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE hospital_id = ? AND status = 'pending'");
$pendingAppointments->execute([$hospital_id]);
$pendingAppointments = $pendingAppointments->fetch(PDO::FETCH_ASSOC)['count'];

$completedTests = $pdo->prepare("SELECT COUNT(*) as count FROM covid_reports WHERE hospital_id = ?");
$completedTests->execute([$hospital_id]);
$completedTests = $completedTests->fetch(PDO::FETCH_ASSOC)['count'];

// ✅ Monthly Appointments
$currentYear = date('Y');
$monthlyAppointments = [];
for ($i = 1; $i <= 12; $i++) {
    $month = date('F', mktime(0, 0, 0, $i, 1));
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE hospital_id = ? AND YEAR(appointment_date) = ? AND MONTH(appointment_date) = ?");
    $stmt->execute([$hospital_id, $currentYear, $i]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $monthlyAppointments[$month] = $count;
}

// ✅ Test Type Distribution
$testTypes = $pdo->prepare("SELECT test_type, COUNT(*) as count FROM appointments WHERE hospital_id = ? GROUP BY test_type");
$testTypes->execute([$hospital_id]);
$testTypeDistribution = [];
while ($row = $testTypes->fetch(PDO::FETCH_ASSOC)) {
    $testTypeDistribution[$row['test_type']] = $row['count'];
}

// ✅ Age Demographics
$ageGroups = [
    '0-18' => 0,
    '19-30' => 0,
    '31-45' => 0,
    '46-60' => 0,
    '60+' => 0
];

$patients = $pdo->prepare("SELECT age FROM patients WHERE hospital_id = ?");
$patients->execute([$hospital_id]);
while ($patient = $patients->fetch(PDO::FETCH_ASSOC)) {
    $age = $patient['age'];
    if ($age <= 18) $ageGroups['0-18']++;
    elseif ($age <= 30) $ageGroups['19-30']++;
    elseif ($age <= 45) $ageGroups['31-45']++;
    elseif ($age <= 60) $ageGroups['46-60']++;
    else $ageGroups['60+']++;
}

// ✅ Recent Appointments
$recentAppointments = $pdo->prepare("
    SELECT a.*, p.name as patient_name 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.id 
    WHERE a.hospital_id = ? 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$recentAppointments->execute([$hospital_id]);
$recentAppointments = $recentAppointments->fetchAll(PDO::FETCH_ASSOC);

// ✅ Additional Statistics - Final Version
$completedTests = $pdo->prepare("SELECT COUNT(*) FROM covid_reports WHERE hospital_id = ?");
$completedTests->execute([$hospital_id]);
$completedTests = (int) $completedTests->fetchColumn();

$positiveCases = $pdo->prepare("SELECT COUNT(*) FROM covid_reports WHERE hospital_id = ? AND LOWER(result) = 'positive'");
$positiveCases->execute([$hospital_id]);
$positiveCases = (int) $positiveCases->fetchColumn();

$todayAppointments = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE hospital_id = ? AND DATE(appointment_date) = CURDATE()");
$todayAppointments->execute([$hospital_id]);
$todayAppointments = (int) $todayAppointments->fetchColumn();

$vaccinatedPatients = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE hospital_id = ? AND LOWER(vaccination_status) = 'fully_vaccinated'");
$vaccinatedPatients->execute([$hospital_id]);
$vaccinatedPatients = (int) $vaccinatedPatients->fetchColumn();

$availableVaccines = $pdo->query("SELECT COUNT(*) FROM vaccines WHERE status = 'available'");
$availableVaccines = (int) $availableVaccines->fetchColumn();

// ✅ Vaccination Status Distribution
$vaccinationStatus = $pdo->prepare("SELECT vaccination_status, COUNT(*) as count FROM patients WHERE hospital_id = ? GROUP BY vaccination_status");
$vaccinationStatus->execute([$hospital_id]);
$vaccinationStatusData = [];
while ($row = $vaccinationStatus->fetch(PDO::FETCH_ASSOC)) {
    $vaccinationStatusData[$row['vaccination_status']] = $row['count'];
}

// ✅ COVID Results Summary
$covidResults = $pdo->prepare("SELECT result, COUNT(*) as count FROM covid_reports WHERE hospital_id = ? GROUP BY result");
$covidResults->execute([$hospital_id]);
$covidResultsData = [];
while ($row = $covidResults->fetch(PDO::FETCH_ASSOC)) {
    $covidResultsData[$row['result']] = $row['count'];
}

// ✅ FIX: Fallbacks in case hospital_id data is missing
if ($totalPatients == 0) {
    $totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
}
if (empty($vaccinationStatusData)) {
    $vaccinationStatus = $pdo->query("SELECT vaccination_status, COUNT(*) as count FROM patients GROUP BY vaccination_status");
    while ($row = $vaccinationStatus->fetch(PDO::FETCH_ASSOC)) {
        $vaccinationStatusData[$row['vaccination_status']] = $row['count'];
    }
}
if (empty($covidResultsData)) {
    $covidResults = $pdo->query("SELECT result, COUNT(*) as count FROM covid_reports GROUP BY result");
    while ($row = $covidResults->fetch(PDO::FETCH_ASSOC)) {
        $covidResultsData[$row['result']] = $row['count'];
    }
}

// ✅ Recent Activities
$activities = $pdo->prepare("
    (SELECT 
        'appointment' as type,
        CONCAT('New appointment for ', p.name) as title,
        a.created_at as timestamp,
        a.appointment_date as activity_date
     FROM appointments a
     JOIN patients p ON a.patient_id = p.id
     WHERE a.hospital_id = ?
    )
    UNION
    (SELECT 
        'covid_report' as type,
        CONCAT('COVID Test: ', result) as title,
        created_at as timestamp,
        test_date as activity_date
     FROM covid_reports 
     WHERE hospital_id = ?
    )
    UNION
    (SELECT 
        'vaccination' as type,
        'Vaccination status updated' as title,
        NOW() as timestamp,
        NOW() as activity_date
     FROM patients 
     WHERE hospital_id = ?
    )
    ORDER BY activity_date DESC 
    LIMIT 5
");
$activities->execute([$hospital_id, $hospital_id, $hospital_id]);
$activities = $activities->fetchAll(PDO::FETCH_ASSOC);

// ✅ Helper function
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = [
        'y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day',
        'h' => 'hour', 'i' => 'minute', 's' => 'second',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        else unset($string[$k]);
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard - Vaxify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .header h1 {
            color: var(--heading-color);
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        /* Cards Section */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: var(--surface-color);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: var(--heading-color);
            margin-bottom: 10px;
        }

        .card p:first-of-type {
            font-size: 28px;
            font-weight: bold;
            color: var(--accent-color);
            margin-bottom: 5px;
        }

        .card p:last-of-type {
            font-size: 14px;
            color: var(--nav-color);
        }

        /* Charts Section */
        .chart-container {
            background: var(--surface-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .chart-container h4 {
            color: var(--heading-color);
            margin-bottom: 15px;
            font-size: 18px;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: var(--surface-color);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-item h5 {
            color: var(--nav-color);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-item p {
            font-size: 20px;
            font-weight: bold;
            color: var(--accent-color);
        }

        /* Right Column Styles */
        .right-column .panel {
            background: var(--surface-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .panel h4 {
            color: var(--heading-color);
            margin-bottom: 15px;
            font-size: 18px;
        }

        .up-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .up-item:last-child {
            border-bottom: none;
        }

        .up-item b {
            color: var(--default-color);
            display: block;
            margin-bottom: 5px;
        }

        .up-item div:first-child {
            flex: 1;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        .export {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s ease;
        }

        .export:hover {
            background: #038a9e;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            
            .main-content {
                margin-left: 70px;
            }
            
            .cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
        width: 100%;
        box-sizing: border-box;
    }

    </style>
</head>
<body>
<div class="container">
            <div class="dashboard-grid">
                <div class="left-column">
                    <!-- Welcome Section -->
                    <div>
                        <h3 style="font-size:18px;color:var(--heading-color)">Welcome to Hospital Dashboard</h3>
                        <div style="color:var(--nav-color);font-size:14px;margin-top:4px">Overview of hospital management and operations</div>
                    </div>
                    
                    <!-- Main Statistics Cards -->
                    <div class="cards" style="margin-top:12px">
                        <div class="card">
                            <h3>
                                <i class="fa-solid fa-user-injured" style="color: #0d9769ff;"></i>
                                Total Patients
                            </h3>
                            <p><?php echo $totalPatients; ?></p>
                            <p>Registered patients</p>
                        </div>
                        <div class="card">
                            <h3>
                                <i class="fa-solid fa-calendar-check" style="color: #f929d6ff;"></i>
                                Total Appointments
                            </h3>
                            <p><?php echo $totalAppointments; ?></p>
                            <p>All time appointments</p>
                        </div>
                        <div class="card">
                            <h3>
                                <i class="fa-solid fa-syringe" style="color: #b10000ff;"></i>
                                Available Vaccines
                            </h3>
                            <p><?php echo $availableVaccines; ?></p>
                            <p>Vaccines in stock</p>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="chart-container">
                        <h4>Monthly Appointments Overview - <?php echo date('Y'); ?></h4>
                        <div class="chart-item">
                            <canvas id="appointmentsChart" style="width:100%; height:250px;"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-grid">
                        <div class="chart-container">
                            <h4>Vaccination Status Distribution</h4>
                            <div class="chart-item">
                                <canvas id="vaccinationChart" style="width:100%; height:250px;"></canvas>
                            </div>
                        </div>
                        <div class="chart-container">
                            <h4>COVID Test Results Summary</h4>
                            <div class="chart-item">
                                <canvas id="covidChart" style="width:100%; height:250px;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Statistics -->
                    <div class="stats-grid">
                        <div class="stat-item">
                            <h5>Total Completed Tests</h5>
                            <p><?php echo $completedTests; ?></p>
                        </div>
                        <div class="stat-item">
                            <h5>Positive Cases</h5>
                            <p><?php echo $positiveCases; ?></p>
                        </div>
                        <div class="stat-item">
                            <h5>Today's Appointments</h5>
                            <p><?php echo $todayAppointments; ?></p>
                        </div>
                        <div class="stat-item">
                            <h5>Vaccinated Patients</h5>
                            <p><?php echo $vaccinatedPatients; ?></p>
                        </div>
                    </div>
                </div>
                
                <aside class="right-column">
                    
                    <!-- Recent Activities Panel -->
                    <div class="panel" style="margin-top: 16px;">
                        <h4>Recent Activities</h4>
                        <?php if (!empty($activities)): ?>
                            <?php foreach($activities as $activity): ?>
                            <div class="up-item">
                                <div>
                                    <b><?php echo $activity['title']; ?></b>
                                    <div style="color:var(--nav-color);font-size:13px">
                                        <?php echo time_elapsed_string($activity['timestamp']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="up-item">
                                <div>
                                    <b>No recent activities</b>
                                    <div style="color:var(--nav-color);font-size:13px">
                                        There are no activities to display at the moment.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Appointments Panel -->
                    <div class="panel" style="margin-top: 16px;">
                        <h4>Recent Appointments</h4>
                        <?php if (!empty($recentAppointments)): ?>
                            <?php foreach($recentAppointments as $appointment): ?>
                            <div class="up-item">
                                <div>
                                    <b><?php echo $appointment['patient_name']; ?></b>
                                    <div style="color:var(--nav-color);font-size:13px">
                                        <?php echo $appointment['test_type']; ?> - 
                                        <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?> at 
                                        <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                    <div style="color:var(--nav-color);font-size:12px;margin-top:4px">
                                        Status: <span style="color:<?php 
                                            if($appointment['status'] == 'pending') echo '#f59e0b';
                                            elseif($appointment['status'] == 'approved') echo '#10b981';
                                            else echo '#ef4444';
                                        ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="up-item">
                                <div>
                                    <b>No recent appointments</b>
                                    <div style="color:var(--nav-color);font-size:13px">
                                        There are no appointments to display at the moment.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </div>
</div>

    <script>
    // Initialize Charts when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Monthly Appointments Chart - Line Chart
        const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
        const appointmentsChart = new Chart(appointmentsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($monthlyAppointments)); ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode(array_values($monthlyAppointments)); ?>,
                    backgroundColor: 'rgba(4, 158, 187, 0.2)',
                    borderColor: 'rgba(4, 158, 187, 1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgba(4, 158, 187, 1)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Vaccination Status Distribution Chart - Doughnut chart
        const vaccinationCtx = document.getElementById('vaccinationChart').getContext('2d');
        const vaccinationChart = new Chart(vaccinationCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($vaccinationStatusData)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($vaccinationStatusData)); ?>,
                    backgroundColor: [
                        'rgba(4, 158, 187, 0.8)',      // Teal
                        'rgba(24, 68, 76, 0.8)',       // Dark Teal
                        'rgba(73, 98, 104, 0.8)',      // Gray Blue
                        'rgba(150, 50, 150, 0.8)',     // Purple
                        'rgba(50, 150, 150, 0.8)'      // Light Teal
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverBackgroundColor: [
                        'rgba(4, 158, 187, 1)',
                        'rgba(24, 68, 76, 1)',
                        'rgba(73, 98, 104, 1)',
                        'rgba(150, 50, 150, 1)',
                        'rgba(50, 150, 150, 1)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: window.innerWidth < 768 ? 10 : 12
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });

        // COVID Test Results Summary Chart - Bar chart
        const covidCtx = document.getElementById('covidChart').getContext('2d');
        const covidChart = new Chart(covidCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($covidResultsData)); ?>,
                datasets: [{
                    label: 'Tests',
                    data: <?php echo json_encode(array_values($covidResultsData)); ?>,
                    backgroundColor: [
                        'rgba(4, 158, 187, 0.7)',      // Teal
                        'rgba(24, 68, 76, 0.7)',       // Dark Teal
                        'rgba(73, 98, 104, 0.7)',      // Gray Blue
                        'rgba(150, 50, 150, 0.7)'      // Purple
                    ],
                    borderColor: [
                        'rgba(4, 158, 187, 1)',
                        'rgba(24, 68, 76, 1)',
                        'rgba(73, 98, 104, 1)',
                        'rgba(150, 50, 150, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>

<?php include 'includes/hospital_footer.php'; ?>