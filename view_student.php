<?php
include 'db_config.php';

// Vérifier si l'ID de l'élève est fourni
if (!isset($_GET['id_eleve']) || empty($_GET['id_eleve'])) {
    echo '<div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
           <p>معرف التلميذ غير صحيح</p>
          </div>';
    exit;
}

$id_eleve = intval($_GET['id_eleve']);

// Récupérer les informations de l'élève
$sql = "SELECT e.*, c.nom_classe 
        FROM eleves e 
        JOIN classes c ON e.id_classe = c.id_classe 
        WHERE e.id_eleve = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_eleve);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="error-message">
            <i class="fas fa-user-slash"></i>
           <p>لم يتم العثور على التلميذ</p>
          </div>';
    exit;
}

$eleve = $result->fetch_assoc();

// Récupérer les notes de l'élève (si disponible)
$notes_sql = "SELECT n.*, m.nom as nom_matiere 
              FROM notes n 
              JOIN matieres m ON n.matiere_id = m.matiere_id 
              WHERE n.id_eleve = ?
              ORDER BY m.nom";

$notes_stmt = $conn->prepare($notes_sql);
$notes_stmt->bind_param("i", $id_eleve);
$notes_stmt->execute();
$notes_result = $notes_stmt->get_result();
?>

<div class="student-profile">
    <div class="student-header">
        <div class="student-avatar-large">
            <?php echo substr($eleve['nom'], 0, 1); ?>
        </div>
        <div class="student-info">
            <h3><?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></h3>
            <div class="student-class-badge"><?php echo htmlspecialchars($eleve['nom_classe']); ?></div>
            <div class="student-id">رقم التسجيل: <?php echo $eleve['id_eleve']; ?></div>
        </div>
    </div>

    <div class="student-details-grid">
        <div class="detail-card">
            <div class="detail-card-header">
                <i class="fas fa-user"></i>
                المعلومات الشخصية
            </div>
            <div class="detail-card-body">
                <div class="detail-item">
                    <span class="detail-label">الاسم</span>
                    <div class="detail-value"><?php echo htmlspecialchars($eleve['nom']); ?></div>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">اللقب</span>
                    <div class="detail-value"><?php echo htmlspecialchars($eleve['prenom']); ?></div>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">البريد الإلكتروني</span>
                    <div class="detail-value">
                        <?php echo !empty($eleve['email']) ? htmlspecialchars($eleve['email']) : '<em class="not-available">غير متوفر</em>'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="detail-card">
            <div class="detail-card-header">
                <i class="fas fa-lock"></i>
                معلومات الحساب
            </div>
            <div class="detail-card-body">
                <div class="detail-item">
                    <span class="detail-label">اسم المستخدم</span>
                    <div class="detail-value"><?php echo htmlspecialchars($eleve['login']); ?></div>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">كلمة المرور</span>
                    <div class="detail-value password-masked">••••••••</div>
                </div>
            </div>
        </div>
        
        <div class="detail-card">
            <div class="detail-card-header">
                <i class="fas fa-school"></i>
                معلومات الدراسة
            </div>
            <div class="detail-card-body">
                <div class="detail-item">
                <span class="detail-label">القسم</span>
                    <div class="detail-value"><?php echo htmlspecialchars($eleve['nom_classe']); ?></div>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">تاريخ التسجيل</span>
                    <div class="detail-value">
                        <?php 
                        // Si la date d'inscription existe, l'afficher, sinon afficher "غير متوفر"
                        echo isset($eleve['date_inscription']) ? date('d/m/Y', strtotime($eleve['date_inscription'])) : '<em class="not-available">غير متوفر</em>'; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($notes_result->num_rows > 0): ?>
    <div class="grades-section">
        <div class="grades-header">
            <i class="fas fa-chart-line"></i>
            العلامات الدراسية
        </div>
        <div class="grades-table-container">
            <table class="grades-table">
                <thead>
                    <tr>
                        <th>المادة</th>
                        <th>العلامة</th>
                        <th>التقييم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_notes = 0;
                    $count_notes = 0;
                    
                    while ($note = $notes_result->fetch_assoc()): 
                        $total_notes += $note['note'];
                        $count_notes++;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($note['nom_matiere']); ?></td>
                        <td class="grade-value <?php echo $note['note'] >= 10 ? 'passing-grade' : 'failing-grade'; ?>">
                            <?php echo number_format($note['note'], 2); ?>
                        </td>
                        <td>
                            <?php 
                            if ($note['note'] >= 16) {
                                echo '<span class="grade-excellent">ممتاز</span>';
                            } elseif ($note['note'] >= 14) {
                                echo '<span class="grade-very-good">جيد جداً</span>';
                            } elseif ($note['note'] >= 12) {
                                echo '<span class="grade-good">جيد</span>';
                            } elseif ($note['note'] >= 10) {
                                echo '<span class="grade-average">مقبول</span>';
                            } else {
                                echo '<span class="grade-poor">ضعيف</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="average-row">
                            <div class="average-container">
                                <span class="average-label">المعدل العام:</span>
                                <span class="average-value <?php echo ($total_notes / $count_notes) >= 10 ? 'passing-grade' : 'failing-grade'; ?>">
                                    <?php echo number_format($total_notes / $count_notes, 2); ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="no-grades">
        <div class="no-grades-icon">
            <i class="fas fa-book"></i>
        </div>
        <p>لا توجد علامات مسجلة لهذا الطالب.</p>
    </div>
    <?php endif; ?>
</div>

<style>
    .student-profile {
        font-family: 'Cairo', sans-serif;
    }
    
    .student-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        border: 1px solid #eee;
    }
    
    .student-avatar-large {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #1a5276, #2980b9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 15px;
        color: white;
        font-size: 28px;
        font-weight: 700;
        box-shadow: 0 3px 10px rgba(41, 128, 185, 0.3);
    }
    
    .student-info {
        flex: 1;
    }
    
    .student-info h3 {
        margin: 0 0 8px 0;
        color: #2c3e50;
        font-size: 20px;
    }
    
    .student-class-badge {
        display: inline-block;
        background-color: #1a5276;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .student-id {
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .student-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .detail-card {
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .detail-card-header {
        background-color: #1a5276;
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        font-size: 16px;
        display: flex;
        align-items: center;
    }
    
    .detail-card-header i {
        margin-left: 10px;
    }
    
    .detail-card-body {
        padding: 15px;
    }
    
    .detail-item {
        margin-bottom: 12px;
    }
    
    .detail-item:last-child {
        margin-bottom: 0;
    }
    
    .detail-label {
        display: block;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
        font-size: 14px;
    }
    
    .detail-value {
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #eee;
        font-size: 14px;
    }
    
    .password-masked {
        letter-spacing: 2px;
        font-weight: bold;
    }
    
    .not-available {
        color: #95a5a6;
        font-style: italic;
    }
    
    .grades-section {
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
    }
    
    .grades-header {
        background-color: #27ae60;
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        font-size: 16px;
        display: flex;
        align-items: center;
    }
    
    .grades-header i {
        margin-left: 10px;
    }
    
    .grades-table-container {
        padding: 15px;
        overflow-x: auto;
    }
    
    .grades-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .grades-table th,
    .grades-table td {
        padding: 10px;
        text-align: right;
        border-bottom: 1px solid #eee;
    }
    
    .grades-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .grades-table tr:last-child td {
        border-bottom: none;
    }
    
    .grade-value {
        font-weight: 600;
        text-align: center;
    }
    
    .passing-grade {
        color: #27ae60;
    }
    
    .failing-grade {
        color: #c0392b;
    }
    
    .grade-excellent {
        background-color: rgba(46, 204, 113, 0.2);
        color: #27ae60;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 600;
        display: inline-block;
    }
    
    .grade-very-good {
        background-color: rgba(39, 174, 96, 0.2);
        color: #27ae60;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 600;
        display: inline-block;
    }
    
    .grade-good {
        background-color: rgba(41, 128, 185, 0.2);
        color: #2980b9;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 600;
        display: inline-block;
    }
    
    .grade-average {
        background-color: rgba(243, 156, 18, 0.2);
        color: #f39c12;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 600;
        display: inline-block;
    }
    
    .grade-poor {
        background-color: rgba(192, 57, 43, 0.2);
        color: #c0392b;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 600;
        display: inline-block;
    }
    
    .average-row {
        background-color: #f8f9fa;
    }
    
    .average-container {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
    }
    
    .average-label {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .average-value {
        font-weight: 700;
        font-size: 16px;
        padding: 3px 10px;
        border-radius: 4px;
        background-color: #f8f9fa;
        border: 1px solid #eee;
    }
    
    .no-grades {
        background-color: white;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
    }
    
    .no-grades-icon {
        font-size: 48px;
        color: #bdc3c7;
        margin-bottom: 15px;
    }
    
    .no-grades p {
        color: #7f8c8d;
        font-size: 16px;
    }
    
    .error-message {
        background-color: rgba(192, 57, 43, 0.1);
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        color: #c0392b;
    }
    
    .error-message i {
        font-size: 36px;
        margin-bottom: 10px;
    }
    
    @media (max-width: 768px) {
        .student-details-grid {
            grid-template-columns: 1fr;
        }
        
        .student-avatar-large {
            width: 60px;
            height: 60px;
            font-size: 24px;
        }
        
        .student-info h3 {
            font-size: 18px;
        }
    }
</style>
