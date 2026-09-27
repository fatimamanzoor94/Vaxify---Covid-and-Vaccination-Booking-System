<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Vaxify COVID Test & Vaccination Booking System</title>
  
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

<body class="services-page">
  <!-- Healthcare Navigation -->
  <nav class="navbar">
    <a href="index.php" class="logo d-flex align-items-center me-auto">
          <h1 class="sitename">Vaxify</h1>
    </a>
    
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="services.php" class="active">Services</a></li>
      <li><a href="testing_center.php">Testing Centers</a></li>
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
            <li class="breadcrumb-item"><a href="#">Services</a></li>
            <li class="breadcrumb-item active current">COVID Testing & Vaccination</li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <h1>COVID Testing & Vaccination Services</h1>
        <p>Comprehensive COVID-19 testing, vaccination, and certification services to keep you and your community safe.</p>
      </div>
    </div><!-- End Page Title -->

    <!-- Services Section -->
    <section id="services" class="services section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="services-tabs">
          <ul class="nav nav-tabs" role="tablist" data-aos="fade-up" data-aos-delay="200">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="services-primary-tab" data-bs-toggle="tab" data-bs-target="#services-primary" type="button" role="tab">Testing Services</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="services-specialty-tab" data-bs-toggle="tab" data-bs-target="#services-specialty" type="button" role="tab">Vaccination Services</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="services-diagnostics-tab" data-bs-toggle="tab" data-bs-target="#services-diagnostics" type="button" role="tab">Travel & Certification</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="services-emergency-tab" data-bs-toggle="tab" data-bs-target="#services-emergency" type="button" role="tab">Emergency COVID Care</button>
            </li>
          </ul>

          <div class="tab-content" data-aos="fade-up" data-aos-delay="300">

            <div class="tab-pane fade show active" id="services-primary" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-virus"></i>
                    </div>
                    <div class="service-details">
                      <h5>PCR Testing</h5>
                      <p>Gold standard COVID-19 testing with high accuracy. Results available within 24-48 hours with official certification for travel requirements.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>High Accuracy</li>
                        <li><i class="fa fa-check-circle"></i>Travel Certification</li>
                        <li><i class="fa fa-check-circle"></i>24-48 Hour Results</li>
                      </ul>
                      <a href="pcr-testing.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-clock"></i>
                    </div>
                    <div class="service-details">
                      <h5>Rapid Antigen Testing</h5>
                      <p>Quick COVID-19 screening with results in just 15 minutes. Ideal for immediate needs and events with fast turnaround time.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>15-Minute Results</li>
                        <li><i class="fa fa-check-circle"></i>Same-Day Appointments</li>
                        <li><i class="fa fa-check-circle"></i>Multiple Locations</li>
                      </ul>
                      <a href="rapid-test.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-child"></i>
                    </div>
                    <div class="service-details">
                      <h5>Pediatric COVID Testing</h5>
                      <p>Specialized COVID-19 testing services for children and adolescents in a child-friendly environment with experienced pediatric staff.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Child-Friendly Environment</li>
                        <li><i class="fa fa-check-circle"></i>Specialized Pediatric Staff</li>
                        <li><i class="fa fa-check-circle"></i>Parental Support</li>
                      </ul>
                      <a href="pediatric.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-ambulance"></i>
                    </div>
                    <div class="service-details">
                      <h5>Mobile Testing Units</h5>
                      <p>Convenient drive-through and mobile testing services that bring COVID-19 testing directly to your community or workplace.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Drive-Through Service</li>
                        <li><i class="fa fa-check-circle"></i>Community Locations</li>
                        <li><i class="fa fa-check-circle"></i>Corporate Services</li>
                      </ul>
                      <a href="travel-clearance.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="services-specialty" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-syringe"></i>
                    </div>
                    <div class="service-details">
                      <h5>COVID-19 Vaccinations</h5>
                      <p>Access to all approved COVID-19 vaccines including booster shots. Book appointments for first, second, and booster doses at authorized centers.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>All Approved Vaccines</li>
                        <li><i class="fa fa-check-circle"></i>Booster Shots Available</li>
                        <li><i class="fa fa-check-circle"></i>Certified Providers</li>
                      </ul>
                      <a href="vaccination.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-baby"></i>
                    </div>
                    <div class="service-details">
                      <h5>Pediatric Vaccinations</h5>
                      <p>Specialized COVID-19 vaccination services for children and adolescents with age-appropriate vaccines and child-friendly environments.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Age-Appropriate Vaccines</li>
                        <li><i class="fa fa-check-circle"></i>Child-Friendly Clinics</li>
                        <li><i class="fa fa-check-circle"></i>Parental Guidance</li>
                      </ul>
                      <a href="telemedicine.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-users"></i>
                    </div>
                    <div class="service-details">
                      <h5>Corporate Vaccination Programs</h5>
                      <p>Specialized COVID-19 vaccination solutions for businesses, schools, and organizations. On-site services and bulk booking options available.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>On-Site Vaccination</li>
                        <li><i class="fa fa-check-circle"></i>Group Booking</li>
                        <li><i class="fa fa-check-circle"></i>Workplace Safety</li>
                      </ul>
                      <a href="vaccination.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-shield-alt"></i>
                    </div>
                    <div class="service-details">
                      <h5>Booster Shot Clinics</h5>
                      <p>Dedicated clinics for COVID-19 booster shots to maintain optimal protection against emerging variants and waning immunity.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Latest Booster Formulations</li>
                        <li><i class="fa fa-check-circle"></i>Quick Appointments</li>
                        <li><i class="fa fa-check-circle"></i>Immunity Assessment</li>
                      </ul>
                      <a href="telemedicine.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="services-diagnostics" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-file-medical-alt"></i>
                    </div>
                    <div class="service-details">
                      <h5>Digital Health Pass</h5>
                      <p>Secure digital storage for your COVID-19 test results and vaccination records. Easily access and share your health status for work, travel, or events.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Digital Certificates</li>
                        <li><i class="fa fa-check-circle"></i>Secure Cloud Storage</li>
                        <li><i class="fa fa-check-circle"></i>Easy Sharing</li>
                      </ul>
                      <a href="digital_healthpass.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-plane"></i>
                    </div>
                    <div class="service-details">
                      <h5>Travel Certification</h5>
                      <p>Official COVID-19 test results and vaccination certificates accepted for international travel with digital and physical copies available.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Travel-Ready Documentation</li>
                        <li><i class="fa fa-check-circle"></i>Multiple Format Options</li>
                        <li><i class="fa fa-check-circle"></i>Country-Specific Requirements</li>
                      </ul>
                      <a href="travel_certification.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="services-emergency" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-12">
                  <div class="service-item emergency-highlight">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-ambulance"></i>
                    </div>
                    <div class="service-details">
                      <h5>Emergency COVID Care</h5>
                      <p>If you're experiencing severe COVID-19 symptoms such as difficulty breathing, persistent pain or pressure in the chest, or confusion, seek immediate medical attention at our emergency facilities.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>24/7 Emergency Services</li>
                        <li><i class="fa fa-check-circle"></i>COVID-Specialized Staff</li>
                        <li><i class="fa fa-check-circle"></i>Isolation Facilities</li>
                        <li><i class="fa fa-check-circle"></i>Advanced Treatment Options</li>
                      </ul>
                      <div class="emergency-actions">
                        <!-- <a href="tel:911" class="btn-emergency">
                          <i class="fa fa-phone"></i>
                          <span>Call Emergency</span>
                        </a> -->
                        <a href="contact.php" class="btn-directions">
                          <i class="fa fa-map-marker-alt"></i>
                          <span>Find Nearest ER</span>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="services-cta" data-aos="fade-up" data-aos-delay="400">
          <div class="row">
            <div class="col-lg-8 mx-auto text-center">
              <div class="cta-content">
                <i class="fa fa-calendar-check"></i>
                <h3>Ready to Schedule Your COVID Test or Vaccination?</h3>
                <p>Book your COVID-19 test or vaccination appointment online today. Our network of certified centers ensures you receive quality care with quick results and minimal wait times.</p>
                <div class="cta-buttons">
                  <a href="../book_appointment.php" class="btn-book">Book Now</a>
                  <a href="testing_center.php" class="btn-contact">Find Testing Center</a>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Services Section -->

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
  </script>

</body>

</html>