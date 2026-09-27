<?php
session_start();

// Check if hospital is logged in
if (!isset($_SESSION['hospital_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'vaxify');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$hospital_id = $_SESSION['hospital_id'];
// Debug session hospital_id
if(!$hospital_id){
    die("No hospital ID found in session!");
}
$message = '';
$message_type = '';

// Get hospital name
$hospital_stmt = $conn->prepare("SELECT name FROM hospitals WHERE id = ?");
$hospital_stmt->bind_param("i", $hospital_id);
$hospital_stmt->execute();
$hospital_result = $hospital_stmt->get_result();
$hospital = $hospital_result->fetch_assoc();
$hospital_name = $hospital['name'] ?? 'Unknown Hospital';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id']) && isset($_POST['covid_result'])) {
    $patient_id = $_POST['patient_id'];
    $new_result = $_POST['covid_result'];
    
    // Verify patient exists
    $verify_stmt = $conn->prepare("SELECT id, test_type FROM patients WHERE id = ?");
    $verify_stmt->bind_param("i", $patient_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $patient_data = $verify_result->fetch_assoc();
        $test_type = $patient_data['test_type'];
        
        // Update patient record
        $update_patient = $conn->prepare("UPDATE patients SET covid_result = ? WHERE id = ?");
        $update_patient->bind_param("si", $new_result, $patient_id);
        
        // Update or insert report
        $check_report = $conn->prepare("SELECT id FROM covid_reports WHERE patient_id = ?");
        $check_report->bind_param("i", $patient_id);
        $check_report->execute();
        $report_exists = $check_report->get_result();
        
        if ($report_exists->num_rows > 0) {
            $update_report = $conn->prepare("UPDATE covid_reports SET result = ?, test_date = CURRENT_DATE WHERE patient_id = ?");
            $update_report->bind_param("si", $new_result, $patient_id);
            $report_success = $update_report->execute();
        } else {
            $insert_report = $conn->prepare("INSERT INTO covid_reports (patient_id, hospital_id, test_date, test_type, result, test_center) VALUES (?, ?, CURRENT_DATE, ?, ?, ?)");
            $insert_report->bind_param("iisss", $patient_id, $hospital_id, $test_type, $new_result, $hospital_name);
            $report_success = $insert_report->execute();
        }
        
        if ($update_patient->execute() && $report_success) {
            // ✅ Success
            $_SESSION['message'] = "Test result updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            // ❌ Error
            $_SESSION['message'] = "Error updating test result!";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Invalid patient selection!";
        $_SESSION['message_type'] = "error";
    }

    // Redirect to same page to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch patients for this hospital
$patients_stmt = $conn->prepare("SELECT id, name, email, test_type, covid_result, hospital_id FROM patients");
$patients_stmt->execute();
$patients_result = $patients_stmt->get_result();


// Debug: check if any patients found
if($patients_result->num_rows === 0){
    echo "<pre>";
    echo "No patients found for hospital_id = " . htmlspecialchars($hospital_id) . "\n";
    // Check all patient hospital_ids in database
    $all_patients = $conn->query("SELECT id, name, hospital_id FROM patients");
    while($row = $all_patients->fetch_assoc()){
        print_r($row);
    }
    echo "</pre>";
    exit;
}

$page_title = "Hospital Panel";
include 'includes/hospital_header.php';
?>

<?php if(isset($_SESSION['message'])): ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    showPopup('<?php echo $_SESSION['message_type']; ?>', '<?php echo addslashes($_SESSION['message']); ?>');
  });
</script>
<?php
unset($_SESSION['message']);
unset($_SESSION['message_type']);
endif;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Covid-19 Results</title>
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
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--default-color);
            background-color: #f5f7fa;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
        }

        header {
            background: var(--surface-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        h1 {
            color: var(--heading-color);
            font-size: 1.7rem;
            margin-bottom: 0.5rem;
        }

        .hospital-info {
            color: var(--nav-color);
            font-size: 1rem;
        }

        .message {
            padding: 12px 20px;
            margin: 20px 0;
            border-radius: 5px;
            font-weight: 500;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .patients-table {
            background: var(--surface-color);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 20px;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: var(--heading-color);
            color: var(--contrast-color);
            font-weight: 600;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .result-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            font-size: 14px;
            color: var(--default-color);
            min-width: 120px;
        }

        .btn {
            padding: 8px 16px;
            background-color: var(--accent-color);
            color: var(--contrast-color);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            min-height: 36px;
        }

        .btn:hover {
            background-color: var(--nav-hover-color);
        }

        .logout-btn {
            background-color: var(--nav-color);
            margin-left: 10px;
        }

        .logout-btn:hover {
            background-color: var(--nav-hover-color);
        }

        /* Search & Filter Section */
        .search-filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
        }

        #patientSearch {
            flex: 1;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            min-width: 200px;
        }

        #resultFilter {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        /* Mobile Cards View - Hidden by default */
        .mobile-cards {
            display: none;
        }

        .patient-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--accent-color);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
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
            gap: 8px;
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-label {
            font-weight: 600;
            color: var(--heading-color);
        }

        .detail-value {
            text-align: right;
        }

        .card-form {
            margin-top: 10px;
        }

        /* ====== MODAL POPUP STYLES ====== */
        .popup-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.45);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .popup-content {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            text-align: center;
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
        @keyframes slideUp { from {transform: translateY(40px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }

        .popup-content h2 {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
        }

        .popup-content p {
            font-size: 1rem;
            color: #444;
            margin-bottom: 1.2rem;
        }

        .popup-btn {
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 25px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }

        .popup-btn:hover {
            background: var(--nav-hover-color);
        }

        .popup-close {
            position: absolute;
            top: 12px;
            right: 18px;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
        }

        .popup-success h2 { color: #155724; }
        .popup-error h2 { color: #721c24; }

        /* Enhanced Responsive Design */
        @media (max-width: 1024px) {
            .container {
                padding: 15px;
            }
            
            .patients-table {
                overflow-x: auto;
            }
            
            table {
                min-width: 800px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 12px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .search-filter-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            #patientSearch, #resultFilter {
                min-width: 100%;
            }
            
            .result-form {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            
            select, .btn {
                width: 100%;
            }
            
            /* Switch to cards view on mobile */
            .table-container {
                display: none;
            }
            
            .mobile-cards {
                display: block;
            }
            
            .patient-card {
                padding: 12px;
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
                padding: 3px 0;
            }
            
            .detail-label {
                margin-bottom: 2px;
            }
            
            .detail-value {
                text-align: left;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 10px;
            }
            
            h1 {
                font-size: 1.3rem;
            }
            
            .patient-card {
                padding: 10px;
            }
            
            .card-form {
                margin-top: 8px;
            }
            
            .popup-content {
                padding: 1.5rem;
            }
            
            .popup-content h2 {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 8px;
            }
            
            h1 {
                font-size: 1.2rem;
            }
            
            .patient-card {
                padding: 8px;
            }
            
            .card-title {
                font-size: 0.95rem;
            }
            
            .detail-row {
                font-size: 0.9rem;
            }
            
            .popup-content {
                padding: 1.2rem;
            }
            
            .popup-content h2 {
                font-size: 1.1rem;
            }
            
            .popup-content p {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 360px) {
            .container {
                padding: 5px;
            }
            
            .patient-card {
                padding: 6px;
            }
            
            .card-title {
                font-size: 0.9rem;
            }
            
            .detail-row {
                font-size: 0.85rem;
            }
        }

        /* Print Styles */
        @media print {
            .search-filter-container, .mobile-nav-toggle, .popup-modal {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Update Covid-19 Results</h1>

        <?php
        if(isset($_SESSION['message'])) {
            echo '<div class="message ' . $_SESSION['message_type'] . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <!-- Search & Filter Section -->
        <div class="search-filter-container">
            <input type="text" id="patientSearch" placeholder="Search by name or email...">
            
            <select id="resultFilter">
                <option value="">All Results</option>
                <option value="Positive">Positive</option>
                <option value="Negative">Negative</option>
                <option value="Inconclusive">Inconclusive</option>
            </select>
            
            <button id="applyFilters" class="btn">Apply Filters</button>
        </div>

        <!-- Desktop Table View -->
        <div class="patients-table table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Test Type</th>
                        <th>Current Result</th>
                        <th>Update Result</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($patients_result->num_rows > 0): ?>
                        <?php while ($patient = $patients_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['id']); ?></td>
                                <td><?php echo htmlspecialchars($patient['name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                <td><?php echo htmlspecialchars($patient['test_type']); ?></td>
                                <td><?php echo htmlspecialchars($patient['covid_result']); ?></td>
                                <td>
                                    <form method="POST" class="result-form">
                                        <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                                        <select name="covid_result" required>
                                            <option value="Positive" <?php echo $patient['covid_result'] === 'Positive' ? 'selected' : ''; ?>>Positive</option>
                                            <option value="Negative" <?php echo $patient['covid_result'] === 'Negative' ? 'selected' : ''; ?>>Negative</option>
                                            <option value="Inconclusive" <?php echo $patient['covid_result'] === 'Inconclusive' ? 'selected' : ''; ?>>Inconclusive</option>
                                        </select>
                                </td>
                                <td>
                                        <button type="submit" class="btn">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No patients found for your hospital.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards View -->
        <div class="mobile-cards">
            <?php if ($patients_result->num_rows > 0): ?>
                <?php 
                // Reset pointer and loop again for mobile view
                $patients_result->data_seek(0); 
                while ($patient = $patients_result->fetch_assoc()): 
                ?>
                    <div class="patient-card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo htmlspecialchars($patient['name']); ?></h3>
                            <div style="color:var(--nav-color);font-size:13px">ID: <?php echo htmlspecialchars($patient['id']); ?></div>
                        </div>
                        
                        <div class="card-details">
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($patient['email']); ?></span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Test Type:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($patient['test_type']); ?></span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Current Result:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($patient['covid_result']); ?></span>
                            </div>
                        </div>
                        
                        <div class="card-form">
                            <form method="POST" class="result-form">
                                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                                <select name="covid_result" required style="width:100%;margin-bottom:8px;">
                                    <option value="Positive" <?php echo $patient['covid_result'] === 'Positive' ? 'selected' : ''; ?>>Positive</option>
                                    <option value="Negative" <?php echo $patient['covid_result'] === 'Negative' ? 'selected' : ''; ?>>Negative</option>
                                    <option value="Inconclusive" <?php echo $patient['covid_result'] === 'Inconclusive' ? 'selected' : ''; ?>>Inconclusive</option>
                                </select>
                                <button type="submit" class="btn" style="width:100%;">Save Changes</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="patient-card">
                    <div class="card-header">
                        <h3 class="card-title">No Patients Found</h3>
                    </div>
                    <div class="card-details">
                        <div class="detail-row">
                            <span class="detail-value">No patients found for your hospital.</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Popup Modal -->
    <div id="popupModal" class="popup-modal">
        <div class="popup-content">
            <span class="popup-close" onclick="closePopup()">&times;</span>
            <h2 id="popupTitle"></h2>
            <p id="popupMessage"></p>
            <button class="popup-btn" onclick="closePopup()">OK</button>
        </div>
    </div>

    <script>
    const searchInput = document.getElementById('patientSearch');
    const resultFilter = document.getElementById('resultFilter');
    const applyFilters = document.getElementById('applyFilters');

    // Unified filter function for desktop table
    function filterTable() {
        const filterText = searchInput.value.toLowerCase();
        const resultValue = resultFilter.value;
        const rows = document.querySelectorAll('.table-container tbody tr');

        rows.forEach(row => {
            const name = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const result = row.cells[4].textContent;

            const matchesSearch = name.includes(filterText) || email.includes(filterText);
            const matchesResult = !resultValue || result === resultValue;

            row.style.display = (matchesSearch && matchesResult) ? '' : 'none';
        });
    }

    // Unified filter function for mobile cards
    function filterCards() {
        const filterText = searchInput.value.toLowerCase();
        const resultValue = resultFilter.value;
        const cards = document.querySelectorAll('.mobile-cards .patient-card');

        cards.forEach(card => {
            const name = card.querySelector('.card-title').textContent.toLowerCase();
            const email = card.querySelector('.detail-row:nth-child(1) .detail-value').textContent.toLowerCase();
            const result = card.querySelector('.detail-row:nth-child(3) .detail-value').textContent;

            const matchesSearch = name.includes(filterText) || email.includes(filterText);
            const matchesResult = !resultValue || result === resultValue;

            card.style.display = (matchesSearch && matchesResult) ? '' : 'none';
        });
    }

    // Apply filters on button click
    applyFilters.addEventListener('click', function() {
        filterTable();
        filterCards();
    });

    // Live search as you type
    searchInput.addEventListener('keyup', function() {
        filterTable();
        filterCards();
    });

    // Dropdown change live filtering
    resultFilter.addEventListener('change', function() {
        filterTable();
        filterCards();
    });

    function showPopup(type, message) {
        const modal = document.getElementById("popupModal");
        const title = document.getElementById("popupTitle");
        const text = document.getElementById("popupMessage");

        modal.style.display = "flex";
        modal.className = "popup-modal popup-" + type;
        title.textContent = type === "success" ? "Success" : "Error";
        text.textContent = message;
    }

    function closePopup() {
        document.getElementById("popupModal").style.display = "none";
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('popupModal');
        if (event.target === modal) {
            closePopup();
        }
    });
    </script>

</body>
</html>

<?php
$conn->close();
?>

<?php include 'includes/hospital_footer.php'; ?>