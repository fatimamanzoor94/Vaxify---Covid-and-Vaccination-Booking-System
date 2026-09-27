<?php
session_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Karachi');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'vaxify');
define('DB_USER', 'root');
define('DB_PASS', '');

// Check if hospital is logged in
if (!isset($_SESSION['hospital_id'])) {
    if (isset($_POST['action'])) {
        echo json_encode(['success' => false, 'error' => 'Session expired']);
        exit;
    } else {
        header('Location: login.php');
        exit;
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    $response = ['success' => false];
    
    try {
        switch ($_POST['action']) {
            case 'update_status':
                if (isset($_POST['request_id'], $_POST['status'])) {
                    $stmt = $pdo->prepare("UPDATE hospital_request SET status = ?, updated_at = NOW() WHERE id = ? AND hospital_id = ?");
                    $stmt->execute([$_POST['status'], $_POST['request_id'], $_SESSION['hospital_id']]);
                    $response['success'] = ($stmt->rowCount() > 0);
                    $response['new_status'] = $_POST['status'];
                }
                break;
                
            case 'update_notes':
                if (isset($_POST['request_id'], $_POST['notes'])) {
                    $stmt = $pdo->prepare("UPDATE hospital_request SET admin_notes = ?, updated_at = NOW() WHERE id = ? AND hospital_id = ?");
                    $stmt->execute([$_POST['notes'], $_POST['request_id'], $_SESSION['hospital_id']]);
                    $response['success'] = ($stmt->rowCount() > 0);
                }
                break;
                
            case 'refresh_data':
                $counts = getRequestCounts($pdo, $_SESSION['hospital_id']);
                $requests = getFilteredRequests($pdo, $_SESSION['hospital_id'], $_GET);
                $response = ['success' => true, 'counts' => $counts, 'requests' => $requests];
                break;
                
            case 'get_request_details':
                if (isset($_POST['request_id'])) {
                    try {
                        $stmt = $pdo->prepare("
                            SELECT r.*, p.name as patient_name, p.email, p.phone, p.age, p.gender, 
                                   p.address, p.image_path, p.dob, p.vaccination_status, p.covid_result
                            FROM hospital_request r
                            JOIN patients p ON r.patient_id = p.id
                            WHERE r.id = ? AND r.hospital_id = ?
                        ");
                        
                        if ($stmt->execute([$_POST['request_id'], $_SESSION['hospital_id']])) {
                            $request_details = $stmt->fetch();
                            
                            if ($request_details) {
                                $response['success'] = true;
                                $response['data'] = $request_details;
                            } else {
                                $response['error'] = 'Request not found or access denied';
                            }
                        } else {
                            $response['error'] = 'Database query failed';
                        }
                    } catch (PDOException $e) {
                        $response['error'] = 'Database error: ' . $e->getMessage();
                    }
                } else {
                    $response['error'] = 'Request ID missing';
                }
                break;
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Helper functions
function getRequestCounts($pdo, $hospital_id) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(status = 'pending') as pending,
            SUM(status = 'approved') as approved,
            SUM(status = 'rejected') as rejected
        FROM hospital_request 
        WHERE hospital_id = ?
    ");
    $stmt->execute([$hospital_id]);
    return $stmt->fetch();
}

function getFilteredRequests($pdo, $hospital_id, $filters = []) {
    $where = "WHERE r.hospital_id = ?";
    $params = [$hospital_id];
    
    // Status filter
    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $where .= " AND r.status = ?";
        $params[] = $filters['status'];
    }
    
    // Search filter
    if (!empty($filters['search'])) {
        $where .= " AND (p.name LIKE ? OR p.email LIKE ? OR p.phone LIKE ?)";
        $search_term = "%{$filters['search']}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Request type filter
    if (!empty($filters['request_type']) && $filters['request_type'] !== 'all') {
        $where .= " AND r.request_type = ?";
        $params[] = $filters['request_type'];
    }
    
    // Date filter
    if (!empty($filters['date_from'])) {
        $where .= " AND DATE(r.request_date) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where .= " AND DATE(r.request_date) <= ?";
        $params[] = $filters['date_to'];
    }
    
    // Pagination
    $limit = 10;
    $page = max(1, intval($filters['page'] ?? 1));
    $offset = ($page - 1) * $limit;
    
    $stmt = $pdo->prepare("
        SELECT r.*, p.name as patient_name, p.email, p.phone, p.age, p.gender, p.image_path
        FROM hospital_request r
        JOIN patients p ON r.patient_id = p.id
        $where
        ORDER BY r.request_date DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
    
    // Get total for pagination
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM hospital_request r JOIN patients p ON r.patient_id = p.id $where");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    return [
        'data' => $requests,
        'total' => $total,
        'page' => $page,
        'total_pages' => ceil($total / $limit)
    ];
}

// Get initial data
$counts = getRequestCounts($pdo, $_SESSION['hospital_id']);
$requests_data = getFilteredRequests($pdo, $_SESSION['hospital_id'], $_GET);
$requests = $requests_data['data'];

// Safe htmlspecialchars function to prevent null errors
function safe_html($value) {
    if ($value === null || $value === '') {
        return '';
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$page_title = "Hospital Panel";
include 'includes/hospital_header.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Requests - Vaxify</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        padding: 0 15px;
        width: 100%;
        box-sizing: border-box;
    }

    header {
        margin-bottom: 1rem;
        margin-top: 1.8rem;
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    h1 {
        color: var(--heading-color);
        font-size: 1.8rem;
        margin: 0;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-box {
        background: var(--background-color);
        border-radius: 15px;
        padding: 20px;
        border-left: 5px solid var(--accent-color);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .stat-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.2);
    }

    .stat-box .heading {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        gap: 10px;
    }

    .stat-box i {
        font-size: 1.5rem;
        color: var(--accent-color);
    }

    .stat-box h4 {
        margin: 0;
        font-size: 1rem;
        color: var(--heading-color);
    }

    .stat-box h3 {
        font-size: 2rem;
        color: var(--accent-color);
        margin: 5px 0;
        font-weight: 700;
    }

    .stat-box p {
        margin: 0;
        font-size: 0.85rem;
        color: #777;
    }

    /* Filters */
    .filters {
        display: flex;
        gap: 12px;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        align-items: center;
    }

    .search-box, .status-filter, .request-type-filter, .date-filter, .export-btn {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        box-sizing: border-box;
        min-height: 44px;
    }

    .search-box {
        flex: 1;
        min-width: 200px;
    }

    .date-filter {
        min-width: 140px;
    }

    .btn {
        background: var(--accent-color);
        color: var(--contrast-color);
        border: none;
        padding: 10px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-weight: 500;
    }

    .btn:hover {
        background: var(--nav-hover-color);
        transform: translateY(-1px);
    }

    .btn-outline {
        background: transparent;
        color: var(--accent-color);
        border: 1px solid var(--accent-color);
    }

    .btn-outline:hover {
        background: var(--accent-color);
        color: var(--contrast-color);
    }

    .btn-approve {
        background: #28a745;
    }

    .btn-approve:hover {
        background: #218838;
    }

    .btn-reject {
        background: #dc3545;
    }

    .btn-reject:hover {
        background: #c82333;
    }

    .btn-sm {
        padding: 8px 12px;
        font-size: 12px;
        min-height: 36px;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
        text-align: center;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d1edff;
        color: var(--accent-color);
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    /* Table */
    .requests-table {
        width: 100%;
        background: var(--surface-color);
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }

    th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    th {
        background: #f8f9fa;
        color: var(--heading-color);
        font-weight: 600;
        font-size: 14px;
    }

    td {
        font-size: 14px;
    }

    .patient-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .patient-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        background: #f0f0f0;
        flex-shrink: 0;
    }

    .patient-details h4 {
        color: var(--heading-color);
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 600;
    }

    .patient-contact {
        font-size: 12px;
        color: #666;
        line-height: 1.3;
    }

    .notes-preview {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #666;
        font-size: 13px;
    }

    .actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin: 2rem 0;
        flex-wrap: wrap;
    }

    .page-link {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        text-decoration: none;
        color: var(--default-color);
        font-size: 14px;
        min-width: 40px;
        text-align: center;
        transition: all 0.3s;
    }

    .page-link:hover {
        background: #f8f9fa;
    }

    .page-link.active {
        background: var(--accent-color);
        color: var(--contrast-color);
        border-color: var(--accent-color);
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #666;
    }

    .empty-state svg {
        width: 64px;
        height: 64px;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    /* Modal */
    .modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(6px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 15px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .modal.show {
        display: flex;
        opacity: 1;
    }

    .modal-content {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border-radius: 16px;
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
        width: 95%;
        max-width: 700px;
        padding: 25px;
        position: relative;
        animation: slideUp 0.35s ease forwards;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    @keyframes slideUp {
        from { transform: translateY(50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
        flex-shrink: 0;
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--heading-color);
        margin: 0;
    }

    .close-btn {
        background: transparent;
        border: none;
        font-size: 1.8rem;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.2s ease;
        padding: 0;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .close-btn:hover {
        color: var(--accent-color);
        background-color: rgba(0, 0, 0, 0.05);
        transform: rotate(90deg);
    }

    .modal-body {
        font-size: 0.95rem;
        color: var(--default-color);
        line-height: 1.6;
        overflow-y: auto;
        padding-right: 5px;
        flex: 1;
    }

    .modal-body::-webkit-scrollbar {
        width: 6px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .detail-item {
        background: #f1faff;
        border-left: 4px solid var(--accent-color);
        border-radius: 8px;
        padding: 12px 15px;
    }

    .detail-item strong {
        display: block;
        color: var(--heading-color);
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 0.95rem;
    }

    .detail-item span {
        color: #555;
        font-size: 0.9rem;
    }

    .medical-info {
        background: #fff3e0;
        border-left: 4px solid #ff9800;
        border-radius: 8px;
        padding: 12px 15px;
        margin-bottom: 20px;
    }

    .medical-info strong {
        display: block;
        color: #e65100;
        font-weight: 600;
        margin-bottom: 5px;
    }

    /* Form inputs inside modal */
    .modal-content textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
        background: #fff;
        transition: all 0.3s ease;
        min-height: 100px;
        resize: vertical;
        box-sizing: border-box;
    }

    .modal-content textarea:focus {
        border-color: var(--accent-color);
        outline: none;
        box-shadow: 0 0 6px rgba(4, 158, 187, 0.2);
    }

    /* Modal buttons */
    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: flex-end;
        flex-wrap: wrap;
        flex-shrink: 0;
    }

    .modal-content .btn {
        padding: 10px 16px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
        min-height: 44px;
    }

    .modal-content .btn:hover {
        opacity: 0.9;
    }

    /* Loading spinner */
    .spinner {
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid var(--accent-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        display: inline-block;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Patient Header in Modal */
    .patient-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
        flex-wrap: wrap;
    }

    .patient-avatar-large {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        background: #f0f0f0;
        flex-shrink: 0;
    }

    .patient-header-info h3 {
        margin: 0 0 5px 0;
        color: var(--heading-color);
        font-size: 1.3rem;
    }

    .patient-header-info p {
        margin: 0;
        color: #666;
        font-size: 0.9rem;
    }

    /* Section Headers */
    .section-header {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--heading-color);
        margin: 20px 0 10px 0;
        padding-bottom: 5px;
        border-bottom: 2px solid var(--accent-color);
    }

    /* Success Modal */
    .success-modal .modal-content {
        max-width: 500px;
        text-align: center;
    }

    .success-icon {
        font-size: 4rem;
        color: #28a745;
        margin-bottom: 1rem;
    }

    .success-message {
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
        color: var(--heading-color);
    }

    /* ==================== RESPONSIVE DESIGN ==================== */

    /* Large Tablets (769px - 1024px) */
    @media (max-width: 1024px) {
        .container {
            padding: 0 20px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .filters {
            gap: 10px;
        }
        
        .search-box {
            min-width: 180px;
        }
    }

    /* Tablets (577px - 768px) */
    @media (max-width: 768px) {
        .container {
            padding: 0 15px;
        }
        
        header {
            margin-top: 1.2rem;
            margin-bottom: 0.8rem;
        }
        
        h1 {
            font-size: 1.5rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            padding: 15px;
        }
        
        .stat-box h3 {
            font-size: 1.8rem;
        }
        
        .filters {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
            padding: 12px;
        }
        
        .search-box, .status-filter, .request-type-filter, .date-filter, .export-btn {
            width: 100%;
            min-width: auto;
        }
        
        .requests-table {
            border-radius: 6px;
        }
        
        table {
            min-width: 900px;
        }
        
        th, td {
            padding: 10px 12px;
            font-size: 13px;
        }
        
        .patient-info {
            gap: 8px;
        }
        
        .patient-avatar {
            width: 35px;
            height: 35px;
        }
        
        .actions {
            gap: 4px;
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 11px;
            min-height: 32px;
        }
        
        .modal-content {
            padding: 20px;
            width: 98%;
        }
        
        .modal-title {
            font-size: 1.3rem;
        }
        
        .details-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .patient-header {
            flex-direction: column;
            text-align: center;
            gap: 12px;
        }
        
        .modal-actions {
            justify-content: center;
        }
        
        .pagination {
            gap: 6px;
        }
        
        .page-link {
            padding: 6px 10px;
            font-size: 13px;
            min-width: 36px;
        }
    }

    /* Mobile Devices (426px - 576px) */
    @media (max-width: 576px) {
        .container {
            padding: 0 12px;
        }
        
        header {
            margin-top: 1rem;
        }
        
        .header-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        h1 {
            font-size: 1.3rem;
        }
        
        .stat-box {
            padding: 12px;
        }
        
        .stat-box h3 {
            font-size: 1.6rem;
        }
        
        .stat-box .heading {
            gap: 8px;
        }
        
        .stat-box i {
            font-size: 1.3rem;
        }
        
        .filters {
            padding: 10px;
        }
        
        .search-box, .status-filter, .request-type-filter, .date-filter, .export-btn {
            padding: 8px 10px;
            font-size: 13px;
            min-height: 40px;
        }
        
        .btn {
            padding: 8px 12px;
            font-size: 13px;
            min-height: 40px;
        }
        
        table {
            min-width: 1000px;
        }
        
        th, td {
            padding: 8px 10px;
        }
        
        .patient-avatar {
            width: 32px;
            height: 32px;
        }
        
        .patient-details h4 {
            font-size: 13px;
        }
        
        .patient-contact {
            font-size: 11px;
        }
        
        .status-badge {
            padding: 4px 8px;
            font-size: 11px;
        }
        
        .notes-preview {
            font-size: 12px;
            max-width: 150px;
        }
        
        .modal {
            padding: 10px;
        }
        
        .modal-content {
            padding: 15px;
            border-radius: 12px;
        }
        
        .modal-header {
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 1.2rem;
        }
        
        .close-btn {
            width: 28px;
            height: 28px;
            font-size: 1.5rem;
        }
        
        .patient-avatar-large {
            width: 50px;
            height: 50px;
        }
        
        .patient-header-info h3 {
            font-size: 1.1rem;
        }
        
        .section-header {
            font-size: 1rem;
            margin: 15px 0 8px 0;
        }
        
        .detail-item {
            padding: 10px 12px;
        }
        
        .modal-actions {
            flex-direction: column;
            width: 100%;
        }
        
        .modal-actions .btn {
            width: 100%;
            justify-content: center;
        }
        
        .pagination {
            gap: 4px;
        }
        
        .page-link {
            padding: 5px 8px;
            font-size: 12px;
            min-width: 32px;
        }
    }

    /* Small Mobile Devices (320px - 425px) */
    @media (max-width: 425px) {
        .container {
            padding: 0 10px;
        }
        
        h1 {
            font-size: 1.2rem;
        }
        
        .stat-box {
            padding: 10px;
        }
        
        .stat-box h3 {
            font-size: 1.4rem;
        }
        
        .stat-box h4 {
            font-size: 0.9rem;
        }
        
        .stat-box p {
            font-size: 0.8rem;
        }
        
        .filters {
            padding: 8px;
            gap: 6px;
        }
        
        .btn {
            font-size: 12px;
            padding: 7px 10px;
        }
        
        table {
            min-width: 1100px;
        }
        
        th, td {
            padding: 6px 8px;
            font-size: 12px;
        }
        
        .patient-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
        
        .patient-avatar {
            width: 30px;
            height: 30px;
        }
        
        .actions {
            flex-direction: column;
            gap: 4px;
        }
        
        .btn-sm {
            width: 100%;
            justify-content: center;
            font-size: 10px;
            padding: 5px 8px;
        }
        
        .modal-content {
            padding: 12px;
            width: 99%;
        }
        
        .modal-title {
            font-size: 1.1rem;
        }
        
        .modal-body {
            font-size: 0.9rem;
        }
        
        .details-grid {
            gap: 8px;
        }
        
        .detail-item {
            padding: 8px 10px;
        }
        
        .detail-item strong {
            font-size: 0.9rem;
        }
        
        .detail-item span {
            font-size: 0.85rem;
        }
        
        .success-message {
            font-size: 1rem;
        }
        
        .success-icon {
            font-size: 3rem;
        }
        
        .page-link {
            padding: 4px 6px;
            font-size: 11px;
            min-width: 28px;
        }
    }

    /* Extra Small Devices (max-width: 320px) */
    @media (max-width: 320px) {
        .container {
            padding: 0 8px;
        }
        
        h1 {
            font-size: 1.1rem;
        }
        
        .stat-box {
            padding: 8px;
        }
        
        .stat-box h3 {
            font-size: 1.3rem;
        }
        
        .filters {
            padding: 6px;
        }
        
        .search-box, .status-filter, .request-type-filter, .date-filter, .export-btn {
            padding: 6px 8px;
            font-size: 12px;
        }
        
        .modal-content {
            padding: 10px;
        }
        
        .modal-title {
            font-size: 1rem;
        }
        
        .patient-avatar-large {
            width: 45px;
            height: 45px;
        }
    }

    /* Touch device improvements */
    @media (hover: none) and (pointer: coarse) {
        .stat-box:hover {
            transform: none;
        }
        
        .btn:hover {
            transform: none;
        }
        
        .close-btn:hover {
            transform: none;
            background-color: transparent;
        }
        
        /* Increase touch targets */
        .btn, .page-link, .close-btn {
            min-height: 44px;
        }
        
        .btn-sm {
            min-height: 36px;
        }
        
        /* Remove hover effects on table rows */
        tr:hover {
            background-color: inherit;
        }
    }

    /* High contrast mode support */
    @media (prefers-contrast: high) {
        .stat-box {
            border: 2px solid var(--accent-color);
        }
        
        .btn {
            border: 2px solid transparent;
        }
        
        .btn-outline {
            border: 2px solid var(--accent-color);
        }
    }

    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        .stat-box, .btn, .close-btn, .modal-content {
            transition: none;
            animation: none;
        }
        
        .spinner {
            animation-duration: 2s;
        }
    }
</style>
</head>
<body>
    <main class="container">
        <header>
            <div class="header-container">
                <div class="header-content">
                    <h1>Patient Requests</h1>
                    <div class="refresh-controls">
                        <label>
                            <input type="hidden" id="autoRefreshToggle">
                        </label>
                    </div>
                </div>
            </div>
        </header>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-box total">
                <div class="details">
                    <div class="heading">
                        <i class="bi bi-clipboard-data"></i>
                        <h4>Total Requests</h4>
                    </div>
                    <h3 id="totalCount"><?= $counts['total'] ?></h3>
                    <p>All requests received</p>
                </div>
            </div>

            <div class="stat-box pending">
                <div class="details">
                    <div class="heading">
                        <i class="bi bi-hourglass-split"></i>
                        <h4>Pending</h4>
                    </div>
                    <h3 id="pendingCount"><?= $counts['pending'] ?></h3>
                    <p>Awaiting hospital review</p>
                </div>
            </div>

            <div class="stat-box approved">
                <div class="details">
                    <div class="heading">
                        <i class="bi bi-check-circle-fill"></i>
                        <h4>Approved</h4>
                    </div>
                    <h3 id="approvedCount"><?= $counts['approved'] ?></h3>
                    <p>Requests approved</p>
                </div>
            </div>

            <div class="stat-box rejected">
                <div class="details">
                    <div class="heading">
                        <i class="bi bi-x-circle-fill"></i>
                        <h4>Rejected</h4>
                    </div>
                    <h3 id="rejectedCount"><?= $counts['rejected'] ?></h3>
                    <p>Requests declined</p>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="filters">
            <input type="text" class="search-box" id="searchInput" placeholder="Search by patient name, email or phone..." value="<?= safe_html($_GET['search'] ?? '') ?>">
            
            <select class="status-filter" id="statusFilter">
                <option value="all" <?= ($_GET['status'] ?? 'all') === 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= ($_GET['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
            
            <select class="request-type-filter" id="requestTypeFilter">
                <option value="all" <?= ($_GET['request_type'] ?? 'all') === 'all' ? 'selected' : '' ?>>All Types</option>
                <option value="covid_test" <?= ($_GET['request_type'] ?? '') === 'covid_test' ? 'selected' : '' ?>>COVID Test</option>
                <option value="vaccination" <?= ($_GET['request_type'] ?? '') === 'vaccination' ? 'selected' : '' ?>>Vaccination</option>
            </select>
            
            <input type="date" class="date-filter" id="dateFrom" placeholder="From Date" value="<?= safe_html($_GET['date_from'] ?? '') ?>">
            <input type="date" class="date-filter" id="dateTo" placeholder="To Date" value="<?= safe_html($_GET['date_to'] ?? '') ?>">
            
            <button class="btn" id="applyFilters">Apply Filters</button>
            <button class="btn btn-outline" id="resetFilters">Reset</button>
        </div>

        <!-- Requests Table -->
        <div class="requests-table">
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3>No requests found</h3>
                    <p>There are no patient requests matching your current filters.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Request Type</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Admin Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="requestsTableBody">
                        <?php foreach ($requests as $request): ?>
                            <tr data-request-id="<?= $request['id'] ?>">
                                <td>
                                    <div class="patient-info">
                                        <img src="<?= safe_html($request['image_path'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjNjY2IiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTIwIDIxdi0yYTQgNCAwIDAwLTQgNEg4YTQgNCAwIDAwLTQgNHYyIj48L3BhdGg+PGNpcmNsZSBjeD0iMTIiIGN5PSI3IiByPSI0Ij48L2NpcmNsZT48L3N2Zz4=') ?>" 
                                             alt="<?= safe_html($request['patient_name']) ?>" class="patient-avatar">
                                        <div class="patient-details">
                                            <h4><?= safe_html($request['patient_name']) ?></h4>
                                            <div class="patient-contact">
                                                <?= safe_html($request['email']) ?><br>
                                                <?= safe_html($request['phone'] ?? '') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= safe_html(ucfirst(str_replace('_', ' ', $request['request_type']))) ?></td>
                                <td><?= date('M j, Y g:i A', strtotime($request['request_date'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $request['status'] ?>">
                                        <?= ucfirst($request['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="notes-preview" title="<?= safe_html($request['admin_notes'] ?? '') ?>">
                                        <?= safe_html(substr($request['admin_notes'] ?? '', 0, 50)) ?>
                                        <?= strlen($request['admin_notes'] ?? '') > 50 ? '...' : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions">
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button class="btn btn-approve btn-sm" onclick="updateRequestStatus(<?= $request['id'] ?>, 'approved')">Approve</button>
                                            <button class="btn btn-reject btn-sm" onclick="rejectRequest(<?= $request['id'] ?>)">Reject</button>
                                        <?php endif; ?>
                                        <button class="btn btn-outline btn-sm" onclick="viewRequestDetails(<?= $request['id'] ?>)">View</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($requests_data['total_pages'] > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $requests_data['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>&status=<?= $_GET['status'] ?? 'all' ?>&search=<?= urlencode($_GET['search'] ?? '') ?>&request_type=<?= $_GET['request_type'] ?? 'all' ?>&date_from=<?= $_GET['date_from'] ?? '' ?>&date_to=<?= $_GET['date_to'] ?? '' ?>" 
                       class="page-link <?= $i == $requests_data['page'] ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Request Details Modal -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Request Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="text-center">
                    <div class="spinner"></div>
                    <p>Loading request details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Confirmation Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Request</h3>
                <button class="close-btn" onclick="closeRejectModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reject this request? You can add a reason below:</p>
                <div class="form-group">
                    <label for="rejectionReason">Rejection Reason (Optional):</label>
                    <textarea class="form-control" id="rejectionReason" placeholder="Enter reason for rejection..."></textarea>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-outline" onclick="closeRejectModal()">Cancel</button>
                    <button class="btn btn-reject" id="confirmReject">Confirm Rejection</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message Modal -->
    <div class="modal success-modal" id="successModal">
        <div class="modal-content">
            <div class="modal-body">
                <div class="success-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="success-message" id="successMessage">Operation completed successfully!</div>
                <div class="modal-actions">
                    <button class="btn" onclick="closeSuccessModal()">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentRequestId = null;
        let autoRefreshInterval = null;
        const csrfToken = '<?= $_SESSION['csrf_token'] ?>';

        // Auto-refresh toggle
        document.getElementById('autoRefreshToggle').addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });

        function startAutoRefresh() {
            autoRefreshInterval = setInterval(refreshData, 15000);
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        }

        // Filter and search functionality
        document.getElementById('applyFilters').addEventListener('click', function() {
            applyFilters();
        });

        document.getElementById('resetFilters').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = 'all';
            document.getElementById('requestTypeFilter').value = 'all';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            applyFilters();
        });

        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const requestType = document.getElementById('requestTypeFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            const params = new URLSearchParams();
            
            if (search) params.set('search', search);
            if (status !== 'all') params.set('status', status);
            if (requestType !== 'all') params.set('request_type', requestType);
            if (dateFrom) params.set('date_from', dateFrom);
            if (dateTo) params.set('date_to', dateTo);
            
            window.location.href = '?' + params.toString();
        }

        // Enter key in search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        // Update request status
        function updateRequestStatus(requestId, newStatus) {
            const button = event.target;
            const originalText = button.textContent;
            button.innerHTML = '<span class="spinner"></span>';
            button.disabled = true;

            fetch('hospital_requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'update_status',
                    request_id: requestId,
                    status: newStatus,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateRequestRow(requestId, newStatus);
                    if (newStatus === 'approved') {
                        showSuccessModal('Request approved successfully!');
                    }
                } else {
                    alert('Error updating request: ' + (data.error || 'Unknown error'));
                    button.textContent = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                alert('Error updating request: ' + error);
                button.textContent = originalText;
                button.disabled = false;
            });
        }

        // Reject request with confirmation
        function rejectRequest(requestId) {
            currentRequestId = requestId;
            document.getElementById('rejectModal').classList.add('show');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('show');
            document.getElementById('rejectionReason').value = '';
            currentRequestId = null;
        }

        document.getElementById('confirmReject').addEventListener('click', function() {
            const reason = document.getElementById('rejectionReason').value;
            const button = this;
            const originalText = button.textContent;
            
            button.innerHTML = '<span class="spinner"></span>';
            button.disabled = true;

            // First update notes if reason provided
            const promises = [];
            
            if (reason.trim()) {
                promises.push(
                    fetch('hospital_requests.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'update_notes',
                            request_id: currentRequestId,
                            notes: 'Rejection reason: ' + reason,
                            csrf_token: csrfToken
                        })
                    }).then(response => response.json())
                );
            }

            // Then update status
            promises.push(
                fetch('hospital_requests.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'update_status',
                        request_id: currentRequestId,
                        status: 'rejected',
                        csrf_token: csrfToken
                    })
                }).then(response => response.json())
            );

            Promise.all(promises)
                .then(results => {
                    const statusResult = results[results.length - 1];
                    if (statusResult.success) {
                        updateRequestRow(currentRequestId, 'rejected');
                        closeRejectModal();
                        showSuccessModal('Request rejected successfully!');
                    } else {
                        alert('Error rejecting request: ' + (statusResult.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    alert('Error rejecting request: ' + error);
                })
                .finally(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                });
        });

        // Update request row in DOM after status change
        function updateRequestRow(requestId, newStatus) {
            const row = document.querySelector(`tr[data-request-id="${requestId}"]`);
            if (!row) return;

            const statusCell = row.cells[3];
            const actionsCell = row.cells[5];
            
            // Update status badge
            statusCell.innerHTML = `<span class="status-badge status-${newStatus}">${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}</span>`;
            
            // Update actions (remove Approve/Reject buttons for non-pending status)
            if (newStatus !== 'pending') {
                actionsCell.querySelector('.actions').innerHTML = '<button class="btn btn-outline btn-sm" onclick="viewRequestDetails(' + requestId + ')">View</button>';
            }

            // Refresh counts
            refreshCounts();
        }

        // View request details
        function viewRequestDetails(requestId) {
            currentRequestId = requestId;
            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner"></div>
                    <p>Loading request details...</p>
                </div>
            `;

            document.getElementById('detailsModal').classList.add('show');

            // CSRF token check
            if (!csrfToken) {
                modalBody.innerHTML = `
                    <div class="text-center text-danger">
                        <p>Security token missing. Please refresh the page.</p>
                        <button class="btn btn-outline" onclick="closeModal()">Close</button>
                    </div>
                `;
                return;
            }

            fetch('hospital_requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_request_details',
                    request_id: requestId,
                    csrf_token: csrfToken
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                // Check if response is HTML error page
                if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                    throw new Error('Server returned HTML page instead of JSON. Possible PHP error.');
                }
                
                try {
                    const data = JSON.parse(text);
                    return data;
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response text:', text);
                    throw new Error('Invalid server response format');
                }
            })
            .then(data => {
                if (data.success) {
                    const request = data.data;
                    
                    // Format dates properly
                    const requestDate = new Date(request.request_date).toLocaleString();
                    const dob = request.dob ? new Date(request.dob).toLocaleDateString() : 'N/A';
                    
                    // Display the request details with better formatting
                    modalBody.innerHTML = `
                        <div class="patient-header">
                            <img src="${escapeHtml(request.image_path || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjNjY2IiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTIwIDIxdi0yYTQgNCAwIDAwLTQgNEg4YTQgNCAwIDAwLTQgNHYyIj48L3BhdGg+PGNpcmNsZSBjeD0iMTIiIGN5PSI3IiByPSI0Ij48L2NpcmNsZT48L3N2Zz4=')}" 
                                 alt="${escapeHtml(request.patient_name)}" class="patient-avatar-large">
                            <div class="patient-header-info">
                                <h3>${escapeHtml(request.patient_name)}</h3>
                                <p>${escapeHtml(request.email)}</p>
                                <p>${escapeHtml(request.phone || 'N/A')}</p>
                            </div>
                        </div>

                        <div class="section-header">Personal Information</div>
                        <div class="details-grid">
                            <div class="detail-item">
                                <strong>Age:</strong>
                                <span>${request.age || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Gender:</strong>
                                <span>${request.gender ? escapeHtml(request.gender) : 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Date of Birth:</strong>
                                <span>${dob}</span>
                            </div>
                        </div>

                        ${request.address ? `
                        <div class="detail-item">
                            <strong>Address:</strong>
                            <span>${escapeHtml(request.address)}</span>
                        </div>
                        ` : ''}

                        <div class="section-header">Request Information</div>
                        <div class="details-grid">
                            <div class="detail-item">
                                <strong>Request Type:</strong>
                                <span>${escapeHtml(request.request_type.replace('_', ' '))}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Request Date:</strong>
                                <span>${requestDate}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Status:</strong>
                                <span class="status-badge status-${request.status}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>
                            </div>
                        </div>

                        <div class="section-header">Medical Information</div>
                        <div class="details-grid">
                            ${request.vaccination_status ? `
                            <div class="detail-item">
                                <strong>Vaccination Status:</strong>
                                <span>${escapeHtml(request.vaccination_status.replace('_', ' '))}</span>
                            </div>
                            ` : ''}
                            
                            ${request.covid_result ? `
                            <div class="detail-item">
                                <strong>Last COVID Result:</strong>
                                <span>${escapeHtml(request.covid_result)}</span>
                            </div>
                            ` : ''}
                        </div>

                        <div class="section-header">Admin Notes</div>
                        <div class="form-group">
                            <textarea class="form-control" id="adminNotes" placeholder="Add admin notes here...">${escapeHtml(request.admin_notes || '')}</textarea>
                        </div>
                        
                        <div class="modal-actions">
                            <button class="btn btn-outline" onclick="closeModal()">Close</button>
                            <button class="btn" onclick="saveNotes(${requestId})">Save Notes</button>
                        </div>
                    `;
                } else {
                    throw new Error(data.error || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                modalBody.innerHTML = `
                    <div class="text-center text-danger">
                        <p>Error loading request details: ${error.message}</p>
                        <p><small>Check browser console for details</small></p>
                        <button class="btn btn-outline" onclick="closeModal()">Close</button>
                    </div>
                `;
            });
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.remove('show');
            currentRequestId = null;
        }

        // Save admin notes
        function saveNotes(requestId) {
            const notes = document.getElementById('adminNotes').value;
            const button = event.target;
            const originalText = button.textContent;
            
            button.innerHTML = '<span class="spinner"></span> Saving...';
            button.disabled = true;

            fetch('hospital_requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'update_notes',
                    request_id: requestId,
                    notes: notes,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the notes preview in the table
                    const row = document.querySelector(`tr[data-request-id="${requestId}"]`);
                    if (row) {
                        const notesPreview = row.querySelector('.notes-preview');
                        notesPreview.textContent = notes.length > 50 ? notes.substring(0, 50) + '...' : notes;
                        notesPreview.title = notes;
                    }
                    showSuccessModal('Notes saved successfully!');
                    closeModal();
                } else {
                    alert('Error saving notes: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error saving notes: ' + error);
            })
            .finally(() => {
                button.textContent = originalText;
                button.disabled = false;
            });
        }

        // Success modal functions
        function showSuccessModal(message) {
            document.getElementById('successMessage').textContent = message;
            document.getElementById('successModal').classList.add('show');
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.remove('show');
        }

        // Refresh data (for auto-refresh)
        function refreshData() {
            fetch('hospital_requests.php?action=refresh_data&' + new URLSearchParams({
                status: document.getElementById('statusFilter').value,
                search: document.getElementById('searchInput').value,
                request_type: document.getElementById('requestTypeFilter').value,
                date_from: document.getElementById('dateFrom').value,
                date_to: document.getElementById('dateTo').value,
                page: <?= $requests_data['page'] ?>
            }))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCounts(data.counts);
                }
            })
            .catch(error => console.error('Error refreshing data:', error));
        }

        function refreshCounts() {
            location.reload();
        }

        function updateCounts(counts) {
            document.getElementById('totalCount').textContent = counts.total;
            document.getElementById('pendingCount').textContent = counts.pending;
            document.getElementById('approvedCount').textContent = counts.approved;
            document.getElementById('rejectedCount').textContent = counts.rejected;
        }

        // Utility function to escape HTML
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const detailsModal = document.getElementById('detailsModal');
            const rejectModal = document.getElementById('rejectModal');
            const successModal = document.getElementById('successModal');
            
            if (event.target === detailsModal) {
                closeModal();
            }
            if (event.target === rejectModal) {
                closeRejectModal();
            }
            if (event.target === successModal) {
                closeSuccessModal();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeRejectModal();
                closeSuccessModal();
            }
        });
    </script>
</body>
</html>

<?php include 'includes/hospital_footer.php'; ?>