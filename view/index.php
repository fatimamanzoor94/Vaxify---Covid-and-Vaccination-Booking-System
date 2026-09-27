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
      background-color: transparent;
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

<body class="index-page">

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

    <!-- Hero Section -->
    <section id="hero" class="hero section dark-background">
      <div class="container-fluid p-0">
        <div class="hero-wrapper">
          <div class="hero-image">
            <img src="../assets/img/updated-covid-vaccine.jpg" alt="COVID-19 Testing and Vaccination" class="img-fluid">
          </div>

          <div class="hero-content">
            <div class="container">
              <div class="row">
                <div class="col-lg-7 col-md-10" data-aos="fade-right" data-aos-delay="100">
                  <div class="content-box">
                    <span class="badge-accent" data-aos="fade-up" data-aos-delay="150">Leading COVID-19 Response</span>
                    <h1 data-aos="fade-up" data-aos-delay="200">Fast, Safe, and Reliable COVID Testing & Vaccination Services</h1>
                    <p data-aos="fade-up" data-aos-delay="250">Book your COVID-19 test or vaccination appointment online. Find nearby testing centers and vaccination sites with real-time availability.</p>

                    <div class="cta-group" data-aos="fade-up" data-aos-delay="300">
                      <a href="../book_appointment.php" class="btn btn-primary">Book COVID Test</a>
                      <a href="services.php" class="btn btn-outline">Schedule Vaccination</a>
                    </div>

                    <div class="info-badges" data-aos="fade-up" data-aos-delay="350">
                      <div class="badge-item">
                        <i class="bi bi-telephone-fill"></i>
                        <div class="badge-content">
                          <span>COVID Hotline</span>
                          <strong>+1 (555) 987-6543</strong>
                        </div>
                      </div>
                      <div class="badge-item">
                        <i class="bi bi-clock-fill"></i>
                        <div class="badge-content">
                          <span>Testing Hours</span>
                          <strong>Mon-Sun: 8AM-8PM</strong>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="features-wrapper">
                <div class="row gy-4">

                  <div class="col-lg-4">
                    <div class="feature-item" data-aos="fade-up" data-aos-delay="450">
                      <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                      </div>
                      <div class="feature-text">
                        <h3>Vaccinations</h3>
                        <p>Book your COVID-19 vaccination appointment at authorized centers near you.</p>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-4">
                    <div class="feature-item" data-aos="fade-up" data-aos-delay="500">
                      <div class="feature-icon">
                        <i class="bi bi-virus"></i>
                      </div>
                      <div class="feature-text">
                        <h3>PCR Testing</h3>
                        <p>Schedule fast and accurate PCR tests with quick results delivery.</p>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-4">
                    <div class="feature-item" data-aos="fade-up" data-aos-delay="550">
                      <div class="feature-icon">
                        <i class="bi bi-clipboard-check"></i>
                      </div>
                      <div class="feature-text">
                        <h3>Health Certificates</h3>
                        <p>Get official COVID-19 test results and vaccination certificates.</p>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section><!-- /Hero Section -->

    <!-- Home About Section -->
    <section id="home-about" class="home-about section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-5 align-items-center">
          <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
            <div class="about-image">
              <img src="../assets/img/Newborn-Baby-Vaccination.webp" alt="COVID Testing Facility" class="img-fluid rounded-3 mb-4">
              <div class="experience-badge">
                <span class="years">2M+</span>
                <span class="text">Vaccinations Administered</span>
              </div>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
            <div class="about-content">
              <h2>Your Trusted Partner in COVID-19 Protection</h2>
              <p class="lead">Vaxify provides a seamless online platform for booking COVID-19 tests and vaccinations with verified healthcare providers.</p>

              <p>Our mission is to make COVID-19 testing and vaccination accessible to everyone. With partnerships with hundreds of certified testing centers and vaccination sites, we ensure you receive quality care when you need it most.</p>

              <div class="row g-4 mt-4">
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
                  <div class="feature-item">
                    <div class="icon">
                      <i class="bi bi-shield-plus"></i>
                    </div>
                    <h4>Verified Providers</h4>
                    <p>All testing centers and vaccination sites are certified and regularly inspected.</p>
                  </div>
                </div>

                <div class="col-md-6" data-aos="fade-up" data-aos-delay="500">
                  <div class="feature-item">
                    <div class="icon">
                      <i class="bi bi-clock-history"></i>
                    </div>
                    <h4>Quick Appointments</h4>
                    <p>Book same-day or next-day appointments at convenient locations near you.</p>
                  </div>
                </div>
              </div>

              <div class="cta-wrapper mt-4">
                <a href="about.php" class="btn btn-primary">Learn More About Us</a>
                <a href="contact.php" class="btn btn-outline">See All Locations</a>
              </div>
            </div>
          </div>
        </div>

        <div class="row mt-5 pt-4 certifications-row" data-aos="fade-up" data-aos-delay="600">
          <div class="col-12 text-center mb-4">
            <h4 class="certification-title">Our Health Partners</h4>
          </div>
          <div class="col-12">
            <div class="certifications">
              <div class="certification-item" data-aos="zoom-in" data-aos-delay="700">
                <img src="../assets/img/clients/clients-1.webp" alt="Health Partner">
              </div>
              <div class="certification-item" data-aos="zoom-in" data-aos-delay="800">
                <img src="../assets/img/clients/clients-2.webp" alt="Health Partner">
              </div>
              <div class="certification-item" data-aos="zoom-in" data-aos-delay="900">
                <img src="../assets/img/clients/clients-3.webp" alt="Health Partner">
              </div>
              <div class="certification-item" data-aos="zoom-in" data-aos-delay="1000">
                <img src="../assets/img/clients/clients-4.webp" alt="Health Partner">
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Home About Section -->

    <!-- Featured Departments Section -->
    <section id="featured-departments" class="featured-departments section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Featured COVID Services</h2>
        <p>Comprehensive COVID-19 testing, vaccination, and support services for your safety and peace of mind</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="department-card">
              <div class="department-image">
                <img src="../assets/img/rt-PCR-items-laboratory-hero.avif" alt="PCR Testing" class="img-fluid">
              </div>
              <div class="department-content">
                <div class="department-icon">
                  <i class="fas fa-virus"></i>
                </div>
                <h3>PCR Testing</h3>
                <p>Gold standard COVID-19 testing with high accuracy. Results available within 24-48 hours with official certification for travel requirements.</p>
                <a href="pcr-testing.php" class="btn-learn-more">
                  <span>Learn More</span>
                  <i class="fas fa-arrow-right"></i>
                </a>
              </div>
            </div>
          </div><!-- End Department Card -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="department-card">
              <div class="department-image">
                <img src="../assets/img/rapid_antigen_test.jpg" alt="Rapid Antigen Testing" class="img-fluid">
              </div>
              <div class="department-content">
                <div class="department-icon">
                  <i class="fas fa-clock"></i>
                </div>
                <h3>Rapid Antigen Testing</h3>
                <p>Quick COVID-19 screening with results in just 15 minutes. Ideal for immediate needs and events with fast turnaround time.</p>
                <a href="rapid-test.php" class="btn-learn-more">
                  <span>Learn More</span>
                  <i class="fas fa-arrow-right"></i>
                </a>
              </div>
            </div>
          </div><!-- End Department Card -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="department-card">
              <div class="department-image">
                <img src="../assets/img/covid-still-life-with-vaccine-2-e1664458442814.jpg" alt="Vaccination Services" class="img-fluid">
              </div>
              <div class="department-content">
                <div class="department-icon">
                  <i class="fas fa-syringe"></i>
                </div>
                <h3>Vaccination Services</h3>
                <p>Access to all approved COVID-19 vaccines including booster shots. Book appointments for first, second, and booster doses.</p>
                <a href="vaccination.php" class="btn-learn-more">
                  <span>Learn More</span>
                  <i class="fas fa-arrow-right"></i>
                </a>
              </div>
            </div>
          </div><!-- End Department Card -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="department-card">
              <div class="department-image">
                <img src="../assets/img/Bk6LSYWG146h4ZRgcfgs36fmbL64mlNbT9ud3XUGsKA.jpg" alt="COVID Health Certificates" class="img-fluid">
              </div>
              <div class="department-content">
                <div class="department-icon">
                  <i class="fas fa-file-medical"></i> <!-- Updated icon -->
                </div>
                <h3>COVID Health Certificates</h3>
                <p>Official COVID-19 test results and vaccination certificates accepted for international travel with digital and physical copies.</p>
                <a href="health-certificates.php" class="btn-learn-more">
                  <span>Learn More</span>
                  <i class="fas fa-arrow-right"></i>
                </a>
              </div>
            </div>
          </div> <!-- End Department Card -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="department-card">
              <div class="department-image">
                <img src="../assets/img/health/oncology-4.webp" alt="Telemedicine Consultation" class="img-fluid">
              </div>
              <div class="department-content">
                <div class="department-icon">
                  <i class="fas fa-laptop-medical"></i>
                </div>
                <h3>Telemedicine Consultation</h3>
                <p>Virtual healthcare consultations for COVID-19 concerns and general health advice from the comfort of your home.</p>
                <a href="telemedicine.php" class="btn-learn-more">
                  <span>Learn More</span>
                  <i class="fas fa-arrow-right"></i>
                </a>
              </div>
            </div>
          </div><!-- End Department Card -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="department-card">
              <div class="department-image">
                <img src="../assets/img/health/emergency-2.webp" alt="Travel Clearance Testing" class="img-fluid">
              </div>
              <div class="department-content">
                <div class="department-icon">
                  <i class="fas fa-plane"></i>
                </div>
                <h3>Travel Clearance Testing</h3>
                <p>Pre-travel COVID-19 testing with certificates accepted for international travel. Quick results for urgent departures.</p>
                <a href="travel-clearance.php" class="btn-learn-more">
                  <span>Learn More</span>
                  <i class="fas fa-arrow-right"></i>
                </a>
              </div>
            </div>
          </div><!-- End Department Card -->

        </div>

      </div>

    </section><!-- /Featured Departments Section -->

    <!-- Call To Action Section -->
    <section id="call-to-action" class="call-to-action section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center">
          <div class="col-lg-8 text-center">
            <h2 data-aos="fade-up" data-aos-delay="200">Your Health is Our Priority</h2>
            <p data-aos="fade-up" data-aos-delay="250">Book your COVID-19 test or vaccination today. Our network of certified centers ensures you receive quality care with quick results and minimal wait times.</p>

            <div class="cta-buttons" data-aos="fade-up" data-aos-delay="300">
              <a href="../book_appointment.php" class="btn-primary">Book COVID Test</a>
              <a href="testing_center.php" class="btn-secondary">Find Vaccination Site</a>
            </div>
          </div>
        </div>

        <div class="row features-row" data-aos="fade-up" data-aos-delay="400">

          <div class="col-lg-4 col-md-6 mb-4">
            <div class="feature-card">
              <div class="icon-wrapper">
                <i class="bi bi-clock-history"></i>
              </div>
              <h5>Quick Results</h5>
              <p>Get your COVID-19 test results within 24-48 hours for PCR tests and 15 minutes for rapid antigen tests.</p>
              <a href="#" class="feature-link">
                <!-- <span>Learn More</span> -->
                <!-- <i class="bi bi-arrow-right"></i> -->
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6 mb-4">
            <div class="feature-card">
              <div class="icon-wrapper">
                <i class="bi bi-calendar-check"></i>
              </div>
              <h5>Easy Online Booking</h5>
              <p>Schedule your COVID-19 test or vaccination appointment in minutes with our user-friendly online platform.</p>
              <a href="#" class="feature-link">
                <!-- <span>Book Now</span> -->
                <!-- <i class="bi bi-arrow-right"></i> -->
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6 mb-4">
            <div class="feature-card">
              <div class="icon-wrapper">
                <i class="bi bi-shield-check"></i>
              </div>
              <h5>Certified Providers</h5>
              <p>All testing centers and vaccination sites in our network are certified and regularly inspected for quality and safety.</p>
              <a href="#" class="feature-link">
                <!-- <span>View Our Partners</span> -->
                <!-- <i class="bi bi-arrow-right"></i> -->
              </a>
            </div>
          </div>

        </div>

        <div class="emergency-alert" data-aos="zoom-in" data-aos-delay="500">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="emergency-content">
                <div class="emergency-icon">
                  <i class="bi bi-telephone-fill"></i>
                </div>
                <div class="emergency-text">
                  <h4>COVID-19 Emergency?</h4>
                  <p>If you're experiencing severe symptoms such as difficulty breathing, persistent pain or pressure in the chest, or confusion, seek immediate medical attention.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 text-end">
              <a href="tel:911" class="emergency-btn">
                <i class="bi bi-telephone-fill"></i>
                Call 1166 – COVID-19 Helpline
              </a>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Call To Action Section -->


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
      
      // Change navbar background on scroll
      window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
          navbar.style.backgroundColor = 'rgba(17, 38, 42, 0.95)';
        } else {
          navbar.style.backgroundColor = 'transparent';
        }
      });
    });
  </script>

</body>

</html>