<?php
require 'db_config.php';

if (!isset($_POST['id_classe'])) {
    echo '<option value="">خطأ في تحميل الطلاب</option>';
    exit;
}

$id_classe = intval($_POST['id_classe']);

$stmt = $conn->prepare("SELECT id_eleve, nom, prenom FROM eleves WHERE id_classe = ? ORDER BY nom, prenom");
$stmt->bind_param("i", $id_classe);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<option value="">-- اختر طالبًا --</option>';
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . $row['id_eleve'] . '">' . htmlspecialchars($row['nom'] . ' ' . $row['prenom']) . '</option>';
    }
} else {
    echo '<option value="">لا يوجد طلاب في هذا الفصل</option>';
}
?>
