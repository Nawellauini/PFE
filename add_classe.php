<?php
include 'db_config.php'; // ربط بقاعدة البيانات

$message = ''; // لعرض الرسائل
$message_type = '';

// التحقق إذا تم إرسال النموذج
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nom_classe'])) {
    $nom_classe = $_POST['nom_classe'];

    // التحقق إذا كان القسم موجود بالفعل
    $check_sql = "SELECT * FROM classes WHERE nom_classe = ?";
    $stmt_check = $conn->prepare($check_sql);

    if ($stmt_check === false) {
        $message = "فما مشكلة في تحضير الطلب: " . $conn->error;
        $message_type = "error";
    } else {
        $stmt_check->bind_param("s", $nom_classe);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            $message = "القسم هذا موجود من قبل.";
            $message_type = "error";
        } else {
            // إدخال القسم في قاعدة البيانات
            $insert_sql = "INSERT INTO classes (nom_classe) VALUES (?)";
            $stmt_insert = $conn->prepare($insert_sql);

            if ($stmt_insert) {
                $stmt_insert->bind_param("s", $nom_classe);
                if ($stmt_insert->execute()) {
                    $message = "تم إضافة القسم بنجاح.";
                    $message_type = "success";
                    
                    // Réinitialiser le champ après succès
                    $nom_classe = "";
                } else {
                    $message = "فما مشكلة في إضافة القسم: " . $stmt_insert->error;
                    $message_type = "error";
                }
                $stmt_insert->close();
            } else {
                $message = "فما مشكلة في تحضير الطلب: " . $conn->error;
                $message_type = "error";
            }
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة قسم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --primary-light: #34495e;
            --secondary-color: #3498db;
            --secondary-light: #5dade2;
            --accent-color: #e74c3c;
            --accent-light: #f1948a;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --text-color: #333;
            --border-radius: 10px;
            --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
            padding: 30px 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
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
            color: var(--warning-color);
        }

        .page-subtitle {
            color: #7f8c8d;
            font-size: 18px;
            font-weight: 500;
        }

        .form-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            transition: var(--transition);
        }

        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
            color: white;
            padding: 25px;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
        }

        .form-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-family: 'Tajawal', sans-serif;
            font-size: 16px;
            transition: var(--transition);
            background-color: #f9f9f9;
        }

        .form-control:focus {
            border-color: var(--warning-color);
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.2);
            outline: none;
            background-color: white;
        }

        .form-footer {
            padding: 20px 30px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            text-align: center;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            font-family: 'Tajawal', sans-serif;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        .alert {
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            animation: slideDown 0.3s ease;
            box-shadow: var(--box-shadow);
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
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
            border-left: 5px solid #27ae60;
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.2);
            color: #c0392b;
            border-left: 5px solid #c0392b;
        }

        .classes-list {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-top: 30px;
        }

        .classes-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 15px 20px;
            font-weight: 700;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .classes-count {
            background-color: white;
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
        }

        .classes-body {
            padding: 0;
            max-height: 300px;
            overflow-y: auto;
        }

        .classes-list-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .classes-list-item:last-child {
            border-bottom: none;
        }

        .classes-list-item:hover {
            background-color: #f8f9fa;
        }

        .class-name {
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .class-name i {
            color: var(--warning-color);
        }

        .empty-classes {
            padding: 30px;
            text-align: center;
            color: #7f8c8d;
        }

        .empty-classes i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }

            .form-body {
                padding: 20px;
            }

            .form-footer {
                padding: 15px 20px;
            }

            .btn-submit {
                padding: 12px 20px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-school"></i>
                إضافة قسم جديد
            </h1>
            <p class="page-subtitle">أدخل اسم القسم الجديد للمنصة</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header">
                <i class="fas fa-plus-circle"></i> معلومات القسم
            </div>
            <form method="POST" action="">
                <div class="form-body">
                    <div class="form-group">
                        <label for="nom_classe" class="form-label">اسم القسم:</label>
                        <input type="text" id="nom_classe" name="nom_classe" class="form-control" required value="<?php echo isset($nom_classe) ? htmlspecialchars($nom_classe) : ''; ?>" placeholder="مثال: السنة الأولى أ">
                    </div>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-plus-circle"></i>
                        إضافة القسم
                    </button>
                </div>
            </form>
        </div>

        <?php
        // Afficher la liste des classes existantes
        $classes_result = $conn->query("SELECT id_classe, nom_classe FROM classes ORDER BY nom_classe");
        ?>

        <div class="classes-list">
            <div class="classes-header">
                <span>الأقسام الموجودة</span>
                <span class="classes-count"><?php echo $classes_result->num_rows; ?> قسم</span>
            </div>
            <div class="classes-body">
                <?php if ($classes_result->num_rows > 0): ?>
                    <?php while ($classe = $classes_result->fetch_assoc()): ?>
                        <div class="classes-list-item">
                            <div class="class-name">
                                <i class="fas fa-users"></i>
                                <?php echo htmlspecialchars($classe['nom_classe']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-classes">
                        <i class="fas fa-school"></i>
                        <p>مافماش أقسام موجودة حاليا</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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
