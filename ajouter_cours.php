<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté en tant que professeur
if (!isset($_SESSION['id_professeur'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit();
}

// Inclure le fichier de configuration de la base de données
include 'db_config.php';

// Récupérer l'ID du professeur connecté
$id_professeur = $_SESSION['id_professeur'];

// Récupérer les classes enseignées par ce professeur
$sql_classes = "SELECT c.* FROM classes c 
                INNER JOIN professeurs_classes pc ON c.id_classe = pc.id_classe 
                WHERE pc.id_professeur = ? 
                ORDER BY c.nom_classe";
$stmt_classes = $conn->prepare($sql_classes);
$stmt_classes->bind_param("i", $id_professeur);
$stmt_classes->execute();
$result_classes = $stmt_classes->get_result();

// Récupérer tous les thèmes
$sql_themes = "SELECT * FROM themes ORDER BY nom_theme";
$result_themes = $conn->query($sql_themes);

// Récupérer les informations du professeur connecté
$sql_prof = "SELECT * FROM professeurs WHERE id_professeur = ?";
$stmt_prof = $conn->prepare($sql_prof);
$stmt_prof->bind_param("i", $id_professeur);
$stmt_prof->execute();
$prof = $stmt_prof->get_result()->fetch_assoc();

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $titre = $conn->real_escape_string($_POST['titre']);
    $description = $conn->real_escape_string($_POST['description']);
    $id_classe = intval($_POST['classe']);
    $id_theme = intval($_POST['theme']);
    $id_matiere = intval($_POST['matiere']);
    
    // Vérifier si la classe sélectionnée est enseignée par ce professeur
    $sql_check_classe = "SELECT * FROM professeurs_classes WHERE id_professeur = ? AND id_classe = ?";
    $stmt_check = $conn->prepare($sql_check_classe);
    $stmt_check->bind_param("ii", $id_professeur, $id_classe);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows == 0) {
        $error = "لا يمكنك إضافة درس لهذا الفصل.";
    } else {
        // Vérifier si un fichier a été téléchargé
        $fichier_path = "";
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] == 0) {
            $allowed = array('pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx');
            $filename = $_FILES['fichier']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_dir = 'uploads/';
                
                // Créer le répertoire s'il n'existe pas
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $fichier_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['fichier']['tmp_name'], $fichier_path)) {
                    // Le fichier a été téléchargé avec succès
                } else {
                    $error = "Erreur lors du téléchargement du fichier.";
                }
            } else {
                $error = "Type de fichier non autorisé.";
            }
        }
        
        // Vérifier si une image a été téléchargée
        $illustration_path = "";
        if (isset($_FILES['illustration']) && $_FILES['illustration']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            $filename = $_FILES['illustration']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_dir = 'uploads/images/';
                
                // Créer le répertoire s'il n'existe pas
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $illustration_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['illustration']['tmp_name'], $illustration_path)) {
                    // L'image a été téléchargée avec succès
                } else {
                    $error = "Erreur lors du téléchargement de l'image.";
                }
            } else {
                $error = "Type d'image non autorisé.";
            }
        }
        
        // Si aucune erreur, insérer le cours dans la base de données
        if (!isset($error)) {
            $sql = "INSERT INTO cours (id_professeur, id_classe, id_theme, matiere_id, titre, description, fichier, illustration, date_creation) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiiissss", $id_professeur, $id_classe, $id_theme, $id_matiere, $titre, $description, $fichier_path, $illustration_path);
            
            if ($stmt->execute()) {
                // Rediriger vers la liste des cours avec un message de succès
                header("Location: liste_cours.php?message=تمت إضافة الدرس بنجاح");
                exit();
            } else {
                $error = "خطأ في إضافة الدرس: " . $conn->error;
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
    <title>إضافة درس جديد</title>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Variables CSS */
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        /* Styles généraux */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f7fa;
            color: var(--secondary-color);
            line-height: 1.6;
            direction: rtl;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            position: relative;
        }

        .card-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
        }

        .card-body {
            padding: 30px;
        }

        /* Styles des messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
        }

        .message i {
            margin-left: 10px;
            font-size: 20px;
        }

        .message.error {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        /* Formulaire */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 10px center;
            background-size: 16px;
            padding-left: 30px;
            transition: var(--transition);
        }

        .form-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .form-file {
            display: flex;
            flex-direction: column;
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
            transition: var(--transition);
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

        /* Boutons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn i {
            margin-left: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--light-color);
            color: var(--secondary-color);
        }

        .btn-secondary:hover {
            background-color: #dfe6e9;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        /* Spinner de chargement */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            display: none;
            font-size: 14px;
            color: #777;
            text-align: center;
            padding: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 15px auto;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        /* Classes info */
        .classes-info {
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: var(--border-radius);
            padding: 10px 15px;
            margin-top: 5px;
            font-size: 14px;
            color: var(--primary-dark);
        }
        
        .classes-info i {
            margin-left: 5px;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Carte principale -->
        <div class="card animate-fadeInUp">
            <div class="card-header">
                <h1>إضافة درس جديد</h1>
            </div>
            
            <div class="card-body">
                <!-- Message d'erreur -->
                <?php if (isset($error)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulaire d'ajout de cours -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="titre" class="form-label">عنوان الدرس *</label>
                        <input type="text" id="titre" name="titre" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">وصف الدرس *</label>
                        <textarea id="description" name="description" class="form-control" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="classe" class="form-label">القسم *</label>
                        <select id="classe" name="classe" class="form-select" required>
                            <option value="">اختر القسم</option>
                            <?php
                            if ($result_classes->num_rows > 0) {
                                while($row = $result_classes->fetch_assoc()) {
                                    echo "<option value='" . $row["id_classe"] . "'>" . $row["nom_classe"] . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <div class="classes-info">
                            <i class="fas fa-info-circle"></i>
                            ملاحظة: يتم عرض فقط الأقسام التي تقوم بتدريسها
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="theme" class="form-label">الموضوع *</label>
                        <select id="theme" name="theme" class="form-select" required>
                            <option value="">اختر الموضوع</option>
                            <?php
                            if ($result_themes->num_rows > 0) {
                                while($row = $result_themes->fetch_assoc()) {
                                    echo "<option value='" . $row["id_theme"] . "'>" . $row["nom_theme"] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Champ pour la matière avec filtrage dynamique -->
                    <div class="form-group">
                        <label for="matiere" class="form-label">المادة *</label>
                        <select id="matiere" name="matiere" class="form-select" required>
                            <option value="">اختر المادة</option>
                        </select>
                        <div id="matiere-loading" class="loading-text">
                            <span class="spinner"></span> جاري تحميل المواد...
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">ملف الدرس (اختياري)</label>
                        <div class="form-file">
                            <div class="file-input-wrapper">
                                <input type="file" id="fichier" name="fichier" class="file-input" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx">
                                <label for="fichier" class="file-input-label">
                                    <i class="fas fa-upload"></i>
                                    اختر ملفًا
                                </label>
                            </div>
                            <div id="fichier-name" class="file-name"></div>
                        </div>
                        <small>الملفات المسموح بها: PDF, Word, PowerPoint, Excel</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">صورة توضيحية (اختياري)</label>
                        <div class="form-file">
                            <div class="file-input-wrapper">
                                <input type="file" id="illustration" name="illustration" class="file-input" accept="image/*">
                                <label for="illustration" class="file-input-label">
                                    <i class="fas fa-image"></i>
                                    اختر صورة
                                </label>
                            </div>
                            <div id="illustration-name" class="file-name"></div>
                        </div>
                        <small>الصور المسموح بها: JPG, JPEG, PNG, GIF</small>
                    </div>
                    
                    <div class="form-actions">
                        <a href="liste_cours.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i>
                            العودة إلى القائمة
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            حفظ الدرس
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Afficher le nom du fichier sélectionné
        document.getElementById('fichier').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : '';
            document.getElementById('fichier-name').textContent = fileName;
        });
        
        document.getElementById('illustration').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : '';
            document.getElementById('illustration-name').textContent = fileName;
        });
        
        // Filtrage dynamique des matières en fonction de la classe sélectionnée
        const classeSelect = document.getElementById('classe');
        const matiereSelect = document.getElementById('matiere');
        const matiereLoading = document.getElementById('matiere-loading');
        
        classeSelect.addEventListener('change', function() {
            const classeId = this.value;
            
            if (!classeId) {
                // Si aucune classe n'est sélectionnée, vider le select des matières
                while (matiereSelect.options.length > 1) {
                    matiereSelect.remove(1);
                }
                return;
            }
            
            // Afficher l'indicateur de chargement
            matiereLoading.style.display = 'block';
            
            // Récupérer les matières pour cette classe via AJAX
            fetch('get_matieres_cours.php?classe_id=' + classeId)
                .then(response => response.json())
                .then(data => {
                    // Masquer l'indicateur de chargement
                    matiereLoading.style.display = 'none';
                    
                    // Vider le select des matières sauf la première option
                    while (matiereSelect.options.length > 1) {
                        matiereSelect.remove(1);
                    }
                    
                    // Ajouter les nouvelles options
                    data.forEach(matiere => {
                        const option = document.createElement('option');
                        option.value = matiere.matiere_id;
                        option.textContent = matiere.nom;
                        matiereSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    matiereLoading.style.display = 'none';
                });
        });
    </script>
</body>
</html>

<?php
// Fermer la connexion
$conn->close();
?>
