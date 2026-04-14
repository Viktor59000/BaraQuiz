# BaraQuiz ! — L'Arène des Baraquis

[![Tech](https://img.shields.io/badge/Stack-PHP%20%7C%20MySQL%20%7C%20JS-ffc107?style=for-the-badge)](https://github.com/)
[![University](https://img.shields.io/badge/Projet-Universitaire-ef4444?style=for-the-badge)](https://www.telecom-st-etienne.fr/)

> **"Prouve que t'es un vrai Baraqui d'ichi !"** > Une immersion ludique et décalée dans la culture des Hauts-de-France.

---

## Le Concept

**BaraQuiz** est une application web interactive née d'un défi : allier la rigueur du développement web full-stack à la chaleur humaine du Nord de la France. Réalisé dans le cadre du module de M. Cartailler, ce projet propose une expérience de quiz dynamique où le hasard et la culture régionale se rencontrent autour d'un cornet de frites.

### Direction Artistique : "Carnival Pop"
Le projet adopte un style **Néo-Brutalisme** assumé : 
- **Couleurs saturées** : Jaune Frite (`#FFC107`), Rouge Carnaval (`#EF4444`), Bleu Ciel (`#0EA5E9`).
- **Design Radical** : Contours noirs épais, ombres portées massives, et typographies "bouncy" (`Lilita One`).
- **Inspiration** : L'esthétique visuelle des fêtes foraines et des baraques à frites traditionnelles.

---

## Fonctionnalités Clés

- **La Frite du Destin** : Une mécanique de tirage au sort unique. Cliquez sur une frite pour déclencher la roulette des thèmes.
- **Base de données massive** : Plus de 400 questions sourcées couvrant 10 thématiques (du Patois au Bassin Minier).
- **Système de Jeu Dynamique** : Timer de 15 secondes, feedbacks visuels immédiats et anecdotes culturelles après chaque réponse.
- **Le Carnet du Baraqui** : Un espace profil avec un anneau de score global animé et des statistiques détaillées par thématique.
- **Sécurité Académique** : Authentification complète avec hachage SHA-512 et grain de sel statique.
- **Interface Responsive** : Une expérience pensée pour être fluide sur mobile comme sur desktop.

---

## Stack Technique

| Couche | Technologie | Rôle |
| :--- | :--- | :--- |
| **Frontend** | Vanilla JS / Tailwind CSS | Logique de jeu (SPA-like), Animations CSS, UI Néo-Brutaliste |
| **Backend** | PHP 8.2 (PDO) | API REST, Gestion des sessions, Validation des scores |
| **Base de données** | MySQL | Stockage des questions, statistiques et utilisateurs |
| **Outils** | Git Bash | Versionnage et déploiement |

---

## Installation & Déploiement

### Prérequis
- Un serveur local (WAMP, MAMP, XAMPP ou Laragon) avec **PHP 8.0+** et **MySQL**.

### Installation rapide
1. **Cloner le projet** dans votre répertoire `www` ou `htdocs` :
   ```bash
   git clone https://github.com/votre-compte/baraquiz.git
   ```

2. **Configuration** : Renommez `config.example.php` en `db.php` (pour des raisons de sécurité, ce fichier est ignoré par Git via le `.gitignore`). Renseignez vos identifiants MySQL à l'intérieur.

3. **Initialisation Magique** : Lancez simplement l'URL `http://localhost/baraquiz/` dans votre navigateur. Le script `db.php` s'occupe de tout :
   - Création de la base de données `baraquiz`.
   - Création des tables (`utilisateurs`, `questions`, `user_answers`).
   - Importation automatique des 400+ questions.

---

## Notes d'intention

### Genèse & Projet Étudiant

BaraQuiz est l'aboutissement d'un module de développement web dispensé par M. Cartailler. Le défi était de concevoir une application web complète en maîtrisant la séparation entre un Frontend dynamique et un Backend robuste. Ce projet m'a permis de mettre en pratique l'utilisation des API REST minimalistes en PHP et la manipulation avancée du DOM en JavaScript.

### Une histoire de Terroir

Plutôt que de réaliser un quiz générique, j'ai choisi de mettre à l'honneur ma région d'origine : les Hauts-de-France. BaraQuiz célèbre la diversité de notre patrimoine : de la ferveur des stades à la subtilité du patois, en passant par l'histoire des mines ou la folie du Carnaval de Dunkerque. C'est une démonstration qu'on peut allier rigueur algorithmique et identité culturelle forte.

### Sous le capot

L'application respecte les protocoles de sécurité vus en cours, notamment le hachage des mots de passe. Le système de statistiques ("Le Carnet du Baraqui") agrège les données SQL pour fournir un suivi de progression précis, prouvant qu'un backend sérieux peut faire tourner une application ludique.

---

## Structure des fichiers

```
BaraQuiz/
├── index.php             # Point d'entrée unique (Logique SPA)
├── login.php             # Authentification (Inspiration Néo-Brutaliste)
├── profil.php            # Dashboard utilisateur & Statistiques
├── db.php                # Cerveau de la BDD (Auto-install)
├── api/
│   ├── get_quiz.php      # Livraison de 10 questions aléatoires
│   ├── save_stats.php    # Sauvegarde sécurisée des résultats
│   └── connexion.php     # Logique de sécurité PHP
├── assets/
│   ├── img/              # Logos, icônes et images de questions
│   └── favicon.ico       
└── sql/
    └── questions.sql     # Le gisement de culture régionale (400+ questions)
```

---

## Crédits & Licence

**Développeur** : Venet Viktor

**Module** : Développement Web - Télécom Saint-Étienne

**Enseignant** : M. Cartailler

> Projet réalisé par amour pour le Nord et pour un module universitaire. Tous droits de "Baraqui" réservés.
