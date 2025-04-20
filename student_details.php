<?php

require_once 'db_config.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

// Récupérer l'ID de l'élève depuis l'URL
$id_eleve = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_eleve === 0) {
    header("Location: classes.php");
    exit();
}

// Récupérer les informations de l'élève
$queryEleve = $conn->prepare("
    SELECT e.*, c.nom_classe, c.annee 
    FROM eleves e 
    JOIN classes c ON e.id_classe = c.id_classe 
    WHERE e.id_eleve = ?
");
if ($queryEleve === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}

$queryEleve->bind_param("i", $id_eleve);
if (!$queryEleve->execute()) {
    die("Erreur d'exécution de la requête: " . $queryEleve->error);
}

$eleve = $queryEleve->get_result()->fetch_assoc();

if (!$eleve) {
    header("Location: classes.php");
    exit();
}

// Déterminer si c'est une classe de niveau 1-2 ou 3-6
$isNiveau12 = preg_match('/السنة (الأولى|الثانية)/', $eleve['nom_classe']);

// Récupérer les notes par domaine
$queryNotes = $conn->prepare("
    SELECT 
        d.nom as domaine,
        AVG(n.note) as moyenne_domaine,
        COUNT(n.note) as nb_notes
    FROM notes n
    JOIN matieres m ON n.matiere_id = m.matiere_id
    JOIN domaines d ON m.domaine_id = d.id
    WHERE n.id_eleve = ?
    GROUP BY d.id, d.nom
");
if ($queryNotes === false) {
    die("Erreur de préparation de la requête des notes: " . $conn->error);
}

$queryNotes->bind_param("i", $id_eleve);
if (!$queryNotes->execute()) {
    die("Erreur d'exécution de la requête des notes: " . $queryNotes->error);
}

$notes = $queryNotes->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculer la moyenne générale
$moyenneGenerale = 0;
$totalCoefficients = 0;

foreach ($notes as $note) {
    switch ($note['domaine']) {
        case 'مجال اللغة العربية':
            $coefficient = 2;
            break;
        case 'مجال العلوم والتكنولوجيا':
            $coefficient = 2;
            break;
        case 'مجال التنشئة':
            $coefficient = 1;
            break;
        case 'مجال اللغات الأجنبية':
            $coefficient = $isNiveau12 ? 0 : 1.5;
            break;
        default:
            $coefficient = 0;
            break;
    }
    
    if ($note['moyenne_domaine'] !== null) {
        $moyenneGenerale += $note['moyenne_domaine'] * $coefficient;
        $totalCoefficients += $coefficient;
    }
}

if ($totalCoefficients > 0) {
    $moyenneGenerale = $moyenneGenerale / $totalCoefficients;
}
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل التلميذ | <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e6091;
            --primary-dark: #0d4a77;
            --secondary-color: #2a9d8f;
            --secondary-dark: #1f756a;
            --accent-color: #e9c46a;
            --text-color: #264653;
            --text-light: #546a7b;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
            --error-color: #e76f51;
            --success-color: #2a9d8f;
            --warning-color: #f4a261;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .student-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: var(--shadow);
        }

        .student-title {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .student-meta {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }

        .meta-item:hover {
            transform: translateY(-2px);
        }

        .meta-item i {
            font-size: 1.2rem;
            color: var(--accent-color);
        }

        .meta-item span {
            font-weight: 600;
            color: white;
        }

        .back-btn {
            background-color: rgba(255, 255, 255, 0.15);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
        }

        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateX(-5px);
        }

        .back-btn i {
            font-size: 1.2rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .grades-section {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .grades-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .grade-item {
            background-color: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .grade-item:hover {
            transform: translateX(-5px);
        }

        .grade-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .grade-title {
            font-weight: 600;
            color: var(--text-color);
        }

        .grade-average {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 60px;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: var(--shadow);
        }

        .grade-good {
            background-color: rgba(42, 157, 143, 0.1);
            color: var(--success-color);
        }

        .grade-average {
            background-color: rgba(244, 162, 97, 0.1);
            color: var(--warning-color);
        }

        .grade-poor {
            background-color: rgba(231, 111, 81, 0.1);
            color: var(--error-color);
        }

        .grade-details {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .general-average {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 2rem;
        }

        .average-label {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .average-value {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .info-section {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            background-color: rgba(30, 96, 145, 0.05);
        }

        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-weight: 600;
            color: var(--text-color);
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .student-info {
                flex-direction: column;
                text-align: center;
            }

            .student-meta {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="student-info">
                    <div class="student-avatar">
                        <?= strtoupper(substr($eleve['prenom'], 0, 1) . substr($eleve['nom'], 0, 1)) ?>
                    </div>
                    <div>
                        <h1 class="student-title"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h1>
                        <div class="student-meta">
                            <span class="meta-item">
                                <i class="fas fa-graduation-cap"></i>
                                <span><?= htmlspecialchars($eleve['nom_classe']) ?></span>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?= htmlspecialchars($eleve['annee']) ?></span>
                            </span>
                        </div>
                    </div>
                </div>
                <a href="class_details.php?id=<?= $eleve['id_classe'] ?>" class="back-btn">
                    <i class="fas fa-arrow-right"></i>
                    العودة إلى القسم
                </a>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="content-grid">
            <section class="grades-section">
                <h2 class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    النتائج حسب المجالات
                </h2>
                
                <div class="grades-list">
                    <?php foreach ($notes as $note): ?>
                        <div class="grade-item">
                            <div class="grade-header">
                                <span class="grade-title"><?= htmlspecialchars($note['domaine']) ?></span>
                                <?php
                                $moyenne = round($note['moyenne_domaine'], 2);
                                $badgeClass = $moyenne >= 10 ? 'grade-good' : ($moyenne >= 5 ? 'grade-average' : 'grade-poor');
                                ?>
                                <span class="grade-average <?= $badgeClass ?>">
                                    <?= $moyenne ?>
                                </span>
                            </div>
                            <div class="grade-details">
                                <?= $note['nb_notes'] ?> اختبار
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="general-average">
                    <div class="average-label">المتوسط العام</div>
                    <div class="average-value">
                        <?= round($moyenneGenerale, 2) ?>
                    </div>
                </div>
            </section>

            <section class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    المعلومات الشخصية
                </h2>
                
                <div class="info-list">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">البريد الإلكتروني</div>
                            <div class="info-value"><?= htmlspecialchars($eleve['email']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">رقم الهاتف</div>
                            <div class="info-value"><?= htmlspecialchars($eleve['telephone'] ?? 'غير محدد') ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">العنوان</div>
                            <div class="info-value"><?= htmlspecialchars($eleve['adresse'] ?? 'غير محدد') ?></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animation pour les éléments de la page
            const elements = document.querySelectorAll('.grades-section, .info-section');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    element.style.transition = 'all 0.5s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 200 * index);
            });
        });
    </script>
</body>
</html> 