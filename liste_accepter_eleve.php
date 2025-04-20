<?php
include 'db_config.php';

use Shuchkin\SimpleXLSXGen;

// Fonction pour exporter en Excel
if (isset($_POST['export_excel'])) {
    require_once 'libs/SimpleXLSXGen.php';
    
    $query = "SELECT nom, prenom, age, 
              (SELECT nom_classe FROM classes WHERE id_classe = d.classe_demande) as classe,
              email, telephone, date_demande, login, mot_de_passe
              FROM demandes_inscription d 
              WHERE statut = 'Accepté'
              ORDER BY date_demande DESC";
    
    $result = $conn->query($query);
    
    $data = [];
    // En-têtes
    $data[] = ['الاسم', 'اللقب', 'العمر', 'القسم', 'البريد الإلكتروني', 'رقم الهاتف', 'تاريخ القبول', 'اسم المستخدم', 'كلمة المرور'];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            $row['nom'],
            $row['prenom'],
            $row['age'],
            $row['classe'],
            $row['email'],
            $row['telephone'],
            $row['date_demande'],
            $row['login'],
            $row['mot_de_passe']
        ];
    }
    
    $xlsx = SimpleXLSXGen::fromArray($data);
    $xlsx->downloadAs('liste_eleves_acceptes.xlsx');
    exit;
}

// Récupérer les élèves acceptés
$query = "SELECT d.*, c.nom_classe 
          FROM demandes_inscription d 
          LEFT JOIN classes c ON d.classe_demande = c.id_classe 
          WHERE d.statut = 'Accepté'
          ORDER BY d.date_demande DESC";
$result = $conn->query($query);

// Vérifier si la requête a réussi
if ($result === false) {
    die("Erreur SQL : " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة التلاميذ المقبولين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --secondary-color: #10b981;
            --secondary-dark: #059669;
            --danger-color: #ef4444;
            --danger-dark: #dc2626;
            --warning-color: #f59e0b;
            --warning-dark: #d97706;
            --info-color: #6366f1;
            --info-dark: #4f46e5;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
            --gray-color: #9ca3af;
            --white-color: #ffffff;
            --border-radius: 12px;
            --transition-speed: 0.3s;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        body {
            background-color: #f9fafb;
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
            margin-bottom: 2rem;
            width: 100%;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            padding: 1rem;
        }
        
        .table {
            margin-bottom: 0;
            width: 100%;
        }
        
        .table th {
            background-color: var(--light-color);
            border-bottom: 2px solid var(--gray-color);
            font-weight: 600;
            text-align: center;
            padding: 1rem;
        }
        
        .table td {
            text-align: center;
            padding: 1rem;
            vertical-align: middle;
        }
        
        .btn-export {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: all var(--transition-speed) ease;
        }
        
        .btn-export:hover {
            background-color: var(--secondary-dark);
            color: white;
        }
        
        .table-responsive {
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            overflow: hidden;
        }
        
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 0 0.5rem;
            }
            
            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">قائمة التلاميذ المقبولين</h3>
                <form method="post" class="d-inline">
                    <button type="submit" name="export_excel" class="btn btn-export">
                        <i class="fas fa-file-excel"></i> تصدير إلى Excel
                    </button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>اللقب</th>
                            <th>العمر</th>
                            <th>القسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>رقم الهاتف</th>
                            <th>تاريخ القبول</th>
                            <th>اسم المستخدم</th>
                            <th>كلمة المرور</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nom']); ?></td>
                            <td><?php echo htmlspecialchars($row['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($row['age']); ?></td>
                            <td><?php echo htmlspecialchars($row['nom_classe']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['telephone']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['date_demande'])); ?></td>
                            <td><?php echo htmlspecialchars($row['login']); ?></td>
                            <td><?php echo htmlspecialchars($row['mot_de_passe']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 