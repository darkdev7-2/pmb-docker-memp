#!/usr/bin/env bash
# ============================================================
#  SCRIPT DE SAUVEGARDE PMB
#  Usage : ./scripts/backup.sh
#  Planification : crontab -e → 0 2 * * * /chemin/scripts/backup.sh
# ============================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$SCRIPT_DIR"

source .env

BACKUP_DIR="./backup/dumps"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="${BACKUP_DIR}/biblio_${TIMESTAMP}.sql.gz"
RETENTION_DAYS=7

mkdir -p "$BACKUP_DIR"

echo "[$(date)] Démarrage sauvegarde..."

docker compose exec -T db mysqldump \
    -u root -p"${MYSQL_ROOT_PASSWORD}" \
    --single-transaction \
    --routines \
    --triggers \
    --add-drop-database \
    --port="${MYSQL_PORT:-6033}" \
    "${MYSQL_DATABASE}" \
    | gzip > "$BACKUP_FILE"

SIZE=$(du -sh "$BACKUP_FILE" | cut -f1)
echo "[$(date)] Sauvegarde créée : $BACKUP_FILE ($SIZE)"

# Nettoyage des dumps de plus de RETENTION_DAYS jours
find "$BACKUP_DIR" -name "biblio_*.sql.gz" -mtime "+${RETENTION_DAYS}" -delete
echo "[$(date)] Nettoyage : dumps de plus de ${RETENTION_DAYS} jours supprimés"

echo "[$(date)] Sauvegarde terminée."
