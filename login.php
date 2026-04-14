<?php
/**
 * login.php — Page de connexion et d'inscription.
 *
 * Double formulaire (connexion / inscription) dans un même écran,
 * avec un système d'onglets JS (switchTab) pour basculer entre les deux.
 *
 * Si l'utilisateur est déjà connecté (session active), redirection
 * immédiate vers index.php (pas de re-login).
 *
 * Les formulaires envoient les données en POST vers :
 * - api/connexion.php (login)
 * - api/inscription.php (register)
 *
 * Alertes gérées via query params (?error=1 ou ?success=1).
 */
session_start();

// Redirection si déjà authentifié
if (isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>BaraQuiz! - Connexion / Inscription</title>
  <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Outfit:wght@400;600;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!--
    Config Tailwind : palette réduite par rapport à index.php,
    seules les couleurs utilisées dans les boutons et inputs sont déclarées.
  -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { 'frites': '#FFC107', 'carnival': '#EF4444', 'sky': '#0EA5E9' },
          fontFamily: { display: ['"Outfit"', 'sans-serif'], body: ['"Inter"', 'sans-serif'] },
        }
      }
    };
  </script>

  <style>
    /* Fond identique à l'application principale pour cohérence visuelle */
    body { background: linear-gradient(135deg, #0c1445 0%, #1a237e 30%, #1565c0 70%, #0EA5E9 100%); }

    /* Carte principale : néo-brutalisme avec contour épais et ombre portée */
    .card-premium { background: white; border: 4px solid black; box-shadow: 8px 8px 0px 0px rgba(0,0,0,1); border-radius: 2rem; }

    /* Boutons et inputs : même design system que index.php */
    .btn-premium { border: 3px solid black; box-shadow: 6px 6px 0px 0px rgba(0,0,0,1); transition: all 0.15s ease-out; }
    .btn-premium:hover { box-shadow: 2px 2px 0px 0px rgba(0,0,0,1); transform: translate(4px, 4px); }
    .btn-premium:active { box-shadow: 0px 0px 0px 0px rgba(0,0,0,1); transform: translate(6px, 6px); }

    /* Inputs : ombre portée + focus bleu (sky) pour feedback visuel */
    .input-premium { border: 3px solid black; box-shadow: 4px 4px 0px 0px rgba(0,0,0,1); transition: all 0.2s ease; }
    .input-premium:focus { box-shadow: 6px 6px 0px 0px #0EA5E9; outline: none; }
  </style>
</head>

<body class="min-h-screen font-body flex items-center justify-center p-4 relative overflow-hidden text-black">

  <div class="w-full max-w-[1000px] grid md:grid-cols-2 gap-8 items-center relative z-10">
    
    <!-- Bloc de présentation (visible à gauche sur desktop, au-dessus sur mobile) -->
    <div class="flex flex-col justify-center items-center md:items-start text-center md:text-left text-white">
      <h1 class="font-display text-5xl md:text-7xl mb-6 text-white drop-shadow-lg">BaraQuiz!</h1>
      <p class="text-xl md:text-2xl font-bold leading-relaxed max-w-sm text-white/80">
        Préparez-vous à devenir incollable sur notre belle région.
      </p>
    </div>

    <!-- Carte formulaire (connexion + inscription en onglets) -->
    <div class="card-premium p-6 md:p-8 flex flex-col w-full max-h-[90vh] overflow-hidden relative">
      
      <!-- Alertes conditionnelles (query params) -->
      <?php if(isset($_GET['error'])): ?>
      <div class="bg-red-50 text-red-600 border border-red-200 font-semibold p-4 mb-6 rounded-2xl shadow-sm animate-pulse">
        Identifiants incorrects. Veuillez réessayer.
      </div>
      <?php endif; ?>

      <?php if(isset($_GET['success'])): ?>
      <div class="bg-green-50 text-green-600 border border-green-200 font-semibold p-4 mb-6 rounded-2xl shadow-sm">
        Inscription réussie ! Connectez-vous maintenant.
      </div>
      <?php endif; ?>

      <!--
        Système d'onglets néo-brutaliste :
        Le toggle est purement JS (switchTab). L'onglet actif reçoit un fond coloré
        + bordure + ombre (style bouton "pressé"), l'inactif est transparent.
      -->
      <div class="flex bg-slate-100 p-2 border-3 border-black shadow-[4px_4px_0_0_#000] rounded-full mb-8 relative">
        <button id="tabLogin" onclick="switchTab('login')" class="flex-1 py-3 px-4 font-bold text-sm md:text-base rounded-full transition-all duration-200 z-10 text-white bg-sky border-2 border-black shadow-[3px_3px_0_0_#000]">Se connecter</button>
        <button id="tabRegister" onclick="switchTab('register')" class="flex-1 py-3 px-4 font-bold text-sm md:text-base rounded-full transition-all duration-200 z-10 text-slate-600 hover:text-black border-2 border-transparent hover:bg-slate-200">Créer un compte</button>
      </div>

      <!-- Conteneur des formulaires -->
      <div class="flex-1 overflow-y-auto px-1 pb-2">

        <!-- Formulaire connexion -->
        <div id="formLogin" class="animate-fade-in block">
          <h2 class="font-display text-3xl mb-4 text-black">Ravi de vous revoir !</h2>
          <form action="api/connexion.php" method="POST" class="flex flex-col gap-4">
            <div>
              <label class="font-bold text-sm text-slate-800 mb-2 block">Adresse Email</label>
              <input type="email" name="email" required placeholder="biloute@exemple.com" class="w-full input-premium bg-white rounded-xl p-4 font-bold text-black placeholder-slate-400">
            </div>
            <div>
              <label class="font-bold text-sm text-slate-800 mb-2 block">Mot de passe</label>
              <input type="password" name="mdp" required placeholder="••••••••" class="w-full input-premium bg-white rounded-xl p-4 font-bold text-black placeholder-slate-400">
            </div>
            <button type="submit" class="btn-premium mt-4 w-full bg-sky text-white font-display text-2xl px-8 py-4 rounded-full cursor-pointer">
              Connexion
            </button>
          </form>
        </div>

        <!-- Formulaire inscription -->
        <div id="formRegister" class="animate-fade-in hidden">
          <h2 class="font-display text-3xl mb-4 text-black">Rejoignez la famille !</h2>
          <form action="api/inscription.php" method="POST" class="flex flex-col gap-4">
            <div class="grid grid-cols-2 gap-5">
              <div>
                <label class="font-bold text-sm text-slate-800 mb-2 block">Prénom</label>
                <input type="text" name="prenom" required class="w-full input-premium bg-white rounded-xl p-3 font-bold text-black">
              </div>
              <div>
                <label class="font-bold text-sm text-slate-800 mb-2 block">Nom</label>
                <input type="text" name="nom" required class="w-full input-premium bg-white rounded-xl p-3 font-bold text-black">
              </div>
            </div>
            <div>
              <label class="font-bold text-sm text-slate-800 mb-2 block">Email</label>
              <input type="email" name="email" required class="w-full input-premium bg-white rounded-xl p-3 font-bold text-black">
            </div>
            <div>
              <label class="font-bold text-sm text-slate-800 mb-2 block">Mot de passe</label>
              <input type="password" name="mdp" required class="w-full input-premium bg-white rounded-xl p-3 font-bold text-black">
            </div>
            <button type="submit" class="btn-premium mt-4 w-full bg-carnival text-white font-display text-2xl px-8 py-4 rounded-full cursor-pointer">
              S'inscrire
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* Animation d'entrée des formulaires lors du switch d'onglet */
    .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  </style>

  <script>
    /**
     * switchTab(tab)
     * Bascule entre les formulaires connexion/inscription.
     * Gère l'état visuel des onglets (actif = couleur + ombre, inactif = transparent).
     * Le formulaire courant est masqué/affiché via la classe .hidden de Tailwind.
     */
    function switchTab(tab) {
      const formLogin = document.getElementById('formLogin');
      const formRegister = document.getElementById('formRegister');
      const btnLogin = document.getElementById('tabLogin');
      const btnRegister = document.getElementById('tabRegister');

      if (tab === 'login') {
        formLogin.classList.remove('hidden');
        formRegister.classList.add('hidden');
        btnLogin.className = "flex-1 py-3 px-4 font-bold text-sm md:text-base rounded-full transition-all duration-200 z-10 text-white bg-sky border-2 border-black shadow-[3px_3px_0_0_#000]";
        btnRegister.className = "flex-1 py-3 px-4 font-bold text-sm md:text-base rounded-full transition-all duration-200 z-10 text-slate-600 hover:text-black border-2 border-transparent hover:bg-slate-200";
      } else {
        formLogin.classList.add('hidden');
        formRegister.classList.remove('hidden');
        btnRegister.className = "flex-1 py-3 px-4 font-bold text-sm md:text-base rounded-full transition-all duration-200 z-10 text-white bg-carnival border-2 border-black shadow-[3px_3px_0_0_#000]";
        btnLogin.className = "flex-1 py-3 px-4 font-bold text-sm md:text-base rounded-full transition-all duration-200 z-10 text-slate-600 hover:text-black border-2 border-transparent hover:bg-slate-200";
      }
    }
  </script>
</body>
</html>
