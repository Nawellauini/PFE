<?php
// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=gestion_notes', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (isset($_GET['classe_id'])) {
    $classe_id = $_GET['classe_id'];

    // Récupérer les élèves de la classe
    $query = "SELECT id, nom FROM eleves WHERE classe_id = :classe_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['classe_id' => $classe_id]);
    $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les matières et professeurs de la classe
    $query = "SELECT m.id AS matiere_id, m.nom AS matiere_nom, p.id AS professeur_id, p.nom AS professeur_nom
              FROM matieres m
              JOIN professeurs p ON p.matiere_id = m.id
              WHERE m.classe_id = :classe_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['classe_id' => $classe_id]);
    $matieres_professeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="message-container"></div> <!-- Message affiché après enregistrement -->

<form id="form_notes" method="POST" action="enregistrement_notes.php">
    <!-- Sélection unique de la matière et du professeur -->
    <div class="form-group">
        <label for="matiere_select">Matière :</label>
        <select id="matiere_select" name="matiere_id" class="form-control" required>
            <option value="">Sélectionner une matière</option>
            <?php foreach ($matieres_professeurs as $mp): ?>
                <option value="<?= $mp['matiere_id'] ?>"><?= $mp['matiere_nom'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="professeur_select">Professeur :</label>
        <select id="professeur_select" name="professeur_id" class="form-control" required>
            <option value="">Sélectionner un professeur</option>
            <?php foreach ($matieres_professeurs as $mp): ?>
                <option value="<?= $mp['professeur_id'] ?>"><?= $mp['professeur_nom'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Élève</th>
                <th>Note 1</th>
                <th>Note 2</th>
                <th>Note 3</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eleves as $eleve): ?>
                <tr>
                    <td><?= $eleve['nom'] ?></td>
                    <input type="hidden" name="notes[<?= $eleve['id'] ?>][eleve_id]" value="<?= $eleve['id'] ?>">
                    <td><input type="number" name="notes[<?= $eleve['id'] ?>][note1]" value="0"></td>
                    <td><input type="number" name="notes[<?= $eleve['id'] ?>][note2]" value="0"></td>
                    <td><input type="number" name="notes[<?= $eleve['id'] ?>][note3]" value="0"></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button type="submit" class="btn btn-primary">Enregistrer les Notes</button>
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#form_notes').submit(function(e) {
        e.preventDefault(); // Empêcher le rechargement de la page

        $.ajax({
            type: "POST",
            url: "enregistrement_notes.php",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                let alertType = response.success ? 'success' : 'danger';
                let messageBox = `<div class="alert alert-${alertType}">${response.message}</div>`;
                $('#message-container').html(messageBox);

                // Effacer le message après 3 secondes
                setTimeout(() => { $('#message-container').html(''); }, 3000);
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">Erreur lors de l\'enregistrement.</div>');
            }
        });
    });
});
</script>

<?php
}
?>
