<?php

include 'db_config.php';
session_start();

// Vérifier si le professeur est connecté
if (!isset($_SESSION['id_professeur'])) {
  header("Location: login.php");
  exit();
}

$id_professeur = $_SESSION['id_professeur'];
$trimestre = isset($_GET['trimestre']) ? $_GET['trimestre'] : 1; // Par défaut, le premier trimestre

// Récupérer uniquement les classes enseignées par le professeur connecté
$query = "SELECT c.id_classe, c.nom_classe 
        FROM classes c
        JOIN professeurs_classes pc ON c.id_classe = pc.id_classe
        WHERE pc.id_professeur = ?
        ORDER BY c.nom_classe ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_professeur);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
  die("Erreur lors de la récupération des classes : " . $conn->error);
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

// Si une classe est déjà sélectionnée via GET
$classe_preselected = isset($_GET['classe_id']) ? $_GET['classe_id'] : null;

// Récupérer le nom du professeur
$query_prof = "SELECT nom, prenom FROM professeurs WHERE id_professeur = ?";
$stmt_prof = $conn->prepare($query_prof);
$stmt_prof->bind_param("i", $id_professeur);
$stmt_prof->execute();
$result_prof = $stmt_prof->get_result();
$prof_info = $result_prof->fetch_assoc();
$nom_professeur = $prof_info ? $prof_info['prenom'] . ' ' . $prof_info['nom'] : 'الأستاذ(ة)';

// Précharger les données des élèves pour toutes les classes
$classes_eleves = [];
$stmt_classes = $conn->prepare("SELECT id_classe FROM classes WHERE id_classe IN (SELECT id_classe FROM professeurs_classes WHERE id_professeur = ?)");
$stmt_classes->bind_param("i", $id_professeur);
$stmt_classes->execute();
$result_classes = $stmt_classes->get_result();

while ($classe = $result_classes->fetch_assoc()) {
    $classe_id = $classe['id_classe'];
    $stmt_eleves = $conn->prepare("SELECT id_eleve, nom, prenom FROM eleves WHERE id_classe = ? ORDER BY nom, prenom");
    $stmt_eleves->bind_param("i", $classe_id);
    $stmt_eleves->execute();
    $result_eleves = $stmt_eleves->get_result();
    
    $eleves = [];
    while ($eleve = $result_eleves->fetch_assoc()) {
        $eleves[] = $eleve;
    }
    
    $classes_eleves[$classe_id] = $eleves;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>بطاقة ٱعداد - <?php echo $trimestre_nom; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
      :root {
          --primary-color: #3498db;
          --secondary-color: #2ecc71;
          --accent-color: #f39c12;
          --dark-color: #2c3e50;
          --light-color: #ecf0f1;
          --danger-color: #e74c3c;
          --success-color: #27ae60;
          --border-radius: 12px;
          --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
          --transition: all 0.3s ease;
      }
      
      body {
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          background-color: #f5f7fa;
          color: var(--dark-color);
          line-height: 1.6;
          min-height: 100vh;
      }
      
      .main-container {
          max-width: 1200px;
          margin: 0 auto;
          padding: 20px;
      }
      
      .header {
          background-color: white;
          border-radius: var(--border-radius);
          padding: 20px;
          margin-bottom: 30px;
          box-shadow: var(--box-shadow);
          display: flex;
          justify-content: space-between;
          align-items: center;
      }
      
      .header-title {
          font-size: 1.5rem;
          font-weight: bold;
          color: var(--dark-color);
      }
      
      .user-info {
          display: flex;
          align-items: center;
          gap: 10px;
      }
      
      .user-avatar {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          background-color: var(--primary-color);
          color: white;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: bold;
      }
      
      .card {
          background-color: white;
          border-radius: var(--border-radius);
          box-shadow: var(--box-shadow);
          border: none;
          margin-bottom: 30px;
          overflow: hidden;
      }
      
      .card-header {
          background-color: var(--primary-color);
          color: white;
          padding: 15px 20px;
          font-weight: bold;
          border-bottom: none;
      }
      
      .card-body {
          padding: 30px;
      }
      
      .trimestre-selector {
          display: flex;
          justify-content: center;
          margin-bottom: 30px;
          gap: 15px;
      }
      
      .trimestre-btn {
          padding: 12px 25px;
          border-radius: var(--border-radius);
          cursor: pointer;
          font-weight: bold;
          text-decoration: none;
          transition: var(--transition);
          text-align: center;
          flex: 1;
          max-width: 180px;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
      }
      
      .trimestre-active {
          background-color: var(--success-color);
          color: white;
          box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
      }
      
      .trimestre-inactive {
          background-color: white;
          color: var(--dark-color);
          border: 2px solid #e9ecef;
      }
      
      .trimestre-inactive:hover {
          background-color: #f8f9fa;
          transform: translateY(-3px);
          box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      }
      
      .form-label {
          font-weight: bold;
          margin-bottom: 15px;
          color: var(--dark-color);
          font-size: 1.1rem;
          display: flex;
          align-items: center;
          gap: 8px;
      }
      
      .btn-primary {
          background-color: var(--primary-color);
          border: none;
          padding: 12px 30px;
          font-weight: bold;
          border-radius: var(--border-radius);
          transition: var(--transition);
          box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
      }
      
      .btn-primary:hover {
          background-color: #2980b9;
          transform: translateY(-3px);
          box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
      }
      
      .btn-lg {
          font-size: 1.1rem;
          padding: 15px 40px;
      }
      
      .empty-state {
          text-align: center;
          padding: 40px 20px;
          color: #6c757d;
      }
      
      .empty-state-icon {
          font-size: 3rem;
          margin-bottom: 20px;
          color: #dee2e6;
      }
      
      .empty-state-text {
          font-size: 1.2rem;
          margin-bottom: 15px;
      }
      
      .empty-state-subtext {
          font-size: 0.9rem;
          max-width: 400px;
          margin: 0 auto;
      }
      
      /* Nouveaux styles pour les sélecteurs modernes */
      .selection-container {
          margin-bottom: 30px;
      }
      
      .selection-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
          gap: 15px;
          margin-top: 15px;
      }
      
      .selection-item {
          background-color: white;
          border: 2px solid #e9ecef;
          border-radius: var(--border-radius);
          padding: 15px;
          text-align: center;
          cursor: pointer;
          transition: var(--transition);
          position: relative;
          overflow: hidden;
      }
      
      .selection-item:hover {
          transform: translateY(-5px);
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
          border-color: #d6d6d6;
      }
      
      .selection-item.selected {
          border-color: var(--primary-color);
          background-color: rgba(52, 152, 219, 0.05);
          box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
      }
      
      .selection-item.selected::after {
          content: "\f00c";
          font-family: "Font Awesome 5 Free";
          font-weight: 900;
          position: absolute;
          top: 5px;
          left: 5px;
          background-color: var(--primary-color);
          color: white;
          width: 20px;
          height: 20px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 10px;
      }
      
      .selection-icon {
          font-size: 24px;
          margin-bottom: 10px;
          color: var(--primary-color);
      }
      
      .selection-text {
          font-weight: bold;
          font-size: 0.9rem;
      }
      
      .selection-search {
          margin-bottom: 15px;
          position: relative;
      }
      
      .selection-search input {
          width: 100%;
          padding: 12px 15px 12px 40px;
          border-radius: var(--border-radius);
          border: 2px solid #e9ecef;
          font-size: 1rem;
          transition: var(--transition);
      }
      
      .selection-search input:focus {
          border-color: var(--primary-color);
          box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
          outline: none;
      }
      
      .selection-search i {
          position: absolute;
          right: 15px;
          top: 50%;
          transform: translateY(-50%);
          color: #adb5bd;
      }
      
      .selection-placeholder {
          text-align: center;
          padding: 30px;
          color: #adb5bd;
          background-color: #f8f9fa;
          border-radius: var(--border-radius);
          border: 2px dashed #e9ecef;
      }
      
      .selection-placeholder i {
          font-size: 2rem;
          margin-bottom: 10px;
          display: block;
      }
      
      .selection-loading {
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 30px;
          color: #6c757d;
      }
      
      .selection-loading i {
          margin-left: 10px;
          animation: spin 1s linear infinite;
      }
      
      @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
      }
      
      .step-indicator {
          display: flex;
          justify-content: space-between;
          margin-bottom: 30px;
          position: relative;
      }
      
      .step-indicator::before {
          content: "";
          position: absolute;
          top: 15px;
          left: 0;
          right: 0;
          height: 2px;
          background-color: #e9ecef;
          z-index: 1;
      }
      
      .step {
          width: 30px;
          height: 30px;
          border-radius: 50%;
          background-color: white;
          border: 2px solid #e9ecef;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: bold;
          position: relative;
          z-index: 2;
      }
      
      .step.active {
          background-color: var(--primary-color);
          border-color: var(--primary-color);
          color: white;
      }
      
      .step.completed {
          background-color: var(--success-color);
          border-color: var(--success-color);
          color: white;
      }
      
      .step-label {
          position: absolute;
          top: 35px;
          left: 50%;
          transform: translateX(-50%);
          white-space: nowrap;
          font-size: 0.8rem;
          color: #6c757d;
      }
      
      .step.active .step-label {
          color: var(--primary-color);
          font-weight: bold;
      }
      
      .step-content {
          display: none;
      }
      
      .step-content.active {
          display: block;
          animation: fadeIn 0.5s ease;
      }
      
      @keyframes fadeIn {
          from { opacity: 0; transform: translateY(10px); }
          to { opacity: 1; transform: translateY(0); }
      }
      
      .navigation-buttons {
          display: flex;
          justify-content: space-between;
          margin-top: 30px;
      }
      
      .btn-outline-secondary {
          background-color: white;
          border: 2px solid #e9ecef;
          color: #6c757d;
          padding: 12px 25px;
          border-radius: var(--border-radius);
          font-weight: bold;
          transition: var(--transition);
      }
      
      .btn-outline-secondary:hover {
          background-color: #f8f9fa;
          border-color: #d6d6d6;
      }
      
      /* Nouveaux styles pour la liste des élèves */
      .eleves-list {
          margin-top: 20px;
          border-radius: var(--border-radius);
          overflow: hidden;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      }
      
      .eleve-item {
          display: flex;
          align-items: center;
          padding: 15px;
          border-bottom: 1px solid #eee;
          background-color: white;
          transition: var(--transition);
          cursor: pointer;
      }
      
      .eleve-item:last-child {
          border-bottom: none;
      }
      
      .eleve-item:hover {
          background-color: #f8f9fa;
      }
      
      .eleve-item.selected {
          background-color: rgba(52, 152, 219, 0.1);
          border-right: 4px solid var(--primary-color);
      }
      
      .eleve-avatar {
          width: 45px;
          height: 45px;
          border-radius: 50%;
          background-color: #e9ecef;
          display: flex;
          align-items: center;
          justify-content: center;
          margin-left: 15px;
          color: var(--dark-color);
          font-weight: bold;
          font-size: 1.2rem;
      }
      
      .eleve-info {
          flex: 1;
      }
      
      .eleve-name {
          font-weight: bold;
          font-size: 1rem;
          margin-bottom: 3px;
      }
      
      .eleve-id {
          font-size: 0.8rem;
          color: #6c757d;
      }
      
      .eleve-actions {
          display: flex;
          gap: 10px;
      }
      
      .eleve-action-btn {
          width: 35px;
          height: 35px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          background-color: #f8f9fa;
          color: var(--dark-color);
          border: none;
          transition: var(--transition);
      }
      
      .eleve-action-btn:hover {
          background-color: var(--primary-color);
          color: white;
      }
      
      .eleves-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 15px;
          background-color: #f8f9fa;
          border-bottom: 1px solid #eee;
      }
      
      .eleves-count {
          font-size: 0.9rem;
          color: #6c757d;
      }
      
      .eleves-sort {
          display: flex;
          align-items: center;
          gap: 10px;
      }
      
      .sort-btn {
          background: none;
          border: none;
          color: #6c757d;
          font-size: 0.9rem;
          cursor: pointer;
          padding: 5px 10px;
          border-radius: 20px;
          transition: var(--transition);
      }
      
      .sort-btn:hover, .sort-btn.active {
          background-color: var(--primary-color);
          color: white;
      }
      
      .eleves-empty {
          padding: 30px;
          text-align: center;
          color: #6c757d;
      }
      
      .eleves-empty i {
          font-size: 3rem;
          color: #dee2e6;
          margin-bottom: 15px;
          display: block;
      }
      
      .eleve-select-indicator {
          width: 20px;
          height: 20px;
          border-radius: 50%;
          border: 2px solid #dee2e6;
          margin-right: 15px;
          transition: var(--transition);
          flex-shrink: 0;
      }
      
      .eleve-item.selected .eleve-select-indicator {
          background-color: var(--primary-color);
          border-color: var(--primary-color);
          position: relative;
      }
      
      .eleve-item.selected .eleve-select-indicator::after {
          content: "\f00c";
          font-family: "Font Awesome 5 Free";
          font-weight: 900;
          color: white;
          font-size: 10px;
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
      }
      
      .eleves-list-container {
          max-height: 400px;
          overflow-y: auto;
          border: 1px solid #eee;
          border-radius: var(--border-radius);
      }
      
      .eleves-list-container::-webkit-scrollbar {
          width: 8px;
      }
      
      .eleves-list-container::-webkit-scrollbar-track {
          background: #f1f1f1;
          border-radius: 10px;
      }
      
      .eleves-list-container::-webkit-scrollbar-thumb {
          background: #ccc;
          border-radius: 10px;
      }
      
      .eleves-list-container::-webkit-scrollbar-thumb:hover {
          background: #aaa;
      }
      
      @media (max-width: 768px) {
          .header {
              flex-direction: column;
              text-align: center;
              gap: 15px;
          }
          
          .trimestre-selector {
              flex-direction: column;
              align-items: center;
          }
          
          .trimestre-btn {
              width: 100%;
              max-width: 100%;
          }
          
          .card-body {
              padding: 20px;
          }
          
          .selection-grid {
              grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
          }
          
          .eleve-avatar {
              width: 35px;
              height: 35px;
              font-size: 1rem;
          }
          
          .eleve-action-btn {
              width: 30px;
              height: 30px;
          }
      }
  </style>
</head>
<body>
<div class="main-container">
  <div class="header">
      <div class="header-title">نظام إدارة بطاقات الأعداد</div>
      <div class="user-info">
          <span>مرحبا، <?php echo htmlspecialchars($nom_professeur); ?></span>
          <div class="user-avatar">
              <i class="fas fa-user"></i>
          </div>
      </div>
  </div>

  <div class="card">
      <div class="card-header">
          <i class="fas fa-file-alt me-2"></i> بطاقة ٱعداد - <?php echo $trimestre_nom; ?>
      </div>
      <div class="card-body">
          <div class="trimestre-selector">
              <a href="?trimestre=1<?php echo $classe_preselected ? '&classe_id='.$classe_preselected : ''; ?>" class="trimestre-btn <?php echo $trimestre == 1 ? 'trimestre-active' : 'trimestre-inactive'; ?>">
                  <i class="fas fa-calendar-alt"></i> الثلاثي الأول
              </a>
              <a href="?trimestre=2<?php echo $classe_preselected ? '&classe_id='.$classe_preselected : ''; ?>" class="trimestre-btn <?php echo $trimestre == 2 ? 'trimestre-active' : 'trimestre-inactive'; ?>">
                  <i class="fas fa-calendar-alt"></i> الثلاثي الثاني
              </a>
              <a href="?trimestre=3<?php echo $classe_preselected ? '&classe_id='.$classe_preselected : ''; ?>" class="trimestre-btn <?php echo $trimestre == 3 ? 'trimestre-active' : 'trimestre-inactive'; ?>">
                  <i class="fas fa-calendar-alt"></i> الثلاثي الثالث
              </a>
          </div>

          <!-- CORRECTION: Assurer que le formulaire utilise la méthode GET et le bon chemin -->
          <form action="afficher_classe.php" method="GET" id="report-form">
              <input type="hidden" name="trimestre" value="<?php echo $trimestre; ?>">
              <input type="hidden" name="classe_id" id="selected_classe_id" value="<?php echo $classe_preselected; ?>">
              <input type="hidden" name="eleve_id" id="selected_eleve_id" value="">
              
              <div class="step-indicator">
                  <div class="step active" id="step1">
                      1
                      <div class="step-label">اختيار القسم</div>
                  </div>
                  <div class="step" id="step2">
                      2
                      <div class="step-label">اختيار التلميذ</div>
                  </div>
                  <div class="step" id="step3">
                      3
                      <div class="step-label">عرض البطاقة</div>
                  </div>
              </div>
              
              <div class="step-content active" id="step1-content">
                  <div class="selection-container">
                      <div class="form-label">
                          <i class="fas fa-chalkboard"></i> اختر القسم:
                      </div>
                      
                      <div class="selection-search">
                          <input type="text" id="classe-search" placeholder="ابحث عن قسم...">
                          <i class="fas fa-search"></i>
                      </div>
                      
                      <div class="selection-grid" id="classes-grid">
                          <?php 
                          $has_classes = false;
                          while ($row = $result->fetch_assoc()) { 
                              $has_classes = true;
                          ?>
                              <div class="selection-item classe-item <?php echo ($classe_preselected == $row['id_classe']) ? 'selected' : ''; ?>" 
                                   data-id="<?= $row['id_classe'] ?>">
                                  <div class="selection-icon">
                                      <i class="fas fa-chalkboard-teacher"></i>
                                  </div>
                                  <div class="selection-text">
                                      <?= htmlspecialchars($row['nom_classe']) ?>
                                  </div>
                              </div>
                          <?php } ?>
                          
                          <?php if (!$has_classes) { ?>
                              <div class="selection-placeholder">
                                  <i class="fas fa-info-circle"></i>
                                  لا توجد فصول مخصصة لك حاليًا
                                  <div class="mt-2 small">يرجى التواصل مع إدارة المدرسة لتخصيص الفصول الدراسية</div>
                              </div>
                          <?php } ?>
                      </div>
                  </div>
                  
                  <div class="navigation-buttons">
                      <div></div> <!-- Placeholder pour l'alignement -->
                      <button type="button" class="btn btn-primary next-step" data-step="1" <?php echo (!$has_classes) ? 'disabled' : ''; ?>>
                          التالي <i class="fas fa-arrow-left ms-2"></i>
                      </button>
                  </div>
              </div>
              
              <div class="step-content" id="step2-content">
                  <div class="selection-container">
                      <div class="form-label">
                          <i class="fas fa-user-graduate"></i> اختر التلميذ:
                      </div>
                      
                      <div class="selection-search">
                          <input type="text" id="eleve-search" placeholder="ابحث عن تلميذ...">
                          <i class="fas fa-search"></i>
                      </div>
                      
                      <div id="eleves-container">
                          <div class="selection-placeholder">
                              <i class="fas fa-hand-point-up"></i>
                              الرجاء اختيار قسم أولاً
                              <div class="mt-2 small">سيتم عرض قائمة التلاميذ فور اختيار القسم</div>
                          </div>
                      </div>
                  </div>
                  
                  <div class="navigation-buttons">
                      <button type="button" class="btn btn-outline-secondary prev-step" data-step="2">
                          <i class="fas fa-arrow-right me-2"></i> السابق
                      </button>
                      <button type="button" class="btn btn-primary next-step" data-step="2" disabled>
                          التالي <i class="fas fa-arrow-left ms-2"></i>
                      </button>
                  </div>
              </div>
              
              <div class="step-content" id="step3-content">
                  <div class="text-center p-4">
                      <div class="mb-4">
                          <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                      </div>
                      <h4 class="mb-3">كل شيء جاهز!</h4>
                      <p class="mb-4">أنت على وشك عرض بطاقة الأعداد للتلميذ المحدد.</p>
                      <div id="summary" class="mb-4 p-3 bg-light rounded">
                          <div class="mb-2"><strong>القسم:</strong> <span id="summary-classe">-</span></div>
                          <div><strong>التلميذ:</strong> <span id="summary-eleve">-</span></div>
                      </div>
                  </div>
                  
                  <div class="navigation-buttons">
                      <button type="button" class="btn btn-outline-secondary prev-step" data-step="3">
                          <i class="fas fa-arrow-right me-2"></i> السابق
                      </button>
                      <button type="submit" class="btn btn-primary">
                          <i class="fas fa-search me-2"></i> عرض بطاقة الأعداد
                      </button>
                  </div>
              </div>
          </form>
      </div>
  </div>
</div>

<script>
$(document).ready(function () {
    // Préchargement des données des élèves pour toutes les classes
    const classesEleves = <?php echo json_encode($classes_eleves); ?>;
    
    // Variables pour stocker les informations sélectionnées
    let selectedClasseId = <?php echo $classe_preselected ? $classe_preselected : 'null'; ?>;
    let selectedClasseName = "";
    let selectedEleveId = null;
    let selectedEleveName = "";
    let sortOrder = "nom"; // Par défaut, tri par nom
    
    // Initialisation
    if (selectedClasseId) {
        // Récupérer le nom de la classe sélectionnée
        selectedClasseName = $(".classe-item.selected .selection-text").text().trim();
        // Afficher immédiatement les élèves de la classe sélectionnée
        displayEleves(selectedClasseId);
    }
    
    // Recherche de classes
    $("#classe-search").on("input", function() {
        const searchTerm = $(this).val().toLowerCase();
        $(".classe-item").each(function() {
            const className = $(this).find(".selection-text").text().toLowerCase();
            if (className.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Recherche d'élèves
    $("#eleve-search").on("input", function() {
        const searchTerm = $(this).val().toLowerCase();
        $(".eleve-item").each(function() {
            const eleveName = $(this).find(".eleve-name").text().toLowerCase();
            if (eleveName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Sélection d'une classe
    $(document).on("click", ".classe-item", function() {
        $(".classe-item").removeClass("selected");
        $(this).addClass("selected");
        
        selectedClasseId = $(this).data("id");
        selectedClasseName = $(this).find(".selection-text").text().trim();
        $("#selected_classe_id").val(selectedClasseId);
        
        // Afficher immédiatement les élèves de cette classe
        displayEleves(selectedClasseId);
        
        // Activer le bouton suivant
        $(".next-step[data-step='1']").prop("disabled", false);
    });
    
    // Sélection d'un élève
    $(document).on("click", ".eleve-item", function() {
        $(".eleve-item").removeClass("selected");
        $(this).addClass("selected");
        
        selectedEleveId = $(this).data("id");
        selectedEleveName = $(this).find(".eleve-name").text().trim();
        $("#selected_eleve_id").val(selectedEleveId);
        
        // Activer le bouton suivant
        $(".next-step[data-step='2']").prop("disabled", false);
    });
    
    // Tri des élèves
    $(document).on("click", ".sort-btn", function() {
        $(".sort-btn").removeClass("active");
        $(this).addClass("active");
        
        sortOrder = $(this).data("sort");
        if (selectedClasseId) {
            displayEleves(selectedClasseId);
        }
    });
    
    // Navigation entre les étapes
    $(".next-step").click(function() {
        const currentStep = parseInt($(this).data("step"));
        const nextStep = currentStep + 1;
        
        // Mettre à jour les indicateurs d'étape
        $("#step" + currentStep).removeClass("active").addClass("completed");
        $("#step" + nextStep).addClass("active");
        
        // Afficher le contenu de l'étape suivante
        $(".step-content").removeClass("active");
        $("#step" + nextStep + "-content").addClass("active");
        
        // Si on passe à l'étape 3, mettre à jour le résumé
        if (nextStep === 3) {
            $("#summary-classe").text(selectedClasseName);
            $("#summary-eleve").text(selectedEleveName);
        }
    });
    
    $(".prev-step").click(function() {
        const currentStep = parseInt($(this).data("step"));
        const prevStep = currentStep - 1;
        
        // Mettre à jour les indicateurs d'étape
        $("#step" + currentStep).removeClass("active");
        $("#step" + prevStep).removeClass("completed").addClass("active");
        
        // Afficher le contenu de l'étape précédente
        $(".step-content").removeClass("active");
        $("#step" + prevStep + "-content").addClass("active");
    });
    
    // Afficher les élèves d'une classe (sans AJAX)
    function displayEleves(classeId) {
        const eleves = classesEleves[classeId] || [];
        
        if (eleves.length === 0) {
            $("#eleves-container").html(`
                <div class="eleves-empty">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>لا يوجد تلاميذ في هذا القسم</div>
                    <div class="mt-2 small">يرجى التحقق من قائمة التلاميذ في النظام</div>
                </div>
            `);
        } else {
            // Trier les élèves selon l'ordre sélectionné
            const sortedEleves = [...eleves].sort((a, b) => {
                if (sortOrder === "nom") {
                    return a.nom.localeCompare(b.nom);
                } else {
                    return a.prenom.localeCompare(b.prenom);
                }
            });
            
            let html = `
                <div class="eleves-header">
                    <div class="eleves-count">${sortedEleves.length} تلميذ</div>
                    <div class="eleves-sort">
                        <span>ترتيب حسب:</span>
                        <button type="button" class="sort-btn ${sortOrder === 'nom' ? 'active' : ''}" data-sort="nom">اللقب</button>
                        <button type="button" class="sort-btn ${sortOrder === 'prenom' ? 'active' : ''}" data-sort="prenom">الاسم</button>
                    </div>
                </div>
                <div class="eleves-list-container">
                    <div class="eleves-list">
            `;
            
            sortedEleves.forEach(function(eleve) {
                const initials = (eleve.prenom.charAt(0) + eleve.nom.charAt(0)).toUpperCase();
                
                html += `
                    <div class="eleve-item" data-id="${eleve.id_eleve}">
                        <div class="eleve-select-indicator"></div>
                        <div class="eleve-avatar">${initials}</div>
                        <div class="eleve-info">
                            <div class="eleve-name">${eleve.nom} ${eleve.prenom}</div>
                            <div class="eleve-id">رقم التلميذ: ${eleve.id_eleve}</div>
                        </div>
                        <div class="eleve-actions">
                            <button type="button" class="eleve-action-btn view-btn" title="عرض بطاقة الأعداد">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
            
            $("#eleves-container").html(html);
            
            // Ajouter un gestionnaire d'événements pour le bouton de visualisation
            $(".view-btn").click(function(e) {
                e.stopPropagation(); // Empêcher la propagation de l'événement
                const eleveItem = $(this).closest(".eleve-item");
                eleveItem.click(); // Simuler un clic sur l'élément parent
                
                // Passer directement à l'étape 3
                $("#step1").removeClass("active").addClass("completed");
                $("#step2").removeClass("active").addClass("completed");
                $("#step3").addClass("active");
                
                $(".step-content").removeClass("active");
                $("#step3-content").addClass("active");
                
                // Mettre à jour le résumé
                $("#summary-classe").text(selectedClasseName);
                $("#summary-eleve").text(eleveItem.find(".eleve-name").text().trim());
            });
        }
    }
    
    // Validation du formulaire
    $("#report-form").submit(function(e) {
        if (!selectedClasseId || !selectedEleveId) {
            e.preventDefault();
            alert("يرجى اختيار القسم والتلميذ");
            return false;
        }
        
        // AJOUT: Stocker les valeurs dans sessionStorage
        sessionStorage.setItem('selectedClasseId', selectedClasseId);
        sessionStorage.setItem('selectedEleveId', selectedEleveId);
        sessionStorage.setItem('selectedTrimestre', <?php echo $trimestre; ?>);
    });
});
</script>

</body>
</html>