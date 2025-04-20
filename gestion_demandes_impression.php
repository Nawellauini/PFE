<?php

include 'db_config.php';



// Traitement de la réponse à une demande
if (isset($_POST['repondre_demande'])) {
    $id_demande = $_POST['id_demande'];
    $statut = $_POST['statut'];
    $raison_refus = $_POST['raison_refus'] ?? null;
    
    $query = "UPDATE demandes_impression 
              SET statut = ?, raison_refus = ?, date_reponse = NOW() 
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $statut, $raison_refus, $id_demande);
    
    if ($stmt->execute()) {
        // Récupérer l'email du professeur pour l'envoi de notification
        $query_prof = "SELECT p.email, p.nom, p.prenom 
                      FROM professeurs p 
                      JOIN demandes_impression d ON p.id_professeur = d.id_prof 
                      WHERE d.id = ?";
        $stmt_prof = $conn->prepare($query_prof);
        $stmt_prof->bind_param("i", $id_demande);
        $stmt_prof->execute();
        $result_prof = $stmt_prof->get_result();
        $prof_info = $result_prof->fetch_assoc();
        
        if ($prof_info) {
            $sujet = "رد على طلب الطباعة";
            $message = "مرحباً {$prof_info['prenom']} {$prof_info['nom']}،\n\n";
            if ($statut === 'accepte') {
                $message .= "تم قبول طلب الطباعة الخاص بك.\n";
            } else {
                $message .= "تم رفض طلب الطباعة الخاص بك.\n";
                $message .= "سبب الرفض: " . $raison_refus . "\n";
            }
            
            // Envoyer l'email
            mail($prof_info['email'], $sujet, $message);
        }
        
        header("Location: gestion_demandes_impression.php?success=1");
        exit();
    }
}

// Récupérer toutes les demandes
$query = "SELECT d.*, p.nom, p.prenom 
          FROM demandes_impression d 
          JOIN professeurs p ON d.id_prof = p.id_professeur 
          ORDER BY d.date_demande DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة طلبات الطباعة - نظام إدارة المدرسة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        .table-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin: 20px 0;
        }
        .modal-content {
            border-radius: 10px;
        }
        .btn-success {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }
        .btn-danger {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">
            <i class="fas fa-print me-2"></i>
            إدارة طلبات الطباعة
        </h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">تم تحديث حالة الطلب بنجاح</div>
        <?php endif; ?>
        
        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>الأستاذ</th>
                        <th>الملف</th>
                        <th>عدد النسخ</th>
                        <th>تاريخ الطلب</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?></td>
                            <td>
                                <a href="uploads/impressions/<?= htmlspecialchars($row['nom_fichier']) ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download"></i>
                                    <?= htmlspecialchars($row['nom_fichier_original']) ?>
                                </a>
                            </td>
                            <td><?= $row['nb_copies'] ?></td>
                            <td><?= $row['date_demande'] ?></td>
                            <td>
                                <?php if ($row['statut'] === 'en_attente'): ?>
                                    <span class="badge bg-warning">في الانتظار</span>
                                <?php elseif ($row['statut'] === 'accepte'): ?>
                                    <span class="badge bg-success">مقبول</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">مرفوض</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['statut'] === 'en_attente'): ?>
                                    <button type="button" class="btn btn-success btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#reponseModal<?= $row['id'] ?>">
                                        <i class="fas fa-reply"></i>
                                        الرد
                                    </button>
                                    
                                    <!-- Modal de réponse -->
                                    <div class="modal fade" id="reponseModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">الرد على طلب الطباعة</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_demande" value="<?= $row['id'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">القرار</label>
                                                            <select name="statut" class="form-select" required>
                                                                <option value="accepte">قبول</option>
                                                                <option value="refuse">رفض</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3" id="raisonRefus<?= $row['id'] ?>" style="display: none;">
                                                            <label class="form-label">سبب الرفض</label>
                                                            <textarea name="raison_refus" class="form-control" rows="3"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" name="repondre_demande" class="btn btn-primary">إرسال</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Afficher/masquer le champ de raison de refus selon le choix
        document.querySelectorAll('select[name="statut"]').forEach(select => {
            select.addEventListener('change', function() {
                const modalId = this.closest('.modal').id;
                const raisonRefusDiv = document.getElementById('raisonRefus' + modalId.replace('reponseModal', ''));
                raisonRefusDiv.style.display = this.value === 'refuse' ? 'block' : 'none';
            });
        });
    </script>
</body>
</html> 