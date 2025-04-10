<?php
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $age = intval($_POST['age']);
    $classe_demande = htmlspecialchars($_POST['classe_demande']);
    $email = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $message = htmlspecialchars($_POST['message']);
    $statut = 'قيد الانتظار'; // Initialiser le statut à "En attente" en arabe

    $sql = "INSERT INTO demandes_inscription (nom, prenom, age, classe_demande, email, telephone, message, statut) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisssss", $nom, $prenom, $age, $classe_demande, $email, $telephone, $message, $statut);
    
    if ($stmt->execute()) {
        echo "<script>alert('Demande envoyée avec succès !'); window.location.href='inscription.php';</script>";
    } else {
        echo "<script>alert('Erreur lors de l\'envoi: " . $stmt->error . "'); window.history.back();</script>";
    }
    
    $stmt->close();
}
?>

