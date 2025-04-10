<?php
include 'db_config.php';

if (isset($_POST['classe_id'])) {
    $classe_id = $_POST['classe_id'];

    $query = "SELECT id, nom FROM matieres WHERE classe_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<option value=''>-- Choisir un domaine --</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['id']}'>{$row['nom']}</option>";
    }
}
?>
