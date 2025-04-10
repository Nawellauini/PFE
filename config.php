<?php
// config.php
$servername = "localhost";  // Nom du serveur
$username = "root";         // Nom d'utilisateur de la base de données
$password = "";             // Mot de passe (vide pour XAMPP par défaut)
$dbname = "u504721134_formation"; // Nom de la base de données

// Créer une connexion à la base de données
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}
?>
