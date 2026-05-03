#!/usr/bin/env bash
# ============================================================
#  Télécharge les sources vendorisées nécessaires au build Docker.
#  À lancer UNE FOIS avant le premier `docker compose build`.
# ============================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VENDOR_DIR="${SCRIPT_DIR}/app/vendor"
mkdir -p "$VENDOR_DIR"

# SHA-256 des archives de référence (à mettre à jour si les sources changent)
PMB_SHA256="7a85e2a854ca32b175c01de94b8eaaee9d19b1ccea03a1b59500e565bdb5b780"
YAZ_SHA256="f35572c7fa3e9d0f52a60d34ed0b90da67f972d56c202ccd7c2272ab0ad24088"

verify_sha256() {
    local file="$1"
    local expected="$2"
    local actual
    actual=$(sha256sum "$file" | awk '{print $1}')
    if [[ "$actual" != "$expected" ]]; then
        echo "[ERROR] Checksum invalide pour $(basename "$file")"
        echo "  Attendu  : $expected"
        echo "  Calculé  : $actual"
        rm -f "$file"
        exit 1
    fi
    echo "[OK]   Checksum vérifié : $(basename "$file")"
}

# --- PMB 7.4.1 ---
PMB_ZIP="${VENDOR_DIR}/pmb7.4.1.zip"
if [[ ! -f "$PMB_ZIP" ]]; then
    echo "[INFO] Téléchargement PMB 7.4.1..."
    curl -fsSL "https://forge.sigb.net/attachments/download/3709/pmb7.4.1.zip" -o "$PMB_ZIP"
    verify_sha256 "$PMB_ZIP" "$PMB_SHA256"
    echo "[OK]   PMB 7.4.1 → app/vendor/pmb7.4.1.zip"
else
    verify_sha256 "$PMB_ZIP" "$PMB_SHA256"
    echo "[SKIP] PMB 7.4.1 déjà présent (checksum OK)"
fi

# --- Extension PHP YAZ 1.2.4 ---
YAZ_TGZ="${VENDOR_DIR}/yaz.tgz"
if [[ ! -f "$YAZ_TGZ" ]]; then
    echo "[INFO] Téléchargement php-yaz 1.2.4..."
    curl -fsSL "https://pecl.php.net/get/yaz-1.2.4.tgz" -o "$YAZ_TGZ"
    verify_sha256 "$YAZ_TGZ" "$YAZ_SHA256"
    echo "[OK]   php-yaz 1.2.4 → app/vendor/yaz.tgz"
else
    verify_sha256 "$YAZ_TGZ" "$YAZ_SHA256"
    echo "[SKIP] php-yaz déjà présent (checksum OK)"
fi

echo ""
echo "Vendors prêts. Vous pouvez maintenant lancer :"
echo "  docker compose -f docker-compose.dev.yml up --build    # Dev"
echo "  sudo ./deploy.sh                                        # Production"
