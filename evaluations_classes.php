<?php
session_start(); // Démarrer la session

// Assurez-vous d'inclure le fichier de configuration pour d'autres paramètres
include 'db_config.php'; 

// Vérifiez si $pdo est déjà défini, sinon créez une nouvelle connexion PDO
if (!isset($pdo)) {
    try {
        $host = 'localhost'; // Ou l'adresse de votre serveur de base de données
        $dbname = 'u504721134_formation'; // Le nom de votre base de données
        $username = 'root'; // Votre nom d'utilisateur de base de données
        $password = ''; // Votre mot de passe de base de données

        // Créer la connexion PDO
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Active le mode d'erreur pour PDO
    } catch (PDOException $e) {
        echo "Connexion échouée: " . $e->getMessage();
        exit();
    }
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_inspecteur'])) {
    header("Location: login.php");
    exit();
}

$id_inspecteur = $_SESSION['id_inspecteur'];
$inspecteur_name = isset($_SESSION['nom_inspecteur']) ? $_SESSION['nom_inspecteur'] . ' ' . $_SESSION['prenom_inspecteur'] : 'مفتّش';

// Traitement du formulaire
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_classe = $_POST['id_classe'];
    $infrastructure = $_POST['infrastructure'];
    $pedagogie = $_POST['pedagogie'];
    $discipline = $_POST['discipline'];
    $commentaire = $_POST['commentaire'];

    try {
        // Requête d'insertion dans la base de données
        $sql = "INSERT INTO evaluations_classes (id_inspecteur, id_classe, date_evaluation, infrastructure, pedagogie, discipline, commentaire)
                VALUES (?, ?, NOW(), ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$id_inspecteur, $id_classe, $infrastructure, $pedagogie, $discipline, $commentaire])) {
            $success_message = "تم تسجيل التقييم بنجاح!";
        } 
    } catch (PDOException $e) {
        $error_message = "حدث خطأ أثناء التسجيل: " . $e->getMessage();
    }
}

// Récupérer les informations sur la classe sélectionnée (si disponible)
$classe_info = null;
if (isset($_GET['id_classe'])) {
    $id_classe = $_GET['id_classe'];
    $query = "SELECT * FROM classes WHERE id_classe = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_classe]);
    $classe_info = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقييم القسم الدراسي - منظومة متابعة المتفقد التربوي</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
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
        }

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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(to left, var(--primary-dark), var(--primary-color));
            color: white;
            padding: 1rem 0;
            box-shadow: var(--box-shadow);
            position: relative;
            z-index: 10;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1.5rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .logo-icon {
            font-size: 2rem;
            color: var(--accent-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-name {
            font-weight: 600;
            font-size: 1rem;
        }

        .user-role {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 1.2rem;
        }

        .breadcrumb-container {
            background-color: white;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .breadcrumb {
            margin-bottom: 0;
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .breadcrumb-item.active {
            color: var(--gray-600);
        }

        .main-content {
            flex: 1;
            padding: 2rem 0;
        }

        .evaluation-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            max-width: 800px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease-out forwards;
        }

        .evaluation-card:hover {
            box-shadow: var(--box-shadow-lg);
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header-icon {
            font-size: 2rem;
            background: rgba(255, 255, 255, 0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: -10px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 1;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -20px;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            z-index: 1;
        }

        .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-family: 'Tajawal', sans-serif;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            outline: none;
        }

        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-family: 'Tajawal', sans-serif;
            background-position: left 1rem center;
        }

        .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            outline: none;
        }

        .rating-container {
            margin-top: 0.5rem;
        }

        .rating-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .rating-label {
            font-weight: 600;
            color: var(--gray-700);
        }

        .rating-value {
            font-weight: 700;
            color: var(--accent-color);
            font-size: 1.2rem;
        }

        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.25rem;
        }

        .rating input {
            display: none;
        }

        .rating label {
            cursor: pointer;
            width: 48px;
            height: 48px;
            background-color: var(--gray-200);
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--gray-400);
            border-radius: var(--border-radius);
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .rating label:hover,
        .rating label:hover ~ label,
        .rating input:checked ~ label {
            background-color: var(--accent-color);
            color: white;
            transform: scale(1.05);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--gray-200);
            color: var(--gray-700);
            border: none;
        }

        .btn-secondary:hover {
            background-color: var(--gray-300);
            transform: translateY(-2px);
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
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

        .alert-icon {
            font-size: 1.5rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .classe-info {
            background-color: var(--gray-100);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-right: 4px solid var(--primary-color);
        }

        .classe-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--primary-dark);
            font-weight: 600;
        }

        .classe-info p {
            margin: 0;
            color: var(--gray-600);
        }

        .footer {
            background-color: var(--gray-800);
            color: white;
            padding: 1.5rem 0;
            margin-top: 2rem;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1.5rem;
        }

        .footer-text {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .footer-links {
            display: flex;
            gap: 1rem;
        }

        .footer-link {
            color: white;
            opacity: 0.8;
            transition: var(--transition);
            text-decoration: none;
        }

        .footer-link:hover {
            opacity: 1;
            color: var(--accent-color);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .header-content, .footer-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .card-header h2 {
                font-size: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .rating label {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
            }
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem;
            }

            .rating {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1>منظومة متابعة التفقّد التربوي</h1>
            </div>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($inspecteur_name); ?></div>
                    <div class="user-role">متفقّد تربوي</div>
                </div>
                <div class="avatar">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb-container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="inspecteur.php"><i class="fas fa-home"></i> الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="inspecteur.php?page=evaluations_classes">تقييم الأقسام</a></li>
                <li class="breadcrumb-item active" aria-current="page">تقييم قسم دراسي جديد</li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <div class="alert-icon"><i class="fas fa-check-circle"></i></div>
                <div><?php echo $success_message; ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <div class="alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div><?php echo $error_message; ?></div>
            </div>
            <?php endif; ?>

            <div class="evaluation-card">
                <div class="card-header">
                    <h2>
                        <div class="card-header-icon">
                            <i class="fas fa-school"></i>
                        </div>
                        تقييم قسم دراسي
                    </h2>
                </div>
                <div class="card-body">
                    <?php if ($classe_info): ?>
                    <div class="classe-info">
                    <h4><i class="fas fa-info-circle ml-2"></i> معلومات القسم</h4>
                    <p>اسم القسم: <?php echo htmlspecialchars($classe_info['nom_classe']); ?></p>
                    </div>
                    <?php endif; ?>

                    <form method="post" id="evaluationForm">
                        <div class="form-group">
                        <label class="form-label" for="id_classe">اختر القسم الدراسي</label>
                            <select name="id_classe" id="id_classe" class="form-select" required>
                            <option value="" disabled selected>-- اختر القسم --</option>
                                <?php
                                // Récupérer les classes depuis la base de données
                                $query = "SELECT id_classe, nom_classe FROM classes ORDER BY nom_classe";
                                $stmt = $pdo->query($query);
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = (isset($_GET['id_classe']) && $_GET['id_classe'] == $row['id_classe']) ? 'selected' : '';
                                    echo "<option value='{$row['id_classe']}' {$selected}>{$row['nom_classe']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Infrastructure -->
                        <div class="form-group">
                            <div class="rating-title">
                                <label class="rating-label">البنية التحتية</label>
                                <span class="rating-value" id="infrastructure-value">0</span>
                            </div>
                            <div class="rating-container">
                                <div class="rating">
                                    <input type="radio" name="infrastructure" value="5" id="infra5" required>
                                    <label for="infra5"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="infrastructure" value="4" id="infra4">
                                    <label for="infra4"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="infrastructure" value="3" id="infra3">
                                    <label for="infra3"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="infrastructure" value="2" id="infra2">
                                    <label for="infra2"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="infrastructure" value="1" id="infra1">
                                    <label for="infra1"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                        </div>

                        <!-- Pédagogie -->
                        <div class="form-group">
                            <div class="rating-title">
                                <label class="rating-label">التربية والتعليم</label>
                                <span class="rating-value" id="pedagogie-value">0</span>
                            </div>
                            <div class="rating-container">
                                <div class="rating">
                                    <input type="radio" name="pedagogie" value="5" id="pedago5" required>
                                    <label for="pedago5"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="pedagogie" value="4" id="pedago4">
                                    <label for="pedago4"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="pedagogie" value="3" id="pedago3">
                                    <label for="pedago3"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="pedagogie" value="2" id="pedago2">
                                    <label for="pedago2"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="pedagogie" value="1" id="pedago1">
                                    <label for="pedago1"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                        </div>

                        <!-- Discipline -->
                        <div class="form-group">
                            <div class="rating-title">
                                <label class="rating-label">الانضباط</label>
                                <span class="rating-value" id="discipline-value">0</span>
                            </div>
                            <div class="rating-container">
                                <div class="rating">
                                    <input type="radio" name="discipline" value="5" id="discipline5" required>
                                    <label for="discipline5"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="discipline" value="4" id="discipline4">
                                    <label for="discipline4"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="discipline" value="3" id="discipline3">
                                    <label for="discipline3"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="discipline" value="2" id="discipline2">
                                    <label for="discipline2"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="discipline" value="1" id="discipline1">
                                    <label for="discipline1"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                        </div>

                        <!-- Commentaire -->
                        <div class="form-group">
                            <label class="form-label" for="commentaire">ملاحظات وتوصيات</label>
                            <textarea name="commentaire" id="commentaire" class="form-control" rows="4" placeholder="أدخل ملاحظاتك وتوصياتك حول الفصل الدراسي..."></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> حفظ التقييم
                            </button>
                            <a href="inspecteur.php?page=evaluations_classes" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i> العودة
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-text">
                &copy; <?php echo date('Y'); ?> منظومة متابعة التفقّد التربوي - جميع الحقوق محفوظة
            </div>
           
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mettre à jour les valeurs des évaluations
        document.addEventListener('DOMContentLoaded', function() {
            // Infrastructure rating
            const infraInputs = document.querySelectorAll('input[name="infrastructure"]');
            const infraValue = document.getElementById('infrastructure-value');
            
            infraInputs.forEach(input => {
                input.addEventListener('change', function() {
                    infraValue.textContent = this.value;
                });
            });
            
            // Pédagogie rating
            const pedagoInputs = document.querySelectorAll('input[name="pedagogie"]');
            const pedagoValue = document.getElementById('pedagogie-value');
            
            pedagoInputs.forEach(input => {
                input.addEventListener('change', function() {
                    pedagoValue.textContent = this.value;
                });
            });
            
            // Discipline rating
            const disciplineInputs = document.querySelectorAll('input[name="discipline"]');
            const disciplineValue = document.getElementById('discipline-value');
            
            disciplineInputs.forEach(input => {
                input.addEventListener('change', function() {
                    disciplineValue.textContent = this.value;
                });
            });

            // Validation du formulaire
            const form = document.getElementById('evaluationForm');
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Vérifier si une classe est sélectionnée
                const classeSelect = document.getElementById('id_classe');
                if (classeSelect.value === '') {
                    isValid = false;
                    classeSelect.classList.add('is-invalid');
                } else {
                    classeSelect.classList.remove('is-invalid');
                }
                
                // Vérifier si toutes les évaluations sont remplies
                const ratingGroups = ['infrastructure', 'pedagogie', 'discipline'];
                
                ratingGroups.forEach(group => {
                    const inputs = document.querySelectorAll(`input[name="${group}"]`);
                    let checked = false;
                    
                    inputs.forEach(input => {
                        if (input.checked) {
                            checked = true;
                        }
                    });
                    
                    if (!checked) {
                        isValid = false;
                        document.querySelector(`.rating-value#${group}-value`).classList.add('text-danger');
                    } else {
                        document.querySelector(`.rating-value#${group}-value`).classList.remove('text-danger');
                    }
                });
                
                if (!isValid) {
                    event.preventDefault();
                    alert('الرجاء تعمير جميع الخانات المطلوبة');
                }
            });
            
            // Afficher les alertes avec animation
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('show');
                }, 100);
                
                // Auto-hide success alerts after 5 seconds
                if (alert.classList.contains('alert-success')) {
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        setTimeout(() => {
                            alert.style.display = 'none';
                        }, 500);
                    }, 5000);
                }
            });
        });
    </script>
</body>
</html>

