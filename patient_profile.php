<?php
session_start();

$patient_id = $_SESSION['patient_id'];
$page_title = "Patient Panel";

// Database configuration
$host = 'localhost';
$dbname = 'vaxify';
$username = 'root';
$password = '';

// Login check
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize validation errors array
$errors = [];

try {
    // PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch patient data
    $patient_id = $_SESSION['patient_id'];
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die("Patient not found.");
    }

    // Initialize variables
    $message = '';
    $message_type = '';

    // Handle form submission for updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        // Sanitize inputs
        $name = trim(htmlspecialchars($_POST['name']));
        $phone = trim(htmlspecialchars($_POST['phone']));
        $email = trim(htmlspecialchars($_POST['email']));
        $address = trim(htmlspecialchars($_POST['address']));
        $dob = trim(htmlspecialchars($_POST['dob']));
        $gender = trim(htmlspecialchars($_POST['gender']));
        
        // Validate Name
        if (empty($name)) {
            $errors['name'] = "Name is required.";
        } elseif (!preg_match("/^[a-zA-Z ]+$/", $name)) {
            $errors['name'] = "Name can only contain letters and spaces.";
        } elseif (strlen($name) < 3 || strlen($name) > 50) {
            $errors['name'] = "Name must be between 3 and 50 characters.";
        }
        
        // Validate Phone
        if (empty($phone)) {
            $errors['phone'] = "Phone number is required.";
        } elseif (!preg_match("/^[0-9]+$/", $phone)) {
            $errors['phone'] = "Phone number can only contain digits.";
        } elseif (strlen($phone) < 10 || strlen($phone) > 15) {
            $errors['phone'] = "Phone number must be between 10 and 15 digits.";
        }
        
        // Validate Email (even though it's readonly)
        if (empty($email)) {
            $errors['email'] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format.";
        }
        
        // Validate Address
        if (empty($address)) {
            $errors['address'] = "Address is required.";
        } elseif (strlen($address) < 5) {
            $errors['address'] = "Address must be at least 5 characters long.";
        }
        
        // Validate DOB
        if (empty($dob)) {
            $errors['dob'] = "Date of birth is required.";
        } else {
            $dob_timestamp = strtotime($dob);
            $current_timestamp = time();
            
            if (!$dob_timestamp) {
                $errors['dob'] = "Invalid date format.";
            } elseif ($dob_timestamp > $current_timestamp) {
                $errors['dob'] = "Date of birth cannot be in the future.";
            }
        }
        
        // Validate Gender
        $allowed_genders = ['Male', 'Female', 'Other'];
        if (empty($gender)) {
            $errors['gender'] = "Gender is required.";
        } elseif (!in_array($gender, $allowed_genders)) {
            $errors['gender'] = "Please select a valid gender.";
        }
        
        // Handle image upload
        $image_path = $patient['image_path'];
        $upload_error = false;
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $allowed_types = ['jpg', 'jpeg', 'png'];
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_types)) {
                $errors['profile_image'] = "Only JPG, JPEG, and PNG files are allowed.";
                $upload_error = true;
            } elseif ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) { // 2MB in bytes
                $errors['profile_image'] = "Image must be less than 2MB.";
                $upload_error = true;
            } else {
                $upload_dir = 'uploads/patients/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = 'patient_' . $patient_id . '_' . time() . '.' . $file_extension;
                $target_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                    // Delete old image if exists
                    if ($image_path && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    $image_path = $target_path;
                } else {
                    $errors['profile_image'] = "Error uploading image. Please try again.";
                    $upload_error = true;
                    $message = "Error uploading image. Please try again.";
                    $message_type = 'error';
                }
            }
        }
        
        // Check for any file upload errors other than "no file selected"
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== 0 && $_FILES['profile_image']['error'] !== 4) {
            $errors['profile_image'] = "File upload error. Please try again.";
            $upload_error = true;
        }
        
        // Update patient data only if no validation errors and no upload error
        if (empty($errors) && !$upload_error) {
            $update_stmt = $pdo->prepare("
                UPDATE patients 
                SET name = ?, phone = ?, address = ?, dob = ?, gender = ?, image_path = ? 
                WHERE id = ?
            ");
            
            if ($update_stmt->execute([$name, $phone, $address, $dob, $gender, $image_path, $patient_id])) {
                $message = "Profile updated successfully!";
                $message_type = 'success';
                // Refresh patient data
                $stmt->execute([$patient_id]);
                $patient = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = "Error updating profile. Please try again.";
                $message_type = 'error';
            }
        } elseif (!empty($errors)) {
            $message = "Please correct the errors below.";
            $message_type = 'error';
        }
    }
    
    // Handle account deletion
    if (isset($_POST['delete_account'])) {
        try {
            $pdo->beginTransaction();

            // Delete from appointments
            $stmt1 = $pdo->prepare("DELETE FROM appointments WHERE patient_id = ?");
            $stmt1->execute([$patient_id]);

            // Delete from hospital_request
            $stmt2 = $pdo->prepare("DELETE FROM hospital_request WHERE patient_id = ?");
            $stmt2->execute([$patient_id]);

            // Delete other dependent tables if any
            // $stmtX = $pdo->prepare("DELETE FROM another_table WHERE patient_id = ?");
            // $stmtX->execute([$patient_id]);

            // Finally delete patient
            $stmt3 = $pdo->prepare("DELETE FROM patients WHERE id = ?");
            $stmt3->execute([$patient_id]);

            $pdo->commit();

            session_destroy();
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Error deleting account: " . $e->getMessage();
            $message_type = 'error';
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Include header after processing to avoid header sent errors
include 'includes/patient_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Vaxify</title>
    <style>
        :root { 
            --background-color: #ffffff;
            --default-color: #2c3031;
            --heading-color: #18444c; 
            --accent-color: #049ebb; 
            --surface-color: #ffffff; 
            --contrast-color: #ffffff; 
        }

        :root {
            --nav-color: #496268;  
            --nav-hover-color: #049ebb; 
            --nav-mobile-background-color: #ffffff; 
            --nav-dropdown-background-color: #ffffff; 
            --nav-dropdown-color: #496268; 
            --nav-dropdown-hover-color: #049ebb;
        }

        .profile-card {
            background: var(--surface-color);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .profile-header {
            background: #049ebb; 
            color: var(--contrast-color);
            padding: 2rem;
            text-align: center;
        }

        .profile-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .profile-content {
            padding: 2rem;
        }

        .profile-image-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 4px solid var(--accent-color);
            margin-bottom: 1rem;
        }

        .image-upload-label {
            display: inline-block;
            background: var(--accent-color);
            color: var(--contrast-color);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .image-upload-label:hover {
            background: var(--nav-hover-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 0.8rem;
        }

        .form-group.half {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--heading-color);
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--accent-color);
            outline: none;
        }

        .readonly-field {
            background-color: #f5f5f5;
            color: #666;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-primary {
            background: var(--accent-color);
            color: var(--contrast-color);
        }

        .btn-primary:hover {
            background: var(--nav-hover-color);
        }

        .btn-danger {
            background: #dc3545;
            color: var(--contrast-color);
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-outline {
            background: #18444c;
            color: white;
            border: 1px solid #ddd;
        }

        .btn-outline:hover {
            background: #18444c;
            color: #f5f5f5;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            text-align: center;
            transition: opacity 0.5s ease-in-out;
        }

        .message.hidden {
            opacity: 0;
            height: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .field-error {
            border-color: #dc3545 !important;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--surface-color);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .modal-header h2 {
            color: var(--heading-color);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .modal-body {
            margin-bottom: 2rem;
            text-align: center;
        }

        .modal-body p {
            color: var(--default-color);
            font-size: 1.1rem;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .modal-actions .btn {
            min-width: 120px;
        }

        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .actions button {
                width: 100%;
            }

            .modal-content {
                padding: 1.5rem;
            }

            .modal-actions {
                flex-direction: column;
            }

            .modal-actions .btn {
                width: 100%;
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
        <div class="profile-card">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>Manage your personal information</p>
            </div>
            
            <div class="profile-content">
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>" id="statusMessage">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="profile-image-section">
                        <img src="<?php echo $patient['image_path'] ?: 'https://via.placeholder.com/150'; ?>" 
                             alt="Profile Picture" class="profile-image">
                        <input type="file" name="profile_image" id="profile_image" accept=".jpg,.jpeg,.png" style="display: none;">
                        <label for="profile_image" class="image-upload-label">Change Photo</label>
                        <?php if (isset($errors['profile_image'])): ?>
                            <span class="error-message"><?php echo $errors['profile_image']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($patient['name']); ?>" 
                               class="<?php echo isset($errors['name']) ? 'field-error' : ''; ?>">
                        <?php if (isset($errors['name'])): ?>
                            <span class="error-message"><?php echo $errors['name']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" 
                               class="readonly-field <?php echo isset($errors['email']) ? 'field-error' : ''; ?>" readonly>
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-message"><?php echo $errors['email']; ?></span>
                        <?php endif; ?>
                        <small style="color: #666;">Email cannot be changed</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>"
                                   class="<?php echo isset($errors['phone']) ? 'field-error' : ''; ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <span class="error-message"><?php echo $errors['phone']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group half">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" class="<?php echo isset($errors['gender']) ? 'field-error' : ''; ?>">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo $patient['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $patient['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $patient['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <?php if (isset($errors['gender'])): ?>
                                <span class="error-message"><?php echo $errors['gender']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo $patient['dob']; ?>"
                               class="<?php echo isset($errors['dob']) ? 'field-error' : ''; ?>">
                        <?php if (isset($errors['dob'])): ?>
                            <span class="error-message"><?php echo $errors['dob']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" 
                                  class="<?php echo isset($errors['address']) ? 'field-error' : ''; ?>"><?php echo htmlspecialchars($patient['address']); ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                            <span class="error-message"><?php echo $errors['address']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Vaccination Status</label>
                            <input type="text" value="<?php echo ucfirst(str_replace('_', ' ', $patient['vaccination_status'])); ?>" 
                                   class="readonly-field" readonly>
                        </div>
                        
                        <div class="form-group half">
                            <label>COVID-19 Result</label>
                            <input type="text" value="<?php echo ucfirst($patient['covid_result']); ?>" 
                                   class="readonly-field" readonly>
                        </div>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        <button type="button" id="deleteAccountBtn" class="btn btn-danger">Delete Account</button>
                    </div>
                </form>
                
                <!-- Hidden form for account deletion -->
                <form method="POST" id="deleteForm" style="display: none;">
                    <input type="hidden" name="delete_account" value="1">
                </form>
            </div>
        </div>
    </div>

    <!-- Custom Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Account</h2>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently lost.</p>
            </div>
            <div class="modal-actions">
                <button type="button" id="cancelDelete" class="btn btn-outline">Cancel</button>
                <button type="button" id="confirmDelete" class="btn btn-danger">Delete Account</button>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide success message after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const statusMessage = document.getElementById('statusMessage');
            if (statusMessage && statusMessage.classList.contains('success')) {
                setTimeout(function() {
                    statusMessage.classList.add('hidden');
                }, 5000); // 5000 milliseconds = 5 seconds
            }
        });

        // Get modal and buttons
        const deleteModal = document.getElementById('deleteModal');
        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        
        // Show modal when delete button is clicked
        deleteAccountBtn.addEventListener('click', function() {
            deleteModal.classList.add('active');
        });
        
        // Hide modal when cancel button is clicked
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.classList.remove('active');
        });
        
        // Submit delete form when confirm button is clicked
        confirmDeleteBtn.addEventListener('click', function() {
            document.getElementById('deleteForm').submit();
        });
        
        // Close modal when clicking outside the modal content
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                deleteModal.classList.remove('active');
            }
        });
        
        // Preview image when selected
        document.getElementById('profile_image').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-image').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>

<?php include 'includes/patient_footer.php'; ?>