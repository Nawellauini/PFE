<?php
session_start();
include 'db_config.php'; 

if (!isset($_SESSION['id_eleve'])) {
    header("Location: login.php");
    exit();
}

$id_eleve = $_SESSION['id_eleve'];

$query = "SELECT observation, date_observation 
          FROM observations 
          WHERE eleve_id = ? 
          ORDER BY date_observation DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_eleve);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملاحظاتي</title>
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
            border-radius: 12px;
            border: none;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
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
        }
        
        .card-body {
            padding: 25px;
        }
        
        .observation-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .observation-table th {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 700;
            padding: 15px;
            text-align: right;
            border: none;
        }
        
        .observation-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .observation-table tr:last-child td {
            border-bottom: none;
        }
        
        .observation-table tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .date-cell {
            color: var(--primary);
            font-weight: 500;
            white-space: nowrap;
        }
        
        .observation-cell {
            line-height: 1.8;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: var(--danger);
            font-weight: 500;
            font-size: 1.2rem;
        }
        
        .icon-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            background-color: var(--primary-light);
            color: var(--primary);
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
            .card-header {
                padding: 15px;
                font-size: 1.3rem;
            }
            
            .observation-table th,
            .observation-table td {
                padding: 12px 10px;
            }
            
            .date-cell {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .card {
                border-radius: 8px;
            }
            
            .observation-table {
                display: block;
            }
            
            .observation-table thead {
                display: none;
            }
            
            .observation-table tbody,
            .observation-table tr {
                display: block;
                width: 100%;
            }
            
            .observation-table td {
                display: block;
                text-align: right;
                padding: 10px;
                position: relative;
                border-bottom: none;
            }
            
            .observation-table td:before {
                content: attr(data-label);
                position: absolute;
                right: 10px;
                top: 10px;
                font-weight: 700;
                color: var(--primary);
            }
            
            .observation-table td.date-cell {
                background-color: var(--primary-light);
                color: var(--primary);
                font-weight: 700;
                border-radius: 8px 8px 0 0;
                margin-top: 15px;
                padding-right: 10px;
            }
            
            .observation-table td.observation-cell {
                padding: 15px 10px 20px;
                margin-bottom: 5px;
                border-bottom: 1px solid #eee;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                منصة الطالب
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="observations.php">ملاحظاتي</a>
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
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-right me-2"></i> العودة للرئيسية
        </a>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clipboard-list me-2"></i> ملاحظاتي
            </div>
            <div class="card-body">
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="observation-table">
                            <thead>
                                <tr>
                                    <th width="30%">
                                        <div class="icon-container">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        التاريخ
                                    </th>
                                    <th>
                                        <div class="icon-container">
                                            <i class="fas fa-comment-alt"></i>
                                        </div>
                                        الملاحظة
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="date-cell" data-label="التاريخ:">
                                            <i class="fas fa-calendar-day me-2"></i>
                                            <?= date('Y-m-d', strtotime($row['date_observation'])) ?>
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('H:i', strtotime($row['date_observation'])) ?>
                                            </div>
                                        </td>
                                        <td class="observation-cell" data-label="الملاحظة:">
                                            <?= htmlspecialchars($row['observation']) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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
            <p class="mb-0">&copy; <?= date('Y') ?> منصة الطالب - جميع الحقوق محفوظة</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>