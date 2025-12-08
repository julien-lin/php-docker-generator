# PHP Docker Generator

Générateur de recettes Docker pour projets PHP/Symfony avec Apache et MariaDB.

## 📋 Description

Ce package permet de générer rapidement une configuration Docker complète pour vos projets PHP/Symfony via `composer create-project`. Le générateur pose des questions interactives pour personnaliser votre recette Docker selon vos besoins.

## 🚀 Installation

```bash
composer create-project julienlinard/php-docker-generator mon-projet
```

## ✨ Fonctionnalités

Le générateur vous demande de configurer :

- **Noms des containers** : Apache et MariaDB
- **Ports** : Apache et MariaDB
- **Credentials** : Mot de passe root, nom de la base de données, utilisateur et mot de passe
- **Options d'installation** :
  - Symfony CLI (optionnel)
  - Node.js via NVM (optionnel)
- **Configuration PHP** : Error reporting et display errors

## 📦 Fichiers générés

Après l'installation, vous obtenez une recette Docker complète avec :

- `docker-compose.yml` : Configuration Docker Compose
- `apache/Dockerfile` : Image Apache/PHP personnalisée
- `apache/custom-php.ini` : Configuration PHP
- `.env` : Variables d'environnement (configurées selon vos réponses)
- `.env.example` : Modèle de configuration
- `aliases.sh` : Aliases pour faciliter l'utilisation
- `db/backup.sh` : Script de sauvegarde de la base de données
- `db/restore.sh` : Script de restauration de la base de données
- `.htaccess` : Configuration Apache
- `.dockerignore` : Fichiers exclus du build
- `.gitignore` : Fichiers ignorés par Git
- `README.md` : Documentation complète de votre recette

## 🎯 Utilisation

### 1. Installer le générateur

```bash
composer create-project julienlinard/php-docker-generator mon-projet
```

### 2. Répondre aux questions

Le générateur vous posera plusieurs questions :

```
❓ Nom du container Apache [apache_app]: 
❓ Nom du container MariaDB [mariadb_app]: 
❓ Port Apache [80]: 
❓ Port MariaDB [3306]: 
❓ Mot de passe root MariaDB [root]: 
❓ Nom de la base de données [app_db]: 
❓ Utilisateur MariaDB [app_user]: 
❓ Mot de passe utilisateur MariaDB [app_password]: 
❓ Installer Symfony CLI ? (y/N): 
❓ Installer Node.js ? (y/N): 
❓ PHP Error Reporting [E_ALL]: 
❓ PHP Display Errors (On/Off) [On]: 
```

### 3. Utiliser la recette générée

```bash
# Charger les aliases
source aliases.sh

# Démarrer les containers
docker compose up -d --build

# Voir les logs
docker compose logs -f
```

## 📚 Documentation

Une fois la recette générée, consultez le fichier `README.md` dans votre projet pour la documentation complète.

## 🔧 Configuration

### Options disponibles

- **Symfony CLI** : Si activé, installe Symfony CLI dans le container Apache
- **Node.js** : Si activé, installe Node.js 20 via NVM dans le container Apache

### Personnalisation

Tous les fichiers générés peuvent être modifiés après l'installation selon vos besoins.

## 📝 Exemple de recette générée

```
mon-projet/
├── apache/
│   ├── Dockerfile
│   └── custom-php.ini
├── db/
│   ├── backup.sh
│   └── restore.sh
├── www/                    # Votre code source ici
├── docker-compose.yml
├── .env
├── .env.example
├── .htaccess
├── aliases.sh
├── .dockerignore
├── .gitignore
└── README.md
```

## 🆘 Support

- **Issues** : [GitHub Issues](https://github.com/julien-lin/php-docker-generator/issues)
- **Source** : [GitHub Repository](https://github.com/julien-lin/php-docker-generator)

## 📄 Licence

MIT License - Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

**Créé avec ❤️ par Julien Linard**

