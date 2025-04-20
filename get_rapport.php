<?php
session_start();
include 'db_config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

$id = intval($_GET['id']);

$query = "SELECT r.id, r.titre, r.commentaires, r.recommandations, r.date_creation,
                 c.nom_classe, c.id_classe, p.nom AS nom_professeur, p.prenom AS prenom_professeur 
          FROM rapports_inspection r
          JOIN classes c ON r.id_classe = c.id_classe
          JOIN professeurs p ON r.id_professeur = p.id_professeur
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Rapport non trouvé']);
    exit;
}

$rapport = $result->fetch_assoc();
echo json_encode($rapport);
?> 