<?php

session_start();
include 'db_config.php'; // Connexion à la base de données

// Vérifier si l'ID du rapport est passé en paramètre
if (isset($_GET['id'])) {
    $rapport_id = $_GET['id'];

    // Récupérer les informations du rapport - Version corrigée avec la structure correcte de la table
    $sql_rapport = "SELECT r.*, c.nom_classe, p.nom AS nom_professeur, p.prenom AS prenom_professeur,
                    i.nom AS nom_inspecteur, i.prenom AS prenom_inspecteur
                    FROM rapports_inspection r
                    JOIN classes c ON r.id_classe = c.id_classe
                    JOIN professeurs p ON r.id_professeur = p.id_professeur
                    LEFT JOIN inspecteurs i ON r.id_inspecteur = i.id_inspecteur
                    WHERE r.id = ?";
    
    // Vérifier si la préparation de la requête a réussi
    $stmt_rapport = $conn->prepare($sql_rapport);
    
    if ($stmt_rapport === false) {
        die("خطأ في إعداد الاستعلام: " . $conn->error);
    }
    
    $stmt_rapport->bind_param("i", $rapport_id);
    
    if (!$stmt_rapport->execute()) {
        die("خطأ في تنفيذ الاستعلام: " . $stmt_rapport->error);
    }
    
    $result_rapport = $stmt_rapport->get_result();
    
    if ($result_rapport->num_rows === 0) {
        header("Location: liste_rapports.php?message=لم+يتم+العثور+على+التقرير&type=error");
        exit();
    }
    
    $rapport = $result_rapport->fetch_assoc();
    
    // Récupérer les fichiers attachés au rapport - Avec gestion d'erreur
    // Correction: utilisation de rapport_id au lieu de id_rapport
    $sql_fichiers = "SELECT * FROM fichiers_rapport WHERE rapport_id = ?";
    $stmt_fichiers = $conn->prepare($sql_fichiers);
    
    if ($stmt_fichiers === false) {
        die("خطأ في إعداد استعلام الملفات: " . $conn->error);
    }
    
    $stmt_fichiers->bind_param("i", $rapport_id);
    
    if (!$stmt_fichiers->execute()) {
        die("خطأ في تنفيذ استعلام الملفات: " . $stmt_fichiers->error);
    }
    
    $result_fichiers = $stmt_fichiers->get_result();
    $fichiers = $result_fichiers->fetch_all(MYSQLI_ASSOC);
    
} else {
    header("Location: liste_rapports.php?message=لم+يتم+تحديد+أي+تقرير&type=error");
    exit();
}

// Récupérer les classes pour les listes déroulantes - Avec gestion d'erreur
$result_classes = $conn->query("SELECT * FROM classes ORDER BY nom_classe");
if ($result_classes === false) {
    die("خطأ أثناء استرجاع الأقسام: " . $conn->error); 
}
$classes = $result_classes->fetch_all(MYSQLI_ASSOC);

// Récupérer tous les professeurs - Avec gestion d'erreur
$result_profs = $conn->query("SELECT * FROM professeurs ORDER BY nom, prenom");
if ($result_profs === false) {
    die("خطأ أثناء استرجاع المعلمين: " . $conn->error);

}
$professeurs = $result_profs->fetch_all(MYSQLI_ASSOC);

// Récupérer tous les inspecteurs - Avec gestion d'erreur
$result_inspecteurs = $conn->query("SELECT * FROM inspecteurs ORDER BY nom, prenom");
if ($result_inspecteurs === false) {
    die("خطأ أثناء استرجاع المتفقدين: " . $conn->error);


}
$inspecteurs = $result_inspecteurs->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>تعديل تقرير التفتيش التربوي | نظام إدارة التقارير</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56d4;
            --secondary-color: #7209b7;
            --accent-color: #f72585;
            --success-color: #38b000;
            --warning-color: #f9c74f;
            --danger-color: #d90429;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f7fb;
            color: var(--gray-800);
            line-height: 1.6;
            text-align: right;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 0;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .nav ul {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }

        .nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .nav a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .nav a.active {
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* Main Content Styles */
        .main-content {
            padding: 2rem 0;
        }

        .page-title {
            margin-bottom: 1.5rem;
            color: var(--gray-800);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title i {
            color: var(--primary-color);
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.2rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form Styles */
        .form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .form {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-group.full-width {
                grid-column: span 2;
            }
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            background-color: var(--gray-100);
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .file-upload {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .file-label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 0.75rem 1.5rem;
            background-color: var(--gray-200);
            color: var(--gray-700);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .file-label:hover {
            background-color: var(--gray-300);
        }

        .file-input {
            position: absolute;
            width: 0.1px;
            height: 0.1px;
            opacity: 0;
            overflow: hidden;
            z-index: -1;
        }

        .file-names {
            padding: 0.75rem;
            background-color: var(--gray-100);
            border-radius: var(--border-radius);
            border: 1px dashed var(--gray-400);
            min-height: 60px;
            max-height: 150px;
            overflow-y: auto;
        }

        .file-names:empty {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-500);
        }

        .file-names:empty::after {
            content: "لم يتم اختيار أي ملف";
        }

        .file-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            grid-column: span 2;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--gray-500);
            color: white;
        }

        .btn-secondary:hover {
            background-color: var(--gray-600);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #b80021;
        }

        /* Existing Files Section */
        .existing-files {
            margin-bottom: 1rem;
        }

        .existing-files-title {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }

        .file-list {
            list-style: none;
            padding: 0;
            margin: 0;
            background-color: var(--gray-100);
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-300);
        }

        .file-list-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-300);
        }

        .file-list-item:last-child {
            border-bottom: none;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }

        .file-icon {
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .file-name {
            font-weight: 500;
        }

        .file-size {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-right: auto;
        }

        /* Footer Styles */
        .footer {
            background-color: var(--gray-800);
            color: var(--gray-300);
            padding: 2rem 0;
            text-align: center;
            margin-top: 2rem;
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--gray-400);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-copyright {
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.5s ease-out;
        }

        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background-color: white;
            color: var(--gray-800);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1100;
            transform: translateX(120%);
            transition: transform 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-success {
            border-right: 4px solid var(--success-color);
        }

        .toast-error {
            border-right: 4px solid var(--danger-color);
        }

        .toast-icon {
            font-size: 1.2rem;
        }

        .toast-success .toast-icon {
            color: var(--success-color);
        }

        .toast-error .toast-icon {
            color: var(--danger-color);
        }

        .toast-message {
            flex: 1;
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--gray-600);
            cursor: pointer;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-clipboard-check"></i>
                    <h1>نظام إدارة تقارير التفقد التربوي</h1>
                </div>
                <nav class="nav">
                    <ul>
                        <li><a href="liste_rapports.php"><i class="fas fa-list"></i> قائمة التقارير</a></li>
                        <li><a href="ajouter_rapport.php"><i class="fas fa-plus-circle"></i> إضافة تقرير</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
        <h1 class="page-title"><i class="fas fa-edit"></i> تعديل تقرير التفقد</h1>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-file-alt"></i>
                    <h2>تقرير #<?= $rapport['id'] ?> - <?= htmlspecialchars($rapport['titre']) ?></h2>
                </div>
                <div class="card-body">
                    <form action="traiter_modification_rapport.php" method="POST" enctype="multipart/form-data" class="form" id="rapport-form">
                        <input type="hidden" name="id" value="<?= $rapport['id'] ?>">
                        
                        <div class="form-group">
                            <label for="titre" class="form-label">عنوان التقرير</label>
                            <input type="text" name="titre" id="titre" class="form-control" value="<?= htmlspecialchars($rapport['titre']) ?>" required>
                        </div>

                        <div class="form-group">
                        <label for="id_classe" class="form-label">القسم</label>
                            <select name="id_classe" id="id_classe" class="form-control" required>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?= $classe['id_classe'] ?>" <?= $rapport['id_classe'] == $classe['id_classe'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($classe['nom_classe']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                        <label for="id_professeur" class="form-label">المعلم</label>
                            <select name="id_professeur" id="id_professeur" class="form-control" required>
                                <?php foreach ($professeurs as $prof): ?>
                                    <option value="<?= $prof['id_professeur'] ?>" <?= $rapport['id_professeur'] == $prof['id_professeur'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($prof['nom'] . ' ' . $prof['prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                        <label for="id_inspecteur" class="form-label">المتفقد</label>
                            <select name="id_inspecteur" id="id_inspecteur" class="form-control" required>
                                <?php foreach ($inspecteurs as $inspecteur): ?>
                                    <option value="<?= $inspecteur['id_inspecteur'] ?>" <?= $rapport['id_inspecteur'] == $inspecteur['id_inspecteur'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($inspecteur['nom'] . ' ' . $inspecteur['prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="commentaires" class="form-label">التعليقات</label>
                            <textarea name="commentaires" id="commentaires" class="form-control" rows="4" required><?= htmlspecialchars($rapport['commentaires']) ?></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="recommandations" class="form-label">التوصيات</label>
                            <textarea name="recommandations" id="recommandations" class="form-control" rows="4" required><?= htmlspecialchars($rapport['recommandations']) ?></textarea>
                        </div>

                        <?php if (!empty($fichiers)): ?>
                        <div class="form-group full-width">
                            <div class="existing-files">
                                <div class="existing-files-title">الملفات الموجودة</div>
                                <ul class="file-list">
                                    <?php foreach ($fichiers as $fichier): ?>
                                    <li class="file-list-item">
                                        <div class="file-info">
                                            <i class="fas fa-file file-icon"></i>
                                            <span class="file-name"><?= htmlspecialchars($fichier['nom_fichier']) ?></span>
                                            <!-- Correction ici: Utiliser filesize() pour obtenir la taille du fichier -->
                                            <?php 
                                            $file_path = $fichier['chemin_fichier'];
                                            $file_size = file_exists($file_path) ? filesize($file_path) : 0;
                                            $file_size_kb = number_format($file_size / 1024, 2);
                                            ?>
                                            <span class="file-size">(<?= $file_size_kb ?> KB)</span>
                                        </div>
                                        <!-- Les boutons de téléchargement et de suppression ont été supprimés ici -->
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="form-group full-width">
                            <label class="form-label">إضافة ملفات جديدة</label>
                            <div class="file-upload">
                                <label for="fichiers" class="file-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>اختر الملفات</span>
                                </label>
                                <input type="file" name="fichiers[]" id="fichiers" class="file-input" multiple>
                                <div id="file-names" class="file-names"></div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="fas fa-save"></i> تحديث التقرير
                            </button>
                            <a href="liste_rapports.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-links">
                    <a href="liste_rapports.php">قائمة التقارير</a>
                    <a href="ajouter_rapport.php">إضافة تقرير</a>
                    <a href="#">حول النظام</a>
                    <a href="#">تواصل معنا</a>
                </div>
                <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> نظام إدارة التقارير التربوية. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-icon"><i class="fas fa-check-circle"></i></div>
        <div class="toast-message">رسالة تنبيه</div>
        <button class="toast-close"><i class="fas fa-times"></i></button>
    </div>

    <script>
        $(document).ready(function() {
            // Afficher les noms des fichiers sélectionnés
            $('#fichiers').change(function() {
                const fileList = this.files;
                
                let fileNamesHTML = '';
                
                if (fileList.length > 0) {
                    for (let i = 0; i < fileList.length; i++) {
                        const file = fileList[i];
                        const fileSize = (file.size / 1024).toFixed(2) + ' KB';
                        
                        fileNamesHTML += `
                            <div class="file-item">
                                <i class="fas fa-file"></i>
                                <span>${file.name}</span>
                                <span class="text-muted">(${fileSize})</span>
                            </div>
                        `;
                    }
                }
                
                $('#file-names').html(fileNamesHTML);
            });

            // Gestion du formulaire
            $('#rapport-form').on('submit', function(e) {
                const submitBtn = $('#submit-btn');
                submitBtn.html('<span class="spinner"></span> جاري التحديث...');
                submitBtn.prop('disabled', true);
                
                // Le formulaire sera soumis normalement
            });

            // Afficher un toast si un message est présent dans l'URL (après redirection)
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            const type = urlParams.get('type') || 'success';
            
            if (message) {
                showToast(type, decodeURIComponent(message));
            }
        });

        // Fonction pour afficher les notifications toast
        function showToast(type, message) {
            const toast = document.getElementById('toast');
            
            // Configurer le type de toast
            toast.className = 'toast';
            toast.classList.add('toast-' + type);
            
            // Définir l'icône
            let icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            document.querySelector('.toast-icon i').className = 'fas fa-' + icon;
            
            // Définir le message
            document.querySelector('.toast-message').textContent = message;
            
            // Afficher le toast
            toast.classList.add('show');
            
            // Masquer après 3 secondes
            setTimeout(function() {
                toast.classList.remove('show');
            }, 3000);
        }
        
        // Fermer le toast en cliquant sur le bouton de fermeture
        document.querySelector('.toast-close').addEventListener('click', function() {
            document.getElementById('toast').classList.remove('show');
        });
    </script>
</body>
</html>