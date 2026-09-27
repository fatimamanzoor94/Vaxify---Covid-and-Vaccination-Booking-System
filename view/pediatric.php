<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Pediatric COVID Testing - Vaxify Child Healthcare Services</title>
  <meta name="description" content="Specialized COVID-19 testing services for children. Gentle, safe, and reliable testing with child-friendly approaches at Vaxify.">
  <meta name="keywords" content="pediatric COVID testing, children COVID test, child COVID testing, kids COVID testing, pediatric COVID services">

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

<body class="service-page">

  <!-- Healthcare Navigation -->
  <nav class="navbar">
    <a href="index.php" class="logo d-flex align-items-center me-auto">
          <!-- Uncomment the line below if you also wish to use an image logo -->
          <!-- <img src="assets/img/logo.png" alt=""> -->
          <h1 class="sitename">Vaxify</h1>
    </a>
    
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="services.php"  class="active">Services</a></li>
      <li><a href="testing_center.php">Pediatric Centers</a></li>
      <li><a href="doctors.php">Pediatricians</a></li>
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

    <!-- Hero Section -->
    <section id="hero" class="hero section dark-background">
      <div class="container-fluid p-0">
        <div class="hero-wrapper">
          <div class="hero-image">
            <img src="../assets/img/GettyImages-1417341332-2048x1366.jpg" alt="Pediatric COVID Testing" class="img-fluid">
          </div>

          <div class="hero-content">
            <div class="container">
              <div class="row">
                <div class="col-lg-7 col-md-10" data-aos="fade-right" data-aos-delay="100">
                  <div class="content-box">
                    <span class="badge-accent" data-aos="fade-up" data-aos-delay="150">Child-Friendly Care</span>
                    <h1 data-aos="fade-up" data-aos-delay="200">Pediatric COVID Testing</h1>
                    <p data-aos="fade-up" data-aos-delay="250">Gentle and safe COVID-19 testing services designed specifically for children of all ages.</p>

                    <div class="cta-group" data-aos="fade-up" data-aos-delay="300">
                      <a href="../book_appointment.php" class="btn btn-primary">Schedule Child's Test</a>
                      <a href="testing_center.php" class="btn btn-outline">Find Pediatric Center</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Hero Section -->

    <!-- Service Details Section -->
    <section id="service-details" class="service-details section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row">
          <div class="col-lg-12">
            <div class="service-content">
              <h2>Pediatric COVID Testing</h2>
              <p>Vaxify offers specialized COVID-19 testing services designed specifically for children. Our pediatric-trained healthcare professionals use gentle approaches and child-friendly techniques to make the testing experience as comfortable as possible for your child. We understand that children have unique needs when it comes to medical procedures, and our team is trained to address those needs with compassion and expertise.</p>
              
              <p>Our pediatric COVID testing services include both PCR and rapid antigen tests with age-appropriate collection methods. For younger children, we use less invasive techniques when possible, and our facilities are designed with colorful, child-friendly environments to help reduce anxiety. Parents can stay with their children throughout the entire testing process to provide comfort and reassurance.</p>

              <h2>Benefits of Pediatric COVID Testing</h2>
              <div class="benefits-list">
                <div class="benefit-item" data-aos="fade-up" data-aos-delay="200">
                  <div class="icon">
                    <i class="bi bi-check-circle-fill"></i>
                  </div>
                  <h4>Child-Friendly Approach</h4>
                  <p>Our pediatric-trained specialists use gentle techniques and a compassionate approach to make testing comfortable for children.</p>
                </div>
                
                <div class="benefit-item" data-aos="fade-up" data-aos-delay="300">
                  <div class="icon">
                    <i class="bi bi-check-circle-fill"></i>
                  </div>
                  <h4>Age-Appropriate Methods</h4>
                  <p>We utilize testing methods suitable for different age groups, from infants to teenagers, ensuring safety and comfort.</p>
                </div>
                
                <div class="benefit-item" data-aos="fade-up" data-aos-delay="400">
                  <div class="icon">
                    <i class="bi bi-check-circle-fill"></i>
                  </div>
                  <h4>Quick Results</h4>
                  <p>Get reliable results quickly, with rapid tests available within minutes and PCR tests within 24-48 hours.</p>
                </div>
                
                <div class="benefit-item" data-aos="fade-up" data-aos-delay="500">
                  <div class="icon">
                    <i class="bi bi-check-circle-fill"></i>
                  </div>
                  <h4>Parental Support</h4>
                  <p>Parents are encouraged to stay with their children during testing to provide comfort and emotional support.</p>
                </div>
              </div>

              <div class="cta-section" data-aos="fade-up" data-aos-delay="600">
                <h2>Need Pediatric COVID Testing?</h2>
                <p>Ensure your child receives gentle, professional COVID testing from our pediatric specialists. Book an appointment today.</p>
                <a href="../book_appointment.php" class="btn btn-secondary">Schedule Pediatric Test Now</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Service Details Section -->

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
            <li><a href="pediatric-covid-testing.php">Pediatric Testing</a></li>
            <li><a href="health-certificates.php">Health Certificates</a></li>
            <li><a href="telemedicine.php">Mobile Services</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Pediatric Resources</h4>
          <ul>
            <!-- <li><a href="#">Child Symptoms Checker</a></li> -->
            <li><a href="vaccination.php">Child Vaccine Information</a></li>
            <li><a href="testing_center.php">Pediatric Testing Guidelines</a></li>
            <li><a href="travel-clearance.php">Child Travel Requirements</a></li>
            <li><a href="pediatric-covid-testing.php">Child Health & Safety</a></li>
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