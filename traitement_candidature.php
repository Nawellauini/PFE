<?php
include('db_config.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

if (isset($_GET['action'], $_GET['id_candidature']) || isset($_POST['action'], $_POST['id_candidature'])) {
    $id_candidature = isset($_GET['id_candidature']) ? $_GET['id_candidature'] : $_POST['id_candidature'];
    $action = isset($_GET['action']) ? $_GET['action'] : $_POST['action'];

    // Récupérer les infos de la candidature
    $sql = "SELECT * FROM candidatures_professeurs WHERE id = '$id_candidature'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $candidature = $result->fetch_assoc();
        $nom = $candidature['nom'];
        $prenom = $candidature['prenom'];
        $email = $candidature['email'];
        $matiere = $candidature['matiere'];
    } else {
        die("Candidature introuvable !");
    }

    // Définir le statut en fonction de l'action
    $statut = ($action == 'accepter') ? 'مقبول' : 'مرفوض';
    $update_sql = "UPDATE candidatures_professeurs SET statut = '$statut' WHERE id = '$id_candidature'";
    
    if ($conn->query($update_sql)) {
        // Configurer l'email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Serveur SMTP
            $mail->SMTPAuth = true;
            $mail->Username = 'nawellaouini210@gmail.com'; // Remplace par ton email
            $mail->Password = 'lddg ridp kmxw alfn'; // Remplace par ton mot de passe
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Destinataire
            $mail->setFrom('votre_email@gmail.com', 'مدرستنا - إدارة المدرسة');
            $mail->addAddress($email, $prenom . ' ' . $nom);

            // Contenu du mail
            if ($action == 'accepter') {
                $mail->Subject = 'قبول طلب الانضمام كمدرس';
                $mail->Body = "مرحباً {$prenom} {$nom}،

نحن سعداء بإبلاغك بأن طلبك للانضمام إلى فريق التدريس في مدرستنا لمادة '{$matiere}' قد تم قبوله!

سنتواصل معك قريباً لمناقشة التفاصيل والخطوات التالية.

نتطلع للعمل معك قريباً.

مع أطيب التحيات،
إدارة مدرستنا";
            } else {
                $mail->Subject = 'بخصوص طلب الانضمام كمدرس';
                $mail->Body = "مرحباً {$prenom} {$nom}،

نشكرك على اهتمامك بالانضمام إلى فريق التدريس في مدرستنا.

بعد مراجعة طلبك للتدريس في مادة '{$matiere}'، نأسف لإبلاغك أننا لن نتمكن من المضي قدماً في طلبك في الوقت الحالي.

نقدر اهتمامك ونتمنى لك التوفيق في مساعيك المستقبلية.

مع أطيب التحيات،
إدارة مدرستنا";
            }

            // Envoi de l'email
            $mail->send();
            header("Location: reponse_email_admin.php?message=تم تحديث الحالة وإرسال البريد الإلكتروني بنجاح");
            exit();
        } catch (Exception $e) {
            header("Location: reponse_email_admin.php?message=تم تحديث الحالة ولكن فشل إرسال البريد الإلكتروني: " . $mail->ErrorInfo);
            exit();
        }
    } else {
        header("Location: reponse_email_admin.php?message=فشل تحديث الحالة: " . $conn->error);
        exit();
    }
} else {
    header("Location: reponse_email_admin.php?message=طلب غير صالح");
    exit();
}
?>