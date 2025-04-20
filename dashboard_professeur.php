<?php

session_start();
include 'db_config.php';

if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المعلم | إدخال الأعداد</title>
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
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
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

        /* Grid Layout */
        .grid {
            display: grid;
            gap: 1.5rem;
        }

        .grid-cols-1 {
            grid-template-columns: 1fr;
        }

        .grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .grid-cols-3 {
            grid-template-columns: repeat(3, 1fr);
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

        /* Selection Grid */
        .selection-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .selection-item {
            position: relative;
            background-color: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .selection-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(247, 37, 133, 0.05));
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .selection-item:hover {
            border-color: var(--primary-light);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(67, 97, 238, 0.1), 0 10px 10px -5px rgba(67, 97, 238, 0.04);
        }

        .selection-item:hover::before {
            opacity: 1;
        }

        .selection-item.selected {
            background-color: rgba(67, 97, 238, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }

        .selection-item.selected::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 2.5rem 2.5rem 0;
            border-color: transparent var(--primary) transparent transparent;
        }

        .selection-item.selected::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            color: white;
            font-size: 0.75rem;
        }

        .selection-item-content {
            font-weight: 600;
            font-size: 1.125rem;
            position: relative;
            z-index: 1;
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

        .table-input {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid var(--input);
            border-radius: var(--radius);
            text-align: center;
            font-family: 'Cairo', sans-serif;
            transition: all 0.2s ease;
        }

        .table-input:focus {
            border-color: var(--primary-light);
            outline: 0;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .table-input.is-invalid {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(239, 71, 111, 0.2);
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

        .btn-link {
            color: var(--primary);
            background-color: transparent;
            border-color: transparent;
            text-decoration: none;
            padding: 0.5rem;
        }

        .btn-link:hover {
            text-decoration: underline;
            transform: translateY(-2px);
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

        /* Notes List */
        .notes-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notes-list li {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .notes-list li:last-child {
            border-bottom: none;
        }

        .notes-list li:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .note-value {
            font-weight: 700;
            color: var(--primary);
            background-color: rgba(67, 97, 238, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
        }

        /* Action Links */
        .action-links {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            margin-top: 2.5rem;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background-color: var(--card);
            color: var(--foreground);
            text-decoration: none;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .action-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(67, 97, 238, 0.1), rgba(247, 37, 133, 0.1));
            z-index: -1;
            transition: opacity 0.3s ease;
            opacity: 0;
        }

        .action-link:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: rgba(67, 97, 238, 0.3);
        }

        .action-link:hover::before {
            opacity: 1;
        }

        .action-link i {
            color: var(--primary);
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }

        .action-link:hover i {
            transform: scale(1.2);
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
        @media (max-width: 1024px) {
            .grid-cols-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .grid-cols-2, .grid-cols-3 {
                grid-template-columns: 1fr;
            }

            .nav-list {
                display: none;
            }

            .selection-container {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
            
            .page-title {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 640px) {
            .page-title {
                font-size: 1.5rem;
            }

            .card-header, .card-body, .card-footer {
                padding: 1rem;
            }

            .action-links {
                flex-direction: column;
            }

            .action-link {
                width: 100%;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 1.5rem;
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
                            <a href="dashboard_professeur.php" class="active">
                                <i class="fas fa-edit"></i>
                                <span>تسجيل الأعداد</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="selection_classe.php">
                                <i class="fas fa-search"></i>
                                <span>مراجعة الأعداد</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="historique_prof.php">
                                <i class="fas fa-history"></i>
                                <span>الأرشيف</span>
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
                            <a href="profile.php" class="dropdown-item">
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
                    <i class="fas fa-edit"></i>
                    <span>تسجيل الأعداد</span>
                </h1>
                <p class="page-description">إختار القسم والمادة  لإدخال أعداد  التلامذة</p>
            </div>
            
            <div class="grid grid-cols-1 animate-slide-up">
                <!-- Classes Selection -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-users"></i>
                            <span>اختيار القسم</span>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="selection-container" id="classes">
                            <?php
                            $query = "SELECT c.id_classe, c.nom_classe 
                                      FROM classes c 
                                      JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
                                      WHERE pc.id_professeur = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $id_professeur);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<div class='selection-item classe-card' data-id='" . $row['id_classe'] . "'><div class='selection-item-content'>" . $row['nom_classe'] . "</div></div>";
                                }
                            } else {
                                echo "<div class='empty-state'>
                                        <i class='fas fa-users-slash empty-icon'></i>
                                        <h3 class='empty-title'>لا توجد أقسام</h3>
                                       <p class='empty-description'>لم يتم تعيين أي أقسام لك حتى الآن</p>
                                      </div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Matières Selection -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-book"></i>
                            <span>اختيار المادة</span>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div id="matieres">
                            <div class="empty-state">
                                <i class="fas fa-book empty-icon"></i>
                                <h3 class="empty-title">اختر القسم أولاً</h3>
                                <p class="empty-description">يرجى اختيار القسم لعرض المواد المتاحة</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notes Form -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-clipboard-list"></i>
                            <span>تسجيل الأعداد</span>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div id="eleves_notes">
                            <div class="empty-state">
                                <i class="fas fa-clipboard-check empty-icon"></i>
                                <h3 class="empty-title">اختر القسم والمادة</h3>
                                <p class="page-description">إختار القسم والمادة  لإدخال أعداد  التلامذة</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Links -->
            <div class="action-links animate-slide-up" style="animation-delay: 0.2s;">
                <a href="index.php" class="action-link">
                    <i class="fas fa-home"></i>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
                <a href="selection_classe.php" class="action-link">
                    <i class="fas fa-search"></i>
                    <span>عرض النتائج على طريقة الشجرة</span>
                </a>
                <a href="calendar_events.php" class="action-link">
                    <i class="fas fa-calendar-plus"></i>
                    <span>إضافة حدث للتقويم</span>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                <h3 class="footer-title">لوحة المعلم</h3>
                <p>منصة تعليمية متكاملة لإدارة العملية التعليمية وتسهيل التواصل بين المعلمين والتلامذة.</p>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li><a href="classes.php"><i class="fas fa-users"></i> الأقسام</a></li>
                        <li><a href="dashboard_professeur.php"><i class="fas fa-edit"></i> تسجيل الأعداد</a></li>
                        <li><a href="selection_classe.php"><i class="fas fa-search"></i> عرض النتائج</a></li>
                        <li><a href="historique_prof.php"><i class="fas fa-history"></i> الأرشيف</a></li>
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
            <p>&copy; <?= date('Y') ?> منصة المدرس. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- Modal for Notes Verification -->
    <div class="modal-backdrop" id="verificationModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-clipboard-check"></i>
                    <span>تأكيد النتائج</span>
                </h3>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <div class="modal-body" id="verificationContent">
                <!-- Les notes vérifiées seront affichées ici -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="saveNotes">
                    <i class="fas fa-save"></i>
                    <span>حفظ النتائج</span>
                </button>
                <button class="btn btn-outline" id="cancelSave">
                    <i class="fas fa-times"></i>
                    <span>إلغاء</span>
                </button>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function () {
        let selectedClasse = null;
        let selectedMatiere = null;

        // User Dropdown Toggle
        $("#userMenuButton").click(function(e) {
            e.stopPropagation();
            $("#userDropdown").toggleClass("show");
        });

        // Close dropdown when clicking outside
        $(document).click(function() {
            $("#userDropdown").removeClass("show");
        });

        // Sélection de classe
        $(".classe-card").click(function () {
            $(".classe-card").removeClass("selected");
            $(this).addClass("selected");

            selectedClasse = $(this).attr("data-id");
            $("#matieres").html('<div class="loading"><div class="spinner"></div></div>');

            if (selectedClasse) {
                $.ajax({
                    type: "POST",
                    url: "get_matieres.php",
                    data: { classe_id: selectedClasse },
                    success: function (response) {
                        if (response.trim() === "Aucune matière trouvée pour cette classe." || response.trim() === "ID de classe non fourni.") {
                            $("#matieres").html(`
                                <div class="empty-state">
                                    <i class="fas fa-book-open empty-icon"></i>
                                    <h3 class="empty-title">لا توجد مواد</h3>
                                    <p class="empty-description">${response}</p>
                                </div>
                            `);
                        } else {
                            $("#matieres").html(`<div class="selection-container">${response}</div>`);
                            
                            // Attacher les événements aux nouvelles cartes de matières
                            $(".matiere-card").click(function () {
                                $(".matiere-card").removeClass("selected");
                                $(this).addClass("selected");

                                selectedMatiere = $(this).attr("data-id");
                                $("#eleves_notes").html('<div class="loading"><div class="spinner"></div></div>');

                                if (selectedMatiere) {
                                    $.ajax({
                                        type: "POST",
                                        url: "get_eleves.php",
                                        data: { classe_id: selectedClasse, matiere_id: selectedMatiere },
                                        success: function (response) {
                                            $("#eleves_notes").html(response);
                                            
                                            // Attacher les événements au formulaire de notes
                                            $("#btn_verification").click(function() {
                                                verifyNotes();
                                            });
                                        },
                                        error: function() {
                                            $("#eleves_notes").html(`
                                                <div class="empty-state">
                                                    <i class="fas fa-exclamation-triangle empty-icon"></i>
                                                    <h3 class="empty-title">خطأ</h3>
                                                    <p class="empty-description">حدث خطأ أثناء تحميل قائمة الطلاب</p>
                                                </div>
                                            `);
                                        }
                                    });
                                }
                            });
                        }
                    },
                    error: function() {
                        $("#matieres").html(`
                            <div class="empty-state">
                                <i class="fas fa-exclamation-triangle empty-icon"></i>
                                <h3 class="empty-title">خطأ</h3>
                                <p class="empty-description">حدث خطأ أثناء تحميل المواد</p>
                            </div>
                        `);
                    }
                });
            }
        });

        // Fonction pour vérifier les notes
        function verifyNotes() {
            // Vérifier si un trimestre est sélectionné
            const trimestre = $("#trimestre").val();
            if (!trimestre) {
                Swal.fire({
                    title: "تنبيه!",
                    text: "يرجى اختيار الثلاثي أولاً",
                    icon: "warning",
                    confirmButtonText: "حسنًا"
                });
                return;
            }
            
            const notes = {};
            let hasNotes = false;
            let isValid = true;
            
            // Collecter toutes les notes
            $('input[name^="notes"]').each(function() {
                const eleveId = $(this).attr('name').match(/\[(\d+)\]/)[1];
                const noteValue = $(this).val().trim();
                
                if (noteValue !== '') {
                    const noteNum = parseFloat(noteValue.replace(',', '.'));
                    
                    if (isNaN(noteNum) || noteNum < 0 || noteNum > 20) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        notes[eleveId] = noteNum;
                        hasNotes = true;
                    }
                }
            });
            
            if (!isValid) {
                Swal.fire({
                    title: "خطأ!",
                    text: "يرجى التحقق من النتائج. يجب أن تكون بين 0 و 20.",
                    icon: "error",
                    confirmButtonText: "حسنًا"
                });
                return;
            }
            
            if (!hasNotes) {
                Swal.fire({
                    title: "تنبيه!",
                    text: "لم يتم إدخال أي نتائج بعد!",
                    icon: "warning",
                    confirmButtonText: "حسنًا"
                });
                return;
            }
            
            // Afficher les notes dans la modal
            let content = "<ul class='notes-list'>";
            
            $('input[name^="notes"]').each(function() {
                const eleveId = $(this).attr('name').match(/\[(\d+)\]/)[1];
                const noteValue = $(this).val().trim();
                const eleveName = $(this).closest('tr').find('td:nth-child(2)').text();
                
                if (noteValue !== '') {
                    content += `<li><strong>${eleveName}</strong>: <span class="note-value">${noteValue}</span></li>`;
                }
            });
            
            content += "</ul>";
            
            $("#verificationContent").html(content);
            $("#verificationModal").addClass("show");
        }

        // Enregistrer les notes
        $("#saveNotes").click(function() {
            // Afficher un indicateur de chargement
            $(this).html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');
            $(this).prop('disabled', true);
            
            const formData = $("#form_notes").serialize();
            
            $.ajax({
                type: "POST",
                url: "save_notes.php",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: "تم بنجاح!",
                            text: response.message || "تم حفظ النتائج بنجاح!",
                            icon: "success",
                            confirmButtonText: "حسنًا"
                        });
                        $("#verificationModal").removeClass("show");
                    } else {
                        Swal.fire({
                            title: "خطأ!",
                            text: response.message || "حدث خطأ أثناء حفظ النتائج!",
                            icon: "error",
                            confirmButtonText: "حسنًا"
                        });
                    }
                    
                    // Réinitialiser le bouton
                    $("#saveNotes").html('<i class="fas fa-save"></i> <span>حفظ النتائج</span>');
                    $("#saveNotes").prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error("Erreur AJAX:", xhr.responseText);
                    Swal.fire({
                        title: "خطأ!",
                        text: "حدث خطأ أثناء الاتصال بالخادم!",
                        icon: "error",
                        confirmButtonText: "حسنًا"
                    });
                    
                    // Réinitialiser le bouton
                    $("#saveNotes").html('<i class="fas fa-save"></i> <span>حفظ النتائج</span>');
                    $("#saveNotes").prop('disabled', false);
                }
            });
        });
        
        // Fermer la modal
        $("#closeModal, #cancelSave").click(function() {
            $("#verificationModal").removeClass("show");
        });
    });
    </script>
</body>
</html>