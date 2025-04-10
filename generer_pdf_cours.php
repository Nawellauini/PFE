<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit();
}

// Vérifier si l'ID du cours est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Rediriger vers la liste des cours
    header("Location: liste_cours.php?error=معرف الدرس غير صالح");
    exit();
}

// Inclure le fichier de configuration de la base de données
include 'db_config.php';

// Récupérer l'ID du cours
$id_cours = intval($_GET['id']);

// Récupérer les informations du cours
$sql = "SELECT c.*, p.nom as nom_prof, p.prenom as prenom_prof, cl.nom_classe, t.nom_theme, m.nom as nom_matiere 
        FROM cours c 
        JOIN professeurs p ON c.id_professeur = p.id_professeur 
        JOIN classes cl ON c.id_classe = cl.id_classe 
        JOIN themes t ON c.id_theme = t.id_theme 
        LEFT JOIN matieres m ON c.matiere_id = m.matiere_id 
        WHERE c.id_cours = $id_cours";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Le cours n'existe pas
    header("Location: liste_cours.php?error=الدرس غير موجود");
    exit();
}

$cours = $result->fetch_assoc();

// Inclure la bibliothèque TCPDF
require_once('tcpdf/tcpdf.php');

// Créer une nouvelle instance de TCPDF
class MYPDF extends TCPDF {
    // En-tête de page
    public function Header() {
        // Logo
        $image_file = 'assets/logo.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 30, '', 'PNG', '', 'T', false, 300, 'R', false, false, 0, false, false, false);
        }
        
        // Titre
        $this->SetFont('aealarabiya', 'B', 20);
        $this->SetY(15);
        $this->Cell(0, 15, 'منصة التعليم الإلكتروني', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        // Ligne
        $this->SetY(30);
        $this->SetDrawColor(0, 102, 204);
        $this->SetLineWidth(0.5);
        $this->Line(15, 30, $this->getPageWidth() - 15, 30);
    }

    // Pied de page
    public function Footer() {
        // Position à 15 mm du bas
        $this->SetY(-15);
        // Police
        $this->SetFont('aealarabiya', '', 8);
        // Numéro de page
        $this->Cell(0, 10, 'الصفحة '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Créer un nouveau document PDF
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Définir les informations du document
$pdf->SetCreator('منصة التعليم الإلكتروني');
$pdf->SetAuthor($cours['prenom_prof'] . ' ' . $cours['nom_prof']);
$pdf->SetTitle($cours['titre']);
$pdf->SetSubject($cours['nom_theme']);
$pdf->SetKeywords('درس, تعليم, ' . $cours['nom_theme'] . ', ' . $cours['nom_classe']);

// Définir les marges
$pdf->SetMargins(15, 35, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Définir l'auto-saut de page
$pdf->SetAutoPageBreak(TRUE, 15);

// Ajouter une page
$pdf->AddPage();

// Définir la police
$pdf->SetFont('aealarabiya', 'B', 16);

// Titre du cours
$pdf->Cell(0, 10, $cours['titre'], 0, 1, 'C');

// Informations du cours
$pdf->SetFont('aealarabiya', '', 12);
$pdf->Ln(5);

// Tableau d'informations
$pdf->SetFillColor(240, 240, 240);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.1);

// Professeur
$pdf->Cell(40, 10, 'المعلم:', 1, 0, 'R', 1);
$pdf->Cell(135, 10, $cours['prenom_prof'] . ' ' . $cours['nom_prof'], 1, 1, 'R');

// Classe
$pdf->Cell(40, 10, 'القسم:', 1, 0, 'R', 1);
$pdf->Cell(135, 10, $cours['nom_classe'], 1, 1, 'R');

// Thème
$pdf->Cell(40, 10, 'الموضوع:', 1, 0, 'R', 1);
$pdf->Cell(135, 10, $cours['nom_theme'], 1, 1, 'R');

// Matière
$pdf->Cell(40, 10, 'المادة:', 1, 0, 'R', 1);
$pdf->Cell(135, 10, $cours['nom_matiere'] ?? 'غير محدد', 1, 1, 'R');

// Date de création
$pdf->Cell(40, 10, 'تاريخ الإنشاء:', 1, 0, 'R', 1);
$pdf->Cell(135, 10, date('d/m/Y', strtotime($cours['date_creation'])), 1, 1, 'R');

// Description du cours
$pdf->Ln(10);
$pdf->SetFont('aealarabiya', 'B', 14);
$pdf->Cell(0, 10, 'وصف الدرس:', 0, 1, 'R');
$pdf->SetFont('aealarabiya', '', 12);

// Traiter le texte de la description
$description = $cours['description'];
$pdf->writeHTML('<div style="text-align: right;">' . nl2br($description) . '</div>', true, false, true, false, '');

// Ajouter l'image si elle existe
if (!empty($cours['illustration']) && file_exists($cours['illustration'])) {
    $pdf->Ln(10);
    $pdf->SetFont('aealarabiya', 'B', 14);
    $pdf->Cell(0, 10, 'الصورة التوضيحية:', 0, 1, 'R');
    $pdf->Image($cours['illustration'], null, null, 150, 0, '', '', 'C');
}

// Générer le PDF
$pdf->Output('cours_' . $id_cours . '.pdf', 'I');

// Fermer la connexion
$conn->close();
?>