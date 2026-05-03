#!/usr/bin/env bash
# ============================================================
#  SCRIPT DE DÉPLOIEMENT PMB - Base Documentaire
#  Usage : sudo ./deploy.sh
# ============================================================

set -euo pipefail

# --- Couleurs ---
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

info()    { echo -e "${CYAN}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }
step()    { echo -e "\n${BOLD}${CYAN}══════════════════════════════════════════${NC}"; \
            echo -e "${BOLD}${CYAN}  $*${NC}"; \
            echo -e "${BOLD}${CYAN}══════════════════════════════════════════${NC}"; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# ============================================================
# ÉTAPE 0 : Bannière
# ============================================================
echo -e "${BOLD}"
echo "  ██████╗ ███╗   ███╗██████╗ "
echo "  ██╔══██╗████╗ ████║██╔══██╗"
echo "  ██████╔╝██╔████╔██║██████╔╝"
echo "  ██╔═══╝ ██║╚██╔╝██║██╔══██╗"
echo "  ██║     ██║ ╚═╝ ██║██████╔╝"
echo "  ╚═╝     ╚═╝     ╚═╝╚═════╝ "
echo -e "${NC}"
echo -e "${BOLD}  Déploiement Docker - Base Documentaire PMB${NC}"
echo -e "  Qualisy Consulting — $(date '+%d/%m/%Y %H:%M')\n"

# ============================================================
# ÉTAPE 1 : Vérifications prérequis
# ============================================================
step "ÉTAPE 1 : Vérification des prérequis"

# Docker
if ! command -v docker &>/dev/null; then
    warn "Docker non trouvé. Installation via le dépôt officiel Docker..."
    apt-get update -qq
    apt-get install -y -qq ca-certificates curl gnupg lsb-release
    install -m 0755 -d /usr/share/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
        | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] \
https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" \
        > /etc/apt/sources.list.d/docker.list
    apt-get update -qq
    apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-compose-plugin
    systemctl enable docker --now
    success "Docker installé (via dépôt officiel + GPG)"
else
    DOCKER_VERSION=$(docker --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    success "Docker $DOCKER_VERSION trouvé"
fi

# Docker Compose
if ! docker compose version &>/dev/null 2>&1; then
    warn "Docker Compose plugin non trouvé. Installation..."
    apt-get update -qq && apt-get install -y -qq docker-compose-plugin
    success "Docker Compose installé"
else
    COMPOSE_VERSION=$(docker compose version --short 2>/dev/null || echo "inconnu")
    success "Docker Compose $COMPOSE_VERSION trouvé"
fi

# Ports disponibles
for PORT in 80 443; do
    if ss -tlnp | grep -q ":${PORT} " 2>/dev/null; then
        warn "Le port $PORT est déjà utilisé. Vérifiez qu'aucun service ne le bloque."
    else
        success "Port $PORT disponible"
    fi
done

# ============================================================
# ÉTAPE 2 : Fichier .env
# ============================================================
step "ÉTAPE 2 : Configuration de l'environnement"

if [[ ! -f ".env" ]]; then
    info "Création du fichier .env depuis .env.example..."
    cp .env.example .env

    # Génération automatique de mots de passe forts
    ROOT_PASS=$(openssl rand -base64 20 | tr -dc 'A-Za-z0-9!@#' | head -c 20)
    USER_PASS=$(openssl rand -base64 20 | tr -dc 'A-Za-z0-9!@#' | head -c 20)
    BACK_PASS=$(openssl rand -base64 20 | tr -dc 'A-Za-z0-9!@#' | head -c 20)

    sed -i "s/ChangeMeRootStrong!/${ROOT_PASS}/" .env
    sed -i "s/ChangeMeUserStrong!/${USER_PASS}/" .env
    sed -i "s/ChangeMeBackupStrong!/${BACK_PASS}/" .env

    success ".env créé avec des mots de passe générés automatiquement"
    echo -e "\n${YELLOW}  ⚠  Vos mots de passe ont été enregistrés dans .env${NC}"
    echo -e "${YELLOW}     Conservez ce fichier en lieu sûr.${NC}"
    echo -e "${YELLOW}     Les mots de passe DB sont lus depuis .env au démarrage${NC}"
    echo -e "${YELLOW}     (aucun mot de passe en dur dans le SQL).\n${NC}"
else
    success ".env existant détecté — conservé tel quel"
fi

# Charger les variables d'environnement
source .env

# ============================================================
# ÉTAPE 2b : Téléchargement des sources vendorisées
# ============================================================
step "ÉTAPE 2b : Préparation des sources"

if [[ ! -f "app/vendor/pmb7.4.1.zip" ]] || [[ ! -f "app/vendor/yaz.tgz" ]]; then
    info "Téléchargement des sources (PMB + php-yaz)..."
    bash scripts/fetch-vendors.sh
else
    success "Sources vendorisées déjà présentes"
fi

# ============================================================
# ÉTAPE 3 : Construction des images Docker
# ============================================================
step "ÉTAPE 3 : Construction des images Docker"
info "Cela peut prendre 5-10 minutes..."

docker compose build --no-cache 2>&1 | while IFS= read -r line; do
    # Affichage filtré : ne montrer que les étapes importantes
    if echo "$line" | grep -qE "^(Step|#[0-9]+|Successfully|ERROR)"; then
        echo "  $line"
    fi
done

success "Images construites avec succès"

# ============================================================
# ÉTAPE 4 : Démarrage des conteneurs
# ============================================================
step "ÉTAPE 4 : Démarrage des services"

docker compose up -d

info "Attente du démarrage de MySQL (jusqu'à 60s)..."
TRIES=0
until docker compose exec -T db mysqladmin ping \
      -h 127.0.0.1 --port=6033 \
      -u root -p"${MYSQL_ROOT_PASSWORD}" \
      --silent 2>/dev/null; do
    TRIES=$((TRIES + 1))
    if [[ $TRIES -ge 30 ]]; then
        error "MySQL n'a pas démarré dans le délai imparti. Vérifiez : docker compose logs db"
    fi
    sleep 2
    echo -n "."
done
echo ""
success "MySQL opérationnel"

info "Attente du démarrage d'Apache (jusqu'à 30s)..."
sleep 10
if docker compose exec -T app curl -sk https://localhost:9002/ -o /dev/null; then
    success "Apache opérationnel"
else
    warn "Apache met du temps à démarrer — continuons quand même"
fi

# ============================================================
# ÉTAPE 5 : Vérification santé des conteneurs
# ============================================================
step "ÉTAPE 5 : Vérification de l'état des services"

docker compose ps

FAILED=$(docker compose ps --format json 2>/dev/null \
    | python3 -c "
import sys, json
for line in sys.stdin:
    line = line.strip()
    if not line:
        continue
    try:
        s = json.loads(line)
        state = s.get('State', s.get('Status', ''))
        if state not in ('running', 'Up') and not state.startswith('Up'):
            print(s.get('Name', s.get('Service', '?')))
    except Exception:
        pass
" 2>/dev/null || true)

if [[ -n "$FAILED" ]]; then
    warn "Certains conteneurs semblent avoir des problèmes : $FAILED"
    warn "Consultez les logs : docker compose logs <service>"
else
    success "Tous les conteneurs sont en cours d'exécution"
fi

# ============================================================
# ÉTAPE 6 : Test de connectivité
# ============================================================
step "ÉTAPE 6 : Tests de connectivité"

# Test accès HTTP → redirection HTTPS
HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null || echo "000")
if [[ "$HTTP_CODE" == "301" ]] || [[ "$HTTP_CODE" == "302" ]]; then
    success "Redirection HTTP → HTTPS fonctionnelle (code $HTTP_CODE)"
elif [[ "$HTTP_CODE" == "200" ]]; then
    success "HTTP accessible (code 200)"
else
    warn "HTTP retourne le code $HTTP_CODE (normal si Nginx démarre encore)"
fi

# Test HTTPS
HTTPS_CODE=$(curl -sk -o /dev/null -w "%{http_code}" https://localhost/ 2>/dev/null || echo "000")
if [[ "$HTTPS_CODE" =~ ^(200|301|302|404)$ ]]; then
    success "HTTPS accessible (code $HTTPS_CODE)"
else
    warn "HTTPS retourne $HTTPS_CODE — attendez quelques secondes et réessayez"
fi

# Test connexion DB depuis le conteneur app
DB_TEST=$(docker compose exec -T app php -r "
try {
    \$pdo = new PDO(
        'mysql:host=db;port=${MYSQL_PORT:-6033};dbname=${MYSQL_DATABASE}',
        '${MYSQL_USER}',
        '${MYSQL_PASSWORD}'
    );
    echo 'OK';
} catch(Exception \$e) {
    echo 'FAIL: ' . \$e->getMessage();
}
" 2>/dev/null || echo "SKIP")

if [[ "$DB_TEST" == "OK" ]]; then
    success "Connexion PHP → MySQL vérifiée"
else
    warn "Test DB : $DB_TEST"
fi

# ============================================================
# ÉTAPE 7 : Résumé final
# ============================================================
step "DÉPLOIEMENT TERMINÉ"

SERVER_IP=$(hostname -I | awk '{print $1}')

echo -e "${GREEN}${BOLD}"
echo "  ✅  PMB est déployé et opérationnel !"
echo -e "${NC}"
echo -e "${BOLD}  Accès à l'application :${NC}"
echo -e "  🌐  https://${SERVER_IP}/pmb/"
echo -e "  🌐  http://${SERVER_IP}/pmb/        (redirigé vers HTTPS)"
echo ""
echo -e "${YELLOW}  ⚠  L'installeur PMB (tables/install.php) est bloqué par Nginx.${NC}"
echo -e "${YELLOW}     Pour une première installation, commentez temporairement${NC}"
echo -e "${YELLOW}     le bloc 'location ~ ^/pmb/tables/' dans nginx/conf/pmb.conf,${NC}"
echo -e "${YELLOW}     puis relancez : docker compose restart web${NC}"
echo ""
echo -e "${BOLD}  Paramètres de connexion à la base de données :${NC}"
echo -e "  Host     : db"
echo -e "  Port     : ${MYSQL_PORT:-6033}"
echo -e "  Database : ${MYSQL_DATABASE}"
echo -e "  User     : ${MYSQL_USER}"
echo -e "  Password : (voir fichier .env)"
echo ""
echo -e "${BOLD}  Identifiants PMB par défaut :${NC}"
echo -e "  Login    : admin"
echo -e "  Password : admin  ← À changer immédiatement !"
echo ""
echo -e "${BOLD}  Commandes utiles :${NC}"
echo -e "  docker compose ps                  # Statut des services"
echo -e "  docker compose logs -f app         # Logs Apache"
echo -e "  docker compose logs -f db          # Logs MySQL"
echo -e "  docker compose logs -f web         # Logs Nginx"
echo -e "  docker compose restart <service>   # Redémarrer un service"
echo -e "  docker compose down                # Arrêter"
echo -e "  docker compose down -v             # Arrêter + supprimer volumes"
echo ""
echo -e "${YELLOW}  ⚠  Certificat SSL auto-signé (test uniquement).${NC}"
echo -e "${YELLOW}     Pour la production, remplacez par Let's Encrypt.${NC}"
echo ""
