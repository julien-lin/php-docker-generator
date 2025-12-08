<?php

declare(strict_types=1);

namespace Julien;

class Installer
{
    private static array $config = [];

    public static function postInstall(): void
    {
        self::displayWelcome();
        
        // Collecter les informations
        self::collectConfiguration();
        
        // Générer les fichiers
        self::generateFiles();
        
        self::displayCompletion();
    }
    
    private static function displayWelcome(): void
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║      PHP Docker Generator - Installation Interactive     ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }
    
    private static function collectConfiguration(): void
    {
        echo "📋 Configuration de votre recette Docker...\n\n";
        
        // Noms des containers
        self::$config['apache_container'] = self::askInput('Nom du container Apache', 'apache_app');
        self::$config['mariadb_container'] = self::askInput('Nom du container MariaDB', 'mariadb_app');
        
        // Ports
        self::$config['apache_port'] = self::askInput('Port Apache', '80');
        self::$config['mariadb_port'] = self::askInput('Port MariaDB', '3306');
        
        // Credentials
        echo "\n🔐 Configuration de la base de données:\n";
        self::$config['mysql_root_password'] = self::askInput('Mot de passe root MariaDB', 'root');
        self::$config['mysql_database'] = self::askInput('Nom de la base de données', 'app_db');
        self::$config['mysql_user'] = self::askInput('Utilisateur MariaDB', 'app_user');
        self::$config['mysql_password'] = self::askInput('Mot de passe utilisateur MariaDB', 'app_password');
        
        // Options d'installation
        echo "\n🛠️  Options d'installation:\n";
        self::$config['install_symfony_cli'] = self::askQuestion('Installer Symfony CLI ? (y/N)', false);
        self::$config['install_node'] = self::askQuestion('Installer Node.js ? (y/N)', false);
        
        // PHP Configuration
        echo "\n⚙️  Configuration PHP:\n";
        self::$config['php_error_reporting'] = self::askInput('PHP Error Reporting', 'E_ALL');
        self::$config['php_display_errors'] = self::askInput('PHP Display Errors (On/Off)', 'On');
    }
    
    private static function askQuestion(string $question, bool $default = false): bool
    {
        $defaultText = $default ? 'Y' : 'N';
        echo "❓ {$question} [{$defaultText}]: ";
        
        $handle = fopen('php://stdin', 'r');
        if (!$handle) {
            return $default;
        }
        
        $answer = trim((string) fgets($handle));
        fclose($handle);
        
        if (empty($answer)) {
            return $default;
        }
        
        return strtolower($answer) === 'y' || strtolower($answer) === 'yes';
    }
    
    private static function askInput(string $question, string $default = ''): string
    {
        $defaultText = $default ? " [{$default}]" : '';
        echo "❓ {$question}{$defaultText}: ";
        
        $handle = fopen('php://stdin', 'r');
        if (!$handle) {
            return $default;
        }
        
        $answer = trim((string) fgets($handle));
        fclose($handle);
        
        return empty($answer) ? $default : $answer;
    }
    
    private static function generateFiles(): void
    {
        echo "\n📝 Génération des fichiers...\n";
        
        $baseDir = self::getProjectRoot();
        
        // Créer les dossiers nécessaires
        self::createDirectories($baseDir);
        
        // Générer les fichiers
        self::createEnvFile($baseDir);
        self::createDockerCompose($baseDir);
        self::createDockerfile($baseDir);
        self::createCustomPhpIni($baseDir);
        self::createAliases($baseDir);
        self::createDbScripts($baseDir);
        self::createHtaccess($baseDir);
        self::createDockerignore($baseDir);
        self::createGitignore($baseDir);
        self::createReadme($baseDir);
        
        echo "✅ Tous les fichiers ont été générés avec succès!\n";
    }
    
    private static function getProjectRoot(): string
    {
        return getcwd() ?: dirname(__DIR__, 1);
    }
    
    private static function createDirectories(string $baseDir): void
    {
        $directories = [
            $baseDir . '/apache',
            $baseDir . '/db',
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    private static function createEnvFile(string $baseDir): void
    {
        $content = "# Configuration Docker\n";
        $content .= "# Généré automatiquement par php-docker-generator\n\n";
        $content .= "# Configuration Apache / PHP\n";
        $content .= "APACHE_CONTAINER=" . self::$config['apache_container'] . "\n";
        $content .= "APACHE_PORT=" . self::$config['apache_port'] . "\n";
        $content .= "PHP_ERROR_REPORTING=" . self::$config['php_error_reporting'] . "\n";
        $content .= "PHP_DISPLAY_ERRORS=" . self::$config['php_display_errors'] . "\n\n";
        $content .= "# Configuration MariaDB\n";
        $content .= "MARIADB_CONTAINER=" . self::$config['mariadb_container'] . "\n";
        $content .= "MARIADB_PORT=" . self::$config['mariadb_port'] . "\n";
        $content .= "MYSQL_ROOT_PASSWORD=" . self::$config['mysql_root_password'] . "\n";
        $content .= "MYSQL_DATABASE=" . self::$config['mysql_database'] . "\n";
        $content .= "MYSQL_USER=" . self::$config['mysql_user'] . "\n";
        $content .= "MYSQL_PASSWORD=" . self::$config['mysql_password'] . "\n";
        $content .= "MYSQL_ROOT_HOST=%\n";
        
        file_put_contents($baseDir . '/.env', $content);
        
        // Créer aussi .env.example
        $exampleContent = str_replace(
            [self::$config['mysql_root_password'], self::$config['mysql_password']],
            ['changez_moi_en_production', 'changez_moi_en_production'],
            $content
        );
        $exampleContent = "# Exemple de configuration\n" . $exampleContent;
        $exampleContent = preg_replace('/^# Généré automatiquement.*$/m', '# Copiez ce fichier en .env et modifiez les valeurs', $exampleContent);
        
        file_put_contents($baseDir . '/.env.example', $exampleContent);
    }
    
    private static function createDockerCompose(string $baseDir): void
    {
        $apacheService = preg_replace('/[^a-z0-9_-]/', '_', strtolower(self::$config['apache_container']));
        $mariadbService = preg_replace('/[^a-z0-9_-]/', '_', strtolower(self::$config['mariadb_container']));
        
        $content = <<<YAML
services:
  {$apacheService}:
    build: apache
    container_name: \${APACHE_CONTAINER:-{$apacheService}}
    restart: unless-stopped
    ports:
      - "\${APACHE_PORT:-80}:80"
    volumes:
      - ./www:/var/www/html
      - ./apache/custom-php.ini:/usr/local/etc/php/conf.d/custom-php.ini
    environment:
      - PHP_ERROR_REPORTING=\${PHP_ERROR_REPORTING:-E_ALL}
      - PHP_DISPLAY_ERRORS=\${PHP_DISPLAY_ERRORS:-On}
    networks:
      - app_network
    depends_on:
      {$mariadbService}:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "wget", "--quiet", "--tries=1", "--spider", "http://localhost/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
    mem_limit: 512m
    mem_reservation: 256m
    cpus: 2.0

  {$mariadbService}:
    image: mariadb:11.3
    container_name: \${MARIADB_CONTAINER:-{$mariadbService}}
    restart: unless-stopped
    ports:
      - "\${MARIADB_PORT:-3306}:3306"
    environment:
      - MYSQL_ROOT_PASSWORD=\${MYSQL_ROOT_PASSWORD:-root}
      - MYSQL_DATABASE=\${MYSQL_DATABASE:-app_db}
      - MYSQL_USER=\${MYSQL_USER:-app_user}
      - MYSQL_PASSWORD=\${MYSQL_PASSWORD:-app_password}
      - MYSQL_ROOT_HOST=\${MYSQL_ROOT_HOST:-%}
    volumes:
      - mysql:/var/lib/mysql
      - ./db:/docker-entrypoint-initdb.d
    networks:
      - app_network
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 15s
      timeout: 10s
      retries: 10
      start_period: 60s
    mem_limit: 1g
    mem_reservation: 512m
    cpus: 2.0

networks:
  app_network:
    driver: bridge

volumes:
  mysql:
YAML;
        
        file_put_contents($baseDir . '/docker-compose.yml', $content);
    }
    
    private static function createDockerfile(string $baseDir): void
    {
        $apacheDir = $baseDir . '/apache';
        
        $content = "# 1. BASE IMAGE & ARGUMENTS\n";
        $content .= "FROM php:8.4-apache\n\n";
        $content .= "# Arguments pour les versions (bonne pratique)\n";
        $content .= "ARG NVM_VERSION=0.39.7\n";
        $content .= "ARG NODE_VERSION=20\n\n";
        $content .= "# 2. SYSTÈME ET DÉPENDANCES\n";
        $content .= "RUN apt-get update \\\n";
        $content .= "    && apt-get install -y --no-install-recommends \\\n";
        $content .= "        git \\\n";
        $content .= "        unzip \\\n";
        $content .= "        wget \\\n";
        $content .= "        libpng-dev \\\n";
        $content .= "        libjpeg-dev \\\n";
        $content .= "        libfreetype6-dev \\\n";
        $content .= "        libicu-dev \\\n";
        $content .= "        curl \\\n";
        $content .= "        nano \\\n";
        $content .= "    && rm -rf /var/lib/apt/lists/*\n\n";
        $content .= "# 3. EXTENSIONS PHP\n";
        $content .= "RUN docker-php-ext-configure gd --with-freetype --with-jpeg \\\n";
        $content .= "    && docker-php-ext-install -j\$(nproc) \\\n";
        $content .= "        gd \\\n";
        $content .= "        intl \\\n";
        $content .= "        pdo \\\n";
        $content .= "        pdo_mysql \\\n";
        $content .= "        opcache\n\n";
        $content .= "# 4. COMPOSER\n";
        $content .= "COPY --from=composer:latest /usr/bin/composer /usr/bin/composer\n\n";
        
        // Symfony CLI (conditionnel)
        if (self::$config['install_symfony_cli']) {
            $content .= "# 5. SYMFONY CLI\n";
            $content .= "RUN wget https://get.symfony.com/cli/installer -O - | bash \\\n";
            $content .= "  && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony\n\n";
        }
        
        $content .= "# 6. XDEBUG\n";
        $content .= "RUN pecl install xdebug \\\n";
        $content .= "    && docker-php-ext-enable xdebug\n\n";
        $content .= "# Copie de la configuration PHP personnalisée\n";
        $content .= "COPY custom-php.ini /usr/local/etc/php/conf.d/\n\n";
        
        // Node.js (conditionnel)
        if (self::$config['install_node']) {
            $content .= "# 7. NVM et NODE.JS\n";
            $content .= "ENV NVM_DIR=/root/.nvm\n";
            $content .= "ENV PATH=\$NVM_DIR/versions/node/v\$NODE_VERSION/bin:\$PATH\n\n";
            $content .= "RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v\$NVM_VERSION/install.sh | bash \\\n";
            $content .= "    && /bin/bash -c \"source \$NVM_DIR/nvm.sh && nvm install \$NODE_VERSION && nvm alias default \$NODE_VERSION && nvm use default\" \\\n";
            $content .= "    && rm -rf /tmp/*\n\n";
        }
        
        $content .= "# 8. CONFIGURATION APACHE\n";
        $content .= "RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \\\n";
        $content .= "    && echo \"<Directory /var/www/html/public>\\n\\\n";
        $content .= "    AllowOverride All\\n\\\n";
        $content .= "    Require all granted\\n\\\n";
        $content .= "    </Directory>\" >> /etc/apache2/apache2.conf \\\n";
        $content .= "    && a2enmod rewrite\n\n";
        $content .= "# 9. PERMISSIONS\n";
        $content .= "RUN chown -R www-data:www-data /var/www/html \\\n";
        $content .= "    && find /var/www/html -type d -exec chmod 775 {} \\; \\\n";
        $content .= "    && find /var/www/html -type f -exec chmod 644 {} \\;\n\n";
        $content .= "# 10. POINT D'ENTRÉE\n";
        $content .= "EXPOSE 80\n";
        
        file_put_contents($apacheDir . '/Dockerfile', $content);
    }
    
    private static function createCustomPhpIni(string $baseDir): void
    {
        $apacheDir = $baseDir . '/apache';
        
        $content = <<<'INI'
[PHP]
html_errors=1

; Limites de téléchargement de fichiers
upload_max_filesize = 100M
post_max_size = 100M

; Configuration mémoire et exécution
memory_limit = 256M
max_execution_time = 300
max_input_time = 300

; Xdebug - Configuration pour le débogage
xdebug.mode = develop,debug
xdebug.max_nesting_level = 256
xdebug.show_exception_trace = 0
xdebug.collect_params = 0
xdebug.log = /tmp/xdebug.log

; Configuration Xdebug pour VSCode/IDE (décommenter si nécessaire)
;xdebug.client_host = host.docker.internal
;xdebug.client_port = 9003
;xdebug.start_with_request = yes
;xdebug.idekey = VSCODE

; Timezone
date.timezone = Europe/Paris
INI;
        
        file_put_contents($apacheDir . '/custom-php.ini', $content);
    }
    
    private static function createAliases(string $baseDir): void
    {
        $apacheContainer = self::$config['apache_container'];
        $mariadbContainer = self::$config['mariadb_container'];
        
        $content = <<<BASH
# Charger les variables d'environnement depuis .env si le fichier existe
if [ -f .env ]; then
  set -a
  source .env 2>/dev/null || {
    export \$(grep -v '^#' .env | grep -v '^\$' | grep -v '^[[:space:]]*\$' | xargs)
  }
  set +a
fi

# Noms des containers (avec valeurs par défaut si .env n'existe pas)
APACHE_CONTAINER="\${APACHE_CONTAINER:-{$apacheContainer}}"
MARIADB_CONTAINER="\${MARIADB_CONTAINER:-{$mariadbContainer}}"

# alias pour installer une librairie composer
alias ccomposer='docker compose exec \${APACHE_CONTAINER} composer'
BASH;
        
        // Ajouter Symfony CLI alias si installé
        if (self::$config['install_symfony_cli']) {
            $content .= "\n# alias pour utiliser le wizard symfony\n";
            $content .= "alias cconsole='docker compose exec \${APACHE_CONTAINER} symfony console'\n";
        }
        
        $content .= <<<BASH

# alias pour entrer dans le container Apache (interactif avec -it)
alias capache='docker compose exec -it \${APACHE_CONTAINER} bash'

# alias pour entrer dans le container MariaDB (interactif avec -it)
alias cmariadb='docker compose exec -it \${MARIADB_CONTAINER} bash'

# alias pour exporter un snap de la base de données
alias db-export='docker compose exec \${MARIADB_CONTAINER} /docker-entrypoint-initdb.d/backup.sh'

# alias pour importer un snap de la base de données
alias db-import='docker compose exec \${MARIADB_CONTAINER} /docker-entrypoint-initdb.d/restore.sh'
BASH;
        
        file_put_contents($baseDir . '/aliases.sh', $content);
    }
    
    private static function createDbScripts(string $baseDir): void
    {
        $dbDir = $baseDir . '/db';
        
        // backup.sh
        $backupContent = <<<'SH'
#!/usr/bin/sh
set -euo pipefail

if [ -z "${MYSQL_DATABASE:-}" ]; then
  echo "Erreur : La variable d'environnement MYSQL_DATABASE n'est pas définie." >&2
  exit 1
fi

if [ -z "${MYSQL_ROOT_PASSWORD:-}" ]; then
  echo "Erreur : La variable d'environnement MYSQL_ROOT_PASSWORD n'est pas définie." >&2
  exit 1
fi

BACKUP_DIR="/docker-entrypoint-initdb.d"
BACKUP_FILE="${BACKUP_DIR}/init.sql"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE_TIMESTAMPED="${BACKUP_DIR}/init_${TIMESTAMP}.sql"

if [ -f "$BACKUP_FILE" ]; then
  cp "$BACKUP_FILE" "$BACKUP_FILE_TIMESTAMPED"
  echo "Ancienne sauvegarde copiée vers : $BACKUP_FILE_TIMESTAMPED"
fi

if mariadb-dump "$MYSQL_DATABASE" -uroot -p"$MYSQL_ROOT_PASSWORD" > "$BACKUP_FILE"; then
  echo "✓ Sauvegarde terminée avec succès : $BACKUP_FILE"
  if command -v du >/dev/null 2>&1; then
    SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo "  Taille : $SIZE"
  fi
else
  echo "✗ Erreur lors de la sauvegarde" >&2
  exit 1
fi
SH;
        
        file_put_contents($dbDir . '/backup.sh', $backupContent);
        chmod($dbDir . '/backup.sh', 0755);
        
        // restore.sh
        $restoreContent = <<<'SH'
#!/usr/bin/sh
set -euo pipefail

if [ -z "${MYSQL_DATABASE:-}" ]; then
  echo "Erreur : La variable d'environnement MYSQL_DATABASE n'est pas définie." >&2
  exit 1
fi

if [ -z "${MYSQL_ROOT_PASSWORD:-}" ]; then
  echo "Erreur : La variable d'environnement MYSQL_ROOT_PASSWORD n'est pas définie." >&2
  exit 1
fi

BACKUP_DIR="/docker-entrypoint-initdb.d"
BACKUP_FILE="${BACKUP_DIR}/init.sql"

if [ ! -f "$BACKUP_FILE" ]; then
  echo "Erreur : Le fichier de sauvegarde $BACKUP_FILE n'existe pas." >&2
  echo "Vérifiez que le fichier existe dans le répertoire ./db/ sur l'hôte." >&2
  exit 1
fi

if [ ! -s "$BACKUP_FILE" ]; then
  echo "Erreur : Le fichier de sauvegarde $BACKUP_FILE est vide." >&2
  exit 1
fi

echo "Attention : Cette opération va écraser la base de données $MYSQL_DATABASE"
echo "Fichier de restauration : $BACKUP_FILE"

if mariadb "$MYSQL_DATABASE" -uroot -p"$MYSQL_ROOT_PASSWORD" < "$BACKUP_FILE"; then
  echo "✓ Restauration terminée avec succès depuis : $BACKUP_FILE"
else
  echo "✗ Erreur lors de la restauration" >&2
  exit 1
fi
SH;
        
        file_put_contents($dbDir . '/restore.sh', $restoreContent);
        chmod($dbDir . '/restore.sh', 0755);
    }
    
    private static function createHtaccess(string $baseDir): void
    {
        $content = <<<'HTACCESS'
RewriteEngine On

# Rediriger toutes les requêtes vers index.php sauf les fichiers existants
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Sécurité : Empêcher l'accès aux fichiers sensibles
<FilesMatch "\.(env|log|ini|conf)$">
    Require all denied
</FilesMatch>
HTACCESS;
        
        file_put_contents($baseDir . '/.htaccess', $content);
    }
    
    private static function createDockerignore(string $baseDir): void
    {
        $content = <<<'IGNORE'
vendor/
.env
.env.local
.git/
.gitignore
.idea/
.vscode/
*.log
.DS_Store
node_modules/
www/
IGNORE;
        
        file_put_contents($baseDir . '/.dockerignore', $content);
    }
    
    private static function createGitignore(string $baseDir): void
    {
        $content = <<<'IGNORE'
/.env
/.env.local
/.env.*.local
/vendor/
/node_modules/
/.idea/
/.vscode/
*.log
.DS_Store
/www/
IGNORE;
        
        file_put_contents($baseDir . '/.gitignore', $content);
    }
    
    private static function createReadme(string $baseDir): void
    {
        $apacheContainer = self::$config['apache_container'];
        $mariadbContainer = self::$config['mariadb_container'];
        $apachePort = self::$config['apache_port'];
        $mariadbPort = self::$config['mariadb_port'];
        
        $symfonySection = '';
        if (self::$config['install_symfony_cli']) {
            $symfonySection = <<<MD

## 🎯 Configuration Symfony

### Installation d'un nouveau projet

```bash
# Entrer dans le container Apache
capache

# Créer un nouveau projet Symfony 8 directement dans www
cd /var/www/html
composer create-project symfony/skeleton:"8.0.x" ./

# Installer les dépendances supplémentaires
composer require symfony/orm-pack
composer require symfony/maker-bundle --dev
```

### Commandes Symfony utiles

```bash
# Via alias
cconsole cache:clear
cconsole doctrine:migrations:migrate
cconsole doctrine:database:create

# Ou sans alias
docker compose exec {$apacheContainer} symfony console cache:clear
```
MD;
        }
        
        $content = <<<MD
# Recipe Docker - PHP/Symfony

Configuration Docker professionnelle pour un projet PHP/Symfony avec Apache, PHP 8.4 et MariaDB.

## 🚀 Stack Technique

- **PHP** : 8.4 avec Apache (mod_rewrite activé)
- **Base de données** : MariaDB 11.3
- **Extensions PHP** : GD, Intl, MySQLi, PDO, PDO_MySQL, Opcache
- **Outils** : Composer 2{$symfonySection ? ', Symfony CLI' : ''}{$symfonySection ? ', Node.js 20 (via NVM)' : ''}, Xdebug

## 📋 Prérequis

- Docker Engine 20.10+
- Docker Compose 2.0+
- Git

## 🏗️ Structure du Projet

\`\`\`
.
├── apache/
│   ├── Dockerfile          # Image Apache/PHP personnalisée
│   └── custom-php.ini      # Configuration PHP personnalisée
├── db/
│   ├── backup.sh           # Script de sauvegarde
│   ├── restore.sh          # Script de restauration
│   └── init.sql            # Scripts SQL d'initialisation (optionnel)
├── www/                    # Code source de l'application
├── docker-compose.yml      # Configuration Docker Compose
├── .env                    # Configuration locale (ignoré par Git)
├── .env.example            # Modèle de configuration
├── .htaccess              # Configuration Apache
├── aliases.sh             # Aliases pour faciliter l'utilisation
└── README.md              # Ce fichier
\`\`\`

## 🚦 Démarrage Rapide

### 1. Configuration de l'environnement

Le fichier \`.env\` a été généré automatiquement avec vos paramètres. Vous pouvez le modifier si nécessaire.

**⚠️ Important** : Le fichier \`.env\` est automatiquement ignoré par Git. Ne commitez **JAMAIS** le fichier \`.env\` dans Git car il contient des informations sensibles.

### 2. Construction et démarrage

\`\`\`bash
# Construire les images et démarrer les containers
docker compose up -d --build

# Vérifier l'état des containers
docker compose ps

# Voir les logs
docker compose logs -f
\`\`\`

### 3. Accès aux services

- **Application web** : http://localhost:{$apachePort}
- **MariaDB** : localhost:{$mariadbPort}
  - Utilisateur root : \`root\` / Mot de passe : défini dans \`.env\`
  - Utilisateur : défini dans \`.env\`

### 4. Charger les aliases

\`\`\`bash
source aliases.sh
\`\`\`

### Commandes utiles

#### Avec les aliases (plus rapide)

\`\`\`bash
# Composer (installation de dépendances)
ccomposer install
ccomposer require package/name
{$symfonySection ? "\n# Symfony Console\ncconsole cache:clear\ncconsole doctrine:migrations:migrate\n" : ""}
# Accéder aux containers
capache    # Entrer dans le container Apache
cmariadb   # Entrer dans le container MariaDB

# Base de données
db-export  # Sauvegarder la base de données
db-import  # Restaurer la base de données
\`\`\`

#### Sans aliases (avec docker compose exec)

\`\`\`bash
# Composer
docker compose exec {$apacheContainer} composer install
docker compose exec {$apacheContainer} composer require package/name

# Accéder aux containers
docker compose exec -it {$apacheContainer} bash
docker compose exec -it {$mariadbContainer} bash

# Base de données
docker compose exec {$mariadbContainer} /docker-entrypoint-initdb.d/backup.sh
docker compose exec {$mariadbContainer} /docker-entrypoint-initdb.d/restore.sh
\`\`\`

## 🔒 Sécurité

### Bonnes pratiques implémentées

✅ **Réseau isolé** : Les services communiquent via un réseau Docker privé  
✅ **Healthchecks** : Vérification automatique de la santé des containers  
✅ **Variables d'environnement** : Mots de passe configurables via \`.env\`  
✅ **Limites de ressources** : Contrôle de la mémoire et CPU  
✅ **Versions fixées** : Images Docker versionnées pour la reproductibilité  
✅ **.dockerignore** : Exclusion des fichiers inutiles du contexte de build  

### Recommandations de sécurité

1. **Toujours utiliser \`.env.example\` comme modèle** : Copiez-le en \`.env\` et modifiez les valeurs
2. **Ne jamais commiter le fichier \`.env\`** dans Git (déjà configuré dans \`.gitignore\`)
3. **Utiliser des mots de passe forts** en production
4. **Limiter l'exposition des ports** en production (utiliser un reverse proxy)
5. **Désactiver Xdebug** en production (modifier le Dockerfile)
6. **Vérifier que \`.env\` est bien ignoré** : \`git status\` ne doit pas lister \`.env\`

## 📊 Gestion de la Base de Données

### Sauvegarde

\`\`\`bash
# Via alias
db-export

# Ou directement
docker compose exec {$mariadbContainer} /docker-entrypoint-initdb.d/backup.sh
\`\`\`

Le fichier de sauvegarde sera créé dans \`./db/init.sql\` sur l'hôte.

### Restauration

\`\`\`bash
# Via alias
db-import

# Ou directement
docker compose exec {$mariadbContainer} /docker-entrypoint-initdb.d/restore.sh
\`\`\`

### Scripts SQL d'initialisation

Placez vos scripts SQL dans le dossier \`./db/\`. Ils seront automatiquement exécutés au premier démarrage de MariaDB.

## 🐛 Débogage avec Xdebug

Xdebug est installé et configuré. Pour l'utiliser avec VSCode :

1. Décommentez les lignes dans \`apache/custom-php.ini\` :
\`\`\`ini
xdebug.client_host = host.docker.internal
xdebug.client_port = 9003
xdebug.start_with_request = yes
xdebug.idekey = VSCODE
\`\`\`

2. Configurez VSCode avec \`.vscode/launch.json\` :
\`\`\`json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "\${workspaceFolder}/www"
      }
    }
  ]
}
\`\`\`

## ⚙️ Configuration PHP

Le fichier \`apache/custom-php.ini\` contient les paramètres personnalisés :

- Limites d'upload : 100M
- Mémoire : 256M
- Timeout d'exécution : 300s
- Timezone : Europe/Paris

Modifiez selon vos besoins.

## 📝 Notes de Production

Avant de déployer en production :

1. **Désactiver le mode debug** : \`PHP_DISPLAY_ERRORS=Off\` dans \`.env\`
2. **Désactiver Xdebug** dans le Dockerfile
3. **Utiliser un reverse proxy** (Nginx/Traefik) au lieu d'exposer directement le port 80
4. **Configurer des sauvegardes automatiques** de la base de données
5. **Utiliser HTTPS** avec un certificat SSL

## 📚 Ressources

- [Documentation Docker Compose](https://docs.docker.com/compose/)
- [Documentation PHP](https://www.php.net/docs.php)
- [Documentation MariaDB](https://mariadb.com/docs/)

## 📄 Licence

Ce template est fourni tel quel pour vos projets.

---

**Créé avec ❤️ par php-docker-generator**
MD;
        
        file_put_contents($baseDir . '/README.md', $content);
    }
    
    private static function displayCompletion(): void
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║         Génération terminée avec succès !                 ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "📝 Prochaines étapes:\n";
        echo "   1. Chargez les aliases: source aliases.sh\n";
        echo "   2. Démarrez Docker: docker compose up -d --build\n";
        echo "   3. Créez votre application dans le dossier www/\n";
        echo "   4. Visitez http://localhost:" . self::$config['apache_port'] . "\n";
        echo "\n";
        echo "💡 Astuce: Utilisez 'source aliases.sh' pour charger les aliases utiles\n";
        echo "\n";
        echo "📚 Documentation:\n";
        echo "   Consultez README.md pour plus d'informations\n";
        echo "\n";
    }
}

