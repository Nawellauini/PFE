<?php
// Placer ce code au tout début de afficher_classe.php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit();
}

// Récupérer les paramètres
$classe_id = isset($_GET['classe_id']) ? $_GET['classe_id'] : null;
$eleve_id = isset($_GET['eleve_id']) ? $_GET['eleve_id'] : null;
$trimestre = isset($_GET['trimestre']) ? $_GET['trimestre'] : 1;

// Si les paramètres sont manquants, rediriger vers la page de sélection
if (!$classe_id || !$eleve_id) {
    header("Location: selection_classe.php");
    exit();
}
?>

