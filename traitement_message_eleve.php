<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['id_eleve'])) {
    header("Location: login.php");
    exit;
}

$id_eleve = $_SESSION['id_eleve'];
$id_professeur = $_POST['id_professeur'];
$subject = $_POST['subject'];
$message_text = $_POST['message_text'];
$date_sent = date("Y-m-d H:i:s");

// Gérer la pièce jointe
$attachment_path = null;
if (!empty($_FILES['attachment']['name'])) {
    $upload_dir = "uploads/messages/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Vérifier le type de fichier
    $allowed_types = array(
        'application/pdf', 
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-zip-compressed'
    );
    
    if (!in_array($_FILES['attachment']['type'], $allowed_types) && !empty($_FILES['attachment']['type'])) {
        header("Location: envoyer_message_eleve.php?status=error&error=file_type");
        exit;
    }
    
    // Vérifier la taille du fichier (10MB max)
    if ($_FILES['attachment']['size'] > 10 * 1024 * 1024) {
        header("Location: envoyer_message_eleve.php?status=error&error=file_size");
        exit;
    }
    
    $filename = basename($_FILES["attachment"]["name"]);
    $target_file = $upload_dir . time() . "_" . $filename;

    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
        $attachment_path = $target_file;
    } else {
        header("Location: envoyer_message_eleve.php?status=error&error=upload_failed");
        exit;
    }
}

// Insérer dans message_eleve
$sql = "INSERT INTO message_eleve (id_professeur, id_eleve, recipient, subject, message_text, attachment_path, date_sent)
        VALUES (?, ?, '', ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    header("Location: envoyer_message_eleve.php?status=error&error=prepare_failed");
    exit;
}

$stmt->bind_param("iissss", $id_professeur, $id_eleve, $subject, $message_text, $attachment_path, $date_sent);

if ($stmt->execute()) {
    header("Location: envoyer_message_eleve.php?status=success");
} else {
    header("Location: envoyer_message_eleve.php?status=error&error=execute_failed");
}

$stmt->close();
$conn->close();
?>
