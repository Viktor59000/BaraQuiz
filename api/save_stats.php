<?php
/**
 * api/save_stats.php — Endpoint POST de validation et sauvegarde des résultats.
 *
 * Reçoit un JSON : { theme: string, answers: { questionId: "A"|"B"|"C"|"D"|null } }
 * Retourne un JSON : { success, score, total, recap: [...] }
 *
 * Architecture de sécurité :
 * Le score n'est PAS calculé côté client. Ce endpoint :
 * 1. Récupère les vraies bonnes réponses depuis la BDD
 * 2. Compare chaque réponse utilisateur avec la bonne réponse serveur
 * 3. Calcule le score officiel et le persiste en BDD
 * 4. Renvoie le score + un récapitulatif détaillé au client
 *
 * Cela empêche toute manipulation du score via les DevTools.
 *
 * Protection : session requise + méthode POST uniquement.
 */
session_start();
header('Content-Type: application/json');
include("../db.php");

// Guard : utilisateur authentifié + méthode POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Lecture du body JSON brut
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$theme = $input['theme'] ?? 'Général';
$answers = $input['answers'] ?? [];
$total = count($answers);

if ($total === 0) {
    echo json_encode(['error' => 'Aucune réponse soumise']);
    exit;
}

try {
    $bdd = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les bonnes réponses depuis la BDD pour les questions jouées
    $questionIds = array_keys($answers);
    $placeholders = str_repeat('?,', count($questionIds) - 1) . '?';

    $stmt = $bdd->prepare("SELECT id, question, reponse_A, reponse_B, reponse_C, reponse_D, bonne_reponse FROM questions WHERE id IN ($placeholders)");
    $stmt->execute($questionIds);
    $questionsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcul du score côté serveur (source de vérité)
    $score = 0;
    $recap = [];

    foreach ($questionsData as $q) {
        $qId = $q['id'];
        $userChoice = $answers[$qId] ?? null;
        $isCorrect = ($userChoice === $q['bonne_reponse']);

        if ($isCorrect) {
            $score++;
        }

        // Résolution des textes de réponse pour le récapitulatif
        $correctChoiceStr = "reponse_" . $q['bonne_reponse'];
        $userChoiceStr = $userChoice ? "reponse_" . $userChoice : null;

        $recap[] = [
            'id' => $qId,
            'question' => $q['question'],
            'userChoice' => $userChoice,
            'userText' => $userChoice ? $q[$userChoiceStr] : 'Non répondu',
            'isCorrect' => $isCorrect,
            'correctChoice' => $q['bonne_reponse'],
            'correctText' => $q[$correctChoiceStr]
        ];
    }

    // Persistance en BDD : 1 ligne par partie (score agrégé)
    $stmtInsert = $bdd->prepare('INSERT INTO user_answers (id_utilisateur, theme, is_correct, total) VALUES (:idu, :theme, :score, :total)');
    $stmtInsert->execute([
        'idu' => $user_id,
        'theme' => $theme,
        'score' => $score,
        'total' => $total
    ]);

    echo json_encode([
        'success' => true,
        'score' => $score,
        'total' => $total,
        'recap' => $recap
    ]);

}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données']);
}
?>
