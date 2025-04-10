<?php
session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action.']);
    exit;
}

// Vérifier si toutes les données nécessaires sont présentes
if (!isset($_POST['classe_id'], $_POST['matiere_id'], $_POST['trimestre'])) {
    echo json_encode(['success' => false, 'message' => 'Données incomplètes.']);
    exit;
}

$classe_id = $_POST['classe_id'];
$matiere_id = $_POST['matiere_id'];
$trimestre = $_POST['trimestre'];
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

// Récupérer les notes existantes
$query = "SELECT n.id_eleve, n.note 
          FROM notes n 
          JOIN eleves e ON n.id_eleve = e.id_eleve 
          WHERE e.id_classe = ? AND n.matiere_id = ? AND n.trimestre = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $classe_id, $matiere_id, $trimestre);
$stmt->execute();
$result = $stmt->get_result();

$notes = [];
while ($row = $result->fetch_assoc()) {
    $notes[$row['id_eleve']] = $row['note'];
}

echo json_encode(['success' => true, 'notes' => $notes]);

// Fermer la connexion
$conn->close();
?>