<?php
include 'db_config.php';
session_start();

if (isset($_POST['classe_id'])) {
    $classe_id = intval($_POST['classe_id']);

    $query = "SELECT id_eleve, nom, prenom FROM eleves WHERE id_classe = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $eleves = [];
    while ($row = $result->fetch_assoc()) {
        $eleves[] = $row;
    }

    echo json_encode($eleves);
}
?>
