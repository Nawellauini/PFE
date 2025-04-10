<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['id_eleve'])) {
    header("Location: login.php");
    exit;
}

$id_eleve = $_SESSION['id_eleve'];

// Récupérer les informations de l'élève
$stmt_eleve = $conn->prepare("SELECT nom, prenom FROM eleves WHERE id_eleve = ?");
if ($stmt_eleve === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt_eleve->bind_param("i", $id_eleve);
$stmt_eleve->execute();
$result_eleve = $stmt_eleve->get_result();
$eleve = $result_eleve->fetch_assoc();
$stmt_eleve->close();

// Vérifier si la colonne 'lu' existe dans la table
$has_lu_column = false;
$check_column = $conn->query("SHOW COLUMNS FROM message_profeleve LIKE 'lu'");
if ($check_column->num_rows > 0) {
    $has_lu_column = true;
}

// Marquer un message comme lu
if ($has_lu_column && isset($_GET['read']) && is_numeric($_GET['read'])) {
    $message_id = intval($_GET['read']);
    $stmt_read = $conn->prepare("UPDATE message_profeleve SET lu = 1 WHERE id = ? AND id_eleve = ?");
    if ($stmt_read === false) {
        die("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt_read->bind_param("ii", $message_id, $id_eleve);
    $stmt_read->execute();
    $stmt_read->close();
}

// Filtrer par professeur
$filter_prof = isset($_GET['prof']) ? intval($_GET['prof']) : 0;

// Recherche
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Requête pour récupérer les messages
$query = "
    SELECT m.id, m.subject, m.message_text, m.date_envoi, 
           p.id_professeur, p.nom AS nom_prof, p.prenom AS prenom_prof";

// Ajouter la colonne 'lu' si elle existe
if ($has_lu_column) {
    $query .= ", m.lu";
}

$query .= " FROM message_profeleve m
    JOIN professeurs p ON m.id_professeur = p.id_professeur
    WHERE m.id_eleve = ?";

// Ajouter les filtres si nécessaires
$params = array($id_eleve);
$types = "i";

if ($filter_prof > 0) {
    $query .= " AND p.id_professeur = ?";
    $params[] = $filter_prof;
    $types .= "i";
}

if (!empty($search)) {
    $query .= " AND (m.subject LIKE ? OR m.message_text LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY m.date_envoi DESC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Récupérer la liste des professeurs pour le filtre
$profs_query = "SELECT DISTINCT p.id_professeur, p.nom, p.prenom 
                FROM professeurs p 
                JOIN message_profeleve m ON p.id_professeur = m.id_professeur 
                WHERE m.id_eleve = ? 
                ORDER BY p.nom, p.prenom";
$stmt_profs = $conn->prepare($profs_query);
if ($stmt_profs === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt_profs->bind_param("i", $id_eleve);
$stmt_profs->execute();
$result_profs = $stmt_profs->get_result();

// Compter les messages non lus
$unread_count = 0;
if ($has_lu_column) {
    $unread_query = "SELECT COUNT(*) as count FROM message_profeleve WHERE id_eleve = ? AND lu = 0";
    $stmt_unread = $conn->prepare($unread_query);
    if ($stmt_unread === false) {
        die("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt_unread->bind_param("i", $id_eleve);
    $stmt_unread->execute();
    $unread_result = $stmt_unread->get_result();
    $unread_count = $unread_result->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرسائل الواردة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3c4b64;
            --primary-light: #5d6e8c;
            --primary-dark: #2d3a4f;
            --secondary-color: #636f83;
            --accent-color: #321fdb;
            --success-color: #2eb85c;
            --info-color: #39f;
            --warning-color: #f9b115;
            --danger-color: #e55353;
            --light-color: #ebedef;
            --dark-color: #4f5d73;
            --border-color: #d8dbe0;
            --border-radius: 4px;
            --box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f1f1f1;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 15px;
        }

        .page-title {
            margin-bottom: 20px;
            color: var(--primary-color);
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-left: 10px;
            color: var(--primary-color);
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            font-weight: 700;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header i {
            margin-left: 10px;
        }

        .card-body {
            padding: 20px;
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .search-form {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 8px 12px;
            padding-left: 35px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-family: 'Tajawal', sans-serif;
            font-size: 14px;
        }

        .search-input:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(50, 31, 219, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-family: 'Tajawal', sans-serif;
            font-size: 14px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%233c4b64' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 12px center;
            background-size: 16px;
            padding-left: 35px;
            min-width: 200px;
        }

        .filter-select:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(50, 31, 219, 0.1);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn i {
            margin-left: 5px;
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2b1cc4;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #566175;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background-color: var(--accent-color);
            color: white;
        }

        .badge i {
            margin-left: 5px;
            font-size: 10px;
        }

        .message-table {
            width: 100%;
            border-collapse: collapse;
        }

        .message-table th,
        .message-table td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid var(--border-color);
        }

        .message-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--primary-color);
        }

        .message-table tr:hover {
            background-color: #f8f9fa;
        }

        .message-table tr:last-child td {
            border-bottom: none;
        }

        .message-subject {
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .message-subject i {
            margin-left: 8px;
            color: var(--accent-color);
        }

        .message-date {
            color: #6c757d;
            font-size: 13px;
            white-space: nowrap;
        }

        .message-sender {
            color: #495057;
            font-size: 14px;
        }

        .message-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }

        .action-btn-view {
            background-color: var(--accent-color);
        }

        .action-btn-view:hover {
            background-color: #2b1cc4;
        }

        .action-btn-mark {
            background-color: var(--success-color);
        }

        .action-btn-mark:hover {
            background-color: #27a34c;
        }

        .unread-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--accent-color);
            margin-left: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 30px 20px;
        }

        .empty-icon {
            font-size: 48px;
            color: #adb5bd;
            margin-bottom: 15px;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .empty-text {
            color: #6c757d;
            margin-bottom: 15px;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-weight: 700;
            font-size: 16px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
        }

        .message-details {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message-info {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .message-info-item {
            flex: 1;
            min-width: 200px;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: var(--border-radius);
            font-size: 14px;
        }

        .message-info-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .message-content {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 15px;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .modal-footer {
            padding: 15px;
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid var(--border-color);
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
            }
            
            .message-table {
                display: block;
                overflow-x: auto;
            }
            
            .message-actions {
                flex-direction: row;
            }
            
            .action-btn {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-inbox"></i>
            الرسائل الواردة
        </h1>

        <div class="card">
            <div class="card-header">
                <div>
                    <i class="fas fa-envelope"></i>
                    صندوق الوارد
                </div>
                <?php if ($has_lu_column && $unread_count > 0): ?>
                    <div class="badge">
                        <i class="fas fa-envelope"></i>
                        <?php echo $unread_count; ?> رسائل غير مقروءة
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="filter-bar">
                    <form action="" method="GET" class="search-form">
                        <input type="text" name="search" class="search-input" placeholder="البحث في الرسائل..." value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fas fa-search search-icon"></i>
                    </form>
                    
                    <select name="prof" id="prof-filter" class="filter-select" onchange="this.form.submit()">
                        <option value="0">جميع الأساتذة</option>
                        <?php while ($prof = $result_profs->fetch_assoc()): ?>
                            <option value="<?php echo $prof['id_professeur']; ?>" <?php echo ($filter_prof == $prof['id_professeur']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prof['nom'] . ' ' . $prof['prenom']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <?php if (!empty($search) || $filter_prof > 0): ?>
                        <a href="consulter_messages.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            إلغاء التصفية
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <table class="message-table">
                        <thead>
                            <tr>
                                <th>الموضوع</th>
                                <th>المرسل</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($message = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="message-subject">
                                            <i class="fas fa-envelope<?php echo ($has_lu_column && $message['lu']) ? '-open' : ''; ?>"></i>
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                            <?php if ($has_lu_column && !$message['lu']): ?>
                                                <span class="unread-indicator" title="غير مقروءة"></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="message-sender">
                                        <?php echo htmlspecialchars($message['nom_prof'] . ' ' . $message['prenom_prof']); ?>
                                    </td>
                                    <td class="message-date">
                                        <?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?>
                                    </td>
                                    <td>
                                        <div class="message-actions">
                                            <button class="action-btn action-btn-view view-message" data-id="<?php echo $message['id']; ?>" title="قراءة الرسالة">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($has_lu_column && !$message['lu']): ?>
                                                <a href="consulter_messages.php?read=<?php echo $message['id']; ?>" class="action-btn action-btn-mark" title="تعليم كمقروءة">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <h3 class="empty-title">لا توجد رسائل</h3>
                        <p class="empty-text">
                            <?php if (!empty($search) || $filter_prof > 0): ?>
                                لم يتم العثور على رسائل تطابق معايير البحث.
                            <?php else: ?>
                                لم تتلق أي رسائل بعد.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search) || $filter_prof > 0): ?>
                            <a href="consulter_messages.php" class="btn btn-primary">
                                <i class="fas fa-inbox"></i>
                                عرض جميع الرسائل
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal pour afficher le message complet -->
    <div class="modal-overlay" id="messageModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">عرض الرسالة</div>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="message-details">
                    <div class="message-info">
                        <div class="message-info-item">
                            <div class="message-info-label">المرسل</div>
                            <div id="modalSender"></div>
                        </div>
                        <div class="message-info-item">
                            <div class="message-info-label">تاريخ الإرسال</div>
                            <div id="modalDate"></div>
                        </div>
                    </div>
                    <div class="message-info-item">
                        <div class="message-info-label">الموضوع</div>
                        <div id="modalSubject"></div>
                    </div>
                    <div class="message-content" id="modalContent"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="closeModalBtn">
                    <i class="fas fa-times"></i>
                    إغلاق
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Soumission du formulaire de filtre par professeur
            $('#prof-filter').on('change', function() {
                window.location.href = 'consulter_messages.php?prof=' + $(this).val() + '<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>';
            });

            // Afficher le message complet dans le modal
            $('.view-message').on('click', function() {
                const messageId = $(this).data('id');
                const row = $(this).closest('tr');
                
                // Récupérer les informations du message
                const subject = row.find('.message-subject').text().trim();
                const sender = row.find('.message-sender').text().trim();
                const date = row.find('.message-date').text().trim();
                
                // Récupérer le contenu complet du message via AJAX
                $.ajax({
                    url: 'get_message.php',
                    type: 'POST',
                    data: { id: messageId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#modalTitle').text('عرض الرسالة');
                            $('#modalSender').text(sender);
                            $('#modalDate').text(date);
                            $('#modalSubject').text(subject);
                            $('#modalContent').text(response.message);
                            
                            // Afficher le modal
                            $('#messageModal').css('display', 'flex');
                            
                            // Marquer le message comme lu
                            <?php if ($has_lu_column): ?>
                            if (row.find('.unread-indicator').length > 0) {
                                $.ajax({
                                    url: 'consulter_messages.php?read=' + messageId,
                                    type: 'GET',
                                    success: function() {
                                        row.find('.unread-indicator').remove();
                                        row.find('.message-subject i').removeClass('fa-envelope').addClass('fa-envelope-open');
                                        
                                        // Mettre à jour le compteur de messages non lus
                                        const unreadCount = parseInt($('.badge').text());
                                        if (unreadCount > 1) {
                                            $('.badge').html('<i class="fas fa-envelope"></i> ' + (unreadCount - 1) + ' رسائل غير مقروءة');
                                        } else {
                                            $('.badge').remove();
                                        }
                                    }
                                });
                            }
                            <?php endif; ?>
                        } else {
                            alert('حدث خطأ أثناء استرجاع الرسالة.');
                        }
                    },
                    error: function() {
                        alert('حدث خطأ أثناء الاتصال بالخادم.');
                    }
                });
            });

            // Fermer le modal
            $('#closeModal, #closeModalBtn').on('click', function() {
                $('#messageModal').hide();
            });

            // Fermer le modal en cliquant en dehors
            $(window).on('click', function(event) {
                if ($(event.target).is('#messageModal')) {
                    $('#messageModal').hide();
                }
            });
        });
    </script>
</body>
</html>
