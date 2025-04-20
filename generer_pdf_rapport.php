<?php
session_start();
require_once 'config.php';
require_once('tcpdf/tcpdf.php');

if (!isset($_SESSION['id_professeur']) || !isset($_GET['id'])) {
    die('Non autorisé');
}

$id = intval($_GET['id']);

// Récupérer les informations du rapport
$query = "SELECT r.*, c.nom_classe, p.nom as nom_professeur, p.prenom as prenom_professeur 
          FROM rapports r 
          JOIN classes c ON r.id_classe = c.id_classe 
          JOIN professeurs p ON r.id_professeur = p.id_professeur 
          WHERE r.id_rapport = ? AND r.id_professeur = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Erreur de préparation de la requête: ' . $conn->error);
}
$stmt->bind_param("ii", $id, $_SESSION['id_professeur']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Rapport non trouvé');
}

$rapport = $result->fetch_assoc();

// Créer un nouveau document PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Définir les informations du document
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('نظام إدارة التقارير');
$pdf->SetTitle('تقرير التفتيش');

// Supprimer l'en-tête et le pied de page par défaut
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Définir les marges
$pdf->SetMargins(15, 15, 15);

// Ajouter une page
$pdf->AddPage();

// Définir la police
$pdf->SetFont('dejavusans', '', 12);

// Titre
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'تقرير التفتيش', 0, 1, 'C');
$pdf->Ln(10);

// Informations du rapport
$pdf->SetFont('dejavusans', '', 12);
$pdf->Cell(40, 10, 'العنوان:', 0, 0, 'R');
$pdf->Cell(0, 10, $rapport['titre'], 0, 1, 'R');
$pdf->Cell(40, 10, 'الصف:', 0, 0, 'R');
$pdf->Cell(0, 10, $rapport['nom_classe'], 0, 1, 'R');
$pdf->Cell(40, 10, 'المفتش:', 0, 0, 'R');
$pdf->Cell(0, 10, $rapport['prenom_professeur'] . ' ' . $rapport['nom_professeur'], 0, 1, 'R');
$pdf->Cell(40, 10, 'التاريخ:', 0, 0, 'R');
$pdf->Cell(0, 10, date('Y/m/d', strtotime($rapport['date_creation'])), 0, 1, 'R');
$pdf->Ln(10);

// Commentaires
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->Cell(0, 10, 'التعليقات:', 0, 1, 'R');
$pdf->SetFont('dejavusans', '', 12);
$pdf->MultiCell(0, 10, $rapport['commentaires'], 0, 'R');
$pdf->Ln(10);

// Recommandations
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->Cell(0, 10, 'التوصيات:', 0, 1, 'R');
$pdf->SetFont('dejavusans', '', 12);
$pdf->MultiCell(0, 10, $rapport['recommandations'], 0, 'R');
$pdf->Ln(20);

// Signature
$pdf->Cell(0, 10, 'توقيع المفتش: ___________________', 0, 1, 'L');

// Générer le nom du fichier
$filename = 'rapport_' . $id . '_' . date('Y-m-d') . '.pdf';

// Envoyer le PDF au navigateur
$pdf->Output($filename, 'D');
?> 