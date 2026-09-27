<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
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
        
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #5ccde4;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 25px;
            color: #fff;
            font-size: 40px;
        }
        
        .success-title {
            color: #0d1f25;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            color: #4a4a4a;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .note {
            background-color: #f0f9fb;
            padding: 15px;
            border-left: 4px solid #5ccde4;
            margin-bottom: 30px;
            color: #0d1f25;
            font-size: 14px;
        }
        
        .login-btn {
            display: inline-block;
            background-color: #5ccde4;
            color: #0d1f25;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            background-color: #4ab8d0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(92, 205, 228, 0.3);
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .left-panel, .right-panel {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="panel-content">
                <div class="logo">COVID CARE PORTAL</div>
                <h1>Welcome Back!</h1>
                <p>Your health and safety is our priority. Access your personalized dashboard to manage appointments and vaccination records.</p>
                
                <div class="features">
                    <div class="feature-item">
                        <i>✓</i> Easy Appointment Booking
                    </div>
                    <div class="feature-item">
                        <i>✓</i> Secure Payment Processing
                    </div>
                    <div class="feature-item">
                        <i>✓</i> Digital Signature Support
                    </div>
                    <div class="feature-item">
                        <i>✓</i> 24/7 Customer Support
                    </div>
                </div>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="success-icon">✓</div>
            <h2 class="success-title">Registration Successful!</h2>
            
            <div class="success-message">
                Your account has been created successfully.<br><br>

                You can now log in to your account and start booking your COVID-19 test or vaccination appointment. <br><br>
            </div>
            
            <div class="note">
                <strong>Note: </strong> Keep your login details safe for future access.
            </div>
            
            <a href="login.php" class="login-btn">Click here to Login</a>
        </div>
    </div>
</body>
</html>