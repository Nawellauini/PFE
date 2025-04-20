<?php
$mysqli = new mysqli("localhost", "root", "", "u504721134_formation");
if ($mysqli->connect_error) die("Erreur connexion : " . $mysqli->connect_error);

// Récupération des données
$matieres = $mysqli->query("SELECT m.*, p.nom AS prof_nom, p.prenom AS prof_prenom, c.nom_classe, d.nom AS domaine_nom
    FROM matieres m
    LEFT JOIN professeurs p ON m.professeur_id = p.id_professeur
    LEFT JOIN classes c ON m.classe_id = c.id_classe
    LEFT JOIN domaines d ON m.domaine_id = d.id")->fetch_all(MYSQLI_ASSOC);

$professeurs = $mysqli->query("SELECT * FROM professeurs")->fetch_all(MYSQLI_ASSOC);
$classes = $mysqli->query("SELECT * FROM classes")->fetch_all(MYSQLI_ASSOC);
$domaines = $mysqli->query("SELECT * FROM domaines")->fetch_all(MYSQLI_ASSOC);

// Récupérer les messages de succès/erreur
$message = '';
$alertType = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'add':
            $message = '✅ تمت إضافة المادة بنجاح';
            break;
        case 'edit':
            $message = '✏️ تم تعديل المادة بنجاح';
            break;
        case 'delete':
            $message = '🗑️ تم حذف المادة بنجاح';
            break;
        default:
            $message = '✔️ تم تنفيذ العملية بنجاح';
    }
    $alertType = 'success';
} elseif (isset($_GET['error'])) {
    $message = '❌ حدث خطأ أثناء العملية';
    $alertType = 'danger';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المواد</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3949ab;
            --primary-light: #6f74dd;
            --primary-dark: #00227b;
            --secondary-color: #ff6f00;
            --secondary-light: #ffa040;
            --secondary-dark: #c43e00;
            --success-color: #2e7d32;
            --warning-color: #ff8f00;
            --danger-color: #c62828;
            --light-color: #f5f7fa;
            --dark-color: #263238;
            --gray-color: #607d8b;
            --gray-light: #eceff1;
            --border-radius: 12px;
            --card-radius: 16px;
            --box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-light);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .btn-add {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            color: white;
        }

        .search-filter-container {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .search-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .search-input-group {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            transition: var(--transition);
        }

        .search-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-color);
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            transition: var(--transition);
            background-color: white;
        }

        .filter-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
        }

        .card {
            background: white;
            border-radius: var(--card-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            overflow: hidden;
            border: none;
        }

        .table-container {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: white;
        }

        .data-table th {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            font-weight: 600;
            text-align: right;
            padding: 15px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table th:first-child {
            border-top-right-radius: var(--border-radius);
        }

        .data-table th:last-child {
            border-top-left-radius: var(--border-radius);
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--gray-light);
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover td {
            background-color: var(--light-color);
        }

        .subject-name {
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .subject-icon {
            width: 36px;
            height: 36px;
            background-color: var(--primary-light);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .teacher-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .teacher-avatar {
            width: 36px;
            height: 36px;
            background-color: var(--secondary-light);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 600;
        }

        .badge-custom {
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-class {
            background-color: rgba(57, 73, 171, 0.1);
            color: var(--primary-color);
        }

        .badge-domain {
            background-color: rgba(255, 111, 0, 0.1);
            color: var(--secondary-color);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: var(--transition);
            color: white;
        }

        .btn-edit {
            background-color: var(--warning-color);
        }

        .btn-edit:hover {
            background-color: #f57c00;
            transform: translateY(-3px);
        }

        .btn-delete {
            background-color: var(--danger-color);
        }

        .btn-delete:hover {
            background-color: #b71c1c;
            transform: translateY(-3px);
        }

        .btn-view {
            background-color: var(--primary-color);
        }

        .btn-view:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
        }

        /* Modal styles */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-bottom: none;
            padding: 15px 20px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            border-top: 1px solid var(--gray-light);
            padding: 15px 20px;
            background-color: #f8f9fa;
        }

        .btn-close {
            color: white;
            opacity: 1;
            text-shadow: none;
            background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='18' y1='6' x2='6' y2='18'%3E%3C/line%3E%3Cline x1='6' y1='6' x2='18' y2='18'%3E%3C/line%3E%3C/svg%3E") center/1em auto no-repeat;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            padding: 10px 15px;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            transition: var(--transition);
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
            outline: none;
        }

        .form-select {
            padding: 10px 15px;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            transition: var(--transition);
            width: 100%;
            background-position: left 15px center;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
            outline: none;
        }

        .btn-modal {
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-modal-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-modal-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-modal-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-modal-secondary:hover {
            background-color: #5a6268;
        }

        .btn-modal-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-modal-warning:hover {
            background-color: var(--secondary-dark);
        }

        .btn-modal-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-modal-danger:hover {
            background-color: #b71c1c;
        }

        .btn-modal-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-modal-success:hover {
            background-color: #1b5e20;
        }

        /* Alert styles */
        .alert {
            border-radius: var(--border-radius);
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background-color: #e8f5e9;
            color: var(--success-color);
            border-right: 4px solid var(--success-color);
        }

        .alert-danger {
            background-color: #ffebee;
            color: var(--danger-color);
            border-right: 4px solid var(--danger-color);
        }

        .alert i {
            font-size: 1.5rem;
        }

        /* Delete confirmation modal */
        .delete-icon-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .delete-icon {
            font-size: 4rem;
            color: var(--danger-color);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        .delete-warning {
            text-align: center;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .delete-subject-name {
            font-weight: 700;
            color: var(--danger-color);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--gray-color);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .empty-description {
            color: var(--gray-color);
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .search-row, .filter-row {
                flex-direction: column;
                gap: 15px;
            }

            .search-input-group, .filter-group {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }

            .btn-action {
                width: 100%;
                border-radius: 8px;
                height: auto;
                padding: 8px;
            }
        }

        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        .slide-in {
            animation: slideIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header fade-in">
        <h1 class="page-title">
            <i class="fas fa-book"></i>
            إدارة المواد الدراسية
        </h1>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#ajouterModal">
            <i class="fas fa-plus"></i>
            إضافة مادة جديدة
        </button>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $alertType ?> fade-in" role="alert" id="actionAlert">
        <i class="fas fa-<?= $alertType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <div><?= $message ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="search-filter-container slide-in">
        <div class="search-row">
            <div class="search-input-group">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="البحث عن مادة...">
            </div>
        </div>
        <div class="filter-row">
            <div class="filter-group">
                <label for="filterProfesseur" class="filter-label">تصفية حسب الأستاذ:</label>
                <select id="filterProfesseur" class="filter-select">
                    <option value="">جميع الأساتذة</option>
                    <?php 
                    $uniqueProfs = [];
                    foreach ($matieres as $m) {
                        $profId = $m['professeur_id'];
                        $profName = $m['prof_nom'] . ' ' . $m['prof_prenom'];
                        if (!isset($uniqueProfs[$profId]) && $profId) {
                            $uniqueProfs[$profId] = $profName;
                            echo "<option value=\"$profId\">$profName</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="filterClasse" class="filter-label">تصفية حسب القسم:</label>
                <select id="filterClasse" class="filter-select">
                    <option value="">جميع الأقسام</option>
                    <?php 
                    $uniqueClasses = [];
                    foreach ($matieres as $m) {
                        $classeId = $m['classe_id'];
                        $classeName = $m['nom_classe'];
                        if (!isset($uniqueClasses[$classeId]) && $classeId) {
                            $uniqueClasses[$classeId] = $classeName;
                            echo "<option value=\"$classeId\">$classeName</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="filterDomaine" class="filter-label">تصفية حسب المجال:</label>
                <select id="filterDomaine" class="filter-select">
                    <option value="">جميع المجالات</option>
                    <?php 
                    $uniqueDomains = [];
                    foreach ($matieres as $m) {
                        $domaineId = $m['domaine_id'];
                        $domaineName = $m['domaine_nom'];
                        if (!isset($uniqueDomains[$domaineId]) && $domaineId) {
                            $uniqueDomains[$domaineId] = $domaineName;
                            echo "<option value=\"$domaineId\">$domaineName</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <?php if (empty($matieres)): ?>
        <div class="empty-state fade-in">
            <i class="fas fa-book-open empty-icon"></i>
            <h3 class="empty-title">لا توجد مواد</h3>
            <p class="empty-description">لم يتم إضافة أي مادة دراسية بعد. يمكنك إضافة مادة جديدة بالنقر على زر "إضافة مادة جديدة".</p>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#ajouterModal">
                <i class="fas fa-plus"></i>
                إضافة مادة جديدة
            </button>
        </div>
    <?php else: ?>
        <div class="table-container fade-in">
            <table class="data-table" id="matieresTable">
                <thead>
                    <tr>
                        <th width="25%">المادة</th>
                        <th width="25%">الأستاذ</th>
                        <th width="15%">القسم</th>
                        <th width="15%">المجال</th>
                        <th width="20%">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matieres as $m): ?>
                    <tr class="matiere-row" 
                        data-nom="<?= htmlspecialchars($m['nom']) ?>"
                        data-prof="<?= htmlspecialchars($m['prof_nom'] . ' ' . $m['prof_prenom']) ?>"
                        data-prof-id="<?= $m['professeur_id'] ?>"
                        data-classe="<?= htmlspecialchars($m['nom_classe']) ?>"
                        data-classe-id="<?= $m['classe_id'] ?>"
                        data-domaine="<?= htmlspecialchars($m['domaine_nom']) ?>"
                        data-domaine-id="<?= $m['domaine_id'] ?>">
                        <td>
                            <div class="subject-name">
                                <div class="subject-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <?= htmlspecialchars($m['nom']) ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($m['professeur_id']): ?>
                            <div class="teacher-info">
                                <div class="teacher-avatar">
                                    <?= mb_substr($m['prof_prenom'], 0, 1) . mb_substr($m['prof_nom'], 0, 1) ?>
                                </div>
                                <div>
                                    <?= htmlspecialchars($m['prof_nom'] . ' ' . $m['prof_prenom']) ?>
                                </div>
                            </div>
                            <?php else: ?>
                                <span class="text-muted">غير محدد</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($m['classe_id']): ?>
                                <span class="badge-custom badge-class">
                                    <i class="fas fa-chalkboard"></i>
                                    <?= htmlspecialchars($m['nom_classe']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">غير محدد</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($m['domaine_id']): ?>
                                <span class="badge-custom badge-domain">
                                    <i class="fas fa-tag"></i>
                                    <?= htmlspecialchars($m['domaine_nom']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">غير محدد</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-view" title="عرض التفاصيل" data-bs-toggle="modal" data-bs-target="#viewModal<?= $m['matiere_id'] ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action btn-edit" title="تعديل" data-bs-toggle="modal" data-bs-target="#modifierModal<?= $m['matiere_id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" title="حذف" data-bs-toggle="modal" data-bs-target="#supprimerModal<?= $m['matiere_id'] ?>">
    <i class="fas fa-trash-alt"></i>
</button>

                            </div>
                        </td>
                    </tr>

                    <!-- Modal Voir -->
                    <div class="modal fade" id="viewModal<?= $m['matiere_id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-info-circle"></i>
                                        تفاصيل المادة
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-4 text-center">
                                        <div class="subject-icon mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <h4><?= htmlspecialchars($m['nom']) ?></h4>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-4 fw-bold">الأستاذ:</div>
                                        <div class="col-8">
                                            <?php if ($m['professeur_id']): ?>
                                                <?= htmlspecialchars($m['prof_nom'] . ' ' . $m['prof_prenom']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">غير محدد</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-4 fw-bold">القسم:</div>
                                        <div class="col-8">
                                            <?php if ($m['classe_id']): ?>
                                                <?= htmlspecialchars($m['nom_classe']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">غير محدد</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-4 fw-bold">المجال:</div>
                                        <div class="col-8">
                                            <?php if ($m['domaine_id']): ?>
                                                <?= htmlspecialchars($m['domaine_nom']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">غير محدد</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i>
                                        إغلاق
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Modifier -->
                    <div class="modal fade" id="modifierModal<?= $m['matiere_id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <form method="POST" action="modifier_matiere.php">
                                <input type="hidden" name="matiere_id" value="<?= $m['matiere_id'] ?>">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-edit"></i>
                                            تعديل المادة
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="nom<?= $m['matiere_id'] ?>" class="form-label">اسم المادة:</label>
                                            <input type="text" id="nom<?= $m['matiere_id'] ?>" name="nom" class="form-control" value="<?= htmlspecialchars($m['nom']) ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="professeur_id<?= $m['matiere_id'] ?>" class="form-label">الأستاذ:</label>
                                            <select id="professeur_id<?= $m['matiere_id'] ?>" name="professeur_id" class="form-select">
                                                <option value="">-- اختر الأستاذ --</option>
                                                <?php foreach ($professeurs as $p): ?>
                                                    <option value="<?= $p['id_professeur'] ?>" <?= $p['id_professeur'] == $m['professeur_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="classe_id<?= $m['matiere_id'] ?>" class="form-label">القسم:</label>
                                            <select id="classe_id<?= $m['matiere_id'] ?>" name="classe_id" class="form-select">
                                                <option value="">-- اختر القسم --</option>
                                                <?php foreach ($classes as $c): ?>
                                                    <option value="<?= $c['id_classe'] ?>" <?= $c['id_classe'] == $m['classe_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($c['nom_classe']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="domaine_id<?= $m['matiere_id'] ?>" class="form-label">المجال:</label>
                                            <select id="domaine_id<?= $m['matiere_id'] ?>" name="domaine_id" class="form-select">
                                                <option value="">-- اختر المجال --</option>
                                                <?php foreach ($domaines as $d): ?>
                                                    <option value="<?= $d['id'] ?>" <?= $d['id'] == $m['domaine_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($d['nom']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">
                                            <i class="fas fa-times"></i>
                                            إلغاء
                                        </button>
                                        <button type="submit" class="btn-modal btn-modal-warning">
                                            <i class="fas fa-save"></i>
                                            حفظ التعديلات
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal Supprimer -->
                    <div class="modal fade" id="supprimerModal<?= $m['matiere_id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <form method="POST" action="supprimer_matiere.php">
                                <input type="hidden" name="matiere_id" value="<?= $m['matiere_id'] ?>">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            تأكيد الحذف
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="delete-icon-container">
                                            <i class="fas fa-trash-alt delete-icon"></i>
                                        </div>
                                        <p class="delete-warning">
                                            هل أنت متأكد من حذف المادة 
                                            <span class="delete-subject-name"><?= htmlspecialchars($m['nom']) ?></span>؟
                                        </p>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <div>هذا الإجراء لا يمكن التراجع عنه.</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">
                                            <i class="fas fa-times"></i>
                                            إلغاء
                                        </button>
                                        <button type="submit" class="btn-modal btn-modal-danger">
                                            <i class="fas fa-trash-alt"></i>
                                            تأكيد الحذف
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Ajouter -->
<div class="modal fade" id="ajouterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="ajouter_matiere.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i>
                        إضافة مادة جديدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nom_new" class="form-label">اسم المادة:</label>
                        <input type="text" id="nom_new" name="nom" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="professeur_id_new" class="form-label">الأستاذ:</label>
                        <select id="professeur_id_new" name="professeur_id" class="form-select">
                            <option value="">-- اختر الأستاذ --</option>
                            <?php foreach ($professeurs as $p): ?>
                                <option value="<?= $p['id_professeur'] ?>">
                                    <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="classe_id_new" class="form-label">القسم:</label>
                        <select id="classe_id_new" name="classe_id" class="form-select">
                            <option value="">-- اختر القسم --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id_classe'] ?>">
                                    <?= htmlspecialchars($c['nom_classe']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="domaine_id_new" class="form-label">المجال:</label>
                        <select id="domaine_id_new" name="domaine_id" class="form-select">
                            <option value="">-- اختر المجال --</option>
                            <?php foreach ($domaines as $d): ?>
                                <option value="<?= $d['id'] ?>">
                                    <?= htmlspecialchars($d['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        إلغاء
                    </button>
                    <button type="submit" class="btn-modal btn-modal-success">
                        <i class="fas fa-plus"></i>
                        إضافة المادة
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fermeture automatique des alertes
    setTimeout(function () {
        const alertBox = document.getElementById("actionAlert");
        if (alertBox) {
            const alert = bootstrap.Alert.getOrCreateInstance(alertBox);
            alert.close();
        }
    }, 5000);

    // Filtrage des matières
    const searchInput = document.getElementById('searchInput');
    const filterProfesseur = document.getElementById('filterProfesseur');
    const filterClasse = document.getElementById('filterClasse');
    const filterDomaine = document.getElementById('filterDomaine');
    const matiereRows = document.querySelectorAll('.matiere-row');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const profId = filterProfesseur.value;
        const classeId = filterClasse.value;
        const domaineId = filterDomaine.value;

        matiereRows.forEach(row => {
            const nom = row.getAttribute('data-nom').toLowerCase();
            const prof = row.getAttribute('data-prof').toLowerCase();
            const rowProfId = row.getAttribute('data-prof-id');
            const rowClasseId = row.getAttribute('data-classe-id');
            const rowDomaineId = row.getAttribute('data-domaine-id');

            const matchesSearch = nom.includes(searchTerm) || prof.includes(searchTerm);
            const matchesProf = !profId || rowProfId === profId;
            const matchesClasse = !classeId || rowClasseId === classeId;
            const matchesDomaine = !domaineId || rowDomaineId === domaineId;

            if (matchesSearch && matchesProf && matchesClasse && matchesDomaine) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterTable);
    filterProfesseur.addEventListener('change', filterTable);
    filterClasse.addEventListener('change', filterTable);
    filterDomaine.addEventListener('change', filterTable);
});
</script>
</body>
</html>

<Actions>
  <Action name="Ajouter un système de pagination" description="Implémenter la pagination pour les tables avec beaucoup d'entrées" />
  <Action name="Créer un tableau de bord statistique" description="Ajouter des graphiques montrant la répartition des matières par domaine et classe" />
  <Action name="Implémenter un mode sombre" description="Ajouter un thème sombre pour l'interface" />
  <Action name="Ajouter une fonctionnalité d'exportation" description="Permettre l'exportation des données en format Excel ou PDF" />
  <Action name="Créer une vue d'impression" description="Ajouter une option pour imprimer la liste des matières" />
</Actions>

