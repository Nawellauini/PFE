<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO('mysql:host=localhost;dbname=gestion_notes', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Récupérer toutes les classes
$query_classes = "SELECT id, nom FROM classes ORDER BY nom ASC";
$stmt_classes = $pdo->query($query_classes);
$classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les élèves selon la classe sélectionnée
$eleves = [];
if (!empty($_GET['classe_id'])) {
    $classe_id = $_GET['classe_id'];
    $query_eleves = "SELECT id, nom FROM eleves WHERE classe_id = :classe_id ORDER BY nom ASC";
    $stmt_eleves = $pdo->prepare($query_eleves);
    $stmt_eleves->execute(['classe_id' => $classe_id]);
    $eleves = $stmt_eleves->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer les notes si un élève et une classe sont sélectionnés
$notes = [];
if (!empty($_GET['eleve_id']) && !empty($_GET['classe_id'])) {
    $eleve_id = $_GET['eleve_id'];
    $classe_id = $_GET['classe_id'];

    $query_notes = "
        SELECT e.nom AS eleve_nom, m.nom AS matiere_nom, n.note1, n.note2, n.note3
        FROM notes n
        JOIN eleves e ON n.eleve_id = e.id
        JOIN matieres m ON n.matiere_id = m.id
        WHERE e.id = :eleve_id AND m.classe_id = :classe_id";
    
    $stmt_notes = $pdo->prepare($query_notes);
    $stmt_notes->execute(['eleve_id' => $eleve_id, 'classe_id' => $classe_id]);
    $notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes des Élèves</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function loadEleves(classe_id) {
            if (classe_id) {
                $.ajax({
                    url: 'load_eleves.php',
                    method: 'GET',
                    data: { classe_id: classe_id },
                    success: function(response) {
                        $('#eleve_id').html(response);
                    },
                    error: function() {
                        alert('Erreur de chargement des élèves.');
                    }
                });
            } else {
                $('#eleve_id').html('<option value="">Sélectionner un élève</option>');
            }
        }

        $(document).ready(function() {
            var classe_id = $('#classe_id').val();
            if (classe_id) {
                loadEleves(classe_id);
            }
        });
    </script>
    <style>
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        .container { max-width: 800px; margin-top: 50px; }
        .card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .btn-custom { background-color: #007bff; color: white; }
        .table th, .table td { text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">Gestion des Notes des Élèves</h1>
        <div class="card p-4">
            <form action="afficher_notes.php" method="GET">
                <div class="mb-3">
                    <label for="classe_id" class="form-label">Sélectionner une classe</label>
                    <select class="form-select" name="classe_id" id="classe_id" required onchange="loadEleves(this.value)">
                        <option value="">Sélectionner une classe</option>
                        <?php foreach ($classes as $classe) { ?>
                            <option value="<?= htmlspecialchars($classe['id']) ?>" <?= isset($_GET['classe_id']) && $_GET['classe_id'] == $classe['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($classe['nom']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="eleve_id" class="form-label">Sélectionner un élève</label>
                    <select class="form-select" name="eleve_id" id="eleve_id" required>
                        <option value="">Sélectionner un élève</option>
                        <?php foreach ($eleves as $eleve) { ?>
                            <option value="<?= htmlspecialchars($eleve['id']) ?>" <?= isset($_GET['eleve_id']) && $_GET['eleve_id'] == $eleve['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($eleve['nom']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-custom w-100">Afficher les Notes</button>
            </form>
        </div>

        <?php if (!empty($notes)) { ?>
            <div class="card p-4 mt-4">
                <h3 class="text-center">Notes de : <?= htmlspecialchars($notes[0]['eleve_nom']) ?></h3>
                <table class="table table-striped mt-3">
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th>Note 1</th>
                            <th>Note 2</th>
                            <th>Note 3</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notes as $note) { ?>
                            <tr>
                                <td><?= htmlspecialchars($note['matiere_nom']) ?></td>
                                <td><?= htmlspecialchars($note['note1']) ?></td>
                                <td><?= htmlspecialchars($note['note2']) ?></td>
                                <td><?= htmlspecialchars($note['note3']) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <a href="export_pdf.php?eleve_id=<?= htmlspecialchars($_GET['eleve_id']) ?>&classe_id=<?= htmlspecialchars($_GET['classe_id']) ?>" class="btn btn-success w-100 mt-3">Exporter en PDF</a>
            </div>
        <?php } else if (isset($_GET['eleve_id'])) { ?>
            <div class="alert alert-warning mt-3">Aucune note trouvée pour cet élève.</div>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
