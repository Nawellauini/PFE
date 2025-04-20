<?php

session_start();
include 'db_config.php';


if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nb_copies = $_POST['nb_copies'];
    $nom_fichier_original = $_FILES['fichier']['name'];
    $nom_fichier = time() . '_' . $nom_fichier_original;
    $dossier_upload = 'uploads/impressions/';
    
    if (!file_exists($dossier_upload)) {
        mkdir($dossier_upload, 0777, true);
    }
    
    $chemin_fichier = $dossier_upload . $nom_fichier;
    
    if (move_uploaded_file($_FILES['fichier']['tmp_name'], $chemin_fichier)) {
        $query = "INSERT INTO demandes_impression (id_prof, nb_copies, nom_fichier, date_demande, nom_fichier_original) 
                 VALUES (?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiss", $id_professeur, $nb_copies, $nom_fichier, $nom_fichier_original);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">تم إرسال طلب الطباعة بنجاح</div>';
        } else {
            $message = '<div class="alert alert-danger">حدث خطأ أثناء إرسال الطلب</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">حدث خطأ أثناء رفع الملف</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب طباعة - نظام إدارة المدرسة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-title {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">
                <i class="fas fa-print me-2"></i>
                طلب طباعة
            </h2>
            
            <?php echo $message; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="fichier" class="form-label">الملف المراد طباعته</label>
                    <input type="file" class="form-control" id="fichier" name="fichier" required>
                </div>
                
                <div class="mb-3">
                    <label for="nb_copies" class="form-label">عدد النسخ</label>
                    <input type="number" class="form-control" id="nb_copies" name="nb_copies" min="1" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>
                        إرسال الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 