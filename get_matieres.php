<?php
// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=gestion_notes', 'root', ''); 
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (isset($_GET['classe_id'])) {
    $classe_id = $_GET['classe_id'];

    // Récupérer les matières disponibles pour cette classe
    $query = "SELECT id, nom FROM matieres WHERE classe_id = :classe_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['classe_id' => $classe_id]);

    $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($matieres) {
        foreach ($matieres as $matiere) {
            echo "<option value='{$matiere['id']}'>{$matiere['nom']}</option>";
        }
    } else {
        echo "<option>Aucune matière trouvée</option>";
    }
}
?>
