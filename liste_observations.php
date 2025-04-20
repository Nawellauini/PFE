<?php
session_start();
include 'db_config.php'; 

if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];

// Récupérer les informations du professeur
$query_prof = "SELECT nom, prenom FROM professeurs WHERE id_professeur = ?";
$stmt_prof = $conn->prepare($query_prof);
$stmt_prof->bind_param("i", $id_professeur);
$stmt_prof->execute();
$result_prof = $stmt_prof->get_result();
$prof_info = $result_prof->fetch_assoc();
$nom_professeur = $prof_info ? $prof_info['prenom'] . ' ' . $prof_info['nom'] : 'الأستاذ(ة)';

// Récupération des classes enseignées par le professeur
$query_classes = "SELECT c.id_classe, c.nom_classe
                  FROM classes c
                  JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
                  WHERE pc.id_professeur = ?";
$stmt = $conn->prepare($query_classes);
$stmt->bind_param("i", $id_professeur);
$stmt->execute();
$result_classes = $stmt->get_result();

// Filtres
$classe_id = isset($_GET['classe_id']) ? $_GET['classe_id'] : null;
$eleve_id = isset($_GET['eleve_id']) ? $_GET['eleve_id'] : null;

// Récupérer le nom de la classe sélectionnée
$classe_nom = "";
if ($classe_id) {
    $query_classe = "SELECT nom_classe FROM classes WHERE id_classe = ?";
    $stmt = $conn->prepare($query_classe);
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $result_classe = $stmt->get_result();
    $classe_data = $result_classe->fetch_assoc();
    $classe_nom = $classe_data ? $classe_data['nom_classe'] : "";
}

// Récupération des élèves de la classe sélectionnée
$eleves_result = null;
if ($classe_id) {
    $query_eleves = "SELECT id_eleve, nom, prenom FROM eleves WHERE id_classe = ? ORDER BY nom, prenom";
    $stmt = $conn->prepare($query_eleves);
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $eleves_result = $stmt->get_result();
}

// Récupération des observations
$observations_result = null;
if ($classe_id) {
    $query_observations = "SELECT o.id, o.eleve_id, o.observation, o.date_observation, o.type_observation, e.nom, e.prenom
                          FROM observations o
                          JOIN eleves e ON o.eleve_id = e.id_eleve
                          WHERE o.classe_id = ?";
    
    $params = [$classe_id];
    $types = "i";
    
    if ($eleve_id) {
        $query_observations .= " AND o.eleve_id = ?";
        $params[] = $eleve_id;
        $types .= "i";
    }
    
    $query_observations .= " ORDER BY o.date_observation DESC";
    
    $stmt = $conn->prepare($query_observations);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $observations_result = $stmt->get_result();
}

// Traitement de l'ajout d'observations (depuis le modal)
$observation_added = false;
if (isset($_POST['ajouter_observation'])) {
    $observation = trim($_POST['observation']);
    $selected_eleves = $_POST['eleves'] ?? [];
    $modal_classe_id = $_POST['classe_id'] ?? null;
    $type_observation = $_POST['type_observation'] ?? 'individuelle';

    if (!empty($observation) && !empty($selected_eleves) && $modal_classe_id) {
        // Insérer l'observation pour les élèves sélectionnés
        foreach ($selected_eleves as $eleve_id) {
            $query_observation = "INSERT INTO observations (classe_id, eleve_id, type_observation, observation, date_observation)
                                VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query_observation);
            if ($stmt === false) {
                die("Erreur de préparation de la requête: " . $conn->error);
            }
            $stmt->bind_param("iiss", $modal_classe_id, $eleve_id, $type_observation, $observation);
            $stmt->execute();
        }
        $observation_added = true;
        
        // Rediriger pour éviter la resoumission du formulaire
        header("Location: liste_observations.php?classe_id=" . $modal_classe_id . "&success=add");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الملاحظات - نظام إدارة المدرسة</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --success-color: #27ae60;
            --border-radius: 12px;
            --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background-color: white;
            box-shadow: var(--box-shadow);
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .user-name {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .user-role {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            margin-bottom: 10px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: var(--border-radius);
            color: var(--dark-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .nav-link:hover {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
        }
        
        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .nav-icon {
            margin-left: 10px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-right: 280px;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .page-title-icon {
            margin-left: 10px;
            color: var(--primary-color);
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-label {
            font-weight: bold;
            margin-bottom: 8px;
            color: var(--dark-color);
        }
        
        .form-select, .form-control {
            border-radius: var(--border-radius);
            border: 2px solid #e9ecef;
            padding: 10px 15px;
            transition: var(--transition);
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .form-select:disabled, .form-control:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: bold;
            padding: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .student-name {
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .btn {
            border-radius: var(--border-radius);
            padding: 10px 20px;
            font-weight: bold;
            transition: var(--transition);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
        }
        
        .btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .selection-summary {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .summary-icon {
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        
        .summary-text {
            font-weight: bold;
        }
        
        .summary-value {
            color: var(--dark-color);
            background-color: white;
            padding: 5px 10px;
            border-radius: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .empty-state-text {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .empty-state-subtext {
            color: #7f8c8d;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .observation-text {
            white-space: pre-line;
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
        }
        
        .observation-text.expanded {
            max-height: none;
        }
        
        .expand-btn {
            color: var(--primary-color);
            cursor: pointer;
            font-weight: bold;
            display: inline-block;
            margin-top: 5px;
        }
        
        .date-display {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .date-primary {
            font-weight: bold;
        }
        
        .date-secondary {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
        }
        
        .modal-header {
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
        }
        
        .modal-title {
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            border-top: 1px solid #eee;
            padding: 15px 20px;
        }
        
        /* Styles pour le modal d'ajout d'observation */
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: var(--border-radius);
        }
        
        .custom-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-label {
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 0;
        }
        
        .selected-count {
            margin-right: auto;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: none;
        }
        
        .observation-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .submit-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .submit-btn {
            padding: 12px 30px;
            font-size: 1.1rem;
            min-width: 200px;
        }
        
        .modal-lg {
            max-width: 800px;
        }
        
        .modal-dialog-scrollable .modal-content {
            max-height: 90vh;
        }
        
        .table-responsive-modal {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .table-responsive-modal::-webkit-scrollbar {
            width: 8px;
        }
        
        .table-responsive-modal::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .table-responsive-modal::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }
        
        .table-responsive-modal::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }
        
        .observation-type {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-right: 10px;
        }
        
        .type-individual {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .type-all-classes {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        
        .type-icon {
            margin-left: 4px;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                padding: 15px 10px;
            }
            
            .logo, .user-info, .nav-text {
                display: none;
            }
            
            .sidebar-header {
                padding-bottom: 10px;
                margin-bottom: 10px;
            }
            
            .nav-link {
                justify-content: center;
                padding: 12px;
            }
            
            .nav-icon {
                margin: 0;
            }
            
            .main-content {
                margin-right: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px;
            }
            
            .logo, .user-info, .nav-text {
                display: block;
            }
            
            .nav-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .nav-item {
                margin-bottom: 0;
                flex: 1;
                min-width: 120px;
            }
            
            .nav-link {
                flex-direction: column;
                text-align: center;
                padding: 10px;
                height: 100%;
            }
            
            .nav-icon {
                margin: 0 0 5px 0;
            }
            
            .main-content {
                margin-right: 0;
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">مدرستي</div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    if ($prof_info) {
                        echo substr($prof_info['prenom'], 0, 1) . substr($prof_info['nom'], 0, 1);
                    } else {
                        echo "أ";
                    }
                    ?>
                </div>
                <div class="user-name"><?php echo htmlspecialchars($nom_professeur); ?></div>
                <div class="user-role">أستاذ(ة)</div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home nav-icon"></i>
                        <span class="nav-text">الرئيسية</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="openAddObservationModal(); return false;">
                        <i class="fas fa-plus-circle nav-icon"></i>
                        <span class="nav-text">إضافة ملاحظة</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="liste_observations.php" class="nav-link active">
                        <i class="fas fa-clipboard-list nav-icon"></i>
                        <span class="nav-text">قائمة الملاحظات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt nav-icon"></i>
                        <span class="nav-text">تسجيل الخروج</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-clipboard-list page-title-icon"></i>
                    قائمة الملاحظات
                </h1>
                <div class="header-actions">
                    <button type="button" class="btn btn-primary btn-icon" onclick="openAddObservationModal()">
                        <i class="fas fa-plus"></i>
                        إضافة ملاحظة جديدة
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <span>تصفية الملاحظات</span>
                    <div class="card-header-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                </div>
                <div class="card-body">
                    <form method="get" id="filter-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="classe_id" class="form-label">
                                    <i class="fas fa-chalkboard-teacher me-1"></i>
                                    القسم:
                                </label>
                                <select name="classe_id" id="classe_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- اختر القسم --</option>
                                    <?php 
                                    // Reset result pointer
                                    $result_classes->data_seek(0);
                                    while ($row = $result_classes->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $row['id_classe'] ?>" <?= $classe_id == $row['id_classe'] ? 'selected' : '' ?>>
                                            <?= $row['nom_classe'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="eleve_id" class="form-label">
                                    <i class="fas fa-user-graduate me-1"></i>
                                    الطالب:
                                </label>
                                <select name="eleve_id" id="eleve_id" class="form-select" <?= !$classe_id ? 'disabled' : '' ?> onchange="this.form.submit()">
                                    <option value="">-- جميع الطلاب --</option>
                                    <?php if ($eleves_result): ?>
                                        <?php while ($row = $eleves_result->fetch_assoc()): ?>
                                            <option value="<?= $row['id_eleve'] ?>" <?= $eleve_id == $row['id_eleve'] ? 'selected' : '' ?>>
                                                <?= $row['nom'] . ' ' . $row['prenom'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                        <?php $eleves_result->data_seek(0); // Reset result pointer ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($classe_id): ?>
                <div class="selection-summary">
                    <i class="fas fa-chalkboard-teacher summary-icon"></i>
                    <span class="summary-text">القسم:</span>
                    <span class="summary-value"><?php echo htmlspecialchars($classe_nom); ?></span>
                    
                    <?php if ($eleve_id && $eleves_result): ?>
                        <?php 
                        $eleve_nom = "";
                        while ($row = $eleves_result->fetch_assoc()) {
                            if ($row['id_eleve'] == $eleve_id) {
                                $eleve_nom = $row['nom'] . ' ' . $row['prenom'];
                                break;
                            }
                        }
                        $eleves_result->data_seek(0); // Reset result pointer
                        ?>
                        <i class="fas fa-user-graduate summary-icon"></i>
                        <span class="summary-text">الطالب:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($eleve_nom); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($observations_result && $observations_result->num_rows > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <span>قائمة الملاحظات</span>
                        <div class="card-header-icon">
                            <i class="fas fa-list"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px">#</th>
                                        <?php if (!$eleve_id): ?>
                                            <th>الطالب</th>
                                        <?php endif; ?>
                                        <th>الملاحظة</th>
                                        <th>النوع</th>
                                        <th>التاريخ</th>
                                        <th style="width: 180px">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1;
                                    while ($row = $observations_result->fetch_assoc()): 
                                        $initials = substr($row['prenom'], 0, 1) . substr($row['nom'], 0, 1);
                                        
                                        // Générer une couleur de fond basée sur les initiales
                                        $hash = 0;
                                        foreach (str_split($initials) as $char) {
                                            $hash = ord($char) + (($hash << 5) - $hash);
                                        }
                                        $hue = $hash % 360;
                                        $background_color = "hsl($hue, 70%, 80%)";
                                        $text_color = "hsl($hue, 70%, 30%)";
                                    ?>
                                        <tr>
                                            <td><?= $counter++ ?></td>
                                            <?php if (!$eleve_id): ?>
                                                <td>
                                                    <div class="student-name">
                                                        <div class="student-avatar" style="background-color: <?= $background_color ?>; color: <?= $text_color ?>;">
                                                            <?= $initials ?>
                                                        </div>
                                                        <?= htmlspecialchars($row['nom'] . ' ' . $row['prenom']) ?>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <div class="observation-text" id="observation-<?= $row['id'] ?>">
                                                    <?= nl2br(htmlspecialchars($row['observation'])) ?>
                                                </div>
                                                <?php if (strlen($row['observation']) > 150): ?>
                                                    <span class="expand-btn" onclick="toggleObservation('observation-<?= $row['id'] ?>')">عرض المزيد</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['type_observation'] == 'toutes_classes'): ?>
                                                    <span class="observation-type type-all-classes">
                                                        <i class="fas fa-users type-icon"></i>
                                                        لجميع الطلاب
                                                    </span>
                                                <?php else: ?>
                                                    <span class="observation-type type-individual">
                                                        <i class="fas fa-user type-icon"></i>
                                                        شخصية
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="date-display">
                                                    <span class="date-primary"><?= date('Y-m-d', strtotime($row['date_observation'])) ?></span>
                                                    <span class="date-secondary"><?= date('H:i', strtotime($row['date_observation'])) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-sm btn-primary view-btn" data-id="<?= $row['id'] ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success edit-btn" data-id="<?=  $row['id'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="<?= $row['id'] ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php elseif ($classe_id): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list empty-state-icon"></i>
                    <div class="empty-state-text">لا توجد ملاحظات</div>
                    <div class="empty-state-subtext">لم يتم العثور على أي ملاحظات تطابق معايير البحث</div>
                    <button type="button" class="btn btn-primary mt-3" onclick="openAddObservationModal()">
                        <i class="fas fa-plus me-2"></i>
                        إضافة ملاحظة جديدة
                    </button>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-filter empty-state-icon"></i>
                    <div class="empty-state-text">يرجى اختيار القسم</div>
                    <div class="empty-state-subtext">قم باختيار القسم لعرض الملاحظات المسجلة</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-list me-2"></i>
                        تفاصيل الملاحظة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        تعديل الملاحظة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash-alt me-2"></i>
                        حذف الملاحظة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center mb-0">هل أنت متأكد من رغبتك في حذف هذه الملاحظة؟</p>
                    <p class="text-center text-danger">هذا الإجراء لا يمكن التراجع عنه.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <form id="deleteForm" method="post" action="supprimer_observation.php">
                        <input type="hidden" name="observation_id" id="delete_observation_id">
                        <button type="submit" class="btn btn-danger">حذف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Observation Modal -->
    <div class="modal fade" id="addObservationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        إضافة ملاحظة جديدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="observation-form">
                        <div class="mb-3">
                            <label for="modal_classe_id" class="form-label">
                                <i class="fas fa-chalkboard-teacher me-1"></i>
                                اختر القسم:
                            </label>
                            <select name="classe_id" id="modal_classe_id" class="form-select" required onchange="loadStudents()">
                                <option value="">-- اختر القسم --</option>
                                <?php 
                                // Reset result pointer
                                $result_classes->data_seek(0);
                                while ($row = $result_classes->fetch_assoc()): 
                                ?>
                                    <option value="<?= $row['id_classe'] ?>">
                                        <?= $row['nom_classe'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div id="students-container" style="display: none;">
                            <div class="checkbox-container">
                                <input type="checkbox" id="select_all" class="custom-checkbox" onclick="toggleAll(this)"> 
                                <label for="select_all" class="checkbox-label">تحديد الكل</label>
                                <span class="selected-count" id="selected-count">0 طالب محدد</span>
                            </div>
                            
                            <div class="table-responsive-modal mb-4">
                                <table class="table" id="students-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px">اختيار</th>
                                            <th>الطالب</th>
                                        </tr>
                                    </thead>
                                    <tbody id="students-list">
                                        <!-- Students will be loaded here via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="type_observation" class="form-label">نوع الملاحظة:</label>
                                <select name="type_observation" id="type_observation" class="form-control" required>
                                    <option value="individuelle">ملاحظة فردية</option>
                                    <option value="toutes_classes">ملاحظة لجميع الطلاب</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="observation" class="form-label">
                                    <i class="fas fa-comment-alt me-1"></i>
                                    أدخل الملاحظة:
                                </label>
                                <textarea name="observation" id="observation" class="form-control observation-textarea" required placeholder="اكتب ملاحظتك هنا..."></textarea>
                            </div>
                            
                            <div class="submit-container">
                                <button type="submit" name="ajouter_observation" class="btn btn-success btn-icon submit-btn" id="submit-btn" disabled>
                                    <i class="fas fa-plus"></i>
                                    إضافة الملاحظة
                                </button>
                            </div>
                        </div>
                        
                        <div id="loading-students" style="display: none; text-align: center; padding: 20px;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">جاري تحميل الطلاب...</span>
                            </div>
                            <p class="mt-2">جاري تحميل قائمة الطلاب...</p>
                        </div>
                        
                        <div id="no-students" style="display: none; text-align: center; padding: 20px;">
                            <i class="fas fa-users empty-state-icon"></i>
                            <div class="empty-state-text">لا يوجد طلاب في هذا القسم</div>
                            <div class="empty-state-subtext">يرجى التحقق من قائمة الطلاب في النظام أو اختيار قسم آخر</div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialiser les modals Bootstrap
        var viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
        var editModal = new bootstrap.Modal(document.getElementById('editModal'));
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        var addObservationModal = new bootstrap.Modal(document.getElementById('addObservationModal'));
        
        // Fonction pour afficher/masquer le texte complet d'une observation
        function toggleObservation(id) {
            const element = document.getElementById(id);
            element.classList.toggle('expanded');
            
            const button = element.nextElementSibling;
            if (element.classList.contains('expanded')) {
                button.textContent = 'عرض أقل';
            } else {
                button.textContent = 'عرض المزيد';
            }
        }
        
        // Fonction pour ouvrir le modal d'ajout d'observation
        function openAddObservationModal() {
            // Réinitialiser le formulaire
            document.getElementById('observation-form').reset();
            document.getElementById('students-container').style.display = 'none';
            document.getElementById('loading-students').style.display = 'none';
            document.getElementById('no-students').style.display = 'none';
            
            // Afficher le modal
            addObservationModal.show();
        }
        
        // Fonction pour charger les élèves d'une classe
        function loadStudents() {
            const classeId = document.getElementById('modal_classe_id').value;
            
            if (!classeId) {
                document.getElementById('students-container').style.display = 'none';
                document.getElementById('loading-students').style.display = 'none';
                document.getElementById('no-students').style.display = 'none';
                return;
            }
            
            // Afficher l'indicateur de chargement
            document.getElementById('students-container').style.display = 'none';
            document.getElementById('loading-students').style.display = 'block';
            document.getElementById('no-students').style.display = 'none';
            
            // Charger les élèves via AJAX
            fetch('get_elevess.php?classe_id=' + classeId)
                .then(response => response.json())
                .then(data => {
                    // Masquer l'indicateur de chargement
                    document.getElementById('loading-students').style.display = 'none';
                    
                    if (data.length > 0) {
                        // Générer les lignes du tableau
                        const tableBody = document.getElementById('students-list');
                        tableBody.innerHTML = '';
                        
                        data.forEach(eleve => {
                            const initials = eleve.prenom.charAt(0) + eleve.nom.charAt(0);
                            
                            // Générer une couleur de fond basée sur les initiales
                            let hash = 0;
                            for (let i = 0; i < initials.length; i++) {
                                hash = initials.charCodeAt(i) + ((hash << 5) - hash);
                            }
                            const hue = hash % 360;
                            const backgroundColor = `hsl(${hue}, 70%, 80%)`;
                            const textColor = `hsl(${hue}, 70%, 30%)`;
                            
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td class="text-center">
                                    <input type="checkbox" name="eleves[]" value="${eleve.id_eleve}" class="custom-checkbox eleve-checkbox">
                                </td>
                                <td>
                                    <div class="student-name">
                                        <div class="student-avatar" style="background-color: ${backgroundColor}; color: ${textColor};">${initials}</div>
                                        ${eleve.nom} ${eleve.prenom}
                                    </div>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });
                        
                        // Afficher le conteneur des élèves
                        document.getElementById('students-container').style.display = 'block';
                        
                        // Initialiser les événements pour les cases à cocher
                        initCheckboxEvents();
                    } else {
                        // Afficher le message "pas d'élèves"
                        document.getElementById('no-students').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('loading-students').style.display = 'none';
                    document.getElementById('no-students').style.display = 'block';
                });
        }
        
        // Fonction pour initialiser les événements des cases à cocher
        function initCheckboxEvents() {
            // Mettre à jour le compteur lorsqu'une case est cochée
            $('.eleve-checkbox').change(function() {
                updateSelectedCount();
            });
        }
        
        // Fonction pour mettre à jour le compteur de sélection
        function updateSelectedCount() {
            const count = $('.eleve-checkbox:checked').length;
            $('#selected-count').text(count + ' طالب محدد');
            
            if (count > 0) {
                $('#selected-count').show();
                $('#submit-btn').prop('disabled', false);
            } else {
                $('#selected-count').hide();
                $('#submit-btn').prop('disabled', true);
            }
        }
        
        // Fonction pour sélectionner/désélectionner tous les élèves
        function toggleAll(source) {
            const checkboxes = document.getElementsByName('eleves[]');
            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
            
            // Mettre à jour le compteur
            updateSelectedCount();
        }
        
        // Gestionnaire pour le bouton de visualisation
        $(document).on('click', '.view-btn', function() {
            const id = $(this).data('id');
            $('#viewModalBody').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div></div>');
            
            // Charger les détails de l'observation
            $.ajax({
                url: 'voir_observation.php',
                type: 'GET',
                data: { id: id },
                success: function(response) {
                    $('#viewModalBody').html(response);
                },
                error: function() {
                    $('#viewModalBody').html('<div class="alert alert-danger">حدث خطأ أثناء تحميل البيانات</div>');
                }
            });
            
            viewModal.show();
        });
        
        // Gestionnaire pour le bouton de modification
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            $('#editModalBody').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div></div>');
            
            // Charger le formulaire de modification
            $.ajax({
                url: 'modifier_observation.php',
                type: 'GET',
                data: { id: id },
                success: function(response) {
                    $('#editModalBody').html(response);
                    
                    // Ajouter un gestionnaire pour le formulaire de modification
                    $('#editModalBody form').on('submit', function(e) {
                        e.preventDefault();
                        
                        $.ajax({
                            url: $(this).attr('action'),
                            type: 'POST',
                            data: $(this).serialize(),
                            success: function(response) {
                                if (response.includes('alert-success')) {
                                    $('#editModalBody').html(response);
                                    // Recharger la page après un court délai
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    $('#editModalBody').html(response);
                                }
                            },
                            error: function() {
                                $('#editModalBody').html('<div class="alert alert-danger">حدث خطأ أثناء تحديث الملاحظة</div>');
                            }
                        });
                    });
                },
                error: function() {
                    $('#editModalBody').html('<div class="alert alert-danger">حدث خطأ أثناء تحميل البيانات</div>');
                }
            });
            
            editModal.show();
        });
        
        // Gestionnaire pour le bouton de suppression
        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            $('#delete_observation_id').val(id);
            deleteModal.show();
        });
        
        // Vérifier si le formulaire d'ajout d'observation est valide avant de soumettre
        $('#observation-form').submit(function(e) {
            const selectedEleves = $('.eleve-checkbox:checked').length;
            const observation = $('#observation').val().trim();
            
            if (selectedEleves === 0) {
                e.preventDefault();
                Swal.fire({
                    title: "تنبيه!",
                    text: "يرجى اختيار طالب واحد على الأقل",
                    icon: "warning",
                    confirmButtonText: "موافق"
                });
                return false;
            }
            
            if (observation === '') {
                e.preventDefault();
                Swal.fire({
                    title: "تنبيه!",
                    text: "يرجى إدخال ملاحظة",
                    icon: "warning",
                    confirmButtonText: "موافق"
                });
                return false;
            }
            
            return true;
        });
        
        // Fonction pour adapter l'interface en fonction de la taille de l'écran
        function adjustLayout() {
            const width = window.innerWidth;
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (width <= 992) {
                sidebar.classList.add('collapsed');
                mainContent.style.marginRight = '70px';
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.style.marginRight = '280px';
            }
            
            if (width <= 768) {
                mainContent.style.marginRight = '0';
            }
        }
        
        // Appliquer l'ajustement au chargement et au redimensionnement
        window.addEventListener('load', adjustLayout);
        window.addEventListener('resize', adjustLayout);
        
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({
                title: "نجاح!",
                text: "<?= $_GET['success'] == 'edit' ? 'تم تعديل الملاحظة بنجاح' : ($_GET['success'] == 'delete' ? 'تم حذف الملاحظة بنجاح' : ($_GET['success'] == 'add' ? 'تمت إضافة الملاحظة بنجاح' : 'تمت العملية بنجاح')) ?>",
                icon: "success",
                confirmButtonText: "حسنًا"
            });
        <?php endif; ?>
    </script>
</body>
</html>
