<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?php echo $page_title ?? 'Hospital Panel'; ?> - Vaxify</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  :root {
  --sidebar-width: 220px;
  --teal: #0ea5a4;
  --teal-light: #14bfbf;
  --dark: #0f2b2c;
  --muted: #6b7280;
  --card-bg: #ffffff;
  --radius: 12px;
  --shadow: 0 6px 18px rgba(2,6,23,0.08);
  --background-color: #ffffff;
  --default-color: #2c3031;
  --heading-color: #18444c; 
  --accent-color: #049ebb; 
  --surface-color: #ffffff; 
  --contrast-color: #ffffff; 
  --nav-color: #496268;  
  --nav-hover-color: #049ebb; 
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

body {
  background: linear-gradient(180deg, #2f3737 0%, #1f2324 100%);
  color: #0f1724;
  min-height: 100vh;
}

a {
  color: inherit;
  text-decoration: none;
}

.app {
  display: flex;
  width: 100%;
  min-height: 100vh;
}

/* Fixed Sidebar */
.sidebar {
  width: var(--sidebar-width);
  background: linear-gradient(180deg, var(--dark), #062728);
  color: #dbeaea;
  padding: 18px 18px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  overflow-y: auto;
  z-index: 100;
}

.brand {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 12px 0;
  margin-bottom: 10px;
  flex-shrink: 0;
}

.brand .logo {
  width: 140px;
  height: 140px;
  border-radius: 6px;
  object-fit: contain;
}

.nav {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: auto;
  padding: 6px 0;
  flex: 1;
}

.nav a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  color: #cfe8e8;
  font-size: 14px;
  transition: background 0.25s, color 0.25s, transform 0.2s;
}

.nav a .ico {
  color: #fff;
  font-size: 16px;
  width: 20px;
  text-align: center;
}

.nav a.active {
  background: linear-gradient(90deg, var(--teal), #14bfbf);
  color: #ffffffff;
  font-weight: 600;
}

.nav a:hover {
  background: rgba(20,191,191,0.15);
  color: #fff;
  transform: translateX(4px);
}

.nav a:hover .ico {
  color: #fff;
}

.nav a.active .ico {
  color: #ffffffff;
}

/* Logout */
.logout {
  padding: 6px 12px 12px;
  margin-top: auto;
}

.logout button {
  width: 100%;
  background: transparent;
  color: #dfe;
  border: 1px solid white;
  padding: 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.25s, color 0.25s;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: center;
}

.logout button:hover {
  background: var(--teal);
  color: #fff;
}

/* Main Content Area */
.main {
  flex: 1;
  background: white;
  border-top-left-radius: 8px;
  margin: 18px 18px 18px calc(var(--sidebar-width) + 18px);
  box-shadow: var(--shadow);
  display: flex;
  flex-direction: column;
  min-height: calc(100vh - 36px);
}

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 20px;
  border-bottom: 1px solid #eef2f4;
  background: #fff;
  position: sticky;
  top: 0;
  z-index: 10;
}

.left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.hamburger {
  display: none;
  background: transparent;
  border: 1px solid #b2e5f2;
  padding: 8px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 18px;
}

.topbar h2 {
  font-size: 20px;
  color: #0f1724;
}

.profile {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f1f6f6;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--teal);
}

.content {
  padding: 24px;
  overflow: auto;
  flex: 1;
}

/* Rest of the styles remain the same... */

/* Responsive Design */
@media (max-width: 1024px) {
  .grid {
    grid-template-columns: 1fr;
  }
  .cards {
    grid-template-columns: repeat(2, 1fr);
  }
  .chart-row {
    grid-template-columns: 1fr;
  }
  .right-column {
    order: -1;
  }
}

@media (max-width: 860px) {
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    transform: translateX(-100%);
    width: var(--sidebar-width);
    transition: transform 0.3s ease;
  }
  .sidebar.open {
    transform: translateX(0);
  }
  .main {
    margin: 0;
    border-radius: 0;
    min-height: 100vh;
    margin-left: 0;
  }
  .hamburger {
    display: inline-flex;
  }
  .topbar .left {
    flex: 1;
    justify-content: flex-start;
  }
}

@media (max-width: 600px) {
  .cards {
    grid-template-columns: 1fr;
  }
  .topbar h2 {
    font-size: 18px;
  }
  .profile {
    font-size: 14px;
  }
  .avatar {
    width: 32px;
    height: 32px;
  }
  .content {
    padding: 16px;
  }
  .card p {
    font-size: 16px;
  }
  .up-item {
    flex-direction: column;
    align-items: flex-start;
  }
  .up-item .button-group {
    flex-direction: column;
    width: 100%;
    gap: 6px;
  }
  .up-item button {
    width: 100%;
    min-width: 0;
  }
  .sidebar {
    width: 100%;
    max-width: 260px;
  }
  .nav a {
    font-size: 13px;
  }
  .nav a .ico {
    font-size: 14px;
  }
  .popup-box {
    padding: 20px 24px;
    width: 280px;
  }
  .popup-actions {
    flex-direction: column;
    gap: 10px;
  }
  .popup-actions button {
    width: 100%;
  }
}
</style>
</head>
<body>
<div class="app">
  <?php include 'hospital_sidebar.php'; ?>
  <div class="mobile-overlay" id="mobileOverlay"></div>
  <main class="main">
    <div class="topbar">
      <div class="left">
        <button class="hamburger" id="menuBtn"><i class="fa-solid fa-bars"></i></button>
        <h2><?php echo $page_title ?? 'Hospital Panel'; ?></h2>
      </div>
      <div class="profile">
  <div>
    Welcome, 
    <b>
      <?php 
      if (!empty($_SESSION['hospital_name'])) {
          echo htmlspecialchars($_SESSION['hospital_name']);
      } elseif (!empty($_SESSION['hospital_id'])) {
          echo 'Hospital Admin';
      } else {
          echo 'Guest';
      }
      ?>
    </b>
  </div>
  <div class="avatar"><i class="fa-solid fa-hospital"></i></div>
</div>

    </div>
    <section class="content">