<?php
session_start();
include 'db_config.php';

// Vérifier que le professeur est bien connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$professeur_id = $_SESSION['id_professeur'];
$message = "";

// Vérifier si un ID d'événement est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: consulter_evenements.php");
    exit();
}

$event_id = $_GET['id'];

// Obtenir le nom du professeur pour l'afficher
$query = "SELECT nom FROM professeurs WHERE id_professeur = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $professeur_id);
$stmt->execute();
$result = $stmt->get_result();
$professeur = $result->fetch_assoc();

// Récupérer les classes enseignées par ce professeur
$query = "SELECT DISTINCT c.id_classe, c.nom_classe 
          FROM classes c 
          INNER JOIN professeurs p ON p.id_professeur = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $professeur_id);
$stmt->execute();
$result = $stmt->get_result();
$classes = [];
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Récupérer les informations de l'événement
$query = "SELECT * FROM calendar_events WHERE id = ? AND id_professeur = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $event_id, $professeur_id);
$stmt->execute();
$result = $stmt->get_result();

// Vérifier si l'événement existe et appartient au professeur connecté
if ($result->num_rows === 0) {
    header("Location: consulter_evenements.php");
    exit();
}

$event = $result->fetch_assoc();
$formatted_date = date('d/m/Y', strtotime($event['events_date']));

// Traitement du formulaire de modification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_classe = $_POST['id_classe'];
    $events_date = $_POST['events_date'];
    $description = $_POST['description'];
    
    // Validation des données
    if (empty($id_classe) || empty($events_date) || empty($description)) {
        $message = '<div class="alert alert-danger">Tous les champs sont obligatoires.</div>';
    } else {
        // Conversion de la date au format MySQL (YYYY-MM-DD)
        $events_date_mysql = date('Y-m-d', strtotime(str_replace('/', '-', $events_date)));
        
        // Mise à jour dans la base de données
        $query = "UPDATE calendar_events 
                  SET id_classe = ?, events_date = ?, description = ? 
                  WHERE id = ? AND id_professeur = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issii", $id_classe, $events_date_mysql, $description, $event_id, $professeur_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">L\'événement a été modifié avec succès!</div>';
            // Mettre à jour les données locales
            $event['id_classe'] = $id_classe;
            $event['events_date'] = $events_date_mysql;
            $event['description'] = $description;
            $formatted_date = $events_date;
        } else {
            $message = '<div class="alert alert-danger">Erreur lors de la modification de l\'événement: ' . $stmt->error . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un événement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #007bff;
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .form-label {
            font-weight: bold;
            color: #495057;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 10px 20px;
            font-weight: bold;
        }
        .btn-primary:hover {
            background-color: #0069d9;
        }
        .btn-danger {
            background-color: #dc3545;
            border: none;
            padding: 10px 20px;
            font-weight: bold;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .welcome-text {
            font-size: 18px;
            color: #6c757d;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .calendar-icon {
            position: absolute;
            right: 10px;
            top: 10px;
            color: #007bff;
            pointer-events: none;
        }
        .date-container {
            position: relative;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="welcome-text">Bienvenue, Professeur <strong><?php echo $professeur['nom']; ?></strong></div>
        <div>
            <a href="consulter_evenements.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left"></i> Retour</a>
            <a href="index.php" class="btn btn-outline-primary"><i class="fas fa-home"></i> Accueil</a>
        </div>
    </div>
    
    <h1><i class="fas fa-edit"></i> Modifier un événement</h1>
    
    <?php echo $message; ?>
    
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $event_id; ?>" class="needs-validation" novalidate>
        <div class="mb-4">
            <label for="id_classe" class="form-label">الصف (Classe)</label>
            <select class="form-select" id="id_classe" name="id_classe" required>
                <option value="">اختر الصف (Choisir une classe)</option>
                <?php foreach ($classes as $classe): ?>
                    <option value="<?php echo $classe['id_classe']; ?>" <?php echo ($event['id_classe'] == $classe['id_classe']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($classe['nom_classe']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Veuillez sélectionner une classe.</div>
        </div>
        
        <div class="mb-4">
            <label for="events_date" class="form-label">Date</label>
            <div class="date-container">
                <input type="text" class="form-control" id="events_date" name="events_date" placeholder="jj/mm/aaaa" value="<?php echo $formatted_date; ?>" required>
                <div class="calendar-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
            <div class="invalid-feedback">Veuillez sélectionner une date.</div>
        </div>
        
        <div class="mb-4">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Description de l'événement" required><?php echo htmlspecialchars($event['description']); ?></textarea>
            <div class="invalid-feedback">Veuillez ajouter une description.</div>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="consulter_evenements.php" class="btn btn-danger"><i class="fas fa-times"></i> Annuler</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script>
    // Initialisation du sélecteur de date
    flatpickr("#events_date", {
        dateFormat: "d/m/Y",
        locale: "fr",
        disableMobile: "true"
    });
    
    // Validation du formulaire Bootstrap
    (function () {
        'use strict'
        
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')
        
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>

</body>
</html>

<?php
$conn->close();
?>