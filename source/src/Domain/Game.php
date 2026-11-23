<?php

declare(strict_types=1);

namespace Buscaminas\Domain;

use Random\Randomizer;

/** Una partida de Buscaminas: tablero, estado y tiempo de juego. */
final class Game
{
    private Board $board;
    private GameStatus $status = GameStatus::Ready;
    private ?float $startedAt = null;
    private ?float $endedAt = null;
    private ?Position $explodedAt = null;

    public function __construct(
        public readonly Difficulty $difficulty,
    ) {
        $this->board = new Board($difficulty->size(), $difficulty->size());
    }

    public function board(): Board
    {
        return $this->board;
    }

    public function status(): GameStatus
    {
        return $this->status;
    }

    public function explodedAt(): ?Position
    {
        return $this->explodedAt;
    }

    /**
     * Descubre una casilla. En el primer clic se colocan las minas (evitando esa zona)
     * y empieza a correr el tiempo.
     */
    public function reveal(Position $position, Randomizer $randomizer, float $now): void
    {
        if ($this->status->isFinished()) {
            return;
        }

        $cell = $this->board->cellAt($position);
        if (!$cell->isHidden()) {
            return;
        }

        if ($this->status === GameStatus::Ready) {
            $this->board->placeRandomMines($this->difficulty->mines(), $position, $randomizer);
            $this->status = GameStatus::Playing;
            $this->startedAt = $now;
        }

        $this->board->reveal($position);

        if ($cell->hasMine()) {
            $this->status = GameStatus::Lost;
            $this->explodedAt = $position;
            $this->endedAt = $now;

            return;
        }

        if ($this->board->allSafeCellsRevealed()) {
            $this->status = GameStatus::Won;
            $this->endedAt = $now;
            $this->board->flagAllMines();
        }
    }

    public function toggleFlag(Position $position): void
    {
        if ($this->status->isFinished()) {
            return;
        }

        $this->board->toggleFlag($position);
    }

    public function minesRemaining(): int
    {
        return $this->difficulty->mines() - $this->board->flaggedCount();
    }

    public function isTimerRunning(): bool
    {
        return $this->status === GameStatus::Playing;
    }

    /** Segundos transcurridos desde el primer clic (se congela al terminar la partida). */
    public function elapsedSeconds(float $now): int
    {
        if ($this->startedAt === null) {
            return 0;
        }

        $end = $this->endedAt ?? $now;

        return max(0, (int) floor($end - $this->startedAt));
    }
}
