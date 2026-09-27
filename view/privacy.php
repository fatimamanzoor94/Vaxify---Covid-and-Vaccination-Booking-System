<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Privacy - Vaxify COVID Test & Vaccination Booking System</title>
  <meta name="description" content="Privacy Policy for Vaxify COVID-19 testing and vaccination booking platform">
  <meta name="keywords" content="COVID test privacy, vaccination privacy, data protection, Vaxify privacy policy">

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

<body class="privacy-page">

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
            <li class="breadcrumb-item active current">Privacy</li>
          </ol>
        </nav>
      </div>

    <!-- Privacy Section -->
    <section id="privacy" class="privacy section">

      <div class="container" data-aos="fade-up">
        <!-- Header -->
        <div class="privacy-header" data-aos="fade-up">
          <div class="header-content">
            <div class="last-updated">Effective Date: February 27, 2025</div>
            <h1>Privacy Policy</h1>
            <p class="intro-text">This Privacy Policy describes how we collect, use, process, and disclose your information, including personal information, in conjunction with your access to and use of our services.</p>
          </div>
        </div>

        <!-- Main Content -->
        <div class="privacy-content" data-aos="fade-up">
          <!-- Introduction -->
          <div class="content-section">
            <h2>1. Introduction</h2>
            <p>When you use our services, you're trusting us with your information. We understand this is a big responsibility and work hard to protect your information and put you in control.</p>
            <p>This Privacy Policy is meant to help you understand what information we collect, why we collect it, and how you can update, manage, export, and delete your information.</p>
          </div>

          <!-- Information Collection -->
          <div class="content-section">
            <h2>2. Information We Collect</h2>
            <p>We collect information to provide better services to our users. The types of information we collect include:</p>

            <h3>2.1 Information You Provide</h3>
            <p>When you create an account or use our services, you provide us with personal information that includes:</p>
            <ul>
              Your name and contact information
              Account credentials
              Payment information when required
              Communication preferences
            </ul>

            <h3>2.2 Automatic Information</h3>
            <p>We automatically collect and store certain information when you use our services:</p>
            <ul>
              Device information and identifiers
              Log information and usage statistics
              Location information when enabled
              Browser type and settings
            </ul>
          </div>

          <!-- Use of Information -->
          <div class="content-section">
            <h2>3. How We Use Your Information</h2>
            <p>We use the information we collect to provide, maintain, and improve our services. Specifically, we use your information to:</p>
            <ul>
              Provide and personalize our services
              Process transactions and send related information
              Send notifications and updates about our services
              Maintain security and verify identity
              Analyze and improve our services
            </ul>
          </div>

          <!-- Information Sharing -->
          <div class="content-section">
            <h2>4. Information Sharing and Disclosure</h2>
            <p>We do not share personal information with companies, organizations, or individuals outside of our company except in the following cases:</p>

            <h3>4.1 With Your Consent</h3>
            <p>We will share personal information with companies, organizations, or individuals outside of our company when we have your consent to do so.</p>

            <h3>4.2 For Legal Reasons</h3>
            <p>We will share personal information if we have a good-faith belief that access, use, preservation, or disclosure of the information is reasonably necessary to:</p>
            <ul>
              Meet any applicable law, regulation, legal process, or enforceable governmental request
              Enforce applicable Terms of Service
              Detect, prevent, or otherwise address fraud, security, or technical issues
              Protect against harm to the rights, property, or safety of our users
            </ul>
          </div>

          <!-- Data Security -->
          <div class="content-section">
            <h2>5. Data Security</h2>
            <p>We work hard to protect our users from unauthorized access to or unauthorized alteration, disclosure, or destruction of information we hold. In particular:</p>
            <ul>
              We encrypt our services using SSL
              We review our information collection, storage, and processing practices
              We restrict access to personal information to employees who need that information
            </ul>
          </div>

          <!-- Your Rights -->
          <div class="content-section">
            <h2>6. Your Rights and Choices</h2>
            <p>You have certain rights regarding your personal information, including:</p>
            <ul>
              The right to access your personal information
              The right to correct inaccurate information
              The right to request deletion of your information
              The right to restrict or object to our processing of your information
            </ul>
          </div>

          <!-- Policy Updates -->
          <div class="content-section">
            <h2>7. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the effective date at the top.</p>
            <p>Your continued use of our services after any changes to this Privacy Policy constitutes your acceptance of such changes.</p>
          </div>
        </div>

        <!-- Contact Section -->
        <div class="privacy-contact" data-aos="fade-up">
          <h2>Contact Us</h2>
          <p>If you have any questions about this Privacy Policy or our practices, please contact us:</p>
          <div class="contact-details">
            <p><strong>Email:</strong> privacy@vaxify.com</p>
            <p><strong>Address:</strong> 123 Health Plaza, Medical District, MD 20815</p>
          </div>
        </div>

      </div>

    </section><!-- /Privacy Section -->

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
            <li><a href="index.php" class="active">Home</a></li>
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