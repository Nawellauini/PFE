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
    $result = $conn->query("SELECT email, nom, prenom FROM demandes_inscription WHERE id=$id");
    $row = $result->fetch_assoc();
    $email = $row['email'];
    $nom = $row['nom'];
    $prenom = $row['prenom'];

    // Mettre à jour le statut
    $conn->query("UPDATE demandes_inscription SET statut='$statut' WHERE id=$id");

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

        // Expéditeur & Destinataire
        $mail->setFrom('ecole12@gmail.com', 'مدرستنا');
        $mail->addAddress($email, "$nom $prenom");

        // Sujet & Message
        if ($statut == 'Accepté') {
            $mail->Subject = "تم قبول طلب التسجيل الخاص بك!";
            $mail->Body = "مرحبًا $prenom $nom،\n\nيسعدنا إبلاغك بأنه تم قبول طلب التسجيل الخاص بك في مدرستنا! 🎉\n\nيرجى الاتصال بنا للخطوات التالية.\n\nمع أطيب التحيات،\nفريق مدرستنا";
        } else {
            $mail->Subject = "تم رفض طلب التسجيل الخاص بك.";
            $mail->Body = "مرحبًا $prenom $nom،\n\nنأسف لإبلاغك بأنه تم رفض طلب التسجيل الخاص بك في مدرستنا.\n\nلمزيد من المعلومات، يرجى الاتصال بنا.\n\nمع أطيب التحيات،\nفريق مدرستنا";
        }

        // Envoyer l'email
        $mail->send();
        echo "<script>alert('تم تحديث الحالة وإرسال البريد الإلكتروني.'); window.location.href='gestion_inscriptions.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('تم تحديث الحالة، ولكن تعذر إرسال البريد الإلكتروني.'); window.location.href='gestion_inscriptions.php';</script>";
    }
}
?>