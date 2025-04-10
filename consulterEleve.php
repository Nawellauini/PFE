<?php
include 'db_config.php';
session_start();

// Vérifier si le professeur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];
$eleves = [];
$classe_selected = false;
$nom_classe = "";
$error_message = "";
$trimestre = isset($_GET['trimestre']) ? $_GET['trimestre'] : 1; // Par défaut, le premier trimestre

// Récupérer uniquement les classes enseignées par le professeur connecté
$query = "SELECT c.id_classe, c.nom_classe 
          FROM classes c
          JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
          WHERE pc.id_professeur = ?
          ORDER BY c.nom_classe ASC";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Erreur de préparation de la requête : " . $conn->error);
}

$stmt->bind_param("i", $id_professeur);
$stmt->execute();
$result_classes = $stmt->get_result();
$classes = [];
while ($row = $result_classes->fetch_assoc()) {
    $classes[] = $row;
}

// Si une classe est sélectionnée, récupérer les élèves de cette classe
if (isset($_GET['classe_id']) && !empty($_GET['classe_id'])) {
    $classe_id = $_GET['classe_id'];
    $classe_selected = true;
    
    // Vérifier que le professeur enseigne bien cette classe
    $query_verify = "SELECT c.nom_classe FROM classes c 
                    JOIN professeurs_classes pc ON c.id_classe = pc.id_classe 
                    WHERE pc.id_professeur = ? AND c.id_classe = ?";
    $stmt_verify = $conn->prepare($query_verify);
    
    if ($stmt_verify === false) {
        $error_message = "Erreur de préparation de la requête : " . $conn->error;
    } else {
        $stmt_verify->bind_param("ii", $id_professeur, $classe_id);
        $stmt_verify->execute();
        $result_verify = $stmt_verify->get_result();
        
        if ($result_verify->num_rows > 0) {
            $nom_classe = $result_verify->fetch_assoc()['nom_classe'];
            
            // Récupérer les élèves de la classe - Utiliser uniquement les colonnes existantes
            $query_eleves = "SELECT e.id_eleve, e.nom, e.prenom, e.email, e.login 
                            FROM eleves e 
                            WHERE e.id_classe = ? 
                            ORDER BY e.nom, e.prenom";
            $stmt_eleves = $conn->prepare($query_eleves);
            
            if ($stmt_eleves === false) {
                $error_message = "Erreur de préparation de la requête : " . $conn->error;
            } else {
                $stmt_eleves->bind_param("i", $classe_id);
                $stmt_eleves->execute();
                $result_eleves = $stmt_eleves->get_result();
                
                while ($row = $result_eleves->fetch_assoc()) {
                    $eleves[] = $row;
                }
            }
        } else {
            // Le professeur n'enseigne pas cette classe
            $error_message = "Vous n'êtes pas autorisé à consulter cette classe.";
        }
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

// Récupérer les matières enseignées par le professeur
$query_matieres = "SELECT m.id_matiere, m.nom_matiere 
                  FROM matieres m 
                  JOIN professeurs_matieres pm ON m.id_matiere = pm.id_matiere
                  WHERE pm.id_professeur = ?
                  ORDER BY m.nom_matiere ASC";
$stmt_matieres = $conn->prepare($query_matieres);

if ($stmt_matieres) {
    $stmt_matieres->bind_param("i", $id_professeur);
    $stmt_matieres->execute();
    $result_matieres = $stmt_matieres->get_result();
    $matieres = [];
    while ($row = $result_matieres->fetch_assoc()) {
        $matieres[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة التلاميذ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f1c40f;
            --info-color: #3498db;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar styles */
        .sidebar {
            width: 280px;
            background: var(--dark-color);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h3 {
            color: var(--light-color);
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .tree-view {
            padding: 20px;
        }
        
        .tree-item {
            margin-bottom: 10px;
        }
        
        .tree-parent {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 5px;
        }
        
        .tree-parent:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .tree-parent.active {
            background: var(--primary-color);
        }
        
        .tree-icon {
            margin-left: 10px;
            width: 24px;
            text-align: center;
        }
        
        .tree-toggle {
            margin-right: auto;
            transition: transform 0.3s ease;
        }
        
        .tree-toggle.open {
            transform: rotate(90deg);
        }
        
        .tree-children {
            padding-right: 20px;
            margin-top: 5px;
            display: none;
        }
        
        .tree-children.show {
            display: block;
        }
        
        .tree-child {
            padding: 8px 15px;
            border-radius: 6px;
            margin-bottom: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        
        .tree-child:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .tree-child.active {
            background: rgba(52, 152, 219, 0.5);
        }
        
        .tree-child-icon {
            margin-left: 10px;
            width: 20px;
            text-align: center;
            font-size: 0.9rem;
        }
        
        /* Main content styles */
        .main-content {
            flex: 1;
            padding: 30px;
            margin-right: 280px;
            transition: all 0.3s ease;
        }
        
        .content-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .content-title {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .content-title i {
            margin-left: 15px;
            color: var(--primary-color);
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--dark-color);
        }
        
        /* Trimestre selector */
        .trimestre-selector {
            display: flex;
            margin-bottom: 30px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .trimestre-btn {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            border: none;
            background: transparent;
        }
        
        .trimestre-btn.active {
            background: var(--primary-color);
            color: white;
            font-weight: 700;
        }
        
        /* Cards and tables */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .card-title {
            margin: 0;
            color: var(--dark-color);
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .table {
            margin: 0;
        }
        
        .table thead th {
            background: rgba(52, 152, 219, 0.1);
            color: var(--dark-color);
            font-weight: 600;
            border: none;
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table tbody tr:hover {
            background: rgba(52, 152, 219, 0.05);
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }
        
        .btn-success {
            background: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-success:hover {
            background: #219d54;
            border-color: #219d54;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
        }
        
        .btn-warning {
            background: var(--warning-color);
            border-color: var(--warning-color);
            color: var(--dark-color);
        }
        
        .btn-warning:hover {
            background: #f39c12;
            border-color: #f39c12;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(241, 196, 15, 0.3);
        }
        
        .btn-danger {
            background: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background: #c0392b;
            border-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
        }
        
        .btn-icon {
            margin-left: 8px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 240px;
            }
            
            .main-content {
                margin-right: 240px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding-bottom: 0;
            }
            
            .main-content {
                margin-right: 0;
                padding: 20px;
            }
            
            .tree-view {
                padding: 10px;
            }
            
            .trimestre-selector {
                flex-direction: column;
            }
            
            .trimestre-btn {
                padding: 10px;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease forwards;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: #d1d8e0;
            margin-bottom: 20px;
        }
        
        .empty-state-text {
            color: #a5b1c2;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        /* Student badge */
        .student-badge {
            display: inline-block;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            text-align: center;
            line-height: 36px;
            margin-left: 10px;
            font-weight: bold;
        }
        
        /* Status badges */
        .badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 500;
        }
        
        .badge-success {
            background: rgba(46, 204, 113, 0.2);
            color: var(--success-color);
        }
        
        .badge-warning {
            background: rgba(241, 196, 15, 0.2);
            color: #d35400;
        }
        
        .badge-danger {
            background: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }
        
        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: var(--dark-color);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .sidebar {
                transform: translateX(100%);
                position: fixed;
                top: 0;
                right: 0;
                height: 100vh;
                width: 280px;
                z-index: 1000;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>

<!-- Mobile menu toggle button -->
<button class="mobile-menu-toggle" id="mobile-menu-toggle">
    <i class="fas fa-bars"></i>
</button>

<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>لوحة التحكم</h3>
            <p>مرحبًا، <?php echo $_SESSION['nom_professeur']; ?></p>
        </div>
        
        <div class="tree-view">
            <div class="tree-item">
                <div class="tree-parent" onclick="window.location.href='index.php'">
                    <div class="tree-icon"><i class="fas fa-home"></i></div>
                    <span>الرئيسية</span>
                </div>
            </div>
            
            <div class="tree-item">
                <div class="tree-parent active">
                    <div class="tree-icon"><i class="fas fa-users"></i></div>
                    <span>الفصول والتلاميذ</span>
                    <div class="tree-toggle open"><i class="fas fa-chevron-left"></i></div>
                </div>
                <div class="tree-children show">
                    <?php foreach ($classes as $classe): ?>
                    <div class="tree-child <?php echo (isset($_GET['classe_id']) && $_GET['classe_id'] == $classe['id_classe']) ? 'active' : ''; ?>" 
                         data-id="<?php echo $classe['id_classe']; ?>">
                        <div class="tree-child-icon"><i class="fas fa-graduation-cap"></i></div>
                        <span><?php echo htmlspecialchars($classe['nom_classe']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>  
            <div class="tree-item">
                <div class="tree-parent" onclick="window.location.href='logout.php'">
                    <div class="tree-icon"><i class="fas fa-sign-out-alt"></i></div>
                    <span>تسجيل الخروج</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <h1 class="content-title">
                <i class="fas fa-users"></i>
                قائمة التلاميذ
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">قائمة التلاميذ</li>
                    <?php if ($classe_selected): ?>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($nom_classe); ?></li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
        
        <!-- Trimestre Selector -->
        <div class="trimestre-selector">
            <a href="?trimestre=1<?php echo isset($_GET['classe_id']) ? '&classe_id='.$_GET['classe_id'] : ''; ?>" 
               class="trimestre-btn <?php echo $trimestre == 1 ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> الثلاثي الأول
            </a>
            <a href="?trimestre=2<?php echo isset($_GET['classe_id']) ? '&classe_id='.$_GET['classe_id'] : ''; ?>" 
               class="trimestre-btn <?php echo $trimestre == 2 ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> الثلاثي الثاني
            </a>
            <a href="?trimestre=3<?php echo isset($_GET['classe_id']) ? '&classe_id='.$_GET['classe_id'] : ''; ?>" 
               class="trimestre-btn <?php echo $trimestre == 3 ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> الثلاثي الثالث
            </a>
        </div>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($classe_selected && !empty($nom_classe)): ?>
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-graduation-cap"></i>
                    <?php echo htmlspecialchars($nom_classe); ?> - <?php echo htmlspecialchars($trimestre_nom); ?>
                </h5>
                <span class="badge bg-primary"><?php echo count($eleves); ?> تلميذ</span>
            </div>
            <div class="card-body">
                <?php if (empty($eleves)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="empty-state-text">لا يوجد تلاميذ مسجلين في هذا الفصل</div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>الاسم واللقب</th>
                                <th>البريد الإلكتروني</th>
                                <th>اسم المستخدم</th>
                                <th width="150">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eleves as $index => $eleve): ?>
                            <tr>
                                <td>
                                    <div class="student-badge"><?php echo $index + 1; ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-graduate text-primary me-2"></i>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($eleve['nom']); ?></div>
                                            <div class="text-muted"><?php echo htmlspecialchars($eleve['prenom']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($eleve['email'] ?? 'غير متوفر'); ?></td>
                                <td><?php echo htmlspecialchars($eleve['login'] ?? 'غير متوفر'); ?></td>
                                <td>
                                    <a href="#" class="btn btn-primary btn-sm view-bulletin" 
                                       data-classe="<?php echo $_GET['classe_id']; ?>" 
                                       data-eleve="<?php echo $eleve['id_eleve']; ?>" 
                                       data-trimestre="<?php echo $trimestre; ?>">
                                        <i class="fas fa-eye btn-icon"></i> كشف النقاط
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card fade-in">
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="empty-state-text">الرجاء اختيار فصل من القائمة الجانبية</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle tree items
    const treeParents = document.querySelectorAll('.tree-parent');
    treeParents.forEach(parent => {
        const toggle = parent.querySelector('.tree-toggle');
        if (toggle) {
            parent.addEventListener('click', function(e) {
                // Ne pas déclencher si on clique sur un lien direct
                if (e.target.tagName === 'A' || e.target.parentElement.tagName === 'A') {
                    return;
                }
                
                const children = this.nextElementSibling;
                if (children && children.classList.contains('tree-children')) {
                    children.classList.toggle('show');
                    toggle.classList.toggle('open');
                }
            });
        }
    });
    
    // Handle tree child clicks
    const treeChildren = document.querySelectorAll('.tree-child');
    treeChildren.forEach(child => {
        child.addEventListener('click', function(e) {
            // Ne pas déclencher si on clique sur un lien direct
            if (e.target.tagName === 'A' || e.target.parentElement.tagName === 'A') {
                return;
            }
            
            const classeId = this.getAttribute('data-id');
            if (classeId) {
                window.location.href = `?classe_id=${classeId}&trimestre=<?php echo $trimestre; ?>`;
            }
        });
    });
    
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Gestion des clics sur les boutons de visualisation des bulletins
    const viewButtons = document.querySelectorAll('.view-bulletin');
    viewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Créer un formulaire dynamique
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'afficher_classe.php';
            form.style.display = 'none';
            
            // Ajouter les champs cachés
            const classeInput = document.createElement('input');
            classeInput.type = 'hidden';
            classeInput.name = 'classe_id';
            classeInput.value = this.getAttribute('data-classe');
            form.appendChild(classeInput);
            
            const eleveInput = document.createElement('input');
            eleveInput.type = 'hidden';
            eleveInput.name = 'eleve_id';
            eleveInput.value = this.getAttribute('data-eleve');
            form.appendChild(eleveInput);
            
            const trimestreInput = document.createElement('input');
            trimestreInput.type = 'hidden';
            trimestreInput.name = 'trimestre';
            trimestreInput.value = this.getAttribute('data-trimestre');
            form.appendChild(trimestreInput);
            
            // Ajouter le formulaire au document et le soumettre
            document.body.appendChild(form);
            form.submit();
        });
    });
});
</script>
</body>
</html>