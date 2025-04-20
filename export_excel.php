<?php
include 'db_config.php';
require_once 'libs/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

// Récupérer l'identifiant de la classe depuis les paramètres GET
$classe_id = isset($_GET['classe_id']) ? intval($_GET['classe_id']) : 0;

// Construire la requête SQL en fonction de l'identifiant de la classe
$sql = "SELECT e.nom, e.prenom, e.email, c.nom_classe, e.login, e.mp
        FROM eleves e
        JOIN classes c ON e.id_classe = c.id_classe";

if ($classe_id > 0) {
    $sql .= " WHERE e.id_classe = $classe_id";
}

$result = $conn->query($sql);

// Préparer les données pour l'exportation
$data = [];
$data[] = ['Nom', 'Prénom', 'Email', 'Classe', 'Login', 'Mot de passe'];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        $row['nom'],
        $row['prenom'],
        $row['email'],
        $row['nom_classe'],
        $row['login'],
        $row['mp']
    ];
}

// Générer et télécharger le fichier Excel
SimpleXLSXGen::fromArray($data)->downloadAs('eleves.xlsx');
exit;
?>
