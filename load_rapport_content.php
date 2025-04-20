<?php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Veuillez vous connecter']);
    exit;
}

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

switch ($action) {
    case 'view':
        // Charger le contenu pour la visualisation
        $query = "SELECT r.*, c.nom_classe, p.nom as professeur_nom, p.prenom as professeur_prenom 
                 FROM rapports r 
                 JOIN classes c ON r.id_classe = c.id_classe 
                 JOIN professeurs p ON r.id_professeur = p.id_professeur 
                 WHERE r.id_rapport = ? AND r.id_professeur = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die('Erreur de préparation de la requête: ' . $conn->error);
        }
        $stmt->bind_param("ii", $id, $_SESSION['id_professeur']);
        $stmt->execute();
        $result = $stmt->get_result();
        $rapport = $result->fetch_assoc();
        ?>
        <div class="rapport-content">
            <h3><?php echo htmlspecialchars($rapport['titre']); ?></h3>
            <p><strong>القسم:</strong> <?php echo htmlspecialchars($rapport['nom_classe']); ?></p>
            <p><strong>المعلم:</strong> <?php echo htmlspecialchars($rapport['professeur_nom'] . ' ' . $rapport['professeur_prenom']); ?></p>
            <p><strong>التعليقات:</strong></p>
            <div class="commentaires"><?php echo nl2br(htmlspecialchars($rapport['commentaires'])); ?></div>
            <p><strong>التوصيات:</strong></p>
            <div class="recommandations"><?php echo nl2br(htmlspecialchars($rapport['recommandations'])); ?></div>
            <p><strong>تاريخ الإنشاء:</strong> <?php echo date('d/m/Y', strtotime($rapport['date_creation'])); ?></p>
        </div>
        <?php
        break;

    case 'edit':
        // Charger le contenu pour l'édition
        $query = "SELECT * FROM rapports WHERE id_rapport = ? AND id_professeur = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die('Erreur de préparation de la requête: ' . $conn->error);
        }
        $stmt->bind_param("ii", $id, $_SESSION['id_professeur']);
        $stmt->execute();
        $result = $stmt->get_result();
        $rapport = $result->fetch_assoc();

        // Récupérer la liste des classes
        $query = "SELECT id_classe, nom_classe FROM classes ORDER BY nom_classe";
        $result = $conn->query($query);
        $classes = [];
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
        ?>
        <form id="editForm" method="POST" action="traitement_rapport.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <div class="form-group">
                <label class="form-label">العنوان</label>
                <input type="text" name="titre" class="form-control" value="<?php echo htmlspecialchars($rapport['titre']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">التعليقات</label>
                <textarea name="commentaires" class="form-control" rows="4" required><?php echo htmlspecialchars($rapport['commentaires']); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">التوصيات</label>
                <textarea name="recommandations" class="form-control" rows="4" required><?php echo htmlspecialchars($rapport['recommandations']); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">القسم</label>
                <select name="id_classe" class="form-control" required>
                    <?php foreach ($classes as $classe): ?>
                        <option value="<?php echo $classe['id_classe']; ?>" <?php echo $classe['id_classe'] == $rapport['id_classe'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($classe['nom_classe']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
            </div>
        </form>
        <?php
        break;

    default:
        echo "Action non reconnue";
        break;
}
?> 