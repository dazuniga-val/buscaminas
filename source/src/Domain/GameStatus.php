<?php

declare(strict_types=1);

namespace Buscaminas\Domain;

enum GameStatus: string
{
    /** Tablero nuevo: todavía no hay minas ni corre el tiempo (esperando el primer clic). */
    case Ready = 'ready';
    case Playing = 'playing';
    case Won = 'won';
    case Lost = 'lost';

    public function isFinished(): bool
    {
        return $this === self::Won || $this === self::Lost;
    }
}
