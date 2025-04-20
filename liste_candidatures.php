<?php

require 'db_config.php';

// Récupérer les candidatures en attente
$sql = "SELECT * FROM candidatures_professeurs WHERE statut = 'en attente'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Candidatures</title>
    <link rel="stylesheet" href="styles.css"> <!-- Ajoute un fichier CSS si besoin -->
</head>
<body>
    <h2>Liste des Candidatures des Professeurs</h2>

    <?php if ($result->num_rows > 0) : ?>
        <table border="1">
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Matière</th>
                <th>Message</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['nom']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['matiere']); ?></td>
                    <td><?php echo htmlspecialchars($row['message']); ?></td>
                    <td>
                        <a href="reponse_email_admin.php?id=<?php echo $row['id']; ?>&statut=accepte">✅ Accepter</a>
                        <a href="reponse_email_admin.php?id=<?php echo $row['id']; ?>&statut=refuse">❌ Refuser</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else : ?>
        <p>Aucune candidature en attente.</p>
    <?php endif; ?>

</body>
</html>
