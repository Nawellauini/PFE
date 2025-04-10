<?php
session_start();
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $userType = $_POST['user_type']; // نوع المستخدم المحدد

    if ($userType === 'professeur') {
        // التحقق من بيانات المدرسين فقط
        $query = "SELECT * FROM professeurs WHERE email = ? AND mot_de_passe = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['id_professeur'] = $user['id_professeur'];
            $_SESSION['nom_professeur'] = $user['nom'];
            $_SESSION['role'] = 'professeur';
            header("Location: index.php");
            exit();
        } else {
            // التحقق مما إذا كان البريد الإلكتروني موجودًا في جدول الطلاب
            $check_query = "SELECT * FROM eleves WHERE email = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "هذا البريد الإلكتروني ينتمي إلى طالب. يرجى تحديد نوع المستخدم 'طالب'.";
            } else {
                $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
            }
        }
    } elseif ($userType === 'eleve') {
        // التحقق من بيانات الطلاب فقط
        $query = "SELECT * FROM eleves WHERE email = ? AND mp = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['id_eleve'] = $user['id_eleve'];
            $_SESSION['nom_eleve'] = $user['nom'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'eleve';
            header("Location: dashboard_eleve.php");
            exit();
        } else {
            // التحقق مما إذا كان البريد الإلكتروني موجودًا في جدول المدرسين
            $check_query = "SELECT * FROM professeurs WHERE email = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "هذا البريد الإلكتروني ينتمي إلى مدرس. يرجى تحديد نوع المستخدم 'مدرس'.";
            } else {
                $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
            }
        }
    } elseif ($userType === 'administrateur') {
        // التحقق من بيانات المسؤولين
        $query = "SELECT * FROM administrateurs WHERE (email = ? OR login = ?) AND mot_de_passe = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $email, $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['id_admin'] = $user['id_admin'];
            $_SESSION['nom_admin'] = $user['nom'];
            $_SESSION['prenom_admin'] = $user['prenom'];
            $_SESSION['role'] = 'administrateur';
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
        }
    } else { // inspecteur
        // التحقق من بيانات المفتشين - يمكن تسجيل الدخول باستخدام البريد الإلكتروني أو اسم المستخدم
        $query = "SELECT * FROM inspecteurs WHERE (email = ? OR login = ?) AND mot_de_passe = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $email, $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['id_inspecteur'] = $user['id_inspecteur']; // تم تصحيح اسم العمود
            $_SESSION['nom_inspecteur'] = $user['nom'];
            $_SESSION['prenom_inspecteur'] = $user['prenom'];
            $_SESSION['role'] = 'inspecteur';
            header("Location: inspecteur.php");
            exit();
        } else {
            // محاولة التحقق من كلمة المرور المشفرة (للمستخدم الأول)
            $query_hash = "SELECT * FROM inspecteurs WHERE email = ? OR login = ?";
            $stmt_hash = $conn->prepare($query_hash);
            $stmt_hash->bind_param("ss", $email, $email);
            $stmt_hash->execute();
            $result_hash = $stmt_hash->get_result();
            
            if ($result_hash->num_rows > 0) {
                $user = $result_hash->fetch_assoc();
                // التحقق مما إذا كانت كلمة المرور مشفرة باستخدام password_verify
                if (password_verify($password, $user['mot_de_passe'])) {
                    $_SESSION['id_inspecteur'] = $user['id_inspecteur'];
                    $_SESSION['nom_inspecteur'] = $user['nom'];
                    $_SESSION['prenom_inspecteur'] = $user['prenom'];
                    $_SESSION['role'] = 'inspecteur';
                    header("Location: inspecteur.php");
                    exit();
                } else {
                    $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
                }
            } else {
                $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - المدرسة الابتدائية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --success-color: #4CAF50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            display: flex;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            overflow: hidden;
            background-color: white;
        }
        
        .login-sidebar {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-sidebar::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: 0;
        }
        
        .login-sidebar-content {
            position: relative;
            z-index: 1;
        }
        
        .login-sidebar h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
        }
        
        .login-sidebar h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: 0;
            width: 50px;
            height: 4px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }
        
        .login-sidebar p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .login-features {
            margin-top: 40px;
        }
        
        .login-feature {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .login-feature-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            font-size: 1.2rem;
        }
        
        .login-feature-text {
            font-size: 1rem;
        }
        
        .login-form-container {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo img {
            width: 150px;
            height: 100px;
            object-fit: contain;
        }
        
        .login-form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-form-header h2 {
            font-size: 1.8rem;
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-form-header p {
            color: var(--gray-color);
            font-size: 1rem;
        }
        
        .login-form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-control {
            height: 55px;
            padding: 10px 50px 10px 20px;
            font-size: 1rem;
            border: 1px solid #e1e5eb;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .form-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus + .form-icon {
            color: var(--primary-color);
        }
        
        .user-type-selector {
            display: flex;
            margin-bottom: 25px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e1e5eb;
        }
        
        .user-type-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .user-type-option.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .user-type-option:not(.active) {
            background-color: #f8f9fa;
            color: var(--dark-color);
        }
        
        .user-type-option:not(.active):hover {
            background-color: #e9ecef;
        }
        
        .user-type-option i {
            margin-left: 8px;
            font-size: 1.1rem;
        }
        
        .user-type-option::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        
        .user-type-option:active::after {
            transform: translateY(0);
        }
        
        .btn-login {
            height: 55px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.35);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        
        .btn-login:active::after {
            transform: translateY(0);
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        
        .forgot-password a {
            color: var(--gray-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: var(--primary-color);
        }
        
        .error-message {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            animation: shake 0.5s ease-in-out;
        }
        
        .error-message i {
            margin-left: 10px;
            font-size: 1.2rem;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-form-container, .login-sidebar-content {
            animation: fadeIn 0.8s ease forwards;
        }
        
        .login-form-container {
            animation-delay: 0.3s;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 500px;
            }
            
            .login-sidebar {
                padding: 30px;
            }
            
            .login-sidebar h1 {
                font-size: 2rem;
            }
            
            .login-feature {
                margin-bottom: 15px;
            }
            
            .login-form-container {
                padding: 30px;
            }
        }
        
        @media (max-width: 576px) {
            .login-sidebar, .login-form-container {
                padding: 20px;
            }
            
            .login-sidebar h1 {
                font-size: 1.8rem;
            }
            
            .login-sidebar p {
                font-size: 1rem;
            }
            
            .login-form-header h2 {
                font-size: 1.5rem;
            }
            
            .form-control, .btn-login {
                height: 50px;
            }
            
            .user-type-option {
                padding: 12px 10px;
                font-size: 0.9rem;
            }
            
            .user-type-option i {
                margin-left: 5px;
            }
        }
        
        /* Floating shapes animation */
        .shape {
            position: absolute;
            opacity: 0.2;
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 150px;
            height: 150px;
            background-color: var(--accent-color);
            top: -50px;
            left: -50px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 100px;
            height: 100px;
            background-color: white;
            bottom: 50px;
            left: 50px;
            animation-delay: 3s;
        }
        
        .shape-3 {
            width: 70px;
            height: 70px;
            background-color: var(--accent-color);
            bottom: -20px;
            right: 30%;
            animation-delay: 6s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- Sidebar with information -->
    <div class="login-sidebar">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        
        <div class="login-sidebar-content">
            <h1>مرحبًا بك في مدرستنا</h1>
            <p>قم بتسجيل الدخول للوصول إلى مساحتك الشخصية ومتابعة مسارك التعليمي.</p>
            
            <div class="login-features">
                <div class="login-feature">
                    <div class="login-feature-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="login-feature-text">الوصول إلى دروسك والموارد التعليمية</div>
                </div>
                
                <div class="login-feature">
                    <div class="login-feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="login-feature-text">متابعة تقدمك ونتائجك الدراسية</div>
                </div>
                
                <div class="login-feature">
                    <div class="login-feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="login-feature-text">الاطلاع على جدولك الزمني وواجباتك</div>
                </div>
                
                <div class="login-feature">
                    <div class="login-feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="login-feature-text">التواصل مع مدرسيك وزملائك</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Login form -->
    <div class="login-form-container">
        <div class="login-logo">
            <img src="uploads/photos_eleves/myschool.png" alt="شعار المدرسة" onerror="this.src='https://via.placeholder.com/80?text=مدرستي';this.onerror='';">
        </div>
        
        <div class="login-form-header">
            <h2>تسجيل الدخول</h2>
            <p>أدخل بيانات الاعتماد الخاصة بك لتسجيل الدخول</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form class="login-form" method="post">
            <!-- حقل مخفي لتخزين نوع المستخدم -->
            <input type="hidden" name="user_type" id="user_type" value="eleve">
            
            <div class="user-type-selector">
                <div class="user-type-option active" data-type="eleve">
                <i class="fas fa-user-graduate"></i> تلميذ
                </div>
                <div class="user-type-option" data-type="professeur">
                <i class="fas fa-chalkboard-teacher"></i> معلّم
                </div>
                <div class="user-type-option" data-type="inspecteur">
                <i class="fas fa-user-tie"></i> متفقد
                </div>
                <div class="user-type-option" data-type="administrateur">
                <i class="fas fa-user-shield"></i> مدير
                </div>
            </div>
            
            <div class="form-group">
                <input type="text" name="email" class="form-control" placeholder="البريد الإلكتروني أو اسم المستخدم" required>
                <i class="fas fa-envelope form-icon"></i>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="كلمة المرور" required>
                <i class="fas fa-lock form-icon"></i>
            </div>
            
            <button type="submit" class="btn btn-login w-100">
                <i class="fas fa-sign-in-alt ms-2"></i> تسجيل الدخول
            </button>
        </form>
        
        <div class="forgot-password">
            <a href="#" id="forgot-password-link">نسيت كلمة المرور؟</a>
        </div>
        
        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> المدرسة الابتدائية - جميع الحقوق محفوظة
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // محدد نوع المستخدم
    const userTypeOptions = document.querySelectorAll('.user-type-option');
    const userTypeInput = document.getElementById('user_type');
    const emailInput = document.querySelector('input[name="email"]');

    userTypeOptions.forEach(option => {
        option.addEventListener('click', function() {
            // إزالة الفئة النشطة من جميع الخيارات
            userTypeOptions.forEach(opt => opt.classList.remove('active'));
            
            // إضافة الفئة النشطة إلى الخيار المحدد
            this.classList.add('active');
            
            // تحديث الحقل المخفي بنوع المستخدم المحدد
            const userType = this.getAttribute('data-type');
            userTypeInput.value = userType;
            
            // تغيير نص العنصر النائب للنموذج بناءً على نوع المستخدم
            if (userType === 'eleve') {
                emailInput.placeholder = 'البريد الإلكتروني للطالب';
            } else if (userType === 'professeur') {
                emailInput.placeholder = 'البريد الإلكتروني للمدرس';
            } else if (userType === 'administrateur') {
                emailInput.placeholder = 'البريد الإلكتروني أو اسم المستخدم للمسؤول';
            } else {
                emailInput.placeholder = 'البريد الإلكتروني أو اسم المستخدم للمفتش';
            }
        });
    });

    // رابط نسيت كلمة المرور
    const forgotPasswordLink = document.getElementById('forgot-password-link');

    forgotPasswordLink.addEventListener('click', function(e) {
        e.preventDefault();
        alert('يرجى الاتصال بإدارة المدرسة لإعادة تعيين كلمة المرور الخاصة بك.');
    });

    // تحسين التحقق من صحة النموذج
    const loginForm = document.querySelector('.login-form');

    loginForm.addEventListener('submit', function(e) {
        const emailInput = document.querySelector('input[name="email"]');
        const passwordInput = document.querySelector('input[name="password"]');
        
        if (!emailInput.value.trim() || !passwordInput.value.trim()) {
            e.preventDefault();
            alert('يرجى ملء جميع الحقول.');
        }
    });
});
</script>
</body>
</html>

