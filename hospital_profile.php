<?php
session_start();
ob_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'vaxify');
define('DB_USER', 'root');
define('DB_PASS', '');

// Initialize variables
$message = '';
$message_type = ''; // success, error, warning
$errors = [];
$hospitalData = [];

// Check if hospital is logged in
if (!isset($_SESSION['hospital_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch hospital data
    $stmt = $pdo->prepare("SELECT * FROM hospitals WHERE id = ?");
    $stmt->execute([$_SESSION['hospital_id']]);
    $hospitalData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hospitalData) {
        $message = 'Hospital profile not found.';
        $message_type = 'error';
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle delete request
        if (isset($_POST['delete_account'])) {
            // Delete account logic would go here
            $message = 'Account deletion request received. Please confirm your password to proceed.';
            $message_type = 'warning';
        } else {
            // Handle profile update
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $registration_number = trim($_POST['registration_number'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $contact_numbers = trim($_POST['contact_numbers'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $opening_time = trim($_POST['opening_time'] ?? '');
            $closing_time = trim($_POST['closing_time'] ?? '');
            $services = trim($_POST['services'] ?? '');
            $available_vaccines = trim($_POST['available_vaccines'] ?? '');
            $admin_name = trim($_POST['admin_name'] ?? '');

            // Validate name
            if (empty($name)) {
                $errors['name'] = 'Hospital name is required.';
            } elseif (strlen($name) < 3 || strlen($name) > 100) {
                $errors['name'] = 'Name must be between 3 and 100 characters.';
            } elseif (!preg_match('/^[a-zA-Z\s\-\.\']+$/', $name)) {
                $errors['name'] = 'Name can only contain letters, spaces, hyphens, dots, and apostrophes.';
            }

            // Validate type
            if (!empty($type) && strlen($type) > 50) {
                $errors['type'] = 'Type cannot exceed 50 characters.';
            }

            // Validate registration number
            if (!empty($registration_number) && strlen($registration_number) > 100) {
                $errors['registration_number'] = 'Registration number cannot exceed 100 characters.';
            }

            // Validate email
            if (empty($email)) {
                $errors['email'] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address.';
            }

            // Validate contact numbers
            if (empty($contact_numbers)) {
                $errors['contact_numbers'] = 'Contact numbers are required.';
            } else {
                $numbers = explode(',', $contact_numbers);
                $valid_numbers = [];
                foreach ($numbers as $number) {
                    $number = trim($number);
                    if (!empty($number)) {
                        // Remove any non-digit characters except + at the beginning
                        $cleaned_number = preg_replace('/[^\d+]/', '', $number);
                        
                        // Check if it's a valid phone number
                        if (preg_match('/^(\+?\d{10,15})$/', $cleaned_number)) {
                            $valid_numbers[] = $cleaned_number;
                        } else {
                            $errors['contact_numbers'] = 'Each contact number must be 10-15 digits (international format allowed).';
                            break;
                        }
                    }
                }
                // If no errors, update the contact numbers with cleaned values
                if (!isset($errors['contact_numbers'])) {
                    $contact_numbers = implode(', ', $valid_numbers);
                }
            }

            // Validate address
            if (empty($address)) {
                $errors['address'] = 'Address is required.';
            } elseif (strlen($address) < 5) {
                $errors['address'] = 'Address must be at least 5 characters.';
            } elseif (strlen($address) > 255) {
                $errors['address'] = 'Address cannot exceed 255 characters.';
            }

            // Validate city
            if (!empty($city) && strlen($city) > 100) {
                $errors['city'] = 'City cannot exceed 100 characters.';
            }

            // Validate times
            if (!empty($opening_time) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $opening_time)) {
                $errors['opening_time'] = 'Please enter a valid opening time (HH:MM format).';
            }

            if (!empty($closing_time) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $closing_time)) {
                $errors['closing_time'] = 'Please enter a valid closing time (HH:MM format).';
            }

            // Validate time logic
            if (!empty($opening_time) && !empty($closing_time) && $opening_time >= $closing_time) {
                $errors['closing_time'] = 'Closing time must be after opening time.';
            }

            // Validate services and vaccines
            if (!empty($services) && strlen($services) > 500) {
                $errors['services'] = 'Services cannot exceed 500 characters.';
            }

            if (!empty($available_vaccines) && strlen($available_vaccines) > 500) {
                $errors['available_vaccines'] = 'Available vaccines cannot exceed 500 characters.';
            }

            // Validate admin name
            if (!empty($admin_name) && strlen($admin_name) > 100) {
                $errors['admin_name'] = 'Admin name cannot exceed 100 characters.';
            }

            // Handle file uploads
            $logo_path = $hospitalData['logo'] ?? '';
            $banner_path = $hospitalData['banner'] ?? '';

            // Logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                $logo = $_FILES['logo'];
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
                $max_size = 2 * 1024 * 1024; // 2MB

                if (!in_array($logo['type'], $allowed_types)) {
                    $errors['logo'] = 'Logo must be JPG or PNG format.';
                } elseif ($logo['size'] > $max_size) {
                    $errors['logo'] = 'Logo must be smaller than 2MB.';
                } else {
                    // Create uploads directory if it doesn't exist
                    if (!is_dir('uploads')) {
                        mkdir('uploads', 0755, true);
                    }
                    
                    $logo_extension = pathinfo($logo['name'], PATHINFO_EXTENSION);
                    $logo_filename = 'logo_' . $_SESSION['hospital_id'] . '_' . time() . '.' . $logo_extension;
                    $logo_path = 'uploads/' . $logo_filename;
                    
                    if (!move_uploaded_file($logo['tmp_name'], $logo_path)) {
                        $errors['logo'] = 'Failed to upload logo. Please try again.';
                    }
                }
            } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] !== 4) { // Error 4 means no file uploaded
                $errors['logo'] = 'Error uploading logo: ' . getFileUploadError($_FILES['logo']['error']);
            }

            // Banner upload
            if (isset($_FILES['banner']) && $_FILES['banner']['error'] === 0) {
                $banner = $_FILES['banner'];
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
                $max_size = 2 * 1024 * 1024; // 2MB

                if (!in_array($banner['type'], $allowed_types)) {
                    $errors['banner'] = 'Banner must be JPG or PNG format.';
                } elseif ($banner['size'] > $max_size) {
                    $errors['banner'] = 'Banner must be smaller than 2MB.';
                } else {
                    // Create uploads directory if it doesn't exist
                    if (!is_dir('uploads')) {
                        mkdir('uploads', 0755, true);
                    }
                    
                    $banner_extension = pathinfo($banner['name'], PATHINFO_EXTENSION);
                    $banner_filename = 'banner_' . $_SESSION['hospital_id'] . '_' . time() . '.' . $banner_extension;
                    $banner_path = 'uploads/' . $banner_filename;
                    
                    if (!move_uploaded_file($banner['tmp_name'], $banner_path)) {
                        $errors['banner'] = 'Failed to upload banner. Please try again.';
                    }
                }
            } elseif (isset($_FILES['banner']) && $_FILES['banner']['error'] !== 4) { // Error 4 means no file uploaded
                $errors['banner'] = 'Error uploading banner: ' . getFileUploadError($_FILES['banner']['error']);
            }

            // Update database if no errors
            if (empty($errors)) {
                $updateStmt = $pdo->prepare("
                    UPDATE hospitals SET 
                    name = ?, type = ?, registration_number = ?, email = ?, 
                    contact_numbers = ?, address = ?, city = ?, opening_time = ?, 
                    closing_time = ?, services = ?, available_vaccines = ?, 
                    admin_name = ?, logo = ?, banner = ? 
                    WHERE id = ?
                ");

                $updateStmt->execute([
                    htmlspecialchars($name),
                    htmlspecialchars($type),
                    htmlspecialchars($registration_number),
                    filter_var($email, FILTER_SANITIZE_EMAIL),
                    htmlspecialchars($contact_numbers),
                    htmlspecialchars($address),
                    htmlspecialchars($city),
                    $opening_time,
                    $closing_time,
                    htmlspecialchars($services),
                    htmlspecialchars($available_vaccines),
                    htmlspecialchars($admin_name),
                    $logo_path,
                    $banner_path,
                    $_SESSION['hospital_id']
                ]);

                $message = 'Profile updated successfully!';
                $message_type = 'success';
                
                // Refresh hospital data
                $stmt = $pdo->prepare("SELECT * FROM hospitals WHERE id = ?");
                $stmt->execute([$_SESSION['hospital_id']]);
                $hospitalData = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = 'Please correct the errors below.';
                $message_type = 'error';
            }
        }
    }
} catch (PDOException $e) {
    $message = 'Database error: ' . htmlspecialchars($e->getMessage());
    $message_type = 'error';
}

// Function to get file upload error message
function getFileUploadError($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk.';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload.';
        default:
            return 'Unknown upload error.';
    }
}

$page_title = "Hospital Panel";
include 'includes/hospital_header.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Profile Management</title>
    <style>
        :root { 
            --background-color: #ffffff;
            --default-color: #2c3031;
            --heading-color: #18444c; 
            --accent-color: #049ebb; 
            --surface-color: #ffffff; 
            --contrast-color: #ffffff; 
            --success-color: #28a745;
            --error-color: #dc3545;
            --warning-color: #ffc107;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--surface-color);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        header {
            background: #049ebb;
            color: var(--contrast-color);
            padding: 20px;
            text-align: center;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Banner Section Styles */
        .banner-section {
            position: relative;
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, var(--accent-color), var(--heading-color));
            overflow: hidden;
        }

        .banner-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .banner-section:hover .banner-overlay {
            opacity: 1;
        }

        .banner-upload-btn {
            background: var(--contrast-color);
            color: var(--heading-color);
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .banner-upload-btn:hover {
            background: var(--accent-color);
            color: var(--contrast-color);
        }

        .banner-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e0e0e0, #b0b0b0);
            color: #666;
            font-size: 1.2rem;
            font-weight: 500;
        }

        /* Logo Section Styles */
        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: var(--surface-color);
            border-bottom: 1px solid #eee;
        }

        .logo-container {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--accent-color);
            background: var(--surface-color);
        }

        .logo-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logo-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
            color: #999;
            font-size: 0.8rem;
            text-align: center;
        }

        .logo-upload-btn {
            background: var(--accent-color);
            color: var(--contrast-color);
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .logo-upload-btn:hover {
            background: #038a9e;
        }

        .hospital-info {
            flex: 1;
        }

        .hospital-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--heading-color);
            margin-bottom: 5px;
        }

        .hospital-type {
            font-size: 1.1rem;
            color: var(--accent-color);
            font-weight: 500;
        }

        .profile-form {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--heading-color);
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
            box-sizing: border-box;
        }

        input:focus, textarea:focus, select:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(4, 158, 187, 0.2);
        }

        input[readonly] {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .error {
            color: var(--error-color);
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .form-col {
            flex: 1;
            padding: 0 10px;
            min-width: 250px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--accent-color);
            color: var(--contrast-color);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn:hover {
            background: #038a9e;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-delete {
            background: var(--error-color);
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .file-upload {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin-top: 5px;
            transition: border 0.3s;
        }

        .file-upload:hover {
            border-color: var(--accent-color);
        }

        .current-images {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .current-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 5px;
            border: 1px solid #ddd;
            object-fit: cover;
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
            padding: 20px;
            box-sizing: border-box;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: modalAppear 0.3s ease-out;
            overflow: hidden;
        }

        @keyframes modalAppear {
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
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #fffdfdff;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: #fefefeff;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .success .modal-header {
            background-color: var(--success-color);
            color: white;
        }

        .error .modal-header {
            background-color: var(--error-color);
            color: white;
        }

        .warning .modal-header {
            background-color: #049ebb;
            color: #ffffffff;
        }

        .info .modal-header {
            background-color: var(--accent-color);
            color: white;
        }

        .delete-confirm {
            text-align: center;
        }

        .delete-confirm p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .delete-form {
            margin-top: 20px;
        }

        .delete-form input {
            margin-bottom: 15px;
        }

        .delete-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .banner-section {
                height: 200px;
            }

            .logo-section {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .logo-container {
                width: 80px;
                height: 80px;
            }

            .hospital-name {
                font-size: 1.5rem;
            }

            .form-col {
                flex: 100%;
            }
            
            .actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
            }
            
            .modal-content {
                margin: 10px;
            }
            
            .modal-footer {
                flex-direction: column;
            }
            
            .modal-footer .btn {
                width: 100%;
            }
            
            .delete-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .container {
                border-radius: 0;
            }
            
            header {
                padding: 15px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .banner-section {
                height: 150px;
            }

            .profile-form {
                padding: 15px;
            }
            
            .current-images {
                flex-direction: column;
                align-items: center;
            }
            
            .current-image {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Hospital Profile Management</h1>
            <p>Update your hospital information and settings</p>
        </header>

        <form class="profile-form" method="POST" enctype="multipart/form-data" id="profileForm">
            <!-- Banner Section inside the form -->
            <div class="banner-section">
                <?php if (!empty($hospitalData['banner'])): ?>
                    <img src="<?php echo htmlspecialchars($hospitalData['banner']); ?>" alt="Hospital Banner" class="banner-image" id="bannerPreview">
                <?php else: ?>
                    <div class="banner-placeholder" id="bannerPlaceholder">
                        Hospital Banner
                    </div>
                <?php endif; ?>
                <div class="banner-overlay">
                    <label for="banner" class="banner-upload-btn">Change Banner</label>
                    <input type="file" id="banner" name="banner" accept="image/jpeg, image/png" style="display: none;">
                </div>
            </div>

            <!-- Logo and Basic Info Section inside the form -->
            <div class="logo-section">
                <div class="logo-container">
                    <?php if (!empty($hospitalData['logo'])): ?>
                        <img src="<?php echo htmlspecialchars($hospitalData['logo']); ?>" alt="Hospital Logo" class="logo-image" id="logoPreview">
                    <?php else: ?>
                        <div class="logo-placeholder" id="logoPlaceholder">
                            Hospital Logo
                        </div>
                    <?php endif; ?>
                </div>
                <div class="hospital-info">
                    <div class="hospital-name"><?php echo htmlspecialchars($hospitalData['name'] ?? 'Hospital Name'); ?></div>
                    <div class="hospital-type"><?php echo htmlspecialchars($hospitalData['type'] ?? 'Healthcare Facility'); ?></div>
                </div>
                <label for="logo" class="logo-upload-btn">Change Logo</label>
                <input type="file" id="logo" name="logo" accept="image/jpeg, image/png" style="display: none;">
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="name">Hospital Name *</label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo htmlspecialchars($hospitalData['name'] ?? ''); ?>" 
                               required>
                        <?php if (isset($errors['name'])): ?>
                            <span class="error"><?php echo $errors['name']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="type">Hospital Type</label>
                        <input type="text" id="type" name="type" 
                               value="<?php echo htmlspecialchars($hospitalData['type'] ?? ''); ?>">
                        <?php if (isset($errors['type'])): ?>
                            <span class="error"><?php echo $errors['type']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="registration_number">Registration Number</label>
                        <input type="text" id="registration_number" name="registration_number" 
                               value="<?php echo htmlspecialchars($hospitalData['registration_number'] ?? ''); ?>">
                        <?php if (isset($errors['registration_number'])): ?>
                            <span class="error"><?php echo $errors['registration_number']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($hospitalData['email'] ?? ''); ?>" 
                               readonly required>
                        <?php if (isset($errors['email'])): ?>
                            <span class="error"><?php echo $errors['email']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="contact_numbers">Contact Numbers * (Separate multiple numbers with commas)</label>
                <input type="text" id="contact_numbers" name="contact_numbers" 
                       value="<?php echo htmlspecialchars($hospitalData['contact_numbers'] ?? ''); ?>" 
                       placeholder="1234567890, 0987654321" required>
                <?php if (isset($errors['contact_numbers'])): ?>
                    <span class="error"><?php echo $errors['contact_numbers']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="address">Address *</label>
                <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($hospitalData['address'] ?? ''); ?></textarea>
                <?php if (isset($errors['address'])): ?>
                    <span class="error"><?php echo $errors['address']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                               value="<?php echo htmlspecialchars($hospitalData['city'] ?? ''); ?>">
                        <?php if (isset($errors['city'])): ?>
                            <span class="error"><?php echo $errors['city']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="admin_name">Admin Name</label>
                        <input type="text" id="admin_name" name="admin_name" 
                               value="<?php echo htmlspecialchars($hospitalData['admin_name'] ?? ''); ?>">
                        <?php if (isset($errors['admin_name'])): ?>
                            <span class="error"><?php echo $errors['admin_name']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="opening_time">Opening Time</label>
                        <input type="time" id="opening_time" name="opening_time" 
                               value="<?php echo htmlspecialchars($hospitalData['opening_time'] ?? ''); ?>">
                        <?php if (isset($errors['opening_time'])): ?>
                            <span class="error"><?php echo $errors['opening_time']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="closing_time">Closing Time</label>
                        <input type="time" id="closing_time" name="closing_time" 
                               value="<?php echo htmlspecialchars($hospitalData['closing_time'] ?? ''); ?>">
                        <?php if (isset($errors['closing_time'])): ?>
                            <span class="error"><?php echo $errors['closing_time']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="services">Services Offered</label>
                <textarea id="services" name="services" rows="3"><?php echo htmlspecialchars($hospitalData['services'] ?? ''); ?></textarea>
                <?php if (isset($errors['services'])): ?>
                    <span class="error"><?php echo $errors['services']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="available_vaccines">Available Vaccines</label>
                <textarea id="available_vaccines" name="available_vaccines" rows="3"><?php echo htmlspecialchars($hospitalData['available_vaccines'] ?? ''); ?></textarea>
                <?php if (isset($errors['available_vaccines'])): ?>
                    <span class="error"><?php echo $errors['available_vaccines']; ?></span>
                <?php endif; ?>
            </div>

            <div class="actions">
                <button type="submit" class="btn">Update Profile</button>
                <button type="button" class="btn btn-delete" id="deleteBtn">Delete Account</button>
            </div>
        </form>
    </div>

    <!-- Message Modal -->
    <?php if (!empty($message)): ?>
    <div class="modal active" id="messageModal">
        <div class="modal-content <?php echo $message_type; ?>">
            <div class="modal-header">
                <h3 class="modal-title">
                    <?php if ($message_type === 'success'): ?>
                        Success
                    <?php elseif ($message_type === 'error'): ?>
                        Error
                    <?php elseif ($message_type === 'warning'): ?>
                        Warning
                    <?php else: ?>
                        Information
                    <?php endif; ?>
                </h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn modal-close-btn">OK</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content warning">
            <div class="modal-header">
                <h3 class="modal-title">
                    Confirm Account Deletion
                </h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="delete-confirm">
                    <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                    <p>All your data will be permanently removed from our system.</p>
                    <form method="POST" class="delete-form" id="deleteForm">
                        <input type="hidden" name="delete_account" value="1">
                        <div class="form-group">
                            <label for="confirm_password">Enter your password to confirm:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="delete-actions">
                            <button type="button" class="btn modal-close-btn">Cancel</button>
                            <button type="submit" class="btn btn-delete">Delete Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Get all modals and close buttons
            const modals = document.querySelectorAll('.modal');
            const closeButtons = document.querySelectorAll('.modal-close, .modal-close-btn');
            
            // Function to close modal
            function closeModal(modal) {
                modal.classList.remove('active');
            }
            
            // Add event listeners to close buttons
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = this.closest('.modal');
                    closeModal(modal);
                });
            });
            
            // Close modal when clicking outside
            modals.forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this);
                    }
                });
            });
            
            // Delete account button
            const deleteBtn = document.getElementById('deleteBtn');
            const deleteModal = document.getElementById('deleteModal');
            
            if (deleteBtn && deleteModal) {
                deleteBtn.addEventListener('click', function() {
                    deleteModal.classList.add('active');
                });
            }
            
            // Auto-close success message after 5 seconds
            const messageModal = document.getElementById('messageModal');
            if (messageModal && messageModal.classList.contains('success')) {
                setTimeout(() => {
                    closeModal(messageModal);
                }, 5000);
            }
            
            // Form validation
            const form = document.getElementById('profileForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;
                    const inputs = form.querySelectorAll('input[required], textarea[required]');
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            isValid = false;
                            input.style.borderColor = 'var(--error-color)';
                            
                            // Create error message if it doesn't exist
                            if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('error')) {
                                const error = document.createElement('span');
                                error.className = 'error';
                                error.textContent = 'This field is required.';
                                input.parentNode.appendChild(error);
                            }
                        } else {
                            input.style.borderColor = '';
                            const error = input.nextElementSibling;
                            if (error && error.classList.contains('error')) {
                                error.remove();
                            }
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        
                        // Show error modal
                        const errorModal = document.createElement('div');
                        errorModal.className = 'modal active';
                        errorModal.innerHTML = `
                            <div class="modal-content error">
                                <div class="modal-header">
                                    <h3 class="modal-title">
                                        Form Validation Error
                                    </h3>
                                    <button type="button" class="modal-close">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <p>Please fill in all required fields correctly.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn modal-close-btn">OK</button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(errorModal);
                        
                        // Add event listeners to the new modal
                        const closeBtn = errorModal.querySelector('.modal-close');
                        const closeBtn2 = errorModal.querySelector('.modal-close-btn');
                        
                        closeBtn.addEventListener('click', () => errorModal.remove());
                        closeBtn2.addEventListener('click', () => errorModal.remove());
                        errorModal.addEventListener('click', (e) => {
                            if (e.target === errorModal) errorModal.remove();
                        });
                    }
                });
            }

            // Banner and Logo upload functionality
            const bannerInput = document.getElementById('banner');
            const logoInput = document.getElementById('logo');
            const bannerPreview = document.getElementById('bannerPreview');
            const logoPreview = document.getElementById('logoPreview');
            const bannerPlaceholder = document.getElementById('bannerPlaceholder');
            const logoPlaceholder = document.getElementById('logoPlaceholder');

            // Banner upload
            if (bannerInput) {
                bannerInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            if (bannerPreview) {
                                bannerPreview.src = e.target.result;
                            } else if (bannerPlaceholder) {
                                // Replace placeholder with image
                                bannerPlaceholder.outerHTML = `<img src="${e.target.result}" alt="Hospital Banner" class="banner-image" id="bannerPreview">`;
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Logo upload
            if (logoInput) {
                logoInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            if (logoPreview) {
                                logoPreview.src = e.target.result;
                            } else if (logoPlaceholder) {
                                // Replace placeholder with image
                                logoPlaceholder.outerHTML = `<img src="${e.target.result}" alt="Hospital Logo" class="logo-image" id="logoPreview">`;
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>

<?php include 'includes/hospital_footer.php'; ?>