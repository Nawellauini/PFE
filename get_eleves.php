<?php
include 'db_config.php';
session_start();

if (isset($_POST['classe_id'], $_POST['matiere_id']) && isset($_SESSION['id_professeur'])) {
    $classe_id = $_POST['classe_id'];
    $matiere_id = $_POST['matiere_id'];
    $id_professeur = $_SESSION['id_professeur'];

    // Vérifier que le professeur a accès à cette classe
    $query_access = "SELECT COUNT(*) as count FROM professeurs_classes 
                    WHERE id_professeur = ? AND id_classe = ?";
    $stmt_access = $conn->prepare($query_access);
    $stmt_access->bind_param("ii", $id_professeur, $classe_id);
    $stmt_access->execute();
    $result_access = $stmt_access->get_result();
    $access = $result_access->fetch_assoc()['count'];

    if (!$access) {
        echo '<div class="empty-state">
                <i class="fas fa-lock empty-icon"></i>
               <h3 class="empty-title">غير مصرح به</h3>
                <p class="empty-description">ليس لديك الصلاحيات الكافية للوصول إلى هذه القسم.</p>
              </div>';
        exit;
    }

    // Récupérer le nom de la matière
    $query_matiere = "SELECT nom FROM matieres WHERE matiere_id = ?";
    $stmt_matiere = $conn->prepare($query_matiere);
    $stmt_matiere->bind_param("i", $matiere_id);
    $stmt_matiere->execute();
    $result_matiere = $stmt_matiere->get_result();
    
    if ($result_matiere->num_rows === 0) {
        echo '<div class="empty-state">
                <i class="fas fa-exclamation-circle empty-icon"></i>
                <h3 class="empty-title">مادة غير موجودة</h3>
                <p class="empty-description">المادة المحددة غير موجودة</p>
              </div>';
        exit;
    }
    
    $matiere_nom = $result_matiere->fetch_assoc()['nom'];

    // Récupérer le nom de la classe
    $query_classe = "SELECT nom_classe FROM classes WHERE id_classe = ?";
    $stmt_classe = $conn->prepare($query_classe);
    $stmt_classe->bind_param("i", $classe_id);
    $stmt_classe->execute();
    $result_classe = $stmt_classe->get_result();
    
    if ($result_classe->num_rows === 0) {
        echo '<div class="empty-state">
                <i class="fas fa-exclamation-circle empty-icon"></i>
<h3 class="empty-title">القسم غير موجود</h3>
               <p class="empty-description">القسم المحدد غير موجود.</p>
              </div>';
        exit;
    }
    
    $classe_nom = $result_classe->fetch_assoc()['nom_classe'];

    // Récupérer les élèves de la classe sélectionnée
    $query = "SELECT id_eleve, nom, prenom FROM eleves WHERE id_classe = ? ORDER BY nom, prenom";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<div class="form-header">';
        echo '<div class="form-info">';
        echo '<div class="info-item"><span class="info-label">الفصل:</span> <span class="info-value">' . htmlspecialchars($classe_nom) . '</span></div>';
        echo '<div class="info-item"><span class="info-label">المادة:</span> <span class="info-value">' . htmlspecialchars($matiere_nom) . '</span></div>';
        echo '</div>';
        echo '</div>';

        echo '<form id="form_notes" method="POST">';
        echo '<input type="hidden" name="classe_id" value="' . $classe_id . '">';
        echo '<input type="hidden" name="matiere_id" value="' . $matiere_id . '">';
        
        echo '<div class="form-group">';
        echo '<label for="trimestre" class="form-label">اختيار الثلاثي:</label>';
        echo '<select name="trimestre" id="trimestre" class="form-select" required>';
        echo '<option value="" disabled selected>-- اختر الثلاثي --</option>';
        echo '<option value="1">الثلاثي الأول</option>';
        echo '<option value="2">الثلاثي الثاني</option>';
        echo '<option value="3">الثلاثي الثالث</option>';
        echo '</select>';
        echo '</div>';

        echo '<div class="table-container">';
        echo '<table class="table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th width="5%">#</th>';
        echo '<th width="55%">اسم التلميذ</th>';
        echo '<th width="40%">النتيجة (من 0 إلى 20)</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $count++;
            echo '<tr>';
            echo '<td>' . $count . '</td>';
            echo '<td>' . htmlspecialchars($row['nom']) . ' ' . htmlspecialchars($row['prenom']) . '</td>';
            echo '<td><input type="number" name="notes[' . $row['id_eleve'] . ']" class="table-input" min="0" max="20" step="0.5" placeholder="0-20"></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        echo '<div class="form-actions">';
        echo '<button type="button" id="btn_verification" class="btn btn-primary">';
        echo '<i class="fas fa-check"></i> <span>تحقق من النتائج</span>';
        echo '</button>';
        echo '</div>';
        
        echo '</form>';

        // Ajouter des styles spécifiques pour cette page
        echo '<style>
            .form-header {
                margin-bottom: 1.5rem;
            }
            
            .form-info {
                display: flex;
                flex-wrap: wrap;
                gap: 1.5rem;
                background-color: rgba(37, 99, 235, 0.05);
                padding: 1rem;
                border-radius: 0.5rem;
                border: 1px solid rgba(37, 99, 235, 0.1);
            }
            
            .info-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .info-label {
                font-weight: 600;
                color: var(--primary);
            }
            
            .info-value {
                font-weight: 500;
            }
            
            .form-actions {
                margin-top: 1.5rem;
                display: flex;
                justify-content: center;
            }
            
            .table-input {
                width: 100%;
                padding: 0.625rem;
                border: 1px solid var(--input);
                border-radius: 0.375rem;
                text-align: center;
                font-family: "Cairo", sans-serif;
                transition: all 0.2s ease;
            }
            
            .table-input:focus {
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
                outline: none;
            }
            
            .table-input.is-invalid {
                border-color: var(--destructive);
                background-color: rgba(239, 68, 68, 0.05);
            }
            
            .table tbody tr:nth-child(odd) {
                background-color: rgba(37, 99, 235, 0.02);
            }
            
            .table tbody tr:hover {
                background-color: rgba(37, 99, 235, 0.05);
            }
            
            .table th:first-child,
            .table td:first-child {
                text-align: center;
                font-weight: 600;
                color: var(--muted-foreground);
            }
            
            .table-container {
                position: relative;
            }
            
            .table-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(255, 255, 255, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
                border-radius: var(--radius);
            }
            
            .table-loading {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .table-loading-text {
                font-weight: 600;
                color: var(--primary);
            }
            
            @media (max-width: 640px) {
                .form-info {
                    flex-direction: column;
                    gap: 0.75rem;
                }
                
                .table th:first-child,
                .table td:first-child {
                    display: none;
                }
            }
        </style>';

        // Ajouter le script pour charger les notes existantes
        echo '<script>
            $(document).ready(function() {
                $("#trimestre").change(function() {
                    const trimestre = $(this).val();
                    if (trimestre) {
                        // Afficher un indicateur de chargement
                        $(".table-input").val("").attr("disabled", true);
                        
                        // Ajouter un overlay de chargement
                        $(".table-container").append(`
                            <div class="table-overlay">
                                <div class="table-loading">
                                    <div class="spinner"></div>
                                    <div class="table-loading-text">جاري تحميل الدرجات...</div>
                                </div>
                            </div>
                        `);
                        
                        // Charger les notes existantes
                        $.ajax({
                            type: "POST",
                            url: "get_notes.php",
                            data: {
                                classe_id: ' . $classe_id . ',
                                matiere_id: ' . $matiere_id . ',
                                trimestre: trimestre
                            },
                            dataType: "json",
                            success: function(response) {
                                // Supprimer l\'overlay de chargement
                                $(".table-overlay").remove();
                                
                                if (response.success) {
                                    // Remplir les champs avec les notes existantes
                                    const notes = response.notes;
                                    for (const eleveId in notes) {
                                        $("input[name=\'notes[" + eleveId + "]\']").val(notes[eleveId]);
                                    }
                                    
                                    // Afficher un message si des notes ont été chargées
                                    if (Object.keys(notes).length > 0) {
                                        Swal.fire({
                                            title: "تم التحميل!",
                                           text: "تم تحميل النتائج بنجاح."
                                            icon: "info",
                                            confirmButtonText: "حسنًا",
                                            timer: 2000,
                                            timerProgressBar: true
                                        });
                                    }
                                }
                                
                                // Réactiver les champs
                                $(".table-input").attr("disabled", false);
                            },
                            error: function() {
                                // Supprimer l\'overlay de chargement
                                $(".table-overlay").remove();
                                
                                // Réactiver les champs en cas d\'erreur
                                $(".table-input").attr("disabled", false);
                                
                                Swal.fire({
                                    title: "خطأ!",
                                    text: "حدث خطأ أثناء تحميل النتائج."
                                    icon: "error",
                                    confirmButtonText: "حسنًا"
                                });
                            }
                        });
                    }
                });
                
                // Validation des champs de notes
                $(".table-input").on("input", function() {
                    const value = $(this).val();
                    if (value !== "") {
                        const num = parseFloat(value);
                        if (isNaN(num) || num < 0 || num > 20) {
                            $(this).addClass("is-invalid");
                        } else {
                            $(this).removeClass("is-invalid");
                        }
                    } else {
                        $(this).removeClass("is-invalid");
                    }
                });
                
                // Permettre l\'utilisation de la virgule comme séparateur décimal
                $(".table-input").on("blur", function() {
                    const value = $(this).val().replace(",", ".");
                    if (value !== "") {
                        const num = parseFloat(value);
                        if (!isNaN(num) && num >= 0 && num <= 20) {
                            $(this).val(num);
                        }
                    }
                });
                
                // Navigation avec les touches fléchées
                $(".table-input").on("keydown", function(e) {
                    const key = e.which;
                    const inputs = $(".table-input");
                    const index = inputs.index(this);
                    
                    if (key === 38) { // Flèche haut
                        if (index > 0) {
                            inputs.eq(index - 1).focus();
                            e.preventDefault();
                        }
                    } else if (key === 40) { // Flèche bas
                        if (index < inputs.length - 1) {
                            inputs.eq(index + 1).focus();
                            e.preventDefault();
                        }
                    }
                });
            });
        </script>';
    } else {
        echo '<div class="empty-state">';
        echo '<i class="fas fa-user-graduate empty-icon"></i>';
        echo '<h3 class="empty-title">لا يوجد أي تلميذ في هذا القسم</h3>';
        echo '<p class="empty-description">لا يوجد تلاميذ مسجلون في هذا القسم.</p>';
        echo '</div>';
    }
} else {
    echo '<div class="empty-state">';
    echo '<i class="fas fa-exclamation-triangle empty-icon"></i>';
    echo '<h3 class="empty-title">خطأ</h3>';
    echo '<p class="empty-description">المعلومات غير مكتملة. يرجى اختيار القسم والمادة.</p>';
    echo '</div>';
}
?>