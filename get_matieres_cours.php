<?php
// Inclure le fichier de configuration de la base de données
include 'db_config.php';

// Vérifier si l'ID de classe est fourni
if (!isset($_GET['classe_id']) || empty($_GET['classe_id'])) {
    // Retourner toutes les matières si aucune classe n'est sélectionnée
    $sql = "SELECT * FROM matieres ORDER BY nom";
} else {
    // Récupérer l'ID de la classe
    $classe_id = intval($_GET['classe_id']);
    
    // Récupérer les matières pour cette classe
    $sql = "SELECT * FROM matieres WHERE classe_id = $classe_id ORDER BY nom";
}

$result = $conn->query($sql);
$matieres = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $matieres[] = $row;
    }
}

// Retourner les matières au format JSON
header('Content-Type: application/json');
echo json_encode($matieres);

// Fermer la connexion
$conn->close();
?>