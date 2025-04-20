<form id="editForm" method="POST" action="traitement_rapport.php">
    <input type="hidden" name="id" value="<?= $rapport['id'] ?>">
    
    <div class="form-group">
        <label for="titre">العنوان</label>
        <input type="text" class="form-control" id="titre" name="titre" value="<?= htmlspecialchars($rapport['titre']) ?>" required>
    </div>

    <div class="form-group">
        <label for="id_classe">القسم</label>
        <select class="form-control" id="id_classe" name="id_classe" required>
            <?php foreach ($classes as $classe): ?>
                <option value="<?= $classe['id_classe'] ?>" <?= $classe['id_classe'] == $rapport['id_classe'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($classe['nom_classe']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="commentaires">التعليقات</label>
        <textarea class="form-control" id="commentaires" name="commentaires" rows="5" required><?= htmlspecialchars($rapport['commentaires']) ?></textarea>
    </div>

    <div class="form-group">
        <label for="recommandations">التوصيات</label>
        <textarea class="form-control" id="recommandations" name="recommandations" rows="5" required><?= htmlspecialchars($rapport['recommandations']) ?></textarea>
    </div>

    <div class="text-center mt-4">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
    </div>
</form> 