<?php

declare(strict_types=1);

namespace Buscaminas\Infrastructure;

use Buscaminas\Application\GameRepository;
use Buscaminas\Domain\Game;

/** Guarda la partida en la sesión de PHP del jugador. */
final class SessionGameRepository implements GameRepository
{
    private const string KEY = 'buscaminas.game';

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function load(): ?Game
    {
        $serialized = $_SESSION[self::KEY] ?? null;
        if (!is_string($serialized)) {
            return null;
        }

        $game = unserialize($serialized, ['allowed_classes' => true]);

        return $game instanceof Game ? $game : null;
    }

    public function save(Game $game): void
    {
        $_SESSION[self::KEY] = serialize($game);
    }
}
