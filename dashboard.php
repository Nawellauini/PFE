<?php

// Inclure la configuration de la base de données
require_once 'db_config.php';

// Démarrer la session
session_start();

// Vérifier si l'élève est connecté
if (!isset($_SESSION['id_eleve'])) {
    header("Location: login.php");
    exit();
}

$id_eleve = $_SESSION['id_eleve'];

// Récupérer les informations de l'élève
$queryStudent = "SELECT e.*, c.nom_classe 
                FROM eleves e 
                JOIN classes c ON e.id_classe = c.id_classe 
                WHERE e.id_eleve = ?";
$stmt = $conn->prepare($queryStudent);
if (!$stmt) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt->bind_param("i", $id_eleve);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$nom_eleve = $student['prenom'] . ' ' . $student['nom'];
$nom_classe = $student['nom_classe'];
$id_classe = $student['id_classe'];

// Récupérer les statistiques
// 1. Nombre total de matières pour l'élève
$querySubjects = "SELECT COUNT(*) as total FROM matieres WHERE classe_id = ?";
$stmt = $conn->prepare($querySubjects);
if (!$stmt) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt->bind_param("i", $id_classe);
$stmt->execute();
$totalSubjects = $stmt->get_result()->fetch_assoc()['total'];

// 2. Récupérer les dernières notes
$queryNotes = "SELECT n.*, m.nom as matiere_nom, p.nom as prof_nom, p.prenom as prof_prenom 
              FROM notes n 
              JOIN matieres m ON n.matiere_id = m.matiere_id
              JOIN professeurs p ON m.professeur_id = p.id_professeur
              WHERE n.id_eleve = ?
              ORDER BY n.id DESC LIMIT 5";
$stmt = $conn->prepare($queryNotes);
if (!$stmt) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt->bind_param("i", $id_eleve);
$stmt->execute();
$notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Récupérer les dernières remarques
$queryRemarks = "SELECT r.*, d.nom as domaine_nom 
                FROM remarques r 
                JOIN domaines d ON r.domaine_id = d.id
                WHERE r.eleve_id = ?
                ORDER BY r.date_remarque DESC LIMIT 3";
$stmt = $conn->prepare($queryRemarks);
if (!$stmt) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt->bind_param("i", $id_eleve);
$stmt->execute();
$remarks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم | الفضاء الخاص بالتلميذ</title>
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

        /* Dashboard Styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
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

        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            background-color: rgba(30, 96, 145, 0.1);
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 1rem;
        }

        .welcome-card {
            background-color: var(--primary-color);
            color: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .welcome-card:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background-color: var(--accent-color);
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .welcome-text {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .welcome-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-student {
            display: flex;
            align-items: center;
        }

        .welcome-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-left: 15px;
            object-fit: cover;
            border: 3px solid white;
        }

        .welcome-name {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .welcome-class {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .welcome-date {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
            position: relative;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: 0;
            width: 50px;
            height: 2px;
            background-color: var(--accent-color);
        }

        .tree-container {
            margin: 20px 0;
        }

        .tree {
            position: relative;
            padding-right: 20px;
        }

        .tree-item {
            position: relative;
            padding: 10px 30px 10px 0;
            border-right: 2px solid var(--tree-line-color);
            margin-right: 20px;
            margin-bottom: 15px;
        }

        .tree-item:last-child {
            border-right: none;
            margin-bottom: 0;
        }

        .tree-item:before {
            content: '';
            position: absolute;
            top: 20px;
            right: 0;
            width: 20px;
            height: 2px;
            background-color: var(--tree-line-color);
        }

        .note-card, .remark-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .note-card {
            border-right: 4px solid var(--secondary-color);
        }

        .remark-card {
            border-right: 4px solid var(--primary-color);
        }

        .note-card:hover, .remark-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .note-title, .remark-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .note-title {
            color: var(--secondary-color);
        }

        .remark-title {
            color: var(--primary-color);
        }

        .note-title i, .remark-title i {
            margin-left: 8px;
        }

        .note-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 10px 0;
            padding: 5px 10px;
            background-color: rgba(42, 157, 143, 0.1);
            border-radius: 4px;
            display: inline-block;
        }

        .note-professor, .remark-professor {
            font-size: 0.9rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .note-professor i, .remark-professor i {
            margin-left: 8px;
            color: var(--secondary-color);
        }

        .note-date, .remark-date {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 5px;
        }

        .remark-text {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--text-color);
            margin-bottom: 10px;
            padding-right: 10px;
            border-right: 2px solid var(--accent-color);
        }

        .no-data {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            text-align: center;
            font-size: 1.2rem;
            color: var(--text-light);
        }

        .no-data i {
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

            .welcome-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .welcome-date {
                margin-top: 1rem;
            }

            .tree-item {
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
            <div class="welcome-card animate-fade-in">
            <div class="welcome-title">مرحباً بك في الفضاء الخاص بالتلميذ!</div>
                <div class="welcome-text">هنا يمكنك متابعة دروسك ونتائجك وملاحظات المعلمين.</div>
                <div class="welcome-info">
                    <div class="welcome-student">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($nom_eleve) ?>&background=random" alt="صورة الطالب" class="welcome-avatar">
                        <div>
                            <div class="welcome-name"><?= htmlspecialchars($nom_eleve) ?></div>
                            <div class="welcome-class"><?= htmlspecialchars($nom_classe) ?></div>
                        </div>
                    </div>
                    <div class="welcome-date">
                        <i class="fas fa-calendar"></i>
                        <?php 
                        $date = new DateTime();
                        $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
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
                        
                        echo $dayNames[$date->format('w')] . ' ' . $date->format('d') . ' ' . $monthNames[$date->format('m')] . ' ' . $date->format('Y');
                        ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid animate-fade-in">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-value"><?= $totalSubjects ?></div>
                    <div class="stat-label">المواد الدراسية</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-value"><?= count($notes) ?></div>
                    <div class="stat-label">آخر النتائج</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?= htmlspecialchars($nom_classe) ?></div>
                    <div class="stat-label">القسم الدراسي</div>
                </div>
            </div>

            <h2 class="section-title"><i class="fas fa-star"></i> آخر النتائج</h2>
            
            <div class="tree-container animate-fade-in">
                <div class="tree">
                    <?php if (empty($notes)) : ?>
                        <div class="no-data">
                            <i class="fas fa-chart-line"></i>
                            <p>لا توجد نتائج حديثة</p>
                        </div>
                    <?php else : ?>
                        <?php foreach ($notes as $note) : ?>
                            <div class="tree-item">
                                <div class="note-card">
                                    <div class="note-title">
                                        <i class="fas fa-book"></i>
                                        <?= htmlspecialchars($note['matiere_nom']); ?>
                                    </div>
                                    <div class="note-value">
                                        <?= htmlspecialchars($note['note']); ?>
                                    </div>
                                    <div class="note-professor">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <?= htmlspecialchars($note['prof_prenom'] . ' ' . $note['prof_nom']); ?>
                                    </div>
                                    <div class="note-date">
                                        <i class="fas fa-clock"></i>
                                        القسم: <?= htmlspecialchars($note['trimestre']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <h2 class="section-title"><i class="fas fa-comment-alt"></i> آخر الملاحظات</h2>
            
            <div class="tree-container animate-fade-in">
                <div class="tree">
                    <?php if (empty($remarks)) : ?>
                        <div class="no-data">
                            <i class="fas fa-comments"></i>
                            <p>لا توجد ملاحظات حديثة</p>
                        </div>
                    <?php else : ?>
                        <?php foreach ($remarks as $remark) : ?>
                            <div class="tree-item">
                                <div class="remark-card">
                                    <div class="remark-title">
                                        <i class="fas fa-comment"></i>
                                        <?= htmlspecialchars($remark['domaine_nom']); ?>
                                    </div>
                                    <div class="remark-text">
                                        <?= nl2br(htmlspecialchars($remark['remarque'])); ?>
                                    </div>
                                    <div class="remark-date">
                                        <i class="fas fa-calendar-day"></i>
                                        <?php 
                                        $remarkDate = new DateTime($remark['date_remarque']);
                                        echo $remarkDate->format('d/m/Y');
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                <h3 class="footer-title">بوابة التلميذ</h3>
                <p>منصة تعليمية مخصصة لمساعدة التلاميذ على متابعة دروسهم ونتائجهم.</p>
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
            // Animation pour les cartes
            const cards = document.querySelectorAll('.stat-card, .note-card, .remark-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('animate-fade-in');
                }, 100 * index);
            });
        });
    </script>
</body>
</html>