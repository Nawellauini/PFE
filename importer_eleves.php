<?php
// Connexion à la base de données (adapter selon tes identifiants)
$host = 'localhost';
$dbname = 'u504721134_formation';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

require_once 'libs/SimpleXLSX.php';

// Vérifie si le formulaire a été soumis
if (isset($_FILES['fichier_excel'])) {
    if ($xlsx = new SimpleXLSX($_FILES['fichier_excel']['tmp_name'])) {
        $rows = $xlsx->rows();
        
        // On peut ignorer la première ligne si c'est un en-tête
        $startRow = 1;

        for ($i = $startRow; $i < count($rows); $i++) {
            $row = $rows[$i];
            $nom = $row[0] ?? '';
            $prenom = $row[1] ?? '';
            $email = $row[2] ?? '';

            // Vérifie si tous les champs sont remplis
            if (!empty($nom) && !empty($prenom) && !empty($email)) {
                $stmt = $pdo->prepare("INSERT INTO eleves (nom, prenom, email) VALUES (?, ?, ?)");
                $stmt->execute([$nom, $prenom, $email]);
            }
        }

        header("Location: classes_admin.php?importation=success");
exit;

    } 
} header("Location: classes_admin.php?importation=error");
exit;


?>
