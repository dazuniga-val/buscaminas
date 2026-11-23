<?php

declare(strict_types=1);

namespace Buscaminas\Presentation;

use Buscaminas\Application\GameService;
use Buscaminas\Domain\Difficulty;
use InvalidArgumentException;

/** Atiende las acciones que envía el navegador y responde con el estado de la partida. */
final class ApiController
{
    public function __construct(
        private readonly GameService $service,
        private readonly GameStatePresenter $presenter = new GameStatePresenter(),
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function handle(string $action, array $input): array
    {
        $game = match ($action) {
            'state' => $this->service->current(),
            'new' => $this->service->newGame($this->difficulty($input)),
            'reveal' => $this->service->reveal(...$this->coordinates($input)),
            'flag' => $this->service->toggleFlag(...$this->coordinates($input)),
            default => throw new InvalidArgumentException("Acción desconocida: {$action}"),
        };

        return $this->presenter->present($game, $this->service->now());
    }

    /** @param array<string, mixed> $input */
    private function difficulty(array $input): Difficulty
    {
        $value = $input['difficulty'] ?? null;
        $difficulty = is_string($value) ? Difficulty::tryFrom($value) : null;

        return $difficulty ?? throw new InvalidArgumentException('Dificultad no válida.');
    }

    /**
     * @param array<string, mixed> $input
     * @return array{int, int}
     */
    private function coordinates(array $input): array
    {
        $row = $input['row'] ?? null;
        $col = $input['col'] ?? null;

        if (!is_int($row) || !is_int($col)) {
            throw new InvalidArgumentException('Se esperaban las coordenadas "row" y "col" como enteros.');
        }

        return [$row, $col];
    }
}
