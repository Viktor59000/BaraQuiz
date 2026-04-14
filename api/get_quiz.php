<?php
/**
 * api/get_quiz.php — Endpoint GET qui retourne 10 questions aléatoires en JSON.
 *
 * Paramètre : ?theme=NomDuThème
 * Retour : tableau JSON de 10 objets {id, theme, question, reponse_A..D, bonne_reponse, anecdote, ...}
 *
 * ORDER BY RAND() est acceptable ici car la table questions contient ~400 lignes
 * (performance négligeable). Pour des volumes plus importants, envisager un tirage
 * aléatoire côté application ou un index dédié.
 *
 * Pas de vérification de session ici : l'appel est fait depuis index.php
 * qui est déjà protégé par le guard d'authentification PHP.
 */
include("../db.php");
header('Content-Type: application/json');

$theme = isset($_GET['theme']) ? $_GET['theme'] : '';

try {
    $bdd = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $bdd->prepare('SELECT * FROM questions WHERE theme = :theme ORDER BY RAND() LIMIT 10');
    $stmt->execute(['theme' => $theme]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($questions);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
