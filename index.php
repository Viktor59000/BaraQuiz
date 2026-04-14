<?php
/**
 * index.php — Point d'entrée principal de l'application BaraQuiz.
 *
 * Architecture : SPA-like côté client avec 3 vues (Home, Frites, Quiz)
 * gérées par un système de sections HTML togglées via JS (pas de rechargement).
 * Le backend PHP ne sert ici qu'au guard d'authentification et à l'injection
 * de variables de session. Toute la logique de jeu est côté client.
 *
 * Flux utilisateur : Login → Home → Tirer la Frite (roulette) → Quiz 10 questions → Récap
 */
session_start();

// Guard d'authentification — redirection vers login si pas de session active.
// La vérification se fait côté serveur pour empêcher l'accès direct à l'URL.
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

// Fallback 'Baraqui' si le prénom n'est pas en session (cas théorique, sécurité)
$prenom = $_SESSION['prenom'] ?? 'Baraqui';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BaraQuiz! – Le quiz du Nord</title>
  <meta name="description" content="BaraQuiz! Le quiz festif et coloré qui fleure bon la frite et le carnaval du Nord de la France." />
  <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Lilita+One&family=Outfit:wght@400;600;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!--
    Configuration Tailwind CSS (CDN).
    On étend le thème par défaut avec la palette Néo-Brutaliste du projet :
    - frites (#FFC107) : jaune kermesse, couleur primaire de la DA
    - carnival (#EF4444) : rouge Carnaval de Dunkerque, utilisé pour les erreurs et le timer
    - sky (#0EA5E9) : bleu ciel du Nord, anecdotes et accents
    - flandres (#FBBF24) : or des Flandres, highlights et CTA

    Typographie :
    - display (Outfit) : titres et éléments forts, grasse et moderne
    - body (Inter) : texte courant, lisibilité optimale
    - brewery (Fredoka One) : réservée à des usages ponctuels festifs
  -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'frites': '#FFC107',
            'carnival': '#EF4444',
            'sky': '#0EA5E9',
            'neo-black': '#000000',
            'flandres': '#FBBF24',
            'slate-dark': '#0f172a',
          },
          fontFamily: {
            display: ['"Outfit"', 'sans-serif'],
            body: ['"Inter"', 'sans-serif'],
            brewery: ['"Fredoka One"', 'cursive'],
          },
          boxShadow: {
            'neo-premium': '6px 6px 0px 0px rgba(0,0,0,1)',
            'neo-premium-hover': '2px 2px 0px 0px rgba(0,0,0,1)',
          },
          animation: {
            'float': 'float-anim 6s ease-in-out infinite',
            'pulse-soft': 'pulse-opacity 2.5s ease-in-out infinite',
          },
          keyframes: {
            'float-anim': {
              '0%, 100%': { transform: 'translateY(0)' },
              '50%': { transform: 'translateY(-15px)' },
            },
            'pulse-opacity': {
              '0%, 100%': { opacity: 0.6 },
              '50%': { opacity: 1 },
            }
          }
        },
      },
    };
  </script>

  <style>
    /*
     * ===== BASE & BACKGROUND =====
     * Le fond dégradé 135° (bleu marine → bleu ciel) est fixé via background-attachment
     * pour éviter le défilement du gradient lorsque la page scrolle (effet parallaxe subtil).
     *
     * Système de verrouillage du scroll :
     * On utilise une classe CSS .scroll-locked (togglée par JS) plutôt que
     * document.body.style.overflow pour faciliter la gestion multi-vues.
     * - Accueil (viewHome) : scroll libre (accès aux notes d'intention en bas)
     * - Frites (viewFrites) et Quiz (viewQuiz) : scroll verrouillé (contenu plein écran)
     * - Fin de quiz (endScreen) : scroll déverrouillé (récap longue à parcourir)
     */
    *, *::before, *::after { box-sizing: border-box; }
    html {
      scroll-behavior: smooth;
    }
    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      min-height: 100vh;
      background: linear-gradient(135deg, #0c1445 0%, #1a237e 30%, #1565c0 70%, #0EA5E9 100%);
      background-attachment: fixed;
    }
    body.scroll-locked {
      overflow: hidden;
      height: 100vh;
    }

    /* ===== ENTRANCE ANIMATIONS ===== */
    @keyframes popUp {
      0% { opacity: 0; transform: translateY(40px) scale(0.9); }
      60% { transform: translateY(-8px) scale(1.02); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulseGlow {
      0%, 100% { box-shadow: 6px 6px 0px 0px rgba(0,0,0,1), 0 0 15px rgba(251,191,36,0.4); }
      50% { box-shadow: 6px 6px 0px 0px rgba(0,0,0,1), 0 0 35px rgba(251,191,36,0.8); }
    }
    .anim-pop { animation: popUp 0.8s cubic-bezier(0.34,1.56,0.64,1) forwards; opacity: 0; }
    .anim-slide { animation: slideUp 0.6s ease-out forwards; opacity: 0; }
    .anim-delay-1 { animation-delay: 0.2s; }
    .anim-delay-2 { animation-delay: 0.5s; }
    .anim-delay-3 { animation-delay: 0.8s; }
    /* specific glow for the CTA after it pops up */
    .btn-cta-glow { animation: popUp 0.8s cubic-bezier(0.34,1.56,0.64,1) forwards, pulseGlow 3s ease-in-out infinite 0.8s; opacity: 0; }

    /* ===== CONFETTI ANIMATION ===== */
    .confetti-container { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
    .confetti-piece {
      position: absolute; width: 12px; height: 12px; top: -20px; opacity: 0.85;
      animation: confetti-fall linear infinite;
    }
    @keyframes confetti-fall {
      0%   { transform: translateY(-10vh) rotate(0deg); opacity: 0.9; }
      100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
    }

    /*
     * ===== UTILITAIRES NÉO-BRUTALISME =====
     * Design system Néo-Brutaliste : bordures noires épaisses (3px), ombres portées
     * plates (offset solide), coins arrondis. Ce style rappelle l'esprit kermesse/baraque
     * à frites tout en restant moderne.
     *
     * Les boutons .btn-premium utilisent un pattern d'interaction physique :
     * au hover l'ombre se réduit et le bouton "s'enfonce" (translate 4px),
     * au clic il touche le sol (translate 6px, ombre 0). Cela simule un bouton
     * mécanique pressé, renforçant le feedback haptique visuel.
     */
    .card-premium {
      background: white;
      border: 3px solid black;
      box-shadow: 6px 6px 0px 0px rgba(0,0,0,1);
      border-radius: 1.5rem;
    }
    .btn-premium {
      border: 3px solid black;
      box-shadow: 6px 6px 0px 0px rgba(0,0,0,1);
      transition: all 0.15s ease-out;
    }
    .btn-premium:hover {
      box-shadow: 2px 2px 0px 0px rgba(0,0,0,1);
      transform: translate(4px, 4px);
    }
    .btn-premium:active {
      box-shadow: 0px 0px 0px 0px rgba(0,0,0,1);
      transform: translate(6px, 6px);
    }

    /* ===== PREMIUM FRITES VIEW ===== */
    #viewFrites {
      background: transparent;
    }
    .ambient-particle {
      position: absolute; width: 4px; height: 4px;
      background: rgba(255,255,255,0.6); border-radius: 50%;
      pointer-events: none; animation: float-up linear infinite;
      box-shadow: 0 0 10px rgba(255,255,255,0.8);
    }
    @keyframes float-up {
      0%   { transform: translateY(0) scale(1); opacity: 0; }
      10%  { opacity: 1; }
      100% { transform: translateY(-250px) scale(0.4); opacity: 0; }
    }

    /* ===== IMAGE-BASED CONE SCENE ===== */
    .cone-scene {
      position: relative; display: flex; flex-direction: column;
      align-items: center; width: 320px; margin: 0 auto;
      filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3)); z-index: 2;
    }
    .cone-image-wrapper {
      position: relative; width: 280px; height: 320px;
      display: flex; align-items: center; justify-content: center;
    }
    .cone-image-wrapper img {
      width: 100%; height: 100%; object-fit: contain;
      pointer-events: none; user-select: none;
      filter: drop-shadow(4px 6px 12px rgba(0,0,0,0.4));
    }

    /*
     * ===== FRITE INTERACTIVE =====
     * Élément clé de l'UX : une frite CSS animée superposée sur l'image du cornet.
     * L'utilisateur clique dessus pour déclencher le tirage du thème.
     *
     * Effets visuels intentionnels :
     * - Glow multi-couleur (rouge/bleu/jaune) via box-shadow empilées
     * - Pulsation verticale (courte-pulse) pour attirer l'attention
     * - Ring animé (::before) pour renforcer l'affordance "cliquable"
     * - Reflet lumineux (::after) pour simuler un effet 3D
     *
     * Au clic (.pulled) : animation d'arrachage avec rotation et translation
     * vers le haut, simulant le geste physique de tirer une frite du cornet.
     */
    .fry-interactive {
      position: absolute;
      top: -20px; left: 50%; transform: translateX(-50%) rotate(-4deg);
      width: 28px; height: 120px;
      border-radius: 8px 8px 4px 4px;
      cursor: pointer; z-index: 15;
      border: 3px solid rgba(251,191,36,0.9);
      background:
        linear-gradient(170deg, rgba(255,255,255,0.45) 0%, rgba(255,255,255,0.1) 15%, transparent 35%, rgba(200,140,40,0.1) 100%),
        linear-gradient(to bottom, #fde68a 0%, #fbbf24 15%, #f59e0b 40%, #d97706 70%, #b45309 100%);
      box-shadow:
        0 0 0 3px rgba(0,0,0,0.9),
        0 0 25px rgba(251,191,36,0.7), 0 0 50px rgba(251,191,36,0.3), 0 0 80px rgba(251,191,36,0.15),
        -3px -1px 12px rgba(239,68,68,0.35),
        3px 1px 12px rgba(59,130,246,0.35),
        inset 2px 0 4px rgba(255,255,255,0.2);
      animation: courte-pulse 2.2s ease-in-out infinite;
      transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .fry-interactive::before {
      content: ''; position: absolute; inset: -6px; border-radius: 10px;
      background: transparent; border: 2px solid rgba(251,191,36,0.4);
      animation: courte-ring 2.2s ease-in-out infinite; pointer-events: none;
    }
    .fry-interactive::after {
      content: ''; position: absolute; top: 3%; left: 10%;
      width: 35%; height: 30%;
      background: linear-gradient(180deg, rgba(255,255,255,0.5) 0%, rgba(255,255,255,0) 100%);
      border-radius: 3px;
    }
    @keyframes courte-pulse {
      0%, 100% {
        box-shadow: 0 0 0 3px rgba(0,0,0,0.9), 0 0 25px rgba(251,191,36,0.6), 0 0 50px rgba(251,191,36,0.25), 0 0 80px rgba(251,191,36,0.1), -3px -1px 12px rgba(239,68,68,0.3), 3px 1px 12px rgba(59,130,246,0.3), inset 2px 0 4px rgba(255,255,255,0.2);
        transform: translateX(-50%) rotate(-4deg) translateY(0);
      }
      50% {
        box-shadow: 0 0 0 3px rgba(0,0,0,0.9), 0 0 35px rgba(251,191,36,0.85), 0 0 70px rgba(251,191,36,0.4), 0 0 100px rgba(251,191,36,0.2), -5px -2px 18px rgba(239,68,68,0.5), 5px 2px 18px rgba(59,130,246,0.5), inset 2px 0 4px rgba(255,255,255,0.3);
        transform: translateX(-50%) rotate(-4deg) translateY(-8px);
      }
    }
    @keyframes courte-ring {
      0%, 100% { opacity: 0.4; transform: scale(1); }
      50%      { opacity: 0.8; transform: scale(1.08); }
    }
    .fry-interactive:hover {
      animation: none;
      transform: translateX(-50%) rotate(-4deg) translateY(-18px) scale(1.08);
      box-shadow:
        0 0 0 3px rgba(0,0,0,0.9), 0 0 45px rgba(251,191,36,0.95), 0 0 90px rgba(251,191,36,0.5),
        -6px -3px 20px rgba(239,68,68,0.6), 6px 3px 20px rgba(59,130,246,0.6),
        inset 2px 0 4px rgba(255,255,255,0.3);
    }
    .fry-interactive:hover::before { animation: none; opacity: 1; transform: scale(1.12); }
    .fry-interactive.pulled {
      animation: courte-pull 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
      cursor: default;
    }
    .fry-interactive.pulled::before { display: none; }
    @keyframes courte-pull {
      0%   { transform: translateX(-50%) rotate(0deg) scale(1) translateY(0); opacity: 1; }
      30%  { transform: translateX(-50%) rotate(12deg) scale(1.15) translateY(-80px); }
      50%  { transform: translateX(-50%) rotate(-6deg) scale(1.1) translateY(-100px); }
      100% { transform: translateX(-50%) rotate(4deg) scale(1.05) translateY(-80px); opacity: 1; }
    }

    /* ===== GLASSMORPHISM CTA ===== */
    .cta-glass {
      position: absolute; z-index: 20;
      padding: 8px 24px 10px;
      background: rgba(30,30,46,0.55);
      backdrop-filter: blur(12px) saturate(1.4);
      -webkit-backdrop-filter: blur(12px) saturate(1.4);
      border: 1.5px solid rgba(251,191,36,0.3);
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.08);
      animation: cta-pulse 2s ease-in-out infinite;
      pointer-events: none; white-space: nowrap;
    }
    .cta-text {
      font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 800;
      color: white;
      text-shadow: 0 0 12px rgba(255,255,255,0.4);
      letter-spacing: 3px;
    }
    @keyframes cta-pulse {
      0%, 100% { opacity: 0.85; transform: translateY(0); }
      50%      { opacity: 1; transform: translateY(-3px); }
    }
    .cta-glass.hidden { display: none; }

    /*
     * ===== ÉTIQUETTE DE THÈME (Machine à sous) =====
     * Après le tirage de la frite, une étiquette apparaît avec une animation
     * de "slot machine" : les noms de thème défilent rapidement avant de
     * se figer sur le thème sélectionné (reveal).
     *
     * Le conteneur .theme-slot utilise overflow:hidden pour masquer les noms
     * qui sortent du cadre pendant le défilement (classe .spinning).
     * La transition cubic-bezier(0.34, 1.56, 0.64, 1) ajoute un rebond
     * exagéré typique du style néo-brutaliste.
     */
    .theme-label {
      opacity: 0; transform: translateY(20px) scale(0.8);
      transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
      position: relative; z-index: 50; /* Ensure it is above the frites */
    }
    .theme-label.visible { opacity: 1; transform: translateY(0) scale(1); }
    .theme-label-box {
      display: inline-block;
      background: rgba(30,30,46,0.7);
      backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
      border: 2px solid rgba(251,191,36,0.4);
      border-radius: 16px; padding: 16px 32px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 0 30px rgba(251,191,36,0.08), inset 0 1px 0 rgba(255,255,255,0.06);
    }
    .theme-slot {
      display: inline-block; min-width: 220px;
      text-align: center; overflow: hidden;
    }
    .theme-slot.spinning #themeName { animation: slot-spin 0.1s linear infinite; }
    @keyframes slot-spin {
      0%   { transform: translateY(-100%); opacity: 0; }
      30%  { opacity: 1; }
      70%  { opacity: 1; }
      100% { transform: translateY(100%); opacity: 0; }
    }
    .theme-slot.revealed #themeName { animation: slot-reveal 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
    @keyframes slot-reveal {
      0%   { transform: translateY(-100%) scale(0.8); opacity: 0; }
      100% { transform: translateY(0) scale(1); opacity: 1; }
    }

    /* ===== CONFETTI BURST ===== */
    @keyframes confetti-burst {
      0%   { transform: translate(0, 0) rotate(0deg) scale(1); opacity: 1; }
      100% { opacity: 0; }
    }
    .confetti-burst-piece {
      position: absolute; width: 10px; height: 10px;
      pointer-events: none; z-index: 50;
    }

    /* ===== QUIZ PILL ===== */
    .pill {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 18px; border-radius: 9999px; font-weight: 800; font-family: 'Inter', sans-serif; font-size: 1.1rem;
      background: white;
      border: 3px solid black; box-shadow: 4px 4px 0px 0px rgba(0,0,0,1); color: #000;
    }

    /* ===== ANSWER GRID BTN ===== */
    .answer-btn {
      transition: all 0.15s ease-out;
      background: white;
      border: 3px solid black; box-shadow: 6px 6px 0px 0px rgba(0,0,0,1);
      border-radius: 1rem; color: #000;
    }
    .answer-btn:hover:not(.answered) {
      box-shadow: 2px 2px 0px 0px rgba(0,0,0,1) !important;
      transform: translate(4px, 4px) !important;
      background: #FFC107 !important; /* Yellow fill on hover */
    }
    .answer-btn:active:not(.answered) {
      box-shadow: 0px 0px 0px 0px rgba(0,0,0,1) !important;
      transform: translate(6px, 6px) !important;
    }



    /* ===== TIMER SHRINK ===== */
    .timer-bar {
      height: 6px;
      border-radius: 999px;
      background: #EF4444;
      transition: width 1s linear;
    }

    /*
     * Système de vues (SPA-like) :
     * Toutes les <section class="view"> sont display:none par défaut.
     * La classe .active les affiche avec une animation d'entrée (slide up + fade).
     * Le switch est géré par showView() en JS, qui toggle .active et
     * contrôle le verrouillage du scroll via .scroll-locked sur le body.
     */
    .view { display: none; }
    .view.active {
      display: flex;
      animation: fadeSlideIn 0.5s ease forwards;
    }
    @keyframes fadeSlideIn {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ===== IMAGE PLACEHOLDER ===== */
    .img-placeholder {
      background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
    }

    /*
     * ===== SECTION NOTES D'INTENTION =====
     * Section accessible par scroll libre depuis la page d'accueil.
     * Le gradient vertical (transparent → noir 30%) assombrit progressivement
     * le bas de page pour séparer visuellement le hero des notes.
     *
     * Les blocs de texte (.notes-block) utilisent un border-left comme
     * marqueur typographique discret, sans effet glassmorphism, pour rester
     * cohérent avec la DA néo-brutaliste du reste du site.
     *
     * Les thèmes (.theme-item) affichent un tooltip positionné en absolute
     * (pas de décalage du layout) avec une flèche CSS (::after border trick).
     */
    .notes-section {
      background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.3) 100%);
    }
    .notes-block {
      border-left: 3px solid rgba(255,255,255,0.15);
      padding-left: 1.5rem;
      margin-bottom: 2.5rem;
      transition: border-color 0.3s ease;
    }
    .notes-block:hover {
      border-color: rgba(251,191,36,0.5);
    }
    .theme-item {
      position: relative;
      cursor: default;
      transition: color 0.2s ease;
    }
    .theme-item:hover {
      color: #FBBF24;
    }
    .theme-tip {
      position: absolute;
      bottom: calc(100% + 8px);
      left: 50%;
      transform: translateX(-50%) scale(0.95);
      background: rgba(15, 23, 42, 0.92);
      backdrop-filter: blur(12px);
      border: 1.5px solid rgba(255,255,255,0.15);
      border-radius: 10px;
      padding: 8px 12px;
      width: max-content;
      max-width: 220px;
      pointer-events: none;
      opacity: 0;
      transition: opacity 0.2s ease, transform 0.2s ease;
      z-index: 50;
      box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    }
    .theme-tip::after {
      content: '';
      position: absolute;
      top: 100%;
      left: 50%;
      transform: translateX(-50%);
      border: 5px solid transparent;
      border-top-color: rgba(15, 23, 42, 0.92);
    }
    .theme-item:hover .theme-tip {
      opacity: 1;
      transform: translateX(-50%) scale(1);
    }
    .scroll-indicator {
      animation: bounce-down 2s ease-in-out infinite;
    }
    @keyframes bounce-down {
      0%, 100% { transform: translateY(0); opacity: 0.6; }
      50% { transform: translateY(10px); opacity: 1; }
    }

    /*
     * ===== QUIZ RESPONSIVE (HAUTEUR) =====
     * Le quiz doit tenir en une seule vue sans scroll pendant le jeu.
     * On utilise des breakpoints en max-height (pas width) car le problème
     * principal est le viewport vertical : sur mobile paysage ou écrans courts,
     * la question + 4 réponses + timer doivent tous rester visibles.
     *
     * Les !important sont nécessaires car les tailles Tailwind inline
     * ont une spécificité élevée qu'il faut surcharger.
     */
    @media (max-height: 700px) {
      .quiz-question-card { padding: 0.5rem 0.75rem !important; }
      .quiz-question-card p:first-child { font-size: 1rem !important; }
      .quiz-answer-btn { min-height: 40px !important; padding: 0.35rem !important; font-size: 0.8rem !important; }
      .quiz-timer-bar { height: 1.25rem !important; }
    }
    @media (max-height: 550px) {
      .quiz-question-card { padding: 0.35rem 0.5rem !important; }
      .quiz-question-card p:first-child { font-size: 0.85rem !important; }
      .quiz-answer-btn { min-height: 32px !important; padding: 0.25rem !important; font-size: 0.75rem !important; }
    }
  </style>
</head>

<body class="font-body">

  <!--
    ===================== HEADER =====================
    Header fixe en haut de page (z-30 pour rester au-dessus de tout contenu).
    Le bouton Accueil utilise un style glassmorphism léger (backdrop-filter + fond translucide)
    pour se démarquer des boutons Profil/Quitter qui restent en néo-brutalisme pur.
    Le texte "BaraQuiz" est masqué sur mobile (hidden sm:inline) pour gagner en place.
  -->
  <header class="p-4 sm:p-6 flex justify-between items-center relative z-30" style="flex-shrink:0;">
    <a href="index.php" class="pill flex items-center gap-2 no-underline cursor-pointer transition-all duration-200 hover:scale-105" style="background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.25);color:white;box-shadow:none;backdrop-filter:blur(10px);text-decoration:none;">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
      <span class="hidden sm:inline">BaraQuiz</span>
    </a>
    <div class="flex gap-3 sm:gap-4">
      <a href="profil.php" class="btn-premium font-display font-bold text-black bg-white px-4 sm:px-5 py-2 hover:bg-frites text-base sm:text-lg flex items-center gap-2 rounded-full">
        <div class="w-3 h-3 rounded-full bg-frites hidden md:block"></div>
        Profil
      </a>
      <a href="api/logout.php" class="btn-premium font-display font-bold text-black bg-white px-4 sm:px-5 py-2 hover:bg-carnival hover:text-white text-base sm:text-lg flex items-center gap-2 rounded-full">
        <svg class="w-4 h-4 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        Quitter
      </a>
    </div>
  </header>

  <!--
    Conteneur de confettis (fixed, plein écran, pointer-events:none).
    Les 35 pièces sont générées en JS au chargement et tombées en boucle infinie.
    Masqué par défaut, affiché uniquement pendant l'écran de fin.
  -->
  <div class="confetti-container hidden" id="confettiContainer"></div>

  <!--
    ===================== VUE 1 : ACCUEIL =====================
    Première vue chargée (class="active"). Contient :
    - Le hero (titre, sous-titre, CTA principal)
    - Des stickers SVG flottants décoratifs (brique, bière, maroilles, parapluie, hareng)
    - L'indicateur de scroll vers les notes d'intention
    - La section notes d'intention (genèse, terroir, technique, thèmes)
    - Le footer avec lien GitHub

    Cette vue a le scroll libre (body non verrouillé) pour permettre
    à l'utilisateur de découvrir les notes en bas de page.
  -->
  <section id="viewHome" class="view active flex-col items-center px-4 relative z-10 w-full text-white">
    
    <!-- Decorative floating stickers -->
    <div class="absolute inset-0 pointer-events-none z-0 opacity-30">

      <!-- Brick -->
      <div class="absolute top-[10%] left-[5%] sm:left-[15%] animate-float" style="animation-duration: 6s;">
        <div style="transform: rotate(-15deg);">
          <svg width="60" height="60" viewBox="0 0 100 100" fill="none" stroke="black" stroke-width="4" class="drop-shadow-lg">
            <path d="M10,40 L40,20 L90,30 L60,50 Z" fill="#E53935" stroke-linejoin="round"/>
            <path d="M10,40 L60,50 L60,80 L10,70 Z" fill="#C62828" stroke-linejoin="round"/>
            <path d="M60,50 L90,30 L90,60 L60,80 Z" fill="#B71C1C" stroke-linejoin="round"/>
            <circle cx="35" cy="35" r="4" fill="black" opacity="0.2"/>
            <circle cx="50" cy="38" r="4" fill="black" opacity="0.2"/>
            <circle cx="65" cy="41" r="4" fill="black" opacity="0.2"/>
          </svg>
        </div>
      </div>
      
      <!-- Beer -->
      <div class="absolute top-[20%] right-[5%] sm:right-[15%] animate-float" style="animation-duration: 7s; animation-delay: 1s;">
        <div style="transform: rotate(10deg);">
          <svg width="60" height="60" viewBox="0 0 100 100" fill="none" stroke="black" stroke-width="4" class="drop-shadow-lg">
            <path d="M25,30 L25,85 A5,5 0 0,0 30,90 L70,90 A5,5 0 0,0 75,85 L75,30" fill="#FFCA28" stroke-linejoin="round"/>
            <path d="M20,30 C20,15 40,15 40,25 C50,10 70,10 70,25 C85,25 80,40 65,40 C55,45 35,45 25,40 C15,35 15,30 20,30 Z" fill="white" stroke-linejoin="round"/>
            <path d="M75,40 C90,40 90,70 75,70" fill="none" stroke-width="8" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      
      <!-- Maroilles -->
      <div class="absolute bottom-[20%] left-[8%] sm:left-[20%] animate-float" style="animation-duration: 5s; animation-delay: 2s;">
        <div style="transform: rotate(25deg);">
          <svg width="60" height="60" viewBox="0 0 100 100" fill="none" stroke="black" stroke-width="4" class="drop-shadow-lg">
            <path d="M20,40 L80,20 L90,40 L30,60 Z" fill="#FFD54F" stroke-linejoin="round"/>
            <path d="M30,60 L90,40 L90,80 L30,90 Z" fill="#FFB300" stroke-linejoin="round"/>
            <path d="M20,40 L30,60 L30,90 L20,70 Z" fill="#FFA000" stroke-linejoin="round"/>
            <path d="M30,60 L90,40 L90,50 L30,70 Z" fill="#E65100" opacity="0.6"/>
          </svg>
        </div>
      </div>
      
      <!-- Umbrella -->
      <div class="absolute bottom-[30%] right-[10%] sm:right-[20%] animate-float" style="animation-duration: 8s; animation-delay: 0.5s;">
        <div style="transform: rotate(-20deg);">
          <svg width="60" height="60" viewBox="0 0 100 100" fill="none" stroke="black" stroke-width="4" class="drop-shadow-lg">
            <path d="M10,50 C10,20 90,20 90,50 Z" fill="#039BE5" stroke-linejoin="round"/>
            <path d="M50,23 L50,50" stroke-width="3"/>
            <path d="M10,50 C30,30 50,23 50,23" stroke-width="3"/>
            <path d="M90,50 C70,30 50,23 50,23" stroke-width="3"/>
            <path d="M50,50 L50,80 C50,90 40,90 40,85" stroke-width="4" stroke-linecap="round" fill="none"/>
          </svg>
        </div>
      </div>
      
      <!-- Hareng -->
      <div class="absolute top-[45%] left-[2%] sm:left-[10%] animate-float" style="animation-duration: 6.5s; animation-delay: 3s;">
        <div style="transform: rotate(35deg);">
          <svg width="80" height="80" viewBox="0 0 100 100" fill="none" stroke="black" stroke-width="4" class="drop-shadow-lg">
            <path d="M20,50 C30,30 70,30 85,45 L95,35 L95,65 L85,55 C70,70 30,70 20,50 Z" fill="#B0BEC5" stroke-linejoin="round"/>
            <circle cx="35" cy="50" r="3" fill="black"/>
            <line x1="88" y1="50" x2="93" y2="50" stroke-width="2"/>
            <line x1="88" y1="45" x2="93" y2="40" stroke-width="2"/>
            <line x1="88" y1="55" x2="93" y2="60" stroke-width="2"/>
          </svg>
        </div>
      </div>
    </div>

    <div class="text-center max-w-3xl mx-auto z-10 relative flex flex-col items-center justify-center" style="min-height: calc(100vh - 72px);">
      <!-- Title -->
      <h1 class="anim-pop font-display text-7xl sm:text-8xl md:text-[11rem] text-white mb-4 sm:mb-6 select-none relative inline-block tracking-tight"
          style="line-height:1; text-shadow: 0 8px 30px rgba(0,0,0,0.4), 0 2px 4px rgba(0,0,0,0.3);">
        BaraQuiz!
        <!-- Fries cone icon -->
        <span class="absolute -top-6 -right-10 sm:-right-14 w-14 h-14 sm:w-20 sm:h-20 pointer-events-none" style="z-index: 10; transform: rotate(15deg); animation: float-anim 4s ease-in-out infinite;">
          <svg viewBox="0 0 100 100" fill="none" stroke="rgba(255,255,255,0.8)" stroke-width="3" xmlns="http://www.w3.org/2000/svg">
            <path d="M20,40 L50,90 L80,40 Z" fill="#EF4444" stroke-linejoin="round"/>
            <rect x="30" y="20" width="8" height="25" fill="#FFC107" rx="4" transform="rotate(-15 34 32)" />
            <rect x="45" y="15" width="8" height="30" fill="#FFC107" rx="4" />
            <rect x="60" y="22" width="8" height="20" fill="#FFC107" rx="4" transform="rotate(15 64 32)" />
          </svg>
        </span>
      </h1>
      <!-- Subtitle -->
      <p class="anim-slide anim-delay-2 text-lg sm:text-2xl text-white/70 font-semibold mb-8 sm:mb-12 px-4 font-body">
         Le quiz ultime du Nord. Prouve que t'es pas un boubourse.
      </p>
      <!-- CTA -->
      <button id="btnStart"
              onclick="showView('viewFrites')"
              class="btn-cta-glow anim-delay-3 btn-premium inline-flex items-center justify-center gap-4 bg-frites text-black font-display text-2xl sm:text-4xl px-10 sm:px-16 py-5 sm:py-6 rounded-2xl cursor-pointer select-none tracking-wide">
        TIRER LA FRITE !
      </button>

      <!-- Scroll indicator -->
      <div class="scroll-indicator mt-12 sm:mt-16 flex flex-col items-center gap-2 cursor-pointer" onclick="document.getElementById('notesSection').scrollIntoView({behavior:'smooth'})">
        <span class="text-white/50 text-sm font-semibold tracking-widest uppercase">Découvrir le projet</span>
        <svg class="w-6 h-6 text-white/40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
      </div>
    </div>

    <!-- ===================== NOTES D'INTENTION ===================== -->
    <div id="notesSection" class="notes-section w-full px-4 sm:px-8 py-16 sm:py-24 relative z-10">
      <div class="max-w-3xl mx-auto">
        <!-- Section Title -->
        <div class="text-center mb-12 sm:mb-16">
          <h2 class="font-display text-3xl sm:text-5xl text-white mb-4" style="text-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            Pourquoi BaraQuiz ?
          </h2>
          <p class="text-white/50 text-base sm:text-lg max-w-2xl mx-auto">
            De l'idée universitaire à la déclaration d'amour pour les Hauts-de-France.
          </p>
        </div>

        <!-- Block 1: Genèse -->
        <div class="notes-block">
          <h3 class="font-display text-xl sm:text-2xl text-white mb-3">Genèse & Projet Étudiant</h3>
          <p class="text-white/55 leading-relaxed text-sm sm:text-base">
            BaraQuiz n'est pas seulement un jeu festif, c'est avant tout l'aboutissement d'un projet universitaire réalisé dans le cadre du module de développement web dispensé par M. Cartailler. Le cahier des charges était exigeant : concevoir une application web complète, fonctionnelle et sécurisée, en maîtrisant l'architecture logicielle de bout en bout. Ce projet a permis de mettre en pratique la séparation entre un Frontend dynamique et un Backend robuste (API PHP, base de données MySQL via PDO). Au-delà de l'exercice académique, un véritable défi de conception pour valider des compétences de développeur full-stack.
          </p>
        </div>

        <!-- Block 2: Terroir -->
        <div class="notes-block">
          <h3 class="font-display text-xl sm:text-2xl text-white mb-3">Une histoire de Terroir & de Cœur</h3>
          <p class="text-white/55 leading-relaxed text-sm sm:text-base">
            Plutôt que de développer un énième quiz de culture générale ou de reprendre le concept froid d'un jeu télévisé, ce projet technique est devenu une véritable vitrine de la région natale : les Hauts-de-France. Notre territoire est riche, complexe, et mérite bien mieux que les clichés habituels. À travers plus de 400 questions minutieusement sourcées, BaraQuiz célèbre la diversité de notre patrimoine — de la ferveur des stades de football locaux aux subtilités du patois ch'ti et picard, en passant par l'histoire des bassins miniers, la gastronomie des estaminets ou la douce folie du Carnaval de Dunkerque.
          </p>
        </div>

        <!-- Block 3: Technique -->
        <div class="notes-block">
          <h3 class="font-display text-xl sm:text-2xl text-white mb-3">Sous le capot : Technique & Design</h3>
          <p class="text-white/55 leading-relaxed text-sm sm:text-base">
            La Direction Artistique s'appuie sur le Néo-Brutalisme : couleurs primaires saturées, bordures épaisses et ombres dures. Ce design moderne incarne l'esprit joyeux et convivial d'une baraque à frites, tout en respectant les standards de l'UI/UX design actuel. Côté serveur, l'application intègre un système d'authentification sécurisé avec hachage des mots de passe. Le Carnet du Baraqui agrège les données pour offrir un suivi de progression précis aux joueurs.
          </p>
        </div>

        <!-- Separator -->
        <div class="w-16 h-[2px] bg-white/15 mx-auto my-10 sm:my-14"></div>

        <!-- Themes -->
        <div class="text-center">
          <h3 class="font-display text-2xl sm:text-3xl text-white mb-8" style="text-shadow: 0 2px 10px rgba(0,0,0,0.3);">10 thèmes à explorer</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-x-6 gap-y-5 text-left max-w-3xl mx-auto">
            <div class="theme-item">
              <span class="text-white/70 font-bold text-sm sm:text-base">Gastronomie</span>
              <div class="theme-tip"><span class="text-white/90 text-xs leading-snug">Fricadelle, welsh, maroilles et baraques à frites.</span></div>
            </div>
            <div class="theme-item">
              <span class="text-white/70 font-bold text-sm sm:text-base">Carnaval de Dunkerque</span>
              <div class="theme-tip"><span class="text-white/90 text-xs leading-snug">Bandes, jets de harengs et chansons de carnaval.</span></div>
            </div>
            <div class="theme-item">
              <span class="text-white/70 font-bold text-sm sm:text-base">Patois</span>
              <div class="theme-tip"><span class="text-white/90 text-xs leading-snug">Ch'ti, picard et expressions du Nord.</span></div>
            </div>
            <div class="theme-item">
              <span class="text-white/70 font-bold text-sm sm:text-base">Géographie</span>
              <div class="theme-tip"><span class="text-white/90 text-xs leading-snug">Villes, fleuves et paysages des Hauts-de-France.</span></div>
            </div>
            <div class="theme-item">
              <span class="text-white/70 font-bold text-sm sm:text-base">Braderie de Lille</span>
              <div class="theme-tip"><span class="text-white/90 text-xs leading-snug">Le plus grand marché aux puces d'Europe.</span></div>
            </div>
            <div class="theme-item">
              <span class="text-white/70 font-bold text-sm sm:text-base">Histoire & Mines</span>
              <div class="theme-tip"><span class="text-white/90 text-xs leading-snug">Bassins miniers, patrimoine UNESCO et mémoire ouvrière.</span></div>
            </div>
            <div class="theme-item">
              <span class="text-white/70 font-bold text-sm sm:text-base">Paris-Roubaix & Sport</span>
              <div class="theme-tip"><span class="text-white/90 text-xs leading-snug">L'Enfer du Nord, le LOSC et les clubs mythiques.</span></div>
            </div>
            <div class="theme-item">
              <span class="text-white/70 font-bold text-sm sm:text-base">Célébrités du Nord</span>
              <div class="theme-tip"><span class="text-white/90 text-xs leading-snug">De Gaulle, Dany Boon, Pierre Bachelet...</span></div>
            </div>
            <div class="theme-item">
              <span class="text-white/70 font-bold text-sm sm:text-base">Cinéma & Culture</span>
              <div class="theme-tip"><span class="text-white/90 text-xs leading-snug">Bienvenue chez les Ch'tis, Germinal, les Corons.</span></div>
            </div>
            <div class="theme-item">
              <span class="text-white/70 font-bold text-sm sm:text-base">Traditions & Folklore</span>
              <div class="theme-tip"><span class="text-white/90 text-xs leading-snug">Géants, ducasses, P'tit Quinquin et estaminets.</span></div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-14">
          <a href="https://github.com/Viktor59000/BaraQuiz" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-white/30 hover:text-white/60 text-xs sm:text-sm font-semibold transition-colors">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
            Voir le code source sur GitHub
          </a>
          <p class="text-white/20 text-xs sm:text-sm mt-3 font-semibold">
            BaraQuiz — Projet universitaire · Module Développement Web · <?= date('Y') ?>
          </p>
        </div>
      </div>
    </div>
  </section>

  <!--
    ===================== VUE 2 : TIRAGE DE FRITES =====================
    Vue intermédiaire entre l'accueil et le quiz. Scroll verrouillé.
    L'utilisateur interagit avec la frite cliquable superposée sur l'image
    du cornet pour déclencher la roulette de sélection du thème.

    Composants :
    - Particules ambiantes (générées par JS, effet lumineux flottant)
    - Image statique du cornet + frite interactive (CSS pur)
    - CTA glassmorphism ("TIRER !") superposé sur le cornet
    - Étiquette de thème (slot machine) qui apparaît après le tirage
    - Bouton "Lancer le Quiz" (affiché après la révélation du thème)
  -->
  <section id="viewFrites" class="view flex-col items-center justify-center px-4 py-4 relative z-10 text-white" style="height: calc(100vh - 88px);">
    <!-- Ambient mist -->
    <div class="ambient-mist"></div>
    <!-- Ambient particles (generated by JS) -->
    <div id="ambientParticles" class="absolute inset-0 pointer-events-none z-0 overflow-hidden"></div>

    <div class="text-center max-w-xl mx-auto relative z-10 -mt-10">
      <h2 class="font-display text-4xl sm:text-5xl text-flandres mb-2 select-none"
          style="line-height:1.15; text-shadow: 0 0 30px rgba(251,191,36,0.3), 0 2px 4px rgba(0,0,0,0.5);">
        La Frite du Destin !
      </h2>
      <p class="text-base sm:text-lg text-gray-400 font-semibold mb-4"
         style="text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
        Tire la frite la plus haute pour découvrir ton thème !
      </p>

      <!-- Frites Cone Scene (image-based) -->
      <div class="cone-scene mx-auto mb-4">
        <div class="cone-image-wrapper" id="coneWrapper">
          <!-- Static frites cone image -->
          <img src="assets/img/frites_cone.png" alt="Cornet de frites" />
          <!-- Single interactive fry overlaid -->
          <div class="fry-interactive" id="interactiveFrite" title="Tire ta frite !"></div>
          <!-- Glassmorphism CTA -->
          <div class="cta-glass" id="ctaGlass" style="top: -50px; left: 50%; transform: translateX(-50%);">
            <span class="cta-text">TIRER !</span>
          </div>
        </div>
      </div>

      <!-- Theme reveal label (premium dark glass) -->
      <div id="themeLabel" class="theme-label mt-2">
        <div class="theme-label-box" style="background: white; border: 3px solid black; box-shadow: 4px 4px 0px 0px black; backdrop-filter: none; border-radius: 1rem;">
          <span class="font-display text-xl sm:text-2xl text-black">THÈME :</span>
          <span class="theme-slot" id="themeSlot">
            <span id="themeName" class="font-display text-xl sm:text-2xl text-sky ml-2 font-black"></span>
          </span>
        </div>
      </div>

      <!-- Launch quiz button (premium style) -->
      <button id="btnLaunchQuiz"
              onclick="showView('viewQuiz'); startQuiz()"
              class="btn-premium mt-10 font-display text-2xl sm:text-3xl px-10 py-4 rounded-3xl cursor-pointer select-none hidden text-white w-full max-w-sm mx-auto"
              style="background: #10B981;">
        Lancer le Quiz !
      </button>
    </div>
  </section>

  <!--
    ===================== VUE 3 : QUIZ =====================
    Vue principale de jeu. Scroll verrouillé pendant les questions.
    min-height au lieu de height fixe pour permettre le scroll libre
    sur l'écran de fin (endScreen) qui peut dépasser le viewport.

    Structure flex verticale :
    1. Timer bar (barre rouge qui se réduit chaque seconde)
    2. Status bar (score + thème en pills néo-brutalistes)
    3. Zone média conditionnelle (image si type_media === 'image')
    4. Carte question + grille 2x2 de réponses
    5. Overlay anecdote (slide-up après chaque réponse)

    L'anecdote recouvre les réponses avec un slide-up animé
    (translate-y) plutôt qu'un modal classique, pour maintenir
    le contexte visuel de la question en arrière-plan.
  -->
  <section id="viewQuiz" class="view flex-col items-center px-2 py-2 relative z-10 w-full max-w-3xl mx-auto gap-1 sm:gap-2 text-black" style="min-height: calc(100vh - 72px);">

      <!-- Loading State -->
      <div id="quizLoader" class="flex flex-col items-center justify-center py-10 w-full card-premium mt-4">
        <div class="w-12 h-12 border-4 border-slate-200 border-t-sky-500 rounded-full animate-spin mb-4"></div>
        <p class="font-display text-xl text-center animate-pulse text-sky-700">Préparation...</p>
      </div>

      <!-- Core Quiz UI (hidden initially) -->
      <div id="quizCore" class="hidden w-full h-full flex-col gap-1 sm:gap-2 relative overflow-hidden pb-2 sm:pb-4">
        
        <!-- Big Premium Timer Bar at the very top -->
        <div class="quiz-timer-bar w-full h-6 sm:h-8 border-3 sm:border-4 border-black rounded-full overflow-hidden bg-white shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] sm:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] relative flex-shrink-0">
          <div id="timerBar" class="bg-carnival h-full" style="width:100%; transition: width 1s linear;"></div>
        </div>

        <!-- Top Status Bar -->
        <div class="flex items-center justify-between gap-2 px-1 sm:px-2 py-1 sm:py-2 flex-shrink-0">
          <div class="pill text-xs sm:text-base px-3 sm:px-4 py-1">
            <span class="text-frites hidden sm:inline">●</span>
            <span id="scoreDisplay">0 / 10</span>
          </div>
          <div class="pill text-xs sm:text-base px-3 sm:px-4 py-1" id="themePill">
            <span class="text-sky-500 hidden sm:inline">★</span>
            <span id="themeDisplayPill" class="truncate max-w-[120px] sm:max-w-none">THÈME</span>
          </div>
        </div>

        <!-- Media area (Conditional) -->
        <div id="mediaWrapper" class="card-premium overflow-hidden relative hidden flex-shrink-0 border-[3px] self-center transition-all duration-300">
          <div id="mediaContainer" class="select-none relative flex justify-center items-center bg-white" style="max-height: 25vh;">
            <!-- Injected by JS -->
          </div>
        </div>

        <!-- Main Workspace: Question + Answers -->
        <div class="flex-grow flex flex-col gap-1 sm:gap-2 min-h-0 relative">
          
          <!-- Question Card -->
          <div class="quiz-question-card card-premium p-3 sm:p-5 text-center flex-shrink-0 flex flex-col justify-center shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] sm:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] z-10">
            <p id="questionText" class="font-body text-base sm:text-xl md:text-2xl leading-snug text-black font-bold"></p>
            <p id="questionCounter" class="text-[0.65rem] sm:text-xs font-bold text-slate-500 mt-1 sm:mt-2 uppercase tracking-wide"></p>
          </div>

          <!-- Answers Grid -->
          <div id="answersGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 flex-grow min-h-0 z-10">
            <!-- Answers injected by JS -->
          </div>

          <!-- Anecdote & Next Button Overlay (Slides up over answers) -->
          <div id="anecdoteOverlay" class="absolute bottom-0 left-0 w-full h-full bg-sky/95 backdrop-blur-md border-3 sm:border-4 border-black rounded-2xl sm:rounded-3xl shadow-[0_-8px_30px_rgba(0,0,0,0.5)] z-20 flex flex-col p-3 sm:p-6 transition-transform duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] translate-y-[120%]" style="pointer-events: auto;">
            <div class="flex justify-between items-center mb-2 border-b-2 border-black pb-2">
              <p class="font-display text-lg sm:text-2xl text-white drop-shadow-md">Le savais-tu ?</p>
            </div>
            <div class="flex-grow pr-2">
              <p id="anecdoteText" class="text-sm sm:text-base lg:text-lg font-semibold text-white leading-relaxed"></p>
            </div>
            <!-- Next Button inside the overlay -->
            <button id="btnNext"
                    onclick="nextQuestion()"
                    class="btn-premium mt-2 sm:mt-4 bg-frites text-black font-display text-lg sm:text-2xl px-4 sm:px-6 py-3 sm:py-4 rounded-xl sm:rounded-2xl w-full flex-shrink-0 animate-pulse hover:animate-none">
              QUESTION SUIVANTE →
            </button>
          </div>

        </div>
      </div>

      <!-- End screen (hidden) -->
      <div id="endScreen" class="hidden text-center relative w-full flex-col pb-8">
        
        <div class="relative z-10 w-full flex flex-col items-center min-h-0">
          <!-- Score Header -->
          <div class="w-full bg-white border-3 sm:border-4 border-black rounded-2xl shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] sm:shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] p-4 sm:p-6 mb-4">
            <h2 class="font-display text-2xl sm:text-4xl text-black mb-2 sm:mb-3">Quiz terminé !</h2>
            <p id="finalScore" class="font-display text-4xl sm:text-6xl text-frites mb-2" style="-webkit-text-stroke: 2px black; text-shadow: 4px 4px 0px black;">...</p>
            <p id="finalMessage" class="text-sm sm:text-base font-bold text-slate-600">Calcul des résultats...</p>
          </div>
          
          <!-- Recap Grid (Detailed list) -->
          <div id="recapContainer" class="hidden w-full mb-4">
            <div id="recapGrid" class="flex flex-col gap-2 sm:gap-3 w-full text-left">
              <!-- JS fills 10 cells -->
            </div>
          </div>

          <button onclick="location.reload()"
                  class="flex-shrink-0 btn-premium bg-carnival text-white font-display text-lg sm:text-2xl px-6 sm:px-8 py-3 rounded-2xl w-full md:w-auto mb-4">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 inline -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            REJOUER
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== JAVASCRIPT ===================== -->
  <script>
    // ——————————————————————————————————————————————————————————
    // CONFETTIS : Génération procédurale de 35 pièces de confettis
    // avec couleurs, formes, tailles et timings aléatoires.
    // Les confettis tombent en boucle infinie (animation CSS)
    // et sont utilisés comme fond festif pendant la page de résultats.
    // ——————————————————————————————————————————————————————————
    (function createConfetti() {
      const container = document.getElementById('confettiContainer');
      const colors = ['#FFC107', '#EF4444', '#0EA5E9', '#ffffff', '#66bb6a', '#ab47bc'];
      const shapes = ['■', '●', '▲', '◆'];
      for (let i = 0; i < 35; i++) {
        const el = document.createElement('div');
        el.classList.add('confetti-piece');
        el.textContent = shapes[Math.floor(Math.random() * shapes.length)];
        el.style.left = Math.random() * 100 + '%';
        el.style.color = colors[Math.floor(Math.random() * colors.length)];
        el.style.fontSize = (10 + Math.random() * 16) + 'px';
        el.style.animationDuration = (4 + Math.random() * 6) + 's';
        el.style.animationDelay = (Math.random() * 8) + 's';
        container.appendChild(el);
      }
    })();

    // ——————————————————————————————————————————————————————————
    // GESTION DES VUES
    // Système SPA-like : une seule vue visible à la fois.
    // On toggle la classe .active et on contrôle le scroll du body :
    // - viewHome : scroll libre pour accéder aux notes d'intention
    // - viewFrites/viewQuiz : scroll verrouillé (contenu plein écran)
    // window.scrollTo(0,0) évite de garder la position de scroll
    // de la vue précédente.
    // ——————————————————————————————————————————————————————————
    function showView(viewId) {
      document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
      const target = document.getElementById(viewId);
      target.classList.add('active');
      window.scrollTo(0, 0);
      if (viewId === 'viewHome') {
        document.body.classList.remove('scroll-locked');
      } else if (viewId === 'viewFrites') {
        document.body.classList.add('scroll-locked');
      } else if (viewId === 'viewQuiz') {
        document.body.classList.add('scroll-locked');
      }
    }

    // ——————————————————————————————————————————————————————————
    // THÈMES DISPONIBLES
    // Liste miroir des 10 thèmes en BDD (table questions.theme).
    // Utilisée pour la roulette et l'appel API get_quiz.php.
    // Si de nouveaux thèmes sont ajoutés en BDD, mettre à jour ce tableau.
    // ——————————————————————————————————————————————————————————
    const ALL_THEMES = [
      'Gastronomie',
      'Carnaval de Dunkerque',
      'Patois',
      'Géographie',
      'Braderie de Lille',
      'Histoire & Mines',
      'Paris-Roubaix & Sport',
      'Célébrités du Nord',
      'Cinéma & Culture',
      'Traditions & Folklore'
    ];
    let selectedTheme = '';

    // Sélection aléatoire simple. Pas de logique anti-répétition car
    // l'utilisateur joue un quiz à la fois et recharge la page entre deux.
    function getNextTheme() {
      return ALL_THEMES[Math.floor(Math.random() * ALL_THEMES.length)];
    }

    // ——————————————————————————————————————————————————————————
    // PARTICULES AMBIANTES
    // 20 points lumineux qui remontent légèrement (animation float-up CSS)
    // dans la vue Frites. Purement décoratif, pointer-events désactivé.
    // ——————————————————————————————————————————————————————————
    (function generateParticles() {
      const container = document.getElementById('ambientParticles');
      for (let i = 0; i < 20; i++) {
        const p = document.createElement('div');
        p.className = 'ambient-particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.top = (50 + Math.random() * 50) + '%';
        p.style.animationDuration = (8 + Math.random() * 12) + 's';
        p.style.animationDelay = (Math.random() * 5) + 's';
        container.appendChild(p);
      }
    })();

    // ——————————————————————————————————————————————————————————
    // INTERACTION FRITE + ROULETTE DE THÈME
    // Séquence d'animation au clic sur la frite :
    // 1. Animation d'arrachage (.pulled) sur la frite
    // 2. Après 700ms : apparition de l'étiquette de thème
    // 3. Slot machine : 24 cycles de 80ms (noms aléatoires qui défilent)
    // 4. Révélation du thème final + burst de confettis
    // 5. Après 700ms supplémentaires : affichage du bouton "Lancer le Quiz"
    //
    // Verrouillage via friteSelected pour éviter les double-clics.
    // ——————————————————————————————————————————————————————————
    document.getElementById('interactiveFrite').onclick = () => selectFrite();
    let friteSelected = false;

    function selectFrite() {
      if (friteSelected) return;
      friteSelected = true;

      const interFrite = document.getElementById('interactiveFrite');
      const themeLabel = document.getElementById('themeLabel');
      const themeSlot = document.getElementById('themeSlot');
      const themeName = document.getElementById('themeName');
      const ctaGlass = document.getElementById('ctaGlass');

      // Hide CTA immediately
      if (ctaGlass) ctaGlass.classList.add('hidden');

      // 1) Pull animation on the interactive fry
      interFrite.classList.add('pulled');

      // 3) Show theme label and start slot machine
      setTimeout(() => {
        themeLabel.classList.add('visible');
        themeSlot.classList.add('spinning');

        // Pick theme from shuffled deck (no repeats until all used)
        selectedTheme = getNextTheme();

        // Rapid cycling of theme names
        let cycleCount = 0;
        const totalCycles = 24;
        const slotInterval = setInterval(() => {
          const randomT = ALL_THEMES[Math.floor(Math.random() * ALL_THEMES.length)];
          themeName.textContent = randomT;
          cycleCount++;
          if (cycleCount >= totalCycles) {
            clearInterval(slotInterval);
            // Reveal final theme
            themeSlot.classList.remove('spinning');
            themeSlot.classList.add('revealed');
            themeName.textContent = selectedTheme;
            document.getElementById('themeDisplayPill').textContent = selectedTheme;

            // Confetti burst!
            launchConfettiBurst(themeLabel);

            // Show launch button
            setTimeout(() => {
              document.getElementById('btnLaunchQuiz').classList.remove('hidden');
            }, 700);
          }
        }, 80);
      }, 700);
    }

    // ——— CONFETTI BURST ———
    function launchConfettiBurst(anchor) {
      const rect = anchor.getBoundingClientRect();
      const originX = rect.left + rect.width / 2;
      const originY = rect.top + rect.height / 2;
      const colors = ['#FFC107', '#EF4444', '#0EA5E9', '#66bb6a', '#ab47bc', '#ff7043', '#ffffff'];
      const shapes = ['■', '●', '▲', '◆', '★'];
      for (let i = 0; i < 40; i++) {
        const piece = document.createElement('div');
        piece.classList.add('confetti-burst-piece');
        piece.textContent = shapes[Math.floor(Math.random() * shapes.length)];
        piece.style.left = originX + 'px';
        piece.style.top = originY + 'px';
        piece.style.color = colors[Math.floor(Math.random() * colors.length)];
        piece.style.fontSize = (10 + Math.random() * 14) + 'px';
        piece.style.position = 'fixed';
        // Random velocity
        const angle = Math.random() * Math.PI * 2;
        const dist = 80 + Math.random() * 180;
        const tx = Math.cos(angle) * dist;
        const ty = Math.sin(angle) * dist - 60;
        const rot = (Math.random() - 0.5) * 720;
        piece.style.setProperty('--tx', tx + 'px');
        piece.style.setProperty('--ty', ty + 'px');
        piece.style.animation = `confetti-burst 1.2s cubic-bezier(0, 0.5, 0.5, 1) forwards`;
        piece.style.setProperty('animation-name', 'none');
        document.body.appendChild(piece);
        // Force reflow then animate
        requestAnimationFrame(() => {
          piece.style.animation = 'none';
          piece.style.transition = 'all 1.2s cubic-bezier(0, 0.5, 0.5, 1)';
          piece.style.transform = `translate(${tx}px, ${ty}px) rotate(${rot}deg) scale(0.3)`;
          piece.style.opacity = '0';
        });
        setTimeout(() => piece.remove(), 1500);
      }
    }

    // ——————————————————————————————————————————————————————————
    // ÉTAT DU QUIZ
    // Toutes les variables d'état du quiz. Le state est côté client
    // uniquement ; la validation des réponses se fait côté serveur
    // via save_stats.php pour éviter toute triche (le score affiché
    // en fin de quiz est celui retourné par l'API, pas le compteur local).
    //
    // userAnswersObj : map {question_id: lettre_choisie} envoyée au serveur.
    // ——————————————————————————————————————————————————————————
    let quizQuestions = [];
    let currentQuestion = 0;
    let score = 0;
    let timerInterval = null;
    let timeLeft = 15;
    let userAnswersObj = {};

    /**
     * startQuiz()
     * Appelle l'API GET /api/get_quiz.php?theme=... pour récupérer 10 questions
     * aléatoires du thème sélectionné. Les réponses sont mélangées côté client
     * (Fisher-Yates) pour que l'ordre A/B/C/D ne soit jamais prédictible.
     *
     * On conserve les mappings originaux (original_A..D) car l'API save_stats
     * attend la lettre originale ("A", "B", etc.) pour valider côté serveur.
     */
    async function startQuiz() {
      currentQuestion = 0;
      score = 0;
      userAnswersObj = {};

      // Afficher le loader et masquer le quiz précédent
      document.getElementById('quizLoader').classList.remove('hidden');
      document.getElementById('quizLoader').classList.add('flex');
      document.getElementById('quizCore').classList.add('hidden');
      document.getElementById('quizCore').classList.remove('flex');
      document.getElementById('endScreen').classList.add('hidden');

      // Récupération des questions depuis l'API
      try {
        const response = await fetch(`api/get_quiz.php?theme=${encodeURIComponent(selectedTheme)}`);
        const data = await response.json();
        if (!data || data.error || data.length === 0) {
          alert('Aucune question trouvée pour ce thème. Recharge la page !');
          return;
        }
        // Transformation et mélange des réponses
        quizQuestions = data.map(q => {
          const answers = [q.reponse_A, q.reponse_B, q.reponse_C, q.reponse_D];
          const correctLetter = q.bonne_reponse; // A, B, C ou D
          const correctIndex = correctLetter.charCodeAt(0) - 65; // A=0, B=1, C=2, D=3
          const correctAnswer = answers[correctIndex];
          // Mélange Fisher-Yates : complexité O(n), uniforme
          for (let i = answers.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [answers[i], answers[j]] = [answers[j], answers[i]];
          }
        return {
            id: q.id,
            question: q.question,
            answers: answers,
            correct: answers.indexOf(correctAnswer), // Nouvel index après mélange
            anecdote: q.anecdote || '',
            type_media: q.type_media,
            url_media: q.url_media,
            // Mappings originaux pour retrouver la lettre envoyée au serveur
            original_A: q.reponse_A,
            original_B: q.reponse_B,
            original_C: q.reponse_C,
            original_D: q.reponse_D
          };
        });
      } catch (err) {
        alert('Erreur de chargement des questions. Vérifie ta connexion !');
        console.error(err);
        return;
      }

      updateScore();
      renderQuestion();
    }

    function renderQuestion() {
      if (currentQuestion >= quizQuestions.length) {
        endQuiz();
        return;
      }

      // Hide loader, show core
      document.getElementById('quizLoader').classList.add('hidden');
      document.getElementById('quizLoader').classList.remove('flex');
      document.getElementById('quizCore').classList.remove('hidden');
      document.getElementById('quizCore').classList.add('flex');

      const q = quizQuestions[currentQuestion];
      document.getElementById('questionText').textContent = q.question;
      document.getElementById('questionCounter').textContent = `Question ${currentQuestion + 1} / ${quizQuestions.length}`;

      // Handle Multimedia
      const mediaWrapper = document.getElementById('mediaWrapper');
      const mediaContainer = document.getElementById('mediaContainer');
      const mediaLabel = document.getElementById('mediaLabel');

      if (q.type_media === 'image') {
          mediaWrapper.classList.remove('hidden', 'w-full');
          mediaWrapper.classList.add('self-center', 'max-w-full');
          mediaContainer.innerHTML = `<img src="${q.url_media.startsWith('http') ? q.url_media : 'assets/img/' + q.url_media}" alt="Illustration" class="block w-auto h-auto" style="max-height: 25vh; max-width: 100%; object-fit: contain;" onerror="this.src=''; this.alt='Image introuvable';"/>`;
      } else {
          mediaWrapper.classList.add('hidden');
          mediaContainer.innerHTML = '';
          if (mediaLabel) mediaLabel.textContent = '';
      }

      // Hide anecdote overlay
      document.getElementById('anecdoteOverlay').classList.remove('translate-y-0');
      document.getElementById('anecdoteOverlay').classList.add('translate-y-[120%]');

      // Render answers
      const grid = document.getElementById('answersGrid');
      grid.innerHTML = '';
      q.answers.forEach((ans, idx) => {
        const btn = document.createElement('button');
        // Reduce height constraint to fit on screen
        btn.className = 'quiz-answer-btn answer-btn bg-white border-[3px] border-black rounded-xl shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] sm:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] p-1.5 sm:p-2 flex items-center justify-center min-h-[44px] sm:min-h-[60px] font-bold text-xs sm:text-sm md:text-base text-center cursor-pointer select-none';
        btn.textContent = ans;
        btn.onclick = () => handleAnswer(idx, btn);
        grid.appendChild(btn);
      });

      startTimer();
    }

    /**
     * handleAnswer(idx, btn)
     * Gère le clic sur une réponse. Logique :
     * 1. Désactive tous les boutons (anti-double-clic)
     * 2. Arrête le timer
     * 3. Retrouve la lettre originale (A/B/C/D) correspondant au texte
     *    cliqué (les réponses ont été mélangées par Fisher-Yates)
     * 4. Stocke la réponse dans userAnswersObj pour envoi au serveur
     * 5. Feedback visuel : vert (correct) / rouge (mauvais) + vert sur la bonne
     * 6. Affiche l'anecdote en overlay slide-up après 500ms
     */
    function handleAnswer(idx, btn) {
      const q = quizQuestions[currentQuestion];
      
      // Désactiver tous les boutons pour empêcher les clics multiples
      const allBtns = document.querySelectorAll('.answer-btn');
      allBtns.forEach(b => { b.classList.add('answered'); b.style.pointerEvents = 'none'; });

      clearInterval(timerInterval);

      // Retrouver la lettre originale de la réponse cliquée.
      // Nécessaire car les réponses sont mélangées côté client
      // mais l'API attend "A", "B", "C" ou "D".
      const clickedText = btn.textContent;
      
      let trueLetter = null;
      if (q.original_A === clickedText) trueLetter = 'A';
      else if (q.original_B === clickedText) trueLetter = 'B';
      else if (q.original_C === clickedText) trueLetter = 'C';
      else if (q.original_D === clickedText) trueLetter = 'D';
      
      userAnswersObj[q.id] = trueLetter;

      // Feedback visuel immédiat
      if (idx === q.correct) {
        btn.classList.add('bg-green-500', 'text-white');
        btn.style.background = '#22c55e';
        btn.style.color = 'white';
        score++;
        updateScore();
      } else {
        btn.style.background = '#ef4444';
        btn.style.color = 'white';
        // Toujours montrer la bonne réponse en vert
        allBtns[q.correct].style.background = '#22c55e';
        allBtns[q.correct].style.color = 'white';
      }

      // Affichage de l'anecdote en overlay avec un délai pour laisser
      // le temps de voir le feedback de couleur
      document.getElementById('anecdoteText').textContent = q.anecdote ? q.anecdote : (idx === q.correct ? "C'est la bonne réponse !" : "C'était la mauvaise réponse.");
      setTimeout(() => {
        document.getElementById('anecdoteOverlay').classList.remove('translate-y-[120%]');
        document.getElementById('anecdoteOverlay').classList.add('translate-y-0');
      }, 500);
    }

    function nextQuestion() {
      currentQuestion++;
      renderQuestion();
    }

    function updateScore() {
      document.getElementById('scoreDisplay').textContent = `${score} / ${quizQuestions.length}`;
    }

    // Audio support removed — questions are text-only now

    function startTimer() {
      timeLeft = 15;
      document.getElementById('timerBar').style.width = '100%';

      clearInterval(timerInterval);
      timerInterval = setInterval(() => {
        timeLeft--;
        document.getElementById('timerBar').style.width = ((timeLeft / 15) * 100) + '%';
        if (timeLeft <= 0) {
          clearInterval(timerInterval);
          // Time up — treat as wrong
          const allBtns = document.querySelectorAll('.answer-btn');
          allBtns.forEach(b => { b.classList.add('answered'); b.style.pointerEvents = 'none'; });
          const q = quizQuestions[currentQuestion];
          
          userAnswersObj[q.id] = null; // No answer recorded
          
          allBtns[q.correct].style.background = '#22c55e';
          allBtns[q.correct].style.color = 'white';
          document.getElementById('anecdoteText').textContent = 'TEMPS ÉCOULÉ ! ' + (q.anecdote || '');
          setTimeout(() => {
            document.getElementById('anecdoteOverlay').classList.remove('translate-y-[120%]');
            document.getElementById('anecdoteOverlay').classList.add('translate-y-0');
          }, 300);
        }
      }, 1000);
    }

    /**
     * endQuiz()
     * Finalise le quiz et envoie les réponses au serveur pour validation.
     *
     * Architecture de sécurité : le score affiché est celui calculé par le serveur
     * (pas le compteur local `score`). Cela empêche toute manipulation côté client.
     * L'API save_stats.php re-vérifie chaque réponse contre la BDD.
     *
     * Le scroll est déverrouillé pour permettre le défilement libre
     * dans la liste récapitulative des 10 questions.
     */
    async function endQuiz() {
      clearInterval(timerInterval);

      // Masquer la vue de jeu
      document.getElementById('quizCore').classList.add('hidden');
      document.getElementById('quizCore').classList.remove('flex');
      document.getElementById('anecdoteOverlay').classList.remove('translate-y-0');
      document.getElementById('anecdoteOverlay').classList.add('translate-y-[120%]');

      // Déverrouiller le scroll pour parcourir les résultats
      document.body.classList.remove('scroll-locked');
      window.scrollTo(0, 0);

      // Afficher l'écran de fin avec état de chargement
      const endScreen = document.getElementById('endScreen');
      endScreen.classList.remove('hidden');
      document.getElementById('finalScore').textContent = `...`;
      document.getElementById('finalMessage').textContent = "Calcul des résultats...";

      // Envoi des réponses au serveur (POST JSON)
      // Le serveur recalcule le score et renvoie le récap détaillé
      try {
        const response = await fetch('api/save_stats.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                theme: selectedTheme,
                answers: userAnswersObj
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Score officiel (calculé par le serveur, pas le client)
            document.getElementById('finalScore').textContent = `${data.score} / ${data.total}`;
            
            // Messages de fin adaptés au score (vocabulaire ch'ti)
            let msg = '';
            if (data.score === data.total) msg = "T'es un vrai ducasseux, bravo !";
            else if (data.score >= (data.total * 0.7)) msg = "Bien joué, t'es un vrai d'ichi !";
            else if (data.score >= (data.total * 0.4)) msg = "C'est pas pire, mais tu peux faire mieux !";
            else msg = "T'es un vrai babache, t'as core des croûtes à mingir !";
            document.getElementById('finalMessage').innerHTML = msg;

            // Recap grid (compact 5x2)
            const recapContainer = document.getElementById('recapContainer');
            const recapGrid = document.getElementById('recapGrid');
            recapGrid.innerHTML = '';
            
            data.recap.forEach((item, index) => {
                const isCorrect = item.isCorrect;
                const noAnswer = !item.userChoice;
                
                let bgClass = isCorrect ? 'bg-[#dcfce7] border-[#22c55e]' : 'bg-[#fef2f2] border-[#ef4444]';
                
                // Truncate question
                let shortQ = item.question;
                if (shortQ.length > 55) shortQ = shortQ.substring(0, 52) + '...';
                
                let answerHtml = '';
                if (isCorrect) {
                  answerHtml = `<p style="color:#15803d; font-weight:bold; font-size:0.85rem; margin-top:2px;">${item.correctText}</p>`;
                } else {
                  answerHtml = `
                    <p style="color:#b91c1c; font-weight:bold; font-size:0.85rem; margin-top:2px; text-decoration:line-through; opacity:0.8;">${noAnswer ? 'Temps écoulé' : item.userText}</p>
                    <p style="color:#15803d; font-weight:bold; font-size:0.85rem;">=> ${item.correctText}</p>
                  `;
                }

                recapGrid.innerHTML += `
                  <div style="border:3px solid; border-radius:12px; box-shadow:3px 3px 0 0 #000; padding:12px;" class="${bgClass}">
                    <p style="font-weight:700; font-size:0.95rem; color:#1e293b; line-height:1.2; display:flex; gap:6px; align-items:start;">
                      <span style="flex-shrink:0;">${index + 1}.</span>
                      <span>${shortQ}</span>
                    </p>
                    <div style="margin-left:20px; margin-top:2px;">
                      ${answerHtml}
                    </div>
                  </div>
                `;
            });
            
            recapContainer.classList.remove('hidden');
            
        } else {
            document.getElementById('finalMessage').textContent = "Erreur : " + data.error;
        }

      } catch (e) {
          console.error("Erreur API de sauvegarde des stats", e);
          document.getElementById('finalMessage').textContent = "Erreur de connexion lors du calcul.";
      }
    }
  </script>
</body>
</html>
