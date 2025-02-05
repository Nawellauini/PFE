<?php
// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=gestion_notes', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['note'])) {
    // On récupère les données envoyées via le formulaire (notes)
    foreach ($_POST['note'] as $eleve_id => $matieres) {
        foreach ($matieres as $matiere_id => $notes) {
            // Les 3 notes de l'élève pour une matière
            $note1 = isset($notes['note1']) ? $notes['note1'] : 0;
            $note2 = isset($notes['note2']) ? $notes['note2'] : 0;
            $note3 = isset($notes['note3']) ? $notes['note3'] : 0;

            // Vérifier si des notes existent déjà pour cet élève et cette matière
            $query_check = "SELECT * FROM notes WHERE eleve_id = :eleve_id AND matiere_id = :matiere_id";
            $stmt_check = $pdo->prepare($query_check);
            $stmt_check->execute(['eleve_id' => $eleve_id, 'matiere_id' => $matiere_id]);

            if ($stmt_check->rowCount() > 0) {
                // Mise à jour des notes si elles existent déjà
                $query = "UPDATE notes SET note1 = :note1, note2 = :note2, note3 = :note3 
                          WHERE eleve_id = :eleve_id AND matiere_id = :matiere_id";
                $stmt = $pdo->prepare($query);
                $stmt->execute([ 
                    ':note1' => $note1,
                    ':note2' => $note2,
                    ':note3' => $note3,
                    ':eleve_id' => $eleve_id,
                    ':matiere_id' => $matiere_id
                ]);
            } else {
                // Insérer les nouvelles notes si elles n'existent pas encore
                $query = "INSERT INTO notes (eleve_id, matiere_id, note1, note2, note3) 
                          VALUES (:eleve_id, :matiere_id, :note1, :note2, :note3)";
                $stmt = $pdo->prepare($query);
                $stmt->execute([ 
                    ':eleve_id' => $eleve_id,
                    ':matiere_id' => $matiere_id,
                    ':note1' => $note1,
                    ':note2' => $note2,
                    ':note3' => $note3
                ]);
            }
        }
    }

    // Confirmation de l'enregistrement des notes
    echo "Les notes ont été enregistrées avec succès.";
} else {
    echo "Aucune donnée à enregistrer.";
}
?>
