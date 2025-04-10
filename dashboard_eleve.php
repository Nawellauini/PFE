<?php
session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est bien un élève et que l'email est défini
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'eleve') {
    header('Location: login.php');
    exit;
}

$nom = isset($_SESSION['nom']) ? $_SESSION['nom'] : "Élève inconnu";
$prenom = isset($_SESSION['prenom']) ? $_SESSION['prenom'] : "";
$email = isset($_SESSION['email']) ? $_SESSION['email'] : "Email non disponible";
$classe = isset($_SESSION['classe']) ? $_SESSION['classe'] : "Classe non définie";
$id_eleve = isset($_SESSION['id_eleve']) ? $_SESSION['id_eleve'] : 0;
$classe_id = isset($_SESSION['classe_id']) ? $_SESSION['classe_id'] : 0;

// Valeurs par défaut au cas où les requêtes échouent
$moyenne_generale = 15.75;
$rang = 3;
$total_eleves = 25;
$absences = 2;
$retards = 1;

// Données des matières (par défaut)
$matieres = [
    ["nom" => "الرياضيات", "note" => 16.5, "moyenne_classe" => 14.2, "couleur" => "#4CAF50"],
    ["nom" => "اللغة العربية", "note" => 15.0, "moyenne_classe" => 13.8, "couleur" => "#2196F3"],
    ["nom" => "العلوم", "note" => 17.5, "moyenne_classe" => 15.1, "couleur" => "#FF9800"],
    ["nom" => "التاريخ والجغرافيا", "note" => 14.0, "moyenne_classe" => 12.9, "couleur" => "#9C27B0"]
];

// Données des événements à venir (par défaut)
$evenements = [
    ["date" => "2023-12-15", "titre" => "اختبار الرياضيات", "type" => "امتحان"],
    ["date" => "2023-12-18", "titre" => "رحلة مدرسية", "type" => "نشاط"],
    ["date" => "2023-12-20", "titre" => "اجتماع أولياء الأمور", "type" => "اجتماع"]
];

// Fonction pour exécuter une requête SQL en toute sécurité
function executeQuery($conn, $query, $params = [], $types = "") {
    $result = false;
    
    try {
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            error_log("Erreur de préparation de la requête: " . $conn->error);
            return false;
        }
        
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
        } else {
            error_log("Erreur d'exécution de la requête: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Exception lors de l'exécution de la requête: " . $e->getMessage());
    }
    
    return $result;
}

// Récupération dynamique des données
try {
    // Récupérer le niveau de la classe pour le calcul de la moyenne
    $query_niveau = "SELECT nom_classe FROM classes WHERE id_classe = ?";
    $result_niveau = executeQuery($conn, $query_niveau, [$classe_id], "i");
    
    $niveau = 0;
    if ($result_niveau && $result_niveau->num_rows > 0) {
        $row_niveau = $result_niveau->fetch_assoc();
        $nom_classe = $row_niveau['nom_classe'];
        
        // Déterminer le niveau à partir du nom de la classe
        if (strpos($nom_classe, 'الأولى') !== false || strpos($nom_classe, 'اولى') !== false) {
            $niveau = 1;
        } elseif (strpos($nom_classe, 'الثانية') !== false || strpos($nom_classe, 'ثانية') !== false) {
            $niveau = 2;
        } elseif (strpos($nom_classe, 'الثالثة') !== false || strpos($nom_classe, 'ثالثة') !== false) {
            $niveau = 3;
        } elseif (strpos($nom_classe, 'الرابعة') !== false || strpos($nom_classe, 'رابعة') !== false) {
            $niveau = 4;
        } elseif (strpos($nom_classe, 'الخامسة') !== false || strpos($nom_classe, 'خامسة') !== false) {
            $niveau = 5;
        } elseif (strpos($nom_classe, 'السادسة') !== false || strpos($nom_classe, 'سادسة') !== false) {
            $niveau = 6;
        }
    }
    
    // Par défaut, utiliser le premier trimestre
    $trimestre = 1;
    
    // 1. Récupérer la moyenne générale
    // Vérifier d'abord si les tables et colonnes nécessaires existent
    $check_tables_query = "
    SELECT 
        COUNT(*) as notes_exists 
    FROM 
        information_schema.tables 
    WHERE 
        table_schema = DATABASE() 
        AND table_name = 'notes'";
    
    $check_tables_result = $conn->query($check_tables_query);
    $tables_exist = false;
    
    if ($check_tables_result && $check_tables_result->num_rows > 0) {
        $row = $check_tables_result->fetch_assoc();
        $tables_exist = ($row['notes_exists'] > 0);
    }
    
    if ($tables_exist) {
        // Récupérer les moyennes par domaine pour cet élève
        $query_moyennes = "
        SELECT 
            d.nom AS domaine_nom,
            AVG(n.note) AS moyenne_domaine
        FROM notes n
        JOIN matieres m ON n.matiere_id = m.matiere_id
        JOIN domaines d ON m.domaine_id = d.id
        WHERE n.id_eleve = ? AND m.classe_id = ? AND n.trimestre = ?
        GROUP BY d.nom";
        
        $result_moyennes = executeQuery($conn, $query_moyennes, [$id_eleve, $classe_id, $trimestre], "iii");
        
        if ($result_moyennes && $result_moyennes->num_rows > 0) {
            $moyennes_domaines = [];
            while ($row = $result_moyennes->fetch_assoc()) {
                $moyennes_domaines[$row['domaine_nom']] = $row['moyenne_domaine'];
            }
            
            // Vérifier si tous les domaines nécessaires existent
            $domaine_arabe = isset($moyennes_domaines['مجال اللغة العربية']) ? $moyennes_domaines['مجال اللغة العربية'] : 0;
            $domaine_sciences = isset($moyennes_domaines['مجال العلوم والتكنولوجيا']) ? $moyennes_domaines['مجال العلوم والتكنولوجيا'] : 0;
            $domaine_education = isset($moyennes_domaines['مجال التنشئة']) ? $moyennes_domaines['مجال التنشئة'] : 0;
            $domaine_langues = isset($moyennes_domaines['مجال اللغات الأجنبية']) ? $moyennes_domaines['مجال اللغات الأجنبية'] : 0;
            
            // Calcul selon le niveau
            if ($niveau == 1 || $niveau == 2) {
                // Pour الأولي et الثانية
                $moyenne_generale = ($domaine_arabe * 2 + $domaine_sciences * 2 + $domaine_education * 1) / 5;
            } else {
                // Pour الثالثة, الرابعة, الخامسة, et السادسة
                $moyenne_generale = ($domaine_arabe * 2 + $domaine_sciences * 2 + $domaine_education * 1 + $domaine_langues * 1.5) / 6.5;
            }
            
            // 2. Calculer le rang de l'élève
            $query_eleves = "SELECT id_eleve FROM eleves WHERE id_classe = ?";
            $result_eleves = executeQuery($conn, $query_eleves, [$classe_id], "i");
            
            if ($result_eleves && $result_eleves->num_rows > 0) {
                $moyennes_classe = [];
                $total_eleves = $result_eleves->num_rows;
                
                while ($row_eleve = $result_eleves->fetch_assoc()) {
                    $eleve_id_classe = $row_eleve['id_eleve'];
                    
                    // Récupérer les moyennes par domaine pour cet élève
                    $query_moyennes_eleve = "
                    SELECT 
                        d.nom AS domaine_nom,
                        AVG(n.note) AS moyenne_domaine
                    FROM notes n
                    JOIN matieres m ON n.matiere_id = m.matiere_id
                    JOIN domaines d ON m.domaine_id = d.id
                    WHERE n.id_eleve = ? AND m.classe_id = ? AND n.trimestre = ?
                    GROUP BY d.nom";
                    
                    $result_moyennes_eleve = executeQuery($conn, $query_moyennes_eleve, [$eleve_id_classe, $classe_id, $trimestre], "iii");
                    
                    if ($result_moyennes_eleve && $result_moyennes_eleve->num_rows > 0) {
                        $moyennes_domaines_eleve = [];
                        while ($row = $result_moyennes_eleve->fetch_assoc()) {
                            $moyennes_domaines_eleve[$row['domaine_nom']] = $row['moyenne_domaine'];
                        }
                        
                        // Vérifier si tous les domaines nécessaires existent
                        $domaine_arabe_eleve = isset($moyennes_domaines_eleve['مجال اللغة العربية']) ? $moyennes_domaines_eleve['مجال اللغة العربية'] : 0;
                        $domaine_sciences_eleve = isset($moyennes_domaines_eleve['مجال العلوم والتكنولوجيا']) ? $moyennes_domaines_eleve['مجال العلوم والتكنولوجيا'] : 0;
                        $domaine_education_eleve = isset($moyennes_domaines_eleve['مجال التنشئة']) ? $moyennes_domaines_eleve['مجال التنشئة'] : 0;
                        $domaine_langues_eleve = isset($moyennes_domaines_eleve['مجال اللغات الأجنبية']) ? $moyennes_domaines_eleve['مجال اللغات الأجنبية'] : 0;
                        
                        // Calcul selon le niveau
                        if ($niveau == 1 || $niveau == 2) {
                            // Pour الأولي et الثانية
                            $moyenne_eleve = ($domaine_arabe_eleve * 2 + $domaine_sciences_eleve * 2 + $domaine_education_eleve * 1) / 5;
                        } else {
                            // Pour الثالثة, الرابعة, الخامسة, et السادسة
                            $moyenne_eleve = ($domaine_arabe_eleve * 2 + $domaine_sciences_eleve * 2 + $domaine_education_eleve * 1 + $domaine_langues_eleve * 1.5) / 6.5;
                        }
                        
                        if ($moyenne_eleve > 0) { // Ignorer les élèves sans notes
                            $moyennes_classe[$eleve_id_classe] = $moyenne_eleve;
                        }
                    }
                }
                
                // Trier les moyennes par ordre décroissant
                arsort($moyennes_classe);
                
                // Calculer le rang de l'élève
                $rang = 1;
                foreach ($moyennes_classe as $eleve_id_classe => $moyenne) {
                    if ($eleve_id_classe == $id_eleve) {
                        break;
                    }
                    $rang++;
                }
                
                $total_eleves = count($moyennes_classe);
            }
        }
    }
    
    // 3. Récupérer le nombre d'absences
    // Vérifier si la table absences existe
    $check_absences_query = "
    SELECT 
        COUNT(*) as absences_exists 
    FROM 
        information_schema.tables 
    WHERE 
        table_schema = DATABASE() 
        AND table_name = 'absences'";
    
    $check_absences_result = $conn->query($check_absences_query);
    $absences_table_exists = false;
    
    if ($check_absences_result && $check_absences_result->num_rows > 0) {
        $row = $check_absences_result->fetch_assoc();
        $absences_table_exists = ($row['absences_exists'] > 0);
    }
    
    if ($absences_table_exists) {
        $query_absences = "SELECT COUNT(*) as total FROM absences WHERE id_eleve = ? AND type = 'absence'";
        $result_absences = executeQuery($conn, $query_absences, [$id_eleve], "i");
        
        if ($result_absences && $result_absences->num_rows > 0) {
            $absences = $result_absences->fetch_assoc()['total'];
        }
        
        // 4. Récupérer le nombre de retards
        $query_retards = "SELECT COUNT(*) as total FROM absences WHERE id_eleve = ? AND type = 'retard'";
        $result_retards = executeQuery($conn, $query_retards, [$id_eleve], "i");
        
        if ($result_retards && $result_retards->num_rows > 0) {
            $retards = $result_retards->fetch_assoc()['total'];
        }
    }
    
    // 5. Récupérer les matières et les notes
    if ($tables_exist) {
        $query_matieres = "
        SELECT 
            m.matiere_id,
            m.nom AS matiere_nom,
            n.note,
            (SELECT AVG(note) FROM notes WHERE matiere_id = m.matiere_id AND trimestre = ? AND id_eleve IN (SELECT id_eleve FROM eleves WHERE id_classe = ?)) AS moyenne_classe
        FROM matieres m
        LEFT JOIN notes n ON n.matiere_id = m.matiere_id AND n.id_eleve = ? AND n.trimestre = ?
        WHERE m.classe_id = ?
        ORDER BY m.nom";
        
        $result_matieres = executeQuery($conn, $query_matieres, [$trimestre, $classe_id, $id_eleve, $trimestre, $classe_id], "iiiii");
        
        if ($result_matieres && $result_matieres->num_rows > 0) {
            $matieres = [];
            $colors = ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#E91E63', '#3F51B5', '#009688'];
            $color_index = 0;
            
            while ($row = $result_matieres->fetch_assoc()) {
                if (isset($row['note'])) { // Ne prendre que les matières avec des notes
                    $matieres[] = [
                        "nom" => $row['matiere_nom'],
                        "note" => $row['note'],
                        "moyenne_classe" => $row['moyenne_classe'] ?: 0,
                        "couleur" => $colors[$color_index % count($colors)]
                    ];
                    $color_index++;
                    
                    // Limiter à 4 matières pour l'affichage dans le dashboard
                    if (count($matieres) >= 4) {
                        break;
                    }
                }
            }
        }
    }
    
    // 6. Récupérer les événements à venir
    // Vérifier si la table evenements existe
    $check_evenements_query = "
    SELECT 
        COUNT(*) as evenements_exists 
    FROM 
        information_schema.tables 
    WHERE 
        table_schema = DATABASE() 
        AND table_name = 'evenements'";
    
    $check_evenements_result = $conn->query($check_evenements_query);
    $evenements_table_exists = false;
    
    if ($check_evenements_result && $check_evenements_result->num_rows > 0) {
        $row = $check_evenements_result->fetch_assoc();
        $evenements_table_exists = ($row['evenements_exists'] > 0);
    }
    
    if ($evenements_table_exists) {
        $query_evenements = "
        SELECT 
            date_evenement,
            titre,
            type
        FROM evenements
        WHERE date_evenement >= CURDATE() AND (classe_id = ? OR classe_id IS NULL)
        ORDER BY date_evenement
        LIMIT 3";
        
        $result_evenements = executeQuery($conn, $query_evenements, [$classe_id], "i");
        
        if ($result_evenements && $result_evenements->num_rows > 0) {
            $evenements = [];
            while ($row = $result_evenements->fetch_assoc()) {
                $evenements[] = [
                    "date" => $row['date_evenement'],
                    "titre" => $row['titre'],
                    "type" => $row['type']
                ];
            }
        }
    }
    
} catch (Exception $e) {
    // En cas d'erreur, utiliser des valeurs par défaut
    error_log("Erreur dans dashboard_eleve.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo htmlspecialchars($nom . ' ' . $prenom); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --success-color: #27ae60;
            --border-radius: 12px;
            --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background-color: white;
            box-shadow: var(--box-shadow);
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .user-name {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .user-email {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            margin-bottom: 10px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: var(--border-radius);
            color: var(--dark-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .nav-link:hover {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
        }
        
        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .nav-icon {
            margin-left: 10px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-right: 280px;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--dark-color);
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .notification-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark-color);
            position: relative;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }
        
        .notification-btn:hover {
            background-color: var(--light-color);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            left: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: var(--danger-color);
            color: white;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-left: 15px;
            flex-shrink: 0;
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
            line-height: 1;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .progress-card {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: var(--border-radius);
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-title {
            font-weight: bold;
        }
        
        .progress-value {
            font-weight: bold;
        }
        
        .progress-bar-container {
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            border-radius: 5px;
        }
        
        .progress-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .event-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: var(--border-radius);
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
            transition: var(--transition);
        }
        
        .event-card:hover {
            transform: translateX(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .event-date {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            flex-shrink: 0;
        }
        
        .event-day {
            font-size: 1.5rem;
            font-weight: bold;
            line-height: 1;
        }
        
        .event-month {
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        
        .event-info {
            flex: 1;
        }
        
        .event-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .event-type {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .btn-custom {
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: bold;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-2px);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                padding: 15px 10px;
            }
            
            .logo, .user-info, .nav-text {
                display: none;
            }
            
            .sidebar-header {
                padding-bottom: 10px;
                margin-bottom: 10px;
            }
            
            .nav-link {
                justify-content: center;
                padding: 12px;
            }
            
            .nav-icon {
                margin: 0;
            }
            
            .main-content {
                margin-right: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px;
            }
            
            .logo, .user-info, .nav-text {
                display: block;
            }
            
            .nav-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .nav-item {
                margin-bottom: 0;
                flex: 1;
                min-width: 120px;
            }
            
            .nav-link {
                flex-direction: column;
                text-align: center;
                padding: 10px;
                height: 100%;
            }
            
            .nav-icon {
                margin: 0 0 5px 0;
            }
            
            .main-content {
                margin-right: 0;
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">مدرستي</div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo substr($prenom, 0, 1) . substr($nom, 0, 1); ?>
                </div>
                <div class="user-name"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($email); ?></div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-home nav-icon"></i>
                        <span class="nav-text">الرئيسية</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-book nav-icon"></i>
                        <span class="nav-text"> ملفي الشخصي</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="eleve_observations.php" class="nav-link">
                        <i class="fas fa-book nav-icon"></i>
                        <span class="nav-text">ملاحظاتي</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="afficher_classe.php?eleve_id=<?php echo $id_eleve; ?>" class="nav-link">
                        <i class="fas fa-graduation-cap nav-icon"></i>
                        <span class="nav-text">النتائج</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="liste_cours.php" class="nav-link">
                    <i class="fas fa-book-reader nav-icon"></i>
                    <span class="nav-text">الدروس</span>
                    </a>
                </li>
                <li class="nav-item">
    <a href="student_agenda.php" class="nav-link">
        <i class="fas fa-calendar-alt nav-icon"></i>
        <span class="nav-text">الروزنامة</span>
    </a> 
</li>

                <li class="nav-item">
                    <a href="consulter_messages.php" class="nav-link">
                        <i class="fas fa-envelope nav-icon"></i>
                        <span class="nav-text">الرسائل</span>
                    </a>
                </li>
                <li class="nav-item">
    <a href="envoyer_message_eleve.php" class="nav-link">
        <i class="fas fa-paper-plane nav-icon"></i>
        <span class="nav-text">إرسال رسالة إلى الأستاذ</span>
    </a>
</li>

                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt nav-icon"></i>
                        <span class="nav-text">تسجيل الخروج</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">لوحة التحكم</h1>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(52, 152, 219, 0.1); color: #3498db;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($moyenne_generale, 2); ?></div>
                        <div class="stat-label">المعدل العام</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $rang; ?>/<?php echo $total_eleves; ?></div>
                        <div class="stat-label">الترتيب في القسم</div>
                    </div>
                </div>
                
              
                
              
            </div>
            
            <div class="row">
                <!-- Matières et Notes -->
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <span>المواد والنتائج</span>
                            <div class="card-header-icon">
                                <i class="fas fa-book"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php foreach ($matieres as $matiere): ?>
                                <div class="progress-card">
                                    <div class="progress-header">
                                        <div class="progress-title"><?php echo htmlspecialchars($matiere['nom']); ?></div>
                                        <div class="progress-value"><?php echo number_format($matiere['note'], 1); ?>/20</div>
                                    </div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill" style="width: <?php echo ($matiere['note'] / 20) * 100; ?>%; background-color: <?php echo $matiere['couleur']; ?>"></div>
                                    </div>
                                    <div class="progress-footer">
                                        <div>معدل القسم: <?php echo number_format($matiere['moyenne_classe'], 1); ?>/20</div>
                                        <div>
                                            <?php 
                                            if ($matiere['note'] > $matiere['moyenne_classe']) {
                                                echo '<span style="color: #2ecc71;"><i class="fas fa-arrow-up"></i> فوق المعدل</span>';
                                            } elseif ($matiere['note'] < $matiere['moyenne_classe']) {
                                                echo '<span style="color: #e74c3c;"><i class="fas fa-arrow-down"></i> تحت المعدل</span>';
                                            } else {
                                                echo '<span style="color: #f39c12;"><i class="fas fa-equals"></i> في المعدل</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                           
                        </div>
                    </div>
                </div>
                
                <!-- Événements à venir -->
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <span>الأحداث القادمة</span>
                            <div class="card-header-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php foreach ($evenements as $evenement): 
                                $date = new DateTime($evenement['date']);
                                $jour = $date->format('d');
                                $mois = $date->format('M');
                            ?>
                                <div class="event-card">
                                    <div class="event-date">
                                        <div class="event-day"><?php echo $jour; ?></div>
                                        <div class="event-month"><?php echo $mois; ?></div>
                                    </div>
                                    <div class="event-info">
                                        <div class="event-title"><?php echo htmlspecialchars($evenement['titre']); ?></div>
                                        <div class="event-type"><?php echo htmlspecialchars($evenement['type']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                           
                        </div>
                    </div>
                    
                    <!-- Informations de l'élève -->
                   
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour adapter l'interface en fonction de la taille de l'écran
        function adjustLayout() {
            const width = window.innerWidth;
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (width <= 992) {
                sidebar.classList.add('collapsed');
                mainContent.style.marginRight = '70px';
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.style.marginRight = '280px';
            }
            
            if (width <= 768) {
                mainContent.style.marginRight = '0';
            }
        }
        
        // Appliquer l'ajustement au chargement et au redimensionnement
        window.addEventListener('load', adjustLayout);
        window.addEventListener('resize', adjustLayout);
    </script>
</body>
</html>