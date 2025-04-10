<?php
include 'db_config.php';

// Récupération du filtre de classe
$classe_filter = isset($_GET['classe_id']) ? intval($_GET['classe_id']) : 0;

// Récupération des classes pour le menu déroulant
$classes_result = $conn->query("SELECT id_classe, nom_classe FROM classes ORDER BY nom_classe");

// Récupération des élèves avec filtrage par classe et/ou lettre
$sql = "SELECT e.*, c.nom_classe, SUBSTRING(e.nom, 1, 1) as premiere_lettre FROM eleves e 
        JOIN classes c ON e.id_classe = c.id_classe WHERE 1=1"; 

if ($classe_filter > 0) {
    $sql .= " AND e.id_classe = $classe_filter";  
}

// Filtre par lettre si spécifié
if (isset($_GET['letter']) && $_GET['letter'] != 'all') {
    $letter = $conn->real_escape_string($_GET['letter']);
    $sql .= " AND e.nom LIKE '$letter%'";
}

// Tri par première lettre, puis par nom complet
$sql .= " ORDER BY premiere_lettre, e.nom, e.prenom";
$eleves_result = $conn->query($sql);

// Message de statut pour les opérations
$status_message = '';
$status_type = '';

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id_eleve = $_POST['id_eleve'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $login = $_POST['login'];
    $mp = $_POST['mp'];
    $id_classe = $_POST['id_classe'];

    $update_sql = "UPDATE eleves SET nom = ?, prenom = ?, email = ?, login = ?, mp = ?, id_classe = ? WHERE id_eleve = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssssii", $nom, $prenom, $email, $login, $mp, $id_classe, $id_eleve);

    if ($update_stmt->execute()) {
        $status_message = 'تم تحديث معلومات الطالب بنجاح';
        $status_type = 'success';
    } else {
        $status_message = 'حدث خطأ أثناء تحديث المعلومات: ' . $conn->error;
        $status_type = 'error';
    }
    
    // Redirection pour éviter la resoumission du formulaire
    header("Location: liste_eleves.php?message=" . urlencode($status_message) . "&type=" . $status_type . ($classe_filter > 0 ? "&classe_id=" . $classe_filter : ""));
    exit;
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id_eleve = $_POST['id_eleve'];

    $delete_sql = "DELETE FROM eleves WHERE id_eleve = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id_eleve);

    if ($delete_stmt->execute()) {
        $status_message = 'تم حذف الطالب بنجاح';
        $status_type = 'success';
    } else {
        $status_message = 'حدث خطأ أثناء حذف الطالب: ' . $conn->error;
        $status_type = 'error';
    }
    
    // Redirection pour éviter la resoumission du formulaire
    header("Location: liste_eleves.php?message=" . urlencode($status_message) . "&type=" . $status_type . ($classe_filter > 0 ? "&classe_id=" . $classe_filter : ""));
    exit;
}

// Récupération des messages de statut depuis l'URL
if (isset($_GET['message'])) {
    $status_message = $_GET['message'];
    $status_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة التلاميذ</title>
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

        .students-card {
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

        .students-table {
            width: 100%;
            border-collapse: collapse;
        }

        .students-table th,
        .students-table td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }

        .students-table th {
            background-color: #f8f9fa;
            font-weight: 700;
            color: var(--dark-color);
            position: sticky;
            top: 0;
            font-size: 14px;
        }

        .students-table tr:hover {
            background-color: #f8f9fa;
        }

        .students-table tr:last-child td {
            border-bottom: none;
        }

        .student-name {
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-avatar {
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

        .student-email {
            color: #666;
            font-size: 13px;
        }

        .student-class {
            background-color: var(--light-color);
            color: var(--dark-color);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
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

            .students-table th,
            .students-table td {
                padding: 10px;
            }

            .student-avatar {
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
                <i class="fas fa-user-graduate"></i>
                قائمة التلاميذ
            </h1>
            <a href="ajouter_eleve.php" class="btn-add">
                <i class="fas fa-plus"></i>
                إضافة تلميذ جديد
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
                <label for="classe_id" class="filter-label">
                    <i class="fas fa-filter"></i>
                    تصفية حسب القسم:
                </label>
                <select name="classe_id" id="classe_id" class="filter-select">
                <option value="0">جميع الأقسام</option>
                    <?php while ($classe = $classes_result->fetch_assoc()): ?>
                        <option value="<?php echo $classe['id_classe']; ?>" <?php echo ($classe_filter == $classe['id_classe']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($classe['nom_classe']); ?>
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
                <a href="?<?php echo $classe_filter > 0 ? 'classe_id='.$classe_filter.'&' : ''; ?>letter=all" class="alphabet-btn <?php echo !isset($_GET['letter']) || $_GET['letter'] == 'all' ? 'active' : ''; ?>">الكل</a>
                <?php
                // Lettres arabes courantes
                $arabic_letters = ['أ', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي'];
                
                foreach ($arabic_letters as $letter) {
                    $active = isset($_GET['letter']) && $_GET['letter'] == urlencode($letter) ? 'active' : '';
                    echo '<a href="?'.($classe_filter > 0 ? 'classe_id='.$classe_filter.'&' : '').'letter='.urlencode($letter).'" class="alphabet-btn '.$active.'">'.$letter.'</a>';
                }
                ?>
            </div>
        </div>

        <div class="students-card">
            <div class="card-header">
            <span>قائمة التلاميذ</span>
            <span class="card-header-count"><?php echo $eleves_result->num_rows; ?> تلميذ</span>
            </div>
            <div class="table-responsive">
                <?php if ($eleves_result->num_rows > 0): ?>
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الفصل</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($eleve = $eleves_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="student-name">
                                            <div class="student-avatar" style="background-color: <?php echo '#' . substr(md5($eleve['nom']), 0, 6); ?>">
                                                <?php echo $eleve['premiere_lettre']; ?>
                                            </div>
                                            <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?>
                                        </div>
                                    </td>
                                    <td class="student-email"><?php echo !empty($eleve['email']) ? htmlspecialchars($eleve['email']) : '<em style="color: #999;">غير متوفر</em>'; ?></td>
                                    <td><span class="student-class"><?php echo htmlspecialchars($eleve['nom_classe']); ?></span></td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn-icon btn-view" onclick="openDetailModal(<?php echo $eleve['id_eleve']; ?>)" title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-edit" onclick="openEditModal(<?php echo $eleve['id_eleve']; ?>, '<?php echo htmlspecialchars(addslashes($eleve['nom'])); ?>', '<?php echo htmlspecialchars(addslashes($eleve['prenom'])); ?>', '<?php echo htmlspecialchars(addslashes($eleve['email'])); ?>', '<?php echo htmlspecialchars(addslashes($eleve['login'])); ?>', '<?php echo htmlspecialchars(addslashes($eleve['mp'])); ?>', <?php echo $eleve['id_classe']; ?>)" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-delete" onclick="openDeleteModal(<?php echo $eleve['id_eleve']; ?>, '<?php echo htmlspecialchars(addslashes($eleve['nom'] . ' ' . $eleve['prenom'])); ?>')" title="حذف">
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
                        <p>لا يوجد تلاميذ لعرضهم</p>
                        <a href="ajouter_eleve.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            إضافة تلميذ جديد
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de modification amélioré -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
            <h3 class="modal-title">تعديل معلومات التلميذ</h3>
                <button type="button" class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form id="editForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_eleve" id="edit_id_eleve">
                    
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
                            <h4><i class="fas fa-school"></i> معلومات الدراسة</h4>
                            <div class="form-group mb-0">
                            <label for="id_classe" class="form-label">القسم:</label>
                                <select name="id_classe" id="edit_id_classe" class="form-control" required>
                                    <?php
                                    // Réinitialiser le pointeur de résultat
                                    $classes_result = $conn->query("SELECT id_classe, nom_classe FROM classes ORDER BY nom_classe");
                                    while ($classe = $classes_result->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $classe['id_classe']; ?>"><?php echo htmlspecialchars($classe['nom_classe']); ?></option>
                                    <?php endwhile; ?>
                                </select>
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
                    <input type="hidden" name="id_eleve" id="delete_id_eleve">
                    <p class="text-center">هل أنت متأكد من حذف التلميذ <strong id="delete_student_name"></strong>؟</p>
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

    <!-- Modal de détails -->
    <div id="detailModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
            <h3 class="modal-title">تفاصيل التلميذ</h3>
                <button type="button" class="modal-close" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="detailContent" class="student-details">
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

    <script>
        // Fonction pour ouvrir la modale de modification
        function openEditModal(id, nom, prenom, email, login, mp, id_classe) {
            document.getElementById('edit_id_eleve').value = id;
            document.getElementById('edit_nom').value = nom;
            document.getElementById('edit_prenom').value = prenom;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_login').value = login;
            document.getElementById('edit_mp').value = mp;
            document.getElementById('edit_id_classe').value = id_classe;
            
            document.getElementById('editModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Fonction pour ouvrir la modale de suppression
        function openDeleteModal(id, nom) {
            document.getElementById('delete_id_eleve').value = id;
            document.getElementById('delete_student_name').textContent = nom;
            
            document.getElementById('deleteModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Fonction pour ouvrir la modale de détails
        function openDetailModal(id) {
            document.getElementById('detailModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Charger les détails via AJAX
            fetch('view_student.php?id_eleve=' + id)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('detailContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('detailContent').innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-exclamation-circle" style="font-size: 24px; color: var(--danger-color);"></i>
                            <p>حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.</p>
                        </div>
                    `;
                });
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
