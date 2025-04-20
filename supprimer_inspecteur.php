<?php

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "u504721134_formation");
$conn->set_charset("utf8");

// Initialisation des variables
$success = false;
$error = false;
$message = '';
$redirectDelay = 3; // Délai de redirection en secondes

// Vérification si un ID a été passé dans l'URL
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Vérifier si l'inspecteur existe
    $checkStmt = $conn->prepare("SELECT nom, prenom FROM inspecteurs WHERE id_inspecteur = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $inspecteur = $checkResult->fetch_assoc();
        $inspecteurName = $inspecteur['nom'] . ' ' . $inspecteur['prenom'];
        
        // Requête pour supprimer l'inspecteur
        $stmt = $conn->prepare("DELETE FROM inspecteurs WHERE id_inspecteur = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Vérifier si l'exécution a été réussie
        if ($stmt->affected_rows > 0) {
            $success = true;
            $message = "تم حذف المتفقد <strong>" . htmlspecialchars($inspecteurName) . "</strong> بنجاح.";
        } else {
            $error = true;
            $message = "حدث خطأ أثناء محاولة حذف المتفقد.";
        }
    } else {
        $error = true;
        $message = "المتفقد غير موجود في قاعدة البيانات.";
    }
} else {
    // Redirection si l'ID n'est pas fourni
    $error = true;
    $message = "لم يتم تحديد معرف المتفقد. لا يمكن إجراء عملية الحذف.";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف المتفقد | نظام إدارة التفتيش</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card-body {
            padding: 2rem;
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
        
        .alert {
            border-radius: 0.75rem;
            padding: 1.5rem;
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
        
        .icon-container {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
        }
        
        .success-icon {
            background-color: #d1fae5;
            color: var(--success-color);
        }
        
        .error-icon {
            background-color: #fee2e2;
            color: var(--danger-color);
        }
        
        .progress-container {
            width: 100%;
            background-color: #e2e8f0;
            border-radius: 0.5rem;
            height: 0.5rem;
            margin: 1.5rem 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 0.5rem;
            transition: width 1s linear;
        }
        
        .redirect-message {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem 0;
                border-radius: 0 0 1rem 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .icon-container {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
        }
    </style>
    <?php if ($success): ?>
    <meta http-equiv="refresh" content="<?= $redirectDelay ?>;url=view_inspecteur.php">
    <?php endif; ?>
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
    <div class="container text-center">
        <h1><i class="fas fa-trash-alt me-2"></i>حذف المتفقد</h1>
        <p class="lead">نتيجة عملية حذف المتفقد من النظام</p>
    </div>
</div>

<div class="main-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if ($success): ?>
                            <div class="icon-container success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h3 class="mb-4">تمت عملية الحذف بنجاح</h3>
                            <div class="alert alert-success">
                                <?= $message ?>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                            </div>
                            <p class="redirect-message">
                                سيتم توجيهك إلى صفحة المتفقدين خلال <span id="countdown"><?= $redirectDelay ?></span> ثوان
                            </p>
                        <?php else: ?>
                            <div class="icon-container error-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 class="mb-4">فشلت عملية الحذف</h3>
                            <div class="alert alert-danger">
                                <?= $message ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="view_inspecteur.php" class="btn btn-primary">
                                <i class="fas fa-list me-1"></i>
                                العودة إلى قائمة المتفقدين
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($success): ?>
<script>
    // Compte à rebours et barre de progression
    var seconds = <?= $redirectDelay ?>;
    var progressBar = document.getElementById('progressBar');
    var countdown = document.getElementById('countdown');
    
    function updateCountdown() {
        seconds--;
        if (seconds >= 0) {
            countdown.textContent = seconds;
            progressBar.style.width = ((<?= $redirectDelay ?> - seconds) / <?= $redirectDelay ?> * 100) + '%';
            setTimeout(updateCountdown, 1000);
        }
    }
    
    // Démarrer le compte à rebours
    updateCountdown();
</script>
<?php endif; ?>
</body>
</html>