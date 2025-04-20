<?php

include 'db_config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notes'], $_POST['classe_id'], $_POST['matiere_id'], $_POST['trimestre'])) {
    $classe_id = $_POST['classe_id'];
    $matiere_id = $_POST['matiere_id'];
    $trimestre = $_POST['trimestre'];
    $notes = $_POST['notes'];

    foreach ($notes as $id_eleve => $note) {
        if ($note === "" || $note === null) {
            $note = "NULL";
        }

        // Vérifier si la note existe déjà
        $check_query = "SELECT id FROM notes WHERE id_eleve = ? AND matiere_id = ? AND trimestre = ?";
        $stmt_check = $conn->prepare($check_query);
        $stmt_check->bind_param("iii", $id_eleve, $matiere_id, $trimestre);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $update_query = "UPDATE notes SET note = ? WHERE id_eleve = ? AND matiere_id = ? AND trimestre = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("diii", $note, $id_eleve, $matiere_id, $trimestre);
            $stmt_update->execute();
        } else {
            $insert_query = "INSERT INTO notes (id_eleve, matiere_id, note, trimestre) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_query);
            $stmt_insert->bind_param("iidi", $id_eleve, $matiere_id, $note, $trimestre);
            $stmt_insert->execute();
        }
    }

    echo "OK";
} else {
    echo "Erreur : Données manquantes.";
}
?>
