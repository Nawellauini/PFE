<?php
include('db_base.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

// Fonction de journalisation pour le débogage
function logTraitement($message) {
    $logFile = 'debug_traitement.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logTraitement("Début du traitement de candidature");

if (isset($_GET['action'], $_GET['id_candidature']) || isset($_POST['action'], $_POST['id_candidature'])) {
    $id_candidature = isset($_GET['id_candidature']) ? $_GET['id_candidature'] : $_POST['id_candidature'];
    $action = isset($_GET['action']) ? $_GET['action'] : $_POST['action'];

    logTraitement("Action: $action, ID: $id_candidature");

    // Récupérer les infos de la candidature
    $sql = "SELECT * FROM candidatures_professeurs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_candidature);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $candidature = $result->fetch_assoc();
        $nom = $candidature['nom'];
        $prenom = $candidature['prenom'];
        $email = $candidature['email'];
        $matiere = $candidature['matiere'];
        $login = $candidature['login'];
        $mot_de_passe = $candidature['mot_de_passe'];
        $role = $candidature['role'] ?? 'مدرس';
        
        logTraitement("Candidature trouvée: $prenom $nom, Email: $email, Matière: $matiere, Login: $login");
        
        // Vérifier si le mot de passe est vide
        if (empty($mot_de_passe)) {
            $mot_de_passe = bin2hex(random_bytes(4)); // 8 caractères
            
            // Mettre à jour le mot de passe
            $update_pwd = $conn->prepare("UPDATE candidatures_professeurs SET mot_de_passe = ? WHERE id = ?");
            $update_pwd->bind_param("si", $mot_de_passe, $id_candidature);
            $update_pwd->execute();
            
            logTraitement("Mot de passe généré et mis à jour: $mot_de_passe");
        }
    } else {
        logTraitement("Candidature introuvable pour ID: $id_candidature");
        die("Candidature introuvable !");
    }

    // Définir le statut en fonction de l'action
    $statut = ($action == 'accepter') ? 'مقبول' : 'مرفوض';
    
    // Utiliser une requête préparée pour éviter les problèmes d'encodage
    $stmt = $conn->prepare("UPDATE candidatures_professeurs SET statut = ? WHERE id = ?");
    $stmt->bind_param("si", $statut, $id_candidature);
    
    if ($stmt->execute()) {
        logTraitement("Statut mis à jour avec succès: $statut");
        
        // Si le statut est "accepté", créer un nouvel enregistrement dans la table professeurs
        if ($action == 'accepter') {
            logTraitement("Début du traitement d'acceptation pour la candidature ID: $id_candidature");
            
            // Vérifier si le professeur existe déjà avec cet email
            $check_prof = $conn->prepare("SELECT COUNT(*) as count FROM professeurs WHERE email = ?");
            $check_prof->bind_param("s", $email);
            $check_prof->execute();
            $prof_result = $check_prof->get_result();
            $prof_row = $prof_result->fetch_assoc();
            
            if ($prof_row['count'] == 0) {
                // Vérifier si login est défini
                if (empty($login)) {
                    $login = strtolower(substr($prenom, 0, 1) . $nom);
                    $login = preg_replace('/[^a-z0-9]/', '', $login);
                    
                    // Mettre à jour le login dans la table candidatures_professeurs
                    $update_login = $conn->prepare("UPDATE candidatures_professeurs SET login = ? WHERE id = ?");
                    $update_login->bind_param("si", $login, $id_candidature);
                    $update_login->execute();
                    logTraitement("Login généré et mis à jour: $login");
                }
                
                // Insérer le nouveau professeur
                $insert_prof = $conn->prepare("INSERT INTO professeurs (nom, prenom, email, matiere_id, login, mot_de_passe, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_prof->bind_param("sssssss", $nom, $prenom, $email, $matiere, $login, $mot_de_passe, $role);
                $insert_result = $insert_prof->execute();
                
                logTraitement("Insertion du professeur: " . ($insert_result ? "Succès" : "Échec: " . $insert_prof->error));
                
                if (!$insert_result) {
                    logTraitement("Erreur lors de l'insertion du professeur: " . $insert_prof->error);
                    logTraitement("Valeurs utilisées - nom: $nom, prenom: $prenom, email: $email, matiere_id: $matiere, login: $login, mot_de_passe: $mot_de_passe, role: $role");
                }
            } else {
                logTraitement("Le professeur avec l'email $email existe déjà dans la table professeurs");
            }
        }
        
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
            $mail->setFrom('nawellaouini210@gmail.com', 'مدرستنا - إدارة المدرسة');
            $mail->addAddress($email, $prenom . ' ' . $nom);

            // Contenu du mail
            if ($action == 'accepter') {
                $mail->Subject = 'قبول طلب الانضمام كمدرس';
                $mail->Body = "مرحباً {$prenom} {$nom}،<br><br>
                نحن سعداء بإبلاغك بأن طلبك للانضمام إلى فريق التدريس في مدرستنا لمادة '{$matiere}' قد تم قبوله!<br><br>
                يمكنك الآن تسجيل الدخول إلى نظام المدرسة باستخدام:<br>
                اسم المستخدم: {$login}<br>
                كلمة المرور: {$mot_de_passe}<br><br>
                سنتواصل معك قريباً لمناقشة التفاصيل والخطوات التالية.<br><br>
                نتطلع للعمل معك قريباً.<br><br>
                مع أطيب التحيات،<br>
                إدارة مدرستنا";
            } else {
                $mail->Subject = 'بخصوص طلب الانضمام كمدرس';
                $mail->Body = "مرحباً {$prenom} {$nom}،<br><br>
                نشكرك على اهتمامك بالانضمام إلى فريق التدريس في مدرستنا.<br><br>
                بعد مراجعة طلبك للتدريس في مادة '{$matiere}'، نأسف لإبلاغك أننا لن نتمكن من المضي قدماً في طلبك في الوقت الحالي.<br><br>
                نقدر اهتمامك ونتمنى لك التوفيق في مساعيك المستقبلية.<br><br>
                مع أطيب التحيات،<br>
                إدارة مدرستنا";
            }

            // Envoi de l'email
            $mail->send();
            logTraitement("Email envoyé avec succès");
            header("Location: reponse_email_admin.php?message=تم تحديث الحالة وإرسال البريد الإلكتروني بنجاح");
            exit();
        } catch (Exception $e) {
            logTraitement("Erreur d'envoi d'email: " . $mail->ErrorInfo);
            header("Location: reponse_email_admin.php?message=تم تحديث الحالة ولكن فشل إرسال البريد الإلكتروني: " . $mail->ErrorInfo);
            exit();
        }
    } else {
        logTraitement("Échec de la mise à jour du statut: " . $stmt->error);
        header("Location: reponse_email_admin.php?message=فشل تحديث الحالة: " . $conn->error);
        exit();
    }
} else {
    logTraitement("Paramètres manquants dans la requête");
    header("Location: reponse_email_admin.php?message=طلب غير صالح");
    exit();
}
?>
