<?php

require_once('tcpdf/tcpdf.php'); // Inclure TCPDF
include 'db_config.php'; // Connexion à la base de données

// Vérifier si un ID de rapport est fourni
if (!isset($_GET['id'])) {
    header("Location: liste_rapports.php?message=معرف التقرير غير محدد&type=error");
    exit();
}

$rapport_id = intval($_GET['id']);

// Récupérer les informations du rapport
$query = "SELECT r.titre, r.date_creation, r.date_modification, c.nom_classe, 
                 p.nom AS prof_nom, p.prenom AS prof_prenom, 
                 i.nom AS insp_nom, i.prenom AS insp_prenom, 
                 r.commentaires, r.recommandations
          FROM rapports_inspection r
          JOIN classes c ON r.id_classe = c.id_classe
          JOIN professeurs p ON r.id_professeur = p.id_professeur
          JOIN inspecteurs i ON r.id_inspecteur = i.id_inspecteur
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $rapport_id);
$stmt->execute();
$result = $stmt->get_result();
$rapport = $result->fetch_assoc();

if (!$rapport) {
    header("Location: liste_rapports.php?message=التقرير غير موجود&type=error");
    exit();
}

// Récupérer les fichiers attachés
$query_fichiers = "SELECT nom_fichier, chemin_fichier, date_upload FROM fichiers_rapport WHERE rapport_id = ? ORDER BY date_upload DESC";
$stmt_fichiers = $conn->prepare($query_fichiers);
$stmt_fichiers->bind_param("i", $rapport_id);
$stmt_fichiers->execute();
$result_fichiers = $stmt_fichiers->get_result();
$fichiers = $result_fichiers->fetch_all(MYSQLI_ASSOC);

// Fonction pour formater la date
function formater_date($date_mysql) {
    $date = new DateTime($date_mysql);
    return $date->format('d/m/Y');
}

// Vérifier si on est en mode interface ou en mode génération PDF
$mode_interface = !isset($_GET['pdf']) && !isset($_GET['download']) && !isset($_GET['print']);

// Si on est en mode interface, afficher l'interface utilisateur
if ($mode_interface) {
    include 'rapport_interface.php';
    exit();
}

// Création d'une classe personnalisée qui étend TCPDF
class MYPDF extends TCPDF {
    // En-tête de page
    public function Header() {
        // Logo
        $image_file = 'uploads/photos_eleves/myschool.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 30, '', 'PNG', '', 'T', false, 300, 'R', false, false, 0, false, false, false);
        }
        
        // Titre
        $this->SetFont('aealarabiya', 'B', 18);
        $this->SetTextColor(25, 54, 127); // Bleu foncé professionnel
        $this->Cell(0, 15, 'نظام إدارة التقارير التربوية', 0, false, 'C', 0, '', 0, false, 'M', 'M');

        
        // Ligne de séparation
        $this->SetY(25);
        $this->SetDrawColor(25, 54, 127); // Bleu foncé professionnel
        $this->SetLineWidth(0.5);
        $this->Line(15, 25, 195, 25);
    }

    // Pied de page
    public function Footer() {
        // Position à 15 mm du bas
        $this->SetY(-15);
        // Police
        $this->SetFont('aealarabiya', 'I', 8);
        $this->SetTextColor(128, 128, 128); // Gris
        // Numéro de page
        $this->Cell(0, 10, 'الصفحة '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        // Date d'impression
        $this->Cell(0, 10, 'تاريخ الطباعة: '.date('d/m/Y'), 0, false, 'L', 0, '', 0, false, 'T', 'M');
        // Copyright
        $this->Cell(0, 10, '© '.date('Y').' نظام إدارة التقارير التربوية', 0, false, 'R', 0, '', 0, false, 'T', 'M');

    }
}

// Création du PDF
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Définir les informations du document
$pdf->SetCreator('نظام إدارة التقارير التربوية');
$pdf->SetAuthor('نظام إدارة المدرسة');
$pdf->SetTitle('تقرير التفقد: ' . $rapport['titre']);
$pdf->SetSubject('تقرير التفقد');
$pdf->SetKeywords('تقرير,  التفقد, مدرسة, تعليم');
// Définir les marges
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Définir la direction RTL pour l'arabe
$pdf->setRTL(true);

// Définir la police
$pdf->SetFont('aealarabiya', '', 12);

// Ajouter une page
$pdf->AddPage();

// Titre du rapport
$pdf->SetFont('aealarabiya', 'B', 16);
$pdf->SetTextColor(25, 54, 127); // Bleu foncé professionnel
$pdf->Cell(0, 10, 'تقرير التفقد: ' . $rapport['titre'], 0, 1, 'C');
$pdf->Ln(5);

// Informations générales dans un tableau
$pdf->SetFont('aealarabiya', 'B', 12);
$pdf->SetTextColor(255, 255, 255); // Texte blanc
$pdf->SetFillColor(25, 54, 127); // Fond bleu foncé
$pdf->Cell(0, 10, 'معلومات عامة', 1, 1, 'C', 1);

$pdf->SetFont('aealarabiya', '', 11);
$pdf->SetTextColor(0, 0, 0); // Texte noir
$pdf->SetFillColor(240, 240, 240); // Fond gris clair

// Tableau d'informations
$info_table = array(
    array('تاريخ الإنشاء:', formater_date($rapport['date_creation'])),
    array('تاريخ التعديل:', $rapport['date_modification'] ? formater_date($rapport['date_modification']) : 'غير متوفر'),
    array('القسم:', $rapport['nom_classe']),
    array('المعلم:', $rapport['prof_nom'] . ' ' . $rapport['prof_prenom']),
    array('المتفقد:', $rapport['insp_nom'] . ' ' . $rapport['insp_prenom']),
);

$fill = true;
foreach ($info_table as $row) {
    $pdf->SetFont('aealarabiya', 'B', 11);
    $pdf->Cell(40, 8, $row[0], 1, 0, 'R', $fill);
    $pdf->SetFont('aealarabiya', '', 11);
    $pdf->Cell(140, 8, $row[1], 1, 1, 'R', $fill);
    $fill = !$fill;
}

$pdf->Ln(5);

// Commentaires
$pdf->SetFont('aealarabiya', 'B', 12);
$pdf->SetTextColor(255, 255, 255); // Texte blanc
$pdf->SetFillColor(25, 54, 127); // Fond bleu foncé
$pdf->Cell(0, 10, 'التعليقات', 1, 1, 'C', 1);

$pdf->SetFont('aealarabiya', '', 11);
$pdf->SetTextColor(0, 0, 0); // Texte noir
$pdf->SetFillColor(255, 255, 255); // Fond blanc
$pdf->MultiCell(0, 10, $rapport['commentaires'], 1, 'R', 1);
$pdf->Ln(5);

// Recommandations
$pdf->SetFont('aealarabiya', 'B', 12);
$pdf->SetTextColor(255, 255, 255); // Texte blanc
$pdf->SetFillColor(25, 54, 127); // Fond bleu foncé
$pdf->Cell(0, 10, 'التوصيات', 1, 1, 'C', 1);

$pdf->SetFont('aealarabiya', '', 11);
$pdf->SetTextColor(0, 0, 0); // Texte noir
$pdf->SetFillColor(255, 255, 255); // Fond blanc
$pdf->MultiCell(0, 10, $rapport['recommandations'], 1, 'R', 1);
$pdf->Ln(5);

// Fichiers attachés
if (count($fichiers) > 0) {
    $pdf->SetFont('aealarabiya', 'B', 12);
    $pdf->SetTextColor(255, 255, 255); // Texte blanc
    $pdf->SetFillColor(25, 54, 127); // Fond bleu foncé
    $pdf->Cell(0, 10, 'الملفات المرفقة', 1, 1, 'C', 1);

    $pdf->SetFont('aealarabiya', '', 11);
    $pdf->SetTextColor(0, 0, 0); // Texte noir
    
    // En-tête du tableau des fichiers
    $pdf->SetFillColor(240, 240, 240); // Fond gris clair
    $pdf->SetFont('aealarabiya', 'B', 11);
    $pdf->Cell(120, 8, 'اسم الملف', 1, 0, 'C', 1);
    $pdf->Cell(60, 8, 'تاريخ الرفع', 1, 1, 'C', 1);
    
    // Contenu du tableau des fichiers
    $pdf->SetFont('aealarabiya', '', 10);
    $fill = false;
    foreach ($fichiers as $fichier) {
        $pdf->Cell(120, 8, $fichier['nom_fichier'], 1, 0, 'R', $fill);
        $pdf->Cell(60, 8, formater_date($fichier['date_upload']), 1, 1, 'C', $fill);
        $fill = !$fill;
    }
} else {
    $pdf->SetFont('aealarabiya', 'B', 12);
    $pdf->SetTextColor(255, 255, 255); // Texte blanc
    $pdf->SetFillColor(25, 54, 127); // Fond bleu foncé
    $pdf->Cell(0, 10, 'الملفات المرفقة', 1, 1, 'C', 1);
    
    $pdf->SetFont('aealarabiya', 'I', 11);
    $pdf->SetTextColor(0, 0, 0); // Texte noir
    $pdf->SetFillColor(255, 255, 255); // Fond blanc
    $pdf->Cell(0, 10, 'لا توجد ملفات مرفقة بهذا التقرير.', 1, 1, 'C', 1);
}

// Signature et date
$pdf->Ln(15);
$pdf->SetFont('aealarabiya', 'B', 11);
$pdf->Cell(90, 10, 'توقيع المتفقد:', 0, 0, 'R');
$pdf->Cell(90, 10, 'التاريخ: ' . date('d/m/Y'), 0, 1, 'L');

// Ligne pour signature
$pdf->SetDrawColor(25, 54, 127); // Bleu foncé professionnel
$pdf->SetLineWidth(0.2);
$pdf->Line(30, $pdf->GetY() + 10, 90, $pdf->GetY() + 10);

// Ajouter un filigrane
$pdf->SetFont('aealarabiya', 'B', 60);
$pdf->SetTextColor(230, 230, 230); // Gris très clair
$pdf->StartTransform();
$pdf->Rotate(45, 105, 150);
$pdf->Text(50, 150, 'نظام إدارة تقارير المتفقد');
$pdf->StopTransform();

// Déterminer le mode de sortie du PDF
if (isset($_GET['download'])) {
    // Mode téléchargement
    $pdf->Output('تقرير_المتفقد_' . $rapport_id . '.pdf', 'D');
} elseif (isset($_GET['print'])) {
    // Mode impression (ouvre le PDF et affiche la boîte de dialogue d'impression)
    $pdf->Output('تقرير_المتفقد_' . $rapport_id . '.pdf', 'I');
    echo '<script>window.print();</script>';
} else {
    // Mode affichage simple
    $pdf->Output('تقرير_المتفقد_' . $rapport_id . '.pdf', 'I');
}
?>

