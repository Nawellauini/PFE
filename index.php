<?php
session_start();
include 'db_config.php';

// Vérifier si le professeur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$professeur_id = $_SESSION['id_professeur'];

// Récupérer le nom du professeur
$query = "SELECT nom FROM professeurs WHERE id_professeur = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $professeur_id);
$stmt->execute();
$result = $stmt->get_result();
$professeur = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مرحبًا بك في منصة المعلم، <?php echo htmlspecialchars($professeur['nom']); ?>!</title>

    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts (Tajawal) -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --primary-color: #1e6091;
            --primary-light: #2a7aad;
            --primary-dark: #0d4a77;
            --secondary-color: #2a9d8f;
            --accent-color: #e9c46a;
            --text-color: #264653;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
            color: var(--text-color);
            line-height: 1.6;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgdmlld0JveD0iMCAwIDYwIDYwIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiMxZTYwOTEiIGZpbGwtb3BhY2l0eT0iLjAzIj48cGF0aCBkPSJNMzYgMzRjMC0yLjIgMS44LTQgNC00czQgMS44IDQgNC0xLjggNC00IDQtNC0xLjgtNC00bTAtMTZjMC0yLjIgMS44LTQgNC00czQgMS44IDQgNC0xLjggNC00IDQtNC0xLjgtNC00bTE2IDE2YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0tMTYgMTZjMC0yLjIgMS44LTQgNC00czQgMS44IDQgNC0xLjggNC00IDQtNC0xLjgtNC00bTE2IDBjMC0yLjIgMS44LTQgNC00czQgMS44IDQgNC0xLjggNC00IDQtNC0xLjgtNC00bTAtMTZjMC0yLjIgMS44LTQgNC00czQgMS44IDQgNC0xLjggNC00IDQtNC0xLjgtNC00TTIwIDM0YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0wLTE2YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0xNiAwYzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0tOC04YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0wIDE2YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0wIDE2YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNG0wIDE2YzAtMi4yIDEuOC00IDQtNHM0IDEuOCA0IDQtMS44IDQtNCA0LTQtMS44LTQtNCIvPjwvZz48L2c+PC9zdmc+');
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .header-container {
            background-color: var(--primary-color);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiB2aWV3Qm94PSIwIDAgMTUwIDE1MCI+PGcgZmlsbD0iI2ZmZmZmZiIgZmlsbC1vcGFjaXR5PSIwLjA1Ij48Y2lyY2xlIGN4PSIxMCIgY3k9IjEwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMzAiIGN5PSIxMCIgcj0iNSIvPjxjaXJjbGUgY3g9IjUwIiBjeT0iMTAiIHI9IjUiLz48Y2lyY2xlIGN4PSI3MCIgY3k9IjEwIiByPSI1Ii8+PGNpcmNsZSBjeD0iOTAiIGN5PSIxMCIgcj0iNSIvPjxjaXJjbGUgY3g9IjExMCIgY3k9IjEwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMTMwIiBjeT0iMTAiIHI9IjUiLz48Y2lyY2xlIGN4PSIxMCIgY3k9IjMwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iNSIvPjxjaXJjbGUgY3g9IjUwIiBjeT0iMzAiIHI9IjUiLz48Y2lyY2xlIGN4PSI3MCIgY3k9IjMwIiByPSI1Ii8+PGNpcmNsZSBjeD0iOTAiIGN5PSIzMCIgcj0iNSIvPjxjaXJjbGUgY3g9IjExMCIgY3k9IjMwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMTMwIiBjeT0iMzAiIHI9IjUiLz48Y2lyY2xlIGN4PSIxMCIgY3k9IjUwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMzAiIGN5PSI1MCIgcj0iNSIvPjxjaXJjbGUgY3g9IjUwIiBjeT0iNTAiIHI9IjUiLz48Y2lyY2xlIGN4PSI3MCIgY3k9IjUwIiByPSI1Ii8+PGNpcmNsZSBjeD0iOTAiIGN5PSI1MCIgcj0iNSIvPjxjaXJjbGUgY3g9IjExMCIgY3k9IjUwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMTMwIiBjeT0iNTAiIHI9IjUiLz48Y2lyY2xlIGN4PSIxMCIgY3k9IjcwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMzAiIGN5PSI3MCIgcj0iNSIvPjxjaXJjbGUgY3g9IjUwIiBjeT0iNzAiIHI9IjUiLz48Y2lyY2xlIGN4PSI3MCIgY3k9IjcwIiByPSI1Ii8+PGNpcmNsZSBjeD0iOTAiIGN5PSI3MCIgcj0iNSIvPjxjaXJjbGUgY3g9IjExMCIgY3k9IjcwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMTMwIiBjeT0iNzAiIHI9IjUiLz48Y2lyY2xlIGN4PSIxMCIgY3k9IjkwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMzAiIGN5PSI5MCIgcj0iNSIvPjxjaXJjbGUgY3g9IjUwIiBjeT0iOTAiIHI9IjUiLz48Y2lyY2xlIGN4PSI3MCIgY3k9IjkwIiByPSI1Ii8+PGNpcmNsZSBjeD0iOTAiIGN5PSI5MCIgcj0iNSIvPjxjaXJjbGUgY3g9IjExMCIgY3k9IjkwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMTMwIiBjeT0iOTAiIHI9IjUiLz48Y2lyY2xlIGN4PSIxMCIgY3k9IjExMCIgcj0iNSIvPjxjaXJjbGUgY3g9IjMwIiBjeT0iMTEwIiByPSI1Ii8+PGNpcmNsZSBjeD0iNTAiIGN5PSIxMTAiIHI9IjUiLz48Y2lyY2xlIGN4PSI3MCIgY3k9IjExMCIgcj0iNSIvPjxjaXJjbGUgY3g9IjkwIiBjeT0iMTEwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMTEwIiBjeT0iMTEwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMTMwIiBjeT0iMTEwIiByPSI1Ii8+PGNpcmNsZSBjeD0iMTAiIGN5PSIxMzAiIHI9IjUiLz48Y2lyY2xlIGN4PSIzMCIgY3k9IjEzMCIgcj0iNSIvPjxjaXJjbGUgY3g9IjUwIiBjeT0iMTMwIiByPSI1Ii8+PGNpcmNsZSBjeD0iNzAiIGN5PSIxMzAiIHI9IjUiLz48Y2lyY2xlIGN4PSI5MCIgY3k9IjEzMCIgcj0iNSIvPjxjaXJjbGUgY3g9IjExMCIgY3k9IjEzMCIgcj0iNSIvPjxjaXJjbGUgY3g9IjEzMCIgY3k9IjEzMCIgcj0iNSIvPjwvZz48L3N2Zz4=');
            opacity: 0.1;
            z-index: 0;
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header-subtitle {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .menu-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .menu-category {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .menu-category:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .menu-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .menu-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100%;
            height: 3px;
            background-color: var(--accent-color);
        }

        .menu-header i {
            font-size: 24px;
            margin-left: 15px;
            width: 30px;
            text-align: center;
        }

        .menu-title {
            font-size: 18px;
            font-weight: 600;
        }

        .menu-content {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }

        .menu-content.active {
            max-height: 300px;
            padding: 15px 0;
        }

        .menu-item {
            padding: 0;
            list-style: none;
        }

        .menu-link {
            display: block;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
            border-right: 3px solid transparent;
        }

        .menu-link:hover {
            background-color: rgba(30, 96, 145, 0.08);
            border-right-color: var(--primary-color);
            padding-right: 25px;
        }

        .menu-link i {
            margin-left: 10px;
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }

        .logout-btn {
            display: block;
            background-color: #dc3545;
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(220, 53, 69, 0.2);
        }

        .logout-btn:hover {
            background-color: #c82333;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(220, 53, 69, 0.3);
        }

        .logout-btn i {
            margin-left: 10px;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .menu-category {
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }

        .menu-category:nth-child(1) { animation-delay: 0.1s; }
        .menu-category:nth-child(2) { animation-delay: 0.2s; }
        .menu-category:nth-child(3) { animation-delay: 0.3s; }
        .menu-category:nth-child(4) { animation-delay: 0.4s; }
        .menu-category:nth-child(5) { animation-delay: 0.5s; }
        .menu-category:nth-child(6) { animation-delay: 0.6s; }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
                margin: 20px auto;
            }
            
            .header-container {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .header-title {
                font-size: 24px;
            }
            
            .menu-container {
                grid-template-columns: 1fr;
            }
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: var(--text-light);
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-container">
    <h1 class="header-title">مرحبًا، بك في منصة المعلم <?php echo htmlspecialchars($professeur['nom']); ?> !</h1>
    <p class="header-subtitle">اختر ما ترغب في القيام به من الخيارات التالية</p>
    </div>

    <div class="menu-container">
        <!-- Gestion des Notes -->
        <div class="menu-category">
            <div class="menu-header">
                <i class="fas fa-book-open"></i>
                <h2 class="menu-title">إدارة النتائج</h2>
            </div>
            <div class="menu-content">
                <ul class="menu-item">
                <li><a href="dashboard_professeur.php" class="menu-link"><i class="fas fa-edit"></i> إدخال النتائج الدراسية</a></li>
                <li><a href="ajouter_cours.php" class="menu-link"><i class="fas fa-edit"></i> إضافة الدروس</a></li>
                <li><a href="liste_cours.php" class="menu-link"><i class="fas fa-edit"></i> استعراض الدروس</a></li>
            </div>
        </div>

        <!-- Gestion des Remarques -->
        <div class="menu-category">
            <div class="menu-header">
                <i class="fas fa-comments"></i>
                <h2 class="menu-title">إدارة الملاحظات</h2>
            </div>
            <div class="menu-content">
                <ul class="menu-item">
                    <li><a href="ajouter_remarque.php" class="menu-link"><i class="fas fa-plus"></i> إضافة ملاحظة</a></li>
                    <li><a href="consulter_remarques.php" class="menu-link"><i class="fas fa-eye"></i> عرض الملاحظات</a></li>
                </ul>
            </div>
        </div>

        <!-- Gestion des Classes et Élèves -->
        <div class="menu-category">
            <div class="menu-header">
                <i class="fas fa-users"></i>
                <h2 class="menu-title">إدارة الأقسام والتلاميذ</h2>
            </div>
            <div class="menu-content">
                <ul class="menu-item">
                <li><a href="selection_classe.php" class="menu-link"><i class="fas fa-chalkboard-teacher"></i> عرض الأقسام</a></li>
                <li><a href="consulterEleve.php" class="menu-link"><i class="fas fa-user-graduate"></i> عرض التلاميذ</a></li>
                <li><a href="envoyer_message.php" class="menu-link-comptable"><i class="fas fa-envelope"></i> إرسال رسالة إلى التلميذ</a></li>


                </ul>
            </div>
        </div>

        <!-- Gestion des Rapports -->
        <div class="menu-category">
            <div class="menu-header">
                <i class="fas fa-file-alt"></i>
                <h2 class="menu-title">إدارة الملاحظات</h2>
            </div>
            <div class="menu-content">
                <ul class="menu-item">
                <li><a href="ajouter_observation.php" class="menu-link"><i class="fas fa-plus-circle"></i> إضافة ملاحظة</a></li>
                </ul>
            </div>
        </div>

        <!-- Contenu Vidéo -->
        <div class="menu-category">
            <div class="menu-header">
                <i class="fas fa-video"></i>
                <h2 class="menu-title">إضافة فيديو جديد</h2>
            </div>
            <div class="menu-content">
                <ul class="menu-item">
                    <li><a href="ajouter_video.php" class="menu-link"><i class="fas fa-video"></i> إضافة فيديو</a></li>
                </ul>
            </div>
        </div>
        <!-- Gestion des Événements -->
        <div class="menu-category">
            <div class="menu-header">
                <i class="fas fa-calendar-alt"></i>
                <h2 class="menu-title">إدارة الأنشطة</h2>
            </div>
            <div class="menu-content">
                <ul class="menu-item">
                <li><a href="calendar_events.php" class="menu-link"><i class="fas fa-calendar-check"></i> الرزنامة</a></li>
                <li><a href="historique_prof.php" class="menu-link"><i class="fas fa-history"></i> الأرشيف</a></li>
                <li><a href="messages_reçus_professeur.php" class="menu-link-comptable"><i class="fas fa-comments"></i> رسائل التلاميذ</a></li>


                </ul>
            </div>
        </div>
    </div>

    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>

    <div class="footer">
    <p>&copy; <?= date('Y') ?> منصة المعلم. جميع الحقوق محفوظة.</p>
    </div>
</div>

<script>
    $(document).ready(function(){
        // Ouvrir tous les menus au chargement
        $(".menu-content").addClass("active");
        
        // Toggle des menus au clic
        $(".menu-header").click(function(){
            $(this).next(".menu-content").toggleClass("active");
        });
        
        // Effet de survol
        $(".menu-category").hover(
            function() {
                $(this).find(".menu-header").css("background-color", "var(--primary-dark)");
            },
            function() {
                $(this).find(".menu-header").css("background-color", "var(--primary-color)");
            }
        );
    });
</script>

</body>
</html>

<?php
$conn->close();
?>