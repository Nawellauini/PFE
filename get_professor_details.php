<?php
include 'db_config.php';

if (isset($_GET['id_professeur'])) {
    $id_prof = $_GET['id_professeur'];
    
    // Requête SQL uniquement pour la table 'professeurs'
    $sql = "SELECT p.id_professeur, p.nom, p.prenom, p.email, p.login, p.mot_de_passe,
                   m.nom AS matiere, GROUP_CONCAT(c.nom_classe SEPARATOR ', ') AS classes
            FROM professeurs p
            LEFT JOIN matieres m ON p.matiere_id = m.matiere_id
            LEFT JOIN professeurs_classes pc ON p.id_professeur = pc.id_professeur
            LEFT JOIN classes c ON pc.id_classe = c.id_classe
            WHERE p.id_professeur = ?
            GROUP BY p.id_professeur";
    
    $stmt = $conn->prepare($sql);

    // Vérification si la préparation de la requête a échoué
    if ($stmt === false) {
        die('Erreur de préparation de la requête SQL: ' . $conn->error);
    }

    $stmt->bind_param('i', $id_prof);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $prof = $result->fetch_assoc();
        echo json_encode($prof);
    } else {
        echo json_encode(["error" => "Professeur non trouvé"]);
    }
} else {
    echo json_encode(["error" => "ID Professeur non fourni"]);
}
?>
