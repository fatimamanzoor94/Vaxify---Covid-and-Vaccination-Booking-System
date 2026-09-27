<?php
session_start();

// Database connection (PDO)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vaxify";

// Database connection
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$patient_id = $_SESSION['patient_id'];

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$success_message = "";
$error_message = "";

// Handle Book Appointment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $hospital_id = $_POST['hospital_id'];
    $vaccine_id = $_POST['vaccine_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    
    // Server-side validation
    $errors = [];
    
    if (empty($hospital_id)) {
        $errors[] = "Hospital selection is required.";
    }
    
    if (empty($vaccine_id)) {
        $errors[] = "Vaccine selection is required.";
    }
    
    if (empty($appointment_date)) {
        $errors[] = "Appointment date is required.";
    } elseif (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Appointment date cannot be in the past.";
    }
    
    if (empty($appointment_time)) {
        $errors[] = "Appointment time is required.";
    }
    
    if (empty($errors)) {
        try {
            // Get vaccine name for storing in appointments table
            $stmt = $pdo->prepare("SELECT name FROM vaccines WHERE id = ?");
            $stmt->execute([$vaccine_id]);
            $vaccine = $stmt->fetch(PDO::FETCH_ASSOC);
            $vaccine_name = $vaccine ? $vaccine['name'] : 'Unknown Vaccine';
            
            // Use the existing test_type column to store vaccine information
            $test_type = "Vaccine: " . $vaccine_name;
            
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, hospital_id, appointment_date, appointment_time, test_type, status, appointment_status) 
                                  VALUES (?, ?, ?, ?, ?, 'scheduled', 'pending')");
            $stmt->execute([$patient_id, $hospital_id, $appointment_date, $appointment_time, $test_type]);
            $success_message = "Vaccine appointment booked successfully!";
        } catch (PDOException $e) {
            $error_message = "Error booking appointment: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Fetch active hospitals
$hospitals = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM hospitals WHERE status = 'Active' ORDER BY name");
    $stmt->execute();
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching hospitals: " . $e->getMessage();
}

// Fetch available vaccines
$vaccines = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM vaccines WHERE status = 'available' ORDER BY name");
    $stmt->execute();
    $vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching vaccines: " . $e->getMessage();
}

$page_title = "Book Appointment - Patient Panel";
include 'includes/patient_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - COVID-19 Patient Portal</title>
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

        .card {
            background: var(--surface-color);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }

        button {
            background-color: var(--accent-color);
            color: var(--contrast-color);
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
            width: 100%;
        }

        button:hover {
            background-color: var(--nav-hover-color);
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .vaccine-info {
            background-color: #f8f9fa;
            border-left: 4px solid var(--accent-color);
            padding: 10px 15px;
            margin-top: 5px;
            border-radius: 0 4px 4px 0;
        }

        .vaccine-info small {
            color: #666;
            font-size: 14px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (max-width: 768px) {
            .card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 30px; color: var(--heading-color);">Book Vaccine Appointment</h1>
        
        <!-- Display Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Book Vaccine Appointment Section -->
        <div class="card">
            <h2 style="margin-bottom: 20px; font-size: 1.5rem; color: var(--heading-color);">Schedule Your Vaccination</h2>
            <form id="appointmentForm" method="POST" action="">
                <div class="form-group">
                    <label for="hospital_id">Select Hospital:</label>
                    <select id="hospital_id" name="hospital_id" required>
                        <option value="">-- Select Hospital --</option>
                        <?php foreach ($hospitals as $hospital): ?>
                            <option value="<?php echo htmlspecialchars($hospital['id']); ?>">
                                <?php echo htmlspecialchars($hospital['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="vaccine_id">Select Vaccine:</label>
                    <select id="vaccine_id" name="vaccine_id" required>
                        <option value="">-- Select Vaccine --</option>
                        <?php foreach ($vaccines as $vaccine): ?>
                            <option value="<?php echo htmlspecialchars($vaccine['id']); ?>" 
                                    data-description="<?php echo htmlspecialchars($vaccine['description']); ?>"
                                    data-doses="<?php echo htmlspecialchars($vaccine['doses_required']); ?>">
                                <?php echo htmlspecialchars($vaccine['name']); ?>
                                (<?php echo htmlspecialchars($vaccine['doses_required']); ?> dose<?php echo $vaccine['doses_required'] > 1 ? 's' : ''; ?> required)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="vaccineInfo" class="vaccine-info" style="display: none;">
                        <small><strong>Doses Required:</strong> <span id="dosesInfo"></span></small><br>
                        <small><strong>Description:</strong> <span id="descriptionInfo"></span></small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="appointment_date">Appointment Date:</label>
                    <input type="date" id="appointment_date" name="appointment_date" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="appointment_time">Appointment Time:</label>
                    <input type="time" id="appointment_time" name="appointment_time" required>
                </div>
                
                <button type="submit" name="book_appointment">Book Vaccine Appointment</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const appointmentForm = document.getElementById('appointmentForm');
            const vaccineSelect = document.getElementById('vaccine_id');
            const vaccineInfo = document.getElementById('vaccineInfo');
            const dosesInfo = document.getElementById('dosesInfo');
            const descriptionInfo = document.getElementById('descriptionInfo');
            const appointmentDate = document.getElementById('appointment_date');
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            appointmentDate.min = today;
            
            // Show vaccine information when a vaccine is selected
            vaccineSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (selectedOption.value && selectedOption.dataset.description) {
                    dosesInfo.textContent = selectedOption.dataset.doses + ' dose' + (selectedOption.dataset.doses > 1 ? 's' : '');
                    descriptionInfo.textContent = selectedOption.dataset.description;
                    vaccineInfo.style.display = 'block';
                } else {
                    vaccineInfo.style.display = 'none';
                }
            });
            
            // Client-side form validation
            if (appointmentForm) {
                appointmentForm.addEventListener('submit', function(e) {
                    const hospitalId = document.getElementById('hospital_id').value;
                    const vaccineId = document.getElementById('vaccine_id').value;
                    const appointmentDate = document.getElementById('appointment_date').value;
                    const appointmentTime = document.getElementById('appointment_time').value;
                    
                    let errors = [];
                    
                    if (!hospitalId) {
                        errors.push('Please select a hospital.');
                    }
                    
                    if (!vaccineId) {
                        errors.push('Please select a vaccine.');
                    }
                    
                    if (!appointmentDate) {
                        errors.push('Please select an appointment date.');
                    } else {
                        const selectedDate = new Date(appointmentDate);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        
                        if (selectedDate < today) {
                            errors.push('Appointment date cannot be in the past.');
                        }
                    }
                    
                    if (!appointmentTime) {
                        errors.push('Please select an appointment time.');
                    }
                    
                    if (errors.length > 0) {
                        e.preventDefault();
                        alert('Please fix the following errors:\n\n' + errors.join('\n'));
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php include 'includes/patient_footer.php'; ?>