<?php
session_start();
require_once 'db_config.php';
require_once 'send_email.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_professeur = $_POST['id_professeur'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $matiere_id = $_POST['matiere_id'];

    // Récupérer les anciennes informations pour les comparer
    $query_old = "SELECT p.*, m.nom as nom_matiere 
                 FROM professeurs p 
                 LEFT JOIN matieres m ON p.matiere_id = m.matiere_id 
                 WHERE p.id_professeur = ?";
    $stmt_old = $conn->prepare($query_old);
    $stmt_old->bind_param("i", $id_professeur);
    $stmt_old->execute();
    $old_data = $stmt_old->get_result()->fetch_assoc();

    // Mettre à jour les informations du professeur
    $query = "UPDATE professeurs SET nom = ?, prenom = ?, email = ?, matiere_id = ? WHERE id_professeur = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("sssii", $nom, $prenom, $email, $matiere_id, $id_professeur);
        
        if ($stmt->execute()) {
            // Récupérer le nom de la nouvelle matière
            $query_matiere = "SELECT nom FROM matieres WHERE matiere_id = ?";
            $stmt_matiere = $conn->prepare($query_matiere);
            $stmt_matiere->bind_param("i", $matiere_id);
            $stmt_matiere->execute();
            $result_matiere = $stmt_matiere->get_result();
            $matiere = $result_matiere->fetch_assoc();
            
            // Préparer le message des changements
            $changes = [];
            if ($old_data['nom'] !== $nom || $old_data['prenom'] !== $prenom) {
                $changes[] = "الاسم: من {$old_data['nom']} {$old_data['prenom']} إلى {$nom} {$prenom}";
            }
            if ($old_data['email'] !== $email) {
                $changes[] = "البريد الإلكتروني: من {$old_data['email']} إلى {$email}";
            }
            if ($old_data['matiere_id'] != $matiere_id) {
                $changes[] = "المادة: من {$old_data['nom_matiere']} إلى {$matiere['nom']}";
            }

            // Préparer et envoyer l'email
            $subject = "تحديث معلوماتك الشخصية";
            $message = "
                <div dir='rtl' style='font-family: Arial, sans-serif;'>
                    <h2>تحديث المعلومات الشخصية</h2>
                    <p>مرحباً {$nom} {$prenom},</p>
                    <p>نود إعلامكم أنه تم تحديث معلوماتكم الشخصية في منصة المدرسة:</p>
                    <h3>التغييرات التي تم إجراؤها:</h3>
                    <ul>";
            
            foreach ($changes as $change) {
                $message .= "<li>{$change}</li>";
            }
            
            $message .= "
                    </ul>
                    <p>إذا كان لديك أي استفسار أو ملاحظة، يرجى التواصل مع الإدارة.</p>
                    <p>مع أطيب التحيات،<br>إدارة المدرسة</p>
                </div>";

            // Envoyer l'email
            $email_sent = sendEmailToProf($email, $nom . ' ' . $prenom, $subject, $message);
            
            if ($email_sent) {
                $_SESSION['success_message'] = "تم تحديث المعلومات وإرسال إشعار للأستاذ بنجاح";
            } else {
                $_SESSION['warning_message'] = "تم تحديث المعلومات ولكن فشل إرسال الإشعار. يرجى التحقق من صحة البريد الإلكتروني.";
            }
            
            header('Location: afficher_prof.php');
            exit();
        } else {
            $_SESSION['error_message'] = "حدث خطأ أثناء تحديث المعلومات";
            header('Location: afficher_prof.php');
            exit();
        }
    }
}

header('Location: afficher_prof.php');
exit();
?> 