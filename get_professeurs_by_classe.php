<?php
include 'db_config.php';

if (isset($_GET['id_classe'])) {
    $id_classe = $_GET['id_classe'];

    $query = "SELECT p.id_professeur, p.nom, p.prenom 
              FROM professeurs p 
              JOIN professeurs_classes pc ON p.id_professeur = pc.id_professeur 
              WHERE pc.id_classe = ? 
              ORDER BY p.nom, p.prenom";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_classe);
    $stmt->execute();
    $result = $stmt->get_result();

    $professeurs = [];

    while ($row = $result->fetch_assoc()) {
        $professeurs[] = [
            'id_professeur' => $row['id_professeur'],
            'nom' => $row['nom'],
            'prenom' => $row['prenom']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($professeurs);
}
?>
