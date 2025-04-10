<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير المتفقد: <?php echo htmlspecialchars($rapport['titre']); ?></title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #19367f;
            --primary-light: rgba(25, 54, 127, 0.1);
            --secondary-color: #4a6baf;
            --accent-color: #e9ecef;
            --text-color: #333;
            --text-light: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
        }
        
        .navbar-brand img {
            height: 40px;
            margin-left: 10px;
        }
        
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-dark .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.85);
        }
        
        .navbar-dark .navbar-nav .nav-link:hover {
            color: #fff;
        }
        
        .page-header {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: var(--primary-light);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .info-item i {
            color: var(--text-light);
            margin-left: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .info-label {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background-color: var(--accent-color);
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
        }
        
        .file-info {
            display: flex;
            align-items: center;
        }
        
        .file-info i {
            color: var(--text-light);
            margin-left: 0.75rem;
        }
        
        .file-name {
            font-weight: 500;
        }
        
        .file-date {
            font-size: 0.75rem;
            color: var(--text-light);
        }
        
        .action-card {
            text-align: center;
            padding: 1.5rem;
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .action-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .nav-tabs .nav-link {
            color: var(--text-color);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            background-color: transparent;
        }
        
        .footer {
            background-color: #f1f3f5;
            padding: 1.5rem 0;
            margin-top: 3rem;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1050;
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.5rem;
            }
        }
        
        /* Ajout d'animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Amélioration des boutons */
        .btn {
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            box-shadow: 0 2px 5px rgba(25, 54, 127, 0.2);
        }
        
        .btn-primary:hover {
            box-shadow: 0 4px 8px rgba(25, 54, 127, 0.3);
            transform: translateY(-2px);
        }
        
        /* Amélioration des cartes */
        .card {
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="uploads/photos_eleves/myschool.png" alt="Logo">
                <div>
                <span class="d-block">نظام إدارة تقارير المتفقد</span>
                    <small class="d-block" style="font-size: 0.7rem; opacity: 0.8;">وزارة التربية والتعليم</small>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="liste_rapports.php">التقارير</a>
                    </li>
                   
                    <li class="nav-item">
                    <a class="nav-link" href="liste_inspecteurs.php">المتفقدين</a>
                    </li>
                    
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div class="fade-in">
                    <h1 class="mb-1"><?php echo htmlspecialchars($rapport['titre']); ?></h1>
                    <p class="text-muted mb-0">معرف التقرير: <?php echo $rapport_id; ?></p>
                </div>
                <div class="mt-3 mt-md-0 fade-in" style="animation-delay: 0.2s;">
                    <a href="liste_rapports.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-right"></i> العودة للقائمة
                    </a>
                    <a href="generer_rapport.php?id=<?php echo $rapport_id; ?>&pdf=1" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> إنشاء PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container fade-in" style="animation-delay: 0.3s;">
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab" aria-selected="true">
                    <i class="fas fa-eye me-2"></i>معاينة التقرير
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="attachments-tab" data-bs-toggle="tab" data-bs-target="#attachments" type="button" role="tab" aria-selected="false">
                    <i class="fas fa-paperclip me-2"></i>الملفات المرفقة
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="actions-tab" data-bs-toggle="tab" data-bs-target="#actions" type="button" role="tab" aria-selected="false">
                    <i class="fas fa-cogs me-2"></i>إجراءات
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="reportTabsContent">
            <!-- Preview Tab -->
            <div class="tab-pane fade show active" id="preview" role="tabpanel" aria-labelledby="preview-tab">
                <!-- General Information Card -->
                <div class="card fade-in" style="animation-delay: 0.4s;">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i> معلومات عامة
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <div>
                                        <div class="info-label">تاريخ الإنشاء</div>
                                        <div class="info-value"><?php echo formater_date($rapport['date_creation']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <div>
                                        <div class="info-label">تاريخ التعديل</div>
                                        <div class="info-value">
                                            <?php echo $rapport['date_modification'] ? formater_date($rapport['date_modification']) : 'غير متوفر'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <i class="fas fa-school"></i>
                                    <div>
                                    <div class="info-label">القسم</div>
                                        <div class="info-value"><?php echo htmlspecialchars($rapport['nom_classe']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <i class="fas fa-user"></i>
                                    <div>
                                    <div class="info-label">المعلم</div>
                                        <div class="info-value">
                                            <?php echo htmlspecialchars($rapport['prof_nom'] . ' ' . $rapport['prof_prenom']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <i class="fas fa-user-tie"></i>
                                    <div>
                                    <div class="info-label">المتفقد</div>
                                        <div class="info-value">
                                            <?php echo htmlspecialchars($rapport['insp_nom'] . ' ' . $rapport['insp_prenom']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments Card -->
                <div class="card fade-in" style="animation-delay: 0.5s;">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-comments me-2"></i> التعليقات
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($rapport['commentaires'])); ?></p>
                    </div>
                </div>

                <!-- Recommendations Card -->
                <div class="card fade-in" style="animation-delay: 0.6s;">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-clipboard-list me-2"></i> التوصيات
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($rapport['recommandations'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Attachments Tab -->
            <div class="tab-pane fade" id="attachments" role="tabpanel" aria-labelledby="attachments-tab">
                <div class="card fade-in">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-paperclip me-2"></i> الملفات المرفقة
                        <span class="badge bg-secondary ms-2"><?php echo count($fichiers); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (count($fichiers) > 0): ?>
                            <?php foreach ($fichiers as $fichier): ?>
                                <div class="file-item fade-in">
                                    <div class="file-info">
                                        <i class="fas fa-file"></i>
                                        <div>
                                            <div class="file-name"><?php echo htmlspecialchars($fichier['nom_fichier']); ?></div>
                                            <div class="file-date"><?php echo formater_date($fichier['date_upload']); ?></div>
                                        </div>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($fichier['chemin_fichier']); ?>" class="btn btn-sm btn-outline-primary" download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-muted py-5">لا توجد ملفات مرفقة بهذا التقرير</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions Tab -->
            <div class="tab-pane fade" id="actions" role="tabpanel" aria-labelledby="actions-tab">
                <div class="card fade-in">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-cogs me-2"></i> إجراءات التقرير
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card action-card">
                                    <i class="fas fa-download"></i>
                                    <h5>تنزيل التقرير</h5>
                                    <p class="text-muted small">تنزيل نسخة PDF من التقرير</p>
                                    <a href="generer_rapport.php?id=<?php echo $rapport_id; ?>&download=1" class="btn btn-outline-primary mt-2">
                                        تنزيل
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card action-card">
                                    <i class="fas fa-print"></i>
                                    <h5>طباعة التقرير</h5>
                                    <p class="text-muted small">طباعة التقرير مباشرة</p>
                                    <a href="generer_rapport.php?id=<?php echo $rapport_id; ?>&print=1" class="btn btn-outline-primary mt-2">
                                        طباعة
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card action-card">
                                    <i class="fas fa-eye"></i>
                                    <h5>معاينة التقرير</h5>
                                    <p class="text-muted small">معاينة التقرير قبل الطباعة</p>
                                    <a href="generer_rapport.php?id=<?php echo $rapport_id; ?>&pdf=1" target="_blank" class="btn btn-outline-primary mt-2">
                                        معاينة
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="supprimer_rapport.php?id=<?php echo $rapport_id; ?>" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا التقرير؟')">
                            <i class="fas fa-trash-alt me-1"></i> حذف التقرير
                        </a>
                        <a href="modifier_rapport.php?id=<?php echo $rapport_id; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> تعديل التقرير
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container">
        <?php if (isset($_GET['message'])): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <strong class="me-auto">
                    <i class="fas fa-info-circle me-1"></i> تنبيه
                    </strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="container">
        <p class="mb-1">© <?php echo date('Y'); ?> نظام إدارة تقارير المتفقد - جميع الحقوق محفوظة</p>
          
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide toast after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const toastElList = [].slice.call(document.querySelectorAll('.toast'));
            toastElList.map(function(toastEl) {
                const toast = new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 5000
                });
                toast.show();
            });
            
            // Ajouter des animations lors du changement d'onglet
            const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabEls.forEach(tabEl => {
                tabEl.addEventListener('shown.bs.tab', function (event) {
                    const targetId = event.target.getAttribute('data-bs-target');
                    const targetPane = document.querySelector(targetId);
                    const cards = targetPane.querySelectorAll('.card');
                    
                    cards.forEach((card, index) => {
                        card.classList.add('fade-in');
                        card.style.animationDelay = `${0.1 * (index + 1)}s`;
                    });
                });
            });
        });
    </script>
</body>
</html>

