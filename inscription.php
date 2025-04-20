<?php
include 'db_config.php';

// Vérifier si les colonnes login et mot_de_passe existent dans la table demandes_inscription
$check_columns = $conn->query("SHOW COLUMNS FROM demandes_inscription LIKE 'login'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE demandes_inscription ADD COLUMN login VARCHAR(50) NULL");
}

$check_columns = $conn->query("SHOW COLUMNS FROM demandes_inscription LIKE 'mot_de_passe'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE demandes_inscription ADD COLUMN mot_de_passe VARCHAR(255) NULL");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التسجيل في مدرستنا</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --secondary-color: #10b981;
            --secondary-dark: #059669;
            --danger-color: #ef4444;
            --danger-dark: #dc2626;
            --warning-color: #f59e0b;
            --warning-dark: #d97706;
            --info-color: #6366f1;
            --info-dark: #4f46e5;
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
        
        /* Header */
        .header {
            background-color: var(--white-color);
            box-shadow: var(--box-shadow);
            padding: 20px 0;
            position: relative;
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
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--dark-color);
            cursor: pointer;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .hero-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 18px;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-btn {
            display: inline-block;
            background-color: white;
            color: var(--primary-color);
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all var(--transition-speed) ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .hero-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Main Container */
        .main-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 0;
        }
        
        /* Form Container */
        .form-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        
        /* Form Header */
        .form-header {
            background-color: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .form-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        
        /* Form Steps */
        .form-steps {
            display: flex;
            justify-content: space-between;
            padding: 20px 40px;
            background-color: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            right: 50%;
            width: 100%;
            height: 2px;
            background-color: #e5e7eb;
            z-index: 1;
        }
        
        .step.active:not(:last-child)::after,
        .step.completed:not(:last-child)::after {
            background-color: var(--primary-color);
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e5e7eb;
            color: var(--gray-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
            transition: all var(--transition-speed) ease;
        }
        
        .step.active .step-number {
            background-color: var(--primary-color);
            color: white;
        }
        
        .step.completed .step-number {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .step-label {
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-color);
            transition: all var(--transition-speed) ease;
        }
        
        .step.active .step-label {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .step.completed .step-label {
            color: var(--secondary-color);
        }
        
        /* Form Body */
        .form-body {
            padding: 40px;
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 15px center;
            background-size: 15px;
            transition: all var(--transition-speed) ease;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            min-height: 120px;
            resize: vertical;
            transition: all var(--transition-speed) ease;
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .input-group {
            display: flex;
            gap: 15px;
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
        }
        
        .form-input.error + .error-message,
        .form-select.error + .error-message,
        .form-textarea.error + .error-message {
            display: block;
        }
        
        /* Form Footer */
        .form-footer {
            padding: 20px 40px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #e5e7eb;
            color: var(--dark-color);
        }
        
        .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-prev {
            background-color: transparent;
            border: 1px solid #e5e7eb;
            color: var(--dark-color);
        }
        
        .btn-prev:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-next {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-next:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-submit {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-submit:hover {
            background-color: var(--secondary-dark);
        }
        
        /* Features Section */
        .features {
            padding: 60px 0;
            background-color: #f8fafc;
        }
        
        .features-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-title h2 {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .section-title p {
            font-size: 16px;
            color: var(--gray-color);
            max-width: 600px;
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
            box-shadow: var(--box-shadow);
            padding: 30px;
            transition: all var(--transition-speed) ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .feature-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .feature-description {
            font-size: 14px;
            color: var(--gray-color);
            line-height: 1.6;
        }
        
        /* Footer */
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }
        
        .footer-about {
            font-size: 14px;
            color: #d1d5db;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-link {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
        }
        
        .social-link:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .footer-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: white;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-link {
            margin-bottom: 10px;
        }
        
        .footer-link a {
            color: #d1d5db;
            text-decoration: none;
            font-size: 14px;
            transition: all var(--transition-speed) ease;
        }
        
        .footer-link a:hover {
            color: white;
        }
        
        .contact-info {
            list-style: none;
        }
        
        .contact-item {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #d1d5db;
        }
        
        .contact-icon {
            color: var(--primary-color);
            font-size: 16px;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #d1d5db;
        }
        
        /* Success Message */
        .success-message {
            display: none;
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* Password Toggle */
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray-color);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 28px;
            }
            
            .hero-subtitle {
                font-size: 16px;
            }
            
            .form-steps {
                padding: 15px;
            }
            
            .step-label {
                font-size: 12px;
            }
            
            .form-body {
                padding: 20px;
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
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                z-index: 100;
            }
            
            .nav-menu.show {
                display: flex;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-container">
        <a href="#" class="logo">
            <i class="fas fa-school logo-icon"></i>
            <span>مدرستنا</span>
        </a>
        <button class="mobile-toggle" id="mobileToggle">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="nav-menu" id="navMenu">
            <a href="#" class="nav-link active">الرئيسية</a>
            <a href="#" class="nav-link">عن المدرسة</a>
            <a href="#" class="nav-link">البرامج التعليمية</a>
            <a href="#" class="nav-link">الأنشطة</a>
            <a href="#" class="nav-link">اتصل بنا</a>
        </nav>
    </div>
</header>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-container">
        <h1 class="hero-title">انضم إلى عائلة مدرستنا</h1>
        <p class="hero-subtitle">نوفر بيئة تعليمية متميزة تساعد الطلاب على تحقيق أقصى إمكاناتهم وبناء مستقبل مشرق</p>
        <a href="#registration-form" class="hero-btn">سجل الآن</a>
    </div>
</section>

<!-- Main Container -->
<div class="main-container">
    <!-- Registration Form -->
    <div class="form-container" id="registration-form">
        <div class="form-header">
            <h2 class="form-title">طلب التسجيل</h2>
            <p class="form-subtitle">يرجى ملء النموذج التالي للتقديم في مدرستنا</p>
        </div>
        
        <div class="form-steps">
            <div class="step active" id="step1-indicator">
                <div class="step-number">1</div>
                <div class="step-label">المعلومات الشخصية</div>
            </div>
            <div class="step" id="step2-indicator">
                <div class="step-number">2</div>
                <div class="step-label">المعلومات الدراسية</div>
            </div>
            <div class="step" id="step3-indicator">
                <div class="step-number">3</div>
                <div class="step-label">معلومات الاتصال</div>
            </div>
            <div class="step" id="step4-indicator">
                <div class="step-number">4</div>
                <div class="step-label">معلومات الحساب</div>
            </div>
        </div>
        
        <div class="success-message" id="successMessage">
            <i class="fas fa-check-circle"></i> تم إرسال طلب التسجيل بنجاح! سنتواصل معك قريبًا.
        </div>
        
        <form id="registrationForm" action="traitement_inscription.php" method="POST">
            <div class="form-body">
                <!-- Step 1: Personal Information -->
                <div class="form-step active" id="step1">
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
                    
                    <div class="form-group">
                        <label for="age" class="form-label">العمر</label>
                        <input type="number" id="age" name="age" min="3" max="18" class="form-input" required>
                        <div class="error-message">يرجى إدخال عمر صحيح (3-18)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_naissance" class="form-label">تاريخ الميلاد</label>
                        <input type="date" id="date_naissance" name="date_naissance" class="form-input">
                    </div>
                </div>
                
                <!-- Step 2: Academic Information -->
                <div class="form-step" id="step2">
                    <div class="form-group">
                        <label for="classe_demande" class="form-label">الصف المطلوب</label>
                        <select id="classe_demande" name="classe_demande" class="form-select" required>
                            <option value="" disabled selected>اختر الصف</option>
                            <?php
                            // Connexion à la base de données (à adapter selon ta config)
                            $conn = new mysqli("localhost", "root", "", "u504721134_formation");
                            if ($conn->connect_error) {
                                die("Erreur de connexion : " . $conn->connect_error);
                            }

                            // Requête pour récupérer les classes
                            $sql = "SELECT id_classe, nom_classe FROM classes";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($row['id_classe']) . '">' . htmlspecialchars($row['nom_classe']) . '</option>';
                                }
                            } else {
                                echo '<option disabled>Aucune classe trouvée</option>';
                            }
                            ?>
                        </select>
                        <div class="error-message">يرجى اختيار الصف</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="ecole_precedente" class="form-label">المدرسة السابقة (اختياري)</label>
                        <input type="text" id="ecole_precedente" name="ecole_precedente" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="niveau_precedent" class="form-label">المستوى الدراسي السابق (اختياري)</label>
                        <input type="text" id="niveau_precedent" name="niveau_precedent" class="form-input">
                    </div>
                </div>
                
                <!-- Step 3: Contact Information -->
                <div class="form-step" id="step3">
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
                    
                    <div class="form-group">
                        <label for="message" class="form-label">رسالة (اختياري)</label>
                        <textarea id="message" name="message" class="form-textarea" rows="4" placeholder="أي معلومات إضافية ترغب في مشاركتها معنا"></textarea>
                    </div>
                </div>
                
                <!-- Step 4: Account Information (New) -->
                <div class="form-step" id="step4">
                    <div class="form-group">
                        <label for="mot_de_passe" class="form-label">كلمة المرور</label>
                        <div class="password-container">
                            <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-input" required minlength="8">
                            <i class="fas fa-eye toggle-password" onclick="togglePassword('mot_de_passe')"></i>
                        </div>
                        <div class="error-message">يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل</div>
                        <small class="form-text text-muted">يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_mot_de_passe" class="form-label">تأكيد كلمة المرور</label>
                        <div class="password-container">
                            <input type="password" id="confirm_mot_de_passe" name="confirm_mot_de_passe" class="form-input" required minlength="8">
                            <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_mot_de_passe')"></i>
                        </div>
                        <div class="error-message">كلمات المرور غير متطابقة</div>
                    </div>
                    
                    <div class="form-group">
                        <p class="form-text text-muted">
                            <i class="fas fa-info-circle"></i>
                            سيتم إنشاء اسم المستخدم تلقائيًا بناءً على اسمك. يمكنك استخدامه لتسجيل الدخول بعد قبول طلبك.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="form-footer">
                <button type="button" id="prevBtn" class="btn btn-prev" style="display: none;">
                    <i class="fas fa-arrow-right"></i> السابق
                </button>
                <button type="button" id="nextBtn" class="btn btn-next">
                    التالي <i class="fas fa-arrow-left"></i>
                </button>
                <button type="submit" id="submitBtn" class="btn btn-submit" style="display: none;">
                    <i class="fas fa-paper-plane"></i> إرسال الطلب
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Features Section -->
<section class="features">
    <div class="features-container">
        <div class="section-title">
            <h2>لماذا تختار مدرستنا؟</h2>
            <p>نقدم تجربة تعليمية فريدة تجمع بين الأصالة والمعاصرة لبناء جيل واعٍ ومبدع</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3 class="feature-title">جودة التعليم</h3>
                <p class="feature-description">نوفر مناهج تعليمية متطورة تتماشى مع المعايير العالمية وتلبي احتياجات الطلاب المختلفة.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3 class="feature-title">كادر تعليمي متميز</h3>
                <p class="feature-description">يضم فريقنا نخبة من المعلمين ذوي الخبرة والكفاءة العالية في مجال التربية والتعليم.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-laptop"></i>
                </div>
                <h3 class="feature-title">تكنولوجيا حديثة</h3>
                <p class="feature-description">نستخدم أحدث التقنيات التعليمية لتعزيز تجربة التعلم وتنمية مهارات الطلاب الرقمية.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-futbol"></i>
                </div>
                <h3 class="feature-title">أنشطة متنوعة</h3>
                <p class="feature-description">نقدم مجموعة واسعة من الأنشطة الرياضية والفنية والثقافية لتنمية مواهب الطلاب المختلفة.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="feature-title">بيئة اجتماعية داعمة</h3>
                <p class="feature-description">نحرص على توفير بيئة آمنة ومحفزة تشجع على التعاون والاحترام المتبادل بين جميع أفراد المجتمع المدرسي.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <h3 class="feature-title">تعليم اللغات</h3>
                <p class="feature-description">نهتم بتعليم اللغات المختلفة مثل العربية والفرنسية والإنجليزية لتمكين الطلاب من التواصل بفعالية في عالم متعدد الثقافات.</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div>
                <div class="footer-logo">
                    <i class="fas fa-school"></i>
                    <span>مدرستنا</span>
                </div>
                <p class="footer-about">مدرستنا هي مؤسسة تعليمية رائدة تسعى لتوفير تعليم متميز يجمع بين الأصالة والمعاصرة، ويهدف إلى بناء شخصية متكاملة للطالب.</p>
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
    // Mobile Menu Toggle
    document.getElementById('mobileToggle').addEventListener('click', function() {
        document.getElementById('navMenu').classList.toggle('show');
    });
    
    // Multi-step Form
    let currentStep = 1;
    const totalSteps = 4; // Updated to 4 steps
    
    // DOM Elements
    const form = document.getElementById('registrationForm');
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const step4 = document.getElementById('step4'); // New step
    const step1Indicator = document.getElementById('step1-indicator');
    const step2Indicator = document.getElementById('step2-indicator');
    const step3Indicator = document.getElementById('step3-indicator');
    const step4Indicator = document.getElementById('step4-indicator'); // New indicator
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const successMessage = document.getElementById('successMessage');
    
    // Form Navigation
    function showStep(stepNumber) {
        // Hide all steps
        step1.classList.remove('active');
        step2.classList.remove('active');
        step3.classList.remove('active');
        step4.classList.remove('active'); // New step
        
        // Update indicators
        step1Indicator.classList.remove('active', 'completed');
        step2Indicator.classList.remove('active', 'completed');
        step3Indicator.classList.remove('active', 'completed');
        step4Indicator.classList.remove('active', 'completed'); // New indicator
        
        // Show current step
        if (stepNumber === 1) {
            step1.classList.add('active');
            step1Indicator.classList.add('active');
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        } else if (stepNumber === 2) {
            step2.classList.add('active');
            step2Indicator.classList.add('active');
            step1Indicator.classList.add('completed');
            prevBtn.style.display = 'block';
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        } else if (stepNumber === 3) {
            step3.classList.add('active');
            step3Indicator.classList.add('active');
            step1Indicator.classList.add('completed');
            step2Indicator.classList.add('completed');
            prevBtn.style.display = 'block';
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        } else if (stepNumber === 4) { // New step
            step4.classList.add('active');
            step4Indicator.classList.add('active');
            step1Indicator.classList.add('completed');
            step2Indicator.classList.add('completed');
            step3Indicator.classList.add('completed');
            prevBtn.style.display = 'block';
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'block';
        }
        
        currentStep = stepNumber;
    }
    
    // Validate current step
    function validateStep(stepNumber) {
        let isValid = true;
        
        if (stepNumber === 1) {
            // Validate step 1 fields
            const nom = document.getElementById('nom');
            const prenom = document.getElementById('prenom');
            const age = document.getElementById('age');
            
            if (nom.value.trim() === '') {
                nom.classList.add('error');
                isValid = false;
            } else {
                nom.classList.remove('error');
            }
            
            if (prenom.value.trim() === '') {
                prenom.classList.add('error');
                isValid = false;
            } else {
                prenom.classList.remove('error');
            }
            
            if (age.value === '' || age.value < 3 || age.value > 18) {
                age.classList.add('error');
                isValid = false;
            } else {
                age.classList.remove('error');
            }
        } else if (stepNumber === 2) {
            // Validate step 2 fields
            const classe = document.getElementById('classe_demande');
            
            if (classe.value === '') {
                classe.classList.add('error');
                isValid = false;
            } else {
                classe.classList.remove('error');
            }
        } else if (stepNumber === 3) {
            // Validate step 3 fields
            const email = document.getElementById('email');
            const telephone = document.getElementById('telephone');
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                email.classList.add('error');
                isValid = false;
            } else {
                email.classList.remove('error');
            }
            
            if (telephone.value.trim() === '') {
                telephone.classList.add('error');
                isValid = false;
            } else {
                telephone.classList.remove('error');
            }
        } else if (stepNumber === 4) { // New validation for step 4
            // Validate step 4 fields
            const password = document.getElementById('mot_de_passe');
            const confirmPassword = document.getElementById('confirm_mot_de_passe');
            
            if (password.value.length < 8) {
                password.classList.add('error');
                isValid = false;
            } else {
                password.classList.remove('error');
            }
            
            if (confirmPassword.value !== password.value) {
                confirmPassword.classList.add('error');
                isValid = false;
            } else {
                confirmPassword.classList.remove('error');
            }
        }
        
        return isValid;
    }
    
    // Next button click
    nextBtn.addEventListener('click', function() {
        if (validateStep(currentStep)) {
            showStep(currentStep + 1);
        }
    });
    
    // Previous button click
    prevBtn.addEventListener('click', function() {
        showStep(currentStep - 1);
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        if (!validateStep(currentStep)) {
            e.preventDefault();
            return;
        }
    });
    
    // Toggle password visibility
    function togglePassword(inputId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = passwordInput.nextElementSibling;
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
</script>

</body>
</html>
