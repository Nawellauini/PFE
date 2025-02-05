<?php
// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=gestion_notes', 'root', ''); 
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (isset($_GET['classe_id'])) {
    $classe_id = $_GET['classe_id'];

    // Requête pour récupérer les professeurs associés aux matières
    $query = "
        SELECT p.id, p.nom 
        FROM professeurs p
        JOIN matieres m ON m.professeur_id = p.id
        WHERE m.classe_id = :classe_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['classe_id' => $classe_id]);

    $professeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($professeurs as $professeur) {
        echo "<option value='{$professeur['id']}'>{$professeur['nom']}</option>";
    }
}
?>
