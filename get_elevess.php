<?php
session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est connecté en tant que professeur
if (!isset($_SESSION['id_professeur'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// Récupérer l'ID de la classe depuis la requête GET
$classe_id = isset($_GET['classe_id']) ? intval($_GET['classe_id']) : 0;

if ($classe_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// Vérifier si le professeur enseigne dans cette classe
$query_check = "SELECT * FROM professeurs_classes 
                WHERE id_professeur = ? AND id_classe = ?";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bind_param("ii", $_SESSION['id_professeur'], $classe_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    // Le professeur n'enseigne pas dans cette classe
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// Récupérer les élèves de la classe
$query = "SELECT id_eleve, nom, prenom 
          FROM eleves 
          WHERE id_classe = ? 
          ORDER BY nom, prenom";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $classe_id);
$stmt->execute();
$result = $stmt->get_result();

$eleves = [];
while ($row = $result->fetch_assoc()) {
    $eleves[] = $row;
}

// Renvoyer les données au format JSON
header('Content-Type: application/json');
echo json_encode($eleves);
?>