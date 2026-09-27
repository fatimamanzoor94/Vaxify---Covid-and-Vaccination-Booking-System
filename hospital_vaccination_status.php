<?php
session_start();

// Redirect to login if hospital is not logged in
if (!isset($_SESSION['hospital_id'])) {
    header("Location: login.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Database connection
$servername = "localhost";
$username = "root"; // Change as per your setup
$password = ""; // Change as per your setup
$dbname = "vaxify";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle vaccination status update
$update_message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['patient_id']) && isset($_POST['vaccination_status'])) {
    $patient_id = $_POST['patient_id'];
    $vaccination_status = $_POST['vaccination_status'];
    
    // Verify the patient belongs to the logged-in hospital
        $verify_sql = "SELECT p.id 
                    FROM patients p
                    JOIN appointments a ON p.id = a.patient_id
                    WHERE p.id = ? AND a.hospital_id = ?";
        $stmt = $conn->prepare($verify_sql);
        $stmt->bind_param("ii", $patient_id, $hospital_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update vaccination status
        $update_sql = "UPDATE patients SET vaccination_status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $vaccination_status, $patient_id);
        
        if ($update_stmt->execute()) {
            $update_message = "<div class='success-message'>Vaccination status updated successfully!</div>";
        } else {
            $update_message = "<div class='error-message'>Error updating status: " . $conn->error . "</div>";
        }
        $update_stmt->close();
    } else {
        $update_message = "<div class='error-message'>Invalid patient or access denied.</div>";
    }
    $stmt->close();
}

// Fetch patients linked to this hospital via appointments
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';

$sql = "SELECT DISTINCT p.id, p.name, p.email, p.phone, p.vaccination_status
        FROM patients p
        JOIN appointments a ON p.id = a.patient_id
        WHERE a.hospital_id = ?";

$params = [$hospital_id];
$types = "i";

// Add search condition if provided
if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.email LIKE ? OR p.phone LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

// Add vaccination status filter if provided
if (!empty($status_filter)) {
    $sql .= " AND p.vaccination_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$page_title = "Hospital Panel";
include 'includes/hospital_header.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Vaccination Status Portal</title>
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
            padding: 0 20px;
            width: 100%;
        }

        h1 {
            color: var(--heading-color);
            margin-bottom: 1.5rem;
            text-align: left;
            font-size: 1.8rem;
        }

        .message {
            margin-bottom: 1rem;
            padding: 10px;
            border-radius: 4px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .table-container {
            overflow-x: auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            background-color: var(--surface-color);
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--accent-color);
            color: var(--contrast-color);
            font-weight: 600;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            width: 100%;
            font-size: 14px;
        }

        .btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 500;
            min-height: 40px;
        }

        .btn:hover {
            background-color: var(--nav-hover-color);
        }

        .search-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 1rem;
            background-color: var(--surface-color);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .search-filter-form input[type="text"],
        .search-filter-form select {
            flex: 1;
            min-width: 200px;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        .search-filter-form input[type="text"]:focus,
        .search-filter-form select:focus {
            border-color: var(--accent-color);
            outline: none;
        }

        .search-filter-form .btn {
            padding: 10px 18px;
            background-color: var(--accent-color);
            color: white;
            font-weight: 500;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .search-filter-form .btn:hover {
            background-color: var(--nav-hover-color);
        }

        /* Notification message container */
        .success-message, .error-message {
            position: relative;
            padding: 14px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            margin: 1rem 0;
            animation: fadeIn 0.4s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        /* ✅ Success message style */
        .success-message {
            background-color: #d1f2eb;
            color: #0b5345;
            border-left: 5px solid var(--accent-color);
        }

        /* ❌ Error message style */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #e74c3c;
        }

        /* Subtle appear animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
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

        .card-form select {
            margin-bottom: 10px;
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1024px) {
            .container {
                padding: 0 15px;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 800px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 12px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .search-filter-form {
                flex-direction: column;
                padding: 12px;
            }
            
            .search-filter-form input[type="text"],
            .search-filter-form select {
                min-width: 100%;
            }
            
            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 8px 10px;
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
                padding: 0 10px;
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
            
            .success-message, .error-message {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 8px;
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
            
            .search-filter-form {
                padding: 10px;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 360px) {
            .container {
                padding: 0 5px;
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
            .search-filter-form, .mobile-nav-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main>
        <div class="container">
            <h1>Patient Vaccination Status</h1>
            
            <?php 
            // Display update message if any
            if (!empty($update_message)) {
                echo $update_message;
            }
            ?>

            <form method="GET" action="" class="search-filter-form">
                <input type="text" name="search" placeholder="Search by name, email, or phone" 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                
                <select name="status_filter">
                    <option value="">All Statuses</option>
                    <option value="not_vaccinated" <?php if(isset($_GET['status_filter']) && $_GET['status_filter']=='not_vaccinated') echo 'selected'; ?>>Not Vaccinated</option>
                    <option value="partially_vaccinated" <?php if(isset($_GET['status_filter']) && $_GET['status_filter']=='partially_vaccinated') echo 'selected'; ?>>Partially Vaccinated</option>
                    <option value="fully_vaccinated" <?php if(isset($_GET['status_filter']) && $_GET['status_filter']=='fully_vaccinated') echo 'selected'; ?>>Fully Vaccinated</option>
                </select>

                <button type="submit" class="btn">Filter</button>
            </form>
            
            <!-- Desktop Table View -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Vaccination Status</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                echo "<td>" . ucfirst(str_replace('_', ' ', $row['vaccination_status'])) . "</td>";
                                echo "<td>";
                                echo "<form method='POST' action=''>";
                                echo "<input type='hidden' name='patient_id' value='" . $row['id'] . "'>";
                                echo "<select name='vaccination_status' onchange='this.form.submit()'>";
                                
                                $status_options = [
                                    'not_vaccinated' => 'Not Vaccinated',
                                    'partially_vaccinated' => 'Partially Vaccinated', 
                                    'fully_vaccinated' => 'Fully Vaccinated'
                                ];
                                
                                foreach ($status_options as $value => $label) {
                                    $selected = ($value == $row['vaccination_status']) ? 'selected' : '';
                                    echo "<option value='$value' $selected>$label</option>";
                                }
                                
                                echo "</select>";
                                echo "</form>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align: center;'>No patients found for your hospital.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards View -->
            <div class="mobile-cards">
                <?php if ($result->num_rows > 0): ?>
                    <?php 
                    // Reset pointer and loop again for mobile view
                    $result->data_seek(0); 
                    while($row = $result->fetch_assoc()): 
                    ?>
                        <div class="patient-card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                                <div style="color:var(--nav-color);font-size:13px">
                                    Status: <?php echo ucfirst(str_replace('_', ' ', $row['vaccination_status'])); ?>
                                </div>
                            </div>
                            
                            <div class="card-details">
                                <div class="detail-row">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($row['email']); ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($row['phone']); ?></span>
                                </div>
                            </div>
                            
                            <div class="card-form">
                                <form method="POST" action="">
                                    <input type="hidden" name="patient_id" value="<?php echo $row['id']; ?>">
                                    <select name="vaccination_status" onchange="this.form.submit()" style="width:100%;margin-bottom:10px;">
                                        <?php
                                        $status_options = [
                                            'not_vaccinated' => 'Not Vaccinated',
                                            'partially_vaccinated' => 'Partially Vaccinated', 
                                            'fully_vaccinated' => 'Fully Vaccinated'
                                        ];
                                        
                                        foreach ($status_options as $value => $label) {
                                            $selected = ($value == $row['vaccination_status']) ? 'selected' : '';
                                            echo "<option value='$value' $selected>$label</option>";
                                        }
                                        ?>
                                    </select>
                                    <button type="submit" class="btn" style="width:100%;">Update Status</button>
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
    </main>

    <script>
    // Auto-hide success and error messages after 6 seconds
    setTimeout(() => {
        document.querySelectorAll('.success-message, .error-message').forEach(msg => {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
        });
    }, 6000);

    // Filter functionality for mobile cards
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        const statusFilter = document.querySelector('select[name="status_filter"]');
        
        function filterCards() {
            const filterText = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value;
            const cards = document.querySelectorAll('.mobile-cards .patient-card');
            
            cards.forEach(card => {
                const name = card.querySelector('.card-title').textContent.toLowerCase();
                const email = card.querySelector('.detail-row:nth-child(1) .detail-value').textContent.toLowerCase();
                const phone = card.querySelector('.detail-row:nth-child(2) .detail-value').textContent.toLowerCase();
                const status = card.querySelector('.card-header div').textContent.toLowerCase().replace('status: ', '');
                
                const matchesSearch = name.includes(filterText) || 
                                   email.includes(filterText) || 
                                   phone.includes(filterText);
                
                const matchesStatus = !statusValue || 
                                   status.includes(statusValue.replace('_', ' '));
                
                card.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }
        
        // Apply filters when form is submitted
        document.querySelector('.search-filter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            filterCards();
        });
    });
    </script>

</body>
</html>

<?php
// Close database connection
$stmt->close();
$conn->close();
?>

<?php include 'includes/hospital_footer.php'; ?>