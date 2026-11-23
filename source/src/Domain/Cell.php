<?php

declare(strict_types=1);

namespace Buscaminas\Domain;

final class Cell
{
    private bool $mine = false;
    private int $adjacentMines = 0;
    private CellState $state = CellState::Hidden;

    public function hasMine(): bool
    {
        return $this->mine;
    }

    public function placeMine(): void
    {
        $this->mine = true;
    }

    public function adjacentMines(): int
    {
        return $this->adjacentMines;
    }

    public function setAdjacentMines(int $count): void
    {
        $this->adjacentMines = $count;
    }

    public function state(): CellState
    {
        return $this->state;
    }

    public function isHidden(): bool
    {
        return $this->state === CellState::Hidden;
    }

    public function isRevealed(): bool
    {
        return $this->state === CellState::Revealed;
    }

    public function isFlagged(): bool
    {
        return $this->state === CellState::Flagged;
    }

    public function reveal(): void
    {
        $this->state = CellState::Revealed;
    }

    public function toggleFlag(): void
    {
        $this->state = match ($this->state) {
            CellState::Hidden => CellState::Flagged,
            CellState::Flagged => CellState::Hidden,
            CellState::Revealed => CellState::Revealed,
        };
    }

    public function flag(): void
    {
        if (!$this->isRevealed()) {
            $this->state = CellState::Flagged;
        }
    }
}
