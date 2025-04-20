<?php

include 'db_config.php';



$id_professeur = $_SESSION['id_professeur'];

// Récupérer les demandes d'impression avec leur statut
$query = "SELECT d.*, 
          CASE 
              WHEN d.statut = 'accepte' THEN 'تم قبول طلب الطباعة'
              WHEN d.statut = 'refuse' THEN 'تم رفض طلب الطباعة'
              ELSE 'في انتظار الرد'
          END as message_statut
          FROM demandes_impression d
          WHERE d.id_prof = ?
          ORDER BY d.date_demande DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_professeur);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إشعارات الطباعة - نظام إدارة المدرسة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        .notification-container {
            max-width: 800px;
            margin: 30px auto;
        }
        .notification-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .notification-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .notification-title {
            font-weight: 600;
            margin: 0;
        }
        .notification-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .notification-body {
            padding: 20px;
        }
        .notification-message {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .notification-file {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .notification-file i {
            margin-left: 10px;
            color: #3498db;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .reason-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border-right: 3px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="notification-container">
            <h2 class="mb-4">
                <i class="fas fa-bell me-2"></i>
                إشعارات الطباعة
            </h2>
            
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="notification-card">
                        <div class="notification-header">
                            <h5 class="notification-title">
                                <?php if ($row['statut'] === 'accepte'): ?>
                                    <span class="status-badge status-accepted">
                                        <i class="fas fa-check-circle me-1"></i>
                                        مقبول
                                    </span>
                                <?php elseif ($row['statut'] === 'refuse'): ?>
                                    <span class="status-badge status-rejected">
                                        <i class="fas fa-times-circle me-1"></i>
                                        مرفوض
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-clock me-1"></i>
                                        في الانتظار
                                    </span>
                                <?php endif; ?>
                            </h5>
                            <span class="notification-date">
                                <i class="far fa-calendar-alt me-1"></i>
                                <?= date('Y-m-d', strtotime($row['date_demande'])) ?>
                            </span>
                        </div>
                        
                        <div class="notification-body">
                            <div class="notification-message">
                                <?= $row['message_statut'] ?>
                            </div>
                            
                            <div class="notification-file">
                                <i class="fas fa-file-alt"></i>
                                <span><?= htmlspecialchars($row['nom_fichier_original']) ?></span>
                            </div>
                            
                            <div class="notification-file">
                                <i class="fas fa-copy"></i>
                                <span>عدد النسخ: <?= $row['nb_copies'] ?></span>
                            </div>
                            
                            <?php if ($row['statut'] === 'refuse' && !empty($row['raison_refus'])): ?>
                                <div class="reason-box">
                                    <strong>سبب الرفض:</strong>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($row['raison_refus'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    لا توجد إشعارات حالياً
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 