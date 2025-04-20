<?php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter']);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

switch ($action) {
    case 'add':
        if (isset($_POST['titre'], $_POST['commentaires'], $_POST['recommandations'], $_POST['id_classe'])) {
            $titre = trim($_POST['titre']);
            $commentaires = trim($_POST['commentaires']);
            $recommandations = trim($_POST['recommandations']);
            $id_classe = intval($_POST['id_classe']);
            $id_professeur = $_SESSION['id_professeur'];

            if (empty($titre) || empty($commentaires) || empty($recommandations)) {
                $response['message'] = 'جميع الحقول مطلوبة';
            } else {
                $query = "INSERT INTO rapports (titre, commentaires, recommandations, id_classe, id_professeur, date_creation) 
                         VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($query);
                if ($stmt === false) {
                    $response['message'] = 'خطأ في إعداد الاستعلام';
                } else {
                    $stmt->bind_param("sssii", $titre, $commentaires, $recommandations, $id_classe, $id_professeur);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'تم إضافة التقرير بنجاح';
                    } else {
                        $response['message'] = 'حدث خطأ أثناء إضافة التقرير';
                    }
                }
            }
        }
        break;

    case 'edit':
        if (isset($_POST['id'], $_POST['titre'], $_POST['commentaires'], $_POST['recommandations'], $_POST['id_classe'])) {
            $id = intval($_POST['id']);
            $titre = trim($_POST['titre']);
            $commentaires = trim($_POST['commentaires']);
            $recommandations = trim($_POST['recommandations']);
            $id_classe = intval($_POST['id_classe']);

            if (empty($titre) || empty($commentaires) || empty($recommandations)) {
                $response['message'] = 'جميع الحقول مطلوبة';
            } else {
                $query = "UPDATE rapports SET titre = ?, commentaires = ?, recommandations = ?, id_classe = ? 
                         WHERE id_rapport = ? AND id_professeur = ?";
                $stmt = $conn->prepare($query);
                if ($stmt === false) {
                    $response['message'] = 'خطأ في إعداد الاستعلام';
                } else {
                    $stmt->bind_param("sssiii", $titre, $commentaires, $recommandations, $id_classe, $id, $_SESSION['id_professeur']);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'تم تحديث التقرير بنجاح';
                    } else {
                        $response['message'] = 'حدث خطأ أثناء تحديث التقرير';
                    }
                }
            }
        }
        break;

    case 'delete':
        if (isset($_POST['id'])) {
            $id = intval($_POST['id']);
            
            $query = "DELETE FROM rapports WHERE id_rapport = ? AND id_professeur = ?";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                $response['message'] = 'خطأ في إعداد الاستعلام';
            } else {
                $stmt->bind_param("ii", $id, $_SESSION['id_professeur']);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'تم حذف التقرير بنجاح';
                } else {
                    $response['message'] = 'حدث خطأ أثناء حذف التقرير';
                }
            }
        }
        break;

    default:
        $response['message'] = 'عملية غير صالحة';
        break;
}

header('Content-Type: application/json');
echo json_encode($response);
?> 