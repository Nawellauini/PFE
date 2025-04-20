<?php
// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "u504721134_formation");
$conn->set_charset("utf8");

// Vérification si un ID a été passé dans l'URL
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Requête pour récupérer les détails de l'inspecteur
    $stmt = $conn->prepare("SELECT * FROM inspecteurs WHERE id_inspecteur = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Vérification si un inspecteur avec cet ID existe
    if ($result->num_rows > 0) {
        $inspecteur = $result->fetch_assoc();
    } else {
        $error = "المتفقد غير موجود.";
    }
} else {
    $error = "معرف المتفقد غير محدد.";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل المتفقد | نظام إدارة التفتيش</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            position: relative;
            border-radius: 0 0 2rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgcGF0dGVyblRyYW5zZm9ybT0icm90YXRlKDQ1KSI+PHJlY3QgaWQ9InBhdHRlcm4tYmFja2dyb3VuZCIgd2lkdGg9IjQwMCUiIGhlaWdodD0iNDAwJSIgZmlsbD0icmdiYSgyNTUsIDI1NSwgMjU1LCAwLjApIj48L3JlY3Q+PGNpcmNsZSBmaWxsPSJyZ2JhKDI1NSwgMjU1LCAyNTUsIDAuMSkiIGN4PSIyMCIgY3k9IjIwIiByPSIxIj48L2NpcmNsZT48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IGZpbGw9InVybCgjcGF0dGVybikiIGhlaWdodD0iMTAwJSIgd2lkdGg9IjEwMCUiPjwvcmVjdD48L3N2Zz4=');
            opacity: 0.5;
            z-index: 0;
        }
        
        .profile-header .container {
            position: relative;
            z-index: 1;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 5px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }
        
        .profile-avatar i {
            font-size: 5rem;
            color: var(--primary-color);
        }
        
        .profile-name {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .profile-title {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 1rem;
            border-radius: 1rem;
            min-width: 120px;
            backdrop-filter: blur(5px);
            transition: transform 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 700;
            padding: 1.25rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .info-item {
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 1rem;
            flex-shrink: 0;
        }
        
        .info-content {
            flex-grow: 1;
        }
        
        .info-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1.125rem;
            font-weight: 500;
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
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: white;
        }
        
        .btn-warning:hover, .btn-warning:focus {
            background-color: #d97706;
            border-color: #d97706;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
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
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        
        .action-buttons .btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert {
            border-radius: 0.75rem;
            padding: 1.25rem 1.5rem;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-header {
                padding: 2rem 0;
            }
            
            .profile-avatar {
                width: 120px;
                height: 120px;
            }
            
            .profile-avatar i {
                font-size: 4rem;
            }
            
            .profile-name {
                font-size: 2rem;
            }
            
            .profile-stats {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }
            
            .stat-item {
                width: 80%;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
                justify-content: center;
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

<div class="container">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger mt-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
            <div class="mt-3">
                <a href="view_inspecteur.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-right me-1"></i> العودة إلى قائمة المتفقدين
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="container">
                <div class="profile-avatar">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h1 class="profile-name"><?= htmlspecialchars($inspecteur['nom'] . ' ' . $inspecteur['prenom']) ?></h1>
                <p class="profile-title">متفقد تربوي</p>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-value">0</div>
                        <div class="stat-label">الزيارات</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">0</div>
                        <div class="stat-label">التقارير</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= date('Y', strtotime($inspecteur['date_creation'])) ?></div>
                        <div class="stat-label">سنة التسجيل</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Informations personnelles -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2 text-primary"></i>المعلومات الشخصية
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">الاسم الكامل</div>
                                <div class="info-value"><?= htmlspecialchars($inspecteur['nom'] . ' ' . $inspecteur['prenom']) ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">البريد الإلكتروني</div>
                                <div class="info-value">
                                    <a href="mailto:<?= htmlspecialchars($inspecteur['email']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($inspecteur['email']) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user-tag"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">اسم المستخدم</div>
                                <div class="info-value"><?= htmlspecialchars($inspecteur['login']) ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">تاريخ التسجيل</div>
                                <div class="info-value"><?= date('Y-m-d', strtotime($inspecteur['date_creation'])) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Statistiques et actions -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-cogs me-2 text-primary"></i>الإجراءات
                    </div>
                    <div class="card-body">
                        <div class="action-buttons">
                            <a href="modifier_inspecteur.php?id=<?= $inspecteur['id_inspecteur'] ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i>
                                <span>تعديل البيانات</span>
                            </a>
                            
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $inspecteur['id_inspecteur'] ?>, '<?= htmlspecialchars($inspecteur['nom'] . ' ' . $inspecteur['prenom']) ?>')">
                                <i class="fas fa-trash-alt"></i>
                                <span>حذف المتفقد</span>
                            </button>
                        </div>
                        
                        <hr>
                        
                        <div class="action-buttons">
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fas fa-calendar-plus"></i>
                                <span>إضافة زيارة جديدة</span>
                            </a>
                            
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fas fa-file-alt"></i>
                                <span>عرض التقارير</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Dernière activité -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history me-2 text-primary"></i>آخر نشاط
                    </div>
                    <div class="card-body">
                        <p class="text-muted text-center">
                            <i class="fas fa-info-circle me-1"></i>
                            لا توجد أنشطة حديثة لهذا المتفقد
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 mb-5">
            <a href="view_inspecteur.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-right me-1"></i>
                العودة إلى قائمة المتفقدين
            </a>
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
                        <a href="#" id="deleteLink" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i>تأكيد الحذف
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Fonction pour afficher le modal de confirmation de suppression
    function confirmDelete(id, name) {
        document.getElementById('inspecteurName').textContent = name;
        document.getElementById('deleteLink').href = 'supprimer_inspecteur.php?id=' + id;
        
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>
</body>
</html>