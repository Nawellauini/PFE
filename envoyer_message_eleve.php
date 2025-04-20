<?php

session_start();
require_once 'db_config.php';

if (!isset($_SESSION['id_eleve'])) {
    header("Location: login.php");
    exit;
}

$id_eleve = $_SESSION['id_eleve'];

// Récupérer les informations de l'élève
$stmt_eleve = $conn->prepare("SELECT nom, prenom FROM eleves WHERE id_eleve = ?");
if ($stmt_eleve === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt_eleve->bind_param("i", $id_eleve);
$stmt_eleve->execute();
$result_eleve = $stmt_eleve->get_result();
$eleve = $result_eleve->fetch_assoc();
$stmt_eleve->close();

// Récupérer la classe de l'élève
$sql = "SELECT id_classe FROM eleves WHERE id_eleve = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_eleve);
$stmt->execute();
$stmt->bind_result($id_classe);
$stmt->fetch();
$stmt->close();

// Récupérer les profs de cette classe
$sql = "SELECT p.id_professeur, p.nom, p.prenom, m.nom as matiere_nom
        FROM professeurs p
        INNER JOIN professeurs_classes pc ON p.id_professeur = pc.id_professeur
        LEFT JOIN matieres m ON p.matiere_id = m.matiere_id
        WHERE pc.id_classe = ?
        ORDER BY p.nom, p.prenom";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_classe);
$stmt->execute();
$result = $stmt->get_result();
$profs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Traitement du message de confirmation
$message_status = '';
$message_type = '';

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message_status = "تم إرسال الرسالة بنجاح.";
        $message_type = "success";
    } elseif ($_GET['status'] == 'error') {
        $message_status = "حدث خطأ أثناء إرسال الرسالة.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إرسال رسالة للأستاذ</title>
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-family: 'Tajawal', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(50, 31, 219, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 10px 12px;
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
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(50, 31, 219, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .file-input-wrapper {
            position: relative;
            margin-top: 8px;
        }

        .file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background-color: var(--light-color);
            border: 1px dashed #ccc;
            border-radius: var(--border-radius);
            color: var(--secondary-color);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label i {
            margin-left: 8px;
            font-size: 20px;
        }

        .file-input-label:hover {
            background-color: #dfe6e9;
        }

        .file-name {
            margin-top: 8px;
            font-size: 14px;
            color: var(--secondary-color);
            word-break: break-all;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn i {
            margin-left: 8px;
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2b1cc4;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #566175;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert i {
            margin-left: 10px;
            font-size: 18px;
        }

        .alert-success {
            background-color: rgba(46, 184, 92, 0.1);
            color: var(--success-color);
            border-right: 4px solid var(--success-color);
        }

        .alert-error {
            background-color: rgba(229, 83, 83, 0.1);
            color: var(--danger-color);
            border-right: 4px solid var(--danger-color);
        }

        .required::after {
            content: ' *';
            color: var(--danger-color);
        }

        .form-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        .professor-card {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .professor-card:hover {
            background-color: rgba(50, 31, 219, 0.05);
            border-color: var(--accent-color);
        }

        .professor-card.selected {
            background-color: rgba(50, 31, 219, 0.1);
            border-color: var(--accent-color);
        }

        .professor-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            margin-left: 15px;
        }

        .professor-info {
            flex: 1;
        }

        .professor-name {
            font-weight: 600;
            color: var(--primary-color);
        }

        .professor-subject {
            font-size: 12px;
            color: var(--secondary-color);
        }

        .professor-radio {
            margin-right: 10px;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background-color: #e9ecef;
            border-radius: 2px;
            margin-top: 10px;
            overflow: hidden;
            display: none;
        }

        .progress-bar-fill {
            height: 100%;
            background-color: var(--accent-color);
            width: 0%;
            transition: width 0.3s ease;
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
            إرسال رسالة للأستاذ
        </h1>

        <?php if (!empty($message_status)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message_status); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-envelope"></i>
                إرسال رسالة جديدة
            </div>
            <form action="traitement_message_eleve.php" method="post" enctype="multipart/form-data" id="messageForm">
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required">اختر الأستاذ</label>
                        <div class="professor-list">
                            <?php if (count($profs) > 0): ?>
                                <?php foreach ($profs as $index => $prof): ?>
                                    <div class="professor-card" onclick="selectProfessor(this, <?php echo $prof['id_professeur']; ?>)">
                                        <div class="professor-avatar">
                                            <?php echo substr($prof['nom'], 0, 1); ?>
                                        </div>
                                        <div class="professor-info">
                                            <div class="professor-name"><?php echo htmlspecialchars($prof['nom'] . ' ' . $prof['prenom']); ?></div>
                                            <?php if (!empty($prof['matiere_nom'])): ?>
                                                <div class="professor-subject"><?php echo htmlspecialchars($prof['matiere_nom']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="radio" name="id_professeur" value="<?php echo $prof['id_professeur']; ?>" class="professor-radio" required <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    لا يوجد أساتذة متاحين لهذا الفصل.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="subject" class="form-label required">الموضوع</label>
                        <input type="text" id="subject" name="subject" class="form-control" required placeholder="أدخل موضوع الرسالة">
                    </div>

                    <div class="form-group">
                        <label for="message_text" class="form-label required">نص الرسالة</label>
                        <textarea id="message_text" name="message_text" class="form-control" required placeholder="اكتب رسالتك هنا..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="attachment" class="form-label">ملف مرفق (اختياري)</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="attachment" name="attachment" class="file-input">
                            <label for="attachment" class="file-input-label">
                                <i class="fas fa-upload"></i>
                                اختر ملفًا
                            </label>
                        </div>
                        <div id="file-name" class="file-name"></div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i>
                            الملفات المسموح بها: PDF, Word, Excel, PowerPoint, ZIP (الحد الأقصى: 10MB)
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="dashboard_eleve.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i>
                        العودة
                    </a>
                    <button type="submit" class="btn btn-primary" id="sendButton">
                        <i class="fas fa-paper-plane"></i>
                        إرسال الرسالة
                    </button>
                </div>
                <div class="progress-bar" id="progressBar">
                    <div class="progress-bar-fill" id="progressBarFill"></div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Afficher le nom du fichier sélectionné
        document.getElementById('attachment').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : '';
            document.getElementById('file-name').textContent = fileName;
            
            // Vérifier la taille du fichier
            if (this.files[0] && this.files[0].size > 10 * 1024 * 1024) {
                alert('حجم الملف كبير جدًا. الحد الأقصى هو 10 ميغابايت.');
                this.value = '';
                document.getElementById('file-name').textContent = '';
            }
        });

        // Sélectionner un professeur
        function selectProfessor(element, professorId) {
            // Supprimer la classe selected de tous les éléments
            const cards = document.querySelectorAll('.professor-card');
            cards.forEach(card => card.classList.remove('selected'));
            
            // Ajouter la classe selected à l'élément cliqué
            element.classList.add('selected');
            
            // Cocher le bouton radio correspondant
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;
        }

        // Initialiser la sélection du premier professeur
        document.addEventListener('DOMContentLoaded', function() {
            const firstCard = document.querySelector('.professor-card');
            if (firstCard) {
                firstCard.classList.add('selected');
            }
        });

        // Soumettre le formulaire avec animation
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message_text').value.trim();
            
            if (!subject || !message) {
                e.preventDefault();
                alert('يرجى ملء جميع الحقول المطلوبة.');
                return;
            }
            
            // Désactiver le bouton d'envoi
            const sendButton = document.getElementById('sendButton');
            sendButton.disabled = true;
            sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';
            
            // Afficher la barre de progression
            const progressBar = document.getElementById('progressBar');
            const progressBarFill = document.getElementById('progressBarFill');
            progressBar.style.display = 'block';
            
            // Simuler la progression
            let width = 0;
            const interval = setInterval(function() {
                if (width >= 90) {
                    clearInterval(interval);
                } else {
                    width += 5;
                    progressBarFill.style.width = width + '%';
                }
            }, 100);
        });

        // Fermer les alertes après 5 secondes
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
