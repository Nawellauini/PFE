<?php
session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_inspecteur'])) {
    header("Location: login.php?error=يجب أن تكون متصلاً كمفتش");
    exit();
}

// Récupérer les classes pour le menu déroulant
$classes = $conn->query("SELECT * FROM classes")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة تقرير | نظام إدارة تقارير المتفقد</title>
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

        /* Toast Notification - Style amélioré */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
            width: 350px;
        }

        .toast {
            position: relative;
            padding: 1.25rem;
            background-color: white;
            color: var(--gray-800);
            border-radius: var(--border-radius);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 15px;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            width: 5px;
        }

        .toast-success {
            border: 1px solid rgba(56, 176, 0, 0.2);
        }

        .toast-success::before {
            background-color: var(--success-color);
        }

        .toast-error {
            border: 1px solid rgba(217, 4, 41, 0.2);
        }

        .toast-error::before {
            background-color: var(--danger-color);
        }

        .toast-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .toast-success .toast-icon {
            color: var(--success-color);
            background-color: rgba(56, 176, 0, 0.1);
        }

        .toast-error .toast-icon {
            color: var(--danger-color);
            background-color: rgba(217, 4, 41, 0.1);
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--gray-900);
        }

        .toast-message {
            font-size: 0.95rem;
            color: var(--gray-700);
            line-height: 1.5;
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0;
            position: absolute;
            top: 10px;
            left: 10px;
            transition: color 0.2s;
        }

        .toast-close:hover {
            color: var(--gray-800);
        }

        .toast-progress {
            position: absolute;
            bottom: 0;
            right: 0;
            height: 3px;
            background-color: var(--success-color);
            width: 100%;
            animation: toast-progress 5s linear forwards;
        }

        .toast-error .toast-progress {
            background-color: var(--danger-color);
        }

        @keyframes toast-progress {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background-color: rgba(217, 4, 41, 0.1);
            border-right: 4px solid var(--danger-color);
            color: var(--danger-color);
        }

        .alert-success {
            background-color: rgba(56, 176, 0, 0.1);
            border-right: 4px solid var(--success-color);
            color: var(--success-color);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-clipboard-check"></i>
                    <h1>نظام إدارة تقارير التفقّد</h1>
                </div>
                <nav class="nav">
                    <ul>
                        <li><a href="liste_rapports.php"><i class="fas fa-list"></i> قائمة التقارير</a></li>
                        <li><a href="rapports.php" class="active"><i class="fas fa-plus-circle"></i> إضافة تقرير</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
        <h1 class="page-title"><i class="fas fa-file-medical"></i> إضافة تقرير تفقّد</h1>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>تمت إضافة التقرير بنجاح.</span>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-edit"></i>
                    <h2>نموذج إضافة تقرير</h2>
                </div>
                <div class="card-body">
                    <form action="traiter_rapport.php" method="POST" enctype="multipart/form-data" class="form" id="rapport-form">
                        <div class="form-group">
                            <label for="titre" class="form-label">عنوان التقرير</label>
                            <input type="text" name="titre" id="titre" class="form-control" required placeholder="أدخل عنوان التقرير">
                        </div>

                        <div class="form-group">
                        <label for="id_classe" class="form-label">القسم</label>
                            <select name="id_classe" id="id_classe" class="form-control" required>
                            <option value="">اختر قسماً</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?= $classe['id_classe'] ?>"><?= $classe['nom_classe'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                        <label for="id_professeur" class="form-label">المعلّم</label>
                            <select name="id_professeur" id="id_professeur" class="form-control" required>
                            <option value="">اختر قسماً أولاً</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="commentaires" class="form-label">التعليقات</label>
                            <textarea name="commentaires" id="commentaires" class="form-control" rows="4" required placeholder="أدخل تعليقاتك المفصلة حول التفتيش"></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="recommandations" class="form-label">التوصيات</label>
                            <textarea name="recommandations" id="recommandations" class="form-control" rows="4" required placeholder="أدخل توصياتك بعد التفتيش"></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">المرفقات</label>
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
                                <i class="fas fa-save"></i> حفظ التقرير
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
                    <a href="rapports.php">إضافة تقرير</a>
                    <a href="#">حول النظام</a>
                    <a href="#">تواصل معنا</a>
                </div>
                <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> نظام إدارة تقارير المتفقد. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toast-container"></div>

    <script>
        $(document).ready(function () {
            // Vérifier si on vient d'ajouter un rapport avec succès
            <?php if (isset($_GET['success'])): ?>
                showSuccessToast();
            <?php endif; ?>

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

            // Charger les professeurs en fonction de la classe sélectionnée
            $("#id_classe").change(function () {
                var id_classe = $(this).val();
                var professeurSelect = $("#id_professeur");
                
                professeurSelect.html('<option value="">جاري التحميل...</option>');

                if (id_classe !== "") {
                    $.ajax({
                        url: "get_professeurs_by_classe.php",
                        type: "GET",
                        data: { id_classe: id_classe },
                        dataType: "json",
                        success: function (data) {
                            professeurSelect.empty();
                            professeurSelect.append('<option value="">اختر معلماً</option>');
                            
                            if (data.length === 0) {
                                professeurSelect.append('<option value="" disabled>لا يوجد معلمون متاحون لهذا القسم</option>');
                            } else {
                                $.each(data, function (index, professeur) {
                                    professeurSelect.append('<option value="' + professeur.id_professeur + '">' + professeur.nom + ' ' + professeur.prenom + '</option>');
                                });
                            }
                            
                            // Afficher une notification de succès
                            showToast('success', 'تم تحميل قائمة المعلمين', 'تم تحميل قائمة المعلمين بنجاح للقسم المحدد');
                        },
                        error: function () {
                            professeurSelect.html('<option value="">خطأ في التحميل</option>');
                            showToast('error', 'خطأ في التحميل', 'حدث خطأ أثناء تحميل قائمة المعلمين، يرجى المحاولة مرة أخرى');
                        }
                    });
                } else {
                    professeurSelect.html('<option value="">اختر قسماً أولاً</option>');
                }
            });

            // Gestion du formulaire
            $('#rapport-form').on('submit', function(e) {
                const submitBtn = $('#submit-btn');
                submitBtn.html('<span class="spinner"></span> جاري الحفظ...');
                submitBtn.prop('disabled', true);
                
                // Le formulaire sera soumis normalement
                // Cette partie est juste pour l'animation du bouton
            });

            // Fonction pour afficher un toast de succès après l'ajout d'un rapport
            function showSuccessToast() {
                showToast(
                    'success',
                    'تم إضافة التقرير بنجاح',
                   'تم حفظ تقرير المتفقد بنجاح في النظام. يمكنك الآن مشاهدته في قائمة التقارير أو إضافة تقرير جديد.'
                );
            }

            // Fonction pour afficher les notifications toast améliorées
            function showToast(type, title, message) {
                const toastContainer = $('#toast-container');
                const toastId = 'toast-' + Date.now();
                
                // Créer le HTML du toast
                const toastHTML = `
                    <div id="${toastId}" class="toast toast-${type}">
                        <div class="toast-icon">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                        </div>
                        <div class="toast-content">
                            <div class="toast-title">${title}</div>
                            <div class="toast-message">${message}</div>
                        </div>
                        <button class="toast-close">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="toast-progress"></div>
                    </div>
                `;
                
                // Ajouter le toast au conteneur
                toastContainer.append(toastHTML);
                
                // Afficher le toast avec un délai pour l'animation
                setTimeout(() => {
                    $(`#${toastId}`).addClass('show');
                }, 100);
                
                // Configurer la fermeture du toast
                $(`#${toastId} .toast-close`).on('click', function() {
                    closeToast(toastId);
                });
                
                // Fermer automatiquement après 5 secondes
                setTimeout(() => {
                    closeToast(toastId);
                }, 5000);
            }
            
            // Fonction pour fermer un toast
            function closeToast(toastId) {
                const toast = $(`#${toastId}`);
                toast.removeClass('show');
                
                // Supprimer le toast après l'animation
                setTimeout(() => {
                    toast.remove();
                }, 400);
            }
        });
    </script>
</body>
</html>