<?php

include 'db_config.php';
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_GET['id']) && isset($_GET['statut'])) {
    $id = intval($_GET['id']);
    $statut = $_GET['statut'];

    // Récupérer les informations de l'élève
    $result = $conn->query("SELECT * FROM demandes_inscription WHERE id=$id");
    if (!$result || $result->num_rows == 0) {
        echo "<script>alert('Demande introuvable'); window.location.href='gestion_inscriptions.php';</script>";
        exit;
    }
    
    $row = $result->fetch_assoc();
    $email = $row['email'];
    $nom = $row['nom'];
    $prenom = $row['prenom'];
    $classe_demande = $row['classe_demande'];
    $login = $row['login'];
    $mot_de_passe = $row['mot_de_passe'];

    // Si le login est vide, le générer
    if (empty($login)) {
        $login = strtolower(substr($prenom, 0, 1) . $nom);
        $login = preg_replace('/[^a-z0-9]/', '', $login);
        $login .= rand(100, 999);
        
        // Mettre à jour le login dans la table demandes_inscription
        $conn->query("UPDATE demandes_inscription SET login='$login' WHERE id=$id");
    }

    // Mettre à jour le statut
    $update_query = "UPDATE demandes_inscription SET statut='$statut' WHERE id=$id";
    $update_result = $conn->query($update_query);
    
    if (!$update_result) {
        echo "<script>alert('Erreur lors de la mise à jour du statut: " . $conn->error . "'); window.location.href='gestion_inscriptions.php';</script>";
        exit;
    }

    // Si la demande est acceptée, créer un nouvel élève
    if ($statut == 'Accepté') {
        // Vérifier si l'élève existe déjà
        $check_eleve = $conn->query("SELECT COUNT(*) as count FROM eleves WHERE email='$email'");
        $eleve_exists = $check_eleve->fetch_assoc()['count'] > 0;
        
        if (!$eleve_exists) {
            // Insérer le nouvel élève
            $insert_eleve = "INSERT INTO eleves (nom, prenom, email, id_classe, login, mp) 
                            VALUES ('$nom', '$prenom', '$email', '$classe_demande', '$login', '$mot_de_passe')";
            
            $insert_result = $conn->query($insert_eleve);
            
            if (!$insert_result) {
                echo "<script>alert('Erreur lors de la création de l\\'élève: " . $conn->error . "'); window.location.href='gestion_inscriptions.php';</script>";
                exit;
            }
        }
    }

    // Configuration de l'email
    $mail = new PHPMailer(true);
    try {
        // Configurer SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Remplace par ton serveur SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'nawellaouini210@gmail.com'; // Remplace par ton email
        $mail->Password = 'lddg ridp kmxw alfn'; // Remplace par ton mot de passe
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8'; // Important pour les caractères arabes

        // Expéditeur & Destinataire
        $mail->setFrom('ecole12@gmail.com', 'مدرستنا');
        $mail->addAddress($email, "$nom $prenom");

        // Sujet & Message
        if ($statut == 'Accepté') {
            $mail->Subject = "تم قبول طلب التسجيل الخاص بك!";
            $mail->Body = "مرحبًا $prenom $nom،\n\n
            يسعدنا إبلاغك بأنه تم قبول طلب التسجيل الخاص بك في مدرستنا! 🎉\n\n
            يمكنك الآن تسجيل الدخول إلى نظام المدرسة باستخدام:\n
            اسم المستخدم: $login\n
            كلمة المرور: $mot_de_passe\n\n
            يرجى الاتصال بنا للخطوات التالية.\n\n
            مع أطيب التحيات،\n
            فريق مدرستنا";
        } else {
            $mail->Subject = "تم رفض طلب التسجيل الخاص بك.";
            $mail->Body = "مرحبًا $prenom $nom،\n\n
            نأسف لإبلاغك بأنه تم رفض طلب التسجيل الخاص بك في مدرستنا.\n\n
            لمزيد من المعلومات، يرجى الاتصال بنا.\n\n
            مع أطيب التحيات،\n
            فريق مدرستنا";
        }

        // Envoyer l'email
        $mail->send();
        header("Location: gestion_inscriptions.php?success=1");
        exit;
    } catch (Exception $e) {
        header("Location: gestion_inscriptions.php?error=1");
        exit;
    }
} else {
    header("Location: gestion_inscriptions.php");
    exit;
}
?>
