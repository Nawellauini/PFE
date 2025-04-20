<?php

session_start();
include 'db_config.php';

if (!isset($_SESSION['id_professeur'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if (isset($_POST['remarques']) && isset($_POST['trimestre'])) {
    $remarques = json_decode($_POST['remarques'], true);
    $trimestre = intval($_POST['trimestre']);
    
    $success = true;
    $message = 'Remarques enregistrées avec succès !';
    
    // Vérifier si la colonne trimestre existe dans la table remarques
    $result_check_column = $conn->query("SHOW COLUMNS FROM remarques LIKE 'trimestre'");
    $trimestre_exists = $result_check_column->num_rows > 0;
    
    $conn->begin_transaction();
    
    try {
        foreach ($remarques as $r) {
            if (empty($r['remarque'])) continue; // Ignorer les remarques vides
            
            if ($trimestre_exists) {
                // Si la colonne trimestre existe
                $query = "INSERT INTO remarques (eleve_id, domaine_id, remarque, date_remarque, trimestre) 
                          VALUES (?, ?, ?, NOW(), ?) 
                          ON DUPLICATE KEY UPDATE remarque = ?, date_remarque = NOW()";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iisisi", $r['eleve_id'], $r['domaine_id'], $r['remarque'], $trimestre, $r['remarque'], $trimestre);
            } else {
                // Si la colonne trimestre n'existe pas
                $query = "INSERT INTO remarques (eleve_id, domaine_id, remarque, date_remarque) 
                          VALUES (?, ?, ?, NOW()) 
                          ON DUPLICATE KEY UPDATE remarque = ?, date_remarque = NOW()";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiss", $r['eleve_id'], $r['domaine_id'], $r['remarque'], $r['remarque']);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de l'enregistrement de la remarque: " . $stmt->error);
            }
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $success = false;
        $message = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
}
?>

