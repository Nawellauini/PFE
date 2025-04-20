<?php


include 'db.php'; // Connexion à la base de données


if (isset($_POST['id_professeur'])) {
    $id_professeur = $_POST['id_professeur'];

    // Requête pour récupérer les classes de ce professeur
    $query = "SELECT c.id_classe, c.nom_classe 
              FROM classes c 
              JOIN professeurs_classes pc ON c.id_classe = pc.id_classe 
              WHERE pc.id_professeur = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_professeur]);
    
    // Affichage des options de classe
    echo "<option value=''>-- اختر الفصل --</option>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<option value='{$row['id_classe']}'>{$row['nom_classe']}</option>";
    }
}
?>
