#!/usr/bin/env bash
# ============================================================
# Création de l'utilisateur de sauvegarde avec le mot de passe
# issu de la variable d'environnement BACKUP_PASSWORD.
# Ce script est exécuté par le conteneur MariaDB au premier
# démarrage, après le traitement des fichiers .sql.
# ============================================================
set -euo pipefail

mysql -u root -p"${MYSQL_ROOT_PASSWORD}" --port=6033 <<-EOSQL
    CREATE USER IF NOT EXISTS 'backup'@'localhost'
        IDENTIFIED BY '${BACKUP_PASSWORD}';

    -- Droits strictement nécessaires pour mysqldump sur biblio_bd
    GRANT SELECT, LOCK TABLES, SHOW VIEW, TRIGGER
        ON \`${MYSQL_DATABASE}\`.* TO 'backup'@'localhost';

    -- RELOAD est un privilège global requis pour FLUSH TABLES
    GRANT RELOAD ON *.* TO 'backup'@'localhost';

    FLUSH PRIVILEGES;
EOSQL

echo "[OK] Utilisateur backup créé avec droits limités sur ${MYSQL_DATABASE}"
