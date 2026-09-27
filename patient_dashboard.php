<?php
session_start();

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];

$host = "localhost";
$dbname = "vaxify";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ Fetch patient info
    $patient_stmt = $pdo->prepare("SELECT name, gender, image_path, vaccination_status FROM patients WHERE id = ?");
    $patient_stmt->execute([$patient_id]);
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get appointment data
$appointment_stmt = $pdo->prepare("
    SELECT COUNT(*) as total_upcoming, 
           MAX(appointment_date) as latest_date 
    FROM appointments 
    WHERE patient_id = ? AND (status = 'scheduled' OR appointment_status = 'approved')
");
$appointment_stmt->execute([$patient_id]);
$appointment_data = $appointment_stmt->fetch(PDO::FETCH_ASSOC);

// Get COVID test data
$covid_stmt = $pdo->prepare("
    SELECT COUNT(*) as total_tests, 
           result, 
           MAX(test_date) as latest_test_date 
    FROM covid_reports 
    WHERE patient_id = ?
");
$covid_stmt->execute([$patient_id]);
$covid_data = $covid_stmt->fetch(PDO::FETCH_ASSOC);

// Get vaccine distribution data
$vaccine_stmt = $pdo->prepare("
    SELECT name AS vaccine_name, COUNT(*) AS count
    FROM vaccines
    GROUP BY name
");
$vaccine_stmt->execute();
$vaccine_data = $vaccine_stmt->fetchAll(PDO::FETCH_ASSOC);


// Get monthly appointment counts for current year
$monthly_appointments_stmt = $pdo->prepare("
    SELECT MONTH(appointment_date) as month, COUNT(*) as count 
    FROM appointments 
    WHERE patient_id = ? AND YEAR(appointment_date) = YEAR(CURDATE())
    GROUP BY MONTH(appointment_date)
");
$monthly_appointments_stmt->execute([$patient_id]);
$monthly_appointments = $monthly_appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get vaccination status distribution
$vaccination_status_stmt = $pdo->prepare("
    SELECT vaccination_status, COUNT(*) as count 
    FROM patients 
    GROUP BY vaccination_status
");
$vaccination_status_stmt->execute();
$vaccination_status_data = $vaccination_status_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activities
$activities_stmt = $pdo->prepare("
    (SELECT 
        'appointment' as type,
        CONCAT('Appointment ', appointment_status) as title,
        appointment_date as timestamp,
        hospital_id,
        appointment_date as activity_date
     FROM appointments 
     WHERE patient_id = ? AND (appointment_status = 'approved' OR appointment_status = 'rejected')
    )
    UNION
    (SELECT 
        'covid_report' as type,
        CONCAT('COVID Test: ', result) as title,
        test_date as timestamp,
        hospital_id,
        test_date as activity_date
     FROM covid_reports 
     WHERE patient_id = ?
    )
    UNION
    (SELECT 
        'hospital_request' as type,
        CONCAT('Request: ', request_type) as title,
        request_date as timestamp,
        hospital_id,
        request_date as activity_date
     FROM hospital_request 
     WHERE patient_id = ?
    )
    ORDER BY activity_date DESC 
    LIMIT 5
");
$activities_stmt->execute([$patient_id, $patient_id, $patient_id]);
$activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get hospital names for activities
$hospital_ids = [];
foreach ($activities as $activity) {
    if ($activity['hospital_id']) {
        $hospital_ids[] = $activity['hospital_id'];
    }
}

$hospital_names = [];
if (!empty($hospital_ids)) {
    $placeholders = str_repeat('?,', count($hospital_ids) - 1) . '?';
$hospital_stmt = $pdo->prepare("SELECT id, name FROM hospitals WHERE id IN ($placeholders)");
$hospital_stmt->execute($hospital_ids);
$hospital_results = $hospital_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($hospital_results as $hospital) {
    $hospital_names[$hospital['id']] = $hospital['name'];
}

}

$page_title = "Patient Panel";
include 'includes/patient_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Vaxify</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
:root {
    --background-color: #ffffff;
    --heading-color: #18444c;
    --accent-color: #049ebb;
    --nav-color: #496268;
    --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --border-radius: 10px;
}

/* General Layout */

/* .dashboard-container {
    display: flex;
    flex-direction: row;
    min-height: 100vh;
    background: var(--background-color);
} */

.main-content {
    flex: 1;
    padding: 20px 30px;
    transition: all 0.3s ease;
}

/* User Info */
.user-info {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.user-info img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    margin-right: 12px;
    border: 2px solid var(--accent-color);
}

.user-details h3 {
    font-size: 1rem;
    margin: 0;
}

.user-details p {
    font-size: 0.8rem;
    color: var(--nav-color);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: var(--background-color);
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--card-shadow);
    border-left: 5px solid var(--accent-color);
    transition: 0.3s ease;
}

.stat-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 12px rgba(0,0,0,0.12);
}

.stat-box .heading {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    gap: 10px;
}

.stat-box i {
    font-size: 1.5rem;
    color: var(--accent-color);
}

.stat-box h4 {
    margin: 0;
    font-size: 1rem;
    color: var(--heading-color);
}

.stat-box h3 {
    font-size: 2rem;
    color: var(--accent-color);
    margin: 5px 0;
    font-weight: 700;
}

.stat-box p {
    margin: 0;
    font-size: 0.85rem;
    color: #777;
}

/* Charts Section */
.charts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.chart-card {
    background: var(--background-color);
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--card-shadow);
}

.chart-card h3 {
    font-size: 1.1rem;
    margin-bottom: 15px;
    color: var(--heading-color);
}

.chart-container {
    position: relative;
    height: 280px;
    width: 100%;
}

/* Activities Section */
.activities-card {
    background: var(--background-color);
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
}

.activities-card h3 {
    color: var(--heading-color);
    font-size: 1.2rem;
    margin-bottom: 15px;
}

.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
    flex-wrap: wrap;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.appointment-activity { background: var(--accent-color); }
.covid-activity { background: #4CAF50; }
.request-activity { background: #049ebb; }

.activity-content {
    flex: 1;
    min-width: 200px;
}

.activity-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.activity-time,
.activity-hospital {
    font-size: 0.8rem;
}

.activity-time { color: var(--nav-color); }
.activity-hospital { color: var(--accent-color); }

/* ================= RESPONSIVENESS ================= */

/* Large tablets (1024px) */
@media (max-width: 1024px) {
    .main-content {
        margin: 0;
        padding: 20px;
    }
}

/* Tablets (768px) */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .chart-container {
        height: 230px;
    }

    .activity-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .activity-icon {
        margin-bottom: 10px;
    }

    .chart-card, .activities-card {
        padding: 15px;
    }
}

/* Mobile (480px) */
@media (max-width: 480px) {
    .main-content {
        padding: 15px;
    }

    .stat-box h3 {
        font-size: 1.5rem;
    }

    .stat-box h4 {
        font-size: 0.9rem;
    }

    .chart-container {
        height: 200px;
    }

    .activity-content {
        width: 100%;
    }

    .user-info img {
        width: 35px;
        height: 35px;
    }

    .user-details h3 {
        font-size: 0.9rem;
    }

    .user-details p {
        font-size: 0.75rem;
    }
}

/* Small phones (360px and below) */
@media (max-width: 360px) {
    .stat-box {
        padding: 15px;
    }
    .chart-container {
        height: 180px;
    }
    .chart-card h3 {
        font-size: 1rem;
    }
}

/* ======= Dashboard Container Fix ======= */
.container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

</style>
</head>
<body>
        <!-- Main Content -->
        <div class="container">
        <div class="main-content">

        <div>
            <h3 style="font-size:1.7rem;color:#0b1720">Welcome to Patient Dashboard</h3>
            <div style="color:#6b7280;font-size:16px;margin-top:4px">Manage Your Appointments with Ease</div>
        </div>

            <button class="menu-toggle" id="menuToggle"></button>

         <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-box pending">
                <div class="details">
                    <div class="heading">
                        <i class="bi bi-calendar-event"></i>
                        <h4>Upcoming Appointments</h4>
                    </div>
                    <h3><?php echo $appointment_data['total_upcoming'] ?: '0'; ?></h3>
                    <p>Hospital visits scheduled</p>
                </div>
            </div>

            <div class="stat-box active">
                <div class="details">
                    <div class="heading">
                        <i class="bi bi-thermometer-half"></i>
                        <h4>COVID Tests Taken</h4>
                    </div>
                    <h3><?php echo $covid_data['total_tests'] ?: '0'; ?></h3>
                    <p>Latest: <?php echo $covid_data['latest_test_date'] ? date('M j, Y', strtotime($covid_data['latest_test_date'])) : 'N/A'; ?></p>
                </div>
            </div>

            <div class="stat-box vaccines">
                <div class="details">
                    <div class="heading">
                        <i class="fa-solid fa-syringe"></i>
                        <h4>Vaccination Status</h4>
                    </div>
                    <h3><?php echo ucfirst(htmlspecialchars($patient['vaccination_status'])); ?></h3>
                    <p>Updated recently</p>
                </div>
            </div>
        </div>


            <!-- Charts Section -->
            <div class="charts-container">
                <div class="chart-card">
                    <h3>Monthly Appointments (<?php echo date('Y'); ?>)</h3>
                    <div class="chart-container">
                        <canvas id="appointmentsChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Vaccination Status Distribution</h3>
                    <div class="chart-container">
                        <canvas id="vaccinationChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Vaccine Distribution</h3>
                    <div class="chart-container">
                        <canvas id="vaccineChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="activities-card">
                <h3>Recent Activities</h3>
                <ul class="activity-list">
                    <?php if (empty($activities)): ?>
                        <li class="activity-item">
                            <div class="activity-content">
                                <div class="activity-title">No recent activities</div>
                            </div>
                        </li>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <li class="activity-item">
                                <div class="activity-icon 
                                    <?php 
                                    if ($activity['type'] == 'appointment') echo 'appointment-activity';
                                    elseif ($activity['type'] == 'covid_report') echo 'covid-activity';
                                    else echo 'request-activity';
                                    ?>">
                                    <?php 
                                    if ($activity['type'] == 'appointment') echo '📅';
                                    elseif ($activity['type'] == 'covid_report') echo '🩺';
                                    else echo '🏥';
                                    ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                    <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?></div>
                                    <?php if ($activity['hospital_id'] && isset($hospital_names[$activity['hospital_id']])): ?>
                                        <div class="activity-hospital"><?php echo htmlspecialchars($hospital_names[$activity['hospital_id']]); ?></div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Prepare data for charts
        const monthlyAppointmentsData = [
            <?php
            // Create an array with zeros for all months
            $monthlyCounts = array_fill(1, 12, 0);
            
            // Fill in the actual counts
            foreach ($monthly_appointments as $appointment) {
                $monthlyCounts[$appointment['month']] = $appointment['count'];
            }
            
            // Output the counts
            echo implode(', ', $monthlyCounts);
            ?>
        ];

        const vaccinationStatusData = {
            labels: [<?php 
                $labels = [];
                $counts = [];
                foreach ($vaccination_status_data as $status) {
                    $labels[] = "'" . ucfirst(htmlspecialchars($status['vaccination_status'])) . "'";
                    $counts[] = $status['count'];
                }
                echo implode(', ', $labels);
            ?>],
            datasets: [{
                data: [<?php echo implode(', ', $counts); ?>],
                backgroundColor: [
                    '#ffcd27ff', '#4CAF50', '#FF9800', '#ff6a25ff', '#E91E63'
                ]
            }]
        };

        const vaccineDistributionData = {
            labels: [<?php 
                $labels = [];
                $counts = [];
                foreach ($vaccine_data as $vaccine) {
                    $labels[] = "'" . htmlspecialchars($vaccine['vaccine_name']) . "'";
                    $counts[] = $vaccine['count'];
                }
                echo implode(', ', $labels);
            ?>],
            datasets: [{
                data: [<?php echo implode(', ', $counts); ?>],
                backgroundColor: [
                    '#fc38b7ff', '#4CAF50', '#FF9800', '#9C27B0', '#E91E63', '#673AB7'
                ]
            }]
        };

        // Initialize charts when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Monthly Appointments Chart (Bar)
            const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
            new Chart(appointmentsCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Appointments',
                        data: monthlyAppointmentsData,
                        backgroundColor: '#048431ff',
                        borderColor: '#037a8f',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Vaccination Status Chart (Doughnut)
            const vaccinationCtx = document.getElementById('vaccinationChart').getContext('2d');
            new Chart(vaccinationCtx, {
                type: 'doughnut',
                data: vaccinationStatusData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Vaccine Distribution Chart (Pie)
            const vaccineCtx = document.getElementById('vaccineChart').getContext('2d');
            new Chart(vaccineCtx, {
                type: 'pie',
                data: vaccineDistributionData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>

<?php include 'includes/patient_footer.php'; ?>