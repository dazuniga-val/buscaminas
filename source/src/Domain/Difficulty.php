<?php

declare(strict_types=1);

namespace Buscaminas\Domain;

/**
 * Niveles de dificultad.
 *
 * El tamaño del tablero crece con la dificultad y la cantidad de minas
 * mantiene la misma densidad que el nivel fácil (10 minas en 64 casillas).
 */
enum Difficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    /** Densidad de referencia: 10 minas en un tablero de 8x8. */
    private const int REFERENCE_MINES = 10;
    private const int REFERENCE_CELLS = 64;

    public function size(): int
    {
        return match ($this) {
            self::Easy => 8,
            self::Medium => 12,
            self::Hard => 16,
        };
    }

    /**
     * Minas proporcionales a la cantidad de casillas:
     * 8x8 = 10, 12x12 = 23 (22.5 redondeado), 16x16 = 40.
     */
    public function mines(): int
    {
        $cells = $this->size() ** 2;

        return (int) round($cells * self::REFERENCE_MINES / self::REFERENCE_CELLS);
    }

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Fácil',
            self::Medium => 'Intermedio',
            self::Hard => 'Difícil',
        };
    }
}
