<?php


include 'db_config.php';

$result = $conn->query("SELECT * FROM demandes_inscription ORDER BY date_demande DESC");

// Statistiques
$total_demandes = $conn->query("SELECT COUNT(*) as total FROM demandes_inscription")->fetch_assoc()['total'];
$demandes_en_attente = $conn->query("SELECT COUNT(*) as total FROM demandes_inscription WHERE statut='قيد الانتظار'")->fetch_assoc()['total'];
$demandes_acceptees = $conn->query("SELECT COUNT(*) as total FROM demandes_inscription WHERE statut='Accepté'")->fetch_assoc()['total'];
$demandes_refusees = $conn->query("SELECT COUNT(*) as total FROM demandes_inscription WHERE statut='Refusé'")->fetch_assoc()['total'];

// Fonction pour traduire les statuts en arabe
function getStatusInArabic($status) {
    switch($status) {
        case 'Accepté':
            return 'مقبول';
        case 'Refusé':
            return 'مرفوض';
        default:
            return 'قيد الانتظار';
    }
}

// Fonction pour obtenir la classe CSS du statut
function getStatusClass($status) {
    switch($status) {
        case 'Accepté':
            return 'status-accepted';
        case 'Refusé':
            return 'status-rejected';
        default:
            return 'status-pending';
    }
}

// Pagination
$items_per_page = 10;
$total_pages = ceil($total_demandes / $items_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Requête avec pagination
$result = $conn->query("SELECT * FROM demandes_inscription ORDER BY date_demande DESC LIMIT $offset, $items_per_page");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة التسجيلات | لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --secondary-color: #10b981;
            --secondary-dark: #059669;
            --danger-color: #ef4444;
            --danger-dark: #dc2626;
            --warning-color: #f59e0b;
            --warning-dark: #d97706;
            --info-color: #6366f1;
            --info-dark: #4f46e5;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
            --gray-color: #9ca3af;
            --white-color: #ffffff;
            --sidebar-width: 260px;
            --header-height: 70px;
            --border-radius: 12px;
            --transition-speed: 0.3s;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }
        
        body {
            background-color: #f9fafb;
            color: var(--dark-color);
            line-height: 1.6;
            direction: rtl;
            overflow-x: hidden;
        }
        
        /* Scrollbar personnalisé */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--gray-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }
        
        /* Layout */
        .layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--white-color);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            z-index: 100;
            transition: all var(--transition-speed) ease;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .logo-icon {
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--dark-color);
            cursor: pointer;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-title {
            padding: 0 20px;
            margin-bottom: 10px;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--gray-color);
            font-weight: 600;
        }
        
        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark-color);
            text-decoration: none;
            transition: all var(--transition-speed) ease;
            border-right: 3px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: rgba(59, 130, 246, 0.05);
            color: var(--primary-color);
            border-right-color: var(--primary-color);
        }
        
        .menu-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-right: var(--sidebar-width);
            transition: all var(--transition-speed) ease;
        }
        
        /* Header */
        .header {
            background-color: var(--white-color);
            height: var(--header-height);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 99;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--dark-color);
            cursor: pointer;
        }
        
        .search-bar {
            position: relative;
        }
        
        .search-input {
            padding: 10px 15px 10px 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            width: 300px;
            transition: all var(--transition-speed) ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            width: 350px;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-icon {
            position: relative;
            font-size: 20px;
            color: var(--dark-color);
            cursor: pointer;
            transition: all var(--transition-speed) ease;
        }
        
        .header-icon:hover {
            color: var(--primary-color);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            left: -5px;
            background-color: var(--danger-color);
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .user-info {
            display: none;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-role {
            font-size: 12px;
            color: var(--gray-color);
        }
        
        /* Container */
        .container {
            padding: 30px;
        }
        
        /* Page Title */
        .page-title {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: var(--white-color);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            transition: all var(--transition-speed) ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-title {
            font-size: 14px;
            color: var(--gray-color);
            font-weight: 500;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        
        .stat-icon.blue {
            background-color: var(--primary-color);
        }
        
        .stat-icon.green {
            background-color: var(--secondary-color);
        }
        
        .stat-icon.red {
            background-color: var(--danger-color);
        }
        
        .stat-icon.orange {
            background-color: var(--warning-color);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-description {
            font-size: 12px;
            color: var(--gray-color);
        }
        
        .stat-progress {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .progress-bar {
            flex: 1;
            height: 6px;
            background-color: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-value {
            height: 100%;
            border-radius: 10px;
        }
        
        .progress-value.blue {
            background-color: var(--primary-color);
        }
        
        .progress-value.green {
            background-color: var(--secondary-color);
        }
        
        .progress-value.red {
            background-color: var(--danger-color);
        }
        
        .progress-value.orange {
            background-color: var(--warning-color);
        }
        
        .progress-percentage {
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 10px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background-color: white;
            min-width: 150px;
            transition: all var(--transition-speed) ease;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            display: flex;
            align-items: center;
            gap: 5px;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-dark);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #e5e7eb;
            color: var(--dark-color);
        }
        
        .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        /* Card */
        .card {
            background-color: var(--white-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .card-tools {
            display: flex;
            gap: 10px;
        }
        
        .card-body {
            padding: 0;
        }
        
        /* Table */
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px 20px;
            text-align: right;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: var(--dark-color);
            white-space: nowrap;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background-color: #f9fafb;
        }
        
        .table-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .student-name {
            font-weight: 600;
        }
        
        .student-email {
            font-size: 12px;
            color: var(--gray-color);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        
        .status-accepted {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--secondary-color);
            border: 1px solid var(--secondary-color);
        }
        
        .status-rejected {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        .status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border: 1px solid var(--warning-color);
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            border-radius: 8px;
            font-size: 14px;
            transition: all var(--transition-speed) ease;
            border: none;
            cursor: pointer;
            color: var(--dark-color);
            background-color: #f3f4f6;
            margin-right: 5px;
        }
        
        .action-btn:hover {
            background-color: #e5e7eb;
        }
        
        .btn-view {
            color: var(--info-color);
        }
        
        .btn-view:hover {
            background-color: rgba(99, 102, 241, 0.1);
        }
        
        .btn-accept {
            color: var(--secondary-color);
        }
        
        .btn-accept:hover {
            background-color: rgba(16, 185, 129, 0.1);
        }
        
        .btn-refuse {
            color: var(--danger-color);
        }
        
        .btn-refuse:hover {
            background-color: rgba(239, 68, 68, 0.1);
        }
        
        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .pagination-info {
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .pagination {
            display: flex;
            gap: 5px;
        }
        
        .pagination-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            background-color: white;
            border: 1px solid #e5e7eb;
            color: var(--dark-color);
            cursor: pointer;
            transition: all var(--transition-speed) ease;
        }
        
        .pagination-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .pagination-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Empty State */
        .empty-state {
            padding: 50px 20px;
            text-align: center;
        }
        
        .empty-icon {
            font-size: 48px;
            color: #e5e7eb;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .empty-description {
            font-size: 14px;
            color: var(--gray-color);
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--gray-color);
            cursor: pointer;
            transition: all var(--transition-speed) ease;
        }
        
        .close-modal:hover {
            color: var(--danger-color);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Student Details */
        .student-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-size: 12px;
            color: var(--gray-color);
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 500;
        }
        
        .detail-message {
            grid-column: span 2;
        }
        
        .message-box {
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 5px;
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: white;
            border-radius: var(--border-radius);
            padding: 15px 20px;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 1001;
            transform: translateX(-120%);
            transition: transform 0.5s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        
        .notification-icon.success {
            background-color: var(--secondary-color);
        }
        
        .notification-icon.error {
            background-color: var(--danger-color);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .notification-message {
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .close-notification {
            background: none;
            border: none;
            font-size: 16px;
            color: var(--gray-color);
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .user-info {
                display: none;
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-right: 0;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .search-input {
                width: 200px;
            }
            
            .search-input:focus {
                width: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 20px;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
            }
            
            .student-details {
                grid-template-columns: 1fr;
            }
            
            .detail-message {
                grid-column: span 1;
            }
            
            .header {
                padding: 0 20px;
            }
            
            .search-bar {
                display: none;
            }
        }
        
        @media (min-width: 992px) {
            .user-info {
                display: block;
            }
        }
    </style>
</hea  {
            .user-info {
                display: block;
            }
        }
    </style>
</head>
<body>

<div class="layout">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-school logo-icon"></i>
                <span>مدرستنا</span>
            </div>
            <button class="sidebar-toggle" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sidebar-menu">
            <div class="menu-title">القائمة الرئيسية</div>
            <a href="#" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>لوحة التحكم</span>
            </a>
            <a href="#" class="menu-item active">
                <i class="fas fa-user-graduate"></i>
                <span>طلبات التسجيل</span>
            </a>
            <a href="afficher_eleves.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>الطلاب</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>المعلمون</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-book"></i>
                <span>المواد الدراسية</span>
            </a>
            
            <div class="menu-title">الإعدادات</div>
            <a href="#" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>إعدادات النظام</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-user-cog"></i>
                <span>الملف الشخصي</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <button class="mobile-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="search-bar">
                    <input type="text" class="search-input" placeholder="البحث...">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>
            <div class="header-right">
                <div class="header-icon">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="header-icon">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-badge">5</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-info">
                        <div class="user-name">مدير النظام</div>
                        <div class="user-role">مسؤول</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container -->
        <div class="container">
            <!-- Page Title -->
            <div class="page-title">
                <h1 class="title">إدارة طلبات التسجيل</h1>
                <div class="breadcrumb">
                    <a href="#">الرئيسية</a>
                    <i class="fas fa-chevron-left"></i>
                    <span>طلبات التسجيل</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">إجمالي الطلبات</div>
                        <div class="stat-icon blue">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $total_demandes ?></div>
                    <div class="stat-description">عدد طلبات التسجيل</div>
                    <div class="stat-progress">
                        <div class="progress-bar">
                            <div class="progress-value blue" style="width: 100%"></div>
                        </div>
                        <div class="progress-percentage">100%</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">قيد الانتظار</div>
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $demandes_en_attente ?></div>
                    <div class="stat-description">طلبات تحتاج إلى مراجعة</div>
                    <div class="stat-progress">
                        <div class="progress-bar">
                            <div class="progress-value orange" style="width: <?= ($demandes_en_attente / max(1, $total_demandes)) * 100 ?>%"></div>
                        </div>
                        <div class="progress-percentage"><?= round(($demandes_en_attente / max(1, $total_demandes)) * 100) ?>%</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">مقبولة</div>
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $demandes_acceptees ?></div>
                    <div class="stat-description">طلبات تمت الموافقة عليها</div>
                    <div class="stat-progress">
                        <div class="progress-bar">
                            <div class="progress-value green" style="width: <?= ($demandes_acceptees / max(1, $total_demandes)) * 100 ?>%"></div>
                        </div>
                        <div class="progress-percentage"><?= round(($demandes_acceptees / max(1, $total_demandes)) * 100) ?>%</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">مرفوضة</div>
                        <div class="stat-icon red">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $demandes_refusees ?></div>
                    <div class="stat-description">طلبات تم رفضها</div>
                    <div class="stat-progress">
                        <div class="progress-bar">
                            <div class="progress-value red" style="width: <?= ($demandes_refusees / max(1, $total_demandes)) * 100 ?>%"></div>
                        </div>
                        <div class="progress-percentage"><?= round(($demandes_refusees / max(1, $total_demandes)) * 100) ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <div class="filter-group">
                    <select class="filter-select" id="statusFilter">
                        <option value="all">جميع الحالات</option>
                        <option value="pending">قيد الانتظار</option>
                        <option value="accepted">مقبول</option>
                        <option value="rejected">مرفوض</option>
                    </select>
                    <select class="filter-select" id="classFilter">
                    <option value="all">جميع الأقسام</option>
                        <option value="CP">CP</option>
                        <option value="CE1">CE1</option>
                        <option value="CE2">CE2</option>
                        <option value="CM1">CM1</option>
                        <option value="CM2">CM2</option>
                    </select>
                    <input type="text" id="searchInput" class="filter-select" placeholder="البحث عن طالب...">
                </div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i>
                        تحديث
                    </button>
                    <button class="btn btn-primary" id="exportBtn">
                        <i class="fas fa-file-export"></i>
                        تصدير البيانات
                    </button>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">قائمة طلبات التسجيل</h2>
                    <div class="card-tools">
                        <button class="btn btn-outline" id="printBtn">
                            <i class="fas fa-print"></i>
                            طباعة
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="applicationsTable">
                            <thead>
                                <tr>
                                    <th>الطالب</th>
                                    <th>العمر</th>
                                    <th>الصف المطلوب</th>
                                    <th>معلومات الاتصال</th>
                                    <th>تاريخ الطلب</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) { 
                                        $statusClass = getStatusClass($row['statut']);
                                        $statusArabic = getStatusInArabic($row['statut']);
                                        $initial = mb_substr($row['prenom'], 0, 1, 'UTF-8');
                                ?>
                                <tr>
                                    <td>
                                        <div class="student-info">
                                            <div class="table-avatar"><?= $initial ?></div>
                                            <div>
                                                <div class="student-name"><?= $row['prenom'] . ' ' . $row['nom'] ?></div>
                                                <div class="student-email"><?= $row['email'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $row['age'] ?> سنة</td>
                                    <td><?= $row['classe_demande'] ?></td>
                                    <td>
                                        <div><i class="fas fa-envelope"></i> <?= $row['email'] ?></div>
                                        <div><i class="fas fa-phone"></i> <?= $row['telephone'] ?></div>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($row['date_demande'])) ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= $statusArabic ?></span></td>
                                    <td>
                                        <button class="action-btn btn-view" onclick="viewDetails(<?= $row['id'] ?>, '<?= $row['nom'] ?>', '<?= $row['prenom'] ?>', '<?= $row['age'] ?>', '<?= $row['classe_demande'] ?>', '<?= $row['email'] ?>', '<?= $row['telephone'] ?>', '<?= $row['message'] ?>', '<?= $row['statut'] ?>', '<?= $row['date_demande'] ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($row['statut'] != 'Accepté' && $row['statut'] != 'Refusé') { ?>
                                        <button class="action-btn btn-accept" onclick="confirmAction(<?= $row['id'] ?>, 'Accepté')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="action-btn btn-refuse" onclick="confirmAction(<?= $row['id'] ?>, 'Refusé')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else { 
                                ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="fas fa-inbox"></i>
                                            </div>
                                            <h3 class="empty-title">لا توجد طلبات تسجيل</h3>
                                            <p class="empty-description">ستظهر طلبات التسجيل الجديدة هنا</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($total_demandes > $items_per_page) { ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        عرض <?= min($items_per_page, $result->num_rows) ?> من <?= $total_demandes ?> طلب
                    </div>
                    <div class="pagination">
                        <a href="?page=<?= max(1, $current_page - 1) ?>" class="pagination-btn <?= $current_page == 1 ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php for ($i = 1; $i <= min(5, $total_pages); $i++) { ?>
                        <a href="?page=<?= $i ?>" class="pagination-btn <?= $current_page == $i ? 'active' : '' ?>"><?= $i ?></a>
                        <?php } ?>
                        <?php if ($total_pages > 5) { ?>
                        <span class="pagination-btn">...</span>
                        <a href="?page=<?= $total_pages ?>" class="pagination-btn <?= $current_page == $total_pages ? 'active' : '' ?>"><?= $total_pages ?></a>
                        <?php } ?>
                        <a href="?page=<?= min($total_pages, $current_page + 1) ?>" class="pagination-btn <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">تأكيد الإجراء</h2>
            <button class="close-modal" onclick="closeModal('confirmModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage">هل أنت متأكد من أنك تريد تغيير حالة هذا الطلب؟</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('confirmModal')">إلغاء</button>
            <button id="confirmBtn" class="btn btn-primary">تأكيد</button>
        </div>
    </div>
</div>

<!-- Modal de détails -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">تفاصيل طلب التسجيل</h2>
            <button class="close-modal" onclick="closeModal('detailsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="student-details">
                <div class="detail-item">
                    <div class="detail-label">الاسم الكامل</div>
                    <div class="detail-value" id="detailName"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">العمر</div>
                    <div class="detail-value" id="detailAge"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">الصف المطلوب</div>
                    <div class="detail-value" id="detailClass"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">البريد الإلكتروني</div>
                    <div class="detail-value" id="detailEmail"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">رقم الهاتف</div>
                    <div class="detail-value" id="detailPhone"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">تاريخ الطلب</div>
                    <div class="detail-value" id="detailDate"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">الحالة</div>
                    <div class="detail-value" id="detailStatus"></div>
                </div>
                <div class="detail-message">
                    <div class="detail-label">الرسالة</div>
                    <div class="message-box" id="detailMessage"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('detailsModal')">إغلاق</button>
            <div id="detailActions"></div>
        </div>
    </div>
</div>

<!-- Notification -->
<div id="notification" class="notification">
    <div id="notificationIcon" class="notification-icon success">
        <i class="fas fa-check"></i>
    </div>
    <div class="notification-content">
        <div id="notificationTitle" class="notification-title">تم بنجاح</div>
        <div id="notificationMessage" class="notification-message">تم تنفيذ العملية بنجاح</div>
    </div>
    <button class="close-notification" onclick="closeNotification()">
        <i class="fas fa-times"></i>
    </button>
</div>

<script>
    // Sidebar Toggle
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });
    
    document.getElementById('sidebarClose').addEventListener('click', function() {
        document.getElementById('sidebar').classList.remove('show');
    });
    
    // Fonction de recherche
    document.getElementById('searchInput').addEventListener('keyup', function() {
        filterTable();
    });
    
    // Filtrage par statut
    document.getElementById('statusFilter').addEventListener('change', function() {
        filterTable();
    });
    
    // Filtrage par classe
    document.getElementById('classFilter').addEventListener('change', function() {
        filterTable();
    });
    
    // Fonction de filtrage combinée
    function filterTable() {
        const searchValue = document.getElementById('searchInput').value.toLowerCase();
        const statusValue = document.getElementById('statusFilter').value;
        const classValue = document.getElementById('classFilter').value;
        
        const table = document.getElementById('applicationsTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            let showRow = true;
            const cells = rows[i].getElementsByTagName('td');
            
            if (cells.length === 0) continue;
            
            // Filtre de recherche
            if (searchValue !== '') {
                let found = false;
                for (let j = 0; j < cells.length; j++) {
                    const cellText = cells[j].textContent.toLowerCase();
                    if (cellText.indexOf(searchValue) > -1) {
                        found = true;
                        break;
                    }
                }
                if (!found) showRow = false;
            }
            
            // Filtre de statut
            if (statusValue !== 'all') {
                const statusCell = cells[5];
                const statusText = statusCell.textContent.toLowerCase();
                
                if (
                    (statusValue === 'pending' && !statusText.includes('قيد الانتظار')) ||
                    (statusValue === 'accepted' && !statusText.includes('مقبول')) ||
                    (statusValue === 'rejected' && !statusText.includes('مرفوض'))
                ) {
                    showRow = false;
                }
            }
            
            // Filtre de classe
            if (classValue !== 'all') {
                const classCell = cells[2];
                const classText = classCell.textContent;
                if (!classText.includes(classValue)) {
                    showRow = false;
                }
            }
            
            rows[i].style.display = showRow ? '' : 'none';
        }
    }
    
    // Fonctions pour les modales
    let currentId = null;
    let currentStatus = null;
    
    function confirmAction(id, status) {
        currentId = id;
        currentStatus = status;
        
        const message = status === 'Accepté' 
            ? 'هل أنت متأكد من أنك تريد قبول هذا الطلب؟ سيتم إرسال بريد إلكتروني للمتقدم.'
            : 'هل أنت متأكد من أنك تريد رفض هذا الطلب؟ سيتم إرسال بريد إلكتروني للمتقدم.';
            
        document.getElementById('confirmMessage').textContent = message;
        document.getElementById('confirmBtn').className = status === 'Accepté' 
            ? 'btn btn-primary' 
            : 'btn btn-danger';
            
        document.getElementById('confirmModal').style.display = 'flex';
        
        document.getElementById('confirmBtn').onclick = function() {
            window.location.href = 'modifier_statut.php?id=' + currentId + '&statut=' + currentStatus;
        };
    }
    
    function viewDetails(id, nom, prenom, age, classe, email, telephone, message, statut, date) {
        document.getElementById('detailName').textContent = prenom + ' ' + nom;
        document.getElementById('detailAge').textContent = age + ' سنة';
        document.getElementById('detailClass').textContent = classe;
        document.getElementById('detailEmail').textContent = email;
        document.getElementById('detailPhone').textContent = telephone;
        document.getElementById('detailDate').textContent = formatDate(date);
        
        const statusArabic = getStatusArabic(statut);
        const statusClass = getStatusClass(statut);
        document.getElementById('detailStatus').innerHTML = `<span class="status-badge ${statusClass}">${statusArabic}</span>`;
        
        document.getElementById('detailMessage').textContent = message || 'لا توجد رسالة';
        
        let actionsHtml = '';
        if (statut !== 'Accepté' && statut !== 'Refusé') {
            actionsHtml = `
                <button class="btn btn-secondary" onclick="confirmAction(${id}, 'Accepté')">
                    <i class="fas fa-check"></i> قبول
                </button>
                <button class="btn btn-danger" onclick="confirmAction(${id}, 'Refusé')">
                    <i class="fas fa-times"></i> رفض
                </button>
            `;
        }
        document.getElementById('detailActions').innerHTML = actionsHtml;
        
        document.getElementById('detailsModal').style.display = 'flex';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ar-SA');
    }
    
    function getStatusArabic(status) {
        switch(status) {
            case 'Accepté': return 'مقبول';
            case 'Refusé': return 'مرفوض';
            default: return 'قيد الانتظار';
        }
    }
    
    function getStatusClass(status) {
        switch(status) {
            case 'Accepté': return 'status-accepted';
            case 'Refusé': return 'status-rejected';
            default: return 'status-pending';
        }
    }
    
    // Notification
    function showNotification(type, title, message) {
        const notification = document.getElementById('notification');
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationTitle = document.getElementById('notificationTitle');
        const notificationMessage = document.getElementById('notificationMessage');
        
        notificationIcon.className = 'notification-icon ' + type;
        notificationIcon.innerHTML = type === 'success' ? '<i class="fas fa-check"></i>' : '<i class="fas fa-exclamation"></i>';
        
        notificationTitle.textContent = title;
        notificationMessage.textContent = message;
        
        notification.classList.add('show');
        
        setTimeout(function() {
            closeNotification();
        }, 5000);
    }
    
    function closeNotification() {
        document.getElementById('notification').classList.remove('show');
    }
    
    // Bouton de rafraîchissement
    document.getElementById('refreshBtn').addEventListener('click', function() {
        window.location.reload();
    });
    
    // Bouton d'exportation
    document.getElementById('exportBtn').addEventListener('click', function() {
        showNotification('success', 'تم التصدير', 'تم تصدير البيانات بنجاح');
    });
    
    // Bouton d'impression
    document.getElementById('printBtn').addEventListener('click', function() {
        window.print();
    });
    
    // Fermer la modal si l'utilisateur clique en dehors
    window.onclick = function(event) {
        const confirmModal = document.getElementById('confirmModal');
        const detailsModal = document.getElementById('detailsModal');
        
        if (event.target === confirmModal) {
            closeModal('confirmModal');
        }
        
        if (event.target === detailsModal) {
            closeModal('detailsModal');
        }
    };
    
    // Vérifier si une notification doit être affichée (après redirection)
    <?php if (isset($_GET['success'])) { ?>
    showNotification('success', 'تم بنجاح', 'تم تحديث حالة الطلب وإرسال البريد الإلكتروني بنجاح');
    <?php } ?>
    
    <?php if (isset($_GET['error'])) { ?>
    showNotification('error', 'خطأ', 'حدث خطأ أثناء تحديث حالة الطلب');
    <?php } ?>
</script>

</body>
</html>