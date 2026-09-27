<?php

// Database connection
$servername = "localhost";
$username = "root";   
$password = "";      
$dbname = "vaxify";  

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables
$name = $email = $subject = $message = "";
$nameErr = $emailErr = $subjectErr = $messageErr = "";
$successMsg = "";

// Check if form submitted
if (isset($_POST['submit'])) {

    // Name validation
    if (empty($_POST['name'])) {
        $nameErr = "Name is required";
    } else {
        $name = htmlspecialchars(trim($_POST['name']));
        if (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
            $nameErr = "Only letters and spaces allowed";
        }
    }

    // Email validation
    if (empty($_POST['email'])) {
        $emailErr = "Email is required";
    } else {
        $email = htmlspecialchars(trim($_POST['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }

    // Subject validation
    if (empty($_POST['subject'])) {
        $subjectErr = "Subject is required";
    } else {
        $subject = htmlspecialchars(trim($_POST['subject']));
    }

    // Message validation
    if (empty($_POST['message'])) {
        $messageErr = "Message is required";
    } else {
        $message = htmlspecialchars(trim($_POST['message']));
    }

    // If no errors, show success
    if (empty($nameErr) && empty($emailErr) && empty($subjectErr) && empty($messageErr)) {
    try {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        $stmt->execute();

        $successMsg = "Your message has been sent successfully!";
        $name = $email = $subject = $message = ""; 
    } catch(PDOException $e) {
        $successMsg = "Error: " . $e->getMessage();
    }
}

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Vaxify COVID Test & Vaccination Booking System</title>
  <meta name="description" content="Contact Vaxify for COVID-19 testing and vaccination services. Find our locations, contact information, and get answers to your questions.">
  <meta name="keywords" content="COVID testing contact, vaccination contact, Vaxify contact, COVID support, COVID help desk">

  <!-- Favicons -->
  <link href="../assets/img/favicon.png" rel="icon">
  <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="../assets/css/main.css" rel="stylesheet">

  <style>

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 5%;
      background-color: #11262a;
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
      transition: all 0.3s ease;
    }

    .nav-links {
      display: flex;
      list-style: none;
      align-items: center;
    }

    .nav-links li {
      margin: 0 15px;
    }

    .nav-links a {
      text-decoration: none;
      color: #ffffff;
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
      padding: 3px 0;
      font-size: 16px;
    }

    .nav-links a:hover {
      color: #049ebb;
    }

    .nav-links a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: 0;
      left: 0;
      background-color: #049ebb;
      transition: width 0.3s ease;
    }

    .nav-links a:hover::after {
      width: 100%;
    }

    .login-btn {
      background-color: #049ebb;
      color: white;
      border: none;
      padding: 6px 18px;
      border-radius: 30px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 14px;
    }

    .login-btn:hover {
      background-color: #18444c;
      transform: translateY(-2px);
    }

    .hamburger {
      display: none;
      cursor: pointer;
      flex-direction: column;
      justify-content: space-between;
      width: 30px;
      height: 21px;
      z-index: 1100;
    }

    .hamburger span {
      display: block;
      height: 3px;
      width: 100%;
      background-color: white;
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    nav .sitename {
      color: #ffffff;        
      font-weight: 700;       
      font-size: 1.6rem;      
    }

    /* Large screens (desktops) */
    @media screen and (min-width: 1200px) {
      .navbar {
        padding: 18px 8%;
      }
    }

    /* Medium screens (tablets and smaller desktops) */
    @media screen and (max-width: 1160px) {
      .navbar {
        padding: 18px 4%;
      }
      
      .hamburger {
        display: flex;
      }
      
      .nav-links {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background-color: rgba(17, 38, 42, 0.98);
        flex-direction: column;
        align-items: center;
        padding: 15px 0;
        clip-path: polygon(0 0, 100% 0, 100% 0, 0 0);
        transition: all 0.5s ease;
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
      }
      
      .nav-links.active {
        clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);
      }
      
      .nav-links li {
        margin: 10px 0;
        width: 100%;
        text-align: center;
      }
      
      .nav-links a {
        font-size: 18px;
        display: block;
        padding: 8px 0;
      }
      
      .login-btn {
        margin-top: 12px;
        padding: 8px 22px;
        font-size: 16px;
        width: 80%;
      }
    }

    /* Small screens (large phones) */
    @media screen and (max-width: 768px) {
      .navbar {
        padding: 16px 3%;
        background-color: rgba(17, 38, 42, 0.95);
      }
    }

    /* Extra small screens (phones) */
    @media screen and (max-width: 576px) {
      .navbar {
        padding: 15px 2%;
      }
      
      .nav-links a {
        font-size: 16px;
      }
      
      .login-btn {
        width: 70%;
        padding: 6px 18px;
        font-size: 14px;
      }
    }

    /* Ultra small screens (very small phones) */
    @media screen and (max-width: 380px) {
      .navbar {
        padding: 14px 2%;
      }
    }

    /* Hamburger animation */
    @media screen and (max-width: 1160px) {
      .hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
      }
      
      .hamburger.active span:nth-child(2) {
        opacity: 0;
      }
      
      .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -6px);
      }
    }

    /* Active navbar link style */
.nav-links a.active {
  color: #049ebb;
  font-weight: 600;
}

.nav-links a.active::after {
  width: 100%;
  background-color: #049ebb;
}

/* Form Container Styles */
.form-container {
    max-width: 3000px;          /* Ideal form width */
    margin: 40px auto;         /* Center horizontally with top/bottom margin */
    background: #ffffff;       /* White background */
    padding: 40px 30px;        /* Inner spacing */
    border-radius: 12px;       /* Rounded corners */
    box-shadow: 0 6px 20px rgba(0,0,0,0.1); /* Soft shadow */
    transition: all 0.3s ease;
}

/* Optional Title */
.form-container h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #049ebb;              /* Primary color */
    font-size: 28px;
    font-weight: 600;
}

/* Form fields */
.form-container form {
    display: flex;
    flex-direction: column;
    gap: 18px;                
}

.form-container input,
.form-container textarea {
    padding: 14px 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    width: 100%;
    transition: all 0.3s ease;
}

.form-container input:focus,
.form-container textarea:focus {
    outline: none;
    border-color: #049ebb;
    box-shadow: 0 0 8px rgba(4,158,187,0.3);
}

.form-container textarea {
    resize: vertical;
    min-height: 140px;
}

/* Submit Button */
.form-container button {
    padding: 14px;
    border: none;
    background-color: #049ebb;
    color: #fff;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.form-container button:hover {
    background-color: #027b90;
    transform: translateY(-2px);
}

/* Error and Success Messages */
.form-container .error {
    color: red;
    font-size: 13px;
    margin-top: -5px;
}

.form-container .success {
    text-align: center;
    color: green;
    margin-bottom: 15px;
}

/* Responsive for mobile */
@media screen and (max-width: 768px) {
    .form-container {
        padding: 30px 20px;
        margin: 30px 15px;
    }

    .form-container h2 {
        font-size: 24px;
    }
}

.container {max-width: 1200px; margin: 0 auto; padding: 20px;}
.contact-wrapper {display: flex; flex-wrap: wrap; gap: 20px;}
.contact-info-panel {flex: 1; min-width: 300px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.1);}
.contact-form-panel {flex: 1; min-width: 300px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.1);}
.form-container {width: 100%;}
.form-container h2 {color:#049ebb; margin-bottom:20px; text-align:center;}
.form-container input, .form-container textarea, .form-container button {width: 100%; padding: 12px 15px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 16px;}
.form-container input:focus, .form-container textarea:focus {outline:none; border-color:#049ebb; box-shadow: 0 0 8px rgba(4,158,187,0.3);}
.form-container button {background:#049ebb; color:#fff; border:none; cursor:pointer; transition: 0.3s;}
.form-container button:hover {background:#027b90;}
.error {color:red; font-size:14px; margin-top:-8px; margin-bottom:8px;}
.success-popup {
    display: none;
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: #fff;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    z-index: 9999;
    font-size: 16px;
}
.map-container {width: 100%; height: 450px; margin-bottom: 20px;}
@media screen and (max-width: 768px) {.contact-wrapper {flex-direction: column;}}
  </style>
</head>

<body class="contact-page">

  <nav class="navbar">
    <a href="index.php" class="logo d-flex align-items-center me-auto">
          <h1 class="sitename">Vaxify</h1>
    </a>
    
    <ul class="nav-links">
      <li><a href="index.php" >Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="services.php">Services</a></li>
      <li><a href="testing_center.php">Testing Centers</a></li>
      <li><a href="testimonials.php">Testimonials</a></li>
      <li><a href="contact.php" class="active">Contact</a></li>
      <li>
        <button class="login-btn" onclick="window.location.href='../login.php'">LOGIN</button>
      </li>
    </ul>
    
    <div class="hamburger">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </nav>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Category</a></li>
            <li class="breadcrumb-item active current">Contact</li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <h1>Contact</h1>
        <p>Contact Vaxify for COVID-19 testing and vaccination services. Find our locations, contact information, and get answers to your questions.</p>
      </div>
    </div><!-- End Page Title -->

    <!-- Contact Section -->
    <section id="contact" class="contact section">

      <div class="container">
        <div class="contact-wrapper">
          <div class="contact-info-panel">
            <div class="contact-info-header">
              <h3>Contact Information</h3>
              <p>Get in touch with our team for any questions about COVID-19 testing, vaccinations, or our services.</p>
            </div>

            <div class="contact-info-cards">
              <div class="info-card">
                <div class="icon-container">
                  <i class="bi bi-pin-map-fill"></i>
                </div>
                <div class="card-content">
                  <h4>Our Location</h4>
                  <p>123 Health Plaza, Medical District, MD 20815</p>
                </div>
              </div>

              <div class="info-card">
                <div class="icon-container">
                  <i class="bi bi-envelope-open"></i>
                </div>
                <div class="card-content">
                  <h4>Email Us</h4>
                  <p>info@vaxify.com</p>
                </div>
              </div>

              <div class="info-card">
                <div class="icon-container">
                  <i class="bi bi-telephone-fill"></i>
                </div>
                <div class="card-content">
                  <h4>Call Us</h4>
                  <p>+1 (555) 987-6543</p>
                </div>
              </div>

              <div class="info-card">
                <div class="icon-container">
                  <i class="bi bi-clock-history"></i>
                </div>
                <div class="card-content">
                  <h4>Working Hours</h4>
                  <p>Monday-Sunday: 8AM - 8PM</p>
                </div>
              </div>
            </div>

            <div class="social-links-panel">
              <h5>Follow Us</h5>
              <div class="social-icons">
                <a href="https://www.facebook.com/"><i class="bi bi-facebook"></i></a>
                <!-- <a href="#"><i class="bi bi-twitter-x"></i></a> -->
                <a href="https://www.instagram.com/"><i class="bi bi-instagram"></i></a>
                <a href="https://www.linkedin.com/"><i class="bi bi-linkedin"></i></a>
                <!-- <a href="#"><i class="bi bi-youtube"></i></a> -->
              </div>
            </div>
          </div>

          <div class="contact-form-panel">
            <div class="map-container">
              <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d48389.78314118045!2d-74.006138!3d40.710059!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a22a3bda30d%3A0xb89d1fe6bc499443!2sDowntown%20Conference%20Center!5e0!3m2!1sen!2sus!4v1676961268712!5m2!1sen!2sus!4f13.1!3m3!1m2!1s0x89c25a22a3bda30d%3A0xb89d1fe6bc499443!2sDowntown%20Conference%20Center!5e0!3m2!1sen!2sus!4v1676961268712!5m2!1sen!2sus!4f13.1!3m3!1m2!1s0x89c25a22a3bda30d%3A0xb89d1fe6bc499443!2sDowntown%20Conference%20Center!5e0!3m2!1sen!2sus!4v1676961268712!5m2!1sen!2sus" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>

<div class="form-container">
  <h2>Contact Us</h2>

                <?php if($successMsg): ?>
                <div class="success-popup" id="successPopup"><?php echo $successMsg; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="text" name="name" placeholder="Your Name" value="<?php echo htmlspecialchars($name); ?>">
                    <?php if($nameErr) echo '<div class="error">'.$nameErr.'</div>'; ?>

                    <input type="email" name="email" placeholder="Your Email" value="<?php echo htmlspecialchars($email); ?>">
                    <?php if($emailErr) echo '<div class="error">'.$emailErr.'</div>'; ?>

                    <input type="text" name="subject" placeholder="Subject" value="<?php echo htmlspecialchars($subject); ?>">
                    <?php if($subjectErr) echo '<div class="error">'.$subjectErr.'</div>'; ?>

                    <textarea name="message" placeholder="Your Message"><?php echo htmlspecialchars($message); ?></textarea>
                    <?php if($messageErr) echo '<div class="error">'.$messageErr.'</div>'; ?>

                    <button type="submit" name="submit">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</div>
          </div>
        </div>
      </div>
    </section><!-- /Contact Section -->

  </main>

  <footer id="footer" class="footer position-relative light-background">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <a href="index.php" class="logo d-flex align-items-center">
            <span class="sitename">Vaxify</span>
          </a>
          <div class="footer-contact pt-3">
            <p>123 Health Plaza</p>
            <p>Medical District, MD 20815</p>
            <p class="mt-3"><strong>Phone:</strong> <span>+1 5589 55488 55</span></p>
            <p><strong>Email:</strong> <span>info@vaxify.com</span></p>
          </div>
          <div class="social-links d-flex mt-4">
            <a href="https://x.com/ajlkn"><i class="bi bi-twitter-x"></i></a>
            <a href="https://www.facebook.com/"><i class="bi bi-facebook"></i></a>
            <a href="https://www.instagram.com/"><i class="bi bi-instagram"></i></a>
            <a href="https://www.linkedin.com/"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Useful Links</h4>
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About us</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="terms.php">Terms of service</a></li>
            <li><a href="privacy.php">Privacy policy</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Our Services</h4>
          <ul>
            <li><a href="pcr-testing.php">PCR Testing</a></li>
            <li><a href="rapid-test.php">Rapid Testing</a></li>
            <li><a href="vaccination.php">Vaccinations</a></li>
            <li><a href="health-certificates.php">Travel Certificates</a></li>
            <li><a href="telemedicine.php">Mobile Testing</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>COVID Resources</h4>
          <ul>
            <!-- <li><a href="#">Symptoms Checker</a></li> -->
            <li><a href="vaccination.php">Vaccine Information</a></li>
            <li><a href="testing_center.php">Testing Guidelines</a></li>
            <li><a href="travel-clearance.php">Travel Requirements</a></li>
            <li><a href="health-certificates.php">Health & Safety</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Support</h4>
          <ul>
            <li><a href="faq.php">FAQ</a></li>
            <li><a href="contact.php">Contact Support</a></li>
            <li><a href="../book_appointment.php">Booking Help</a></li>
          </ul>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">Vaxify</strong> <span>All Rights Reserved</span></p>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/vendor/php-email-form/validate.js"></script>
  <script src="../assets/vendor/aos/aos.js"></script>
  <script src="../assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="../assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="../assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="../assets/vendor/glightbox/js/glightbox.min.js"></script>

  <!-- Main JS File -->
  <script src="../assets/js/main.js"></script>

  <!-- Healthcare Navigation Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Mobile menu toggle
      const hamburger = document.querySelector('.hamburger');
      const navLinks = document.querySelector('.nav-links');
      
      hamburger.addEventListener('click', function() {
        navLinks.classList.toggle('active');
        hamburger.classList.toggle('active');
      });
      
      // Close mobile menu when clicking on a link
      const links = document.querySelectorAll('.nav-links a');
      links.forEach(link => {
        link.addEventListener('click', () => {
          navLinks.classList.remove('active');
          hamburger.classList.remove('active');
        });
      });
    });

     window.addEventListener("scroll", function() {
    const navbar = document.querySelector(".navbar");
    if (window.scrollY > 10) {
      navbar.classList.add("scrolled");
    } else {
      navbar.classList.remove("scrolled");
    }
  });

  document.addEventListener('DOMContentLoaded', function() {
    const successPopup = document.getElementById('successPopup');
    if(successPopup){
        successPopup.style.display = 'block';
        setTimeout(() => {
            successPopup.style.display = 'none';
        }, 5000); // Hide after 5 seconds
    }
});
  </script>

</body>

</html>