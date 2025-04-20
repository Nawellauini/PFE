<?php

session_start();
include 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_id'], $_POST['note'])) {
    $note_id = intval($_POST['note_id']);
    $note_value = floatval(str_replace(',', '.', $_POST['note']));
    
    // Vérifier que la note est valide
    if ($note_value < 0 || $note_value > 20) {
        header("Location: liste_notes.php?message=النتيجة يجب أن تكون بين 0 و 20&type=danger");
        exit();
    }
    
    // Vérifier que le professeur a accès à cette note
    $query_check = "SELECT n.id
                   FROM notes n
                   JOIN eleves e ON n.id_eleve = e.id_eleve
                   JOIN classes c ON e.id_classe = c.id_classe
                   JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
                   WHERE n.id = ? AND pc.id_professeur = ?";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bind_param("ii", $note_id, $id_professeur);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        header("Location: liste_notes.php?message=ليس لديك صلاحية تعديل هذه النتيجة&type=danger");
        exit();
    }
    
    // Mettre à jour la note
    $query_update = "UPDATE notes SET note = ? WHERE id = ?";
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bind_param("di", $note_value, $note_id);
    
    if ($stmt_update->execute()) {
        header("Location: liste_notes.php?message=تم تعديل النتيجة بنجاح&type=success");
        exit();
    } else {
        header("Location: liste_notes.php?message=حدث خطأ أثناء تعديل النتيجة&type=danger");
        exit();
    }
} else {
    header("Location: liste_notes.php?message=بيانات غير صالحة&type=danger");
    exit();
}
