<?php
require_once 'config.php';
require_once 'balance.php';

header('Content-Type: application/json');

$balanceService = new Balance($mysqli);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'deposit':
            echo json_encode($balanceService->deposit($data['user_id'], $data['amount']));
            break;
        case 'withdraw':
            echo json_encode($balanceService->withdraw($data['user_id'], $data['amount']));
            break;
        case 'transfer':
            echo json_encode($balanceService->transfer($data['from_user_id'], $data['to_user_id'], $data['amount']));
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
            break;
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'GET')
{
    $userId = $_GET['user_id'] ?? null;
    if ($userId) {
        echo json_encode($balanceService->getBalance($userId));
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
    }
}

else
{
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
