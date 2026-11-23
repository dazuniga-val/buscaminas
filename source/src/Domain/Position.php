<?php

declare(strict_types=1);

namespace Buscaminas\Domain;

/** Coordenada de una casilla dentro del tablero. */
final readonly class Position
{
    public function __construct(
        public int $row,
        public int $col,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->row === $other->row && $this->col === $other->col;
    }
}
