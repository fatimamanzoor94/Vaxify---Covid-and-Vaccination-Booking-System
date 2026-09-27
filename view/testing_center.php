<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Vaxify COVID Test & Vaccination Booking System</title>
  <meta name="description" content="Locate COVID-19 testing centers and vaccination sites near you with Vaxify. Book appointments with certified healthcare providers.">
  <meta name="keywords" content="COVID test centers, vaccination sites, healthcare providers, COVID-19 testing, COVID vaccination">

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

<body class="find-doctor-page">

  <!-- Healthcare Navigation -->
  <nav class="navbar">
    <a href="index.php" class="logo d-flex align-items-center me-auto">
          <h1 class="sitename">Vaxify</h1>
    </a>
    
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="services.php">Services</a></li>
      <li><a href="testing_center.php"  class="active">Testing Centers</a></li>
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
            <li class="breadcrumb-item active current">Testing Centers</li>
          </ol>
        </nav>
      </div>

      <!-- End Page Title -->

    <!-- Find A Doctor Section -->
    <section id="find-a-doctor" class="find-a-doctor section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Find a Testing Center</h2>
        <p>Locate COVID-19 testing centers and vaccination sites near you</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center" data-aos="fade-up" data-aos-delay="200">
          <div class="col-lg-12">
            <div class="search-container">
              <form class="search-form" id="centerSearchForm">
                <div class="row g-3">
                  <div class="col-md-4">
                    <input type="text" class="form-control" id="locationInput" name="location" placeholder="Enter hospital name..">
                  </div>
                  <div class="col-md-4">
                    <select class="form-select" id="serviceTypeSelect" name="service_type">
                      <option value="">All Service Types</option>
                      <option value="pcr">Liaquat National Hospital</option>
                      <option value="rapid">Dr. Ruth Pfau Civil Hospital</option>
                      <option value="vaccine">Jinnah Postgraduate Medical Centre</option>
                      <option value="antibody">PNS Shifa Hospital</option>
                      <option value="pediatric">Ziauddin Hospital</option>
                      <option value="mobile">Tarique Hospital</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <button type="button" id="searchButton" class="btn btn-primary w-100">
                      <i class="bi bi-search me-2"></i>Find Centers
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="row" id="centersContainer" data-aos="fade-up" data-aos-delay="400">
          <div class="col-lg-4 col-md-6 mb-4 doctor-card-container" data-location="downtown" data-service="pcr,vaccine">
            <div class="doctor-card">
              <div class="doctor-image">
                <img src="../assets/img/central-hospital-1.jpg" alt="Central Medical Center" class="img-fluid">
                <div class="availability-badge online">Open Now</div>
              </div>
              <div class="doctor-info">
                <h5>Liaquat National Hospital</h5>
                <p class="specialty">PCR Testing & Vaccination</p>
                <p class="experience">Downtown Location</p>
                <div class="rating">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <span class="rating-text">(4.9)</span>
                </div>
                <div class="appointment-actions">
                  <!-- <a href="#" class="btn btn-outline-primary btn-sm">View Details</a> -->
                  <a href="../book_appointment.php" class="btn btn-primary btn-sm">Book Now</a>
                </div>
              </div>
            </div>
          </div><!-- End Doctor Card -->

          <div class="col-lg-4 col-md-6 mb-4 doctor-card-container" data-location="west" data-service="rapid">
            <div class="doctor-card">
              <div class="doctor-image">
                <img src="../assets/img/Chermside-Health-Hub.jpeg" alt="Westside Health Hub" class="img-fluid">
                <div class="availability-badge busy">Limited Slots</div>
              </div>
              <div class="doctor-info">
                <h5>Dr. Ruth Pfau Civil Hospital</h5>
                <p class="specialty">Rapid Testing Only</p>
                <p class="experience">West District</p>
                <div class="rating">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-half"></i>
                  <span class="rating-text">(4.7)</span>
                </div>
                <div class="appointment-actions">
                  <!-- <a href="#" class="btn btn-outline-primary btn-sm">View Details</a> -->
                  <a href="../book_appointment.php" class="btn btn-primary btn-sm">Book Now</a>
                </div>
              </div>
            </div>
          </div><!-- End Doctor Card -->

          <div class="col-lg-4 col-md-6 mb-4 doctor-card-container" data-location="north" data-service="vaccine">
            <div class="doctor-card">
              <div class="doctor-image">
                <img src="../assets/img/Shifa-shot.jpg" alt="Community Vaccination Center" class="img-fluid">
                <div class="availability-badge online">Open Now</div>
              </div>
              <div class="doctor-info">
                <h5>Jinnah Postgraduate Medical Centre</h5>
                <p class="specialty">Vaccinations Only</p>
                <p class="experience">North Suburb</p>
                <div class="rating">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <span class="rating-text">(5.0)</span>
                </div>
                <div class="appointment-actions">
                  <!-- <a href="#" class="btn btn-outline-primary btn-sm">View Details</a> -->
                  <a href="../book_appointment.php" class="btn btn-primary btn-sm">Book Now</a>
                </div>

              </div>
            </div>
          </div><!-- End Doctor Card -->

          <div class="col-lg-4 col-md-6 mb-4 doctor-card-container" data-location="multiple" data-service="mobile">
            <div class="doctor-card">
              <div class="doctor-image">
                <img src="../assets/img/4828.webp" alt="Mobile Testing Unit" class="img-fluid">
                <div class="availability-badge offline">Next: Tomorrow 9AM</div>
              </div>
              <div class="doctor-info">
                <h5>PNS Shifa Hospital</h5>
                <p class="specialty">Drive-through Testing</p>
                <p class="experience">Multiple Locations</p>
                <div class="rating">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-half"></i>
                  <span class="rating-text">(4.8)</span>
                </div>
                <div class="appointment-actions">
                  <!-- <a href="#" class="btn btn-outline-primary btn-sm">View Schedule</a> -->
                  <a href="../book_appointment.php" class="btn btn-primary btn-sm">Book Now</a>
                </div>
              </div>
            </div>
          </div><!-- End Doctor Card -->

          <div class="col-lg-4 col-md-6 mb-4 doctor-card-container" data-location="airport" data-service="rapid,pcr">
            <div class="doctor-card">
              <div class="doctor-image">
                <img src="../assets/img/img-20210908-wa0010-optimizada-para-web.webp" alt="Airport Testing Center" class="img-fluid">
                <div class="availability-badge online">Open Now</div>
              </div>
              <div class="doctor-info">
                <h5>Ziauddin Hospital</h5>
                <p class="specialty">Travel Testing</p>
                <p class="experience">International Airport</p>
                <div class="rating">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star"></i>
                  <span class="rating-text">(4.6)</span>
                </div>
                <div class="appointment-actions">
                  <!-- <a href="#" class="btn btn-outline-primary btn-sm">View Details</a> -->
                  <a href="../book_appointment.php" class="btn btn-primary btn-sm">Book Now</a>
                </div>
              </div>
            </div>
          </div><!-- End Doctor Card -->

          <div class="col-lg-4 col-md-6 mb-4 doctor-card-container" data-location="child-friendly" data-service="pediatric,vaccine">
            <div class="doctor-card">
              <div class="doctor-image">
                <img src="../assets/img/Newborn-Baby-Vaccination.webp" alt="Pediatric COVID Clinic" class="img-fluid">
                <div class="availability-badge online">Open Now</div>
              </div>
              <div class="doctor-info">
                <h5>Tarique Hospital</h5>
                <p class="specialty">Children's Services</p>
                <p class="experience">Child-friendly Facility</p>
                <div class="rating">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <span class="rating-text">(4.9)</span>
                </div>
                <div class="appointment-actions">
                  <!-- <a href="#" class="btn btn-outline-primary btn-sm">View Details</a> -->
                  <a href="../book_appointment.php" class="btn btn-primary btn-sm">Book Now</a>
                </div>
              </div>
            </div>
          </div><!-- End Doctor Card -->

          <!-- No results message (initially hidden) -->
          <div id="noResultsMessage" class="col-12 text-center" style="display: none;">
            <div class="alert alert-info">
              <i class="bi bi-info-circle me-2"></i>
              No testing centers found. Please try a different location or service type.
            </div>
          </div>
        </div>

      </div>

    </section>

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

 <script>
document.addEventListener('DOMContentLoaded', function() {

  // Get DOM elements
  const locationInput = document.getElementById('locationInput');
  const serviceTypeSelect = document.getElementById('serviceTypeSelect');
  const searchButton = document.getElementById('searchButton');
  const noResultsMessage = document.getElementById('noResultsMessage');
  const doctorCards = document.querySelectorAll('.doctor-card-container');

  // Function to filter centers
  function filterCenters() {
    const locationValue = locationInput.value.toLowerCase().trim();
    const serviceTypeValue = serviceTypeSelect.value;

    let visibleCount = 0;

    doctorCards.forEach(card => {
      const cardLocation = card.getAttribute('data-location').toLowerCase();
      const cardServices = card.getAttribute('data-service');

      const locationMatch =
        locationValue === '' ||
        cardLocation.includes(locationValue) ||
        card.querySelector('h5').textContent.toLowerCase().includes(locationValue) ||
        card.querySelector('.specialty').textContent.toLowerCase().includes(locationValue) ||
        card.querySelector('.experience').textContent.toLowerCase().includes(locationValue);

      const serviceMatch =
        serviceTypeValue === '' || cardServices.includes(serviceTypeValue);

      // Show / Hide card
      if (locationMatch && serviceMatch) {
        card.style.display = '';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    // Show "No Results"
    noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
  }

  // Add event listeners
  locationInput.addEventListener('input', filterCenters);
  serviceTypeSelect.addEventListener('change', filterCenters);
  searchButton.addEventListener('click', filterCenters);

});
</script>

</body>

</html>