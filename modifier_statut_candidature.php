<?php

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les fichiers nécessaires pour l'envoi d'emails
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fonction de journalisation
function logMessage($message) {
    $logFile = 'debug_statut.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("=== DÉBUT DU TRAITEMENT ===");

// Connexion directe à la base de données pour éviter les problèmes d'inclusion
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "u504721134_formation";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    logMessage("Erreur de connexion à la base de données: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

logMessage("Connexion à la base de données réussie");

// Vérifier la structure de la table pour confirmer le nom de la colonne statut
$table_structure = $conn->query("DESCRIBE candidatures_professeurs");
$column_names = [];
if ($table_structure) {
    while ($row = $table_structure->fetch_assoc()) {
        $column_names[] = $row['Field'];
    }
    logMessage("Colonnes de la table candidatures_professeurs: " . implode(", ", $column_names));
} else {
    logMessage("Erreur lors de la récupération de la structure de la table: " . $conn->error);
}

if (isset($_GET['id']) && isset($_GET['statut'])) {
    $id = intval($_GET['id']);
    $statut_arabe = $_GET['statut']; // "مقبول" ou "مرفوض"
    
    // Utiliser des valeurs en anglais pour tester
    $statut = ($statut_arabe == 'مقبول') ? 'Acceptée' : 'Refusée';
    
    logMessage("ID: $id, Statut arabe: $statut_arabe, Statut traduit: $statut");
    
    // 1. MISE À JOUR DU STATUT - PROBLÈME 1
    // Essayer plusieurs approches pour la mise à jour du statut
    
    // Approche 1: Requête directe avec statut en anglais
    $update_query1 = "UPDATE candidatures_professeurs SET statut = '$statut' WHERE id = $id";
    logMessage("Tentative 1 - Requête de mise à jour du statut: $update_query1");
    $result1 = $conn->query($update_query1);
    logMessage("Résultat tentative 1: " . ($result1 ? "Succès" : "Échec: " . $conn->error));
    
    // Approche 2: Requête directe avec statut en arabe
    $statut_arabe_escaped = $conn->real_escape_string($statut_arabe);
    $update_query2 = "UPDATE candidatures_professeurs SET statut = '$statut_arabe_escaped' WHERE id = $id";
    logMessage("Tentative 2 - Requête de mise à jour du statut: $update_query2");
    $result2 = $conn->query($update_query2);
    logMessage("Résultat tentative 2: " . ($result2 ? "Succès" : "Échec: " . $conn->error));
    
    // Approche 3: Requête préparée avec statut en anglais
    $stmt = $conn->prepare("UPDATE candidatures_professeurs SET statut = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $statut, $id);
        $result3 = $stmt->execute();
        logMessage("Résultat tentative 3: " . ($result3 ? "Succès" : "Échec: " . $stmt->error));
        $stmt->close();
    } else {
        logMessage("Échec de la préparation de la requête 3: " . $conn->error);
    }
    
    // Vérifier que le statut a bien été mis à jour
    $check_statut = $conn->query("SELECT statut FROM candidatures_professeurs WHERE id = $id");
    if ($check_statut && $check_statut->num_rows > 0) {
        $statut_row = $check_statut->fetch_assoc();
        logMessage("Statut après tentatives de mise à jour: " . ($statut_row['statut'] ?? 'NULL'));
    }
    
    // Récupérer les informations du candidat pour l'email et la création du professeur
    $candidat_query = "SELECT * FROM candidatures_professeurs WHERE id = $id";
    $candidat_result = $conn->query($candidat_query);
    
    if ($candidat_result && $candidat_result->num_rows > 0) {
        $candidat = $candidat_result->fetch_assoc();
        
        $nom = $candidat['nom'];
        $prenom = $candidat['prenom'];
        $email = $candidat['email'];
        $matiere_text = $candidat['matiere'];
        $login = isset($candidat['login']) ? $candidat['login'] : '';
        $mot_de_passe = isset($candidat['mot_de_passe']) ? $candidat['mot_de_passe'] : '';
        $role = isset($candidat['role']) ? $candidat['role'] : 'مدرس';
        
        logMessage("Informations du candidat: Nom=$nom, Prénom=$prenom, Email=$email, Matière=$matiere_text");
        
        // Générer login et mot de passe si nécessaire
        if (empty($login)) {
            $login = strtolower(substr($prenom, 0, 1) . $nom);
            $login = preg_replace('/[^a-z0-9]/', '', $login);
            $login .= rand(100, 999);
            
            // Mettre à jour le login dans la table candidatures_professeurs
            $login_escaped = $conn->real_escape_string($login);
            $update_login = "UPDATE candidatures_professeurs SET login = '$login_escaped' WHERE id = $id";
            $conn->query($update_login);
            logMessage("Login généré: $login");
        }
        
        if (empty($mot_de_passe)) {
            $mot_de_passe = bin2hex(random_bytes(4)); // 8 caractères
            
            // Mettre à jour le mot de passe dans la table candidatures_professeurs
            $mdp_escaped = $conn->real_escape_string($mot_de_passe);
            $update_pwd = "UPDATE candidatures_professeurs SET mot_de_passe = '$mdp_escaped' WHERE id = $id";
            $conn->query($update_pwd);
            logMessage("Mot de passe généré: $mot_de_passe");
        }
        
        // 2. CRÉATION DU PROFESSEUR (si accepté)
        if ($statut_arabe == 'مقبول' || $statut == 'Acceptée') {
            // 3. GESTION DE LA MATIÈRE
            // Trouver l'ID de la matière à partir du texte
            $matiere_id = 1; // Valeur par défaut
            
            if (!empty($matiere_text)) {
                // Vérifier si la matière existe déjà dans la table matieres
                $matiere_text_escaped = $conn->real_escape_string($matiere_text);
                $matiere_query = "SELECT id FROM matieres WHERE nom = '$matiere_text_escaped'";
                $matiere_result = $conn->query($matiere_query);
                
                if ($matiere_result && $matiere_result->num_rows > 0) {
                    // La matière existe, récupérer son ID
                    $matiere_row = $matiere_result->fetch_assoc();
                    $matiere_id = $matiere_row['id'];
                    logMessage("ID de matière trouvé: $matiere_id pour '$matiere_text'");
                } else {
                    // La matière n'existe pas, la créer
                    $insert_matiere = "INSERT INTO matieres (nom) VALUES ('$matiere_text_escaped')";
                    
                    if ($conn->query($insert_matiere) === TRUE) {
                        $matiere_id = $conn->insert_id;
                        logMessage("Nouvelle matière créée avec ID: $matiere_id pour '$matiere_text'");
                    } else {
                        logMessage("Erreur lors de la création de la matière: " . $conn->error);
                    }
                }
            } else {
                logMessage("Matière vide, utilisation de l'ID par défaut: $matiere_id");
            }
            
            // Vérifier si le professeur existe déjà
            $email_escaped = $conn->real_escape_string($email);
            $check_prof = "SELECT COUNT(*) as count FROM professeurs WHERE email = '$email_escaped'";
            $prof_result = $conn->query($check_prof);
            $prof_exists = false;
            
            if ($prof_result && $prof_result->num_rows > 0) {
                $prof_row = $prof_result->fetch_assoc();
                if ($prof_row['count'] > 0) {
                    $prof_exists = true;
                    logMessage("Le professeur avec l'email $email existe déjà");
                }
            }
            
            if (!$prof_exists) {
                // Insérer le nouveau professeur avec matiere_id explicite
                $nom_escaped = $conn->real_escape_string($nom);
                $prenom_escaped = $conn->real_escape_string($prenom);
                $login_escaped = $conn->real_escape_string($login);
                $mdp_escaped = $conn->real_escape_string($mot_de_passe);
                $role_escaped = $conn->real_escape_string($role);
                
                $insert_prof = "INSERT INTO professeurs (nom, prenom, email, matiere_id, login, mot_de_passe, role) 
                               VALUES ('$nom_escaped', '$prenom_escaped', '$email_escaped', $matiere_id, '$login_escaped', '$mdp_escaped', '$role_escaped')";
                
                logMessage("Requête d'insertion du professeur: $insert_prof");
                
                if ($conn->query($insert_prof) === TRUE) {
                    $new_prof_id = $conn->insert_id;
                    logMessage("Professeur créé avec succès! ID: $new_prof_id");
                } else {
                    logMessage("ERREUR lors de l'insertion du professeur: " . $conn->error);
                }
            }
        }
        
        // 4. ENVOI D'EMAIL
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
            
            if ($statut_arabe == 'مقبول' || $statut == 'Acceptée') {
                $mail->Subject = 'قبول طلب الانضمام كمدرس';
                $mail->Body = "مرحباً {$prenom} {$nom}،<br><br>
                نحن سعداء بإبلاغك بأن طلبك للانضمام إلى فريق التدريس في مدرستنا لمادة '{$matiere_text}' قد تم قبوله!<br><br>
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
                بعد مراجعة طلبك للتدريس في مادة '{$matiere_text}'، نأسف لإبلاغك أننا لن نتمكن من المضي قدماً في طلبك في الوقت الحالي.<br><br>
                نقدر اهتمامك ونتمنى لك التوفيق في مساعيك المستقبلية.<br><br>
                مع أطيب التحيات،<br>
                إدارة مدرستنا";
            }

            $mail->send();
            logMessage("Email envoyé avec succès à $email");
            
            // Rediriger vers la page d'administration avec un message de succès
            if ($statut_arabe == 'مقبول' || $statut == 'Acceptée') {
                header("Location: reponse_email_admin.php?message=تم قبول الطلب وإنشاء المدرس وإرسال البريد الإلكتروني بنجاح");
            } else {
                header("Location: reponse_email_admin.php?message=تم رفض الطلب وإرسال البريد الإلكتروني بنجاح");
            }
            exit();
        } catch (Exception $e) {
            logMessage("Erreur d'envoi d'email: " . $mail->ErrorInfo);
            
            // Rediriger avec un message d'erreur pour l'email
            if ($statut_arabe == 'مقبول' || $statut == 'Acceptée') {
                header("Location: reponse_email_admin.php?message=تم قبول الطلب وإنشاء المدرس ولكن فشل إرسال البريد الإلكتروني");
            } else {
                header("Location: reponse_email_admin.php?message=تم رفض الطلب ولكن فشل إرسال البريد الإلكتروني");
            }
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
