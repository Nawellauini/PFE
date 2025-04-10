<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté en tant que professeur
if (!isset($_SESSION['id_professeur'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit();
}

// Inclure le fichier de configuration de la base de données
include 'db_config.php';

// Récupérer l'ID du professeur connecté
$id_professeur = $_SESSION['id_professeur'];

// Vérifier si l'ID du cours est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Rediriger vers la liste des cours
    header("Location: liste_cours.php?error=معرف الدرس غير صالح");
    exit();
}

$id_cours = intval($_GET['id']);

// Vérifier si le cours appartient au professeur connecté
$sql_check = "SELECT * FROM cours WHERE id_cours = $id_cours AND id_professeur = $id_professeur";
$result_check = $conn->query($sql_check);

if ($result_check->num_rows == 0) {
    // Le cours n'existe pas ou n'appartient pas au professeur connecté
    header("Location: liste_cours.php?error=لا يمكنك حذف هذا الدرس");
    exit();
}

// Récupérer les informations du cours pour supprimer les fichiers associés
$cours = $result_check->fetch_assoc();

// Supprimer le cours de la base de données
$sql_delete = "DELETE FROM cours WHERE id_cours = $id_cours AND id_professeur = $id_professeur";

if ($conn->query($sql_delete) === TRUE) {
    // Supprimer le fichier du cours s'il existe
    if (!empty($cours['fichier']) && file_exists($cours['fichier'])) {
        unlink($cours['fichier']);
    }
    
    // Supprimer l'illustration du cours si elle existe
    if (!empty($cours['illustration']) && file_exists($cours['illustration'])) {
        unlink($cours['illustration']);
    }
    
    // Rediriger vers la liste des cours avec un message de succès
    header("Location: liste_cours.php?message=تم حذف الدرس بنجاح");
} else {
    // Erreur lors de la suppression
    header("Location: liste_cours.php?error=خطأ في حذف الدرس: " . $conn->error);
}

// Fermer la connexion
$conn->close();
?>