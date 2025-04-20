<?php
session_start();
require_once 'config.php';

// Récupérer la liste des classes
$query = "SELECT id_classe, nom_classe FROM classes ORDER BY nom_classe";
$result = $conn->query($query);
$classes = [];
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
?>

<form id="addForm" method="POST" action="traitement_rapport.php">
    <input type="hidden" name="action" value="add">
    <div class="form-group">
        <label class="form-label">العنوان</label>
        <input type="text" name="titre" class="form-control" required>
    </div>
    <div class="form-group">
        <label class="form-label">التعليقات</label>
        <textarea name="commentaires" class="form-control" rows="4" required></textarea>
    </div>
    <div class="form-group">
        <label class="form-label">التوصيات</label>
        <textarea name="recommandations" class="form-control" rows="4" required></textarea>
    </div>
    <div class="form-group">
        <label class="form-label">القسم</label>
        <select name="id_classe" class="form-control" required>
            <?php foreach ($classes as $classe): ?>
                <option value="<?php echo $classe['id_classe']; ?>">
                    <?php echo htmlspecialchars($classe['nom_classe']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary">حفظ</button>
    </div>
</form> 