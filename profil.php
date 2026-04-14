<?php
/**
 * profil.php — Page "Le Carnet du Baraqui" (profil utilisateur)
 *
 * Affiche les statistiques de jeu de l'utilisateur connecté :
 * - Score global (anneau SVG animé avec pourcentage)
 * - Diagramme en barres verticales par thème (vert >= 50%, rouge < 50%)
 *
 * Requête SQL : agrégation par thème sur la table user_answers.
 * Chaque ligne représente un thème joué avec le nombre de bonnes réponses
 * et le total de questions. Les thèmes non joués apparaissent à 0%.
 */
session_start();

// Guard d'authentification (identique à index.php)
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}
include("db.php");

$user_id = $_SESSION['user_id'];
$prenom = $_SESSION['prenom'];

try {
    $bdd = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Agrégation des stats par thème pour l'utilisateur courant.
    // COUNT(id) = nombre de parties jouées sur ce thème
    // SUM(is_correct) = total de bonnes réponses (bool 0/1)
    // SUM(total) = total de questions posées
    $stmt = $bdd->prepare('
        SELECT theme, COUNT(id) as parties, SUM(is_correct) as bonnes_rep, SUM(total) as questions_total 
        FROM user_answers 
        WHERE id_utilisateur = :idu 
        GROUP BY theme
    ');
    $stmt->execute(['idu' => $user_id]);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcul des totaux globaux (tous thèmes confondus)
    $total_bonnes = 0;
    $total_questions = 0;
    foreach($stats as $s) {
        $total_bonnes += $s['bonnes_rep'];
        $total_questions += $s['questions_total'];
    }

} catch (PDOException $e) {
    die("Erreur !: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BaraQuiz! - Le Carnet du Baraqui</title>
  <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Outfit:wght@400;600;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: { extend: { colors: { 'frites': '#FFC107', 'carnival': '#EF4444', 'sky': '#0EA5E9' }, fontFamily: { display: ['"Outfit"', 'sans-serif'], body: ['"Inter"', 'sans-serif'] } } }
    };
  </script>
  <style>
    /* Fond dégradé identique à index.php pour cohérence visuelle */
    body { background: linear-gradient(135deg, #0c1445 0%, #1a237e 30%, #1565c0 70%, #0EA5E9 100%); min-height: 100vh; }

    /* Boutons néo-brutalistes (réutilisés depuis le design system global) */
    .btn-premium { border: 3px solid black; box-shadow: 6px 6px 0px 0px rgba(0,0,0,1); transition: all 0.15s ease-out; }
    .btn-premium:hover { box-shadow: 2px 2px 0px 0px rgba(0,0,0,1); transform: translate(4px, 4px); }
    .btn-premium:active { box-shadow: 0px 0px 0px 0px rgba(0,0,0,1); transform: translate(6px, 6px); }

    /*
     * Anneau de score SVG :
     * Utilise stroke-dasharray/dashoffset pour dessiner un arc de cercle proportionnel.
     * La transition cubic-bezier crée une animation fluide au chargement.
     * Le data-target est calculé côté PHP et appliqué par JS après 300ms (effet reveal).
     */
    .score-ring-circle {
      transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-in { animation: fadeInUp 0.6s ease-out forwards; opacity: 0; }
    .animate-delay-1 { animation-delay: 0.15s; }
    .animate-delay-2 { animation-delay: 0.3s; }

    /*
     * Carte glassmorphism légère :
     * Fond quasi-transparent avec blur intense pour un effet de profondeur
     * sans masquer le dégradé de fond. Bordure 2px semi-transparente.
     */
    .glass-card {
      background: rgba(255,255,255,0.06);
      backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
      border: 2px solid rgba(255,255,255,0.1);
      border-radius: 1.5rem;
    }

    /*
     * Diagramme en barres verticales :
     * Structure : .bar-chart (flex align-items:flex-end) contient 10 .bar-col.
     * Les labels sont dans une rangée séparée (.bar-labels) sous le graphique
     * pour garantir une baseline alignée, indépendante de la longueur des noms.
     *
     * Code couleur intentionnel (binaire) :
     * - Vert (>= 50%) : thèmes maîtrisés
     * - Rouge (< 50%) : thèmes à travailler
     * Pas de couleur intermédiaire pour une lecture instantanée.
     */
    .bar-chart {
      display: flex;
      align-items: flex-end;
      justify-content: center;
      gap: 6px;
      height: 200px;
      padding: 0 4px;
    }
    @media (min-width: 640px) {
      .bar-chart { height: 260px; gap: 10px; }
    }
    .bar-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      min-width: 0;
      height: 100%;
      justify-content: flex-end;
    }
    .bar-fill {
      width: 100%;
      border-radius: 6px 6px 0 0;
      transition: height 1s cubic-bezier(0.4, 0, 0.2, 1);
      min-height: 4px;
    }
    .bar-fill:hover {
      filter: brightness(1.2);
    }
    .bar-pct {
      font-size: 0.6rem;
      font-weight: 800;
      color: white;
      flex-shrink: 0;
    }
    @media (min-width: 640px) {
      .bar-pct { font-size: 0.75rem; }
    }
    .bar-labels {
      display: flex;
      gap: 6px;
      padding: 8px 4px 0;
    }
    @media (min-width: 640px) {
      .bar-labels { gap: 10px; }
    }
    .bar-label-cell {
      flex: 1;
      text-align: center;
      font-size: 0.55rem;
      font-weight: 700;
      color: rgba(255,255,255,0.45);
      line-height: 1.2;
      min-width: 0;
      overflow: hidden;
    }
    @media (min-width: 640px) {
      .bar-label-cell { font-size: 0.65rem; }
    }
  </style>
</head>
<body class="min-h-screen font-body text-white">
  
  <!-- Header -->
  <header class="p-4 sm:p-6 flex justify-between items-center relative z-20">
    <a href="index.php" class="btn-premium bg-white font-display font-bold text-black px-5 sm:px-6 py-2 sm:py-3 text-base sm:text-xl flex items-center gap-2 rounded-full hover:bg-frites transition-colors">
      <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
      Accueil
    </a>
    <a href="api/logout.php" class="btn-premium bg-white font-display font-bold text-black px-5 sm:px-6 py-2 sm:py-3 text-base sm:text-xl flex items-center gap-2 rounded-full hover:bg-red-500 hover:text-white transition-colors">
      <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
      Quitter
    </a>
  </header>

  <section class="max-w-4xl mx-auto px-4 pb-12 relative">

    <!-- Title area -->
    <div class="text-center mb-8 sm:mb-12 animate-in">
      <h1 class="font-display text-4xl sm:text-5xl md:text-6xl text-white mb-2" style="text-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        Le Carnet du Baraqui
      </h1>
      <p class="text-lg sm:text-xl font-semibold text-white/60">
        Les notes de <?= htmlspecialchars($prenom) ?>
      </p>
    </div>

    <?php if ($total_questions > 0): 
      $pourcentage_global = round(($total_bonnes / $total_questions) * 100);
      $circumference = 2 * 3.14159 * 54;
      $offset = $circumference - ($pourcentage_global / 100) * $circumference;
      
      if ($pourcentage_global >= 80) {
        $ringColor = '#22c55e';
        $message = "T'es un vrai Baraqui de la friterie !";
      } elseif ($pourcentage_global >= 50) {
        $ringColor = '#FBBF24';
        $message = "T'es un ch'ti biloute en devenir !";
      } else {
        $ringColor = '#EF4444';
        $message = "T'es un vrai babache assume !";
      }
    ?>

    <!-- Score Ring -->
    <div class="flex flex-col items-center mb-10 sm:mb-14 animate-in animate-delay-1">
      <div class="relative w-40 h-40 sm:w-48 sm:h-48 mb-4">
        <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
          <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="8"/>
          <circle class="score-ring-circle" cx="60" cy="60" r="54" fill="none" 
                  stroke="<?= $ringColor ?>" stroke-width="8" stroke-linecap="round"
                  stroke-dasharray="<?= $circumference ?>" 
                  stroke-dashoffset="<?= $circumference ?>"
                  data-target="<?= $offset ?>"
                  style="filter: drop-shadow(0 0 8px <?= $ringColor ?>80);"/>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
          <span class="font-display text-4xl sm:text-5xl text-white font-black" style="text-shadow: 0 2px 10px rgba(0,0,0,0.3);"><?= $pourcentage_global ?>%</span>
          <span class="text-[0.65rem] sm:text-xs font-bold text-white/40 uppercase tracking-widest mt-1">Score global</span>
        </div>
      </div>
      <div class="glass-card px-6 py-3 text-center">
        <p class="font-bold text-base sm:text-lg text-white/80"><?= $message ?></p>
      </div>
      <p class="text-white/30 text-xs sm:text-sm mt-3 font-semibold">
        <?= $total_bonnes ?> bonnes reponses sur <?= $total_questions ?> questions
      </p>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
          const circle = document.querySelector('.score-ring-circle');
          if (circle) circle.style.strokeDashoffset = circle.dataset.target;
        }, 300);
      });
    </script>

    <!-- Bar Chart Diagram -->
    <div class="animate-in animate-delay-2">
      <h3 class="font-display text-2xl sm:text-3xl mb-6 text-center text-white" style="text-shadow: 0 2px 10px rgba(0,0,0,0.3);">
        Niveau par theme
      </h3>
      
      <?php
        $allThemes = [
          'Gastronomie', 'Carnaval de Dunkerque', 'Patois', 'Geographie',
          'Braderie de Lille', 'Histoire & Mines', 'Paris-Roubaix & Sport',
          'Celebrites du Nord', 'Cinema & Culture', 'Traditions & Folklore'
        ];
        $statsMap = [];
        foreach($stats as $s) {
          $statsMap[$s['theme']] = [
            'pct' => round(($s['bonnes_rep'] / $s['questions_total']) * 100),
            'parties' => $s['parties'],
          ];
        }
        // Short labels for chart
        $shortLabels = [
          'Gastronomie' => 'Gastro.',
          'Carnaval de Dunkerque' => 'Carnaval',
          'Patois' => 'Patois',
          'Geographie' => 'Geo.',
          'Braderie de Lille' => 'Braderie',
          'Histoire & Mines' => 'Mines',
          'Paris-Roubaix & Sport' => 'Sport',
          'Celebrites du Nord' => 'Stars',
          'Cinema & Culture' => 'Cinema',
          'Traditions & Folklore' => 'Folklore'
        ];
      ?>
      
      <div class="glass-card p-4 sm:p-6">
        <div class="bar-chart">
          <?php foreach($allThemes as $theme): 
            $pct = isset($statsMap[$theme]) ? $statsMap[$theme]['pct'] : 0;
            
            if ($pct === 0) {
              $barColor = 'background: rgba(255,255,255,0.1);';
            } elseif ($pct >= 50) {
              $barColor = 'background: linear-gradient(180deg, #4ade80, #22c55e);';
            } else {
              $barColor = 'background: linear-gradient(180deg, #f87171, #ef4444);';
            }
          ?>
            <div class="bar-col">
              <span class="bar-pct"><?= $pct ?>%</span>
              <div class="bar-fill" style="height: <?= max($pct, 3) ?>%; <?= $barColor ?>" title="<?= htmlspecialchars($theme) ?> : <?= $pct ?>%"></div>
            </div>
          <?php endforeach; ?>
        </div>
        <!-- Labels row (fixed height, aligned below bars) -->
        <div class="bar-labels">
          <?php foreach($allThemes as $theme): 
            $label = $shortLabels[$theme];
          ?>
            <div class="bar-label-cell"><?= $label ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php else: ?>
      <div class="glass-card p-8 sm:p-12 text-center animate-in animate-delay-1">
        <p class="text-xl sm:text-2xl font-bold text-white/80 mb-2">
          Tu n'as pas encore joue de partie, boubourse !
        </p>
        <p class="text-base text-white/50 font-semibold mb-6">
          Va vite tirer une frite pour commencer ton aventure.
        </p>
        <a href="index.php" class="btn-premium inline-block bg-frites text-black font-display text-lg sm:text-xl px-8 py-3 rounded-full">
          Tirer la frite !
        </a>
      </div>
    <?php endif; ?>

    <!-- Footer -->
    <p class="text-center text-white/20 text-xs mt-10 font-semibold">
      BaraQuiz — Le Carnet du Baraqui · <?= date('Y') ?>
    </p>
  </section>

</body>
</html>
