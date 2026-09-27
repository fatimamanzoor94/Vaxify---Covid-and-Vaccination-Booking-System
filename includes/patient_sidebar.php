<aside class="sidebar" id="sidebar">
  <div class="brand">
    <a href="patient_dashboard.php">
      <img src="assets/img/Vaccination_Logo3-removebg-preview.png" alt="Vaxify Logo" class="logo">
    </a>
  </div>

  <nav class="nav">
    <a href="patient_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patient_dashboard.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-gauge ico"></i><span class="label">Dashboard</span>
    </a>

    <a href="search_hospital.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'search_hospital.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-hospital ico"></i><span class="label">Search Hospital</span>
    </a>

    <a href="book_appointment.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'book_appointment.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-calendar-check ico"></i><span class="label">Book Appointment</span>
    </a>

    <a href="my_appointments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_appointments.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-notes-medical ico"></i><span class="label">My Appointments</span>
    </a>

    <a href="view_results.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_results.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-file-medical ico"></i><span class="label">View Results</span>
    </a>

    <a href="patient_profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patient_profile.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-user ico"></i><span class="label">My Profile</span>
    </a>
  </nav>

  <div class="logout">
    <button id="logoutBtn"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
  </div>
</aside>
