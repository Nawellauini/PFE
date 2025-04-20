<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté en tant que professeur
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'professeur') {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté en tant que professeur pour accéder à cette page.']);
    exit();
}

// Inclure le fichier de configuration de la base de données
include 'db_config.php';

// Récupérer l'ID du professeur connecté
$id_professeur = $_SESSION['id_professeur'];

// Vérifier si l'ID du cours est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف الدرس غير صالح']);
    exit();
}

$id_cours = intval($_GET['id']);

// Récupérer les informations du cours
$sql = "SELECT * FROM cours WHERE id_cours = ? AND id_professeur = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_cours, $id_professeur);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'لا يمكنك تعديل هذا الدرس']);
    exit();
}

$cours = $result->fetch_assoc();

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

// Récupérer les matières en fonction de la classe sélectionnée
if ($cours['id_classe'] > 0) {
    $sql_matieres = "SELECT * FROM matieres WHERE classe_id = ? ORDER BY nom";
    $stmt_matieres = $conn->prepare($sql_matieres);
    $stmt_matieres->bind_param("i", $cours['id_classe']);
    $stmt_matieres->execute();
    $result_matieres = $stmt_matieres->get_result();
} else {
    $sql_matieres = "SELECT * FROM matieres ORDER BY nom";
    $result_matieres = $conn->query($sql_matieres);
}

// Générer le HTML pour le formulaire de modification
ob_start();
?>
<form action="" method="post" enctype="multipart/form-data" id="editCourseForm">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id_cours" value="<?php echo $id_cours; ?>">
    
    <div class="edit-form-group">
        <label for="titre" class="edit-form-label">عنوان الدرس *</label>
        <input type="text" id="titre" name="titre" class="edit-form-control" value="<?php echo htmlspecialchars($cours['titre']); ?>" required>
    </div>
    
    <div class="edit-form-group">
        <label for="description" class="edit-form-label">وصف الدرس *</label>
        <textarea id="description" name="description" class="edit-form-control" required><?php echo htmlspecialchars($cours['description']); ?></textarea>
    </div>
    
    <div class="edit-form-group">
        <label for="classe" class="edit-form-label">القسم *</label>
        <select id="classe" name="classe" class="edit-form-select" required>
            <option value="">اختر القسم</option>
            <?php
            if ($result_classes->num_rows > 0) {
                while($row = $result_classes->fetch_assoc()) {
                    $selected = ($cours['id_classe'] == $row["id_classe"]) ? "selected" : "";
                    echo "<option value='" . $row["id_classe"] . "' $selected>" . $row["nom_classe"] . "</option>";
                }
            }
            ?>
        </select>
        <div class="classes-info">
            <i class="fas fa-info-circle"></i>
            ملاحظة: يتم عرض فقط الأقسام التي تقوم بتدريسها
        </div>
    </div>
    
    <div class="edit-form-group">
        <label for="theme" class="edit-form-label">الموضوع *</label>
        <select id="theme" name="theme" class="edit-form-select" required>
            <option value="">اختر الموضوع</option>
            <?php
            if ($result_themes->num_rows > 0) {
                while($row = $result_themes->fetch_assoc()) {
                    $selected = ($cours['id_theme'] == $row["id_theme"]) ? "selected" : "";
                    echo "<option value='" . $row["id_theme"] . "' $selected>" . $row["nom_theme"] . "</option>";
                }
            }
            ?>
        </select>
    </div>
    
    <div class="edit-form-group">
        <label for="matiere" class="edit-form-label">المادة *</label>
        <select id="matiere" name="matiere" class="edit-form-select" required>
            <option value="">اختر المادة</option>
            <?php
            if ($result_matieres->num_rows > 0) {
                while($row = $result_matieres->fetch_assoc()) {
                    $selected = ($cours['matiere_id'] == $row["matiere_id"]) ? "selected" : "";
                    echo "<option value='" . $row["matiere_id"] . "' $selected>" . $row["nom"] . "</option>";
                }
            }
            ?>
        </select>
        <div id="matiere-loading" class="loading-text">
            <span class="spinner"></span> جاري تحميل المواد...
        </div>
    </div>
    
    <div class="edit-form-group">
    <label class="edit-form-label">صور توضيحية (اختياري)</label>
    <?php 
    // Récupérer les images existantes
    $sql_images = "SELECT * FROM cours_images WHERE id_cours = ?";
    $stmt_images = $conn->prepare($sql_images);
    if ($stmt_images) {
        $stmt_images->bind_param("i", $id_cours);
        $stmt_images->execute();
        $result_images = $stmt_images->get_result();
        
        if ($result_images->num_rows > 0) {
            echo '<div class="current-images">';
            while ($image = $result_images->fetch_assoc()) {
                echo '<div class="current-image-item">';
                echo '<img src="' . $image['chemin'] . '" alt="صورة الدرس">';
                echo '<div class="image-actions">';
                echo '<button type="button" class="btn-delete-image" onclick="deleteImage(' . $image['id_image'] . ')">';
                echo '<i class="fas fa-trash-alt"></i>';
                echo '</button>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else if (!empty($cours['illustration'])) {
            // Afficher l'ancienne illustration (pour la compatibilité)
            echo '<div class="current-image">';
            echo '<img src="' . $cours['illustration'] . '" alt="صورة الدرس">';
            echo '</div>';
        }
    }
    ?>
    <div class="form-file">
        <div class="file-input-wrapper">
            <input type="file" id="illustrations" name="illustrations[]" class="file-input" accept="image/*" multiple>
            <label for="illustrations" class="file-input-label">
                <i class="fas fa-images"></i>
                إضافة صور جديدة
            </label>
        </div>
        <div id="illustrations_preview" class="images-preview"></div>
    </div>
    <small>الصور المسموح بها: JPG, JPEG, PNG, GIF</small>
</div>
    
    <div class="edit-form-group">
        <label class="edit-form-label">صورة توضيحية (اختياري)</label>
        <?php if (!empty($cours['illustration'])): ?>
            <div class="current-image">
                <img src="<?php echo $cours['illustration']; ?>" alt="صورة الدرس">
            </div>
        <?php endif; ?>
        <div class="form-file">
            <div class="file-input-wrapper">
                <input type="file" id="illustration" name="illustration" class="file-input" accept="image/*">
                <label for="illustration" class="file-input-label">
                    <i class="fas fa-image"></i>
                    تغيير الصورة
                </label>
            </div>
            <div id="illustration-name" class="file-name"></div>
        </div>
        <small>الصور المسموح بها: JPG, JPEG, PNG, GIF</small>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editCourseModal')">
            إلغاء
        </button>
        <button type="submit" class="btn btn-warning">
            <i class="fas fa-save"></i>
            حفظ التعديلات
        </button>
    </div>
</form>
<?php
$html = ob_get_clean();

// Retourner le HTML généré
echo json_encode(['success' => true, 'html' => $html]);

// Fermer la connexion
$conn->close();
?>