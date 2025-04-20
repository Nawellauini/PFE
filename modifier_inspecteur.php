<?php

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "u504721134_formation");
$conn->set_charset("utf8");

// Vérification de l'existence de l'ID dans l'URL
if (!isset($_GET['id'])) {
    header("Location: view_inspecteur.php");
    exit;
}

$id = $_GET['id'];

// Récupérer les données de l'inspecteur à modifier
$stmt = $conn->prepare("SELECT * FROM inspecteurs WHERE id_inspecteur = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$inspecteur = $result->fetch_assoc();

if (!$inspecteur) {
    $error = "المتفقد غير موجود.";
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($error)) {
    if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
        $stmt = $conn->prepare("UPDATE inspecteurs SET nom=?, prenom=?, email=?, login=?, mot_de_passe=? WHERE id_inspecteur=?");
        $stmt->bind_param("sssssi", $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['login'], $_POST['mot_de_passe'], $_POST['id']);
        
        if ($stmt->execute()) {
            header("Location: view_inspecteur.php?success=" . urlencode("تم تعديل بيانات المتفقد بنجاح"));
            exit;
        } else {
            $error = "حدث خطأ أثناء تحديث البيانات: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar"  dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات المتفقد | نظام إدارة التفتيش</title>
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
        
        .card-footer {
            background-color: white;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
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
            padding: 1.25rem 1.5rem;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
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
            
            .btn {
                padding: 0.4rem 1rem;
            }
            
            .card-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
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
        <h1><i class="fas fa-edit me-2"></i>تعديل بيانات المتفقد</h1>
        <p class="lead">تعديل معلومات المتفقد في النظام</p>
    </div>
</div>

<div class="container">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
            <div class="mt-3">
                <a href="view_inspecteur.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-right me-1"></i> العودة إلى قائمة المتفقدين
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-edit me-2 text-primary"></i>تعديل بيانات المتفقد: <?= htmlspecialchars($inspecteur['nom'] . ' ' . $inspecteur['prenom']) ?>
            </div>
            <div class="card-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" value="<?= $inspecteur['id_inspecteur'] ?>">
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">الاسم</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($inspecteur['nom']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">اللقب</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($inspecteur['prenom']) ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($inspecteur['email']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="login" class="form-label">إسم الدخول</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                            <input type="text" name="login" id="login" class="form-control" value="<?= htmlspecialchars($inspecteur['login']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="mot_de_passe" class="form-label">كلمة المرور</label>
                        <div class="input-group password-toggle">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" value="<?= htmlspecialchars($inspecteur['mot_de_passe']) ?>" required>
                            <span class="toggle-password" onclick="togglePassword('mot_de_passe')">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                        <div class="form-text text-muted mt-1">
                            <i class="fas fa-info-circle me-1"></i>
                            يجب أن تكون كلمة المرور 6 أحرف على الأقل
                        </div>
                    </div>
                    
                    <div class="card-footer text-end">
                        <a href="view_inspecteur.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-times me-1"></i>إلغاء
                        </a>
                        <button type="submit" class="btn btn-success" id="submitBtn">
                            <i class="fas fa-save me-1"></i>حفظ التعديلات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Validation du formulaire
    document.getElementById('editForm').addEventListener('submit', function(e) {
        var password = document.getElementById('mot_de_passe').value;
        if (password.length < 6) {
            e.preventDefault();
            alert('يجب أن تكون كلمة المرور 6 أحرف على الأقل');
            return false;
        }
        
        // Ajouter un loader au bouton de soumission
        document.getElementById('submitBtn').innerHTML = '<span class="loader"></span> جاري الحفظ...';
        document.getElementById('submitBtn').disabled = true;
        
        return true;
    });
    
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