-- Créer la base et donner les droits
CREATE DATABASE IF NOT EXISTS temu_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON temu_clone.* TO 'afri_user'@'%';
FLUSH PRIVILEGES;
