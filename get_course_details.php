<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour accéder à cette page.']);
    exit();
}

// Variables selon le rôle
$is_prof = ($_SESSION['role'] == 'professeur');
$is_eleve = ($_SESSION['role'] == 'eleve');

// Inclure le fichier de configuration de la base de données
include 'db_config.php';

// Vérifier si l'ID du cours est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف الدرس غير صالح']);
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
    echo json_encode(['success' => false, 'message' => 'Erreur de préparation de la requête: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $id_cours);
$stmt->execute();
$result = $stmt->get_result();

// Vérifier si le cours existe
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'الدرس غير موجود']);
    exit();
}

$cours = $result->fetch_assoc();

// Vérifier les permissions d'accès
if ($is_prof && $cours['id_professeur'] != $_SESSION['id_professeur']) {
    // Les professeurs ne peuvent voir que leurs propres cours
    echo json_encode(['success' => false, 'message' => 'لا يمكنك عرض هذا الدرس']);
    exit();
} elseif ($is_eleve) {
    // Vérifier si l'élève est dans la classe du cours
    $sql_check = "SELECT * FROM eleves WHERE id_eleve = ? AND id_classe = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check) {
        echo json_encode(['success' => false, 'message' => 'Erreur de préparation de la requête: ' . $conn->error]);
        exit();
    }
    $stmt_check->bind_param("ii", $_SESSION['id_eleve'], $cours['id_classe']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        // L'élève n'est pas dans la classe du cours
        echo json_encode(['success' => false, 'message' => 'لا يمكنك عرض هذا الدرس']);
        exit();
    }
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

// Générer le HTML pour le modal
ob_start();
?>
<div class="view-course-header">
    <div>
        <h2 class="view-course-title"><?php echo htmlspecialchars($cours['titre']); ?></h2>
        <div class="view-course-meta">
            <div class="view-course-meta-item">
                <i class="fas fa-users"></i>
                <?php echo htmlspecialchars($cours['nom_classe']); ?>
            </div>
            <div class="view-course-meta-item">
                <i class="fas fa-book"></i>
                <?php echo htmlspecialchars($cours['nom_matiere']); ?>
            </div>
            <div class="view-course-meta-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <?php echo htmlspecialchars($cours['nom_professeur']); ?>
            </div>
            <div class="view-course-meta-item">
                <i class="far fa-calendar-alt"></i>
                <?php echo date('d/m/Y', strtotime($cours['date_creation'])); ?>
            </div>
        </div>
    </div>
</div>

<div class="view-course-image" style="<?php echo !empty($cours['illustration']) ? 'background-image: url(\'' . $cours['illustration'] . '\');' : ''; ?>">
    <?php if (empty($cours['illustration'])): ?>
    <div class="view-course-image-placeholder">
        <i class="fas fa-book"></i>
    </div>
    <?php endif; ?>
    <div class="view-course-theme"><?php echo htmlspecialchars($cours['nom_theme']); ?></div>
</div>

<div class="view-course-description">
    <?php echo nl2br(htmlspecialchars($cours['description'])); ?>
</div>

<?php if (!empty($cours['fichier'])): ?>
<div class="view-course-file">
    <div class="view-course-file-icon">
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
    <div class="view-course-file-info">
        <div class="view-course-file-name">ملف الدرس</div>
        <div class="view-course-file-meta">
            <?php echo strtoupper($ext); ?> - <?php echo date('d/m/Y', strtotime($cours['date_creation'])); ?>
        </div>
    </div>
    <a href="<?php echo $cours['fichier']; ?>" class="view-course-file-download" download>
        <i class="fas fa-download"></i>
        تحميل
    </a>
</div>
<?php endif; ?>

<div class="comments-section">
    <h2 class="comments-title">
        <i class="fas fa-comments"></i>
        التعليقات
    </h2>
    
    <?php if ($is_eleve): ?>
    <div class="comment-form">
        <form onsubmit="return submitComment(<?php echo $id_cours; ?>)">
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
<?php
$html = ob_get_clean();

// Retourner le HTML généré
echo json_encode(['success' => true, 'html' => $html]);

// Fermer la connexion
$conn->close();
?>