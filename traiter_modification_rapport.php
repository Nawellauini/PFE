<?php
session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est connecté et est un inspecteur
if (!isset($_SESSION['id_inspecteur'])) {
    header("Location: login.php?error=يجب أن تكون متصلاً كمفتش");
    exit();
}

$id_inspecteur = $_SESSION['id_inspecteur']; // Récupération de l'inspecteur connecté

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire
    $id_rapport = $_POST['id'];
    $titre = $_POST['titre'];
    $id_classe = $_POST['id_classe'];
    $id_professeur = $_POST['id_professeur'];
    $id_inspecteur = $_POST['id_inspecteur']; // Utilisation de l'inspecteur sélectionné dans le formulaire
    $commentaires = $_POST['commentaires'];
    $recommandations = $_POST['recommandations'];
    $date_modification = date("Y-m-d H:i:s");

    // Requête de mise à jour du rapport
    $query = "UPDATE rapports_inspection SET 
              titre = ?, 
              id_classe = ?, 
              id_professeur = ?, 
              id_inspecteur = ?, 
              commentaires = ?, 
              recommandations = ?, 
              date_modification = ? 
              WHERE id = ?";

    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        // Journaliser l'erreur de préparation
        error_log("خطأ في إعداد الاستعلام: " . $conn->error);
        header("Location: modifier_rapport.php?id=$id_rapport&message=" . urlencode("خطأ في إعداد الاستعلام: " . $conn->error) . "&type=error");
        exit();
    }
    
    $stmt->bind_param("siiisssi", $titre, $id_classe, $id_professeur, $id_inspecteur, $commentaires, $recommandations, $date_modification, $id_rapport);

    if ($stmt->execute()) {
        // Traitement des nouveaux fichiers joints
        $upload_success = true;
        $error_message = "";
        
        if (isset($_FILES['fichiers']) && !empty($_FILES['fichiers']['name'][0])) {
            $upload_dir = "uploads/";
            
            // Créer le répertoire s'il n'existe pas
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['fichiers']['name'] as $key => $name) {
                if ($_FILES['fichiers']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['fichiers']['tmp_name'][$key];
                    $original_name = $_FILES['fichiers']['name'][$key];
                    $file_size = $_FILES['fichiers']['size'][$key];
                    $file_type = $_FILES['fichiers']['type'][$key];
                    
                    // Générer un nom de fichier unique
                    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $extension;
                    $destination = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        // Enregistrer le fichier dans la base de données
                        $sql_file = "INSERT INTO fichiers_rapport (rapport_id, nom_fichier, chemin_fichier, type_fichier, date_upload) 
                                     VALUES (?, ?, ?, ?, NOW())";
                        $stmt_file = $conn->prepare($sql_file);
                        
                        if ($stmt_file === false) {
                            $upload_success = false;
                            $error_message = "خطأ في إعداد استعلام الملف: " . $conn->error;
                            break;
                        }
                        
                        $stmt_file->bind_param("isss", $id_rapport, $original_name, $destination, $file_type);
                        
                        if (!$stmt_file->execute()) {
                            $upload_success = false;
                            $error_message = "خطأ أثناء تسجيل الملف في قاعدة البيانات: " . $stmt_file->error;
                            break;
                        }
                    } else {
                        $upload_success = false;
                        $error_message = "خطأ أثناء تحميل الملف: " . $original_name;
                        break;
                    }
                } else if ($_FILES['fichiers']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                    // Ignorer les erreurs UPLOAD_ERR_NO_FILE (aucun fichier sélectionné)
                    $upload_success = false;
                    $error_code = $_FILES['fichiers']['error'][$key];
                    $error_message = "خطأ في التحميل (الرمز: $error_code) للملف: " . $_FILES['fichiers']['name'][$key];
                    break;
                }
            }
            
            if (!$upload_success) {
                // Journaliser l'erreur pour le débogage
                error_log("خطأ في التحميل: " . $error_message);
                
                // Rediriger avec un message d'erreur
                header("Location: modifier_rapport.php?id=$id_rapport&message=" . urlencode("تم تحديث التقرير ولكن هناك مشكلة مع الملفات: " . $error_message) . "&type=warning");
                exit();
            }
        }
        
        // Redirection en cas de succès
        header("Location: liste_rapports.php?message=تم+تحديث+التقرير+بنجاح&type=success");
        exit();
    } else {
        // Journaliser l'erreur pour le débogage
        error_log("خطأ في تحديث التقرير: " . $stmt->error);
        
        // Rediriger avec un message d'erreur
        header("Location: modifier_rapport.php?id=$id_rapport&message=" . urlencode("خطأ أثناء تحديث التقرير: " . $stmt->error) . "&type=error");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جاري المعالجة | نظام إدارة تقارير التفتيش</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56d4;
            --secondary-color: #7209b7;
            --accent-color: #f72585;
            --success-color: #38b000;
            --warning-color: #f9c74f;
            --danger-color: #d90429;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f7fb;
            color: var(--gray-800);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: right;
        }

        .loading-container {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            max-width: 400px;
            width: 100%;
        }

        .loading-spinner {
            display: inline-block;
            width: 60px;
            height: 60px;
            border: 5px solid rgba(67, 97, 238, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 1.5rem;
        }

        .loading-text {
            font-size: 1.2rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
        }

        .loading-subtext {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <meta http-equiv="refresh" content="2;url=liste_rapports.php">
</head>
<body>
    <div class="loading-container">
        <div class="loading-spinner"></div>
        <p class="loading-text">جاري المعالجة...</p>
        <p class="loading-subtext">سيتم إعادة توجيهك تلقائياً.</p>
    </div>
</body>
</html>