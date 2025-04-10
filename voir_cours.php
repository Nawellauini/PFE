<?php
// Démarrer la session
session_start();

// Inclure le fichier de configuration de la base de données
include 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Variables selon le rôle
$is_prof = ($_SESSION['role'] == 'professeur');
$is_eleve = ($_SESSION['role'] == 'eleve');

// Vérifier si l'ID du cours est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: liste_cours.php");
    exit();
}

$id_cours = intval($_GET['id']);

// Récupérer les détails du cours
$sql = "SELECT c.*, cl.nom_classe, t.nom_theme, m.nom as nom_matiere, 
        CONCAT(p.prenom, ' ', p.nom) as nom_professeur
        FROM cours c
        JOIN classes cl ON c.id_classe = cl.id_classe
        JOIN themes t ON c.id_theme = t.id_theme
        JOIN matieres m ON c.matiere_id = m.matiere_id
        JOIN professeurs p ON c.id_professeur = p.id_professeur
        WHERE c.id_cours = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    // Gérer l'erreur de préparation de la requête
    die("Erreur de préparation de la requête: " . $conn->error);
}

$stmt->bind_param("i", $id_cours);
$stmt->execute();
$result = $stmt->get_result();

// Vérifier si le cours existe
if ($result->num_rows === 0) {
    header("Location: liste_cours.php");
    exit();
}

$cours = $result->fetch_assoc();

// Vérifier les permissions d'accès
if ($is_prof && $cours['id_professeur'] != $_SESSION['id_professeur']) {
    // Les professeurs ne peuvent voir que leurs propres cours
    header("Location: liste_cours.php");
    exit();
} elseif ($is_eleve) {
    // Vérifier si l'élève est dans la classe du cours
    $sql_check = "SELECT * FROM eleves WHERE id_eleve = ? AND id_classe = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check) {
        die("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt_check->bind_param("ii", $_SESSION['id_eleve'], $cours['id_classe']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        // L'élève n'est pas dans la classe du cours
        header("Location: liste_cours.php");
        exit();
    }
}

// Traitement du formulaire de commentaire (si applicable)
if ($is_eleve && isset($_POST['commentaire']) && !empty($_POST['commentaire'])) {
    $commentaire = $conn->real_escape_string($_POST['commentaire']);
    $id_eleve = $_SESSION['id_eleve'];
    
    // Vérifier si la table commentaires_cours existe
    $check_table = $conn->query("SHOW TABLES LIKE 'commentaires_cours'");
    if ($check_table->num_rows == 0) {
        // Créer la table si elle n'existe pas
        $create_table = "CREATE TABLE commentaires_cours (
            id_commentaire INT(11) NOT NULL AUTO_INCREMENT,
            id_cours INT(11) NOT NULL,
            id_eleve INT(11) NOT NULL,
            commentaire TEXT NOT NULL,
            date_commentaire DATETIME NOT NULL,
            PRIMARY KEY (id_commentaire),
            KEY id_cours (id_cours),
            KEY id_eleve (id_eleve)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($create_table);
    }
    
    $sql_comment = "INSERT INTO commentaires_cours (id_cours, id_eleve, commentaire, date_commentaire) 
                    VALUES (?, ?, ?, NOW())";
    $stmt_comment = $conn->prepare($sql_comment);
    if (!$stmt_comment) {
        die("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt_comment->bind_param("iis", $id_cours, $id_eleve, $commentaire);
    $stmt_comment->execute();
    
    // Rediriger pour éviter la soumission multiple
    header("Location: voir_cours.php?id=" . $id_cours . "&comment_added=1");
    exit();
}

// Récupérer les commentaires du cours
$comments = [];
$check_table = $conn->query("SHOW TABLES LIKE 'commentaires_cours'");
if ($check_table->num_rows > 0) {
    $sql_comments = "SELECT c.*, CONCAT(e.prenom, ' ', e.nom) as nom_eleve
                    FROM commentaires_cours c
                    JOIN eleves e ON c.id_eleve = e.id_eleve
                    WHERE c.id_cours = ?
                    ORDER BY c.date_commentaire DESC";
    $stmt_comments = $conn->prepare($sql_comments);
    if ($stmt_comments) {
        $stmt_comments->bind_param("i", $id_cours);
        $stmt_comments->execute();
        $comments_result = $stmt_comments->get_result();
        
        while ($comment = $comments_result->fetch_assoc()) {
            $comments[] = $comment;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cours['titre']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e6091;
            --primary-dark: #0d4a77;
            --secondary-color: #2a9d8f;
            --accent-color: #e9c46a;
            --text-color: #264653;
            --text-light: #546a7b;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
            --error-color: #e76f51;
            --success-color: #2a9d8f;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .btn {
            padding: 10px 20px;
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

        .btn-secondary {
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-left: 10px;
            font-size: 20px;
        }

        .alert-success {
            background-color: rgba(42, 157, 143, 0.2);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .course-title-section {
            flex: 1;
        }

        .course-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .course-meta {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .course-meta-item {
            display: flex;
            align-items: center;
            margin-left: 20px;
            margin-bottom: 5px;
            font-size: 16px;
            color: var(--text-light);
        }

        .course-meta-item i {
            margin-left: 8px;
            color: var(--primary-color);
        }

        .course-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .course-image {
            height: 300px;
            background-color: #e9ecef;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .course-image-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 64px;
        }

        .course-theme {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .course-content {
            padding: 30px;
        }

        .course-description {
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.8;
            white-space: pre-line;
        }

        .course-file {
            background-color: #e9ecef;
            border-radius: var(--border-radius);
            padding: 20px;
            display: flex;
            align-items: center;
            margin-top: 20px;
        }

        .course-file-icon {
            font-size: 40px;
            color: var(--primary-color);
            margin-left: 20px;
        }

        .course-file-info {
            flex: 1;
        }

        .course-file-name {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .course-file-meta {
            font-size: 14px;
            color: var(--text-light);
        }

        .course-file-download {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
        }

        .course-file-download i {
            margin-left: 8px;
        }

        .course-file-download:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .comments-section {
            margin-top: 40px;
        }

        .comments-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-color);
            display: flex;
            align-items: center;
        }

        .comments-title i {
            margin-left: 10px;
            color: var(--primary-color);
        }

        .comment-form {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 30px;
        }

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
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 96, 145, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .comments-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .comment-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .comment-author {
            font-weight: 500;
            color: var(--primary-color);
        }

        .comment-date {
            font-size: 14px;
            color: var(--text-light);
        }

        .comment-content {
            font-size: 16px;
            line-height: 1.6;
        }

        .no-comments {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            text-align: center;
            color: var(--text-light);
        }

        .no-comments i {
            font-size: 48px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .course-header {
                flex-direction: column;
            }
            
            .course-image {
                height: 200px;
            }
            
            .course-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="course-header">
            <div class="course-title-section">
                <h1 class="course-title"><?php echo htmlspecialchars($cours['titre']); ?></h1>
                <div class="course-meta">
                    <div class="course-meta-item">
                        <i class="fas fa-users"></i>
                        <?php echo htmlspecialchars($cours['nom_classe']); ?>
                    </div>
                    <div class="course-meta-item">
                        <i class="fas fa-book"></i>
                        <?php echo htmlspecialchars($cours['nom_matiere']); ?>
                    </div>
                    <div class="course-meta-item">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <?php echo htmlspecialchars($cours['nom_professeur']); ?>
                    </div>
                    <div class="course-meta-item">
                        <i class="far fa-calendar-alt"></i>
                        <?php echo date('d/m/Y', strtotime($cours['date_creation'])); ?>
                    </div>
                </div>
            </div>
            <a href="liste_cours.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i>
                العودة إلى القائمة
            </a>
        </div>
        
        <?php if (isset($_GET['comment_added'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            تمت إضافة تعليقك بنجاح!
        </div>
        <?php endif; ?>
        
        <div class="course-card">
            <div class="course-image" style="<?php echo !empty($cours['illustration']) ? 'background-image: url(\'' . $cours['illustration'] . '\');' : ''; ?>">
                <?php if (empty($cours['illustration'])): ?>
                <div class="course-image-placeholder">
                    <i class="fas fa-book"></i>
                </div>
                <?php endif; ?>
                <div class="course-theme"><?php echo htmlspecialchars($cours['nom_theme']); ?></div>
            </div>
            <div class="course-content">
                <div class="course-description">
                    <?php echo nl2br(htmlspecialchars($cours['description'])); ?>
                </div>
                
                <?php if (!empty($cours['fichier'])): ?>
                <div class="course-file">
                    <div class="course-file-icon">
                        <?php
                        $ext = pathinfo($cours['fichier'], PATHINFO_EXTENSION);
                        $icon = 'fa-file';
                        
                        if (in_array($ext, ['pdf'])) {
                            $icon = 'fa-file-pdf';
                        } elseif (in_array($ext, ['doc', 'docx'])) {
                            $icon = 'fa-file-word';
                        } elseif (in_array($ext, ['xls', 'xlsx'])) {
                            $icon = 'fa-file-excel';
                        } elseif (in_array($ext, ['ppt', 'pptx'])) {
                            $icon = 'fa-file-powerpoint';
                        }
                        ?>
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="course-file-info">
                        <div class="course-file-name">ملف الدرس</div>
                        <div class="course-file-meta">
                            <?php echo strtoupper($ext); ?> - <?php echo date('d/m/Y', strtotime($cours['date_creation'])); ?>
                        </div>
                    </div>
                    <a href="<?php echo $cours['fichier']; ?>" class="course-file-download" download>
                        <i class="fas fa-download"></i>
                        تحميل
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="comments-section">
            <h2 class="comments-title">
                <i class="fas fa-comments"></i>
                التعليقات
            </h2>
            
            <?php if ($is_eleve): ?>
            <div class="comment-form">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id_cours); ?>" method="post">
                    <div class="form-group">
                        <label for="commentaire" class="form-label">أضف تعليقًا</label>
                        <textarea id="commentaire" name="commentaire" class="form-control" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        إرسال
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="comments-list">
                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-card">
                            <div class="comment-header">
                                <div class="comment-author"><?php echo htmlspecialchars($comment['nom_eleve']); ?></div>
                                <div class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['date_commentaire'])); ?></div>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['commentaire'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-comments">
                        <i class="far fa-comment-dots"></i>
                        <p>لا توجد تعليقات حتى الآن.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
