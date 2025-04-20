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
    $mot_de_passe = htmlspecialchars($_POST['mot_de_passe']);
    $statut = 'قيد الانتظار'; // Initialiser le statut à "En attente" en arabe
    
    // Générer un login automatiquement
    $login = strtolower(substr($prenom, 0, 1) . $nom);
    // Supprimer les caractères spéciaux et les espaces
    $login = preg_replace('/[^a-z0-9]/', '', $login);
    
    // Vérifier si le login existe déjà
    $check_login = $conn->prepare("SELECT COUNT(*) as count FROM demandes_inscription WHERE login = ?");
    $check_login->bind_param("s", $login);
    $check_login->execute();
    $result = $check_login->get_result();
    $row = $result->fetch_assoc();
    
    // Si le login existe, ajouter un nombre aléatoire
    if ($row['count'] > 0) {
        $login = $login . rand(100, 999);
    }

    // Vérifier si les colonnes login et mot_de_passe existent
    $check_columns = $conn->query("SHOW COLUMNS FROM demandes_inscription LIKE 'login'");
    if ($check_columns->num_rows == 0) {
        $conn->query("ALTER TABLE demandes_inscription ADD COLUMN login VARCHAR(50) NULL");
    }

    $check_columns = $conn->query("SHOW COLUMNS FROM demandes_inscription LIKE 'mot_de_passe'");
    if ($check_columns->num_rows == 0) {
        $conn->query("ALTER TABLE demandes_inscription ADD COLUMN mot_de_passe VARCHAR(255) NULL");
    }

    $sql = "INSERT INTO demandes_inscription (nom, prenom, age, classe_demande, email, telephone, message, statut, login, mot_de_passe) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisssssss", $nom, $prenom, $age, $classe_demande, $email, $telephone, $message, $statut, $login, $mot_de_passe);
    
    if ($stmt->execute()) {
        echo "<script>alert('Demande envoyée avec succès ! Votre nom d\\'utilisateur est: " . $login . "'); window.location.href='inscription.php';</script>";
    } else {
        echo "<script>alert('Erreur lors de l\\'envoi: " . $stmt->error . "'); window.history.back();</script>";
    }
    
    $stmt->close();
}
?>
