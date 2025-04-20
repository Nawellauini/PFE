<?php
// تضمين ملف التكوين للاتصال بقاعدة البيانات
require_once 'db_config.php';


// بدء الجلسة
session_start();

// التحقق مما إذا كان المدرس متصلاً
if (!isset($_SESSION['id_professeur'])) {
    die("تم رفض الوصول. يرجى تسجيل الدخول.");
}

$id_professeur = $_SESSION['id_professeur'];

// استرجاع الفصول المرتبطة بالمدرس
$queryClasses = $conn->prepare("SELECT c.id_classe, c.nom_classe 
                               FROM professeurs_classes pc
                               JOIN classes c ON pc.id_classe = c.id_classe
                               WHERE pc.id_professeur = ?");
$queryClasses->bind_param("i", $id_professeur);
$queryClasses->execute();
$classes = $queryClasses->get_result()->fetch_all(MYSQLI_ASSOC);

// استرجاع المادة من جدول المدرسين
$queryMatiere = $conn->prepare("SELECT m.matiere_id, m.nom 
                               FROM matieres m 
                               JOIN professeurs p ON m.matiere_id = p.matiere_id 
                               WHERE p.id_professeur = ?");
$queryMatiere->bind_param("i", $id_professeur);
$queryMatiere->execute();
$matieres = $queryMatiere->get_result()->fetch_all(MYSQLI_ASSOC);

// استرجاع جميع المواضيع للعرض في الهيكل الشجري
$queryAllThemes = $conn->query("SELECT id_theme, nom_theme FROM themes ORDER BY nom_theme");
if ($queryAllThemes) {
    $allThemes = $queryAllThemes->fetch_all(MYSQLI_ASSOC);
} else {
    $allThemes = [];
}

// التحقق مما إذا تم إرسال النموذج
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titre = $_POST['titre_video'];
    $nom_theme = $_POST['nom_theme']; // اسم الموضوع الذي أدخله المستخدم
    $id_classe = $_POST['id_classe'];
    $matiere = $_POST['matiere'];

    // استرجاع معرف الموضوع من الاسم
    $queryTheme = $conn->prepare("SELECT id_theme FROM themes WHERE nom_theme = ?");
    if ($queryTheme) {
        $queryTheme->bind_param("s", $nom_theme); // "s" لأن اسم الموضوع هو سلسلة
        $queryTheme->execute();
        $theme_result = $queryTheme->get_result();

        if ($theme_result->num_rows > 0) {
            $theme_data = $theme_result->fetch_assoc();
            $id_theme = $theme_data['id_theme']; // استرجاع معرف الموضوع

            // التحقق وإنشاء مجلد التحميلات إذا لم يكن موجودًا
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // معالجة ملف الفيديو
            $target_file = $target_dir . basename($_FILES["video"]["name"]);
            
            // التحقق من حجم الملف (100 ميجابايت)
            $maxFileSize = 100 * 1024 * 1024; // 100MB in bytes
            if ($_FILES["video"]["size"] > $maxFileSize) {
                $error_message = "حجم الملف كبير جداً. الحد الأقصى هو 100 ميجابايت.";
            } else {
                move_uploaded_file($_FILES["video"]["tmp_name"], $target_file);
            }

            // إدراج الفيديو في قاعدة البيانات
            $stmt = $conn->prepare("INSERT INTO videos (titre_video, url_video, id_professeur, id_classe, id_theme, matiere) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssisii", $titre, $target_file, $id_professeur, $id_classe, $id_theme, $matiere);
                
                if ($stmt->execute()) {
                    $success_message = "تمت إضافة الفيديو بنجاح!";
                } else {
                    $error_message = "خطأ عند إضافة الفيديو: " . $stmt->error;
                }
            } else {
                $error_message = "خطأ في إعداد الاستعلام: " . $conn->error;
            }
        } else {
            $error_message = "لم يتم العثور على الموضوع، يرجى التحقق من اسم الموضوع.";
        }
    } else {
        $error_message = "خطأ في إعداد الاستعلام: " . $conn->error;
    }
}

// استرجاع مقاطع الفيديو التي أضافها هذا المدرس بالفعل
$queryVideos = $conn->prepare("SELECT v.id_video, v.titre_video, c.nom_classe, t.nom_theme, m.nom as nom_matiere
                              FROM videos v
                              JOIN classes c ON v.id_classe = c.id_classe
                              JOIN themes t ON v.id_theme = t.id_theme
                              JOIN matieres m ON v.matiere = m.matiere_id
                              WHERE v.id_professeur = ?
                              ORDER BY v.date_ajout DESC");
if ($queryVideos) {
    $queryVideos->bind_param("i", $id_professeur);
    $queryVideos->execute();
    $videos = $queryVideos->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $videos = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة مقاطع الفيديو التعليمية</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4CAF50;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --info-color: #2196F3;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --sidebar-width: 280px;
            --header-height: 60px;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background-color: white;
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 100;
        }

        .header-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-right: 20px;
            color: var(--dark-color);
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--dark-color);
            cursor: pointer;
        }

        .user-profile {
            margin-right: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Main Container */
        .container {
            display: flex;
            margin-top: var(--header-height);
            min-height: calc(100vh - var(--header-height));
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
            position: fixed;
            top: var(--header-height);
            right: 0;
            height: calc(100vh - var(--header-height));
            overflow-y: auto;
            transition: transform var(--transition-speed);
            z-index: 99;
        }

        .sidebar-collapsed {
            transform: translateX(100%);
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }

        .sidebar-title {
            font-size: 1.2rem;
            color: var(--dark-color);
            font-weight: 600;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--gray-color);
            text-decoration: none;
            transition: all var(--transition-speed);
            border-right: 3px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background-color: #f8f9fa;
            color: var(--primary-color);
            border-right-color: var(--primary-color);
        }

        .nav-link i {
            margin-left: 10px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        /* Tree View */
        .tree-view {
            padding: 0 20px;
        }

        .tree-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }

        .tree-title i {
            margin-left: 8px;
        }

        .tree-list {
            list-style: none;
            margin-right: 10px;
        }

        .tree-item {
            margin-bottom: 5px;
            position: relative;
        }

        .tree-item::before {
            content: "";
            position: absolute;
            right: -15px;
            top: 0;
            height: 100%;
            border-right: 1px dashed #ccc;
        }

        .tree-item:last-child::before {
            height: 50%;
        }

        .tree-item::after {
            content: "";
            position: absolute;
            right: -15px;
            top: 12px;
            width: 10px;
            border-top: 1px dashed #ccc;
        }

        .tree-toggle {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray-color);
            font-size: 0.8rem;
            margin-left: 5px;
        }

        .tree-link {
            display: flex;
            align-items: center;
            color: var(--gray-color);
            text-decoration: none;
            padding: 5px 0;
            font-size: 0.9rem;
            transition: color var(--transition-speed);
        }

        .tree-link:hover {
            color: var(--primary-color);
        }

        .tree-link i {
            margin-left: 5px;
            font-size: 0.9rem;
        }

        .tree-children {
            margin-right: 20px;
            display: none;
        }

        .tree-children.show {
            display: block;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
            margin-right: var(--sidebar-width);
            transition: margin-right var(--transition-speed);
        }

        .main-content-expanded {
            margin-right: 0;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-left: 10px;
            color: var(--primary-color);
        }

        /* Cards */
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-left: 8px;
            color: var(--primary-color);
        }

        .card-body {
            padding: 20px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color var(--transition-speed);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 12px center;
            background-size: 16px;
        }

        .form-file {
            position: relative;
        }

        .form-file-input {
            position: absolute;
            right: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .form-file-label {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            background-color: #f8f9fa;
            border: 1px dashed #ddd;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all var(--transition-speed);
        }

        .form-file-label:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
        }

        .form-file-icon {
            margin-left: 8px;
            color: var(--gray-color);
        }

        .form-file-text {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 10px 20px;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: var(--border-radius);
            transition: all var(--transition-speed);
            cursor: pointer;
        }

        .btn-primary {
            color: white;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: var(--border-radius);
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table-actions {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
        }

        .btn-info {
            color: white;
            background-color: var(--info-color);
            border-color: var(--info-color);
        }

        .btn-danger {
            color: white;
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-info:hover, .btn-danger:hover {
            opacity: 0.9;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(100%);
            }
            
            .sidebar-expanded {
                transform: translateX(0);
            }
            
            .main-content {
                margin-right: 0;
            }
        }

        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-title {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 1.3rem;
            }
            
            .card-title {
                font-size: 1rem;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes slideInRight {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .slide-in-right {
            animation: slideInRight 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-title">المنصة التعليمية</div>
        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <span>أستاذ</span>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">التنقل</div>
            </div>
            <ul class="nav-menu">
               
                <li class="nav-item">
                    <a href="ajouter_video.php" class="nav-link active">
                        <i class="fas fa-video"></i>
                        <span>إدارة الفيديوهات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>تسجيل الخروج</span>
                    </a>
                </li>
            </ul>

            <!-- Tree View -->
         
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <h1 class="page-title slide-in-right">
                <i class="fas fa-video"></i>
                إدارة مقاطع الفيديو التعليمية
            </h1>

            <!-- Form Card -->
            <div class="card fade-in">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-plus-circle"></i>
                        إضافة فيديو جديد
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $success_message; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= $error_message; ?>
                    </div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data" class="video-form">
                        <div class="form-group">
                            <label for="titre_video">عنوان الفيديو:</label>
                            <input type="text" id="titre_video" name="titre_video" required>
                        </div>

                        <div class="form-group">
                            <label for="video">اختر ملف الفيديو:</label>
                            <input type="file" id="video" name="video" accept="video/*" required>
                            <small class="form-text">يمكنك تحميل ملفات الفيديو حتى 100 ميجابايت</small>
                        </div>

                        <div class="form-group">
                        <label for="id_classe" class="form-label">القسم</label>
                            <select id="id_classe" name="id_classe" class="form-select" required>
                            <option value="">اختر القسم</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?= $classe['id_classe']; ?>"><?= $classe['nom_classe']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="matiere" class="form-label">المادة</label>
                            <select id="matiere" name="matiere" class="form-select" required>
                            <option value="">اختر المادة</option>
                                <?php foreach ($matieres as $matiere): ?>
                                    <option value="<?= $matiere['matiere_id']; ?>"><?= $matiere['nom']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="nom_theme" class="form-label">اسم الموضوع</label>
                            <input type="text" id="nom_theme" name="nom_theme" class="form-control" placeholder="أدخل اسم الموضوع" required>
                            <small class="text-muted">أدخل موضوعًا موجودًا أو قم بإنشاء موضوع جديد.</small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-plus-circle"></i> إضافة الفيديو
                        </button>
                    </form>
                </div>
            </div>

            <!-- Videos List Card -->
           
        </main>
    </div>

    <script>
        // Toggle Sidebar
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-expanded');
            mainContent.classList.toggle('main-content-expanded');
        });

        // File Input
        const fileInput = document.getElementById('video');
        const fileName = document.getElementById('file-name');

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                fileName.textContent = e.target.files[0].name;
            } else {
                fileName.textContent = 'اختر ملف فيديو';
            }
        });

        // Tree View Toggle
        const treeToggles = document.querySelectorAll('.tree-toggle');

        treeToggles.forEach(toggle => {
            toggle.addEventListener('click', () => {
                const targetId = toggle.getAttribute('data-toggle');
                const targetElement = document.getElementById(targetId);
                
                if (targetElement.classList.contains('show')) {
                    targetElement.classList.remove('show');
                    toggle.innerHTML = '<i class="fas fa-plus-square"></i>';
                } else {
                    targetElement.classList.add('show');
                    toggle.innerHTML = '<i class="fas fa-minus-square"></i>';
                }
            });
        });

        // Responsive Sidebar
        function handleResize() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('sidebar-expanded');
                mainContent.classList.remove('main-content-expanded');
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize();
    </script>
</body>
</html>