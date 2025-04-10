<?php
session_start();
include 'db_config.php'; // Connexion à la base de données

// Vérifier si l'ID du rapport est passé en paramètre
if (isset($_GET['id'])) {
    $rapport_id = $_GET['id'];

    // Supprimer les fichiers associés
    $sql_fichiers = "DELETE FROM fichiers_rapport WHERE rapport_id = ?";
    $stmt_fichiers = $conn->prepare($sql_fichiers);
    $stmt_fichiers->bind_param("i", $rapport_id);
    $stmt_fichiers->execute();

    // Supprimer le rapport
    $sql_rapport = "DELETE FROM rapports_inspection WHERE id = ?";
    $stmt_rapport = $conn->prepare($sql_rapport);
    $stmt_rapport->bind_param("i", $rapport_id);

    if ($stmt_rapport->execute()) {
        header("Location: liste_rapports.php?msg=تم حذف التقرير بنجاح.");
    } else {
        echo "خطأ أثناء حذف التقرير.";
    }
} else {
    echo "لم يتم العثور على أي تقرير.";
}
?>