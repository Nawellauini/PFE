<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit;
}

$id_professeur = $_SESSION['id_professeur'];

// Récupérer les informations du professeur
$stmt_prof = $conn->prepare("SELECT nom, prenom FROM professeurs WHERE id_professeur = ?");
if ($stmt_prof === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt_prof->bind_param("i", $id_professeur);
$stmt_prof->execute();
$result_prof = $stmt_prof->get_result();
$professeur = $result_prof->fetch_assoc();
$stmt_prof->close();

// Traitement de l'envoi du message
$message_status = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subject'])) {
    $id_eleve = $_POST['id_eleve'];
    $subject = $_POST['subject'];
    $message_text = $_POST['message_text'];
    $date_envoi = date("Y-m-d H:i:s");

    // Vérifier si la colonne 'lu' existe dans la table
    $check_column = $conn->query("SHOW COLUMNS FROM message_profeleve LIKE 'lu'");
    if ($check_column->num_rows > 0) {
        // La colonne 'lu' existe
        $stmt = $conn->prepare("INSERT INTO message_profeleve (id_professeur, id_eleve, subject, message_text, date_envoi, lu) VALUES (?, ?, ?, ?, ?, 0)");
        if ($stmt === false) {
            die("Erreur de préparation de la requête: " . $conn->error);
        }
        $stmt->bind_param("iisss", $id_professeur, $id_eleve, $subject, $message_text, $date_envoi);
    } else {
        // La colonne 'lu' n'existe pas
        $stmt = $conn->prepare("INSERT INTO message_profeleve (id_professeur, id_eleve, subject, message_text, date_envoi) VALUES (?, ?, ?, ?, ?)");
        if ($stmt === false) {
            die("Erreur de préparation de la requête: " . $conn->error);
        }
        $stmt->bind_param("iisss", $id_professeur, $id_eleve, $subject, $message_text, $date_envoi);
    }

    if ($stmt->execute()) {
        $message_status = "تم إرسال الرسالة بنجاح.";
        $message_type = "success";
        
        // Récupérer les informations de l'élève pour l'affichage de confirmation
        $stmt_eleve = $conn->prepare("SELECT nom, prenom FROM eleves WHERE id_eleve = ?");
        if ($stmt_eleve === false) {
            die("Erreur de préparation de la requête: " . $conn->error);
        }
        $stmt_eleve->bind_param("i", $id_eleve);
        $stmt_eleve->execute();
        $result_eleve = $stmt_eleve->get_result();
        $eleve = $result_eleve->fetch_assoc();
        $stmt_eleve->close();
    } else {
        $message_status = "حدث خطأ أثناء إرسال الرسالة: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Vérifier si la colonne 'lu' existe dans la table
$has_lu_column = false;
$check_column = $conn->query("SHOW COLUMNS FROM message_profeleve LIKE 'lu'");
if ($check_column->num_rows > 0) {
    $has_lu_column = true;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إرسال رسالة للتلميذ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3c4b64;
            --primary-light: #5d6e8c;
            --primary-dark: #2d3a4f;
            --secondary-color: #636f83;
            --accent-color: #321fdb;
            --success-color: #2eb85c;
            --info-color: #39f;
            --warning-color: #f9b115;
            --danger-color: #e55353;
            --light-color: #ebedef;
            --dark-color: #4f5d73;
            --border-color: #d8dbe0;
            --border-radius: 4px;
            --box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f1f1f1;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 15px;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            font-weight: 700;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            align-items: center;
        }

        .card-header i {
            margin-left: 10px;
        }

        .card-body {
            padding: 20px;
        }

        .card-footer {
            padding: 15px;
            background-color: #f8f9fa;
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            display: flex;
            justify-content: space-between;
        }

        .page-title {
            margin-bottom: 20px;
            color: var(--primary-color);
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-left: 10px;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-family: 'Tajawal', sans-serif;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(50, 31, 219, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-family: 'Tajawal', sans-serif;
            font-size: 14px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%233c4b64' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 12px center;
            background-size: 16px;
            padding-left: 35px;
        }

        .form-select:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(50, 31, 219, 0.1);
        }

        .form-select:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn i {
            margin-left: 5px;
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2b1cc4;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #566175;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #27a34c;
        }

        .alert {
            padding: 12px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .required::after {
            content: ' *';
            color: var(--danger-color);
        }

        .form-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }

        .success-message {
            text-align: center;
            padding: 20px;
        }

        .success-icon {
            font-size: 48px;
            color: var(--success-color);
            margin-bottom: 15px;
        }

        .success-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .success-actions {
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .card-footer {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-paper-plane"></i>
            إرسال رسالة للتلميذ
        </h1>

        <?php if (!empty($message_status)): ?>
            <?php if ($message_type === 'success'): ?>
                <div class="card">
                    <div class="card-body success-message">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="success-title">تم إرسال الرسالة بنجاح!</h3>
                        <p>
                            تم إرسال رسالتك إلى التلميذ <strong><?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></strong> بنجاح.
                        </p>
                        <div class="success-actions">
                            <a href="envoyer_message.php" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                إرسال رسالة أخرى
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($message_status); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($message_status) || $message_type !== 'success'): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-envelope"></i>
                    إرسال رسالة جديدة
                </div>
                <form method="POST" action="" id="sendMessageForm">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="classe" class="form-label required">اختر القسم</label>
                            <select id="classe" name="id_classe" class="form-select" required>
                                <option value="">-- اختر القسم --</option>
                                <?php
                                $sql = "SELECT DISTINCT c.id_classe, c.nom_classe
                                        FROM classes c
                                        INNER JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
                                        WHERE pc.id_professeur = ?";
                                $stmt = $conn->prepare($sql);
                                if ($stmt === false) {
                                    echo "<option value=''>خطأ في تحميل الأقسام: " . $conn->error . "</option>";
                                } else {
                                    $stmt->bind_param("i", $id_professeur);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='{$row['id_classe']}'>{$row['nom_classe']}</option>";
                                    }
                                    $stmt->close();
                                }
                                ?>
                            </select>
                            <div class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                يتم عرض الأقسام التي تقوم بتدريسها فقط
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="eleve" class="form-label required">اختر التلميذ</label>
                            <select name="id_eleve" id="eleve" class="form-select" required disabled>
                                <option value="">-- اختر القسم أولاً --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="subject" class="form-label required">الموضوع</label>
                            <input type="text" id="subject" name="subject" class="form-control" required placeholder="أدخل موضوع الرسالة">
                        </div>

                        <div class="form-group">
                            <label for="message_text" class="form-label required">نص الرسالة</label>
                            <textarea id="message_text" name="message_text" class="form-control" required placeholder="اكتب رسالتك هنا..."></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i>
                            إعادة تعيين
                        </button>
                        <button type="submit" class="btn btn-primary" id="sendButton">
                            <i class="fas fa-paper-plane"></i>
                            إرسال الرسالة
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // Charger les élèves lorsqu'une classe est sélectionnée
            $('#classe').on('change', function () {
                var id_classe = $(this).val();
                if (id_classe) {
                    $('#eleve').prop('disabled', true);
                    $('#eleve').html('<option value="">جاري التحميل...</option>');
                    
                    $.ajax({
                        url: 'get_eleves_by_classe.php',
                        type: 'POST',
                        data: {id_classe: id_classe},
                        success: function (data) {
                            $('#eleve').html(data);
                            $('#eleve').prop('disabled', false);
                        },
                        error: function() {
                            $('#eleve').html('<option value="">حدث خطأ أثناء تحميل التلاميذ</option>');
                            $('#eleve').prop('disabled', true);
                        }
                    });
                } else {
                    $('#eleve').html('<option value="">-- اختر القسم أولاً --</option>');
                    $('#eleve').prop('disabled', true);
                }
            });

            // Animation du bouton d'envoi
            $('#sendMessageForm').on('submit', function() {
                $('#sendButton').html('<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...');
                $('#sendButton').prop('disabled', true);
            });
        });
    </script>
</body>
</html>
