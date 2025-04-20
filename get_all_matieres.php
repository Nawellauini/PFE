<?php



session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$id_professeur = $_SESSION['id_professeur'];

// Récupérer toutes les matières enseignées par le professeur
$query = "SELECT DISTINCT m.matiere_id, m.nom 
          FROM matieres m 
          JOIN professeurs_classes pc ON m.classe_id = pc.id_classe
          WHERE pc.id_professeur = ?
          ORDER BY m.nom";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_professeur);
$stmt->execute();
$result = $stmt->get_result();

$matieres = [];
while ($row = $result->fetch_assoc()) {
    $matieres[] = $row;
}

// Renvoyer les matières au format JSON
header('Content-Type: application/json');
echo json_encode($matieres);
?>
