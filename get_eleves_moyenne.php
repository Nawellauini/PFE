<?php
include 'db_config.php';

if (isset($_POST['classe_id'], $_POST['domaine_id'])) {
    $classe_id = $_POST['classe_id'];
    $domaine_id = $_POST['domaine_id'];

    $query = "SELECT e.id, e.nom, 
                     (SELECT AVG(n.note) FROM notes n WHERE n.eleve_id = e.id AND n.matiere_id = ?) AS moyenne 
              FROM eleves e 
              WHERE e.classe_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $domaine_id, $classe_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['nom']}</td>
                <td>" . number_format($row['moyenne'], 2) . "</td>
                <td><input type='text' class='remarque' data-eleve-id='{$row['id']}' placeholder='Ajouter une remarque'></td>
              </tr>";
    }
}
?>
