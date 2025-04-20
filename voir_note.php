<?php
session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    echo '<div class="alert alert-danger">
            <div class="alert-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="alert-content">
                <div class="alert-title">غير مصرح به</div>
                <div class="alert-message">يجب تسجيل الدخول للوصول إلى هذه الصفحة.</div>
            </div>
          </div>';
    exit;
}

// Vérifier si l'ID de la note est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">
            <div class="alert-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="alert-content">
                <div class="alert-title">خطأ</div>
                <div class="alert-message">معرف النتيجة غير صالح.</div>
            </div>
          </div>';
    exit;
}

$note_id = intval($_GET['id']);
$id_professeur = $_SESSION['id_professeur'];

// Récupérer les détails de la note
$query = "SELECT n.id, n.note, n.trimestre, n.id_eleve, n.matiere_id,
          e.nom as nom_eleve, e.prenom as prenom_eleve, e.email as email_eleve,
          c.id_classe, c.nom_classe,
          m.nom as nom_matiere
          FROM notes n
          JOIN eleves e ON n.id_eleve = e.id_eleve
          JOIN classes c ON e.id_classe = c.id_classe
          JOIN matieres m ON n.matiere_id = m.matiere_id
          JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
          WHERE n.id = ? AND pc.id_professeur = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $note_id, $id_professeur);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">
            <div class="alert-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="alert-content">
                <div class="alert-title">خطأ</div>
                <div class="alert-message">النتيجة غير موجودة أو ليس لديك صلاحية الوصول إليها.</div>
            </div>
          </div>';
    exit;
}

$note = $result->fetch_assoc();

// Récupérer la moyenne de la classe pour cette matière et ce trimestre
$query_moyenne = "SELECT AVG(note) as moyenne
                 FROM notes
                 JOIN eleves ON notes.id_eleve = eleves.id_eleve
                 WHERE eleves.id_classe = ? AND notes.matiere_id = ? AND notes.trimestre = ?";
$stmt_moyenne = $conn->prepare($query_moyenne);
$stmt_moyenne->bind_param("iii", $note['id_classe'], $note['matiere_id'], $note['trimestre']);
$stmt_moyenne->execute();
$result_moyenne = $stmt_moyenne->get_result();
$moyenne = $result_moyenne->fetch_assoc()['moyenne'];

// Récupérer le rang de l'élève dans cette matière et ce trimestre
$query_rang = "SELECT id_eleve, note, 
              (SELECT COUNT(*) + 1 
               FROM notes n2 
               JOIN eleves e2 ON n2.id_eleve = e2.id_eleve 
               WHERE e2.id_classe = ? AND n2.matiere_id = ? AND n2.trimestre = ? AND n2.note > n1.note) as rang
              FROM notes n1
              WHERE n1.id_eleve = ? AND n1.matiere_id = ? AND n1.trimestre = ?";
$stmt_rang = $conn->prepare($query_rang);
$stmt_rang->bind_param("iiiiii", $note['id_classe'], $note['matiere_id'], $note['trimestre'], $note['id_eleve'], $note['matiere_id'], $note['trimestre']);
$stmt_rang->execute();
$result_rang = $stmt_rang->get_result();
$rang = $result_rang->fetch_assoc()['rang'];

// Récupérer la note la plus élevée et la plus basse de la classe
$query_extremes = "SELECT MAX(note) as max_note, MIN(note) as min_note
                  FROM notes
                  JOIN eleves ON notes.id_eleve = eleves.id_eleve
                  WHERE eleves.id_classe = ? AND notes.matiere_id = ? AND notes.trimestre = ?";
$stmt_extremes = $conn->prepare($query_extremes);
$stmt_extremes->bind_param("iii", $note['id_classe'], $note['matiere_id'], $note['trimestre']);
$stmt_extremes->execute();
$result_extremes = $stmt_extremes->get_result();
$extremes = $result_extremes->fetch_assoc();

// Récupérer le nombre d'élèves dans la classe
$query_count = "SELECT COUNT(*) as total
               FROM eleves
               WHERE id_classe = ?";
$stmt_count = $conn->prepare($query_count);
$stmt_count->bind_param("i", $note['id_classe']);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_eleves = $result_count->fetch_assoc()['total'];

// Récupérer l'historique des notes de l'élève dans cette matière
$query_historique = "SELECT trimestre, note
                    FROM notes
                    WHERE id_eleve = ? AND matiere_id = ?
                    ORDER BY trimestre";
$stmt_historique = $conn->prepare($query_historique);
$stmt_historique->bind_param("ii", $note['id_eleve'], $note['matiere_id']);
$stmt_historique->execute();
$result_historique = $stmt_historique->get_result();

// Déterminer la couleur de la note
function getNoteColor($note) {
    if ($note < 10) {
        return 'danger';
    } elseif ($note < 14) {
        return 'warning';
    } else {
        return 'success';
    }
}

$note_color = getNoteColor($note['note']);
?>

<div class="note-details">
    <div class="note-header">
        <div class="note-badge note-badge-<?= $note_color ?>"><?= number_format($note['note'], 1) ?></div>
        <div class="note-info">
            <h3 class="note-title"><?= htmlspecialchars($note['nom_eleve'] . ' ' . $note['prenom_eleve']) ?></h3>
            <p class="note-subtitle"><?= htmlspecialchars($note['nom_matiere']) ?> - الثلاثي <?= $note['trimestre'] ?></p>
        </div>
    </div>
    
    <div class="note-grid">
        <div class="note-stat">
            <div class="stat-label">القسم</div>
            <div class="stat-value"><?= htmlspecialchars($note['nom_classe']) ?></div>
        </div>
        
        <div class="note-stat">
            <div class="stat-label">معدل القسم</div>
            <div class="stat-value"><?= number_format($moyenne, 2) ?></div>
        </div>
        
        <div class="note-stat">
            <div class="stat-label">الترتيب</div>
            <div class="stat-value"><?= $rang ?> / <?= $total_eleves ?></div>
        </div>
        
        <div class="note-stat">
            <div class="stat-label">أعلى نتيجة</div>
            <div class="stat-value"><?= number_format($extremes['max_note'], 1) ?></div>
        </div>
        
        <div class="note-stat">
            <div class="stat-label">أدنى نتيجة</div>
            <div class="stat-value"><?= number_format($extremes['min_note'], 1) ?></div>
        </div>
        
        <div class="note-stat">
            <div class="stat-label">تاريخ التسجيل</div>
            <div class="stat-value"><?= date('Y-m-d') ?></div>
        </div>
    </div>
    
    <?php if ($result_historique->num_rows > 1): ?>
    <div class="note-section">
        <h4 class="section-title">تطور النتائج</h4>
        <div class="note-progress">
            <?php
            $historique = [];
            while ($row = $result_historique->fetch_assoc()) {
                $historique[$row['trimestre']] = $row['note'];
            }
            
            for ($i = 1; $i <= 3; $i++) {
                $has_note = isset($historique[$i]);
                $current_note = $has_note ? $historique[$i] : null;
                $note_color = $has_note ? getNoteColor($current_note) : 'muted';
                
                echo '<div class="progress-item">';
                echo '<div class="progress-label">الثلاثي ' . $i . '</div>';
                if ($has_note) {
                    echo '<div class="progress-badge progress-badge-' . $note_color . '">' . number_format($current_note, 1) . '</div>';
                } else {
                    echo '<div class="progress-badge progress-badge-muted">-</div>';
                }
                echo '</div>';
            }
            ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="note-section">
        <h4 class="section-title">معلومات التلميذ</h4>
        <div class="student-info">
            <div class="info-item">
                <div class="info-label">الاسم الكامل</div>
                <div class="info-value"><?= htmlspecialchars($note['nom_eleve'] . ' ' . $note['prenom_eleve']) ?></div>
            </div>
            
            <?php if (!empty($note['email_eleve'])): ?>
            <div class="info-item">
                <div class="info-label">البريد الإلكتروني</div>
                <div class="info-value"><?= htmlspecialchars($note['email_eleve']) ?></div>
            </div>
            <?php endif; ?>
            
            <div class="info-item">
                <div class="info-label">القسم</div>
                <div class="info-value"><?= htmlspecialchars($note['nom_classe']) ?></div>
            </div>
        </div>
    </div>
</div>

<style>
    .note-details {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .note-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border);
    }
    
    .note-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 4rem;
        height: 4rem;
        border-radius: 50%;
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    
    .note-badge-success {
        background: linear-gradient(45deg, #06d6a0, #0deeb1);
    }
    
    .note-badge-warning {
        background: linear-gradient(45deg, #ffd166, #ffe066);
        color: #1d3557;
    }
    
    .note-badge-danger {
        background: linear-gradient(45deg, #ef476f, #ff5a5f);
    }
    
    .note-info {
        flex: 1;
    }
    
    .note-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .note-subtitle {
        color: var(--muted-foreground);
        font-size: 0.875rem;
    }
    
    .note-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
        background-color: var(--muted);
        border-radius: var(--radius);
        padding: 1rem;
    }
    
    .note-stat {
        background-color: var(--card);
        border-radius: var(--radius);
        padding: 1rem;
        box-shadow: var(--shadow);
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .note-stat:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
    }
    
    .stat-label {
        font-size: 0.875rem;
        color: var(--muted-foreground);
        margin-bottom: 0.5rem;
    }
    
    .stat-value {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--foreground);
    }
    
    .note-section {
        margin-top: 1rem;
    }
    
    .section-title {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border);
        color: var(--foreground);
    }
    
    .note-progress {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
    }
    
    .progress-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        background-color: var(--card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        transition: all 0.3s ease;
    }
    
    .progress-item:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
    }
    
    .progress-label {
        font-size: 0.875rem;
        color: var(--muted-foreground);
    }
    
    .progress-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        font-size: 1.125rem;
        font-weight: 700;
        color: white;
    }
    
    .progress-badge-success {
        background: linear-gradient(45deg, #06d6a0, #0deeb1);
    }
    
    .progress-badge-warning {
        background: linear-gradient(45deg, #ffd166, #ffe066);
        color: #1d3557;
    }
    
    .progress-badge-danger {
        background: linear-gradient(45deg, #ef476f, #ff5a5f);
    }
    
    .progress-badge-muted {
        background: linear-gradient(45deg, #e2e8f0, #f1f5f9);
        color: var(--muted-foreground);
    }
    
    .student-info {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .info-item {
        background-color: var(--muted);
        border-radius: var(--radius);
        padding: 1rem;
    }
    
    .info-label {
        font-size: 0.875rem;
        color: var(--muted-foreground);
        margin-bottom: 0.5rem;
    }
    
    .info-value {
        font-size: 1rem;
        font-weight: 500;
        color: var(--foreground);
    }
    
    @media (max-width: 640px) {
        .note-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .note-progress {
            flex-direction: column;
        }
    }
</style>
