<?php 
include 'db_config.php';

// Récupérer toutes les candidatures - Modification de la requête pour trier par ID au lieu de date_candidature
$result = $conn->query("SELECT * FROM candidatures_professeurs ORDER BY id DESC");

// Vérifier si la requête a réussi
if ($result === false) {
    $error_message = "Erreur de requête SQL: " . $conn->error;
} else {
    $error_message = '';
}

// Message de confirmation
$message = '';
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Statistiques
$total_candidatures = $conn->query("SELECT COUNT(*) as total FROM candidatures_professeurs")->fetch_assoc()['total'];
$candidatures_en_attente = $conn->query("SELECT COUNT(*) as total FROM candidatures_professeurs WHERE statut='قيد الانتظار' OR statut=''")->fetch_assoc()['total'];
$candidatures_acceptees = $conn->query("SELECT COUNT(*) as total FROM candidatures_professeurs WHERE statut='مقبول' OR statut='Acceptée'")->fetch_assoc()['total'];
$candidatures_refusees = $conn->query("SELECT COUNT(*) as total FROM candidatures_professeurs WHERE statut='مرفوض' OR statut='Refusée'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة طلبات المدرسين | لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --primary-light: #dbeafe;
            --secondary-color: #10b981;
            --secondary-dark: #059669;
            --secondary-light: #d1fae5;
            --danger-color: #ef4444;
            --danger-dark: #dc2626;
            --danger-light: #fee2e2;
            --warning-color: #f59e0b;
            --warning-dark: #d97706;
            --warning-light: #fef3c7;
            --info-color: #6366f1;
            --info-dark: #4f46e5;
            --info-light: #e0e7ff;
            --success-color: #10b981;
            --success-dark: #059669;
            --success-light: #d1fae5;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
            --gray-color: #9ca3af;
            --white-color: #ffffff;
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
            width: 280px;
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
            background-color: rgba(99, 102, 241, 0.05);
            color: var(--info-color);
            border-right-color: var(--info-color);
        }
        
        .menu-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-right: 280px;
            transition: all var(--transition-speed) ease;
        }
        
        /* Header */
        .header {
            background-color: var(--white-color);
            height: 70px;
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
            border-color: var(--info-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
            color: var(--info-color);
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
            background-color: var(--info-color);
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .title i {
            color: var(--info-color);
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .breadcrumb a {
            color: var(--info-color);
            text-decoration: none;
        }
        
        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: fadeIn 0.5s ease;
        }
        
        .alert-success {
            background-color: var(--success-light);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: var(--danger-light);
            border-left: 4px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        .alert-icon {
            font-size: 20px;
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
        
        /* Card */
        .card {
            background-color: var(--white-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            transition: all var(--transition-speed) ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9fafb;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i {
            color: var(--info-color);
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
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background-color: rgba(99, 102, 241, 0.05);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        
        .status-pending {
            background-color: var(--warning-light);
            color: var(--warning-color);
            border: 1px solid var(--warning-color);
        }
        
        .status-accepted {
            background-color: var(--success-light);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .status-rejected {
            background-color: var(--danger-light);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all var(--transition-speed) ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            margin-right: 5px;
        }
        
        .btn-view {
            background-color: var(--info-light);
            color: var(--info-color);
        }
        
        .btn-view:hover {
            background-color: var(--info-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-accept {
            background-color: var(--success-light);
            color: var(--success-color);
        }
        
        .btn-accept:hover {
            background-color: var(--success-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-refuse {
            background-color: var(--danger-light);
            color: var(--danger-color);
        }
        
        .btn-refuse:hover {
            background-color: var(--danger-color);
            color: white;
            transform: translateY(-2px);
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
            backdrop-filter: blur(5px);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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
            background: linear-gradient(to right, var(--info-color), var(--info-dark));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 20px;
            color: white;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-modal:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
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
            background-color: #f9fafb;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
        
        /* Teacher Details */
        .teacher-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .detail-item {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            transition: all var(--transition-speed) ease;
        }
        
        .detail-item:hover {
            background-color: var(--info-light);
        }
        
        .detail-label {
            font-size: 12px;
            color: var(--gray-color);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .detail-label i {
            color: var(--info-color);
        }
        
        .detail-value {
            font-size: 16px;
            font-weight: 500;
            color: var(--dark-color);
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
            border-right: 3px solid var(--info-color);
        }
        
        /* Empty State */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        .empty-icon {
            font-size: 64px;
            color: #e5e7eb;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .empty-title {
            font-size: 20px;
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
            .container {
                padding: 20px;
            }
            
            .header {
                padding: 0 20px;
            }
            
            .search-bar {
                display: none;
            }
            
            .action-btn {
                padding: 6px 10px;
                font-size: 12px;
            }
            
            .teacher-details {
                grid-template-columns: 1fr;
            }
            
            .detail-message {
                grid-column: span 1;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (min-width: 992px) {
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
            <a href="#" class="menu-item">
                <i class="fas fa-user-graduate"></i>
                <span>طلبات التسجيل</span>
            </a>
            <a href="#" class="menu-item active">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>طلبات المدرسين</span>
            </a>
            <a href="afficher_prof.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>المعلمون </span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-book"></i>
                <span>المواد الدراسية</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>الجدول الدراسي</span>
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
                <h1 class="title"><i class="fas fa-chalkboard-teacher"></i> إدارة طلبات المدرسين</h1>
                <div class="breadcrumb">
                    <a href="#">الرئيسية</a>
                    <i class="fas fa-chevron-left"></i>
                    <span>طلبات المدرسين</span>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success">
                <div class="alert-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">إجمالي الطلبات</div>
                        <div class="stat-icon blue">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $total_candidatures ?></div>
                    <div class="stat-description">عدد طلبات المدرسين</div>
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
                    <div class="stat-value"><?= $candidatures_en_attente ?></div>
                    <div class="stat-description">طلبات تحتاج إلى مراجعة</div>
                    <div class="stat-progress">
                        <div class="progress-bar">
                            <div class="progress-value orange" style="width: <?= ($candidatures_en_attente / max(1, $total_candidatures)) * 100 ?>%"></div>
                        </div>
                        <div class="progress-percentage"><?= round(($candidatures_en_attente / max(1, $total_candidatures)) * 100) ?>%</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">مقبولة</div>
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $candidatures_acceptees ?></div>
                    <div class="stat-description">طلبات تمت الموافقة عليها</div>
                    <div class="stat-progress">
                        <div class="progress-bar">
                            <div class="progress-value green" style="width: <?= ($candidatures_acceptees / max(1, $total_candidatures)) * 100 ?>%"></div>
                        </div>
                        <div class="progress-percentage"><?= round(($candidatures_acceptees / max(1, $total_candidatures)) * 100) ?>%</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">مرفوضة</div>
                        <div class="stat-icon red">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $candidatures_refusees ?></div>
                    <div class="stat-description">طلبات تم رفضها</div>
                    <div class="stat-progress">
                        <div class="progress-bar">
                            <div class="progress-value red" style="width: <?= ($candidatures_refusees / max(1, $total_candidatures)) * 100 ?>%"></div>
                        </div>
                        <div class="progress-percentage"><?= round(($candidatures_refusees / max(1, $total_candidatures)) * 100) ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-list"></i> قائمة طلبات المدرسين</h2>
                    <div class="card-tools">
                        <button class="action-btn btn-view" onclick="printTable()">
                            <i class="fas fa-print"></i> طباعة
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="applicationsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الاسم الكامل</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>رقم الهاتف</th>
                                    <th>المادة</th>
                                    <th>الخبرة</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result && $result->num_rows > 0) {
                                    $counter = 1;
                                    while ($row = $result->fetch_assoc()) { 
                                        $statusClass = '';
                                        $statusText = 'قيد الانتظار';
                                        
                                        if ($row['statut'] == 'Acceptée' || $row['statut'] == 'مقبول') {
                                            $statusClass = 'status-accepted';
                                            $statusText = 'مقبول';
                                        } elseif ($row['statut'] == 'Refusée' || $row['statut'] == 'مرفوض') {
                                            $statusClass = 'status-rejected';
                                            $statusText = 'مرفوض';
                                        } else {
                                            $statusClass = 'status-pending';
                                        }
                                ?>
                                <tr>
                                    <td><?= $counter++ ?></td>
                                    <td><?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['telephone']) ?></td>
                                    <td><?= htmlspecialchars($row['matiere']) ?></td>
                                    <td><?= htmlspecialchars($row['experience']) ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                    <td>
                                        <button class="action-btn btn-view" onclick="viewDetails(<?= $row['id'] ?>, '<?= addslashes($row['nom']) ?>', '<?= addslashes($row['prenom']) ?>', '<?= addslashes($row['email']) ?>', '<?= addslashes($row['telephone']) ?>', '<?= addslashes($row['matiere']) ?>', '<?= addslashes($row['experience']) ?>', '<?= addslashes($row['message']) ?>', '<?= addslashes($row['statut']) ?>')">
                                            <i class="fas fa-eye"></i> عرض
                                        </button>
                                        <?php if ($row['statut'] != 'Acceptée' && $row['statut'] != 'Refusée' && $row['statut'] != 'مقبول' && $row['statut'] != 'مرفوض'): ?>
                                        <button class="action-btn btn-accept" onclick="confirmAction(<?= $row['id'] ?>, 'مقبول')">
                                            <i class="fas fa-check"></i> قبول
                                        </button>
                                        <button class="action-btn btn-refuse" onclick="confirmAction(<?= $row['id'] ?>, 'مرفوض')">
                                            <i class="fas fa-times"></i> رفض
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else { 
                                ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="fas fa-inbox"></i>
                                            </div>
                                            <h3 class="empty-title">لا توجد طلبات حالياً</h3>
                                            <p class="empty-description">ستظهر طلبات المدرسين الجديدة هنا</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
            <button class="action-btn btn-view" onclick="closeModal('confirmModal')">إلغاء</button>
            <button id="confirmBtn" class="action-btn btn-accept">تأكيد</button>
        </div>
    </div>
</div>

<!-- Modal de détails -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-info-circle"></i> تفاصيل طلب المدرس</h2>
            <button class="close-modal" onclick="closeModal('detailsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="teacher-details">
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-user"></i> الاسم الكامل</div>
                    <div class="detail-value" id="detailName"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-envelope"></i> البريد الإلكتروني</div>
                    <div class="detail-value" id="detailEmail"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-phone"></i> رقم الهاتف</div>
                    <div class="detail-value" id="detailPhone"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-book"></i> المادة</div>
                    <div class="detail-value" id="detailSubject"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-briefcase"></i> الخبرة</div>
                    <div class="detail-value" id="detailExperience"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-tag"></i> الحالة</div>
                    <div class="detail-value" id="detailStatus"></div>
                </div>
                <div class="detail-message">
                    <div class="detail-label"><i class="fas fa-comment"></i> الرسالة</div>
                    <div class="message-box" id="detailMessage"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="action-btn btn-view" onclick="closeModal('detailsModal')">إغلاق</button>
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

// View Details Modal
function viewDetails(id, nom, prenom, email, telephone, matiere, experience, message, statut) {
    document.getElementById('detailName').textContent = prenom + ' ' + nom;
    document.getElementById('detailEmail').textContent = email;
    document.getElementById('detailPhone').textContent = telephone;
    document.getElementById('detailSubject').textContent = matiere;
    document.getElementById('detailExperience').textContent = experience;
    
    let statusClass = '';
    let statusText = 'قيد الانتظار';
    
    if (statut == 'Acceptée' || statut == 'مقبول') {
        statusClass = 'status-accepted';
        statusText = 'مقبول';
    } else if (statut == 'Refusée' || statut == 'مرفوض') {
        statusClass = 'status-rejected';
        statusText = 'مرفوض';
    } else {
        statusClass = 'status-pending';
    }
    
    document.getElementById('detailStatus').innerHTML = `<span class="status-badge ${statusClass}">${statusText}</span>`;
    document.getElementById('detailMessage').textContent = message || 'لا توجد رسالة';
    
    let actionsHtml = '';
    if (statut !== 'Acceptée' && statut !== 'Refusée' && statut !== 'مقبول' && statut !== 'مرفوض') {
        actionsHtml = `
            <button class="action-btn btn-accept" onclick="confirmAction(${id}, 'مقبول')">
                <i class="fas fa-check"></i> قبول
            </button>
            <button class="action-btn btn-refuse" onclick="confirmAction(${id}, 'مرفوض')">
                <i class="fas fa-times"></i> رفض
            </button>
        `;
    }
    document.getElementById('detailActions').innerHTML = actionsHtml;
    
    document.getElementById('detailsModal').style.display = 'flex';
}

// Confirmation Modal
let currentId = null;
let currentStatus = null;

function confirmAction(id, status) {
    currentId = id;
    currentStatus = status;
    
    const message = status === 'مقبول' 
        ? 'هل أنت متأكد من أنك تريد قبول هذا الطلب؟ سيتم إرسال بريد إلكتروني للمتقدم.'
        : 'هل أنت متأكد من أنك تريد رفض هذا الطلب؟ سيتم إرسال بريد إلكتروني للمتقدم.';
        
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmBtn').className = status === 'مقبول' 
        ? 'action-btn btn-accept' 
        : 'action-btn btn-refuse';
        
    document.getElementById('confirmModal').style.display = 'flex';
    
    document.getElementById('confirmBtn').onclick = function() {
        // Redirection vers modifier_statut_candidature.php
        window.location.href = 'modifier_statut_candidature.php?id=' + currentId + '&statut=' + status;
        
        // Mettre à jour l'interface immédiatement sans attendre le rechargement
        updateInterfaceStatus(currentId, status);
        
        closeModal('confirmModal');
    };
}

// Nouvelle fonction pour mettre à jour l'interface
function updateInterfaceStatus(id, status) {
    // Trouver la ligne correspondante dans le tableau
    const rows = document.querySelectorAll('#applicationsTable tbody tr');
    
    rows.forEach(row => {
        // Trouver l'ID de la ligne actuelle
        const buttons = row.querySelectorAll('button');
        if (buttons.length > 0) {
            const onclickAttr = buttons[0].getAttribute('onclick');
            if (onclickAttr) {
                const match = onclickAttr.match(/viewDetails\((\d+)/);
                if (match && match[1] == id) {
                    // Mettre à jour le statut
                    const statusCell = row.querySelector('td:nth-child(7)');
                    const actionsCell = row.querySelector('td:nth-child(8)');
                    
                    if (status === 'مقبول') {
                        statusCell.innerHTML = '<span class="status-badge status-accepted">مقبول</span>';
                    } else {
                        statusCell.innerHTML = '<span class="status-badge status-rejected">مرفوض</span>';
                    }
                    
                    // Ne garder que le bouton "عرض" (Voir)
                    let viewButton = '';
                    buttons.forEach(btn => {
                        if (btn.classList.contains('btn-view')) {
                            viewButton = btn.outerHTML;
                        }
                    });
                    
                    actionsCell.innerHTML = viewButton;
                    
                    // Mettre à jour également le modal de détails s'il est ouvert
                    const detailStatus = document.getElementById('detailStatus');
                    const detailActions = document.getElementById('detailActions');
                    
                    if (detailStatus && detailActions) {
                        if (status === 'مقبول') {
                            detailStatus.innerHTML = '<span class="status-badge status-accepted">مقبول</span>';
                        } else {
                            detailStatus.innerHTML = '<span class="status-badge status-rejected">مرفوض</span>';
                        }
                        detailActions.innerHTML = '';
                    }
                }
            }
        }
    });
    
    // Mettre à jour les statistiques (optionnel, mais améliore l'expérience utilisateur)
    updateStatistics(status);
}

// Fonction pour mettre à jour les statistiques
function updateStatistics(status) {
    // Récupérer les éléments des statistiques
    const totalElement = document.querySelector('.stats-grid .stat-card:nth-child(1) .stat-value');
    const pendingElement = document.querySelector('.stats-grid .stat-card:nth-child(2) .stat-value');
    const acceptedElement = document.querySelector('.stats-grid .stat-card:nth-child(3) .stat-value');
    const rejectedElement = document.querySelector('.stats-grid .stat-card:nth-child(4) .stat-value');
    
    if (pendingElement && (acceptedElement || rejectedElement)) {
        // Convertir les valeurs en nombres
        const pendingCount = parseInt(pendingElement.textContent);
        
        if (pendingCount > 0) {
            // Diminuer le nombre de candidatures en attente
            pendingElement.textContent = (pendingCount - 1).toString();
            
            // Mettre à jour les barres de progression
            updateProgressBars();
            
            // Augmenter le nombre de candidatures acceptées ou refusées
            if (status === 'مقبول' && acceptedElement) {
                const acceptedCount = parseInt(acceptedElement.textContent);
                acceptedElement.textContent = (acceptedCount + 1).toString();
            } else if (status === 'مرفوض' && rejectedElement) {
                const rejectedCount = parseInt(rejectedElement.textContent);
                rejectedElement.textContent = (rejectedCount + 1).toString();
            }
        }
    }
}

// Fonction pour mettre à jour les barres de progression
function updateProgressBars() {
    const totalElement = document.querySelector('.stats-grid .stat-card:nth-child(1) .stat-value');
    const pendingElement = document.querySelector('.stats-grid .stat-card:nth-child(2) .stat-value');
    const acceptedElement = document.querySelector('.stats-grid .stat-card:nth-child(3) .stat-value');
    const rejectedElement = document.querySelector('.stats-grid .stat-card:nth-child(4) .stat-value');
    
    if (totalElement && pendingElement && acceptedElement && rejectedElement) {
        const total = parseInt(totalElement.textContent);
        const pending = parseInt(pendingElement.textContent);
        const accepted = parseInt(acceptedElement.textContent);
        const rejected = parseInt(rejectedElement.textContent);
        
        // Mettre à jour les pourcentages
        const pendingPercentage = Math.round((pending / Math.max(1, total)) * 100);
        const acceptedPercentage = Math.round((accepted / Math.max(1, total)) * 100);
        const rejectedPercentage = Math.round((rejected / Math.max(1, total)) * 100);
        
        // Mettre à jour les barres de progression
        document.querySelector('.stats-grid .stat-card:nth-child(2) .progress-value').style.width = pendingPercentage + '%';
        document.querySelector('.stats-grid .stat-card:nth-child(2) .progress-percentage').textContent = pendingPercentage + '%';
        
        document.querySelector('.stats-grid .stat-card:nth-child(3) .progress-value').style.width = acceptedPercentage + '%';
        document.querySelector('.stats-grid .stat-card:nth-child(3) .progress-percentage').textContent = acceptedPercentage + '%';
        
        document.querySelector('.stats-grid .stat-card:nth-child(4) .progress-value').style.width = rejectedPercentage + '%';
        document.querySelector('.stats-grid .stat-card:nth-child(4) .progress-percentage').textContent = rejectedPercentage + '%';
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Print Table
function printTable() {
    window.print();
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

// Close modal when clicking outside
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

// Afficher une notification au chargement de la page si nécessaire
<?php if (isset($_GET['message'])) { ?>
showNotification('success', 'تم بنجاح', '<?= htmlspecialchars($_GET['message']) ?>');
<?php } ?>
</script>

</body>
</html>