<?php
include 'db_config.php';


require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';

// Récupérer la liste des classes pour le menu déroulant
$classes_result = $conn->query("SELECT id_classe, nom_classe FROM classes ORDER BY nom_classe");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nom'])) {
    $nom       = $_POST['nom'];
    $prenom    = $_POST['prenom'];
    $email     = $_POST['email'];
    $id_classe = $_POST['id_classe'];
    $login     = $_POST['login'];
    $mp        = $_POST['mp'];

    // Vérification de l'existence du login ou mot de passe
    $check_sql = "SELECT * FROM eleves WHERE login = ? OR email = ?";
    $stmt_check = $conn->prepare($check_sql);

    if ($stmt_check) {
        $stmt_check->bind_param("ss", $login, $email);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            $message = "يوجد طالب بنفس اسم المستخدم أو البريد الإلكتروني.";
            $message_type = "error";
        } else {
            $sql = "INSERT INTO eleves (nom, prenom, email, id_classe, login, mp) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("sssiss", $nom, $prenom, $email, $id_classe, $login, $mp);
                if ($stmt->execute()) {
                    $message = "تم إضافة التلميذ بنجاح.";
                    $message_type = "success";

                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'nawellaouini210@gmail.com';
                        $mail->Password = 'lddg ridp kmxw alfn';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('ecole12@gmail.com', 'مدرستنا');
                        $mail->addAddress($email, "$nom $prenom");
                        $mail->Subject = 'مرحبًا بك في المنصة التعليمية';

                        $mail->Body = "السلام عليكم $prenom \n\n".
                                      "إليك بيانات الدخول الخاصة بك:\n\n".
                                      "البريد الإلكتروني: $email\n".
                                      "اسم المستخدم: $login\n".
                                      "القسم: $id_classe\n".
                                      "كلمة المرور: $mp\n\n".
                                      "نتمنى لك تجربة موفقة على منصتنا.";

                        $mail->send();
                    } catch (Exception $e) {
                        $message .= " لكن لم نتمكن من إرسال البريد الإلكتروني.";
                    }
                    
                    // Réinitialiser les champs du formulaire après succès
                    $nom = $prenom = $email = $login = $mp = "";
                    $id_classe = 0;
                } else {
                    $message = "حدثت مشكلة في إضافة الطالب: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "حدثت مشكلة في تحضير الطلب: " . $conn->error;
                $message_type = "error";
            }
        }
        $stmt_check->close();
    } else {
        $message = "حدثت مشكلة في التحقق م�� وجود الطالب.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة تلميذ جديد</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a5276;
            --primary-light: #2980b9;
            --primary-dark: #154360;
            --secondary-color: #27ae60;
            --secondary-light: #2ecc71;
            --accent-color: #e67e22;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --success-color: #2ecc71;
            --info-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --text-color: #333;
            --border-radius-sm: 4px;
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --box-shadow-sm: 0 2px 5px rgba(0, 0, 0, 0.05);
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeInDown 0.6s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-title i {
            font-size: 36px;
            color: var(--primary-color);
        }

        .page-subtitle {
            color: var(--gray-600);
            font-size: 18px;
            font-weight: 500;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
            font-size: 14px;
            color: var(--gray-600);
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: var(--gray-500);
        }

        .form-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            transition: var(--transition);
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-card:hover {
            box-shadow: var(--box-shadow-lg);
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 25px;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .form-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
            z-index: 1;
        }

        .form-header i {
            font-size: 28px;
            z-index: 2;
        }

        .form-body {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .form-label i {
            margin-left: 8px;
            color: var(--primary-color);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 14px;
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            transition: var(--transition);
            background-color: var(--gray-100);
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.2);
            outline: none;
            background-color: white;
        }

        .form-control::placeholder {
            color: var(--gray-500);
        }

        .form-select {
            width: 100%;
            padding: 14px;
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            transition: var(--transition);
            background-color: var(--gray-100);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%232c3e50' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 15px center;
            background-size: 16px;
            padding-left: 40px;
        }

        .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.2);
            outline: none;
            background-color: white;
        }

        .form-footer {
            padding: 25px 30px;
            background-color: var(--gray-100);
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Cairo', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 10px rgba(26, 82, 118, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(26, 82, 118, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background-color: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background-color: var(--gray-300);
        }

        .btn-lg {
            padding: 14px 28px;
            font-size: 18px;
        }

        .alert {
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            animation: slideDown 0.5s ease;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert i {
            margin-left: 15px;
            font-size: 24px;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .alert-success::before {
            background-color: var(--success-color);
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .alert-error::before {
            background-color: var(--danger-color);
        }

        .alert-close {
            position: absolute;
            top: 10px;
            left: 10px;
            background: none;
            border: none;
            color: currentColor;
            font-size: 16px;
            cursor: pointer;
            opacity: 0.7;
            transition: var(--transition);
        }

        .alert-close:hover {
            opacity: 1;
        }

        .password-field {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray-600);
            transition: var(--transition);
            background: none;
            border: none;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: var(--primary-color);
        }

        .password-strength {
            margin-top: 12px;
            height: 6px;
            border-radius: 3px;
            background-color: var(--gray-300);
            overflow: hidden;
            position: relative;
        }

        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: var(--transition);
            border-radius: 3px;
        }

        .strength-weak {
            width: 33%;
            background-color: var(--danger-color);
            box-shadow: 0 0 5px rgba(231, 76, 60, 0.5);
        }

        .strength-medium {
            width: 66%;
            background-color: var(--warning-color);
            box-shadow: 0 0 5px rgba(243, 156, 18, 0.5);
        }

        .strength-strong {
            width: 100%;
            background-color: var(--success-color);
            box-shadow: 0 0 5px rgba(46, 204, 113, 0.5);
        }

        .password-feedback {
            margin-top: 8px;
            font-size: 14px;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .password-feedback i {
            font-size: 14px;
        }

        .form-hint {
            font-size: 13px;
            color: var(--gray-600);
            margin-top: 6px;
        }

        .form-hint i {
            margin-left: 5px;
            color: var(--info-color);
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-200);
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-title i {
            color: var(--primary-color);
        }

        .required-field::after {
            content: '*';
            color: var(--danger-color);
            margin-right: 5px;
        }

        .back-link {
            color: var(--gray-600);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--primary-color);
        }

        .back-link i {
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
                margin: 20px auto;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-body {
                padding: 20px;
            }

            .form-footer {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
            }

            .btn {
                width: 100%;
            }

            .page-title {
                font-size: 24px;
            }

            .page-title i {
                font-size: 28px;
            }

            .page-subtitle {
                font-size: 16px;
            }
        }

        /* Animation pour les champs du formulaire */
        .form-group {
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.5s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-user-graduate"></i>
                إضافة تلميذ جديد
            </h1>
            <p class="page-subtitle">أدخل معلومات التلميذ الجديد للمنصة التعليمية</p>
            <div class="breadcrumb">
                <a href="admin_dashboard.php"><i class="fas fa-home"></i> الرئيسية</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-left"></i></span>
                <a href="afficher_eleves.php">قائمة التلاميذ</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-left"></i></span>
                <span>إضافة تلميذ</span>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <div>
                    <strong><?php echo $message_type === 'success' ? 'تم بنجاح!' : 'خطأ!'; ?></strong>
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
                <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header">
                <i class="fas fa-user-plus"></i>
                <span>معلومات التلميذ الجديد</span>
            </div>
            <form method="POST" action="" id="studentForm">
                <div class="form-body">
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-user"></i>
                            المعلومات الشخصية
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nom" class="form-label required-field">
                                    <i class="fas fa-user-tag"></i>
                                    الاسم
                                </label>
                                <input type="text" id="nom" name="nom" class="form-control" required 
                                    value="<?php echo isset($nom) ? htmlspecialchars($nom) : ''; ?>"
                                    placeholder="أدخل اسم التلميذ">
                                <div class="form-hint">
                                    <i class="fas fa-info-circle"></i>
                                    يرجى إدخال الاسم باللغة العربية
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="prenom" class="form-label required-field">
                                    <i class="fas fa-user-tag"></i>
                                    اللقب
                                </label>
                                <input type="text" id="prenom" name="prenom" class="form-control" required 
                                    value="<?php echo isset($prenom) ? htmlspecialchars($prenom) : ''; ?>"
                                    placeholder="أدخل لقب التلميذ">
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label required-field">
                                    <i class="fas fa-envelope"></i>
                                    البريد الإلكتروني
                                </label>
                                <input type="email" id="email" name="email" class="form-control" required 
                                    value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                    placeholder="example@domain.com">
                                <div class="form-hint">
                                    <i class="fas fa-info-circle"></i>
                                    سيتم إرسال بيانات الدخول إلى هذا البريد الإلكتروني
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="id_classe" class="form-label required-field">
                                    <i class="fas fa-school"></i>
                                    القسم
                                </label>
                                <select id="id_classe" name="id_classe" class="form-select" required>
                                    <option value="">-- اختر القسم --</option>
                                    <?php while ($classe = $classes_result->fetch_assoc()): ?>
                                        <option value="<?php echo $classe['id_classe']; ?>" <?php echo (isset($id_classe) && $id_classe == $classe['id_classe']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($classe['nom_classe']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-lock"></i>
                            معلومات الحساب
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="login" class="form-label required-field">
                                    <i class="fas fa-user-circle"></i>
                                    اسم المستخدم
                                </label>
                                <input type="text" id="login" name="login" class="form-control" required 
                                    value="<?php echo isset($login) ? htmlspecialchars($login) : ''; ?>"
                                    placeholder="أدخل اسم المستخدم">
                                <div class="form-hint">
                                    <i class="fas fa-info-circle"></i>
                                    يجب أن يكون اسم المستخدم فريدًا وبدون مسافات
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="mp" class="form-label required-field">
                                    <i class="fas fa-key"></i>
                                    كلمة المرور
                                </label>
                                <div class="password-field">
                                    <input type="password" id="mp" name="mp" class="form-control" required
                                        placeholder="أدخل كلمة المرور">
                                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">
                                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-meter" id="password-strength-meter"></div>
                                </div>
                                <div class="password-feedback" id="password-feedback">
                                    <i class="fas fa-info-circle"></i>
                                    أدخل كلمة مرور قوية
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-footer">
                    <a href="afficher_eleves.php" class="back-link">
                        <i class="fas fa-arrow-right"></i>
                        عودة إلى قائمة التلاميذ
                    </a>
                    <div>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i>
                            إعادة تعيين
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus"></i>
                            إضافة التلميذ
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('mp');
            const passwordToggleIcon = document.getElementById('password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggleIcon.classList.remove('fa-eye');
                passwordToggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggleIcon.classList.remove('fa-eye-slash');
                passwordToggleIcon.classList.add('fa-eye');
            }
        }

        // Password strength meter
        const passwordInput = document.getElementById('mp');
        const strengthMeter = document.getElementById('password-strength-meter');
        const strengthFeedback = document.getElementById('password-feedback');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) {
                strength += 1;
            }
            
            // Character variety check
            if (password.match(/[A-Z]/)) {
                strength += 1;
            }
            
            if (password.match(/[0-9]/)) {
                strength += 1;
            }
            
            if (password.match(/[^A-Za-z0-9]/)) {
                strength += 1;
            }
            
            // Update the strength meter
            strengthMeter.className = 'password-strength-meter';
            
            if (password.length === 0) {
                strengthMeter.style.width = '0';
                strengthFeedback.innerHTML = '<i class="fas fa-info-circle"></i> أدخل كلمة مرور قوية';
                strengthFeedback.style.color = 'var(--gray-600)';
            } else if (strength < 2) {
                strengthMeter.classList.add('strength-weak');
                strengthFeedback.innerHTML = '<i class="fas fa-exclamation-circle"></i> ضعيفة: أضف حروفًا كبيرة وأرقامًا';
                strengthFeedback.style.color = 'var(--danger-color)';
            } else if (strength < 4) {
                strengthMeter.classList.add('strength-medium');
                strengthFeedback.innerHTML = '<i class="fas fa-exclamation-triangle"></i> متوسطة: أضف رموزًا خاصة (@, !, #, ...)';
                strengthFeedback.style.color = 'var(--warning-color)';
            } else {
                strengthMeter.classList.add('strength-strong');
                strengthFeedback.innerHTML = '<i class="fas fa-check-circle"></i> قوية: كلمة مرور آمنة';
                strengthFeedback.style.color = 'var(--success-color)';
            }
        });

        // Auto-generate username from name and surname
        const nomInput = document.getElementById('nom');
        const prenomInput = document.getElementById('prenom');
        const loginInput = document.getElementById('login');

        function generateUsername() {
            if (nomInput.value && prenomInput.value && !loginInput.value) {
                // Take first letter of nom and full prenom, remove spaces and special chars
                const firstLetter = nomInput.value.charAt(0).toLowerCase();
                const prenom = prenomInput.value.toLowerCase().replace(/\s+/g, '');
                loginInput.value = firstLetter + prenom;
            }
        }

        nomInput.addEventListener('blur', generateUsername);
        prenomInput.addEventListener('blur', generateUsername);

        // Form validation
        document.getElementById('studentForm').addEventListener('submit', function(event) {
            const password = passwordInput.value;
            
            if (password.length < 6) {
                event.preventDefault();
                alert('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
                passwordInput.focus();
            }
        });

        // Close alert after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(alert => {
                        alert.style.opacity = '0';
                        alert.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => {
                            alert.style.display = 'none';
                        }, 500);
                    });
                }, 5000);
            }
        });
    </script>
</body>
</html>
