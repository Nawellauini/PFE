<?php
session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action.']);
    exit;
}

// Vérifier si toutes les données nécessaires sont présentes
if (!isset($_POST['classe_id'], $_POST['matiere_id'], $_POST['trimestre'], $_POST['notes'])) {
    echo json_encode(['success' => false, 'message' => 'Données incomplètes.']);
    exit;
}

$classe_id = $_POST['classe_id'];
$matiere_id = $_POST['matiere_id'];
$trimestre = $_POST['trimestre'];
$notes = $_POST['notes'];
$id_professeur = $_SESSION['id_professeur'];

// Vérifier que le professeur a accès à cette classe
$query_access = "SELECT COUNT(*) as count FROM professeurs_classes 
                WHERE id_professeur = ? AND id_classe = ?";
$stmt_access = $conn->prepare($query_access);
$stmt_access->bind_param("ii", $id_professeur, $classe_id);
$stmt_access->execute();
$result_access = $stmt_access->get_result();
$access = $result_access->fetch_assoc()['count'];

if (!$access) {
    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas accès à cette classe.']);
    exit;
}

// Commencer une transaction
$conn->begin_transaction();

try {
    // Insérer ou mettre à jour chaque note
    $count = 0;
    
    foreach ($notes as $id_eleve => $note) {
        if (trim($note) !== '') {
            // Convertir la note en nombre à virgule flottante
            $note_value = floatval(str_replace(',', '.', $note));
            
            // Vérifier que la note est valide
            if ($note_value < 0 || $note_value > 20) {
                continue; // Ignorer les notes invalides
            }
            
            // Vérifier si la note existe déjà
            $check_query = "SELECT id FROM notes WHERE id_eleve = ? AND matiere_id = ? AND trimestre = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("iii", $id_eleve, $matiere_id, $trimestre);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // La note existe, on la met à jour
                $note_id = $check_result->fetch_assoc()['id'];
                $update_query = "UPDATE notes SET note = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("di", $note_value, $note_id);
                $update_stmt->execute();
            } else {
                // La note n'existe pas, on l'insère
                $insert_query = "INSERT INTO notes (id_eleve, matiere_id, trimestre, note) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("iiid", $id_eleve, $matiere_id, $trimestre, $note_value);
                $insert_stmt->execute();
            }
            
            $count++;
        }
    }
    
    // Valider la transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => "تم حفظ $count درجات بنجاح", 'count' => $count]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء حفظ الدرجات: ' . $e->getMessage()]);
}

// Fermer la connexion
$conn->close();
?>