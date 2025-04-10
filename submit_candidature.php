<?php
include('db_config.php'); // Inclure la connexion à la base de données

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $matiere = mysqli_real_escape_string($conn, $_POST['matiere']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    // Insertion dans la base de données
    $sql = "INSERT INTO candidatures_professeurs (nom, email, matiere, message) 
            VALUES ('$nom', '$email', '$matiere', '$message')";

    if ($conn->query($sql) === TRUE) {
        echo "Candidature envoyée avec succès!";
    } else {
        echo "Erreur : " . $sql . "<br>" . $conn->error;
    }
}
?>
