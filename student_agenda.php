<?php
// Inclure la configuration de la base de données
require_once 'db_config.php';

// Démarrer la session
session_start();

// Vérifier si l'élève est connecté
if (!isset($_SESSION['id_eleve'])) {
    die("Accès refusé. Veuillez vous connecter.");
}

$id_eleve = $_SESSION['id_eleve'];

// Récupérer la classe et les informations de l'élève
$queryStudent = $conn->prepare("SELECT e.id_classe, e.nom, e.prenom, c.nom_classe 
                               FROM eleves e 
                               JOIN classes c ON e.id_classe = c.id_classe 
                               WHERE e.id_eleve = ?");
$queryStudent->bind_param("i", $id_eleve);
$queryStudent->execute();
$result = $queryStudent->get_result();
$student = $result->fetch_assoc();
$id_classe = $student['id_classe'];
$nom_classe = $student['nom_classe'];
$nom_eleve = $student['prenom'] . ' ' . $student['nom'];

// Récupérer les événements pour la classe de l'élève
$queryEvents = $conn->prepare("SELECT event_date, description, professor_id FROM calendar_events WHERE class = ? ORDER BY event_date ASC");
$queryEvents->bind_param("s", $id_classe);
$queryEvents->execute();
$events = $queryEvents->get_result()->fetch_all(MYSQLI_ASSOC);

// Organiser les événements par mois
$eventsByMonth = [];
foreach ($events as $event) {
    $month = date('Y-m', strtotime($event['event_date']));
    if (!isset($eventsByMonth[$month])) {
        $eventsByMonth[$month] = [];
    }
    $eventsByMonth[$month][] = $event;
}

// Récupérer les noms des professeurs
$professorNames = [];
if (!empty($events)) {
    $professorIds = array_unique(array_column($events, 'professor_id'));
    $placeholders = str_repeat('?,', count($professorIds) - 1) . '?';
    $queryProfessors = $conn->prepare("SELECT id_professeur, nom, prenom FROM professeurs WHERE id_professeur IN ($placeholders)");
    $queryProfessors->bind_param(str_repeat('i', count($professorIds)), ...$professorIds);
    $queryProfessors->execute();
    $professors = $queryProfessors->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($professors as $professor) {
        $professorNames[$professor['id_professeur']] = $professor['prenom'] . ' ' . $professor['nom'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مواعيد الأنشطة | بوابة التلميذ</title>
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
            --tree-line-color: #ddd;
            --tree-connector: #ccc;
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

        .student-info {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .student-details {
            display: flex;
            align-items: center;
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-left: 15px;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }

        .student-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .student-class {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .class-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Tree Structure */
        .tree-container {
            margin: 20px 0;
        }

        .tree {
            position: relative;
            padding-right: 20px;
        }

        .tree-month {
            position: relative;
            padding: 10px 30px 10px 0;
            border-right: 2px solid var(--tree-line-color);
            margin-right: 20px;
            margin-bottom: 20px;
        }

        .tree-month:last-child {
            border-right: none;
        }

        .tree-month:before {
            content: '';
            position: absolute;
            top: 20px;
            right: 0;
            width: 20px;
            height: 2px;
            background-color: var(--tree-line-color);
        }

        .month-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px dashed var(--accent-color);
            display: inline-block;
        }

        .tree-event {
            position: relative;
            padding: 10px 30px 10px 0;
            border-right: 2px solid var(--tree-connector);
            margin-right: 20px;
            margin-bottom: 15px;
        }

        .tree-event:last-child {
            border-right: none;
            margin-bottom: 0;
        }

        .tree-event:before {
            content: '';
            position: absolute;
            top: 20px;
            right: 0;
            width: 20px;
            height: 2px;
            background-color: var(--tree-connector);
        }

        .event-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
            border-right: 4px solid var(--primary-color);
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .event-date {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .event-date i {
            margin-left: 8px;
            color: var(--accent-color);
        }

        .event-description {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--text-color);
            margin-bottom: 10px;
            padding-right: 10px;
            border-right: 2px solid var(--accent-color);
        }

        .event-professor {
            font-size: 0.9rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .event-professor i {
            margin-left: 8px;
            color: var(--secondary-color);
        }

        .no-events {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            text-align: center;
            font-size: 1.2rem;
            color: var(--text-light);
        }

        .no-events i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: block;
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

            .student-info {
                flex-direction: column;
                text-align: center;
            }

            .student-details {
                margin-bottom: 1rem;
                flex-direction: column;
            }

            .student-avatar {
                margin-left: 0;
                margin-bottom: 10px;
            }

            .tree-month, .tree-event {
                padding-right: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="dashboard.php" class="logo">
                <span>الفضاء الخاص بالتلميذ</span>
                    <i class="fas fa-user-graduate"></i>
                </a>
                <ul class="nav-links">
                <li><a href="dashboard.php">لوحة التحكم</a></li>
                        <li><a href="cours.php"><i class="fas fa-book"></i> الدورس</a></li>
                        <li><a href="calendar_events.php"><i class="fas fa-calendar-alt"></i> الروزنامة</a></li>
                        <li><a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                </ul>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($nom_eleve) ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($nom_eleve) ?>&background=random" alt="صورة الملف الشخصي">
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
        <h1 class="page-title"><i class="fas fa-calendar-alt"></i> الروزنامة</h1>

            
            <div class="student-info animate-fade-in">
                <div class="student-details">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($nom_eleve) ?>&background=random" alt="صورة الطالب" class="student-avatar">
                    <div>
                        <div class="student-name"><?= htmlspecialchars($nom_eleve) ?></div>
                        <div class="student-class"><i class="fas fa-users"></i> <?= htmlspecialchars($nom_classe) ?></div>
                    </div>
                </div>
                <div class="class-badge">
                    <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($nom_classe) ?>
                </div>
            </div>

            <?php if (empty($events)) : ?>
                <div class="no-events animate-fade-in">
                    <i class="fas fa-calendar-times"></i>
                    <p>لا توجد أحداث مجدولة حاليًا</p>
                </div>
            <?php else : ?>
                <div class="tree-container animate-fade-in">
                    <div class="tree">
                        <?php foreach ($eventsByMonth as $month => $monthEvents) : ?>
                            <div class="tree-month">
                                <div class="month-title">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php 
                                  $monthNames = [
                                    '01' => 'جانفي',   // Janvier
                                    '02' => 'فيفري',   // Février
                                    '03' => 'مارس',    // Mars
                                    '04' => 'أفريل',   // Avril
                                    '05' => 'ماي',     // Mai
                                    '06' => 'جوان',    // Juin
                                    '07' => 'جويلية',  // Juillet
                                    '08' => 'أوت',     // Août
                                    '09' => 'سبتمبر',  // Septembre
                                    '10' => 'أكتوبر',  // Octobre
                                    '11' => 'نوفمبر',  // Novembre
                                    '12' => 'ديسمبر'   // Décembre
                                ];
                                    $monthParts = explode('-', $month);
                                    echo $monthNames[$monthParts[1]] . ' ' . $monthParts[0];
                                    ?>
                                </div>
                                
                                <?php foreach ($monthEvents as $event) : ?>
                                    <div class="tree-event">
                                        <div class="event-card">
                                            <div class="event-date">
                                                <i class="fas fa-calendar-day"></i>
                                                <?php 
                                                $date = new DateTime($event['event_date']);
                                                $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                                                echo $dayNames[$date->format('w')] . ' ' . $date->format('d') . ' ' . $monthNames[$date->format('m')] . ' ' . $date->format('Y');
                                                ?>
                                            </div>
                                            <div class="event-description">
                                                <?= nl2br(htmlspecialchars($event['description'])); ?>
                                            </div>
                                            <?php if (isset($event['professor_id']) && isset($professorNames[$event['professor_id']])) : ?>
                                                <div class="event-professor">
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                    <?= htmlspecialchars($professorNames[$event['professor_id']]); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                <h3 class="footer-title">بوابة التلميذ</h3>
                <p>منصة تعليمية مخصصة لمساعدة التلاميذ على متابعة دروسهم وأحداثهم المدرسية.</p>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul class="footer-links">
                    <li><a href="dashboard.php">لوحة التحكم</a></li>
                        <li><a href="cours.php"><i class="fas fa-book"></i> الدورس</a></li>
                        <li><a href="calendar_events.php"><i class="fas fa-calendar-alt"></i> الروزنامة</a></li>
                        <li><a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                <h3 class="footer-title">تواصل معنا</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> support@portail-etudiant.fr</li>
                        <li><i class="fas fa-phone"></i> +33 1 23 45 67 89</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> بوابة التلميذ. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animation pour les cartes d'événements
            const eventCards = document.querySelectorAll('.event-card');
            eventCards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('animate-fade-in');
                }, 100 * index);
            });
        });
    </script>
</body>
</html>