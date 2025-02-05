<?php
// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=gestion_notes', 'root', ''); 
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Requête pour récupérer les classes
$query = 'SELECT id, nom FROM classes';
$stmt = $pdo->query($query);

// Vérifier si des résultats sont trouvés
if ($stmt) {
    $options = '<option value="">Sélectionner une classe</option>';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $options .= "<option value='{$row['id']}'>{$row['nom']}</option>";
    }
    echo $options;
} else {
    echo '<option value="">Aucune classe trouvée</option>';
}
?>
