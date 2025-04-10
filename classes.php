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
$prof = $queryProf->get_result()->fetch_assoc();
$nomProf = $prof['prenom'] . ' ' . $prof['nom'];

// Récupérer les classes du professeur
$queryClasses = $conn->prepare("SELECT c.*, COUNT(e.id_eleve) as nb_eleves 
                               FROM professeurs_classes pc
                               JOIN classes c ON pc.id_classe = c.id_classe
                               LEFT JOIN eleves e ON c.id_classe = e.id_classe
                               WHERE pc.id_professeur = ?
                               GROUP BY c.id_classe");
$queryClasses->bind_param("i", $id_professeur);
$queryClasses->execute();
$classes = $queryClasses->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الدروس | منصة المدرس</title>
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

        /* Classes Grid */
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .class-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .class-card:before {
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

        .class-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            position: relative;
        }

        .class-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .class-level {
            font-size: 1rem;
            opacity: 0.9;
        }

        .class-body {
            padding: 1.5rem;
        }

        .class-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .class-stat {
            text-align: center;
            flex: 1;
            padding: 0.5rem;
            border-right: 1px solid var(--border-color);
        }

        .class-stat:last-child {
            border-right: none;
        }

        .class-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.3rem;
        }

        .class-stat-label {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .class-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .class-btn {
            flex: 1;
            text-align: center;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }

        .class-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .class-btn i {
            margin-left: 0.5rem;
        }

        .no-classes {
            text-align: center;
            padding: 3rem;
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-top: 2rem;
        }

        .no-classes i {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .no-classes-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .no-classes-text {
            color: var(--text-light);
            margin-bottom: 1.5rem;
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

            .classes-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="dashboardProf.php" class="logo">
                <span>منصة المدرس</span>
                    <i class="fas fa-graduation-cap"></i>
                </a>
                <ul class="nav-links">
                    <li><a href="dashboardProf.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                    <li><a href="classes.php" class="active"><i class="fas fa-users"></i> الدروس</a></li>
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
        <h1 class="page-title"><i class="fas fa-users"></i> الأقسام</h1>
            
            <?php if (empty($classes)): ?>
                <div class="no-classes animate-fade-in">
                    <i class="fas fa-users-slash"></i>
                    <h2 class="no-classes-title">لا توجد أقسام مسجلة</h2>
                    <p class="no-classes-text">لم يتم إسناد أي قسم لك حتى الآن.</p>
                </div>
            <?php else: ?>
                <div class="classes-grid">
                    <?php foreach ($classes as $class): ?>
                        <div class="class-card animate-fade-in">
                           
                            <div class="class-body">
                                <div class="class-stats">
                                    <div class="class-stat">
                                        <div class="class-stat-value"><?= $class['nb_eleves'] ?></div>
                                        <div class="class-stat-label">تلميذ</div>

                                    </div>
                                    
                                    <?php
                                    // Récupérer le nombre d'événements pour cette classe
                                    $queryEventCount = $conn->prepare("SELECT COUNT(*) as nb_events FROM calendar_events WHERE class = ?");
                                    $queryEventCount->bind_param("i", $class['id_classe']);
                                    $queryEventCount->execute();
                                    $eventCount = $queryEventCount->get_result()->fetch_assoc()['nb_events'];
                                    ?>
                                    
                                    <div class="class-stat">
                                        <div class="class-stat-value"><?= $eventCount ?></div>
                                        <div class="class-stat-label">حدث</div>
                                    </div>
                                </div>
                                
                                <div class="class-actions">
                                    <a href="class_details.php?id=<?= $class['id_classe'] ?>" class="class-btn">
                                    <i class="fas fa-info-circle"></i> معلومات
                                    </a>
                                    
                                    <a href="calendar_events.php?class=<?= $class['id_classe'] ?>" class="class-btn">
                                    <i class="fas fa-calendar-plus"></i> إضافة موعد
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                <h3 class="footer-title">منصة المعلم</h3>
                <p>منصة شاملة لإدارة الدروس والأنشطة المدرسية للمعلمين.</p>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul class="footer-links">
                        <li><a href="dashboardProf.php">لوحة التحكم</a></li>
                        <li><a href="classes.php" class="active"><i class="fas fa-users"></i> الدروس</a></li>
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
            <p>&copy; <?= date('Y') ?> بوابة المدرس. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animation pour les cartes de classe
            const cards = document.querySelectorAll('.animate-fade-in');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>