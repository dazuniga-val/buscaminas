<?php

declare(strict_types=1);

namespace Buscaminas\Application;

use Buscaminas\Domain\Difficulty;
use Buscaminas\Domain\Game;
use Buscaminas\Domain\Position;
use Closure;
use Random\Randomizer;

/** Casos de uso del juego. Coordina el dominio con el almacenamiento. */
final class GameService
{
    /** @var Closure(): float */
    private Closure $clock;

    /** @param (callable(): float)|null $clock Reloj inyectable (útil en pruebas). */
    public function __construct(
        private readonly GameRepository $repository,
        private readonly Randomizer $randomizer = new Randomizer(),
        ?callable $clock = null,
    ) {
        $this->clock = $clock !== null
            ? Closure::fromCallable($clock)
            : static fn (): float => microtime(true);
    }

    public function current(): Game
    {
        return $this->repository->load() ?? $this->newGame(Difficulty::Easy);
    }

    public function newGame(Difficulty $difficulty): Game
    {
        $game = new Game($difficulty);
        $this->repository->save($game);

        return $game;
    }

    public function reveal(int $row, int $col): Game
    {
        $game = $this->current();
        $game->reveal(new Position($row, $col), $this->randomizer, $this->now());
        $this->repository->save($game);

        return $game;
    }

    public function toggleFlag(int $row, int $col): Game
    {
        $game = $this->current();
        $game->toggleFlag(new Position($row, $col));
        $this->repository->save($game);

        return $game;
    }

    public function now(): float
    {
        return ($this->clock)();
    }
}
