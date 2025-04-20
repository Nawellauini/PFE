<?php
$mysqli = new mysqli("localhost", "root", "", "u504721134_formation");

if ($mysqli->connect_error) {
    die("Erreur de connexion: " . $mysqli->connect_error);
}

$matiere_id = $_POST['matiere_id'] ?? null;
$nom = $_POST['nom'] ?? '';
$professeur_id = $_POST['professeur_id'] ?? null;
$classe_id = $_POST['classe_id'] ?? null;
$domaine_id = $_POST['domaine_id'] ?? null;

if ($matiere_id && $nom && $professeur_id && $classe_id && $domaine_id) {
    $stmt = $mysqli->prepare("UPDATE matieres SET nom = ?, professeur_id = ?, classe_id = ?, domaine_id = ? WHERE matiere_id = ?");
    $stmt->bind_param("siiii", $nom, $professeur_id, $classe_id, $domaine_id, $matiere_id);
    if ($stmt->execute()) {
        header("Location: matiere.php?success=1");
    } else {
        header("Location: matiere.php?error=1");
    }
    $stmt->close();
} else {
    header("Location: matiere.php?error=1");
}
$mysqli->close();
?>
