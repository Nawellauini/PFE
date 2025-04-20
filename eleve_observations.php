<?php
session_start();
include 'db_config.php'; 

if (!isset($_SESSION['id_eleve'])) {
    header("Location: login.php");
    exit();
}

$id_eleve = $_SESSION['id_eleve'];

// Récupérer les informations de l'élève
$query_eleve = "SELECT e.nom, e.prenom, c.nom_classe, e.id_classe 
                FROM eleves e 
                JOIN classes c ON e.id_classe = c.id_classe 
                WHERE e.id_eleve = ?";
$stmt_eleve = $conn->prepare($query_eleve);
$stmt_eleve->bind_param("i", $id_eleve);
$stmt_eleve->execute();
$result_eleve = $stmt_eleve->get_result();
$eleve_info = $result_eleve->fetch_assoc();

// Récupérer les observations de l'élève
$query = "SELECT o.id, o.observation, o.date_observation, o.type_observation, o.classe_id 
          FROM observations o 
          WHERE o.eleve_id = ? 
          ORDER BY o.date_observation DESC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}

$stmt->bind_param("i", $id_eleve);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملاحظاتي - نظام إدارة المدرسة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3f51b5;
            --primary-light: #e8eaf6;
            --secondary: #ff9800;
            --success: #4caf50;
            --danger: #f44336;
            --dark: #333;
            --light: #f5f5f5;
            --border-radius: 12px;
            --box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }
        
        .navbar {
            background-color: var(--primary);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
        }
        
        .main-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: var(--primary);
            color: white;
            border-bottom: none;
            padding: 20px;
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .card-header-icon {
            margin-left: 10px;
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .student-info {
            background-color: var(--primary-light);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .student-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin-left: 20px;
        }
        
        .student-details h4 {
            margin-bottom: 5px;
            color: var(--primary);
        }
        
        .student-details p {
            margin-bottom: 0;
            color: var(--dark);
        }
        
        .observation-item {
            border: 1px solid #eee;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .observation-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: var(--primary-light);
        }
        
        .observation-date {
            display: flex;
            align-items: center;
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .date-icon {
            margin-left: 8px;
        }
        
        .observation-content {
            white-space: pre-line;
            line-height: 1.8;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .observation-type {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 10px;
            width: fit-content;
        }
        
        .type-individual {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .type-all-classes {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        
        .type-icon {
            margin-left: 6px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: var(--danger);
            font-weight: 500;
            font-size: 1.2rem;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background-color: var(--primary);
            color: white;
        }
        
        @media (max-width: 768px) {
            .student-info {
                flex-direction: column;
                text-align: center;
            }
            
            .student-avatar {
                margin-left: 0;
                margin-bottom: 15px;
            }
            
            .card-header {
                padding: 15px;
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard_eleve.php">
                <i class="fas fa-graduation-cap me-2"></i>
                منصة الطالب
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_eleve.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="eleve_observations.php">ملاحظاتي</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">الملف الشخصي</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <a href="dashboard_eleve.php" class="back-button">
            <i class="fas fa-arrow-right me-2"></i> العودة للرئيسية
        </a>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clipboard-list card-header-icon"></i>
                ملاحظاتي
            </div>
            <div class="card-body">
                <?php if ($eleve_info): ?>
                    <div class="student-info">
                        <div class="student-avatar">
                            <?= substr($eleve_info['prenom'], 0, 1) . substr($eleve_info['nom'], 0, 1) ?>
                        </div>
                        <div class="student-details">
                            <h4><?= htmlspecialchars($eleve_info['prenom'] . ' ' . $eleve_info['nom']) ?></h4>
                            <p>
                                <i class="fas fa-chalkboard-teacher me-2"></i>
                                <?= htmlspecialchars($eleve_info['nom_classe']) ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($result->num_rows > 0): ?>
                    <div class="observations-list">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="observation-item">
                                <div class="observation-date">
                                    <i class="fas fa-calendar-day date-icon"></i>
                                    <span><?= date('Y-m-d', strtotime($row['date_observation'])) ?></span>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-clock date-icon"></i>
                                    <span><?= date('H:i', strtotime($row['date_observation'])) ?></span>
                                </div>
                                <div class="observation-content">
                                    <?= nl2br(htmlspecialchars($row['observation'])) ?>
                                </div>
                                
                                <?php if ($row['type_observation'] == 'toutes_classes'): ?>
                                    <div class="observation-type type-all-classes">
                                        <i class="fas fa-users type-icon"></i>
                                        ملاحظة لجميع الطلاب
                                    </div>
                                <?php else: ?>
                                    <div class="observation-type type-individual">
                                        <i class="fas fa-user type-icon"></i>
                                        ملاحظة شخصية
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle me-2"></i>
                        لا توجد ملاحظات مسجلة حتى الآن
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> نظام إدارة المدرسة - جميع الحقوق محفوظة</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>