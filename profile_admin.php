<?php
session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est connecté en tant qu'administrateur
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Récupérer les informations de l'administrateur
$query = "SELECT * FROM administrateurs WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Rediriger si l'administrateur n'existe pas
    header("Location: login.php");
    exit();
}

$admin = $result->fetch_assoc();

// Traitement du formulaire de mise à jour du profil
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $telephone = $_POST['telephone'];

        // Validation des données
        if (empty($nom) || empty($prenom) || empty($email)) {
            $error_message = "الرجاء ملء جميع الحقول المطلوبة";
        } else {
            // Vérifier si l'email existe déjà pour un autre administrateur
            $check_email = "SELECT id FROM administrateurs WHERE email = ? AND id != ?";
            $stmt_check = $conn->prepare($check_email);
            $stmt_check->bind_param("si", $email, $admin_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $error_message = "البريد الإلكتروني مستخدم بالفعل";
            } else {
                // Mise à jour du profil
                $update_query = "UPDATE administrateurs SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id = ?";
                $stmt_update = $conn->prepare($update_query);
                $stmt_update->bind_param("ssssi", $nom, $prenom, $email, $telephone, $admin_id);
                
                if ($stmt_update->execute()) {
                    $success_message = "تم تحديث الملف الشخصي بنجاح";
                    
                    // Mettre à jour les données de session
                    $_SESSION['admin_nom'] = $nom;
                    $_SESSION['admin_prenom'] = $prenom;
                    
                    // Rafraîchir les données de l'administrateur
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $admin = $result->fetch_assoc();
                } else {
                    $error_message = "حدث خطأ أثناء تحديث الملف الشخصي: " . $conn->error;
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation des données
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "الرجاء ملء جميع حقول كلمة المرور";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "كلمة المرور الجديدة وتأكيدها غير متطابقين";
        } elseif (strlen($new_password) < 8) {
            $error_message = "يجب أن تتكون كلمة المرور من 8 أحرف على الأقل";
        } else {
            // Vérifier le mot de passe actuel
            $check_password = "SELECT mot_de_passe FROM administrateurs WHERE id = ?";
            $stmt_check = $conn->prepare($check_password);
            $stmt_check->bind_param("i", $admin_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $row = $result_check->fetch_assoc();
            
            if (password_verify($current_password, $row['mot_de_passe'])) {
                // Hacher le nouveau mot de passe
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Mettre à jour le mot de passe
                $update_query = "UPDATE administrateurs SET mot_de_passe = ? WHERE id = ?";
                $stmt_update = $conn->prepare($update_query);
                $stmt_update->bind_param("si", $hashed_password, $admin_id);
                
                if ($stmt_update->execute()) {
                    $success_message = "تم تغيير كلمة المرور بنجاح";
                } else {
                    $error_message = "حدث خطأ أثناء تغيير كلمة المرور: " . $conn->error;
                }
            } else {
                $error_message = "كلمة المرور الحالية غير صحيحة";
            }
        }
    } elseif (isset($_POST['update_avatar']) && isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];
        
        // Vérifier s'il y a une erreur
        if ($file['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            // Vérifier le type de fichier
            if (!in_array($file['type'], $allowed_types)) {
                $error_message = "نوع الملف غير مسموح به. يرجى تحميل صورة بتنسيق JPEG أو PNG أو GIF";
            } 
            // Vérifier la taille du fichier
            elseif ($file['size'] > $max_size) {
                $error_message = "حجم الملف كبير جدًا. الحد الأقصى هو 2 ميغابايت";
            } else {
                // Créer le répertoire s'il n'existe pas
                $upload_dir = 'uploads/avatars/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Générer un nom de fichier unique
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
                $target_path = $upload_dir . $filename;
                
                // Déplacer le fichier téléchargé
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Mettre à jour le chemin de l'avatar dans la base de données
                    $update_query = "UPDATE administrateurs SET avatar = ? WHERE id = ?";
                    $stmt_update = $conn->prepare($update_query);
                    $stmt_update->bind_param("si", $filename, $admin_id);
                    
                    if ($stmt_update->execute()) {
                        $success_message = "تم تحديث الصورة الشخصية بنجاح";
                        
                        // Rafraîchir les données de l'administrateur
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $admin = $result->fetch_assoc();
                    } else {
                        $error_message = "حدث خطأ أثناء تحديث الصورة الشخصية: " . $conn->error;
                    }
                } else {
                    $error_message = "فشل في تحميل الملف";
                }
            }
        } else {
            $error_message = "حدث خطأ أثناء تحميل الملف. رمز الخطأ: " . $file['error'];
        }
    }
}

// Récupérer les statistiques de l'administrateur
$stats = [
    'total_candidatures' => 0,
    'total_inscriptions' => 0,
    'total_professeurs' => 0,
    'total_etudiants' => 0
];

// Requêtes pour obtenir les statistiques
$query_candidatures = "SELECT COUNT(*) as total FROM candidatures_professeurs";
$result_candidatures = $conn->query($query_candidatures);
if ($result_candidatures) {
    $stats['total_candidatures'] = $result_candidatures->fetch_assoc()['total'];
}

$query_inscriptions = "SELECT COUNT(*) as total FROM inscriptions_etudiants";
$result_inscriptions = $conn->query($query_inscriptions);
if ($result_inscriptions) {
    $stats['total_inscriptions'] = $result_inscriptions->fetch_assoc()['total'];
}

$query_professeurs = "SELECT COUNT(*) as total FROM professeurs";
$result_professeurs = $conn->query($query_professeurs);
if ($result_professeurs) {
    $stats['total_professeurs'] = $result_professeurs->fetch_assoc()['total'];
}

$query_etudiants = "SELECT COUNT(*) as total FROM etudiants";
$result_etudiants = $conn->query($query_etudiants);
if ($result_etudiants) {
    $stats['total_etudiants'] = $result_etudiants->fetch_assoc()['total'];
}

// Récupérer les dernières activités de l'administrateur
$query_activities = "SELECT * FROM activites_admin WHERE admin_id = ? ORDER BY date_activite DESC LIMIT 5";
$stmt_activities = $conn->prepare($query_activities);
$stmt_activities->bind_param("i", $admin_id);
$stmt_activities->execute();
$result_activities = $stmt_activities->get_result();

// Récupérer les dernières notifications
$query_notifications = "SELECT * FROM notifications WHERE destinataire_id = ? AND type_destinataire = 'admin' ORDER BY date_creation DESC LIMIT 5";
$stmt_notifications = $conn->prepare($query_notifications);
$stmt_notifications->bind_param("i", $admin_id);
$stmt_notifications->execute();
$result_notifications = $stmt_notifications->get_result();

// Récupérer le nombre de notifications non lues
$query_unread = "SELECT COUNT(*) as total FROM notifications WHERE destinataire_id = ? AND type_destinataire = 'admin' AND lu = 0";
$stmt_unread = $conn->prepare($query_unread);
$stmt_unread->bind_param("i", $admin_id);
$stmt_unread->execute();
$result_unread = $stmt_unread->get_result();
$unread_count = $result_unread->fetch_assoc()['total'];

// Fonction pour formater la date
function formatDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return "منذ " . $diff . " ثانية";
    } elseif ($diff < 3600) {
        return "منذ " . floor($diff / 60) . " دقيقة";
    } elseif ($diff < 86400) {
        return "منذ " . floor($diff / 3600) . " ساعة";
    } elseif ($diff < 604800) {
        return "منذ " . floor($diff / 86400) . " يوم";
    } else {
        return date("Y-m-d", $timestamp);
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي للمسؤول | لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --primary-light: #dbeafe;
            --secondary-color: #10b981;
            --secondary-dark: #059669;
            --secondary-light: #d1fae5;
            --danger-color: #ef4444;
            --danger-dark: #dc2626;
            --danger-light: #fee2e2;
            --warning-color: #f59e0b;
            --warning-dark: #d97706;
            --warning-light: #fef3c7;
            --info-color: #6366f1;
            --info-dark: #4f46e5;
            --info-light: #e0e7ff;
            --success-color: #10b981;
            --success-dark: #059669;
            --success-light: #d1fae5;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
            --gray-color: #9ca3af;
            --white-color: #ffffff;
            --border-radius: 12px;
            --transition-speed: 0.3s;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }
        
        body {
            background-color: #f9fafb;
            color: var(--dark-color);
            line-height: 1.6;
            direction: rtl;
        }
        
        /* Scrollbar personnalisé */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--gray-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }
        
        /* Layout */
        .layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(to bottom, var(--info-dark), var(--info-color));
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            z-index: 100;
            transition: all var(--transition-speed) ease;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: white;
        }
        
        .logo-icon {
            font-size: 24px;
            color: white;
        }
        
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: white;
            cursor: pointer;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-title {
            padding: 0 20px;
            margin-bottom: 10px;
            font-size: 12px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
        }
        
        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all var(--transition-speed) ease;
            border-right: 4px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-right-color: var(--accent-color);
        }
        
        .menu-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-right: 280px;
            transition: all var(--transition-speed) ease;
        }
        
        /* Header */
        .header {
            background-color: var(--white-color);
            height: 70px;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 99;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--dark-color);
            cursor: pointer;
        }
        
        .search-bar {
            position: relative;
        }
        
        .search-input {
            padding: 10px 15px 10px 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            width: 300px;
            transition: all var(--transition-speed) ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--info-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            width: 350px;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-icon {
            position: relative;
            font-size: 20px;
            color: var(--dark-color);
            cursor: pointer;
            transition: all var(--transition-speed) ease;
        }
        
        .header-icon:hover {
            color: var(--info-color);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            left: -5px;
            background-color: var(--danger-color);
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--info-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            overflow: hidden;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-info {
            display: none;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-role {
            font-size: 12px;
            color: var(--gray-color);
        }
        
        /* Container */
        .container {
            padding: 30px;
        }
        
        /* Page Title */
        .page-title {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .title i {
            color: var(--info-color);
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .breadcrumb a {
            color: var(--info-color);
            text-decoration: none;
        }
        
        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: fadeIn 0.5s ease;
        }
        
        .alert-success {
            background-color: var(--success-light);
            border-right: 4px solid var(--success-color);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: var(--danger-light);
            border-right: 4px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        .alert-icon {
            font-size: 20px;
        }
        
        /* Profile Section */
        .profile-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .profile-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: all var(--transition-speed) ease;
            height: 100%;
        }
        
        .profile-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-5px);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--info-color), var(--info-dark));
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -20px;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 1;
        }
        
        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -30px;
            right: -30px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            z-index: 1;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            position: relative;
            z-index: 2;
            background-color: white;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }
        
        .profile-avatar:hover .profile-avatar-overlay {
            opacity: 1;
        }
        
        .profile-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            position: relative;
            z-index: 2;
        }
        
        .profile-role {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            position: relative;
            z-index: 2;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .profile-body {
            padding: 20px;
        }
        
        .profile-info-list {
            list-style: none;
            padding: 0;
        }
        
        .profile-info-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .profile-info-item:last-child {
            border-bottom: none;
        }
        
        .profile-info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--info-light);
            color: var(--info-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            font-size: 16px;
        }
        
        .profile-info-content {
            flex: 1;
        }
        
        .profile-info-label {
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 3px;
        }
        
        .profile-info-value {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .profile-action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all var(--transition-speed) ease;
        }
        
        .btn-primary {
            background-color: var(--info-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--info-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--gray-200);
            color: var(--gray-700);
        }
        
        .btn-secondary:hover {
            background-color: var(--gray-300);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: var(--danger-dark);
            transform: translateY(-2px);
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 12px 20px;
            font-weight: 600;
            color: var(--gray-500);
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {  
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            color: var(--info-color);
            border-bottom-color: var(--info-color);
        }
        
        .tab:hover {
            color: var(--info-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: all var(--transition-speed) ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--info-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-text {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 5px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            transition: all var(--transition-speed) ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--info-color), var(--info-light));
        }
        
        .stat-card:nth-child(2)::before {
            background: linear-gradient(to right, var(--success-color), var(--success-light));
        }
        
        .stat-card:nth-child(3)::before {
            background: linear-gradient(to right, var(--warning-color), var(--warning-light));
        }
        
        .stat-card:nth-child(4)::before {
            background: linear-gradient(to right, var(--danger-color), var(--danger-light));
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .stat-title {
            font-size: 14px;
            color: var(--gray-500);
            font-weight: 500;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        
        .stat-icon.blue {
            background-color: var(--info-color);
        }
        
        .stat-icon.green {
            background-color: var(--success-color);
        }
        
        .stat-icon.orange {
            background-color: var(--warning-color);
        }
        
        .stat-icon.red {
            background-color: var(--danger-color);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-description {
            font-size: 12px;
            color: var(--gray-500);
        }
        
        /* Activity List */
        .activity-list {
            list-style: none;
            padding: 0;
        }
        
        .activity-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--info-light);
            color: var(--info-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .activity-time {
            font-size: 12px;
            color: var(--gray-500);
        }
        
        /* Notification List */
        .notification-list {
            list-style: none;
            padding: 0;
        }
        
        .notification-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid var(--gray-200);
            transition: all var(--transition-speed) ease;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item:hover {
            background-color: var(--gray-100);
        }
        
        .notification-item.unread {
            background-color: var(--info-light);
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--info-light);
            color: var(--info-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .notification-text {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 5px;
        }
        
        .notification-time {
            font-size: 12px;
            color: var(--gray-500);
        }
        
        /* Card */
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            transition: all var(--transition-speed) ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--gray-50);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i {
            color: var(--info-color);
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
            backdrop-filter: blur(5px);
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to right, var(--info-color), var(--info-dark));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 20px;
            color: white;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-modal:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background-color: var(--gray-50);
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .profile-section {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-right: 0;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .search-input {
                width: 200px;
            }
            
            .search-input:focus {
                width: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .header {
                padding: 0 20px;
            }
            
            .search-bar {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-stats {
                flex-wrap: wrap;
            }
            
            .stat-item {
                width: 50%;
                margin-bottom: 15px;
            }
            
            .tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
            
            .tab {
                padding: 10px 15px;
            }
        }
        
        @media (min-width: 992px) {
            .user-info {
                display: block;
            }
        }
    </style>
</head>
<body>

<div class="layout">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-school logo-icon"></i>
                <span>مدرستنا</span>
            </div>
            <button class="sidebar-toggle" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sidebar-menu">
            <div class="menu-title">القائمة الرئيسية</div>
            <a href="#" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>لوحة التحكم</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-user-graduate"></i>
                <span>طلبات التسجيل</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>طلبات المدرسين</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-users"></i>
                <span>الطلاب</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-book"></i>
                <span>المواد الدراسية</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>الجدول الدراسي</span>
            </a>
            
            <div class="menu-title">الإعدادات</div>
            <a href="#" class="menu-item active">
                <i class="fas fa-user-cog"></i>
                <span>الملف الشخصي</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>إعدادات النظام</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <button class="mobile-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="search-bar">
                    <input type="text" class="search-input" placeholder="البحث...">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>
            <div class="header-right">
                <div class="header-icon">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?= $unread_count ?></span>
                    <?php endif; ?>
                </div>
                <div class="header-icon">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php if (!empty($admin['avatar'])): ?>
                        <img src="uploads/avatars/<?= $admin['avatar'] ?>" alt="صورة الملف الشخصي">
                        <?php else: ?>
                        <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) ?></div>
                        <div class="user-role">مسؤول النظام</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container -->
        <div class="container">
            <!-- Page Title -->
            <div class="page-title">
                <h1 class="title"><i class="fas fa-user-cog"></i> الملف الشخصي</h1>
                <div class="breadcrumb">
                    <a href="#">الرئيسية</a>
                    <i class="fas fa-chevron-left"></i>
                    <span>الملف الشخصي</span>
                </div>
            </div>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <div class="alert-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <?= $success_message ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <?= $error_message ?>
            </div>
            <?php endif; ?>

            <!-- Profile Section -->
            <div class="profile-section">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php if (!empty($admin['avatar'])): ?>
                            <img src="uploads/avatars/<?= $admin['avatar'] ?>" alt="صورة الملف الشخصي">
                            <?php else: ?>
                            <i class="fas fa-user" style="font-size: 60px; color: #6366f1;"></i>
                            <?php endif; ?>
                            <div class="profile-avatar-overlay" id="changeAvatarBtn">
                                <i class="fas fa-camera" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        <h2 class="profile-name"><?= htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) ?></h2>
                        <div class="profile-role">مسؤول النظام</div>
                        <div class="profile-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?= $stats['total_candidatures'] ?></div>
                                <div class="stat-label">طلبات المدرسين</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= $stats['total_inscriptions'] ?></div>
                                <div class="stat-label">طلبات التسجيل</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= $stats['total_professeurs'] ?></div>
                                <div class="stat-label">المدرسين</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= $stats['total_etudiants'] ?></div>
                                <div class="stat-label">الطلاب</div>
                            </div>
                        </div>
                    </div>
                    <div class="profile-body">
                        <ul class="profile-info-list">
                            <li class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="profile-info-content">
                                    <div class="profile-info-label">البريد الإلكتروني</div>
                                    <div class="profile-info-value"><?= htmlspecialchars($admin['email']) ?></div>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="profile-info-content">
                                    <div class="profile-info-label">رقم الهاتف</div>
                                    <div class="profile-info-value"><?= htmlspecialchars($admin['telephone'] ?? 'غير متوفر') ?></div>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="profile-info-content">
                                    <div class="profile-info-label">تاريخ الانضمام</div>
                                    <div class="profile-info-value"><?= isset($admin['date_creation']) ? date('Y-m-d', strtotime($admin['date_creation'])) : 'غير متوفر' ?></div>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="profile-info-content">
                                    <div class="profile-info-label">الصلاحيات</div>
                                    <div class="profile-info-value">مسؤول كامل الصلاحيات</div>
                                </div>
                            </li>
                        </ul>
                        <div class="profile-actions">
                            <button class="profile-action-btn btn-primary" id="editProfileBtn">
                                <i class="fas fa-edit"></i> تعديل الملف الشخصي
                            </button>
                            <button class="profile-action-btn btn-secondary" id="changePasswordBtn">
                                <i class="fas fa-key"></i> تغيير كلمة المرور
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-tasks"></i> الأنشطة والإشعارات</h2>
                    </div>
                    <div class="card-body">
                        <div class="tabs">
                            <div class="tab active" data-tab="activities">آخر الأنشطة</div>
                            <div class="tab" data-tab="notifications">الإشعارات</div>
                            <div class="tab" data-tab="security">الأمان</div>
                        </div>
                        
                        <div class="tab-content active" id="activities-tab">
                            <ul class="activity-list">
                                <?php if ($result_activities && $result_activities->num_rows > 0): ?>
                                    <?php while ($activity = $result_activities->fetch_assoc()): ?>
                                    <li class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-history"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title"><?= htmlspecialchars($activity['description']) ?></div>
                                            <div class="activity-time"><?= formatDate($activity['date_activite']) ?></div>
                                        </div>
                                    </li>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <li class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">لا توجد أنشطة حديثة</div>
                                            <div class="activity-time">-</div>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <div class="tab-content" id="notifications-tab">
                            <ul class="notification-list">
                                <?php if ($result_notifications && $result_notifications->num_rows > 0): ?>
                                    <?php while ($notification = $result_notifications->fetch_assoc()): ?>
                                    <li class="notification-item <?= $notification['lu'] ? '' : 'unread' ?>">
                                        <div class="notification-icon">
                                            <i class="fas fa-bell"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-title"><?= htmlspecialchars($notification['titre']) ?></div>
                                            <div class="notification-text"><?= htmlspecialchars($notification['message']) ?></div>
                                            <div class="notification-time"><?= formatDate($notification['date_creation']) ?></div>
                                        </div>
                                    </li>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <li class="notification-item">
                                        <div class="notification-icon">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-title">لا توجد إشعارات</div>
                                            <div class="notification-text">ستظهر الإشعارات الجديدة هنا</div>
                                            <div class="notification-time">-</div>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <div class="tab-content" id="security-tab">
                            <div class="form-group">
                                <label class="form-label">تسجيل الدخول ثنائي العامل</label>
                                <div class="d-flex align-items-center">
                                    <button class="profile-action-btn btn-primary" style="width: auto;">
                                        <i class="fas fa-shield-alt"></i> تفعيل المصادقة الثنائية
                                    </button>
                                </div>
                                <div class="form-text">تعزيز أمان حسابك باستخدام المصادقة الثنائية</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">سجل تسجيل الدخول</label>
                                <button class="profile-action-btn btn-secondary" style="width: auto;">
                                    <i class="fas fa-history"></i> عرض سجل تسجيل الدخول
                                </button>
                                <div class="form-text">عرض جميع عمليات تسجيل الدخول السابقة</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">جلسات نشطة</label>
                                <button class="profile-action-btn btn-danger" style="width: auto;">
                                    <i class="fas fa-sign-out-alt"></i> إنهاء جميع الجلسات الأخرى
                                </button>
                                <div class="form-text">تسجيل الخروج من جميع الأجهزة الأخرى</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">طلبات المدرسين</div>
                        <div class="stat-icon blue">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $stats['total_candidatures'] ?></div>
                    <div class="stat-description">إجمالي طلبات المدرسين</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">طلبات التسجيل</div>
                        <div class="stat-icon green">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $stats['total_inscriptions'] ?></div>
                    <div class="stat-description">إجمالي طلبات تسجيل الطلاب</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">المدرسين</div>
                        <div class="stat-icon orange">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $stats['total_professeurs'] ?></div>
                    <div class="stat-description">إجمالي المدرسين المسجلين</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">الطلاب</div>
                        <div class="stat-icon red">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $stats['total_etudiants'] ?></div>
                    <div class="stat-description">إجمالي الطلاب المسجلين</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal" id="editProfileModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-edit"></i> تعديل الملف الشخصي</h2>
            <button class="close-modal" id="closeEditProfileModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="post" action="">
                <div class="form-group">
                    <label class="form-label" for="nom">الاسم العائلي</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($admin['nom']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="prenom">الاسم الشخصي</label>
                    <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($admin['prenom']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="telephone">رقم الهاتف</label>
                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($admin['telephone'] ?? '') ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="profile-action-btn btn-secondary" id="cancelEditProfile">إلغاء</button>
                    <button type="submit" name="update_profile" class="profile-action-btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal" id="changePasswordModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-key"></i> تغيير كلمة المرور</h2>
            <button class="close-modal" id="closeChangePasswordModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="post" action="">
                <div class="form-group">
                    <label class="form-label" for="current_password">كلمة المرور الحالية</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_password">كلمة المرور الجديدة</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <div class="form-text">يجب أن تتكون كلمة المرور من 8 أحرف على الأقل</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">تأكيد كلمة المرور الجديدة</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="profile-action-btn btn-secondary" id="cancelChangePassword">إلغاء</button>
                    <button type="submit" name="change_password" class="profile-action-btn btn-primary">تغيير كلمة المرور</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Avatar Modal -->
<div class="modal" id="changeAvatarModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-camera"></i> تغيير الصورة الشخصية</h2>
            <button class="close-modal" id="closeChangeAvatarModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="post" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label" for="avatar">اختر صورة جديدة</label>
                    <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*" required>
                    <div class="form-text">يجب أن يكون حجم الصورة أقل من 2 ميغابايت</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="profile-action-btn btn-secondary" id="cancelChangeAvatar">إلغاء</button>
                    <button type="submit" name="update_avatar" class="profile-action-btn btn-primary">تحديث الصورة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Sidebar Toggle
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });

    document.getElementById('sidebarClose').addEventListener('click', function() {
        document.getElementById('sidebar').classList.remove('show');
    });

    // Tabs
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabId = tab.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to current tab and content
            tab.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });

    // Edit Profile Modal
    const editProfileBtn = document.getElementById('editProfileBtn');
    const editProfileModal = document.getElementById('editProfileModal');
    const closeEditProfileModal = document.getElementById('closeEditProfileModal');
    const cancelEditProfile = document.getElementById('cancelEditProfile');

    editProfileBtn.addEventListener('click', () => {
        editProfileModal.classList.add('show');
    });

    closeEditProfileModal.addEventListener('click', () => {
        editProfileModal.classList.remove('show');
    });

    cancelEditProfile.addEventListener('click', () => {
        editProfileModal.classList.remove('show');
    });

    // Change Password Modal
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const changePasswordModal = document.getElementById('changePasswordModal');
    const closeChangePasswordModal = document.getElementById('closeChangePasswordModal');
    const cancelChangePassword = document.getElementById('cancelChangePassword');

    changePasswordBtn.addEventListener('click', () => {
        changePasswordModal.classList.add('show');
    });

    closeChangePasswordModal.addEventListener('click', () => {
        changePasswordModal.classList.remove('show');
    });

    cancelChangePassword.addEventListener('click', () => {
        changePasswordModal.classList.remove('show');
    });

    // Change Avatar Modal
    const changeAvatarBtn = document.getElementById('changeAvatarBtn');
    const changeAvatarModal = document.getElementById('changeAvatarModal');
    const closeChangeAvatarModal = document.getElementById('closeChangeAvatarModal');
    const cancelChangeAvatar = document.getElementById('cancelChangeAvatar');

    changeAvatarBtn.addEventListener('click', () => {
        changeAvatarModal.classList.add('show');
    });

    closeChangeAvatarModal.addEventListener('click', () => {
        changeAvatarModal.classList.remove('show');
    });

    cancelChangeAvatar.addEventListener('click', () => {
        changeAvatarModal.classList.remove('show');
    });

    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === editProfileModal) {
            editProfileModal.classList.remove('show');
        }
        if (e.target === changePasswordModal) {
            changePasswordModal.classList.remove('show');
        }
        if (e.target === changeAvatarModal) {
            changeAvatarModal.classList.remove('show');
        }
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
</script>

</body>
</html>

