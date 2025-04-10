<?php
$servername = "localhost";
$username = "root"; // Change si besoin
$password = ""; // Change si besoin
$database = "u504721134_formation"; // Nom de ta base de données

try {
    // Création d'une nouvelle instance PDO pour la connexion
    $pdo = new PDO("mysql:host=$servername;dbname=$database;charset=utf8", $username, $password);
    // Configuration des options PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si la connexion échoue, afficher l'erreur
    die("❌ Erreur de connexion à la base de données: " . $e->getMessage());
}
?>
