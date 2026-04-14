<?php
/**
 * api/inscription.php — Endpoint de création de compte (POST uniquement).
 *
 * Processus :
 * 1. Récupère prénom, nom, email, mot de passe depuis le formulaire
 * 2. Applique le même grain de sel + SHA-512 que connexion.php
 * 3. Insère le nouvel utilisateur en BDD (contrainte UNIQUE sur mail)
 * 4. Redirige vers login.php avec ?success=1
 *
 * En cas de doublon d'email, PDO lèvera une exception (contrainte UNIQUE).
 */
session_start();
include("../db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prenom = $_POST['prenom'];
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $mdp = $_POST['mdp'];

    // Hachage identique à connexion.php (même sel, même algo)
    $mdp = $mdp . 'dTbZ!912cV@2Ad';
    $mdpc = hash('sha512', $mdp);

    try {
        $bdd = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $bdd->prepare('INSERT INTO utilisateurs (prenom, nom, mail, mdp) VALUES (:prenom, :nom, :mail, :mdp)');
        $stmt->execute([
            'prenom' => $prenom,
            'nom' => $nom,
            'mail' => $email,
            'mdp' => $mdpc
        ]);

        header('location: ../login.php?success=1');
        exit();
    }
    catch (PDOException $e) {
        die("Erreur !: " . $e->getMessage());
    }
}
else {
    header('location: ../login.php');
    exit();
}
?>
