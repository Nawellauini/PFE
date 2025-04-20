<?php
session_start();
include 'db_config.php';


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_inspecteur'])) {
    header("Location: login.php?error=Vous devez être connecté en tant qu'inspecteur");
    exit();
}

// Vérifier si l'ID du fichier est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: liste_rapports.php?message=ID+de+fichier+invalide&type=error");
    exit();
}

$id_fichier = $_GET['id'];
$rapport_id = isset($_GET['rapport_id']) ? $_GET['rapport_id'] : null;

// Récupérer les informations du fichier
$sql = "SELECT * FROM fichiers_rapport WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    header("Location: liste_rapports.php?message=Erreur+de+préparation+de+la+requête&type=error");
    exit();
}

$stmt->bind_param("i", $id_fichier);

if (!$stmt->execute()) {
    header("Location: liste_rapports.php?message=Erreur+d'exécution+de+la+requête&type=error");
    exit();
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: liste_rapports.php?message=Fichier+introuvable&type=error");
    exit();
}

$fichier = $result->fetch_assoc();

// Si l'utilisateur confirme la suppression
if (isset($_POST['confirmer']) && $_POST['confirmer'] === 'oui') {
    // Supprimer le fichier physique
    if (file_exists($fichier['chemin_fichier'])) {
        unlink($fichier['chemin_fichier']);
    }
    
    // Supprimer l'entrée dans la base de données
    $sql_delete = "DELETE FROM fichiers_rapport WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    
    if ($stmt_delete === false) {
        header("Location: liste_rapports.php?message=Erreur+de+préparation+de+la+requête+de+suppression&type=error");
        exit();
    }
    
    $stmt_delete->bind_param("i", $id_fichier);
    
    if (!$stmt_delete->execute()) {
        header("Location: liste_rapports.php?message=Erreur+lors+de+la+suppression+du+fichier&type=error");
        exit();
    }
    
    // Rediriger vers la page appropriée
    if ($rapport_id) {
        header("Location: modifier_rapport.php?id=$rapport_id&message=Fichier+supprimé+avec+succès&type=success");
    } else {
        header("Location: liste_rapports.php?message=Fichier+supprimé+avec+succès&type=success");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer la suppression | Système de Gestion des Rapports d'Inspection</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56d4;
            --secondary-color: #7209b7;
            --accent-color: #f72585;
            --success-color: #38b000;
            --warning-color: #f9c74f;
            --danger-color: #d90429;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--gray-800);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 500px;
            padding: 0 20px;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        .card-header {
            background-color: var(--danger-color);
            color: white;
            padding: 1.2rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        .file-info {
            background-color: var(--gray-100);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-300);
        }

        .file-info p {
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .file-info i {
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }

        .warning-text {
            color: var(--danger-color);
            font-weight: 500;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #b80021;
        }

        .btn-secondary {
            background-color: var(--gray-500);
            color: white;
        }

        .btn-secondary:hover {
            background-color: var(--gray-600);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Confirmer la suppression</h2>
            </div>
            <div class="card-body">
                <div class="file-info">
                    <p><i class="fas fa-file"></i> <strong>Nom du fichier:</strong> <?= htmlspecialchars($fichier['nom_fichier']) ?></p>
                    <p><i class="fas fa-calendar-alt"></i> <strong>Date d'upload:</strong> <?= date('d/m/Y H:i', strtotime($fichier['date_upload'])) ?></p>
                </div>
                
                <p class="warning-text">
                    <i class="fas fa-exclamation-circle"></i> Attention: Cette action est irréversible!
                </p>
                
                <form action="supprimer_fichier.php?id=<?= $id_fichier ?><?= $rapport_id ? '&rapport_id='.$rapport_id : '' ?>" method="POST">
                    <div class="form-actions">
                        <button type="submit" name="confirmer" value="oui" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Confirmer la suppression
                        </button>
                        <a href="<?= $rapport_id ? 'modifier_rapport.php?id='.$rapport_id : 'liste_rapports.php' ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>