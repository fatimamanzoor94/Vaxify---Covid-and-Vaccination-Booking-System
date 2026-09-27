<?php
session_start();
include("includes/db_connect.php");

// Initialize variables
$email = "";
$errors = [];
$isValid = true;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and validate inputs
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // ===== Validation =====
    if (empty($email)) {
        $errors['email'] = "Email is required!";
        $isValid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format!";
        $isValid = false;
    }

    if (empty($password)) {
        $errors['password'] = "Password is required!";
        $isValid = false;
    } elseif (strlen($password) < 6) {
        $errors['password'] = "At least 6 characters required!";
        $isValid = false;
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors['password'] = "Must contain 1 uppercase letter!";
        $isValid = false;
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors['password'] = "Must contain 1 number!";
        $isValid = false;
    } elseif (!preg_match("/[!@#$%^&*]/", $password)) {
        $errors['password'] = "Must contain 1 special character!";
        $isValid = false;
    }

    // ===== If validation passed =====
    if ($isValid) {
        $user = null;

        // ===== Check users table =====
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
        } else {

        // ===== Check hospitals table =====
        $sql2 = "SELECT * FROM hospitals WHERE email = ?";
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, "s", $email);
        mysqli_stmt_execute($stmt2);
        $result2 = mysqli_stmt_get_result($stmt2);

        if ($result2 && mysqli_num_rows($result2) > 0) {
        $user = mysqli_fetch_assoc($result2);
        $user['role'] = "Hospital";

        // Set correct name field
        if (isset($user['hospital_name'])) {
            $user['name'] = $user['hospital_name'];
        } elseif (isset($user['name'])) {
            $user['name'] = $user['name'];
        }

        $isApproved = false;
    
    // Check new is_approved column first
    if (isset($user['is_approved'])) {
        $isApproved = ($user['is_approved'] == 1);
    } 
    // Fallback to status column if is_approved doesn't exist
    elseif (isset($user['status'])) {
        $isApproved = ($user['status'] === "Approved");
    }
    
    // If hospital is NOT approved, show error
    if (!$isApproved) {
        $errors['email'] = "Your hospital account is pending admin approval. Please wait for approval before logging in.";
        $user = null; // prevent login
    }
} else {

                // ===== Check patients table =====
                $sql3 = "SELECT * FROM patients WHERE email = ?";
                $stmt3 = mysqli_prepare($conn, $sql3);
                mysqli_stmt_bind_param($stmt3, "s", $email);
                mysqli_stmt_execute($stmt3);
                $result3 = mysqli_stmt_get_result($stmt3);

                if ($result3 && mysqli_num_rows($result3) > 0) {
                    $user = mysqli_fetch_assoc($result3);
                    $user['role'] = "Patient";
                }
            }
        }

        // ===== If user exists, verify password =====
        if ($user) {

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_image'] = $user['image_path'] ?? null;
                $_SESSION['user_role'] = $user['role'] ?? 'User';

                // ===== Admin Login =====
                if ($user['role'] === "Admin") {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['name'];
                    header("Location: admin_dashboard.php");
                    exit();
                }

                // ===== Hospital Login =====
                if ($user['role'] === "Hospital") {
                    $_SESSION['hospital_id'] = $user['id'];
                    $_SESSION['hospital_name'] = $user['name'];
                    header("Location: hospital_dashboard.php");
                    exit();
                }

                // ===== Patient Login =====
                if ($user['role'] === "Patient") {
                    $_SESSION['patient_id'] = $user['id'];
                    $_SESSION['patient_name'] = $user['name'];
                    header("Location: patient_dashboard.php");
                    exit();
                }

            } else {
                $errors['password'] = "Incorrect password.";
            }

        } else {
            if (!isset($errors['email'])) {
                $errors['email'] = "Email not registered.";
            }
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - COVID Care Portal</title>
        <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-image: url('assets/img/ImgRegstr.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, #0d1f25 0%, #163138 100%);
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .left-panel::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="none" width="100" height="100"/><circle cx="50" cy="50" r="40" stroke="%231c4a54" stroke-width="2" fill="none"/></svg>');
            background-size: 100px;
            opacity: 0.1;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #5ccde4;
            margin-bottom: 30px;
            text-align: center;
        }

        .panel-content {
            text-align: center;
            z-index: 1;
        }

        .panel-content h1 {
            font-size: 32px;
            margin-bottom: 20px;
            color: #5ccde4;
        }

        .panel-content p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #a0c8d0;
        }

        .features {
            text-align: left;
            margin-top: 30px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #a0c8d0;
        }

        .feature-item i {
            color: #5ccde4;
            margin-right: 10px;
            font-size: 18px;
        }

        .right-panel {
            flex: 1;
            background-color: #fff;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-container {
            text-align: center;
        }

        .form-container h2 {
            color: #0d1f25;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            color: #0d1f25;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d0d0d0;
            border-radius: 5px;
            font-size: 16px;
            color: #0d1f25;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: #5ccde4;
            box-shadow: 0 0 5px rgba(92, 205, 228, 0.3);
        }

        .form-error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            min-height: 20px;
        }

        .login-btn {
            display: block;
            width: 100%;
            background-color: #5ccde4;
            color: #0d1f25;
            padding: 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background-color: #4ab8d0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(92, 205, 228, 0.3);
        }

        .register-link {
            margin-top: 20px;
            font-size: 14px;
            color: #4a4a4a;
        }

        .register-link a {
            color: #5ccde4;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .left-panel, .right-panel {
                padding: 30px 20px;
            }
        }

.back-home-btn {
    display: inline-block;
    margin-top: 30px;
    padding: 12px 20px;
    color: #58b8cbff;
    text-decoration: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 16px;
    letter-spacing: 0.5px;
    backdrop-filter: blur(8px);
    position: relative;
    overflow: hidden;
    transition: all 0.4s ease;
}

/* Hover Effects */
.back-home-btn:hover {
    color: #18444c;  
    border-color: #5ccde4;
    transform: translateY(-3px); 
}

    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="panel-content">
                <div class="logo">COVID CARE PORTAL</div>
                <h1>Welcome Back!</h1>
                <p>Access your personalized dashboard to manage appointments and vaccination records.</p>
                <div class="features">
                    <div class="feature-item"><i>✓</i> Easy Appointment Booking</div>
                    <div class="feature-item"><i>✓</i> Secure Payment Processing</div>
                    <div class="feature-item"><i>✓</i> Digital Signature Support</div>
                    <div class="feature-item"><i>✓</i> 24/7 Customer Support</div>
                </div>
            </div>
        </div>
        <div class="right-panel">
            <div class="form-container">
                <h2>Login</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" name="email" id="email" value="<?= htmlspecialchars($email) ?>">
                        <div class="form-error"><?= $errors['email'] ?? '' ?></div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password">
                        <div class="form-error"><?= $errors['password'] ?? '' ?></div>
                    </div>
                    <button type="submit" class="login-btn">Login</button>
                    <div class="register-link">
                        Don't have an account? <a href="register.php">Register here</a>
                    </div>
                    <a href="view/index.php" class="back-home-btn">← Back to Home</a>
                </form>
            </div>
        </div>
    </div>

    <script>
document.addEventListener("DOMContentLoaded", function () {

    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");

    // Email error remove on typing
    emailInput.addEventListener("input", function () {
        document.querySelector(".form-error").textContent = "";
    });

    // Password error remove on typing
    passwordInput.addEventListener("input", function () {
        const errorDivs = document.querySelectorAll(".form-error");
        errorDivs[1].textContent = "";
    });

});
</script>
</body>
</html>