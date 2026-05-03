# PMB Docker — Base de Données Documentaire

Déploiement containerisé de la plateforme **PMB** (Physalis Multimedia Bibliothèque)
pour le projet de bases documentaires éducatives en Afrique subsaharienne francophone.

## Stack technique

| Composant | Version | Rôle |
|-----------|---------|------|
| Nginx     | stable  | Reverse proxy (port 80/443 prod · 8080 dev) |
| Apache 2  | 2.4     | Serveur d'application PHP (port 9001/9002) |
| PHP       | 7.4     | Runtime PMB + extensions Z39.50 |
| MariaDB   | 10.4    | Base de données (port 6033) |
| PMB       | 7.4.1   | Application bibliothécaire |

---

## Développement local (macOS / Linux / Windows + Docker Desktop)

Aucun accès root requis. HTTP simple sur le port 8080.

```bash
# 1. Copier la config
cp .env.example .env

# 2. Télécharger les sources (une seule fois, ~120 Mo)
./scripts/fetch-vendors.sh

# 3. Construire et lancer
docker compose -f docker-compose.dev.yml up --build

# 4. Accéder à l'application
# http://localhost:8080/pmb/
```

Identifiants PMB par défaut : `admin` / `admin` — **à changer immédiatement.**

### Première installation (installeur PMB)

L'installeur est **bloqué par défaut** pour des raisons de sécurité.
Pour l'activer temporairement lors d'une (ré)installation :

1. Commenter le bloc dans `nginx/conf/pmb-dev.conf` :
   ```nginx
   # location ~ ^/pmb/tables/ {
   #     deny all;
   #     return 404;
   # }
   ```
2. `docker compose -f docker-compose.dev.yml restart web`
3. Accéder à `http://localhost:8080/pmb/tables/install.php`
4. Sur l'écran **Gestion du service MySQL**, saisir :

   | Champ          | Valeur                                            |
   |----------------|---------------------------------------------------|
   | Serveur MySQL  | `db:6033` ← **le champ Host doit contenir le port** |
   | Utilisateur    | `biblio_user`                                     |
   | Mot de passe   | valeur `MYSQL_PASSWORD` dans `.env`               |
   | Base de données| `biblio_bd`                                       |

   > Le serveur s'écrit `db:6033` (hostname Docker interne + port non standard).
   > Ne pas utiliser `localhost` ni `127.0.0.1`.

5. Une fois l'installation terminée, **remettre le bloc `deny all`** et relancer Nginx.

---

## Production (Linux, accès root)

```bash
# Prérequis : Linux (Ubuntu 20.04+), accès root
git clone <repo> pmb-docker
cd pmb-docker
chmod +x deploy.sh
sudo ./deploy.sh
```

Le script `deploy.sh` :
1. Installe Docker si absent
2. Génère des mots de passe forts automatiquement
3. Construit les images
4. Démarre les services (HTTPS sur 80/443)
5. Vérifie la connectivité

Après déploiement, l'installeur est **bloqué par défaut**.
Pour l'activer (première installation uniquement) :

1. Commenter le bloc `location ~ ^/pmb/tables/` dans `nginx/conf/pmb.conf`
2. `docker compose restart web`
3. Accéder à `https://<IP-SERVEUR>/pmb/tables/install.php`
4. Serveur MySQL : `db:6033` — Utilisateur : `biblio_user` — BDD : `biblio_bd`
5. Remettre le bloc `deny all` après installation et relancer Nginx.

---

## Commandes de gestion

```bash
# --- DEV ---
docker compose -f docker-compose.dev.yml ps
docker compose -f docker-compose.dev.yml logs -f app
docker compose -f docker-compose.dev.yml down

# --- PRODUCTION ---
docker compose ps
docker compose logs -f app   # Apache + PHP
docker compose logs -f db    # MySQL
docker compose logs -f web   # Nginx
docker compose restart app
docker compose down
docker compose build --no-cache && docker compose up -d  # Mise à jour
```

---

## Sauvegarde

```bash
# Sauvegarde manuelle
./scripts/backup.sh

# Planification automatique (tous les jours à 02h00)
echo "0 2 * * * $(pwd)/scripts/backup.sh >> $(pwd)/backup/backup.log 2>&1" | crontab -
```

Les dumps sont stockés dans `./backup/dumps/` et conservés 7 jours.

---

## Architecture réseau

### Production (HTTPS)

```
Internet
    │
    ▼ :80/:443
┌─────────┐   réseau: frontend
│  Nginx  │──────────────────────────────┐
└─────────┘                              │
                                         ▼
                              ┌──────────────────┐
                              │  Apache + PHP    │
                              │  PMB :9001/:9002 │
                              └────────┬─────────┘
                                       │ réseau: backend (isolé)
                                       ▼
                              ┌──────────────────┐
                              │  MySQL :6033     │
                              │  (non exposé)    │
                              └──────────────────┘
```

### Développement local (HTTP)

```
Navigateur
    │
    ▼ :8080
┌─────────┐   réseau: frontend_dev
│  Nginx  │──────────────────────────────┐
└─────────┘                              │
                                         ▼ :9001
                              ┌──────────────────┐
                              │  Apache + PHP    │
                              └────────┬─────────┘
                                       │ réseau: backend_dev (isolé)
                                       ▼ :6033
                              ┌──────────────────┐
                              │  MySQL           │
                              │  (exposé hôte)   │
                              └──────────────────┘
```

---

## Sécurité en production

- [ ] Remplacer le certificat auto-signé par **Let's Encrypt** (`certbot`)
- [ ] Changer les identifiants PMB par défaut (`admin/admin`)
- [ ] Configurer un firewall (`ufw allow 80,443/tcp`)
- [ ] Activer les logs centralisés
- [ ] Tester la restauration de la sauvegarde

---

## Fichiers importants

```
.env                       ← Mots de passe (NE PAS commiter)
docker-compose.yml         ← Production (HTTPS, ports 80/443)
docker-compose.dev.yml     ← Développement (HTTP, port 8080)
nginx/conf/pmb.conf        ← Nginx production
nginx/conf/pmb-dev.conf    ← Nginx développement
db/conf/my.cnf             ← Tuning MySQL
app/conf/php.ini           ← Config PHP
backup/dumps/              ← Dumps MySQL
```
