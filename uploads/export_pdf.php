<?php
session_start();
include 'db_config.php';
require 'vendor/autoload.php'; // Nécessite l'installation de TCPDF via Composer

// Vérifier si l'utilisateur est connecté en tant qu'inspecteur
if (!isset($_SESSION['id_inspecteur'])) {
    header("Location: login.php");
    exit();
}

$inspecteur_id = $_SESSION['id_inspecteur'];

// Utilisation de TCPDF pour générer le PDF
use TCPDF as TCPDF;

class MYPDF extends TCPDF {
    // En-tête de page
    public function Header() {
        // Logo
        $image_file = 'uploads/photos_eleves/myschool.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        // Titre
        $this->SetFont('helvetica', 'B', 16);
        $this->SetY(15);
        $this->Cell(0, 15, 'Rapport d\'Inspection - MySchool', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        // Ligne
        $this->Line(10, 30, $this->getPageWidth() - 10, 30);
    }

    // Pied de page
    public function Footer() {
        // Position à 15 mm du bas
        $this->SetY(-15);
        // Police
        $this->SetFont('helvetica', 'I', 8);
        // Numéro de page
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Créer un nouveau document PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Définir les informations du document
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('MySchool');
$pdf->SetTitle('Rapport d\'Inspection');
$pdf->SetSubject('Rapport d\'Inspection');
$pdf->SetKeywords('Rapport, Inspection, MySchool');

// Définir les marges
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP + 10, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Définir les sauts de page automatiques
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Définir le facteur d'échelle de l'image
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Définir la police
$pdf->SetFont('helvetica', '', 10);

// Exporter un rapport spécifique
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $rapport_id = $_GET['id'];
    
    // Vérifier que le rapport appartient à l'inspecteur
    $check_query = "SELECT r.*, c.nom_classe, i.nom AS inspecteur_nom, i.prenom AS inspecteur_prenom 
                   FROM rapports_inspection r 
                   LEFT JOIN classes c ON r.id_classe = c.id_classe 
                   LEFT JOIN inspecteurs i ON r.id_inspecteur = i.id
                   WHERE r.id = ? AND r.id_inspecteur = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $rapport_id, $inspecteur_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $rapport = $check_result->fetch_assoc();
        
        // Récupérer les fichiers attachés
        $files_query = "SELECT * FROM fichiers_rapport WHERE rapport_id = ?";
        $files_stmt = $conn->prepare($files_query);
        $files_stmt->bind_param("i", $rapport_id);
        $files_stmt->execute();
        $files_result = $files_stmt->get_result();
        $files = [];
        
        while ($file = $files_result->fetch_assoc()) {
            $files[] = $file;
        }
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Contenu du rapport
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Rapport d\'inspection: ' . $rapport['titre'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Ln(5);
        
        // Informations générales
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 10, 'Informations générales', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Cell(40, 7, 'Inspecteur:', 0, 0, 'L');
        $pdf->Cell(0, 7, $rapport['inspecteur_nom'] . ' ' . $rapport['inspecteur_prenom'], 0, 1, 'L');
        
        $pdf->Cell(40, 7, 'Classe:', 0, 0, 'L');
        $pdf->Cell(0, 7, $rapport['nom_classe'] ? $rapport['nom_classe'] : 'Non spécifiée', 0, 1, 'L');
        
        $pdf->Cell(40, 7, 'Date de création:', 0, 0, 'L');
        $pdf->Cell(0, 7, date('d/m/Y H:i', strtotime($rapport['date_creation'])), 0, 1, 'L');
        
        $pdf->Cell(40, 7, 'Statut:', 0, 0, 'L');
        $pdf->Cell(0, 7, $rapport['statut'], 0, 1, 'L');
        
        $pdf->Ln(5);
        
        // Commentaires et recommandations
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 10, 'Commentaires et recommandations', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->writeHTML(nl2br($rapport['commentaires']), true, false, true, false, '');
        
        $pdf->Ln(5);
        
        // Fichiers attachés
        if (!empty($files)) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 10, 'Fichiers attachés', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            foreach ($files as $index => $file) {
                $pdf->Cell(0, 7, ($index + 1) . '. ' . $file['nom_fichier'], 0, 1, 'L');
            }
            
            // Ajouter des images si ce sont des images
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 10, 'Aperçu des images', 0, 1, 'L');
            
            $image_count = 0;
            foreach ($files as $file) {
                if (strpos($file['type_fichier'], 'image/') === 0 && file_exists($file['chemin_fichier'])) {
                    $image_count++;
                    $pdf->Ln(5);
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(0, 7, 'Image ' . $image_count . ': ' . $file['nom_fichier'], 0, 1, 'L');
                    $pdf->Image($file['chemin_fichier'], '', '', 100, 0, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
                    $pdf->Ln(5);
                }
            }
        }
        
        // Générer le PDF
        $pdf->Output('rapport_inspection_' . $rapport_id . '.pdf', 'I');
    } else {
        echo "Vous n'avez pas l'autorisation d'accéder à ce rapport.";
    }
}
// Exporter tous les rapports
elseif (isset($_GET['export_all'])) {
    // Récupérer tous les rapports de l'inspecteur
    $query = "SELECT r.*, c.nom_classe 
             FROM rapports_inspection r 
             LEFT JOIN classes c ON r.id_classe = c.id_classe 
             WHERE r.id_inspecteur = ? 
             ORDER BY r.date_creation DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $inspecteur_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Ajouter une page
        $pdf->AddPage();
        
        // Titre
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Liste des rapports d\'inspection', 0, 1, 'C');
        $pdf->Ln(5);
        
        // En-tête du tableau
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(15, 7, 'ID', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Date', 1, 0, 'C');
        $pdf->Cell(40, 7, 'Classe', 1, 0, 'C');
        $pdf->Cell(70, 7, 'Titre', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Statut', 1, 1, 'C');
        
        // Contenu du tableau
        $pdf->SetFont('helvetica', '', 9);
        
        while ($rapport = $result->fetch_assoc()) {
            $pdf->Cell(15, 7, $rapport['id'], 1, 0, 'C');
            $pdf->Cell(30, 7, date('d/m/Y', strtotime($rapport['date_creation'])), 1, 0, 'C');
            $pdf->Cell(40, 7, $rapport['nom_classe'] ? $rapport['nom_classe'] : 'N/A', 1, 0, 'L');
            $pdf->Cell(70, 7, $rapport['titre'], 1, 0, 'L');
            $pdf->Cell(30, 7, $rapport['statut'], 1, 1, 'C');
        }
        
        // Générer le PDF
        $pdf->Output('tous_rapports_inspection.pdf', 'I');
    } else {
        echo "Aucun rapport trouvé.";
    }
} else {
    echo "Paramètre manquant.";
}