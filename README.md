# ECA Training System - Laravel

Système de gestion de formation développé avec Laravel 12, MySQL, HTML/CSS/JS.

## Prérequis
- XAMPP (PHP 8.2+, MySQL)
- Composer
- Git

## Installation

### 1. Cloner le projet
```bash
git clone https://github.com/jeremie337/eca-laravel.git
cd eca-laravel
```

### 2. Installer les dépendances
```bash
composer install
```

### 3. Configurer l'environnement
```bash
cp .env.example .env
php artisan key:generate
```

Ouvre le fichier `.env` et modifie :
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eca_training_system
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Créer la base de données
- Ouvre phpMyAdmin : `http://localhost/phpmyadmin`
- Crée une base de données nommée `eca_training_system`
- Importe le fichier `database/eca_training_system.sql`

### 5. Lancer les migrations
```bash
php artisan migrate
```

### 6. Créer le lien de stockage
```bash
php artisan storage:link
```

### 7. Lancer le serveur
```bash
php artisan serve
```

### 8. Accéder à l'application
Ouvre `http://localhost:8000`

## Comptes de connexion
| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@ecaconseils.com | admin123 |

## Fonctionnalités
- Gestion des utilisateurs (Admin, Formateur, Stagiaire)
- Gestion des formations
- Inscription aux formations
- Upload de documents
- Suivi de progression