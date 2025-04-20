<?php
require 'db_base.php';

// Vérifier la structure de la table professeurs
$result = $conn->query("DESCRIBE professeurs");
if ($result) {
    echo "<h2>Structure de la table professeurs</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Erreur lors de la vérification de la structure de la table: " . $conn->error;
}

// Vérifier les contraintes de clé étrangère
$result = $conn->query("
    SELECT 
        TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
    FROM
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
        TABLE_SCHEMA = 'gestion_notes' AND
        TABLE_NAME = 'professeurs' AND
        REFERENCED_TABLE_NAME IS NOT NULL;
");

if ($result) {
    echo "<h2>Contraintes de clé étrangère</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Table</th><th>Colonne</th><th>Contrainte</th><th>Table référencée</th><th>Colonne référencée</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['TABLE_NAME'] . "</td>";
        echo "<td>" . $row['COLUMN_NAME'] . "</td>";
        echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Erreur lors de la vérification des contraintes: " . $conn->error;
}
?> 