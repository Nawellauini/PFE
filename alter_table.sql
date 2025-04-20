ALTER TABLE candidatures_professeurs
ADD COLUMN login VARCHAR(50) AFTER email,
ADD COLUMN mot_de_passe VARCHAR(255) AFTER login; 