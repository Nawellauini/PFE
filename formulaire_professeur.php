<?php
require 'db_base.php';


$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $conn->real_escape_string($_POST['nom']);
    $prenom = $conn->real_escape_string($_POST['prenom']);
    $email = $conn->real_escape_string($_POST['email']);
    $telephone = $conn->real_escape_string($_POST['telephone']);
    $matiere = $conn->real_escape_string($_POST['matiere']);
    $experience = $conn->real_escape_string($_POST['experience']);
    $message = $conn->real_escape_string($_POST['message']);
    $mot_de_passe = $conn->real_escape_string($_POST['mot_de_passe']);

    // Générer le login
    $login = strtolower(substr($prenom, 0, 1) . $nom);
    $login = preg_replace('/[^a-z0-9]/', '', $login);
    
    // Vérifier si le login existe déjà
    $check_login = $conn->prepare("SELECT COUNT(*) as count FROM candidatures_professeurs WHERE login = ?");
    $check_login->bind_param("s", $login);
    $check_login->execute();
    $result = $check_login->get_result();
    $row = $result->fetch_assoc();
    
    // Si le login existe, ajouter un numéro
    if ($row['count'] > 0) {
        $i = 1;
        $original_login = $login;
        do {
            $login = $original_login . $i;
            $check_login->bind_param("s", $login);
            $check_login->execute();
            $result = $check_login->get_result();
            $row = $result->fetch_assoc();
            $i++;
        } while ($row['count'] > 0);
    }

    $sql = "INSERT INTO candidatures_professeurs (nom, prenom, email, telephone, matiere, experience, message, mot_de_passe, login) 
            VALUES ('$nom', '$prenom', '$email', '$telephone', '$matiere', '$experience', '$message', '$mot_de_passe', '$login')";
    
    if ($conn->query($sql) === TRUE) {
        $success_message = "تم إرسال طلبك بنجاح! سنتواصل معك قريبًا.";
    } else {
        $error_message = "خطأ: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب الانضمام كمدرس | مدرستنا</title>
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
        
        /* Header */
        .header {
            background-color: var(--white-color);
            box-shadow: var(--box-shadow);
            padding: 15px 0;
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 100;
            transition: all var(--transition-speed) ease;
        }
        
        .header.scrolled {
            padding: 10px 0;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .header-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            transition: all var(--transition-speed) ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .logo-icon {
            font-size: 28px;
            color: var(--primary-color);
        }
        
        .nav-menu {
            display: flex;
            gap: 20px;
        }
        
        .nav-link {
            color: var(--dark-color);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition-speed) ease;
            padding: 8px 12px;
            border-radius: 6px;
            position: relative;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        
        .nav-link:hover::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary-color);
            border-radius: 2px;
            animation: navLinkAnimation 0.3s ease;
        }
        
        @keyframes navLinkAnimation {
            from { width: 0; }
            to { width: 100%; }
        }
        
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--dark-color);
            cursor: pointer;
            transition: all var(--transition-speed) ease;
        }
        
        .mobile-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 160px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTI4MCAxNDAiIHByZXNlcnZlQXNwZWN0UmF0aW89Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0iI2ZmZmZmZiI+PHBhdGggZD0iTTEyODAgMTQwVjBTOTkzLjQ2IDE0MCA2NDAgMTM5IDAgMCAwIDB2MTQweiIvPjwvZz48L3N2Zz4=');
            background-size: 100% 100px;
            background-position: bottom;
            background-repeat: no-repeat;
            z-index: 1;
            opacity: 0.3;
        }
        
        .hero-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: fadeInDown 1s ease;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hero-subtitle {
            font-size: 20px;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.9;
            animation: fadeIn 1s ease 0.3s both;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .hero-btn {
            display: inline-block;
            background-color: white;
            color: var(--info-color);
            padding: 14px 28px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            transition: all var(--transition-speed) ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease 0.6s both;
        }
        
        .hero-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        
        .hero-btn i {
            margin-left: 8px;
            transition: transform 0.3s ease;
        }
        
        .hero-btn:hover i {
            transform: translateX(-5px);
        }
        
        /* Main Container */
        .main-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 0;
        }
        
        /* Form Container */
        .form-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 60px;
            position: relative;
            z-index: 10;
        }
        
        /* Form Header */
        .form-header {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .form-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTI4MCAxNDAiIHByZXNlcnZlQXNwZWN0UmF0aW89Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0iI2ZmZmZmZiI+PHBhdGggZD0iTTEyODAgMTQwVjBTOTkzLjQ2IDE0MCA2NDAgMTM5IDAgMCAwIDB2MTQweiIvPjwvZz48L3N2Zz4=');
            background-size: 100% 50px;
            background-position: bottom;
            background-repeat: no-repeat;
            z-index: 1;
            opacity: 0.1;
        }
        
        .form-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }
        
        .form-subtitle {
            font-size: 18px;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        /* Form Body */
        .form-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 16px;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
            background-color: #f9fafb;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--info-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background-color: white;
        }
        
        .form-select {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 15px center;
            background-size: 15px;
            transition: all var(--transition-speed) ease;
            background-color: #f9fafb;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--info-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background-color: white;
        }
        
        .form-textarea {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            min-height: 150px;
            resize: vertical;
            transition: all var(--transition-speed) ease;
            background-color: #f9fafb;
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: var(--info-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background-color: white;
        }
        
        .input-group {
            display: flex;
            gap: 20px;
        }
        
        .input-group .form-group {
            flex: 1;
        }
        
        .error-message {
            color: var(--danger-color);
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        
        .form-input.error, .form-select.error, .form-textarea.error {
            border-color: var(--danger-color);
            background-color: var(--danger-light);
        }
        
        .form-input.error + .error-message,
        .form-select.error + .error-message,
        .form-textarea.error + .error-message {
            display: block;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        /* Form Footer */
        .form-footer {
            padding: 25px 40px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            background-color: #f8fafc;
        }
        
        .btn {
            padding: 14px 28px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
        
        /* Success Message */
        .alert {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        .alert-success {
            background: linear-gradient(135deg, var(--success-light) 0%, rgba(16, 185, 129, 0.1) 100%);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }
        
        .alert-error {
            background: linear-gradient(135deg, var(--danger-light) 0%, rgba(239, 68, 68, 0.1) 100%);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        .alert-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTI4MCAxNDAiIHByZXNlcnZlQXNwZWN0UmF0aW89Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0iI2ZmZmZmZiI+PHBhdGggZD0iTTAgMHYxNDBoMTI4MFYweiIvPjwvZz48L3N2Zz4=');
            background-size: 100% 100px;
            background-position: top;
            background-repeat: no-repeat;
            z-index: 1;
        }
        
        .features-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h2 {
            font-size: 36px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--info-color), var(--info-dark));
            border-radius: 2px;
        }
        
        .section-title p {
            font-size: 18px;
            color: var(--gray-color);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        
        .feature-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            transition: all var(--transition-speed) ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to left, var(--info-color), var(--info-dark));
            transition: all var(--transition-speed) ease;
            z-index: -1;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-card:hover::before {
            height: 100%;
            opacity: 0.05;
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--info-light) 0%, rgba(99, 102, 241, 0.1) 100%);
            color: var(--info-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 25px;
            transition: all var(--transition-speed) ease;
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.1);
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            background: linear-gradient(135deg, var(--info-color) 0%, var(--info-dark) 100%);
            color: white;
        }
        
        .feature-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .feature-description {
            font-size: 16px;
            color: var(--gray-color);
            line-height: 1.6;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: white;
            padding: 80px 0 30px;
            position: relative;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTI4MCAxNDAiIHByZXNlcnZlQXNwZWN0UmF0aW89Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0iI2Y4ZmFmYyI+PHBhdGggZD0iTTAgMHYxNDBoMTI4MFYweiIvPjwvZz48L3N2Zz4=');
            background-size: 100% 100px;
            background-position: top;
            background-repeat: no-repeat;
            z-index: 1;
        }
        
        .footer-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 50px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }
        
        .footer-about {
            font-size: 16px;
            color: #d1d5db;
            margin-bottom: 25px;
            line-height: 1.7;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all var(--transition-speed) ease;
            text-decoration: none;
        }
        
        .social-link:hover {
            background-color: var(--info-color);
            transform: translateY(-5px);
        }
        
        .footer-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 25px;
            color: white;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(to right, var(--info-color), var(--info-dark));
            border-radius: 2px;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-link {
            margin-bottom: 12px;
        }
        
        .footer-link a {
            color: #d1d5db;
            text-decoration: none;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
            display: inline-flex;
            align-items: center;
        }
        
        .footer-link a::before {
            content: '\f105';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-left: 8px;
            color: var(--info-color);
            transition: all var(--transition-speed) ease;
        }
        
        .footer-link a:hover {
            color: white;
            transform: translateX(-5px);
        }
        
        .footer-link a:hover::before {
            color: white;
        }
        
        .contact-info {
            list-style: none;
        }
        
        .contact-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 16px;
            color: #d1d5db;
        }
        
        .contact-icon {
            color: var(--info-color);
            font-size: 18px;
            margin-top: 3px;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            text-align: center;
            font-size: 16px;
            color: #d1d5db;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .hero-title {
                font-size: 40px;
            }
            
            .hero-subtitle {
                font-size: 18px;
            }
        }
        
        @media (max-width: 992px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }
            
            .hero-title {
                font-size: 36px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 32px;
            }
            
            .hero-subtitle {
                font-size: 16px;
            }
            
            .form-body {
                padding: 30px 20px;
            }
            
            .input-group {
                flex-direction: column;
                gap: 0;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                right: 0;
                left: 0;
                background-color: white;
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                z-index: 100;
            }
            
            .nav-menu.show {
                display: flex;
            }
        }
        
        @media (max-width: 576px) {
            .hero-title {
                font-size: 28px;
            }
            
            .hero-btn {
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .form-title {
                font-size: 24px;
            }
            
            .form-subtitle {
                font-size: 16px;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 14px;
            }
        }
        
        /* Animations */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .float {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header" id="header">
    <div class="header-container">
        <a href="#" class="logo">
            <i class="fas fa-school logo-icon"></i>
            <span>مدرستنا</span>
        </a>
        <button class="mobile-toggle" id="mobileToggle">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="nav-menu" id="navMenu">
            <a href="#" class="nav-link">الرئيسية</a>
            <a href="#" class="nav-link">عن المدرسة</a>
            <a href="#" class="nav-link">البرامج التعليمية</a>
            <a href="#" class="nav-link active">انضم إلينا</a>
            <a href="#" class="nav-link">اتصل بنا</a>
        </nav>
    </div>
</header>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-container">
        <h1 class="hero-title">انضم إلى فريق التدريس المتميز</h1>
        <p class="hero-subtitle">نبحث عن أفضل المعلمين المؤهلين والمتحمسين للانضمام إلى فريقنا وتقديم تعليم عالي الجودة لطلابنا</p>
        <a href="#application-form" class="hero-btn">
            قدم طلبك الآن
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>
</section>

<!-- Main Container -->
<div class="main-container">
    <!-- Application Form -->
    <div class="form-container" id="application-form">
        <div class="form-header">
            <h2 class="form-title">طلب الانضمام كمدرس</h2>
            <p class="form-subtitle">يرجى ملء النموذج التالي للتقديم على وظيفة مدرس في مدرستنا</p>
        </div>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <div class="alert-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <div class="alert-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <form id="applicationForm" method="POST" action="">
            <div class="form-body">
                <div class="input-group">
                    <div class="form-group">
                        <label for="nom" class="form-label">الاسم العائلي</label>
                        <input type="text" id="nom" name="nom" class="form-input" required>
                        <div class="error-message">يرجى إدخال الاسم العائلي</div>
                    </div>
                    <div class="form-group">
                        <label for="prenom" class="form-label">الاسم الشخصي</label>
                        <input type="text" id="prenom" name="prenom" class="form-input" required>
                        <div class="error-message">يرجى إدخال الاسم الشخصي</div>
                    </div>
                </div>
                
                <div class="input-group">
                    <div class="form-group">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                        <div class="error-message">يرجى إدخال بريد إلكتروني صحيح</div>
                    </div>
                    <div class="form-group">
                        <label for="telephone" class="form-label">رقم الهاتف</label>
                        <input type="tel" id="telephone" name="telephone" class="form-input" required>
                        <div class="error-message">يرجى إدخال رقم هاتف صحيح</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="mot_de_passe" class="form-label">كلمة المرور</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-input" required minlength="8">
                    <div class="error-message">يجب أن تتكون كلمة المرور من 8 أحرف على الأقل</div>
                </div>
                
                <div class="form-group">
                    <label for="matiere" class="form-label">المادة التي ترغب في تدريسها</label>
                    <select id="matiere" name="matiere" class="form-select" required>
                        <option value="" disabled selected>اختر المادة</option>
                        <option value="الرياضيات">الرياضيات</option>
                        <option value="العلوم">العلوم</option>
                        <option value="اللغة العربية">اللغة العربية</option>
                        <option value="اللغة الفرنسية">اللغة الفرنسية</option>
                        <option value="اللغة الإنجليزية">اللغة الإنجليزية</option>
                        <option value="التاريخ والجغرافيا">التاريخ والجغرافيا</option>
                        <option value="التربية الإسلامية">التربية الإسلامية</option>
                        <option value="التربية البدنية">التربية البدنية</option>
                        <option value="الفنون">الفنون</option>
                        <option value="المعلوماتية">المعلوماتية</option>
                        <option value="أخرى">أخرى</option>
                    </select>
                    <div class="error-message">يرجى اختيار المادة</div>
                </div>
                
                <div class="form-group">
                    <label for="experience" class="form-label">سنوات الخبرة</label>
                    <select id="experience" name="experience" class="form-select" required>
                        <option value="" disabled selected>اختر سنوات الخبرة</option>
                        <option value="أقل من سنة">أقل من سنة</option>
                        <option value="1-3 سنوات">1-3 سنوات</option>
                        <option value="3-5 سنوات">3-5 سنوات</option>
                        <option value="5-10 سنوات">5-10 سنوات</option>
                        <option value="أكثر من 10 سنوات">أكثر من 10 سنوات</option>
                    </select>
                    <div class="error-message">يرجى اختيار سنوات الخبرة</div>
                </div>
                
                <div class="form-group">
                    <label for="message" class="form-label">لماذا ترغب في الانضمام إلى فريقنا؟</label>
                    <textarea id="message" name="message" class="form-textarea" rows="5" placeholder="اكتب هنا عن خبراتك ومؤهلاتك ولماذا ترغب في العمل معنا..." required></textarea>
                    <div class="error-message">يرجى كتابة رسالة</div>
                </div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> إرسال الطلب
                </button>
            </div>
        </form>
    </div>
    
    <!-- Features Section -->
    <section class="features">
        <div class="features-container">
            <div class="section-title">
                <h2>لماذا تنضم إلى فريقنا؟</h2>
                <p>نوفر بيئة عمل محفزة ومتميزة تساعدك على تطوير مهاراتك وتحقيق طموحاتك المهنية</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">فرص التطور المهني</h3>
                    <p class="feature-description">نقدم برامج تدريبية متنوعة وفرص للتطوير المهني المستمر لمساعدتك على تحسين مهاراتك التعليمية واكتساب خبرات جديدة.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">بيئة عمل داعمة</h3>
                    <p class="feature-description">نحرص على توفير بيئة عمل إيجابية ومحفزة تشجع على التعاون والإبداع وتبادل الخبرات بين أعضاء الفريق التعليمي.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3 class="feature-title">تكنولوجيا حديثة</h3>
                    <p class="feature-description">نوفر أحدث التقنيات والأدوات التعليمية لمساعدتك على تقديم تجربة تعليمية متميزة وتفاعلية للطلاب.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-medal"></i>
                    </div>
                    <h3 class="feature-title">تقدير الكفاءات</h3>
                    <p class="feature-description">نؤمن بأهمية تقدير الكفاءات والإنجازات، ونقدم حوافز ومكافآت للمعلمين المتميزين الذين يساهمون في تحقيق رؤية المدرسة.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="feature-title">طلاب متحمسون</h3>
                    <p class="feature-description">ستعمل مع طلاب متحمسين للتعلم في بيئة تعليمية متميزة تشجع على الإبداع والتفكير النقدي وتنمية المهارات.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 class="feature-title">استقرار وظيفي</h3>
                    <p class="feature-description">نوفر استقرارًا وظيفيًا ومزايا تنافسية لأعضاء فريقنا، مع فرص للترقي الوظيفي وتولي مناصب قيادية في المستقبل.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div>
                <div class="footer-logo">
                    <i class="fas fa-school"></i>
                    <span>مدرستنا</span>
                </div>
                <p class="footer-about">مدرستنا هي مؤسسة تعليمية رائدة تسعى لتوفير تعليم متميز يجمع بين الأصالة والمعاصرة، ويهدف إلى بناء شخصية متكاملة للطالب تجمع بين المعرفة والقيم والمهارات اللازمة للنجاح في الحياة.</p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div>
                <h3 class="footer-title">روابط سريعة</h3>
                <ul class="footer-links">
                    <li class="footer-link"><a href="#">الرئيسية</a></li>
                    <li class="footer-link"><a href="#">عن المدرسة</a></li>
                    <li class="footer-link"><a href="#">البرامج التعليمية</a></li>
                    <li class="footer-link"><a href="#">الأنشطة</a></li>
                    <li class="footer-link"><a href="#">التسجيل</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="footer-title">الخدمات</h3>
                <ul class="footer-links">
                    <li class="footer-link"><a href="#">التعليم الأساسي</a></li>
                    <li class="footer-link"><a href="#">الأنشطة اللاصفية</a></li>
                    <li class="footer-link"><a href="#">الدعم النفسي</a></li>
                    <li class="footer-link"><a href="#">النقل المدرسي</a></li>
                    <li class="footer-link"><a href="#">التغذية المدرسية</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="footer-title">اتصل بنا</h3>
                <ul class="contact-info">
                    <li class="contact-item">
                        <i class="fas fa-map-marker-alt contact-icon"></i>
                        <span>123 شارع المدارس، المدينة</span>
                    </li>
                    <li class="contact-item">
                        <i class="fas fa-phone-alt contact-icon"></i>
                        <span>+123 456 7890</span>
                    </li>
                    <li class="contact-item">
                        <i class="fas fa-envelope contact-icon"></i>
                        <span>info@madrasatuna.com</span>
                    </li>
                    <li class="contact-item">
                        <i class="fas fa-clock contact-icon"></i>
                        <span>الأحد - الخميس: 8:00 - 16:00</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2023 مدرستنا. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</footer>

<script>
    // Header Scroll Effect
    window.addEventListener('scroll', function() {
        const header = document.getElementById('header');
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
    
    // Mobile Menu Toggle
    document.getElementById('mobileToggle').addEventListener('click', function() {
        document.getElementById('navMenu').classList.toggle('show');
    });
    
    // Form Validation
    const form = document.getElementById('applicationForm');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate Name
        const nom = document.getElementById('nom');
        if (nom.value.trim() === '') {
            nom.classList.add('error');
            isValid = false;
        } else {
            nom.classList.remove('error');
        }
        
        // Validate Prenom
        const prenom = document.getElementById('prenom');
        if (prenom.value.trim() === '') {
            prenom.classList.add('error');
            isValid = false;
        } else {
            prenom.classList.remove('error');
        }
        
        // Validate Email
        const email = document.getElementById('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value)) {
            email.classList.add('error');
            isValid = false;
        } else {
            email.classList.remove('error');
        }
        
        // Validate Phone
        const telephone = document.getElementById('telephone');
        if (telephone.value.trim() === '') {
            telephone.classList.add('error');
            isValid = false;
        } else {
            telephone.classList.remove('error');
        }
        
        // Validate Subject
        const matiere = document.getElementById('matiere');
        if (matiere.value === '') {
            matiere.classList.add('error');
            isValid = false;
        } else {
            matiere.classList.remove('error');
        }
        
        // Validate Experience
        const experience = document.getElementById('experience');
        if (experience.value === '') {
            experience.classList.add('error');
            isValid = false;
        } else {
            experience.classList.remove('error');
        }
        
        // Validate Message
        const message = document.getElementById('message');
        if (message.value.trim() === '') {
            message.classList.add('error');
            isValid = false;
        } else {
            message.classList.remove('error');
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Animate elements when they come into view
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.feature-card, .section-title, .form-container');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 100) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Set initial styles for animation
    document.querySelectorAll('.feature-card, .section-title, .form-container').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });
    
    // Run animation on scroll
    window.addEventListener('scroll', animateOnScroll);
    
    // Run animation on page load
    window.addEventListener('load', animateOnScroll);
</script>

</body>
</html>