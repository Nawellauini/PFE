<?php

session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];

// Récupérer les classes du professeur
$query_classes = "SELECT c.id_classe, c.nom_classe 
                  FROM classes c 
                  JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
                  WHERE pc.id_professeur = ?
                  ORDER BY c.nom_classe";
$stmt_classes = $conn->prepare($query_classes);
$stmt_classes->bind_param("i", $id_professeur);
$stmt_classes->execute();
$result_classes = $stmt_classes->get_result();

// Récupérer les matières du professeur
$query_matieres = "SELECT DISTINCT m.matiere_id, m.nom 
                   FROM matieres m 
                   JOIN professeurs_classes pc ON m.classe_id = pc.id_classe
                   WHERE pc.id_professeur = ?
                   ORDER BY m.nom";
$stmt_matieres = $conn->prepare($query_matieres);
$stmt_matieres->bind_param("i", $id_professeur);
$stmt_matieres->execute();
$result_matieres = $stmt_matieres->get_result();

// Filtres
$classe_id = isset($_GET['classe_id']) ? intval($_GET['classe_id']) : 0;
$matiere_id = isset($_GET['matiere_id']) ? intval($_GET['matiere_id']) : 0;
$trimestre = isset($_GET['trimestre']) ? intval($_GET['trimestre']) : 0;

// Message de statut
$status_message = '';
$status_type = '';

if (isset($_GET['message'])) {
    $status_message = $_GET['message'];
    $status_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة النتائج | فضاء المعلم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #4f70ff;
            --primary-foreground: #ffffff;
            
            --secondary: #06d6a0;
            --secondary-dark: #05c091;
            --secondary-light: #0deeb1;
            --secondary-foreground: #ffffff;
            
            --accent: #f72585;
            --accent-dark: #e91c7b;
            --accent-light: #ff3d97;
            --accent-foreground: #ffffff;
            
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --info: #118ab2;
            
            --background: #f8f9fa;
            --foreground: #1d3557;
            
            --card: #ffffff;
            --card-foreground: #1d3557;
            
            --border: #e2e8f0;
            --input: #e2e8f0;
            
            --muted: #f1f5f9;
            --muted-foreground: #64748b;
            
            --destructive: #ef4444;
            --destructive-foreground: #ffffff;
            
            --ring: rgba(67, 97, 238, 0.3);
            
            --radius: 0.75rem;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--background);
            color: var(--foreground);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image: linear-gradient(135deg, rgba(67, 97, 238, 0.05) 0%, rgba(6, 214, 160, 0.05) 100%);
        }

        /* Header */
        .header {
            background-color: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow);
        }

        .container {
            width: 90%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo i {
            font-size: 1.75rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-list {
            display: flex;
            list-style: none;
            gap: 0.5rem;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            color: var(--foreground);
            text-decoration: none;
            font-weight: 500;
            border-radius: var(--radius);
            transition: all 0.3s ease;
        }

        .nav-item a:hover {
            background-color: var(--muted);
            transform: translateY(-2px);
        }

        .nav-item a.active {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            color: var(--primary-foreground);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }

        .user-menu {
            position: relative;
        }

        .user-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: none;
            border: none;
            cursor: pointer;
            border-radius: var(--radius);
            transition: all 0.3s ease;
        }

        .user-button:hover {
            background-color: var(--muted);
        }

        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
            transition: all 0.3s ease;
        }

        .user-button:hover .user-avatar {
            transform: scale(1.1);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.3);
        }

        .user-name {
            font-weight: 600;
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 220px;
            background-color: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            padding: 0.5rem;
            z-index: 100;
            display: none;
            transform-origin: top center;
        }

        .user-dropdown.show {
            display: block;
            animation: dropdownFadeIn 0.3s ease forwards;
        }

        @keyframes dropdownFadeIn {
            from { opacity: 0; transform: translateY(-10px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            color: var(--foreground);
            text-decoration: none;
            border-radius: var(--radius);
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }

        .dropdown-item:hover {
            background-color: var(--muted);
            transform: translateX(-5px);
        }

        .dropdown-item.logout {
            color: var(--destructive);
            margin-top: 0.25rem;
            border-top: 1px solid var(--border);
            padding-top: 0.75rem;
        }

        .dropdown-item.logout:hover {
            background-color: rgba(239, 68, 68, 0.1);
        }

        .dropdown-item i {
            width: 1.25rem;
            text-align: center;
        }

        /* Main Content */
        .main {
            flex: 1;
            padding: 2rem 0;
        }

        .page-header {
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 2px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary);
            font-size: 1.75rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-description {
            color: var(--muted-foreground);
            font-size: 1.125rem;
        }

        /* Card */
        .card {
            background-color: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid rgba(226, 232, 240, 0.7);
            position: relative;
            margin-bottom: 1.5rem;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-5px);
        }

        .card:hover::before {
            opacity: 1;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: rgba(241, 245, 249, 0.5);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--foreground);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: var(--primary);
            font-size: 1.25rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            background-color: rgba(241, 245, 249, 0.5);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-select {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--foreground);
            background-color: var(--card);
            background-clip: padding-box;
            border: 1px solid var(--input);
            border-radius: var(--radius);
            transition: all 0.15s ease-in-out;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 1rem center;
            background-size: 16px 12px;
            padding-left: 2.5rem;
        }

        .form-select:focus {
            border-color: var(--primary-light);
            outline: 0;
            box-shadow: 0 0 0 0.25rem var(--ring);
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--foreground);
            background-color: var(--card);
            background-clip: padding-box;
            border: 1px solid var(--input);
            border-radius: var(--radius);
            transition: all 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            outline: 0;
            box-shadow: 0 0 0 0.25rem var(--ring);
        }

        /* Filter Form */
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-form .form-group {
            margin-bottom: 0;
        }

        .filter-form .btn {
            align-self: flex-end;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
        }

        .table th,
        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .table th {
            background: linear-gradient(to right, rgba(67, 97, 238, 0.1), rgba(67, 97, 238, 0.05));
            font-weight: 600;
            color: var(--foreground);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .table tbody tr:nth-child(even) {
            background-color: rgba(241, 245, 249, 0.5);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 9999px;
        }

        .badge-primary {
            color: var(--primary-foreground);
            background-color: var(--primary);
        }

        .badge-secondary {
            color: var(--secondary-foreground);
            background-color: var(--secondary);
        }

        .badge-accent {
            color: var(--accent-foreground);
            background-color: var(--accent);
        }

        .badge-success {
            color: white;
            background-color: var(--success);
        }

        .badge-warning {
            color: #1d3557;
            background-color: var(--warning);
        }

        .badge-danger {
            color: white;
            background-color: var(--danger);
        }

        .badge-info {
            color: white;
            background-color: var(--info);
        }

        /* Note Badge */
        .note-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.5rem;
            height: 2.5rem;
            padding: 0 0.75rem;
            font-size: 1rem;
            font-weight: 700;
            border-radius: var(--radius);
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 2px 5px rgba(67, 97, 238, 0.3);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.5;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            border: 1px solid transparent;
            border-radius: var(--radius);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn:active::after {
            animation: ripple 0.6s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }

        .btn-primary {
            color: var(--primary-foreground);
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            border-color: var(--primary);
            box-shadow: 0 4px 6px -1px rgba(67, 97, 238, 0.2), 0 2px 4px -1px rgba(67, 97, 238, 0.1);
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3), 0 4px 6px -2px rgba(67, 97, 238, 0.15);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            color: var(--secondary-foreground);
            background: linear-gradient(45deg, var(--secondary), var(--secondary-light));
            border-color: var(--secondary);
            box-shadow: 0 4px 6px -1px rgba(6, 214, 160, 0.2), 0 2px 4px -1px rgba(6, 214, 160, 0.1);
        }

        .btn-secondary:hover {
            background: linear-gradient(45deg, var(--secondary-dark), var(--secondary));
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(6, 214, 160, 0.3), 0 4px 6px -2px rgba(6, 214, 160, 0.15);
        }

        .btn-accent {
            color: var(--accent-foreground);
            background: linear-gradient(45deg, var(--accent), var(--accent-light));
            border-color: var(--accent);
            box-shadow: 0 4px 6px -1px rgba(247, 37, 133, 0.2), 0 2px 4px -1px rgba(247, 37, 133, 0.1);
        }

        .btn-accent:hover {
            background: linear-gradient(45deg, var(--accent-dark), var(--accent));
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(247, 37, 133, 0.3), 0 4px 6px -2px rgba(247, 37, 133, 0.15);
        }

        .btn-danger {
            color: white;
            background: linear-gradient(45deg, var(--danger), #ff5a5f);
            border-color: var(--danger);
            box-shadow: 0 4px 6px -1px rgba(239, 71, 111, 0.2), 0 2px 4px -1px rgba(239, 71, 111, 0.1);
        }

        .btn-danger:hover {
            background: linear-gradient(45deg, #e43f6f, var(--danger));
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(239, 71, 111, 0.3), 0 4px 6px -2px rgba(239, 71, 111, 0.15);
        }

        .btn-outline {
            color: var(--foreground);
            background-color: transparent;
            border-color: var(--border);
        }

        .btn-outline:hover {
            background-color: var(--muted);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .btn-icon {
            width: 2.5rem;
            height: 2.5rem;
            padding: 0;
            border-radius: 50%;
        }

        .btn-icon i {
            font-size: 1rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            text-align: center;
            background-color: rgba(241, 245, 249, 0.5);
            border-radius: var(--radius);
            border: 1px dashed var(--border);
        }

        .empty-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            color: var(--muted-foreground);
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.7;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--foreground);
            margin-bottom: 0.75rem;
        }

        .empty-description {
            color: var(--muted-foreground);
            max-width: 30rem;
            margin: 0 auto;
            font-size: 1.125rem;
        }

        /* Loading */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem;
        }

        .spinner {
            width: 3rem;
            height: 3rem;
            border: 0.25rem solid rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Modal */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(29, 53, 87, 0.7);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-dialog {
            width: 100%;
            max-width: 32rem;
            background-color: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            transform: translateY(-1rem) scale(0.95);
            transition: all 0.3s ease;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .modal-backdrop.show .modal-dialog {
            transform: translateY(0) scale(1);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(to right, rgba(67, 97, 238, 0.1), rgba(67, 97, 238, 0.05));
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--foreground);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-title i {
            color: var(--primary);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--muted-foreground);
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .modal-close:hover {
            color: var(--foreground);
            background-color: rgba(241, 245, 249, 0.8);
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--border);
            background: linear-gradient(to right, rgba(67, 97, 238, 0.05), rgba(67, 97, 238, 0.02));
        }

        /* Alert */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert-success {
            background-color: rgba(6, 214, 160, 0.1);
            border: 1px solid rgba(6, 214, 160, 0.3);
            color: var(--success);
        }

        .alert-danger {
            background-color: rgba(239, 71, 111, 0.1);
            border: 1px solid rgba(239, 71, 111, 0.3);
            color: var(--danger);
        }

        .alert-info {
            background-color: rgba(17, 138, 178, 0.1);
            border: 1px solid rgba(17, 138, 178, 0.3);
            color: var(--info);
        }

        .alert-warning {
            background-color: rgba(255, 209, 102, 0.1);
            border: 1px solid rgba(255, 209, 102, 0.3);
            color: #d4a400;
        }

        .alert-icon {
            font-size: 1.5rem;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .alert-message {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background-color: var(--card);
            border-top: 1px solid var(--border);
            padding: 2.5rem 0 1.5rem;
            margin-top: auto;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap;
            gap: 2.5rem;
        }

        .footer-section {
            flex: 1;
            min-width: 250px;
        }

        .footer-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--foreground);
            margin-bottom: 1.25rem;
            position: relative;
            padding-bottom: 0.75rem;
            display: inline-block;
        }

        .footer-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 1.5px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: var(--muted-foreground);
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0;
            position: relative;
        }

        .footer-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            transition: width 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
            transform: translateX(-5px);
        }

        .footer-links a:hover::after {
            width: 100%;
        }

        .footer-links i {
            color: var(--primary);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            border-top: 1px solid var(--border);
            color: var(--muted-foreground);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        .animate-slide-up {
            animation: slideUp 0.5s ease forwards;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-list {
                display: none;
            }
            
            .page-title {
                font-size: 1.75rem;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .page-title {
                font-size: 1.5rem;
            }

            .card-header, .card-body, .card-footer {
                padding: 1rem;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="navbar">
                <a href="index.php" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>فضاء المعلم</span>
                </a>
                
                <div class="nav-menu">
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="index.php">
                                <i class="fas fa-home"></i>
                                <span>الصفحة الرئيسية</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="classes.php">
                                <i class="fas fa-users"></i>
                                <span>الأقسام</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="dashboard_professeur.php">
                                <i class="fas fa-edit"></i>
                                <span>تسجيل الأعداد</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="liste_notes.php" class="active">
                                <i class="fas fa-clipboard-list"></i>
                                <span>قائمة النتائج</span>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="user-menu">
                        <button class="user-button" id="userMenuButton">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nom_professeur']) ?>&background=4361ee&color=fff" alt="صورة المستخدم" class="user-avatar">
                            <span class="user-name"><?= htmlspecialchars($_SESSION['nom_professeur']) ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <div class="user-dropdown" id="userDropdown">
                            <a href="profile_prof.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>الملف الشخصي</span>
                            </a>
                            <a href="logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>تسجيل الخروج</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <div class="page-header animate-fade-in">
                <h1 class="page-title">
                    <i class="fas fa-clipboard-list"></i>
                    <span>قائمة النتائج</span>
                </h1>
                <p class="page-description">عرض وإدارة نتائج التلاميذ</p>
            </div>
            
            <?php if (!empty($status_message)): ?>
                <div class="alert alert-<?= $status_type ?>">
                    <div class="alert-icon">
                        <i class="fas fa-<?= $status_type === 'success' ? 'check-circle' : ($status_type === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                    </div>
                    <div class="alert-content">
                        <div class="alert-title">
                            <?= $status_type === 'success' ? 'تمت العملية بنجاح!' : ($status_type === 'danger' ? 'حدث خطأ!' : 'معلومات') ?>
                        </div>
                        <div class="alert-message"><?= htmlspecialchars($status_message) ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card animate-slide-up">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-filter"></i>
                        <span>تصفية النتائج</span>
                    </h2>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="filter-form">
                        <div class="form-group">
                            <label for="classe_id" class="form-label">القسم:</label>
                            <select name="classe_id" id="classe_id" class="form-select" onchange="chargerMatieres()">
                                <option value="0">جميع الأقسام</option>
                                <?php while ($classe = $result_classes->fetch_assoc()): ?>
                                    <option value="<?= $classe['id_classe'] ?>" <?= $classe_id == $classe['id_classe'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($classe['nom_classe']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="matiere_id" class="form-label">المادة:</label>
                            <select name="matiere_id" id="matiere_id" class="form-select">
                                <option value="0">جميع المواد</option>
                                <?php while ($matiere = $result_matieres->fetch_assoc()): ?>
                                    <option value="<?= $matiere['matiere_id'] ?>" <?= $matiere_id == $matiere['matiere_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($matiere['nom']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="trimestre" class="form-label">الثلاثي:</label>
                            <select name="trimestre" id="trimestre" class="form-select">
                                <option value="0">جميع الثلاثيات</option>
                                <option value="1" <?= $trimestre == 1 ? 'selected' : '' ?>>الثلاثي الأول</option>
                                <option value="2" <?= $trimestre == 2 ? 'selected' : '' ?>>الثلاثي الثاني</option>
                                <option value="3" <?= $trimestre == 3 ? 'selected' : '' ?>>الثلاثي الثالث</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                <span>بحث</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card animate-slide-up" style="animation-delay: 0.1s;">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-clipboard-list"></i>
                        <span>قائمة النتائج</span>
                    </h2>
                    <a href="dashboard_professeur.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i>
                        <span>إضافة نتائج جديدة</span>
                    </a>
                </div>
                <div class="card-body">
                    <?php
                    // Construire la requête SQL en fonction des filtres
                    $query = "SELECT n.id, n.note, n.trimestre, e.id_eleve, e.nom as nom_eleve, e.prenom as prenom_eleve, 
                              c.id_classe, c.nom_classe, m.matiere_id, m.nom as nom_matiere
                              FROM notes n
                              JOIN eleves e ON n.id_eleve = e.id_eleve
                              JOIN classes c ON e.id_classe = c.id_classe
                              JOIN matieres m ON n.matiere_id = m.matiere_id
                              JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
                              WHERE pc.id_professeur = ?";
                    
                    $params = array($id_professeur);
                    $types = "i";
                    
                    if ($classe_id > 0) {
                        $query .= " AND c.id_classe = ?";
                        $params[] = $classe_id;
                        $types .= "i";
                    }
                    
                    if ($matiere_id > 0) {
                        $query .= " AND m.matiere_id = ?";
                        $params[] = $matiere_id;
                        $types .= "i";
                    }
                    
                    if ($trimestre > 0) {
                        $query .= " AND n.trimestre = ?";
                        $params[] = $trimestre;
                        $types .= "i";
                    }
                    
                    $query .= " ORDER BY c.nom_classe, m.nom, e.nom, e.prenom, n.trimestre";
                    
                    $stmt = $conn->prepare($query);
                    
                    // Lier les paramètres dynamiquement
                    if (!empty($params)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        echo '<div class="table-container">';
                        echo '<table class="table">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>#</th>';
                        echo '<th>التلميذ</th>';
                        echo '<th>القسم</th>';
                        echo '<th>المادة</th>';
                        echo '<th>الثلاثي</th>';
                        echo '<th>النتيجة</th>';
                        echo '<th>الإجراءات</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        $count = 0;
                        while ($row = $result->fetch_assoc()) {
                            $count++;
                            echo '<tr>';
                            echo '<td>' . $count . '</td>';
                            echo '<td>' . htmlspecialchars($row['nom_eleve'] . ' ' . $row['prenom_eleve']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['nom_classe']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['nom_matiere']) . '</td>';
                            echo '<td><span class="badge badge-primary">الثلاثي ' . $row['trimestre'] . '</span></td>';
                            echo '<td><div class="note-badge">' . number_format($row['note'], 1) . '</div></td>';
                            echo '<td>';
                            echo '<div class="action-buttons">';
                            echo '<button type="button" class="btn btn-primary btn-sm btn-icon view-note" data-id="' . $row['id'] . '" title="عرض التفاصيل">';
                            echo '<i class="fas fa-eye"></i>';
                            echo '</button>';
                            echo '<button type="button" class="btn btn-accent btn-sm btn-icon edit-note" data-id="' . $row['id'] . '" data-note="' . $row['note'] . '" data-eleve="' . htmlspecialchars($row['nom_eleve'] . ' ' . $row['prenom_eleve']) . '" data-matiere="' . htmlspecialchars($row['nom_matiere']) . '" data-trimestre="' . $row['trimestre'] . '" title="تعديل">';
                            echo '<i class="fas fa-edit"></i>';
                            echo '</button>';
                            echo '<button type="button" class="btn btn-danger btn-sm btn-icon delete-note" data-id="' . $row['id'] . '" data-eleve="' . htmlspecialchars($row['nom_eleve'] . ' ' . $row['prenom_eleve']) . '" data-matiere="' . htmlspecialchars($row['nom_matiere']) . '" title="حذف">';
                            echo '<i class="fas fa-trash-alt"></i>';
                            echo '</button>';
                            echo '</div>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    } else {
                        echo '<div class="empty-state">';
                        echo '<i class="fas fa-clipboard-list empty-icon"></i>';
                        echo '<h3 class="empty-title">لا توجد نتائج</h3>';
                        echo '<p class="empty-description">لم يتم العثور على أي نتائج تطابق معايير البحث.</p>';
                        echo '<a href="dashboard_professeur.php" class="btn btn-primary mt-3">';
                        echo '<i class="fas fa-plus"></i>';
                        echo '<span>إضافة نتائج جديدة</span>';
                        echo '</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title">فضاء المعلم</h3>
                    <p>منصة تعليمية متكاملة لإدارة العملية التعليمية وتسهيل التواصل بين المعلمين والتلامذة.</p>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li><a href="classes.php"><i class="fas fa-users"></i> الأقسام</a></li>
                        <li><a href="dashboard_professeur.php"><i class="fas fa-edit"></i> تسجيل الأعداد</a></li>
                        <li><a href="liste_notes.php"><i class="fas fa-clipboard-list"></i> قائمة النتائج</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">تواصل معنا</h3>
                    <ul class="footer-links">
                        <li><a href="mailto:support@teacher-portal.com"><i class="fas fa-envelope"></i> support@teacher-portal.com</a></li>
                        <li><a href="tel:+123456789"><i class="fas fa-phone"></i> +123 456 789</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> فضاء المعلم. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- Modal de visualisation -->
    <div class="modal-backdrop" id="viewModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-clipboard-check"></i>
                    <span>تفاصيل النتيجة</span>
                </h3>
                <button class="modal-close" id="closeViewModal">&times;</button>
            </div>
            <div class="modal-body" id="viewModalContent">
                <!-- Le contenu sera chargé dynamiquement -->
                <div class="loading">
                    <div class="spinner"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="closeViewBtn">
                    <i class="fas fa-times"></i>
                    <span>إغلاق</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de modification -->
    <div class="modal-backdrop" id="editModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-edit"></i>
                    <span>تعديل النتيجة</span>
                </h3>
                <button class="modal-close" id="closeEditModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm" method="POST" action="modifier_note.php">
                    <input type="hidden" name="note_id" id="edit_note_id">
                    
                    <div class="form-group">
                        <label for="edit_eleve" class="form-label">التلميذ:</label>
                        <input type="text" id="edit_eleve" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_matiere" class="form-label">المادة:</label>
                        <input type="text" id="edit_matiere" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_trimestre" class="form-label">الثلاثي:</label>
                        <input type="text" id="edit_trimestre" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_note" class="form-label">النتيجة:</label>
                        <input type="number" name="note" id="edit_note" class="form-control" min="0" max="20" step="0.5" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelEdit">
                    <i class="fas fa-times"></i>
                    <span>إلغاء</span>
                </button>
                <button class="btn btn-primary" id="saveEdit">
                    <i class="fas fa-save"></i>
                    <span>حفظ التغييرات</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de suppression -->
    <div class="modal-backdrop" id="deleteModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-trash-alt"></i>
                    <span>حذف النتيجة</span>
                </h3>
                <button class="modal-close" id="closeDeleteModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="deleteForm" method="POST" action="supprimer_note.php">
                    <input type="hidden" name="note_id" id="delete_note_id">
                    
                    <div class="alert alert-danger">
                        <div class="alert-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <div class="alert-title">تأكيد الحذف</div>
                            <div class="alert-message">
                                هل أنت متأكد من حذف نتيجة <strong id="delete_eleve_name"></strong> في مادة <strong id="delete_matiere_name"></strong>؟
                                <br>
                                <strong>هذا الإجراء لا يمكن التراجع عنه.</strong>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelDelete">
                    <i class="fas fa-times"></i>
                    <span>إلغاء</span>
                </button>
                <button class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash-alt"></i>
                    <span>تأكيد الحذف</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // User Dropdown Toggle
            $("#userMenuButton").click(function(e) {
                e.stopPropagation();
                $("#userDropdown").toggleClass("show");
            });

            // Close dropdown when clicking outside
            $(document).click(function() {
                $("#userDropdown").removeClass("show");
            });

            // Visualiser une note
            $(document).on("click", ".view-note", function() {
                const noteId = $(this).data("id");
                $("#viewModalContent").html('<div class="loading"><div class="spinner"></div></div>');
                $("#viewModal").addClass("show");
            
                // Charger les détails via AJAX
                $.ajax({
                    url: "voir_note.php",
                    type: "GET",
                    data: { id: noteId },
                    success: function(response) {
                        $("#viewModalContent").html(response);
                    },
                    error: function() {
                        $("#viewModalContent").html(`
                            <div class="alert alert-danger">
                                <div class="alert-icon">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="alert-content">
                                    <div class="alert-title">خطأ</div>
                                    <div class="alert-message">حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.</div>
                                </div>
                            </div>
                        `);
                    }
                });
            });

            // Fermer le modal de visualisation
            $(document).on("click", "#closeViewModal, #closeViewBtn", function() {
                $("#viewModal").removeClass("show");
            });

            // Modifier une note
            $(document).on("click", ".edit-note", function() {
                const noteId = $(this).data("id");
                const note = $(this).data("note");
                const eleve = $(this).data("eleve");
                const matiere = $(this).data("matiere");
                const trimestre = $(this).data("trimestre");
            
                $("#edit_note_id").val(noteId);
                $("#edit_note").val(note);
                $("#edit_eleve").val(eleve);
                $("#edit_matiere").val(matiere);
                $("#edit_trimestre").val("الثلاثي " + trimestre);
            
                $("#editModal").addClass("show");
            });

            // Fermer le modal de modification
            $(document).on("click", "#closeEditModal, #cancelEdit", function() {
                $("#editModal").removeClass("show");
            });

            // Enregistrer les modifications
            $(document).on("click", "#saveEdit", function() {
                const noteValue = $("#edit_note").val();
            
                if (noteValue === "" || isNaN(parseFloat(noteValue)) || parseFloat(noteValue) < 0 || parseFloat(noteValue) > 20) {
                    Swal.fire({
                        title: "خطأ!",
                        text: "يرجى إدخال نتيجة صالحة بين 0 و 20.",
                        icon: "error",
                        confirmButtonText: "حسنًا"
                    });
                    return;
                }
            
                $("#editForm").submit();
            });

            // Supprimer une note
            $(document).on("click", ".delete-note", function() {
                const noteId = $(this).data("id");
                
                const eleve = $(this).data("eleve");
                const matiere = $(this).data("matiere");
            
                $("#delete_note_id").val(noteId);
                $("#delete_eleve_name").text(eleve);
                $("#delete_matiere_name").text(matiere);
            
                $("#deleteModal").addClass("show");
            });

            // Fermer le modal de suppression
            $(document).on("click", "#closeDeleteModal, #cancelDelete", function() {
                $("#deleteModal").removeClass("show");
            });

            // Confirmer la suppression
            $(document).on("click", "#confirmDelete", function() {
                $("#deleteForm").submit();
            });

            // Fermer les modals si on clique en dehors
            $(".modal-backdrop").click(function(e) {
                if ($(e.target).hasClass("modal-backdrop")) {
                    $(this).removeClass("show");
                }
            });

            // Fermer les alertes après 5 secondes
            setTimeout(function() {
                $(".alert").fadeOut(500);
            }, 5000);

            // Si une classe est déjà sélectionnée, charger ses matières
            const classeId = $("#classe_id").val();
            if (classeId > 0) {
                chargerMatieres();
            }
        });

        // Fonction pour charger les matières en fonction de la classe sélectionnée
        function chargerMatieres() {
            const classeId = $("#classe_id").val();
            
            // Si "Toutes les classes" est sélectionné, charger toutes les matières
            if (classeId == 0) {
                $.ajax({
                    url: "get_all_matieres.php",
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        // Vider et reconstruire la liste déroulante des matières
                        const matiereSelect = $("#matiere_id");
                        const selectedMatiere = matiereSelect.val(); // Sauvegarder la sélection actuelle
                        
                        matiereSelect.empty();
                        matiereSelect.append('<option value="0">جميع المواد</option>');
                        
                        $.each(data, function(index, matiere) {
                            matiereSelect.append('<option value="' + matiere.matiere_id + '">' + matiere.nom + '</option>');
                        });
                        
                        // Restaurer la sélection si possible
                        if (selectedMatiere) {
                            matiereSelect.val(selectedMatiere);
                        }
                    },
                    error: function() {
                        console.error("Erreur lors du chargement des matières");
                    }
                });
            } else {
                // Sinon, charger les matières de la classe sélectionnée
                $.ajax({
                    url: "get_matieres_by_classe.php",
                    type: "GET",
                    data: { classe_id: classeId },
                    dataType: "json",
                    success: function(data) {
                        // Vider et reconstruire la liste déroulante des matières
                        const matiereSelect = $("#matiere_id");
                        matiereSelect.empty();
                        matiereSelect.append('<option value="0">جميع المواد</option>');
                        
                        $.each(data, function(index, matiere) {
                            matiereSelect.append('<option value="' + matiere.matiere_id + '">' + matiere.nom + '</option>');
                        });
                    },
                    error: function() {
                        console.error("Erreur lors du chargement des matières pour la classe " + classeId);
                    }
                });
            }
        }
    </script>
</body>
</html>
