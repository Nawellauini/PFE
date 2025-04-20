<?php
require_once 'db_config.php';

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

// Récupérer l'ID de la classe depuis l'URL
$id_classe = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_classe === 0) {
    header("Location: classes.php");
    exit();
}

// Récupérer les informations de la classe
$queryClasse = $conn->prepare("SELECT * FROM classes WHERE id_classe = ?");
if ($queryClasse === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}

$queryClasse->bind_param("i", $id_classe);
if (!$queryClasse->execute()) {
    die("Erreur d'exécution de la requête: " . $queryClasse->error);
}

$classe = $queryClasse->get_result()->fetch_assoc();

if (!$classe) {
    header("Location: classes.php");
    exit();
}

// Compter le nombre d'élèves séparément
$queryCount = $conn->prepare("SELECT COUNT(*) as nb_eleves FROM eleves WHERE id_classe = ?");
if ($queryCount === false) {
    die("Erreur de préparation de la requête de comptage: " . $conn->error);
}

$queryCount->bind_param("i", $id_classe);
if (!$queryCount->execute()) {
    die("Erreur d'exécution de la requête de comptage: " . $queryCount->error);
}

$countResult = $queryCount->get_result()->fetch_assoc();
$classe['nb_eleves'] = $countResult['nb_eleves'];

// Récupérer les élèves de la classe
$queryEleves = $conn->prepare("SELECT id_eleve, nom, prenom, email FROM eleves WHERE id_classe = ? ORDER BY nom, prenom");
if ($queryEleves === false) {
    die("Erreur de préparation de la requête des élèves: " . $conn->error);
}

$queryEleves->bind_param("i", $id_classe);
if (!$queryEleves->execute()) {
    die("Erreur d'exécution de la requête des élèves: " . $queryEleves->error);
}

$eleves = $queryEleves->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculer les moyennes pour chaque élève selon les domaines
foreach ($eleves as &$eleve) {
    // Récupérer le niveau de la classe (1-2 ou 3-6)
    $queryNiveau = $conn->prepare("SELECT nom_classe FROM classes WHERE id_classe = ?");
    $queryNiveau->bind_param("i", $id_classe);
    $queryNiveau->execute();
    $nomClasse = $queryNiveau->get_result()->fetch_assoc()['nom_classe'];
    
    // Déterminer si c'est une classe de niveau 1-2 ou 3-6
    $isNiveau12 = preg_match('/السنة (الأولى|الثانية)/', $nomClasse);
    
    // Calculer la moyenne par domaine
    $queryDomaines = $conn->prepare("
        SELECT 
            d.nom as domaine,
            AVG(n.note) as moyenne_domaine
        FROM notes n
        JOIN matieres m ON n.matiere_id = m.matiere_id
        JOIN domaines d ON m.domaine_id = d.id
        WHERE n.id_eleve = ?
        GROUP BY d.id, d.nom
    ");
    if ($queryDomaines === false) {
        die("Erreur de préparation de la requête des domaines: " . $conn->error);
    }
    
    $queryDomaines->bind_param("i", $eleve['id_eleve']);
    if (!$queryDomaines->execute()) {
        die("Erreur d'exécution de la requête des domaines: " . $queryDomaines->error);
    }
    
    $resultDomaines = $queryDomaines->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculer la moyenne générale selon le niveau
    $moyenneGenerale = 0;
    $totalCoefficients = 0;
    
    foreach ($resultDomaines as $domaine) {
        switch ($domaine['domaine']) {
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
        
        if ($domaine['moyenne_domaine'] !== null) {
            $moyenneGenerale += $domaine['moyenne_domaine'] * $coefficient;
            $totalCoefficients += $coefficient;
        }
    }
    
    if ($totalCoefficients > 0) {
        $eleve['moyenne_generale'] = $moyenneGenerale / $totalCoefficients;
    } else {
        $eleve['moyenne_generale'] = null;
    }
}

// Récupérer les événements de la classe
$queryEvents = $conn->prepare("SELECT * FROM calendar_events 
                             WHERE class = ?
                             ORDER BY event_date DESC
                             LIMIT 5");
if ($queryEvents === false) {
    die("Erreur de préparation de la requête des événements: " . $conn->error);
}

$queryEvents->bind_param("i", $id_classe);
if (!$queryEvents->execute()) {
    die("Erreur d'exécution de la requête des événements: " . $queryEvents->error);
}

$events = $queryEvents->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل القسم | <?= htmlspecialchars($classe['nom_classe']) ?></title>
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

        .class-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .class-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-left: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .class-title {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .class-meta {
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

        .class-year {
            background-color: rgba(233, 196, 106, 0.2);
            color: var(--accent-color);
        }

        .class-students {
            background-color: rgba(42, 157, 143, 0.2);
            color: var(--success-color);
        }

        .back-btn {
            background-color: rgba(255, 255, 255, 0.15);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
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
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .students-section {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
            font-size: 1.2rem;
            font-weight: 600;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .section-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .action-btn:hover {
            background-color: var(--primary-dark);
        }

        .students-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }

        .students-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            font-weight: 600;
            text-align: right;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .students-table td {
            padding: 1rem;
            text-align: right;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.3s ease;
        }

        .students-table tr:hover td {
            background-color: rgba(30, 96, 145, 0.05);
        }

        .student-name {
            font-weight: 600;
            color: var(--text-color);
        }

        .student-email {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .average-badge {
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

        .average-good {
            background-color: rgba(42, 157, 143, 0.1);
            color: var(--success-color);
        }

        .average-average {
            background-color: rgba(244, 162, 97, 0.1);
            color: var(--warning-color);
        }

        .average-poor {
            background-color: rgba(231, 111, 81, 0.1);
            color: var(--error-color);
        }

        .events-section {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .events-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
        }

        .event-item {
            background-color: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .event-item:hover {
            transform: translateX(-5px);
        }

        .event-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .event-description {
            color: var(--text-color);
            line-height: 1.5;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
        }

        .no-data i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .class-info {
                flex-direction: column;
                text-align: center;
            }

            .class-meta {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="class-info">
                    <i class="fas fa-users class-icon"></i>
                    <div>
                        <h1 class="class-title"><?= htmlspecialchars($classe['nom_classe']) ?></h1>
                        <div class="class-meta">
                            <span class="meta-item class-year">
                                <i class="fas fa-calendar"></i>
                                <span><?= htmlspecialchars($classe['annee']) ?></span>
                            </span>
                            <span class="meta-item class-students">
                                <i class="fas fa-user-graduate"></i>
                                <span><?= $classe['nb_eleves'] ?> تلميذ</span>
                            </span>
                        </div>
                    </div>
                </div>
                <a href="classes.php" class="back-btn">
                    <i class="fas fa-arrow-right"></i>
                    العودة إلى الأقسام
                </a>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="content-grid">
            <section class="students-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-user-graduate"></i>
                        التلاميذ
                    </h2>
                    
                </div>
                
                <?php if (empty($eleves)): ?>
                    <div class="no-data">
                        <i class="fas fa-user-slash"></i>
                        <p>لا يوجد تلاميذ في هذا القسم</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>المتوسط</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eleves as $eleve): ?>
                                    <tr>
                                        <td>
                                            <a href="student_details.php?id=<?= $eleve['id_eleve'] ?>" class="student-name">
                                                <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?>
                                            </a>
                                        </td>
                                        <td class="student-email"><?= htmlspecialchars($eleve['email']) ?></td>
                                        <td>
                                            <?php if ($eleve['moyenne_generale'] !== null): ?>
                                                <?php
                                                $moyenne = round($eleve['moyenne_generale'], 2);
                                                $badgeClass = $moyenne >= 10 ? 'average-good' : ($moyenne >= 5 ? 'average-average' : 'average-poor');
                                                ?>
                                                <span class="average-badge <?= $badgeClass ?>">
                                                    <?= $moyenne ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="average-badge">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="events-section">
                <h2 class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    الأحداث القادمة
                </h2>
                
                <?php if (empty($events)): ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-times"></i>
                        <p>لا توجد أحداث قادمة</p>
                    </div>
                <?php else: ?>
                    <ul class="events-list">
                        <?php foreach ($events as $event): ?>
                            <li class="event-item">
                                <div class="event-description">
                                    <?= htmlspecialchars($event['description']) ?>
                                </div>
                                <div class="event-date">
                                    <i class="fas fa-clock"></i>
                                    <?= date('Y-m-d', strtotime($event['event_date'])) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animation pour les éléments de la page
            const elements = document.querySelectorAll('.students-section, .events-section');
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