<?php
$mysqli = new mysqli("localhost", "root", "", "u504721134_formation");

if ($mysqli->connect_error) {
    header("Location: classes.php?error=1");
    exit();
}

if (isset($_POST['id_classe'])) {
    $id_classe = intval($_POST['id_classe']);

    // Supprimer d'abord les élèves
    $mysqli->query("DELETE FROM eleves WHERE id_classe = $id_classe");

    // Supprimer les associations avec les profs
    $mysqli->query("DELETE FROM professeurs_classes WHERE id_classe = $id_classe");

    // Supprimer la classe
    if ($mysqli->query("DELETE FROM classes WHERE id_classe = $id_classe")) {
        header("Location: classes_admin.php?success=1");
    } else {
        header("Location: classes_admin.php?error=1");
    }
} else {
    header("Location: classes_admin.php?error=1");
}
exit();
