<?php
require_once('tcpdf/tcpdf.php'); // Inclure TCPDF

// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=gestion_notes;charset=utf8', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
]);

// Vérifier que les paramètres sont présents
if (isset($_GET['eleve_id']) && isset($_GET['classe_id'])) {
    $eleve_id = $_GET['eleve_id'];
    $classe_id = $_GET['classe_id'];

    // Récupérer les informations de l'élève
    $query_eleves = "SELECT nom FROM eleves WHERE id = :eleve_id";
    $stmt_eleves = $pdo->prepare($query_eleves);
    $stmt_eleves->execute(['eleve_id' => $eleve_id]);
    $eleve = $stmt_eleves->fetch(PDO::FETCH_ASSOC);

    // Récupérer le nom de la classe
    $query_classes = "SELECT nom FROM classes WHERE id = :classe_id";
    $stmt_classes = $pdo->prepare($query_classes);
    $stmt_classes->execute(['classe_id' => $classe_id]);
    $classe = $stmt_classes->fetch(PDO::FETCH_ASSOC);

    // Récupérer les notes de l'élève dans toutes les matières de la classe
    $query_notes = "
        SELECT m.nom AS matiere_nom, n.note1, n.note2, n.note3
        FROM notes n
        JOIN matieres m ON n.matiere_id = m.id
        WHERE n.eleve_id = :eleve_id AND m.classe_id = :classe_id";
    
    $stmt_notes = $pdo->prepare($query_notes);
    $stmt_notes->execute(['eleve_id' => $eleve_id, 'classe_id' => $classe_id]);
    $notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);

    // Créer le PDF
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Gestion Notes');
    $pdf->SetTitle('Rapport des Notes de l\'Élève');
    $pdf->SetSubject('Notes de l\'élève');

    $pdf->AddPage();

    // **Utiliser une police qui supporte l'arabe et UTF-8**
    $pdf->SetFont('dejavusans', '', 12);

    // **Titre**
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->SetTextColor(0, 0, 128);
    $pdf->Cell(0, 10, 'Rapport des Notes de l\'Élève', 0, 1, 'C');

    // **Informations sur l'élève**
    $pdf->SetFont('dejavusans', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'Nom de l\'Élève : ' . $eleve['nom'], 0, 1, 'C');
    $pdf->Cell(0, 10, 'Classe : ' . $classe['nom'], 0, 1, 'C');
    $pdf->Ln(10);

    // **Ajouter la date d'exportation**
    $pdf->Cell(0, 10, 'Date d\'exportation : ' . date('d-m-Y'), 0, 1, 'L');
    $pdf->Ln(10);

    // **Tableau des notes**
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetFillColor(200, 220, 255); // Bleu clair pour les en-têtes
    $pdf->Cell(70, 10, 'Matière', 1, 0, 'C', 1);
    $pdf->Cell(30, 10, 'Note 1', 1, 0, 'C', 1);
    $pdf->Cell(30, 10, 'Note 2', 1, 0, 'C', 1);
    $pdf->Cell(30, 10, 'Note 3', 1, 1, 'C', 1);
    $pdf->SetFont('dejavusans', '', 12);

    // **Remplissage du tableau avec les notes**
    foreach ($notes as $index => $note) {
        $pdf->SetFillColor(($index % 2 == 0) ? 245 : 255, ($index % 2 == 0) ? 245 : 255, ($index % 2 == 0) ? 245 : 255);
        $pdf->Cell(70, 10, $note['matiere_nom'], 1, 0, 'C', 1);
        $pdf->Cell(30, 10, $note['note1'], 1, 0, 'C', 1);
        $pdf->Cell(30, 10, $note['note2'], 1, 0, 'C', 1);
        $pdf->Cell(30, 10, $note['note3'], 1, 1, 'C', 1);
    }

    // **Générer et afficher le PDF**
    $pdf->Output('notes_eleve_' . $eleve['nom'] . '.pdf', 'I');
} else {
    echo "Données manquantes";
}
?>
