<?php
// Vérification si un ID de classe a été reçu
if (isset($_GET['classe_id']) && !empty($_GET['classe_id'])) {
    $classe_id = $_GET['classe_id'];

    try {
        // Connexion à la base de données avec gestion des erreurs
        $pdo = new PDO('mysql:host=localhost;dbname=gestion_notes', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Requête pour récupérer les élèves de la classe sélectionnée
        $query = "SELECT id, nom FROM eleves WHERE classe_id = :classe_id ORDER BY nom ASC";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':classe_id', $classe_id, PDO::PARAM_INT);
        $stmt->execute();
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Vérifier si des élèves ont été trouvés
        if ($eleves) {
            echo '<option value="">Sélectionner un élève</option>';
            foreach ($eleves as $eleve) {
                echo '<option value="' . htmlspecialchars($eleve['id']) . '">' . htmlspecialchars($eleve['nom']) . '</option>';
            }
        } else {
            echo '<option value="">Aucun élève trouvé</option>';
        }
    } catch (Exception $e) {
        echo '<option value="">Erreur de chargement</option>';
    }
} else {
    echo '<option value="">Sélectionner une classe</option>';
}
?>
