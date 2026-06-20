<?php

declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/includes/game.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

initialiseGame();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);
$payload = is_array($payload) ? $payload : [];
$token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $payload['csrf'] ?? '');

if (!hash_equals((string) $_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Session expirée. Recharge la page.']);
    exit;
}

$action = (string) ($payload['action'] ?? 'sync');
$game = &$_SESSION['game'];
$message = null;

switch ($action) {
    case 'click':
        $count = max(1, min(100, (int) ($payload['count'] ?? 1)));
        $earned = $count * clickPower($game);
        $game['cookies'] += $earned;
        $game['lifetime'] += $earned;
        break;

    case 'buy_producer':
        $id = (string) ($payload['id'] ?? '');

        if (!isset(PRODUCERS[$id])) {
            http_response_code(422);
            echo json_encode(['error' => 'Amélioration inconnue.']);
            exit;
        }

        $owned = (int) $game['producers'][$id];
        $cost = producerCost($id, $owned);

        if ($game['cookies'] < $cost) {
            http_response_code(409);
            echo json_encode(['error' => 'Pas encore assez de cookies.', 'state' => publicGameState($game)]);
            exit;
        }

        $game['cookies'] -= $cost;
        $game['producers'][$id]++;
        $message = PRODUCERS[$id]['name'] . ' acheté !';
        break;

    case 'buy_click':
        $level = (int) $game['click_level'];
        $cost = clickUpgradeCost($level);

        if ($game['cookies'] < $cost) {
            http_response_code(409);
            echo json_encode(['error' => 'Pas encore assez de cookies.', 'state' => publicGameState($game)]);
            exit;
        }

        $game['cookies'] -= $cost;
        $game['click_level']++;
        $message = 'Clic renforcé !';
        break;

    case 'reset':
        $_SESSION['game'] = newGame();
        $game = &$_SESSION['game'];
        $message = 'Nouvelle fournée lancée.';
        break;

    case 'sync':
        break;

    default:
        http_response_code(422);
        echo json_encode(['error' => 'Action inconnue.']);
        exit;
}

echo json_encode([
    'state' => publicGameState($game),
    'message' => $message,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
