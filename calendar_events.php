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
$message = "";
$messageType = "";
$showSuccessModal = false; // Variable pour contrôler l'affichage du modal

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

// Vérifier si la table calendar_events existe
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'calendar_events'");
if ($checkTable->num_rows > 0) {
    $tableExists = true;
}

// Si la table n'existe pas, créer la table
if (!$tableExists) {
    $createTableQuery = "CREATE TABLE calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class INT NOT NULL,
        event_date DATE NOT NULL,
        description TEXT NOT NULL,
        professor_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTableQuery) === TRUE) {
        $tableExists = true;
        $message = "تم إنشاء جدول المواعيد بنجاح.";
        $messageType = "success";
    } else {
        $message = "خطأ في إنشاء جدول المواعيد: " . $conn->error;
        $messageType = "error";
    }
}

// Récupérer les classes associées au professeur
$queryClasses = $conn->prepare("SELECT c.id_classe, c.nom_classe 
                               FROM professeurs_classes pc
                               JOIN classes c ON pc.id_classe = c.id_classe
                               WHERE pc.id_professeur = ?");
$queryClasses->bind_param("i", $id_professeur);
$queryClasses->execute();
$classes = $queryClasses->get_result()->fetch_all(MYSQLI_ASSOC);

// Récupérer la classe sélectionnée depuis l'URL si elle existe
$selectedClassId = isset($_GET['class']) ? intval($_GET['class']) : 0;

// Variables pour stocker les informations de l'événement ajouté
$addedEventClass = "";
$addedEventDate = "";
$addedEventDescription = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST" && $tableExists) {
    $class_id = $_POST['class_id'];
    $event_date = $_POST['event_date'];
    $description = trim($_POST['description']);

    if (!empty($class_id) && !empty($event_date) && !empty($description)) {
        $stmt = $conn->prepare("INSERT INTO calendar_events (`class`, event_date, description, professor_id) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $class_id, $event_date, $description, $id_professeur);
        
        if ($stmt->execute()) {
            $message = "تم إضافة الموعد بنجاح!";
            $messageType = "success";
            $showSuccessModal = true; // Activer l'affichage du modal
            
            // Récupérer le nom de la classe pour l'affichage dans le modal
            $classQuery = $conn->prepare("SELECT nom_classe FROM classes WHERE id_classe = ?");
            $classQuery->bind_param("i", $class_id);
            $classQuery->execute();
            $classResult = $classQuery->get_result();
            if ($classRow = $classResult->fetch_assoc()) {
                $addedEventClass = $classRow['nom_classe'];
            }
            
            // Stocker les informations de l'événement pour le modal
            $addedEventDate = date('d/m/Y', strtotime($event_date));
            $addedEventDescription = $description;
        } else {
            $message = "خطأ في إضافة الموعد: " . $conn->error;

            $messageType = "error";
        }
    } else {
        $message = "يرجى ملء جميع البيانات.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة موعد | الفضاء الخاص بالمعلم</title>
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

        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
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

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        /* Tree Structure */
        .tree-container {
            margin: 20px 0;
            padding: 20px;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.7);
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
        }

        .tree-item:last-child {
            border-right: none;
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

        .tree-item-content {
            position: relative;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-right: 3px solid var(--primary-color);
        }

        .tree-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: #fff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            font-family: 'Tajawal', 'Amiri', serif;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30, 96, 145, 0.2);
            outline: none;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.85rem 1.5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: 'Tajawal', 'Amiri', serif;
        }

        .btn-primary {
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 96, 145, 0.3);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            border-right: 4px solid;
            display: flex;
            align-items: center;
            animation: slideIn 0.5s ease forwards;
        }

        @keyframes slideIn {
            from { transform: translateX(50px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .alert-icon {
            margin-left: 1rem;
            font-size: 1.25rem;
        }

        .alert-success {
            background-color: rgba(42, 157, 143, 0.1);
            border-right-color: var(--success-color);
            color: var(--success-color);
        }

        .alert-error {
            background-color: rgba(231, 111, 81, 0.1);
            border-right-color: var(--error-color);
            color: var(--error-color);
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

            .card {
                padding: 1.5rem;
            }
            
            .tree-item {
                padding: 10px 20px 10px 0;
            }
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
        
        /* Custom Form Elements */
        .form-icon {
            position: absolute;
            top: 40px;
            left: 15px;
            color: var(--text-light);
        }
        
        .has-icon .form-control {
            padding-left: 40px;
        }
        
        /* Calendar Styling */
        input[type="date"] {
            position: relative;
            padding-right: 40px;
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator {
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .date-icon {
            position: absolute;
            top: 40px;
            right: 15px;
            color: var(--text-light);
            pointer-events: none;
        }

        /* Modal Styles */
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
            animation: fadeIn 0.3s ease forwards;
        }

        .modal-content {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
            animation: scaleIn 0.3s ease forwards;
        }

        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            background-color: var(--success-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .modal-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }

        .modal-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: white;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-info {
            margin-bottom: 1.5rem;
        }

        .modal-info-title {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .modal-info-content {
            color: var(--text-light);
            background-color: rgba(42, 157, 143, 0.05);
            padding: 0.75rem;
            border-radius: 8px;
            border-right: 3px solid var(--success-color);
        }

        .modal-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .modal-btn {
            flex: 1;
            margin: 0 0.5rem;
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: var(--secondary-dark);
            border-color: var(--secondary-dark);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .btn-outline:hover {
            background-color: var(--bg-color);
            color: var(--primary-color);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            left: 15px;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .modal-close:hover {
            transform: rotate(90deg);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="dashboardProf.php" class="logo">
                <span>الفضاء الخاص بالمعلم</span>
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
        <h1 class="page-title"><i class="fas fa-calendar-plus"></i> إضافة موعد إلى  الرزنامة</h1>
            
            <div class="tree-container animate-fade-in">
                <div class="tree">
                    <div class="tree-item">
                        <div class="tree-item-content">
                        <div class="tree-title"><i class="fas fa-info-circle"></i> تفاصيل الموعد</div>
                            
                            <?php if (!$tableExists): ?>
                                <div class="alert alert-error">
                                    <div class="alert-content">
                                    <strong>تنبيه:</strong> لا يوجد جدول للمواعيد في قاعدة البيانات. يُرجى التواصل مع مسؤول النظام.

                                    </div>
                                    <div class="alert-icon">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($message) && !$showSuccessModal): ?>
                                <div class="alert alert-<?= $messageType ?>">
                                    <div class="alert-content">
                                        <?= htmlspecialchars($message) ?>
                                    </div>
                                    <div class="alert-icon">
                                        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="tree-item">
                                    <div class="tree-item-content">
                                    <div class="tree-title"><i class="fas fa-users"></i> اختيار القسْم</div>
                                        <div class="form-group">
                                        <label for="class_id" class="form-label">القسْم</label>
                                            <select id="class_id" name="class_id" class="form-control" required>
                                            <option value="">اختر القسْم</option>
                                                <?php foreach ($classes as $classe): ?>
                                                    <option value="<?= htmlspecialchars($classe['id_classe']); ?>" <?= ($selectedClassId == $classe['id_classe']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($classe['nom_classe']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="tree-item">
                                    <div class="tree-item-content">
                                    <div class="tree-title"><i class="fas fa-calendar"></i> تاريخ الموعِد</div>

                                        <div class="form-group">
                                        <label for="event_date" class="form-label">تاريخ الموعِد</label>
                                            <div class="date-icon"><i class="fas fa-calendar-alt"></i></div>
                                            <input type="date" id="event_date" name="event_date" class="form-control" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="tree-item">
                                    <div class="tree-item-content">
                                    <div class="tree-title"><i class="fas fa-align-left"></i> وصف الموعِد</div>
                                        <div class="form-group has-icon">
                                        <label for="description" class="form-label">وصف الموعِد</label>
                                            <textarea id="description" name="description" class="form-control" rows="4" placeholder="قم بوصف الحدث بالتفصيل..." required></textarea>
                                            <div class="form-icon"><i class="fas fa-edit"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-calendar-plus"></i> إضافة الموعد
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de succès -->
    <div id="successModal" class="modal" <?= $showSuccessModal ? 'style="display: flex;"' : '' ?>>
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <div class="modal-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="modal-title">تمت الإضافة بنجاح!</h2>
                <p class="modal-subtitle">تم إضافة الموعد إلى الروزنامة بنجاح</p>
            </div>
            <div class="modal-body">
                <div class="modal-info">
                <h3 class="modal-info-title"><i class="fas fa-users"></i> القسم</h3>
                    <div class="modal-info-content"><?= htmlspecialchars($addedEventClass) ?></div>
                </div>
                
                <div class="modal-info">
                <h3 class="modal-info-title"><i class="fas fa-calendar-day"></i> الأرشيف</h3>
                    <div class="modal-info-content"><?= htmlspecialchars($addedEventDate) ?></div>
                </div>
                
                <div class="modal-info">
                    <h3 class="modal-info-title"><i class="fas fa-align-left"></i> الوصف</h3>
                    <div class="modal-info-content"><?= htmlspecialchars($addedEventDescription) ?></div>
                </div>
                
                <div class="modal-actions">
                    <a href="calendar.php" class="btn btn-success modal-btn">
                    <i class="fas fa-calendar-alt"></i> عرض الروزنامة
                    </a>
                    <button onclick="closeModal()" class="btn btn-outline modal-btn">
                    <i class="fas fa-plus-circle"></i> إضافة موعد آخر
                    </button>
                </div>
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
        // Afficher la date du jour par défaut dans le champ date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('event_date').value = today;
            
            // Animation pour les éléments
            const elements = document.querySelectorAll('.animate-fade-in');
            elements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 100 * index);
            });
            
            // Faire disparaître les alertes après 5 secondes
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(() => {
                    alerts.forEach(alert => {
                        alert.style.opacity = '0';
                        alert.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => {
                            alert.style.display = 'none';
                        }, 500);
                    });
                }, 5000);
            }
        });
        
        // Fonction pour fermer le modal
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
            // Réinitialiser le formulaire
            document.querySelector('form').reset();
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('event_date').value = today;
        }
    </script>
</body>
</html>