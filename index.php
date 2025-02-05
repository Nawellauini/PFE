<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes des Élèves</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEJX3HQJhdIqbKugfRR2bUpXkBaC27T99FGUMdbk19rf7Pbse3zJe/h9uP3Q9" crossorigin="anonymous">
    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f4f8;
            color: #343a40;
        }

        h1 {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 40px;
            color: #007bff;
            font-weight: bold;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            background-color: #ffffff;
        }

        .card-header {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }

        .form-control {
            border-radius: 10px;
            box-shadow: none;
            border: 1px solid #ced4da;
            padding: 10px;
            margin-bottom: 20px;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.5);
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: bold;
            transition: background-color 0.3s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .note-input {
            width: 80px;
            text-align: center;
        }

        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }

        .toast {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1050;
        }

        .toast-body {
            font-size: 16px;
        }

        .container {
            padding-top: 50px;
            padding-bottom: 50px;
        }

        table td select,
        table td input {
            width: 100%;
            margin: 5px 0;
        }

        table td button {
            margin-top: 5px;
            padding: 5px 15px;
        }

        /* Pour aligner les boutons et ajuster les tables */
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Gestion des Notes des Élèves</h1>

    <!-- Sélectionner une classe -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-school"></i> Choisir une Classe
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="classe">Sélectionner une classe :</label>
                <select id="classe" name="classe" class="form-control">
                    <option value="">Sélectionner une classe</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Afficher les élèves, matières et professeurs associés à la classe -->
    <div id="details_classe" class="mt-4"></div>
</div>

<!-- Toast Notification -->
<div id="toastMessage" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" style="display:none;">
    <div class="d-flex">
        <div class="toast-body">
            <i class="fas fa-check-circle"></i> Les notes ont été enregistrées avec succès.
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div>
<div class="form-group mt-3">
    <a href="eleve_notes.php" class="btn btn-secondary w-100">Aller à une autre page</a>
</div>

<!-- Bootstrap 5 JS (with Popper) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz4fnFO9gybFv7dX9WvYNp92KE8a+NfALmZZFw8FCSjDP+QxxSIfhflT1p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js" integrity="sha384-pzjw8f+ua7Kw1TIq0gVvE+7tu37gt1ajRr5mA6NTwQh4nZqK2rmHg8wDkpqWpzY5" crossorigin="anonymous"></script>

<script>
    $(document).ready(function() {
        // Charger les classes au démarrage
        $.ajax({
            url: 'get_classes.php',
            method: 'GET',
            success: function(data) {
                $('#classe').html(data); // Remplir la liste déroulante des classes
            },
            error: function() {
                alert("Erreur lors du chargement des classes.");
            }
        });

        // Lorsqu'une classe est sélectionnée
        $('#classe').change(function() {
            var classe_id = $(this).val();
            if (classe_id) {
                // Récupérer les détails de la classe (professeurs, matières, élèves)
                $.ajax({
                    url: 'get_details_classe.php',
                    method: 'GET',
                    data: { classe_id: classe_id },
                    success: function(data) {
                        $('#details_classe').html(data); // Afficher les détails (professeurs, matières, élèves)
                    },
                    error: function() {
                        alert("Erreur lors du chargement des détails de la classe.");
                    }
                });
            } else {
                $('#details_classe').html(''); // Réinitialiser l'affichage si aucune classe n'est sélectionnée
            }
        });

        // Soumettre les notes via AJAX
        $(document).on('submit', '#form_notes', function(e) {
            e.preventDefault();  // Empêcher la soumission classique du formulaire
            var formData = $(this).serialize(); // Récupérer les données du formulaire

            $.ajax({
                url: 'enregistrement_notes.php',
                method: 'POST',
                data: formData, // Envoyer les données
                success: function(response) {
                    // Afficher le toast de succès
                    $('#toastMessage').toast('show');
                },
                error: function() {
                    alert('Erreur lors de l\'enregistrement des notes.');
                }
            });
        });
    });
</script>

</body>
</html>
