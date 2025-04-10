<?php
$servername = "localhost";
$username = "root";  // Changez si besoin
$password = "";      // Changez si besoin
$database = "u504721134_formation"; // Nom de votre base de données

// Création de la connexion
$conn = new mysqli($servername, $username, $password, $database);

// Vérifier la connexion
if ($conn->connect_error) {
    // Utiliser un gestionnaire d'erreurs
    error_log("Échec de la connexion : " . $conn->connect_error);
    die("Échec de la connexion à la base de données. Veuillez réessayer plus tard.");
} else {
    // Définir le jeu de caractères pour éviter les problèmes d'encodage
    $conn->set_charset("utf8mb4");
}

?>
