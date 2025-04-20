<?php

session_start();
include 'db_config.php';

if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];
$success = false;
$error = false;

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['observation_id'])) {
    $observation_id = $_POST['observation_id'];
    $observation_text = trim($_POST['observation']);
    $classe_id = isset($_POST['classe_id']) ? $_POST['classe_id'] : null;
    
    if (!empty($observation_text)) {
        // Mettre à jour l'observation
        $query_update = "UPDATE observations SET observation = ? WHERE id = ?";
        $stmt_update = $conn->prepare($query_update);
        
        if ($stmt_update) {
            $stmt_update->bind_param("si", $observation_text, $observation_id);
            
            if ($stmt_update->execute()) {
                // Vérifier si la table observations_log existe
                $check_table = $conn->query("SHOW TABLES LIKE 'observations_log'");
                
                if ($check_table->num_rows > 0) {
                    // Enregistrer l'action de modification
                    $action = "modification";
                    $query_log = "INSERT INTO observations_log (observation_id, id_professeur, action, date_action) 
                                VALUES (?, ?, ?, NOW())";
                    $stmt_log = $conn->prepare($query_log);
                    
                    if ($stmt_log) {
                        $stmt_log->bind_param("iis", $observation_id, $id_professeur, $action);
                        $stmt_log->execute();
                    }
                }
                
                // Récupérer la classe_id si elle n'est pas fournie
                if (!$classe_id) {
                    $query_classe = "SELECT classe_id FROM observations WHERE id = ?";
                    $stmt_classe = $conn->prepare($query_classe);
                    if ($stmt_classe) {
                        $stmt_classe->bind_param("i", $observation_id);
                        $stmt_classe->execute();
                        $result_classe = $stmt_classe->get_result();
                        if ($row = $result_classe->fetch_assoc()) {
                            $classe_id = $row['classe_id'];
                        }
                    }
                }
                
                // Rediriger vers la liste avec un message de succès
                if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
                    // Si c'est une requête AJAX, retourner un message de succès
                    echo '<div class="alert alert-success mb-3">تم تحديث الملاحظة بنجاح</div>';
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "liste_observations.php?classe_id=' . $classe_id . '&success=edit";
                        }, 1500);
                    </script>';
                    exit();
                } else {
                    // Sinon, rediriger
                    header("Location: liste_observations.php?classe_id=" . $classe_id . "&success=edit");
                    exit();
                }
            } else {
                $error = "حدث خطأ أثناء تحديث الملاحظة: " . $conn->error;
            }
        } else {
            $error = "حدث خطأ في إعداد الاستعلام: " . $conn->error;
        }
    } else {
        $error = "الرجاء إدخال نص الملاحظة";
    }
}

// Récupération des détails de l'observation pour l'affichage du formulaire
if (isset($_GET['id'])) {
    $observation_id = $_GET['id'];
    
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
    
    // Afficher le formulaire de modification
    if ($success) {
        echo '<div class="alert alert-success mb-3">تم تحديث الملاحظة بنجاح</div>';
        echo '<script>
            setTimeout(function() {
                window.location.href = "liste_observations.php?classe_id=' . $observation['classe_id'] . '&success=edit";
            }, 1500);
        </script>';
    } else {
        if ($error) {
            echo '<div class="alert alert-danger mb-3">' . $error . '</div>';
        }
        ?>
        <form method="post" action="modifier_observation.php">
            <input type="hidden" name="observation_id" value="<?= $observation_id ?>">
            <input type="hidden" name="classe_id" value="<?= $observation['classe_id'] ?>">
            <input type="hidden" name="ajax" value="1">
            
            <div class="mb-3 d-flex align-items-center">
                <div class="student-avatar me-3" style="background-color: <?= $background_color ?>; color: <?= $text_color ?>; width: 50px; height: 50px; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold;">
                    <?= $initials ?>
                </div>
                <div>
                    <h6 class="mb-1"><?= htmlspecialchars($observation['eleve_nom'] . ' ' . $observation['eleve_prenom']) ?></h6>
                    <div class="text-muted small"><?= htmlspecialchars($observation['nom_classe']) ?></div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="observation" class="form-label">
                    <i class="fas fa-comment-alt me-1"></i>
                    نص الملاحظة:
                </label>
                <textarea name="observation" id="observation" class="form-control" rows="5" required><?= htmlspecialchars($observation['observation']) ?></textarea>
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i>
                    حفظ التغييرات
                </button>
            </div>
        </form>
        <?php
    }
} else {
    echo '<div class="alert alert-danger">معرف الملاحظة غير محدد</div>';
}
?>
