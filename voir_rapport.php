<?php
session_start();
include 'db_config.php';

// Vérifier si l'ID du rapport est passé en paramètre
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: liste_rapports.php?message=معرف التقرير غير صالح&type=error");
    exit();
}

$id_rapport = intval($_GET['id']);

// Récupérer les détails du rapport
$query = "SELECT r.*, 
                 c.nom_classe, 
                 p.nom AS nom_professeur, p.prenom AS prenom_professeur,
                 i.nom AS nom_inspecteur, i.prenom AS prenom_inspecteur
          FROM rapports_inspection r
          JOIN classes c ON r.id_classe = c.id_classe
          JOIN professeurs p ON r.id_professeur = p.id_professeur
          JOIN inspecteurs i ON r.id_inspecteur = i.id_inspecteur
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_rapport);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: liste_rapports.php?message=التقرير غير موجود&type=error");
    exit();
}

$rapport = $result->fetch_assoc();

// Récupérer les fichiers joints au rapport
$query_fichiers = "SELECT * FROM fichiers_rapport WHERE rapport_id = ? ORDER BY date_upload DESC";
$stmt_fichiers = $conn->prepare($query_fichiers);
$stmt_fichiers->bind_param("i", $id_rapport);
$stmt_fichiers->execute();
$result_fichiers = $stmt_fichiers->get_result();

// Fonction pour formater la date
function formater_date($date_mysql) {
    $date = new DateTime($date_mysql);
    return $date->format('d/m/Y à H:i');
}

// Fonction pour obtenir l'extension d'un fichier
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Fonction pour obtenir l'icône en fonction du type de fichier
function get_file_icon($extension) {
    switch ($extension) {
        case 'pdf':
            return 'fa-file-pdf';
        case 'doc':
        case 'docx':
            return 'fa-file-word';
        case 'xls':
        case 'xlsx':
            return 'fa-file-excel';
        case 'ppt':
        case 'pptx':
            return 'fa-file-powerpoint';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'fa-file-image';
        case 'zip':
        case 'rar':
            return 'fa-file-archive';
        case 'txt':
            return 'fa-file-alt';
        default:
            return 'fa-file';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة تقرير | نظام إدارة تقارير المتفقد</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Card Styles */
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
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.2rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-left {
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

        /* Button Styles */
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

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #2d9300;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #b80021;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: var(--gray-800);
        }

        .btn-warning:hover {
            background-color: #e6b43b;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
        }

        .btn-outline:hover {
            background-color: var(--gray-100);
        }

        /* Info Section */
        .info-section {
            margin-bottom: 2rem;
        }

        .info-section h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--gray-800);
            border-right: 3px solid var(--primary-color);
            padding-right: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            background-color: var(--gray-100);
            padding: 1rem;
            border-radius: var(--border-radius);
        }

        .info-item-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 0.25rem;
        }

        .info-item-value {
            font-weight: 500;
            color: var(--gray-800);
        }

        /* Content Section */
        .content-section {
            margin-bottom: 2rem;
        }

        .content-section h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--gray-800);
            border-right: 3px solid var(--primary-color);
            padding-right: 10px;
        }

        .content-text {
            background-color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-300);
            white-space: pre-line;
        }

        /* Files Section */
        .files-section {
            margin-bottom: 2rem;
        }

        .files-section h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--gray-800);
            border-right: 3px solid var(--primary-color);
            padding-right: 10px;
        }

        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .file-item {
            background-color: white;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: var(--transition);
        }

        .file-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .file-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .file-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
            word-break: break-word;
        }

        .file-info {
            font-size: 0.75rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }

        .file-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .file-actions a {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: var(--transition);
        }

        .file-download {
            background-color: var(--primary-color);
            color: white;
        }

        .file-download:hover {
            background-color: var(--primary-dark);
        }

        .file-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .file-delete:hover {
            background-color: #b80021;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }

        /* Badge Styles */
        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 50rem;
        }

        .badge-primary {
            background-color: var(--primary-color);
            color: white;
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

            .info-grid {
                grid-template-columns: 1fr;
            }

            .files-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .action-buttons {
                flex-direction: column;
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

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1050;
            overflow-y: auto;
            padding: 2rem 1rem;
        }

        .modal-dialog {
            max-width: 600px;
            margin: 1.75rem auto;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            animation: fadeIn 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-300);
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-600);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-300);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
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
                    <h1>نظام إدارة التقارير</h1>
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
        <h1 class="page-title"><i class="fas fa-file-alt"></i> عرض تقرير المتفقد</h1>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-header-left">
                        <i class="fas fa-file-alt"></i>
                        <h2><?= htmlspecialchars($rapport['titre']) ?></h2>
                    </div>
                    <div>
                        <span class="badge badge-primary">
                            <i class="fas fa-calendar-alt"></i> <?= formater_date($rapport['date_creation']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Informations générales -->
                    <div class="info-section">
                        <h3>معلومات عامة</h3>
                        <div class="info-grid">
                            <div class="info-item">
                            <div class="info-item-label">القسم</div>
                                <div class="info-item-value"><?= htmlspecialchars($rapport['nom_classe']) ?></div>
                            </div>
                            <div class="info-item">
                            <div class="info-item-label">المعلم</div>
                                <div class="info-item-value"><?= htmlspecialchars($rapport['nom_professeur'] . ' ' . $rapport['prenom_professeur']) ?></div>
                            </div>
                            <div class="info-item">
                            <div class="info-item-label">المتفقد</div>
                                <div class="info-item-value"><?= htmlspecialchars($rapport['nom_inspecteur'] . ' ' . $rapport['prenom_inspecteur']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">تاريخ الإنشاء</div>
                                <div class="info-item-value"><?= formater_date($rapport['date_creation']) ?></div>
                            </div>
                            <?php if (!empty($rapport['date_modification'])): ?>
                            <div class="info-item">
                            <div class="info-item-label">تاريخ آخر تغيير</div>
                                <div class="info-item-value"><?= formater_date($rapport['date_modification']) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Commentaires -->
                    <div class="content-section">
                        <h3>التعليقات</h3>
                        <div class="content-text">
                            <?= nl2br(htmlspecialchars($rapport['commentaires'])) ?>
                        </div>
                    </div>

                    <!-- Recommandations -->
                    <div class="content-section">
                        <h3>التوصيات</h3>
                        <div class="content-text">
                            <?= nl2br(htmlspecialchars($rapport['recommandations'])) ?>
                        </div>
                    </div>

                    <!-- Fichiers joints -->
                    <div class="files-section">
                        <h3>الملفات المرفقة</h3>
                        <?php if ($result_fichiers->num_rows > 0): ?>
                            <div class="files-grid">
                                <?php while ($fichier = $result_fichiers->fetch_assoc()): ?>
                                    <?php 
                                        $extension = get_file_extension($fichier['nom_fichier']);
                                        $icon = get_file_icon($extension);
                                    ?>
                                    <div class="file-item">
                                        <div class="file-icon">
                                            <i class="fas <?= $icon ?>"></i>
                                        </div>
                                        <div class="file-name"><?= htmlspecialchars($fichier['nom_fichier']) ?></div>
                                        <div class="file-info">
                                            <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($fichier['date_upload'])) ?>
                                        </div>
                                        <div class="file-actions">
                                            <a href="<?= htmlspecialchars($fichier['chemin_fichier']) ?>" class="file-download" download>
                                            <i class="fas fa-download"></i> تحميل
                                            </a>
                                            <?php if (isset($_SESSION['id_inspecteur'])): ?>
                                            <a href="supprimer_fichier.php?id=<?= $fichier['id'] ?>&rapport_id=<?= $id_rapport ?>" class="file-delete" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا الملف؟')">
                                            <i class="fas fa-trash-alt"></i> إزالة
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p>لا توجد ملفات مرفقة بهذا التقرير.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="action-buttons">
                        <a href="liste_rapports.php" class="btn btn-outline">
                            <i class="fas fa-arrow-right"></i> العودة إلى القائمة
                        </a>
                        <?php if (isset($_SESSION['id_inspecteur'])): ?>
                        <a href="modifier_rapport.php?id=<?= $id_rapport ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> تغيير التقرير
                        </a>
                        <?php endif; ?>
                        <a href="generer_rapport.php?id=<?= $id_rapport ?>" target="_blank" class="btn btn-success">
                            <i class="fas fa-file-pdf"></i> تصدير إلى PDF
                        </a>
                        <?php if (isset($_SESSION['id_inspecteur'])): ?>
                        <button onclick="confirmerSuppression(<?= $id_rapport ?>)" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> حذف التقرير
                        </button>
                        <?php endif; ?>
                    </div>
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
                <p>&copy; <?= date('Y') ?> نظام التفقّد التربوي. الحقوق محفوظة.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal de confirmation de suppression -->
    <div id="modal-suppression" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">تأكيد الحذف</h3>
                <button type="button" class="modal-close" onclick="fermerModal()">&times;</button>
            </div>
            <div class="modal-body">
            <p>هل ترغب فعلاً في حذف تقرير التفقّد؟ هذا الإجراء غير قابل للاسترجاع.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="fermerModal()">إلغاء</button>
                <a href="#" id="btn-confirmer-suppression" class="btn btn-danger">إزالة</a>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-icon"><i class="fas fa-check-circle"></i></div>
        <div class="toast-message">تنبيه</div>
        <button class="toast-close"><i class="fas fa-times"></i></button>
    </div>

    <script>
        // Fonction pour afficher le modal de confirmation de suppression
        function confirmerSuppression(id) {
            const modal = document.getElementById('modal-suppression');
            const btnConfirmer = document.getElementById('btn-confirmer-suppression');
            
            btnConfirmer.href = `supprimer_rapport.php?id=${id}`;
            modal.style.display = 'block';
        }

        // Fonction pour fermer le modal
        function fermerModal() {
            const modal = document.getElementById('modal-suppression');
            modal.style.display = 'none';
        }

        // Fermer le modal si on clique en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('modal-suppression');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

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

        // Afficher un toast si un message est présent dans l'URL (après redirection)
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            const type = urlParams.get('type') || 'success';
            
            if (message) {
                showToast(type, decodeURIComponent(message));
            }
        });
    </script>
</body>
</html>