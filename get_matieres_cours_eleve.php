<?php
// Inclure le fichier de configuration de la base de données
include 'db_config.php';

// Vérifier si l'ID de classe est fourni
if (!isset($_GET['classe_id']) || !is_numeric($_GET['classe_id'])) {
    echo json_encode([]);
    exit();
}

$classe_id = intval($_GET['classe_id']);

// Récupérer les matières pour cette classe
$sql = "SELECT * FROM matieres WHERE classe_id = ? ORDER BY nom";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $classe_id);
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
