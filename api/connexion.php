<?php
/**
 * api/connexion.php — Endpoint d'authentification (POST uniquement).
 *
 * Processus :
 * 1. Récupère email + mot de passe depuis le formulaire login.php
 * 2. Applique le "grain de sel" puis hash SHA-512 (imposé par le cahier des charges du module)
 * 3. Compare le hash avec celui stocké en BDD
 * 4. Si OK : crée la session (user_id, prenom) et redirige vers index.php
 * 5. Si KO : redirige vers login.php avec ?error=1
 *
 * Note sécurité : en production, préférer password_hash() / password_verify() (bcrypt).
 * Le SHA-512 + sel statique est utilisé ici car imposé par le cadre pédagogique.
 */
session_start();
include("../db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $mdp = $_POST['mdp'];

    // Hachage avec grain de sel statique (consigne du module M. Cartailler)
    $mdp = $mdp.'dTbZ!912cV@2Ad';
    $mdpc = hash('sha512', $mdp);

    try {
        $bdd = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Requête préparée pour éviter les injections SQL
        $stmt = $bdd->prepare('SELECT id, prenom FROM utilisateurs WHERE mail=:mail AND mdp=:mdp');
        $stmt->execute(['mail' => $email, 'mdp' => $mdpc]);
        $user_data = $stmt->fetch();
        
        $bdd = null;   

        if ($user_data) {
            // Création de la session utilisateur
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['prenom'] = $user_data['prenom'];
            header('location: ../index.php');
            exit();
        } else {
            header('location: ../login.php?error=1'); 
            exit();
        }
    } catch (PDOException $e) {
        die("Erreur !: " . $e->getMessage());
    }
} else {
    // Accès direct par GET : redirection vers le formulaire
    header('location: ../login.php');
    exit();
}
?>
