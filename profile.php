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
$queryStudent = $conn->prepare("SELECT e.*, c.nom_classe 
                               FROM eleves e 
                               JOIN classes c ON e.id_classe = c.id_classe 
                               WHERE e.id_eleve = ?");
$queryStudent->bind_param("i", $id_eleve);
$queryStudent->execute();
$student = $queryStudent->get_result()->fetch_assoc();

$nom_eleve = $student['prenom'] . ' ' . $student['nom'];
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملفي الشخصي | الفضاء الخاص بالطالب</title>
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

        /* Profile Card */
        .profile-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease forwards;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2.5rem;
            text-align: center;
            position: relative;
        }

        .profile-header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background-color: var(--accent-color);
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .profile-class {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .profile-body {
            padding: 2.5rem;
        }

        .profile-info-list {
            list-style: none;
        }

        .profile-info-item {
            display: flex;
            align-items: center;
            padding: 1.2rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .profile-info-item:last-child {
            border-bottom: none;
        }

        .profile-info-item:hover {
            background-color: rgba(30, 96, 145, 0.05);
            transform: translateY(-2px);
        }

        .profile-info-icon {
            width: 50px;
            height: 50px;
            background-color: rgba(30, 96, 145, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 1.5rem;
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .profile-info-content {
            flex: 1;
        }

        .profile-info-label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.3rem;
        }

        .profile-info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
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

            .profile-header {
                padding: 1.5rem;
            }

            .profile-avatar {
                width: 120px;
                height: 120px;
            }

            .profile-name {
                font-size: 1.5rem;
            }

            .profile-body {
                padding: 1.5rem;
            }

            .profile-info-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
                margin-left: 1rem;
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
                        <li><a href="student_agenda.php"><i class="fas fa-calendar-alt"></i> الروزنامة</a></li>
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
            <h1 class="page-title"><i class="fas fa-user"></i> ملفي الشخصي</h1>
            
            <div class="profile-card">
                <div class="profile-header">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($nom_eleve) ?>&background=random&size=200" alt="صورة الطالب" class="profile-avatar">
                    <h2 class="profile-name"><?= htmlspecialchars($nom_eleve) ?></h2>
                    <div class="profile-class"><?= htmlspecialchars($student['nom_classe']) ?></div>
                </div>
                
                <div class="profile-body">
                    <ul class="profile-info-list">
                        <li class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div class="profile-info-content">
                            <div class="profile-info-label">رقم التلميذ</div>
                                <div class="profile-info-value"><?= htmlspecialchars($student['id_eleve']) ?></div>
                            </div>
                        </li>
                        
                        <li class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="profile-info-content">
                                <div class="profile-info-label">الاسم الكامل</div>
                                <div class="profile-info-value"><?= htmlspecialchars($student['prenom'] . ' ' . $student['nom']) ?></div>
                            </div>
                        </li>
                        
                        <li class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="profile-info-content">
                                <div class="profile-info-label">البريد الإلكتروني</div>
                                <div class="profile-info-value"><?= htmlspecialchars($student['email']) ?></div>
                            </div>
                        </li>
                        
                        <li class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="profile-info-content">
                            <div class="profile-info-label">القسم الدراسي</div>
                                <div class="profile-info-value"><?= htmlspecialchars($student['nom_classe']) ?></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title">بوابة الطالب</h3>
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
</body>
</html>