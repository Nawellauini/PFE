<?php
include 'db_config.php';

// Vérifier si la colonne trimestre existe déjà dans la table remarques
$result = $conn->query("SHOW COLUMNS FROM remarques LIKE 'trimestre'");
$exists = $result->num_rows > 0;

if (!$exists) {
    // Ajouter la colonne trimestre
    $sql = "ALTER TABLE remarques ADD COLUMN trimestre INT DEFAULT 1 AFTER remarque";
    
    if ($conn->query($sql) === TRUE) {
        echo "La colonne 'trimestre' a été ajoutée avec succès à la table 'remarques'.";
    } else {
        echo "Erreur lors de l'ajout de la colonne: " . $conn->error;
    }
} else {
    echo "La colonne 'trimestre' existe déjà dans la table 'remarques'.";
}

// Mettre à jour la clé primaire pour inclure le trimestre
$result = $conn->query("SHOW INDEX FROM remarques WHERE Key_name = 'PRIMARY'");
$primary_key_columns = [];

while ($row = $result->fetch_assoc()) {
    $primary_key_columns[] = $row['Column_name'];
}

// Vérifier si trimestre fait partie de la clé primaire
if (!in_array('trimestre', $primary_key_columns)) {
    // Supprimer l'ancienne clé primaire et en créer une nouvelle
    $sql = "ALTER TABLE remarques DROP PRIMARY KEY, ADD PRIMARY KEY(eleve_id, domaine_id, trimestre)";
    
    if ($conn->query($sql) === TRUE) {
        echo "<br>La clé primaire a été mise à jour pour inclure le trimestre.";
    } else {
        echo "<br>Erreur lors de la mise à jour de la clé primaire: " . $conn->error;
    }
} else {
    echo "<br>Le trimestre fait déjà partie de la clé primaire.";
}

$conn->close();
?>

