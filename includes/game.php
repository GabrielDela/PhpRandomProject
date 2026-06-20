<?php

declare(strict_types=1);

const PRODUCERS = [
    'cursor' => [
        'name' => 'Curseur',
        'emoji' => '👆',
        'description' => 'Clique doucement, mais ne se fatigue jamais.',
        'base_cost' => 15.0,
        'cps' => 0.1,
    ],
    'grandma' => [
        'name' => 'Mamie',
        'emoji' => '👵',
        'description' => 'Une recette secrète et beaucoup de patience.',
        'base_cost' => 100.0,
        'cps' => 1.0,
    ],
    'bakery' => [
        'name' => 'Boulangerie',
        'emoji' => '🥐',
        'description' => 'Des fournées chaudes, jour et nuit.',
        'base_cost' => 1100.0,
        'cps' => 8.0,
    ],
    'factory' => [
        'name' => 'Usine à cookies',
        'emoji' => '🏭',
        'description' => 'La gourmandise passe à l’échelle industrielle.',
        'base_cost' => 12000.0,
        'cps' => 47.0,
    ],
];

function newGame(): array
{
    return [
        'cookies' => 0.0,
        'lifetime' => 0.0,
        'click_level' => 0,
        'producers' => array_fill_keys(array_keys(PRODUCERS), 0),
        'last_update' => microtime(true),
    ];
}

function initialiseGame(): void
{
    if (!isset($_SESSION['game']) || !is_array($_SESSION['game'])) {
        $_SESSION['game'] = newGame();
    }

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    applyPassiveProduction($_SESSION['game']);
}

function cookiesPerSecond(array $game): float
{
    $total = 0.0;

    foreach (PRODUCERS as $id => $producer) {
        $owned = max(0, (int) ($game['producers'][$id] ?? 0));
        $total += $owned * $producer['cps'];
    }

    return $total;
}

function clickPower(array $game): float
{
    return 1.0 + ((int) ($game['click_level'] ?? 0) * 2.0);
}

function producerCost(string $id, int $owned): float
{
    return ceil(PRODUCERS[$id]['base_cost'] * (1.15 ** $owned));
}

function clickUpgradeCost(int $level): float
{
    return ceil(50 * (3 ** $level));
}

function applyPassiveProduction(array &$game): void
{
    $now = microtime(true);
    $lastUpdate = (float) ($game['last_update'] ?? $now);
    $elapsed = max(0.0, min($now - $lastUpdate, 60 * 60 * 8));
    $produced = $elapsed * cookiesPerSecond($game);

    $game['cookies'] = max(0.0, (float) ($game['cookies'] ?? 0)) + $produced;
    $game['lifetime'] = max(0.0, (float) ($game['lifetime'] ?? 0)) + $produced;
    $game['last_update'] = $now;
}

function publicGameState(array $game): array
{
    $producers = [];

    foreach (PRODUCERS as $id => $producer) {
        $owned = max(0, (int) ($game['producers'][$id] ?? 0));
        $producers[$id] = [
            'id' => $id,
            'name' => $producer['name'],
            'emoji' => $producer['emoji'],
            'description' => $producer['description'],
            'owned' => $owned,
            'cost' => producerCost($id, $owned),
            'cps' => $producer['cps'],
        ];
    }

    $level = max(0, (int) ($game['click_level'] ?? 0));

    return [
        'cookies' => round((float) $game['cookies'], 3),
        'lifetime' => round((float) $game['lifetime'], 3),
        'cps' => round(cookiesPerSecond($game), 3),
        'clickPower' => clickPower($game),
        'clickUpgrade' => [
            'level' => $level,
            'cost' => clickUpgradeCost($level),
        ],
        'producers' => $producers,
    ];
}
