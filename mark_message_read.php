<?php
session_start();
require_once 'db_config.php';

// Vérifier si l'utilisateur est connecté et si un ID de message est fourni
if (!isset($_SESSION['id_professeur']) || !isset($_POST['message_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

$id_professeur = $_SESSION['id_professeur'];
$message_id = intval($_POST['message_id']);

// Vérifier que le message appartient bien au professeur
$stmt = $conn->prepare("UPDATE message_eleve SET is_read = 1 
                       WHERE id = ? AND id_professeur = ?");

if ($stmt === false) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur de préparation de la requête: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $message_id, $id_professeur);
$result = $stmt->execute();

if ($result) {
    echo json_encode(['success' => true]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
