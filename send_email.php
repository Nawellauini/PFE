<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

function sendModificationEmail($email, $nom, $prenom, $changes, $newPassword = null) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nawellaouini210@gmail.com';
        $mail->Password = 'lddg ridp kmxw alfn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Destinataire
        $mail->setFrom('nawellaouini210@gmail.com', 'مدرستنا - إدارة المدرسة');
        $mail->addAddress($email, $prenom . ' ' . $nom);

        // Contenu du mail
        $mail->Subject = 'تحديث معلومات حسابك';
        
        // Préparer le contenu de l'email
        $body = "مرحباً {$prenom} {$nom}،
        نود إبلاغك بأنه تم تحديث معلومات حسابك في نظام المدرسة. التغييرات التي تم إجراؤها هي:
        " . implode("<br>", $changes) . "
        <br><br>";
        
        // Ajouter les identifiants si le login ou le mot de passe ont été modifiés
        $hasLoginChange = false;
        $hasPasswordChange = false;
        foreach ($changes as $change) {
            if (strpos($change, 'اسم المستخدم') !== false) {
                $hasLoginChange = true;
                $newLogin = explode('إلى', $change)[1];
            }
            if (strpos($change, 'كلمة المرور') !== false) {
                $hasPasswordChange = true;
            }
        }
        
        if ($hasLoginChange || $hasPasswordChange) {
            $body .= "معلومات تسجيل الدخول الجديدة:<br>";
            if ($hasLoginChange) {
                $body .= "- اسم المستخدم: " . trim($newLogin) . "<br>";
            }
            if ($hasPasswordChange && $newPassword) {
                $body .= "- كلمة المرور: " . $newPassword . "<br>";
            }
            $body .= "<br>";
        }
        
        $body .= "إذا كان لديك أي استفسار، فلا تتردد في التواصل معنا.
        <br><br>
        مع أطيب التحيات،
        <br>
        إدارة مدرستنا";

        $mail->Body = $body;
        $mail->isHTML(true);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email: " . $mail->ErrorInfo);
        return false;
    }
}
?> 