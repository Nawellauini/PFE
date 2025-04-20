<?php

// Inclure le fichier de configuration pour la connexion à la base de données
require_once 'db_config.php';

// Démarrer la session
session_start();

// Vérifier si le professeur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];

// Récupérer les informations du professeur
$queryProf = $conn->prepare("SELECT * FROM professeurs WHERE id_professeur = ?");
$queryProf->bind_param("i", $id_professeur);
$queryProf->execute();
$result = $queryProf->get_result();

// Vérifier si le professeur existe
if ($result->num_rows === 0) {
    // Rediriger vers la page de connexion si le professeur n'existe pas
    session_destroy();
    header("Location: login.php");
    exit();
}

$prof = $result->fetch_assoc();
$nomProf = $prof['prenom'] . ' ' . $prof['nom'];

// Récupérer le nombre de classes du professeur
$queryClassCount = $conn->prepare("SELECT COUNT(*) as nb_classes FROM professeurs_classes WHERE id_professeur = ?");
$queryClassCount->bind_param("i", $id_professeur);
$queryClassCount->execute();
$classCount = $queryClassCount->get_result()->fetch_assoc()['nb_classes'];

// Récupérer le nombre d'événements à venir créés par le professeur
$queryEventCount = $conn->prepare("SELECT COUNT(*) as nb_events FROM calendar_events WHERE professor_id = ? AND event_date >= CURDATE()");
$queryEventCount->bind_param("i", $id_professeur);
$queryEventCount->execute();
$eventResult = $queryEventCount->get_result();
$eventCount = $eventResult->fetch_assoc()['nb_events'];

// Récupérer les événements récents (les 5 derniers)
$queryRecentEvents = $conn->prepare("SELECT ce.*, c.nom_classe 
                                    FROM calendar_events ce
                                    JOIN classes c ON ce.class = c.id_classe
                                    WHERE ce.professor_id = ?
                                    ORDER BY ce.event_date DESC
                                    LIMIT 5");
$queryRecentEvents->bind_param("i", $id_professeur);
$queryRecentEvents->execute();
$recentEvents = $queryRecentEvents->get_result()->fetch_all(MYSQLI_ASSOC);

// Récupérer les classes du professeur
$queryClasses = $conn->prepare("SELECT c.* 
                               FROM professeurs_classes pc
                               JOIN classes c ON pc.id_classe = c.id_classe
                               WHERE pc.id_professeur = ?
                               LIMIT 5");
$queryClasses->bind_param("i", $id_professeur);
$queryClasses->execute();
$classes = $queryClasses->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم | منصة المعلم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e6091;
            --primary-dark: #0d4a77;
            --secondary-color: #2a9d8f;
            --secondary-dark: #1f756a;
            --accent-color: #e9c46a;
            --text-color: #264653;
            --text-light: #546a7b;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
            --error-color: #e76f51;
            --success-color: #2a9d8f;
            --warning-color: #f4a261;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', 'Amiri', serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgdmlld0JveD0iMCAwIDYwIDYwIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiMxZTYwOTEiIGZpbGwtb3BhY2l0eT0iLjAzIj48cGF0aCBkPSJNMzYgMzRjMC0yLjIgMS44LTQgNC00czQgMS44IDQgNC0xLjggNC00IDQtNC0xLjgtNC00bTAtMTZjMC0yLjIgMS44LTQgNC00czQgMS44IDQgNC0xLjggNC00IDQtNC0xLjgtNC00bTE2IDE2YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0tMTYgMTZjMC0yLjIgMS44LTQgNC00czQgMS44IDQgNC0xLjggNC00IDQtNC0xLjgtNC00bTE2IDBjMC0yLjIgMS44LTQgNC00czQgMS44IDQgNC0xLjggNC00IDQtNC0xLjgtNC00bTAtMTZjMC0yLjIgMS44LTQgNC00czQgMS44IDQgNC0xLjggNC00IDQtNC0xLjgtNC00TTIwIDM0YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0wLTE2YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0xNiAwYzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0tOC04YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0wIDE2YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0wIDE2YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0wIDE2YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNCIvPjwvZz48L2c+PC9zdmc+');
        }

        /* Header */
        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-left: 10px;
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links li {
            margin-right: 1.5rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            position: relative;
        }

        .nav-links a:hover, .nav-links a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .nav-links a.active:after {
            content: '';
            position: absolute;
            bottom: -5px;
            right: 50%;
            transform: translateX(50%);
            width: 30px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 3px;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            object-fit: cover;
            border: 2px solid white;
        }

        .user-name {
            font-weight: 500;
        }

        .logout-btn {
            margin-right: 15px;
            color: white;
            text-decoration: none;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.4rem 0.75rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
        }

        .page-title {
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary-color);
            position: relative;
            text-align: right;
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: 0;
            width: 100px;
            height: 2px;
            background-color: var(--accent-color);
        }

        /* Dashboard Styles */
        .welcome-section {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            background-image: linear-gradient(to left, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7)), url('https://source.unsplash.com/random/1200x400/?school');
            background-size: cover;
            background-position: center;
        }

        .welcome-section:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background-color: var(--primary-color);
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .welcome-title {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .welcome-text {
            font-size: 1.1rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background-color: var(--primary-color);
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--text-light);
        }

        .dashboard-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .dashboard-section {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .dashboard-section:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background-color: var(--secondary-color);
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .dashboard-section-title {
            font-size: 1.3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .dashboard-section-title i {
            margin-left: 0.5rem;
        }

        .dashboard-list {
            list-style: none;
        }

        .dashboard-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .dashboard-item:last-child {
            border-bottom: none;
        }

        .dashboard-item:hover {
            background-color: rgba(42, 157, 143, 0.05);
            transform: translateX(-5px);
        }

        .dashboard-item-title {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.3rem;
        }

        .dashboard-item-info {
            font-size: 0.9rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
        }

        .dashboard-item-info i {
            margin-left: 0.5rem;
            color: var(--secondary-color);
        }

        .dashboard-link {
            display: block;
            text-align: left;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .dashboard-link:hover {
            color: var(--primary-dark);
            transform: translateX(-5px);
        }

        .dashboard-link i {
            margin-left: 0.5rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
            border-color: var(--primary-color);
        }

        .action-btn i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .action-btn span {
            font-weight: 600;
        }

        /* Footer */
        .footer {
            background-color: #264653;
            color: #ecf0f1;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .footer-section {
            flex: 1;
            min-width: 300px;
            margin-bottom: 1.5rem;
        }

        .footer-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #fff;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 50px;
            height: 2px;
            background-color: var(--accent-color);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
            position: relative;
            padding-right: 20px;
        }

        .footer-links li:before {
            content: '\f105';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 0;
            color: var(--accent-color);
        }

        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #fff;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #34495e;
            margin-top: 1.5rem;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-links {
                margin-top: 1rem;
                flex-direction: column;
                width: 100%;
            }

            .nav-links li {
                margin: 0;
                margin-bottom: 0.5rem;
            }

            .user-info {
                margin-top: 1rem;
            }

            .dashboard-sections {
                grid-template-columns: 1fr;
            }
        }
        
        .no-items {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-light);
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="dashboardProf.php" class="logo">
                <span>منصة المعلم</span>
                    <i class="fas fa-graduation-cap"></i>
                </a>
                <ul class="nav-links">
                    <li><a href="dashboardProf.php" class="active"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                    <li><a href="classes.php"><i class="fas fa-users"></i> الأقسام</a></li>
                    <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> الرزنامة</a></li>
                        <li><a href="calendar_events.php"><i class="fas fa-plus-circle"></i> إضافة موعد </a></li>

                </ul>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($nomProf) ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($nomProf) ?>&background=random" alt="صورة الملف الشخصي">
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <h1 class="page-title"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</h1>
            
            <section class="welcome-section animate-fade-in">
                <h2 class="welcome-title">مرحباً، <?= htmlspecialchars($nomProf) ?>!</h2>
                <p class="welcome-text">مرحباً بك في بوابة المدرس. هنا يمكنك إدارة أقسامك، إضافة أحداث لروزنامتك، ومتابعة نشاطاتك.</p>

            </section>
            
            <div class="stats-container">
                <div class="stat-card animate-fade-in">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?= $classCount ?></div>
                    <div class="stat-label">الأقسام</div>
                </div>
                
                
                
                
            </div>
            
            <div class="dashboard-sections">
                <section class="dashboard-section animate-fade-in">
                <h2 class="dashboard-section-title"><i class="fas fa-calendar-alt"></i> عرض المواعيد</h2>
                    
                    <?php if (empty($recentEvents)): ?>
                        <p class="no-items">لا توجد مواعيد</p>   
                    <?php else: ?>
                        <ul class="dashboard-list">
                            <?php foreach ($recentEvents as $event): ?>
                                <li class="dashboard-item">
                                    <div class="dashboard-item-title"><?= htmlspecialchars($event['description']) ?></div>
                                    <div class="dashboard-item-info">
                                        <i class="fas fa-users"></i>
                                        <?= htmlspecialchars($event['nom_classe']) ?>
                                    </div>
                                    <div class="dashboard-item-info">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= date('d/m/Y', strtotime($event['event_date'])) ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <a href="calendar.php" class="dashboard-link">
                            عرض جميع المواعيد <i class="fas fa-arrow-left"></i>
                        </a>
                    <?php endif; ?>
                </section>
                
                <section class="dashboard-section animate-fade-in">
                <h2 class="dashboard-section-title"><i class="fas fa-users"></i> القسم الخاص بي</h2>
                    
                    <?php if (empty($classes)): ?>
                        <p class="no-items">لا توجد دروس مسجلة</p>
                    <?php else: ?>
                        <ul class="dashboard-list">
                            <?php foreach ($classes as $class): ?>
                                <li class="dashboard-item">
                                    <div class="dashboard-item-title"><?= htmlspecialchars($class['nom_classe']) ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <a href="classes.php" class="dashboard-link">
    عرض جميع الأقسام <i class="fas fa-arrow-left"></i>
</a>

                    <?php endif; ?>
                </section>
            </div>
            
            <div class="quick-actions">
                <a href="classes.php" class="action-btn animate-fade-in">
                    <i class="fas fa-users"></i>
                    <span>إدارة الأقسام</span>
                </a>
                
                <a href="calendar.php" class="action-btn animate-fade-in">
                    <i class="fas fa-calendar-alt"></i>
                    <span>عرض الروزنامة</span>
                </a>
                
                <a href="calendar_events.php" class="action-btn animate-fade-in">
                    <i class="fas fa-plus-circle"></i>
                    <span>إضافة موعد</span>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                <h3 class="footer-title">منصة المعلم</h3>
                <p>منصة مخصصة لإدارة  المواعيد والأقسام الدراسية للمعلمين.</p>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul class="footer-links">
                        <li><a href="dashboardProf.php">لوحة التحكم</a></li>
                        <li><a href="classes.php"><i class="fas fa-users"></i> الأقسام</a></li>

                        <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> الرزنامة</a></li>
                        <li><a href="calendar_events.php"><i class="fas fa-plus-circle"></i> إضافة موعد </a></li>
                    </ul>
                </div>
                <div class="footer-section">
                <h3 class="footer-title">تواصل معنا</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> support@portail-enseignant.fr</li>
                        <li><i class="fas fa-phone"></i> +33 1 23 45 67 89</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> منصة المعلم. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

   
</body>
</html>