</section>
</main>
</div>

<style>
/* Logout Popup Styling */
.popup-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 36, 0.6);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 2000;
    backdrop-filter: blur(3px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.popup-box {
    background: linear-gradient(180deg, var(--surface-color), #f8fafa);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: clamp(20px, 3vw, 28px) clamp(24px, 4vw, 32px);
    text-align: center;
    width: clamp(280px, 80vw, 340px);
    max-width: 90%;
    animation: slideUp 0.35s ease;
}

@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.popup-box h3 {
    color: var(--heading-color);
    font-size: clamp(16px, 2.5vw, 20px);
    margin-bottom: clamp(8px, 1.5vw, 10px);
}

.popup-box p {
    color: var(--muted);
    font-size: clamp(12px, 2vw, 14px);
    margin-bottom: clamp(15px, 2.5vw, 20px);
}

.popup-actions {
    display: flex;
    justify-content: center;
    gap: clamp(10px, 1.5vw, 12px);
}

.popup-actions button {
    border: none;
    padding: clamp(8px, 1.5vw, 10px) clamp(18px, 3vw, 22px);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.25s ease, transform 0.2s;
}

.cancel-btn {
    background: var(--nav-color);
    color: var(--contrast-color);
    border: 1px solid var(--nav-color);
}

.cancel-btn:hover {
    background: var(--nav-color);
    color: var(--contrast-color);
    transform: scale(1.05);
}

.confirm-btn {
    background: var(--accent-color);
    color: var(--contrast-color);
    border: 1px solid var(--accent-color);
}

.confirm-btn:hover {
    background: var(--nav-hover-color);
    color: var(--contrast-color);
    transform: scale(1.05);
}
</style>

<!-- Custom Logout Confirmation Popup -->
<div id="logoutPopup" class="popup-overlay">
  <div class="popup-box">
    <h3><i class="fa-solid fa-right-from-bracket"></i> Confirm Logout</h3>
    <p>Are you sure you want to log out of your patient session?</p>
    <div class="popup-actions">
      <button id="cancelLogout" class="cancel-btn">Cancel</button>
      <button id="confirmLogout" class="confirm-btn">Logout</button>
    </div>
  </div>
</div>

<script>
// Sidebar toggle functionality
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const mobileOverlay = document.getElementById('mobileOverlay');

menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    mobileOverlay.classList.toggle('active');
});

mobileOverlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    mobileOverlay.classList.remove('active');
});

// Close sidebar when clicking on a link (for mobile)
document.querySelectorAll('.nav a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 860) {
            sidebar.classList.remove('open');
            mobileOverlay.classList.remove('active');
        }
    });
});

// Logout functionality
const logoutBtn = document.getElementById('logoutBtn');
const popup = document.getElementById('logoutPopup');
const cancelBtn = document.getElementById('cancelLogout');
const confirmBtn = document.getElementById('confirmLogout');

logoutBtn.addEventListener('click', () => {
    popup.style.display = 'flex';
});

cancelBtn.addEventListener('click', () => {
    popup.style.display = 'none';
});

confirmBtn.addEventListener('click', () => {
    popup.style.display = 'none';
    // Redirect to patient logout script
    window.location.href = "logout.php";
});

popup.addEventListener('click', (e) => {
    if (e.target === popup) popup.style.display = 'none';
});
</script>
</body>
</html>
