<?php
include 'db_config.php';

require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';

// Récupérer la liste des matières pour le menu déroulant
$matieres_result = $conn->query("SELECT matiere_id, nom FROM matieres ORDER BY nom");

// Si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nom'])) {
    $nom          = $_POST['nom'];
    $prenom       = $_POST['prenom'];
    $email        = $_POST['email'];
    $matiere_id   = $_POST['matiere_id'];
    $login        = $_POST['login'];
    $mot_de_passe = $_POST['mot_de_passe'];

    if (strlen($mot_de_passe) < 8) {
        $message = "يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.";
        $message_type = "error";
    } else {
        $check_sql = "SELECT * FROM professeurs WHERE login = ? OR email = ?";
        $stmt_check = $conn->prepare($check_sql);

        if ($stmt_check) {
            $stmt_check->bind_param("ss", $login, $email);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            if ($result->num_rows > 0) {
                $message = "هذا الحساب موجود بالفعل.";
                $message_type = "error";
            } else {
                $sql = "INSERT INTO professeurs (nom, prenom, email, matiere_id, login, mot_de_passe)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param("sssiss", $nom, $prenom, $email, $matiere_id, $login, $mot_de_passe);
                    if ($stmt->execute()) {
                        $message = "تمت إضافة الأستاذ بنجاح.";
                        $message_type = "success";

                        // Envoi Email
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
                            $mail->Subject = 'مرحباً بك في منصة المدرسة';
                            $mail->Body = "مرحباً $prenom \n\n".
                                          "معلومات الدخول:\n".
                                          "📧 البريد الإلكتروني: $email\n".
                                          "👤 اسم المستخدم: $login\n".
                                          "📘 رقم المادة: $matiere_id\n".
                                          "🔐 كلمة المرور: $mot_de_passe\n\n".
                                          "نتمنى لك التوفيق 🌟";

                            $mail->send();
                        } catch (Exception $e) {
                            $message .= " لكن لم نتمكن من إرسال البريد الإلكتروني.";
                        }
                        
                        // Réinitialiser les champs du formulaire après succès
                        $nom = $prenom = $email = $login = $mot_de_passe = "";
                        $matiere_id = 0;
                    } else {
                        $message = "حدثت مشكلة في إضافة الأستاذ: " . $stmt->error;
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
            $message = "حدثت مشكلة في التحقق من وجود الأستاذ.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة معلم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a5276;
            --primary-light: #2980b9;
            --secondary-color: #27ae60;
            --secondary-light: #2ecc71;
            --accent-color: #e67e22;
            --danger-color: #c0392b;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --text-color: #333;
            --border-radius: 6px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            padding: 30px 0;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-title i {
            font-size: 32px;
            color: var(--primary-color);
        }

        .page-subtitle {
            color: #7f8c8d;
            font-size: 16px;
            font-weight: 500;
        }

        .form-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .form-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-body {
            padding: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            transition: var(--transition);
            background-color: #f9f9f9;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.1);
            outline: none;
            background-color: white;
        }

        .form-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            transition: var(--transition);
            background-color: #f9f9f9;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%232c3e50' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 10px center;
            background-size: 16px;
            padding-left: 30px;
        }

        .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.1);
            outline: none;
            background-color: white;
        }

        .form-footer {
            padding: 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            text-align: center;
        }

        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 200px;
            font-family: 'Cairo', sans-serif;
        }

        .btn-submit:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: slideDown 0.3s ease;
            box-shadow: var(--box-shadow);
        }

        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert i {
            margin-left: 10px;
            font-size: 18px;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
            border-right: 4px solid #27ae60;
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.2);
            color: #c0392b;
            border-right: 4px solid #c0392b;
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #7f8c8d;
            cursor: pointer;
            font-size: 14px;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            border-radius: 2px;
            background-color: #e0e0e0;
            overflow: hidden;
        }

        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: var(--transition);
        }

        .strength-weak {
            width: 33%;
            background-color: var(--danger-color);
        }

        .strength-medium {
            width: 66%;
            background-color: var(--accent-color);
        }

        .strength-strong {
            width: 100%;
            background-color: var(--secondary-color);
        }

        .password-feedback {
            margin-top: 5px;
            font-size: 12px;
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-body {
                padding: 20px;
            }

            .form-footer {
                padding: 15px;
            }

            .btn-submit {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chalkboard-teacher"></i>
                <title>إضافة معلم جديد</title>
            </h1>
            <p class="page-subtitle">أدخل معلومات المعلم الجديد للمنصة</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header">
            <i class="fas fa-user-plus"></i> معلومات المعلم

            </div>
            <form method="POST" action="">
                <div class="form-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom" class="form-label">الاسم:</label>
                            <input type="text" id="nom" name="nom" class="form-control" required value="<?php echo isset($nom) ? htmlspecialchars($nom) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="prenom" class="form-label">اللقب:</label>
                            <input type="text" id="prenom" name="prenom" class="form-control" required value="<?php echo isset($prenom) ? htmlspecialchars($prenom) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email" class="form-label">البريد الإلكتروني:</label>
                            <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="matiere_id" class="form-label">المادة:</label>
                            <select id="matiere_id" name="matiere_id" class="form-select" required>
                                <option value="">اختر المادة</option>
                                <?php while ($matiere = $matieres_result->fetch_assoc()): ?>
                                    <option value="<?php echo $matiere['matiere_id']; ?>" <?php echo (isset($matiere_id) && $matiere_id == $matiere['matiere_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($matiere['nom']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="login" class="form-label">اسم المستخدم:</label>
                            <input type="text" id="login" name="login" class="form-control" required value="<?php echo isset($login) ? htmlspecialchars($login) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="mot_de_passe" class="form-label">كلمة المرور:</label>
                            <div class="password-field">
                                <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" required minlength="8">
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility()">
                                    <i class="fas fa-eye" id="password-toggle-icon"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-meter" id="password-strength-meter"></div>
                            </div>
                            <div class="password-feedback" id="password-feedback">كلمة المرور يجب أن تكون 8 أحرف على الأقل</div>
                        </div>
                    </div>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-plus-circle"></i>
                        إضافة معلم
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('mot_de_passe');
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
        const passwordInput = document.getElementById('mot_de_passe');
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
                strengthFeedback.textContent = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
                strengthFeedback.style.color = '#7f8c8d';
            } else if (strength < 2) {
                strengthMeter.classList.add('strength-weak');
                strengthFeedback.textContent = 'ضعيفة: أضف حروفًا كبيرة وأرقامًا';
                strengthFeedback.style.color = '#c0392b';
            } else if (strength < 4) {
                strengthMeter.classList.add('strength-medium');
                strengthFeedback.textContent = 'متوسطة: أضف رموزًا خاصة (@, !, #, ...)';
                strengthFeedback.style.color = '#e67e22';
            } else {
                strengthMeter.classList.add('strength-strong');
                strengthFeedback.textContent = 'قوية: كلمة مرور آمنة';
                strengthFeedback.style.color = '#27ae60';
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
