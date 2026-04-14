# BaraQuiz

Application de quiz interactive dédiée à la culture des Hauts-de-France, réalisée dans le cadre du module de développement web de M. Cartailler.

> Prouve que t'es un vrai Baraqui d'ichi.

---

## Présentation

BaraQuiz est une application web full-stack qui propose un quiz de 10 questions aléatoires parmi une banque de 400+, réparties sur 10 thèmes couvrant le patrimoine, les traditions et la culture du Nord de la France. Le joueur tire une "frite du destin" pour découvrir son thème, puis répond aux questions dans un temps limité.

**Direction Artistique** : Néo-Brutalisme — couleurs primaires saturées, bordures épaisses, ombres portées plates, typographies grasses. L'esthétique s'inspire de l'univers des baraques à frites et du Carnaval de Dunkerque.

---

## Stack technique

| Couche | Technologies |
|---|---|
| **Frontend** | HTML5, Tailwind CSS (CDN), Vanilla JavaScript |
| **Backend** | PHP 8.x, PDO |
| **Base de données** | MySQL (utf8mb4) |
| **Serveur local** | WAMP / MAMP / XAMPP |

---

## Fonctionnalités

- **Authentification** : inscription / connexion avec hachage SHA-512 + sel statique
- **Tirage de thème** : animation "frite interactive" + roulette slot machine
- **Quiz 10 questions** : timer 15s, feedback visuel immédiat, anecdotes culturelles
- **Score serveur** : validation côté serveur (anti-triche), récapitulatif détaillé
- **Profil joueur** : score global (anneau SVG animé) + diagramme en barres par thème
- **Support média** : questions images intégrées
- **Responsive** : mobile-first, breakpoints en hauteur pour le quiz
- **Notes d'intention** : section documentaire avec liens vers la genèse du projet

---

## Structure du projet

```
Nord/
├── index.php              # Application principale (SPA-like, 3 vues)
├── login.php              # Page connexion / inscription
├── profil.php             # Profil joueur (Le Carnet du Baraqui)
├── db.php                 # Config BDD + auto-initialisation des tables
├── questions.sql          # Dump SQL : 400+ questions avec anecdotes
├── update_audio.sql       # Script de neutralisation des questions audio
├── api/
│   ├── connexion.php      # Endpoint POST : authentification
│   ├── inscription.php    # Endpoint POST : création de compte
│   ├── get_quiz.php       # Endpoint GET : 10 questions aléatoires par thème
│   ├── save_stats.php     # Endpoint POST : validation + sauvegarde des résultats
│   └── logout.php         # Déconnexion / destruction de session
├── assets/
│   ├── img/               # Images (favicon, cornet de frites, illustrations)
│   └── audio/             # (réservé, non utilisé actuellement)
└── README.md
```

---

## Installation

### Prérequis
- Serveur WAMP, MAMP ou XAMPP avec PHP 8+ et MySQL
- Accès à phpMyAdmin (optionnel, la BDD se crée automatiquement)

### Démarrage

1. **Cloner le dépôt** dans le répertoire du serveur web :
   ```bash
   git clone https://github.com/votre-repo/baraquiz.git /chemin/vers/www/Nord
   ```

2. **Démarrer le serveur** (Apache + MySQL)

3. **Accéder à l'application** :
   ```
   http://localhost/Nord/
   ```

Au premier accès, `db.php` :
- Crée la base de données `baraquiz`
- Crée les tables (`utilisateurs`, `user_answers`, `questions`)
- Importe automatiquement les 400+ questions depuis `questions.sql`

4. **Créer un compte** via le formulaire d'inscription, puis se connecter.

---

## Architecture

### Frontend (index.php)

Application SPA-like avec 3 vues HTML togglées par JavaScript :

1. **Accueil** — Hero, CTA, scroll libre vers les notes d'intention
2. **Tirage de frites** — Animation interactive, roulette de thème
3. **Quiz** — Questions, timer, anecdotes, écran de résultats

Le scroll du body est verrouillé pendant le jeu (`.scroll-locked`) et libéré sur l'accueil et les résultats.

### Backend (api/)

API REST minimaliste en PHP :
- Les réponses sont envoyées en JSON au serveur (`save_stats.php`)
- Le serveur recalcule le score en comparant les réponses avec la BDD
- Le score affiché est celui du serveur (pas du client) pour éviter la triche

### Base de données

```
utilisateurs (id, prenom, nom, mail, mdp)
    └── user_answers (id, id_utilisateur, theme, is_correct, total, date_partie)

questions (id, theme, type_media, url_media, question, reponse_A..D, bonne_reponse, anecdote)
```

---

## Les 10 thèmes

| Thème | Contenu |
|---|---|
| Gastronomie | Fricadelle, welsh, maroilles, baraques à frites |
| Carnaval de Dunkerque | Bandes, jets de harengs, chansons |
| Patois | Ch'ti, picard, expressions du Nord |
| Géographie | Villes, fleuves, paysages des Hauts-de-France |
| Braderie de Lille | Le plus grand marché aux puces d'Europe |
| Histoire & Mines | Bassins miniers, patrimoine UNESCO |
| Paris-Roubaix & Sport | L'Enfer du Nord, LOSC, clubs mythiques |
| Célébrités du Nord | De Gaulle, Dany Boon, Pierre Bachelet |
| Cinéma & Culture | Bienvenue chez les Ch'tis, Germinal, les Corons |
| Traditions & Folklore | Géants, ducasses, P'tit Quinquin, estaminets |

---

## Crédits

Projet universitaire — Module Développement Back-end, Télécom Saint-Étienne

---

## Licence

Projet académique. Tous droits réservés.
