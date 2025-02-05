<?php
// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=gestion_notes', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vérifier que des données ont été envoyées via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notes'])) {
    try {
        // Démarrer la transaction
        $pdo->beginTransaction();

        foreach ($_POST['notes'] as $eleve_id => $data) {
            $matiere_id = $_POST['matiere_id'] ?? null;
            $professeur_id = $_POST['professeur_id'] ?? null;
            $note1 = $data['note1'] ?? 0;
            $note2 = $data['note2'] ?? 0;
            $note3 = $data['note3'] ?? 0;

            if ($matiere_id && $professeur_id) {
                // Vérifier si l'entrée existe déjà pour cet élève et cette matière
                $query_check = "SELECT id FROM notes WHERE eleve_id = :eleve_id AND matiere_id = :matiere_id";
                $stmt_check = $pdo->prepare($query_check);
                $stmt_check->execute(['eleve_id' => $eleve_id, 'matiere_id' => $matiere_id]);

                if ($stmt_check->rowCount() > 0) {
                    // Mise à jour des notes
                    $query = "UPDATE notes SET note1 = :note1, note2 = :note2, note3 = :note3, professeur_id = :professeur_id 
                              WHERE eleve_id = :eleve_id AND matiere_id = :matiere_id";
                } else {
                    // Insérer une nouvelle entrée
                    $query = "INSERT INTO notes (eleve_id, matiere_id, professeur_id, note1, note2, note3) 
                              VALUES (:eleve_id, :matiere_id, :professeur_id, :note1, :note2, :note3)";
                }

                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':eleve_id' => $eleve_id,
                    ':matiere_id' => $matiere_id,
                    ':professeur_id' => $professeur_id,
                    ':note1' => $note1,
                    ':note2' => $note2,
                    ':note3' => $note3
                ]);
            }
        }

        // Commit de la transaction
        $pdo->commit();

        // Répondre avec un message JSON
        echo json_encode(['success' => true, 'message' => 'Les notes ont été enregistrées avec succès.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
}
?>
