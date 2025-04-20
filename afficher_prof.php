<?php
include 'db_config.php';


// Récupération du filtre de matière
$matiere_filter = isset($_GET['matiere_id']) ? intval($_GET['matiere_id']) : 0;

// Récupération des matières pour le menu déroulant
$matieres_result = $conn->query("SELECT matiere_id, nom FROM matieres ORDER BY nom");

// Récupération des professeurs avec filtrage par matière et/ou lettre
$sql = "SELECT p.*, m.nom AS matiere, GROUP_CONCAT(c.nom_classe SEPARATOR ', ') AS classes, 
        SUBSTRING(p.nom, 1, 1) as premiere_lettre 
        FROM professeurs p
        LEFT JOIN matieres m ON p.matiere_id = m.matiere_id
        LEFT JOIN professeurs_classes pc ON p.id_professeur = pc.id_professeur
        LEFT JOIN classes c ON pc.id_classe = c.id_classe
        WHERE 1=1";

if ($matiere_filter > 0) {
    $sql .= " AND p.matiere_id = $matiere_filter";  
}

// Filtre par lettre si spécifié
if (isset($_GET['letter']) && $_GET['letter'] != 'all') {
    $letter = $conn->real_escape_string($_GET['letter']);
    $sql .= " AND p.nom LIKE '$letter%'";
}

// Grouper par professeur
$sql .= " GROUP BY p.id_professeur";

// Tri par première lettre, puis par nom complet
$sql .= " ORDER BY premiere_lettre, p.nom, p.prenom";
$professeurs_result = $conn->query($sql);

// Message de statut pour les opérations
$status_message = '';
$status_type = '';

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = $_POST['id_professeur'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $login = $_POST['login'];
    $mp = $_POST['mp'];
    $matiere_id = $_POST['matiere_id'];
    $classes = isset($_POST['classes']) ? $_POST['classes'] : [];

    // Récupérer les anciennes informations pour les comparer
    $query_old = "SELECT p.*, m.nom as nom_matiere 
                 FROM professeurs p 
                 LEFT JOIN matieres m ON p.matiere_id = m.matiere_id 
                 WHERE p.id_professeur = ?";
    $stmt_old = $conn->prepare($query_old);
    $stmt_old->bind_param("i", $id);
    $stmt_old->execute();
    $old_data = $stmt_old->get_result()->fetch_assoc();

    $stmt = $conn->prepare("UPDATE professeurs SET nom=?, prenom=?, email=?, login=?, mot_de_passe=?, matiere_id=? WHERE id_professeur=?");
    $stmt->bind_param("sssssii", $nom, $prenom, $email, $login, $mp, $matiere_id, $id);
    
    if ($stmt->execute()) {
        $conn->query("DELETE FROM professeurs_classes WHERE id_professeur = $id");
        foreach ($classes as $id_classe) {
            $conn->query("INSERT INTO professeurs_classes (id_professeur, id_classe) VALUES ($id, $id_classe)");
        }

        // Récupérer le nom de la nouvelle matière
        $query_matiere = "SELECT nom FROM matieres WHERE matiere_id = ?";
        $stmt_matiere = $conn->prepare($query_matiere);
        $stmt_matiere->bind_param("i", $matiere_id);
        $stmt_matiere->execute();
        $result_matiere = $stmt_matiere->get_result();
        $matiere = $result_matiere->fetch_assoc();
        
        // Préparer le message des changements
        $changes = [];
        if ($old_data['nom'] !== $nom || $old_data['prenom'] !== $prenom) {
            $changes[] = "الاسم: من {$old_data['nom']} {$old_data['prenom']} إلى {$nom} {$prenom}";
        }
        if ($old_data['email'] !== $email) {
            $changes[] = "البريد الإلكتروني: من {$old_data['email']} إلى {$email}";
        }
        if ($old_data['matiere_id'] != $matiere_id) {
            $changes[] = "المادة: من {$old_data['nom_matiere']} إلى {$matiere['nom']}";
        }
        if ($old_data['login'] !== $login) {
            $changes[] = "اسم المستخدم: من {$old_data['login']} إلى {$login}";
        }
        if ($old_data['mot_de_passe'] !== $mp) {
            $changes[] = "تم تغيير كلمة المرور";
        }

        // Envoyer l'email si des changements ont été effectués
        if (!empty($changes)) {
            require_once 'send_email.php';
            sendModificationEmail($email, $nom, $prenom, $changes, $mp);
        }

        $status_message = "تم تحديث معلومات الأستاذ بنجاح";
        $status_type = "success";
    } else {
        $status_message = "حدث خطأ أثناء تحديث المعلومات: " . $conn->error;
        $status_type = "error";
    }
    
    // Redirection pour éviter la resoumission du formulaire
    header("Location: afficher_prof.php?message=" . urlencode($status_message) . "&type=" . $status_type . ($matiere_filter > 0 ? "&matiere_id=" . $matiere_filter : ""));
    exit;
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id = $_POST['id_professeur'];

    if ($conn->query("DELETE FROM professeurs_classes WHERE id_professeur = $id") && $conn->query("DELETE FROM professeurs WHERE id_professeur = $id")) {
        $status_message = "تم حذف الأستاذ بنجاح";
        $status_type = "success";
    } else {
        $status_message = "حدث خطأ أثناء حذف الأستاذ: " . $conn->error;
        $status_type = "error";
    }
    
    // Redirection pour éviter la resoumission du formulaire
    header("Location: afficher_prof.php?message=" . urlencode($status_message) . "&type=" . $status_type . ($matiere_filter > 0 ? "&matiere_id=" . $matiere_filter : ""));
    exit;
}

// Récupération des messages de statut depuis l'URL
if (isset($_GET['message'])) {
    $status_message = $_GET['message'];
    $status_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}

// Récupérer toutes les classes pour le formulaire d'édition
$classes_result = $conn->query("SELECT id_classe, nom_classe FROM classes ORDER BY nom_classe");
$classes = [];
while ($classe = $classes_result->fetch_assoc()) {
    $classes[] = $classe;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الأساتذة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a5276;
            --primary-light: #2980b9;
            --secondary-color: #27ae60;
            --secondary-light: #2ecc71;
            --accent-color: #e67e22;
            --danger-color: #c0392b;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --text-color: #333;
            --border-radius: 6px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background-color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-left: 10px;
            color: var(--primary-color);
        }

        .btn-add {
            background-color: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-add:hover {
            background-color: var(--secondary-light);
        }

        .filter-container {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-label {
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            color: var(--dark-color);
        }

        .filter-label i {
            margin-left: 8px;
            color: var(--primary-color);
        }

        .filter-select {
            flex-grow: 1;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            color: var(--text-color);
            max-width: 300px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%232c3e50' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 10px center;
            background-size: 16px;
            padding-left: 30px;
        }

        .filter-select:focus {
            border-color: var(--primary-light);
            outline: none;
        }

        .filter-button {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-button:hover {
            background-color: var(--primary-light);
        }

        .professors-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header-count {
            background-color: white;
            color: var(--primary-color);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        .professors-table {
            width: 100%;
            border-collapse: collapse;
        }

        .professors-table th,
        .professors-table td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }

        .professors-table th {
            background-color: #f8f9fa;
            font-weight: 700;
            color: var(--dark-color);
            position: sticky;
            top: 0;
            font-size: 14px;
        }

        .professors-table tr:hover {
            background-color: #f8f9fa;
        }

        .professors-table tr:last-child td {
            border-bottom: none;
        }

        .professor-name {
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .professor-avatar {
            width: 36px;
            height: 36px;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .professor-email {
            color: #666;
            font-size: 13px;
        }

        .professor-subject {
            background-color: var(--light-color);
            color: var(--dark-color);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .professor-classes {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .class-badge {
            background-color: var(--primary-light);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: white;
            border: none;
            font-size: 14px;
        }

        .btn-view {
            background-color: var(--primary-color);
        }

        .btn-view:hover {
            background-color: var(--primary-light);
        }

        .btn-edit {
            background-color: var(--accent-color);
        }

        .btn-edit:hover {
            background-color: #d35400;
        }

        .btn-delete {
            background-color: var(--danger-color);
        }

        .btn-delete:hover {
            background-color: #a93226;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #777;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        /* Modales */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
            padding: 50px 0;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: white;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--light-color);
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background-color: #f8f9fa;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        /* Formulaires */
        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.1);
        }

        /* Boutons */
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #a93226;
        }

        .btn-success {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-success:hover {
            background-color: var(--secondary-light);
        }

        /* Messages d'alerte */
        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: slideDown 0.3s ease;
            box-shadow: var(--box-shadow);
        }

        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert i {
            margin-left: 10px;
            font-size: 18px;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
            border-right: 4px solid #27ae60;
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.2);
            color: #c0392b;
            border-right: 4px solid #c0392b;
        }

        .alert-info {
            background-color: rgba(52, 152, 219, 0.2);
            color: #2980b9;
            border-right: 4px solid #2980b9;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-select {
                max-width: 100%;
            }

            .professors-table th,
            .professors-table td {
                padding: 10px;
            }

            .professor-avatar {
                width: 30px;
                height: 30px;
                font-size: 14px;
            }

            .actions {
                flex-direction: row;
                gap: 5px;
            }

            .btn-icon {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }

            .modal-container {
                width: 95%;
            }

            .btn {
                padding: 8px 12px;
                font-size: 13px;
            }
        }

        /* Utilitaires */
        .text-center {
            text-align: center;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .mt-3 {
            margin-top: 15px;
        }

        .hidden {
            display: none;
        }
        
        /* Améliorations pour le modal d'édition */
        .edit-form-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .edit-form-section {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 15px;
            border: 1px solid #eee;
        }
        
        .edit-form-section h4 {
            margin-bottom: 15px;
            color: var(--primary-color);
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle-btn {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
            font-size: 14px;
        }
        
        .password-toggle-btn:hover {
            color: var(--primary-color);
        }

        /* Filtre alphabétique */
        .alphabet-filter {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .alphabet-filter-header {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            font-weight: 600;
            font-size: 14px;
        }

        .alphabet-filter-buttons {
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .alphabet-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            background-color: #f8f9fa;
            color: var(--dark-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: 1px solid #eee;
        }

        .alphabet-btn:hover {
            background-color: var(--primary-light);
            color: white;
        }

        .alphabet-btn.active {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chalkboard-teacher"></i>
                قائمة الأساتذة
            </h1>
            <a href="add_teacher.php" class="btn-add">
                <i class="fas fa-plus"></i>
                إضافة أستاذ جديد
            </a>
        </div>

        <?php if (!empty($status_message)): ?>
            <div class="alert alert-<?php echo $status_type; ?>">
                <i class="fas fa-<?php echo $status_type === 'success' ? 'check-circle' : ($status_type === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="">
            <div class="filter-container">
                <label for="matiere_id" class="filter-label">
                    <i class="fas fa-filter"></i>
                    تصفية حسب المادة:
                </label>
                <select name="matiere_id" id="matiere_id" class="filter-select">
                    <option value="0">جميع المواد</option>
                    <?php while ($matiere = $matieres_result->fetch_assoc()): ?>
                        <option value="<?php echo $matiere['matiere_id']; ?>" <?php echo ($matiere_filter == $matiere['matiere_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($matiere['nom']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="filter-button">
                    <i class="fas fa-search"></i>
                    تطبيق
                </button>
            </div>
        </form>

        <!-- Filtre alphabétique -->
        <div class="alphabet-filter">
            <div class="alphabet-filter-header">
                <span>تصفية حسب الحرف الأول:</span>
            </div>
            <div class="alphabet-filter-buttons">
                <a href="?<?php echo $matiere_filter > 0 ? 'matiere_id='.$matiere_filter.'&' : ''; ?>letter=all" class="alphabet-btn <?php echo !isset($_GET['letter']) || $_GET['letter'] == 'all' ? 'active' : ''; ?>">الكل</a>
                <?php
                // Lettres arabes courantes
                $arabic_letters = ['أ', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي'];
                
                foreach ($arabic_letters as $letter) {
                    $active = isset($_GET['letter']) && $_GET['letter'] == urlencode($letter) ? 'active' : '';
                    echo '<a href="?'.($matiere_filter > 0 ? 'matiere_id='.$matiere_filter.'&' : '').'letter='.urlencode($letter).'" class="alphabet-btn '.$active.'">'.$letter.'</a>';
                }
                ?>
            </div>
        </div>

        <div class="professors-card">
            <div class="card-header">
                <span>قائمة الأساتذة</span>
                <span class="card-header-count"><?php echo $professeurs_result->num_rows; ?> أستاذ</span>
            </div>
            <div class="table-responsive">
                <?php if ($professeurs_result->num_rows > 0): ?>
                    <table class="professors-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>المادة</th>
                                <th>الأقسام</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($prof = $professeurs_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="professor-name">
                                            <div class="professor-avatar" style="background-color: <?php echo '#' . substr(md5($prof['nom']), 0, 6); ?>">
                                                <?php echo $prof['premiere_lettre']; ?>
                                            </div>
                                            <?php echo htmlspecialchars($prof['nom'] . ' ' . $prof['prenom']); ?>
                                        </div>
                                    </td>
                                    <td class="professor-email"><?php echo !empty($prof['email']) ? htmlspecialchars($prof['email']) : '<em style="color: #999;">غير متوفر</em>'; ?></td>
                                    <td><span class="professor-subject"><?php echo htmlspecialchars($prof['matiere'] ?: 'غير محدد'); ?></span></td>
                                <td>
                                    <div class="professor-classes">
                                        <?php if (!empty($prof['classes'])): ?>
                                            <?php foreach (explode(', ', $prof['classes']) as $classe): ?>
                                                <span class="class-badge"><?php echo htmlspecialchars($classe); ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <em style="color: #999;">لا توجد أقسام</em>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button type="button" class="btn-icon btn-view" onclick="openDetailModal(<?php echo $prof['id_professeur']; ?>)" title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn-icon btn-edit" onclick="openEditModal(<?php echo $prof['id_professeur']; ?>)" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn-icon btn-delete" onclick="openDeleteModal(<?php echo $prof['id_professeur']; ?>, '<?php echo htmlspecialchars(addslashes($prof['nom'] . ' ' . $prof['prenom'])); ?>')" title="حذف">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>لا يوجد أساتذة لعرضهم</p>
                        <a href="add_teacher.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            إضافة أستاذ جديد
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de détails -->
    <div id="detailModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">تفاصيل الأستاذ</h3>
                <button type="button" class="modal-close" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="detailContent" class="professor-details">
                    <!-- Le contenu sera chargé dynamiquement -->
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary-color);"></i>
                        <p>جاري تحميل البيانات...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeModal('detailModal')">
                    <i class="fas fa-times"></i>
                    إغلاق
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de modification -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">تعديل معلومات الأستاذ</h3>
                <button type="button" class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm" method="POST">
                    <input type="hidden" name="id_professeur" id="edit_id_prof">
                    
                    <div class="edit-form-container">
                        <div class="edit-form-section">
                            <h4><i class="fas fa-user"></i> المعلومات الشخصية</h4>
                            <div class="form-group">
                                <label for="nom" class="form-label">الاسم:</label>
                                <input type="text" name="nom" id="edit_nom" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="prenom" class="form-label">اللقب:</label>
                                <input type="text" name="prenom" id="edit_prenom" class="form-control" required>
                            </div>
                            
                            <div class="form-group mb-0">
                                <label for="email" class="form-label">البريد الإلكتروني:</label>
                                <input type="email" name="email" id="edit_email" class="form-control" placeholder="اختياري">
                            </div>
                        </div>
                        
                        <div class="edit-form-section">
                            <h4><i class="fas fa-lock"></i> معلومات الحساب</h4>
                            <div class="form-group">
                                <label for="login" class="form-label">اسم المستخدم:</label>
                                <input type="text" name="login" id="edit_login" class="form-control" required>
                            </div>
                            
                            <div class="form-group mb-0">
                                <label for="mp" class="form-label">كلمة المرور:</label>
                                <div class="password-toggle">
                                    <input type="password" name="mp" id="edit_mp" class="form-control" required>
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility()">
                                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="edit-form-section">
                            <h4><i class="fas fa-book"></i> الأقسام والمادة</h4>
                            <div class="form-group">
                                <label for="classes" class="form-label">الأقسام:</label>
                                <select name="classes[]" id="edit_classes" class="form-control" multiple onchange="updateMatiereOptions()">
                                    <?php foreach ($classes as $classe): ?>
                                        <option value="<?php echo $classe['id_classe']; ?>"><?php echo htmlspecialchars($classe['nom_classe']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="display: block; margin-top: 5px; color: #666;">اضغط على Ctrl (أو Cmd على Mac) للاختيار المتعدد</small>
                            </div>
                            
                            <div class="form-group mb-0">
                                <label for="matiere_id" class="form-label">المادة:</label>
                                <select name="matiere_id" id="edit_matiere_id" class="form-control" required>
                                    <option value="">-- اختر المادة --</option>
                                    <!-- Les matières seront chargées dynamiquement -->
                                </select>
                                <div id="matiere-loading" style="display: none; margin-top: 5px; color: #666;">
                                    <i class="fas fa-spinner fa-spin"></i> جاري تحميل المواد...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">
                        <i class="fas fa-times"></i>
                        إلغاء
                    </button>
                    <button type="submit" name="update" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de suppression -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">تأكيد الحذف</h3>
                <button type="button" class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form id="deleteForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_professeur" id="delete_id_prof">
                    <p class="text-center">هل أنت متأكد من حذف الأستاذ <strong id="delete_professor_name"></strong>؟</p>
                    <p class="text-center mt-3" style="color: var(--danger-color);">
                        <i class="fas fa-exclamation-triangle"></i>
                        هذا الإجراء لا يمكن التراجع عنه.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">
                        <i class="fas fa-times"></i>
                        إلغاء
                    </button>
                    <button type="submit" name="delete" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i>
                        تأكيد الحذف
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fonction pour ouvrir la modale de détails
        function openDetailModal(id) {
            document.getElementById('detailModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Charger les détails via AJAX
            fetch('get_professor_details.php?id_professeur=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('detailContent').innerHTML = `
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                ${data.error}
                            </div>
                        `;
                        return;
                    }
                    
                    // Créer l'affichage des détails
                    let detailsHtml = `
                        <div class="edit-form-section">
                            <h4><i class="fas fa-user"></i> المعلومات الشخصية</h4>
                            <div class="form-group">
                                <label class="form-label">الاسم الكامل:</label>
                                <div>${data.nom} ${data.prenom}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">البريد الإلكتروني:</label>
                                <div>${data.email || 'غير متوفر'}</div>
                            </div>
                        </div>
                        
                        <div class="edit-form-section">
                            <h4><i class="fas fa-book"></i> المعلومات المهنية</h4>
                            <div class="form-group">
                                <label class="form-label">المادة:</label>
                                <div>${data.matiere || 'غير محدد'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">الأقسام:</label>
                                <div class="professor-classes">
                    `;
                    
                    if (data.classes) {
                        const classesList = data.classes.split(', ');
                        classesList.forEach(classe => {
                            detailsHtml += `<span class="class-badge">${classe}</span>`;
                        });
                    } else {
                        detailsHtml += 'لا توجد أقسام مسندة';
                    }
                    
                    detailsHtml += `
                                </div>
                            </div>
                        </div>
                        
                        <div class="edit-form-section">
                            <h4><i class="fas fa-lock"></i> معلومات تسجيل الدخول</h4>
                            <div class="form-group">
                                <label class="form-label">اسم المستخدم:</label>
                                <div>${data.login}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">كلمة المرور:</label>
                                <div>${data.mot_de_passe}</div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('detailContent').innerHTML = detailsHtml;
                })
                .catch(error => {
                    document.getElementById('detailContent').innerHTML = `
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.
                        </div>
                    `;
                    console.error('Error:', error);
                });
        }
        
        // Fonction pour ouvrir la modale de modification
        function openEditModal(id) {
            document.getElementById('editModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Récupérer les détails du professeur pour pré-remplir le formulaire
            fetch('get_professor_details.php?id_professeur=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        closeModal('editModal');
                        return;
                    }
                    
                    // Remplir le formulaire
                    document.getElementById('edit_id_prof').value = data.id_professeur;
                    document.getElementById('edit_nom').value = data.nom;
                    document.getElementById('edit_prenom').value = data.prenom;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_login').value = data.login;
                    document.getElementById('edit_mp').value = data.mot_de_passe;
                    
                    // Sélectionner la matière
                    const matiereSelect = document.getElementById('edit_matiere_id');
                    for (let i = 0; i < matiereSelect.options.length; i++) {
                        if (matiereSelect.options[i].value == data.matiere_id) {
                            matiereSelect.options[i].selected = true;
                            break;
                        }
                    }
                    
                    // Sélectionner les classes
                    if (data.classes) {
                        const classesList = data.classes.split(', ');
                        const classesSelect = document.getElementById('edit_classes');
                        
                        for (let i = 0; i < classesSelect.options.length; i++) {
                            const option = classesSelect.options[i];
                            if (classesList.some(classe => classe.includes(option.text))) {
                                option.selected = true;
                            }
                        }
                        
                        // Mettre à jour les matières en fonction des classes sélectionnées
                        updateMatiereOptions();
                        
                        // Sélectionner la matière après le chargement des options
                        setTimeout(() => {
                            const matiereSelect = document.getElementById('edit_matiere_id');
                            for (let i = 0; i < matiereSelect.options.length; i++) {
                                if (matiereSelect.options[i].value == data.matiere_id) {
                                    matiereSelect.options[i].selected = true;
                                    break;
                                }
                            }
                        }, 500);
                    }
                })
                .catch(error => {
                    alert('Erreur lors du chargement des données');
                    console.error('Error:', error);
                    closeModal('editModal');
                });
        }
        
        // Fonction pour ouvrir la modale de suppression
        function openDeleteModal(id, nom) {
            document.getElementById('delete_id_prof').value = id;
            document.getElementById('delete_professor_name').textContent = nom;
            
            document.getElementById('deleteModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour fermer les modales
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Fonction pour afficher/masquer le mot de passe
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('edit_mp');
            const passwordIcon = document.getElementById('password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Fonction pour mettre à jour les options de matières en fonction de la classe sélectionnée
        function updateMatiereOptions() {
            const classesSelect = document.getElementById('edit_classes');
            const matiereSelect = document.getElementById('edit_matiere_id');
            const matiereLoading = document.getElementById('matiere-loading');
            
            // Récupérer la classe sélectionnée (première classe si plusieurs sont sélectionnées)
            let selectedClassId = null;
            for (let i = 0; i < classesSelect.options.length; i++) {
                if (classesSelect.options[i].selected) {
                    selectedClassId = classesSelect.options[i].value;
                    break;
                }
            }
            
            // Si aucune classe n'est sélectionnée, vider le select des matières
            if (!selectedClassId) {
                while (matiereSelect.options.length > 1) {
                    matiereSelect.remove(1);
                }
                return;
            }
            
            // Afficher l'indicateur de chargement
            matiereLoading.style.display = 'block';
            
            // Récupérer les matières pour cette classe via AJAX
            fetch('get_matieres_by_classe.php?classe_id=' + selectedClassId)
                .then(response => response.json())
                .then(data => {
                    // Masquer l'indicateur de chargement
                    matiereLoading.style.display = 'none';
                    
                    // Vider le select des matières sauf la première option
                    while (matiereSelect.options.length > 1) {
                        matiereSelect.remove(1);
                    }
                    
                    // Ajouter les nouvelles options
                    data.forEach(matiere => {
                        const option = document.createElement('option');
                        option.value = matiere.matiere_id;
                        option.textContent = matiere.nom;
                        matiereSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    matiereLoading.style.display = 'none';
                    alert('حدث خطأ أثناء تحميل المواد');
                });
        }
        
        // Fermer les modales si on clique en dehors
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        
        // Fermer les alertes après 5 secondes
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(function() {
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
    </script>
</body>
</html>
