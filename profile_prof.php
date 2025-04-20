<?php

session_start();
require_once 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];

// Récupérer les informations du professeur
$query = "SELECT p.*, m.nom as nom_matiere 
          FROM professeurs p 
          LEFT JOIN matieres m ON p.matiere_id = m.matiere_id 
          WHERE p.id_professeur = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Erreur de préparation de la requête: " . $conn->error);
}

$stmt->bind_param("i", $id_professeur);
$stmt->execute();
$result = $stmt->get_result();
$professeur = $result->fetch_assoc();

// Récupérer les classes du professeur
$query_classes = "SELECT DISTINCT c.id_classe, c.nom_classe, c.annee, m.nom as matiere
                 FROM classes c
                 JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
                 JOIN matieres m ON c.id_classe = m.classe_id
                 WHERE pc.id_professeur = ?
                 ORDER BY c.annee DESC, c.nom_classe";

$stmt_classes = $conn->prepare($query_classes);
if (!$stmt_classes) {
    die("Erreur de préparation de la requête: " . $conn->error);
}

$stmt_classes->bind_param("i", $id_professeur);
$stmt_classes->execute();
$result_classes = $stmt_classes->get_result();
$classes = $result_classes->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي | منصة المدرس</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #5c6bc0;
            --success-color: #43a047;
            --warning-color: #fb8c00;
            --text-color: #1a237e;
            --text-light: #5c6bc0;
            --bg-color: #f5f5f5;
            --card-bg: #ffffff;
            --border-color: #e8eaf6;
            --shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', 'Amiri', serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 0;
            flex: 1;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
        }

        .profile-title {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
            font-weight: 700;
        }

        .profile-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        .profile-card {
            background-color: var(--card-bg);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 3rem;
            margin-bottom: 3rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to left, var(--primary-color), var(--secondary-color));
        }

        .profile-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
            margin-bottom: 3rem;
        }

        .info-group {
            background-color: var(--bg-color);
            padding: 2rem;
            border-radius: 15px;
            transition: var(--transition);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .info-group::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
        }

        .info-group:hover {
            transform: translateX(-10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .info-label {
            color: var(--text-light);
            font-size: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .info-label i {
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        .info-value {
            font-weight: 600;
            color: var(--text-color);
            font-size: 1.4rem;
            line-height: 1.4;
        }

        .classes-section {
            margin-top: 4rem;
        }

        .section-title {
            font-size: 2.2rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section-title i {
            color: var(--accent-color);
            font-size: 2rem;
        }

        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .class-card {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .class-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to left, var(--primary-color), var(--secondary-color));
        }

        .class-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .class-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .class-details {
            color: var(--text-light);
            font-size: 1.1rem;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .class-details span {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .class-details i {
            color: var(--secondary-color);
            font-size: 1.2rem;
            width: 25px;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .classes-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-card {
                padding: 2rem;
            }

            .profile-title {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <h1 class="profile-title">الملف الشخصي</h1>
        </div>

        <div class="profile-card">
            <div class="info-grid">
                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-user-graduate"></i>
                        الاسم الكامل
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($professeur['nom'] . ' ' . $professeur['prenom']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-envelope"></i>
                        البريد الإلكتروني
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($professeur['email']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-book-reader"></i>
                        المادة
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($professeur['nom_matiere'] ?? 'غير محدد'); ?>
                    </div>
                </div>
            </div>

            <div class="classes-section">
                <h2 class="section-title">
                    <i class="fas fa-chalkboard-teacher"></i>
                    الأقسام المسندة
                </h2>

                <div class="classes-grid">
                    <?php foreach ($classes as $class): ?>
                        <div class="class-card">
                            <div class="class-name">
                                <?php echo htmlspecialchars($class['nom_classe']); ?>
                            </div>
                            <div class="class-details">
                                <span>
                                    <i class="fas fa-book-open"></i>
                                    <?php echo htmlspecialchars($class['matiere']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-calendar-alt"></i>
                                    السنة الدراسية: <?php echo $class['annee']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 