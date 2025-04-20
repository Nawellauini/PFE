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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_id'])) {
    $note_id = intval($_POST['note_id']);
    
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
        header("Location: liste_notes.php?message=ليس لديك صلاحية حذف هذه النتيجة&type=danger");
        exit();
    }
    
    // Supprimer la note
    $query_delete = "DELETE FROM notes WHERE id = ?";
    $stmt_delete = $conn->prepare($query_delete);
    $stmt_delete->bind_param("i", $note_id);
    
    if ($stmt_delete->execute()) {
        header("Location: liste_notes.php?message=تم حذف النتيجة بنجاح&type=success");
        exit();
    } else {
        header("Location: liste_notes.php?message=حدث خطأ أثناء حذف النتيجة&type=danger");
        exit();
    }
} else {
    header("Location: liste_notes.php?message=بيانات غير صالحة&type=danger");
    exit();
}
