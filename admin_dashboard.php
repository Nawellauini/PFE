<?php
session_start();
include 'db_config.php';

// التحقق من تسجيل الدخول وأن المستخدم هو مسؤول
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: login.php");
    exit();
}

// إحصائيات للوحة التحكم
$stats = [
    'eleves' => 0,
    'professeurs' => 0,
    'inspecteurs' => 0,
    'classes' => 0
];

// عدد الطلاب
$query = "SELECT COUNT(*) as count FROM eleves";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['eleves'] = $row['count'];
}

// عدد المدرسين
$query = "SELECT COUNT(*) as count FROM professeurs";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['professeurs'] = $row['count'];
}

// عدد المفتشين
$query = "SELECT COUNT(*) as count FROM inspecteurs";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['inspecteurs'] = $row['count'];
}

// عدد الفصول الدراسية
$query = "SELECT COUNT(*) as count FROM classes";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['classes'] = $row['count'];
}

// الحصول على قائمة المستخدمين الأخيرة
$recent_users = [];
$query = "SELECT 'طالب' as type, id_eleve as id, nom, prenom, email, DATE_FORMAT(date_inscription, '%Y-%m-%d') as date_creation FROM eleves 
          UNION 
          SELECT 'مدرس' as type, id_professeur as id, nom, prenom, email, DATE_FORMAT(date_creation, '%Y-%m-%d') as date_creation FROM professeurs 
          UNION 
          SELECT 'مفتش' as type, id_inspecteur as id, nom, prenom, email, DATE_FORMAT(date_creation, '%Y-%m-%d') as date_creation FROM inspecteurs 
          ORDER BY date_creation DESC LIMIT 5";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول - المدرسة الابتدائية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --success-color: #4CAF50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
            min-height: 100vh;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-right: 4px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-right-color: var(--accent-color);
        }
        
        .menu-item i {
            margin-left: 15px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }
        
        .content-wrapper {
            margin-right: 280px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .main-header {
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
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
            margin-left: 10px;
        }
        
        .user-name {
            font-weight: 600;
        }
        
        .user-role {
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        .header-actions button {
            background-color: transparent;
            border: none;
            color: var(--gray-color);
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.3s ease;
            margin-right: 15px;
        }
        
        .header-actions button:hover {
            color: var(--primary-color);
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-left: 15px;
            color: white;
        }
        
        .stat-icon.students {
            background-color: var(--primary-color);
        }
        
        .stat-icon.teachers {
            background-color: var(--success-color);
        }
        
        .stat-icon.inspectors {
            background-color: var(--warning-color);
        }
        
        .stat-icon.classes {
            background-color: var(--accent-color);
        }
        
        .stat-info h4 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .stat-info p {
            color: var(--gray-color);
            margin: 0;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .content-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h3 {
            font-weight: 600;
            margin: 0;
        }
        
        .card-header a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .card-header a:hover {
            color: var(--secondary-color);
        }
        
        .recent-users-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-item-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-left: 15px;
            font-size: 1.2rem;
        }
        
        .user-item-info {
            flex: 1;
        }
        
        .user-item-name {
            font-weight: 600;
            margin: 0;
        }
        
        .user-item-email {
            color: var(--gray-color);
            font-size: 0.9rem;
            margin: 0;
        }
        
        .user-item-type {
            background-color: #e9ecef;
            color: var(--dark-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 10px;
        }
        
        .user-item-type.طالب {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .user-item-type.مدرس {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }
        
        .user-item-type.مفتش {
            background-color: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .action-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .action-card:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .action-card p {
            margin: 0;
            font-weight: 500;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .content-wrapper {
                margin-right: 0;
            }
            
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .toggle-sidebar {
                display: block !important;
            }
        }
        
        .toggle-sidebar {
            display: none;
            background-color: transparent;
            border: none;
            color: var(--dark-color);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        @media (max-width: 576px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h3>لوحة تحكم المسؤول</h3>
    </div>
    
    <div class="sidebar-menu">
        <a href="#" class="menu-item active">
            <i class="fas fa-tachometer-alt"></i>
            <span>الرئيسية</span>
        </a>
        
        <a href="gestion_inscriptions.php" class="menu-item">
            <i class="fas fa-user-graduate"></i>
            <span>إدارة التلاميذ</span>
        </a>
        
        <a href="reponse_email_admin.php" class="menu-item">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>إدارة المعلمين</span>
        </a>
        
        <a href="view_inspecteur.php" class="menu-item">
            <i class="fas fa-user-tie"></i>
            <span>إدارة المتفقدين</span>
        </a>
        
        <a href="classes_admin.php" class="menu-item">
            <i class="fas fa-school"></i>
            <span>إدارة الأقسام</span>
        </a>
        
        <a href="matiere.php" class="menu-item">
            <i class="fas fa-book"></i>
            <span>إدارة المواد</span>
        </a>
        <a href="logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>تسجيل الخروج</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="content-wrapper">
    <div class="main-header">
        <button class="toggle-sidebar">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php echo isset($_SESSION['nom_admin']) ? substr($_SESSION['nom_admin'], 0, 1) : 'A'; ?>
            </div>
            <div>
                <div class="user-name">
                    <?php 
                    if(isset($_SESSION['nom_admin']) && isset($_SESSION['prenom_admin'])) {
                        echo $_SESSION['nom_admin'] . ' ' . $_SESSION['prenom_admin'];
                    } else {
                        echo "المسؤول";
                    }
                    ?>
                </div>
                <div class="user-role">مسؤول النظام</div>
            </div>
        </div>
        
        <div class="header-actions">
            <button title="الإشعارات">
                <i class="fas fa-bell"></i>
            </button>
            <button title="الرسائل">
                <i class="fas fa-envelope"></i>
            </button>
            <button title="الإعدادات">
                <i class="fas fa-cog"></i>
            </button>
        </div>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon students">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-info">
                <h4><?php echo $stats['eleves']; ?></h4>
                <p>التلاميذ</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon teachers">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-info">
                <h4><?php echo $stats['professeurs']; ?></h4>
                <p>المعلمين</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon inspectors">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-info">
                <h4><?php echo $stats['inspecteurs']; ?></h4>
                <p>المتفقدين</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon classes">
                <i class="fas fa-school"></i>
            </div>
            <div class="stat-info">
                <h4><?php echo $stats['classes']; ?></h4>
                <p>الأقسام</p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="content-card">
            <div class="card-header">
                <h3>المستخدمون الأخيرون</h3>
                <a href="#">عرض الكل</a>
            </div>
            
            <ul class="recent-users-list">
                <?php foreach ($recent_users as $user): ?>
                    <li class="user-item">
                        <div class="user-item-avatar">
                            <?php echo isset($user['nom']) ? substr($user['nom'], 0, 1) : '?'; ?>
                        </div>
                        <div class="user-item-info">
                            <h4 class="user-item-name"><?php echo $user['nom'] . ' ' . $user['prenom']; ?></h4>
                            <p class="user-item-email"><?php echo $user['email']; ?></p>
                        </div>
                        <span class="user-item-type <?php echo $user['type']; ?>">
                            <?php echo $user['type']; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
                
                <?php if (empty($recent_users)): ?>
                    <li class="text-center py-4">لا يوجد مستخدمين حاليًا</li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="content-card">
            <div class="card-header">
                <h3>إجراءات سريعة</h3>
            </div>
            
            <div class="quick-actions">
                <div class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <p>إضافة تلميذ</p>
                </div>
                
                <div class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <p>إضافة معلم</p>
                </div>
                
                <div class="action-card">
                    <i class="fas fa-plus-circle"></i>
                    <p>إضافة قسم</p>
                </div>
                
                <div class="action-card">
                <i class="fas fa-book-medical"></i>
                    <p>إضافة مادة</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const toggleSidebarBtn = document.querySelector('.toggle-sidebar');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleSidebarBtn) {
        toggleSidebarBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 992 && 
            sidebar && 
            toggleSidebarBtn && 
            !sidebar.contains(event.target) && 
            !toggleSidebarBtn.contains(event.target) && 
            sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });
    
    // Quick actions
    const actionCards = document.querySelectorAll('.action-card');
    
    actionCards.forEach(card => {
        card.addEventListener('click', function() {
            const action = this.querySelector('p').textContent;
            
            switch(action) {
                case 'إضافة تلميذ':
                    window.location.href = 'ajouter_eleve.php';
                    break;
                    case 'إضافة معلم':
                    window.location.href = 'add_teacher.php';
                    break;
                    case 'إضافة قسم':
                    window.location.href = 'add_classe.php';
                    break;
                    case 'إضافة مادة':
                    window.location.href = 'ajouter_matiere.php';
                    break;
            }
        });
    });
});
</script>
</body>
</html>

