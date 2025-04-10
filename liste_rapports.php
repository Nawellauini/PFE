<?php
session_start();
include 'db_config.php'; // Connexion à la base de données

// Pagination
$rapports_par_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $rapports_par_page;

// Recherche
$recherche = isset($_GET['recherche']) ? $conn->real_escape_string($_GET['recherche']) : '';
$condition_recherche = '';
if (!empty($recherche)) {
    $condition_recherche = " WHERE r.titre LIKE '%$recherche%' 
                            OR c.nom_classe LIKE '%$recherche%' 
                            OR p.nom LIKE '%$recherche%' 
                            OR p.prenom LIKE '%$recherche%'";
}

// Tri
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'date_desc';
$ordre = '';
switch ($tri) {
    case 'titre_asc':
        $ordre = 'ORDER BY r.titre ASC';
        break;
    case 'titre_desc':
        $ordre = 'ORDER BY r.titre DESC';
        break;
    case 'classe_asc':
        $ordre = 'ORDER BY c.nom_classe ASC';
        break;
    case 'classe_desc':
        $ordre = 'ORDER BY c.nom_classe DESC';
        break;
    case 'date_asc':
        $ordre = 'ORDER BY r.date_creation ASC';
        break;
    case 'date_desc':
    default:
        $ordre = 'ORDER BY r.date_creation DESC';
        break;
}

// Compter le nombre total de rapports
$query_count = "SELECT COUNT(*) as total FROM rapports_inspection r
                JOIN classes c ON r.id_classe = c.id_classe
                JOIN professeurs p ON r.id_professeur = p.id_professeur
                $condition_recherche";
$result_count = $conn->query($query_count);
$row_count = $result_count->fetch_assoc();
$total_rapports = $row_count['total'];
$total_pages = ceil($total_rapports / $rapports_par_page);

// Récupérer les rapports d'inspection avec pagination
$query = "SELECT r.id, r.titre, r.commentaires, r.recommandations, r.date_creation,
                 c.nom_classe, p.nom AS nom_professeur, p.prenom AS prenom_professeur 
          FROM rapports_inspection r
          JOIN classes c ON r.id_classe = c.id_classe
          JOIN professeurs p ON r.id_professeur = p.id_professeur
          $condition_recherche
          $ordre
          LIMIT $offset, $rapports_par_page";

$result = $conn->query($query);

// Fonction pour tronquer le texte
function tronquer_texte($texte, $longueur = 100) {
    if (strlen($texte) <= $longueur) {
        return $texte;
    }
    return substr($texte, 0, $longueur) . '...';
}

// Fonction pour formater la date
function formater_date($date_mysql) {
    $date = new DateTime($date_mysql);
    return $date->format('d/m/Y à H:i');
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

        /* Toolbar Styles */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
            justify-content: space-between;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .search-icon {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
        }

        .toolbar-actions {
            display: flex;
            gap: 0.5rem;
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

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
        }

        .table th {
            background-color: var(--gray-100);
            color: var(--gray-700);
            font-weight: 600;
            padding: 1rem;
            border-bottom: 2px solid var(--gray-300);
            position: relative;
        }

        .table th.sortable {
            cursor: pointer;
        }

        .table th.sortable:hover {
            background-color: var(--gray-200);
        }

        .table th.sortable::after {
            content: '\f0dc';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 0.5rem;
            color: var(--gray-500);
        }

        .table th.sort-asc::after {
            content: '\f0de';
            color: var(--primary-color);
        }

        .table th.sort-desc::after {
            content: '\f0dd';
            color: var(--primary-color);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-300);
            vertical-align: top;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background-color: var(--gray-100);
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

        .badge-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .badge-success {
            background-color: var(--success-color);
            color: white;
        }

        .badge-warning {
            background-color: var(--warning-color);
            color: var(--gray-800);
        }

        .badge-danger {
            background-color: var(--danger-color);
            color: white;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 0.5rem;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Expandable Text */
        .expandable-text {
            position: relative;
        }

        .expand-btn {
            color: var(--primary-color);
            background: none;
            border: none;
            padding: 0;
            font-size: 0.875rem;
            cursor: pointer;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .expand-btn:hover {
            text-decoration: underline;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: var(--border-radius);
            background-color: white;
            color: var(--gray-700);
            text-decoration: none;
            font-weight: 500;
            border: 1px solid var(--gray-300);
            transition: var(--transition);
        }

        .pagination-item:hover {
            background-color: var(--gray-100);
        }

        .pagination-item.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state-icon {
            font-size: 3rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }

        .empty-state-title {
            font-size: 1.5rem;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .empty-state-description {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
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

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .toolbar-actions {
                justify-content: space-between;
            }

            .table th, .table td {
                padding: 0.75rem 0.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .pagination {
                flex-wrap: wrap;
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

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: var(--gray-800);
            color: white;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            right: 50%;
            transform: translateX(50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.75rem;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
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
                        <li><a href="liste_rapports.php" class="active"><i class="fas fa-list"></i> قائمة التقارير</a></li>
                        <li><a href="ajouter_rapport.php"><i class="fas fa-plus-circle"></i> إضافة تقرير</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
        <h1 class="page-title"><i class="fas fa-clipboard-list"></i> قائمة تقارير المتفقد</h1>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-header-left">
                        <i class="fas fa-table"></i>
                        <h2>تقارير المتفقد</h2>
                    </div>
                    <a href="ajouter_rapport.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> تقرير جديد
                    </a>
                </div>
                <div class="card-body">
                    <div class="toolbar">
                        <form action="" method="GET" class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="recherche" class="search-input" placeholder="البحث عن تقرير..." value="<?= htmlspecialchars($recherche) ?>">
                        </form>
                        <div class="toolbar-actions">
                            <select name="tri" id="tri" class="btn btn-outline" onchange="window.location.href='?tri='+this.value+'&recherche=<?= urlencode($recherche) ?>'">
                                <option value="date_desc" <?= $tri == 'date_desc' ? 'selected' : '' ?>>التاريخ (الأحدث)</option>
                                <option value="date_asc" <?= $tri == 'date_asc' ? 'selected' : '' ?>>التاريخ (الأقدم)</option>
                                <option value="titre_asc" <?= $tri == 'titre_asc' ? 'selected' : '' ?>>العنوان (أ-ي)</option>
                                <option value="titre_desc" <?= $tri == 'titre_desc' ? 'selected' : '' ?>>العنوان (ي-أ)</option>
                                <option value="classe_asc" <?= $tri == 'classe_asc' ? 'selected' : '' ?>>الفصل (أ-ي)</option>
                                <option value="classe_desc" <?= $tri == 'classe_desc' ? 'selected' : '' ?>>الفصل (ي-أ)</option>
                            </select>
                            <a href="generer_rapport.php" class="btn btn-outline">
                                <i class="fas fa-download"></i> تصدير
                            </a>
                        </div>
                    </div>

                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="20%">العنوان</th>
                                        <th width="10%">القسم</th>
                                        <th width="15%">المعلم</th>
                                        <th width="20%">التعليقات</th>
                                        <th width="20%">التوصيات</th>
                                        <th width="10%">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $row['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($row['titre']) ?></strong>
                                                <div class="text-muted" style="font-size: 0.8rem;">
                                                    <i class="far fa-calendar-alt"></i> <?= formater_date($row['date_creation']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary"><?= htmlspecialchars($row['nom_classe']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($row['nom_professeur'] . ' ' . $row['prenom_professeur']) ?></td>
                                            <td>
                                                <div class="expandable-text" data-id="comment-<?= $row['id'] ?>">
                                                    <div class="text-preview"><?= htmlspecialchars(tronquer_texte($row['commentaires'])) ?></div>
                                                    <?php if (strlen($row['commentaires']) > 100): ?>
                                                        <button class="expand-btn" onclick="toggleText('comment-<?= $row['id'] ?>')">مشاهدة المزيد</button>
                                                        <div class="text-full" style="display: none;"><?= nl2br(htmlspecialchars($row['commentaires'])) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="expandable-text" data-id="recomm-<?= $row['id'] ?>">
                                                    <div class="text-preview"><?= htmlspecialchars(tronquer_texte($row['recommandations'])) ?></div>
                                                    <?php if (strlen($row['recommandations']) > 100): ?>
                                                        <button class="expand-btn" onclick="toggleText('recomm-<?= $row['id'] ?>')">مشاهدة المزيد</button>
                                                        <div class="text-full" style="display: none;"><?= nl2br(htmlspecialchars($row['recommandations'])) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="voir_rapport.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary tooltip">
                                                        <i class="fas fa-eye"></i>
                                                        <span class="tooltip-text">مشاهدة</span>
                                                    </a>
                                                    <a href="modifier_rapport.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning tooltip">
                                                        <i class="fas fa-edit"></i>
                                                        <span class="tooltip-text">تغيير</span>
                                                    </a>
                                                    <a href="generer_rapport.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-success tooltip">
                                                        <i class="fas fa-file-pdf"></i>
                                                        <span class="tooltip-text">PDF</span>
                                                    </a>
                                                    <button onclick="confirmerSuppression(<?= $row['id'] ?>)" class="btn btn-sm btn-danger tooltip">
                                                        <i class="fas fa-trash-alt"></i>
                                                        <span class="tooltip-text">إزالة</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>&recherche=<?= urlencode($recherche) ?>&tri=<?= $tri ?>" class="pagination-item">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-item disabled">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $start_page + 4);
                                if ($end_page - $start_page < 4 && $total_pages > 5) {
                                    $start_page = max(1, $end_page - 4);
                                }
                                ?>

                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="?page=<?= $i ?>&recherche=<?= urlencode($recherche) ?>&tri=<?= $tri ?>" 
                                       class="pagination-item <?= $i == $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?>&recherche=<?= urlencode($recherche) ?>&tri=<?= $tri ?>" class="pagination-item">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-item disabled">
                                        <i class="fas fa-chevron-left"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3 class="empty-state-title">لم يتم العثور على أي تقرير</h3>
                            <p class="empty-state-description">
                                <?= empty($recherche) ? 
                                   "لم يتم إنشاء أي تقرير متفقد حتى الآن.":
                                    "لا يوجد تقرير يطابق بحثك \"$recherche\"." ?>
                            </p>
                            <a href="ajouter_rapport.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> إنشاء تقرير
                            </a>
                        </div>
                    <?php endif; ?>
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
                    <a href="#">معلومات عن النظام</a>
                    <a href="#">تواصل معنا</a>
                </div>
                <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> نظام إدارة التقارير المتفقد. جميع الحقوق محفوظة.</p>
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
            <p>هل أنت متأكد من رغبتك في حذف تقرير المتفقد هذا؟ هذا الإجراء لا يمكن التراجع عنه.</p>
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
        // Fonction pour afficher/masquer le texte complet
        function toggleText(id) {
            const container = document.querySelector(`[data-id="${id}"]`);
            const preview = container.querySelector('.text-preview');
            const full = container.querySelector('.text-full');
            const button = container.querySelector('.expand-btn');
            
            if (full.style.display === 'none') {
                preview.style.display = 'none';
                full.style.display = 'block';
                button.textContent = 'عرض أقل';
            } else {
                preview.style.display = 'block';
                full.style.display = 'none';
                button.textContent = 'عرض المزيد';
            }
        }

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