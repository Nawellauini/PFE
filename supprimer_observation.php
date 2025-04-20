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

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['observation_id'])) {
    $observation_id = $_POST['observation_id'];
    
    // Récupérer la classe_id avant de supprimer l'observation
    $query_classe = "SELECT classe_id FROM observations WHERE id = ?";
    $stmt_classe = $conn->prepare($query_classe);
    $classe_id = null;
    
    if ($stmt_classe) {
        $stmt_classe->bind_param("i", $observation_id);
        $stmt_classe->execute();
        $result_classe = $stmt_classe->get_result();
        if ($row = $result_classe->fetch_assoc()) {
            $classe_id = $row['classe_id'];
        }
    }
    
    // Vérifier si la table observations_log existe
    $check_table = $conn->query("SHOW TABLES LIKE 'observations_log'");
    
    if ($check_table->num_rows > 0) {
        // Enregistrer l'action de suppression dans le journal
        $action = "suppression";
        $query_log = "INSERT INTO observations_log (observation_id, id_professeur, action, date_action) 
                    VALUES (?, ?, ?, NOW())";
        $stmt_log = $conn->prepare($query_log);
        
        if ($stmt_log) {
            $stmt_log->bind_param("iis", $observation_id, $id_professeur, $action);
            $stmt_log->execute();
        }
    }
    
    // Supprimer l'observation
    $query_delete = "DELETE FROM observations WHERE id = ?";
    $stmt_delete = $conn->prepare($query_delete);
    
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $observation_id);
        
        if ($stmt_delete->execute()) {
            $success = true;
            
            // Rediriger vers la liste avec un message de succès
            if ($classe_id) {
                header("Location: liste_observations.php?classe_id=" . $classe_id . "&success=delete");
            } else {
                header("Location: liste_observations.php?success=delete");
            }
            exit();
        } else {
            $error = "حدث خطأ أثناء حذف الملاحظة: " . $conn->error;
        }
    } else {
        $error = "حدث خطأ في إعداد الاستعلام: " . $conn->error;
    }
    
    // En cas d'erreur, rediriger avec un message d'erreur
    if ($error) {
        if ($classe_id) {
            header("Location: liste_observations.php?classe_id=" . $classe_id . "&error=" . urlencode($error));
        } else {
            header("Location: liste_observations.php?error=" . urlencode($error));
        }
        exit();
    }
} else {
    // Rediriger si aucun ID n'est fourni
    header("Location: liste_observations.php?error=" . urlencode("معرف الملاحظة غير محدد"));
    exit();
}
?>
