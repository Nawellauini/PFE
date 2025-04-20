<?php
$mysqli = new mysqli("localhost", "root", "", "u504721134_formation");

if ($mysqli->connect_error) {
    die("Erreur de connexion: " . $mysqli->connect_error);
}

$matiere_id = $_POST['matiere_id'] ?? null;

if ($matiere_id) {
    $stmt = $mysqli->prepare("DELETE FROM matieres WHERE matiere_id = ?");
    $stmt->bind_param("i", $matiere_id);
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
