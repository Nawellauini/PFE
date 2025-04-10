<?php
session_start();
include 'db_config.php'; 

if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];
$error_message = '';

// Récupérer les informations du professeur
$query_prof = "SELECT nom, prenom FROM professeurs WHERE id_professeur = ?";
$stmt_prof = $conn->prepare($query_prof);
$stmt_prof->bind_param("i", $id_professeur);
$stmt_prof->execute();
$result_prof = $stmt_prof->get_result();
$prof_info = $result_prof->fetch_assoc();
$nom_professeur = $prof_info ? $prof_info['prenom'] . ' ' . $prof_info['nom'] : 'الأستاذ(ة)';

// Récupérer les classes du professeur
$query_classes = "SELECT c.id_classe, c.nom_classe
                  FROM classes c
                  JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
                  WHERE pc.id_professeur = ?";
$stmt = $conn->prepare($query_classes);
$stmt->bind_param("i", $id_professeur);
$stmt->execute();
$result_classes = $stmt->get_result();

$domaines = [];
$eleves_result = null;
$classe_id = $_POST['classe_id'] ?? null;
$domaine_id = $_POST['domaine_id'] ?? null;
$trimestre = $_POST['trimestre'] ?? null;
$remarque_added = false;

// Si une classe est sélectionnée, récupérer les domaines
if ($classe_id) {
    $query_domaines = "SELECT DISTINCT d.id, d.nom
                       FROM matieres m
                       JOIN domaines d ON m.domaine_id = d.id
                       WHERE m.classe_id = ?";
    $stmt = $conn->prepare($query_domaines);
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $result_domaines = $stmt->get_result();

    while ($row = $result_domaines->fetch_assoc()) {
        $domaines[] = $row;
    }

    // Si un domaine et un trimestre sont sélectionnés, récupérer les élèves
    if ($domaine_id && $trimestre) {
        $query_eleves = "SELECT e.id_eleve, e.nom, e.prenom, 
                         AVG(n.note) AS moyenne,
                         r.remarque
                         FROM eleves e
                         LEFT JOIN notes n ON e.id_eleve = n.id_eleve AND n.trimestre = ?
                         LEFT JOIN matieres m ON n.matiere_id = m.matiere_id AND m.domaine_id = ?
                         LEFT JOIN remarques r ON e.id_eleve = r.eleve_id AND r.domaine_id = ? AND r.trimestre = ?
                         WHERE e.id_classe = ?
                         GROUP BY e.id_eleve";
        
        // Vérifier si la colonne trimestre existe dans la table remarques
        $result_check_column = $conn->query("SHOW COLUMNS FROM remarques LIKE 'trimestre'");
        $trimestre_exists = $result_check_column->num_rows > 0;
        
        if ($trimestre_exists) {
            $stmt = $conn->prepare($query_eleves);
            $stmt->bind_param("iiiii", $trimestre, $domaine_id, $domaine_id, $trimestre, $classe_id);
        } else {
            // Si la colonne trimestre n'existe pas, utiliser une requête différente
            $query_eleves = "SELECT e.id_eleve, e.nom, e.prenom, 
                             AVG(n.note) AS moyenne,
                             r.remarque
                             FROM eleves e
                             LEFT JOIN notes n ON e.id_eleve = n.id_eleve AND n.trimestre = ?
                             LEFT JOIN matieres m ON n.matiere_id = m.matiere_id AND m.domaine_id = ?
                             LEFT JOIN remarques r ON e.id_eleve = r.eleve_id AND r.domaine_id = ?
                             WHERE e.id_classe = ?
                             GROUP BY e.id_eleve";
            $stmt = $conn->prepare($query_eleves);
            $stmt->bind_param("iiii", $trimestre, $domaine_id, $domaine_id, $classe_id);
        }
        
        $stmt->execute();
        $eleves_result = $stmt->get_result();
    }
}

// Récupérer le nom du trimestre
$trimestre_nom = "";
switch($trimestre) {
    case 1:
        $trimestre_nom = "الثلاثي الأول";
        break;
    case 2:
        $trimestre_nom = "الثلاثي الثاني";
        break;
    case 3:
        $trimestre_nom = "الثلاثي الثالث";
        break;
}

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

// Récupérer le nom du domaine sélectionné
$domaine_nom = "";
if ($domaine_id) {
    $query_domaine = "SELECT nom FROM domaines WHERE id = ?";
    $stmt = $conn->prepare($query_domaine);
    $stmt->bind_param("i", $domaine_id);
    $stmt->execute();
    $result_domaine = $stmt->get_result();
    $domaine_data = $result_domaine->fetch_assoc();
    $domaine_nom = $domaine_data ? $domaine_data['nom'] : "";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة ملاحظات - <?php echo $trimestre_nom ? $trimestre_nom : 'نظام إدارة المدرسة'; ?></title>

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
        
        .selection-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .selection-header {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .selection-icon {
            margin-left: 10px;
            color: var(--primary-color);
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
        
        .average-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            text-align: center;
            min-width: 60px;
        }
        
        .average-good {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }
        
        .average-medium {
            background-color: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }
        
        .average-bad {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .average-none {
            background-color: rgba(189, 195, 199, 0.2);
            color: #7f8c8d;
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
        
        .btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .save-all-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .save-all-btn {
            padding: 12px 30px;
            font-size: 1.1rem;
        }
        
        .selection-summary {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .summary-item {
            display: flex;
            align-items: center;
            background-color: white;
            padding: 10px 15px;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .summary-icon {
            margin-left: 8px;
            color: var(--primary-color);
        }
        
        .summary-label {
            font-weight: bold;
            margin-left: 5px;
        }
        
        .summary-value {
            color: var(--dark-color);
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
                    <a href="#" class="nav-link active">
                        <i class="fas fa-edit nav-icon"></i>
                        <span class="nav-text">إضافة ملاحظات</span>
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
                    <i class="fas fa-edit page-title-icon"></i>
                    إضافة ملاحظات
                </h1>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right"></i>
                        العودة
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                <span>اختيار القسم والمجال</span>
                    <div class="card-header-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" id="selection-form">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="classe_id" class="form-label">
                                        <i class="fas fa-chalkboard-teacher me-1"></i>
                                        اختر القسم:
                                    </label>
                                    <select name="classe_id" id="classe_id" class="form-select" required>
                                        <option value="">-- اختر القسم --</option>
                                        <?php while ($row = $result_classes->fetch_assoc()): ?>
                                            <option value="<?= $row['id_classe'] ?>" <?= $classe_id == $row['id_classe'] ? 'selected' : '' ?>>
                                                <?= $row['nom_classe'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="domaine_id" class="form-label">
                                        <i class="fas fa-book me-1"></i>
                                        اختر المجال:
                                    </label>
                                    <select name="domaine_id" id="domaine_id" class="form-select" <?= empty($domaines) ? 'disabled' : '' ?>>
                                        <option value="">-- اختر المجال --</option>
                                        <?php foreach ($domaines as $domaine): ?>
                                            <option value="<?= $domaine['id'] ?>" <?= $domaine_id == $domaine['id'] ? 'selected' : '' ?>>
                                                <?= $domaine['nom'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="trimestre" class="form-label">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        اختر الثلاثي:
                                    </label>
                                    <select name="trimestre" id="trimestre" class="form-select" <?= empty($domaine_id) ? 'disabled' : '' ?>>
                                        <option value="">-- اختر الثلاثي --</option>
                                        <option value="1" <?= $trimestre == '1' ? 'selected' : '' ?>>الثلاثي الأول</option>
                                        <option value="2" <?= $trimestre == '2' ? 'selected' : '' ?>>الثلاثي الثاني</option>
                                        <option value="3" <?= $trimestre == '3' ? 'selected' : '' ?>>الثلاثي الثالث</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($classe_id && $domaine_id && $trimestre): ?>
                <div class="selection-summary">
                    <div class="summary-item">
                        <i class="fas fa-chalkboard-teacher summary-icon"></i>
                        <span class="summary-label">القسم:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($classe_nom); ?></span>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-book summary-icon"></i>
                        <span class="summary-label">المجال:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($domaine_nom); ?></span>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-calendar-alt summary-icon"></i>
                        <span class="summary-label">الثلاثي:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($trimestre_nom); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($eleves_result && $eleves_result->num_rows > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <span>قائمة الطلاب</span>
                        <div class="card-header-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="eleves-table" class="table">
                                <thead>
                                    <tr>
                                        <th>الطالب</th>
                                        <th>معدل المجال</th>
                                        <th>الملاحظة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $eleves_result->fetch_assoc()): 
                                        $moyenne = $row['moyenne'] ? floatval($row['moyenne']) : null;
                                        $moyenne_class = '';
                                        
                                        if ($moyenne === null) {
                                            $moyenne_class = 'average-none';
                                        } elseif ($moyenne >= 14) {
                                            $moyenne_class = 'average-good';
                                        } elseif ($moyenne >= 10) {
                                            $moyenne_class = 'average-medium';
                                        } else {
                                            $moyenne_class = 'average-bad';
                                        }
                                        
                                        $initials = substr($row['prenom'], 0, 1) . substr($row['nom'], 0, 1);
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="student-name">
                                                    <div class="student-avatar"><?php echo $initials; ?></div>
                                                    <?php echo htmlspecialchars($row['nom'] . ' ' . $row['prenom']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($moyenne !== null): ?>
                                                    <span class="average-badge <?php echo $moyenne_class; ?>">
                                                        <?php echo number_format($moyenne, 2); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="average-badge average-none">
                                                        لا توجد علامات
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <textarea class="form-control remarque-text" 
                                                          data-eleve-id="<?= $row['id_eleve'] ?>" 
                                                          data-domaine-id="<?= $domaine_id ?>"
                                                          rows="2"
                                                          placeholder="أدخل ملاحظة..."><?= htmlspecialchars($row['remarque'] ?? '') ?></textarea>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-icon save-remarque">
                                                    <i class="fas fa-save"></i>
                                                    حفظ
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="save-all-container">
                            <button type="button" id="save-all" class="btn btn-success btn-icon save-all-btn">
                                <i class="fas fa-save"></i>
                                حفظ جميع الملاحظات
                            </button>
                        </div>
                    </div>
                </div>
            <?php elseif ($domaine_id && $trimestre): ?>
                <div class="empty-state">
                    <i class="fas fa-search empty-state-icon"></i>
                    <div class="empty-state-text">لا توجد بيانات للطلاب في هذا المجال والثلاثي</div>
                    <div class="empty-state-subtext">يرجى التحقق من اختيارك أو إضافة بيانات للطلاب أولاً</div>
                </div>
            <?php elseif ($classe_id): ?>
                <div class="empty-state">
                    <i class="fas fa-filter empty-state-icon"></i>
                    <div class="empty-state-text">يرجى اختيار المجال والثلاثي</div>
                    <div class="empty-state-subtext">قم باختيار المجال والثلاثي لعرض قائمة الطلاب وإضافة الملاحظات</div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chalkboard-teacher empty-state-icon"></i>
                    <div class="empty-state-text">يرجى اختيار القسم أولاً</div>
                    <div class="empty-state-subtext">قم باختيار القسم لعرض المجالات المتاحة</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Soumettre le formulaire lorsqu'un champ change
            $('#classe_id').change(function() {
                $('#domaine_id').prop('disabled', true);
                $('#trimestre').prop('disabled', true);
                $('#selection-form').submit();
            });
            
            $('#domaine_id').change(function() {
                $('#trimestre').prop('disabled', false);
                if ($('#trimestre').val()) {
                    $('#selection-form').submit();
                }
            });
            
            $('#trimestre').change(function() {
                $('#selection-form').submit();
            });
            
            // Sauvegarder une remarque individuelle
            $('.save-remarque').click(function() {
                const btn = $(this);
                const row = btn.closest('tr');
                const textarea = row.find('.remarque-text');
                const eleve_id = textarea.data('eleve-id');
                const domaine_id = textarea.data('domaine-id');
                const remarque = textarea.val();
                const trimestre = $('#trimestre').val();
                
                if (!trimestre) {
                    Swal.fire({
                        title: "تنبيه!",
                        text: "يرجى اختيار الثلاثي أولاً",
                        icon: "warning",
                        confirmButtonText: "موافق"
                    });
                    return;
                }
                
                // Afficher un indicateur de chargement
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');
                
                const remarques = [{
                    eleve_id: eleve_id,
                    domaine_id: domaine_id,
                    remarque: remarque
                }];
                
                $.ajax({
                    type: 'POST',
                    url: 'save_remarques.php',
                    data: {
                        remarques: JSON.stringify(remarques),
                        trimestre: trimestre
                    },
                    dataType: 'json',
                    success: function(response) {
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> حفظ');
                        
                        if (response.success) {
                            Swal.fire({
                                title: "نجاح!",
                                text: "تم حفظ الملاحظة بنجاح",
                                icon: "success",
                                confirmButtonText: "موافق",
                                timer: 1500
                            });
                        } else {
                            Swal.fire({
                                title: "خطأ!",
                                text: response.message,
                                icon: "error",
                                confirmButtonText: "موافق"
                            });
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> حفظ');
                        
                        Swal.fire({
                            title: "خطأ!",
                            text: "حدث خطأ أثناء حفظ الملاحظة",
                            icon: "error",
                            confirmButtonText: "موافق"
                        });
                    }
                });
            });
            
            // Sauvegarder toutes les remarques
            $('#save-all').click(function() {
                const trimestre = $('#trimestre').val();
                const btn = $(this);
                
                if (!trimestre) {
                    Swal.fire({
                        title: "تنبيه!",
                        text: "يرجى اختيار الثلاثي أولاً",
                        icon: "warning",
                        confirmButtonText: "موافق"
                    });
                    return;
                }
                
                // Afficher un indicateur de chargement
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');
                
                const remarques = [];
                
                $('.remarque-text').each(function() {
                    const textarea = $(this);
                    const eleve_id = textarea.data('eleve-id');
                    const domaine_id = textarea.data('domaine-id');
                    const remarque = textarea.val();
                    
                    remarques.push({
                        eleve_id: eleve_id,
                        domaine_id: domaine_id,
                        remarque: remarque
                    });
                });
                
                $.ajax({
                    type: 'POST',
                    url: 'save_remarques.php',
                    data: {
                        remarques: JSON.stringify(remarques),
                        trimestre: trimestre
                    },
                    dataType: 'json',
                    success: function(response) {
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> حفظ جميع الملاحظات');
                        
                        if (response.success) {
                            Swal.fire({
                                title: "نجاح!",
                                text: "تم حفظ جميع الملاحظات بنجاح",
                                icon: "success",
                                confirmButtonText: "موافق"
                            });
                        } else {
                            Swal.fire({
                                title: "خطأ!",
                                text: response.message,
                                icon: "error",
                                confirmButtonText: "موافق"
                            });
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> حفظ جميع الملاحظات');
                        
                        Swal.fire({
                            title: "خطأ!",
                            text: "حدث خطأ أثناء حفظ الملاحظات",
                            icon: "error",
                            confirmButtonText: "موافق"
                        });
                    }
                });
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
        });
    </script>
</body>
</html>

