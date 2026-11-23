<?php

declare(strict_types=1);

use Buscaminas\Application\GameService;
use Buscaminas\Infrastructure\SessionGameRepository;
use Buscaminas\Presentation\ApiController;

require __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $decoded = json_decode((string) file_get_contents('php://input'), true, 4, JSON_THROW_ON_ERROR);
        $input = is_array($decoded) ? $decoded : [];
        $action = (string) ($input['action'] ?? '');
    } else {
        $action = 'state';
    }

    $controller = new ApiController(new GameService(new SessionGameRepository()));

    echo json_encode($controller->handle($action, $input), JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException | JsonException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor.']);
}
