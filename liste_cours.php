<?php
// Démarrer la session
session_start();

// Inclure le fichier de configuration de la base de données
include 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Variables selon le rôle
$is_prof = ($_SESSION['role'] == 'professeur');
$is_eleve = ($_SESSION['role'] == 'eleve');

// Récupérer l'ID de l'utilisateur
$user_id = $is_prof ? $_SESSION['id_professeur'] : ($is_eleve ? $_SESSION['id_eleve'] : 0);

// Filtres de recherche
$search = isset($_GET['search']) ? $_GET['search'] : '';
$theme_filter = isset($_GET['theme']) ? intval($_GET['theme']) : 0;

// Récupérer tous les thèmes pour le filtre
$sql_themes = "SELECT * FROM themes ORDER BY nom_theme";
$result_themes = $conn->query($sql_themes);
$themes = [];
if ($result_themes) {
    while ($theme = $result_themes->fetch_assoc()) {
        $themes[] = $theme;
    }
}

// Récupérer les cours selon le rôle
if ($is_prof) {
    // Pour les professeurs, afficher leurs propres cours
    $sql = "SELECT c.*, cl.nom_classe, t.nom_theme, m.nom as nom_matiere 
            FROM cours c
            JOIN classes cl ON c.id_classe = cl.id_classe
            JOIN themes t ON c.id_theme = t.id_theme
            JOIN matieres m ON c.matiere_id = m.matiere_id
            WHERE c.id_professeur = ? ";
    
    // Ajouter les filtres si nécessaire
    if (!empty($search)) {
        $sql .= "AND (c.titre LIKE ? OR c.description LIKE ?) ";
    }
    
    if ($theme_filter > 0) {
        $sql .= "AND c.id_theme = ? ";
    }
    
    $sql .= "ORDER BY c.date_creation DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Bind des paramètres selon les filtres
    if (!empty($search) && $theme_filter > 0) {
        $search_param = "%$search%";
        $stmt->bind_param("issi", $user_id, $search_param, $search_param, $theme_filter);
    } elseif (!empty($search)) {
        $search_param = "%$search%";
        $stmt->bind_param("iss", $user_id, $search_param, $search_param);
    } elseif ($theme_filter > 0) {
        $stmt->bind_param("ii", $user_id, $theme_filter);
    } else {
        $stmt->bind_param("i", $user_id);
    }
} elseif ($is_eleve) {
    // Pour les élèves, afficher les cours de leur classe
    $sql = "SELECT c.*, cl.nom_classe, t.nom_theme, m.nom as nom_matiere, 
            CONCAT(p.prenom, ' ', p.nom) as nom_professeur
            FROM cours c
            JOIN classes cl ON c.id_classe = cl.id_classe
            JOIN themes t ON c.id_theme = t.id_theme
            JOIN matieres m ON c.matiere_id = m.matiere_id
            JOIN professeurs p ON c.id_professeur = p.id_professeur
            JOIN eleves e ON e.id_classe = cl.id_classe
            WHERE e.id_eleve = ? ";
    
    // Ajouter les filtres si nécessaire
    if (!empty($search)) {
        $sql .= "AND (c.titre LIKE ? OR c.description LIKE ?) ";
    }
    
    if ($theme_filter > 0) {
        $sql .= "AND c.id_theme = ? ";
    }
    
    $sql .= "ORDER BY c.date_creation DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Bind des paramètres selon les filtres
    if (!empty($search) && $theme_filter > 0) {
        $search_param = "%$search%";
        $stmt->bind_param("issi", $user_id, $search_param, $search_param, $theme_filter);
    } elseif (!empty($search)) {
        $search_param = "%$search%";
        $stmt->bind_param("iss", $user_id, $search_param, $search_param);
    } elseif ($theme_filter > 0) {
        $stmt->bind_param("ii", $user_id, $theme_filter);
    } else {
        $stmt->bind_param("i", $user_id);
    }
} else {
    // Rediriger si le rôle n'est pas reconnu
    header("Location: login.php");
    exit();
}

// Exécuter la requête
$stmt->execute();
$result = $stmt->get_result();

// Compter le nombre total de cours
$total_courses = $result->num_rows;

// Vérifier s'il y a des messages ou des erreurs
$message = isset($_GET['message']) ? $_GET['message'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_prof ? 'دروسي' : 'الدروس المتاحة'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e6091;
            --primary-dark: #0d4a77;
            --secondary-color: #2a9d8f;
            --accent-color: #e9c46a;
            --text-color: #264653;
            --text-light: #546a7b;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
            --error-color: #e76f51;
            --success-color: #2a9d8f;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn i {
            margin-left: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #d35400;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-left: 10px;
            font-size: 20px;
        }

        .alert-success {
            background-color: rgba(42, 157, 143, 0.2);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        .filter-section {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 30px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .filter-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .filter-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 96, 145, 0.2);
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 10px center;
            background-size: 16px;
            padding-left: 30px;
            transition: var(--transition);
        }

        .filter-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 96, 145, 0.2);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .courses-count {
            margin-bottom: 20px;
            font-size: 16px;
            color: var(--text-light);
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .course-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .course-image {
            height: 180px;
            background-color: #e9ecef;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .course-image-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 48px;
        }

        .course-theme {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .course-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .course-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .course-meta {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .course-meta-item {
            display: flex;
            align-items: center;
            margin-left: 15px;
            margin-bottom: 5px;
            font-size: 14px;
            color: var(--text-light);
        }

        .course-meta-item i {
            margin-left: 5px;
            color: var(--primary-color);
        }

        .course-description {
            margin-bottom: 15px;
            font-size: 14px;
            color: var(--text-color);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }

        .course-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }

        .course-date {
            font-size: 12px;
            color: var(--text-light);
            display: flex;
            align-items: center;
        }

        .course-date i {
            margin-left: 5px;
            color: var(--primary-color);
        }

        .course-view {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: var(--transition);
            padding: 5px 10px;
            border-radius: var(--border-radius);
            background-color: rgba(30, 96, 145, 0.1);
        }

        .course-view i {
            margin-right: 5px;
        }

        .course-view:hover {
            color: white;
            background-color: var(--primary-color);
        }

        .course-admin-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            justify-content: flex-end;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: var(--border-radius);
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .action-btn i {
            margin-left: 5px;
        }

        .action-btn-edit {
            background-color: var(--warning-color);
            color: white;
        }

        .action-btn-edit:hover {
            background-color: #d35400;
        }

        .action-btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .action-btn-delete:hover {
            background-color: #c0392b;
        }

        .action-btn-pdf {
            background-color: var(--success-color);
            color: white;
        }

        .action-btn-pdf:hover {
            background-color: #27ae60;
        }

        .no-courses {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .no-courses i {
            font-size: 48px;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .no-courses p {
            font-size: 18px;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        /* Modal de confirmation */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 90%;
            max-width: 500px;
            padding: 20px;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .modal-header i {
            font-size: 24px;
            color: var(--danger-color);
            margin-left: 10px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-color);
        }

        .modal-body {
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .courses-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-actions {
                width: 100%;
            }
            
            .btn {
                width: 100%;
            }
            
            .course-admin-actions {
                flex-wrap: wrap;
            }
            
            .action-btn {
                flex: 1;
                min-width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
        <h1 class="page-title"><?php echo $is_prof ? 'الدروس' : 'الدروس المتاحة'; ?></h1>
            <?php if ($is_prof): ?>
            <a href="ajouter_cours.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                إضافة درس جديد
            </a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="filter-section">
            <form action="" method="get" class="filter-form">
                <div class="filter-group">
                    <label for="search" class="filter-label">بحث</label>
                    <input type="text" id="search" name="search" class="filter-input" placeholder="ابحث عن عنوان أو وصف..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="theme" class="filter-label">الموضوع</label>
                    <select id="theme" name="theme" class="filter-select">
                        <option value="0">جميع المواضيع</option>
                        <?php foreach ($themes as $theme): ?>
                        <option value="<?php echo $theme['id_theme']; ?>" <?php echo $theme_filter == $theme['id_theme'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($theme['nom_theme']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        بحث
                    </button>
                    <a href="liste_cours.php" class="btn btn-outline">
                        <i class="fas fa-redo"></i>
                        إعادة ضبط
                    </a>
                </div>
            </form>
        </div>
        
        <div class="courses-count">
            <strong><?php echo $total_courses; ?></strong> درس <?php echo !empty($search) || $theme_filter > 0 ? 'مطابق لمعايير البحث' : ''; ?>
        </div>
        
        <div class="courses-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="course-card">
                        <div class="course-image" style="<?php echo !empty($row['illustration']) ? 'background-image: url(\'' . $row['illustration'] . '\');' : ''; ?>">
                            <?php if (empty($row['illustration'])): ?>
                            <div class="course-image-placeholder">
                                <i class="fas fa-book"></i>
                            </div>
                            <?php endif; ?>
                            <div class="course-theme"><?php echo htmlspecialchars($row['nom_theme']); ?></div>
                        </div>
                        <div class="course-content">
                            <h3 class="course-title"><?php echo htmlspecialchars($row['titre']); ?></h3>
                            <div class="course-meta">
                                <div class="course-meta-item">
                                    <i class="fas fa-users"></i>
                                    <?php echo htmlspecialchars($row['nom_classe']); ?>
                                </div>
                                <div class="course-meta-item">
                                    <i class="fas fa-book"></i>
                                    <?php echo htmlspecialchars($row['nom_matiere']); ?>
                                </div>
                                <?php if ($is_eleve): ?>
                                <div class="course-meta-item">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    <?php echo htmlspecialchars($row['nom_professeur']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="course-description">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </div>
                            <div class="course-actions">
                                <div class="course-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('d/m/Y', strtotime($row['date_creation'])); ?>
                                </div>
                                <a href="voir_cours.php?id=<?php echo $row['id_cours']; ?>" class="course-view">
                                    عرض الدرس <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                            
                            <?php if ($is_prof): ?>
                            <div class="course-admin-actions">
                                <a href="modifier_cours.php?id=<?php echo $row['id_cours']; ?>" class="action-btn action-btn-edit" title="تعديل الدرس">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </a>
                                <a href="generer_pdf_cours.php?id=<?php echo $row['id_cours']; ?>" class="action-btn action-btn-pdf" title="تحميل كملف PDF" target="_blank">
                                    <i class="fas fa-file-pdf"></i>
                                    PDF
                                </a>
                                <a href="#" class="action-btn action-btn-delete" title="حذف الدرس" 
                                   onclick="confirmDelete(<?php echo $row['id_cours']; ?>, '<?php echo htmlspecialchars(addslashes($row['titre'])); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                    حذف
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-courses">
                    <i class="fas fa-book-open"></i>
                    <p><?php echo $is_prof ? 'لم تقم بإضافة أي دروس بعد.' : 'لا توجد دروس متاحة حاليًا.'; ?></p>
                    <?php if ($is_prof): ?>
                    <a href="ajouter_cours.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        إضافة درس جديد
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3 class="modal-title">تأكيد الحذف</h3>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف الدرس "<span id="courseTitleToDelete"></span>"؟</p>
                <p>هذا الإجراء لا يمكن التراجع عنه.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" onclick="closeDeleteModal()">
                    إلغاء
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash-alt"></i>
                    تأكيد الحذف
                </a>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour afficher le modal de confirmation de suppression
        function confirmDelete(courseId, courseTitle) {
            document.getElementById('courseTitleToDelete').textContent = courseTitle;
            document.getElementById('confirmDeleteBtn').href = 'supprimer_cours.php?id=' + courseId;
            document.getElementById('deleteModal').style.display = 'flex';
            return false;
        }
        
        // Fonction pour fermer le modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Fermer le modal si on clique en dehors
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('deleteModal')) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>
