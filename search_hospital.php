<?php
session_start();

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];

// Database connection (PDO)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vaxify";

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle AJAX GET requests for hospital list
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    header('Content-Type: application/json');
    
    try {
        // Get and sanitize parameters
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $service_filter = isset($_GET['service_filter']) ? $_GET['service_filter'] : '';
        $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name';
        $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'asc';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Build query - Only active hospitals
        $query = "SELECT SQL_CALC_FOUND_ROWS h.*, 
                 GROUP_CONCAT(DISTINCT hr.request_type) as pending_requests
          FROM hospitals h
          LEFT JOIN hospital_requests hr 
               ON h.id = hr.hospital_id
               AND hr.patient_id = ?
               AND hr.status = 'pending'
          WHERE h.status = 'Active'";

        $params = [$patient_id];

        // Search filter
        if (!empty($search)) {
            $query .= " AND (h.name LIKE ? OR h.contact_numbers LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Service filter
        if (!empty($service_filter)) {
            $query .= " AND FIND_IN_SET(?, h.services)";
            $params[] = $service_filter;
        }

        // GROUP BY and sorting & pagination
        $query .= " GROUP BY h.id ORDER BY h.$sort_by $sort_order LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $pdo->prepare($query);

        // Bind all parameters
        for ($i = 0; $i < count($params); $i++) {
            // Bind limit and offset as integer
            $paramType = is_int($params[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($i + 1, $params[$i], $paramType);
        }

        $stmt->execute();
        $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $total_stmt = $pdo->query("SELECT FOUND_ROWS()");
        $total_hospitals = $total_stmt->fetchColumn();
        $total_pages = ceil($total_hospitals / $limit);
        
        echo json_encode([
            'success' => true,
            'hospitals' => $hospitals,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_hospitals' => $total_hospitals
            ]
        ]);
        exit();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching hospitals: ' . $e->getMessage()]);
        exit();
    }
}

$page_title = "Patient Panel";
include 'includes/patient_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Hospitals - Vaxify</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --background-color: #ffffff;
            --default-color: #2c3031;
            --heading-color: #18444c;
            --accent-color: #049ebb;
            --surface-color: #ffffff;
            --contrast-color: #ffffff;
            --nav-color: #496268;
            --nav-hover-color: #049ebb;
            --nav-mobile-background-color: #ffffff;
            --nav-dropdown-background-color: #ffffff;
            --nav-dropdown-color: #496268;
            --nav-dropdown-hover-color: #049ebb;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .search-section {
            background: var(--surface-color);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin: 2rem 0;
        }

        .section-title {
            color: var(--heading-color);
            font-size: 1.7rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--heading-color);
            font-size: 0.95rem;
        }

        input, select {
            padding: 0.875rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .btn {
            background-color: var(--accent-color);
            color: var(--contrast-color);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            border-radius: 8px;
            padding: 0.875rem 1.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background-color: var(--nav-hover-color);
            transform: translateY(-2px);
        }

        .btn:disabled {
            background-color: #cbd5e0;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background-color: var(--nav-color);
        }

        .btn-secondary:hover {
            background-color: var(--nav-dropdown-hover-color);
        }

        .search-box {
            display: flex;
            gap: 0.75rem;
        }

        .search-box input {
            flex: 1;
        }

        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .sorting {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .hospital-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .hospital-card {
            background: var(--surface-color);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 1.75rem;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid #f1f5f9;
        }

        .hospital-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .hospital-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }

        .hospital-name {
            color: var(--heading-color);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .hospital-status {
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background-color: #f0fdf4;
            color: #166809ff;
            border: 1px solid #dcfce7;
        }

        .hospital-info {
            margin-bottom: 1.25rem;
            flex-grow: 1;
        }

        .hospital-detail {
            display: flex;
            align-items: flex-start;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            color: #4b5563;
        }

        .hospital-detail strong {
            color: var(--default-color);
            min-width: 70px;
            display: inline-block;
        }

        .services {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .service-tag {
            background-color: var(--nav-color);
            color: var(--contrast-color);
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .service-tag.vaccination {
            background-color: #07567bff;
        }

        .service-tag.covid_test {
            background-color: #0a6446ff;
        }

        /* HOSPITAL ACTIONS - BUTTONS STYLING */
        .hospital-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: auto;
        }

        .hospital-actions button {
            flex: 1;
            padding: 0.75rem;
            font-size: 0.9rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .request-btn {
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--nav-hover-color) 100%);
            color: var(--contrast-color);
        }

        .request-btn:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--nav-hover-color) 0%, #038099 100%);
        }

        .request-btn:active {
            transform: translateY(0);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-btn {
            padding: 0.75rem 1rem;
            background-color: var(--surface-color);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .page-btn:hover {
            background-color: #f8fafc;
            border-color: var(--accent-color);
        }

        .page-btn.active {
            background-color: var(--accent-color);
            color: var(--contrast-color);
            border-color: var(--accent-color);
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 3rem;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--accent-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--nav-color);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: #cbd5e0;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--heading-color);
        }

        .empty-state p {
            font-size: 1.05rem;
            max-width: 500px;
            margin: 0 auto;
            color: #64748b;
        }

        .message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }

        .message.success {
            background-color: #f0fdf4;
            color: #16a34a;
            border: 1px solid #dcfce7;
        }

        .message.error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .results-count {
            font-weight: 600;
            color: var(--heading-color);
            font-size: 1.05rem;
        }

        @media (max-width: 768px) {
            .filters {
                grid-template-columns: 1fr;
            }
            
            .hospital-grid {
                grid-template-columns: 1fr;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .sorting {
                justify-content: center;
            }
            
            .search-section {
                padding: 1.5rem;
            }
            
            .hospital-card {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .hospital-actions {
                flex-direction: column;
            }
            
            .search-box {
                flex-direction: column;
            }
            
            .hospital-actions button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <section class="search-section">
            <h1 class="section-title">Find Hospitals & Book Appointments</h1>
            
            <div class="filters">
                <div class="filter-group">
                    <label for="service_filter">Service Type</label>
                    <select id="service_filter">
                        <option value="">All Services</option>
                        <option value="covid_test">COVID Test</option>
                        <option value="vaccination">Vaccination</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort_by">Sort By</label>
                    <select id="sort_by">
                        <option value="name">Name</option>
                        <option value="city">City</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort_order">Sort Order</label>
                    <select id="sort_order">
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>
            </div>
            
            <div class="search-box">
                <input type="text" id="search_input" placeholder="Search hospitals by name or phone number...">
                <button class="btn" id="search_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Search
                </button>
            </div>
        </section>

        <div class="controls">
            <div class="sorting">
                <span class="results-count" id="results_count">Loading hospitals...</span>
            </div>
        </div>

        <div id="message_area"></div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Loading hospitals...</p>
        </div>

        <div id="hospitals_container">
            <!-- Hospitals will be loaded here via AJAX -->
        </div>

        <div class="pagination" id="pagination">
            <!-- Pagination will be loaded here via AJAX -->
        </div>
    </div>

    <script>
        // Global variables
        let currentPage = 1;
        let totalPages = 1;

        // DOM elements
        const searchInput = document.getElementById('search_input');
        const searchBtn = document.getElementById('search_btn');
        const serviceFilter = document.getElementById('service_filter');
        const sortBy = document.getElementById('sort_by');
        const sortOrder = document.getElementById('sort_order');
        const hospitalsContainer = document.getElementById('hospitals_container');
        const paginationContainer = document.getElementById('pagination');
        const loadingElement = document.getElementById('loading');
        const messageArea = document.getElementById('message_area');
        const resultsCount = document.getElementById('results_count');

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadHospitals();
            
            // Event listeners
            searchBtn.addEventListener('click', function() {
                currentPage = 1;
                loadHospitals();
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    currentPage = 1;
                    loadHospitals();
                }
            });
            
            serviceFilter.addEventListener('change', function() {
                currentPage = 1;
                loadHospitals();
            });
            
            sortBy.addEventListener('change', function() {
                currentPage = 1;
                loadHospitals();
            });
            
            sortOrder.addEventListener('change', function() {
                currentPage = 1;
                loadHospitals();
            });
        });

        // Load hospitals via AJAX
        function loadHospitals() {
            showLoading(true);
            
            const params = new URLSearchParams({
                action: 'list',
                search: searchInput.value,
                service_filter: serviceFilter.value,
                sort_by: sortBy.value,
                sort_order: sortOrder.value,
                page: currentPage
            });
            
            fetch(`?${params}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    showLoading(false);
                    
                    if (data.success) {
                        displayHospitals(data.hospitals);
                        updatePagination(data.pagination);
                        updateResultsCount(data.pagination.total_hospitals);
                    } else {
                        showMessage(data.message, 'error');
                        hospitalsContainer.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">🏥</div>
                                <h3>Error loading hospitals</h3>
                                <p>${data.message}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    showLoading(false);
                    showMessage('Error loading hospitals: ' + error.message, 'error');
                    hospitalsContainer.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">⚠️</div>
                            <h3>Connection Error</h3>
                            <p>Unable to load hospitals. Please check your connection and try again.</p>
                        </div>
                    `;
                    console.error('Error:', error);
                });
        }

        // Display hospitals in the grid
        function displayHospitals(hospitals) {
            if (!hospitals || hospitals.length === 0) {
                hospitalsContainer.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V19C21 20.1046 20.1046 21 19 21Z" stroke="#CBD5E0" stroke-width="2"/>
                                <path d="M8 8H16" stroke="#CBD5E0" stroke-width="2" stroke-linecap="round"/>
                                <path d="M8 12H16" stroke="#CBD5E0" stroke-width="2" stroke-linecap="round"/>
                                <path d="M8 16H12" stroke="#CBD5E0" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>No hospitals found</h3>
                        <p>Try adjusting your search criteria or filters to find what you're looking for.</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="hospital-grid">';
            
            hospitals.forEach(hospital => {
                const services = (hospital.services || '')
            .split(',')
            .map(s => s.trim().toLowerCase());

            const hasCovidTest = services.includes('covid_test');
            const hasVaccination = services.includes('vaccination');
                
                html += `
                    <div class="hospital-card">
                        <div class="hospital-header">
                            <div>
                                <div class="hospital-name">${escapeHtml(hospital.name)}</div>
                                <div class="hospital-detail">
                                    <span>${escapeHtml(hospital.city || 'Not specified')}</span>
                                </div>
                            </div>
                            <div class="hospital-status status-active">
                                Active
                            </div>
                        </div>
                        
                        <div class="hospital-info">
                            <div class="hospital-detail">
                                <strong>Phone:</strong> ${escapeHtml(hospital.contact_numbers || 'Not available')}
                            </div>
                            <div class="hospital-detail">
                                <strong>Address:</strong> ${escapeHtml(hospital.address || 'Not specified')}
                            </div>
                        </div>
                        
                        <div class="services">
                            ${hasCovidTest ? '<span class="service-tag covid_test">COVID Test</span>' : ''}
                            ${hasVaccination ? '<span class="service-tag vaccination">Vaccination</span>' : ''}
                        </div>
                        
                        <div class="hospital-actions">
                            ${hasCovidTest ? `
                                <button class="request-btn" onclick="bookAppointment(${hospital.id}, 'test')">
                                    Request Test
                                </button>
                            ` : ''}
                            ${hasVaccination ? `
                                <button class="request-btn" onclick="bookAppointment(${hospital.id}, 'vaccination')">
                                    Request Vaccine
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            hospitalsContainer.innerHTML = html;
        }

        // Redirect to booking page
        function bookAppointment(hospitalId, type) {
            window.location.href = `book_appointment.php?hospital_id=${hospitalId}&type=${type}`;
        }

        // Update pagination controls
        function updatePagination(pagination) {
            currentPage = pagination.current_page;
            totalPages = pagination.total_pages;
            
            if (totalPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }
            
            let html = '';
            
            // Previous button
            html += `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Previous
            </button>`;
            
            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
            }
            
            // Next button
            html += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>`;
            
            paginationContainer.innerHTML = html;
        }

        // Change page
        function changePage(page) {
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            loadHospitals();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Update results count
        function updateResultsCount(count) {
            resultsCount.textContent = `${count} hospital${count !== 1 ? 's' : ''} found`;
        }

        // Show/hide loading indicator
        function showLoading(show) {
            loadingElement.style.display = show ? 'block' : 'none';
        }

        // Show message
        function showMessage(message, type) {
            messageArea.innerHTML = `<div class="message ${type}">${escapeHtml(message)}</div>`;
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    messageArea.innerHTML = '';
                }, 5000);
            }
        }

        // Utility function to escape HTML
        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return unsafe.toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>

<?php include 'includes/patient_footer.php'; ?>