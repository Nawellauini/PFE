<?php
$mysqli = new mysqli("localhost", "root", "", "u504721134_formation");
if ($mysqli->connect_error) {
    die("إخفاق في الاتصال بقاعدة البيانات: " . $mysqli->connect_error);
}

if (isset($_POST['id_classe']) && isset($_POST['nom_classe'])) {
    $id_classe = intval($_POST['id_classe']);
    $nom_classe = $mysqli->real_escape_string(trim($_POST['nom_classe']));

    if (!empty($nom_classe)) {
        $sql = "UPDATE classes SET nom_classe = '$nom_classe' WHERE id_classe = $id_classe";
        if ($mysqli->query($sql)) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            echo "خطأ أثناء تحديث القسم: " . $mysqli->error;
        }
    } else {
        echo "لا يمكن أن يكون اسم القسم فارغًا.";
    }
} else {
    echo "البيانات غير كافية.";
}
header("Location: classes_admin.php?modification=success");
exit;
header("Location: classes_admin.php?modification=error");
exit;

?>
