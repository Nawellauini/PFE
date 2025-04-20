<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'élève est connecté
if (!isset($_SESSION['id_eleve'])) {
    header('Location: login.php');
    exit();
}

include 'db_config.php';

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

// Récupérer les informations de l'élève
$id_eleve = $_SESSION['id_eleve'];
$query = "SELECT e.*, c.id_classe, c.nom_classe 
          FROM eleves e 
          JOIN classes c ON e.id_classe = c.id_classe 
          WHERE e.id_eleve = ?";

$result = executeQuery($conn, $query, [$id_eleve], "i");
$eleve = $result ? $result->fetch_assoc() : null;

if (!$eleve) {
    echo "Erreur: Impossible de récupérer les informations de l'élève.";
    exit();
}

// Récupérer directement les vidéos pour la classe de l'élève
$query_videos = "SELECT v.*, p.nom as nom_prof, p.prenom as prenom_prof, m.nom as nom_matiere, t.nom_theme
                 FROM videos v
                 JOIN professeurs p ON v.id_professeur = p.id_professeur
                 JOIN matieres m ON v.matiere = m.matiere_id
                 JOIN themes t ON v.id_theme = t.id_theme
                 WHERE v.id_classe = ?
                 ORDER BY v.id_video DESC";

$result_videos = executeQuery($conn, $query_videos, [$eleve['id_classe']], "i");
$videos = $result_videos ? $result_videos->fetch_all(MYSQLI_ASSOC) : [];

// Débogage - Afficher les requêtes et résultats
$debug = false;
if ($debug) {
    echo "<pre>";
    echo "ID Élève: " . $id_eleve . "\n";
    echo "ID Classe: " . $eleve['id_classe'] . "\n";
    echo "Requête vidéos: " . $query_videos . "\n";
    echo "Nombre de vidéos trouvées: " . count($videos) . "\n";
    echo "</pre>";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مقاطع الفيديو التعليمية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: rgba(67, 97, 238, 0.1);
            --secondary-color: #3a0ca3;
            --secondary-light: rgba(58, 12, 163, 0.1);
            --accent-color: #f72585;
            --accent-light: rgba(247, 37, 133, 0.1);
            --success-color: #4cc9f0;
            --warning-color: #fca311;
            --danger-color: #e63946;
            --dark-color: #1d3557;
            --light-color: #f8f9fa;
            --gray-color: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 16px;
            --card-radius: 20px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: 0;
        }
        
        .header h1 {
            position: relative;
            z-index: 1;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            position: relative;
            z-index: 1;
            opacity: 0.9;
        }
        
        .filters {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }
        
        .video-card {
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .video-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .video-card .card-header {
            background-color: white;
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .video-card .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        
        .video-card .card-subtitle {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .video-card .card-body {
            padding: 0;
            flex: 1;
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            overflow: hidden;
        }
        
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .video-card .card-footer {
            padding: 1.25rem 1.5rem;
            background-color: white;
            border-top: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .badge-primary {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .badge-secondary {
            background-color: var(--secondary-light);
            color: var(--secondary-color);
        }
        
        .badge-accent {
            background-color: var(--accent-light);
            color: var(--accent-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--gray-color);
            margin-bottom: 1.5rem;
        }
        
        .empty-state h3 {
            color: var(--dark-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .empty-state p {
            color: var(--gray-color);
            font-size: 1.1rem;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-light);
            transform: translateY(-3px);
        }
        
        .video-info {
            padding: 1.25rem 1.5rem;
            background-color: var(--light-color);
        }
        
        .video-description {
            margin-bottom: 1rem;
            color: var(--gray-color);
            font-size: 0.95rem;
        }
        
        .video-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        .video-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .teacher-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .teacher-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .teacher-name {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .video-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-color);
            color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .row {
            --bs-gutter-x: 2rem;
        }
        
        @media (max-width: 992px) {
            .container {
                max-width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 1.5rem;
            }
            
            .header h1 {
                font-size: 1.75rem;
            }
            
            .video-card .card-header {
                padding: 1.25rem;
            }
            
            .video-card .card-title {
                font-size: 1.1rem;
            }
            
            .video-card .card-footer {
                padding: 1rem 1.25rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .badge {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
        }
        
        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .slide-in {
            animation: slideIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header fade-in">
            <h1>
                <i class="fas fa-video me-2"></i>
                مقاطع الفيديو التعليمية
            </h1>
            <p class="mb-0 mt-2">
                مرحباً <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?>،
                هذه مقاطع الفيديو المتاحة لصفك <?php echo htmlspecialchars($eleve['nom_classe']); ?>
            </p>
        </div>
        
        <?php if (empty($videos)): ?>
            <div class="empty-state fade-in">
                <i class="fas fa-film"></i>
                <h3>لا توجد مقاطع فيديو متاحة</h3>
                <p>لم يتم العثور على مقاطع فيديو تعليمية لصفك في الوقت الحالي. يرجى التحقق مرة أخرى لاحقًا.</p>
            </div>
        <?php else: ?>
            <div class="row" id="videos-container">
                <?php foreach ($videos as $index => $video): ?>
                    <div class="col-md-6 col-lg-4 mb-4 video-item slide-in" 
                         style="animation-delay: <?php echo $index * 0.1; ?>s">
                        <div class="video-card">
                            <div class="card-header">
                                <h5 class="card-title"><?php echo htmlspecialchars($video['titre_video']); ?></h5>
                                <div class="card-subtitle">
                                    <span class="badge badge-primary">
                                        <i class="fas fa-book"></i>
                                        <?php echo htmlspecialchars($video['nom_matiere']); ?>
                                    </span>
                                    <span class="badge badge-secondary">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($video['nom_theme']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="video-container">
                                    <video controls>
                                        <source src="<?php echo htmlspecialchars($video['url_video']); ?>" type="video/mp4">
                                        متصفحك لا يدعم تشغيل الفيديو.
                                    </video>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="teacher-info">
                                    <div class="teacher-avatar">
                                        <?php 
                                        $initials = mb_substr($video['prenom_prof'], 0, 1) . mb_substr($video['nom_prof'], 0, 1);
                                        echo $initials;
                                        ?>
                                    </div>
                                    <div>
                                        <div class="teacher-name">
                                            <?php echo htmlspecialchars($video['prenom_prof'] . ' ' . $video['nom_prof']); ?>
                                        </div>
                                        <small>أستاذ المادة</small>
                                    </div>
                                </div>
                                <div class="video-actions">
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>