<?php
require 'db_base.php';

// Vérifier les matières disponibles
$result = $conn->query("SELECT matiere_id, nom FROM matieres");
if ($result) {
    echo "<h2>Matières disponibles</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nom</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['matiere_id'] . "</td>";
        echo "<td>" . $row['nom'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Erreur lors de la vérification des matières: " . $conn->error;
}

// Vérifier la dernière candidature traitée
$result = $conn->query("SELECT * FROM candidatures_professeurs ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "<h2>Dernière candidature traitée</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Champ</th><th>Valeur</th></tr>";
    foreach ($row as $key => $value) {
        echo "<tr>";
        echo "<td>" . $key . "</td>";
        echo "<td>" . $value . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?> 