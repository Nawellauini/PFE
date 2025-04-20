<?php
// Démarrer la session
session_start();

// Inclure le fichier de configuration de la base de données
include 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Variables selon le rôle
$is_prof = ($_SESSION['role'] == 'professeur');
$is_eleve = ($_SESSION['role'] == 'eleve');

// Récupérer l'ID de l'utilisateur
$user_id = $is_prof ? $_SESSION['id_professeur'] : ($is_eleve ? $_SESSION['id_eleve'] : 0);

// Traitement de l'ajout d'un cours
if ($is_prof && isset($_POST['action']) && $_POST['action'] == 'add') {
    // Récupérer les données du formulaire
    $titre = $conn->real_escape_string($_POST['titre']);
    $description = $conn->real_escape_string($_POST['description']);
    $id_classe = intval($_POST['classe']);
    $id_theme = intval($_POST['theme']);
    $id_matiere = intval($_POST['matiere']);
    
    // Vérifier si la classe sélectionnée est enseignée par ce professeur
    $sql_check_classe = "SELECT * FROM professeurs_classes WHERE id_professeur = ? AND id_classe = ?";
    $stmt_check = $conn->prepare($sql_check_classe);
    $stmt_check->bind_param("ii", $_SESSION['id_professeur'], $id_classe);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows == 0) {
        $error = "لا يمكنك إضافة درس لهذا الفصل.";
    } else {
        // Initialiser la requête SQL
        $sql_insert = "INSERT INTO cours (titre, description, id_classe, id_theme, matiere_id, id_professeur, date_creation) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $params = array($titre, $description, $id_classe, $id_theme, $id_matiere, $_SESSION['id_professeur']);
        $types = "ssiiii";
        
        // Vérifier si un fichier a été téléchargé
        $fichier_path = null;
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] == 0) {
            $allowed = array('pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx');
            $filename = $_FILES['fichier']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_dir = 'uploads/';
                
                // Créer le répertoire s'il n'existe pas
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $fichier_path = $upload_dir . $new_filename;
                
                if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $fichier_path)) {
                    $error = "Erreur lors du téléchargement du fichier.";
                    $fichier_path = null;
                }
            } else {
                $error = "Type de fichier non autorisé.";
            }
        }
        
        // Ajouter le chemin du fichier à la requête si un fichier a été téléchargé
        if ($fichier_path) {
            $sql_insert = "INSERT INTO cours (titre, description, id_classe, id_theme, matiere_id, id_professeur, date_creation, fichier) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
            $params[] = $fichier_path;
            $types .= "s";
        }
        
        // Si aucune erreur, insérer le cours dans la base de données
        if (!isset($error)) {
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param($types, ...$params);
            
            if ($stmt_insert->execute()) {
                $id_cours = $stmt_insert->insert_id;
                
                // Traitement des images multiples
                if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                    // Vérifier si la table cours_images existe
                    $check_table = $conn->query("SHOW TABLES LIKE 'cours_images'");
                    if ($check_table->num_rows == 0) {
                        // Créer la table si elle n'existe pas
                        $create_table = "CREATE TABLE cours_images (
                            id_image INT(11) NOT NULL AUTO_INCREMENT,
                            id_cours INT(11) NOT NULL,
                            chemin VARCHAR(255) NOT NULL,
                            date_ajout DATETIME NOT NULL,
                            PRIMARY KEY (id_image),
                            KEY id_cours (id_cours)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                        $conn->query($create_table);
                    }
                    
                    $upload_dir = 'uploads/images/cours/';
                    
                    // Créer le répertoire s'il n'existe pas
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Définir la première image comme illustration principale du cours
                    $first_image = true;
                    
                    // Parcourir toutes les images
                    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                        if ($_FILES['images']['error'][$i] == 0) {
                            $filename = $_FILES['images']['name'][$i];
                            $ext = pathinfo($filename, PATHINFO_EXTENSION);
                            
                            if (in_array(strtolower($ext), array('jpg', 'jpeg', 'png', 'gif'))) {
                                $new_filename = uniqid() . '.' . $ext;
                                $image_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $image_path)) {
                                    // Si c'est la première image, l'utiliser comme illustration principale
                                    if ($first_image) {
                                        $sql_update_illustration = "UPDATE cours SET illustration = ? WHERE id_cours = ?";
                                        $stmt_update_illustration = $conn->prepare($sql_update_illustration);
                                        $stmt_update_illustration->bind_param("si", $image_path, $id_cours);
                                        $stmt_update_illustration->execute();
                                        $first_image = false;
                                    }
                                    
                                    // Insérer l'image dans la table cours_images
                                    $sql_image = "INSERT INTO cours_images (id_cours, chemin, date_ajout) VALUES (?, ?, NOW())";
                                    $stmt_image = $conn->prepare($sql_image);
                                    $stmt_image->bind_param("is", $id_cours, $image_path);
                                    $stmt_image->execute();
                                }
                            }
                        }
                    }
                }
                
                $message = "تمت إضافة الدرس بنجاح";
            } else {
                $error = "خطأ في إضافة الدرس: " . $conn->error;
            }
        }
    }
}

// Traitement de la suppression d'un cours
if ($is_prof && isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id_cours'])) {
    $id_cours = intval($_POST['id_cours']);
    
    // Vérifier si le cours appartient au professeur connecté
    $sql_check = "SELECT * FROM cours WHERE id_cours = $id_cours AND id_professeur = " . $_SESSION['id_professeur'];
    $result_check = $conn->query($sql_check);

    if ($result_check->num_rows > 0) {
        // Récupérer les informations du cours pour supprimer les fichiers associés
        $cours = $result_check->fetch_assoc();
        
        // Supprimer le cours de la base de données
        $sql_delete = "DELETE FROM cours WHERE id_cours = $id_cours AND id_professeur = " . $_SESSION['id_professeur'];
        
        if ($conn->query($sql_delete) === TRUE) {
            // Supprimer le fichier du cours s'il existe
            if (!empty($cours['fichier']) && file_exists($cours['fichier'])) {
                unlink($cours['fichier']);
            }
            
            // Supprimer l'illustration du cours si elle existe
            if (!empty($cours['illustration']) && file_exists($cours['illustration'])) {
                unlink($cours['illustration']);
            }
            
            // Supprimer les images multiples associées au cours
            $sql_images = "SELECT * FROM cours_images WHERE id_cours = $id_cours";
            $result_images = $conn->query($sql_images);
            if ($result_images && $result_images->num_rows > 0) {
                while ($image = $result_images->fetch_assoc()) {
                    if (file_exists($image['chemin'])) {
                        unlink($image['chemin']);
                    }
                }
                
                // Supprimer les entrées de la base de données
                $conn->query("DELETE FROM cours_images WHERE id_cours = $id_cours");
            }
            
            $message = "تم حذف الدرس بنجاح";
        } else {
            $error = "خطأ في حذف الدرس: " . $conn->error;
        }
    } else {
        $error = "لا يمكنك حذف هذا الدرس";
    }
}

// Traitement de la modification d'un cours
if ($is_prof && isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['id_cours'])) {
    $id_cours = intval($_POST['id_cours']);
    
    // Récupérer les données du formulaire
    $titre = $conn->real_escape_string($_POST['titre']);
    $description = $conn->real_escape_string($_POST['description']);
    $id_classe = intval($_POST['classe']);
    $id_theme = intval($_POST['theme']);
    $id_matiere = intval($_POST['matiere']);
    
    // Vérifier si la classe sélectionnée est enseignée par ce professeur
    $sql_check_classe = "SELECT * FROM professeurs_classes WHERE id_professeur = ? AND id_classe = ?";
    $stmt_check = $conn->prepare($sql_check_classe);
    $stmt_check->bind_param("ii", $_SESSION['id_professeur'], $id_classe);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows == 0) {
        $error = "لا يمكنك تعديل الدرس لهذا الفصل.";
    } else {
        // Initialiser la requête SQL
        $sql_update = "UPDATE cours SET 
                        titre = ?, 
                        description = ?, 
                        id_classe = ?, 
                        id_theme = ?,
                        matiere_id = ?";
        
        $params = array($titre, $description, $id_classe, $id_theme, $id_matiere);
        $types = "ssiii";
        
        // Vérifier si un nouveau fichier a été téléchargé
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] == 0) {
            $allowed = array('pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx');
            $filename = $_FILES['fichier']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_dir = 'uploads/';
                
                // Créer le répertoire s'il n'existe pas
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $fichier_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['fichier']['tmp_name'], $fichier_path)) {
                    // Récupérer l'ancien fichier pour le supprimer plus tard
                    $sql_old_file = "SELECT fichier FROM cours WHERE id_cours = ? AND id_professeur = ?";
                    $stmt_old_file = $conn->prepare($sql_old_file);
                    $stmt_old_file->bind_param("ii", $id_cours, $_SESSION['id_professeur']);
                    $stmt_old_file->execute();
                    $result_old_file = $stmt_old_file->get_result();
                    $old_file = $result_old_file->fetch_assoc()['fichier'];
                    
                    // Mettre à jour le chemin du fichier dans la requête
                    $sql_update .= ", fichier = ?";
                    $params[] = $fichier_path;
                    $types .= "s";
                } else {
                    $error = "Erreur lors du téléchargement du fichier.";
                }
            } else {
                $error = "Type de fichier non autorisé.";
            }
        }
        
        // Finaliser la requête SQL
        $sql_update .= " WHERE id_cours = ? AND id_professeur = ?";
        $params[] = $id_cours;
        $params[] = $_SESSION['id_professeur'];
        $types .= "ii";
        
        // Si aucune erreur, mettre à jour le cours dans la base de données
        if (!isset($error)) {
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param($types, ...$params);
            
            if ($stmt_update->execute()) {
                // Supprimer l'ancien fichier si un nouveau a été téléchargé
                if (isset($old_file) && !empty($old_file) && file_exists($old_file)) {
                    unlink($old_file);
                }
                
                // Traitement des images multiples
                if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                    // Vérifier si la table cours_images existe
                    $check_table = $conn->query("SHOW TABLES LIKE 'cours_images'");
                    if ($check_table->num_rows == 0) {
                        // Créer la table si elle n'existe pas
                        $create_table = "CREATE TABLE cours_images (
                            id_image INT(11) NOT NULL AUTO_INCREMENT,
                            id_cours INT(11) NOT NULL,
                            chemin VARCHAR(255) NOT NULL,
                            date_ajout DATETIME NOT NULL,
                            PRIMARY KEY (id_image),
                            KEY id_cours (id_cours)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                        $conn->query($create_table);
                    }
                    
                    $upload_dir = 'uploads/images/cours/';
                    
                    // Créer le répertoire s'il n'existe pas
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Vérifier s'il y a déjà des images pour ce cours
                    $has_existing_images = false;
                    $sql_check_images = "SELECT COUNT(*) as count FROM cours_images WHERE id_cours = ?";
                    $stmt_check_images = $conn->prepare($sql_check_images);
                    $stmt_check_images->bind_param("i", $id_cours);
                    $stmt_check_images->execute();
                    $result_check_images = $stmt_check_images->get_result();
                    if ($result_check_images->fetch_assoc()['count'] > 0) {
                        $has_existing_images = true;
                    }
                    
                    // Définir la première image comme illustration principale si aucune image n'existe déjà
                    $first_image = !$has_existing_images;
                    
                    // Parcourir toutes les images
                    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                        if ($_FILES['images']['error'][$i] == 0) {
                            $filename = $_FILES['images']['name'][$i];
                            $ext = pathinfo($filename, PATHINFO_EXTENSION);
                            
                            if (in_array(strtolower($ext), array('jpg', 'jpeg', 'png', 'gif'))) {
                                $new_filename = uniqid() . '.' . $ext;
                                $image_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $image_path)) {
                                    // Si c'est la première image et qu'il n'y a pas d'images existantes, l'utiliser comme illustration principale
                                    if ($first_image) {
                                        $sql_update_illustration = "UPDATE cours SET illustration = ? WHERE id_cours = ?";
                                        $stmt_update_illustration = $conn->prepare($sql_update_illustration);
                                        $stmt_update_illustration->bind_param("si", $image_path, $id_cours);
                                        $stmt_update_illustration->execute();
                                        $first_image = false;
                                    }
                                    
                                    // Insérer l'image dans la table cours_images
                                    $sql_image = "INSERT INTO cours_images (id_cours, chemin, date_ajout) VALUES (?, ?, NOW())";
                                    $stmt_image = $conn->prepare($sql_image);
                                    $stmt_image->bind_param("is", $id_cours, $image_path);
                                    $stmt_image->execute();
                                }
                            }
                        }
                    }
                }
                
                // Supprimer les images sélectionnées
                if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                    foreach ($_POST['delete_images'] as $id_image) {
                        $id_image = intval($id_image);
                        
                        // Récupérer le chemin de l'image
                        $sql_get_image = "SELECT * FROM cours_images WHERE id_image = ? AND id_cours = ?";
                        $stmt_get_image = $conn->prepare($sql_get_image);
                        $stmt_get_image->bind_param("ii", $id_image, $id_cours);
                        $stmt_get_image->execute();
                        $result_get_image = $stmt_get_image->get_result();
                        
                        if ($result_get_image->num_rows > 0) {
                            $image = $result_get_image->fetch_assoc();
                            
                            // Vérifier si cette image est utilisée comme illustration principale
                            $sql_check_illustration = "SELECT illustration FROM cours WHERE id_cours = ? AND illustration = ?";
                            $stmt_check_illustration = $conn->prepare($sql_check_illustration);
                            $stmt_check_illustration->bind_param("is", $id_cours, $image['chemin']);
                            $stmt_check_illustration->execute();
                            $result_check_illustration = $stmt_check_illustration->get_result();
                            
                            // Si c'est l'illustration principale, la mettre à NULL
                            if ($result_check_illustration->num_rows > 0) {
                                $sql_update_illustration = "UPDATE cours SET illustration = NULL WHERE id_cours = ?";
                                $stmt_update_illustration = $conn->prepare($sql_update_illustration);
                                $stmt_update_illustration->bind_param("i", $id_cours);
                                $stmt_update_illustration->execute();
                            }
                            
                            // Supprimer le fichier
                            if (file_exists($image['chemin'])) {
                                unlink($image['chemin']);
                            }
                            
                            // Supprimer l'entrée de la base de données
                            $sql_delete_image = "DELETE FROM cours_images WHERE id_image = ?";
                            $stmt_delete_image = $conn->prepare($sql_delete_image);
                            $stmt_delete_image->bind_param("i", $id_image);
                            $stmt_delete_image->execute();
                        }
                    }
                }
                
                $message = "تم تعديل الدرس بنجاح";
            } else {
                $error = "خطأ في تعديل الدرس: " . $conn->error;
            }
        }
    }
}

// Traitement de l'ajout d'un commentaire
if ($is_eleve && isset($_POST['action']) && $_POST['action'] == 'comment' && isset($_POST['id_cours']) && isset($_POST['commentaire'])) {
    $id_cours = intval($_POST['id_cours']);
    $commentaire = $conn->real_escape_string($_POST['commentaire']);
    $id_eleve = $_SESSION['id_eleve'];
    
    // Vérifier si la table commentaires_cours existe
    $check_table = $conn->query("SHOW TABLES LIKE 'commentaires_cours'");
    if ($check_table->num_rows == 0) {
        // Créer la table si elle n'existe pas
        $create_table = "CREATE TABLE commentaires_cours (
            id_commentaire INT(11) NOT NULL AUTO_INCREMENT,
            id_cours INT(11) NOT NULL,
            id_eleve INT(11) NOT NULL,
            commentaire TEXT NOT NULL,
            date_commentaire DATETIME NOT NULL,
            PRIMARY KEY (id_commentaire),
            KEY id_cours (id_cours),
            KEY id_eleve (id_eleve)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($create_table);
    }
    
    $sql_comment = "INSERT INTO commentaires_cours (id_cours, id_eleve, commentaire, date_commentaire) 
                    VALUES (?, ?, ?, NOW())";
    $stmt_comment = $conn->prepare($sql_comment);
    if (!$stmt_comment) {
        $error = "خطأ في إضافة التعليق: " . $conn->error;
    } else {
        $stmt_comment->bind_param("iis", $id_cours, $id_eleve, $commentaire);
        if ($stmt_comment->execute()) {
            $message = "تمت إضافة تعليقك بنجاح!";
        } else {
            $error = "خطأ في إضافة التعليق: " . $stmt_comment->error;
        }
    }
}

// Filtres de recherche
$search = isset($_GET['search']) ? $_GET['search'] : '';
$theme_filter = isset($_GET['theme']) ? intval($_GET['theme']) : 0;

// Récupérer tous les thèmes pour le filtre
$sql_themes = "SELECT * FROM themes ORDER BY nom_theme";
$result_themes = $conn->query($sql_themes);
$themes = [];
if ($result_themes) {
    while ($theme = $result_themes->fetch_assoc()) {
        $themes[] = $theme;
    }
}

// Récupérer les cours selon le rôle
if ($is_prof) {
    // Pour les professeurs, afficher leurs propres cours
    $sql = "SELECT c.*, cl.nom_classe, t.nom_theme, m.nom as nom_matiere 
            FROM cours c
            JOIN classes cl ON c.id_classe = cl.id_classe
            JOIN themes t ON c.id_theme = t.id_theme
            JOIN matieres m ON c.matiere_id = m.matiere_id
            WHERE c.id_professeur = ? ";
    
    // Ajouter les filtres si nécessaire
    if (!empty($search)) {
        $sql .= "AND (c.titre LIKE ? OR c.description LIKE ?) ";
    }
    
    if ($theme_filter > 0) {
        $sql .= "AND c.id_theme = ? ";
    }
    
    $sql .= "ORDER BY c.date_creation DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Bind des paramètres selon les filtres
    if (!empty($search) && $theme_filter > 0) {
        $search_param = "%$search%";
        $stmt->bind_param("issi", $user_id, $search_param, $search_param, $theme_filter);
    } elseif (!empty($search)) {
        $search_param = "%$search%";
        $stmt->bind_param("iss", $user_id, $search_param, $search_param);
    } elseif ($theme_filter > 0) {
        $stmt->bind_param("ii", $user_id, $theme_filter);
    } else {
        $stmt->bind_param("i", $user_id);
    }
} elseif ($is_eleve) {
    // Pour les élèves, afficher les cours de leur classe
    $sql = "SELECT c.*, cl.nom_classe, t.nom_theme, m.nom as nom_matiere, 
            CONCAT(p.prenom, ' ', p.nom) as nom_professeur
            FROM cours c
            JOIN classes cl ON c.id_classe = cl.id_classe
            JOIN themes t ON c.id_theme = t.id_theme
            JOIN matieres m ON c.matiere_id = m.matiere_id
            JOIN professeurs p ON c.id_professeur = p.id_professeur
            JOIN eleves e ON e.id_classe = cl.id_classe
            WHERE e.id_eleve = ? ";
    
    // Ajouter les filtres si nécessaire
    if (!empty($search)) {
        $sql .= "AND (c.titre LIKE ? OR c.description LIKE ?) ";
    }
    
    if ($theme_filter > 0) {
        $sql .= "AND c.id_theme = ? ";
    }
    
    $sql .= "ORDER BY c.date_creation DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Bind des paramètres selon les filtres
    if (!empty($search) && $theme_filter > 0) {
        $search_param = "%$search%";
        $stmt->bind_param("issi", $user_id, $search_param, $search_param, $theme_filter);
    } elseif (!empty($search)) {
        $search_param = "%$search%";
        $stmt->bind_param("iss", $user_id, $search_param, $search_param);
    } elseif ($theme_filter > 0) {
        $stmt->bind_param("ii", $user_id, $theme_filter);
    } else {
        $stmt->bind_param("i", $user_id);
    }
} else {
    // Rediriger si le rôle n'est pas reconnu
    header("Location: login.php");
    exit();
}

// Exécuter la requête
$stmt->execute();
$result = $stmt->get_result();

// Compter le nombre total de cours
$total_courses = $result->num_rows;

// Vérifier s'il y a des messages ou des erreurs
$message = isset($_GET['message']) ? $_GET['message'] : (isset($message) ? $message : '');
$error = isset($_GET['error']) ? $_GET['error'] : (isset($error) ? $error : '');

// Récupérer les classes enseignées par ce professeur (pour le modal de modification)
if ($is_prof) {
    $sql_classes = "SELECT c.* FROM classes c 
                    INNER JOIN professeurs_classes pc ON c.id_classe = pc.id_classe 
                    WHERE pc.id_professeur = ? 
                    ORDER BY c.nom_classe";
    $stmt_classes = $conn->prepare($sql_classes);
    $stmt_classes->bind_param("i", $_SESSION['id_professeur']);
    $stmt_classes->execute();
    $result_classes = $stmt_classes->get_result();
    $classes = [];
    while ($row = $result_classes->fetch_assoc()) {
        $classes[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_prof ? 'دروسي' : 'الدروس المتاحة'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e6091;
            --primary-dark: #0d4a77;
            --secondary-color: #2a9d8f;
            --accent-color: #e9c46a;
            --text-color: #264653;
            --text-light: #546a7b;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
            --error-color: #e76f51;
            --success-color: #2a9d8f;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn i {
            margin-left: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #d35400;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-left: 10px;
            font-size: 20px;
        }

        .alert-success {
            background-color: rgba(42, 157, 143, 0.2);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        .filter-section {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 30px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .filter-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .filter-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 96, 145, 0.2);
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 10px center;
            background-size: 16px;
            padding-left: 30px;
            transition: var(--transition);
        }

        .filter-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 96, 145, 0.2);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .courses-count {
            margin-bottom: 20px;
            font-size: 16px;
            color: var(--text-light);
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .course-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .course-image {
            height: 180px;
            background-color: #e9ecef;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .course-image-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 48px;
        }

        .course-theme {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .course-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .course-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .course-meta {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .course-meta-item {
            display: flex;
            align-items: center;
            margin-left: 15px;
            margin-bottom: 5px;
            font-size: 14px;
            color: var(--text-light);
        }

        .course-meta-item i {
            margin-left: 5px;
            color: var(--primary-color);
        }

        .course-description {
            margin-bottom: 15px;
            font-size: 14px;
            color: var(--text-color);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }

        .course-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }

        .course-date {
            font-size: 12px;
            color: var(--text-light);
            display: flex;
            align-items: center;
        }

        .course-date i {
            margin-left: 5px;
            color: var(--primary-color);
        }

        .course-view {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: var(--transition);
            padding: 5px 10px;
            border-radius: var(--border-radius);
            background-color: rgba(30, 96, 145, 0.1);
        }

        .course-view i {
            margin-right: 5px;
        }

        .course-view:hover {
            color: white;
            background-color: var(--primary-color);
        }

        .course-admin-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            justify-content: flex-end;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: var(--border-radius);
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            cursor: pointer;
        }

        .action-btn i {
            margin-left: 5px;
        }

        .action-btn-edit {
            background-color: var(--warning-color);
            color: white;
        }

        .action-btn-edit:hover {
            background-color: #d35400;
        }

        .action-btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .action-btn-delete:hover {
            background-color: #c0392b;
        }

        .action-btn-pdf {
            background-color: var(--success-color);
            color: white;
        }

        .action-btn-pdf:hover {
            background-color: #27ae60;
        }

        .no-courses {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .no-courses i {
            font-size: 48px;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .no-courses p {
            font-size: 18px;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            padding: 20px;
        }

        .modal {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease;
            position: relative;
        }

        .modal-sm {
            max-width: 500px;
        }

        .modal-lg {
            max-width: 1000px;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title-wrapper {
            display: flex;
            align-items: center;
        }

        .modal-title-wrapper i {
            font-size: 24px;
            margin-left: 10px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-color);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--danger-color);
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid var(--border-color);
        }

        /* Styles pour le modal de visualisation */
        .view-course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .view-course-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .view-course-meta {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .view-course-meta-item {
            display: flex;
            align-items: center;
            margin-left: 20px;
            margin-bottom: 5px;
            font-size: 14px;
            color: var(--text-light);
        }

        .view-course-meta-item i {
            margin-left: 8px;
            color: var(--primary-color);
        }

        .view-course-image {
            height: 300px;
            background-color: #e9ecef;
            background-size: cover;
            background-position: center;
            position: relative;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }

        .view-course-image-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 64px;
        }

        .view-course-theme {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .view-course-description {
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.8;
            white-space: pre-line;
        }

        .view-course-file {
            background-color: #e9ecef;
            border-radius: var(--border-radius);
            padding: 20px;
            display: flex;
            align-items: center;
            margin-top: 20px;
        }

        .view-course-file-icon {
            font-size: 40px;
            color: var(--primary-color);
            margin-left: 20px;
        }

        .view-course-file-info {
            flex: 1;
        }

        .view-course-file-name {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .view-course-file-meta {
            font-size: 14px;
            color: var(--text-light);
        }

        .view-course-file-download {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
        }

        .view-course-file-download i {
            margin-left: 8px;
        }

        .view-course-file-download:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Styles pour les commentaires */
        .comments-section {
            margin-top: 40px;
        }

        .comments-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-color);
            display: flex;
            align-items: center;
        }

        .comments-title i {
            margin-left: 10px;
            color: var(--primary-color);
        }

        .comment-form {
            background-color: var(--bg-color);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 96, 145, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .comments-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .comment-card {
            background-color: var(--bg-color);
            border-radius: var(--border-radius);
            padding: 15px;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .comment-author {
            font-weight: 500;
            color: var(--primary-color);
        }

        .comment-date {
            font-size: 12px;
            color: var(--text-light);
        }

        .comment-content {
            font-size: 14px;
            line-height: 1.6;
        }

        .no-comments {
            background-color: var(--bg-color);
            border-radius: var(--border-radius);
            padding: 20px;
            text-align: center;
            color: var(--text-light);
        }

        .no-comments i {
            font-size: 32px;
            margin-bottom: 10px;
        }

        /* Styles pour le formulaire de modification */
        .edit-form-group {
            margin-bottom: 20px;
        }

        .edit-form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .edit-form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .edit-form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 96, 145, 0.2);
        }

        .edit-form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 10px center;
            background-size: 16px;
            padding-left: 30px;
            transition: var(--transition);
        }

        .edit-form-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 96, 145, 0.2);
        }

        .file-input-wrapper {
            position: relative;
            margin-top: 8px;
        }

        .file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background-color: var(--bg-color);
            border: 1px dashed #ccc;
            border-radius: var(--border-radius);
            color: var(--text-color);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-input-label i {
            margin-left: 8px;
            font-size: 20px;
        }

        .file-input-label:hover {
            background-color: #e9ecef;
        }

        .file-name {
            margin-top: 8px;
            font-size: 14px;
            color: var(--text-color);
            word-break: break-all;
        }

        .current-file {
            display: flex;
            align-items: center;
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
        }

        .current-file i {
            margin-left: 10px;
            font-size: 18px;
            color: var(--primary-color);
        }

        .current-file a {
            color: var(--primary-color);
            text-decoration: none;
            margin-right: 10px;
        }

        .current-file a:hover {
            text-decoration: underline;
        }

        .current-image {
            margin-top: 10px;
            border-radius: var(--border-radius);
            overflow: hidden;
            max-width: 300px;
        }

        .current-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .classes-info {
            background-color: rgba(243, 156, 18, 0.1);
            border-radius: var(--border-radius);
            padding: 10px 15px;
            margin-top: 5px;
            font-size: 14px;
            color: var(--warning-color);
        }
        
        .classes-info i {
            margin-left: 5px;
            color: var(--warning-color);
        }

        /* Styles pour les images multiples */
        .images-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .image-item {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            height: 150px;
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-item .delete-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(231, 76, 60, 0.8);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .image-item .delete-image:hover {
            background-color: var(--danger-color);
            transform: scale(1.1);
        }

        .images-upload {
            margin-top: 15px;
        }

        .images-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .image-preview-item {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            height: 100px;
        }

        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview-item .remove-preview {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(231, 76, 60, 0.8);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
        }

        .image-preview-item .remove-preview:hover {
            background-color: var(--danger-color);
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .courses-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-actions {
                width: 100%;
            }
            
            .btn {
                width: 100%;
            }
            
            .course-admin-actions {
                flex-wrap: wrap;
            }
            
            .action-btn {
                flex: 1;
                min-width: 80px;
            }
            
            .modal {
                width: 95%;
                max-height: 85vh;
            }
            
            .modal-footer {
                flex-direction: column;
            }
            
            .modal-footer .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><?php echo $is_prof ? 'الدروس' : 'الدروس المتاحة'; ?></h1>
            <?php if ($is_prof): ?>
            <button type="button" class="btn btn-primary" onclick="openAddCourseModal()">
                <i class="fas fa-plus"></i>
                إضافة درس جديد
            </button>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="filter-section">
            <form action="" method="get" class="filter-form">
                <div class="filter-group">
                    <label for="search" class="filter-label">بحث</label>
                    <input type="text" id="search" name="search" class="filter-input" placeholder="ابحث عن عنوان أو وصف..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="theme" class="filter-label">الموضوع</label>
                    <select id="theme" name="theme" class="filter-select">
                        <option value="0">جميع المواضيع</option>
                        <?php foreach ($themes as $theme): ?>
                        <option value="<?php echo $theme['id_theme']; ?>" <?php echo $theme_filter == $theme['id_theme'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($theme['nom_theme']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        بحث
                    </button>
                    <a href="liste_cours.php" class="btn btn-outline">
                        <i class="fas fa-redo"></i>
                        إعادة ضبط
                    </a>
                </div>
            </form>
        </div>
        
        <div class="courses-count">
            <strong><?php echo $total_courses; ?></strong> درس <?php echo !empty($search) || $theme_filter > 0 ? 'مطابق لمعايير البحث' : ''; ?>
        </div>
        
        <div class="courses-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="course-card">
                        <div class="course-image" style="<?php echo !empty($row['illustration']) ? 'background-image: url(\'' . $row['illustration'] . '\');' : ''; ?>">
                            <?php if (empty($row['illustration'])): ?>
                            <div class="course-image-placeholder">
                                <i class="fas fa-book"></i>
                            </div>
                            <?php endif; ?>
                            <div class="course-theme"><?php echo htmlspecialchars($row['nom_theme']); ?></div>
                        </div>
                        <div class="course-content">
                            <h3 class="course-title"><?php echo htmlspecialchars($row['titre']); ?></h3>
                            <div class="course-meta">
                                <div class="course-meta-item">
                                    <i class="fas fa-users"></i>
                                    <?php echo htmlspecialchars($row['nom_classe']); ?>
                                </div>
                                <div class="course-meta-item">
                                    <i class="fas fa-book"></i>
                                    <?php echo htmlspecialchars($row['nom_matiere']); ?>
                                </div>
                                <?php if ($is_eleve): ?>
                                <div class="course-meta-item">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    <?php echo htmlspecialchars($row['nom_professeur']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="course-description">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </div>
                            <div class="course-actions">
                                <div class="course-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('d/m/Y', strtotime($row['date_creation'])); ?>
                                </div>
                                <button type="button" class="course-view" onclick="openViewCourseModal(<?php echo $row['id_cours']; ?>)">
                                    عرض الدرس <i class="fas fa-arrow-left"></i>
                                </button>
                            </div>
                            
                            <?php if ($is_prof): ?>
                            <div class="course-admin-actions">
                                <button type="button" class="action-btn action-btn-edit" title="تعديل الدرس" onclick="openEditCourseModal(<?php echo $row['id_cours']; ?>)">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </button>
                                <a href="generer_pdf_cours.php?id=<?php echo $row['id_cours']; ?>" class="action-btn action-btn-pdf" title="تحميل كملف PDF" target="_blank">
                                    <i class="fas fa-file-pdf"></i>
                                    PDF
                                </a>
                                <button type="button" class="action-btn action-btn-delete" title="حذف الدرس" 
                                   onclick="openDeleteCourseModal(<?php echo $row['id_cours']; ?>, '<?php echo htmlspecialchars(addslashes($row['titre'])); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                    حذف
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-courses">
                    <i class="fas fa-book-open"></i>
                    <p><?php echo $is_prof ? 'لم تقم بإضافة أي دروس بعد.' : 'لا توجد دروس متاحة حاليًا.'; ?></p>
                    <?php if ($is_prof): ?>
                    <button type="button" class="btn btn-primary" onclick="openAddCourseModal()">
                        <i class="fas fa-plus"></i>
                        إضافة درس جديد
                    </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal modal-sm">
            <div class="modal-header">
                <div class="modal-title-wrapper">
                    <i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i>
                    <h3 class="modal-title">تأكيد الحذف</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف الدرس "<span id="courseTitleToDelete"></span>"؟</p>
                <p>هذا الإجراء لا يمكن التراجع عنه.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" onclick="closeModal('deleteModal')">
                    إلغاء
                </button>
                <form id="deleteForm" method="post" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_cours" id="deleteCoursId">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash-alt"></i>
                        تأكيد الحذف
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de visualisation du cours -->
    <div class="modal-overlay" id="viewCourseModal">
        <div class="modal modal-lg">
            <div class="modal-header">
                <div class="modal-title-wrapper">
                    <i class="fas fa-book" style="color: var(--primary-color);"></i>
                    <h3 class="modal-title">عرض الدرس</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('viewCourseModal')">&times;</button>
            </div>
            <div class="modal-body" id="viewCourseContent">
                <!-- Le contenu sera chargé dynamiquement via AJAX -->
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary-color);"></i>
                    <p>جاري تحميل محتوى الدرس...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('viewCourseModal')">
                    إغلاق
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de modification du cours -->
    <div class="modal-overlay" id="editCourseModal">
        <div class="modal modal-lg">
            <div class="modal-header">
                <div class="modal-title-wrapper">
                    <i class="fas fa-edit" style="color: var(--warning-color);"></i>
                    <h3 class="modal-title">تعديل الدرس</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('editCourseModal')">&times;</button>
            </div>
            <div class="modal-body" id="editCourseContent">
                <!-- Le contenu sera chargé dynamiquement via AJAX -->
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--warning-color);"></i>
                    <p>جاري تحميل بيانات الدرس...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal d'ajout de cours -->
    <div class="modal-overlay" id="addCourseModal">
        <div class="modal modal-lg">
            <div class="modal-header">
                <div class="modal-title-wrapper">
                    <i class="fas fa-plus" style="color: var(--primary-color);"></i>
                    <h3 class="modal-title">إضافة درس جديد</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('addCourseModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="" method="post" enctype="multipart/form-data" id="addCourseForm">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="edit-form-group">
                        <label for="add_titre" class="edit-form-label">عنوان الدرس *</label>
                        <input type="text" id="add_titre" name="titre" class="edit-form-control" required>
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="add_description" class="edit-form-label">وصف الدرس *</label>
                        <textarea id="add_description" name="description" class="edit-form-control" required></textarea>
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="add_classe" class="edit-form-label">القسم *</label>
                        <select id="add_classe" name="classe" class="edit-form-select" required>
                            <option value="">اختر القسم</option>
                            <?php
                            if (isset($classes) && count($classes) > 0) {
                                foreach($classes as $classe) {
                                    echo "<option value='" . $classe["id_classe"] . "'>" . $classe["nom_classe"] . "</option>";
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
                        <label for="add_theme" class="edit-form-label">الموضوع *</label>
                        <select id="add_theme" name="theme" class="edit-form-select" required>
                            <option value="">اختر الموضوع</option>
                            <?php
                            if (isset($themes) && count($themes) > 0) {
                                foreach($themes as $theme) {
                                    echo "<option value='" . $theme["id_theme"] . "'>" . $theme["nom_theme"] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="add_matiere" class="edit-form-label">المادة *</label>
                        <select id="add_matiere" name="matiere" class="edit-form-select" required>
                            <option value="">اختر المادة</option>
                        </select>
                        <div id="add_matiere_loading" class="loading-text">
                            <span class="spinner"></span> جاري تحميل المواد...
                        </div>
                    </div>
                    
                    <div class="edit-form-group">
                        <label class="edit-form-label">ملف الدرس (اختياري)</label>
                        <div class="form-file">
                            <div class="file-input-wrapper">
                                <input type="file" id="add_fichier" name="fichier" class="file-input" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx">
                                <label for="add_fichier" class="file-input-label">
                                    <i class="fas fa-upload"></i>
                                    اختر ملف
                                </label>
                            </div>
                            <div id="add_fichier_name" class="file-name"></div>
                        </div>
                        <small>الملفات المسموح بها: PDF, Word, PowerPoint, Excel</small>
                    </div>
                    
                    <div class="edit-form-group">
                        <label class="edit-form-label">صور الدرس (اختياري)</label>
                        <div class="form-file">
                            <div class="file-input-wrapper">
                                <input type="file" id="add_images" name="images[]" class="file-input" accept="image/*" multiple>
                                <label for="add_images" class="file-input-label">
                                    <i class="fas fa-images"></i>
                                    اختر صور متعددة
                                </label>
                            </div>
                        </div>
                        <div id="add_images_preview" class="images-preview"></div>
                        <small>يمكنك اختيار عدة صور في نفس الوقت. الصورة الأولى ستكون الصورة الرئيسية للدرس.</small>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addCourseModal')">
                            إلغاء
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            إضافة الدرس
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour ouvrir le modal de confirmation de suppression
        function openDeleteCourseModal(courseId, courseTitle) {
            document.getElementById('courseTitleToDelete').textContent = courseTitle;
            document.getElementById('deleteCoursId').value = courseId;
            openModal('deleteModal');
        }
        
        // Fonction pour ouvrir le modal de visualisation du cours
        function openViewCourseModal(courseId) {
            // Réinitialiser le contenu
            document.getElementById('viewCourseContent').innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary-color);"></i>
                    <p>جاري تحميل محتوى الدرس...</p>
                </div>
            `;
            
            // Ouvrir le modal
            openModal('viewCourseModal');
            
            // Charger le contenu via AJAX
            fetch('get_course_details.php?id=' + courseId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('viewCourseContent').innerHTML = data.html;
                    } else {
                        document.getElementById('viewCourseContent').innerHTML = `
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                ${data.message}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('viewCourseContent').innerHTML = `
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            حدث خطأ أثناء تحميل بيانات الدرس.
                        </div>
                    `;
                });
        }
        
        // Fonction pour ouvrir le modal de modification du cours
        function openEditCourseModal(courseId) {
            // Réinitialiser le contenu
            document.getElementById('editCourseContent').innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--warning-color);"></i>
                    <p>جاري تحميل بيانات الدرس...</p>
                </div>
            `;
            
            // Ouvrir le modal
            openModal('editCourseModal');
            
            // Charger le contenu via AJAX
            fetch('get_course_edit_form.php?id=' + courseId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editCourseContent').innerHTML = data.html;
                        
                        // Initialiser les événements pour les champs de fichier
                        initFileInputs();
                        
                        // Initialiser le filtrage des matières
                        initMatiereFilter();
                    } else {
                        document.getElementById('editCourseContent').innerHTML = `
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                ${data.message}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('editCourseContent').innerHTML = `
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            حدث خطأ أثناء تحميل بيانات الدرس.
                        </div>
                    `;
                });
        }
        
        // Fonction pour ouvrir le modal d'ajout de cours
        function openAddCourseModal() {
            // Réinitialiser le formulaire
            document.getElementById('addCourseForm').reset();
            document.getElementById('add_images_preview').innerHTML = '';
            
            // Ouvrir le modal
            openModal('addCourseModal');
            
            // Initialiser les événements pour les champs de fichier
            document.getElementById('add_fichier').addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : '';
                document.getElementById('add_fichier_name').textContent = fileName;
            });
            
            // Prévisualisation des images multiples
            document.getElementById('add_images').addEventListener('change', function() {
                const previewContainer = document.getElementById('add_images_preview');
                previewContainer.innerHTML = '';
                
                if (this.files) {
                    Array.from(this.files).forEach((file, index) => {
                        if (file.type.match('image.*')) {
                            const reader = new FileReader();
                            
                            reader.onload = function(e) {
                                const previewItem = document.createElement('div');
                                previewItem.className = 'image-preview-item';
                                
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                
                                previewItem.appendChild(img);
                                previewContainer.appendChild(previewItem);
                            }
                            
                            reader.readAsDataURL(file);
                        }
                    });
                }
            });
            
            // Initialiser le filtrage des matières
            const classeSelect = document.getElementById('add_classe');
            const matiereSelect = document.getElementById('add_matiere');
            const matiereLoading = document.getElementById('add_matiere_loading');
            
            classeSelect.addEventListener('change', function() {
                const classeId = this.value;
                
                if (!classeId) {
                    // Si aucune classe n'est sélectionnée, vider le select des matières
                    while (matiereSelect.options.length > 1) {
                        matiereSelect.remove(1);
                    }
                    return;
                }
                
                // Afficher l'indicateur de chargement
                matiereLoading.style.display = 'block';
                
                // Récupérer les matières pour cette classe via AJAX
                fetch('get_matieres_cours.php?classe_id=' + classeId)
                    .then(response => response.json())
                    .then(data => {
                        // Masquer l'indicateur de chargement
                        matiereLoading.style.display = 'none';
                        
                        // Vider le select des matières sauf la première option
                        while (matiereSelect.options.length > 1) {
                            matiereSelect.remove(1);
                        }
                        
                        // Ajouter les nouvelles options
                        data.forEach(matiere => {
                            const option = document.createElement('option');
                            option.value = matiere.matiere_id;
                            option.textContent = matiere.nom;
                            matiereSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        matiereLoading.style.display = 'none';
                    });
            });
        }
        
        // Fonction pour ouvrir un modal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Empêcher le défilement de la page
        }
        
        // Fonction pour fermer un modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = ''; // Réactiver le défilement de la page
        }
        
        // Fermer le modal si on clique en dehors
        window.addEventListener('click', function(event) {
            const modals = document.getElementsByClassName('modal-overlay');
            for (let i = 0; i < modals.length; i++) {
                if (event.target === modals[i]) {
                    closeModal(modals[i].id);
                }
            }
        });
        
        // Initialiser les événements pour les champs de fichier
        function initFileInputs() {
            const fichierInput = document.getElementById('fichier');
            const imagesInput = document.getElementById('images');
            
            if (fichierInput) {
                fichierInput.addEventListener('change', function() {
                    const fileName = this.files[0] ? this.files[0].name : '';
                    document.getElementById('fichier-name').textContent = fileName;
                });
            }
            
            if (imagesInput) {
                imagesInput.addEventListener('change', function() {
                    const previewContainer = document.getElementById('images-preview');
                    if (previewContainer) {
                        previewContainer.innerHTML = '';
                        
                        if (this.files) {
                            Array.from(this.files).forEach((file, index) => {
                                if (file.type.match('image.*')) {
                                    const reader = new FileReader();
                                    
                                    reader.onload = function(e) {
                                        const previewItem = document.createElement('div');
                                        previewItem.className = 'image-preview-item';
                                        
                                        const img = document.createElement('img');
                                        img.src = e.target.result;
                                        
                                        previewItem.appendChild(img);
                                        previewContainer.appendChild(previewItem);
                                    }
                                    
                                    reader.readAsDataURL(file);
                                }
                            });
                        }
                    }
                });
            }
        }
        
        // Initialiser le filtrage des matières en fonction de la classe sélectionnée
        function initMatiereFilter() {
            const classeSelect = document.getElementById('classe');
            const matiereSelect = document.getElementById('matiere');
            const matiereLoading = document.getElementById('matiere-loading');
            
            if (classeSelect && matiereSelect) {
                classeSelect.addEventListener('change', function() {
                    const classeId = this.value;
                    
                    if (!classeId) {
                        // Si aucune classe n'est sélectionnée, vider le select des matières
                        while (matiereSelect.options.length > 1) {
                            matiereSelect.remove(1);
                        }
                        return;
                    }
                    
                    // Afficher l'indicateur de chargement
                    if (matiereLoading) {
                        matiereLoading.style.display = 'block';
                    }
                    
                    // Récupérer les matières pour cette classe via AJAX
                    fetch('get_matieres_cours.php?classe_id=' + classeId)
                        .then(response => response.json())
                        .then(data => {
                            // Masquer l'indicateur de chargement
                            if (matiereLoading) {
                                matiereLoading.style.display = 'none';
                            }
                            
                            // Vider le select des matières sauf la première option
                            while (matiereSelect.options.length > 1) {
                                matiereSelect.remove(1);
                            }
                            
                            // Ajouter les nouvelles options
                            data.forEach(matiere => {
                                const option = document.createElement('option');
                                option.value = matiere.matiere_id;
                                option.textContent = matiere.nom;
                                matiereSelect.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            if (matiereLoading) {
                                matiereLoading.style.display = 'none';
                            }
                        });
                });
            }
        }
        
        // Fonction pour soumettre un commentaire
        function submitComment(courseId) {
            const commentText = document.getElementById('commentaire').value.trim();
            if (!commentText) {
                alert('الرجاء كتابة تعليق قبل الإرسال');
                return false;
            }
            
            // Créer un formulaire caché et le soumettre
            const form = document.createElement('form');
            form.method = 'post';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'comment';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id_cours';
            idInput.value = courseId;
            
            const commentInput = document.createElement('input');
            commentInput.type = 'hidden';
            commentInput.name = 'commentaire';
            commentInput.value = commentText;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            form.appendChild(commentInput);
            
            document.body.appendChild(form);
            form.submit();
            
            return false;
        }
    </script>
</body>
</html>