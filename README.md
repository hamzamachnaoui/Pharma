# PharmaGest — Application de gestion de pharmacie

Application web complète de gestion de pharmacie développée en **PHP / MySQL**. Interface moderne, responsive et animée.

## Fonctionnalités

- 🔐 **Authentification** — Login sécurisé avec sessions PHP
- 📊 **Dashboard** — Vue d'ensemble : CA du jour, alertes stock, top médicaments, ventes récentes
- 💊 **Médicaments** — Gestion du stock, alertes rupture, dates d'expiration, ajustement stock
- 🧾 **Ventes & Caisse** — Enregistrement des ventes, calcul automatique, historique, annulation
- 🚚 **Fournisseurs** — Gestion des fournisseurs avec contacts
- 👥 **Clients** — Fiches clients, historique des achats, total dépensé
- 📈 **Statistiques** — Graphiques interactifs Chart.js (courbe CA, camembert catégories, barres top 10)
- 📄 **Rapport PDF** — Export imprimable avec filtrage par période

## Technologies

| Côté | Technologies |
|------|-------------|
| Backend | PHP 8+ (PDO) |
| Base de données | MySQL 5.7+ |
| Frontend | HTML5, CSS3, JavaScript Vanilla |
| Graphiques | Chart.js 4.4 |
| Typographie | Plus Jakarta Sans, Outfit (Google Fonts) |

## Installation

### Prérequis
- PHP 8.0+
- MySQL 5.7+
- Serveur web (Apache/Nginx) ou WAMP/XAMPP

### Étapes

1. **Cloner le projet**
```bash
git clone https://github.com/hamzamachnaoui/pharma-gestion.git
cd pharma-gestion
```

2. **Créer la base de données**
```bash
mysql -u root -p < db_setup.sql
```

3. **Configurer la connexion**

Modifier `config/database.php` :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pharma_gestion');
define('DB_USER', 'root');
define('DB_PASS', 'votre_mot_de_passe');
```

4. **Lancer**

Avec WAMP/XAMPP : placer le dossier dans `www/` ou `htdocs/`

5. **Se connecter**
```
URL    : http://localhost/pharma-gestion
Login  : admin
Mot de passe : admin123
```

## Structure du projet

```
pharma-gestion/
├── config/
│   └── database.php
├── assets/
│   └── style.css
├── includes/
│   ├── auth.php
│   └── sidebar.php
├── index.php           Dashboard
├── login.php           Authentification
├── logout.php
├── medicaments.php     Stock
├── ventes.php          Caisse
├── fournisseurs.php    Fournisseurs
├── clients.php         Clients
├── statistiques.php    Graphiques & analyses
├── rapport.php         Export PDF
├── ajax_vente.php      Détail vente
├── db_setup.sql        Schéma + données démo
└── README.md
```

## Auteur

**Mohamed Hamza Machnaoui**
- GitHub : [@hamzamachnaoui](https://github.com/hamzamachnaoui)
- LinkedIn : [Mohamed Hamza Machnaoui](https://www.linkedin.com/in/mohamed-hamza-machnaoui/)
- Portfolio : [hamzamachnaoui.rf.gd](https://hamzamachnaoui.rf.gd)

## Licence

MIT License

