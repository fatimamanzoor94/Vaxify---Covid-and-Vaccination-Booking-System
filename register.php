<?php
include("includes/db_connect.php");

// Create hardcoded admin user if not exists
$adminCheckQuery = "SELECT * FROM users WHERE email = 'fatima.manzoor@admin.com'";
$adminCheckResult = mysqli_query($conn, $adminCheckQuery);

if (mysqli_num_rows($adminCheckResult) == 0) {
    $adminName = "Fatima Manzoor";
    $adminEmail = "fatima.manzoor@admin.com";
    $adminGender = "Female";
    $adminRole = "Admin";
    $adminPassword = password_hash("Admin123!@", PASSWORD_DEFAULT); 
    $adminImagePath = "Uploads/default_admin.jpg";

    $adminInsertQuery = "INSERT INTO users (name, email, gender, role, password, image_path)
                         VALUES ('$adminName', '$adminEmail', '$adminGender', '$adminRole', '$adminPassword', '$adminImagePath')";
    mysqli_query($conn, $adminInsertQuery);
}

// Initialize variables 
$name = $email = $gender = $role = "";
$password = $confirm_password = "";
$errors = [];
$imagePath = "";

// Form submission 
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $image = $_FILES['profile'] ?? null;

    // Hospital fields
    $registration_number = $_POST['registration_number'] ?? '';
    $contact_numbers = $_POST['contact_numbers'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $opening_time = $_POST['opening_time'] ?? '';
    $closing_time = $_POST['closing_time'] ?? '';
    $services = $_POST['services'] ?? 'Vaccination';
    $available_vaccines = $_POST['available_vaccines'] ?? 'None';
    $vaccines = $_POST['vaccines'] ?? 'None';
    $logo = $_FILES['logo'] ?? null;
    $banner = $_FILES['banner'] ?? null;

    // Validation 
    $isValid = true; 
    $errors = [];    

    // Name validation
    if (empty($name) || !preg_match('/^[a-zA-Z0-9\s\-\.\'&\/,()]+$/u', $name)) {
    $errors['name'] = 'Hospital name contains invalid characters.';
    $isValid = false;
    }

    // Email validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Valid email is required!";
        $isValid = false;
    } else {
        // Check if email already exists in hospitals or patients
        $checkEmail = mysqli_query($conn, "SELECT email FROM hospitals WHERE email='$email' UNION SELECT email FROM patients WHERE email='$email'");
        if(mysqli_num_rows($checkEmail) > 0){
            $errors['email'] = "Email already registered!";
            $isValid = false;
        }
    }

    // Role validation
    if (empty($role) || !in_array($role, ['Hospital', 'Patient'])) {
        $errors['role'] = "Valid role is required!";
        $isValid = false;
    }

    // Conditional validation
    if ($role === 'Patient') {
        if (empty($gender)) {
            $errors['gender'] = "Gender is required!";
            $isValid = false;
        }
    } elseif ($role === 'Hospital') {
        if (empty($registration_number) || !preg_match("/^[A-Z0-9\-]+$/i", $registration_number)) {
            $errors['registration_number'] = "Valid registration number is required!";
            $isValid = false;
        }
    }

    // Password validation
    if (empty($password) || strlen($password) < 6 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[!@#$%^&*]/", $password)) {
        $errors['password'] = "Password must be at least 6 chars, include uppercase, number, and special char!";
        $isValid = false;
    }

    // Confirm password validation
    if (empty($confirm_password) || $confirm_password !== $password) {
        $errors['confirm_password'] = "Passwords do not match!";
        $isValid = false;
    }

    // Profile image validation
    if (!$image || $image['error'] == 4) {
        $errors['profile'] = "Profile image is required!";
        $isValid = false;
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jfif'];
        $max_size = 2 * 1024 * 1024;
        if (!in_array($image['type'], $allowed_types)) {
            $errors['profile'] = "Only JPG, JPEG, PNG allowed!";
            $isValid = false;
        } elseif ($image['size'] > $max_size) {
            $errors['profile'] = "Image must be <2MB!";
            $isValid = false;
        }
    }

    // If valid, process insert 
    if ($isValid) {
        // Upload profile image
        $target_dir = "Uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $file_name = uniqid() . "_" . basename($image['name']);
        $imagePath = $target_dir . $file_name;
        move_uploaded_file($image['tmp_name'], $imagePath);

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($role === "Hospital") {
            // Default logo & banner
            $logoPath = 'Uploads/default_logo.png';
            $bannerPath = 'Uploads/default_banner.png';
            if ($logo && $logo['error'] == 0) {
                $logoPath = "Uploads/" . uniqid() . "_" . basename($logo['name']);
                move_uploaded_file($logo['tmp_name'], $logoPath);
            }
            if ($banner && $banner['error'] == 0) {
                $bannerPath = "Uploads/" . uniqid() . "_" . basename($banner['name']);
                move_uploaded_file($banner['tmp_name'], $bannerPath);
            }

            // Insert into hospitals table
            $sql = "INSERT INTO hospitals (name, type, registration_number, email, contact_numbers, address, status, password, logo, banner, city, latitude, longitude, opening_time, closing_time, services, available_vaccines, vaccines)
                    VALUES ('$name','Hospital','$registration_number','$email','$contact_numbers','$address','Pending','$hashedPassword','$logoPath','$bannerPath','$city','$latitude','$longitude','$opening_time','$closing_time','$services','$available_vaccines','$vaccines')";
            
            if(mysqli_query($conn, $sql)){
                header("Location: success.php");
                exit();
            } else {
                $errors['database'] = "Error inserting hospital: " . mysqli_error($conn);
            }

        } else {
            // Insert into patients table
            $patientInsert = "INSERT INTO patients (name, email, gender, password, image_path)
                              VALUES ('$name', '$email', '$gender', '$hashedPassword', '$imagePath')";

            if (mysqli_query($conn, $patientInsert)) {
                header("Location: success.php");
                exit();
            } else {
                $errors['database'] = "Error inserting patient: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('assets/img/ImgRegstr.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .form-container {
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            padding: 30px;
            background-color: #ffff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
            font-size: 28px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        input[type="text"], 
        input[type="password"], 
        select {
            width: 100%;
            padding: 12px;
            margin-bottom: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus, 
        input[type="password"]:focus, 
        select:focus {
            border-color: #1da1f2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(29, 161, 242, 0.2);
        }
        
        .radio-group {
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
        }
        
        .radio-group label {
            margin-right: 15px;
            font-weight: normal;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .radio-group input[type="radio"] {
            margin-right: 5px;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background-color: #1da1f2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #0c85d0;
        }
        
        .form-error {
            color: #e74c3c;
            font-size: 14px;
            margin-bottom: 15px;
            margin-top: 5px;
        }
        
        .file-input-container {
            border: 2px dashed skyblue;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 10px;
            position: relative;
            overflow: hidden;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }
        
        .file-input-container label {
            display: block;
            color: #333;
            font-weight: normal;
            cursor: pointer;
            margin-bottom: 0;
        }
        
        .file-input-container input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-input-container:hover {
            border-color: #00bfff;
            background-color: #e6f3ff;
        }
        
        /* Media Queries for Responsive Design */
        
        /* Large Devices (Desktops, 1200px and up) */
        @media (min-width: 1200px) {
            .form-container {
                max-width: 600px;
                padding: 40px;
            }
            
            h2 {
                font-size: 32px;
            }
            
            input[type="text"], 
            input[type="password"], 
            select {
                padding: 14px;
                font-size: 18px;
            }
            
            button {
                padding: 16px;
                font-size: 18px;
            }
        }
        
        /* Medium Devices (Tablets, 768px to 1199px) */
        @media (min-width: 768px) and (max-width: 1199px) {
            .form-container {
                max-width: 550px;
                padding: 30px;
            }
            
            h2 {
                font-size: 28px;
            }
            
            input[type="text"], 
            input[type="password"], 
            select {
                padding: 12px;
                font-size: 16px;
            }
            
            button {
                padding: 14px;
                font-size: 16px;
            }
        }
        
        /* Small Devices (Landscape Phones, 576px to 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            .form-container {
                max-width: 500px;
                padding: 25px;
                margin: 15px auto;
            }
            
            h2 {
                font-size: 24px;
                margin-bottom: 20px;
            }
            
            input[type="text"], 
            input[type="password"], 
            select {
                padding: 11px;
                font-size: 15px;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 8px;
            }
            
            .file-input-container {
                padding: 15px;
            }
            
            button {
                padding: 12px;
                font-size: 15px;
            }
        }
        
        /* Extra Small Devices (Portrait Phones, less than 576px) */
        @media (max-width: 575px) {
            body {
                padding: 10px;
            }
            
            .form-container {
                width: 100%;
                padding: 20px;
                margin: 10px auto;
                border-radius: 8px;
            }
            
            h2 {
                font-size: 22px;
                margin-bottom: 15px;
            }
            
            input[type="text"], 
            input[type="password"], 
            select {
                padding: 10px;
                font-size: 14px;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 8px;
            }
            
            .file-input-container {
                padding: 12px;
            }
            
            button {
                padding: 12px;
                font-size: 14px;
            }
            
            .form-error {
                font-size: 13px;
            }
        }
        
        /* Very Small Devices (Small Portrait Phones, less than 360px) */
        @media (max-width: 359px) {
            body {
                padding: 5px;
            }
            
            .form-container {
                padding: 15px;
            }
            
            h2 {
                font-size: 20px;
            }
            
            input[type="text"], 
            input[type="password"], 
            select {
                padding: 9px;
                font-size: 13px;
            }
            
            .file-input-container {
                padding: 10px;
            }
            
            button {
                padding: 10px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Registration Form</h2>

    <?php if (isset($errors['database'])): ?>
        <div class="form-error"><?= $errors['database']; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>">
        <div class="form-error"><?= $errors['name'] ?? '' ?></div>

        <label>Email</label>
        <input type="text" name="email" value="<?= htmlspecialchars($email) ?>">
        <div class="form-error"><?= $errors['email'] ?? '' ?></div>

        <div id="registration-number-container" style="display: none;">
            <label>Registration Number</label>
            <input type="text" name="registration_number" value="<?= htmlspecialchars($registration_number ?? '') ?>">
            <div class="form-error"><?= $errors['registration_number'] ?? '' ?></div>
        </div>


        <label>Role</label>
        <select name="role">
            <option value="">--Select--</option>
            <option value="Hospital" <?= ($role=="Hospital")?"selected":"" ?>>Hospital</option>
            <option value="Patient" <?= ($role=="Patient")?"selected":"" ?>>Patient</option>
        </select>
        <div class="form-error"><?= $errors['role'] ?? '' ?></div>

        <div id="gender-container">
            <label>Gender</label>
            <div class="radio-group">
                <label><input type="radio" name="gender" value="Male" <?= ($gender=="Male")?"checked":"" ?>> Male</label>
                <label><input type="radio" name="gender" value="Female" <?= ($gender=="Female")?"checked":"" ?>> Female</label>
            </div>
            <div class="form-error"><?= $errors['gender'] ?? '' ?></div>
        </div>

        <label>Password</label>
        <input type="password" name="password">
        <div class="form-error"><?= $errors['password'] ?? '' ?></div>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password">
        <div class="form-error"><?= $errors['confirm_password'] ?? '' ?></div>

        <label>Profile Picture</label>
        <div class="file-input-container">
            <label>Choose Profile Picture (JPG, PNG - Max 2MB)</label>
            <input type="file" name="profile" id="profile-input">
        </div>
        <div class="form-error"><?= $errors['profile'] ?? '' ?></div>

        <button type="submit">Register</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.querySelector('input[name="name"]');
    const emailInput = document.querySelector('input[name="email"]');
    const passwordInput = document.querySelector('input[name="password"]');
    const confirmInput = document.querySelector('input[name="confirm_password"]');
    const genderInputs = document.querySelectorAll('input[name="gender"]');
    const roleSelect = document.querySelector('select[name="role"]');
    const profileInput = document.querySelector('input[name="profile"]');
    const registrationNumberContainer = document.getElementById('registration-number-container');

    const nameError = nameInput.nextElementSibling;
    const emailError = emailInput.nextElementSibling;
    const passwordError = passwordInput.nextElementSibling;
    const confirmError = confirmInput.nextElementSibling;
    const genderError = genderInputs[genderInputs.length - 1].parentElement.parentElement.nextElementSibling;
    const roleError = roleSelect.nextElementSibling;
    const profileError = profileInput.parentElement.nextElementSibling;

    // ===== Function to toggle Gender / Registration Number =====
    function toggleFields() {
    const genderContainer = document.getElementById('gender-container');

        if(roleSelect.value === 'Hospital') {
            registrationNumberContainer.style.display = 'block';
            genderContainer.style.display = 'none'; // hide entire gender field
            genderInputs.forEach(input => input.disabled = true);
        } else if(roleSelect.value === 'Patient') {
            registrationNumberContainer.style.display = 'none';
            genderContainer.style.display = 'block'; // show gender
            genderInputs.forEach(input => input.disabled = false);
        } else {
            registrationNumberContainer.style.display = 'none';
            genderContainer.style.display = 'block';
            genderInputs.forEach(input => input.disabled = false);
        }
    }

    // Initial toggle on page load
    toggleFields();

    // Role change listener
    roleSelect.addEventListener('change', function() {
        roleError.textContent = '';
        toggleFields();
    });

    // ===== Real-time validation =====

    // Name validation
    nameInput.addEventListener('input', function() {
        const val = nameInput.value;
        if(/^[a-zA-Z ]+$/.test(val)) nameError.textContent = '';
    });

    // Email validation
    emailInput.addEventListener('input', function() {
        const val = emailInput.value;
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if(pattern.test(val)) emailError.textContent = '';
    });

    // Password validation
    passwordInput.addEventListener('input', function() {
        const val = passwordInput.value;
        const pattern = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{6,}$/;
        if(pattern.test(val)) passwordError.textContent = '';
        if(confirmInput.value === val) confirmError.textContent = '';
    });

    // Confirm password validation
    confirmInput.addEventListener('input', function() {
        if(confirmInput.value === passwordInput.value) confirmError.textContent = '';
    });

    // Gender validation
    genderInputs.forEach(radio => {
        radio.addEventListener('change', function() {
            genderError.textContent = '';
        });
    });

    // Profile image validation
    profileInput.addEventListener('change', function() {
        if(profileInput.files.length > 0) profileError.textContent = '';
    });
});
</script>
</body>
</html>