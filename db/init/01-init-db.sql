-- ============================================================
-- Initialisation de la base PMB
-- Executé automatiquement au premier démarrage du conteneur
--
-- L'utilisateur applicatif (biblio_user) est créé par le
-- conteneur MariaDB via les variables MYSQL_USER / MYSQL_PASSWORD.
-- L'utilisateur backup est créé par 02-init-users.sh.
-- ============================================================

-- Base documentaire (idempotent, fixe l'encodage)
CREATE DATABASE IF NOT EXISTS `biblio_bd`
  CHARACTER SET utf8
  COLLATE utf8_unicode_ci;
