<?php
session_start();
include("includes/db_connect.php");

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Check if user is logged in and is Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Section titles array - only "Admin Panel" for all sections
$sectionTitles = [
    'dashboard' => 'Admin Panel',
    'patients' => 'Admin Panel', 
    'reports' => 'Admin Panel',
    'vaccines' => 'Admin Panel',
    'approval' => 'Admin Panel',
    'hospitals' => 'Admin Panel',
    'bookings' => 'Admin Panel',
    'admins' => 'Admin Panel'
];

// Database queries
$section = $_GET['section'] ?? 'dashboard';
$currentSectionTitle = $sectionTitles[$section] ?? 'Admin Panel';

$totalPatients = $totalHospitals = $totalBookings = 0;

if ($section === 'dashboard') {
    // Get total counts
    $totalPatients = $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'];
    $totalHospitals = $conn->query("SELECT COUNT(*) AS c FROM hospitals")->fetch_assoc()['c'];
    $totalBookings = $conn->query("SELECT COUNT(*) AS c FROM bookings")->fetch_assoc()['c'];
    
    // Dynamic Monthly Bookings Data
    $monthlyBookings = [];
    $monthlyResult = $conn->query("
        SELECT 
            MONTH(booking_date) as month,
            COUNT(*) as count,
            MONTHNAME(booking_date) as month_name
        FROM bookings 
        WHERE YEAR(booking_date) = YEAR(CURDATE())
        GROUP BY MONTH(booking_date), MONTHNAME(booking_date)
        ORDER BY month
    ");
    
    // Initialize all months with 0
    $allMonths = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 
                 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];
    
    if ($monthlyResult) {
        while($row = $monthlyResult->fetch_assoc()) {
            $monthAbbr = date('M', mktime(0, 0, 0, $row['month'], 1));
            $allMonths[$monthAbbr] = (int)$row['count'];
        }
    }
    $monthlyBookings = $allMonths;
    
    // Dynamic Vaccine Distribution
    $vaccineDistribution = [];

    $vaccineResult = $conn->query("
    SELECT 
        name,
        1 as count
        FROM vaccines 
        WHERE status = 'available'
        GROUP BY name
        ORDER BY MAX(created_at) DESC
    ");
    
    if ($vaccineResult && $vaccineResult->num_rows > 0) {
        while($row = $vaccineResult->fetch_assoc()) {
            $vaccineDistribution[$row['name']] = (int)$row['count'];
        }
    } else {
        // Fallback data if no vaccines
        $vaccineDistribution = [
            'Pfizer' => 35, 'Moderna' => 25, 'AstraZeneca' => 20, 
            'Johnson & Johnson' => 15, 'Other' => 5
        ];
    }
    
    // Dynamic Age Groups from Patients
    $ageGroups = [
        '0-18' => 0, '19-30' => 0, '31-45' => 0, '46-60' => 0, '60+' => 0
    ];
    
    // Check if date_of_birth column exists
    $checkColumns = $conn->query("SHOW COLUMNS FROM patients");
    $existingColumns = [];
    while($column = $checkColumns->fetch_assoc()) {
        $existingColumns[] = $column['Field'];
    }
    
    if (in_array('date_of_birth', $existingColumns)) {
        $ageResult = $conn->query("
            SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 0 AND 18 THEN '0-18'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 19 AND 30 THEN '19-30'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 45 THEN '31-45'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
                    ELSE '60+'
                END as age_group,
                COUNT(*) as count
            FROM patients 
            WHERE date_of_birth IS NOT NULL
            GROUP BY age_group
        ");
        
        if ($ageResult) {
            while($row = $ageResult->fetch_assoc()) {
                $ageGroups[$row['age_group']] = (int)$row['count'];
            }
        }
    }
    
    // Additional Dynamic Statistics
    $pendingBookings = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'pending'")->fetch_assoc()['c'];
    $activeHospitals = $conn->query("SELECT COUNT(*) AS c FROM hospitals WHERE status = 'active'")->fetch_assoc()['c'];
    $availableVaccines = $conn->query("SELECT COUNT(*) AS c FROM vaccines WHERE status = 'available'")->fetch_assoc()['c'];
    
    // Recent Activities
    $recentActivities = [];
    
    // Recent hospital registrations
    $recentHospitals = $conn->query("
        SELECT name, created_at 
        FROM hospitals 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    if ($recentHospitals) {
        while($row = $recentHospitals->fetch_assoc()) {
            $recentActivities[] = [
                'type' => 'hospital',
                'message' => "New Hospital Registered: " . $row['name'],
                'time' => $row['created_at']
            ];
        }
    }
    
    // Recent bookings
    $recentBookings = $conn->query("
        SELECT b.id, p.name as patient_name, b.created_at 
        FROM bookings b 
        JOIN patients p ON b.patient_id = p.id 
        ORDER BY b.created_at DESC 
        LIMIT 3
    ");
    if ($recentBookings) {
        while($row = $recentBookings->fetch_assoc()) {
            $recentActivities[] = [
                'type' => 'booking',
                'message' => "New Booking: " . $row['patient_name'],
                'time' => $row['created_at']
            ];
        }
    }
    
    // Pending hospital approvals
    $pendingApprovals = $conn->query("
        SELECT COUNT(*) as count 
        FROM hospital_requests 
        WHERE status = 'pending'
    ")->fetch_assoc()['count'];
}

// Patient Details Section - Enhanced with dynamic charts
if ($section === 'patients') {
    // First, let's check what columns exist in the patients table
    $checkColumns = $conn->query("SHOW COLUMNS FROM patients");
    $existingColumns = [];
    while($column = $checkColumns->fetch_assoc()) {
        $existingColumns[] = $column['Field'];
    }
    
    // Fetch all patients from the database
    $patientsQuery = "SELECT * FROM patients ORDER BY created_at DESC";
    $patientsResult = $conn->query($patientsQuery);
    
    // Patient statistics for charts - Dynamic data
    $totalPatients = $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'];
    $malePatients = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE gender = 'Male'")->fetch_assoc()['c'];
    $femalePatients = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE gender = 'Female'")->fetch_assoc()['c'];
    $otherPatients = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE gender NOT IN ('Male', 'Female') OR gender IS NULL")->fetch_assoc()['c'];
    
    // Age group statistics - check if date_of_birth column exists
    $ageGroups = [
        '0-18' => 0, '19-30' => 0, '31-45' => 0, '46-60' => 0, '60+' => 0
    ];
    
    if (in_array('date_of_birth', $existingColumns)) {
        // If date_of_birth column exists, use it for age calculation
        $ageResult = $conn->query("
            SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 0 AND 18 THEN '0-18'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 19 AND 30 THEN '19-30'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 45 THEN '31-45'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
                    ELSE '60+'
                END as age_group,
                COUNT(*) as count
            FROM patients 
            WHERE date_of_birth IS NOT NULL
            GROUP BY age_group
        ");
        
        if ($ageResult) {
            while($row = $ageResult->fetch_assoc()) {
                $ageGroups[$row['age_group']] = (int)$row['count'];
            }
        }
    }
    
    // Vaccination status - check if vaccination_status column exists
    $fullyVaccinated = 0;
    $partiallyVaccinated = 0;
    $notVaccinated = 0;
    
    if (in_array('vaccination_status', $existingColumns)) {
        $fullyVaccinated = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE vaccination_status = 'fully_vaccinated'")->fetch_assoc()['c'];
        $partiallyVaccinated = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE vaccination_status = 'partially_vaccinated'")->fetch_assoc()['c'];
        $notVaccinated = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE vaccination_status = 'not_vaccinated' OR vaccination_status IS NULL")->fetch_assoc()['c'];
    } else {
        // If vaccination_status column doesn't exist, set default values
        $notVaccinated = $totalPatients;
    }
    
    // Dynamic registration data for chart
    $monthlyRegistrations = [];
    $monthlyResult = $conn->query("
        SELECT 
            MONTH(created_at) as month,
            COUNT(*) as count,
            MONTHNAME(created_at) as month_name
        FROM patients 
        WHERE YEAR(created_at) = YEAR(CURDATE())
        GROUP BY MONTH(created_at), MONTHNAME(created_at)
        ORDER BY month
    ");
    
    // Initialize all months with 0
    $allMonths = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 
                 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];
    
    if ($monthlyResult) {
        while($row = $monthlyResult->fetch_assoc()) {
            $monthAbbr = date('M', mktime(0, 0, 0, $row['month'], 1));
            $allMonths[$monthAbbr] = (int)$row['count'];
        }
    }
    $monthlyRegistrations = $allMonths;
}

// Handle patient export
if (isset($_GET['export_patients']) && $_GET['export_patients'] === 'xls') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="patient_details_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "Patient Details Report\n\n";
    echo "Generated On: " . date('Y-m-d H:i:s') . "\n\n";
    
    echo "Patient ID\tName\tEmail\tPhone\tGender\tDate of Birth\tAge\tVaccination Status\tRegistration Date\n";
    
    $exportQuery = "SELECT * FROM patients ORDER BY created_at DESC";
    $exportResult = $conn->query($exportQuery);
    
    if ($exportResult && $exportResult->num_rows > 0) {
        while($patient = $exportResult->fetch_assoc()) {
            // Calculate age
            $age = 'N/A';
            if (isset($patient['date_of_birth'])) {
                $birthDate = new DateTime($patient['date_of_birth']);
                $today = new DateTime();
                $age = $today->diff($birthDate)->y . ' years';
            }
            
            echo $patient['id'] . "\t";
            echo $patient['name'] . "\t";
            echo $patient['email'] . "\t";
            echo $patient['phone'] . "\t";
            echo $patient['gender'] . "\t";
            echo $patient['date_of_birth'] . "\t";
            echo $age . "\t";
            echo $patient['vaccination_status'] . "\t";
            echo $patient['created_at'] . "\n";
        }
    }
    exit();
}

// Handle add patient form submission
if (isset($_POST['action']) && $_POST['action'] === 'add_patient') {
    $errors = [];
    
    // Validate required fields - Add patient_dob to required fields
    $required_fields = ['patient_name', 'patient_email', 'patient_phone', 'patient_gender', 'patient_address', 'patient_dob'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('patient_', '', $field)) . " is required!";
        }
    }

    // Validate email
    if (!empty($_POST['patient_email']) && !filter_var($_POST['patient_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format!";
    }
    
    // Validate date of birth
    if (!empty($_POST['patient_dob'])) {
        $dob = $_POST['patient_dob'];
        $today = date('Y-m-d');
        if ($dob > $today) {
            $errors[] = "Date of birth cannot be in the future!";
        }
    }
    
    if (empty($errors)) {
        $name = $conn->real_escape_string($_POST['patient_name']);
        $email = $conn->real_escape_string($_POST['patient_email']);
        $phone = $conn->real_escape_string($_POST['patient_phone']);
        $gender = $conn->real_escape_string($_POST['patient_gender']);
        $date_of_birth = $conn->real_escape_string($_POST['patient_dob']); // Now required
        $address = $conn->real_escape_string($_POST['patient_address']);
        $vaccination_status = !empty($_POST['patient_vaccination_status']) ? $conn->real_escape_string($_POST['patient_vaccination_status']) : 'not_vaccinated';
        
        $insertQuery = "INSERT INTO patients (name, email, phone, gender, date_of_birth, address, vaccination_status) 
                       VALUES ('$name', '$email', '$phone', '$gender', '$date_of_birth', '$address', '$vaccination_status')";
        
        if ($conn->query($insertQuery)) {
            $_SESSION['message'] = "Patient added successfully!";
            header("Location: admin_dashboard.php?section=patients");
            exit();
        } else {
            $_SESSION['error'] = "Error adding patient: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Handle edit patient form submission
if (isset($_POST['action']) && $_POST['action'] === 'edit_patient' && isset($_POST['patient_id'])) {
    $errors = [];
    
    // Validate required fields - Add patient_dob to required fields
    $required_fields = ['patient_name', 'patient_email', 'patient_phone', 'patient_gender', 'patient_dob'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('patient_', '', $field)) . " is required!";
        }
    }
    
    // Validate email
    if (!empty($_POST['patient_email']) && !filter_var($_POST['patient_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format!";
    }
    
    // Check if email already exists (excluding current patient)
    if (!empty($_POST['patient_email'])) {
        $patient_id = $conn->real_escape_string($_POST['patient_id']);
        $checkEmail = $conn->query("SELECT id FROM patients WHERE email = '" . $conn->real_escape_string($_POST['patient_email']) . "' AND id != '$patient_id'");
        if ($checkEmail->num_rows > 0) {
            $errors[] = "Email already exists!";
        }
    }
    
    // Validate date of birth
    if (!empty($_POST['patient_dob'])) {
        $dob = $_POST['patient_dob'];
        $today = date('Y-m-d');
        if ($dob > $today) {
            $errors[] = "Date of birth cannot be in the future!";
        }
    }
    
    if (empty($errors)) {
        $patient_id = $conn->real_escape_string($_POST['patient_id']);
        $name = $conn->real_escape_string($_POST['patient_name']);
        $email = $conn->real_escape_string($_POST['patient_email']);
        $phone = $conn->real_escape_string($_POST['patient_phone']);
        $gender = $conn->real_escape_string($_POST['patient_gender']);
        $date_of_birth = $conn->real_escape_string($_POST['patient_dob']); // Now required
        $address = !empty($_POST['patient_address']) ? $conn->real_escape_string($_POST['patient_address']) : NULL;
        $vaccination_status = !empty($_POST['patient_vaccination_status']) ? $conn->real_escape_string($_POST['patient_vaccination_status']) : 'not_vaccinated';
        
        $updateQuery = "UPDATE patients SET 
                       name = '$name', 
                       email = '$email', 
                       phone = '$phone', 
                       gender = '$gender', 
                       date_of_birth = '$date_of_birth', 
                       address = '$address', 
                       vaccination_status = '$vaccination_status' 
                       WHERE id = '$patient_id'";
        
        if ($conn->query($updateQuery)) {
            $_SESSION['message'] = "Patient updated successfully!";
            header("Location: admin_dashboard.php?section=patients");
            exit();
        } else {
            $_SESSION['error'] = "Error updating patient: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Fetch patient details for view/edit
$patientDetails = null;
if (isset($_GET['view_patient']) || isset($_GET['edit_patient'])) {
    $patient_id = $conn->real_escape_string($_GET['view_patient'] ?? $_GET['edit_patient']);
    $patientQuery = "SELECT * FROM patients WHERE id = '$patient_id'";
    $patientResult = $conn->query($patientQuery);
    if ($patientResult && $patientResult->num_rows > 0) {
        $patientDetails = $patientResult->fetch_assoc();
    }
}


if (isset($_GET['ajax_get_patient']) && isset($_GET['patient_id'])) {
    $patient_id = $conn->real_escape_string($_GET['patient_id']);
    $query = "SELECT * FROM patients WHERE id = '$patient_id'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'patient' => $patient]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit();
    }
}

    // Naya code for handling view/edit parameters
    $patientDetails = null;
    if (isset($_GET['view_patient']) || isset($_GET['edit_patient'])) {
        $patient_id = $conn->real_escape_string($_GET['view_patient'] ?? $_GET['edit_patient']);
        $patientQuery = "SELECT * FROM patients WHERE id = '$patient_id'";
        $patientResult = $conn->query($patientQuery);
        if ($patientResult && $patientResult->num_rows > 0) {
            $patientDetails = $patientResult->fetch_assoc();
        }
    }

    // AJAX endpoint for getting patient details
if (isset($_GET['ajax_get_patient']) && isset($_GET['patient_id'])) {
    $patient_id = $conn->real_escape_string($_GET['patient_id']);
    $query = "SELECT * FROM patients WHERE id = '$patient_id'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'patient' => $patient]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit();
    }
}

// Add this near the patient AJAX endpoint (around line 400)
if (isset($_GET['ajax_get_vaccine']) && isset($_GET['vaccine_id'])) {
    $vaccine_id = $conn->real_escape_string($_GET['vaccine_id']);
    $query = "SELECT * FROM vaccines WHERE id = '$vaccine_id'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $vaccine = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'vaccine' => $vaccine]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Vaccine not found']);
        exit();
    }
}

// Hospital Approval Management Section
if ($section === 'hospital_management') {
    // Initialize variables
    $pendingHospitals = [];
    $approvedHospitals = [];
    
    // Fetch pending hospitals (is_approved = 0)
    $pendingQuery = "SELECT * FROM hospitals WHERE is_approved = 0 ORDER BY created_at DESC";
    $pendingResult = $conn->query($pendingQuery);
    if ($pendingResult) {
        $pendingHospitals = $pendingResult->fetch_all(MYSQLI_ASSOC);
    }
    
    // Fetch approved hospitals (is_approved = 1)
    $approvedQuery = "SELECT * FROM hospitals WHERE is_approved = 1 ORDER BY created_at DESC";
    $approvedResult = $conn->query($approvedQuery);
    if ($approvedResult) {
        $approvedHospitals = $approvedResult->fetch_all(MYSQLI_ASSOC);
    }
    
    // Handle approval/rejection actions
    if (isset($_POST['action'])) {
        if (($_POST['action'] === 'approve_hospital' || $_POST['action'] === 'reject_hospital') && isset($_POST['hospital_id'])) {
            $hospital_id = $conn->real_escape_string($_POST['hospital_id']);
            $is_approved = $_POST['action'] === 'approve_hospital' ? 1 : 0;
            $admin_notes = $conn->real_escape_string($_POST['admin_notes'] ?? '');
            
            // Update hospital approval status using prepared statement
            $stmt = $conn->prepare("UPDATE hospitals SET is_approved = ?, admin_notes = ? WHERE id = ?");
            $stmt->bind_param("isi", $is_approved, $admin_notes, $hospital_id);
            
            if ($stmt->execute()) {
                $action = $_POST['action'] === 'approve_hospital' ? 'approved' : 'rejected';
                $_SESSION['message'] = "Hospital account {$action} successfully";
                
                // Refresh the page to show updated lists
                header("Location: admin_dashboard.php?section=hospital_management");
                exit();
            } else {
                $_SESSION['error'] = "Error updating hospital: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // Handle export request
    if (isset($_GET['export_hospitals']) && $_GET['export_hospitals'] === 'xls') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="hospital_management_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo "Hospital Management Report\n\n";
        echo "Generated On: " . date('Y-m-d H:i:s') . "\n\n";
        
        echo "Hospital ID\tHospital Name\tEmail\tPhone\tAddress\tApproval Status\tCreated Date\n";
        
        // Export all hospitals
        $exportQuery = "SELECT * FROM hospitals ORDER BY created_at DESC";
        $exportResult = $conn->query($exportQuery);
        
        if ($exportResult && $exportResult->num_rows > 0) {
            while($hospital = $exportResult->fetch_assoc()) {
                echo $hospital['id'] . "\t";
                echo $hospital['name'] . "\t";
                echo $hospital['email'] . "\t";
                echo $hospital['phone'] . "\t";
                echo $hospital['address'] . "\t";
                echo $hospital['is_approved'] ? 'Approved' : 'Pending' . "\t";
                echo $hospital['created_at'] . "\n";
            }
        }
        exit();
    }
}


// COVID-19 Reports Section
if ($section === 'reports') {
    // Handle report filtering
    $dateFilter = $_GET['date_filter'] ?? 'all';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    
    // Base query for reports
    $reportsQuery = "
        SELECT cr.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone 
        FROM covid_reports cr 
        JOIN patients p ON cr.patient_id = p.id 
    ";
    
    // Apply filters
    $whereConditions = [];
    $queryParams = [];
    
    if ($dateFilter === 'today') {
        $whereConditions[] = "cr.test_date = CURDATE()";
    } elseif ($dateFilter === 'week') {
        $whereConditions[] = "cr.test_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($dateFilter === 'month') {
        $whereConditions[] = "cr.test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($dateFilter === 'custom' && $startDate && $endDate) {
        $whereConditions[] = "cr.test_date BETWEEN ? AND ?";
        $queryParams[] = $startDate;
        $queryParams[] = $endDate;
    }
    
    if (!empty($whereConditions)) {
        $reportsQuery .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $reportsQuery .= " ORDER BY cr.test_date DESC, cr.created_at DESC";
    
    // Prepare and execute query
    if ($stmt = $conn->prepare($reportsQuery)) {
        if (!empty($queryParams)) {
            $types = str_repeat('s', count($queryParams));
            $stmt->bind_param($types, ...$queryParams);
        }
        $stmt->execute();
        $reportsResult = $stmt->get_result();
    } else {
        $reportsResult = $conn->query($reportsQuery);
    }
    
    // Statistics for dashboard
    $totalTests = $conn->query("SELECT COUNT(*) as count FROM covid_reports")->fetch_assoc()['count'];
    $positiveCases = $conn->query("SELECT COUNT(*) as count FROM covid_reports WHERE result = 'Positive'")->fetch_assoc()['count'];
    $negativeCases = $conn->query("SELECT COUNT(*) as count FROM covid_reports WHERE result = 'Negative'")->fetch_assoc()['count'];
    $todayTests = $conn->query("SELECT COUNT(*) as count FROM covid_reports WHERE test_date = CURDATE()")->fetch_assoc()['count'];
    
    // Test type distribution
    $testTypeStats = [];
    $typeResult = $conn->query("
        SELECT test_type, COUNT(*) as count 
        FROM covid_reports 
        GROUP BY test_type
    ");
    while($row = $typeResult->fetch_assoc()) {
        $testTypeStats[$row['test_type']] = $row['count'];
    }
    
    // Severity distribution
    $severityStats = [];
    $severityResult = $conn->query("
        SELECT severity, COUNT(*) as count 
        FROM covid_reports 
        WHERE severity IS NOT NULL 
        GROUP BY severity
    ");
    while($row = $severityResult->fetch_assoc()) {
        $severityStats[$row['severity']] = $row['count'];
    }
}

// Handle COVID-19 report export
if (isset($_GET['export_covid_reports']) && $_GET['export_covid_reports'] === 'xls') {
    $exportFilter = $_GET['export_filter'] ?? 'all';
    $exportStartDate = $_GET['export_start_date'] ?? '';
    $exportEndDate = $_GET['export_end_date'] ?? '';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="covid_reports_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "COVID-19 Test Reports\n\n";
    echo "Generated On: " . date('Y-m-d H:i:s') . "\n";
    echo "Filter: " . ucfirst($exportFilter) . "\n\n";
    
    // Build export query
    $exportQuery = "
        SELECT cr.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone 
        FROM covid_reports cr 
        JOIN patients p ON cr.patient_id = p.id 
    ";
    
    $whereConditions = [];
    $queryParams = [];
    
    if ($exportFilter === 'today') {
        $whereConditions[] = "cr.test_date = CURDATE()";
    } elseif ($exportFilter === 'week') {
        $whereConditions[] = "cr.test_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($exportFilter === 'month') {
        $whereConditions[] = "cr.test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($exportFilter === 'custom' && $exportStartDate && $exportEndDate) {
        $whereConditions[] = "cr.test_date BETWEEN ? AND ?";
        $queryParams[] = $exportStartDate;
        $queryParams[] = $exportEndDate;
    }
    
    if (!empty($whereConditions)) {
        $exportQuery .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $exportQuery .= " ORDER BY cr.test_date DESC";
    
    // Execute export query
    if ($stmt = $conn->prepare($exportQuery)) {
        if (!empty($queryParams)) {
            $types = str_repeat('s', count($queryParams));
            $stmt->bind_param($types, ...$queryParams);
        }
        $stmt->execute();
        $exportResult = $stmt->get_result();
    } else {
        $exportResult = $conn->query($exportQuery);
    }
    
    // Export headers
    echo "Report ID\tPatient Name\tPatient Email\tPatient Phone\tTest Date\tTest Type\tResult\tTest Center\tSymptoms\tSeverity\tDoctor Name\tNotes\n";
    
    if ($exportResult && $exportResult->num_rows > 0) {
        while($report = $exportResult->fetch_assoc()) {
            echo $report['id'] . "\t";
            echo $report['patient_name'] . "\t";
            echo $report['patient_email'] . "\t";
            echo $report['patient_phone'] . "\t";
            echo $report['test_date'] . "\t";
            echo $report['test_type'] . "\t";
            echo $report['result'] . "\t";
            echo $report['test_center'] . "\t";
            echo $report['symptoms'] . "\t";
            echo $report['severity'] . "\t";
            echo $report['doctor_name'] . "\t";
            echo $report['notes'] . "\n";
        }
    }
    exit();
}

// Handle add COVID report form submission
if (isset($_POST['action']) && $_POST['action'] === 'add_covid_report') {
    $errors = [];
    
    // Validate required fields
    $required_fields = ['patient_id', 'test_date', 'test_type', 'result', 'test_center'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required!";
        }
    }
    
    // Validate test date
    if (!empty($_POST['test_date'])) {
        $testDate = $_POST['test_date'];
        $today = date('Y-m-d');
        if ($testDate > $today) {
            $errors[] = "Test date cannot be in the future!";
        }
    }
    
    if (empty($errors)) {
        $patient_id = $conn->real_escape_string($_POST['patient_id']);
        $test_date = $conn->real_escape_string($_POST['test_date']);
        $test_type = $conn->real_escape_string($_POST['test_type']);
        $result = $conn->real_escape_string($_POST['result']);
        $test_center = $conn->real_escape_string($_POST['test_center']);
        $symptoms = !empty($_POST['symptoms']) ? $conn->real_escape_string($_POST['symptoms']) : NULL;
        $severity = !empty($_POST['severity']) ? $conn->real_escape_string($_POST['severity']) : NULL;
        $doctor_name = !empty($_POST['doctor_name']) ? $conn->real_escape_string($_POST['doctor_name']) : NULL;
        $notes = !empty($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : NULL;
        
        $insertQuery = "INSERT INTO covid_reports (patient_id, test_date, test_type, result, test_center, symptoms, severity, doctor_name, notes) 
                       VALUES ('$patient_id', '$test_date', '$test_type', '$result', '$test_center', '$symptoms', '$severity', '$doctor_name', '$notes')";
        
        if ($conn->query($insertQuery)) {
            $_SESSION['message'] = "COVID-19 test report added successfully!";
            header("Location: admin_dashboard.php?section=reports");
            exit();
        } else {
            $_SESSION['error'] = "Error adding COVID-19 report: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Handle edit COVID report form submission
if (isset($_POST['action']) && $_POST['action'] === 'edit_covid_report' && isset($_POST['report_id'])) {
    $errors = [];
    
    // Validate required fields
    $required_fields = ['patient_id', 'test_date', 'test_type', 'result', 'test_center'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required!";
        }
    }
    
    // Validate test date
    if (!empty($_POST['test_date'])) {
        $testDate = $_POST['test_date'];
        $today = date('Y-m-d');
        if ($testDate > $today) {
            $errors[] = "Test date cannot be in the future!";
        }
    }
    
    if (empty($errors)) {
        $report_id = $conn->real_escape_string($_POST['report_id']);
        $patient_id = $conn->real_escape_string($_POST['patient_id']);
        $test_date = $conn->real_escape_string($_POST['test_date']);
        $test_type = $conn->real_escape_string($_POST['test_type']);
        $result = $conn->real_escape_string($_POST['result']);
        $test_center = $conn->real_escape_string($_POST['test_center']);
        $symptoms = !empty($_POST['symptoms']) ? $conn->real_escape_string($_POST['symptoms']) : NULL;
        $severity = !empty($_POST['severity']) ? $conn->real_escape_string($_POST['severity']) : NULL;
        $doctor_name = !empty($_POST['doctor_name']) ? $conn->real_escape_string($_POST['doctor_name']) : NULL;
        $notes = !empty($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : NULL;
        
        $updateQuery = "UPDATE covid_reports SET 
                       patient_id = '$patient_id', 
                       test_date = '$test_date', 
                       test_type = '$test_type', 
                       result = '$result', 
                       test_center = '$test_center', 
                       symptoms = '$symptoms', 
                       severity = '$severity', 
                       doctor_name = '$doctor_name', 
                       notes = '$notes' 
                       WHERE id = '$report_id'";
        
        if ($conn->query($updateQuery)) {
            $_SESSION['message'] = "COVID-19 test report updated successfully!";
            header("Location: admin_dashboard.php?section=reports");
            exit();
        } else {
            $_SESSION['error'] = "Error updating COVID-19 report: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Fetch COVID report details for view/edit
$covidReportDetails = null;
if (isset($_GET['view_report']) || isset($_GET['edit_report'])) {
    $report_id = $conn->real_escape_string($_GET['view_report'] ?? $_GET['edit_report']);
    $reportQuery = "
        SELECT cr.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone 
        FROM covid_reports cr 
        JOIN patients p ON cr.patient_id = p.id 
        WHERE cr.id = '$report_id'
    ";
    $reportResult = $conn->query($reportQuery);
    if ($reportResult && $reportResult->num_rows > 0) {
        $covidReportDetails = $reportResult->fetch_assoc();
    }
}

// AJAX endpoint for getting COVID report details
if (isset($_GET['ajax_get_covid_report']) && isset($_GET['report_id'])) {
    $report_id = $conn->real_escape_string($_GET['report_id']);
    $query = "
        SELECT cr.*, p.name as patient_name 
        FROM covid_reports cr 
        JOIN patients p ON cr.patient_id = p.id 
        WHERE cr.id = '$report_id'
    ";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $report = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'report' => $report]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'COVID-19 report not found']);
        exit();
    }
}

// Vaccine List Section
if ($section === 'vaccines') {
    // Check if vaccines table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'vaccines'");
    $tableExists = $tableCheck->num_rows > 0;
    
    // Initialize variables
    $vaccinesData = [];
    $totalVaccines = 0;
    $availableVaccines = 0;
    $unavailableVaccines = 0;
    
    if ($tableExists) {
        // Fetch all vaccines
        $vaccinesQuery = "SELECT * FROM vaccines ORDER BY created_at DESC";
        $vaccinesResult = $conn->query($vaccinesQuery);
        
        if ($vaccinesResult) {
            $vaccinesData = $vaccinesResult->fetch_all(MYSQLI_ASSOC);
            $totalVaccines = count($vaccinesData);
            
            // Calculate statistics
            foreach($vaccinesData as $vaccine) {
                if ($vaccine['status'] === 'available') {
                    $availableVaccines++;
                } else {
                    $unavailableVaccines++;
                }
            }
        }
    }

    // Initialize error array for form feedback
    $formErrors = [];

    // Handle vaccine actions with field-specific validation
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_vaccine' && isset($_POST['vaccine_name'])) {
            $formErrors = [];

            // --- Required Field Validation ---
            $name = trim($_POST['vaccine_name'] ?? '');
            if ($name === '') {
                $formErrors['name-error'] = 'Name is required';
            }

            $description = trim($_POST['vaccine_description'] ?? '');
            if ($description === '') {
                $formErrors['description-error'] = 'Description is required';
            }

            $status = $_POST['vaccine_status'] ?? '';
            if ($status === '') {
                $formErrors['status-error'] = 'Please select a status';
            }

            $doses = $_POST['doses_required'] ?? '';
            if ($doses === '' || !is_numeric($doses) || $doses < 1) {
                $formErrors['doses_required-error'] = 'Doses is required';
            }

            $storage = trim($_POST['storage_temp'] ?? '');
            if ($storage === '') {
                $formErrors['storage_temp-error'] = 'Storage temperature is required';
            }

            $efficacy = trim($_POST['efficacy_rate'] ?? '');
            if ($efficacy === '') {
                $formErrors['efficacy_rate-error'] = 'Efficacy rate is required';
            }

            //  Additional Format Checks (optional but recommended) 
            if (!empty($efficacy) && !preg_match('/^(100|[1-9]?[0-9])(\.\d+)?%?$/', $efficacy)) {
                $formErrors['efficacy_rate-error'] = 'Invalid efficacy format (e.g., 95% or 0.95)';
            }

            //  Save to Session for AJAX or Form Redisplay 
            if (!empty($formErrors)) {
                $_SESSION['form_errors'] = $formErrors;
                $_SESSION['old_input'] = $_POST;
            } else {
                $name = $conn->real_escape_string($name);
                $description = $conn->real_escape_string($description);
                $status = $conn->real_escape_string($status);
                $doses_required = (int)$doses;
                $storage_temp = $conn->real_escape_string($storage);
                $efficacy_rate = $conn->real_escape_string($efficacy);

                $insertQuery = "INSERT INTO vaccines 
                    (name, description, status, doses_required, storage_temp, efficacy_rate) 
                    VALUES ('$name', '$description', '$status', '$doses_required', '$storage_temp', '$efficacy_rate')";

                if ($conn->query($insertQuery)) {
                    $_SESSION['message'] = "Vaccine added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding vaccine: " . $conn->error;
                }
            }
            header("Location: admin_dashboard.php?section=vaccines");
            exit();
        }

        //  EDIT VACCINE 
        elseif ($_POST['action'] === 'edit_vaccine' && isset($_POST['vaccine_id'])) {
            $formErrors = [];

            $vaccine_id = $_POST['vaccine_id'];
            if (!is_numeric($vaccine_id)) {
                $_SESSION['error'] = "Invalid vaccine ID";
                header("Location: admin_dashboard.php?section=vaccines");
                exit();
            }

            $name = trim($_POST['vaccine_name'] ?? '');
            if ($name === '') $formErrors['edit-name-error'] = 'Name is required';

            $description = trim($_POST['vaccine_description'] ?? '');
            if ($description === '') $formErrors['edit-description-error'] = 'Description is required';

            $status = $_POST['vaccine_status'] ?? '';
            if ($status === '') $formErrors['edit-status-error'] = 'Please select a status';

            $doses = $_POST['doses_required'] ?? '';
            if ($doses === '' || !is_numeric($doses) || $doses < 1) {
                $formErrors['edit-doses_required-error'] = 'Doses is required';
            }

            $storage = trim($_POST['storage_temp'] ?? '');
            if ($storage === '') $formErrors['edit-storage_temp-error'] = 'Storage temperature is required';

            $efficacy = trim($_POST['efficacy_rate'] ?? '');
            if ($efficacy === '') $formErrors['edit-efficacy_rate-error'] = 'Efficacy rate is required';

            if (!empty($efficacy) && !preg_match('/^(100|[1-9]?[0-9])(\.\d+)?%?$/', $efficacy)) {
                $formErrors['edit-efficacy_rate-error'] = 'Invalid efficacy format (e.g., 95% or 0.95)';
            }

            if (!empty($formErrors)) {
                $_SESSION['form_errors'] = $formErrors;
                $_SESSION['old_input'] = $_POST;
            } else {
                $name = $conn->real_escape_string($name);
                $description = $conn->real_escape_string($description);
                $status = $conn->real_escape_string($status);
                $doses_required = (int)$doses;
                $storage_temp = $conn->real_escape_string($storage);
                $efficacy_rate = $conn->real_escape_string($efficacy);

                $updateQuery = "UPDATE vaccines SET 
                    name = '$name', 
                    description = '$description', 
                    status = '$status', 
                    doses_required = '$doses_required', 
                    storage_temp = '$storage_temp', 
                    efficacy_rate = '$efficacy_rate' 
                    WHERE id = '$vaccine_id'";

                if ($conn->query($updateQuery)) {
                    $_SESSION['message'] = "Vaccine updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating vaccine: " . $conn->error;
                }
            }
            header("Location: admin_dashboard.php?section=vaccines");
            exit();
        }

        //  OTHER ACTIONS (unchanged) 
        elseif ($_POST['action'] === 'update_vaccine_status' && isset($_POST['vaccine_id'])) {
            $vaccine_id = $conn->real_escape_string($_POST['vaccine_id']);
            $status = $conn->real_escape_string($_POST['status']);
            $updateQuery = "UPDATE vaccines SET status = '$status' WHERE id = '$vaccine_id'";
            if ($conn->query($updateQuery)) {
                $_SESSION['message'] = "Vaccine status updated successfully!";
            } else {
                $_SESSION['error'] = "Error updating vaccine status: " . $conn->error;
            }
            header("Location: admin_dashboard.php?section=vaccines");
            exit();
        }

        elseif ($_POST['action'] === 'delete_vaccine' && isset($_POST['vaccine_id'])) {
            $vaccine_id = $conn->real_escape_string($_POST['vaccine_id']);
            $deleteQuery = "DELETE FROM vaccines WHERE id = '$vaccine_id'";
            if ($conn->query($deleteQuery)) {
                $_SESSION['message'] = "Vaccine deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting vaccine: " . $conn->error;
            }
            header("Location: admin_dashboard.php?section=vaccines");
            exit();
        }
    }

    // Fetch vaccine details for view/edit
    $vaccineDetails = null;
    if (isset($_GET['view_vaccine']) || isset($_GET['edit_vaccine'])) {
        $vaccine_id = $conn->real_escape_string($_GET['view_vaccine'] ?? $_GET['edit_vaccine']);
        $vaccineQuery = "SELECT * FROM vaccines WHERE id = '$vaccine_id'";
        $vaccineResult = $conn->query($vaccineQuery);
        if ($vaccineResult && $vaccineResult->num_rows > 0) {
            $vaccineDetails = $vaccineResult->fetch_assoc();
        }
    }

    // AJAX endpoint for getting vaccine details
    if (isset($_GET['ajax_get_vaccine']) && isset($_GET['vaccine_id'])) {
        $vaccine_id = $conn->real_escape_string($_GET['vaccine_id']);
        $query = "SELECT * FROM vaccines WHERE id = '$vaccine_id'";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $vaccine = $result->fetch_assoc();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'vaccine' => $vaccine]);
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vaccine not found']);
            exit();
        }
    }

    // Handle vaccine export
    if (isset($_GET['export_vaccines']) && $_GET['export_vaccines'] === 'xls') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="vaccine_details_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        echo "Vaccine Details Report\n\n";
        echo "Generated On: " . date('Y-m-d H:i:s') . "\n\n";
        echo "Vaccine ID\tName\tDescription\tStatus\tDoses Required\tStorage Temperature\tEfficacy Rate\tCreated Date\n";
        $exportQuery = "SELECT * FROM vaccines ORDER BY created_at DESC";
        $exportResult = $conn->query($exportQuery);
        if ($exportResult && $exportResult->num_rows > 0) {
            while($vaccine = $exportResult->fetch_assoc()) {
                echo $vaccine['id'] . "\t" . $vaccine['name'] . "\t" . $vaccine['description'] . "\t" . $vaccine['status'] . "\t" . $vaccine['doses_required'] . "\t" . $vaccine['storage_temp'] . "\t" . $vaccine['efficacy_rate'] . "\t" . $vaccine['created_at'] . "\n";
            }
        }
        exit();
    }
}

// Enhanced Hospital Approval Section
if ($section === 'approval') {
    // Initialize variables
    $pendingRequests = [];
    $approvedRequests = [];
    $rejectedRequests = [];
    
    // Fetch pending appointment requests that need approval
    $pendingQuery = "SELECT a.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone, 
                    h.name as hospital_name, h.email as hospital_email
                    FROM appointments a 
                    JOIN patients p ON a.patient_id = p.id 
                    JOIN hospitals h ON a.hospital_id = h.id 
                    WHERE a.appointment_status = 'pending' 
                    ORDER BY a.created_at DESC";
    $pendingResult = $conn->query($pendingQuery);
    if ($pendingResult) {
        $pendingRequests = $pendingResult->fetch_all(MYSQLI_ASSOC);
    }
    
    // Approved appointments
$approvedQuery = "SELECT a.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone, 
                 h.name as hospital_name, h.email as hospital_email
                 FROM appointments a 
                 JOIN patients p ON a.patient_id = p.id 
                 JOIN hospitals h ON a.hospital_id = h.id 
                 WHERE a.appointment_status = 'approved' 
                 ORDER BY a.created_at DESC";

// Rejected appointments
$rejectedQuery = "SELECT a.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone, 
                 h.name as hospital_name, h.email as hospital_email
                 FROM appointments a 
                 JOIN patients p ON a.patient_id = p.id 
                 JOIN hospitals h ON a.hospital_id = h.id 
                 WHERE a.appointment_status = 'rejected' 
                 ORDER BY a.created_at DESC";

    $rejectedResult = $conn->query($rejectedQuery);
    if ($rejectedResult) {
        $rejectedRequests = $rejectedResult->fetch_all(MYSQLI_ASSOC);
    }
    
    // Handle approval/rejection actions for appointments
    if (isset($_POST['action'])) {
        if (($_POST['action'] === 'approve_appointment' || $_POST['action'] === 'reject_appointment') && isset($_POST['appointment_id'])) {
            $appointment_id = $conn->real_escape_string($_POST['appointment_id']);
            $status = $_POST['action'] === 'approve_appointment' ? 'approved' : 'rejected';
            $admin_notes = $conn->real_escape_string($_POST['admin_notes'] ?? '');
            
            // Update appointment status
            $updateQuery = "UPDATE appointments SET appointment_status = '$status', status = '$status' WHERE id = '$appointment_id'";
            
            if ($conn->query($updateQuery)) {
                $_SESSION['message'] = "Appointment request " . $status . " successfully";
                header("Location: admin_dashboard.php?section=approval");
                exit();
            } else {
                $_SESSION['error'] = "Error updating appointment request: " . $conn->error;
            }
        }
    }

    // Handle export request
    if (isset($_GET['export_approval']) && $_GET['export_approval'] === 'xls') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="appointment_approval_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo "Appointment Approval Report\n\n";
        echo "Generated On: " . date('Y-m-d H:i:s') . "\n\n";
        
        echo "Appointment ID\tPatient Name\tPatient Email\tPatient Phone\tHospital Name\tTest Type\tAppointment Date\tAppointment Time\tStatus\tCreated Date\n";
        
        // Export all appointment requests
        $exportQuery = "SELECT a.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone, 
                       h.name as hospital_name
                       FROM appointments a 
                       JOIN patients p ON a.patient_id = p.id 
                       JOIN hospitals h ON a.hospital_id = h.id 
                       ORDER BY a.created_at DESC";
        $exportResult = $conn->query($exportQuery);
        
        if ($exportResult && $exportResult->num_rows > 0) {
            while($request = $exportResult->fetch_assoc()) {
                echo $request['id'] . "\t";
                echo $request['patient_name'] . "\t";
                echo $request['patient_email'] . "\t";
                echo $request['patient_phone'] . "\t";
                echo $request['hospital_name'] . "\t";
                echo $request['test_type'] . "\t";
                echo $request['appointment_date'] . "\t";
                echo $request['appointment_time'] . "\t";
                echo $request['appointment_status'] . "\t";
                echo $request['created_at'] . "\n";
            }
        }
        exit();
    }
}

// Hospital list section
if ($section === 'hospitals') {
    // Check if hospitals table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'hospitals'");
    $tableExists = $tableCheck->num_rows > 0;
    
    // Initialize variables
    $hospitalsData = [];
    $totalHospitals = 0;
    $activeHospitals = 0;
    $inactiveHospitals = 0;
    
    if ($tableExists) {
        // Fetch all hospitals
        $hospitalsQuery = "SELECT * FROM hospitals ORDER BY created_at DESC";
        $hospitalsResult = $conn->query($hospitalsQuery);
        
        if ($hospitalsResult) {
            $hospitalsData = $hospitalsResult->fetch_all(MYSQLI_ASSOC);
            $totalHospitals = count($hospitalsData);
            
            // Calculate statistics
            foreach($hospitalsData as $hospital) {
                if ($hospital['status'] === 'active') {
                    $activeHospitals++;
                } else {
                    $inactiveHospitals++;
                }
            }
        }
    }

    // Initialize error array for form feedback
    $formErrors = [];

    // Handle hospital actions with field-specific validation
    if (isset($_POST['action'])) {
        // ====================== ADD HOSPITAL ======================
        if ($_POST['action'] === 'add_hospital' && isset($_POST['hospital_name'])) {
            $formErrors = [];

            // --- Required Field Validation ---
            $name = trim($_POST['hospital_name'] ?? '');
            if ($name === '') {
                $formErrors['name-error'] = 'Hospital name is required';
            }

            $email = trim($_POST['hospital_email'] ?? '');
            if ($email === '') {
                $formErrors['email-error'] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $formErrors['email-error'] = 'Invalid email format';
            }

            $status = $_POST['hospital_status'] ?? '';
            if ($status === '') {
                $formErrors['status-error'] = 'Please select a status';
            }

            // --- Optional Fields ---
            $address = trim($_POST['hospital_address'] ?? '');
            $license_number = trim($_POST['hospital_license'] ?? '');
            $admin_name = trim($_POST['hospital_admin'] ?? '');

            // --- Save to Session for AJAX or Form Redisplay ---
            if (!empty($formErrors)) {
                $_SESSION['form_errors'] = $formErrors;
                $_SESSION['old_input'] = $_POST;
            } else {
                $name = $conn->real_escape_string($name);
                $email = $conn->real_escape_string($email);
                $address = $conn->real_escape_string($address);
                $license_number = $conn->real_escape_string($license_number);
                $admin_name = $conn->real_escape_string($admin_name);
                $status = $conn->real_escape_string($status);

                $insertQuery = "INSERT INTO hospitals 
                    (name, email, address, license_number, admin_name, status) 
                    VALUES ('$name', '$email', '$address', '$license_number', '$admin_name', '$status')";

                if ($conn->query($insertQuery)) {
                    $_SESSION['message'] = "Hospital added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding hospital: " . $conn->error;
                }
            }
            header("Location: admin_dashboard.php?section=hospitals");
            exit();
        }

        // ====================== UPDATE HOSPITAL STATUS ======================
        elseif ($_POST['action'] === 'update_hospital_status' && isset($_POST['hospital_id'])) {
            $hospital_id = $conn->real_escape_string($_POST['hospital_id']);
            $status = $conn->real_escape_string($_POST['status']);
            $updateQuery = "UPDATE hospitals SET status = '$status' WHERE id = '$hospital_id'";
            if ($conn->query($updateQuery)) {
                $_SESSION['message'] = "Hospital status updated successfully!";
            } else {
                $_SESSION['error'] = "Error updating hospital status: " . $conn->error;
            }
            header("Location: admin_dashboard.php?section=hospitals");
            exit();
        }

        // ====================== DELETE HOSPITAL ======================
        elseif ($_POST['action'] === 'delete_hospital' && isset($_POST['hospital_id'])) {
            $hospital_id = $conn->real_escape_string($_POST['hospital_id']);
            $deleteQuery = "DELETE FROM hospitals WHERE id = '$hospital_id'";
            if ($conn->query($deleteQuery)) {
                $_SESSION['message'] = "Hospital deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting hospital: " . $conn->error;
            }
            header("Location: admin_dashboard.php?section=hospitals");
            exit();
        }
    }

    // Fetch hospital details for view
    $hospitalDetails = null;
    if (isset($_GET['view_hospital'])) {
        $hospital_id = $conn->real_escape_string($_GET['view_hospital']);
        $hospitalQuery = "SELECT * FROM hospitals WHERE id = '$hospital_id'";
        $hospitalResult = $conn->query($hospitalQuery);
        if ($hospitalResult && $hospitalResult->num_rows > 0) {
            $hospitalDetails = $hospitalResult->fetch_assoc();
        }
    }

    // AJAX endpoint for getting hospital details
    if (isset($_GET['ajax_get_hospital']) && isset($_GET['hospital_id'])) {
        $hospital_id = $conn->real_escape_string($_GET['hospital_id']);
        $query = "SELECT * FROM hospitals WHERE id = '$hospital_id'";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $hospital = $result->fetch_assoc();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'hospital' => $hospital]);
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Hospital not found']);
            exit();
        }
    }

    // Handle hospital export
    if (isset($_GET['export_hospitals']) && $_GET['export_hospitals'] === 'xls') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="hospital_details_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        echo "Hospital Details Report\n\n";
        echo "Generated On: " . date('Y-m-d H:i:s') . "\n\n";
        echo "Hospital ID\tName\tEmail\tPhone\tAddress\tLicense Number\tAdmin Name\tStatus\tCreated Date\n";
        $exportQuery = "SELECT * FROM hospitals ORDER BY created_at DESC";
        $exportResult = $conn->query($exportQuery);
        if ($exportResult && $exportResult->num_rows > 0) {
            while($hospital = $exportResult->fetch_assoc()) {
                echo $hospital['id'] . "\t" . $hospital['name'] . "\t" . $hospital['email'] . "\t" . $hospital['phone'] . "\t" . $hospital['address'] . "\t" . $hospital['license_number'] . "\t" . $hospital['admin_name'] . "\t" . $hospital['status'] . "\t" . $hospital['created_at'] . "\n";
            }
        }
        exit();
    }
}


// Enhanced Hospital Approval Section
if ($section === 'approval') {
    // Initialize variables
    $pendingRequests = [];
    $approvedRequests = [];
    $rejectedRequests = [];
    
    // Fetch pending appointment requests that need approval
    $pendingQuery = "SELECT a.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone, 
                    h.name as hospital_name, h.email as hospital_email
                    FROM appointments a 
                    JOIN patients p ON a.patient_id = p.id 
                    JOIN hospitals h ON a.hospital_id = h.id 
                    WHERE a.appointment_status = 'pending' 
                    ORDER BY a.created_at DESC";
    $pendingResult = $conn->query($pendingQuery);
    if ($pendingResult) {
        $pendingRequests = $pendingResult->fetch_all(MYSQLI_ASSOC);
    }
    
    // Fetch approved appointments
    $approvedQuery = "SELECT a.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone, 
                     h.name as hospital_name, h.email as hospital_email
                     FROM appointments a 
                     JOIN patients p ON a.patient_id = p.id 
                     JOIN hospitals h ON a.hospital_id = h.id 
                     WHERE a.appointment_status = 'approved' 
                     ORDER BY a.created_at DESC";
    $approvedResult = $conn->query($approvedQuery);
    if ($approvedResult) {
        $approvedRequests = $approvedResult->fetch_all(MYSQLI_ASSOC);
    }
    
    // Fetch rejected appointments
    $rejectedQuery = "SELECT a.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone, 
                     h.name as hospital_name, h.email as hospital_email
                     FROM appointments a 
                     JOIN patients p ON a.patient_id = p.id 
                     JOIN hospitals h ON a.hospital_id = h.id 
                     WHERE a.appointment_status = 'rejected' 
                     ORDER BY a.created_at DESC";
    $rejectedResult = $conn->query($rejectedQuery);
    if ($rejectedResult) {
        $rejectedRequests = $rejectedResult->fetch_all(MYSQLI_ASSOC);
    }
    
    // Handle approval/rejection actions for appointments
    if (isset($_POST['action'])) {
        if (($_POST['action'] === 'approve_appointment' || $_POST['action'] === 'reject_appointment') && isset($_POST['appointment_id'])) {
            $appointment_id = $conn->real_escape_string($_POST['appointment_id']);
            $status = $_POST['action'] === 'approve_appointment' ? 'approved' : 'rejected';
            $admin_notes = $conn->real_escape_string($_POST['admin_notes'] ?? '');
            
            // Update appointment status - adding a timestamp for when it was approved/rejected
            $updateQuery = "UPDATE appointments SET appointment_status = '$status', status = '$status' WHERE id = '$appointment_id'";
            
            if ($conn->query($updateQuery)) {
                $_SESSION['message'] = "Appointment request " . $status . " successfully";
                header("Location: admin_dashboard.php?section=approval");
                exit();
            } else {
                $_SESSION['error'] = "Error updating appointment request: " . $conn->error;
            }
        }
    }

    // Handle export request
    if (isset($_GET['export_approval']) && $_GET['export_approval'] === 'xls') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="appointment_approval_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo "Appointment Approval Report\n\n";
        echo "Generated On: " . date('Y-m-d H:i:s') . "\n\n";
        
        echo "Appointment ID\tPatient Name\tPatient Email\tPatient Phone\tHospital Name\tTest Type\tAppointment Date\tAppointment Time\tStatus\tCreated Date\n";
        
        // Export all appointment requests
        $exportQuery = "SELECT a.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone, 
                       h.name as hospital_name
                       FROM appointments a 
                       JOIN patients p ON a.patient_id = p.id 
                       JOIN hospitals h ON a.hospital_id = h.id 
                       ORDER BY a.created_at DESC";
        $exportResult = $conn->query($exportQuery);
        
        if ($exportResult && $exportResult->num_rows > 0) {
            while($request = $exportResult->fetch_assoc()) {
                echo $request['id'] . "\t";
                echo $request['patient_name'] . "\t";
                echo $request['patient_email'] . "\t";
                echo $request['patient_phone'] . "\t";
                echo $request['hospital_name'] . "\t";
                echo $request['test_type'] . "\t";
                echo $request['appointment_date'] . "\t";
                echo $request['appointment_time'] . "\t";
                echo $request['appointment_status'] . "\t";
                echo $request['created_at'] . "\n";
            }
        }
        exit();
    }
}

// Admin Management Section
if ($section === 'admins') {
    // Handle admin actions
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_admin' && isset($_POST['admin_name'])) {
            $name = $conn->real_escape_string($_POST['admin_name']);
            $email = $conn->real_escape_string($_POST['admin_email']);
            $password = $_POST['admin_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validation
            $errors = [];
            
            if (empty($name)) {
                $errors[] = "Name is required!";
            }
            
            if (empty($email)) {
                $errors[] = "Email is required!";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format!";
            } else {
                // Check if email already exists
                $checkEmail = $conn->query("SELECT id FROM users WHERE email = '$email'");
                if ($checkEmail->num_rows > 0) {
                    $errors[] = "Email already exists!";
                }
            }
            
            if (empty($password)) {
                $errors[] = "Password is required!";
            } elseif (strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters!";
            } elseif ($password !== $confirm_password) {
                $errors[] = "Passwords do not match!";
            }
            
            if (empty($errors)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertQuery = "INSERT INTO users (name, email, password, role, gender, image_path) 
                               VALUES ('$name', '$email', '$hashedPassword', 'Admin', 'Other', 'admin_default.jpg')";
                
                if ($conn->query($insertQuery)) {
                    $_SESSION['message'] = "Admin added successfully!";
                    header("Location: admin_dashboard.php?section=admins");
                    exit();
                } else {
                    $_SESSION['error'] = "Error adding admin: " . $conn->error;
                }
            } else {
                $_SESSION['error'] = implode("<br>", $errors);
            }
        }
        elseif ($_POST['action'] === 'delete_admin' && isset($_POST['admin_id'])) {
            $admin_id = $conn->real_escape_string($_POST['admin_id']);
            
            // Prevent admin from deleting themselves
            if ($admin_id == $_SESSION['user_id']) {
                $_SESSION['error'] = "You cannot delete your own account!";
            } else {
                $deleteQuery = "DELETE FROM users WHERE id = '$admin_id' AND role = 'Admin'";
                
                if ($conn->query($deleteQuery)) {
                    $_SESSION['message'] = "Admin deleted successfully!";
                    header("Location: admin_dashboard.php?section=admins");
                    exit();
                } else {
                    $_SESSION['error'] = "Error deleting admin: " . $conn->error;
                }
            }
        }
        elseif ($_POST['action'] === 'reset_password' && isset($_POST['admin_id'])) {
            $admin_id = $conn->real_escape_string($_POST['admin_id']);
            $new_password = "Admin@123"; // Default password
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            
            $updateQuery = "UPDATE users SET password = '$hashedPassword' WHERE id = '$admin_id' AND role = 'Admin'";
            
            if ($conn->query($updateQuery)) {
                $_SESSION['message'] = "Password reset successfully! Default password: Admin@123";
                header("Location: admin_dashboard.php?section=admins");
                exit();
            } else {
                $_SESSION['error'] = "Error resetting password: " . $conn->error;
            }
        }
    }
    
    // Fetch all admins
    $adminsQuery = "SELECT id, name, email, created_at FROM users WHERE role = 'Admin' ORDER BY created_at DESC";
    $adminsResult = $conn->query($adminsQuery);
    $adminsData = $adminsResult ? $adminsResult->fetch_all(MYSQLI_ASSOC) : [];
    $totalAdmins = count($adminsData);
}

// Handle patient actions (delete, view details)
if (isset($_POST['action']) && $_POST['action'] === 'delete_patient' && isset($_POST['patient_id'])) {
    $patient_id = $conn->real_escape_string($_POST['patient_id']);
    $deleteQuery = "DELETE FROM patients WHERE id = '$patient_id'";
    if ($conn->query($deleteQuery)) {
        $_SESSION['message'] = "Patient deleted successfully";
        header("Location: admin_dashboard.php?section=patients");
        exit();
    } else {
        $_SESSION['error'] = "Error deleting patient: " . $conn->error;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Vaxify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
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
    --sidebar-width: 220px;
    --teal: var(--accent-color);
    --teal-light: #05b8d9;
    --dark: #0f2b2c;
    --muted: #6b7280;
    --card-bg: var(--surface-color);
    --radius: 12px;
    --shadow: 0 6px 18px rgba(2,6,23,0.08);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

body {
    background: linear-gradient(180deg, #2f3737 0%, #1f2324 100%);
    color: var(--default-color);
    min-height: 100vh;
    display: flex;
    font-size: clamp(14px, 2.5vw, 16px); /* Fluid typography */
}

.container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
        width: 100%;
        box-sizing: border-box;
    }


a {
    color: inherit;
    text-decoration: none;
}

.app {
    display: flex;
    width: 100%;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: var(--sidebar-width);
    background: linear-gradient(180deg, var(--dark), #062728);
    color: #dbeaea;
    padding: clamp(12px, 2vw, 18px);
    display: flex;
    flex-direction: column;
    gap: clamp(8px, 1.5vw, 10px);
    transition: transform 0.3s ease;
    z-index: 100;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    overflow-y: auto;
}

.brand {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: clamp(8px, 1.5vw, 12px) 0;
    margin-bottom: clamp(8px, 1.5vw, 10px);
    flex-shrink: 0;
}

.brand .logo {
    width: clamp(100px, 15vw, 140px);
    height: clamp(100px, 15vw, 140px);
    border-radius: 6px;
    object-fit: contain;
}

.nav {
    display: flex;
    flex-direction: column;
    gap: clamp(4px, 1vw, 6px);
    margin-top: auto;
    padding: clamp(4px, 1vw, 6px) 0;
    flex: 1;
}

.nav a {
    display: flex;
    align-items: center;
    gap: clamp(8px, 1.5vw, 12px);
    padding: clamp(8px, 1.5vw, 10px) clamp(10px, 2vw, 12px);
    border-radius: 8px;
    color: #cfe8e8;
    font-size: clamp(12px, 2vw, 14px);
    transition: background 0.25s, color 0.25s, transform 0.2s;
}

.nav a .ico {
    color: #fff;
    font-size: clamp(14px, 2vw, 16px);
    width: clamp(16px, 2.5vw, 20px);
    text-align: center;
}

.nav a.active {
    background: linear-gradient(90deg, var(--accent-color), #05b8d9);
    color: var(--contrast-color);
    font-weight: 600;
}

.nav a:hover {
    background: rgba(4, 158, 187, 0.15);
    color: #fff;
    transform: translateX(4px);
}

.nav a:hover .ico {
    color: #fff;
}

.nav a.active .ico {
    color: var(--contrast-color);
}

/* Logout */
.logout {
    padding: clamp(4px, 1vw, 6px) clamp(10px, 2vw, 12px) clamp(10px, 2vw, 12px);
    margin-top: auto;
}

.logout button {
    width: 100%;
    background: transparent;
    color: #dfe;
    border: 1px solid white;
    padding: clamp(8px, 1.5vw, 10px);
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.25s, color 0.25s;
    font-size: clamp(12px, 2vw, 14px);
    display: flex;
    align-items: center;
    gap: clamp(6px, 1vw, 8px);
    justify-content: center;
}

.logout button:hover {
    background: var(--accent-color);
    color: #fff;
}

/* Main */
.main {
    flex: 1;
    background: var(--background-color);
    border-top-left-radius: clamp(6px, 1vw, 8px);
    margin: clamp(12px, 2vw, 18px) clamp(12px, 2vw, 18px) clamp(12px, 2vw, 18px) calc(var(--sidebar-width) + clamp(12px, 2vw, 18px));
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    transition: margin 0.3s ease;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: clamp(12px, 2vw, 18px) clamp(14px, 2.5vw, 20px);
    border-bottom: 1px solid #eef2f4;
    background: var(--surface-color);
    position: sticky;
    top: 0;
    z-index: 10;
}

.left {
    display: flex;
    align-items: center;
    gap: clamp(8px, 1.5vw, 10px);
}

.hamburger {
    display: none;
    background: transparent;
    border: 1px solid #b2e5f2;
    padding: clamp(6px, 1vw, 8px);
    border-radius: 8px;
    cursor: pointer;
    font-size: clamp(16px, 2.5vw, 18px);
}

.topbar h2 {
    font-size: clamp(16px, 2.5vw, 20px);
    color: var(--heading-color);
}

.profile {
    display: flex;
    align-items: center;
    gap: clamp(8px, 1.5vw, 12px);
}

.avatar {
    width: clamp(28px, 5vw, 36px);
    height: clamp(28px, 5vw, 36px);
    border-radius: 50%;
    background: #f1f6f6;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--accent-color);
}

.content {
    padding: clamp(16px, 3vw, 24px);
    overflow: auto;
    flex: 1;
}

/* Dashboard Grid */
.grid {
    display: grid;
    grid-template-columns: 1fr minmax(300px, 360px);
    gap: clamp(12px, 2vw, 18px);
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
}

.left-column {
    display: flex;
    flex-direction: column;
    gap: clamp(10px, 2vw, 16px);
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: clamp(8px, 1.5vw, 12px);
}

.card {
    background: var(--card-bg);
    border-radius: var(--radius);
    padding: clamp(12px, 2vw, 16px);
    box-shadow: 0 6px 14px rgba(12,20,25,0.04);
    border: 1px solid #f1f5f7;
}

.card h3 {
    font-size: clamp(12px, 2vw, 14px);
    color: #0b1720;
    margin-bottom: clamp(4px, 1vw, 6px);
}

.card p {
    color: var(--muted);
    font-size: clamp(14px, 2.5vw, 18px);
    font-weight: 700;
}

.chart-container {
    background: var(--card-bg);
    border-radius: var(--radius);
    padding: clamp(12px, 2vw, 18px);
    box-shadow: 0 6px 14px rgba(12,20,25,0.04);
    border: 1px solid #f1f5f7;
    margin-top: clamp(8px, 1.5vw, 12px);
}

.chart-container h4 {
    margin-bottom: clamp(8px, 1.5vw, 12px);
    color: #062426;
    font-size: clamp(14px, 2vw, 16px);
}

.chart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: clamp(10px, 2vw, 16px);
}

.chart-item {
    height: clamp(180px, 30vw, 250px);
    position: relative;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: clamp(8px, 1.5vw, 12px);
    margin-top: clamp(10px, 2vw, 16px);
}

.stat-item {
    background: #f8fcfc;
    border-radius: 8px;
    padding: clamp(8px, 1.5vw, 12px);
    border-left: 4px solid var(--teal);
}

.panel {
    background: var(--card-bg);
    padding: clamp(12px, 2vw, 16px);
    border-radius: var(--radius);
    border: 1px solid #eef4f5;
}

.panel h4 {
    margin-bottom: clamp(8px, 1.5vw, 10px);
    color: #08353a;
    font-size: clamp(14px, 2vw, 16px);
}

.up-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: clamp(8px, 1.5vw, 12px);
    padding: clamp(8px, 1.5vw, 10px);
    border-radius: 8px;
    background: #fbfeff;
    border: 1px solid #f0f6f6;
    margin-top: clamp(6px, 1vw, 8px);
}

.up-item .button-group {
    display: flex;
    gap: clamp(6px, 1vw, 8px);
    flex-wrap: nowrap;
}

.up-item button,
.up-item a.export {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    color: #ffffff;
    padding: clamp(6px, 1.2vw, 8px) clamp(12px, 2vw, 16px);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.25s ease;
    text-align: center;
    font-size: clamp(11px, 2vw, 13px);
    font-weight: 600;
    text-decoration: none;
    line-height: 1.2;
    white-space: nowrap;
}

/* Regular buttons keep their flex behavior */
.up-item button {
    flex: 1;
    min-width: clamp(70px, 10vw, 80px);
}

/* Export button gets specific width */
.up-item a.export {
    min-width: clamp(90px, 15vw, 110px);
    width: auto;
}

.up-item button.approve,
.up-item a.export {
    background: #049ebb;
}

.up-item button.reject {
    background: var(--teal-light);
}

.up-item button:hover,
.up-item a.export:hover {
    background: #18444c;
    color: #ffffff;
    transform: translateY(-1px);
    text-decoration: none;
}

.up-item button:active,
.up-item a.export:active {
    transform: translateY(0);
}

.up-item a.export i {
    margin-right: 6px;
    font-size: 10px;
}

/* Mobile responsiveness */
@media (max-width: 480px) {
    .up-item a.export {
        min-width: 85px;
        padding: 6px 10px;
        font-size: 11px;
    }
    
    .up-item a.export i {
        margin-right: 4px;
        font-size: 9px;
    }
}

/* Common Section Styles */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: clamp(15px, 2.5vw, 20px);
    flex-wrap: wrap;
    gap: clamp(10px, 2vw, 15px);
}

.search-box {
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
    flex: 1;
    max-width: clamp(300px, 40vw, 400px);
}

.search-box input {
    flex: 1;
    padding: clamp(8px, 1.5vw, 10px) clamp(10px, 2vw, 15px);
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    font-size: clamp(12px, 2vw, 14px);
    background: var(--surface-color);
    color: var(--default-color);
}

.search-box button {
    background: var(--accent-color);
    color: var(--contrast-color);
    border: none;
    padding: clamp(8px, 1.5vw, 10px) clamp(10px, 2vw, 15px);
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s;
}

.search-box button:hover {
    background: var(--nav-hover-color);
}

.action-buttons {
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
}

.btn {
    padding: clamp(8px, 1.5vw, 10px) clamp(10px, 2vw, 15px);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: clamp(12px, 2vw, 14px);
    display: flex;
    align-items: center;
    gap: clamp(4px, 1vw, 5px);
    transition: all 0.3s;
}

.btn-primary {
    background: var(--accent-color);
    color: var(--contrast-color);
}

.btn-primary:hover {
    background: var(--nav-hover-color);
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-secondary:hover {
    background: #e2e8f0;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

/* Patient Details Section */
.patients-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(clamp(260px, 30vw, 300px), 1fr));
    gap: clamp(15px, 2.5vw, 20px);
    margin-bottom: clamp(20px, 3vw, 30px);
}

.patient-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.patient-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.patient-header {
    background: var(--teal);
    color: white;
    padding: clamp(10px, 2vw, 15px);
    display: flex;
    align-items: center;
    gap: clamp(8px, 1.5vw, 12px);
}

.patient-avatar {
    width: clamp(40px, 6vw, 50px);
    height: clamp(40px, 6vw, 50px);
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: clamp(16px, 2.5vw, 20px);
}

.patient-info {
    flex: 1;
}

.patient-name {
    font-weight: 600;
    font-size: clamp(14px, 2vw, 16px);
}

.patient-id {
    font-size: clamp(10px, 1.5vw, 12px);
    opacity: 0.8;
}

.patient-details {
    padding: clamp(10px, 2vw, 15px);
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: clamp(8px, 1.5vw, 10px);
    padding-bottom: clamp(8px, 1.5vw, 10px);
    border-bottom: 1px solid #f1f5f9;
}

.detail-label {
    font-weight: 500;
    color: #64748b;
    font-size: clamp(11px, 2vw, 13px);
}

.detail-value {
    font-weight: 600;
    color: #1e293b;
    font-size: clamp(11px, 2vw, 13px);
    text-align: right;
}

.patient-actions {
    padding: clamp(10px, 2vw, 15px);
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
}

.patient-actions button {
    flex: 1;
    padding: clamp(6px, 1vw, 8px) clamp(10px, 2vw, 12px);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: clamp(10px, 1.5vw, 12px);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: clamp(4px, 1vw, 5px);
}

.btn-view {
    background: #dbeafe;
    color: #1d4ed8;
}

.btn-view:hover {
    background: #bfdbfe;
}

.btn-edit {
    background: #fef3c7;
    color: #d97706;
}

.btn-edit:hover {
    background: #fde68a;
}

.btn-delete {
    background: #fee2e2;
    color: #dc2626;
}

.btn-delete:hover {
    background: #fecaca;
}

/* Patient Statistics */
.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(clamp(180px, 25vw, 200px), 1fr));
    gap: clamp(10px, 2vw, 15px);
    margin-bottom: clamp(20px, 3vw, 25px);
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: clamp(15px, 2.5vw, 20px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border-left: 4px solid var(--teal);
    text-align: center;
}

.stat-card h3 {
    font-size: clamp(12px, 2vw, 14px);
    color: #64748b;
    margin-bottom: clamp(8px, 1.5vw, 10px);
}

.stat-card .number {
    font-size: clamp(24px, 4vw, 28px);
    font-weight: 700;
    color: var(--teal);
}

/* Table View for Larger Screens */
.patients-table-container {
    overflow-x: auto;
    margin-bottom: clamp(20px, 3vw, 30px);
}

.patients-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.patients-table th {
    background: var(--teal);
    color: white;
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    text-align: left;
    font-weight: 600;
    font-size: clamp(12px, 2vw, 14px);
}

.patients-table td {
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    border-bottom: 1px solid #f1f5f9;
    font-size: clamp(12px, 2vw, 14px);
}

.patients-table tr:last-child td {
    border-bottom: none;
}

.patients-table tr:hover {
    background: #f8fafc;
}

.status-badge {
    padding: clamp(3px, 0.5vw, 4px) clamp(6px, 1vw, 8px);
    border-radius: 20px;
    font-size: clamp(10px, 1.5vw, 12px);
    font-weight: 600;
}

.status-fully {
    background: #dcfce7;
    color: #166534;
}

.status-partial {
    background: #fef3c7;
    color: #92400e;
}

.status-none {
    background: #fee2e2;
    color: #991b1b;
}

/* COVID-19 Reports Section */
.report-filter {
    background: white;
    border-radius: 12px;
    padding: clamp(15px, 2.5vw, 20px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    margin-bottom: clamp(15px, 2.5vw, 20px);
    border: 1px solid #e2e8f0;
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: clamp(10px, 2vw, 15px);
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: clamp(6px, 1vw, 8px);
    flex: 1;
    min-width: clamp(180px, 25vw, 200px);
}

.filter-group label {
    font-weight: 600;
    color: #374151;
    font-size: clamp(12px, 2vw, 14px);
}

.filter-group select,
.filter-group input {
    padding: clamp(8px, 1.5vw, 10px) clamp(10px, 2vw, 12px);
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: clamp(12px, 2vw, 14px);
    background: white;
}

.filter-actions {
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
}

.covid-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(clamp(180px, 25vw, 200px), 1fr));
    gap: clamp(10px, 2vw, 15px);
    margin-bottom: clamp(20px, 3vw, 25px);
}

.stat-box {
    background: white;
    border-radius: 10px;
    padding: clamp(15px, 2.5vw, 20px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    text-align: center;
    border-top: 4px solid var(--teal);
}

.stat-box.positive {
    border-top-color: #ef4444;
}

.stat-box.negative {
    border-top-color: #10b981;
}

.stat-box.pending {
    border-top-color: #f59e0b;
}

.stat-box .number {
    font-size: clamp(28px, 4vw, 32px);
    font-weight: 700;
    margin-bottom: clamp(4px, 1vw, 5px);
}

.stat-box.positive .number {
    color: #ef4444;
}

.stat-box.negative .number {
    color: #10b981;
}

.stat-box.pending .number {
    color: #f59e0b;
}

.stat-box .label {
    font-size: clamp(12px, 2vw, 14px);
    color: #6b7280;
}

.covid-table-container {
    overflow-x: auto;
    margin-bottom: clamp(20px, 3vw, 30px);
}

.covid-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.covid-table th {
    background: var(--teal);
    color: white;
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    text-align: left;
    font-weight: 600;
    font-size: clamp(12px, 2vw, 14px);
}

.covid-table td {
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    border-bottom: 1px solid #f1f5f9;
    font-size: clamp(12px, 2vw, 14px);
}

.covid-table tr:last-child td {
    border-bottom: none;
}

.covid-table tr:hover {
    background: #f8fafc;
}

.result-positive {
    color: #ef4444;
    font-weight: 600;
}

.result-negative {
    color: #10b981;
    font-weight: 600;
}

.result-pending {
    color: #f59e0b;
    font-weight: 600;
}

/* Vaccine List Section */
.vaccine-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(clamp(180px, 25vw, 200px), 1fr));
    gap: clamp(10px, 2vw, 15px);
    margin-bottom: clamp(20px, 3vw, 25px);
}

.vaccine-stat-box {
    background: var(--surface-color);
    border-radius: 10px;
    padding: clamp(15px, 2.5vw, 20px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    text-align: center;
    border-top: 4px solid var(--accent-color);
}

.vaccine-stat-box.available {
    border-top-color: #10b981;
}

.vaccine-stat-box.unavailable {
    border-top-color: #ef4444;
}

.vaccine-stat-box .number {
    font-size: clamp(28px, 4vw, 32px);
    font-weight: 700;
    margin-bottom: clamp(4px, 1vw, 5px);
}

.vaccine-stat-box.available .number {
    color: #10b981;
}

.vaccine-stat-box.unavailable .number {
    color: #ef4444;
}

.vaccine-stat-box .label {
    font-size: clamp(12px, 2vw, 14px);
    color: var(--muted);
}

/* Vaccine Cards Grid */
.vaccines-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(clamp(260px, 30vw, 300px), 1fr));
    gap: clamp(15px, 2.5vw, 20px);
    margin-bottom: clamp(20px, 3vw, 30px);
}

.vaccine-card {
    background: var(--surface-color);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.vaccine-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.vaccine-header {
    background: var(--accent-color);
    color: var(--contrast-color);
    padding: clamp(10px, 2vw, 15px);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.vaccine-name {
    font-weight: 600;
    font-size: clamp(14px, 2vw, 16px);
}

.vaccine-status {
    padding: clamp(3px, 0.5vw, 4px) clamp(6px, 1vw, 8px);
    border-radius: 20px;
    font-size: clamp(10px, 1.5vw, 12px);
    font-weight: 600;
}

.status-available {
    background: rgba(255, 255, 255, 0.2);
    color: var(--contrast-color);
}

.status-unavailable {
    background: #496268;
    color: #ffff;
}

.vaccine-details {
    padding: clamp(10px, 2vw, 15px);
}

.vaccine-actions {
    padding: clamp(10px, 2vw, 15px);
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
}

.vaccine-actions button {
    flex: 1;
    padding: clamp(6px, 1vw, 8px) clamp(10px, 2vw, 12px);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: clamp(10px, 1.5vw, 12px);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: clamp(4px, 1vw, 5px);
}

.btn-status {
    background: #dbeafe;
    color: #1d4ed8;
}

.btn-status:hover {
    background: #bfdbfe;
}

/* Vaccine Table View */
.vaccines-table-container {
    overflow-x: auto;
    margin-bottom: clamp(20px, 3vw, 30px);
}

.vaccines-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface-color);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.vaccines-table th {
    background: var(--accent-color);
    color: var(--contrast-color);
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    text-align: left;
    font-weight: 600;
    font-size: clamp(12px, 2vw, 14px);
}

.vaccines-table td {
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    border-bottom: 1px solid #f1f5f9;
    font-size: clamp(12px, 2vw, 14px);
}

.vaccines-table tr:last-child td {
    border-bottom: none;
}

.vaccines-table tr:hover {
    background: #f8fafc;
}

/* Hospital Approval Section Styles */
.approval-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(clamp(300px, 35vw, 350px), 1fr));
    gap: clamp(15px, 2.5vw, 20px);
    margin-bottom: clamp(20px, 3vw, 30px);
}

.approval-card {
    background: var(--surface-color);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.approval-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.approval-header {
    background: var(--accent-color);
    color: var(--contrast-color);
    padding: clamp(10px, 2vw, 15px);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.approval-name {
    font-weight: 600;
    font-size: clamp(14px, 2vw, 16px);
}

.approval-status {
    padding: clamp(3px, 0.5vw, 4px) clamp(6px, 1vw, 8px);
    border-radius: 20px;
    font-size: clamp(10px, 1.5vw, 12px);
    font-weight: 600;
}

.status-pending {
     background: #496268;
    color: #ffff;
}

.status-approved {
    background: #496268;
    color: #ffff;
}

.status-rejected {
     background: #496268;
    color: #ffff;
}

.approval-details {
    padding: clamp(10px, 2vw, 15px);
}

.approval-actions {
    padding: clamp(10px, 2vw, 15px);
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
}

.approval-form {
    flex: 1;
}

/* Approval Table Styles */
.approval-table-container {
    overflow-x: auto;
    margin-bottom: clamp(20px, 3vw, 30px);
}

.approval-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface-color);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.approval-table th {
    background: var(--accent-color);
    color: var(--contrast-color);
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    text-align: left;
    font-weight: 600;
    font-size: clamp(12px, 2vw, 14px);
}

.approval-table td {
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    border-bottom: 1px solid #f1f5f9;
    font-size: clamp(12px, 2vw, 14px);
}

.approval-table tr:last-child td {
    border-bottom: none;
}

.approval-table tr:hover {
    background: #f8fafc;
}

/* History Section Styles */
.history-section {
    margin-top: clamp(20px, 3vw, 30px);
}

.history-tabs {
    display: flex;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: clamp(15px, 2.5vw, 20px);
}

.history-tab {
    padding: clamp(10px, 1.5vw, 12px) clamp(15px, 2vw, 20px);
    background: none;
    border: none;
    cursor: pointer;
    font-size: clamp(12px, 2vw, 14px);
    font-weight: 500;
    color: var(--muted);
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
}

.history-tab.active {
    color: var(--accent-color);
    border-bottom-color: var(--accent-color);
}

.history-tab:hover {
    color: var(--accent-color);
}

.history-content {
    display: none;
}

.history-content.active {
    display: block;
}

/* Responsive Design */
@media (max-width: 768px) {
    .approval-grid {
        grid-template-columns: 1fr;
    }
    
    .approval-actions {
        flex-direction: column;
    }
    
    .history-tabs {
        flex-direction: column;
    }
    
    .history-tab {
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
    }
}

@media (max-width: 480px) {
    .approval-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}

/* Booking Details Section */
.booking-filter {
    background: white;
    border-radius: 12px;
    padding: clamp(15px, 2.5vw, 20px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    margin-bottom: clamp(15px, 2.5vw, 20px);
    border: 1px solid #e2e8f0;
}

.booking-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(clamp(180px, 25vw, 200px), 1fr));
    gap: clamp(10px, 2vw, 15px);
    margin-bottom: clamp(20px, 3vw, 25px);
}

.booking-stat-box {
    background: white;
    border-radius: 10px;
    padding: clamp(15px, 2.5vw, 20px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    text-align: center;
    border-left: 4px solid var(--teal);
}

.booking-stat-box.pending {
    border-left-color: #f59e0b;
}

.booking-stat-box.confirmed {
    border-left-color: #3b82f6;
}

.booking-stat-box.completed {
    border-left-color: #10b981;
}

.booking-stat-box.cancelled {
    border-left-color: #ef4444;
}

.booking-stat-box .number {
    font-size: clamp(28px, 4vw, 32px);
    font-weight: 700;
    margin-bottom: clamp(4px, 1vw, 5px);
}

.booking-stat-box.pending .number {
    color: #f59e0b;
}

.booking-stat-box.confirmed .number {
    color: #3b82f6;
}

.booking-stat-box.completed .number {
    color: #10b981;
}

.booking-stat-box.cancelled .number {
    color: #ef4444;
}

.booking-stat-box .label {
    font-size: clamp(12px, 2vw, 14px);
    color: #6b7280;
}

/* Booking Cards View */
.bookings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(clamp(300px, 35vw, 350px), 1fr));
    gap: clamp(15px, 2.5vw, 20px);
    margin-bottom: clamp(20px, 3vw, 30px);
}

.booking-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.booking-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.booking-header {
    background: var(--teal);
    color: white;
    padding: clamp(10px, 2vw, 15px);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.booking-id {
    font-weight: 600;
    font-size: clamp(14px, 2vw, 16px);
}

.booking-status {
    padding: clamp(3px, 0.5vw, 4px) clamp(6px, 1vw, 8px);
    border-radius: 20px;
    font-size: clamp(10px, 1.5vw, 12px);
    font-weight: 600;
}

.status-pending {
     background: #496268;
    color: #ffff;
}

.status-confirmed {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
}

.status-completed {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.booking-details {
    padding: clamp(10px, 2vw, 15px);
}

.booking-actions {
    padding: clamp(10px, 2vw, 15px);
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
}

.booking-actions button {
    flex: 1;
    padding: clamp(6px, 1vw, 8px) clamp(10px, 2vw, 12px);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: clamp(10px, 1.5vw, 12px);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: clamp(4px, 1vw, 5px);
}

/* Booking Table View */
.bookings-table-container {
    overflow-x: auto;
    margin-bottom: clamp(20px, 3vw, 30px);
}

.bookings-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.bookings-table th {
    background: var(--teal);
    color: white;
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    text-align: left;
    font-weight: 600;
    font-size: clamp(12px, 2vw, 14px);
}

.bookings-table td {
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    border-bottom: 1px solid #f1f5f9;
    font-size: clamp(12px, 2vw, 14px);
}

.bookings-table tr:last-child td {
    border-bottom: none;
}

.bookings-table tr:hover {
    background: #f8fafc;
}

/* Add Vaccine Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 2000;
}

.modal-content {
    background: var(--surface-color);
    border-radius: 10px;
    padding: clamp(20px, 3vw, 25px);
    width: clamp(300px, 90vw, 500px);
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: clamp(15px, 2.5vw, 20px);
}

.modal-header h3 {
    color: var(--heading-color);
    font-size: clamp(16px, 2.5vw, 20px);
}

.close-modal {
    background: none;
    border: none;
    font-size: clamp(20px, 3vw, 24px);
    cursor: pointer;
    color: var(--muted);
}

.form-group {
    margin-bottom: clamp(10px, 2vw, 15px);
}

.form-group label {
    display: block;
    margin-bottom: clamp(4px, 1vw, 5px);
    font-weight: 500;
    color: var(--default-color);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: clamp(8px, 1.5vw, 10px) clamp(10px, 2vw, 12px);
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: clamp(12px, 2vw, 14px);
    background: var(--surface-color);
    color: var(--default-color);
}

.form-group textarea {
    resize: vertical;
    min-height: clamp(60px, 10vw, 80px);
}

.form-actions {
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
    justify-content: flex-end;
    margin-top: clamp(15px, 2.5vw, 20px);
}

/* Error Message */
.error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: clamp(12px, 2vw, 16px);
    border-radius: 8px;
    margin-bottom: clamp(15px, 2.5vw, 20px);
    text-align: center;
}

.error-message i {
    font-size: clamp(20px, 3vw, 24px);
    margin-bottom: clamp(8px, 1.5vw, 10px);
    display: block;
}

/* No Data State */
.no-data {
    text-align: center;
    padding: clamp(30px, 5vw, 40px);
    color: var(--muted);
}

.no-data i {
    font-size: clamp(36px, 6vw, 48px);
    margin-bottom: clamp(10px, 2vw, 15px);
    color: #d1d5db;
}

/* Logout Popup Styling */
.popup-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 36, 0.6);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 2000;
    backdrop-filter: blur(3px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.popup-box {
    background: linear-gradient(180deg, var(--surface-color), #f8fafa);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: clamp(20px, 3vw, 28px) clamp(24px, 4vw, 32px);
    text-align: center;
    width: clamp(280px, 80vw, 340px);
    max-width: 90%;
    animation: slideUp 0.35s ease;
}

@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.popup-box h3 {
    color: var(--heading-color);
    font-size: clamp(16px, 2.5vw, 20px);
    margin-bottom: clamp(8px, 1.5vw, 10px);
}

.popup-box p {
    color: var(--muted);
    font-size: clamp(12px, 2vw, 14px);
    margin-bottom: clamp(15px, 2.5vw, 20px);
}

.popup-actions {
    display: flex;
    justify-content: center;
    gap: clamp(10px, 1.5vw, 12px);
}

.popup-actions button {
    border: none;
    padding: clamp(8px, 1.5vw, 10px) clamp(18px, 3vw, 22px);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.25s ease, transform 0.2s;
}

.cancel-btn {
    background: var(--nav-color);
    color: var(--contrast-color);
    border: 1px solid var(--nav-color);
}

.cancel-btn:hover {
    background: var(--nav-color);
    color: var(--contrast-color);
    transform: scale(1.05);
}

.confirm-btn {
    background: var(--accent-color);
    color: var(--contrast-color);
    border: 1px solid var(--accent-color);
}

.confirm-btn:hover {
    background: var(--nav-hover-color);
    color: var(--contrast-color);
    transform: scale(1.05);
}

/* Mobile overlay for sidebar */
.mobile-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

.mobile-overlay.active {
    display: block;
}

/* Delete Confirmation Popup */
.delete-popup {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 2000;
}

.delete-popup-content {
    background: var(--surface-color);
    border-radius: 10px;
    padding: clamp(20px, 3vw, 25px);
    width: clamp(300px, 80vw, 400px);
    max-width: 90%;
    text-align: center;
}

.delete-popup h3 {
    margin-bottom: clamp(8px, 1.5vw, 10px);
    color: var(--heading-color);
}

.delete-popup p {
    margin-bottom: clamp(15px, 2.5vw, 20px);
    color: var(--muted);
}

.delete-popup-actions {
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
    justify-content: center;
}

.delete-popup-actions button {
    padding: clamp(8px, 1.5vw, 10px) clamp(15px, 2.5vw, 20px);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-cancel {
    background: #f1f5f9;
    color: #475569;
}

.btn-cancel:hover {
    background: #e2e8f0;
}

.btn-confirm-delete {
    background: #ef4444;
    color: white;
}

.btn-confirm-delete:hover {
    background: #dc2626;
}

/* Enhanced Responsive Design */
@media (max-width: 1400px) {
    .grid {
        grid-template-columns: 1fr;
        gap: clamp(10px, 2vw, 16px);
    }
    .right-column {
        order: -1;
    }
}

@media (max-width: 1024px) {
    .cards {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
    .chart-grid {
        grid-template-columns: 1fr;
    }
    .chart-item {
        height: clamp(160px, 25vw, 220px);
    }
    .patients-grid {
        grid-template-columns: repeat(auto-fill, minmax(clamp(240px, 28vw, 280px), 1fr));
    }
    .vaccines-grid {
        grid-template-columns: repeat(auto-fill, minmax(clamp(240px, 28vw, 280px), 1fr));
    }
    .hospitals-grid {
        grid-template-columns: repeat(auto-fill, minmax(clamp(240px, 28vw, 280px), 1fr));
    }
    .bookings-grid {
        grid-template-columns: repeat(auto-fill, minmax(clamp(280px, 32vw, 320px), 1fr));
    }
}

@media (max-width: 900px) {
    .sidebar {
        transform: translateX(-100%);
        width: clamp(260px, 70vw, 280px);
        z-index: 1000;
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .hamburger {
        display: inline-flex;
    }
    .main {
        margin: 0;
        border-radius: 0;
        min-height: 100vh;
        width: 100%;
    }
    .topbar .left {
        flex: 1;
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .content {
        padding: clamp(12px, 2vw, 16px);
    }
    .cards {
        grid-template-columns: 1fr;
        gap: clamp(8px, 1.5vw, 10px);
    }
    .card {
        padding: clamp(10px, 1.5vw, 14px);
    }
    .card p {
        font-size: clamp(12px, 2vw, 16px);
    }
    .stats-grid {
        grid-template-columns: 1fr;
        gap: clamp(8px, 1.5vw, 10px);
    }
    .chart-container {
        padding: clamp(10px, 1.5vw, 14px);
    }
    .chart-item {
        height: clamp(140px, 25vw, 200px);
    }
    .panel {
        padding: clamp(10px, 1.5vw, 14px);
    }
    .topbar {
        padding: clamp(10px, 1.5vw, 14px) clamp(12px, 2vw, 16px);
    }
    .topbar h2 {
        font-size: clamp(14px, 2vw, 18px);
    }
    .profile {
        font-size: clamp(12px, 2vw, 14px);
    }
    .avatar {
        width: clamp(26px, 4vw, 32px);
        height: clamp(26px, 4vw, 32px);
    }
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .search-box {
        max-width: 100%;
        width: 100%;
    }
    .patients-grid {
        grid-template-columns: 1fr;
    }
    .stats-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-group {
        min-width: 100%;
    }
    .covid-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .vaccines-grid {
        grid-template-columns: 1fr;
    }
    .vaccine-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .vaccine-actions {
        flex-direction: column;
    }
    .approval-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .request-actions {
        flex-direction: column;
    }
    .hospitals-grid {
        grid-template-columns: 1fr;
    }
    .hospital-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .hospital-actions {
        flex-direction: column;
    }
    .bookings-grid {
        grid-template-columns: 1fr;
    }
    .booking-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .booking-actions {
        flex-direction: column;
    }
}

@media (max-width: 600px) {
    .up-item {
        flex-direction: column;
        align-items: flex-start;
        gap: clamp(8px, 1.5vw, 10px);
    }
    .up-item .button-group {
        flex-direction: column;
        width: 100%;
        gap: clamp(4px, 1vw, 6px);
    }
    .up-item button {
        width: 100%;
        min-width: 0;
        padding: clamp(6px, 1vw, 8px) 0;
    }
    .sidebar {
        width: 100%;
        max-width: clamp(240px, 80vw, 280px);
    }
    .nav a {
        font-size: clamp(11px, 2vw, 13px);
        padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 14px);
    }
    .nav a .ico {
        font-size: clamp(12px, 2vw, 14px);
    }
    .brand .logo {
        width: clamp(80px, 20vw, 120px);
        height: clamp(80px, 20vw, 120px);
    }
    .grid {
        gap: clamp(8px, 1.5vw, 12px);
    }
    .stats-cards {
        grid-template-columns: 1fr;
    }
    .patient-actions {
        flex-direction: column;
    }
    .covid-stats {
        grid-template-columns: 1fr;
    }
    .vaccine-stats {
        grid-template-columns: 1fr;
    }
    .action-buttons {
        flex-direction: column;
        width: 100%;
    }
    .action-buttons .btn {
        width: 100%;
        justify-content: center;
    }
    .approval-stats {
        grid-template-columns: 1fr;
    }
    .history-tabs {
        flex-direction: column;
    }
    .history-tab {
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
    }
    .hospital-stats {
        grid-template-columns: 1fr;
    }
    .booking-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .content {
        padding: clamp(8px, 1.5vw, 12px);
    }
    .card {
        padding: clamp(8px, 1.5vw, 12px);
    }
    .chart-container {
        padding: clamp(8px, 1.5vw, 12px);
    }
    .chart-item {
        height: clamp(120px, 20vw, 180px);
    }
    .panel {
        padding: clamp(8px, 1.5vw, 12px);
    }
    .topbar {
        padding: clamp(8px, 1.5vw, 12px) clamp(10px, 2vw, 14px);
    }
    .topbar h2 {
        font-size: clamp(13px, 2vw, 16px);
    }
    .profile {
        gap: clamp(6px, 1vw, 8px);
    }
    .profile div {
        font-size: clamp(11px, 2vw, 13px);
    }
    .avatar {
        width: clamp(24px, 4vw, 28px);
        height: clamp(24px, 4vw, 28px);
    }
    .hamburger {
        padding: clamp(4px, 1vw, 6px);
        font-size: clamp(14px, 2vw, 16px);
    }
    .section-header h3 {
        font-size: clamp(16px, 2.5vw, 18px);
    }
    .patient-header {
        flex-direction: column;
        text-align: center;
    }
    .report-filter {
        padding: clamp(10px, 2vw, 15px);
    }
    .filter-actions {
        flex-direction: column;
        width: 100%;
    }
    .filter-actions .btn {
        width: 100%;
        justify-content: center;
    }
    .vaccine-header {
        flex-direction: column;
        text-align: center;
        gap: clamp(8px, 1.5vw, 10px);
    }
    .modal-content {
        padding: clamp(15px, 2.5vw, 20px);
    }
    .request-header {
        flex-direction: column;
        text-align: center;
        gap: clamp(8px, 1.5vw, 10px);
    }
    .hospital-header {
        flex-direction: column;
        text-align: center;
        gap: clamp(8px, 1.5vw, 10px);
    }
    .booking-filter {
        padding: clamp(10px, 2vw, 15px);
    }
    .booking-header {
        flex-direction: column;
        text-align: center;
        gap: clamp(8px, 1.5vw, 10px);
    }
}

@media (max-width: 360px) {
    .content {
        padding: clamp(6px, 1vw, 10px);
    }
    .card {
        padding: clamp(6px, 1vw, 10px);
    }
    .chart-container {
        padding: clamp(6px, 1vw, 10px);
    }
    .panel {
        padding: clamp(6px, 1vw, 10px);
    }
    .topbar {
        padding: clamp(6px, 1vw, 10px) clamp(8px, 1.5vw, 12px);
    }
    .topbar h2 {
        font-size: clamp(12px, 2vw, 15px);
    }
    .profile div {
        font-size: clamp(10px, 1.5vw, 12px);
    }
}



/* Enhanced Action Buttons for Tables */
.hospitals-table .btn-status,
.hospitals-table .btn-edit,
.hospitals-table .btn-delete,
.vaccines-table .btn-status,
.vaccines-table .btn-edit,
.vaccines-table .btn-delete {
    min-width: auto;
    padding: clamp(6px, 1vw, 8px) clamp(8px, 1.5vw, 12px);
    font-size: clamp(10px, 1.5vw, 12px);
    margin: 2px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Button Colors */
.hospitals-table .btn-status,
.vaccines-table .btn-status {
    background: #dbeafe;
    color: #1d4ed8;
}

.hospitals-table .btn-edit,
.vaccines-table .btn-edit {
    background: #fef3c7;
    color: #d97706;
}

.hospitals-table .btn-delete,
.vaccines-table .btn-delete {
    background: #fee2e2;
    color: #dc2626;
}

/* Hover Effects */
.hospitals-table .btn-status:hover,
.vaccines-table .btn-status:hover {
    background: #bfdbfe;
    transform: translateY(-1px);
}

.hospitals-table .btn-edit:hover,
.vaccines-table .btn-edit:hover {
    background: #fde68a;
    transform: translateY(-1px);
}

.hospitals-table .btn-delete:hover,
.vaccines-table .btn-delete:hover {
    background: #fecaca;
    transform: translateY(-1px);
}

/* Table Action Cell Styling */
.hospitals-table td:last-child,
.vaccines-table td:last-child {
    padding: clamp(8px, 1.5vw, 12px) clamp(6px, 1vw, 8px);
}

.hospitals-table td:last-child > div,
.vaccines-table td:last-child > div {
    display: flex;
    flex-wrap: wrap;
    gap: clamp(3px, 0.5vw, 5px);
    justify-content: flex-start;
    align-items: center;
}

/* Card View Action Buttons */
.hospital-actions,
.vaccine-actions {
    padding: clamp(8px, 1.5vw, 12px);
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: clamp(6px, 1vw, 8px);
    flex-wrap: wrap;
}

.hospital-actions button,
.vaccine-actions button {
    flex: 1;
    min-width: 0;
    padding: clamp(6px, 1vw, 8px) clamp(8px, 1.5vw, 12px);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: clamp(10px, 1.5vw, 12px);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: clamp(3px, 0.5vw, 4px);
    white-space: nowrap;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .hospitals-table td:last-child > div,
    .vaccines-table td:last-child > div {
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
    }
    
    .hospitals-table .btn-status,
    .hospitals-table .btn-edit,
    .hospitals-table .btn-delete,
    .vaccines-table .btn-status,
    .vaccines-table .btn-edit,
    .vaccines-table .btn-delete {
        width: 100%;
        margin: 0;
        justify-content: center;
    }
    
    .hospital-actions,
    .vaccine-actions {
        flex-direction: column;
    }
    
    .hospital-actions button,
    .vaccine-actions button {
        width: 100%;
        margin-bottom: 4px;
    }
}

@media (max-width: 480px) {
    .hospitals-table,
    .vaccines-table {
        font-size: 12px;
    }
    
    .hospitals-table .btn-status,
    .hospitals-table .btn-edit,
    .hospitals-table .btn-delete,
    .vaccines-table .btn-status,
    .vaccines-table .btn-edit,
    .vaccines-table .btn-delete {
        padding: 5px 8px;
        font-size: 10px;
    }
    
    .hospital-actions button,
    .vaccine-actions button {
        padding: 6px 8px;
        font-size: 11px;
    }
}

/* Ensure table responsiveness */
.hospitals-table-container,
.vaccines-table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Hide text on very small screens, show only icons */
@media (max-width: 360px) {
    .hospitals-table .btn-status span,
    .hospitals-table .btn-edit span,
    .hospitals-table .btn-delete span,
    .vaccines-table .btn-status span,
    .vaccines-table .btn-edit span,
    .vaccines-table .btn-delete span,
    .hospital-actions button span,
    .vaccine-actions button span {
        display: none;
    }
    
    .hospitals-table .btn-status,
    .hospitals-table .btn-edit,
    .hospitals-table .btn-delete,
    .vaccines-table .btn-status,
    .vaccines-table .btn-edit,
    .vaccines-table .btn-delete,
    .hospital-actions button,
    .vaccine-actions button {
        min-width: 32px;
        padding: 6px;
    }
    
    .hospitals-table td:last-child > div,
    .vaccines-table td:last-child > div {
        gap: 2px;
    }
}

/* Button focus states for accessibility */
.hospitals-table .btn-status:focus,
.hospitals-table .btn-edit:focus,
.hospitals-table .btn-delete:focus,
.vaccines-table .btn-status:focus,
.vaccines-table .btn-edit:focus,
.vaccines-table .btn-delete:focus,
.hospital-actions button:focus,
.vaccine-actions button:focus {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}



/* Patient Action Buttons */
.patient-actions {
    padding: clamp(10px, 2vw, 15px);
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
    flex-wrap: wrap;
}

.patient-actions button {
    flex: 1;
    min-width: 0;
    padding: clamp(6px, 1vw, 8px) clamp(10px, 2vw, 12px);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: clamp(10px, 1.5vw, 12px);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: clamp(4px, 1vw, 5px);
    white-space: nowrap;
}

.btn-view {
    background: #dbeafe;
    color: #1d4ed8;
}

.btn-view:hover {
    background: #bfdbfe;
    transform: translateY(-1px);
}

.btn-edit {
    background: #fef3c7;
    color: #d97706;
}

.btn-edit:hover {
    background: #fde68a;
    transform: translateY(-1px);
}

.btn-delete {
    background: #fee2e2;
    color: #dc2626;
}

.btn-delete:hover {
    background: #fecaca;
    transform: translateY(-1px);
}

/* Table Action Buttons */
.patients-table .btn-view,
.patients-table .btn-edit,
.patients-table .btn-delete {
    min-width: auto;
    padding: clamp(6px, 1vw, 8px) clamp(8px, 1.5vw, 12px);
    font-size: clamp(10px, 1.5vw, 12px);
    margin: 2px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .patient-actions {
        flex-direction: column;
    }
    
    .patient-actions button {
        width: 100%;
        margin-bottom: 4px;
    }
    
    .patients-table td:last-child > div {
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
    }
    
    .patients-table .btn-view,
    .patients-table .btn-edit,
    .patients-table .btn-delete {
        width: 100%;
        margin: 0;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .patient-actions button {
        padding: 6px 8px;
        font-size: 11px;
    }
    
    .patients-table .btn-view,
    .patients-table .btn-edit,
    .patients-table .btn-delete {
        padding: 5px 8px;
        font-size: 10px;
    }
}

/* Hide text on very small screens, show only icons */
@media (max-width: 360px) {
    .patient-actions button span {
        display: none;
    }
    
    .patient-actions button {
        min-width: 32px;
        padding: 6px;
    }
    
    .patients-table .btn-view span,
    .patients-table .btn-edit span,
    .patients-table .btn-delete span {
        display: none;
    }
    
    .patients-table .btn-view,
    .patients-table .btn-edit,
    .patients-table .btn-delete {
        min-width: 32px;
        padding: 6px;
    }
}


/* Admin Management Action Buttons */
.patients-table .btn-status,
.patients-table .btn-delete {
    min-width: auto;
    padding: clamp(6px, 1vw, 8px) clamp(8px, 1.5vw, 12px);
    font-size: clamp(10px, 1.5vw, 12px);
    margin: 2px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    white-space: nowrap;
    flex-shrink: 0;
    font-weight: 500;
}

/* Reset Password Button */
.btn-status {
    background: #dbeafe;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

.btn-status:hover {
    background: #bfdbfe;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

/* Delete Button */
.btn-delete {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.btn-delete:hover:not(:disabled) {
    background: #fecaca;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
}

/* Disabled Delete Button */
.btn-delete:disabled {
    background: #f3f4f6;
    color: #9ca3af;
    border: 1px solid #e5e7eb;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Action Buttons Container */
.patients-table td:last-child > div {
    display: flex;
    gap: 5px;
    justify-content: center;
    align-items: center;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .patients-table td:last-child > div {
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
    }
    
    .patients-table .btn-status,
    .patients-table .btn-delete {
        width: 100%;
        margin: 0;
        justify-content: center;
        padding: 8px 10px;
    }
}

@media (max-width: 480px) {
    .patients-table .btn-status,
    .patients-table .btn-delete {
        padding: 6px 8px;
        font-size: 11px;
    }
}

/* Hide text on very small screens, show only icons */
@media (max-width: 360px) {
    .patients-table .btn-status span,
    .patients-table .btn-delete span {
        display: none;
    }
    
    .patients-table .btn-status,
    .patients-table .btn-delete {
        min-width: 32px;
        padding: 6px;
        border-radius: 50%;
        aspect-ratio: 1;
    }
}

.chart-item {
    position: relative;
    height: 250px;
    width: 100%;
}

.chart-item canvas {
    display: block;
    width: 100% !important;
    height: 100% !important;
}

/* Ensure right column panels have proper spacing */
.right-column .panel {
    margin-bottom: 20px;
}

.right-column .panel:last-child {
    margin-bottom: 0;
}

.cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--teal);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(2,6,23,0.12);
        }

        .card h3 {
            font-size: 14px;
            color: #0b1720;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card .icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .card .icon.booking {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }

        .card .icon.approval {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }

        .card .icon.hospital {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .card .icon.vaccine {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
        }

        .card p {
            color: var(--muted);
            font-size: 14px;
            margin-top: 8px;
        }

        .card .number {
            font-size: 30px;
            font-weight: 700;
            color: var(--teal);
            margin-top: 8px;
        }

        .card .trend {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .card .trend.up {
            color: #10b981;
        }

        .card .trend.down {
            color: #ef4444;
        }

        @media (max-width: 768px) {
            .cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .card {
                padding: 20px;
            }
            
            .card .number {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .cards {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header h1 {
                font-size: 24px;
            }
        }

        .card p {
    color: var(--muted);
    font-size: clamp(14px, 2.5vw, 18px);
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

.card p:first-of-type {
    font-size: clamp(24px, 4vw, 32px);
    font-weight: 800;
    color: var(--teal);
    margin-bottom: 4px;
}

.card p:last-of-type {
    font-size: clamp(12px, 2vw, 14px);
    font-weight: 500;
    color: var(--muted);
    margin-top: 4px;
}


.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: clamp(8px, 1.5vw, 12px);
}

.card {
    background: var(--card-bg);
    border-radius: var(--radius);
    padding: clamp(12px, 2vw, 16px);
    box-shadow: 0 6px 14px rgba(12,20,25,0.04);
    border: 1px solid #f1f5f7;
    border-left: 4px solid var(--teal);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(12,20,25,0.1);
}

.card h3 {
    font-size: clamp(12px, 2vw, 14px);
    color: #0b1720;
    margin-bottom: clamp(4px, 1vw, 6px);
    display: flex;
    align-items: center;
    gap: 8px;
}

.card h3 i {
    font-size: clamp(14px, 2.5vw, 16px);
}

/* Patient Details Modal Styling */
.patient-details-modal {
    padding: 10px 0;
}

.detail-section {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e2e8f0;
}

.detail-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-section h4 {
    color: var(--heading-color);
    margin-bottom: 12px;
    font-size: 16px;
    font-weight: 600;
}

.detail-section .detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 8px 0;
}

.detail-section .detail-label {
    font-weight: 500;
    color: #64748b;
    flex: 1;
}

.detail-section .detail-value {
    font-weight: 600;
    color: #1e293b;
    flex: 2;
    text-align: right;
}

/* Status badges in modal */
.detail-section .status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

/* Responsive modal adjustments */
@media (max-width: 768px) {
    .detail-section .detail-row {
        flex-direction: column;
        gap: 4px;
    }
    
    .detail-section .detail-value {
        text-align: left;
    }
}

/* Error message styling */
.error-message {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
    display: none;
}

/* Invalid input styling */
.form-group input:invalid, 
.form-group select:invalid,
.form-group textarea:invalid {
    border-color: #dc2626;
}

.form-group input:valid, 
.form-group select:valid,
.form-group textarea:valid {
    border-color: #10b981;
}

/* Button disabled state */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Add CSS to show required field indicators */
.form-group label[for*="patient_gender"]::after,
.form-group label[for*="patient_address"]::after {
    content: " ";
    color: #ef4444;
}

/* Style for required fields */
.form-group input:required,
.form-group select:required,
.form-group textarea:required {
    border-left: 3px solid #ef4444;
}

.form-group input:required:valid,
.form-group select:required:valid,
.form-group textarea:required:valid {
    border-left: 3px solid #10b981;
}

/* Add CSS to show required field indicators */
.form-group label[for*="patient_name"]::after,
.form-group label[for*="patient_email"]::after,
.form-group label[for*="patient_phone"]::after,
.form-group label[for*="patient_gender"]::after,
.form-group label[for*="patient_dob"]::after,
.form-group label[for*="patient_address"]::after {
    content: " *";
    color: #ef4444;
}

/* Style for required fields */
.form-group input:required,
.form-group select:required,
.form-group textarea:required {
    border-left: 3px solid #ef4444;
}

.form-group input:required:valid,
.form-group select:required:valid,
.form-group textarea:required:valid {
    border-left: 3px solid #10b981;
}

/* Vaccine List Section Styles */
.vaccine-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(clamp(180px, 25vw, 200px), 1fr));
    gap: clamp(10px, 2vw, 15px);
    margin-bottom: clamp(20px, 3vw, 25px);
}

.vaccine-stat-box {
    background: var(--surface-color);
    border-radius: 10px;
    padding: clamp(15px, 2.5vw, 20px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    text-align: center;
    border-left: 4px solid var(--accent-color);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.vaccine-stat-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(12,20,25,0.1);
}

.vaccine-stat-box.available {
    border-left-color: #10b981;
}

.vaccine-stat-box.unavailable {
    border-left-color: #ef4444;
}

.vaccine-stat-box .number {
    font-size: clamp(28px, 4vw, 32px);
    font-weight: 700;
    margin-bottom: clamp(4px, 1vw, 5px);
}

.vaccine-stat-box.available .number {
    color: #10b981;
}

.vaccine-stat-box.unavailable .number {
    color: #ef4444;
}

.vaccine-stat-box .label {
    font-size: clamp(12px, 2vw, 14px);
    color: var(--muted);
}

/* Vaccine Cards Grid */
.vaccines-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(clamp(280px, 30vw, 320px), 1fr));
    gap: clamp(15px, 2.5vw, 20px);
    margin-bottom: clamp(20px, 3vw, 30px);
}

.vaccine-card {
    background: var(--surface-color);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.vaccine-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.vaccine-header {
    background: var(--accent-color);
    color: var(--contrast-color);
    padding: clamp(10px, 2vw, 15px);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.vaccine-name {
    font-weight: 600;
    font-size: clamp(14px, 2vw, 16px);
}

.vaccine-status {
    padding: clamp(3px, 0.5vw, 4px) clamp(6px, 1vw, 8px);
    border-radius: 20px;
    font-size: clamp(10px, 1.5vw, 12px);
    font-weight: 600;
}

.status-available {
    background: rgba(255, 255, 255, 0.2);
    color: var(--contrast-color);
}

.status-unavailable {
    background: #496268;
    color: #ffff;
}

.vaccine-details {
    padding: clamp(10px, 2vw, 15px);
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: clamp(8px, 1.5vw, 10px);
    padding-bottom: clamp(8px, 1.5vw, 10px);
    border-bottom: 1px solid #f1f5f9;
}

.detail-label {
    font-weight: 500;
    color: #64748b;
    font-size: clamp(11px, 2vw, 13px);
}

.detail-value {
    font-weight: 600;
    color: var(--default-color);
    font-size: clamp(11px, 2vw, 13px);
    text-align: right;
}

.vaccine-actions {
    padding: clamp(10px, 2vw, 15px);
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
}

.vaccine-actions button {
    flex: 1;
    padding: clamp(6px, 1vw, 8px) clamp(10px, 2vw, 12px);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: clamp(10px, 1.5vw, 12px);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: clamp(4px, 1vw, 5px);
}

.btn-view {
    background: #dbeafe;
    color: #1d4ed8;
}

.btn-view:hover {
    background: #bfdbfe;
    transform: translateY(-1px);
}

.btn-edit {
    background: #fef3c7;
    color: #d97706;
}

.btn-edit:hover {
    background: #fde68a;
    transform: translateY(-1px);
}

.btn-status {
    background: #d1fae5;
    color: #065f46;
}

.btn-status:hover {
    background: #a7f3d0;
    transform: translateY(-1px);
}

.btn-delete {
    background: #fee2e2;
    color: #dc2626;
}

.btn-delete:hover {
    background: #fecaca;
    transform: translateY(-1px);
}

/* Vaccine Table View */
.vaccines-table-container {
    overflow-x: auto;
    margin-bottom: clamp(20px, 3vw, 30px);
}

.vaccines-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface-color);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.vaccines-table th {
    background: var(--accent-color);
    color: var(--contrast-color);
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    text-align: left;
    font-weight: 600;
    font-size: clamp(12px, 2vw, 14px);
}

.vaccines-table td {
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    border-bottom: 1px solid #f1f5f9;
    font-size: clamp(12px, 2vw, 14px);
}

.vaccines-table tr:last-child td {
    border-bottom: none;
}

.vaccines-table tr:hover {
    background: #f8fafc;
}

/* View Toggle */
.view-toggle {
    display: flex;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: clamp(15px, 2.5vw, 20px);
    width: fit-content;
}

.view-option {
    padding: clamp(6px, 1vw, 8px) clamp(12px, 2vw, 16px);
    background-color: var(--surface-color);
    cursor: pointer;
    transition: background-color 0.3s;
    border: none;
    font-size: clamp(12px, 2vw, 14px);
}

.view-option.active {
    background-color: var(--accent-color);
    color: var(--contrast-color);
}

/* Vaccine Modal Styles */
.vaccine-details-modal {
    padding: 10px 0;
}

.detail-section {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e2e8f0;
}

.detail-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-section h4 {
    color: var(--heading-color);
    margin-bottom: 12px;
    font-size: 16px;
    font-weight: 600;
}

.detail-section .detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 8px 0;
}

.detail-section .detail-label {
    font-weight: 500;
    color: #64748b;
    flex: 1;
}

.detail-section .detail-value {
    font-weight: 600;
    color: #1e293b;
    flex: 2;
    text-align: right;
}

/* Status Update Modal */
.status-update-modal .modal-content {
    text-align: center;
}

.status-update-modal .status-options {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin: 20px 0;
}

.status-option {
    padding: 15px 25px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
    min-width: 120px;
}

.status-option:hover {
    border-color: var(--accent-color);
}

.status-option.active {
    border-color: var(--accent-color);
    background: rgba(4, 158, 187, 0.1);
}

.status-option.available.active {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.1);
}

.status-option.unavailable.active {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
}

.status-icon {
    font-size: 24px;
    margin-bottom: 8px;
}

.status-option.available .status-icon {
    color: #10b981;
}

.status-option.unavailable .status-icon {
    color: #ef4444;
}

/* Form validation styles */
.form-group label[for*="vaccine_name"]::after,
.form-group label[for*="vaccine_description"]::after,
.form-group label[for*="vaccine_status"]::after,
.form-group label[for*="doses_required"]::after,
.form-group label[for*="storage_temp"]::after,
.form-group label[for*="efficacy_rate"]::after {
    content: " *";
    color: #ef4444;
}

.form-group input:required,
.form-group select:required,
.form-group textarea:required {
    border-left: 3px solid #ef4444;
}

.form-group input:required:valid,
.form-group select:required:valid,
.form-group textarea:required:valid {
    border-left: 3px solid #10b981;
}

/* Responsive design */
@media (max-width: 768px) {
    .vaccines-grid {
        grid-template-columns: 1fr;
    }
    
    .vaccine-actions {
        flex-direction: column;
    }
    
    .vaccine-stat-box {
        padding: 15px;
    }
    
    .status-options {
        flex-direction: column;
    }
    
    .status-option {
        min-width: auto;
    }
}

@media (max-width: 480px) {
    .vaccine-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .vaccine-details .detail-row {
        flex-direction: column;
        gap: 4px;
    }
    
    .vaccine-details .detail-value {
        text-align: left;
    }
}

/* Vaccine Cards Grid */
.vaccines-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(clamp(280px, 30vw, 320px), 1fr));
    gap: clamp(15px, 2.5vw, 20px);
    margin-bottom: clamp(20px, 3vw, 30px);
}

.vaccine-card {
    background: var(--surface-color);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.vaccine-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.vaccine-header {
    background: var(--accent-color);
    color: var(--contrast-color);
    padding: clamp(10px, 2vw, 15px);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.vaccine-name {
    font-weight: 600;
    font-size: clamp(14px, 2vw, 16px);
}

.vaccine-status {
    padding: clamp(3px, 0.5vw, 4px) clamp(6px, 1vw, 8px);
    border-radius: 20px;
    font-size: clamp(10px, 1.5vw, 12px);
    font-weight: 600;
}

.status-available {
    background: rgba(255, 255, 255, 1);
    color: var(--heading-color);
}

.status-unavailable {
    background: rgba(255, 255, 255, 1);
    color: var(--heading-color);
}

.vaccine-details {
    padding: clamp(10px, 2vw, 15px);
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: clamp(8px, 1.5vw, 10px);
    padding-bottom: clamp(8px, 1.5vw, 10px);
    border-bottom: 1px solid #f1f5f9;
}

.detail-label {
    font-weight: 500;
    color: #64748b;
    font-size: clamp(11px, 2vw, 13px);
}

.detail-value {
    font-weight: 600;
    color: var(--default-color);
    font-size: clamp(11px, 2vw, 13px);
    text-align: right;
}

.vaccine-actions {
    padding: clamp(10px, 2vw, 15px);
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: clamp(8px, 1.5vw, 10px);
}

.vaccine-actions button {
    flex: 1;
    padding: clamp(6px, 1vw, 8px) clamp(10px, 2vw, 12px);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: clamp(10px, 1.5vw, 12px);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: clamp(4px, 1vw, 5px);
}

.btn-view {
    background: #dbeafe;
    color: #1d4ed8;
}

.btn-view:hover {
    background: #bfdbfe;
    transform: translateY(-1px);
}

.btn-edit {
    background: #fef3c7;
    color: #d97706;
}

.btn-edit:hover {
    background: #fde68a;
    transform: translateY(-1px);
}

.btn-status {
    background: #d1fae5;
    color: #065f46;
}

.btn-status:hover {
    background: #a7f3d0;
    transform: translateY(-1px);
}

.btn-delete {
    background: #fee2e2;
    color: #dc2626;
}

.btn-delete:hover {
    background: #fecaca;
    transform: translateY(-1px);
}

/* Vaccine Table View */
.vaccines-table-container {
    overflow-x: auto;
    margin-bottom: clamp(20px, 3vw, 30px);
}

.vaccines-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface-color);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.vaccines-table th {
    background: var(--accent-color);
    color: var(--contrast-color);
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    text-align: left;
    font-weight: 600;
    font-size: clamp(12px, 2vw, 14px);
}

.vaccines-table td {
    padding: clamp(10px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
    border-bottom: 1px solid #f1f5f9;
    font-size: clamp(12px, 2vw, 14px);
}

.vaccines-table tr:last-child td {
    border-bottom: none;
}

.vaccines-table tr:hover {
    background: #f8fafc;
}

/* View Toggle */
.view-toggle {
    display: flex;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: clamp(15px, 2.5vw, 20px);
    width: fit-content;
}

.view-option {
    padding: clamp(6px, 1vw, 8px) clamp(12px, 2vw, 16px);
    background-color: var(--surface-color);
    cursor: pointer;
    transition: background-color 0.3s;
    border: none;
    font-size: clamp(12px, 2vw, 14px);
}

.view-option.active {
    background-color: var(--accent-color);
    color: var(--contrast-color);
}

/* Vaccine Modal Styles */
.vaccine-details-modal {
    padding: 10px 0;
}

.detail-section {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e2e8f0;
}

.detail-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-section h4 {
    color: var(--heading-color);
    margin-bottom: 12px;
    font-size: 16px;
    font-weight: 600;
}

.detail-section .detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 8px 0;
}

.detail-section .detail-label {
    font-weight: 500;
    color: #64748b;
    flex: 1;
}

.detail-section .detail-value {
    font-weight: 600;
    color: #1e293b;
    flex: 2;
    text-align: right;
}

/* Form validation styles */
.form-group label[for*="vaccine_name"]::after,
.form-group label[for*="vaccine_description"]::after,
.form-group label[for*="vaccine_status"]::after,
.form-group label[for*="doses_required"]::after,
.form-group label[for*="storage_temp"]::after,
.form-group label[for*="efficacy_rate"]::after {
    content: " *";
    color: #ef4444;
}

.form-group input:required,
.form-group select:required,
.form-group textarea:required {
    border-left: 3px solid #ef4444;
}

.form-group input:required:valid,
.form-group select:required:valid,
.form-group textarea:required:valid {
    border-left: 3px solid #10b981;
}

/* Responsive design */
@media (max-width: 768px) {
    .vaccines-grid {
        grid-template-columns: 1fr;
    }
    
    .vaccine-actions {
        flex-direction: column;
    }
    
    .vaccine-stat-box {
        padding: 15px;
    }
    
    .status-options {
        flex-direction: column;
    }
    
    .status-option {
        min-width: auto;
    }
}

@media (max-width: 480px) {
    .vaccine-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .vaccine-details .detail-row {
        flex-direction: column;
        gap: 4px;
    }
    
    .vaccine-details .detail-value {
        text-align: left;
    }
}


/* Form validation styles for vaccine forms */
.form-group label[for*="vaccine_name"]::after,
.form-group label[for*="vaccine_description"]::after,
.form-group label[for*="vaccine_status"]::after,
.form-group label[for*="doses_required"]::after,
.form-group label[for*="storage_temp"]::after,
.form-group label[for*="efficacy_rate"]::after {
    content: " *";
    color: #ef4444;
}

.form-group input:required,
.form-group select:required,
.form-group textarea:required {
    border-left: 3px solid #ef4444;
}

.form-group input:required:valid,
.form-group select:required:valid,
.form-group textarea:required:valid {
    border-left: 3px solid #10b981;
}

.form-group input.is-invalid,
.form-group select.is-invalid,
.form-group textarea.is-invalid {
    border-color: #dc2626;
    border-left: 3px solid #dc2626;
}

.form-group input.is-valid,
.form-group select.is-valid,
.form-group textarea.is-valid {
    border-color: #10b981;
    border-left: 3px solid #10b981;
}

.error-message {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
    display: none;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Form validation styles for vaccine forms */
.form-group label[for*="vaccine_name"]::after,
.form-group label[for*="vaccine_description"]::after,
.form-group label[for*="vaccine_status"]::after,
.form-group label[for*="doses_required"]::after,
.form-group label[for*="storage_temp"]::after,
.form-group label[for*="efficacy_rate"]::after {
    content: " *";
    color: #ef4444;
}

.form-group input:required,
.form-group select:required,
.form-group textarea:required {
    border-left: 3px solid #ef4444;
}

.form-group input:required:valid,
.form-group select:required:valid,
.form-group textarea:required:valid {
    border-left: 3px solid #10b981;
}

.form-group input.is-invalid,
.form-group select.is-invalid,
.form-group textarea.is-invalid {
    border-color: #dc2626;
    border-left: 3px solid #dc2626;
}

.form-group input.is-valid,
.form-group select.is-valid,
.form-group textarea.is-valid {
    border-color: #10b981;
    border-left: 3px solid #10b981;
}

.error-message {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
    display: none;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.is-invalid {
    border: 1px solid #dc3545 !important;
    box-shadow: 0 0 5px rgba(220, 53, 69, 0.3);
}

.error-message {
    color: #dc3545;
    font-size: 0.875em;
    margin-top: 4px;
    display: none;
}

.nav-badge {
    background: #ef4444;
    color: white;
    border-radius: 10px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: 600;
    margin-left: auto;
}

.sidebar-menu li a {
    display: flex;
    align-items: center;
    position: relative;
}
</style>

    
</head>
<body>
<div class="app">
    <!-- In the sidebar navigation, add this line after the Bookings link -->
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <a href="/admin_dashboard.php?section=dashboard">
            <img src="assets/img/Vaccination_Logo3-removebg-preview.png" alt="Vaxify Logo" class="logo">
        </a>
    </div>
    <nav class="nav">
        <a href="admin_dashboard.php?section=dashboard" class="<?= ($section === 'dashboard' ? 'active' : '') ?>"><i class="fa-solid fa-gauge ico"></i><span>Dashboard</span></a>
        <a href="admin_dashboard.php?section=patients" class="<?= ($section === 'patients' ? 'active' : '') ?>"><i class="fa-solid fa-users ico"></i><span>Patient Details</span></a>
        <a href="admin_dashboard.php?section=hospital_management" class="<?= ($section === 'hospital_management' ? 'active' : '') ?>">
            <i class="fa-solid fa-hospital ico"></i>
            <span>Hospital Management</span>
            <?php
            // Count pending hospitals for badge
            $pending_count = 0;
            if ($conn) {
                $count_query = "SELECT COUNT(*) as count FROM hospitals WHERE is_approved = 0";
                $count_result = $conn->query($count_query);
                if ($count_result) {
                    $pending_count = $count_result->fetch_assoc()['count'];
                }
            }
            if ($pending_count > 0): ?>
                <span class="nav-badge"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="admin_dashboard.php?section=reports" class="<?= ($section === 'reports' ? 'active' : '') ?>"><i class="fa-solid fa-file-medical ico"></i><span>COVID-19 Reports</span></a>
        <a href="admin_dashboard.php?section=vaccines" class="<?= ($section === 'vaccines' ? 'active' : '') ?>"><i class="fa-solid fa-syringe ico"></i><span>Vaccine List</span></a>
        <a href="admin_dashboard.php?section=approval" class="<?= ($section === 'approval' ? 'active' : '') ?>"><i class="fa-solid fa-calendar-check ico"></i><span>Booking Details</span></a>
        <a href="admin_dashboard.php?section=hospitals" class="<?= ($section === 'hospitals' ? 'active' : '') ?>"><i class="fa-solid fa-hospital ico"></i><span>Hospital List</span></a>
        <a href="admin_dashboard.php?section=contact_messages" class="<?= ($section === 'contact_messages' ? 'active' : '') ?>">
            <i class="fa-solid fa-envelope"></i><span>Contact Messages</span>
        </a>
        <!-- Add Admin Management Link -->
        <a href="admin_dashboard.php?section=admins" class="<?= ($section === 'admins' ? 'active' : '') ?>"><i class="fa-solid fa-user-shield ico"></i><span>Admin Management</span></a>
    </nav>
    <div class="logout">
        <button id="logoutBtn"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
    </div>
</aside>

    <div class="mobile-overlay" id="mobileOverlay"></div>

    <main class="main">
        <div class="topbar">
    <div class="left">
        <button class="hamburger" id="menuBtn"><i class="fa-solid fa-bars"></i></button>
        <h2><?php echo $currentSectionTitle; ?></h2>
    </div>
    <div class="profile">
        <div>Welcome, <b><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></b></div>
        <div class="avatar">
            <?php if (isset($_SESSION['user_image']) && file_exists($_SESSION['user_image'])): ?>
                <img src="<?php echo htmlspecialchars($_SESSION['user_image']); ?>" alt="Profile" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
            <?php else: ?>
                <i class="fa-solid fa-user-shield"></i>
            <?php endif; ?>
        </div>
    </div>
</div>

        <section class="content">
            <!-- Display Messages - YAHAN ADD KAREN -->
            <?php if (isset($_SESSION['message'])): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-check-circle"></i> 
                    <span><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-exclamation-circle"></i> 
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>
            <!-- Display Messages End -->

           <?php if ($section === 'dashboard'): ?>
<!-- Dashboard Section -->
<main class="container">
<div class="grid">
    <div class="left-column">
        <div>
            <h3 style="font-size:18px;color:#0b1720">Welcome to Admin Dashboard</h3>
            <div style="color:#6b7280;font-size:14px;margin-top:4px">Overview of system management and operations</div>
        </div>
        
        <!-- Main Statistics Cards -->
        <div class="cards" style="margin-top:12px">
    <div class="card">
    <h3>
        <i class="fa-solid fa-clock" style="color: #ef4444;"></i>
        Pending Approvals
    </h3>
    <p><?php 
        $pendingCount = $conn->query("SELECT COUNT(*) as count FROM hospital_requests WHERE status = 'pending'")->fetch_assoc()['count'];
        echo $pendingCount; 
    ?></p>
    <p>Hospital approvals pending</p>
</div>
    <div class="card">
        <h3>
            <i class="fa-solid fa-hospital" style="color: #10b981;"></i>
            Active Hospitals
        </h3>
        <p><?php echo $activeHospitals; ?></p>
        <p>Currently active hospitals</p>
    </div>
    <div class="card">
        <h3>
            <i class="fa-solid fa-syringe" style="color: #3b82f6;"></i>
            Available Vaccines
        </h3>
        <p><?php echo $availableVaccines; ?></p>
        <p>Vaccines in stock</p>
    </div>
</div>

        <!-- Charts Section -->
        <div class="chart-container">
            <h4>Monthly Bookings Overview - <?php echo date('Y'); ?></h4>
            <div class="chart-item">
                <canvas id="bookingsChart" style="width:100%; height:250px;"></canvas>
            </div>
        </div>
        
        <div class="chart-grid">
            <div class="chart-container">
                <h4>Vaccine Distribution</h4>
                <div class="chart-item">
                    <canvas id="vaccineChart" style="width:100%; height:250px;"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <h4>Patient Age Demographics</h4>
                <div class="chart-item">
                    <canvas id="ageChart" style="width:100%; height:250px;"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Additional Statistics -->
        <div class="stats-grid">
            <div class="stat-item">
                <h5>Total Patients</h5>
                <p><?php echo $totalPatients; ?></p>
            </div>
            <div class="stat-item">
                <h5>Total Bookings</h5>
                <p><?php echo $totalBookings; ?></p>
            </div>
            <div class="stat-item">
                <h5>Total Hospitals</h5>
                <p><?php echo $totalHospitals; ?></p>
            </div>
            <div class="stat-item">
                <h5>Total Vaccines</h5>
                <p><?php echo $availableVaccines; ?></p>
            </div>
        </div>
    </div>
    
    <aside class="right-column">

        <!-- Report XLS Panel -->
        <div class="panel" style="margin-top: 16px;">
            <h4>Report XLS</h4>
            <div class="up-item">
                <div>
                    <b>Export Options</b>
                    <div style="color:#6b7280;font-size:13px">Download reports in Excel format</div>
                </div>
                <div class="button-group">
                    <a href="admin_dashboard.php?section=reports&export=xls&date_filter=today" class="export">
                        <i class="fa-solid fa-download"></i> Export 
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities Panel -->
        <div class="panel" style="margin-top: 16px;">
            <h4>Recent Activities</h4>
            <?php if (!empty($recentActivities)): ?>
                <?php foreach($recentActivities as $activity): ?>
                <div class="up-item">
                    <div>
                        <b><?php echo $activity['message']; ?></b>
                        <div style="color:#6b7280;font-size:13px">
                            <?php echo time_elapsed_string($activity['time']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Sample activity as shown in your image -->
                <div class="up-item">
                    <div>
                        <b>New Hospital Registered: Liaquat National Hospital</b>
                        <div style="color:#6b7280;font-size:13px">
                            Final name: Liaquat National Hospital<br>
                            Coupon/privacy/pages_currently_authorised provided<br>
                            Date: Issue: 02 (June) 10pm<br>
                            Coupon/privacy/pages/health_duodenal.php on Tue 2025
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        </div>


<script>
// Initialize Charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Bookings Chart - Line Chart with lighter colors
    const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
    const bookingsChart = new Chart(bookingsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($monthlyBookings)); ?>,
            datasets: [{
                label: 'Bookings',
                data: <?php echo json_encode(array_values($monthlyBookings)); ?>,
                backgroundColor: 'rgba(150, 50, 150, 0.2)',
                borderColor: 'rgba(150, 50, 150, 1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgba(150, 50, 150, 1)',
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

    // Vaccine Distribution Chart - Lighter colorful doughnut chart
    const vaccineCtx = document.getElementById('vaccineChart').getContext('2d');
    const vaccineChart = new Chart(vaccineCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($vaccineDistribution)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($vaccineDistribution)); ?>,
                backgroundColor: [
                    'rgba(50, 50, 50, 0.8)',           // Light Gray
                    'rgba(150, 50, 150, 0.8)',         // Light Purple
                    'rgba(220, 100, 180, 0.8)',        // Light Pink
                    'rgba(50, 150, 150, 0.8)',         // Light Teal
                    'rgba(80, 120, 100, 0.8)',         // Light Green
                    'rgba(50, 120, 150, 0.8)',         // Light Blue
                    'rgba(150, 50, 50, 0.8)',          // Light Red
                    'rgba(50, 150, 50, 0.8)',          // Light Green
                    'rgba(120, 50, 150, 0.8)',         // Light Violet
                    'rgba(150, 100, 50, 0.8)'          // Light Brown
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverBackgroundColor: [
                    'rgba(50, 50, 50, 1)',
                    'rgba(150, 50, 150, 1)',
                    'rgba(220, 100, 180, 1)',
                    'rgba(50, 150, 150, 1)',
                    'rgba(80, 120, 100, 1)',
                    'rgba(50, 120, 150, 1)',
                    'rgba(150, 50, 50, 1)',
                    'rgba(50, 150, 50, 1)',
                    'rgba(120, 50, 150, 1)',
                    'rgba(150, 100, 50, 1)'
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

    // Patient Age Demographics Chart - Lighter colorful bar chart
    const ageCtx = document.getElementById('ageChart').getContext('2d');
    const ageChart = new Chart(ageCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($ageGroups)); ?>,
            datasets: [{
                label: 'Patients',
                data: <?php echo json_encode(array_values($ageGroups)); ?>,
                backgroundColor: [
                    'rgba(150, 50, 150, 0.7)',      // Light Purple
                    'rgba(50, 150, 150, 0.7)',      // Light Teal
                    'rgba(150, 100, 50, 0.7)',      // Light Brown
                    'rgba(50, 120, 150, 0.7)',      // Light Blue
                    'rgba(80, 120, 100, 0.7)'       // Light Green
                ],
                borderColor: [
                    'rgba(150, 50, 150, 1)',
                    'rgba(50, 150, 150, 1)',
                    'rgba(150, 100, 50, 1)',
                    'rgba(50, 120, 150, 1)',
                    'rgba(80, 120, 100, 1)'
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

// For Patient Section Charts (if needed)
<?php if ($section === 'patients'): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Patient Gender Distribution Chart - Lighter colorful pie chart
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    const genderChart = new Chart(genderCtx, {
        type: 'pie',
        data: {
            labels: ['Male', 'Female', 'Other'],
            datasets: [{
                data: [<?php echo $malePatients; ?>, <?php echo $femalePatients; ?>, <?php echo $otherPatients; ?>],
                backgroundColor: [
                    'rgba(50, 120, 150, 0.8)',      // Light Blue
                    'rgba(150, 50, 150, 0.8)',      // Light Purple
                    'rgba(150, 100, 50, 0.8)'       // Light Brown
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverBackgroundColor: [
                    'rgba(50, 120, 150, 1)',
                    'rgba(150, 50, 150, 1)',
                    'rgba(150, 100, 50, 1)'
                ]
            }]
        },
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

    // Vaccination Status Chart - Lighter colorful doughnut chart
    const vaccinationCtx = document.getElementById('vaccinationChart').getContext('2d');
    const vaccinationChart = new Chart(vaccinationCtx, {
        type: 'doughnut',
        data: {
            labels: ['Fully Vaccinated', 'Partially Vaccinated', 'Not Vaccinated'],
            datasets: [{
                data: [<?php echo $fullyVaccinated; ?>, <?php echo $partiallyVaccinated; ?>, <?php echo $notVaccinated; ?>],
                backgroundColor: [
                    'rgba(50, 150, 50, 0.8)',       // Light Green
                    'rgba(80, 120, 100, 0.8)',      // Light Olive
                    'rgba(150, 50, 50, 0.8)'        // Light Red
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverBackgroundColor: [
                    'rgba(50, 150, 50, 1)',
                    'rgba(80, 120, 100, 1)',
                    'rgba(150, 50, 50, 1)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '60%'
        }
    });
});
<?php endif; ?>
</script>

<?php elseif ($section === 'patients'): ?>
<!-- Enhanced Patient Details Section -->

<main class="container">
    <div class="section-header">
        <div>
            <h3 style="font-size:22px;color:#0b1720;margin-bottom:5px">Patient Management</h3>
            <div style="color:#6b7280;font-size:14px">Manage and view all patient records</div>
        </div>
        <div class="search-box">
            <input type="text" id="patientSearch" placeholder="Search patients by name, email, or ID...">
            <button><i class="fa-solid fa-search"></i> Search</button>
        </div>
        <div class="action-buttons">
            <!-- <a href="admin_dashboard.php?section=patients&export_patients=xls" class="btn btn-secondary">
                <i class="fa-solid fa-download"></i> Export XLS
            </a> -->
        </div>
    </div>

    <script>
// Patient Search Functionality - Fixed Version
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing patient search');
    initializePatientSearch();
    setupPatientViewToggle();
    
    // Initial search call to show all patients
    setTimeout(() => {
        performPatientSearch();
    }, 500);
});

function initializePatientSearch() {
    const patientSearchInput = document.getElementById('patientSearch');
    const searchButton = document.querySelector('.search-box button');
    
    console.log('Search input found:', patientSearchInput);
    console.log('Search button found:', searchButton);
    
    if (patientSearchInput) {
        // Search on input (real-time)
        patientSearchInput.addEventListener('input', function() {
            console.log('Input event triggered');
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(function() {
                performPatientSearch();
            }, 300);
        });
        
        // Search on Enter key
        patientSearchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                console.log('Enter key pressed');
                performPatientSearch();
            }
        });
    }
    
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            console.log('Search button clicked');
            e.preventDefault();
            performPatientSearch();
        });
    }
}

function performPatientSearch() {
    const searchInput = document.getElementById('patientSearch');
    if (!searchInput) {
        console.error('Search input not found!');
        return;
    }
    
    const searchTerm = searchInput.value.toLowerCase().trim();
    console.log('Searching for:', searchTerm);
    
    let foundAny = false;
    
    // Search in grid view
    const gridView = document.getElementById('patientsGridView');
    if (gridView) {
        const patientCards = gridView.querySelectorAll('.patient-card');
        console.log('Found patient cards in grid:', patientCards.length);
        
        patientCards.forEach((card, index) => {
            // Multiple ways to get patient data
            const patientName = card.querySelector('.patient-name')?.textContent.toLowerCase() || '';
            const patientId = card.querySelector('.patient-id')?.textContent.toLowerCase() || '';
            
            // Try to find email and phone in detail rows
            let patientEmail = '';
            let patientPhone = '';
            
            const detailRows = card.querySelectorAll('.detail-row');
            detailRows.forEach(row => {
                const label = row.querySelector('.detail-label')?.textContent.toLowerCase();
                const value = row.querySelector('.detail-value')?.textContent.toLowerCase();
                if (label && label.includes('email')) patientEmail = value;
                if (label && label.includes('phone')) patientPhone = value;
            });
            
            console.log(`Card ${index}:`, { patientName, patientId, patientEmail, patientPhone });
            
            const matches = patientName.includes(searchTerm) || 
                           patientId.includes(searchTerm) || 
                           patientEmail.includes(searchTerm) ||
                           patientPhone.includes(searchTerm);
            
            if (matches || searchTerm === '') {
                card.style.display = 'block';
                foundAny = true;
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Search in table view
    const tableView = document.getElementById('patientsTableView');
    if (tableView) {
        const tableRows = tableView.querySelectorAll('.patients-table tbody tr');
        console.log('Found table rows:', tableRows.length);
        
        tableRows.forEach((row, index) => {
            // Skip rows that don't have enough cells or are empty
            if (row.cells.length < 4 || row.classList.contains('no-results-row')) {
                return;
            }
            
            const patientId = row.cells[0]?.textContent.toLowerCase() || '';
            const patientName = row.cells[1]?.textContent.toLowerCase() || '';
            const patientEmail = row.cells[2]?.textContent.toLowerCase() || '';
            const patientPhone = row.cells[3]?.textContent.toLowerCase() || '';
            
            console.log(`Row ${index}:`, { patientName, patientId, patientEmail, patientPhone });
            
            const matches = patientName.includes(searchTerm) || 
                           patientId.includes(searchTerm) || 
                           patientEmail.includes(searchTerm) ||
                           patientPhone.includes(searchTerm);
            
            if (matches || searchTerm === '') {
                row.style.display = '';
                foundAny = true;
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Show/hide no results message
    if (searchTerm !== '' && !foundAny) {
        showNoResultsMessage(searchTerm);
    } else {
        removeNoResultsMessage();
    }
    
    console.log('Search completed. Found any results:', foundAny);
}

function showNoResultsMessage(searchTerm) {
    removeNoResultsMessage();
    
    const gridView = document.getElementById('patientsGridView');
    const tableView = document.getElementById('patientsTableView');
    
    // Grid view message
    if (gridView && gridView.style.display !== 'none') {
        const noResultsDiv = document.createElement('div');
        noResultsDiv.className = 'no-results-message';
        noResultsDiv.style.gridColumn = '1 / -1';
        noResultsDiv.style.textAlign = 'center';
        noResultsDiv.style.padding = '40px';
        noResultsDiv.style.background = 'white';
        noResultsDiv.style.borderRadius = '10px';
        noResultsDiv.style.marginTop = '20px';
        noResultsDiv.innerHTML = `
            <i class="fa-solid fa-search" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
            <h3 style="color: #64748b; margin-bottom: 10px;">No Patients Found</h3>
            <p style="color: #94a3b8;">No patients match your search for "<strong>${searchTerm}</strong>"</p>
            <button class="btn btn-primary" onclick="clearSearch()" style="margin-top: 15px;">
                <i class="fa-solid fa-times"></i> Clear Search
            </button>
        `;
        gridView.appendChild(noResultsDiv);
    }
    
    // Table view message
    if (tableView && tableView.style.display !== 'none') {
        const tableBody = tableView.querySelector('tbody');
        const noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-row';
        noResultsRow.style.background = 'white';
        const colCount = tableView.querySelector('thead tr')?.children.length || 6;
        const cell = document.createElement('td');
        cell.colSpan = colCount;
        cell.style.textAlign = 'center';
        cell.style.padding = '30px';
        cell.style.color = '#64748b';
        cell.innerHTML = `
            <i class="fa-solid fa-search" style="font-size: 24px; margin-bottom: 10px; display: block; color: #cbd5e1;"></i>
            No patients match your search for "<strong>${searchTerm}</strong>"
            <br><button class="btn btn-primary" onclick="clearSearch()" style="margin-top: 10px; padding: 5px 10px; font-size: 12px;">
                <i class="fa-solid fa-times"></i> Clear Search
            </button>
        `;
        noResultsRow.appendChild(cell);
        tableBody.appendChild(noResultsRow);
    }
}

function removeNoResultsMessage() {
    const gridView = document.getElementById('patientsGridView');
    const gridMessage = gridView?.querySelector('.no-results-message');
    if (gridMessage) {
        gridMessage.remove();
    }
    
    const tableView = document.getElementById('patientsTableView');
    const tableMessage = tableView?.querySelector('.no-results-row');
    if (tableMessage) {
        tableMessage.remove();
    }
}

function clearSearch() {
    const searchInput = document.getElementById('patientSearch');
    if (searchInput) {
        searchInput.value = '';
        performPatientSearch();
        searchInput.focus();
    }
}

function setupPatientViewToggle() {
    const gridViewOption = document.getElementById('grid-view-patients');
    const tableViewOption = document.getElementById('table-view-patients');
    
    if (gridViewOption && tableViewOption) {
        gridViewOption.addEventListener('click', function() {
            switchPatientView('grid');
        });
        
        tableViewOption.addEventListener('click', function() {
            switchPatientView('table');
        });
        
        console.log('View toggle buttons found and listeners attached');
    } else {
        console.log('View toggle buttons not found');
    }
}

function switchPatientView(viewType) {
    const gridViewOption = document.getElementById('grid-view-patients');
    const tableViewOption = document.getElementById('table-view-patients');
    const gridView = document.getElementById('patientsGridView');
    const tableView = document.getElementById('patientsTableView');
    
    if (!gridView || !tableView) {
        console.error('Grid or Table view not found');
        return;
    }
    
    if (viewType === 'grid') {
        gridViewOption?.classList.add('active');
        tableViewOption?.classList.remove('active');
        gridView.style.display = 'grid';
        tableView.style.display = 'none';
    } else {
        tableViewOption?.classList.add('active');
        gridViewOption?.classList.remove('active');
        gridView.style.display = 'none';
        tableView.style.display = 'block';
    }
    
    // Re-apply search filter after switching view
    setTimeout(() => {
        performPatientSearch();
    }, 100);
}

// Debug function
function debugPatientSearch() {
    console.log('=== DEBUG PATIENT SEARCH ===');
    console.log('Search input:', document.getElementById('patientSearch'));
    console.log('Grid view:', document.getElementById('patientsGridView'));
    console.log('Table view:', document.getElementById('patientsTableView'));
    console.log('Patient cards:', document.querySelectorAll('.patient-card').length);
    console.log('Table rows:', document.querySelectorAll('.patients-table tbody tr').length);
    
    // Check if patient cards have required classes
    const firstCard = document.querySelector('.patient-card');
    if (firstCard) {
        console.log('First card classes:', firstCard.className);
        console.log('First card HTML:', firstCard.innerHTML);
    }
    console.log('=== END DEBUG ===');
}

// Call debug after page load
setTimeout(debugPatientSearch, 1000);
</script>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;">
            <i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <i class="fa-solid fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Patient Statistics Cards -->
    <div class="cards">
        <div class="card">
            <h3>
                <i class="fa-solid fa-users" style="color: #0ea5a4;"></i>
                Total Patients
            </h3>
            <p><?php echo $totalPatients; ?></p>
            <p>Registered in system</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-mars" style="color: #3b82f6;"></i>
                Male Patients
            </h3>
            <p><?php echo $malePatients; ?></p>
            <p><?php echo $totalPatients > 0 ? round(($malePatients/$totalPatients)*100, 1) : 0; ?>% of total</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-venus" style="color: #ec4899;"></i>
                Female Patients
            </h3>
            <p><?php echo $femalePatients; ?></p>
            <p><?php echo $totalPatients > 0 ? round(($femalePatients/$totalPatients)*100, 1) : 0; ?>% of total</p>
        </div>
    </div>

    <!-- Patient List Section -->
<div class="panel" style="margin-top: 20px;">
    <h4>Patient Records</h4>
    
    <!-- View Toggle -->
    <div class="view-toggle">
        <div class="view-option active" id="grid-view-patients">Grid View</div>
        <div class="view-option" id="table-view-patients">Table View</div>
    </div>

    <!-- Patient Cards View -->
    <div class="patients-grid" id="patientsGridView">
        <?php if ($patientsResult && $patientsResult->num_rows > 0): ?>
            <?php while($patient = $patientsResult->fetch_assoc()): ?>
                <div class="patient-card">
                    <div class="patient-header">
                        <div class="patient-avatar">
                            <?php 
                            $avatarColor = '#' . substr(md5($patient['id']), 0, 6);
                            $initials = '';
                            if (isset($patient['name'])) {
                                $nameParts = explode(' ', $patient['name']);
                                $initials = '';
                                foreach ($nameParts as $part) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                }
                                $initials = substr($initials, 0, 2);
                            } else {
                                $initials = 'P';
                            }
                            ?>
                            <div style="background-color: <?php echo $avatarColor; ?>; width: 100%; height: 100%; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                <?php echo $initials; ?>
                            </div>
                        </div>
                        <div class="patient-info">
                            <div class="patient-name"><?php echo htmlspecialchars($patient['name'] ?? 'N/A'); ?></div>
                            <div class="patient-id">ID: <?php echo htmlspecialchars($patient['id'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="patient-details">
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Gender:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patient['gender'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if (in_array('date_of_birth', $existingColumns)): ?>
                        <div class="detail-row">
                            <span class="detail-label">Age:</span>
                            <span class="detail-value">
                                <?php 
                                if (isset($patient['date_of_birth'])) {
                                    $birthDate = new DateTime($patient['date_of_birth']);
                                    $today = new DateTime();
                                    $age = $today->diff($birthDate)->y;
                                    echo $age . ' years';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <?php 
                                $status = $patient['vaccination_status'] ?? 'not_vaccinated';
                                $statusText = '';
                                $statusClass = '';
                                if ($status === 'fully_vaccinated') {
                                    $statusText = 'Fully Vaccinated';
                                    $statusClass = 'status-fully';
                                } elseif ($status === 'partially_vaccinated') {
                                    $statusText = 'Partially Vaccinated';
                                    $statusClass = 'status-partial';
                                } else {
                                    $statusText = 'Not Vaccinated';
                                    $statusClass = 'status-none';
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </span>
                        </div>
                    </div>
                    <div class="patient-actions">
                        <button class="btn-view" onclick="viewPatient(<?php echo $patient['id']; ?>)" title="View">
                            <i class="fa-solid fa-eye"></i> View
                        </button>
                        <button class="btn-edit" onclick="editPatient(<?php echo $patient['id']; ?>)" title="Edit">
                            <i class="fa-solid fa-edit"></i> Edit
                        </button>
                        <button class="btn-delete" onclick="confirmDelete(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['name']); ?>')" title="Delete">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 10px;">
                <i class="fa-solid fa-users" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                <h3 style="color: #64748b; margin-bottom: 10px;">No Patients Found</h3>
                <p style="color: #94a3b8;">There are no patients in the system yet.</p>
                <button class="btn btn-primary" onclick="openAddPatientModal()" style="margin-top: 15px;">
                    <i class="fa-solid fa-plus"></i> Add First Patient
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Patient Table View -->
    <div class="patients-table-container" id="patientsTableView" style="display: none;">
        <table class="patients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    <?php if (in_array('date_of_birth', $existingColumns)): ?>
                    <th>Age</th>
                    <?php endif; ?>
                    <th>Vaccination Status</th>
                    <th>Registration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Reset result pointer for table view
                if ($patientsResult) {
                    $patientsResult->data_seek(0);
                }
                ?>
                <?php if ($patientsResult && $patientsResult->num_rows > 0): ?>
                    <?php while($patient = $patientsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['id'] ?? 'N/A'); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php 
                                    $avatarColor = '#' . substr(md5($patient['id']), 0, 6);
                                    $initials = '';
                                    if (isset($patient['name'])) {
                                        $nameParts = explode(' ', $patient['name']);
                                        $initials = '';
                                        foreach ($nameParts as $part) {
                                            $initials .= strtoupper(substr($part, 0, 1));
                                        }
                                        $initials = substr($initials, 0, 2);
                                    } else {
                                        $initials = 'P';
                                    }
                                    ?>
                                    <div style="background-color: <?php echo $avatarColor; ?>; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
                                        <?php echo $initials; ?>
                                    </div>
                                    <?php echo htmlspecialchars($patient['name'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($patient['gender'] ?? 'N/A'); ?></td>
                            <?php if (in_array('date_of_birth', $existingColumns)): ?>
                            <td>
                                <?php 
                                if (isset($patient['date_of_birth'])) {
                                    $birthDate = new DateTime($patient['date_of_birth']);
                                    $today = new DateTime();
                                    $age = $today->diff($birthDate)->y;
                                    echo $age . ' years';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <?php endif; ?>
                            <td>
                                <?php 
                                $status = $patient['vaccination_status'] ?? 'not_vaccinated';
                                $statusText = '';
                                $statusClass = '';
                                if ($status === 'fully_vaccinated') {
                                    $statusText = 'Fully Vaccinated';
                                    $statusClass = 'status-fully';
                                } elseif ($status === 'partially_vaccinated') {
                                    $statusText = 'Partially Vaccinated';
                                    $statusClass = 'status-partial';
                                } else {
                                    $statusText = 'Not Vaccinated';
                                    $statusClass = 'status-none';
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <button class="btn-view" onclick="viewPatient(<?php echo $patient['id']; ?>)" title="View">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button class="btn-edit" onclick="editPatient(<?php echo $patient['id']; ?>)" title="Edit">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <button class="btn-delete" onclick="confirmDelete(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['name']); ?>')" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo in_array('date_of_birth', $existingColumns) ? '9' : '8'; ?>" style="text-align: center; padding: 30px; color: #64748b;">
                            <i class="fa-solid fa-users" style="font-size: 24px; margin-bottom: 10px; display: block; color: #cbd5e1;"></i>
                            No patients found in the system
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<script>
// Patient Charts with Dashboard Color Scheme
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Registrations Chart - Line Chart
    const registrationsCtx = document.getElementById('registrationsChart').getContext('2d');
    const registrationsChart = new Chart(registrationsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($monthlyRegistrations)); ?>,
            datasets: [{
                label: 'Registrations',
                data: <?php echo json_encode(array_values($monthlyRegistrations)); ?>,
                backgroundColor: 'rgba(74, 220, 227, 0.2)',
                borderColor: 'rgba(13, 134, 158, 0.8)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgba(4, 121, 80, 1)',
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

    // Age Demographics Chart - Bar Chart
    const ageCtx = document.getElementById('ageChart').getContext('2d');
    const ageChart = new Chart(ageCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($ageGroups)); ?>,
            datasets: [{
                label: 'Patients',
                data: <?php echo json_encode(array_values($ageGroups)); ?>,
                backgroundColor: [
                    'rgba(175, 81, 14, 0.7)',      // Light Purple
                    'rgba(50, 150, 150, 0.7)',      // Light Teal
                    'rgba(176, 21, 117, 0.7)',      // Light Brown
                    'rgba(50, 120, 150, 0.7)',      // Light Blue
                    'rgba(80, 120, 100, 0.7)'       // Light Green
                ],
                borderColor: [
                    'rgba(220, 190, 37, 1)',
                    'rgba(50, 150, 150, 1)',
                    'rgba(198, 28, 142, 1)',
                    'rgba(50, 120, 150, 1)',
                    'rgba(80, 120, 100, 1)'
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

    // Gender Distribution Chart - Doughnut Chart
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    const genderChart = new Chart(genderCtx, {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female', 'Other'],
            datasets: [{
                data: [<?php echo $malePatients; ?>, <?php echo $femalePatients; ?>, <?php echo $otherPatients; ?>],
                backgroundColor: [
                    'rgba(50, 120, 150, 0.8)',      // Light Blue
                    'rgba(131, 30, 30, 0.8)',      // Light Purple
                    'rgba(150, 50, 150, 0.8)'       // Light Brown
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverBackgroundColor: [
                    'rgba(50, 120, 150, 1)',
                    'rgba(114, 22, 22, 0.8)',
                    'rgba(150, 50, 150, 1)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '60%'
        }
    });

    // Vaccination Status Chart - Doughnut Chart
    const vaccinationCtx = document.getElementById('vaccinationChart').getContext('2d');
    const vaccinationChart = new Chart(vaccinationCtx, {
        type: 'doughnut',
        data: {
            labels: ['Fully Vaccinated', 'Partially Vaccinated', 'Not Vaccinated'],
            datasets: [{
                data: [<?php echo $fullyVaccinated; ?>, <?php echo $partiallyVaccinated; ?>, <?php echo $notVaccinated; ?>],
                backgroundColor: [
                    'rgba(50, 150, 50, 0.8)',       // Light Green
                    'rgba(80, 120, 100, 0.8)',      // Light Olive
                    'rgba(220, 100, 180, 0.8)'        // Light Red
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverBackgroundColor: [
                    'rgba(50, 150, 50, 1)',
                    'rgba(80, 120, 100, 1)',
                    'rgba(220, 100, 180, 1)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '60%'
        }
    });
});

// Patient View Toggle
document.addEventListener('DOMContentLoaded', function() {
    const gridViewBtn = document.getElementById('grid-view-patients');
    const tableViewBtn = document.getElementById('table-view-patients');
    const patientGrid = document.getElementById('patientsGridView');
    const patientTable = document.getElementById('patientsTableView');
    
    if (gridViewBtn && tableViewBtn) {
        // Initially show grid view on mobile, table view on desktop
        if (window.innerWidth >= 992) {
            patientGrid.style.display = 'none';
            patientTable.style.display = 'block';
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
        } else {
            patientGrid.style.display = 'grid';
            patientTable.style.display = 'none';
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        }
        
        gridViewBtn.addEventListener('click', function() {
            patientGrid.style.display = 'grid';
            patientTable.style.display = 'none';
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        });
        
        tableViewBtn.addEventListener('click', function() {
            patientGrid.style.display = 'none';
            patientTable.style.display = 'block';
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
        });
        
        // Responsive behavior on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                patientGrid.style.display = 'none';
                patientTable.style.display = 'block';
                gridViewBtn.classList.remove('active');
                tableViewBtn.classList.add('active');
            } else {
                patientGrid.style.display = 'grid';
                patientTable.style.display = 'none';
                gridViewBtn.classList.add('active');
                tableViewBtn.classList.remove('active');
            }
        });
    }
});
</script>


<?php elseif ($section === 'hospital_management'): ?>
<!-- Hospital Management Section -->
<main class="container">
    <div class="section-header">
        <div>
            <h3 style="font-size:22px;color:var(--heading-color);margin-bottom:5px">Hospital Management</h3>
            <div style="color:var(--muted);font-size:14px">Approve or manage hospital accounts</div>
        </div>
        <div class="search-box">
            <input type="text" id="hospitalSearch" placeholder="Search hospitals...">
            <button><i class="fa-solid fa-search"></i> Search</button>
        </div>
        
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;">
            <i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <i class="fa-solid fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Hospital Statistics Cards -->
    <div class="cards">
        <div class="card">
            <h3>
                <i class="fa-solid fa-clock" style="color: #f59e0b;"></i>
                Pending Approval
            </h3>
            <p><?php echo count($pendingHospitals); ?></p>
            <p>Awaiting approval</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-check-circle" style="color: #10b981;"></i>
                Approved
            </h3>
            <p><?php echo count($approvedHospitals); ?></p>
            <p>Active hospitals</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-hospital" style="color: #3b82f6;"></i>
                Total Hospitals
            </h3>
            <p><?php echo count($pendingHospitals) + count($approvedHospitals); ?></p>
            <p>All hospital accounts</p>
        </div>
    </div>

    <!-- Pending Hospitals Section -->
    <div class="panel" style="margin-top: 20px;">
        <h4><i class="fa-solid fa-clock"></i> Pending Hospital Approvals (<?php echo count($pendingHospitals); ?>)</h4>
        
        <div class="hospital-grid" id="pendingHospitalsView">
            <?php if (!empty($pendingHospitals)): ?>
                <?php foreach($pendingHospitals as $hospital): ?>
                    <div class="hospital-card">
                        <div class="hospital-header">
                            <div class="hospital-name"><?php echo htmlspecialchars($hospital['name']); ?></div>
                            <div class="hospital-status status-pending">
                                Pending Approval
                            </div>
                        </div>
                        <div class="hospital-details">
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Address:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['address'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Registration Date:</span>
                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($hospital['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="hospital-actions">
                            <form method="POST" class="approval-form">
                                <input type="hidden" name="action" value="approve_hospital">
                                <input type="hidden" name="hospital_id" value="<?php echo $hospital['id']; ?>">
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <input type="text" name="admin_notes" placeholder="Add approval notes (optional)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <button type="submit" class="btn btn-success" style="width: 100%;">
                                    <i class="fa-solid fa-check"></i> Approve Hospital
                                </button>
                            </form>
                            <form method="POST" class="approval-form">
                                <input type="hidden" name="action" value="reject_hospital">
                                <input type="hidden" name="hospital_id" value="<?php echo $hospital['id']; ?>">
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <input type="text" name="admin_notes" placeholder="Add rejection reason" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                                </div>
                                <button type="submit" class="btn btn-danger" style="width: 100%;">
                                    <i class="fa-solid fa-times"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--surface-color); border-radius: 10px;">
                    <i class="fa-solid fa-check-circle" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h3 style="color: var(--muted); margin-bottom: 10px;">No Pending Hospital Approvals</h3>
                    <p style="color: #94a3b8;">All hospital accounts are approved.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approved Hospitals Section -->
    <div class="panel" style="margin-top: 20px;">
        <h4><i class="fa-solid fa-check-circle"></i> Approved Hospitals (<?php echo count($approvedHospitals); ?>)</h4>
        
        <div class="hospital-grid">
            <?php if (!empty($approvedHospitals)): ?>
                <?php foreach($approvedHospitals as $hospital): ?>
                    <div class="hospital-card">
                        <div class="hospital-header">
                            <div class="hospital-name"><?php echo htmlspecialchars($hospital['name']); ?></div>
                            <div class="hospital-status status-approved">
                                Approved
                            </div>
                        </div>
                        <div class="hospital-details">
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Address:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['address'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Approval Notes:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['admin_notes'] ?? 'No notes'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Registration Date:</span>
                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($hospital['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="hospital-actions">
                            <div style="text-align: center; color: #10b981; font-weight: 500;">
                                <i class="fa-solid fa-check-circle"></i> Active Account
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--surface-color); border-radius: 10px;">
                    <i class="fa-solid fa-hospital" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h3 style="color: var(--muted); margin-bottom: 10px;">No Approved Hospitals</h3>
                    <p style="color: #94a3b8;">No hospital accounts have been approved yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
/* GRID */
.hospital-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

/* CARD */
.hospital-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 22px;
    transition: all 0.28s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    position: relative;

    /* NEW — Make all cards equal height */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 330px;
}

.hospital-card:hover {
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
}

/* HEADER */
.hospital-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid #e5e7eb;
}

.hospital-name {
    font-weight: 600;
    color: #111827;
    font-size: 18px;
}

/* STATUS BADGE */
.hospital-status {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.status-pending {
     background: #049ebb;
    color: #ffff;
}

.status-approved {
    background: #d1fae5;
    color: #0f5132;
}

/* DETAILS */
.hospital-details {
    margin-bottom: 18px;

    /* NEW — equal alignment inside cards */
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
    padding: 5px 0;
}

.detail-label {
    color: #6b7280;
    font-weight: 500;
}

.detail-value {
    color: #1f2937;
    font-weight: 600;
    text-align: right;
}

/* ACTION AREA */
.hospital-actions {
    border-top: 1px solid #e5e7eb;
    padding-top: 12px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* BUTTONS */
.hospital-actions button {
    padding: 8px 14px;
    font-size: 13px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    transition: 0.2s;
}

.btn-approve {
    background: #10b981;
    color: white;
}

.btn-approve:hover {
    background: #059669;
}

.btn-reject {
    background: #ef4444;
    color: white;
}

.btn-reject:hover {
    background: #dc2626;
}

</style>

<script>
// Search functionality for hospitals
document.getElementById('hospitalSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.hospital-card');
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>


<?php elseif ($section === 'reports'): ?>
<!-- COVID-19 Reports Section -->
<div class="container">
<div>
    <div class="section-header">
        <div>
            <h3 style="font-size:22px;color:#0b1720;margin-bottom:5px">COVID-19 Test Reports</h3>
            <div style="color:#6b7280;font-size:14px">Manage and track all COVID-19 test results</div>
        </div>
        <div class="action-buttons">
            
            <button class="btn btn-secondary" onclick="openExportModal()">
                <i class="fa-solid fa-download"></i> Export Reports
            </button>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;">
            <i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <i class="fa-solid fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="cards">
        <div class="card">
            <h3>
                <i class="fa-solid fa-vial" style="color: #0ea5a4;"></i>
                Total Tests
            </h3>
            <p><?php echo $totalTests; ?></p>
            <p>COVID-19 tests conducted</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-plus-circle" style="color: #ef4444;"></i>
                Positive Cases
            </h3>
            <p><?php echo $positiveCases; ?></p>
            <p><?php echo $totalTests > 0 ? round(($positiveCases/$totalTests)*100, 1) : 0; ?>% positivity rate</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-minus-circle" style="color: #10b981;"></i>
                Negative Cases
            </h3>
            <p><?php echo $negativeCases; ?></p>
            <p><?php echo $totalTests > 0 ? round(($negativeCases/$totalTests)*100, 1) : 0; ?>% of total tests</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-calendar-day" style="color: #3b82f6;"></i>
                Today's Tests
            </h3>
            <p><?php echo $todayTests; ?></p>
            <p>Tests conducted today</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="panel" style="margin-top: 20px;">
        <h4>Filter Reports</h4>
        <form method="GET" action="admin_dashboard.php" class="filter-form">
            <input type="hidden" name="section" value="reports">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="date_filter">Date Filter:</label>
                    <select name="date_filter" id="date_filter" onchange="toggleCustomDate()">
                        <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>All Time</option>
                        <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="custom" <?= $dateFilter === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                <div class="filter-group" id="custom_date_range" style="display: <?= $dateFilter === 'custom' ? 'flex' : 'none' ?>;">
                    <label for="start_date">From:</label>
                    <input type="date" name="start_date" id="start_date" value="<?= $startDate ?>">
                    <label for="end_date">To:</label>
                    <input type="date" name="end_date" id="end_date" value="<?= $endDate ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-filter"></i> Apply Filter
                    </button>
                    <a href="admin_dashboard.php?section=reports" class="btn btn-secondary">
                        <i class="fa-solid fa-refresh"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="panel" style="margin-top: 20px;">
        <h4>Test Reports</h4>
        
        <?php if ($reportsResult && $reportsResult->num_rows > 0): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Patient</th>
                            <th>Test Date</th>
                            <th>Test Type</th>
                            <th>Result</th>
                            <th>Test Center</th>
                            <th>Severity</th>
                            <th>Doctor</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($report = $reportsResult->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $report['id']; ?></td>
                                <td>
                                    <div class="patient-info-small">
                                        <strong><?php echo htmlspecialchars($report['patient_name']); ?></strong>
                                        <div style="color:#6b7280;font-size:12px">
                                            <?php echo htmlspecialchars($report['patient_phone']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($report['test_date'])); ?></td>
                                <td>
                                    <span class="test-type-badge test-type-<?php echo strtolower(str_replace(' ', '-', $report['test_type'])); ?>">
                                        <?php echo $report['test_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $resultClass = '';
                                    if ($report['result'] === 'Positive') {
                                        $resultClass = 'result-positive';
                                    } elseif ($report['result'] === 'Negative') {
                                        $resultClass = 'result-negative';
                                    } else {
                                        $resultClass = 'result-inconclusive';
                                    }
                                    ?>
                                    <span class="result-badge <?php echo $resultClass; ?>">
                                        <?php echo $report['result']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($report['test_center']); ?></td>
                                <td>
                                    <?php if ($report['severity']): ?>
                                        <span class="severity-badge severity-<?php echo strtolower($report['severity']); ?>">
                                            <?php echo $report['severity']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#6b7280">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $report['doctor_name'] ? htmlspecialchars($report['doctor_name']) : 'N/A'; ?></td>
                                <td>
                                    <div class="action-buttons-small">
                                        <button class="btn-view" onclick="viewCovidReport(<?php echo $report['id']; ?>)" title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="btn-edit" onclick="editCovidReport(<?php echo $report['id']; ?>)" title="Edit Report">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button class="btn-delete" onclick="confirmDeleteReport(<?php echo $report['id']; ?>, 'Report #<?php echo $report['id']; ?>')" title="Delete Report">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <i class="fa-solid fa-file-medical" style="font-size: 48px; margin-bottom: 15px; color: #cbd5e1;"></i>
                <h3 style="margin-bottom: 10px;">No COVID-19 Reports Found</h3>
                <p style="margin-bottom: 20px;">There are no COVID-19 test reports matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- View COVID-19 Report Modal -->
<div id="viewCovidReportModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-file-medical"></i> COVID-19 Test Report Details</h3>
            <button class="close-modal" onclick="closeViewCovidReportModal()">&times;</button>
        </div>
        <div id="viewCovidReportContent">
            <?php if (isset($_GET['view_report']) && $covidReportDetails): ?>
                <div class="patient-details-modal">
                    <div class="detail-section">
                        <h4>Report Information</h4>
                        <div class="detail-row">
                            <span class="detail-label">Report ID:</span>
                            <span class="detail-value">#<?php echo htmlspecialchars($covidReportDetails['id']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Test Date:</span>
                            <span class="detail-value"><?php echo date('M j, Y', strtotime($covidReportDetails['test_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Test Type:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($covidReportDetails['test_type']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Test Result:</span>
                            <span class="detail-value">
                                <?php 
                                $result = $covidReportDetails['result'];
                                $resultClass = '';
                                if ($result === 'Positive') {
                                    $resultClass = 'status-none';
                                } elseif ($result === 'Negative') {
                                    $resultClass = 'status-fully';
                                } else {
                                    $resultClass = 'status-partial';
                                }
                                ?>
                                <span class="status-badge <?php echo $resultClass; ?>"><?php echo htmlspecialchars($result); ?></span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Test Center:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($covidReportDetails['test_center']); ?></span>
                        </div>
                        <?php if (!empty($covidReportDetails['severity'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Severity:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($covidReportDetails['severity']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($covidReportDetails['doctor_name'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Doctor Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($covidReportDetails['doctor_name']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Patient Information</h4>
                        <div class="detail-row">
                            <span class="detail-label">Patient Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($covidReportDetails['patient_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Patient ID:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($covidReportDetails['patient_id']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($covidReportDetails['patient_email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($covidReportDetails['patient_phone']); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($covidReportDetails['symptoms'])): ?>
                    <div class="detail-section">
                        <h4>Symptoms</h4>
                        <div class="detail-row">
                            <span class="detail-value"><?php echo htmlspecialchars($covidReportDetails['symptoms']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($covidReportDetails['notes'])): ?>
                    <div class="detail-section">
                        <h4>Notes</h4>
                        <div class="detail-row">
                            <span class="detail-value"><?php echo htmlspecialchars($covidReportDetails['notes']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-section">
                        <h4>Report Information</h4>
                        <div class="detail-row">
                            <span class="detail-label">Report Created:</span>
                            <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($covidReportDetails['created_at'])); ?></span>
                        </div>
                        <?php if ($covidReportDetails['updated_at'] && $covidReportDetails['updated_at'] != $covidReportDetails['created_at']): ?>
                        <div class="detail-row">
                            <span class="detail-label">Last Updated:</span>
                            <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($covidReportDetails['updated_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <p>COVID-19 report details not found.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeViewCovidReportModal()">Close</button>
            <?php if (isset($_GET['view_report']) && $covidReportDetails): ?>
            <button type="button" class="btn btn-primary" onclick="editCovidReport(<?php echo $covidReportDetails['id']; ?>)">
                <i class="fa-solid fa-edit"></i> Edit Report
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>


<!-- Add/Edit COVID Report Modal -->
<div id="covidReportModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="covidReportModalTitle">Add COVID-19 Test Report</h4>
            <span class="close" onclick="closeCovidReportModal()">&times;</span>
        </div>
        <form id="covidReportForm" method="POST" action="admin_dashboard.php?section=reports">
            <input type="hidden" name="action" id="covidReportAction" value="add_covid_report">
            <input type="hidden" name="report_id" id="report_id" value="">
            
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="patient_id">Patient *</label>
                        <select name="patient_id" id="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php
                            $patients = $conn->query("SELECT id, name, phone FROM patients ORDER BY name");
                            while($patient = $patients->fetch_assoc()): ?>
                                <option value="<?php echo $patient['id']; ?>">
                                    <?php echo htmlspecialchars($patient['name']); ?> (<?php echo htmlspecialchars($patient['phone']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="test_date">Test Date *</label>
                        <input type="date" name="test_date" id="test_date" required max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="test_type">Test Type *</label>
                        <select name="test_type" id="test_type" required>
                            <option value="">Select Test Type</option>
                            <option value="RT-PCR">RT-PCR</option>
                            <option value="Rapid Antigen">Rapid Antigen</option>
                            <option value="Antibody">Antibody</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="result">Test Result *</label>
                        <select name="result" id="result" required>
                            <option value="">Select Result</option>
                            <option value="Positive">Positive</option>
                            <option value="Negative">Negative</option>
                            <option value="Inconclusive">Inconclusive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="test_center">Test Center *</label>
                        <input type="text" name="test_center" id="test_center" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="severity">Severity</label>
                        <select name="severity" id="severity">
                            <option value="">Select Severity</option>
                            <option value="Asymptomatic">Asymptomatic</option>
                            <option value="Mild">Mild</option>
                            <option value="Moderate">Moderate</option>
                            <option value="Severe">Severe</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="doctor_name">Doctor Name</label>
                        <input type="text" name="doctor_name" id="doctor_name">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="symptoms">Symptoms</label>
                        <textarea name="symptoms" id="symptoms" rows="3" placeholder="Enter symptoms separated by commas"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Additional notes or observations"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCovidReportModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="covidReportSubmitBtn">Add Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete COVID-19 Report Confirmation Popup -->
<div id="deleteReportPopup" class="delete-popup">
    <div class="delete-popup-content">
        <h3><i class="fa-solid fa-trash"></i> Delete COVID-19 Report</h3>
        <p id="deleteReportMessage">Are you sure you want to delete this COVID-19 report?</p>
        <form id="deleteReportForm" method="POST" action="admin_dashboard.php?section=reports">
            <input type="hidden" name="action" value="delete_covid_report">
            <input type="hidden" name="report_id" id="deleteReportId">
            <div class="delete-popup-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteReportPopup()">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Export Modal -->
<div id="exportModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h4>Export COVID-19 Reports</h4>
            <span class="close" onclick="closeExportModal()">&times;</span>
        </div>
        <form method="GET" action="admin_dashboard.php">
            <input type="hidden" name="section" value="reports">
            <input type="hidden" name="export_covid_reports" value="xls">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="export_filter">Export Time Period *</label>
                    <select name="export_filter" id="export_filter" required onchange="toggleExportCustomDate()">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">Last 7 Days</option>
                        <option value="month">Last 30 Days</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                
                <div class="form-group" id="export_custom_date_range" style="display: none;">
                    <label for="export_start_date">Start Date</label>
                    <input type="date" name="export_start_date" id="export_start_date" max="<?php echo date('Y-m-d'); ?>">
                    
                    <label for="export_end_date">End Date</label>
                    <input type="date" name="export_end_date" id="export_end_date" max="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExportModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-download"></i> Export to Excel
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* COVID-19 Reports Specific Styles */
.filter-form {
    margin-top: 15px;
}

.filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 15px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-group label {
    font-weight: 500;
    color: var(--heading-color);
    font-size: 14px;
}

.filter-group select,
.filter-group input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.table-container {
    overflow-x: auto;
    margin-top: 15px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.data-table th {
    background: #f8fafc;
    font-weight: 600;
    color: var(--heading-color);
    font-size: 14px;
}

.data-table td {
    font-size: 14px;
}

.patient-info-small strong {
    display: block;
    margin-bottom: 2px;
}

/* Action Buttons - Exactly like the image */
.action-buttons-small {
    display: flex;
    gap: 8px;
}

.btn-view, .btn-edit, .btn-delete {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.2s ease;
}

.btn-view {
    background: #dbeafe;
    color: #1d4ed8;
}

.btn-view:hover {
    background: #bfdbfe;
}

.btn-edit {
    background: #fef3c7;
    color: #d97706;
}

.btn-edit:hover {
    background: #fde68a;
}

.btn-delete {
    background: #fee2e2;
    color: #dc2626;
}

.btn-delete:hover {
    background: #fecaca;
}

/* Badge Styles */
.test-type-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.test-type-rt-pcr {
    background: #dbeafe;
    color: #1e40af;
}

.test-type-rapid-antigen {
    background: #fef3c7;
    color: #92400e;
}

.test-type-antibody {
    background: #fce7f3;
    color: #be185d;
}

.test-type-other {
    background: #e5e7eb;
    color: #374151;
}

.result-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.result-positive {
    background: #fee2e2;
    color: #dc2626;
}

.result-negative {
    background: #d1fae5;
    color: #059669;
}

.result-inconclusive {
    background: #fef3c7;
    color: #d97706;
}

.severity-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.severity-asymptomatic {
    background: #e5e7eb;
    color: #374151;
}

.severity-mild {
    background: #d1fae5;
    color: #059669;
}

.severity-moderate {
    background: #fef3c7;
    color: #d97706;
}

.severity-severe {
    background: #fee2e2;
    color: #dc2626;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h4 {
    color: var(--heading-color);
    margin: 0;
    font-size: 18px;
}

.close {
    color: #6b7280;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: var(--default-color);
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Patient Detail View Styles */
.patient-detail-view {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.detail-section h5 {
    color: var(--heading-color);
    margin-bottom: 15px;
    font-size: 16px;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 8px;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-item label {
    font-weight: 500;
    color: var(--heading-color);
    font-size: 14px;
}

.detail-value {
    color: var(--default-color);
    font-size: 14px;
    padding: 8px 0;
    min-height: 20px;
}

/* Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 500;
    color: var(--heading-color);
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    resize: vertical;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--accent-color);
}

/* Button Styles */
.btn {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.btn-primary {
    background: var(--accent-color);
    color: white;
}

.btn-primary:hover {
    background: var(--teal-light);
    transform: translateY(-1px);
}

.btn-secondary {
    background: #049ebb; ;
    color: white;
}

.btn-secondary:hover {
    background: #049ebb; ;
    transform: translateY(-1px);
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
    
    .action-buttons-small {
        flex-direction: column;
    }
    
    .btn-view, .btn-edit, .btn-delete {
        width: 32px;
        height: 32px;
    }
}

/* Popup Overlay */
        .popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 36, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(3px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .popup-box {
            background: linear-gradient(180deg, var(--surface-color), #f8fafa);
            border-radius: var(--radius);
            padding: 28px 32px;
            text-align: center;
            width: 380px;
            max-width: 90%;
            animation: slideUp 0.35s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .popup-box h3 {
            color: var(--heading-color);
            font-size: 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .popup-box p {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--heading-color);
        }

        .form-group select, 
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 14px;
            background-color: white;
            transition: border-color 0.2s;
        }

        .form-group select:focus, 
        .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .date-range {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .date-range .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .popup-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 10px;
        }

        .popup-actions button {
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.25s ease, transform 0.2s;
            min-width: 100px;
        }

        .cancel-btn {
            background: var(--nav-color);
            color: var(--heading-color);
            border: 1px solid var(--nav-color);
        }

        .cancel-btn:hover {
            background: var(--nav-hover-color);
            transform: scale(1.05);
        }

        .confirm-btn {
            background: var(--accent-color);
            color: var(--contrast-color);
            border: 1px solid var(--accent-color);
        }

        .confirm-btn:hover {
            background: #2563eb;
            transform: scale(1.05);
        }

        /* Message Toast */
        .message-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 3000;
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
</style>

<script>
// COVID-19 Reports JavaScript
let currentReportId = null;

function toggleCustomDate() {
    const dateFilter = document.getElementById('date_filter');
    const customDateRange = document.getElementById('custom_date_range');
    
    if (dateFilter.value === 'custom') {
        customDateRange.style.display = 'flex';
    } else {
        customDateRange.style.display = 'none';
    }
}

function toggleExportCustomDate() {
    const exportFilter = document.getElementById('export_filter');
    const exportCustomDateRange = document.getElementById('export_custom_date_range');
    
    if (exportFilter.value === 'custom') {
        exportCustomDateRange.style.display = 'block';
    } else {
        exportCustomDateRange.style.display = 'none';
    }
}

// COVID-19 Report Modal Functions
function viewCovidReport(reportId) {
    // URL change without page reload
    const url = new URL(window.location);
    url.searchParams.set('view_report', reportId);
    window.history.pushState({}, '', url);
    
    // Fetch report data and open view modal using AJAX
    fetch(`admin_dashboard.php?ajax_get_covid_report=1&report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update modal content with report data
                updateViewCovidReportModalContent(data.report);
                document.getElementById('viewCovidReportModal').style.display = 'flex';
            } else {
                alert('Error loading report details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading report details');
        });
}

function updateViewCovidReportModalContent(report) {
    const modalContent = document.getElementById('viewCovidReportContent');
    
    // Set result badge class
    let resultClass = '';
    if (report.result === 'Positive') {
        resultClass = 'status-none';
    } else if (report.result === 'Negative') {
        resultClass = 'status-fully';
    } else {
        resultClass = 'status-partial';
    }
    
    modalContent.innerHTML = `
        <div class="patient-details-modal">
            <div class="detail-section">
                <h4>Report Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Report ID:</span>
                    <span class="detail-value">#${report.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Test Date:</span>
                    <span class="detail-value">${new Date(report.test_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Test Type:</span>
                    <span class="detail-value">${report.test_type}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Test Result:</span>
                    <span class="detail-value">
                        <span class="status-badge ${resultClass}">${report.result}</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Test Center:</span>
                    <span class="detail-value">${report.test_center}</span>
                </div>
                ${report.severity ? `
                <div class="detail-row">
                    <span class="detail-label">Severity:</span>
                    <span class="detail-value">${report.severity}</span>
                </div>
                ` : ''}
                ${report.doctor_name ? `
                <div class="detail-row">
                    <span class="detail-label">Doctor Name:</span>
                    <span class="detail-value">${report.doctor_name}</span>
                </div>
                ` : ''}
            </div>
            
            <div class="detail-section">
                <h4>Patient Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Patient Name:</span>
                    <span class="detail-value">${report.patient_name}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Patient ID:</span>
                    <span class="detail-value">${report.patient_id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${report.patient_email}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">${report.patient_phone}</span>
                </div>
            </div>
            
            ${report.symptoms ? `
            <div class="detail-section">
                <h4>Symptoms</h4>
                <div class="detail-row">
                    <span class="detail-value">${report.symptoms}</span>
                </div>
            </div>
            ` : ''}
            
            ${report.notes ? `
            <div class="detail-section">
                <h4>Notes</h4>
                <div class="detail-row">
                    <span class="detail-value">${report.notes}</span>
                </div>
            </div>
            ` : ''}
            
            <div class="detail-section">
                <h4>Report Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Report Created:</span>
                    <span class="detail-value">${new Date(report.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                </div>
                ${report.updated_at && report.updated_at !== report.created_at ? `
                <div class="detail-row">
                    <span class="detail-label">Last Updated:</span>
                    <span class="detail-value">${new Date(report.updated_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                </div>
                ` : ''}
            </div>
        </div>
    `;
}

function closeViewCovidReportModal() {
    document.getElementById('viewCovidReportModal').style.display = 'none';
    
    // Remove view_report parameter from URL without page reload
    const url = new URL(window.location);
    url.searchParams.delete('view_report');
    window.history.replaceState({}, '', url);
}

function editCovidReport(reportId) {
    // First close any open modals
    closeViewCovidReportModal();
    
    // URL update without page reload
    const url = new URL(window.location);
    url.searchParams.set('edit_report', reportId);
    window.history.pushState({}, '', url);
    
    // Fetch report data and open edit modal
    fetch(`admin_dashboard.php?ajax_get_covid_report=1&report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fill the form with report data
                document.getElementById('covidReportModalTitle').innerHTML = '<i class="fa-solid fa-file-medical"></i> Edit COVID-19 Test Report';
                document.getElementById('covidReportAction').value = 'edit_covid_report';
                document.getElementById('covidReportSubmitBtn').textContent = 'Update Report';
                document.getElementById('report_id').value = data.report.id;
                document.getElementById('patient_id').value = data.report.patient_id;
                document.getElementById('test_date').value = data.report.test_date;
                document.getElementById('test_type').value = data.report.test_type;
                document.getElementById('result').value = data.report.result;
                document.getElementById('test_center').value = data.report.test_center;
                document.getElementById('symptoms').value = data.report.symptoms || '';
                document.getElementById('severity').value = data.report.severity || '';
                document.getElementById('doctor_name').value = data.report.doctor_name || '';
                document.getElementById('notes').value = data.report.notes || '';
                
                // Show the edit modal
                document.getElementById('covidReportModal').style.display = 'flex';
            } else {
                alert('Error loading report details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading report details');
        });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const viewModal = document.getElementById('viewCovidReportModal');
    if (event.target === viewModal) {
        closeViewCovidReportModal();
    }
}

// Add/Edit Report Modal Functions
function openAddCovidReportModal() {
    document.getElementById('covidReportModalTitle').textContent = 'Add COVID-19 Test Report';
    document.getElementById('covidReportAction').value = 'add_covid_report';
    document.getElementById('covidReportSubmitBtn').textContent = 'Add Report';
    document.getElementById('covidReportForm').reset();
    document.getElementById('report_id').value = '';
    document.getElementById('covidReportModal').style.display = 'block';
}

function editCovidReport(reportId) {
    // Fetch report data via AJAX
    fetch(`admin_dashboard.php?ajax_get_covid_report=1&report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const report = data.report;
                
                document.getElementById('covidReportModalTitle').textContent = 'Edit COVID-19 Test Report';
                document.getElementById('covidReportAction').value = 'edit_covid_report';
                document.getElementById('covidReportSubmitBtn').textContent = 'Update Report';
                document.getElementById('report_id').value = report.id;
                document.getElementById('patient_id').value = report.patient_id;
                document.getElementById('test_date').value = report.test_date;
                document.getElementById('test_type').value = report.test_type;
                document.getElementById('result').value = report.result;
                document.getElementById('test_center').value = report.test_center;
                document.getElementById('symptoms').value = report.symptoms || '';
                document.getElementById('severity').value = report.severity || '';
                document.getElementById('doctor_name').value = report.doctor_name || '';
                document.getElementById('notes').value = report.notes || '';
                
                document.getElementById('covidReportModal').style.display = 'block';
            } else {
                alert('Error loading report details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading report details');
        });
}

function closeCovidReportModal() {
    document.getElementById('covidReportModal').style.display = 'none';
}

// Delete Report Modal Functions
function confirmDeleteReport(reportId, reportName) {
    document.getElementById('deleteReportId').value = reportId;
    document.getElementById('deleteReportMessage').textContent = `Are you sure you want to delete COVID-19 Report #${reportId}?`;
    document.getElementById('deleteReportPopup').style.display = 'flex';
}

function closeDeleteReportPopup() {
    document.getElementById('deleteReportPopup').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const viewModal = document.getElementById('viewCovidReportModal');
    const covidModal = document.getElementById('covidReportModal');
    const deletePopup = document.getElementById('deleteReportPopup');
    const exportModal = document.getElementById('exportModal');
    
    if (event.target === viewModal) {
        viewModal.style.display = 'none';
    }
    if (event.target === covidModal) {
        covidModal.style.display = 'none';
    }
    if (event.target === deletePopup) {
        deletePopup.style.display = 'none';
    }
    if (event.target === exportModal) {
        exportModal.style.display = 'none';
    }
}

// Export Modal Functions
function openExportModal() {
    document.getElementById('exportModal').style.display = 'block';
}

function closeExportModal() {
    document.getElementById('exportModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const viewModal = document.getElementById('viewCovidReportModal');
    const covidModal = document.getElementById('covidReportModal');
    const deleteModal = document.getElementById('deleteReportModal');
    const exportModal = document.getElementById('exportModal');
    
    if (event.target === viewModal) {
        viewModal.style.display = 'none';
    }
    if (event.target === covidModal) {
        covidModal.style.display = 'none';
    }
    if (event.target === deleteModal) {
        deleteModal.style.display = 'none';
    }
    if (event.target === exportModal) {
        exportModal.style.display = 'none';
    }
}

// Initialize date fields with today's date
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('test_date').max = today;
    document.getElementById('export_start_date').max = today;
    document.getElementById('export_end_date').max = today;
});
</script>

<?php elseif ($section === 'vaccines'): ?>
<!-- Enhanced Vaccine List Section -->
<main class="container">
        <div class="section-header">
        <div>
            <h3 style="font-size:22px;color:var(--heading-color);margin-bottom:5px">Vaccine Management</h3>
            <div style="color:var(--muted);font-size:14px">Manage and view all vaccine availability and details</div>
        </div>
        <div class="search-box">
            <input type="text" id="vaccineSearch" placeholder="Search vaccines by name...">
            <button><i class="fa-solid fa-search"></i> Search</button>
        </div>
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="openAddVaccineModal()">
                <i class="fa-solid fa-plus"></i> Add Vaccine
            </button>
            <!-- <a href="admin_dashboard.php?section=vaccines&export_vaccines=xls" class="btn btn-secondary">
                <i class="fa-solid fa-download"></i> Export XLS
            </a> -->
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;">
            <i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <i class="fa-solid fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Vaccine Statistics Cards -->
    <div class="cards">
        <div class="card">
            <h3>
                <i class="fa-solid fa-syringe" style="color: #0ea5a4;"></i>
                Total Vaccines
            </h3>
            <p><?php echo $totalVaccines; ?></p>
            <p>All vaccine types</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-check-circle" style="color: #10b981;"></i>
                Available Vaccines
            </h3>
            <p><?php echo $availableVaccines; ?></p>
            <p><?php echo $totalVaccines > 0 ? round(($availableVaccines/$totalVaccines)*100, 1) : 0; ?>% of total</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-times-circle" style="color: #ef4444;"></i>
                Unavailable Vaccines
            </h3>
            <p><?php echo $unavailableVaccines; ?></p>
            <p><?php echo $totalVaccines > 0 ? round(($unavailableVaccines/$totalVaccines)*100, 1) : 0; ?>% of total</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-chart-line" style="color: #3b82f6;"></i>
                Average Doses
            </h3>
            <p>
                <?php 
                $avgDoses = $conn->query("SELECT AVG(doses_required) as avg FROM vaccines")->fetch_assoc()['avg'];
                echo $avgDoses ? round($avgDoses, 1) : '0';
                ?>
            </p>
            <p>Required per vaccine</p>
        </div>
    </div>

    <!-- Vaccine List Section -->
    <div class="panel" style="margin-top: 20px;">
        <h4>Vaccine Inventory</h4>
        
        <!-- View Toggle -->
        <div class="view-toggle">
            <div class="view-option active" id="grid-view-vaccines">Grid View</div>
            <div class="view-option" id="table-view-vaccines">Table View</div>
        </div>

        <!-- Vaccine Cards View -->
        <div class="vaccines-grid" id="vaccinesGridView">
            <?php if (!empty($vaccinesData)): ?>
                <?php foreach($vaccinesData as $vaccine): ?>
                    <div class="vaccine-card">
                        <div class="vaccine-header">
                            <div class="vaccine-name"><?php echo htmlspecialchars($vaccine['name']); ?></div>
                            <div class="vaccine-status <?php echo $vaccine['status'] === 'available' ? 'status-available' : 'status-unavailable'; ?>">
                                <?php echo ucfirst($vaccine['status']); ?>
                            </div>
                        </div>
                        <div class="vaccine-details">
                            <div class="detail-row">
                                <span class="detail-label">Description:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($vaccine['description'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Doses Required:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($vaccine['doses_required'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Storage Temp:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($vaccine['storage_temp'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Efficacy Rate:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($vaccine['efficacy_rate'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Added On:</span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($vaccine['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="vaccine-actions">
                            <!-- <button class="btn-view" onclick="viewVaccine(<?php echo $vaccine['id']; ?>)" title="View">
                                <i class="fa-solid fa-eye"></i> View
                            </button> -->
                            <button class="btn-edit" onclick="editVaccine(<?php echo $vaccine['id']; ?>)" title="Edit">
                                <i class="fa-solid fa-edit"></i> Edit
                            </button>
                            <button class="btn-status" onclick="openStatusUpdateModal(<?php echo $vaccine['id']; ?>, '<?php echo $vaccine['status']; ?>', '<?php echo htmlspecialchars($vaccine['name']); ?>')" title="Toggle Status">
                                <i class="fa-solid fa-toggle-<?php echo $vaccine['status'] === 'available' ? 'on' : 'off'; ?>"></i> Status
                            </button>
                            <button class="btn-delete" onclick="confirmDeleteVaccine(<?php echo $vaccine['id']; ?>, '<?php echo htmlspecialchars($vaccine['name']); ?>')" title="Delete">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--surface-color); border-radius: 10px;">
                    <i class="fa-solid fa-syringe" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h3 style="color: var(--muted); margin-bottom: 10px;">No Vaccines Found</h3>
                    <p style="color: #94a3b8;">There are no vaccines in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Vaccine Table View -->
        <div class="vaccines-table-container" id="vaccinesTableView" style="display: none;">
            <table class="vaccines-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Doses Required</th>
                        <th>Storage Temp</th>
                        <th>Efficacy Rate</th>
                        <th>Added Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vaccinesData)): ?>
                        <?php foreach($vaccinesData as $vaccine): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vaccine['id']); ?></td>
                                <td><?php echo htmlspecialchars($vaccine['name']); ?></td>
                                <td><?php echo htmlspecialchars($vaccine['description'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="vaccine-status <?php echo $vaccine['status'] === 'available' ? 'status-available' : 'status-unavailable'; ?>">
                                        <?php echo ucfirst($vaccine['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($vaccine['doses_required'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($vaccine['storage_temp'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($vaccine['efficacy_rate'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($vaccine['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <!-- <button class="btn-view" onclick="viewVaccine(<?php echo $vaccine['id']; ?>)" title="View">
                                            <i class="fa-solid fa-eye"></i> -->
                                        </button>
                                        <button class="btn-edit" onclick="editVaccine(<?php echo $vaccine['id']; ?>)" title="Edit">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button class="btn-status" onclick="openStatusUpdateModal(<?php echo $vaccine['id']; ?>, '<?php echo $vaccine['status']; ?>', '<?php echo htmlspecialchars($vaccine['name']); ?>')" title="Toggle Status">
                                            <i class="fa-solid fa-toggle-<?php echo $vaccine['status'] === 'available' ? 'on' : 'off'; ?>"></i>
                                        </button>
                                        <button class="btn-delete" onclick="confirmDeleteVaccine(<?php echo $vaccine['id']; ?>, '<?php echo htmlspecialchars($vaccine['name']); ?>')" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="no-data">
                                <i class="fa-solid fa-syringe"></i>
                                <h3>No Vaccines Found</h3>
                                <p>There are no vaccines in the system yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<?php elseif ($section === 'approval'): ?>
<!-- Enhanced Appointment Approval Section -->
<main class="container">
    <div class="section-header">
        <div>
            <h3 style="font-size:22px;color:var(--heading-color);margin-bottom:5px">Appointment Approval Management</h3>
            <div style="color:var(--muted);font-size:14px">Approve or reject patient appointment requests</div>
        </div>
        <div class="search-box">
            <input type="text" id="approvalSearch" placeholder="Search patients or hospitals...">
            <button><i class="fa-solid fa-search"></i> Search</button>
        </div>
        <div class="action-buttons">
            <a href="admin_dashboard.php?section=approval&export_approval=xls" class="btn btn-primary">
                <i class="fa-solid fa-download"></i> Export XLS
            </a>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;">
            <i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <i class="fa-solid fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Approval Statistics Cards -->
    <div class="cards">
        <div class="card">
            <h3>
                <i class="fa-solid fa-clock" style="color: #f59e0b;"></i>
                Pending Appointments
            </h3>
            <p><?php echo count($pendingRequests); ?></p>
            <p>Awaiting approval</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-check-circle" style="color: #10b981;"></i>
                Approved
            </h3>
            <p><?php echo count($approvedRequests); ?></p>
            <p>Approved appointments</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-times-circle" style="color: #ef4444;"></i>
                Rejected
            </h3>
            <p><?php echo count($rejectedRequests); ?></p>
            <p>Rejected appointments</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-calendar-check" style="color: #3b82f6;"></i>
                Total Requests
            </h3>
            <p><?php echo count($pendingRequests) + count($approvedRequests) + count($rejectedRequests); ?></p>
            <p>All appointment requests</p>
        </div>
    </div>

    <!-- Pending Requests Section -->
    <div class="panel" style="margin-top: 20px;">
        <h4><i class="fa-solid fa-clock"></i> Pending Appointment Requests (<?php echo count($pendingRequests); ?>)</h4>
        
        <!-- Pending Requests Grid View -->
        <div class="approval-grid" id="pendingGridView">
            <?php if (!empty($pendingRequests)): ?>
                <?php foreach($pendingRequests as $request): ?>
                    <div class="approval-card">
                        <div class="approval-header">
                            <div class="approval-name"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                            <div class="approval-status status-pending">
                                Pending
                            </div>
                        </div>
                        <div class="approval-details">
                            <div class="detail-row">
                                <span class="detail-label">Patient Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['patient_email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Patient Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['patient_phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Hospital:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['hospital_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Test Type:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['test_type']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Appointment Date:</span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($request['appointment_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Appointment Time:</span>
                                <span class="detail-value"><?php echo date('g:i A', strtotime($request['appointment_time'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Requested On:</span>
                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="approval-actions">
                            <form method="POST" class="approval-form">
                                <input type="hidden" name="action" value="approve_appointment">
                                <input type="hidden" name="appointment_id" value="<?php echo $request['id']; ?>">
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <input type="text" name="admin_notes" placeholder="Add notes (optional)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <button type="submit" class="btn btn-success" style="width: 100%;">
                                    <i class="fa-solid fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" class="approval-form">
                                <input type="hidden" name="action" value="reject_appointment">
                                <input type="hidden" name="appointment_id" value="<?php echo $request['id']; ?>">
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <input type="text" name="admin_notes" placeholder="Add rejection reason" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                                </div>
                                <button type="submit" class="btn btn-danger" style="width: 100%;">
                                    <i class="fa-solid fa-times"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--surface-color); border-radius: 10px;">
                    <i class="fa-solid fa-check-circle" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h3 style="color: var(--muted); margin-bottom: 10px;">No Pending Appointment Requests</h3>
                    <p style="color: #94a3b8;">There are no pending appointment requests at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- History Section with Tabs -->
    <div class="history-section">
        <div class="history-tabs">
            <button class="history-tab active" onclick="openApprovalHistoryTab('approved')">Approved Appointments (<?php echo count($approvedRequests); ?>)</button>
            <button class="history-tab" onclick="openApprovalHistoryTab('rejected')">Rejected Appointments (<?php echo count($rejectedRequests); ?>)</button>
        </div>

        <!-- Approved Requests -->
        <div id="approved" class="history-content active">
            <div class="approval-grid">
                <?php if (!empty($approvedRequests)): ?>
                    <?php foreach($approvedRequests as $request): ?>
                        <div class="approval-card">
                            <div class="approval-header">
                                <div class="approval-name"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                                <div class="approval-status status-approved">
                                    Approved
                                </div>
                            </div>
                            <div class="approval-details">
                                <div class="detail-row">
                                    <span class="detail-label">Patient Email:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['patient_email']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Hospital:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['hospital_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Test Type:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['test_type']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Appointment Date:</span>
                                    <span class="detail-value"><?php echo date('M j, Y', strtotime($request['appointment_date'])); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Appointment Time:</span>
                                    <span class="detail-value"><?php echo date('g:i A', strtotime($request['appointment_time'])); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Approved On:</span>
                                    <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--surface-color); border-radius: 10px;">
                        <i class="fa-solid fa-history" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                        <h3 style="color: var(--muted); margin-bottom: 10px;">No Approved Appointments</h3>
                        <p style="color: #94a3b8;">No appointment requests have been approved yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rejected Requests -->
        <div id="rejected" class="history-content">
            <div class="approval-grid">
                <?php if (!empty($rejectedRequests)): ?>
                    <?php foreach($rejectedRequests as $request): ?>
                        <div class="approval-card">
                            <div class="approval-header">
                                <div class="approval-name"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                                <div class="approval-status status-rejected">
                                    Rejected
                                </div>
                            </div>
                            <div class="approval-details">
                                <div class="detail-row">
                                    <span class="detail-label">Patient Email:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['patient_email']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Hospital:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['hospital_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Test Type:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['test_type']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Appointment Date:</span>
                                    <span class="detail-value"><?php echo date('M j, Y', strtotime($request['appointment_date'])); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Rejection Reason:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['admin_notes'] ?? 'No reason provided'); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Rejected On:</span>
                                    <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--surface-color); border-radius: 10px;">
                        <i class="fa-solid fa-history" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                        <h3 style="color: var(--muted); margin-bottom: 10px;">No Rejected Appointments</h3>
                        <p style="color: #94a3b8;">No appointment requests have been rejected yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function openApprovalHistoryTab(tabName) {
    // Hide all tab content
    var tabcontent = document.getElementsByClassName("history-content");
    for (var i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
    }

    // Remove active class from all tabs
    var tablinks = document.getElementsByClassName("history-tab");
    for (var i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    // Show the specific tab content and add active class to the button
    document.getElementById(tabName).classList.add("active");
    event.currentTarget.classList.add("active");
}

// Search functionality
document.getElementById('approvalSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.approval-card');
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

<?php elseif ($section === 'hospitals'): ?>
<!-- Enhanced Hospital List Section -->
<main class="container">
        <div class="section-header">
        <div>
            <h3 style="font-size:22px;color:var(--heading-color);margin-bottom:5px">Hospital Management</h3>
            <div style="color:var(--muted);font-size:14px">Manage and view all hospital details and status</div>
        </div>
        <div class="search-box">
            <input type="text" id="hospitalSearch" placeholder="Search hospitals by name...">
            <button><i class="fa-solid fa-search"></i> Search</button>
        </div>
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="openAddHospitalModal()">
                <i class="fa-solid fa-plus"></i> Add Hospital
            </button>
            <!-- <a href="admin_dashboard.php?section=hospitals&export_hospitals=xls" class="btn btn-secondary">
                <i class="fa-solid fa-download"></i> Export XLS
            </a> -->
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;">
            <i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <i class="fa-solid fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Hospital Statistics Cards -->
    <div class="cards">
        <div class="card">
            <h3>
                <i class="fa-solid fa-hospital" style="color: #0ea5a4;"></i>
                Total Hospitals
            </h3>
            <p><?php echo $totalHospitals; ?></p>
            <p>All hospital types</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-check-circle" style="color: #10b981;"></i>
                Active Hospitals
            </h3>
            <p><?php echo $activeHospitals; ?></p>
            <p><?php echo $totalHospitals > 0 ? round(($activeHospitals/$totalHospitals)*100, 1) : 0; ?>% of total</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-times-circle" style="color: #ef4444;"></i>
                Inactive Hospitals
            </h3>
            <p><?php echo $inactiveHospitals; ?></p>
            <p><?php echo $totalHospitals > 0 ? round(($inactiveHospitals/$totalHospitals)*100, 1) : 0; ?>% of total</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-chart-line" style="color: #3b82f6;"></i>
                Recent Additions
            </h3>
            <p>
                <?php 
                $recentHospitals = $conn->query("SELECT COUNT(*) as count FROM hospitals WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
                echo $recentHospitals;
                ?>
            </p>
            <p>Last 7 days</p>
        </div>
    </div>

    <!-- Hospital List Section -->
    <div class="panel" style="margin-top: 20px;">
        <h4>Hospital Directory</h4>
        
        <!-- View Toggle -->
        <div class="view-toggle">
            <div class="view-option active" id="grid-view-hospitals">Grid View</div>
            <div class="view-option" id="table-view-hospitals">Table View</div>
        </div>

        <!-- Hospital Cards View -->
        <div class="hospitals-grid" id="hospitalsGridView">
            <?php if (!empty($hospitalsData)): ?>
                <?php foreach($hospitalsData as $hospital): ?>
                    <div class="hospital-card">
                        <div class="hospital-header">
                            <div class="hospital-name"><?php echo htmlspecialchars($hospital['name']); ?></div>
                            <div class="hospital-status <?php echo $hospital['status'] === 'active' ? 'status-available' : 'status-unavailable'; ?>">
                                <?php echo ucfirst($hospital['status']); ?>
                            </div>
                        </div>
                        <div class="hospital-details">
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Address:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['address'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">License No:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['license_number'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Admin:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($hospital['admin_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Added On:</span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($hospital['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="hospital-actions">
                            <button class="btn-status" onclick="openHospitalStatusUpdateModal(<?php echo $hospital['id']; ?>, '<?php echo $hospital['status']; ?>', '<?php echo htmlspecialchars($hospital['name']); ?>')" title="Toggle Status">
                                <i class="fa-solid fa-toggle-<?php echo $hospital['status'] === 'active' ? 'on' : 'off'; ?>"></i> Status
                            </button>
                            <button class="btn-delete" onclick="confirmDeleteHospital(<?php echo $hospital['id']; ?>, '<?php echo htmlspecialchars($hospital['name']); ?>')" title="Delete">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--surface-color); border-radius: 10px;">
                    <i class="fa-solid fa-hospital" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h3 style="color: var(--muted); margin-bottom: 10px;">No Hospitals Found</h3>
                    <p style="color: #94a3b8;">There are no hospitals in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hospital Table View -->
        <div class="hospitals-table-container" id="hospitalsTableView" style="display: none;">
            <table class="hospitals-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>License No</th>
                        <th>Admin</th>
                        <th>Status</th>
                        <th>Added Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($hospitalsData)): ?>
                        <?php foreach($hospitalsData as $hospital): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hospital['id']); ?></td>
                                <td><?php echo htmlspecialchars($hospital['name']); ?></td>
                                <td><?php echo htmlspecialchars($hospital['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($hospital['address'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($hospital['license_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($hospital['admin_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="hospital-status <?php echo $hospital['status'] === 'active' ? 'status-available' : 'status-unavailable'; ?>">
                                        <?php echo ucfirst($hospital['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($hospital['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <button class="btn-status" onclick="openHospitalStatusUpdateModal(<?php echo $hospital['id']; ?>, '<?php echo $hospital['status']; ?>', '<?php echo htmlspecialchars($hospital['name']); ?>')" title="Toggle Status">
                                            <i class="fa-solid fa-toggle-<?php echo $hospital['status'] === 'active' ? 'on' : 'off'; ?>"></i>
                                        </button>
                                        <button class="btn-delete" onclick="confirmDeleteHospital(<?php echo $hospital['id']; ?>, '<?php echo htmlspecialchars($hospital['name']); ?>')" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="no-data">
                                <i class="fa-solid fa-hospital"></i>
                                <h3>No Hospitals Found</h3>
                                <p>There are no hospitals in the system yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Hospital Modal -->
<div id="addHospitalModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plus"></i> Add New Hospital</h3>
            <button class="close-modal" onclick="closeAddHospitalModal()">&times;</button>
        </div>

        <?php if (!empty($_SESSION['form_errors'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php 
    $errors = $_SESSION['form_errors'];
    $old = $_SESSION['old_input'] ?? [];
    unset($_SESSION['form_errors'], $_SESSION['old_input']);
    ?>

    // Fill old values
    <?php if (!empty($old)): ?>
        <?php foreach ($old as $key => $val): ?>
            const field = document.querySelector('[name="<?= htmlspecialchars($key) ?>"]');
            if (field) field.value = <?= json_encode($val) ?>;
        <?php endforeach; ?>
    <?php endif; ?>

    // Show errors
    <?php foreach ($errors as $key => $msg): ?>
        const errEl = document.getElementById('<?= $key ?>');
        if (errEl) {
            errEl.textContent = <?= json_encode($msg) ?>;
            errEl.style.display = 'block';
        }
        const input = document.querySelector('[name="<?= str_replace('-error', '', $key) ?>"]');
        if (input) input.classList.add('is-invalid');
    <?php endforeach; ?>
});
</script>
<?php endif; ?>

        <form id="addHospitalForm" method="POST" novalidate>
            <input type="hidden" name="action" value="add_hospital">
            
            <div class="form-group">
                <label for="hospital_name">Hospital Name</label>
                <input type="text" id="hospital_name" name="hospital_name" required 
                       placeholder="Enter hospital name">
                <div class="error-message" id="name-error"></div>
            </div>
            
            <div class="form-group">
                <label for="hospital_email">Email</label>
                <input type="email" id="hospital_email" name="hospital_email" required 
                       placeholder="Enter hospital email">
                <div class="error-message" id="email-error"></div>
            </div>
            
            <div class="form-group">
                <label for="hospital_address">Address</label>
                <textarea id="hospital_address" name="hospital_address" 
                          placeholder="Enter hospital address..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="hospital_license">License Number</label>
                <input type="text" id="hospital_license" name="hospital_license" 
                       placeholder="Enter license number">
            </div>
            
            <div class="form-group">
                <label for="hospital_admin">Admin Name</label>
                <input type="text" id="hospital_admin" name="hospital_admin" 
                       placeholder="Enter admin name">
            </div>
            
            <div class="form-group">
                <label for="hospital_status">Status</label>
                <select id="hospital_status" name="hospital_status" required>
                    <option value="">Select Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <div class="error-message" id="status-error"></div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddHospitalModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submit-hospital-btn">Add Hospital</button>
            </div>
        </form>
    </div>
</div>

<!-- View Hospital Modal -->
<div id="viewHospitalModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-hospital"></i> Hospital Details</h3>
            <button class="close-modal" onclick="closeViewHospitalModal()">&times;</button>
        </div>
        <div id="viewHospitalContent">
            <!-- Dynamic content will be loaded here -->
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeViewHospitalModal()">Close</button>
        </div>
    </div>
</div>

<!-- Hospital Status Update Modal -->
<div id="hospitalStatusUpdateModal" class="modal-overlay status-update-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-toggle-on"></i> Update Hospital Status</h3>
            <button class="close-modal" onclick="closeHospitalStatusUpdateModal()">&times;</button>
        </div>
        <form id="hospitalStatusUpdateForm" method="POST">
            <input type="hidden" name="action" value="update_hospital_status">
            <input type="hidden" name="hospital_id" id="hospitalStatusUpdateHospitalId">
            <input type="hidden" name="status" id="hospitalNewStatus">
            
            <p>Update status for: <strong id="hospitalStatusUpdateHospitalName"></strong></p>
            
            <div class="status-options">
                <div class="status-option active" id="hospitalStatusActive" onclick="selectHospitalStatus('active')">
                    <div class="status-icon">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div>Active</div>
                </div>
                <div class="status-option inactive" id="hospitalStatusInactive" onclick="selectHospitalStatus('inactive')">
                    <div class="status-icon">
                        <i class="fa-solid fa-times-circle"></i>
                    </div>
                    <div>Inactive</div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeHospitalStatusUpdateModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Hospital Confirmation Popup -->
<div id="deleteHospitalPopup" class="delete-popup">
    <div class="delete-popup-content">
        <h3><i class="fa-solid fa-trash"></i> Delete Hospital</h3>
        <p id="deleteHospitalMessage">Are you sure you want to delete this hospital?</p>
        <form id="deleteHospitalForm" method="POST">
            <input type="hidden" name="action" value="delete_hospital">
            <input type="hidden" name="hospital_id" id="deleteHospitalId">
            <div class="delete-popup-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteHospitalPopup()">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Hospital List Section Styles */
.hospitals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.hospital-card {
    background: var(--surface-color);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.hospital-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.hospital-header {
    background: var(--accent-color);
    color: var(--contrast-color);
    padding: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.hospital-name {
    font-weight: 600;
    font-size: 16px;
}

.hospital-status {
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-available {
    background: rgba(255, 255, 255, 0.2);
    color: var(--contrast-color);
}

.status-unavailable {
    background: #496268;
    color: #ffff;
}

.hospital-details {
    padding: 15px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}

.detail-row:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.detail-label {
    font-weight: 500;
    color: #64748b;
    font-size: 13px;
}

.detail-value {
    font-weight: 600;
    color: var(--default-color);
    font-size: 13px;
    text-align: right;
}

.hospital-actions {
    display: flex;       
    gap: 8px;            
    flex-wrap: wrap;   
}

.hospital-actions button {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    flex: none;      
}


.btn-view {
    background: #dbeafe;
    color: #1d4ed8;
}

.btn-view:hover {
    background: #bfdbfe;
}

.btn-status {
    background: #dcfce7;
    color: #166534;
}

.btn-status:hover {
    background: #bbf7d0;
}

.btn-delete {
    background: #fee2e2;
    color: #991b1b;
}

.btn-delete:hover {
    background: #fecaca;
}

/* Hospital Table Styles */
.hospitals-table-container {
    overflow-x: auto;
    margin-top: 20px;
}

.hospitals-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface-color);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.hospitals-table th {
    background: var(--accent-color);
    color: var(--contrast-color);
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
}

.hospitals-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

.hospitals-table tr:last-child td {
    border-bottom: none;
}

.hospitals-table tr:hover {
    background: #f8fafc;
}

.hospitals-table .btn-view,
.hospitals-table .btn-status,
.hospitals-table .btn-delete {
    padding: 6px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* View Toggle Styles */
.view-toggle {
    display: flex;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 20px;
    width: fit-content;
}

.view-option {
    padding: 8px 16px;
    background-color: var(--surface-color);
    cursor: pointer;
    transition: background-color 0.3s;
    border: none;
    font-size: 14px;
}

.view-option.active {
    background-color: var(--accent-color);
    color: var(--contrast-color);
}

/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    z-index: 10000; /* Higher z-index */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6);
}

.modal-overlay[style*="display: flex"] {
    display: flex !important;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    margin: auto;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    max-height: 85vh;
    overflow-y: auto;
    animation: modalSlideIn 0.3s ease-out;
}

/* Modal animation add karen */
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
    margin: 0;
    color: var(--heading-color);
}

.close-modal {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover {
    color: #000;
}

/* Form Styles */
.form-group {
    margin-bottom: 15px;
    padding: 0 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: var(--heading-color);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--accent-color);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.error-message {
    color: #dc2626;
    font-size: 12px;
    margin-top: 5px;
    display: none;
}

.is-invalid {
    border-color: #dc2626 !important;
}

.form-actions {
    padding: 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e2e8f0;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--accent-color);
    color: var(--contrast-color);
}

.btn-primary:hover:not(:disabled) {
    opacity: 0.9;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

/* Status Update Modal Specific Styles */
.status-update-modal .modal-content {
    max-width: 500px;
}

.status-options {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin: 25px 0;
}

.status-option {
    padding: 20px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    min-width: 120px;
}

.status-option.active {
    border-color: var(--accent-color);
    background-color: rgba(59, 130, 246, 0.1);
}

.status-option.active .status-icon {
    color: var(--accent-color);
}

.status-icon {
    font-size: 24px;
    margin-bottom: 8px;
}

.status-option.active.available {
    border-color: #10b981;
    background-color: rgba(16, 185, 129, 0.1);
}

.status-option.active.available .status-icon {
    color: #10b981;
}

.status-option.active.inactive {
    border-color: #ef4444;
    background-color: rgba(239, 68, 68, 0.1);
}

.status-option.active.inactive .status-icon {
    color: #ef4444;
}

/* Delete Popup Styles */
.delete-popup {
    display: none;
    align-items: center;
    justify-content: center;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.delete-popup-content {
    background-color: var(--surface-color);
    margin: 0 auto;
    border-radius: 10px;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    padding: 20px;
    text-align: center;
}

.delete-popup h3 {
    margin: 0 0 15px 0;
    color: var(--heading-color);
}

.delete-popup p {
    margin: 0 0 20px 0;
    color: var(--default-color);
}

.delete-popup-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.btn-cancel {
    padding: 10px 20px;
    background: #6b7280;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.btn-cancel:hover {
    background: #4b5563;
}

.btn-confirm-delete {
    padding: 10px 20px;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.btn-confirm-delete:hover {
    background: #b91c1c;
}

/* No Data Styles */
.no-data {
    text-align: center;
    padding: 40px;
    color: var(--muted);
}

.no-data i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #cbd5e1;
}

/* Cards Layout */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: var(--surface-color);
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    text-align: center;
}

.card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: var(--muted);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.card p:first-of-type {
    font-size: 24px;
    font-weight: bold;
    margin: 0;
    color: var(--heading-color);
}

.card p:last-of-type {
    font-size: 12px;
    margin: 5px 0 0 0;
    color: var(--muted);
}

/* Section Header */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.search-box {
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-box input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.search-box button {
    padding: 8px 16px;
    background: var(--accent-color);
    color: var(--contrast-color);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

/* Panel Styles */
.panel {
    background: var(--surface-color);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
}

.panel h4 {
    margin: 0 0 20px 0;
    color: var(--heading-color);
    font-size: 18px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hospitals-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        order: 2;
    }
    
    .action-buttons {
        order: 3;
        justify-content: center;
    }
    
    .hospital-actions {
        flex-direction: column;
    }
    
    .modal-content {
        width: 95%;
        margin: 10px;
    }
    
    .status-options {
        flex-direction: column;
        align-items: center;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions button {
        width: 100%;
    }
    
    .cards {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 480px) {
    .cards {
        grid-template-columns: 1fr;
    }
    
    .hospitals-table-container {
        font-size: 12px;
    }
    
    .hospitals-table th,
    .hospitals-table td {
        padding: 8px 10px;
    }
}

</style>

<script>
// Hospital Management JavaScript Functions
console.log('Hospital JavaScript loaded');

// Modal Functions
function openAddHospitalModal() {
    console.log('Opening add hospital modal');
    document.getElementById('addHospitalModal').style.display = 'flex';
}

function closeAddHospitalModal() {
    document.getElementById('addHospitalModal').style.display = 'none';
    document.getElementById('addHospitalForm').reset();
    // Clear error messages
    document.querySelectorAll('.error-message').forEach(el => {
        el.style.display = 'none';
        el.textContent = '';
    });
}

function viewHospital(hospitalId) {
    console.log('View hospital clicked:', hospitalId);
    
    // Show modal immediately
    const modal = document.getElementById('viewHospitalModal');
    modal.style.display = 'flex';
    
    // Show loading state
    document.getElementById('viewHospitalContent').innerHTML = `
        <div style="padding: 20px; text-align: center;">
            <div style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
            <h4 style="color: #64748b; margin-bottom: 10px;">Loading Hospital Details</h4>
            <p style="color: #94a3b8;">Please wait while we fetch the hospital information...</p>
        </div>
    `;
    
    // Fetch hospital details via AJAX
    fetch(`admin_dashboard.php?ajax_get_hospital=1&hospital_id=${hospitalId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(hospital => {
            updateViewHospitalModalContent(hospital);
        })
        .catch(error => {
            console.error('Error fetching hospital details:', error);
            document.getElementById('viewHospitalContent').innerHTML = `
                <div style="padding: 20px; text-align: center;">
                    <div style="font-size: 48px; color: #ef4444; margin-bottom: 15px;">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                    </div>
                    <h4 style="color: #dc2626; margin-bottom: 10px;">Error Loading Details</h4>
                    <p style="color: #ef4444;">Failed to load hospital information. Please try again.</p>
                    <button class="btn btn-primary" onclick="viewHospital(${hospitalId})" style="margin-top: 15px;">
                        <i class="fa-solid fa-refresh"></i> Try Again
                    </button>
                </div>
            `;
        });
}

function updateViewHospitalModalContent(hospital) {
    console.log('Updating modal with hospital data:', hospital);
    
    let statusClass = hospital.status === 'active' ? 'status-available' : 'status-unavailable';
    let statusText = hospital.status === 'active' ? 'Active' : 'Inactive';
    
    document.getElementById('viewHospitalContent').innerHTML = `
        <div style="padding: 20px;">
            <!-- Hospital Basic Info -->
            <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin: 0; color: var(--heading-color);">${escapeHtml(hospital.name)}</h4>
                    <span class="hospital-status ${statusClass}" style="padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        ${statusText}
                    </span>
                </div>
                <div style="color: var(--muted); font-size: 14px;">
                    <div>Hospital ID: #${hospital.id}</div>
                    <div>Registered: ${new Date(hospital.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                </div>
            </div>

            <!-- Contact Information -->
            <div style="margin-bottom: 20px;">
                <h5 style="color: var(--heading-color); margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                    <i class="fa-solid fa-address-card"></i> Contact Information
                </h5>
                <div style="display: grid; gap: 12px;">
                    <div>
                        <strong style="color: var(--muted); font-size: 13px;">Email:</strong>
                        <div style="color: var(--default-color);">${escapeHtml(hospital.email)}</div>
                    </div>
                    <div>
                        <strong style="color: var(--muted); font-size: 13px;">Phone:</strong>
                        <div style="color: var(--default-color);">${escapeHtml(hospital.phone || 'Not provided')}</div>
                    </div>
                    ${hospital.address ? `
                    <div>
                        <strong style="color: var(--muted); font-size: 13px;">Address:</strong>
                        <div style="color: var(--default-color); line-height: 1.4;">${escapeHtml(hospital.address)}</div>
                    </div>
                    ` : ''}
                </div>
            </div>

            <!-- Additional Details -->
            <div style="margin-bottom: 20px;">
                <h5 style="color: var(--heading-color); margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                    <i class="fa-solid fa-info-circle"></i> Additional Details
                </h5>
                <div style="display: grid; gap: 12px;">
                    <div>
                        <strong style="color: var(--muted); font-size: 13px;">License Number:</strong>
                        <div style="color: var(--default-color);">${escapeHtml(hospital.license_number || 'Not provided')}</div>
                    </div>
                    ${hospital.admin_name ? `
                    <div>
                        <strong style="color: var(--muted); font-size: 13px;">Admin Name:</strong>
                        <div style="color: var(--default-color);">${escapeHtml(hospital.admin_name)}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

function closeViewHospitalModal() {
    console.log('Closing view hospital modal');
    const modal = document.getElementById('viewHospitalModal');
    modal.style.display = 'none';
    
    // Reset modal content
    document.getElementById('viewHospitalContent').innerHTML = `
        <div style="padding: 20px; text-align: center;">
            <i class="fa-solid fa-hospital" style="font-size: 48px; color: #cbd5e1;"></i>
        </div>
    `;
}

function openHospitalStatusUpdateModal(hospitalId, currentStatus, hospitalName) {
    console.log('Opening status modal:', hospitalId, currentStatus, hospitalName);
    
    document.getElementById('hospitalStatusUpdateHospitalId').value = hospitalId;
    document.getElementById('hospitalStatusUpdateHospitalName').textContent = hospitalName;
    document.getElementById('hospitalNewStatus').value = currentStatus;
    
    // Update active status visually
    document.querySelectorAll('#hospitalStatusUpdateModal .status-option').forEach(option => {
        option.classList.remove('active');
    });
    
    if (currentStatus === 'active') {
        document.getElementById('hospitalStatusActive').classList.add('active');
    } else {
        document.getElementById('hospitalStatusInactive').classList.add('active');
    }
    
    document.getElementById('hospitalStatusUpdateModal').style.display = 'flex';
}

function selectHospitalStatus(status) {
    console.log('Selected status:', status);
    document.getElementById('hospitalNewStatus').value = status;
    
    // Update visual selection
    document.querySelectorAll('#hospitalStatusUpdateModal .status-option').forEach(option => {
        option.classList.remove('active');
    });
    
    if (status === 'active') {
        document.getElementById('hospitalStatusActive').classList.add('active');
    } else {
        document.getElementById('hospitalStatusInactive').classList.add('active');
    }
}

function closeHospitalStatusUpdateModal() {
    document.getElementById('hospitalStatusUpdateModal').style.display = 'none';
}

function confirmDeleteHospital(hospitalId, hospitalName) {
    console.log('Confirm delete:', hospitalId, hospitalName);
    
    document.getElementById('deleteHospitalId').value = hospitalId;
    document.getElementById('deleteHospitalMessage').textContent = 
        `Are you sure you want to delete hospital "${hospitalName}"? This action cannot be undone.`;
    document.getElementById('deleteHospitalPopup').style.display = 'flex';
}

function closeDeleteHospitalPopup() {
    document.getElementById('deleteHospitalPopup').style.display = 'none';
}

// Utility function
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing hospital functions');
    
    // View toggle functionality
    const gridViewBtn = document.getElementById('grid-view-hospitals');
    const tableViewBtn = document.getElementById('table-view-hospitals');
    
    if (gridViewBtn && tableViewBtn) {
        gridViewBtn.addEventListener('click', function() {
            document.getElementById('hospitalsGridView').style.display = 'grid';
            document.getElementById('hospitalsTableView').style.display = 'none';
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        });
        
        tableViewBtn.addEventListener('click', function() {
            document.getElementById('hospitalsGridView').style.display = 'none';
            document.getElementById('hospitalsTableView').style.display = 'block';
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
        });
    }
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        // View Hospital Modal
        const viewModal = document.getElementById('viewHospitalModal');
        if (event.target === viewModal) {
            viewModal.style.display = 'none';
        }
        
        // Add Hospital Modal
        const addModal = document.getElementById('addHospitalModal');
        if (event.target === addModal) {
            addModal.style.display = 'none';
        }
        
        // Status Update Modal
        const statusModal = document.getElementById('hospitalStatusUpdateModal');
        if (event.target === statusModal) {
            statusModal.style.display = 'none';
        }
        
        // Delete Popup
        const deletePopup = document.getElementById('deleteHospitalPopup');
        if (event.target === deletePopup) {
            deletePopup.style.display = 'none';
        }
    });

    // Escape key to close modals
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.getElementById('viewHospitalModal').style.display = 'none';
            document.getElementById('addHospitalModal').style.display = 'none';
            document.getElementById('hospitalStatusUpdateModal').style.display = 'none';
            document.getElementById('deleteHospitalPopup').style.display = 'none';
        }
    });
});
</script>

<?php elseif ($section === 'approval'): ?>
<!-- Enhanced Appointment Approval Section -->
<main class="container">
    <div class="section-header">
        <div>
            <h3 style="font-size:22px;color:var(--heading-color);margin-bottom:5px">Appointment Approval Management</h3>
            <div style="color:var(--muted);font-size:14px">Approve or reject patient appointment requests</div>
        </div>
        <div class="search-box">
            <input type="text" id="approvalSearch" placeholder="Search patients or hospitals...">
            <button><i class="fa-solid fa-search"></i> Search</button>
        </div>
        <div class="action-buttons">
            <a href="admin_dashboard.php?section=approval&export_approval=xls" class="btn btn-primary">
                <i class="fa-solid fa-download"></i> Export XLS
            </a>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;">
            <i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <i class="fa-solid fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Approval Statistics Cards -->
    <div class="cards">
        <div class="card">
            <h3>
                <i class="fa-solid fa-clock" style="color: #f59e0b;"></i>
                Pending Appointments
            </h3>
            <p><?php echo count($pendingRequests); ?></p>
            <p>Awaiting approval</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-check-circle" style="color: #10b981;"></i>
                Approved
            </h3>
            <p><?php echo count($approvedRequests); ?></p>
            <p>Approved appointments</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-times-circle" style="color: #ef4444;"></i>
                Rejected
            </h3>
            <p><?php echo count($rejectedRequests); ?></p>
            <p>Rejected appointments</p>
        </div>
        <div class="card">
            <h3>
                <i class="fa-solid fa-calendar-check" style="color: #3b82f6;"></i>
                Total Requests
            </h3>
            <p><?php echo count($pendingRequests) + count($approvedRequests) + count($rejectedRequests); ?></p>
            <p>All appointment requests</p>
        </div>
    </div>

    <!-- Pending Requests Section -->
    <div class="panel" style="margin-top: 20px;">
        <h4><i class="fa-solid fa-clock"></i> Pending Appointment Requests (<?php echo count($pendingRequests); ?>)</h4>
        
        <!-- Pending Requests Grid View -->
        <div class="approval-grid" id="pendingGridView">
            <?php if (!empty($pendingRequests)): ?>
                <?php foreach($pendingRequests as $request): ?>
                    <div class="approval-card">
                        <div class="approval-header">
                            <div class="approval-name"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                            <div class="approval-status status-pending">
                                Pending
                            </div>
                        </div>
                        <div class="approval-details">
                            <div class="detail-row">
                                <span class="detail-label">Patient Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['patient_email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Patient Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['patient_phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Hospital:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['hospital_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Test Type:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['test_type']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Appointment Date:</span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($request['appointment_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Appointment Time:</span>
                                <span class="detail-value"><?php echo date('g:i A', strtotime($request['appointment_time'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Requested On:</span>
                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="approval-actions">
                            <form method="POST" class="approval-form">
                                <input type="hidden" name="action" value="approve_appointment">
                                <input type="hidden" name="appointment_id" value="<?php echo $request['id']; ?>">
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <input type="text" name="admin_notes" placeholder="Add notes (optional)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <button type="submit" class="btn btn-success" style="width: 100%;">
                                    <i class="fa-solid fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" class="approval-form">
                                <input type="hidden" name="action" value="reject_appointment">
                                <input type="hidden" name="appointment_id" value="<?php echo $request['id']; ?>">
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <input type="text" name="admin_notes" placeholder="Add rejection reason" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                                </div>
                                <button type="submit" class="btn btn-danger" style="width: 100%;">
                                    <i class="fa-solid fa-times"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--surface-color); border-radius: 10px;">
                    <i class="fa-solid fa-check-circle" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h3 style="color: var(--muted); margin-bottom: 10px;">No Pending Appointment Requests</h3>
                    <p style="color: #94a3b8;">There are no pending appointment requests at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Debug Information (Remove this in production) -->
    <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 14px;">
        <h4>Debug Information:</h4>
        <p>Pending Requests: <?php echo count($pendingRequests); ?></p>
        <p>Approved Requests: <?php echo count($approvedRequests); ?></p>
        <p>Rejected Requests: <?php echo count($rejectedRequests); ?></p>
        <?php if (!empty($approvedRequests)): ?>
            <p>Sample Approved Appointment ID: <?php echo $approvedRequests[0]['id']; ?></p>
            <p>Sample Approved Appointment Status: <?php echo $approvedRequests[0]['appointment_status']; ?></p>
        <?php endif; ?>
    </div>

    <!-- History Section with Tabs -->
    <div class="history-section">
        <div class="history-tabs">
            <button class="history-tab active" onclick="openApprovalHistoryTab('approved')">Approved Appointments (<?php echo count($approvedRequests); ?>)</button>
            <button class="history-tab" onclick="openApprovalHistoryTab('rejected')">Rejected Appointments (<?php echo count($rejectedRequests); ?>)</button>
        </div>

        <!-- Approved Requests -->
        <div id="approved" class="history-content active">
            <div class="approval-grid">
                <?php if (!empty($approvedRequests)): ?>
                    <?php foreach($approvedRequests as $request): ?>
                        <div class="approval-card">
                            <div class="approval-header">
                                <div class="approval-name"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                                <div class="approval-status status-approved">
                                    Approved
                                </div>
                            </div>
                            <div class="approval-details">
                                <div class="detail-row">
                                    <span class="detail-label">Patient Email:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['patient_email']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Hospital:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['hospital_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Test Type:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['test_type']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Appointment Date:</span>
                                    <span class="detail-value"><?php echo date('M j, Y', strtotime($request['appointment_date'])); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Appointment Time:</span>
                                    <span class="detail-value"><?php echo date('g:i A', strtotime($request['appointment_time'])); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['appointment_status']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Created On:</span>
                                    <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--surface-color); border-radius: 10px;">
                        <i class="fa-solid fa-history" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                        <h3 style="color: var(--muted); margin-bottom: 10px;">No Approved Appointments</h3>
                        <p style="color: #94a3b8;">No appointment requests have been approved yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rejected Requests -->
        <div id="rejected" class="history-content">
            <div class="approval-grid">
                <?php if (!empty($rejectedRequests)): ?>
                    <?php foreach($rejectedRequests as $request): ?>
                        <div class="approval-card">
                            <div class="approval-header">
                                <div class="approval-name"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                                <div class="approval-status status-rejected">
                                    Rejected
                                </div>
                            </div>
                            <div class="approval-details">
                                <div class="detail-row">
                                    <span class="detail-label">Patient Email:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['patient_email']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Hospital:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['hospital_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Test Type:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['test_type']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Appointment Date:</span>
                                    <span class="detail-value"><?php echo date('M j, Y', strtotime($request['appointment_date'])); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($request['appointment_status']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Created On:</span>
                                    <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--surface-color); border-radius: 10px;">
                        <i class="fa-solid fa-history" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                        <h3 style="color: var(--muted); margin-bottom: 10px;">No Rejected Appointments</h3>
                        <p style="color: #94a3b8;">No appointment requests have been rejected yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function openApprovalHistoryTab(tabName) {
    // Hide all tab content
    var tabcontent = document.getElementsByClassName("history-content");
    for (var i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
    }

    // Remove active class from all tabs
    var tablinks = document.getElementsByClassName("history-tab");
    for (var i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    // Show the specific tab content and add active class to the button
    document.getElementById(tabName).classList.add("active");
    event.currentTarget.classList.add("active");
}

// Search functionality
document.getElementById('approvalSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.approval-card');
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

            <?php elseif ($section === 'admins'): ?>
<!-- Admin Management Section -->
<main class="container">
    <div class="section-header">
        <div>
            <h3 style="font-size:22px;color:var(--heading-color);margin-bottom:5px">Admin Management</h3>
            <div style="color:var(--muted);font-size:14px">Manage system administrators and their permissions</div>
        </div>
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="openAddAdminModal()">
                <i class="fa-solid fa-plus"></i> Add New Admin
            </button>
        </div>
    </div>

    <!-- Admin Statistics -->
    <div class="stats-cards">
        <div class="stat-card">
            <h3>Total Admins</h3>
            <div class="number"><?php echo $totalAdmins; ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Session</h3>
            <div class="number"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
        </div>
    </div>

    <!-- Admins Table -->
    <div class="patients-table-container">
        <table class="patients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($adminsData)): ?>
                    <?php foreach($adminsData as $admin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['id']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($admin['name']); ?>
                                <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                    <span style="background: var(--teal); color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; margin-left: 5px;">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn-status" onclick="resetAdminPassword(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['name']); ?>')" title="Reset Password">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                    <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn-delete" onclick="confirmDeleteAdmin(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['name']); ?>')" title="Delete Admin">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn-delete" disabled title="Cannot delete your own account" style="opacity: 0.5; cursor: not-allowed;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">
                            <i class="fa-solid fa-user-shield"></i>
                            <h3>No Admins Found</h3>
                            <p>There are no administrators in the system yet.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($section === 'contact_messages'): ?>
<div class="panel contact-panel" style="padding: 20px; max-width: 1000px; margin: 20px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
    <h3 style="margin-bottom: 20px; font-size: 20px; color: #111827;">Contact Messages</h3>

    <?php
    // mysqli query
    $result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
    ?>

    <div class="table-responsive" style="overflow-x:auto;">
        <table class="table table-bordered table-striped" style="width: 100%; border-collapse: collapse; font-size: 14px; color: #374151;">
            <thead style="background: #f3f4f6; color: #111827;">
                <tr>
                    <th style="padding: 10px; text-align: left;">ID</th>
                    <th style="padding: 10px; text-align: left;">Name</th>
                    <th style="padding: 10px; text-align: left;">Email</th>
                    <th style="padding: 10px; text-align: left;">Subject</th>
                    <th style="padding: 10px; text-align: left;">Message</th>
                    <th style="padding: 10px; text-align: left;">Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($msg = $result->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 8px;"><?= htmlspecialchars($msg['id']) ?></td>
                    <td style="padding: 8px;"><?= htmlspecialchars($msg['name']) ?></td>
                    <td style="padding: 8px;"><?= htmlspecialchars($msg['email']) ?></td>
                    <td style="padding: 8px;"><?= htmlspecialchars($msg['subject']) ?></td>
                    <td style="padding: 8px;"><?= htmlspecialchars($msg['message']) ?></td>
                    <td style="padding: 8px;"><?= htmlspecialchars($msg['created_at']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div style="padding: 20px; text-align: center;">
    <h3>This section is under development</h3>
    <p>Please check back later for the full functionality.</p>
</div>
<?php endif; ?>

    <!-- Other sections content -->

<!-- Add Patient Modal -->
<div id="addPatientModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-plus"></i> Add New Patient</h3>
            <button class="close-modal" onclick="closeAddPatientModal()">&times;</button>
        </div>
        <form id="addPatientForm" method="POST" novalidate>
            <input type="hidden" name="action" value="add_patient">
            
            <!-- Name Field -->
            <div class="form-group">
                <label for="patient_name">Full Name </label>
                <input type="text" id="patient_name" name="patient_name" required 
                       placeholder="Enter patient's full name" 
                       pattern="[A-Za-z\s]{2,50}">
                <div class="error-message" id="vaccine_name-error"></div>

            </div>
            
            <!-- Email Field -->
            <div class="form-group">
                <label for="patient_email">Email Address</label>
                <input type="email" id="patient_email" name="patient_email" required 
                       placeholder="Enter patient's email">
                <div class="error-message" id="email-error"></div>
            </div>
            
            <!-- Phone Field -->
            <div class="form-group">
                <label for="patient_phone">Phone Number</label>
                <input type="tel" id="patient_phone" name="patient_phone" required 
                       placeholder="Enter patient's phone number"
                       pattern="[0-9+\-\s]{10,15}">
                <div class="error-message" id="phone-error"></div>
            </div>
            
            <!-- Gender Field -->
            <div class="form-group">
                <label for="patient_gender">Gender</label> <!-- Add asterisk -->
                <select id="patient_gender" name="patient_gender" required>
                    <option value="">Select Gender</option> <!-- Keep empty option for validation -->
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
                <div class="error-message" id="gender-error"></div>
            </div>
            
            <!-- Date of Birth Field -->
            <div class="form-group">
                <label for="patient_dob">Date of Birth</label>
                <input type="date" id="patient_dob" name="patient_dob" 
                    max="<?php echo date('Y-m-d'); ?>" required>
                <div class="error-message" id="dob-error"></div>
            </div>
            
            <!-- Address Field -->
            <div class="form-group">
                <label for="patient_address">Address</label> <!-- Add asterisk -->
                <textarea id="patient_address" name="patient_address" 
                        placeholder="Enter patient's address" required></textarea> <!-- Add required attribute -->
                <div class="error-message" id="address-error"></div>
            </div>
            
            <!-- Vaccination Status Field -->
            <div class="form-group">
                <label for="patient_vaccination_status">Vaccination Status</label>
                <select id="patient_vaccination_status" name="patient_vaccination_status">
                    <option value="not_vaccinated">Not Vaccinated</option>
                    <option value="partially_vaccinated">Partially Vaccinated</option>
                    <option value="fully_vaccinated">Fully Vaccinated</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddPatientModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submit-patient-btn">Add Patient</button>
            </div>
        </form>
    </div>
</div>

<!-- View Patient Modal -->
<div id="viewPatientModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user"></i> Patient Details</h3>
            <button class="close-modal" onclick="closeViewPatientModal()">&times;</button>
        </div>
        <div id="viewPatientContent">
            <?php if (isset($_GET['view_patient']) && $patientDetails): ?>
                <div class="patient-details-modal">
                    <div class="detail-section">
                        <h4>Personal Information</h4>
                        <div class="detail-row">
                            <span class="detail-label">Patient ID:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patientDetails['id']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Full Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patientDetails['name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patientDetails['email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patientDetails['phone']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Gender:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patientDetails['gender']); ?></span>
                        </div>
                        <?php if (!empty($patientDetails['date_of_birth'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Date of Birth:</span>
                            <span class="detail-value"><?php echo date('M j, Y', strtotime($patientDetails['date_of_birth'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Age:</span>
                            <span class="detail-value">
                                <?php 
                                $birthDate = new DateTime($patientDetails['date_of_birth']);
                                $today = new DateTime();
                                $age = $today->diff($birthDate)->y;
                                echo $age . ' years';
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Medical Information</h4>
                        <div class="detail-row">
                            <span class="detail-label">Vaccination Status:</span>
                            <span class="detail-value">
                                <?php 
                                $status = $patientDetails['vaccination_status'] ?? 'not_vaccinated';
                                $statusText = '';
                                $statusClass = '';
                                if ($status === 'fully_vaccinated') {
                                    $statusText = 'Fully Vaccinated';
                                    $statusClass = 'status-fully';
                                } elseif ($status === 'partially_vaccinated') {
                                    $statusText = 'Partially Vaccinated';
                                    $statusClass = 'status-partial';
                                } else {
                                    $statusText = 'Not Vaccinated';
                                    $statusClass = 'status-none';
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!empty($patientDetails['address'])): ?>
                    <div class="detail-section">
                        <h4>Address</h4>
                        <div class="detail-row">
                            <span class="detail-value"><?php echo htmlspecialchars($patientDetails['address']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-section">
                        <h4>Registration Information</h4>
                        <div class="detail-row">
                            <span class="detail-label">Registered On:</span>
                            <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($patientDetails['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <p>Patient details not found.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeViewPatientModal()">Close</button>
            <?php if (isset($_GET['view_patient']) && $patientDetails): ?>
            <button type="button" class="btn btn-primary" onclick="editPatient(<?php echo $patientDetails['id']; ?>)">
                <i class="fa-solid fa-edit"></i> Edit Patient
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Patient Modal -->
<div id="editPatientModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-edit"></i> Edit Patient</h3>
            <button class="close-modal" onclick="closeEditPatientModal()">&times;</button>
        </div>
        <form id="editPatientForm" method="POST" novalidate>
            <input type="hidden" name="action" value="edit_patient">
            <input type="hidden" name="patient_id" id="edit_patient_id">
            
            <!-- Name Field -->
            <div class="form-group">
                <label for="edit_patient_name">Full Name *</label>
                <input type="text" id="edit_patient_name" name="patient_name" required 
                       pattern="[A-Za-z\s]{2,50}">
                <div class="error-message" id="edit-vaccine_name-error"></div>

            </div>
            
            <!-- Email Field -->
            <div class="form-group">
                <label for="edit_patient_email">Email Address *</label>
                <input type="email" id="edit_patient_email" name="patient_email" required>
                <div class="error-message" id="edit-email-error"></div>
            </div>
            
            <!-- Phone Field -->
            <div class="form-group">
                <label for="edit_patient_phone">Phone Number *</label>
                <input type="tel" id="edit_patient_phone" name="patient_phone" required 
                       pattern="[0-9+\-\s]{10,15}">
                <div class="error-message" id="edit-phone-error"></div>
            </div>
            
            <!-- Gender Field -->
            <div class="form-group">
                <label for="edit_patient_gender">Gender *</label>
                <select id="edit_patient_gender" name="patient_gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
                <div class="error-message" id="edit-gender-error"></div>
            </div>
            
            <!-- Date of Birth Field -->
            <div class="form-group">
                <label for="edit_patient_dob">Date of Birth *</label>
                <input type="date" id="edit_patient_dob" name="patient_dob" 
                    max="<?php echo date('Y-m-d'); ?>" required>
                <div class="error-message" id="edit-dob-error"></div>
            </div>
            
            <!-- Address Field -->
            <div class="form-group">
                <label for="edit_patient_address">Address</label>
                <textarea id="edit_patient_address" name="patient_address" placeholder="Enter patient's address"></textarea>
                <div class="error-message" id="edit-address-error"></div>
            </div>
            
            <!-- Vaccination Status Field -->
            <div class="form-group">
                <label for="edit_patient_vaccination_status">Vaccination Status</label>
                <select id="edit_patient_vaccination_status" name="patient_vaccination_status">
                    <option value="not_vaccinated">Not Vaccinated</option>
                    <option value="partially_vaccinated">Partially Vaccinated</option>
                    <option value="fully_vaccinated">Fully Vaccinated</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditPatientModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submit-edit-patient-btn">Update Patient</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Vaccine Modal -->
<div id="addVaccineModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plus"></i> Add New Vaccine</h3>
            <button class="close-modal" onclick="closeAddVaccineModal()">&times;</button>
        </div>

        <?php if (!empty($_SESSION['form_errors'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php 
    $errors = $_SESSION['form_errors'];
    $old = $_SESSION['old_input'] ?? [];
    unset($_SESSION['form_errors'], $_SESSION['old_input']);
    ?>

    // Fill old values
    <?php if (!empty($old)): ?>
        <?php foreach ($old as $key => $val): ?>
            const field = document.querySelector('[name="<?= htmlspecialchars($key) ?>"]');
            if (field) field.value = <?= json_encode($val) ?>;
        <?php endforeach; ?>
    <?php endif; ?>

    // Show errors
    <?php foreach ($errors as $key => $msg): 
        $field = str_replace(['-error','edit-'], '', $key);
        $prefix = strpos($key, 'edit-') === 0 ? 'edit-' : '';
    ?>
        const errEl = document.getElementById('<?= $prefix ?><?= $field ?>-error');
        if (errEl) {
            errEl.textContent = <?= json_encode($msg) ?>;
            errEl.style.display = 'block';
        }
        const input = document.querySelector('[name="<?= str_replace(['edit-','_error'], '', $key) ?>"]');
        if (input) input.classList.add('is-invalid');
    <?php endforeach; ?>
});
</script>
<?php endif; ?>

        <form id="addVaccineForm" method="POST" novalidate>
            <input type="hidden" name="action" value="add_vaccine">
            
            <div class="form-group">
                <label for="vaccine_name">Vaccine Name</label>
                <input type="text" id="vaccine_name" name="vaccine_name" required 
                       placeholder="Enter vaccine name"
                       pattern="[A-Za-z0-9\s\-&]{2,100}">
                <div class="error-message" id="name-error"></div>  <!-- Correct, no change -->
            </div>
            
            <div class="form-group">
                <label for="vaccine_description">Description</label>
                <textarea id="vaccine_description" name="vaccine_description" required 
                          placeholder="Enter vaccine description..." 
                          minlength="10" maxlength="500"></textarea>
                <div class="error-message" id="description-error"></div>  <!-- Correct, no change -->
            </div>
            
            <div class="form-group">
                <label for="vaccine_status">Status</label>
                <select id="vaccine_status" name="vaccine_status" required>
                    <option value="">Select Status</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
                <div class="error-message" id="status-error"></div>  <!-- Correct, no change -->
            </div>
            
            <div class="form-group">
                <label for="doses_required">Doses</label>
                <input type="number" id="doses_required" name="doses_required" 
                       min="1" max="10" value="2" required
                       placeholder="Enter number of doses required">
                <div class="error-message" id="doses-error"></div>  <!-- Fixed: use _ instead of - -->
            </div>
            
            <div class="form-group">
                <label for="storage_temp">Storage Temperature </label>
                <input type="text" id="storage_temp" name="storage_temp" required 
                       placeholder="e.g., 2-8°C, -20°C"
                       pattern="[0-9\-°C\s]+">
                <div class="error-message" id="storage-error"></div>  <!-- Fixed: use _ instead of - -->
            </div>
            
            <div class="form-group">
                <label for="efficacy_rate">Efficacy Rate</label>
                <input type="text" id="efficacy_rate" name="efficacy_rate" required 
                       placeholder="e.g., 95% or 0.95"
                       pattern="^(100|[1-9]?[0-9])(\.\d+)?%?$">
                <div class="error-message" id="efficacy-error"></div>  <!-- Fixed: use _ instead of - -->
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddVaccineModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submit-vaccine-btn">Add Vaccine</button>
            </div>
        </form>

        <?php 
$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['old_input']);
?>
    </div>
</div>

<!-- Edit Vaccine Modal -->
<div id="editVaccineModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-edit"></i> Edit Vaccine</h3>
            <button class="close-modal" onclick="closeEditVaccineModal()">&times;</button>
        </div>

        <?php if (!empty($_SESSION['form_errors'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php 
    $errors = $_SESSION['form_errors'];
    $old = $_SESSION['old_input'] ?? [];
    unset($_SESSION['form_errors'], $_SESSION['old_input']);
    ?>

    // Fill old values
    <?php if (!empty($old)): ?>
        <?php foreach ($old as $key => $val): ?>
            const field = document.querySelector('[name="<?= htmlspecialchars($key) ?>"]');
            if (field) field.value = <?= json_encode($val) ?>;
        <?php endforeach; ?>
    <?php endif; ?>

    // Show errors
    <?php foreach ($errors as $key => $msg): 
        $field = str_replace(['-error','edit-'], '', $key);
        $prefix = strpos($key, 'edit-') === 0 ? 'edit-' : '';
    ?>
        const errEl = document.getElementById('<?= $prefix ?><?= $field ?>-error');
        if (errEl) {
            errEl.textContent = <?= json_encode($msg) ?>;
            errEl.style.display = 'block';
        }
        const input = document.querySelector('[name="<?= str_replace(['edit-','_error'], '', $key) ?>"]');
        if (input) input.classList.add('is-invalid');
    <?php endforeach; ?>
});
</script>
<?php endif; ?>

        <form id="editVaccineForm" method="POST" novalidate>
            <input type="hidden" name="action" value="edit_vaccine">
            <input type="hidden" name="vaccine_id" id="edit_vaccine_id">
            
            <div class="form-group">
                <label for="edit_vaccine_name">Vaccine Name</label>
                <input type="text" id="edit_vaccine_name" name="vaccine_name" required 
                       pattern="[A-Za-z0-9\s\-&]{2,100}">
                <div class="error-message" id="edit-name-error"></div>  <!-- Correct, no change -->
            </div>
            
            <div class="form-group">
                <label for="edit_vaccine_description">Description</label>
                <textarea id="edit_vaccine_description" name="vaccine_description" required 
                          minlength="10" maxlength="500"></textarea>
                <div class="error-message" id="edit-description-error"></div>  <!-- Correct, no change -->
            </div>
            
            <div class="form-group">
                <label for="edit_vaccine_status">Status</label>
                <select id="edit_vaccine_status" name="vaccine_status" required>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
                <div class="error-message" id="edit-status-error"></div>  <!-- Correct, no change -->
            </div>
            
            <div class="form-group">
                <label for="edit_doses_required">Doses</label>
                <input type="number" id="edit_doses_required" name="doses_required" 
                       min="1" max="10" required>
                <div class="error-message" id="edit-doses-error"></div>  <!-- Fixed: use _ instead of - -->
            </div>
            
            <div class="form-group">
                <label for="edit_storage_temp">Storage Temperature</label>
                <input type="text" id="edit_storage_temp" name="storage_temp" required>
                <div class="error-message" id="edit-storage-error"></div>  <!-- Fixed: use _ instead of - -->
            </div>
            
            <div class="form-group">
                <label for="edit_efficacy_rate">Efficacy Rate</label>
                <input type="text" id="edit_efficacy_rate" name="efficacy_rate" required
                       pattern="^(100|[1-9]?[0-9])(\.\d+)?%?$">
                <div class="error-message" id="edit-efficacy-error"></div>  <!-- Fixed: use _ instead of - -->
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditVaccineModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submit-edit-vaccine-btn">Update Vaccine</button>
            </div>
        </form>
        <?php 
$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['old_input']);
?>
    </div>
</div>

<!-- View Vaccine Modal -->
<div id="viewVaccineModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-syringe"></i> Vaccine Details</h3>
            <button class="close-modal" onclick="closeViewVaccineModal()">&times;</button>
        </div>
        <div id="viewVaccineContent">
            <!-- Dynamic content will be loaded here -->
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeViewVaccineModal()">Close</button>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusUpdateModal" class="modal-overlay status-update-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-toggle-on"></i> Update Vaccine Status</h3>
            <button class="close-modal" onclick="closeStatusUpdateModal()">&times;</button>
        </div>
        <form id="statusUpdateForm" method="POST">
            <input type="hidden" name="action" value="update_vaccine_status">
            <input type="hidden" name="vaccine_id" id="statusUpdateVaccineId">
            <input type="hidden" name="status" id="newStatus">
            
            <p>Update status for: <strong id="statusUpdateVaccineName"></strong></p>
            
            <div class="status-options">
                <div class="status-option available" id="statusAvailable" onclick="selectStatus('available')">
                    <div class="status-icon">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div>Available</div>
                </div>
                <div class="status-option unavailable" id="statusUnavailable" onclick="selectStatus('unavailable')">
                    <div class="status-icon">
                        <i class="fa-solid fa-times-circle"></i>
                    </div>
                    <div>Unavailable</div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeStatusUpdateModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Vaccine Confirmation Popup -->
<div id="deleteVaccinePopup" class="delete-popup">
    <div class="delete-popup-content">
        <h3><i class="fa-solid fa-trash"></i> Delete Vaccine</h3>
        <p id="deleteVaccineMessage">Are you sure you want to delete this vaccine?</p>
        <form id="deleteVaccineForm" method="POST">
            <input type="hidden" name="action" value="delete_vaccine">
            <input type="hidden" name="vaccine_id" id="deleteVaccineId">
            <div class="delete-popup-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteVaccinePopup()">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Test Result Modal -->
<div id="updateResultModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-vial"></i> Update Test Result</h3>
            <button class="close-modal" onclick="closeUpdateResultModal()">&times;</button>
        </div>
        <form id="updateResultForm" method="POST">
            <input type="hidden" name="action" value="update_test_result">
            <input type="hidden" name="booking_id" id="updateResultBookingId">
            <div class="form-group">
                <label for="result">Test Result *</label>
                <select id="result" name="result" required>
                    <option value="Positive">Positive</option>
                    <option value="Negative">Negative</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" placeholder="Enter any additional remarks..."></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeUpdateResultModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Result</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Booking Status Modal -->
<div id="updateStatusModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-edit"></i> Update Booking Status</h3>
            <button class="close-modal" onclick="closeUpdateStatusModal()">&times;</button>
        </div>
        <form id="updateStatusForm" method="POST">
            <input type="hidden" name="action" value="update_booking_status">
            <input type="hidden" name="booking_id" id="updateStatusBookingId">
            <div class="form-group">
                <label for="status">Booking Status *</label>
                <select id="status" name="status" required>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeUpdateStatusModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Logout Confirmation Popup -->
<div id="logoutPopup" class="popup-overlay">
    <div class="popup-box">
        <h3><i class="fa-solid fa-right-from-bracket"></i> Confirm Logout</h3>
        <p>Are you sure you want to log out of your admin session?</p>
        <div class="popup-actions">
            <button id="cancelLogout" class="cancel-btn">Cancel</button>
            <button id="confirmLogout" class="confirm-btn">Logout</button>
        </div>
    </div>
</div>

<!-- Delete Patient Confirmation Popup -->
<div id="deletePopup" class="delete-popup">
    <div class="delete-popup-content">
        <h3><i class="fa-solid fa-trash"></i> Delete Patient</h3>
        <p id="deleteMessage">Are you sure you want to delete this patient?</p>
        <form id="deleteForm" method="POST" action="admin_dashboard.php?section=patients">
            <input type="hidden" name="action" value="delete_patient">
            <input type="hidden" name="patient_id" id="deletePatientId">
            <div class="delete-popup-actions">
                <button type="button" class="btn-cancel" onclick="closeDeletePopup()">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Vaccine Confirmation Popup -->
<div id="deleteVaccinePopup" class="delete-popup">
    <div class="delete-popup-content">
        <h3><i class="fa-solid fa-trash"></i> Delete Vaccine</h3>
        <p id="deleteVaccineMessage">Are you sure you want to delete this vaccine?</p>
        <form id="deleteVaccineForm" method="POST">
            <input type="hidden" name="action" value="delete_vaccine">
            <input type="hidden" name="vaccine_id" id="deleteVaccineId">
            <div class="delete-popup-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteVaccinePopup()">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Booking Confirmation Popup -->
<div id="deleteBookingPopup" class="delete-popup">
    <div class="delete-popup-content">
        <h3><i class="fa-solid fa-trash"></i> Delete Booking</h3>
        <p id="deleteBookingMessage">Are you sure you want to delete this booking?</p>
        <form id="deleteBookingForm" method="POST">
            <input type="hidden" name="action" value="delete_booking">
            <input type="hidden" name="booking_id" id="deleteBookingId">
            <div class="delete-popup-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteBookingPopup()">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-plus"></i> Add New Admin</h3>
            <button class="close-modal" onclick="closeAddAdminModal()">&times;</button>
        </div>
        <form id="addAdminForm" method="POST">
            <input type="hidden" name="action" value="add_admin">
            <div class="form-group">
                <label for="admin_name">Full Name *</label>
                <input type="text" id="admin_name" name="admin_name" required placeholder="Enter admin's full name">
            </div>
            <div class="form-group">
                <label for="admin_email">Email Address *</label>
                <input type="email" id="admin_email" name="admin_email" required placeholder="Enter admin's email">
            </div>
            <div class="form-group">
                <label for="admin_password">Password *</label>
                <input type="password" id="admin_password" name="admin_password" required placeholder="Enter password (min 6 characters)">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddAdminModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Admin Confirmation Popup -->
<div id="deleteAdminPopup" class="delete-popup">
    <div class="delete-popup-content">
        <h3><i class="fa-solid fa-trash"></i> Delete Admin</h3>
        <p id="deleteAdminMessage">Are you sure you want to delete this admin?</p>
        <form id="deleteAdminForm" method="POST">
            <input type="hidden" name="action" value="delete_admin">
            <input type="hidden" name="admin_id" id="deleteAdminId">
            <div class="delete-popup-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteAdminPopup()">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Confirmation Popup -->
<div id="resetPasswordPopup" class="delete-popup">
    <div class="delete-popup-content">
        <h3><i class="fa-solid fa-key"></i> Reset Password</h3>
        <p id="resetPasswordMessage">Are you sure you want to reset this admin's password?</p>
        <form id="resetPasswordForm" method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="admin_id" id="resetPasswordId">
            <div class="delete-popup-actions">
                <button type="button" class="btn-cancel" onclick="closeResetPasswordPopup()">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script>
// Sidebar functionality
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const mobileOverlay = document.getElementById('mobileOverlay');

menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    mobileOverlay.classList.toggle('active');
});

mobileOverlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    mobileOverlay.classList.remove('active');
});

// Close sidebar when clicking on a link (for mobile)
document.querySelectorAll('.nav a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 900) {
            sidebar.classList.remove('open');
            mobileOverlay.classList.remove('active');
        }
    });
});

// Logout functionality
const logoutBtn = document.getElementById('logoutBtn');
const popup = document.getElementById('logoutPopup');
const cancelBtn = document.getElementById('cancelLogout');
const confirmBtn = document.getElementById('confirmLogout');

logoutBtn.addEventListener('click', () => {
    popup.style.display = 'flex';
});

cancelBtn.addEventListener('click', () => {
    popup.style.display = 'none';
});

confirmBtn.addEventListener('click', () => {
    popup.style.display = 'none';
    window.location.href = "logout.php";
});

popup.addEventListener('click', (e) => {
    if (e.target === popup) popup.style.display = 'none';
});

// Patient Management Functions
function viewPatient(patientId) {
    // URL update karen without page reload
    const url = new URL(window.location);
    url.searchParams.set('view_patient', patientId);
    window.history.pushState({}, '', url);
    
    // Fetch patient data and open view modal
    fetch(`admin_dashboard.php?ajax_get_patient=1&patient_id=${patientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update modal content with patient data
                updateViewModalContent(data.patient);
                document.getElementById('viewPatientModal').style.display = 'flex';
            } else {
                alert('Error loading patient details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading patient details');
        });
}

function updateViewModalContent(patient) {
    const modalContent = document.getElementById('viewPatientContent');
    
    // Calculate age
    let age = 'N/A';
    if (patient.date_of_birth) {
        const birthDate = new Date(patient.date_of_birth);
        const today = new Date();
        age = today.getFullYear() - birthDate.getFullYear() + ' years';
    }
    
    // Vaccination status
    let statusText = '';
    let statusClass = '';
    const status = patient.vaccination_status || 'not_vaccinated';
    if (status === 'fully_vaccinated') {
        statusText = 'Fully Vaccinated';
        statusClass = 'status-fully';
    } else if (status === 'partially_vaccinated') {
        statusText = 'Partially Vaccinated';
        statusClass = 'status-partial';
    } else {
        statusText = 'Not Vaccinated';
        statusClass = 'status-none';
    }
    
    modalContent.innerHTML = `
        <div class="patient-details-modal">
            <div class="detail-section">
                <h4>Personal Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Patient ID:</span>
                    <span class="detail-value">${patient.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Full Name:</span>
                    <span class="detail-value">${patient.name}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${patient.email}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">${patient.phone}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Gender:</span>
                    <span class="detail-value">${patient.gender}</span>
                </div>
                ${patient.date_of_birth ? `
                <div class="detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value">${new Date(patient.date_of_birth).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Age:</span>
                    <span class="detail-value">${age}</span>
                </div>
                ` : ''}
            </div>
            
            <div class="detail-section">
                <h4>Medical Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Vaccination Status:</span>
                    <span class="detail-value">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </span>
                </div>
            </div>
            
            ${patient.address ? `
            <div class="detail-section">
                <h4>Address</h4>
                <div class="detail-row">
                    <span class="detail-value">${patient.address}</span>
                </div>
            </div>
            ` : ''}
            
            <div class="detail-section">
                <h4>Registration Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Registered On:</span>
                    <span class="detail-value">${new Date(patient.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                </div>
            </div>
        </div>
    `;
}

function editPatient(patientId) {
    // View modal close karen agar open hai
    closeViewPatientModal();
    
    // URL update karen without page reload
    const url = new URL(window.location);
    url.searchParams.set('edit_patient', patientId);
    window.history.pushState({}, '', url);
    
    // Fetch patient data and open edit modal
    fetch(`admin_dashboard.php?ajax_get_patient=1&patient_id=${patientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_patient_id').value = data.patient.id;
                document.getElementById('edit_patient_name').value = data.patient.name;
                document.getElementById('edit_patient_email').value = data.patient.email;
                document.getElementById('edit_patient_phone').value = data.patient.phone;
                document.getElementById('edit_patient_gender').value = data.patient.gender;
                document.getElementById('edit_patient_dob').value = data.patient.date_of_birth;
                document.getElementById('edit_patient_address').value = data.patient.address || '';
                document.getElementById('edit_patient_vaccination_status').value = data.patient.vaccination_status || 'not_vaccinated';
                
                document.getElementById('editPatientModal').style.display = 'flex';
            } else {
                alert('Error loading patient details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading patient details');
        });
}

// Close delete popup when clicking outside
document.getElementById('deletePopup').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeletePopup();
    }
});


// Validation functions
const validators = {
    name: (value) => {
        if (!value.trim()) return "Name is required";
        if (!/^[A-Za-z\s]{2,50}$/.test(value.trim())) return "Name should contain only letters and spaces (2-50 characters)";
        return "";
    },
    email: (value) => {
        if (!value.trim()) return "Email is required";
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())) return "Please enter a valid email address";
        return "";
    },
    phone: (value) => {
        if (!value.trim()) return "Phone number is required";
        if (!/^[0-9+\-\s]{10,15}$/.test(value.trim())) return "Please enter a valid phone number (10-15 digits)";
        return "";
    },
    gender: (value) => {
        if (!value) return "Please select a gender";
        return "";
    },
    dob: (value) => {
        if (!value) return "Date of birth is required"; // Make this required
        const selectedDate = new Date(value);
        const today = new Date();
        if (selectedDate > today) return "Date of birth cannot be in the future";
        return "";
    },
    address: (value) => {
        if (!value.trim()) return "Address is required";
        return "";
    }
};

// Form validation setup
function setupFormValidation(form, formType) {
    const prefix = formType === 'add' ? '' : 'edit-';
    const fields = ['name', 'email', 'phone', 'gender', 'dob', 'address'];
    
    // Real-time validation on input change
    fields.forEach(field => {
        const input = form.querySelector(`[name="patient_${field}"]`);
        if (input) {
            input.addEventListener('input', () => validateField(input, field, prefix));
            input.addEventListener('blur', () => validateField(input, field, prefix));
            
            if (input.tagName === 'SELECT') {
                input.addEventListener('change', () => validateField(input, field, prefix));
            }
        }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Disable submit button during processing
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + 
                             (formType === 'add' ? 'Adding...' : 'Updating...');
        
        // Validate all fields
        fields.forEach(field => {
            const input = form.querySelector(`[name="patient_${field}"]`);
            if (input && !validateField(input, field, prefix)) {
                isValid = false;
            }
        });
        
        if (isValid) {
            form.submit();
        } else {
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = formType === 'add' ? 'Add Patient' : 'Update Patient';
                
                // Scroll to first error
                const firstError = form.querySelector('.error-message:not(:empty)');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 1000);
        }
    });
}

function validateField(input, fieldName, prefix = '') {
    const value = input.value;
    const errorElement = document.getElementById(`${prefix}${fieldName}-error`);
    const errorMessage = validators[fieldName](value);
    
    // Clear previous error
    errorElement.textContent = '';
    errorElement.style.display = 'none';
    
    // Remove validation classes
    input.classList.remove('is-invalid', 'is-valid');
    
    if (errorMessage) {
        // Show error
        errorElement.textContent = errorMessage;
        errorElement.style.display = 'block';
        input.classList.add('is-invalid');
        return false;
    } else {
        // Valid field
        input.classList.add('is-valid');
        return true;
    }
}

// Update existing close functions
function closeAddPatientModal() {
    const form = document.getElementById('addPatientForm');
    if (form) {
        form.reset();
        
        // Clear all error messages
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => {
            msg.textContent = '';
            msg.style.display = 'none';
        });
        
        // Remove validation classes
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
        
        // Re-enable submit button
        const submitBtn = document.getElementById('submit-patient-btn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Add Patient';
        }
    }
    
    document.getElementById('addPatientModal').style.display = 'none';
}

function closeEditPatientModal() {
    const form = document.getElementById('editPatientForm');
    if (form) {
        // Clear all error messages
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => {
            msg.textContent = '';
            msg.style.display = 'none';
        });
        
        // Remove validation classes
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
        
        // Re-enable submit button
        const submitBtn = document.getElementById('submit-edit-patient-btn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Update Patient';
        }
    }
    
    document.getElementById('editPatientModal').style.display = 'none';
    
    // Remove edit_patient parameter from URL
    const url = new URL(window.location);
    url.searchParams.delete('edit_patient');
    window.history.replaceState({}, '', url);
}

// Initialize form validation when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Set max date for date inputs to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('patient_dob')?.setAttribute('max', today);
    document.getElementById('edit_patient_dob')?.setAttribute('max', today);
    
    // Add Patient Form Validation
    const addForm = document.getElementById('addPatientForm');
    if (addForm) {
        setupFormValidation(addForm, 'add');
    }
    
    // Edit Patient Form Validation
    const editForm = document.getElementById('editPatientForm');
    if (editForm) {
        setupFormValidation(editForm, 'edit');
    }
    
    // Prevent future date selection
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            
            if (selectedDate > today) {
                this.value = '';
                const fieldName = this.id.replace('patient_', '').replace('edit_patient_', '');
                const prefix = this.id.startsWith('edit_') ? 'edit-' : '';
                validateField(this, fieldName, prefix);
            }
        });
    });
});

// COVID-19 Reports Date Input Toggle
function toggleDateInputs() {
    const dateFilter = document.getElementById('date_filter').value;
    
    // Hide all date inputs first
    document.getElementById('date_input').style.display = 'none';
    document.getElementById('week_input').style.display = 'none';
    document.getElementById('month_input').style.display = 'none';
    document.getElementById('custom_range_input').style.display = 'none';
    
    // Show the relevant input based on selection
    if (dateFilter === 'date') {
        document.getElementById('date_input').style.display = 'flex';
    } else if (dateFilter === 'week') {
        document.getElementById('week_input').style.display = 'flex';
    } else if (dateFilter === 'month') {
        document.getElementById('month_input').style.display = 'flex';
    } else if (dateFilter === 'custom_range') {
        document.getElementById('custom_range_input').style.display = 'flex';
    }
}

// Vaccine Management Functions
function openAddVaccineModal() {
    document.getElementById('addVaccineModal').style.display = 'flex';
}

function closeAddVaccineModal() {
    const form = document.getElementById('addVaccineForm');
    if (form) {
        form.reset();
        
        // Clear all error messages
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => {
            msg.textContent = '';
            msg.style.display = 'none';
        });
        
        // Remove validation classes
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
    }
    
    document.getElementById('addVaccineModal').style.display = 'none';
}

function viewVaccine(vaccineId) {
    const url = new URL(window.location);
    url.searchParams.set('view_vaccine', vaccineId);
    window.history.pushState({}, '', url);
    
    fetch(`admin_dashboard.php?ajax_get_vaccine=1&vaccine_id=${vaccineId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateViewVaccineModalContent(data.vaccine);
                document.getElementById('viewVaccineModal').style.display = 'flex';
            } else {
                alert('Error loading vaccine details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading vaccine details');
        });
}

function updateViewVaccineModalContent(vaccine) {
    const modalContent = document.getElementById('viewVaccineContent');
    
    modalContent.innerHTML = `
        <div class="vaccine-details-modal">
            <div class="detail-section">
                <h4>Basic Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Vaccine ID:</span>
                    <span class="detail-value">${vaccine.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">${vaccine.name}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value">${vaccine.description || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="vaccine-status ${vaccine.status === 'available' ? 'status-available' : 'status-unavailable'}">
                            ${vaccine.status.charAt(0).toUpperCase() + vaccine.status.slice(1)}
                        </span>
                    </span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Medical Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Doses Required:</span>
                    <span class="detail-value">${vaccine.doses_required || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Storage Temperature:</span>
                    <span class="detail-value">${vaccine.storage_temp || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Efficacy Rate:</span>
                    <span class="detail-value">${vaccine.efficacy_rate || 'N/A'}</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Registration Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Created On:</span>
                    <span class="detail-value">${new Date(vaccine.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                </div>
            </div>
        </div>
    `;
}

function closeViewVaccineModal() {
    document.getElementById('viewVaccineModal').style.display = 'none';
    
    const url = new URL(window.location);
    url.searchParams.delete('view_vaccine');
    window.history.replaceState({}, '', url);
}

function editVaccine(vaccineId) {
    closeViewVaccineModal();
    
    const url = new URL(window.location);
    url.searchParams.set('edit_vaccine', vaccineId);
    window.history.pushState({}, '', url);
    
    fetch(`admin_dashboard.php?ajax_get_vaccine=1&vaccine_id=${vaccineId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_vaccine_id').value = data.vaccine.id;
                document.getElementById('edit_vaccine_name').value = data.vaccine.name;
                document.getElementById('edit_vaccine_description').value = data.vaccine.description || '';
                document.getElementById('edit_vaccine_status').value = data.vaccine.status;
                document.getElementById('edit_doses_required').value = data.vaccine.doses_required || '';
                document.getElementById('edit_storage_temp').value = data.vaccine.storage_temp || '';
                document.getElementById('edit_efficacy_rate').value = data.vaccine.efficacy_rate || '';
                
                document.getElementById('editVaccineModal').style.display = 'flex';
            } else {
                alert('Error loading vaccine details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading vaccine details');
        });
}

function closeEditVaccineModal() {
    document.getElementById('editVaccineModal').style.display = 'none';
    
    const url = new URL(window.location);
    url.searchParams.delete('edit_vaccine');
    window.history.replaceState({}, '', url);
}

function openStatusUpdateModal(vaccineId, currentStatus, vaccineName) {
    document.getElementById('statusUpdateVaccineId').value = vaccineId;
    document.getElementById('statusUpdateVaccineName').textContent = vaccineName;
    
    // Set active status
    const availableOption = document.getElementById('statusAvailable');
    const unavailableOption = document.getElementById('statusUnavailable');
    
    availableOption.classList.remove('active');
    unavailableOption.classList.remove('active');
    
    if (currentStatus === 'available') {
        availableOption.classList.add('active');
        document.getElementById('newStatus').value = 'available';
    } else {
        unavailableOption.classList.add('active');
        document.getElementById('newStatus').value = 'unavailable';
    }
    
    document.getElementById('statusUpdateModal').style.display = 'flex';
}

function closeStatusUpdateModal() {
    document.getElementById('statusUpdateModal').style.display = 'none';
}

function selectStatus(status) {
    const availableOption = document.getElementById('statusAvailable');
    const unavailableOption = document.getElementById('statusUnavailable');
    
    availableOption.classList.remove('active');
    unavailableOption.classList.remove('active');
    
    if (status === 'available') {
        availableOption.classList.add('active');
    } else {
        unavailableOption.classList.add('active');
    }
    
    document.getElementById('newStatus').value = status;
}

function confirmDeleteVaccine(vaccineId, vaccineName) {
    document.getElementById('deleteVaccineMessage').textContent = `Are you sure you want to delete vaccine "${vaccineName}"? This action cannot be undone.`;
    document.getElementById('deleteVaccineId').value = vaccineId;
    document.getElementById('deleteVaccinePopup').style.display = 'flex';
}

function closeDeleteVaccinePopup() {
    document.getElementById('deleteVaccinePopup').style.display = 'none';
}

// Vaccine Search Functionality
document.getElementById('vaccineSearch')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const vaccineCards = document.querySelectorAll('.vaccine-card');
    const tableRows = document.querySelectorAll('.vaccines-table tbody tr');
    
    // Search in card view
    vaccineCards.forEach(card => {
        const vaccineName = card.querySelector('.vaccine-name').textContent.toLowerCase();
        const vaccineDescription = card.querySelector('.detail-row:nth-child(1) .detail-value').textContent.toLowerCase();
        
        if (vaccineName.includes(searchTerm) || vaccineDescription.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Search in table view
    tableRows.forEach(row => {
        if (row.cells.length > 1) {
            const vaccineName = row.cells[1].textContent.toLowerCase();
            const vaccineDescription = row.cells[2].textContent.toLowerCase();
            
            if (vaccineName.includes(searchTerm) || vaccineDescription.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});

// Vaccine View Toggle
document.addEventListener('DOMContentLoaded', function() {
    const gridViewBtn = document.getElementById('grid-view-vaccines');
    const tableViewBtn = document.getElementById('table-view-vaccines');
    const vaccineGrid = document.getElementById('vaccinesGridView');
    const vaccineTable = document.getElementById('vaccinesTableView');
    
    if (gridViewBtn && tableViewBtn) {
        // Initially show grid view on mobile, table view on desktop
        if (window.innerWidth >= 992) {
            vaccineGrid.style.display = 'none';
            vaccineTable.style.display = 'block';
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
        } else {
            vaccineGrid.style.display = 'grid';
            vaccineTable.style.display = 'none';
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        }
        
        gridViewBtn.addEventListener('click', function() {
            vaccineGrid.style.display = 'grid';
            vaccineTable.style.display = 'none';
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        });
        
        tableViewBtn.addEventListener('click', function() {
            vaccineGrid.style.display = 'none';
            vaccineTable.style.display = 'block';
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
        });
        
        // Responsive behavior on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                vaccineGrid.style.display = 'none';
                vaccineTable.style.display = 'block';
                gridViewBtn.classList.remove('active');
                tableViewBtn.classList.add('active');
            } else {
                vaccineGrid.style.display = 'grid';
                vaccineTable.style.display = 'none';
                gridViewBtn.classList.add('active');
                tableViewBtn.classList.remove('active');
            }
        });
    }
});

// Vaccine Validation Functions
const vaccineValidators = {
    name: (value) => !value.trim() ? "Vaccine name is required" :
        !/^[A-Za-z0-9\s\-&]{2,100}$/.test(value.trim()) ? "Invalid name (2-100 chars, letters, -, &, space)" : "",

    description: (value) => !value.trim() ? "Description is required" :
        value.trim().length < 10 ? "Description must be at least 10 characters" :
        value.trim().length > 500 ? "Description too long (max 500)" : "",

    status: (value) => !value ? "Please select a status" : "",

    doses: (value) => !value ? "Dose is required" :
        !/^\d+$/.test(value) || parseInt(value) < 1 ? "Enter a valid number (1-10)" :
        parseInt(value) > 10 ? "Max 10 doses allowed" : "",

    storage: (value) => !value.trim() ? "Storage temperature is required" :
        !/^[0-9\-°C\s]+$/.test(value.trim()) ? "Invalid format (e.g., 2-8°C)" : "",

    efficacy: (value) => !value.trim() ? "Efficacy rate is required" :
        !/^(100|[1-9]?[0-9])(\.\d+)?%?$/.test(value.trim()) ? "Enter 0-100 (e.g., 95% or 95)" :
        parseFloat(value.replace('%','')) > 100 ? "Cannot exceed 100%" : ""
};

// Vaccine Form Validation Setup
function setupVaccineFormValidation(form, formType) {
    const prefix = formType === 'add' ? '' : 'edit-';
    const fieldMap = {
        'vaccine_name': 'name',
        'vaccine_description': 'description',
        'vaccine_status': 'status',
        'doses_required': 'doses',
        'storage_temp': 'storage',
        'efficacy_rate': 'efficacy'
    };

    Object.keys(fieldMap).forEach(name => {
        const input = form.querySelector(`[name="${name}"]`);
        if (input) {
            ['input', 'blur'].forEach(evt => 
                input.addEventListener(evt, () => validateVaccineField(input, name, prefix))
            );
            if (input.tagName === 'SELECT') {
                input.addEventListener('change', () => validateVaccineField(input, name, prefix));
            }
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let isValid = true;

        Object.keys(fieldMap).forEach(name => {
            const input = form.querySelector(`[name="${name}"]`);
            if (input && !validateVaccineField(input, name, prefix)) {
                isValid = false;
            }
        });

        if (isValid) form.submit();
        else {
            setTimeout(() => {
                const firstError = form.querySelector('.error-message:not(:empty)');
                firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    });
}

function validateVaccineField(input, fieldName, prefix = '') {
    const value = input.value;
    const fieldKey = fieldName === 'doses_required' ? 'doses' :
                    fieldName === 'storage_temp' ? 'storage' :
                    fieldName === 'efficacy_rate' ? 'efficacy' :
                    fieldName.replace('vaccine_', '').replace('edit_vaccine_', '');

    const errorElement = document.getElementById(`${prefix}${fieldKey}-error`);
    const errorMessage = vaccineValidators[fieldKey](value);

    // Clear previous
    if (errorElement) {
        errorElement.textContent = errorMessage;
        errorElement.style.display = errorMessage ? 'block' : 'none';
    }
    input.classList.toggle('is-invalid', !!errorMessage);
    input.classList.toggle('is-valid', !errorMessage && value.trim() !== '');

    return !errorMessage;
}

// Enhanced Vaccine Modal Functions
function openAddVaccineModal() {
    document.getElementById('addVaccineModal').style.display = 'flex';
}

function closeAddVaccineModal() {
    const form = document.getElementById('addVaccineForm');
    if (form) {
        form.reset();
        
        // Clear all error messages
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => {
            msg.textContent = '';
            msg.style.display = 'none';
        });
        
        // Remove validation classes
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
        
        // Re-enable submit button
        const submitBtn = document.getElementById('submit-vaccine-btn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Add Vaccine';
        }
    }
    
    document.getElementById('addVaccineModal').style.display = 'none';
}

function closeEditVaccineModal() {
    const form = document.getElementById('editVaccineForm');
    if (form) {
        // Clear all error messages
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => {
            msg.textContent = '';
            msg.style.display = 'none';
        });
        
        // Remove validation classes
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
        
        // Re-enable submit button
        const submitBtn = document.getElementById('submit-edit-vaccine-btn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Update Vaccine';
        }
    }
    
    document.getElementById('editVaccineModal').style.display = 'none';
    
    // Remove edit_vaccine parameter from URL
    const url = new URL(window.location);
    url.searchParams.delete('edit_vaccine');
    window.history.replaceState({}, '', url);
}

// Initialize vaccine form validation when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add Vaccine Form Validation
    const addForm = document.getElementById('addVaccineForm');
    if (addForm) {
        setupVaccineFormValidation(addForm, 'add');
    }
    
    // Edit Vaccine Form Validation
    const editForm = document.getElementById('editVaccineForm');
    if (editForm) {
        setupVaccineFormValidation(editForm, 'edit');
    }
    
    // Auto-open modals based on URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('view_vaccine')) {
        const vaccineId = urlParams.get('view_vaccine');
        viewVaccine(vaccineId);
    }
    
    if (urlParams.has('edit_vaccine')) {
        const vaccineId = urlParams.get('edit_vaccine');
        editVaccine(vaccineId);
    }
});

// Enhanced editVaccine function with validation reset
function editVaccine(vaccineId) {
    closeViewVaccineModal();
    
    const url = new URL(window.location);
    url.searchParams.set('edit_vaccine', vaccineId);
    window.history.pushState({}, '', url);
    
    fetch(`admin_dashboard.php?ajax_get_vaccine=1&vaccine_id=${vaccineId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_vaccine_id').value = data.vaccine.id;
                document.getElementById('edit_vaccine_name').value = data.vaccine.name;
                document.getElementById('edit_vaccine_description').value = data.vaccine.description || '';
                document.getElementById('edit_vaccine_status').value = data.vaccine.status;
                document.getElementById('edit_doses_required').value = data.vaccine.doses_required || '';
                document.getElementById('edit_storage_temp').value = data.vaccine.storage_temp || '';
                document.getElementById('edit_efficacy_rate').value = data.vaccine.efficacy_rate || '';
                
                // Reset validation states when opening edit modal
                const form = document.getElementById('editVaccineForm');
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid', 'is-valid');
                });
                
                const errorMessages = form.querySelectorAll('.error-message');
                errorMessages.forEach(msg => {
                    msg.textContent = '';
                    msg.style.display = 'none';
                });
                
                document.getElementById('editVaccineModal').style.display = 'flex';
            } else {
                alert('Error loading vaccine details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading vaccine details');
        });
}


// Hospital Management Functions
function openAddHospitalModal() {
    document.getElementById('addHospitalModal').style.display = 'flex';
}

function closeAddHospitalModal() {
    const form = document.getElementById('addHospitalForm');
    if (form) {
        form.reset();
        
        // Clear all error messages
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => {
            msg.textContent = '';
            msg.style.display = 'none';
        });
        
        // Remove validation classes
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
        
        // Re-enable submit button
        const submitBtn = document.getElementById('submit-hospital-btn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Add Hospital';
        }
    }
    
    document.getElementById('addHospitalModal').style.display = 'none';
}

function viewHospital(hospitalId) {
    console.log('View hospital clicked:', hospitalId);
    
    // Modal ko show karen
    const modal = document.getElementById('viewHospitalModal');
    if (modal) {
        modal.style.display = 'flex';
        console.log('Modal should be visible now');
    } else {
        console.error('Modal element not found!');
    }
}

function updateViewHospitalModalContent(hospital) {
    const modalContent = document.getElementById('viewHospitalContent');
    
    modalContent.innerHTML = `
        <div class="hospital-details-modal">
            <div class="detail-section">
                <h4>Basic Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Hospital ID:</span>
                    <span class="detail-value">${hospital.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">${hospital.name}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${hospital.email || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">${hospital.phone || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="hospital-status ${hospital.status === 'active' ? 'status-available' : 'status-unavailable'}">
                            ${hospital.status.charAt(0).toUpperCase() + hospital.status.slice(1)}
                        </span>
                    </span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Administrative Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value">${hospital.address || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">License Number:</span>
                    <span class="detail-value">${hospital.license_number || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Admin Name:</span>
                    <span class="detail-value">${hospital.admin_name || 'N/A'}</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Registration Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Created On:</span>
                    <span class="detail-value">${new Date(hospital.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                </div>
            </div>
        </div>
    `;
}

function closeViewHospitalModal() {
    document.getElementById('viewHospitalModal').style.display = 'none';
    
    const url = new URL(window.location);
    url.searchParams.delete('view_hospital');
    window.history.replaceState({}, '', url);
}

function editHospital(hospitalId) {
    closeViewHospitalModal();
    
    const url = new URL(window.location);
    url.searchParams.set('edit_hospital', hospitalId);
    window.history.pushState({}, '', url);
    
    fetch(`admin_dashboard.php?ajax_get_hospital=1&hospital_id=${hospitalId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_hospital_id').value = data.hospital.id;
                document.getElementById('edit_hospital_name').value = data.hospital.name;
                document.getElementById('edit_hospital_email').value = data.hospital.email || '';
                document.getElementById('edit_hospital_phone').value = data.hospital.phone || '';
                document.getElementById('edit_hospital_address').value = data.hospital.address || '';
                document.getElementById('edit_hospital_license').value = data.hospital.license_number || '';
                document.getElementById('edit_hospital_admin').value = data.hospital.admin_name || '';
                document.getElementById('edit_hospital_status').value = data.hospital.status;
                
                document.getElementById('editHospitalModal').style.display = 'flex';
            } else {
                alert('Error loading hospital details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading hospital details');
        });
}

function closeEditHospitalModal() {
    document.getElementById('editHospitalModal').style.display = 'none';
    
    const url = new URL(window.location);
    url.searchParams.delete('edit_hospital');
    window.history.replaceState({}, '', url);
}

function openHospitalStatusUpdateModal(hospitalId, currentStatus, hospitalName) {
    document.getElementById('hospitalStatusUpdateHospitalId').value = hospitalId;
    document.getElementById('hospitalStatusUpdateHospitalName').textContent = hospitalName;
    
    // Set active status
    const activeOption = document.getElementById('hospitalStatusActive');
    const inactiveOption = document.getElementById('hospitalStatusInactive');
    
    activeOption.classList.remove('active');
    inactiveOption.classList.remove('active');
    
    if (currentStatus === 'active') {
        activeOption.classList.add('active');
        document.getElementById('hospitalNewStatus').value = 'active';
    } else {
        inactiveOption.classList.add('active');
        document.getElementById('hospitalNewStatus').value = 'inactive';
    }
    
    document.getElementById('hospitalStatusUpdateModal').style.display = 'flex';
}

function closeHospitalStatusUpdateModal() {
    document.getElementById('hospitalStatusUpdateModal').style.display = 'none';
}

function selectHospitalStatus(status) {
    const activeOption = document.getElementById('hospitalStatusActive');
    const inactiveOption = document.getElementById('hospitalStatusInactive');
    
    activeOption.classList.remove('active');
    inactiveOption.classList.remove('active');
    
    if (status === 'active') {
        activeOption.classList.add('active');
    } else {
        inactiveOption.classList.add('active');
    }
    
    document.getElementById('hospitalNewStatus').value = status;
}

function confirmDeleteHospital(hospitalId, hospitalName) {
    document.getElementById('deleteHospitalMessage').textContent = `Are you sure you want to delete hospital "${hospitalName}"? This action cannot be undone.`;
    document.getElementById('deleteHospitalId').value = hospitalId;
    document.getElementById('deleteHospitalPopup').style.display = 'flex';
}

function closeDeleteHospitalPopup() {
    document.getElementById('deleteHospitalPopup').style.display = 'none';
}

// Hospital Search Functionality
document.getElementById('hospitalSearch')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const hospitalCards = document.querySelectorAll('.hospital-card');
    const tableRows = document.querySelectorAll('.hospitals-table tbody tr');
    
    // Search in card view
    hospitalCards.forEach(card => {
        const hospitalName = card.querySelector('.hospital-name').textContent.toLowerCase();
        const hospitalEmail = card.querySelector('.detail-row:nth-child(1) .detail-value').textContent.toLowerCase();
        
        if (hospitalName.includes(searchTerm) || hospitalEmail.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Search in table view
    tableRows.forEach(row => {
        if (row.cells.length > 1) {
            const hospitalName = row.cells[1].textContent.toLowerCase();
            const hospitalEmail = row.cells[2].textContent.toLowerCase();
            
            if (hospitalName.includes(searchTerm) || hospitalEmail.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});

// Hospital View Toggle
document.addEventListener('DOMContentLoaded', function() {
    const gridViewBtn = document.getElementById('grid-view-hospitals');
    const tableViewBtn = document.getElementById('table-view-hospitals');
    const hospitalGrid = document.getElementById('hospitalsGridView');
    const hospitalTable = document.getElementById('hospitalsTableView');
    
    if (gridViewBtn && tableViewBtn) {
        // Initially show grid view on mobile, table view on desktop
        if (window.innerWidth >= 992) {
            hospitalGrid.style.display = 'none';
            hospitalTable.style.display = 'block';
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
        } else {
            hospitalGrid.style.display = 'grid';
            hospitalTable.style.display = 'none';
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        }
        
        gridViewBtn.addEventListener('click', function() {
            hospitalGrid.style.display = 'grid';
            hospitalTable.style.display = 'none';
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        });
        
        tableViewBtn.addEventListener('click', function() {
            hospitalGrid.style.display = 'none';
            hospitalTable.style.display = 'block';
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
        });
        
        // Responsive behavior on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                hospitalGrid.style.display = 'none';
                hospitalTable.style.display = 'block';
                gridViewBtn.classList.remove('active');
                tableViewBtn.classList.add('active');
            } else {
                hospitalGrid.style.display = 'grid';
                hospitalTable.style.display = 'none';
                gridViewBtn.classList.add('active');
                tableViewBtn.classList.remove('active');
            }
        });
    }
});

// Hospital Validation Functions
const hospitalValidators = {
    name: (value) => !value.trim() ? "Hospital name is required" :
        !/^[A-Za-z0-9\s\-&.,()]{2,100}$/.test(value.trim()) ? "Invalid name (2-100 chars)" : "",

    email: (value) => !value.trim() ? "Email is required" :
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim()) ? "Invalid email format" : "",

    phone: (value) => !value.trim() ? "Phone number is required" :
        !/^[0-9+\-\s]{10,15}$/.test(value.trim()) ? "Invalid phone number (10-15 digits)" : "",

    address: (value) => !value.trim() ? "Address is required" : "",

    license: (value) => !value.trim() ? "License number is required" : "",

    admin: (value) => !value.trim() ? "Admin name is required" :
        !/^[A-Za-z\s]{2,50}$/.test(value.trim()) ? "Invalid admin name (letters and spaces only)" : "",

    status: (value) => !value ? "Please select a status" : ""
};

// Hospital Form Validation Setup
function setupHospitalFormValidation(form, formType) {
    const prefix = formType === 'add' ? '' : 'edit-';
    const fieldMap = {
        'hospital_name': 'name',
        'hospital_email': 'email',
        'hospital_phone': 'phone',
        'hospital_address': 'address',
        'hospital_license': 'license',
        'hospital_admin': 'admin',
        'hospital_status': 'status'
    };

    Object.keys(fieldMap).forEach(name => {
        const input = form.querySelector(`[name="${name}"]`);
        if (input) {
            ['input', 'blur'].forEach(evt => 
                input.addEventListener(evt, () => validateHospitalField(input, name, prefix))
            );
            if (input.tagName === 'SELECT') {
                input.addEventListener('change', () => validateHospitalField(input, name, prefix));
            }
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let isValid = true;

        Object.keys(fieldMap).forEach(name => {
            const input = form.querySelector(`[name="${name}"]`);
            if (input && !validateHospitalField(input, name, prefix)) {
                isValid = false;
            }
        });

        if (isValid) form.submit();
        else {
            setTimeout(() => {
                const firstError = form.querySelector('.error-message:not(:empty)');
                firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    });
}

function validateHospitalField(input, fieldName, prefix = '') {
    const value = input.value;
    const fieldKey = fieldName.replace('hospital_', '').replace('edit_hospital_', '');

    const errorElement = document.getElementById(`${prefix}${fieldKey}-error`);
    const errorMessage = hospitalValidators[fieldKey](value);

    // Clear previous
    if (errorElement) {
        errorElement.textContent = errorMessage;
        errorElement.style.display = errorMessage ? 'block' : 'none';
    }
    input.classList.toggle('is-invalid', !!errorMessage);
    input.classList.toggle('is-valid', !errorMessage && value.trim() !== '');

    return !errorMessage;
}

// Initialize hospital form validation when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add Hospital Form Validation
    const addForm = document.getElementById('addHospitalForm');
    if (addForm) {
        setupHospitalFormValidation(addForm, 'add');
    }
    
    // Edit Hospital Form Validation
    const editForm = document.getElementById('editHospitalForm');
    if (editForm) {
        setupHospitalFormValidation(editForm, 'edit');
    }
    
    // Auto-open modals based on URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('view_hospital')) {
        const hospitalId = urlParams.get('view_hospital');
        viewHospital(hospitalId);
    }
    
    if (urlParams.has('edit_hospital')) {
        const hospitalId = urlParams.get('edit_hospital');
        editHospital(hospitalId);
    }
});

// Close modals when clicking outside
document.getElementById('addHospitalModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddHospitalModal();
    }
});

document.getElementById('editHospitalModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditHospitalModal();
    }
});

document.getElementById('viewHospitalModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeViewHospitalModal();
    }
});

document.getElementById('hospitalStatusUpdateModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeHospitalStatusUpdateModal();
    }
});

document.getElementById('deleteHospitalPopup')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteHospitalPopup();
    }
});

// Hospital Approval Functions
function openApprovalHistoryTab(tabName) {
    // Hide all tab content
    const tabContents = document.getElementsByClassName('history-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }
    
    // Remove active class from all tabs
    const tabs = document.getElementsByClassName('history-tab');
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }
    
    // Show the specific tab content and activate the tab
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
    
    // Update view based on current toggle state
    updateApprovalView();
}

// Hospital Approval View Toggle
document.addEventListener('DOMContentLoaded', function() {
    const gridViewBtn = document.getElementById('grid-view-approval');
    const tableViewBtn = document.getElementById('table-view-approval');
    
    if (gridViewBtn && tableViewBtn) {
        // Set initial view based on screen size
        updateApprovalView();
        
        gridViewBtn.addEventListener('click', function() {
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
            updateApprovalView();
        });
        
        tableViewBtn.addEventListener('click', function() {
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
            updateApprovalView();
        });
        
        // Responsive behavior
        window.addEventListener('resize', updateApprovalView);
    }
    
    // Hospital Approval Search Functionality
    document.getElementById('approvalSearch')?.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const approvalCards = document.querySelectorAll('.approval-card');
        const tableRows = document.querySelectorAll('.approval-table tbody tr');
        
        // Search in card view
        approvalCards.forEach(card => {
            const hospitalName = card.querySelector('.approval-name').textContent.toLowerCase();
            
            if (hospitalName.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Search in table view
        tableRows.forEach(row => {
            if (row.cells.length > 1) {
                const hospitalName = row.cells[1].textContent.toLowerCase();
                
                if (hospitalName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });
});

function updateApprovalView() {
    const isGridView = document.getElementById('grid-view-approval').classList.contains('active');
    const activeTab = document.querySelector('.history-tab.active').textContent.toLowerCase();
    
    // Show/hide grid and table views for all sections
    const sections = ['pending', 'approved', 'rejected'];
    
    sections.forEach(section => {
        const gridView = document.getElementById(section + 'GridView');
        const tableView = document.getElementById(section + 'TableView');
        
        if (gridView && tableView) {
            if (isGridView) {
                gridView.style.display = 'grid';
                tableView.style.display = 'none';
            } else {
                gridView.style.display = 'none';
                tableView.style.display = 'block';
            }
        }
    });
    
    // Auto-select appropriate view based on screen size
    if (window.innerWidth < 992 && !isGridView) {
        document.getElementById('grid-view-approval').click();
    } else if (window.innerWidth >= 992 && isGridView) {
        document.getElementById('table-view-approval').click();
    }
}

// Initialize approval view on page load
document.addEventListener('DOMContentLoaded', function() {
    updateApprovalView();
});

// Hospital Approval View Toggle - Fixed Version
function setupApprovalViewToggle() {
    const gridViewBtn = document.getElementById('grid-view-approval');
    const tableViewBtn = document.getElementById('table-view-approval');
    
    if (gridViewBtn && tableViewBtn) {
        console.log('Approval view toggle buttons found');
        
        // Remove any existing event listeners
        const newGridViewBtn = gridViewBtn.cloneNode(true);
        const newTableViewBtn = tableViewBtn.cloneNode(true);
        gridViewBtn.parentNode.replaceChild(newGridViewBtn, gridViewBtn);
        tableViewBtn.parentNode.replaceChild(newTableViewBtn, tableViewBtn);
        
        // Set initial view based on screen size
        updateApprovalView();
        
        // Add new event listeners
        newGridViewBtn.addEventListener('click', function() {
            console.log('Grid view clicked');
            newGridViewBtn.classList.add('active');
            newTableViewBtn.classList.remove('active');
            updateApprovalView();
        });
        
        newTableViewBtn.addEventListener('click', function() {
            console.log('Table view clicked');
            newGridViewBtn.classList.remove('active');
            newTableViewBtn.classList.add('active');
            updateApprovalView();
        });
        
        // Responsive behavior
        window.addEventListener('resize', updateApprovalView);
    } else {
        console.log('Approval view toggle buttons not found');
    }
}

// Enhanced updateApprovalView function
function updateApprovalView() {
    const gridViewBtn = document.getElementById('grid-view-approval');
    const tableViewBtn = document.getElementById('table-view-approval');
    
    if (!gridViewBtn || !tableViewBtn) return;
    
    const isGridView = gridViewBtn.classList.contains('active');
    console.log('Current view:', isGridView ? 'Grid' : 'Table');
    
    // Update all sections: pending, approved, rejected
    const sections = ['pending', 'approved', 'rejected'];
    
    sections.forEach(section => {
        const gridView = document.getElementById(section + 'GridView');
        const tableView = document.getElementById(section + 'TableView');
        
        if (gridView && tableView) {
            if (isGridView) {
                gridView.style.display = 'grid';
                tableView.style.display = 'none';
            } else {
                gridView.style.display = 'none';
                tableView.style.display = 'block';
            }
        } else {
            console.log('Elements not found for section:', section);
        }
    });
    
    // Auto-select appropriate view based on screen size
    if (window.innerWidth < 992 && !isGridView) {
        document.getElementById('grid-view-approval').click();
    } else if (window.innerWidth >= 992 && isGridView) {
        document.getElementById('table-view-approval').click();
    }
}

// Enhanced approval history tab function
function openApprovalHistoryTab(tabName) {
    console.log('Opening tab:', tabName);
    
    // Hide all tab content
    const tabContents = document.getElementsByClassName('history-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }
    
    // Remove active class from all tabs
    const tabs = document.getElementsByClassName('history-tab');
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }
    
    // Show the specific tab content and activate the tab
    const targetTab = document.getElementById(tabName);
    if (targetTab) {
        targetTab.classList.add('active');
    }
    
    if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
    }
    
    // Update view based on current toggle state
    updateApprovalView();
}

// Initialize approval section when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only set up approval section if we're on the approval page
    if (window.location.href.includes('section=approval')) {
        console.log('Setting up approval section');
        setupApprovalViewToggle();
        
        // Also set up the history tabs
        const historyTabs = document.querySelectorAll('.history-tab');
        historyTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.textContent.toLowerCase().includes('approved') ? 'approved' : 
                              this.textContent.toLowerCase().includes('rejected') ? 'rejected' : 'pending';
                openApprovalHistoryTab(tabName);
            });
        });
    }
});


// Booking Details Section Functions

function openHospitalStatusUpdateModal(hospitalId, currentStatus, hospitalName) {
    console.log('Function called with:', hospitalId, currentStatus, hospitalName);
    
    // Check if modal exists
    const modal = document.getElementById('hospitalStatusUpdateModal');
    if (!modal) {
        console.error('Modal not found!');
        return;
    }
    console.log('Modal found:', modal);
    
    try {
        currentHospitalId = hospitalId;
        
        // Set values in the form
        document.getElementById('hospitalStatusUpdateHospitalId').value = hospitalId;
        document.getElementById('hospitalStatusUpdateHospitalName').textContent = hospitalName;
        document.getElementById('hospitalNewStatus').value = currentStatus;
        
        // Update active status visually
        document.querySelectorAll('.status-option').forEach(option => {
            option.classList.remove('active');
        });
        
        if (currentStatus === 'active') {
            document.querySelector('.status-option.available').classList.add('active');
        } else {
            document.querySelector('.status-option.unavailable').classList.add('active');
        }
        
        // Show modal
        modal.style.display = 'flex';
        console.log('Modal should be visible now');
    } catch (error) {
        console.error('Error in openHospitalStatusUpdateModal:', error);
    }
}

// Date Input Toggle for Bookings
function toggleDateInputs() {
    const dateFilter = document.getElementById('date_filter').value;
    
    // Hide all date inputs first
    document.getElementById('date_input').style.display = 'none';
    document.getElementById('week_input').style.display = 'none';
    document.getElementById('month_input').style.display = 'none';
    document.getElementById('custom_range_input').style.display = 'none';
    
    // Show the relevant input based on selection
    if (dateFilter === 'date') {
        document.getElementById('date_input').style.display = 'flex';
    } else if (dateFilter === 'week') {
        document.getElementById('week_input').style.display = 'flex';
    } else if (dateFilter === 'month') {
        document.getElementById('month_input').style.display = 'flex';
    } else if (dateFilter === 'custom_range') {
        document.getElementById('custom_range_input').style.display = 'flex';
    }
}

// View Toggle for Bookings
document.addEventListener('DOMContentLoaded', function() {
    const gridViewBtn = document.getElementById('grid-view-bookings');
    const tableViewBtn = document.getElementById('table-view-bookings');
    const bookingGrid = document.getElementById('bookingsGridView');
    const bookingTable = document.getElementById('bookingsTableView');
    
    if (gridViewBtn && tableViewBtn) {
        // Initially show grid view on mobile, table view on desktop
        if (window.innerWidth >= 992) {
            bookingGrid.style.display = 'none';
            bookingTable.style.display = 'block';
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
        } else {
            bookingGrid.style.display = 'grid';
            bookingTable.style.display = 'none';
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        }
        
        gridViewBtn.addEventListener('click', function() {
            bookingGrid.style.display = 'grid';
            bookingTable.style.display = 'none';
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        });
        
        tableViewBtn.addEventListener('click', function() {
            bookingGrid.style.display = 'none';
            bookingTable.style.display = 'block';
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
        });
        
        // Responsive behavior on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                bookingGrid.style.display = 'none';
                bookingTable.style.display = 'block';
                gridViewBtn.classList.remove('active');
                tableViewBtn.classList.add('active');
            } else {
                bookingGrid.style.display = 'grid';
                bookingTable.style.display = 'none';
                gridViewBtn.classList.add('active');
                tableViewBtn.classList.remove('active');
            }
        });
    }
});

// Booking Search Functionality
document.getElementById('bookingSearch')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const bookingCards = document.querySelectorAll('.booking-card');
    const tableRows = document.querySelectorAll('.bookings-table tbody tr');
    
    // Search in card view
    bookingCards.forEach(card => {
        const patientName = card.querySelector('.detail-row:nth-child(1) .detail-value').textContent.toLowerCase();
        const bookingId = card.querySelector('.booking-id').textContent.toLowerCase();
        
        if (patientName.includes(searchTerm) || bookingId.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Search in table view
    tableRows.forEach(row => {
        if (row.cells.length > 1) { // Skip the "no bookings" row
            const patientName = row.cells[1].textContent.toLowerCase();
            const bookingId = row.cells[0].textContent.toLowerCase();
            
            if (patientName.includes(searchTerm) || bookingId.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});

// Booking Management Functions
function viewBookingDetails(bookingId) {
    alert('View booking details for ID: ' + bookingId);
    // In a real application, this would redirect to a booking details page or open a modal
}

function updateBookingStatus(bookingId, currentStatus) {
    document.getElementById('updateStatusBookingId').value = bookingId;
    document.getElementById('status').value = currentStatus;
    document.getElementById('updateStatusModal').style.display = 'flex';
}

function closeUpdateStatusModal() {
    document.getElementById('updateStatusModal').style.display = 'none';
}

function openUpdateResultModal(bookingId) {
    document.getElementById('updateResultBookingId').value = bookingId;
    document.getElementById('updateResultModal').style.display = 'flex';
}

function closeUpdateResultModal() {
    document.getElementById('updateResultModal').style.display = 'none';
}

function confirmDeleteBooking(bookingId, patientName) {
    document.getElementById('deleteBookingMessage').textContent = `Are you sure you want to delete booking for "${patientName}"? This action cannot be undone.`;
    document.getElementById('deleteBookingId').value = bookingId;
    document.getElementById('deleteBookingPopup').style.display = 'flex';
}

function closeDeleteBookingPopup() {
    document.getElementById('deleteBookingPopup').style.display = 'none';
}

// Close modals when clicking outside
document.getElementById('addVaccineModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddVaccineModal();
    }
});

document.getElementById('addHospitalModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddHospitalModal();
    }
});

document.getElementById('updateResultModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUpdateResultModal();
    }
});

document.getElementById('updateStatusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUpdateStatusModal();
    }
});

document.getElementById('deleteVaccinePopup').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteVaccinePopup();
    }
});

document.getElementById('deleteHospitalPopup').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteHospitalPopup();
    }
});

document.getElementById('deleteBookingPopup').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteBookingPopup();
    }
});

// Chart.js Implementation for Dashboard
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($section === 'dashboard'): ?>
    // Monthly Bookings Chart
    const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
    const bookingsChart = new Chart(bookingsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Bookings',
                data: [120, 190, 150, 220, 180, 250, 210, 300, 280, 320, 350, 400],
                backgroundColor: 'rgba(14, 165, 164, 0.1)',
                borderColor: 'rgba(14, 165, 164, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
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

    // Vaccine Distribution Chart
    const vaccineCtx = document.getElementById('vaccineChart').getContext('2d');
    const vaccineChart = new Chart(vaccineCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pfizer', 'Moderna', 'AstraZeneca', 'Johnson & Johnson', 'Other'],
            datasets: [{
                data: [35, 25, 20, 15, 5],
                backgroundColor: [
                    'rgba(14, 165, 164, 0.8)',
                    'rgba(20, 191, 191, 0.8)',
                    'rgba(9, 156, 156, 0.8)',
                    'rgba(6, 120, 120, 0.8)',
                    'rgba(3, 85, 85, 0.8)'
                ],
                borderWidth: 1
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
            }
        }
    });

    // Age Demographics Chart
    const ageCtx = document.getElementById('ageChart').getContext('2d');
    const ageChart = new Chart(ageCtx, {
        type: 'bar',
        data: {
            labels: ['0-18', '19-30', '31-45', '46-60', '60+'],
            datasets: [{
                label: 'Percentage',
                data: [15, 25, 30, 20, 10],
                backgroundColor: 'rgba(14, 165, 164, 0.7)',
                borderColor: 'rgba(14, 165, 164, 1)',
                borderWidth: 1
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
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
    <?php elseif ($section === 'patients'): ?>
    // Patient Gender Distribution Chart
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    const genderChart = new Chart(genderCtx, {
        type: 'pie',
        data: {
            labels: ['Male', 'Female', 'Other'],
            datasets: [{
                data: [<?php echo $malePatients; ?>, <?php echo $femalePatients; ?>, <?php echo $otherPatients; ?>],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ],
                borderWidth: 1
            }]
        },
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

    // Vaccination Status Chart
    const vaccinationCtx = document.getElementById('vaccinationChart').getContext('2d');
    const vaccinationChart = new Chart(vaccinationCtx, {
        type: 'doughnut',
        data: {
            labels: ['Fully Vaccinated', 'Partially Vaccinated', 'Not Vaccinated'],
            datasets: [{
                data: [<?php echo $fullyVaccinated; ?>, <?php echo $partiallyVaccinated; ?>, <?php echo $notVaccinated; ?>],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(251, 191, 36, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderWidth: 1
            }]
        },
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
    <?php endif; ?>

    // Update charts on window resize for better responsiveness
    window.addEventListener('resize', function() {
        <?php if ($section === 'dashboard'): ?>
        bookingsChart.resize();
        vaccineChart.resize();
        ageChart.resize();
        <?php elseif ($section === 'patients'): ?>
        genderChart.resize();
        vaccinationChart.resize();
        <?php endif; ?>
    });
});

// Vaccine Search Functionality
document.getElementById('vaccineSearch')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const vaccineCards = document.querySelectorAll('.vaccine-card');
    const tableRows = document.querySelectorAll('.vaccines-table tbody tr');
    
    // Search in card view
    vaccineCards.forEach(card => {
        const vaccineName = card.querySelector('.vaccine-name').textContent.toLowerCase();
        
        if (vaccineName.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Search in table view
    tableRows.forEach(row => {
        if (row.cells.length > 1) { // Skip the "no vaccines" row
            const vaccineName = row.cells[1].textContent.toLowerCase();
            
            if (vaccineName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});

// Hospital Approval Search Functionality
document.getElementById('approvalSearch')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const requestCards = document.querySelectorAll('.request-card');
    
    // Search in request cards
    requestCards.forEach(card => {
        const hospitalName = card.querySelector('.request-title').textContent.toLowerCase();
        
        if (hospitalName.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// Hospital Search Functionality
document.getElementById('hospitalSearch')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const hospitalCards = document.querySelectorAll('.hospital-card');
    const tableRows = document.querySelectorAll('.hospitals-table tbody tr');
    
    // Search in card view
    hospitalCards.forEach(card => {
        const hospitalName = card.querySelector('.hospital-name').textContent.toLowerCase();
        
        if (hospitalName.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Search in table view
    tableRows.forEach(row => {
        if (row.cells.length > 1) { // Skip the "no hospitals" row
            const hospitalName = row.cells[1].textContent.toLowerCase();
            
            if (hospitalName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});



// Admin Management Functions
function openAddAdminModal() {
    document.getElementById('addAdminModal').style.display = 'flex';
}

function closeAddAdminModal() {
    document.getElementById('addAdminModal').style.display = 'none';
}

function confirmDeleteAdmin(adminId, adminName) {
    document.getElementById('deleteAdminMessage').textContent = `Are you sure you want to delete admin "${adminName}"? This action cannot be undone.`;
    document.getElementById('deleteAdminId').value = adminId;
    document.getElementById('deleteAdminPopup').style.display = 'flex';
}

function closeDeleteAdminPopup() {
    document.getElementById('deleteAdminPopup').style.display = 'none';
}

function resetAdminPassword(adminId, adminName) {
    document.getElementById('resetPasswordMessage').textContent = `Reset password for "${adminName}" to default (Admin@123)?`;
    document.getElementById('resetPasswordId').value = adminId;
    document.getElementById('resetPasswordPopup').style.display = 'flex';
}

function closeResetPasswordPopup() {
    document.getElementById('resetPasswordPopup').style.display = 'none';
}

// Close modals when clicking outside
document.getElementById('addAdminModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddAdminModal();
    }
});

document.getElementById('deleteAdminPopup').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteAdminPopup();
    }
});

document.getElementById('resetPasswordPopup').addEventListener('click', function(e) {
    if (e.target === this) {
        closeResetPasswordPopup();
    }
});

// Password validation
document.getElementById('addAdminForm')?.addEventListener('submit', function(e) {
    const password = document.getElementById('admin_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return;
    }
});


// Patient Management Functions - Fixed version
function openAddPatientModal() {
    document.getElementById('addPatientModal').style.display = 'flex';
}

function closeAddPatientModal() {
    const form = document.getElementById('addPatientForm');
    if (form) {
        form.reset();
        
        // Clear all error messages
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => {
            msg.textContent = '';
            msg.style.display = 'none';
        });
        
        // Remove validation classes
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
        
        // Re-enable submit button
        const submitBtn = document.getElementById('submit-patient-btn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Add Patient';
        }
    }
    
    document.getElementById('addPatientModal').style.display = 'none';
}

function viewPatient(patientId) {
    // URL change without page reload
    const url = new URL(window.location);
    url.searchParams.set('view_patient', patientId);
    window.history.pushState({}, '', url);
    
    // Fetch patient data and open view modal using AJAX
    fetch(`admin_dashboard.php?ajax_get_patient=1&patient_id=${patientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update modal content with patient data
                updateViewModalContent(data.patient);
                document.getElementById('viewPatientModal').style.display = 'flex';
            } else {
                alert('Error loading patient details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading patient details');
        });
}

function updateViewModalContent(patient) {
    const modalContent = document.getElementById('viewPatientContent');
    
    // Calculate age
    let age = 'N/A';
    if (patient.date_of_birth) {
        const birthDate = new Date(patient.date_of_birth);
        const today = new Date();
        age = today.getFullYear() - birthDate.getFullYear() + ' years';
    }
    
    // Vaccination status
    let statusText = '';
    let statusClass = '';
    const status = patient.vaccination_status || 'not_vaccinated';
    if (status === 'fully_vaccinated') {
        statusText = 'Fully Vaccinated';
        statusClass = 'status-fully';
    } else if (status === 'partially_vaccinated') {
        statusText = 'Partially Vaccinated';
        statusClass = 'status-partial';
    } else {
        statusText = 'Not Vaccinated';
        statusClass = 'status-none';
    }
    
    modalContent.innerHTML = `
        <div class="patient-details-modal">
            <div class="detail-section">
                <h4>Personal Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Patient ID:</span>
                    <span class="detail-value">${patient.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Full Name:</span>
                    <span class="detail-value">${patient.name}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${patient.email}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">${patient.phone}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Gender:</span>
                    <span class="detail-value">${patient.gender}</span>
                </div>
                ${patient.date_of_birth ? `
                <div class="detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value">${new Date(patient.date_of_birth).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Age:</span>
                    <span class="detail-value">${age}</span>
                </div>
                ` : ''}
            </div>
            
            <div class="detail-section">
                <h4>Medical Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Vaccination Status:</span>
                    <span class="detail-value">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </span>
                </div>
            </div>
            
            ${patient.address ? `
            <div class="detail-section">
                <h4>Address</h4>
                <div class="detail-row">
                    <span class="detail-value">${patient.address}</span>
                </div>
            </div>
            ` : ''}
            
            <div class="detail-section">
                <h4>Registration Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Registered On:</span>
                    <span class="detail-value">${new Date(patient.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                </div>
            </div>
        </div>
    `;
}

function closeViewPatientModal() {
    document.getElementById('viewPatientModal').style.display = 'none';
    
    // Remove view_patient parameter from URL without page reload
    const url = new URL(window.location);
    url.searchParams.delete('view_patient');
    window.history.replaceState({}, '', url);
}

function editPatient(patientId) {
    // First close any open modals
    closeViewPatientModal();
    closeAddPatientModal();
    
    // URL update without page reload
    const url = new URL(window.location);
    url.searchParams.set('edit_patient', patientId);
    window.history.pushState({}, '', url);
    
    // Fetch patient data and open edit modal
    fetch(`admin_dashboard.php?ajax_get_patient=1&patient_id=${patientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fill the form with patient data
                document.getElementById('edit_patient_id').value = data.patient.id;
                document.getElementById('edit_patient_name').value = data.patient.name;
                document.getElementById('edit_patient_email').value = data.patient.email;
                document.getElementById('edit_patient_phone').value = data.patient.phone;
                document.getElementById('edit_patient_gender').value = data.patient.gender;
                document.getElementById('edit_patient_dob').value = data.patient.date_of_birth;
                document.getElementById('edit_patient_address').value = data.patient.address || '';
                document.getElementById('edit_patient_vaccination_status').value = data.patient.vaccination_status || 'not_vaccinated';
                
                // Reset validation states
                const form = document.getElementById('editPatientForm');
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid', 'is-valid');
                });
                
                const errorMessages = form.querySelectorAll('.error-message');
                errorMessages.forEach(msg => {
                    msg.textContent = '';
                    msg.style.display = 'none';
                });
                
                // Show the edit modal
                document.getElementById('editPatientModal').style.display = 'flex';
            } else {
                alert('Error loading patient details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading patient details');
        });
}

function closeEditPatientModal() {
    const form = document.getElementById('editPatientForm');
    if (form) {
        // Clear all error messages
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => {
            msg.textContent = '';
            msg.style.display = 'none';
        });
        
        // Remove validation classes
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
        
        // Re-enable submit button
        const submitBtn = document.getElementById('submit-edit-patient-btn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Update Patient';
        }
    }
    
    document.getElementById('editPatientModal').style.display = 'none';
    
    // Remove edit_patient parameter from URL
    const url = new URL(window.location);
    url.searchParams.delete('edit_patient');
    window.history.replaceState({}, '', url);
}

// Patient Delete Function
function confirmDelete(patientId, patientName) {
    document.getElementById('deleteMessage').textContent = `Are you sure you want to delete patient "${patientName}"? This action cannot be undone.`;
    document.getElementById('deletePatientId').value = patientId;
    document.getElementById('deletePopup').style.display = 'flex';
}

function closeDeletePopup() {
    document.getElementById('deletePopup').style.display = 'none';
}

// Close modals when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for closing modals when clicking outside
    const modals = [
        'viewPatientModal', 
        'editPatientModal', 
        'addPatientModal', 
        'deletePopup',
        'addVaccineModal',
        'editVaccineModal',
        'viewVaccineModal',
        'statusUpdateModal',
        'deleteVaccinePopup',
        'addHospitalModal',
        'updateResultModal',
        'updateStatusModal',
        'deleteHospitalPopup',
        'deleteBookingPopup',
        'addAdminModal',
        'deleteAdminPopup',
        'resetPasswordPopup'
    ];
    
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    // Close the modal
                    switch(modalId) {
                        case 'viewPatientModal':
                            closeViewPatientModal();
                            break;
                        case 'editPatientModal':
                            closeEditPatientModal();
                            break;
                        case 'addPatientModal':
                            closeAddPatientModal();
                            break;
                        case 'deletePopup':
                            closeDeletePopup();
                            break;
                        case 'addVaccineModal':
                            closeAddVaccineModal();
                            break;
                        case 'editVaccineModal':
                            closeEditVaccineModal();
                            break;
                        case 'viewVaccineModal':
                            closeViewVaccineModal();
                            break;
                        case 'statusUpdateModal':
                            closeStatusUpdateModal();
                            break;
                        case 'deleteVaccinePopup':
                            closeDeleteVaccinePopup();
                            break;
                        case 'addHospitalModal':
                            closeAddHospitalModal();
                            break;
                        case 'updateResultModal':
                            closeUpdateResultModal();
                            break;
                        case 'updateStatusModal':
                            closeUpdateStatusModal();
                            break;
                        case 'deleteHospitalPopup':
                            closeDeleteHospitalPopup();
                            break;
                        case 'deleteBookingPopup':
                            closeDeleteBookingPopup();
                            break;
                        case 'addAdminModal':
                            closeAddAdminModal();
                            break;
                        case 'deleteAdminPopup':
                            closeDeleteAdminPopup();
                            break;
                        case 'resetPasswordPopup':
                            closeResetPasswordPopup();
                            break;
                    }
                }
            });
        }
    });

    // ESC key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeViewPatientModal();
            closeEditPatientModal();
            closeAddPatientModal();
            closeDeletePopup();
            closeAddVaccineModal();
            closeEditVaccineModal();
            closeViewVaccineModal();
            closeStatusUpdateModal();
            closeDeleteVaccinePopup();
            closeAddHospitalModal();
            closeUpdateResultModal();
            closeUpdateStatusModal();
            closeDeleteHospitalPopup();
            closeDeleteBookingPopup();
            closeAddAdminModal();
            closeDeleteAdminPopup();
            closeResetPasswordPopup();
        }
    });

    // Auto-open modals based on URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('view_patient')) {
        const patientId = urlParams.get('view_patient');
        viewPatient(patientId);
    }
    
    if (urlParams.has('edit_patient')) {
        const patientId = urlParams.get('edit_patient');
        editPatient(patientId);
    }
});

// Patient Search Functionality - Fixed Version
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing patient search');
    initializePatientSearch();
    setupPatientViewToggle();
    
    // Debug check
    setTimeout(() => {
        debugPatientSearch();
    }, 1000);
});

function initializePatientSearch() {
    const patientSearchInput = document.getElementById('patientSearch');
    const searchButton = document.querySelector('.search-box button');
    
    console.log('Initializing patient search...');
    console.log('Search input found:', patientSearchInput);
    console.log('Search button found:', searchButton);
    
    if (patientSearchInput) {
        // Search on input (real-time)
        patientSearchInput.addEventListener('input', function() {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(function() {
                console.log('Real-time search triggered');
                performPatientSearch();
            }, 300);
        });
        
        // Search on Enter key
        patientSearchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                console.log('Enter key pressed');
                performPatientSearch();
            }
        });
        
        console.log('Search input event listeners attached successfully');
    } else {
        console.error('Search input not found!');
    }
    
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent form submission if any
            console.log('Search button clicked');
            performPatientSearch();
        });
        console.log('Search button event listener attached successfully');
    }
}

function performPatientSearch() {
    const searchInput = document.getElementById('patientSearch');
    if (!searchInput) {
        console.error('Search input not found!');
        return;
    }
    
    const searchTerm = searchInput.value.toLowerCase().trim();
    console.log('Searching for:', searchTerm);
    
    let foundAny = false;
    
    // Search in both grid and table views
    const gridView = document.getElementById('patientsGridView');
    const tableView = document.getElementById('patientsTableView');
    
    console.log('Grid view:', gridView);
    console.log('Table view:', tableView);
    
    // Search in grid view
    if (gridView) {
        const patientCards = gridView.querySelectorAll('.patient-card');
        console.log('Grid cards found:', patientCards.length);
        
        patientCards.forEach((card, index) => {
            const patientName = card.querySelector('.patient-name')?.textContent.toLowerCase() || '';
            const patientId = card.querySelector('.patient-id')?.textContent.toLowerCase() || '';
            const patientEmail = card.querySelector('.detail-row:nth-child(1) .detail-value')?.textContent.toLowerCase() || '';
            const patientPhone = card.querySelector('.detail-row:nth-child(2) .detail-value')?.textContent.toLowerCase() || '';
            
            console.log(`Card ${index}:`, { patientName, patientId, patientEmail, patientPhone });
            
            const matches = patientName.includes(searchTerm) || 
                           patientId.includes(searchTerm) || 
                           patientEmail.includes(searchTerm) ||
                           patientPhone.includes(searchTerm);
            
            if (matches || searchTerm === '') {
                card.style.display = 'block';
                foundAny = true;
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Search in table view
    if (tableView) {
        const tableRows = tableView.querySelectorAll('.patients-table tbody tr');
        console.log('Table rows found:', tableRows.length);
        
        tableRows.forEach((row, index) => {
            // Skip no results rows or empty rows
            if (row.classList.contains('no-results-row') || row.cells.length < 2) {
                return;
            }
            
            const patientId = row.cells[0]?.textContent.toLowerCase() || '';
            const patientName = row.cells[1]?.textContent.toLowerCase() || '';
            const patientEmail = row.cells[2]?.textContent.toLowerCase() || '';
            const patientPhone = row.cells[3]?.textContent.toLowerCase() || '';
            
            console.log(`Row ${index}:`, { patientId, patientName, patientEmail, patientPhone });
            
            const matches = patientName.includes(searchTerm) || 
                           patientId.includes(searchTerm) || 
                           patientEmail.includes(searchTerm) ||
                           patientPhone.includes(searchTerm);
            
            if (matches || searchTerm === '') {
                row.style.display = '';
                foundAny = true;
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Show/hide no results message
    if (searchTerm !== '' && !foundAny) {
        showNoResultsMessage(searchTerm);
    } else {
        removeNoResultsMessage();
    }
    
    console.log('Search completed. Found results:', foundAny);
}

function showNoResultsMessage(searchTerm) {
    removeNoResultsMessage();
    
    const gridView = document.getElementById('patientsGridView');
    const tableView = document.getElementById('patientsTableView');
    
    // Grid view message
    if (gridView && gridView.style.display !== 'none') {
        const noResultsDiv = document.createElement('div');
        noResultsDiv.className = 'no-results-message';
        noResultsDiv.style.gridColumn = '1 / -1';
        noResultsDiv.style.textAlign = 'center';
        noResultsDiv.style.padding = '40px';
        noResultsDiv.style.background = 'white';
        noResultsDiv.style.borderRadius = '10px';
        noResultsDiv.style.marginTop = '20px';
        noResultsDiv.innerHTML = `
            <i class="fa-solid fa-search" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
            <h3 style="color: #64748b; margin-bottom: 10px;">No Patients Found</h3>
            <p style="color: #94a3b8;">No patients match your search for "<strong>${searchTerm}</strong>"</p>
            
        `;
        gridView.appendChild(noResultsDiv);
    }
    
    // Table view message
    if (tableView && tableView.style.display !== 'none') {
        const tableBody = tableView.querySelector('tbody');
        const noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-row';
        noResultsRow.style.background = 'white';
        const colCount = tableView.querySelector('thead tr').children.length;
        const cell = document.createElement('td');
        cell.colSpan = colCount;
        cell.style.textAlign = 'center';
        cell.style.padding = '30px';
        cell.style.color = '#64748b';
        cell.innerHTML = `
            <i class="fa-solid fa-search" style="font-size: 24px; margin-bottom: 10px; display: block; color: #cbd5e1;"></i>
            No patients match your search for "<strong>${searchTerm}</strong>"
        `;
        noResultsRow.appendChild(cell);
        tableBody.appendChild(noResultsRow);
    }
}

function removeNoResultsMessage() {
    // Remove grid view no results message
    const gridView = document.getElementById('patientsGridView');
    const gridMessage = gridView?.querySelector('.no-results-message');
    if (gridMessage) {
        gridMessage.remove();
    }
    
    // Remove table view no results message
    const tableView = document.getElementById('patientsTableView');
    const tableMessage = tableView?.querySelector('.no-results-row');
    if (tableMessage) {
        tableMessage.remove();
    }
}

function clearSearch() {
    const searchInput = document.getElementById('patientSearch');
    if (searchInput) {
        searchInput.value = '';
        performPatientSearch();
        searchInput.focus();
    }
}

function setupPatientViewToggle() {
    const gridViewOption = document.getElementById('grid-view-patients');
    const tableViewOption = document.getElementById('table-view-patients');
    
    console.log('View toggle buttons:', { gridViewOption, tableViewOption });
    
    if (gridViewOption && tableViewOption) {
        gridViewOption.addEventListener('click', function() {
            switchPatientView('grid');
        });
        
        tableViewOption.addEventListener('click', function() {
            switchPatientView('table');
        });
        
        console.log('View toggle event listeners attached');
    }
}

function switchPatientView(viewType) {
    const gridViewOption = document.getElementById('grid-view-patients');
    const tableViewOption = document.getElementById('table-view-patients');
    const gridView = document.getElementById('patientsGridView');
    const tableView = document.getElementById('patientsTableView');
    
    if (viewType === 'grid') {
        gridViewOption?.classList.add('active');
        tableViewOption?.classList.remove('active');
        if (gridView) gridView.style.display = 'grid';
        if (tableView) tableView.style.display = 'none';
    } else {
        tableViewOption?.classList.add('active');
        gridViewOption?.classList.remove('active');
        if (gridView) gridView.style.display = 'none';
        if (tableView) tableView.style.display = 'block';
    }
    
    // Re-apply search filter after switching view
    setTimeout(() => {
        performPatientSearch();
    }, 100);
}

// Debug function
function debugPatientSearch() {
    console.log('=== DEBUG PATIENT SEARCH ===');
    console.log('Search input:', document.getElementById('patientSearch'));
    console.log('Grid view:', document.getElementById('patientsGridView'));
    console.log('Table view:', document.getElementById('patientsTableView'));
    console.log('Patient cards:', document.querySelectorAll('.patient-card').length);
    console.log('Table rows:', document.querySelectorAll('.patients-table tbody tr').length);
    
    // Check if patient data exists
    const firstCard = document.querySelector('.patient-card');
    if (firstCard) {
        console.log('First card content:', firstCard.textContent);
    }
    console.log('=== END DEBUG ===');
}

// Force initialization if DOM already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePatientSearch);
} else {
    initializePatientSearch();
}

</script>
</body>
</html>