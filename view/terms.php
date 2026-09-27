<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Terms - Vaxify COVID Test & Vaccination Booking System</title>
  <meta name="description" content="Terms of Service for Vaxify COVID-19 testing and vaccination booking platform">
  <meta name="keywords" content="COVID test terms, vaccination terms, service agreement, Vaxify terms">

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

  <!-- =======================================================
  * Template Name: Vaxify
  * Template URL: https://bootstrapmade.com/meditrust-bootstrap-hospital-website-template/
  * Updated: Jul 04 2025 with Bootstrap v5.3.7
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
  <style>
    /* Healthcare Navigation Styles */
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

  </style>
</head>

<body class="terms-page">

  <!-- Healthcare Navigation -->
  <nav class="navbar">
    <a href="index.php" class="logo d-flex align-items-center me-auto">
          <!-- Uncomment the line below if you also wish to use an image logo -->
          <!-- <img src="assets/img/logo.png" alt=""> -->
          <h1 class="sitename">Vaxify</h1>
    </a>
    
    <ul class="nav-links">
      <li><a href="index.php" class="active">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="services.php">Services</a></li>
      <li><a href="testing_center.php">Testing Centers</a></li>
      <!-- <li><a href="doctors.php">Doctors</a></li> -->
      <!-- <li><a href="appointment.html">Appointment</a></li> -->
      <li><a href="testimonials.php">Testimonials</a></li>
      <li><a href="contact.php">Contact</a></li>
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
            <li class="breadcrumb-item active current">Terms</li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <h1>Terms of Service</h1>
        <p>Please read these terms of service carefully before using our services</p>
      </div>
    </div><!-- End Page Title -->

    <!-- Terms Of Service Section -->
    <section id="terms-of-service" class="terms-of-service section">

      <div class="container" data-aos="fade-up">
        <!-- Page Header -->
        

        <!-- Content -->
        <div class="tos-content" data-aos="fade-up" data-aos-delay="200">
          <!-- Agreement Section -->
          <div id="agreement" class="content-section">
            <h3>1. Agreement to Terms</h3>
            <p>By accessing our website and services, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing our services.</p>
            <div class="info-box">
              <i class="bi bi-info-circle"></i>
              <p>These terms apply to all users, visitors, and others who access or use our services.</p>
            </div>
          </div>

          <!-- Intellectual Property -->
          <div id="intellectual-property" class="content-section">
            <h3>2. Intellectual Property Rights</h3>
            <p>Our service and its original content, features, and functionality are owned by us and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.</p>
            <ul class="list-items">
              <li>All content is our exclusive property</li>
              <li>You may not copy or modify the content</li>
              <li>Our trademarks may not be used without permission</li>
              <li>Content is for personal, non-commercial use only</li>
            </ul>
          </div>

          <!-- User Accounts -->
          <div id="user-accounts" class="content-section">
            <h3>3. User Accounts</h3>
            <p>When you create an account with us, you must provide accurate, complete, and current information. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account.</p>
            <div class="alert-box">
              <i class="bi bi-exclamation-triangle"></i>
              <div class="alert-content">
                <h5>Important Notice</h5>
                <p>You are responsible for safeguarding the password and for all activities that occur under your account.</p>
              </div>
            </div>
          </div>

          <!-- Prohibited Activities -->
          <div id="prohibited" class="content-section">
            <h3>4. Prohibited Activities</h3>
            <p>You may not access or use the Service for any purpose other than that for which we make it available.</p>
            <div class="prohibited-list">
              <div class="prohibited-item">
                <i class="bi bi-x-circle"></i>
                <span>Systematic retrieval of data or content</span>
              </div>
              <div class="prohibited-item">
                <i class="bi bi-x-circle"></i>
                <span>Publishing malicious content</span>
              </div>
              <div class="prohibited-item">
                <i class="bi bi-x-circle"></i>
                <span>Engaging in unauthorized framing</span>
              </div>
              <div class="prohibited-item">
                <i class="bi bi-x-circle"></i>
                <span>Attempting to gain unauthorized access</span>
              </div>
            </div>
          </div>

          <!-- Disclaimers -->
          <div id="disclaimer" class="content-section">
            <h3>5. Disclaimers</h3>
            <p>Your use of our service is at your sole risk. The service is provided "AS IS" and "AS AVAILABLE" without warranties of any kind, whether express or implied.</p>
            <div class="disclaimer-box">
              <p>We do not guarantee that:</p>
              <ul>
                <li>The service will meet your requirements</li>
                <li>The service will be uninterrupted or error-free</li>
                <li>Results from using the service will be accurate</li>
                <li>Any errors will be corrected</li>
              </ul>
            </div>
          </div>

          <!-- Limitation of Liability -->
          <div id="limitation" class="content-section">
            <h3>6. Limitation of Liability</h3>
            <p>In no event shall we be liable for any indirect, punitive, incidental, special, consequential, or exemplary damages arising out of or in connection with your use of the service.</p>
          </div>

          <!-- Indemnification -->
          <div id="indemnification" class="content-section">
            <h3>7. Indemnification</h3>
            <p>You agree to defend, indemnify, and hold us harmless from and against any claims, liabilities, damages, losses, and expenses arising out of your use of the service.</p>
          </div>

          <!-- Termination -->
          <div id="termination" class="content-section">
            <h3>8. Termination</h3>
            <p>We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</p>
          </div>

          <!-- Governing Law -->
          <div id="governing-law" class="content-section">
            <h3>9. Governing Law</h3>
            <p>These Terms shall be governed by and construed in accordance with the laws of [Your Country], without regard to its conflict of law provisions.</p>
          </div>

          <!-- Changes -->
          <div id="changes" class="content-section">
            <h3>10. Changes to Terms</h3>
            <p>We reserve the right to modify or replace these Terms at any time. We will provide notice of any changes by posting the new Terms on this page.</p>
            <div class="notice-box">
              <i class="bi bi-bell"></i>
              <p>By continuing to access or use our service after those revisions become effective, you agree to be bound by the revised terms.</p>
            </div>
          </div>
        </div>

        <!-- Contact Section -->
        <div class="tos-contact" data-aos="fade-up" data-aos-delay="300">
          <div class="contact-box">
            <div class="contact-icon">
              <i class="bi bi-envelope"></i>
            </div>
            <div class="contact-content">
              <h4>Questions About Terms?</h4>
              <p>If you have any questions about these Terms, please contact us.</p>
              <a href="contact.php" class="contact-link">Contact Support</a>
            </div>
          </div>
        </div>
      </div>

    </section><!-- /Terms Of Service Section -->

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
            <a href="https://www.linkedin.com/feed/"><i class="bi bi-linkedin"></i></a>
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
            <li><a href="#">Symptoms Checker</a></li>
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
  </script>

</body>

</html>