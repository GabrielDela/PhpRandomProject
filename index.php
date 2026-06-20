<?php

declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/includes/game.php';

initialiseGame();
$initialState = publicGameState($_SESSION['game']);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d120e">
    <title>La Fabrique à Cookies</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,700;9..144,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="grain" aria-hidden="true"></div>

    <header class="topbar">
        <a class="brand" href="./" aria-label="La Fabrique à Cookies, accueil">
            <span class="brand-mark">C</span>
            <span>La Fabrique</span>
        </a>
        <button class="reset-button" id="resetButton" type="button">Recommencer</button>
    </header>

    <main class="game-shell">
        <section class="hero-panel" aria-labelledby="gameTitle">
            <div class="eyebrow">Fait maison, clic après clic</div>
            <h1 id="gameTitle">Des cookies.<br><em>Beaucoup</em> de cookies.</h1>
            <p class="intro">Clique sur le cookie, améliore ta production et transforme une petite fournée en empire croustillant.</p>

            <div class="score-card" aria-live="polite">
                <span class="score-label">Dans le bocal</span>
                <strong id="cookieCount">0</strong>
                <span class="score-unit">cookies</span>
                <div class="production-line">
                    <span><i class="status-dot"></i> <b id="cpsCount">0</b> par seconde</span>
                    <span><b id="clickPower">1</b> par clic</span>
                </div>
            </div>

            <div class="cookie-stage" id="cookieStage">
                <div class="orbit orbit-one" aria-hidden="true"></div>
                <div class="orbit orbit-two" aria-hidden="true"></div>
                <button class="cookie-button" id="cookieButton" type="button" aria-label="Fabriquer un cookie">
                    <span class="cookie" aria-hidden="true">
                        <i class="chip chip-1"></i><i class="chip chip-2"></i><i class="chip chip-3"></i>
                        <i class="chip chip-4"></i><i class="chip chip-5"></i><i class="chip chip-6"></i>
                        <i class="crumb crumb-1"></i><i class="crumb crumb-2"></i>
                    </span>
                </button>
                <p class="click-hint">Clique-moi</p>
            </div>
        </section>

        <aside class="shop-panel" aria-labelledby="shopTitle">
            <div class="shop-heading">
                <div>
                    <div class="eyebrow">La boutique</div>
                    <h2 id="shopTitle">Accélère la recette</h2>
                </div>
                <span class="shop-badge">Production</span>
            </div>

            <button class="upgrade-card featured" id="clickUpgrade" type="button">
                <span class="upgrade-icon">⚡</span>
                <span class="upgrade-copy">
                    <span class="upgrade-name">Clic musclé <small id="clickLevel">Niv. 0</small></span>
                    <span class="upgrade-description">Ajoute 2 cookies à chaque clic.</span>
                    <span class="upgrade-price"><span id="clickCost">50</span> cookies</span>
                </span>
                <span class="upgrade-action">+</span>
            </button>

            <div class="divider"><span>Producteurs automatiques</span></div>
            <div class="upgrade-list" id="producerList"></div>

            <footer class="shop-footer">
                <span>Production totale</span>
                <strong><span id="lifetimeCount">0</span> cookies</strong>
            </footer>
        </aside>
    </main>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <script>
        window.GAME_CONFIG = <?= json_encode([
            'state' => $initialState,
            'csrf' => $_SESSION['csrf_token'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="assets/game.js" defer></script>
</body>
</html>
