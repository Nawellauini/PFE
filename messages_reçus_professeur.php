<?php
session_start();
require_once 'db_config.php';

// Vérifier si l'utilisateur est connecté en tant que professeur
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit;
}

$id_professeur = $_SESSION['id_professeur'];

// Récupérer les informations du professeur
$stmt_prof = $conn->prepare("SELECT nom, prenom FROM professeurs WHERE id_professeur = ?");
if ($stmt_prof === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt_prof->bind_param("i", $id_professeur);
$stmt_prof->execute();
$result_prof = $stmt_prof->get_result();
$professeur = $result_prof->fetch_assoc();
$stmt_prof->close();

// Compter les messages non lus
$query_unread = "SELECT COUNT(*) as unread_count 
                FROM message_eleve 
                WHERE id_professeur = ? AND is_read = 0";
$stmt_unread = $conn->prepare($query_unread);
if ($stmt_unread === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt_unread->bind_param("i", $id_professeur);
$stmt_unread->execute();
$result_unread = $stmt_unread->get_result();
$unread_count = $result_unread->fetch_assoc()['unread_count'];
$stmt_unread->close();

// Filtres
$filter_eleve = isset($_GET['eleve']) ? intval($_GET['eleve']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Requête pour récupérer les messages
$query = "SELECT m.*, e.nom AS nom_eleve, e.prenom AS prenom_eleve, c.nom_classe
          FROM message_eleve m
          INNER JOIN eleves e ON m.id_eleve = e.id_eleve
          INNER JOIN classes c ON e.id_classe = c.id_classe
          WHERE m.id_professeur = ?";

// Ajouter les filtres si nécessaires
$params = array($id_professeur);
$types = "i";

if ($filter_eleve > 0) {
    $query .= " AND m.id_eleve = ?";
    $params[] = $filter_eleve;
    $types .= "i";
}

if (!empty($search)) {
    $query .= " AND (m.subject LIKE ? OR m.message_text LIKE ? OR e.nom LIKE ? OR e.prenom LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

$query .= " ORDER BY m.date_sent DESC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Erreur de préparation de la requête: " . $conn->error . "<br>Requête: " . $query);
}

// Utiliser bind_param avec un tableau de paramètres
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Récupérer la liste des élèves pour le filtre
$eleves_query = "SELECT DISTINCT e.id_eleve, e.nom, e.prenom 
                FROM eleves e 
                JOIN message_eleve m ON e.id_eleve = m.id_eleve 
                WHERE m.id_professeur = ? 
                ORDER BY e.nom, e.prenom";
$stmt_eleves = $conn->prepare($eleves_query);
if ($stmt_eleves === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}
$stmt_eleves->bind_param("i", $id_professeur);
$stmt_eleves->execute();
$result_eleves = $stmt_eleves->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرسائل الواردة من الطلاب</title>
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
            max-width: 1200px;
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
            display: flex;
            align-items: center;
        }

        .student-avatar {
            width: 30px;
            height: 30px;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 12px;
            margin-left: 8px;
        }

        .student-class {
            display: inline-block;
            background-color: var(--light-color);
            color: var(--dark-color);
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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

        .action-btn-reply {
            background-color: var(--success-color);
        }

        .action-btn-reply:hover {
            background-color: #27a34c;
        }

        .attachment-link {
            display: inline-flex;
            align-items: center;
            color: var(--accent-color);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .attachment-link:hover {
            color: #2b1cc4;
            text-decoration: underline;
        }

        .attachment-link i {
            margin-left: 5px;
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

        /* Styles pour les messages non lus */
        .unread-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
        }

        .unread-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--danger-color);
            border-radius: 50%;
            margin-right: 5px;
        }

        .message-unread {
            font-weight: bold;
            background-color: rgba(229, 83, 83, 0.05);
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
            الرسائل الواردة من الطلاب
            <?php if ($unread_count > 0): ?>
                <span class="unread-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </h1>

        <div class="card">
            <div class="card-header">
                <div>
                    <i class="fas fa-envelope"></i>
                    صندوق الوارد
                </div>
                <div>
                    <?php echo $result->num_rows; ?> رسالة
                    <?php if ($unread_count > 0): ?>
                        <span style="color: #ff6b6b; margin-right: 5px;">(<?php echo $unread_count; ?> غير مقروءة)</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="filter-bar">
                    <form action="" method="GET" class="search-form">
                        <input type="text" name="search" class="search-input" placeholder="البحث في الرسائل..." value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fas fa-search search-icon"></i>
                    </form>
                    
                    <select name="eleve" id="eleve-filter" class="filter-select" onchange="this.form.submit()">
                        <option value="0">جميع الطلاب</option>
                        <?php while ($eleve = $result_eleves->fetch_assoc()): ?>
                            <option value="<?php echo $eleve['id_eleve']; ?>" <?php echo ($filter_eleve == $eleve['id_eleve']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <?php if (!empty($search) || $filter_eleve > 0): ?>
                        <a href="messages_reçus_professeur.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            إلغاء التصفية
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <table class="message-table">
                        <thead>
                            <tr>
                                <th>الطالب</th>
                                <th>الموضوع</th>
                                <th>الملف المرفق</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($message = $result->fetch_assoc()): ?>
                                <tr class="<?php echo ($message['is_read'] == 0) ? 'message-unread' : ''; ?>">
                                    <td class="message-sender">
                                        <div class="student-avatar">
                                            <?php echo substr($message['nom_eleve'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <div>
                                                <?php if ($message['is_read'] == 0): ?>
                                                    <span class="unread-indicator"></span>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($message['nom_eleve'] . ' ' . $message['prenom_eleve']); ?>
                                            </div>
                                            <div class="student-class"><?php echo htmlspecialchars($message['nom_classe']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="message-subject">
                                            <i class="fas fa-envelope<?php echo ($message['is_read'] == 0) ? '' : '-open'; ?>"></i>
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($message['attachment_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($message['attachment_path']); ?>" target="_blank" class="attachment-link">
                                                <i class="fas fa-paperclip"></i>
                                                تحميل الملف
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">لا يوجد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="message-date">
                                        <?php echo date('d/m/Y H:i', strtotime($message['date_sent'])); ?>
                                    </td>
                                    <td>
                                        <div class="message-actions">
                                            <button class="action-btn action-btn-view view-message" data-id="<?php echo $message['id']; ?>" data-student-id="<?php echo $message['id_eleve']; ?>" data-subject="<?php echo htmlspecialchars($message['subject']); ?>" data-message="<?php echo htmlspecialchars($message['message_text']); ?>" data-sender="<?php echo htmlspecialchars($message['nom_eleve'] . ' ' . $message['prenom_eleve']); ?>" data-date="<?php echo date('d/m/Y H:i', strtotime($message['date_sent'])); ?>" data-attachment="<?php echo htmlspecialchars($message['attachment_path']); ?>" title="عرض الرسالة">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="envoyer_message.php?reply_to=<?php echo $message['id_eleve']; ?>" class="action-btn action-btn-reply" title="الرد">
                                                <i class="fas fa-reply"></i>
                                            </a>
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
                            <?php if (!empty($search) || $filter_eleve > 0): ?>
                                لم يتم العثور على رسائل تطابق معايير البحث.
                            <?php else: ?>
                                لم تتلق أي رسائل من الطلاب بعد.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search) || $filter_eleve > 0): ?>
                            <a href="messages_reçus_professeur.php" class="btn btn-primary">
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
                    <div id="modalAttachment" style="display: none;">
                        <div class="message-info-label">الملف المرفق</div>
                        <a href="#" id="modalAttachmentLink" target="_blank" class="attachment-link">
                            <i class="fas fa-paperclip"></i>
                            تحميل الملف
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="replyButton" class="btn btn-primary">
                    <i class="fas fa-reply"></i>
                    الرد
                </a>
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
            // Soumission du formulaire de filtre par élève
            $('#eleve-filter').on('change', function() {
                window.location.href = 'messages_reçus_professeur.php?eleve=' + $(this).val() + '<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>';
            });

            // Afficher le message complet dans le modal
            $('.view-message').on('click', function() {
                const messageId = $(this).data('id');
                const subject = $(this).data('subject');
                const message = $(this).data('message');
                const sender = $(this).data('sender');
                const date = $(this).data('date');
                const attachment = $(this).data('attachment');
                const studentId = $(this).data('student-id');
                
                $('#modalTitle').text('عرض الرسالة');
                $('#modalSender').text(sender);
                $('#modalDate').text(date);
                $('#modalSubject').text(subject);
                $('#modalContent').text(message);
                
                // Gérer l'affichage de la pièce jointe
                if (attachment) {
                    $('#modalAttachmentLink').attr('href', attachment);
                    $('#modalAttachment').show();
                } else {
                    $('#modalAttachment').hide();
                }
                
                // Configurer le bouton de réponse
                $('#replyButton').attr('href', 'envoyer_message.php?reply_to=' + studentId);
                
                // Afficher le modal
                $('#messageModal').css('display', 'flex');
                
                // Marquer le message comme lu
                $.ajax({
                    url: 'mark_message_read.php',
                    type: 'POST',
                    data: { message_id: messageId },
                    success: function(response) {
                        // Mettre à jour l'interface utilisateur
                        const row = $(this).closest('tr');
                        row.removeClass('message-unread');
                        row.find('.unread-indicator').remove();
                        $(this).find('i.fas').removeClass('fa-envelope').addClass('fa-envelope-open');
                        
                        // Mettre à jour le compteur de messages non lus
                        let unreadCount = parseInt($('.unread-badge').text());
                        if (unreadCount > 1) {
                            $('.unread-badge').text(unreadCount - 1);
                        } else {
                            $('.unread-badge').remove();
                            $('span[style*="color: #ff6b6b"]').remove();
                        }
                    }.bind(this)
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
