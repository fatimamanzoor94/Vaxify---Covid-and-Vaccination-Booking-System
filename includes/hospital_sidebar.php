<aside class="sidebar" id="sidebar">
  <div class="brand">
    <a href="hospital_dashboard.php">
      <img src="assets/img/Vaccination_Logo3-removebg-preview.png" alt="Vaxify Logo" class="logo">
    </a>
  </div>
  <nav class="nav">
    
    <a href="hospital_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'hospital_dashboard.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-gauge ico"></i><span class="label">Dashboard</span>
    </a>

    <a href="hospital_patients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'hospital_patients.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-users ico"></i><span class="label">Patient</span>
    </a>

    <a href="hospital_requests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'hospital_requests.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-file-circle-check ico"></i><span class="label">Requests</span>
    </a>

    <a href="hospital_test_results.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'hospital_test_results.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-vial-virus ico"></i><span class="label">Covid Test Reports</span>
    </a>

    <a href="hospital_vaccination_status.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'hospital_vaccination_status.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-syringe ico"></i><span class="label">Vaccination status</span>
    </a>

    <a href="hospital_profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'hospital_profile.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-user-md ico"></i><span class="label">Profile</span>
    </a>

  </nav>
  <div class="logout">
    <button id="logoutBtn"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
  </div>
</aside>