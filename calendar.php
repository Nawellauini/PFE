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

// Récupérer le mois et l'année actuels ou ceux spécifiés dans l'URL
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Vérifier que le mois est valide (entre 1 et 12)
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Récupérer les événements du mois pour ce professeur
$firstDayOfMonth = "$year-$month-01";
$lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

// Vérifier si la table calendar_events existe
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'calendar_events'");
if ($checkTable->num_rows > 0) {
    $tableExists = true;
}

$events = [];
$eventsByDate = [];

if ($tableExists) {
    $queryEvents = $conn->prepare("SELECT ce.*, c.nom_classe 
                                  FROM calendar_events ce
                                  JOIN classes c ON ce.class = c.id_classe
                                  WHERE ce.professor_id = ? 
                                  AND ce.event_date BETWEEN ? AND ?
                                  ORDER BY ce.event_date ASC");
    
    if ($queryEvents) {
        $queryEvents->bind_param("iss", $id_professeur, $firstDayOfMonth, $lastDayOfMonth);
        if ($queryEvents->execute()) {
            $events = $queryEvents->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Organiser les événements par date
            foreach ($events as $event) {
                $date = $event['event_date'];
                if (!isset($eventsByDate[$date])) {
                    $eventsByDate[$date] = [];
                }
                $eventsByDate[$date][] = $event;
            }
        } else {
            // Gérer l'erreur d'exécution
            $error = $conn->error;
        }
    } else {
        // Gérer l'erreur de préparation
        $error = $conn->error;
    }
}

// Récupérer les classes du professeur pour le filtre
$classes = [];
$queryClasses = $conn->prepare("SELECT c.id_classe, c.nom_classe 
                               FROM professeurs_classes pc
                               JOIN classes c ON pc.id_classe = c.id_classe
                               WHERE pc.id_professeur = ?");
if ($queryClasses) {
    $queryClasses->bind_param("i", $id_professeur);
    if ($queryClasses->execute()) {
        $classes = $queryClasses->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Filtrer par classe si spécifié
$filteredClassId = isset($_GET['class']) ? intval($_GET['class']) : 0;
if ($filteredClassId > 0 && $tableExists) {
    $queryFilteredEvents = $conn->prepare("SELECT ce.*, c.nom_classe 
                                          FROM calendar_events ce
                                          JOIN classes c ON ce.class = c.id_classe
                                          WHERE ce.professor_id = ? 
                                          AND ce.class = ?
                                          AND ce.event_date BETWEEN ? AND ?
                                          ORDER BY ce.event_date ASC");
    if ($queryFilteredEvents) {
        $queryFilteredEvents->bind_param("iiss", $id_professeur, $filteredClassId, $firstDayOfMonth, $lastDayOfMonth);
        if ($queryFilteredEvents->execute()) {
            $events = $queryFilteredEvents->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Réorganiser les événements filtrés par date
            $eventsByDate = [];
            foreach ($events as $event) {
                $date = $event['event_date'];
                if (!isset($eventsByDate[$date])) {
                    $eventsByDate[$date] = [];
                }
                $eventsByDate[$date][] = $event;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الروزنامة | منصة المعلم</title>
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

        /* Calendar Styles */
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .calendar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .calendar-nav {
            display: flex;
            align-items: center;
        }

        .calendar-nav-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 5px;
        }

        .calendar-nav-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .calendar-filter {
            margin-bottom: 1.5rem;
            background-color: var(--card-bg);
            padding: 1rem;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .filter-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-label {
            margin-left: 1rem;
            font-weight: 600;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-left: 1rem;
            font-family: 'Tajawal', 'Amiri', serif;
        }

        .filter-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Tajawal', 'Amiri', serif;
        }

        .filter-btn:hover {
            background-color: var(--primary-dark);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 2rem;
        }

        .calendar-day-header {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            border-radius: 8px;
        }

        .calendar-day {
            background-color: var(--card-bg);
            min-height: 100px;
            border-radius: 8px;
            padding: 0.5rem;
            box-shadow: var(--shadow);
            position: relative;
        }

        .calendar-day.empty {
            background-color: rgba(255, 255, 255, 0.5);
            box-shadow: none;
        }

        .calendar-day.today {
            border: 2px solid var(--accent-color);
        }

        .calendar-day-number {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .calendar-day.today .calendar-day-number {
            color: var(--accent-color);
        }

        .calendar-event {
            background-color: rgba(30, 96, 145, 0.1);
            border-right: 3px solid var(--primary-color);
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .calendar-event:hover {
            background-color: rgba(30, 96, 145, 0.2);
            transform: translateY(-2px);
        }

        .calendar-event-class {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .calendar-event-desc {
            color: var(--text-light);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .add-event-btn {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 60px;
            height: 60px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .add-event-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
        }

        /* Event Modal */
        .event-modal {
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
        }

        .event-modal-content {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .event-modal-close {
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.3s ease;
        }

        .event-modal-close:hover {
            color: var(--error-color);
        }

        .event-modal-title {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .event-modal-info {
            margin-bottom: 1.5rem;
        }

        .event-modal-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.3rem;
        }

        .event-modal-value {
            color: var(--text-light);
            margin-bottom: 1rem;
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
        @media (max-width: 992px) {
            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
                font-size: 0.9rem;
            }
            
            .calendar-day {
                min-height: 80px;
                padding: 0.3rem;
            }
            
            .calendar-event {
                padding: 0.3rem;
                font-size: 0.8rem;
            }
        }

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
            
            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
                gap: 5px;
            }
            
            .calendar-day-header {
                padding: 0.5rem 0.3rem;
                font-size: 0.8rem;
            }
            
            .calendar-day {
                min-height: 60px;
                padding: 0.2rem;
            }
            
            .calendar-day-number {
                font-size: 0.9rem;
            }
            
            .calendar-event {
                padding: 0.2rem;
                font-size: 0.7rem;
                margin-bottom: 0.3rem;
            }
        }

        @media (max-width: 576px) {
            .calendar-grid {
                display: none; /* Cacher la grille sur mobile */
            }
            
            .calendar-list {
                display: block; /* Afficher la liste sur mobile */
            }
        }

        /* Calendar List View (for mobile) */
        .calendar-list {
            display: none; /* Caché par défaut, affiché sur mobile */
            margin-top: 1.5rem;
        }

        .calendar-list-day {
            background-color: var(--card-bg);
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .calendar-list-day-header {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1rem;
            font-weight: 600;
        }

        .calendar-list-day.today .calendar-list-day-header {
            background-color: var(--accent-color);
        }

        .calendar-list-events {
            padding: 1rem;
        }

        .calendar-list-event {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .calendar-list-event:last-child {
            border-bottom: none;
        }

        .calendar-list-event-class {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .calendar-list-event-desc {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        /* Message d'erreur */
        .error-message {
            background-color: rgba(231, 111, 81, 0.1);
            border-right: 4px solid var(--error-color);
            color: var(--error-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        
        .error-icon {
            margin-left: 1rem;
            font-size: 1.25rem;
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
                    <li><a href="classes.php"><i class="fas fa-users"></i> الدروس</a></li>
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
        <h1 class="page-title"><i class="fas fa-calendar-alt"></i> الروزنامة </h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message animate-fade-in">
                    <div class="error-content">
                        <strong>خطأ:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                    <div class="error-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!$tableExists): ?>
                <div class="error-message animate-fade-in">
                    <div class="error-content">
                    <strong>تنبيه:</strong> جدول الأنشطة غير موجود في قاعدة البيانات. يرجى التواصل مع مسؤول النظام.
                    </div>
                    <div class="error-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="calendar-header">
                <div class="calendar-title">
                    <?php
                  $monthNames = [
                    1 => 'جانفي', 2 => 'فيفري', 3 => 'مارس', 4 => 'أفريل',
                    5 => 'ماي', 6 => 'جوان', 7 => 'جويلية', 8 => 'أوت',
                    9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
                ];
                
                    echo $monthNames[$month] . ' ' . $year;
                    ?>
                </div>
                <div class="calendar-nav">
                    <a href="?month=<?= $month - 1 ?>&year=<?= $year ?>" class="calendar-nav-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="?month=<?= date('m') ?>&year=<?= date('Y') ?>" class="calendar-nav-btn">
                        <i class="fas fa-calendar-day"></i>
                    </a>
                    <a href="?month=<?= $month + 1 ?>&year=<?= $year ?>" class="calendar-nav-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </div>
            </div>
            
            <div class="calendar-filter">
                <form action="" method="GET" class="filter-form">
                <label for="class" class="filter-label">تصفية حسب القسْم:</label>
                    <select name="class" id="class" class="filter-select">
                    <option value="0">جميع الأقسام</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class['id_classe'] ?>" <?= $filteredClassId == $class['id_classe'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class['nom_classe']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="month" value="<?= $month ?>">
                    <input type="hidden" name="year" value="<?= $year ?>">
                    <button type="submit" class="filter-btn">تصفية</button>
                </form>
            </div>
            
            <div class="calendar-grid">
                <?php
                // Afficher les jours de la semaine
                $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                foreach ($dayNames as $dayName) {
                    echo "<div class='calendar-day-header'>$dayName</div>";
                }
                
                // Obtenir le premier jour du mois
                $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
                $numberDays = date('t', $firstDayOfMonth);
                $dateComponents = getdate($firstDayOfMonth);
                $dayOfWeek = $dateComponents['wday']; // 0 pour dimanche, 6 pour samedi
                
                // Remplir les cases vides avant le premier jour du mois
                for ($i = 0; $i < $dayOfWeek; $i++) {
                    echo "<div class='calendar-day empty'></div>";
                }
                
                // Remplir les jours du mois
                $currentDay = 1;
                $today = date('Y-m-d');
                
                while ($currentDay <= $numberDays) {
                    $date = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                    $isToday = ($date === $today) ? 'today' : '';
                    
                    echo "<div class='calendar-day $isToday'>";
                    echo "<div class='calendar-day-number'>$currentDay</div>";
                    
                    // Afficher les événements pour ce jour
                    if (isset($eventsByDate[$date])) {
                        foreach ($eventsByDate[$date] as $event) {
                            echo "<div class='calendar-event' onclick='showEventDetails(\"" . htmlspecialchars($event['description']) . "\", \"" . htmlspecialchars($event['nom_classe']) . "\", \"" . date('d/m/Y', strtotime($event['event_date'])) . "\")'>";
                            echo "<div class='calendar-event-class'>" . htmlspecialchars($event['nom_classe']) . "</div>";
                            echo "<div class='calendar-event-desc'>" . htmlspecialchars(substr($event['description'], 0, 30)) . (strlen($event['description']) > 30 ? '...' : '') . "</div>";
                            echo "</div>";
                        }
                    }
                    
                    echo "</div>";
                    
                    $currentDay++;
                }
                
                // Remplir les cases vides après le dernier jour du mois
                $remainingDays = 7 - (($dayOfWeek + $numberDays) % 7);
                if ($remainingDays < 7) {
                    for ($i = 0; $i < $remainingDays; $i++) {
                        echo "<div class='calendar-day empty'></div>";
                    }
                }
                ?>
            </div>
            
            <!-- Vue liste pour mobile -->
            <div class="calendar-list">
                <?php
                $currentDay = 1;
                while ($currentDay <= $numberDays) {
                    $date = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                    $isToday = ($date === $today) ? 'today' : '';
                    
                    if (isset($eventsByDate[$date])) {
                        echo "<div class='calendar-list-day $isToday'>";
                        echo "<div class='calendar-list-day-header'>" . $currentDay . " " . $monthNames[$month] . " " . $year . "</div>";
                        echo "<div class='calendar-list-events'>";
                        
                        foreach ($eventsByDate[$date] as $event) {
                            echo "<div class='calendar-list-event'>";
                            echo "<div class='calendar-list-event-class'>" . htmlspecialchars($event['nom_classe']) . "</div>";
                            echo "<div class='calendar-list-event-desc'>" . htmlspecialchars($event['description']) . "</div>";
                            echo "</div>";
                        }
                        
                        echo "</div>";
                        echo "</div>";
                    }
                    
                    $currentDay++;
                }
                ?>
            </div>
            
            <a href="calendar_events.php" class="add-event-btn">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </main>

    <!-- Modal pour afficher les détails d'un événement -->
    <div id="eventModal" class="event-modal">
        <div class="event-modal-content">
            <span class="event-modal-close" onclick="closeEventModal()">&times;</span>
            <h2 class="event-modal-title">تفاصيل الموعد</h2>
            <div class="event-modal-info">
            <div class="event-modal-label">القسم:</div>
                <div id="eventClass" class="event-modal-value"></div>
                
                
                <div class="event-modal-label">أرشيف:</div>
                <div id="eventDate" class="event-modal-value"></div>
                
                <div class="event-modal-label">الوصف:</div>
                <div id="eventDescription" class="event-modal-value"></div>
            </div>
        </div>
    </div>

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
            <p>&copy; <?= date('Y') ?> منصة المعلم. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <script>
        // Fonction pour afficher les détails d'un événement
        function showEventDetails(description, className, date) {
            document.getElementById('eventDescription').innerHTML = description;
            document.getElementById('eventClass').innerHTML = className;
            document.getElementById('eventDate').innerHTML = date;
            document.getElementById('eventModal').style.display = 'flex';
        }
        
        // Fonction pour fermer le modal
        function closeEventModal() {
            document.getElementById('eventModal').style.display = 'none';
        }
        
        // Fermer le modal si l'utilisateur clique en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('eventModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Animation pour les éléments
            const elements = document.querySelectorAll('.animate-fade-in');
            elements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 100 * index);
            });
            
            // Vérifier si on est sur mobile pour afficher la vue liste
            function checkMobileView() {
                if (window.innerWidth <= 576) {
                    document.querySelector('.calendar-grid').style.display = 'none';
                    document.querySelector('.calendar-list').style.display = 'block';
                } else {
                    document.querySelector('.calendar-grid').style.display = 'grid';
                    document.querySelector('.calendar-list').style.display = 'none';
                }
            }
            
            // Vérifier au chargement et au redimensionnement
            checkMobileView();
            window.addEventListener('resize', checkMobileView);
        });
    </script>
</body>
</html>

