<?php
// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "u504721134_formation");
$conn->set_charset("utf8");

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'ajouter') {
            $stmt = $conn->prepare("INSERT INTO inspecteurs (nom, prenom, email, login, mot_de_passe) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['login'], $_POST['mot_de_passe']);
            $stmt->execute();
            $success = "تمت إضافة المتفقد بنجاح";
        } elseif ($_POST['action'] === 'modifier') {
            $stmt = $conn->prepare("UPDATE inspecteurs SET nom=?, prenom=?, email=?, login=?, mot_de_passe=? WHERE id_inspecteur=?");
            $stmt->bind_param("sssssi", $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['login'], $_POST['mot_de_passe'], $_POST['id']);
            $stmt->execute();
            $success = "تم تعديل المتفقد بنجاح";
        } elseif ($_POST['action'] === 'supprimer') {
            $stmt = $conn->prepare("DELETE FROM inspecteurs WHERE id_inspecteur=?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            $success = "تم حذف المتفقد بنجاح";
        }
        
        // Redirection avec message de succès
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=" . urlencode($success));
        exit;
    }
}

// Récupération des inspecteurs
$inspecteurs = $conn->query("SELECT * FROM inspecteurs ORDER BY date_creation DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المتفقدين | نظام إدارة التفتيش</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-color: #1e40af;
            --primary-light: #3b82f6;
            --primary-dark: #1e3a8a;
            --secondary-color: #0ea5e9;
            --accent-color: #f97316;
            --light-color: #f8fafc;
            --dark-color: #0f172a;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark-color);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2.5rem 0;
            border-radius: 0 0 2rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .page-header h1 {
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .page-header .lead {
            font-weight: 500;
            opacity: 0.9;
        }
        
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 700;
        }
        
        .card-footer {
            background-color: white;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .btn {
            border-radius: 0.5rem;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(30, 64, 175, 0.3);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-success:hover, .btn-success:focus {
            background-color: #0ca678;
            border-color: #0ca678;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover, .btn-danger:focus {
            background-color: #dc2626;
            border-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover, .btn-outline-primary:focus {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(30, 64, 175, 0.2);
        }
        
        .table {
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }
        
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table thead th {
            font-weight: 600;
            border: none;
            padding: 1rem;
        }
        
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: rgba(0, 0, 0, 0.05);
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            margin: 0 3px;
            border: none;
            cursor: pointer;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .view-btn {
            background-color: var(--info-color);
            color: white;
        }
        
        .view-btn:hover {
            background-color: #0891b2;
        }
        
        .edit-btn {
            background-color: var(--warning-color);
            color: white;
        }
        
        .edit-btn:hover {
            background-color: #d97706;
        }
        
        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #dc2626;
        }
        
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }
        
        .modal-content {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.25rem 1.5rem;
        }
        
        .modal-header .btn-close {
            color: white;
            background: rgba(255, 255, 255, 0.5);
            opacity: 1;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }
        
        .form-control, .input-group-text {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        
        .input-group-text {
            background-color: #f8fafc;
            color: #64748b;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .alert {
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
        }
        
        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
            z-index: 10;
        }
        
        /* Animation pour les notifications */
        @keyframes slideInDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .notification {
            animation: slideInDown 0.5s ease forwards;
        }
        
        /* DataTables customization */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1.5rem;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 0.5rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 0.5rem 1rem;
            margin-right: 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_length select {
            border-radius: 0.5rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 0.5rem;
            margin: 0 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 1.5rem;
            padding-top: 1rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: white;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--primary-light);
            border-color: var(--primary-light);
            color: white !important;
        }
        
        /* Loader */
        .loader {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 3px solid white;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-left: 0.5rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem 0;
                border-radius: 0 0 1rem 1rem;
            }
            
            .action-buttons {
                display: flex;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .table td, .table th {
                padding: 0.75rem;
            }
            
            .btn {
                padding: 0.4rem 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-school me-2"></i>نظام إدارة التفتيش
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>لوحة التحكم
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="view_inspecteur.php">
                        <i class="fas fa-user-tie me-1"></i>المتفقدين
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-calendar-check me-1"></i>الزيارات
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-file-alt me-1"></i>التقارير
                    </a>
                </li>
            </ul>
            <div class="d-flex">
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>المدير
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i>الإعدادات</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="fas fa-user-tie me-2"></i>إدارة المتفقدين</h1>
                <p class="lead">قائمة المتفقدين وإدارة بياناتهم في النظام</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus-circle me-2"></i>إضافة متفقد جديد
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Notification de succès -->
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success notification mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <!-- Card principale -->
    <div class="card mb-4">
        <div class="card-header py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-list me-2"></i>قائمة المتفقدين
                    </h5>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="inspecteurs-table" class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>اللقب</th>
                            <th>البريد الإلكتروني</th>
                            <th>إسم الدخول</th>
                            <th>تاريخ الإضافة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        while ($row = $inspecteurs->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($row['nom']) ?></td>
                                <td><?= htmlspecialchars($row['prenom']) ?></td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($row['email']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['login']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?= date('Y-m-d', strtotime($row['date_creation'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex action-buttons">
                                        <a href="view_inspecteur_details.php?id=<?= $row['id_inspecteur'] ?>" 
                                           class="action-btn view-btn" 
                                           data-bs-toggle="tooltip" 
                                           title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <a href="modifier_inspecteur.php?id=<?= $row['id_inspecteur'] ?>" 
                                           class="action-btn edit-btn" 
                                           data-bs-toggle="tooltip" 
                                           title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <button type="button" 
                                                class="action-btn delete-btn" 
                                                data-bs-toggle="tooltip" 
                                                title="حذف"
                                                onclick="confirmDelete(<?= $row['id_inspecteur'] ?>, '<?= htmlspecialchars($row['nom'] . ' ' . $row['prenom']) ?>')">
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
        <div class="card-footer py-3">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        يمكنك إضافة، تعديل، أو حذف المتفقدين من هذه الصفحة
                    </small>
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    <a href="dashboard.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-arrow-right me-1"></i>
                        العودة إلى لوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'ajout -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="addForm">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalLabel">
                    <i class="fas fa-user-plus me-2"></i>إضافة متفقد جديد
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="ajouter">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nom" class="form-label">الاسم</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="nom" id="nom" class="form-control" placeholder="أدخل الاسم" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="prenom" class="form-label">اللقب</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="prenom" id="prenom" class="form-control" placeholder="أدخل اللقب" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="أدخل البريد الإلكتروني" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="login" class="form-label">إسم الدخول</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                        <input type="text" name="login" id="login" class="form-control" placeholder="أدخل إسم الدخول" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="mot_de_passe" class="form-label">كلمة المرور</label>
                    <div class="input-group password-toggle">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" placeholder="أدخل كلمة المرور" required>
                        <span class="toggle-password" onclick="togglePassword('mot_de_passe')">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                    <div class="form-text text-muted mt-1">
                        <i class="fas fa-info-circle me-1"></i>
                        يجب أن تكون كلمة المرور 6 أحرف على الأقل
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>إلغاء
                </button>
                <button type="submit" class="btn btn-success" id="submitBtn">
                    <i class="fas fa-save me-1"></i>إضافة
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>تأكيد الحذف
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fs-5">هل أنت متأكد من حذف المتفقد <span id="inspecteurName" class="fw-bold"></span>؟</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    هذا الإجراء لا يمكن التراجع عنه.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    
                    <i class="fas fa-times me-1"></i>إلغاء
                </button>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i>تأكيد الحذف
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    // Initialisation des tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    
    // Initialisation de DataTables avec traduction arabe personnalisée
    $(document).ready(function() {
        $('#inspecteurs-table').DataTable({
            language: {
                "sProcessing": "جارٍ التحميل...",
                "sLengthMenu": "عرض _MENU_ مدخلات",
                "sZeroRecords": "لم يعثر على أية سجلات",
                "sInfo": "إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل",
                "sInfoEmpty": "يعرض 0 إلى 0 من أصل 0 سجل",
                "sInfoFiltered": "(منتقاة من مجموع _MAX_ مدخل)",
                "sInfoPostFix": "",
                "sSearch": "بحث:",
                "sUrl": "",
                "oPaginate": {
                    "sFirst": "الأول",
                    "sPrevious": "السابق",
                    "sNext": "التالي",
                    "sLast": "الأخير"
                }
            },
            responsive: true,
            order: [[5, 'desc']], // Trier par date d'ajout (décroissant)
            columnDefs: [
                { orderable: false, targets: 6 } // Désactiver le tri pour la colonne des actions
            ]
        });
        
        // Masquer l'alerte de succès après 5 secondes
        setTimeout(function() {
            $('.alert-success').fadeOut('slow');
        }, 5000);
        
        // Validation du formulaire d'ajout
        $('#addForm').on('submit', function(e) {
            var password = $('#mot_de_passe').val();
            if (password.length < 6) {
                e.preventDefault();
                alert('يجب أن تكون كلمة المرور 6 أحرف على الأقل');
                return false;
            }
            
            // Ajouter un loader au bouton de soumission
            $('#submitBtn').html('<span class="loader"></span> جاري الإضافة...');
            $('#submitBtn').prop('disabled', true);
            
            return true;
        });
    });
    
    // Fonction pour afficher le modal de confirmation de suppression
    function confirmDelete(id, name) {
        document.getElementById('deleteId').value = id;
        document.getElementById('inspecteurName').textContent = name;
        
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
    
    // Fonction pour basculer l'affichage du mot de passe
    function togglePassword(inputId) {
        var input = document.getElementById(inputId);
        var icon = document.querySelector('.toggle-password i');
        
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
</body>
</html>