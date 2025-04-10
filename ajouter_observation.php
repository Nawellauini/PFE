<?php
session_start();
include 'db_config.php'; 

if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];

// Récupérer les informations du professeur
$query_prof = "SELECT nom, prenom FROM professeurs WHERE id_professeur = ?";
$stmt_prof = $conn->prepare($query_prof);
$stmt_prof->bind_param("i", $id_professeur);
$stmt_prof->execute();
$result_prof = $stmt_prof->get_result();
$prof_info = $result_prof->fetch_assoc();
$nom_professeur = $prof_info ? $prof_info['prenom'] . ' ' . $prof_info['nom'] : 'الأستاذ(ة)';

// Récupération des classes enseignées par le professeur
$query_classes = "SELECT c.id_classe, c.nom_classe
                  FROM classes c
                  JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
                  WHERE pc.id_professeur = ?";
$stmt = $conn->prepare($query_classes);
$stmt->bind_param("i", $id_professeur);
$stmt->execute();
$result_classes = $stmt->get_result();

$eleves_result = null;
$classe_id = $_POST['classe_id'] ?? null;
$observation_added = false;

// Récupérer le nom de la classe sélectionnée
$classe_nom = "";
if ($classe_id) {
    $query_classe = "SELECT nom_classe FROM classes WHERE id_classe = ?";
    $stmt = $conn->prepare($query_classe);
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $result_classe = $stmt->get_result();
    $classe_data = $result_classe->fetch_assoc();
    $classe_nom = $classe_data ? $classe_data['nom_classe'] : "";
    
    // Récupération des élèves de la classe sélectionnée
    $query_eleves = "SELECT e.id_eleve, e.nom, e.prenom
                     FROM eleves e
                     WHERE e.id_classe = ?
                     ORDER BY e.nom, e.prenom";
    $stmt = $conn->prepare($query_eleves);
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $eleves_result = $stmt->get_result();
}

// Traitement de l'ajout d'observations
if (isset($_POST['ajouter_observation'])) {
    $observation = trim($_POST['observation']);
    $selected_eleves = $_POST['eleves'] ?? [];

    if (!empty($observation) && !empty($selected_eleves)) {
        foreach ($selected_eleves as $eleve_id) {
            $query_observation = "INSERT INTO observations (classe_id, eleve_id, observation, date_observation)
                                  VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($query_observation);
            $stmt->bind_param("iis", $classe_id, $eleve_id, $observation);
            $stmt->execute();
        }
        $observation_added = true;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة ملاحظة - نظام إدارة المدرسة</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --success-color: #27ae60;
            --border-radius: 12px;
            --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background-color: white;
            box-shadow: var(--box-shadow);
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .user-name {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .user-role {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            margin-bottom: 10px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: var(--border-radius);
            color: var(--dark-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .nav-link:hover {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
        }
        
        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .nav-icon {
            margin-left: 10px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-right: 280px;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .page-title-icon {
            margin-left: 10px;
            color: var(--primary-color);
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-label {
            font-weight: bold;
            margin-bottom: 8px;
            color: var(--dark-color);
        }
        
        .form-select, .form-control {
            border-radius: var(--border-radius);
            border: 2px solid #e9ecef;
            padding: 10px 15px;
            transition: var(--transition);
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .form-select:disabled, .form-control:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: bold;
            padding: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            max-height: 400px;
            overflow-y: auto;
        }
        
        .table-responsive::-webkit-scrollbar {
            width: 8px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }
        
        .student-name {
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .btn {
            border-radius: var(--border-radius);
            padding: 10px 20px;
            font-weight: bold;
            transition: var(--transition);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
        }
        
        .btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .selection-summary {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .summary-icon {
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        
        .summary-text {
            font-weight: bold;
        }
        
        .summary-value {
            color: var(--dark-color);
            background-color: white;
            padding: 5px 10px;
            border-radius: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .empty-state-text {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .empty-state-subtext {
            color: #7f8c8d;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: var(--border-radius);
        }
        
        .custom-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-label {
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 0;
        }
        
        .selected-count {
            margin-right: auto;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: none;
        }
        
        .observation-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .submit-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .submit-btn {
            padding: 12px 30px;
            font-size: 1.1rem;
            min-width: 200px;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                padding: 15px 10px;
            }
            
            .logo, .user-info, .nav-text {
                display: none;
            }
            
            .sidebar-header {
                padding-bottom: 10px;
                margin-bottom: 10px;
            }
            
            .nav-link {
                justify-content: center;
                padding: 12px;
            }
            
            .nav-icon {
                margin: 0;
            }
            
            .main-content {
                margin-right: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px;
            }
            
            .logo, .user-info, .nav-text {
                display: block;
            }
            
            .nav-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .nav-item {
                margin-bottom: 0;
                flex: 1;
                min-width: 120px;
            }
            
            .nav-link {
                flex-direction: column;
                text-align: center;
                padding: 10px;
                height: 100%;
            }
            
            .nav-icon {
                margin: 0 0 5px 0;
            }
            
            .main-content {
                margin-right: 0;
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">مدرستي</div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    if ($prof_info) {
                        echo substr($prof_info['prenom'], 0, 1) . substr($prof_info['nom'], 0, 1);
                    } else {
                        echo "أ";
                    }
                    ?>
                </div>
                <div class="user-name"><?php echo htmlspecialchars($nom_professeur); ?></div>
                <div class="user-role">أستاذ(ة)</div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home nav-icon"></i>
                        <span class="nav-text">الرئيسية</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="ajouter_observation.php" class="nav-link active">
                        <i class="fas fa-clipboard-list nav-icon"></i>
                        <span class="nav-text">إضافة ملاحظة</span>
                    </a>
                </li>
                
               
               
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt nav-icon"></i>
                        <span class="nav-text">تسجيل الخروج</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-clipboard-list page-title-icon"></i>
                    إضافة ملاحظة
                </h1>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right"></i>
                        العودة
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <span>اختيار القسم</span>
                    <div class="card-header-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" id="classe-form">
                        <div class="row">
                            <div class="col-md-6 mx-auto">
                                <div class="mb-3">
                                    <label for="classe_id" class="form-label">
                                        <i class="fas fa-chalkboard-teacher me-1"></i>
                                        اختر القسم:
                                    </label>
                                    <select name="classe_id" id="classe_id" class="form-select" required onchange="this.form.submit()">
                                        <option value="">-- اختر القسم --</option>
                                        <?php while ($row = $result_classes->fetch_assoc()): ?>
                                            <option value="<?= $row['id_classe'] ?>" <?= $classe_id == $row['id_classe'] ? 'selected' : '' ?>>
                                                <?= $row['nom_classe'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($classe_id): ?>
                <div class="selection-summary">
                    <i class="fas fa-chalkboard-teacher summary-icon"></i>
                    <span class="summary-text">القسم المختار:</span>
                    <span class="summary-value"><?php echo htmlspecialchars($classe_nom); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($eleves_result && $eleves_result->num_rows > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <span>إضافة ملاحظة للطلاب</span>
                        <div class="card-header-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" id="observation-form">
                            <input type="hidden" name="classe_id" value="<?= $classe_id ?>">
                            
                            <div class="checkbox-container">
                                <input type="checkbox" id="select_all" class="custom-checkbox" onclick="toggleAll(this)"> 
                                <label for="select_all" class="checkbox-label">تحديد الكل</label>
                                <span class="selected-count" id="selected-count">0 طالب محدد</span>
                            </div>
                            
                            <div class="table-responsive mb-4">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px">اختيار</th>
                                            <th>الطالب</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $eleves_result->fetch_assoc()): 
                                            $initials = substr($row['prenom'], 0, 1) . substr($row['nom'], 0, 1);
                                        ?>
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" name="eleves[]" value="<?= $row['id_eleve'] ?>" class="custom-checkbox eleve-checkbox">
                                                </td>
                                                <td>
                                                    <div class="student-name">
                                                        <div class="student-avatar"><?php echo $initials; ?></div>
                                                        <?php echo htmlspecialchars($row['nom'] . ' ' . $row['prenom']); ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mb-3">
                                <label for="observation" class="form-label">
                                    <i class="fas fa-comment-alt me-1"></i>
                                    أدخل الملاحظة:
                                </label>
                                <textarea name="observation" id="observation" class="form-control observation-textarea" required placeholder="اكتب ملاحظتك هنا..."></textarea>
                            </div>
                            
                            <div class="submit-container">
                                <button type="submit" name="ajouter_observation" class="btn btn-success btn-icon submit-btn" id="submit-btn" disabled>
                                    <i class="fas fa-plus"></i>
                                    إضافة الملاحظة
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($classe_id): ?>
                <div class="empty-state">
                    <i class="fas fa-users empty-state-icon"></i>
                    <div class="empty-state-text">لا يوجد طلاب في هذا القسم</div>
                    <div class="empty-state-subtext">يرجى التحقق من قائمة الطلاب في النظام أو اختيار قسم آخر</div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chalkboard-teacher empty-state-icon"></i>
                    <div class="empty-state-text">يرجى اختيار القسم أولاً</div>
                    <div class="empty-state-subtext">قم باختيار القسم لعرض قائمة الطلاب وإضافة ملاحظة</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Fonction pour mettre à jour le compteur de sélection
            function updateSelectedCount() {
                const count = $('.eleve-checkbox:checked').length;
                $('#selected-count').text(count + ' طالب محدد');
                
                if (count > 0) {
                    $('#selected-count').show();
                    $('#submit-btn').prop('disabled', false);
                } else {
                    $('#selected-count').hide();
                    $('#submit-btn').prop('disabled', true);
                }
            }
            
            // Mettre à jour le compteur lorsqu'une case est cochée
            $('.eleve-checkbox').change(function() {
                updateSelectedCount();
            });
            
            // Vérifier si le formulaire est valide avant de soumettre
            $('#observation-form').submit(function(e) {
                const selectedEleves = $('.eleve-checkbox:checked').length;
                const observation = $('#observation').val().trim();
                
                if (selectedEleves === 0) {
                    e.preventDefault();
                    Swal.fire({
                        title: "تنبيه!",
                        text: "يرجى اختيار طالب واحد على الأقل",
                        icon: "warning",
                        confirmButtonText: "موافق"
                    });
                    return false;
                }
                
                if (observation === '') {
                    e.preventDefault();
                    Swal.fire({
                        title: "تنبيه!",
                        text: "يرجى إدخال ملاحظة",
                        icon: "warning",
                        confirmButtonText: "موافق"
                    });
                    return false;
                }
                
                return true;
            });
            
            // Fonction pour adapter l'interface en fonction de la taille de l'écran
            function adjustLayout() {
                const width = window.innerWidth;
                const sidebar = document.querySelector('.sidebar');
                const mainContent = document.querySelector('.main-content');
                
                if (width <= 992) {
                    sidebar.classList.add('collapsed');
                    mainContent.style.marginRight = '70px';
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.style.marginRight = '280px';
                }
                
                if (width <= 768) {
                    mainContent.style.marginRight = '0';
                }
            }
            
            // Appliquer l'ajustement au chargement et au redimensionnement
            window.addEventListener('load', adjustLayout);
            window.addEventListener('resize', adjustLayout);
        });
        
        // Fonction pour sélectionner/désélectionner tous les élèves
        function toggleAll(source) {
            const checkboxes = document.getElementsByName('eleves[]');
            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
            
            // Mettre à jour le compteur
            const count = source.checked ? checkboxes.length : 0;
            $('#selected-count').text(count + ' طالب محدد');
            
            if (count > 0) {
                $('#selected-count').show();
                $('#submit-btn').prop('disabled', false);
            } else {
                $('#selected-count').hide();
                $('#submit-btn').prop('disabled', true);
            }
        }
        
        <?php if ($observation_added): ?>
            Swal.fire({
                title: "نجاح!",
                text: "تمت إضافة الملاحظة بنجاح.",
                icon: "success",
                confirmButtonText: "حسنًا"
            });
        <?php endif; ?>
    </script>
</body>
</html>

