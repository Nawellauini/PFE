<?php
include 'db_config.php';

// Fonction pour échapper les sorties HTML
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Tableau d'icônes pour les matières
$icons = [
    'fas fa-book',           // Livre
    'fas fa-calculator',     // Calculatrice
    'fas fa-flask',          // Fiole (sciences)
    'fas fa-globe',          // Globe (géographie)
    'fas fa-language',       // Langue
    'fas fa-music',          // Musique
    'fas fa-paint-brush',    // Pinceau (art)
    'fas fa-running',        // Course (sport)
    'fas fa-atom',           // Atome (physique)
    'fas fa-dna',            // ADN (biologie)
    'fas fa-square-root-alt', // Racine carrée (mathématiques avancées)
    'fas fa-history',        // Histoire
    'fas fa-laptop-code',    // Informatique
    'fas fa-microscope',     // Sciences
    'fas fa-palette',        // Art
    'fas fa-book-reader'     // Littérature
];

// Vérifier si l'ID de classe est fourni
if (isset($_POST['classe_id'])) {
    $classe_id = $_POST['classe_id'];

    // Vérifier la connexion à la base de données
    if ($conn === false) {
        echo '<div class="empty-state">
                <i class="fas fa-exclamation-triangle empty-icon"></i>
                <h3 class="empty-title">خطأ في الاتصال</h3>
                <p class="empty-description">تعذر الاتصال بقاعدة البيانات</p>
              </div>';
    } else {
        // Préparer la requête SQL pour récupérer les matières de la classe
        $query = "SELECT matiere_id, nom FROM matieres WHERE classe_id = ?";
        $stmt = $conn->prepare($query);

        // Vérifier si la préparation de la requête a échoué
        if ($stmt === false) {
            echo '<div class="empty-state">
                    <i class="fas fa-exclamation-triangle empty-icon"></i>
                    <h3 class="empty-title">خطأ في الاستعلام</h3>
                    <p class="empty-description">تعذر تنفيذ الاستعلام: ' . e($conn->error) . '</p>
                  </div>';
        } else {
            $stmt->bind_param("i", $classe_id);
            $stmt->execute();
            $result = $stmt->get_result();

            // Vérifier si des matières sont trouvées
            if ($result->num_rows > 0) {
                // Parcours des matières et génération des éléments HTML
                $index = 0;
                while ($row = $result->fetch_assoc()) {
                    // Sélectionner une icône de manière cyclique
                    $icon = $icons[$index % count($icons)];
                    
                    echo '<div class="selection-item matiere-card" data-id="' . e($row['matiere_id']) . '">
                            <div class="matiere-icon"><i class="' . $icon . '"></i></div>
                            <div class="selection-item-content">' . e($row['nom']) . '</div>
                          </div>';
                    
                    $index++;
                }
            } else {
                echo '<div class="empty-state">
                        <i class="fas fa-book-open empty-icon"></i>
                        <h3 class="empty-title">لا توجد مواد</h3>
                       <p class="empty-description">لم يتم العثور على دروس لهذا القسم</p>
                      </div>';
            }

            // Fermer la connexion
            $stmt->close();
        }
    }
} else {
    echo '<div class="empty-state">
            <i class="fas fa-info-circle empty-icon"></i>
          <h3 class="empty-title">اختر القسم أولاً</h3>
           <p class="empty-description">يرجى اختيار قسم لعرض الدروس المتاحة</p>
          </div>';
}
?>

<style>
    /* Styles pour les cartes de matières qui s'harmonisent avec le thème principal */
    .matiere-card {
        position: relative;
        overflow: hidden;
        transition: all var(--transition-normal);
    }
    
    .matiere-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(247, 37, 133, 0.05));
        z-index: -1;
        opacity: 0;
        transition: opacity var(--transition-normal);
    }
    
    .matiere-card:hover::before {
        opacity: 1;
    }
    
    .matiere-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px -5px rgba(67, 97, 238, 0.1), 0 10px 10px -5px rgba(67, 97, 238, 0.04);
    }
    
    .matiere-card.selected {
        background-color: rgba(67, 97, 238, 0.1);
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
    }
    
    .matiere-card.selected::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 0 2.5rem 2.5rem 0;
        border-color: transparent var(--primary) transparent transparent;
    }
    
    .matiere-card.selected::after {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 0.25rem;
        right: 0.25rem;
        color: white;
        font-size: 0.75rem;
    }
    
    .matiere-icon {
        font-size: 1.75rem;
        margin-bottom: 0.75rem;
        color: var(--primary);
        background: linear-gradient(45deg, var(--primary), var(--accent));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
    }
</style>