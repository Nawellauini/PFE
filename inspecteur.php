<?php
session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est connecté en tant qu'inspecteur
if (!isset($_SESSION['id_inspecteur'])) {
    header("Location: login.php");
    exit();
}

$inspecteur_id = $_SESSION['id_inspecteur'];
$inspecteur_name = isset($_SESSION['nom_inspecteur']) ? $_SESSION['nom_inspecteur'] . ' ' . $_SESSION['prenom_inspecteur'] : 'مفتّش';

// Traitement des actions
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$success_message = '';
$error_message = '';

// Initialisation du tableau des rapports par mois
$rapports_par_mois = array_fill(1, 12, 0);

// Fonction pour obtenir le nombre d'enregistrements de manière sécurisée
function getCountSafely($conn, $table, $id_field, $id_value) {
    $query = "SELECT COUNT(*) as count FROM " . $table . " WHERE " . $id_field . " = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log("Prepare failed for table $table: " . $conn->error);
        return 0;
    }
    $stmt->bind_param("i", $id_value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Fonction pour obtenir le nombre distinct d'enregistrements de manière sécurisée
function getDistinctCountSafely($conn, $table, $distinct_field, $id_field, $id_value) {
    $query = "SELECT COUNT(DISTINCT " . $distinct_field . ") as count FROM " . $table . " WHERE " . $id_field . " = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log("Prepare failed for table $table: " . $conn->error);
        return 0;
    }
    $stmt->bind_param("i", $id_value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Récupération des données pour le tableau de bord
if ($page == 'dashboard' || $page == 'statistiques') {
    // Récupérer les statistiques pour le tableau de bord en utilisant des requêtes séparées
    $stats = [
        'total_rapports' => getCountSafely($conn, 'rapports_inspection', 'id_inspecteur', $inspecteur_id),
        'total_formations' => getCountSafely($conn, 'formations_suivies', 'id_inspecteur', $inspecteur_id),
        'total_classes' => getDistinctCountSafely($conn, 'evaluations_classes', 'id_classe', 'id_inspecteur', $inspecteur_id),
        'total_professeurs' => getDistinctCountSafely($conn, 'evaluations_professeurs', 'id_professeur', 'id_inspecteur', $inspecteur_id)
    ];

    // Statistiques des rapports par mois
    $query_rapports_mois = "SELECT MONTH(date_creation) as mois, COUNT(*) as total 
                           FROM rapports_inspection 
                           WHERE id_inspecteur = ? AND YEAR(date_creation) = YEAR(CURRENT_DATE()) 
                           GROUP BY MONTH(date_creation)";
    $stmt_rapports_mois = $conn->prepare($query_rapports_mois);
    if ($stmt_rapports_mois) {
        $stmt_rapports_mois->bind_param("i", $inspecteur_id);
        $stmt_rapports_mois->execute();
        $result_rapports_mois = $stmt_rapports_mois->get_result();

        while ($row = $result_rapports_mois->fetch_assoc()) {
            $rapports_par_mois[$row['mois']] = $row['total'];
        }
    }

    // Statistiques des évaluations par type
    $eval_type = [
        'total_classes' => getCountSafely($conn, 'evaluations_classes', 'id_inspecteur', $inspecteur_id),
        'total_profs' => getCountSafely($conn, 'evaluations_professeurs', 'id_inspecteur', $inspecteur_id)
    ];
}

if ($page == 'dashboard') {
    // Récupérer les derniers rapports d'inspection
    $query_rapports = "SELECT r.*, c.nom_classe 
                      FROM rapports_inspection r 
                      LEFT JOIN classes c ON r.id_classe = c.id_classe 
                      WHERE r.id_inspecteur = ? 
                      ORDER BY r.date_creation DESC LIMIT 5";
    $stmt_rapports = $conn->prepare($query_rapports);
    if ($stmt_rapports) {
        $stmt_rapports->bind_param("i", $inspecteur_id);
        $stmt_rapports->execute();
        $result_rapports = $stmt_rapports->get_result();
    } else {
        $result_rapports = null;
        error_log("Prepare failed for rapports query: " . $conn->error);
    }

    // Récupérer les dernières formations suivies
    $query_formations = "SELECT f.*, p.nom AS professeur_nom, p.prenom AS professeur_prenom 
                        FROM formations_suivies f 
                        JOIN professeurs p ON f.id_professeur = p.id_professeur 
                        WHERE f.id_inspecteur = ? 
                        ORDER BY f.date_formation DESC LIMIT 5";
    $stmt_formations = $conn->prepare($query_formations);
    if ($stmt_formations) {
        $stmt_formations->bind_param("i", $inspecteur_id);
        $stmt_formations->execute();
        $result_formations = $stmt_formations->get_result();
    } else {
        $result_formations = null;
        error_log("Prepare failed for formations query: " . $conn->error);
    }
}

// Fonction pour formater la date en arabe tunisien
function formatDateTunisien($date) {
    $mois_ar = [
        1 => "جانفي", 2 => "فيفري", 3 => "مارس", 4 => "أفريل",
        5 => "ماي", 6 => "جوان", 7 => "جويلية", 8 => "أوت",
        9 => "سبتمبر", 10 => "أكتوبر", 11 => "نوفمبر", 12 => "ديسمبر"
    ];
    
    $jour_ar = [
        "Monday" => "الإثنين", "Tuesday" => "الثلاثاء", "Wednesday" => "الأربعاء",
        "Thursday" => "الخميس", "Friday" => "الجمعة", "Saturday" => "السبت", "Sunday" => "الأحد"
    ];
    
    $timestamp = strtotime($date);
    $jour_semaine = date('l', $timestamp);
    $jour = date('j', $timestamp);
    $mois = date('n', $timestamp);
    $annee = date('Y', $timestamp);
    
    return $jour_ar[$jour_semaine] . " " . $jour . " " . $mois_ar[$mois] . " " . $annee;
}

// Récupérer les statistiques mensuelles pour l'année en cours
$stats_mensuelles = array_fill(1, 12, 0);
$current_month = date('n');
$current_year = date('Y');

$query_stats_mensuelles = "SELECT 
    MONTH(date_creation) as mois, 
    COUNT(*) as total 
    FROM rapports_inspection 
    WHERE id_inspecteur = ? AND YEAR(date_creation) = ? 
    GROUP BY MONTH(date_creation)";
$stmt_stats_mensuelles = $conn->prepare($query_stats_mensuelles);
if ($stmt_stats_mensuelles) {
    $stmt_stats_mensuelles->bind_param("ii", $inspecteur_id, $current_year);
    $stmt_stats_mensuelles->execute();
    $result_stats_mensuelles = $stmt_stats_mensuelles->get_result();

    while ($row = $result_stats_mensuelles->fetch_assoc()) {
        $stats_mensuelles[$row['mois']] = $row['total'];
    }
}

// Récupérer les statistiques pour le mois en cours
$stats_mois_courant = [
    'rapports' => 0,
    'classes' => 0,
    'profs' => 0,
    'formations' => 0
];

// Fonction pour obtenir les statistiques mensuelles
function getMonthlyStats($conn, $table, $date_field, $id_field, $id_value, $month, $year) {
    $query = "SELECT COUNT(*) as total FROM " . $table . " 
              WHERE " . $id_field . " = ? AND MONTH(" . $date_field . ") = ? AND YEAR(" . $date_field . ") = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log("Prepare failed for monthly stats on table $table: " . $conn->error);
        return 0;
    }
    $stmt->bind_param("iii", $id_value, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Fonction pour obtenir les statistiques annuelles
function getYearlyStats($conn, $table, $date_field, $id_field, $id_value, $year) {
    $query = "SELECT COUNT(*) as total FROM " . $table . " 
              WHERE " . $id_field . " = ? AND YEAR(" . $date_field . ") = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log("Prepare failed for yearly stats on table $table: " . $conn->error);
        return 0;
    }
    $stmt->bind_param("ii", $id_value, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Statistiques pour le mois en cours
$stats_mois_courant['rapports'] = getMonthlyStats($conn, 'rapports_inspection', 'date_creation', 'id_inspecteur', $inspecteur_id, $current_month, $current_year);
$stats_mois_courant['classes'] = getMonthlyStats($conn, 'evaluations_classes', 'date_evaluation', 'id_inspecteur', $inspecteur_id, $current_month, $current_year);
$stats_mois_courant['profs'] = getMonthlyStats($conn, 'evaluations_professeurs', 'date_evaluation', 'id_inspecteur', $inspecteur_id, $current_month, $current_year);
$stats_mois_courant['formations'] = getMonthlyStats($conn, 'formations_suivies', 'date_formation', 'id_inspecteur', $inspecteur_id, $current_month, $current_year);

// Récupérer les statistiques pour l'année en cours
$stats_annee_courante = [
    'rapports' => 0,
    'classes' => 0,
    'profs' => 0,
    'formations' => 0
];

// Statistiques pour l'année en cours
$stats_annee_courante['rapports'] = getYearlyStats($conn, 'rapports_inspection', 'date_creation', 'id_inspecteur', $inspecteur_id, $current_year);
$stats_annee_courante['classes'] = getYearlyStats($conn, 'evaluations_classes', 'date_evaluation', 'id_inspecteur', $inspecteur_id, $current_year);
$stats_annee_courante['profs'] = getYearlyStats($conn, 'evaluations_professeurs', 'date_evaluation', 'id_inspecteur', $inspecteur_id, $current_year);
$stats_annee_courante['formations'] = getYearlyStats($conn, 'formations_suivies', 'date_formation', 'id_inspecteur', $inspecteur_id, $current_year);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فضاء المتفقد - منظومة متابعة التفقّد التربوي</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Variables de couleurs */
        :root {
            --primary-color: #1e40af;
            --primary-light: #3b82f6;
            --primary-dark: #1e3a8a;
            --secondary-color: #0f766e;
            --secondary-light: #14b8a6;
            --accent-color: #f59e0b;
            --success-color: #16a34a;
            --warning-color: #f59e0b;
            --danger-color: #dc2626;
            --light-color: #f8fafc;
            --dark-color: #0f172a;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --border-radius: 0.5rem;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --box-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
            
            /* Nouvelles couleurs pour les graphiques */
            --chart-primary: #4f46e5;
            --chart-primary-light: #818cf8;
            --chart-secondary: #0ea5e9;
            --chart-secondary-light: #7dd3fc;
            --chart-accent: #f59e0b;
            --chart-accent-light: #fcd34d;
            --chart-success: #10b981;
            --chart-success-light: #6ee7b7;
            --chart-danger: #ef4444;
            --chart-danger-light: #fca5a5;
        }
        
        /* Styles généraux */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar */
        #sidebar {
            width: 280px;
            background: linear-gradient(to bottom, var(--primary-dark), var(--primary-color));
            color: #fff;
            transition: var(--transition);
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--box-shadow);
        }

        #sidebar::-webkit-scrollbar {
            width: 6px;
        }

        #sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        #sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        #sidebar.active {
            margin-right: -280px;
        }

        .sidebar-header {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: white;
        }

        .sidebar-header h4 {
            margin: 5px 0 0;
            font-size: 1.1rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 0.5px;
        }

        .profile-info {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
        }

        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-info h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
        }

        .profile-info p {
            margin: 5px 0 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
            font-weight: 500;
        }

        #sidebar ul.components {
            padding: 1.5rem 0;
        }

        #sidebar ul li {
            padding: 0;
            margin-bottom: 0.25rem;
        }

        #sidebar ul li a {
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: var(--transition);
            border-right: 4px solid transparent;
            font-weight: 500;
            font-size: 1.05rem;
        }

        #sidebar ul li a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-right-color: var(--accent-color);
        }

        #sidebar ul li.active > a {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-right-color: var(--accent-color);
            font-weight: 600;
        }

        #sidebar ul li a i {
            margin-left: 0.75rem;
            width: 1.5rem;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Content */
        #content {
            width: calc(100% - 280px);
            min-height: 100vh;
            transition: var(--transition);
            position: relative;
            margin-right: 280px;
            background-color: var(--gray-100);
        }

        #content.active {
            width: 100%;
            margin-right: 0;
        }

        .navbar {
            padding: 1rem 1.5rem;
            background: white;
            border: none;
            border-radius: 0;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #sidebarCollapse {
            background: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        #sidebarCollapse:hover {
            background: var(--primary-dark);
        }

        .date-time {
            font-size: 1rem;
            color: var(--gray-600);
            font-weight: 500;
            background-color: var(--gray-100);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .page-title {
            margin-bottom: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            border-right: 4px solid var(--accent-color);
            padding: 0.5rem 1rem;
            background-color: white;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            box-shadow: var(--box-shadow);
            display: inline-block;
            font-size: 1.5rem;
        }

        /* Cards */
        .stats-cards .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            height: 100%;
        }

        .stats-cards .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-lg);
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            height: 100%;
        }

        .stats-card .card-body {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            height: 100%;
        }

        .stats-icon {
            font-size: 2.5rem;
            margin-left: 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .stats-info h5 {
            margin: 0;
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .stats-info h3 {
            margin: 0.5rem 0 0;
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .stats-cards .col-md-3:nth-child(1) .stats-card {
            background: linear-gradient(135deg, #1e40af, #1e3a8a);
        }

        .stats-cards .col-md-3:nth-child(2) .stats-card {
            background: linear-gradient(135deg, #0f766e, #115e59);
        }

        .stats-cards .col-md-3:nth-child(3) .stats-card {
            background: linear-gradient(135deg, #b45309, #92400e);
        }

        .stats-cards .col-md-3:nth-child(4) .stats-card {
            background: linear-gradient(135deg, #6d28d9, #5b21b6);
        }

        /* Charts and Cards */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            background-color: white;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--box-shadow-lg);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 1.2rem;
        }

        .card-header h5 i {
            margin-left: 0.5rem;
            color: var(--primary-color);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Nouveau style pour les cartes de graphiques */
        .chart-card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
            background: white;
            position: relative;
        }

        .chart-card:hover {
            box-shadow: var(--box-shadow-lg);
            transform: translateY(-5px);
        }

        .chart-card .card-header {
            background: linear-gradient(135deg, var(--chart-primary), var(--chart-primary-light));
            color: white;
            border-bottom: none;
            padding: 1.25rem 1.5rem;
            position: relative;
        }

        .chart-card .card-header h5 {
            color: white;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .chart-card .card-header h5 i {
            margin-left: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.25rem;
        }

        .chart-card .card-body {
            padding: 1.5rem;
            position: relative;
        }

        .chart-container {
            position: relative;
            margin: 0 auto;
            height: 300px;
        }

        .chart-info {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.9);
            border-radius: var(--border-radius);
            padding: 0.75rem;
            box-shadow: var(--box-shadow);
            z-index: 10;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .chart-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--gray-700);
        }

        .chart-info-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .chart-legend {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .chart-legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--gray-700);
            background: var(--gray-100);
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        /* Tables */
        .table {
            margin-bottom: 0;
            color: var(--gray-800);
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--primary-dark);
            background-color: var(--gray-100);
            padding: 1rem;
            font-size: 1rem;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem;
            border-top: 1px solid var(--gray-200);
        }

        .table tr:hover {
            background-color: var(--gray-100);
        }

        /* List Group */
        .list-group-item {
            border: 1px solid var(--gray-200);
            margin-bottom: 0.5rem;
            border-radius: var(--border-radius) !important;
            transition: var(--transition);
        }

        .list-group-item:hover {
            background-color: var(--gray-100);
            transform: translateX(-5px);
        }

        .list-group-item h6 {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.25rem;
        }

        .list-group-item small {
            color: var(--gray-500);
            font-weight: 500;
        }

        .list-group-item p {
            margin-bottom: 0;
            color: var(--gray-600);
        }

        /* Quick Access Buttons */
        .quick-access .btn {
            border-radius: var(--border-radius);
            padding: 1.5rem 1rem;
            font-weight: 600;
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: var(--box-shadow);
            border: none;
        }

        .quick-access .btn:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-lg);
        }

        .quick-access .btn i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .quick-access .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }

        .quick-access .btn-success {
            background: linear-gradient(135deg, var(--secondary-color), #115e59);
        }

        .quick-access .btn-info {
            background: linear-gradient(135deg, #0284c7, #0369a1);
        }

        .quick-access .btn-warning {
            background: linear-gradient(135deg, var(--accent-color), #b45309);
        }

        /* Badges */
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 600;
            border-radius: 50rem;
            font-size: 0.85rem;
        }

        .badge-pill {
            padding-right: 0.8em;
            padding-left: 0.8em;
        }

        .badge-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .badge-success {
            background-color: var(--success-color);
            color: white;
        }

        .badge-info {
            background-color: #0284c7;
            color: white;
        }

        .badge-warning {
            background-color: var(--warning-color);
            color: var(--gray-900);
        }

        .badge-secondary {
            background-color: var(--gray-600);
            color: white;
        }

        /* Responsive */
        @media (max-width: 992px) {
            #sidebar {
                margin-right: -280px;
            }
            #sidebar.active {
                margin-right: 0;
            }
            #content {
                width: 100%;
                margin-right: 0;
            }
            #content.active {
                width: calc(100% - 280px);
                margin-right: 280px;
            }
            .stats-cards .col-md-3 {
                margin-bottom: 1rem;
            }
            .quick-access .col-md-3 {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }
            .page-title {
                font-size: 1.25rem;
            }
            .stats-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
            .stats-info h3 {
                font-size: 1.8rem;
            }
            .card-header h5 {
                font-size: 1.1rem;
            }
            #content.active {
                width: 100%;
                margin-right: 0;
                position: relative;
            }
            #sidebar.active {
                width: 100%;
                position: absolute;
                z-index: 1050;
            }
            .chart-container {
                height: 250px;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .stats-cards .card {
            animation: fadeIn 0.5s ease-out forwards;
            animation-delay: calc(var(--animation-order) * 0.1s);
            opacity: 0;
        }

        /* Animation pour les graphiques */
        @keyframes growUp {
            from { height: 0; }
            to { height: 100%; }
        }

        .chart-animation {
            animation: growUp 1s ease-out forwards;
        }

        /* Alerts */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(22, 163, 74, 0.1);
            color: var(--success-color);
            border-right: 4px solid var(--success-color);
        }

        .alert-danger {
            background-color: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
            border-right: 4px solid var(--danger-color);
        }

        /* Page en construction */
        .construction-container {
            text-align: center;
            padding: 3rem 1rem;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            max-width: 600px;
            margin: 2rem auto;
        }

        .construction-icon {
            font-size: 5rem;
            color: var(--accent-color);
            margin-bottom: 1.5rem;
        }

        .construction-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }

        .construction-text {
            font-size: 1.2rem;
            color: var(--gray-600);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3>منظومة التفقّد</h3>
                <h4>فضاء المتفقد التربوي</h4>
            </div>
            <div class="profile-info">
                <img src="uploads/photos_eleves/myschool.png" alt="صورة الملف الشخصي" class="profile-img" onerror="this.src='https://via.placeholder.com/100?text=مفتّش';this.onerror='';">
                <h5><?php echo $inspecteur_name; ?></h5>
                <p>متفقد تربوي</p>
            </div>
            <ul class="list-unstyled components">
                <li class="<?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
                    <a href="inspecteur.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> لوحة المتابعة</a>
                </li>
                <li>
                    <a href="rapports.php"><i class="fas fa-file-alt"></i> تقارير التفقّد</a>
                </li>
                <li class="<?php echo ($page == 'evaluations_classes') ? 'active' : ''; ?>">
                    <a href="evaluations_classes.php"><i class="fas fa-school"></i> تقييم الأقسام</a>
                </li>
                <li class="<?php echo ($page == 'evaluations_professeurs') ? 'active' : ''; ?>">
                    <a href="evaluations_professeurs.php"><i class="fas fa-chalkboard-teacher"></i> تقييم المدرّسين</a>
                </li>
                <li class="<?php echo ($page == 'formations') ? 'active' : ''; ?>">
                    <a href="inspecteur.php?page=formations"><i class="fas fa-graduation-cap"></i> متابعة التكوين</a>
                </li>
                <li class="<?php echo ($page == 'statistiques') ? 'active' : ''; ?>">
                    <a href="inspecteur.php?page=statistiques"><i class="fas fa-chart-bar"></i> الإحصائيات</a>
                </li>
                <li>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-align-right"></i>
                        <span>القائمة</span>
                    </button>
                    <div class="mr-auto">
                        <div class="date-time">
                            <span id="date-time"><i class="far fa-calendar-alt ml-1"></i> <span id="current-date-time"></span></span>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <?php if (isset($success_message) && !empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle ml-2"></i> <?php echo $success_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_message) && !empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle ml-2"></i> <?php echo $error_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($page == 'dashboard'): ?>
                <!-- Tableau de bord -->
                <h2 class="page-title"><i class="fas fa-tachometer-alt ml-2"></i> لوحة المتابعة</h2>
                
                <!-- Stats Cards -->
                <div class="row stats-cards">
                    <div class="col-lg-3 col-md-6 mb-4" style="--animation-order: 1">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="stats-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="stats-info">
                                    <h5>تقارير التفقّد</h5>
                                    <h3><?php echo isset($stats['total_rapports']) ? $stats['total_rapports'] : 0; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4" style="--animation-order: 2">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="stats-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stats-info">
                                    <h5>دورات التكوين</h5>
                                    <h3><?php echo isset($stats['total_formations']) ? $stats['total_formations'] : 0; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4" style="--animation-order: 3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="stats-icon">
                                    <i class="fas fa-school"></i>
                                </div>
                                <div class="stats-info">
                                    <h5>الأقسام المتابعة</h5>
                                    <h3><?php echo isset($stats['total_classes']) ? $stats['total_classes'] : 0; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4" style="--animation-order: 4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="stats-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stats-info">
                                    <h5>المدرّسون</h5>
                                    <h3><?php echo isset($stats['total_professeurs']) ? $stats['total_professeurs'] : 0; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="chart-card h-100">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar ml-2"></i> تقارير التفقّد حسب الشهر</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="rapportsChart"></canvas>
                                </div>
                                <div class="chart-legend">
                                    <div class="chart-legend-item">
                                        <span class="chart-info-color" style="background-color: var(--chart-primary);"></span>
                                        <span>تقارير التفقّد</span>
                                    </div>
                                    <div class="chart-legend-item">
                                        <span class="chart-info-color" style="background-color: var(--chart-primary-light);"></span>
                                        <span>المعدل الشهري</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5><i class="fas fa-history ml-2"></i> آخر الأنشطة</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php if (isset($result_rapports) && $result_rapports && $result_rapports->num_rows > 0): ?>
                                        <?php while ($rapport = $result_rapports->fetch_assoc()): ?>
                                        <a href="voir_rapport.php?id=<?php echo $rapport['id']; ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($rapport['titre']); ?></h6>
                                                <small><i class="far fa-calendar-alt ml-1"></i> <?php echo date('d/m/Y', strtotime($rapport['date_creation'])); ?></small>
                                            </div>
                                            <p class="mb-1"><i class="fas fa-school ml-1"></i> القسم: <?php echo $rapport['nom_classe'] ? htmlspecialchars($rapport['nom_classe']) : 'غير متوفر'; ?></p>
                                        </a>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="text-center p-4">
                                            <i class="fas fa-info-circle mb-3" style="font-size: 2rem; color: var(--gray-400);"></i>
                                            <p class="mb-0">لا توجد أنشطة حديثة</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Access -->
                <div class="row mt-2 quick-access">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt ml-2"></i> وصول سريع</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="ajouter_rapport.php" class="btn btn-primary btn-block">
                                            <i class="fas fa-plus-circle"></i>
                                            إضافة تقرير جديد
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="evaluations_classes.php" class="btn btn-success btn-block">
                                            <i class="fas fa-school"></i>
                                            تقييم قسم
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="evaluations_professeurs.php" class="btn btn-info btn-block">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            تقييم مدرّس
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="inspecteur.php?page=statistiques" class="btn btn-warning btn-block">
                                            <i class="fas fa-chart-bar"></i>
                                            عرض الإحصائيات
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif ($page == 'statistiques'): ?>
                <!-- Statistiques -->
                <h2 class="page-title"><i class="fas fa-chart-bar ml-2"></i> الإحصائيات</h2>
                
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="chart-card h-100">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar ml-2"></i> تقارير التفقّد حسب الشهر</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="statsRapportsChart"></canvas>
                                </div>
                                <div class="chart-legend">
                                    <div class="chart-legend-item">
                                        <span class="chart-info-color" style="background-color: var(--chart-primary);"></span>
                                        <span>تقارير التفقّد</span>
                                    </div>
                                    <div class="chart-legend-item">
                                        <span class="chart-info-color" style="background-color: var(--chart-accent);"></span>
                                        <span>الشهر الحالي</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="chart-card h-100">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie ml-2"></i> التقييمات حسب النوع</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="statsEvaluationsChart"></canvas>
                                </div>
                                <div class="chart-legend">
                                    <div class="chart-legend-item">
                                        <span class="chart-info-color" style="background-color: var(--chart-secondary);"></span>
                                        <span>تقييم الأقسام</span>
                                    </div>
                                    <div class="chart-legend-item">
                                        <span class="chart-info-color" style="background-color: var(--chart-success);"></span>
                                        <span>تقييم المدرّسين</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list-alt ml-2"></i> ملخّص الأنشطة</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>النشاط</th>
                                                <th>المجموع</th>
                                                <th>هذا الشهر</th>
                                                <th>هذه السنة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><i class="fas fa-file-alt ml-2 text-primary"></i> تقارير التفقّد</td>
                                                <td><span class="badge badge-pill badge-primary"><?php echo isset($stats['total_rapports']) ? $stats['total_rapports'] : 0; ?></span></td>
                                                <td><span class="badge badge-pill badge-info"><?php echo $stats_mois_courant['rapports']; ?></span></td>
                                                <td><span class="badge badge-pill badge-secondary"><?php echo $stats_annee_courante['rapports']; ?></span></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-school ml-2 text-success"></i> تقييمات الأقسام</td>
                                                <td><span class="badge badge-pill badge-success"><?php echo isset($eval_type['total_classes']) ? $eval_type['total_classes'] : 0; ?></span></td>
                                                <td><span class="badge badge-pill badge-info"><?php echo $stats_mois_courant['classes']; ?></span></td>
                                                <td><span class="badge badge-pill badge-secondary"><?php echo $stats_annee_courante['classes']; ?></span></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-chalkboard-teacher ml-2 text-info"></i> تقييمات المدرّسين</td>
                                                <td><span class="badge badge-pill badge-info"><?php echo isset($eval_type['total_profs']) ? $eval_type['total_profs'] : 0; ?></span></td>
                                                <td><span class="badge badge-pill badge-info"><?php echo $stats_mois_courant['profs']; ?></span></td>
                                                <td><span class="badge badge-pill badge-secondary"><?php echo $stats_annee_courante['profs']; ?></span></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-graduation-cap ml-2 text-warning"></i> دورات التكوين</td>
                                                <td><span class="badge badge-pill badge-warning"><?php echo isset($stats['total_formations']) ? $stats['total_formations'] : 0; ?></span></td>
                                                <td><span class="badge badge-pill badge-info"><?php echo $stats_mois_courant['formations']; ?></span></td>
                                                <td><span class="badge badge-pill badge-secondary"><?php echo $stats_annee_courante['formations']; ?></span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Page en construction -->
                <div class="construction-container">
                    <div class="construction-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h2 class="construction-title">الصفحة قيد الإنشاء</h2>
                    <p class="construction-text">نعمل حاليا على تطوير هذه الميزة. ستكون متاحة قريبا.</p>
                    <a href="inspecteur.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-home ml-2"></i> العودة إلى لوحة المتابعة
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
    <script>
        // Afficher la date et l'heure en arabe tunisien
        function updateDateTime() {
            const now = new Date();
            
            // Noms des mois en arabe tunisien
            const moisTunisien = [
                "جانفي", "فيفري", "مارس", "أفريل", "ماي", "جوان", 
                "جويلية", "أوت", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"
            ];
            
            // Noms des jours en arabe
            const joursTunisien = [
                "الأحد", "الإثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت"
            ];
            
            const jour = joursTunisien[now.getDay()];
            const date = now.getDate();
            const mois = moisTunisien[now.getMonth()];
            const annee = now.getFullYear();
            const heure = now.getHours().toString().padStart(2, '0');
            const minute = now.getMinutes().toString().padStart(2, '0');
            
            document.getElementById('current-date-time').textContent = 
                `${jour} ${date} ${mois} ${annee} - ${heure}:${minute}`;
        }
        
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Toggle sidebar
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });
            
            // Animation des cartes statistiques
            $('.stats-cards .card').each(function(index) {
                $(this).css('--animation-order', index + 1);
            });
        });

        <?php if ($page == 'dashboard'): ?>
        // Graphique des rapports par mois
        const rapportsData = [<?php echo implode(', ', $rapports_par_mois); ?>];
        
        // Calculer la moyenne des rapports
        const moyenne = rapportsData.reduce((a, b) => a + b, 0) / 12;
        const moyenneData = Array(12).fill(moyenne);
        
        // Déterminer le mois actuel
        const currentMonth = new Date().getMonth(); // 0-11
        
        // Créer un tableau de couleurs avec le mois actuel en surbrillance
        const backgroundColors = rapportsData.map((_, index) => 
            index === currentMonth ? 'rgba(245, 158, 11, 0.8)' : 'rgba(79, 70, 229, 0.7)'
        );
        
        const borderColors = rapportsData.map((_, index) => 
            index === currentMonth ? 'rgba(245, 158, 11, 1)' : 'rgba(79, 70, 229, 1)'
        );
        
        const rapportsCtx = document.getElementById('rapportsChart').getContext('2d');
        const rapportsChart = new Chart(rapportsCtx, {
            type: 'bar',
            data: {
                labels: ['جانفي', 'فيفري', 'مارس', 'أفريل', 'ماي', 'جوان', 'جويلية', 'أوت', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                datasets: [
                    {
                        label: 'تقارير التفقّد',
                        data: rapportsData,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1,
                        borderRadius: 6,
                        hoverBackgroundColor: rapportsData.map((_, index) => 
                            index === currentMonth ? 'rgba(245, 158, 11, 0.9)' : 'rgba(79, 70, 229, 0.9)'
                        ),
                        barPercentage: 0.7,
                        categoryPercentage: 0.8
                    },
                    {
                        label: 'المعدل الشهري',
                        data: moyenneData,
                        type: 'line',
                        fill: false,
                        backgroundColor: 'rgba(129, 140, 248, 0.6)',
                        borderColor: 'rgba(129, 140, 248, 1)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointBackgroundColor: 'rgba(129, 140, 248, 1)',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            family: 'Tajawal',
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            family: 'Tajawal',
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.label === 'المعدل الشهري') {
                                    return `المعدل الشهري: ${context.raw.toFixed(1)}`;
                                }
                                return `عدد التقارير: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: {
                                family: 'Tajawal',
                                size: 12
                            },
                            color: 'rgba(71, 85, 105, 0.8)'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Tajawal',
                                size: 12
                            },
                            color: 'rgba(71, 85, 105, 0.8)'
                        },
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        <?php if ($page == 'statistiques'): ?>
        // Graphique des rapports par mois pour la page statistiques
        const statsRapportsCtx = document.getElementById('statsRapportsChart').getContext('2d');
        
        // Déterminer le mois actuel
        const currentMonthStats = new Date().getMonth(); // 0-11
        
        // Créer un tableau de couleurs avec le mois actuel en surbrillance
        const statsBackgroundColors = Array(12).fill('rgba(79, 70, 229, 0.7)');
        statsBackgroundColors[currentMonthStats] = 'rgba(245, 158, 11, 0.8)';
        
        const statsBorderColors = Array(12).fill('rgba(79, 70, 229, 1)');
        statsBorderColors[currentMonthStats] = 'rgba(245, 158, 11, 1)';
        
        const statsRapportsChart = new Chart(statsRapportsCtx, {
            type: 'bar',
            data: {
                labels: ['جانفي', 'فيفري', 'مارس', 'أفريل', 'ماي', 'جوان', 'جويلية', 'أوت', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                datasets: [{
                    label: 'عدد تقارير التفقّد',
                    data: [<?php echo implode(', ', $rapports_par_mois); ?>],
                    backgroundColor: statsBackgroundColors,
                    borderColor: statsBorderColors,
                    borderWidth: 1,
                    borderRadius: 8,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            family: 'Tajawal',
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            family: 'Tajawal',
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                return `عدد التقارير: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: {
                                family: 'Tajawal',
                                size: 12
                            },
                            color: 'rgba(71, 85, 105, 0.8)'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Tajawal',
                                size: 12
                            },
                            color: 'rgba(71, 85, 105, 0.8)'
                        },
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });

        // Graphique des évaluations par type pour la page statistiques
        const statsEvaluationsCtx = document.getElementById('statsEvaluationsChart').getContext('2d');
        const statsEvaluationsChart = new Chart(statsEvaluationsCtx, {
            type: 'doughnut',
            data: {
                labels: ['تقييم الأقسام', 'تقييم المدرّسين'],
                datasets: [{
                    label: 'التقييمات',
                    data: [<?php echo isset($eval_type['total_classes']) ? $eval_type['total_classes'] : 0; ?>, <?php echo isset($eval_type['total_profs']) ? $eval_type['total_profs'] : 0; ?>],
                    backgroundColor: [
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(16, 185, 129, 0.8)'
                    ],
                    borderColor: [
                        'rgba(14, 165, 233, 1)',
                        'rgba(16, 185, 129, 1)'
                    ],
                    borderWidth: 1,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            family: 'Tajawal',
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            family: 'Tajawal',
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>

