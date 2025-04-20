<?php

session_start();
include 'db_config.php';

if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">معرف الملاحظة غير محدد</div>';
    exit();
}

$observation_id = $_GET['id'];
$id_professeur = $_SESSION['id_professeur'];

// Récupérer les détails de l'observation
$query = "SELECT o.id, o.eleve_id, o.classe_id, o.observation, o.date_observation, 
                 e.nom as eleve_nom, e.prenom as eleve_prenom, 
                 c.nom_classe
          FROM observations o
          JOIN eleves e ON o.eleve_id = e.id_eleve
          JOIN classes c ON o.classe_id = c.id_classe
          WHERE o.id = ?";

$stmt = $conn->prepare($query);

if (!$stmt) {
    echo '<div class="alert alert-danger">خطأ في إعداد الاستعلام: ' . $conn->error . '</div>';
    exit();
}

$stmt->bind_param("i", $observation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">الملاحظة غير موجودة</div>';
    exit();
}

$observation = $result->fetch_assoc();

// Vérifier si la table observations_log existe
$check_table = $conn->query("SHOW TABLES LIKE 'observations_log'");

if ($check_table->num_rows > 0) {
    // Enregistrer l'action de consultation dans la base de données
    $action = "consultation";
    $query_log = "INSERT INTO observations_log (observation_id, id_professeur, action, date_action) 
                VALUES (?, ?, ?, NOW())";
    $stmt_log = $conn->prepare($query_log);
    
    if ($stmt_log) {
        $stmt_log->bind_param("iis", $observation_id, $id_professeur, $action);
        $stmt_log->execute();
    }
}

// Générer les initiales de l'élève
$initials = substr($observation['eleve_prenom'], 0, 1) . substr($observation['eleve_nom'], 0, 1);

// Générer une couleur de fond basée sur les initiales
$hash = 0;
foreach (str_split($initials) as $char) {
    $hash = ord($char) + (($hash << 5) - $hash);
}
$hue = $hash % 360;
$background_color = "hsl($hue, 70%, 80%)";
$text_color = "hsl($hue, 70%, 30%)";
?>

<div class="observation-details">
    <div class="mb-4 d-flex align-items-center">
        <div class="student-avatar me-3" style="background-color: <?= $background_color ?>; color: <?= $text_color ?>; width: 60px; height: 60px; font-size: 1.5rem;">
            <?= $initials ?>
        </div>
        <div>
            <h5 class="mb-1"><?= htmlspecialchars($observation['eleve_nom'] . ' ' . $observation['eleve_prenom']) ?></h5>
            <div class="text-muted"><?= htmlspecialchars($observation['nom_classe']) ?></div>
        </div>
    </div>
    
    <div class="mb-4">
        <div class="fw-bold mb-2">
            <i class="fas fa-calendar-alt me-2"></i>
            تاريخ الملاحظة:
        </div>
        <div class="date-display">
            <span class="date-primary"><?= date('Y-m-d', strtotime($observation['date_observation'])) ?></span>
            <span class="date-secondary"><?= date('H:i', strtotime($observation['date_observation'])) ?></span>
        </div>
    </div>
    
    <div>
        <div class="fw-bold mb-2">
            <i class="fas fa-comment-alt me-2"></i>
            نص الملاحظة:
        </div>
        <div class="observation-content p-3 bg-light rounded">
            <?= nl2br(htmlspecialchars($observation['observation'])) ?>
        </div>
    </div>
    
    <?php
    // Vérifier si la table observations_log existe
    if ($check_table->num_rows > 0) {
        // Récupérer l'historique des actions sur cette observation
        $query_history = "SELECT ol.action, ol.date_action, p.nom as prof_nom, p.prenom as prof_prenom
                        FROM observations_log ol
                        JOIN professeurs p ON ol.id_professeur = p.id_professeur
                        WHERE ol.observation_id = ?
                        ORDER BY ol.date_action DESC
                        LIMIT 5";
        $stmt_history = $conn->prepare($query_history);
        
        if ($stmt_history) {
            $stmt_history->bind_param("i", $observation_id);
            $stmt_history->execute();
            $result_history = $stmt_history->get_result();
            
            if ($result_history->num_rows > 0):
            ?>
            <div class="mt-4">
                <div class="fw-bold mb-2">
                    <i class="fas fa-history me-2"></i>
                    سجل الإجراءات:
                </div>
                <ul class="list-group">
                    <?php while ($row = $result_history->fetch_assoc()): 
                        $action_text = '';
                        switch ($row['action']) {
                            case 'ajout':
                                $action_text = 'إضافة الملاحظة';
                                $icon_class = 'fas fa-plus text-success';
                                break;
                            case 'modification':
                                $action_text = 'تعديل الملاحظة';
                                $icon_class = 'fas fa-edit text-warning';
                                break;
                            case 'consultation':
                                $action_text = 'عرض الملاحظة';
                                $icon_class = 'fas fa-eye text-primary';
                                break;
                            case 'suppression':
                                $action_text = 'حذف الملاحظة';
                                $icon_class = 'fas fa-trash-alt text-danger';
                                break;
                            default:
                                $action_text = $row['action'];
                                $icon_class = 'fas fa-info-circle';
                        }
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="<?= $icon_class ?> me-2"></i>
                                <?= $action_text ?> بواسطة <?= htmlspecialchars($row['prof_prenom'] . ' ' . $row['prof_nom']) ?>
                            </div>
                            <div class="text-muted small">
                                <?= date('Y-m-d H:i', strtotime($row['date_action'])) ?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <?php endif;
        }
    }
    ?>
</div>

<style>
    .observation-content {
        white-space: pre-line;
        line-height: 1.8;
    }
    
    .student-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: bold;
    }
    
    .date-display {
        display: flex;
        flex-direction: column;
    }
    
    .date-primary {
        font-weight: bold;
    }
    
    .date-secondary {
        font-size: 0.85rem;
        color: #6c757d;
    }
</style>
