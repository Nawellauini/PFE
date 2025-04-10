<?php
include 'db_config.php';
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Créer un fichier de log pour le débogage
function logMessage($message) {
    $logFile = 'debug_statut.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("Début du traitement");

if (isset($_GET['id']) && isset($_GET['statut'])) {
    $id = intval($_GET['id']);
    $statut = $_GET['statut']; // "مقبول" ou "مرفوض"
    
    logMessage("ID: $id, Statut: $statut");

    // Récupérer les infos du candidat
    $sql = "SELECT * FROM candidatures_professeurs WHERE id = $id";
    $result = $conn->query($sql);
    
    logMessage("Requête SQL pour récupérer les infos: $sql");
    
    if ($result && $result->num_rows == 1) {
        $prof = $result->fetch_assoc();
        $email = $prof['email'];
        $nom = $prof['nom'];
        $prenom = $prof['prenom'];
        $matiere = $prof['matiere'];
        
        logMessage("Candidat trouvé: $prenom $nom, Email: $email, Matière: $matiere");
        
        // Mettre à jour le statut dans la BD - Méthode 1
        $update_sql = "UPDATE candidatures_professeurs SET statut = '$statut' WHERE id = $id";
        logMessage("Requête de mise à jour: $update_sql");
        
        $update_result = $conn->query($update_sql);
        
        if ($update_result) {
            logMessage("Mise à jour réussie avec la méthode 1");
            
            // Vérifier si la mise à jour a fonctionné
            $check_sql = "SELECT statut FROM candidatures_professeurs WHERE id = $id";
            $check_result = $conn->query($check_sql);
            $check_row = $check_result->fetch_assoc();
            
            logMessage("Statut après mise à jour: " . ($check_row['statut'] ?? 'NULL'));
            
            if (!isset($check_row['statut']) || $check_row['statut'] != $statut) {
                logMessage("La mise à jour n'a pas été enregistrée correctement, essai avec la méthode 2");
                
                // Méthode 2: Requête préparée
                $stmt = $conn->prepare("UPDATE candidatures_professeurs SET statut = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("si", $statut, $id);
                    $result2 = $stmt->execute();
                    logMessage("Résultat méthode 2: " . ($result2 ? "Succès" : "Échec: " . $stmt->error));
                    $stmt->close();
                } else {
                    logMessage("Échec préparation requête: " . $conn->error);
                }
                
                // Méthode 3: Essai avec une valeur simple
                if ($statut == 'مقبول') {
                    $simple_statut = 'Acceptée';
                } else {
                    $simple_statut = 'Refusée';
                }
                
                $simple_sql = "UPDATE candidatures_professeurs SET statut = '$simple_statut' WHERE id = $id";
                $simple_result = $conn->query($simple_sql);
                logMessage("Méthode 3 (valeur simple): " . ($simple_result ? "Succès" : "Échec: " . $conn->error));
            }
            
            // Envoyer un email au candidat
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nawellaouini210@gmail.com';
                $mail->Password = 'lddg ridp kmxw alfn';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8'; // Important pour les caractères arabes

                $mail->setFrom('nawellaouini210@gmail.com', 'مدرستنا - إدارة المدرسة');
                $mail->addAddress($email, $prenom . ' ' . $nom);

                $mail->isHTML(true);
                
                if ($statut == 'مقبول') {
                    $mail->Subject = 'قبول طلب الانضمام كمدرس';
                    $mail->Body = "مرحباً {$prenom} {$nom}،<br><br>
                    نحن سعداء بإبلاغك بأن طلبك للانضمام إلى فريق التدريس في مدرستنا لمادة '{$matiere}' قد تم قبوله!<br><br>
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

                $mail->send();
                logMessage("Email envoyé avec succès");
                header("Location: reponse_email_admin.php?message=تم تحديث الحالة وإرسال البريد الإلكتروني بنجاح");
                exit();
            } catch (Exception $e) {
                logMessage("Erreur d'envoi d'email: " . $mail->ErrorInfo);
                header("Location: reponse_email_admin.php?message=تم تحديث الحالة ولكن فشل إرسال البريد الإلكتروني");
                exit();
            }
        } else {
            logMessage("Erreur mise à jour du statut: " . $conn->error);
            
            // Vérifier si la colonne statut existe
            $check_column = $conn->query("SHOW COLUMNS FROM candidatures_professeurs LIKE 'statut'");
            if ($check_column->num_rows == 0) {
                logMessage("La colonne 'statut' n'existe pas. Tentative de création...");
                $alter_sql = "ALTER TABLE candidatures_professeurs ADD COLUMN statut VARCHAR(50) DEFAULT NULL";
                $alter_result = $conn->query($alter_sql);
                logMessage("Résultat création colonne: " . ($alter_result ? "Succès" : "Échec: " . $conn->error));
                
                // Réessayer la mise à jour
                $retry_result = $conn->query($update_sql);
                logMessage("Réessai mise à jour: " . ($retry_result ? "Succès" : "Échec: " . $conn->error));
            }
            
            header("Location: reponse_email_admin.php?message=خطأ في تحديث الحالة");
            exit();
        }
    } else {
        logMessage("Candidature introuvable pour ID: $id");
        header("Location: reponse_email_admin.php?message=طلب غير موجود");
        exit();
    }
} else {
    logMessage("Paramètres manquants");
    header("Location: reponse_email_admin.php?message=معلمات مفقودة");
    exit();
}
?>