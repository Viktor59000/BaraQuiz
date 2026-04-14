<?php
/**
 * api/logout.php — Déconnexion de l'utilisateur.
 *
 * Détruit la session active (suppression des variables + destruction)
 * puis redirige vers la page de login.
 */
session_start();

unset($_SESSION['user_id']);
unset($_SESSION['prenom']);

session_destroy();

header('location: ../login.php');
exit();
?>
