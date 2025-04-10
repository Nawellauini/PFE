<?php
include 'db_config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Démarrer la session pour accéder aux variables de session
session_start();

// CORRECTION: Vérifier si l'utilisateur est connecté en tant que professeur OU élève
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'eleve' && !isset($_SESSION['id_professeur']))) {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID de l'élève depuis l'URL
$eleve_id = isset($_GET['eleve_id']) ? $_GET['eleve_id'] : null;

// Si l'ID de l'élève n'est pas fourni et que l'utilisateur est un élève, utiliser son propre ID
if (!$eleve_id && isset($_SESSION['id_eleve'])) {
    $eleve_id = $_SESSION['id_eleve'];
}

// Si aucun ID d'élève n'est disponible, rediriger vers la page de sélection
if (!$eleve_id) {
    header('Location: selection_classe.php');
    exit;
}

// Récupérer l'ID de la classe depuis l'URL
$classe_id = isset($_GET['classe_id']) ? $_GET['classe_id'] : null;

// Si l'ID de classe n'est pas fourni, le récupérer depuis la base de données
if (!$classe_id) {
    try {
        $query_classe = "SELECT id_classe FROM eleves WHERE id_eleve = ?";
        $stmt_classe = $conn->prepare($query_classe);
        $stmt_classe->bind_param("i", $eleve_id);
        $stmt_classe->execute();
        $result_classe = $stmt_classe->get_result();
        
        if ($result_classe->num_rows > 0) {
            $row_classe = $result_classe->fetch_assoc();
            $classe_id = $row_classe['id_classe'];
        } else {
            die("Erreur: Élève non trouvé dans la base de données.");
        }
    } catch (Exception $e) {
        die("Erreur lors de la récupération de la classe: " . $e->getMessage());
    }
}

// Vérifier que l'ID de classe est valide
if (!$classe_id) {
    die("Erreur: Impossible de déterminer la classe de l'élève.");
}

// Par défaut, utiliser le premier trimestre si non spécifié
$trimestre = isset($_GET['trimestre']) ? $_GET['trimestre'] : 1;

try {
  // Vérifier que l'élève existe et appartient à la classe
  $check_query = "SELECT e.id_eleve FROM eleves e WHERE e.id_eleve = ? AND e.id_classe = ?";
  $check_stmt = $conn->prepare($check_query);
  $check_stmt->bind_param("ii", $eleve_id, $classe_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();
  
  if ($check_result->num_rows === 0) {
      die("Erreur: L'élève spécifié n'appartient pas à cette classe ou n'existe pas.");
  }

  // Le reste du code reste inchangé...

  // Vérifier si la colonne trimestre existe dans la table remarques
  $result_check_column = $conn->query("SHOW COLUMNS FROM remarques LIKE 'trimestre'");
  $trimestre_exists = $result_check_column->num_rows > 0;

  // Récupération des informations de l'élève et de la classe
  $query = "SELECT c.nom_classe, e.nom, e.prenom 
            FROM classes c 
            JOIN eleves e ON e.id_classe = c.id_classe 
            WHERE c.id_classe = ? AND e.id_eleve = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ii", $classe_id, $eleve_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows === 0) {
      die("Élève ou classe non trouvé");
  }
  
  $info = $result->fetch_assoc();
  $nom_classe = $info['nom_classe'];
  $nom_eleve = $info['nom'];
  $prenom_eleve = $info['prenom'];
  
  // Déterminer le niveau à partir du nom de la classe
  $niveau = 0;
  if (strpos($nom_classe, 'الأولى') !== false || strpos($nom_classe, 'اولى') !== false) {
      $niveau = 1;
  } elseif (strpos($nom_classe, 'الثانية') !== false || strpos($nom_classe, 'ثانية') !== false) {
      $niveau = 2;
  } elseif (strpos($nom_classe, 'الثالثة') !== false || strpos($nom_classe, 'ثالثة') !== false) {
      $niveau = 3;
  } elseif (strpos($nom_classe, 'الرابعة') !== false || strpos($nom_classe, 'رابعة') !== false) {
      $niveau = 4;
  } elseif (strpos($nom_classe, 'الخامسة') !== false || strpos($nom_classe, 'خامسة') !== false) {
      $niveau = 5;
  } elseif (strpos($nom_classe, 'السادسة') !== false || strpos($nom_classe, 'سادسة') !== false) {
      $niveau = 6;
  }

  // Récupérer le nom du trimestre
  $trimestre_nom = "";
  switch($trimestre) {
      case 1:
          $trimestre_nom = "الثلاثي الأول";
          break;
      case 2:
          $trimestre_nom = "الثلاثي الثاني";
          break;
      case 3:
          $trimestre_nom = "الثلاثي الثالث";
          break;
  }

  // Définir les matières par domaine selon le niveau
  function getMatieresParNiveau($niveau) {
      $matieres = [
          // Niveau 1-2
          '1-2' => [
              'مجال اللغة العربية' => [
                  'التواصل الشفوي والمحفوظات',
                  'القراءة',
                  'الخط والإملاء',
                  'الإنتاج الكتابي'
              ],
              'مجال العلوم والتكنولوجيا' => [
                  'الرياضيات',
                  'الإيقاظ العلمي',
                  'التربية التكنولوجية'
              ],
              'مجال التنشئة' => [
                  'التربية الإسلامية',
                  'التربية التشكيلية',
                  'التربية الموسيقية',
                  'التربية البدنية'
              ]
          ],
          // Niveau 3-4
          '3-4' => [
              'مجال اللغة العربية' => [
                  'التواصل الشفوي والمحفوظات',
                  'القراءة',
                  'قواعد اللغة',
                  'الإنتاج الكتابي'
              ],
              'مجال العلوم والتكنولوجيا' => [
                  'الرياضيات',
                  'الإيقاظ العلمي',
                  'التربية التكنولوجية'
              ],
              'مجال التنشئة' => [
                  'التربية الإسلامية',
                  'التربية التشكيلية',
                  'التربية الموسيقية',
                  'التربية البدنية'
              ],
              'مجال اللغات الأجنبية' => [
                  'Exp. orale et récitation',
                  'Lecture',
                  'Prod. écrite, écriture et dictée'
              ]
          ],
          // Niveau 5-6
          '5-6' => [
              'مجال اللغة العربية' => [
                  'التواصل الشفوي والمحفوظات',
                  'القراءة',
                  'قواعد اللغة',
                  'الإنتاج الكتابي'
              ],
              'مجال العلوم والتكنولوجيا' => [
                  'الرياضيات',
                  'الإيقاظ العلمي',
                  'التربية التكنولوجية'
              ],
              'مجال التنشئة' => [
                  'التربية الإسلامية',
                  'التاريخ',
                  'الجغرافيا',
                  'التربية المدنية',
                  'التربية التشكيلية',
                  'التربية الموسيقية',
                  'التربية البدنية'
              ],
              'مجال اللغات الأجنبية' => [
                  'اللغة الفرنسية' => [
                      'Exp. orale et récitation',
                      'Lecture',
                      'Prod. écrite, langue et dictée'
                  ],
                  'اللغة الإنجليزية' => [
                      'Speaking and project work',
                      'Listening/reading language/writing'
                  ]
              ]
          ]
      ];

      if ($niveau <= 2) return $matieres['1-2'];
      if ($niveau <= 4) return $matieres['3-4'];
      return $matieres['5-6'];
  }

  // Récupérer la structure des matières pour ce niveau
  $matieres_structure = getMatieresParNiveau($niveau);
  
  // Définir l'ordre des domaines pour l'affichage
  $ordre_domaines = array_keys($matieres_structure);

  // Requête pour les matières et notes - Modifiée pour gérer l'absence de la colonne trimestre
  if ($trimestre_exists) {
      $query = "
      SELECT 
          m.matiere_id,
          m.nom AS matiere_nom,
          d.id AS domaine_id,
          d.nom AS domaine_nom,
          n.note,
          r.remarque,
          (SELECT MAX(note) FROM notes WHERE matiere_id = m.matiere_id AND trimestre = ?) as max_note,
          (SELECT MIN(note) FROM notes WHERE matiere_id = m.matiere_id AND trimestre = ?) as min_note
      FROM matieres m
      LEFT JOIN domaines d ON m.domaine_id = d.id
      LEFT JOIN notes n ON n.matiere_id = m.matiere_id AND n.id_eleve = ? AND n.trimestre = ?
      LEFT JOIN remarques r ON r.domaine_id = d.id AND r.eleve_id = ? AND r.trimestre = ?
      WHERE m.classe_id = ?";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("iiiiiii", $trimestre, $trimestre, $eleve_id, $trimestre, $eleve_id, $trimestre, $classe_id);
  } else {
      // Si la colonne trimestre n'existe pas dans la table remarques
      $query = "
      SELECT 
          m.matiere_id,
          m.nom AS matiere_nom,
          d.id AS domaine_id,
          d.nom AS domaine_nom,
          n.note,
          r.remarque,
          (SELECT MAX(note) FROM notes WHERE matiere_id = m.matiere_id AND trimestre = ?) as max_note,
          (SELECT MIN(note) FROM notes WHERE matiere_id = m.matiere_id AND trimestre = ?) as min_note
      FROM matieres m
      LEFT JOIN domaines d ON m.domaine_id = d.id
      LEFT JOIN notes n ON n.matiere_id = m.matiere_id AND n.id_eleve = ? AND n.trimestre = ?
      LEFT JOIN remarques r ON r.domaine_id = d.id AND r.eleve_id = ?
      WHERE m.classe_id = ?";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("iiiiii", $trimestre, $trimestre, $eleve_id, $trimestre, $eleve_id, $classe_id);
  }
  
  $stmt->execute();
  $result = $stmt->get_result();

  // Récupérer toutes les matières de la base de données
  $all_matieres = [];
  while ($row = $result->fetch_assoc()) {
      $all_matieres[] = $row;
  }

  // Organiser les données par domaine et matière selon l'ordre défini
  $matieres_par_domaine = [];
  $remarques = [];
  $moyennes_domaines = [];

  // Parcourir les domaines dans l'ordre défini
  foreach ($ordre_domaines as $domaine_nom) {
      $matieres_par_domaine[$domaine_nom] = [];
      
      // Cas spécial pour les langues étrangères au niveau 5-6
      if ($domaine_nom === 'مجال اللغات الأجنبية' && $niveau >= 5) {
          // Traiter d'abord les matières de français
          if (isset($matieres_structure[$domaine_nom]['اللغة الفرنسية'])) {
              foreach ($matieres_structure[$domaine_nom]['اللغة الفرنسية'] as $nom_matiere) {
                  foreach ($all_matieres as $matiere) {
                      if ($matiere['domaine_nom'] === $domaine_nom && $matiere['matiere_nom'] === $nom_matiere) {
                          $matieres_par_domaine[$domaine_nom][] = $matiere;
                          if (!isset($remarques[$matiere['domaine_id']])) {
                              $remarques[$matiere['domaine_id']] = $matiere['remarque'] ?? "لا يوجد ملاحظة";
                          }
                      }
                  }
              }
          }
          
          // Ensuite les matières d'anglais
          if (isset($matieres_structure[$domaine_nom]['اللغة الإنجليزية'])) {
              foreach ($matieres_structure[$domaine_nom]['اللغة الإنجليزية'] as $nom_matiere) {
                  foreach ($all_matieres as $matiere) {
                      if ($matiere['domaine_nom'] === $domaine_nom && $matiere['matiere_nom'] === $nom_matiere) {
                          $matieres_par_domaine[$domaine_nom][] = $matiere;
                          if (!isset($remarques[$matiere['domaine_id']])) {
                              $remarques[$matiere['domaine_id']] = $matiere['remarque'] ?? "لا يوجد ملاحظة";
                          }
                      }
                  }
              }
          }
      } 
      // Pour les autres domaines
      else {
          foreach ($matieres_structure[$domaine_nom] as $nom_matiere) {
              foreach ($all_matieres as $matiere) {
                  if ($matiere['domaine_nom'] === $domaine_nom && $matiere['matiere_nom'] === $nom_matiere) {
                      $matieres_par_domaine[$domaine_nom][] = $matiere;
                      if (!isset($remarques[$matiere['domaine_id']])) {
                          $remarques[$matiere['domaine_id']] = $matiere['remarque'] ?? "لا يوجد ملاحظة";
                      }
                  }
              }
          }
      }
      
      // Ajouter les matières qui n'ont pas été trouvées dans la structure
      foreach ($all_matieres as $matiere) {
          if ($matiere['domaine_nom'] === $domaine_nom) {
              $found = false;
              foreach ($matieres_par_domaine[$domaine_nom] as $existing_matiere) {
                  if ($existing_matiere['matiere_id'] === $matiere['matiere_id']) {
                      $found = true;
                      break;
                  }
              }
              if (!$found) {
                  $matieres_par_domaine[$domaine_nom][] = $matiere;
                  if (!isset($remarques[$matiere['domaine_id']])) {
                      $remarques[$matiere['domaine_id']] = $matiere['remarque'] ?? "لا يوجد ملاحظة";
                  }
              }
          }
      }
  }

  // Ajouter les domaines qui ne sont pas dans l'ordre défini
  foreach ($all_matieres as $matiere) {
      if (!in_array($matiere['domaine_nom'], $ordre_domaines)) {
          if (!isset($matieres_par_domaine[$matiere['domaine_nom']])) {
              $matieres_par_domaine[$matiere['domaine_nom']] = [];
          }
          $matieres_par_domaine[$matiere['domaine_nom']][] = $matiere;
          if (!isset($remarques[$matiere['domaine_id']])) {
              $remarques[$matiere['domaine_id']] = $matiere['remarque'] ?? "لا يوجد ملاحظة";
          }
      }
  }

  // Calcul des moyennes par domaine
  foreach ($matieres_par_domaine as $domaine_nom => $matieres) {
      $total_notes = 0;
      $count_notes = 0;
      foreach ($matieres as $matiere) {
          if (isset($matiere['note'])) {
              $total_notes += $matiere['note'];
              $count_notes++;
          }
      }
      $moyenne_domaine = $count_notes > 0 ? $total_notes / $count_notes : 0;
      $moyennes_domaines[$domaine_nom] = $moyenne_domaine;
  }

  // Calcul de la moyenne générale selon le niveau
  $moyenne_generale = 0;

  // Vérifier si tous les domaines nécessaires existent
  $domaine_arabe = isset($moyennes_domaines['مجال اللغة العربية']) ? $moyennes_domaines['مجال اللغة العربية'] : 0;
  $domaine_sciences = isset($moyennes_domaines['مجال العلوم والتكنولوجيا']) ? $moyennes_domaines['مجال العلوم والتكنولوجيا'] : 0;
  $domaine_education = isset($moyennes_domaines['مجال التنشئة']) ? $moyennes_domaines['مجال التنشئة'] : 0;
  $domaine_langues = isset($moyennes_domaines['مجال اللغات الأجنبية']) ? $moyennes_domaines['مجال اللغات الأجنبية'] : 0;

  // Calcul selon le niveau
  if ($niveau == 1 || $niveau == 2) {
      // Pour الأولي et الثانية
      $moyenne_generale = ($domaine_arabe * 2 + $domaine_sciences * 2 + $domaine_education * 1) / 5;
  } else {
      // Pour الثالثة, الرابعة, الخامسة, et السادسة
      $moyenne_generale = ($domaine_arabe * 2 + $domaine_sciences * 2 + $domaine_education * 1 + $domaine_langues * 1.5) / 6.5;
  }

  // Déterminer la certification basée sur la moyenne
  $certification = "";
  if ($moyenne_generale >= 16) {
      $certification = "ممتاز";
  } elseif ($moyenne_generale >= 14) {
      $certification = "جيد جدا";
  } elseif ($moyenne_generale >= 12) {
      $certification = "جيد";
  } elseif ($moyenne_generale >= 10) {
      $certification = "متوسط";
  } elseif ($moyenne_generale >= 8) {
      $certification = "دون المتوسط";
  } else {
      $certification = "ضعيف";
  }

  // Fonction pour calculer la moyenne générale d'un élève
  function calculerMoyenneGenerale($eleve_id, $classe_id, $niveau, $trimestre, $conn) {
      // Récupérer les moyennes par domaine pour cet élève
      $query = "
      SELECT 
          d.nom AS domaine_nom,
          AVG(n.note) AS moyenne_domaine
      FROM notes n
      JOIN matieres m ON n.matiere_id = m.matiere_id
      JOIN domaines d ON m.domaine_id = d.id
      WHERE n.id_eleve = ? AND m.classe_id = ? AND n.trimestre = ?
      GROUP BY d.nom";
      
      $stmt = $conn->prepare($query);
      $stmt->bind_param("iii", $eleve_id, $classe_id, $trimestre);
      $stmt->execute();
      $result = $stmt->get_result();
      
      $moyennes_domaines = [];
      while ($row = $result->fetch_assoc()) {
          $moyennes_domaines[$row['domaine_nom']] = $row['moyenne_domaine'];
      }
      
      // Vérifier si tous les domaines nécessaires existent
      $domaine_arabe = isset($moyennes_domaines['مجال اللغة العربية']) ? $moyennes_domaines['مجال اللغة العربية'] : 0;
      $domaine_sciences = isset($moyennes_domaines['مجال العلوم والتكنولوجيا']) ? $moyennes_domaines['مجال العلوم والتكنولوجيا'] : 0;
      $domaine_education = isset($moyennes_domaines['مجال التنشئة']) ? $moyennes_domaines['مجال التنشئة'] : 0;
      $domaine_langues = isset($moyennes_domaines['مجال اللغات الأجنبية']) ? $moyennes_domaines['مجال اللغات الأجنبية'] : 0;
      
      // Calcul selon le niveau
      if ($niveau == 1 || $niveau == 2) {
          // Pour الأولي et الثانية
          return ($domaine_arabe * 2 + $domaine_sciences * 2 + $domaine_education * 1) / 5;
      } else {
          // Pour الثالثة, الرابعة, الخامسة, et السادسة
          return ($domaine_arabe * 2 + $domaine_sciences * 2 + $domaine_education * 1 + $domaine_langues * 1.5) / 6.5;
      }
  }

  // Calcul des moyennes de la classe
  $query_eleves = "SELECT id_eleve FROM eleves WHERE id_classe = ?";
  $stmt_eleves = $conn->prepare($query_eleves);
  $stmt_eleves->bind_param("i", $classe_id);
  $stmt_eleves->execute();
  $result_eleves = $stmt_eleves->get_result();
  
  $moyennes_classe = [];
  while ($row_eleve = $result_eleves->fetch_assoc()) {
      $eleve_id_classe = $row_eleve['id_eleve'];
      $moyenne_eleve = calculerMoyenneGenerale($eleve_id_classe, $classe_id, $niveau, $trimestre, $conn);
      if ($moyenne_eleve > 0) { // Ignorer les élèves sans notes
          $moyennes_classe[] = $moyenne_eleve;
      }
  }
  
  $max_moyenne = !empty($moyennes_classe) ? max($moyennes_classe) : 0;
  $min_moyenne = !empty($moyennes_classe) ? min($moyennes_classe) : 0;
  
  // Calculer le rang de l'élève
  $rang = 1;
  foreach ($moyennes_classe as $moyenne) {
      if ($moyenne > $moyenne_generale) {
          $rang++;
      }
  }
  $total_eleves = count($moyennes_classe);
  
  // Déterminer la couleur de certification
  $certification_color = "";
  if ($moyenne_generale >= 16) {
      $certification_color = "#4CAF50"; // Vert
  } elseif ($moyenne_generale >= 14) {
      $certification_color = "#8BC34A"; // Vert clair
  } elseif ($moyenne_generale >= 12) {
      $certification_color = "#2196F3"; // Bleu
  } elseif ($moyenne_generale >= 10) {
      $certification_color = "#FF9800"; // Orange
  } elseif ($moyenne_generale >= 8) {
      $certification_color = "#FF5722"; // Orange foncé
  } else {
      $certification_color = "#F44336"; // Rouge
  }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>كشف النتائج المدرسية - <?php echo $trimestre_nom; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Ajout des bibliothèques nécessaires pour la génération PDF -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
      :root {
          --primary-color: #3a86ff;
          --primary-light: #8bb9ff;
          --primary-dark: #0043ce;
          --secondary-color: #4cc9f0;
          --accent-color: #ff006e;
          --success-color: #38b000;
          --warning-color: #ffbe0b;
          --danger-color: #ff5a5f;
          --light-color: #f8f9fa;
          --dark-color: #212529;
          --gray-color: #6c757d;
          --border-radius-sm: 8px;
          --border-radius: 16px;
          --border-radius-lg: 24px;
          --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
          --box-shadow-hover: 0 15px 35px rgba(0, 0, 0, 0.15);
          --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      }
      
      * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
      }
      
      body {
          font-family: 'Cairo', sans-serif;
          background-color: #f0f2f5;
          color: var(--dark-color);
          line-height: 1.6;
          min-height: 100vh;
          position: relative;
          overflow-x: hidden;
      }
      
      body::before {
          content: '';
          position: absolute;
          top: 0;
          right: 0;
          width: 100%;
          height: 100%;
          background: linear-gradient(135deg, rgba(58, 134, 255, 0.05) 0%, rgba(76, 201, 240, 0.05) 100%);
          z-index: -1;
      }
      
      .bulletin-container {
          max-width: 1200px;
          margin: 40px auto;
          background-color: white;
          border-radius: var(--border-radius-lg);
          box-shadow: var(--box-shadow);
          overflow: hidden;
          position: relative;
      }
      
      .bulletin-header {
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
          color: white;
          padding: 30px;
          position: relative;
          overflow: hidden;
      }
      
      .bulletin-header::before {
          content: '';
          position: absolute;
          top: -50%;
          right: -50%;
          width: 200%;
          height: 200%;
          background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
          z-index: 0;
      }
      
      .header-content {
          position: relative;
          z-index: 1;
          display: flex;
          justify-content: space-between;
          align-items: center;
      }
      
      .school-info {
          display: flex;
          flex-direction: column;
      }
      
      .school-logo {
          width: 80px;
          height: 80px;
          background-color: white;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          margin-bottom: 10px;
          box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      }
      
      .logo-text {
          font-size: 2rem;
          font-weight: 800;
          color: var(--primary-color);
      }
      
      .ministry-text {
          font-size: 1.2rem;
          font-weight: 600;
          margin-bottom: 5px;
      }
      
      .school-name {
          font-size: 1rem;
          opacity: 0.9;
      }
      
      .report-title {
          text-align: center;
          display: flex;
          flex-direction: column;
          align-items: center;
      }
      
      .report-title h1 {
          font-size: 2.5rem;
          font-weight: 800;
          margin-bottom: 10px;
      }
      
      .trimester {
          font-size: 1.5rem;
          font-weight: 600;
          background-color: rgba(255, 255, 255, 0.2);
          padding: 5px 20px;
          border-radius: 30px;
          backdrop-filter: blur(5px);
      }
      
      .academic-year {
          position: absolute;
          top: 20px;
          left: 30px;
          background-color: rgba(255, 255, 255, 0.2);
          padding: 5px 15px;
          border-radius: 20px;
          font-size: 0.9rem;
          backdrop-filter: blur(5px);
      }
      
      .bulletin-body {
          padding: 30px;
      }
      
      .student-info-card {
          background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
          border-radius: var(--border-radius);
          padding: 20px;
          margin-bottom: 30px;
          box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
          display: flex;
          justify-content: space-between;
          align-items: center;
      }
      
      .student-avatar {
          width: 80px;
          height: 80px;
          border-radius: 50%;
          background-color: var(--primary-color);
          color: white;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 2rem;
          font-weight: 700;
          margin-left: 20px;
          box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      }
      
      .student-details {
          flex: 1;
      }
      
      .student-name {
          font-size: 1.5rem;
          font-weight: 700;
          margin-bottom: 5px;
          color: var(--primary-color);
      }
      
      .student-class {
          font-size: 1.1rem;
          color: var(--gray-color);
          margin-bottom: 10px;
      }
      
      .student-stats {
          display: flex;
          gap: 20px;
          margin-top: 10px;
      }
      
      .stat-item {
          display: flex;
          flex-direction: column;
          align-items: center;
          background-color: white;
          padding: 10px 15px;
          border-radius: var(--border-radius-sm);
          box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
          min-width: 100px;
      }
      
      .stat-value {
          font-size: 1.3rem;
          font-weight: 700;
          color: var(--primary-color);
      }
      
      .stat-label {
          font-size: 0.8rem;
          color: var(--gray-color);
      }
      
      .certification-badge {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          background: linear-gradient(135deg, <?php echo $certification_color; ?> 0%, <?php echo $certification_color; ?>99 100%);
          color: white;
          padding: 15px 25px;
          border-radius: var(--border-radius);
          box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
          min-width: 120px;
      }
      
      .certification-value {
          font-size: 1.5rem;
          font-weight: 700;
      }
      
      .certification-label {
          font-size: 0.9rem;
      }
      
      .domains-container {
          display: grid;
          grid-template-columns: 2fr 1fr;
          gap: 30px;
          margin-top: 30px;
      }
      
      .domains-list {
          display: flex;
          flex-direction: column;
          gap: 20px;
      }
      
      .domain-card {
          background-color: white;
          border-radius: var(--border-radius);
          overflow: hidden;
          box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
          transition: var(--transition);
      }
      
      .domain-card:hover {
          transform: translateY(-5px);
          box-shadow: var(--box-shadow-hover);
      }
      
      .domain-header {
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
          color: white;
          padding: 15px 20px;
          display: flex;
          justify-content: space-between;
          align-items: center;
      }
      
      .domain-title {
          font-size: 1.2rem;
          font-weight: 600;
      }
      
      .domain-average {
          background-color: rgba(255, 255, 255, 0.2);
          padding: 5px 15px;
          border-radius: 20px;
          font-size: 0.9rem;
          backdrop-filter: blur(5px);
      }
      
      .domain-body {
          padding: 0;
      }
      
      .subjects-table {
          width: 100%;
          border-collapse: collapse;
      }
      
      .subjects-table th,
      .subjects-table td {
          padding: 12px 15px;
          text-align: center;
          border-bottom: 1px solid #eee;
      }
      
      .subjects-table th {
          background-color: #f8f9fa;
          font-weight: 600;
          color: var(--dark-color);
      }
      
      .subjects-table th:first-child,
      .subjects-table td:first-child {
          text-align: right;
          padding-right: 20px;
      }
      
      .subjects-table th:last-child,
      .subjects-table td:last-child {
          text-align: right;
          padding-right: 20px;
      }
      
      .subjects-table tr:last-child td {
          border-bottom: none;
      }
      
      .subjects-table tr:hover {
          background-color: rgba(58, 134, 255, 0.05);
      }
      
      .grade {
          font-weight: 600;
          color: var(--primary-color);
      }
      
      .max-grade {
          color: var(--success-color);
          background-color: rgba(56, 176, 0, 0.1);
          padding: 3px 8px;
          border-radius: 4px;
      }
      
      .min-grade {
          color: var(--danger-color);
          background-color: rgba(255, 90, 95, 0.1);
          padding: 3px 8px;
          border-radius: 4px;
      }
      
      .remark {
          color: var(--gray-color);
          font-style: italic;
      }
      
      .summary-container {
          display: flex;
          flex-direction: column;
          gap: 20px;
      }
      
      .summary-card {
          background-color: white;
          border-radius: var(--border-radius);
          overflow: hidden;
          box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      }
      
      .summary-header {
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
          color: white;
          padding: 15px 20px;
          font-size: 1.2rem;
          font-weight: 600;
      }
      
      .summary-body {
          padding: 20px;
      }
      
      .summary-table {
          width: 100%;
          border-collapse: collapse;
      }
      
      .summary-table td {
          padding: 10px;
          border-bottom: 1px solid #eee;
      }
      
      .summary-table tr:last-child td {
          border-bottom: none;
      }
      
      .summary-table td:first-child {
          font-weight: 600;
          color: var(--dark-color);
          width: 60%;
      }
      
      .summary-table td:last-child {
          text-align: center;
          font-weight: 600;
          color: var(--primary-color);
      }
      
      .chart-container {
          height: 250px;
          padding: 20px;
      }
      
      .signatures-card {
          margin-top: 30px;
          background-color: white;
          border-radius: var(--border-radius);
          overflow: hidden;
          box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      }
      
      .signatures-header {
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
          color: white;
          padding: 15px 20px;
          font-size: 1.2rem;
          font-weight: 600;
      }
      
      .signatures-body {
          padding: 20px;
          display: flex;
          justify-content: space-between;
          gap: 20px;
      }
      
      .signature-box {
          flex: 1;
          height: 120px;
          border: 2px dashed #dee2e6;
          display: flex;
          align-items: center;
          justify-content: center;
          color: var(--gray-color);
          font-size: 1rem;
          border-radius: var(--border-radius-sm);
          position: relative;
          overflow: hidden;
          transition: var(--transition);
      }
      
      .signature-box:hover {
          border-color: var(--primary-color);
          color: var(--primary-color);
      }
      
      .signature-box::before {
          content: '';
          position: absolute;
          top: 0;
          right: 0;
          width: 100%;
          height: 100%;
          background: linear-gradient(135deg, rgba(58, 134, 255, 0.05) 0%, rgba(76, 201, 240, 0.05) 100%);
          z-index: -1;
      }
      
      .actions-container {
          display: flex;
          justify-content: center;
          margin-top: 30px;
          gap: 20px;
      }
      
      .action-button {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          gap: 10px;
          padding: 12px 25px;
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
          color: white;
          border: none;
          border-radius: 30px;
          font-size: 1.1rem;
          font-weight: 600;
          cursor: pointer;
          transition: var(--transition);
          box-shadow: 0 4px 15px rgba(58, 134, 255, 0.3);
      }
      
      .action-button:hover {
          transform: translateY(-3px);
          box-shadow: 0 8px 25px rgba(58, 134, 255, 0.4);
      }
      
      .action-button.secondary {
          background: linear-gradient(135deg, var(--gray-color) 0%, var(--dark-color) 100%);
      }
      
      .floating-button {
          position: fixed;
          bottom: 30px;
          left: 30px;
          width: 60px;
          height: 60px;
          border-radius: 50%;
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
          color: white;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.5rem;
          cursor: pointer;
          transition: var(--transition);
          box-shadow: 0 4px 15px rgba(58, 134, 255, 0.3);
          z-index: 100;
      }
      
      .floating-button:hover {
          transform: translateY(-5px) rotate(360deg);
          box-shadow: 0 8px 25px rgba(58, 134, 255, 0.4);
      }
      
      .floating-button i {
          transition: var(--transition);
      }
      
      .floating-button:hover i {
          transform: scale(1.2);
      }
      
      .loading-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.7);
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          z-index: 1000;
          color: white;
          font-size: 1.2rem;
      }
      
      .spinner {
          width: 50px;
          height: 50px;
          border: 5px solid rgba(255, 255, 255, 0.3);
          border-radius: 50%;
          border-top-color: white;
          animation: spin 1s ease-in-out infinite;
          margin-bottom: 20px;
      }
      
      @keyframes spin {
          to { transform: rotate(360deg); }
      }
      
      @media print {
          body {
              background-color: white;
          }
          
          .bulletin-container {
              margin: 0;
              box-shadow: none;
          }
          
          .floating-button,
          .actions-container {
              display: none;
          }
          
          .domain-card:hover {
              transform: none;
              box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
          }
      }
      
      @media (max-width: 992px) {
          .domains-container {
              grid-template-columns: 1fr;
          }
          
          .bulletin-header {
              padding: 20px;
          }
          
          .header-content {
              flex-direction: column;
              text-align: center;
              gap: 20px;
          }
          
          .school-info {
              align-items: center;
          }
          
          .academic-year {
              position: static;
              margin-top: 10px;
          }
      }
      
      @media (max-width: 768px) {
          .bulletin-container {
              margin: 20px;
          }
          
          .bulletin-body {
              padding: 20px;
          }
          
          .student-info-card {
              flex-direction: column;
              align-items: center;
              text-align: center;
              gap: 15px;
          }
          
          .student-avatar {
              margin-left: 0;
              margin-bottom: 10px;
          }
          
          .student-stats {
              flex-wrap: wrap;
              justify-content: center;
          }
          
          .signatures-body {
              flex-direction: column;
          }
      }
      
      @media (max-width: 576px) {
          .bulletin-container {
              margin: 10px;
          }
          
          .bulletin-body {
              padding: 15px;
          }
          
          .domain-header {
              flex-direction: column;
              gap: 10px;
              align-items: flex-start;
          }
          
          .subjects-table th,
          .subjects-table td {
              padding: 8px;
              font-size: 0.9rem;
          }
      }
  </style>
</head>
<body>
<div class="bulletin-container">
    <!-- En-tête du bulletin -->
    <div class="bulletin-header">
        <div class="header-content">
            <div class="school-info">
                <div class="school-logo">
                    <div class="logo-text">م</div>
                </div>
                <div class="ministry-text">وزارة التربية</div>
                <div class="school-name">مدرسة خاصة / ميسكول</div>
            </div>
            
            <div class="report-title">
                <h1>بطاقة ٱعداد</h1>
                <div class="trimester"><?php echo $trimestre_nom; ?></div>
            </div>
            
            <div class="academic-year">
                السنة الدراسية <?php echo date('Y') - 1; ?> - <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
    
    <!-- Corps du bulletin -->
    <div class="bulletin-body">
        <!-- Informations de l'élève -->
        <div class="student-info-card">
            <div class="student-avatar">
                <?php echo substr($prenom_eleve, 0, 1) . substr($nom_eleve, 0, 1); ?>
            </div>
            
            <div class="student-details">
                <div class="student-name"><?php echo htmlspecialchars($prenom_eleve . ' ' . $nom_eleve); ?></div>
                <div class="student-class"><?php echo htmlspecialchars($nom_classe); ?></div>
                
                <div class="student-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($moyenne_generale, 2); ?></div>
                        <div class="stat-label">المعدل العام</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $rang; ?>/<?php echo $total_eleves; ?></div>
                        <div class="stat-label">الترتيب</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($max_moyenne, 2); ?></div>
                        <div class="stat-label">أعلى معدل</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($min_moyenne, 2); ?></div>
                        <div class="stat-label">أدنى معدل</div>
                    </div>
                </div>
            </div>
            
            <div class="certification-badge">
                <div class="certification-value"><?php echo htmlspecialchars($certification); ?></div>
                <div class="certification-label">التقدير</div>
            </div>
        </div>
        
        <!-- Domaines et matières -->
        <div class="domains-container">
            <div class="domains-list">
                <?php foreach ($matieres_par_domaine as $domaine_nom => $matieres): 
                    // Ne pas afficher les domaines vides
                    if (empty($matieres)) continue;
                ?>
                <div class="domain-card">
                    <div class="domain-header">
                        <div class="domain-title"><?php echo htmlspecialchars($domaine_nom); ?></div>
                        <div class="domain-average">
                            <?php echo "مُعدّل المَجَال " . number_format($moyennes_domaines[$domaine_nom], 2); ?>
                        </div>
                    </div>
                    
                    <div class="domain-body">
                        <table class="subjects-table">
                            <thead>
                                <tr>
                                    <th>المادة</th>
                                    <th>العلامة</th>
                                    <th>أقصى علامة</th>
                                    <th>أدنى علامة</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $first_row = true;
                                foreach ($matieres as $matiere): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($matiere['matiere_nom']); ?></td>
                                    <td>
                                        <span class="grade">
                                            <?php echo isset($matiere['note']) ? number_format($matiere['note'], 2) : 'لا يوجد'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="max-grade">
                                            <?php echo isset($matiere['max_note']) ? number_format($matiere['max_note'], 2) : 'لا يوجد'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="min-grade">
                                            <?php echo isset($matiere['min_note']) ? number_format($matiere['min_note'], 2) : 'لا يوجد'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($first_row && isset($remarques[$matiere['domaine_id']])) {
                                            echo '<span class="remark">' . htmlspecialchars($remarques[$matiere['domaine_id']]) . '</span>';
                                            $first_row = false;
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="summary-container">
                <!-- Résumé des moyennes -->
                <div class="summary-card">
                    <div class="summary-header">
                        ملخص النتائج
                    </div>
                    
                    <div class="summary-body">
                        <table class="summary-table">
                            <tr>
                                <td>المعدل العام</td>
                                <td><?php echo number_format($moyenne_generale, 2); ?></td>
                            </tr>
                            <tr>
                                <td>الترتيب في القسم</td>
                                <td><?php echo $rang; ?> / <?php echo $total_eleves; ?></td>
                            </tr>
                            <tr>
                                <td>أعلى معدل في القسم</td>
                                <td><?php echo number_format($max_moyenne, 2); ?></td>
                            </tr>
                            <tr>
                                <td>أدنى معدل في القسم</td>
                                <td><?php echo number_format($min_moyenne, 2); ?></td>
                            </tr>
                            <tr>
                                <td>التقدير</td>
                                <td><?php echo htmlspecialchars($certification); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Graphique des moyennes par domaine -->
                <div class="summary-card">
                    <div class="summary-header">
                        رسم بياني للمعدلات حسب المجال
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="domainsChart"></canvas>
                    </div>
                </div>
                
                <!-- Graphique comparatif -->
                <div class="summary-card">
                    <div class="summary-header">
                        مقارنة مع معدلات القسم
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Signatures -->
        <div class="signatures-card">
            <div class="signatures-header">
                التوقيعات
            </div>
            
            <div class="signatures-body">
                <div class="signature-box">
                    ختم وتوقيع المدير
                </div>
                
                <div class="signature-box">
                    إمضاء المربي(ة)
                </div>
                
                <div class="signature-box">
                    إمضاء الولي
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="actions-container">
            <button class="action-button" onclick="printBulletin()">
                <i class="fas fa-print"></i>
                طباعة البطاقة
            </button>
            
            <button class="action-button" onclick="generatePDF()">
                <i class="fas fa-file-pdf"></i>
                تحميل PDF
            </button>
            
            <button class="action-button secondary" onclick="window.history.back()">
                <i class="fas fa-arrow-right"></i>
                العودة
            </button>
        </div>
    </div>
</div>

<!-- Bouton flottant pour télécharger le PDF -->
<div class="floating-button" onclick="generatePDF()">
    <i class="fas fa-download"></i>
</div>
<script>
// Initialiser les graphiques
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des moyennes par domaine
    const domainsCtx = document.getElementById('domainsChart').getContext('2d');
    const domainsChart = new Chart(domainsCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($moyennes_domaines as $domaine_nom => $moyenne) {
                    echo "'" . addslashes($domaine_nom) . "', ";
                }
                ?>
            ],
            datasets: [{
                label: 'معدل المجال',
                data: [
                    <?php 
                    foreach ($moyennes_domaines as $moyenne) {
                        echo number_format($moyenne, 2) . ", ";
                    }
                    ?>
                ],
                backgroundColor: [
                    'rgba(58, 134, 255, 0.7)',
                    'rgba(76, 201, 240, 0.7)',
                    'rgba(255, 0, 110, 0.7)',
                    'rgba(56, 176, 0, 0.7)'
                ],
                borderColor: [
                    'rgba(58, 134, 255, 1)',
                    'rgba(76, 201, 240, 1)',
                    'rgba(255, 0, 110, 1)',
                    'rgba(56, 176, 0, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 20
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Graphique comparatif
    const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
    const comparisonChart = new Chart(comparisonCtx, {
        type: 'radar',
        data: {
            labels: ['المعدل العام', 'أعلى معدل', 'أدنى معدل'],
            datasets: [{
                label: 'الطالب',
                data: [
                    <?php echo number_format($moyenne_generale, 2); ?>,
                    <?php echo number_format($max_moyenne, 2); ?>,
                    <?php echo number_format($min_moyenne, 2); ?>
                ],
                backgroundColor: 'rgba(58, 134, 255, 0.2)',
                borderColor: 'rgba(58, 134, 255, 1)',
                pointBackgroundColor: 'rgba(58, 134, 255, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(58, 134, 255, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: {
                        display: true
                    },
                    suggestedMin: 0,
                    suggestedMax: 20
                }
            }
        }
    });
});

// Fonction pour imprimer le bulletin
function printBulletin() {
    window.print();
}

// Fonction pour générer le PDF
function generatePDF() {
    // Créer l'overlay de chargement
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    
    const loadingText = document.createElement('div');
    loadingText.textContent = 'جاري إنشاء ملف PDF...';
    
    loadingOverlay.appendChild(spinner);
    loadingOverlay.appendChild(loadingText);
    document.body.appendChild(loadingOverlay);
    
    // Masquer les boutons flottants
    const floatingButton = document.querySelector('.floating-button');
    if (floatingButton) {
        floatingButton.style.display = 'none';
    }
    
    // Définir l'espace de noms jsPDF
    const { jsPDF } = window.jspdf;
    
    // Options pour html2canvas
    const options = {
        scale: 2,
        useCORS: true,
        logging: true,
        allowTaint: true,
        backgroundColor: '#ffffff'
    };
    
    // Convertir le contenu HTML en canvas
    html2canvas(document.querySelector('.bulletin-container'), options).then(canvas => {
        // Restaurer l'affichage du bouton flottant
        if (floatingButton) {
            floatingButton.style.display = 'flex';
        }
        
        // Obtenir les données d'image du canvas
        const imgData = canvas.toDataURL('image/png');
        
        // Créer un nouveau document PDF
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        // Calculer les dimensions pour adapter l'image à la page PDF
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        // Vérifier si le contenu dépasse une page
        if (pdfHeight > 297) { // 297mm est la hauteur d'une page A4
            // Créer un PDF multi-pages
            let heightLeft = canvas.height;
            let position = 0;
            let pageHeight = 297; // Hauteur d'une page A4 en mm
            let imgWidth = pdfWidth;
            let imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            // Première page
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            // Pages suivantes
            while (heightLeft > 0) {
                position = heightLeft - canvas.height;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
        } else {
            // Si le contenu tient sur une seule page
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        }
        
        // Enregistrer le PDF
        pdf.save('بطاقة-أعداد-<?php echo $trimestre_nom; ?>.pdf');
        
        // Supprimer l'overlay de chargement
        document.body.removeChild(loadingOverlay);
    }).catch(error => {
        console.error('Erreur lors de la génération du PDF:', error);
        alert('حدث خطأ أثناء إنشاء ملف PDF');
        
        // Restaurer l'affichage du bouton flottant
        if (floatingButton) {
            floatingButton.style.display = 'flex';
        }
        
        // Supprimer l'overlay de chargement
        document.body.removeChild(loadingOverlay);
    });
}
</script>
</body>
</html>
<?php
} catch (Exception $e) {
  die("Une erreur est survenue : " . $e->getMessage());
}
?>