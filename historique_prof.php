<?php


// Inclure la configuration pour la connexion à la base de données
require_once 'db_config.php';

session_start();

// Vérifier si le professeur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];

// Vérifier la connexion à la base de données
if (!$conn) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}

// Récupérer les informations du professeur
$queryProf = $conn->prepare("SELECT nom, prenom FROM professeurs WHERE id_professeur = ?");
$queryProf->bind_param("i", $id_professeur);
$queryProf->execute();
$profInfo = $queryProf->get_result()->fetch_assoc();
$nomProf = isset($profInfo) ? $profInfo['prenom'] . ' ' . $profInfo['nom'] : 'Professeur';
$queryProf->close();

// Récupérer les vidéos ajoutées par le professeur - CORRIGÉ sans date_ajout
$queryVideos = $conn->prepare("SELECT v.titre_video, v.url_video, v.matiere, c.nom_classe 
                              FROM videos v 
                              LEFT JOIN classes c ON v.id_classe = c.id_classe 
                              WHERE v.id_professeur = ?");
if (!$queryVideos) {
    die("Erreur dans la requête SQL (videos) : " . $conn->error);
}

$queryVideos->bind_param("i", $id_professeur);
$queryVideos->execute();
$resultVideos = $queryVideos->get_result();
$videos = $resultVideos->fetch_all(MYSQLI_ASSOC);
$queryVideos->close();

// Récupérer les événements créés par le professeur
$queryEvents = $conn->prepare("
    SELECT ce.event_date, ce.description, c.nom_classe 
    FROM calendar_events ce
    LEFT JOIN classes c ON ce.class = c.id_classe
    WHERE ce.professor_id = ? 
    ORDER BY ce.event_date DESC
");
if (!$queryEvents) {
    die("Erreur dans la requête SQL (calendar_events) : " . $conn->error);
}

$queryEvents->bind_param("i", $id_professeur);
$queryEvents->execute();
$events = $queryEvents->get_result()->fetch_all(MYSQLI_ASSOC);
$queryEvents->close();

// Récupérer les observations ajoutées par le professeur
$queryObservations = $conn->prepare("
    SELECT o.observation, o.date_observation, c.nom_classe 
    FROM observations o
    LEFT JOIN classes c ON o.classe_id = c.id_classe
    LEFT JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
    WHERE pc.id_professeur = ?
    ORDER BY o.date_observation DESC
");

if (!$queryObservations) {
    die("Erreur dans la requête SQL pour les observations : " . $conn->error);
}

$queryObservations->bind_param("i", $id_professeur);
$queryObservations->execute();
$resultObservations = $queryObservations->get_result();
$observations = $resultObservations->fetch_all(MYSQLI_ASSOC);
$queryObservations->close();
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأرشيف | الفضاء الخاص بالمعلم</title>
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

        /* Header & Navigation */
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

        /* Section Styles */
        .section {
            margin-bottom: 2.5rem;
            animation: fadeIn 0.5s ease forwards;
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1.2rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
            position: relative;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-left: 0.5rem;
            color: var(--primary-color);
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: 0;
            width: 60px;
            height: 2px;
            background-color: var(--primary-color);
        }

        /* Card Styles */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .card:before {
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

        .card-header {
            padding: 1.2rem;
            background-color: rgba(30, 96, 145, 0.05);
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            font-size: 0.9rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
        }

        .card-subtitle i {
            margin-left: 0.5rem;
            color: var(--accent-color);
        }

        .card-body {
            padding: 1.2rem;
            flex: 1;
        }

        .card-text {
            font-size: 1rem;
            color: var(--text-color);
            line-height: 1.6;
        }

        .card-footer {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            background-color: rgba(30, 96, 145, 0.02);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-meta {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .card-action {
            font-size: 0.9rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .card-action:hover {
            color: var(--primary-dark);
        }

        /* Video Card Styles */
        .video-card .card-body {
            padding: 0;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            overflow: hidden;
        }

        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            background-color: #000;
        }

        /* Badge pour la matière */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 50px;
            background-color: var(--accent-color);
            color: var(--text-color);
            margin-right: 0.5rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            border: 1px dashed var(--border-color);
        }

        .empty-state-icon {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .empty-state-text {
            font-size: 1.1rem;
            color: var(--text-light);
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

            .card-grid {
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
                <span>منصة المعلم</span>  
                    <i class="fas fa-graduation-cap"></i>
                </a>
                <ul class="nav-links">
                <li><a href="dashboardProf.php" class="active"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                <li><a href="classes.php"><i class="fas fa-users"></i> الأقسام</a></li>
                    <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> الرزنامة</a></li>
                    <li><a href="calendar_events.php"><i class="fas fa-plus-circle"></i> إضافة موعد </a></li>
                    <li><a href="historique.php" class="active"><i class="fas fa-history"></i> الأرشيف</a></li>
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
        <h1 class="page-title"><i class="fas fa-history"></i> الأرشيف</h1>
            
            <!-- Section Vidéos -->
            <section class="section">
            <h2 class="section-title"><i class="fas fa-video"></i> مقاطع الفيديو المضافة</h2>
                
                <?php if (empty($videos)) : ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-film"></i>
                        </div>
                        <p class="empty-state-text">لم تقم بإضافة أي فيديو بعد.</p>
                    </div>
                <?php else : ?>
                    <div class="card-grid">
                        <?php foreach ($videos as $video) : ?>
                            <div class="card video-card">
                                <div class="card-header">
                                    <h3 class="card-title"><?= htmlspecialchars($video['titre_video']); ?></h3>
                                    <div class="card-subtitle">
                                        <?php if (!empty($video['matiere'])) : ?>
                                            <span class="badge"><?= htmlspecialchars($video['matiere']); ?></span>
                                        <?php endif; ?>
                                        <i class="fas fa-users"></i>
                                        <span><?= htmlspecialchars($video['nom_classe'] ?? 'قسم غير محدد'); ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="video-container">
                                        <video controls>
                                            <source src="<?= htmlspecialchars($video['url_video']); ?>" type="video/mp4">
                                            متصفحك لا يدعم تشغيل الفيديو.
                                        </video>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Section Événements -->
            <section class="section">
                <h2 class="section-title"><i class="fas fa-calendar-check"></i> الأحداث المضافة</h2>
                
                <?php if (empty($events)) : ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <p class="empty-state-text">لم تقم بإضافة أي حدث بعد.</p>
                    </div>
                <?php else : ?>
                    <div class="card-grid">
                        <?php foreach ($events as $event) : ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><?= date('d/m/Y', strtotime($event['event_date'])); ?></h3>
                                    <div class="card-subtitle">
                                        <i class="fas fa-users"></i>
                                        <span><?= htmlspecialchars($event['nom_classe'] ?? 'القسم غير محدد'); ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?= nl2br(htmlspecialchars($event['description'])); ?></p>
                                </div>
                                <div class="card-footer">
                                    <span class="card-meta"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($event['event_date'])); ?></span>
                                    <a href="calendar.php" class="card-action">عرض في روزنامتي <i class="fas fa-arrow-left"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Section Observations -->
            <section class="section">
                <h2 class="section-title"><i class="fas fa-comment-dots"></i> الملاحظات المضافة</h2>
                
                <?php if (empty($observations)) : ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-clipboard"></i>
                        </div>
                        <p class="empty-state-text">لم تقم بإضافة أي ملاحظة بعد.</p>
                    </div>
                <?php else : ?>
                    <div class="card-grid">
                        <?php foreach ($observations as $observation) : ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><?= date('d/m/Y', strtotime($observation['date_observation'])); ?></h3>
                                    <div class="card-subtitle">
                                        <i class="fas fa-users"></i>
                                        <span><?= htmlspecialchars($observation['nom_classe'] ?? 'قسم غير محدد'); ?></span>

                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?= nl2br(htmlspecialchars($observation['observation'])); ?></p>
                                </div>
                                <div class="card-footer">
                                    <span class="card-meta"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($observation['date_observation'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
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
                        <li><a href="historique.php" class="active"><i class="fas fa-history"></i> الأرشيف</a></li>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Animation pour les sections
            const sections = document.querySelectorAll('.section');
            sections.forEach((section, index) => {
                section.style.animationDelay = `${index * 0.2}s`;
            });
            
            // Ajuster la hauteur des cartes pour qu'elles soient uniformes
            function adjustCardHeights() {
                const cardGrids = document.querySelectorAll('.card-grid');
                cardGrids.forEach(grid => {
                    const cards = grid.querySelectorAll('.card:not(.video-card)');
                    let maxHeight = 0;
                    
                    // Réinitialiser les hauteurs
                    cards.forEach(card => {
                        card.style.height = 'auto';
                        const height = card.offsetHeight;
                        maxHeight = Math.max(maxHeight, height);
                    });
                    
                    // Appliquer la hauteur maximale
                    cards.forEach(card => {
                        card.style.height = `${maxHeight}px`;
                    });
                });
            }
            
            // Exécuter l'ajustement au chargement et au redimensionnement
            adjustCardHeights();
            window.addEventListener('resize', adjustCardHeights);
        });
    </script>
</body>
</html>