<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes des Élèves</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Charger les élèves en fonction de la classe sélectionnée
        function loadEleves(classe_id) {
            if (classe_id) {
                $.ajax({
                    url: 'load_eleves.php', // Script qui renvoie les élèves
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

        // Charger automatiquement les élèves si une classe est déjà sélectionnée
        $(document).ready(function() {
            var classe_id = $('#classe_id').val();
            if (classe_id) {
                loadEleves(classe_id);
            }
        });
    </script>
    <style>
        body {
            background-color: #f7f7f7;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 900px;
            margin-top: 50px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-custom {
            background-color: #007bff;
            color: #fff;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        .table th, .table td {
            text-align: center;
        }
        .header-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-label {
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 0.9rem;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="header-title">Gestion des Notes des Élèves</h1>
        <div class="card p-4">
            <form action="eleve_notes.php" method="GET">
                <div class="mb-3">
                    <label for="classe_id" class="form-label">Sélectionner une classe</label>
                    <select class="form-select" name="classe_id" id="classe_id" required onchange="loadEleves(this.value)">
                        <option value="">Sélectionner une classe</option>
                        <?php 
                        try {
                            $pdo = new PDO('mysql:host=localhost;dbname=gestion_notes', 'root', '', [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                            ]);
                            $query_classes = "SELECT id, nom FROM classes ORDER BY nom ASC";
                            $stmt_classes = $pdo->query($query_classes);
                            foreach ($stmt_classes->fetchAll(PDO::FETCH_ASSOC) as $classe) { 
                        ?>
                            <option value="<?= htmlspecialchars($classe['id']) ?>" <?= isset($_GET['classe_id']) && $_GET['classe_id'] == $classe['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($classe['nom']) ?>
                            </option>
                        <?php 
                            } 
                        } catch (Exception $e) {
                            echo "<option value=''>Erreur de chargement</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="eleve_id" class="form-label">Sélectionner un élève</label>
                    <select class="form-select" name="eleve_id" id="eleve_id" required>
                        <option value="">Sélectionner un élève</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-custom w-100">Afficher les Notes</button>
            </form>
        </div>

        <?php
        if (!empty($_GET['eleve_id']) && !empty($_GET['classe_id'])) {
            $eleve_id = $_GET['eleve_id'];
            $classe_id = $_GET['classe_id'];

            try {
                $query_notes = "
                    SELECT e.nom AS eleve_nom, c.nom AS classe_nom, m.nom AS matiere_nom, n.note1, n.note2, n.note3
                    FROM notes n
                    JOIN eleves e ON n.eleve_id = e.id
                    JOIN matieres m ON n.matiere_id = m.id
                    JOIN classes c ON m.classe_id = c.id
                    WHERE e.id = :eleve_id AND m.classe_id = :classe_id";

                $stmt_notes = $pdo->prepare($query_notes);
                $stmt_notes->execute(['eleve_id' => $eleve_id, 'classe_id' => $classe_id]);
                $notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($notes)) {
                    echo '<div class="card p-4 mt-4">';
                    echo '<h3 class="text-center">Notes de : ' . htmlspecialchars($notes[0]['eleve_nom']) . ' (Classe: ' . htmlspecialchars($notes[0]['classe_nom']) . ')</h3>';
                    echo '<table class="table table-striped mt-3">
                        <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Note 1</th>
                                <th>Note 2</th>
                                <th>Note 3</th>
                            </tr>
                        </thead>
                        <tbody>';
                    foreach ($notes as $note) {
                        echo "<tr>
                            <td>" . htmlspecialchars($note['matiere_nom']) . "</td>
                            <td>" . htmlspecialchars($note['note1']) . "</td>
                            <td>" . htmlspecialchars($note['note2']) . "</td>
                            <td>" . htmlspecialchars($note['note3']) . "</td>
                        </tr>";
                    }
                    echo '</tbody></table>';
                    echo '<a href="export_pdf.php?eleve_id=' . htmlspecialchars($eleve_id) . '&classe_id=' . htmlspecialchars($classe_id) . '" class="btn btn-success w-100 mt-3">Exporter en PDF</a>';
                    echo '</div>';
                } else {
                    echo "<div class='alert alert-warning mt-3'>Aucune note trouvée pour cet élève.</div>";
                }
            } catch (Exception $e) {
                echo "<div class='alert alert-danger mt-3'>Erreur de récupération des notes.</div>";
            }
        }
        ?>

        <div class="footer">
            <p>&copy; 2025 Gestion des Notes - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>
