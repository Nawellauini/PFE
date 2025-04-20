<?php
// Paramètres de connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "u504721134_formation";

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fonction pour vérifier si une colonne existe dans une table
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result->num_rows > 0;
}

// Vérifier et ajouter les colonnes nécessaires à la table candidatures_professeurs
$alterTableQueries = [];

if (!columnExists($conn, 'candidatures_professeurs', 'login')) {
    $alterTableQueries[] = "ALTER TABLE `candidatures_professeurs` ADD COLUMN `login` VARCHAR(50) NULL AFTER `statut`";
}

if (!columnExists($conn, 'candidatures_professeurs', 'mot_de_passe')) {
    $alterTableQueries[] = "ALTER TABLE `candidatures_professeurs` ADD COLUMN `mot_de_passe` VARCHAR(255) NULL AFTER `login`";
}

if (!columnExists($conn, 'candidatures_professeurs', 'role')) {
    $alterTableQueries[] = "ALTER TABLE `candidatures_professeurs` ADD COLUMN `role` VARCHAR(50) DEFAULT 'مدرس' AFTER `mot_de_passe`";
}

// Exécuter les requêtes d'altération si nécessaire
foreach ($alterTableQueries as $query) {
    $conn->query($query);
}

// Vérifier si la colonne role existe dans la table professeurs
if (!columnExists($conn, 'professeurs', 'role')) {
    $conn->query("ALTER TABLE `professeurs` ADD COLUMN `role` VARCHAR(50) DEFAULT 'مدرس' AFTER `mot_de_passe`");
}
?>
