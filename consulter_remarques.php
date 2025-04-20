<?php
session_start();
include 'db_config.php';


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

// Récupérer l'ID du professeur connecté
$id_professeur = $_SESSION['id_professeur'];

// Récupérer les classes enseignées par le professeur
$query_classes = "SELECT c.id_classe, c.nom_classe
                  FROM classes c
                  JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
                  WHERE pc.id_professeur = ?";
$stmt = $conn->prepare($query_classes);
$stmt->bind_param("i", $id_professeur);
$stmt->execute();
$result_classes = $stmt->get_result();

// Initialisation
$remarques = [];
$domaines = [];
$trimestres = [1 => 'الثلاثي الأول', 2 => 'الثلاثي الثاني', 3 => 'الثلاثي الثالث'];
$classe_id = isset($_GET['classe_id']) ? $_GET['classe_id'] : (isset($_POST['classe_id']) ? $_POST['classe_id'] : null);
$domaine_id = isset($_GET['domaine_id']) ? $_GET['domaine_id'] : (isset($_POST['domaine_id']) ? $_POST['domaine_id'] : null);
$trimestre = isset($_GET['trimestre']) ? $_GET['trimestre'] : (isset($_POST['trimestre']) ? $_POST['trimestre'] : null);
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Vérifier si la colonne trimestre existe dans la table remarques
$result_check_column = $conn->query("SHOW COLUMNS FROM remarques LIKE 'trimestre'");
$trimestre_exists = $result_check_column->num_rows > 0;

// Si une classe est sélectionnée
if ($classe_id) {
    // Récupérer les domaines pour cette classe
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

    // Construire la requête pour récupérer les remarques
    $query_remarques = "SELECT e.id_eleve, e.nom AS nom_eleve, e.prenom AS prenom_eleve, 
                        d.nom AS domaine, r.remarque, r.date_remarque";
    
    if ($trimestre_exists) {
        $query_remarques .= ", r.trimestre";
    }
    
    $query_remarques .= " FROM remarques r
                        JOIN eleves e ON r.eleve_id = e.id_eleve
                        JOIN domaines d ON r.domaine_id = d.id
                        WHERE e.id_classe = ?";

    // Préparer les paramètres pour la requête
    $params = [];
    $types = "i"; // Type pour classe_id
    $params[] = &$classe_id;

    // Ajouter les filtres si nécessaire
    if ($domaine_id) {
        $query_remarques .= " AND r.domaine_id = ?";
        $types .= "i"; // Type pour domaine_id
        $params[] = &$domaine_id;
    }

    if ($trimestre_exists && $trimestre) {
        $query_remarques .= " AND r.trimestre = ?";
        $types .= "i"; // Type pour trimestre
        $params[] = &$trimestre;
    }

    if ($search) {
        $query_remarques .= " AND (e.nom LIKE ? OR e.prenom LIKE ? OR r.remarque LIKE ?)";
        $types .= "sss"; // Types pour les paramètres de recherche
        $search_param = "%$search%";
        $params[] = &$search_param;
        $params[] = &$search_param;
        $params[] = &$search_param;
    }

    // Compter le nombre total de résultats pour la pagination
    $query_count = str_replace("e.id_eleve, e.nom AS nom_eleve, e.prenom AS prenom_eleve, 
                        d.nom AS domaine, r.remarque, r.date_remarque" . ($trimestre_exists ? ", r.trimestre" : ""), 
                        "COUNT(*) as total", $query_remarques);
    
    $stmt_count = $conn->prepare($query_count);
    
    // Ajouter le type en premier paramètre
    array_unshift($params, $types);
    
    // Appliquer les paramètres à la requête de comptage
    call_user_func_array([$stmt_count, 'bind_param'], $params);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row_count = $result_count->fetch_assoc();
    $total_items = $row_count['total'];
    $total_pages = ceil($total_items / $items_per_page);

    // Ajouter la pagination à la requête principale
    $query_remarques .= " ORDER BY r.date_remarque DESC LIMIT ?, ?";
    
    // Recréer le tableau de paramètres pour la requête principale
    $params = [];
    $types = "i"; // Type pour classe_id
    $params[] = &$classe_id;

    if ($domaine_id) {
        $types .= "i"; // Type pour domaine_id
        $params[] = &$domaine_id;
    }

    if ($trimestre_exists && $trimestre) {
        $types .= "i"; // Type pour trimestre
        $params[] = &$trimestre;
    }

    if ($search) {
        $types .= "sss"; // Types pour les paramètres de recherche
        $search_param = "%$search%";
        $params[] = &$search_param;
        $params[] = &$search_param;
        $params[] = &$search_param;
    }

    // Ajouter les paramètres de pagination
    $types .= "ii"; // Types pour offset et limit
    $params[] = &$offset;
    $params[] = &$items_per_page;

    // Ajouter le type en premier paramètre
    array_unshift($params, $types);

    // Exécuter la requête principale
    $stmt = $conn->prepare($query_remarques);
    call_user_func_array([$stmt, 'bind_param'], $params);
    $stmt->execute();
    $result_remarques = $stmt->get_result();

    while ($row = $result_remarques->fetch_assoc()) {
        $remarques[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض الملاحظات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3f51b5;
            --secondary-color: #ff9800;
            --success-color: #4caf50;
            --danger-color: #f44336;
            --light-color: #f5f5f5;
            --dark-color: #212121;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f0f2f5;
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .main-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), #5c6bc0);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }
        
        .page-header h1 {
            margin: 0;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .filter-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .filter-card .form-label {
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .filter-card .form-select,
        .filter-card .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            transition: all 0.3s ease;
        }
        
        .filter-card .form-select:focus,
        .filter-card .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(63, 81, 181, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #303f9f;
            border-color: #303f9f;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
        
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table th {
            font-weight: 500;
            border: none;
            padding: 1rem;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .table tbody tr:hover {
            background-color: rgba(63, 81, 181, 0.05);
        }
        
        .badge-trimestre {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 500;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }
        
        .pagination {
            margin-top: 2rem;
            justify-content: center;
        }
        
        .page-link {
            color: var(--primary-color);
            border: 1px solid #dee2e6;
            margin: 0 3px;
            border-radius: 5px;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .page-link:hover {
            background-color: #e9ecef;
            color: var(--primary-color);
        }
        
        .no-results {
            background-color: #fff3cd;
            color: #856404;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-top: 2rem;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-right: 40px;
        }
        
        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .page-header {
                padding: 1rem;
            }
            
            .filter-card {
                padding: 1rem;
            }
            
            .table-responsive {
                border-radius: 10px;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="page-header text-center">
            <h1><i class="fas fa-comments me-2"></i> عرض الملاحظات</h1>
        </div>
        
        <div class="filter-card">
            <form method="get" action="" id="filter-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="classe_id" class="form-label">القسم</label>
                        <select name="classe_id" id="classe_id" class="form-select" required>
                            <option value="">-- اختر القسم --</option>
                            <?php while ($row = $result_classes->fetch_assoc()): ?>
                                <option value="<?= $row['id_classe'] ?>" <?= $classe_id == $row['id_classe'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['nom_classe']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="domaine_id" class="form-label">المجال</label>
                        <select name="domaine_id" id="domaine_id" class="form-select" <?= empty($domaines) ? 'disabled' : '' ?>>
                            <option value="">-- كل المجالات --</option>
                            <?php foreach ($domaines as $domaine): ?>
                                <option value="<?= $domaine['id'] ?>" <?= $domaine_id == $domaine['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($domaine['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($trimestre_exists): ?>
                    <div class="col-md-3">
                        <label for="trimestre" class="form-label">الثلاثي</label>
                        <select name="trimestre" id="trimestre" class="form-select">
                            <option value="">-- كل الثلاثيات --</option>
                            <?php foreach ($trimestres as $key => $value): ?>
                                <option value="<?= $key ?>" <?= $trimestre == $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($value) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3">
                        <label for="search" class="form-label">بحث</label>
                        <div class="search-box">
                            <input type="text" name="search" id="search" class="form-control" placeholder="بحث عن طالب أو ملاحظة..." value="<?= htmlspecialchars($search) ?>">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> تصفية
                        </button>
                        <a href="consulter_remarques.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-redo me-1"></i> إعادة تعيين
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($classe_id): ?>
            <?php if (!empty($remarques)): ?>
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i> قائمة الملاحظات
                            <span class="badge bg-primary rounded-pill ms-2"><?= $total_items ?></span>
                        </h5>
                        <?php if ($total_items > 0): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="printTable()">
                                <i class="fas fa-print me-1"></i> طباعة
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="remarques-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>اسم الطالب</th>
                                        <th>المجال</th>
                                        <?php if ($trimestre_exists): ?>
                                            <th>الثلاثي</th>
                                        <?php endif; ?>
                                        <th>الملاحظة</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($remarques as $index => $remarque): ?>
                                        <tr>
                                            <td><?= $offset + $index + 1 ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($remarque['nom_eleve'] . ' ' . $remarque['prenom_eleve']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($remarque['domaine']) ?></td>
                                            <?php if ($trimestre_exists): ?>
                                                <td>
                                                    <span class="badge badge-trimestre">
                                                        <?= isset($remarque['trimestre']) && isset($trimestres[$remarque['trimestre']]) 
                                                            ? $trimestres[$remarque['trimestre']] 
                                                            : 'غير محدد' ?>
                                                    </span>
                                                </td>
                                            <?php endif; ?>
                                            <td><?= nl2br(htmlspecialchars($remarque['remarque'])) ?></td>
                                            <td><?= date('d/m/Y', strtotime($remarque['date_remarque'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?classe_id=<?= $classe_id ?>&domaine_id=<?= $domaine_id ?>&trimestre=<?= $trimestre ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?classe_id=' . $classe_id . '&domaine_id=' . $domaine_id . '&trimestre=' . $trimestre . '&search=' . urlencode($search) . '&page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?classe_id=' . $classe_id . '&domaine_id=' . $domaine_id . '&trimestre=' . $trimestre . '&search=' . urlencode($search) . '&page=' . $i . '">' . $i . '</a></li>';
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?classe_id=' . $classe_id . '&domaine_id=' . $domaine_id . '&trimestre=' . $trimestre . '&search=' . urlencode($search) . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?classe_id=<?= $classe_id ?>&domaine_id=<?= $domaine_id ?>&trimestre=<?= $trimestre ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-info-circle me-2"></i>
                    لم يتم العثور على أي ملاحظات تطابق معايير البحث.
                </div>
            <?php endif; ?>
        <?php elseif (isset($_GET['classe_id'])): ?>
            <div class="no-results">
                <i class="fas fa-info-circle me-2"></i>
                لم يتم العثور على أي ملاحظات لهذا القسم.
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Soumettre le formulaire lorsqu'un champ change
        document.getElementById('classe_id').addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
        
        // Activer le champ domaine_id lorsqu'une classe est sélectionnée
        if (document.getElementById('classe_id').value) {
            document.getElementById('domaine_id').disabled = false;
        }
        
        // Fonction pour imprimer la table
        function printTable() {
            const printContents = document.getElementById('remarques-table').outerHTML;
            const originalContents = document.body.innerHTML;
            
            const printStyles = `
                <style>
                    body { font-family: 'Tajawal', sans-serif; direction: rtl; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
                    th { background-color: #f2f2f2; }
                    h1 { text-align: center; margin-bottom: 20px; }
                    .badge-trimestre { background-color: #ff9800; color: white; padding: 3px 8px; border-radius: 10px; }
                </style>
            `;
            
            document.body.innerHTML = `
                ${printStyles}
                <h1>قائمة الملاحظات</h1>
                ${printContents}
            `;
            
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
</body>
</html>

