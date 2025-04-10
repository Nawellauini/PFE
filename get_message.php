<?php
session_start();
require 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_eleve'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

$id_eleve = $_SESSION['id_eleve'];

// Vérifier si l'ID du message est fourni
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de message invalide']);
    exit;
}

$message_id = intval($_POST['id']);

// Récupérer le message
$stmt = $conn->prepare("SELECT message_text FROM message_profeleve WHERE id = ? AND id_eleve = ?");
if ($stmt === false) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de préparation de la requête: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $message_id, $id_eleve);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Message non trouvé']);
    exit;
}

$message = $result->fetch_assoc();
$stmt->close();

// Vérifier si la colonne 'lu' existe dans la table
$check_column = $conn->query("SHOW COLUMNS FROM message_profeleve LIKE 'lu'");
if ($check_column->num_rows > 0) {
    // Marquer le message comme lu
    $stmt_update = $conn->prepare("UPDATE message_profeleve SET lu = 1 WHERE id = ? AND id_eleve = ?");
    if ($stmt_update !== false) {
        $stmt_update->bind_param("ii", $message_id, $id_eleve);
        $stmt_update->execute();
        $stmt_update->close();
    }
}

// Renvoyer le contenu du message
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => $message['message_text']]);
?>
