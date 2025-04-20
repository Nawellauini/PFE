<?php
$mysqli = new mysqli("localhost", "root", "", "u504721134_formation");
if ($mysqli->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $mysqli->connect_error);
}

$classes = [];
$sql_classes = "SELECT * FROM classes";
$result_classes = $mysqli->query($sql_classes);
if ($result_classes->num_rows > 0) {
    while ($row = $result_classes->fetch_assoc()) {
        $classes[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأقسام</title>
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

        .btn-add-class {
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

        .btn-add-class:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            color: white;
        }

        .filter-container {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filter-label {
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .filter-label i {
            color: var(--primary-color);
        }

        .filter-select {
            flex-grow: 1;
            padding: 10px 15px;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-size: 1rem;
            color: var(--dark-color);
            transition: var(--transition);
            background-color: white;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%233949ab' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 15px center;
            background-size: 16px;
            padding-left: 40px;
        }

        .filter-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
        }

        .class-card {
            background: white;
            border-radius: var(--card-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
            border: none;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .class-card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 20px;
            font-weight: 700;
            font-size: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
        }

        .class-card-body {
            padding: 20px;
        }

        .class-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn-class-action {
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            border: none;
        }

        .btn-import {
            background-color: var(--success-color);
            color: white;
        }

        .btn-import:hover {
            background-color: #388e3c;
            transform: translateY(-2px);
        }

        .btn-edit {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: #ffa000;
            transform: translateY(-2px);
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background-color: #d32f2f;
            transform: translateY(-2px);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .data-table th {
            background-color: var(--primary-light);
            color: white;
            font-weight: 600;
            text-align: right;
            padding: 12px 15px;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-light);
            background-color: white;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover td {
            background-color: var(--light-color);
        }

        .empty-message {
            text-align: center;
            padding: 15px;
            color: var(--gray-color);
            font-style: italic;
        }

        .badge-count {
            background-color: white;
            color: var(--primary-color);
            border-radius: 50px;
            padding: 3px 10px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        /* Modals */
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

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .form-control {
            padding: 10px 15px;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 0.25em;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            margin-right: 8px;
        }

        .btn-modal {
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
            border: none;
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

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .filter-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-select {
                width: 100%;
            }

            .class-actions {
                flex-wrap: wrap;
            }

            .btn-class-action {
                font-size: 0.8rem;
                padding: 6px 12px;
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
            <i class="fas fa-school"></i>
            إدارة الأقسام
        </h1>
        <a href="add_classe.php" class="btn-add-class">
            <i class="fas fa-plus"></i>
            إضافة قسم جديد
        </a>
    </div>

<?php
$message = '';
$alertType = '';

if (isset($_GET['success'])) {
        echo '<div class="alert alert-success fade-in" role="alert" id="actionAlert">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong>تم بنجاح!</strong> تم حذف القسم بنجاح.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
} elseif (isset($_GET['error'])) {
        echo '<div class="alert alert-danger fade-in" role="alert" id="actionAlert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>خطأ!</strong> حدث خطأ أثناء حذف القسم.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $alertType ?> fade-in" role="alert" id="actionAlert">
        <i class="fas fa-<?= $alertType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <div><?= $message ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

    <div class="filter-container slide-in">
        <label for="filterSelect" class="filter-label">
            <i class="fas fa-filter"></i>
            تصفية الأقسام:
        </label>
        <select id="filterSelect" class="filter-select">
            <option value="all">جميع الأقسام (<?= count($classes) ?>)</option>
            <?php foreach ($classes as $c): ?>
                <option value="class-<?= $c['id_classe'] ?>"><?= htmlspecialchars($c['nom_classe']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php foreach ($classes as $index => $classe):
        $id_classe = $classe['id_classe'];

        $eleves = [];
        $res = $mysqli->query("SELECT * FROM eleves WHERE id_classe = $id_classe");
        if ($res) while ($e = $res->fetch_assoc()) $eleves[] = $e;

        $profs = [];
        $res = $mysqli->query("
            SELECT p.* FROM professeurs p
            JOIN professeurs_classes pc ON p.id_professeur = pc.id_professeur
            WHERE pc.id_classe = $id_classe
        ");
        if ($res) while ($p = $res->fetch_assoc()) $profs[] = $p;
    ?>
        <div class="class-card class-<?= $id_classe ?> slide-in" style="animation-delay: <?= $index * 0.1 ?>s">
            <div class="class-card-header">
                <div>
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    <?= htmlspecialchars($classe['nom_classe']) ?>
                </div>
                <span class="badge-count">
                    <i class="fas fa-users me-1"></i>
                    <?= count($eleves) ?> تلميذ | <?= count($profs) ?> أستاذ
                </span>
            </div>
            <div class="class-card-body">
                <div class="class-actions">
                    <button class="btn-class-action btn-import" data-bs-toggle="modal" data-bs-target="#importModal<?= $id_classe ?>">
                        <i class="fas fa-file-import"></i>
                        استيراد تلاميذ
                    </button>
                    <button class="btn-class-action btn-edit" data-bs-toggle="modal" data-bs-target="#editClassModal<?= $id_classe ?>">
                        <i class="fas fa-edit"></i>
                        تعديل القسم
                    </button>
                    <button class="btn-class-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $id_classe ?>">
                        <i class="fas fa-trash-alt"></i>
                        حذف القسم
                    </button>
                </div>

                <h5 class="section-title">
                    <i class="fas fa-user-graduate"></i>
                    قائمة التلاميذ
                </h5>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="30%">الاسم</th>
                            <th width="30%">اللقب</th>
                            <th width="40%">البريد الإلكتروني</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($eleves)): foreach ($eleves as $el): ?>
                            <tr>
                                <td><?= htmlspecialchars($el['nom']) ?></td>
                                <td><?= htmlspecialchars($el['prenom']) ?></td>
                                <td><?= htmlspecialchars($el['email']) ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="3" class="empty-message">
                                    <i class="fas fa-info-circle me-2"></i>
                                    لا يوجد تلاميذ مسجلين في هذا القسم.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h5 class="section-title">
                    <i class="fas fa-chalkboard-teacher"></i>
                    قائمة الأساتذة
                </h5>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="30%">الاسم</th>
                            <th width="30%">اللقب</th>
                            <th width="40%">البريد الإلكتروني</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($profs)): foreach ($profs as $pr): ?>
                            <tr>
                                <td><?= htmlspecialchars($pr['nom']) ?></td>
                                <td><?= htmlspecialchars($pr['prenom']) ?></td>
                                <td><?= htmlspecialchars($pr['email']) ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="3" class="empty-message">
                                    <i class="fas fa-info-circle me-2"></i>
                                    لا يوجد أساتذة مسجلين في هذا القسم.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de modification du nom de classe -->
        <div class="modal fade" id="editClassModal<?= $id_classe ?>" tabindex="-1" aria-labelledby="editClassModalLabel<?= $id_classe ?>" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" action="modifier_classe.php">
              <input type="hidden" name="id_classe" value="<?= $id_classe ?>">
              <div class="modal-content">
                <div class="modal-header">
                            <h5 class="modal-title" id="editClassModalLabel<?= $id_classe ?>">
                                <i class="fas fa-edit me-2"></i>
                                تعديل اسم القسم
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                            <div class="mb-3">
                                <label for="nom_classe<?= $id_classe ?>" class="form-label">اسم القسم:</label>
                                <input type="text" name="nom_classe" id="nom_classe<?= $id_classe ?>" class="form-control" value="<?= htmlspecialchars($classe['nom_classe']) ?>" required>
                            </div>
                </div>
                <div class="modal-footer">
                            <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </button>
                            <button type="submit" class="btn-modal btn-modal-warning">
                                <i class="fas fa-save me-1"></i>
                                حفظ التعديل
                            </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Modal d'importation d'élèves -->
        <div class="modal fade" id="importModal<?= $id_classe ?>" tabindex="-1" aria-labelledby="importModalLabel<?= $id_classe ?>" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" action="importer_eleves.php" enctype="multipart/form-data">
              <input type="hidden" name="id_classe" value="<?= $id_classe ?>">
              <div class="modal-content">
                <div class="modal-header">
                            <h5 class="modal-title" id="importModalLabel<?= $id_classe ?>">
                                <i class="fas fa-file-import me-2"></i>
                                استيراد تلاميذ للقسم: <?= htmlspecialchars($classe['nom_classe']) ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <?php if (count($eleves)): ?>
                                <div class="mb-4">
                                    <h6 class="mb-3 fw-bold">
                                        <i class="fas fa-trash-alt me-2 text-danger"></i>
                                        حذف التلاميذ الحاليين
                                    </h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="selectAll<?= $id_classe ?>">
                                        <label class="form-check-label fw-bold" for="selectAll<?= $id_classe ?>">
                                            تحديد الكل
                                        </label>
                    </div>
                                    <div class="student-list" style="max-height: 200px; overflow-y: auto; padding: 10px; border: 1px solid #eee; border-radius: 8px;">
                    <?php foreach ($eleves as $el): ?>
                                            <div class="form-check mb-2">
                            <input class="form-check-input eleve-checkbox-<?= $id_classe ?>" type="checkbox" name="eleves_a_supprimer[]" value="<?= $el['id_eleve'] ?>" id="eleve<?= $el['id_eleve'] ?>">
                            <label class="form-check-label" for="eleve<?= $el['id_eleve'] ?>">
                                <?= htmlspecialchars($el['nom']) ?> <?= htmlspecialchars($el['prenom']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                                    </div>
                                </div>
                  <?php else: ?>
                                <div class="alert alert-info mb-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    لا يوجد تلاميذ حاليين في هذا القسم.
                                </div>
                  <?php endif; ?>
                            
                            <div class="mb-3">
                                <h6 class="mb-3 fw-bold">
                                    <i class="fas fa-file-excel me-2 text-success"></i>
                                    استيراد ملف Excel
                                </h6>
                                <label for="fichier_excel<?= $id_classe ?>" class="form-label">اختر ملف Excel:</label>
                                <input type="file" name="fichier_excel" id="fichier_excel<?= $id_classe ?>" class="form-control" required>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle me-1"></i>
                                    يجب أن يحتوي الملف على الأعمدة التالية: الاسم، اللقب، البريد الإلكتروني، اسم المستخدم، كلمة المرور
                                </small>
                            </div>
                </div>
                <div class="modal-footer">
                            <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </button>
                            <button type="submit" class="btn-modal btn-modal-success">
                                <i class="fas fa-file-import me-1"></i>
                                استيراد
                            </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Modal de suppression de classe -->
        <div class="modal fade" id="deleteModal<?= $id_classe ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $id_classe ?>" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" action="supprimer_classe.php">
              <input type="hidden" name="id_classe" value="<?= $id_classe ?>">
              <div class="modal-content">
                <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel<?= $id_classe ?>">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                تأكيد الحذف
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                            </div>
                            <p class="text-center fs-5">
                                هل أنت متأكد أنك تريد حذف القسم 
                                <strong class="text-danger"><?= htmlspecialchars($classe['nom_classe']) ?></strong>؟
                            </p>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>تنبيه:</strong> سيتم حذف جميع بيانات القسم بما في ذلك التلاميذ والأساتذة المرتبطين به.
                            </div>
                </div>
                <div class="modal-footer">
                            <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </button>
                            <button type="submit" class="btn-modal btn-modal-danger">
                                <i class="fas fa-trash-alt me-1"></i>
                                تأكيد الحذف
                            </button>
                </div>
              </div>
            </form>
          </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($classes)): ?>
        <div class="class-card slide-in">
            <div class="class-card-body text-center py-5">
                <i class="fas fa-school text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                <h3 class="mt-4 text-muted">لا توجد أقسام</h3>
                <p class="text-muted mb-4">لم يتم إضافة أي قسم بعد</p>
                <a href="ajouter_classe.php" class="btn-add-class mx-auto" style="width: fit-content;">
                    <i class="fas fa-plus"></i>
                    إضافة قسم جديد
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Filtrage des classes
document.getElementById("filterSelect").addEventListener("change", function() {
    const selected = this.value;
    document.querySelectorAll(".class-card").forEach(card => {
        card.style.display = (selected === "all" || card.classList.contains(selected)) ? "block" : "none";
    });
});

// Fermeture automatique des alertes
setTimeout(function () {
    const alertBox = document.getElementById("actionAlert");
    if (alertBox) {
        const alert = bootstrap.Alert.getOrCreateInstance(alertBox);
        alert.close();
    }
}, 5000);

// Sélection de tous les élèves dans les modales d'importation
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($classes as $classe): ?>
    const selectAllCheckbox<?= $classe['id_classe'] ?> = document.getElementById('selectAll<?= $classe['id_classe'] ?>');
    if (selectAllCheckbox<?= $classe['id_classe'] ?>) {
        selectAllCheckbox<?= $classe['id_classe'] ?>.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.eleve-checkbox-<?= $classe['id_classe'] ?>');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    <?php endforeach; ?>
});
</script>
</body>
</html>