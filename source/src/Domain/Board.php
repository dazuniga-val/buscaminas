<?php

declare(strict_types=1);

namespace Buscaminas\Domain;

use InvalidArgumentException;
use Random\Randomizer;
use SplQueue;

/** Cuadrícula de casillas: colocación de minas, conteo de vecinas y desborde. */
final class Board
{
    /** Desplazamientos hacia las 8 casillas vecinas. */
    private const array DIRECTIONS = [
        [-1, -1], [-1, 0], [-1, 1],
        [0, -1],           [0, 1],
        [1, -1],  [1, 0],  [1, 1],
    ];

    /** @var list<list<Cell>> */
    private array $cells = [];
    private int $mineCount = 0;

    public function __construct(
        public readonly int $rows,
        public readonly int $cols,
    ) {
        if ($rows < 1 || $cols < 1) {
            throw new InvalidArgumentException('El tablero debe tener al menos una fila y una columna.');
        }

        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $this->cells[$r][$c] = new Cell();
            }
        }
    }

    public function contains(Position $position): bool
    {
        return $position->row >= 0 && $position->row < $this->rows
            && $position->col >= 0 && $position->col < $this->cols;
    }

    public function cellAt(Position $position): Cell
    {
        if (!$this->contains($position)) {
            throw new InvalidArgumentException("La casilla ({$position->row}, {$position->col}) está fuera del tablero.");
        }

        return $this->cells[$position->row][$position->col];
    }

    /** @return list<Position> */
    public function neighborsOf(Position $position): array
    {
        $neighbors = [];
        foreach (self::DIRECTIONS as [$dr, $dc]) {
            $neighbor = new Position($position->row + $dr, $position->col + $dc);
            if ($this->contains($neighbor)) {
                $neighbors[] = $neighbor;
            }
        }

        return $neighbors;
    }

    /**
     * Coloca las minas al azar evitando la casilla del primer clic y sus vecinas,
     * para que la primera jugada siempre abra una zona.
     */
    public function placeRandomMines(int $count, Position $safe, Randomizer $randomizer): void
    {
        $safeZone = [$safe, ...$this->neighborsOf($safe)];
        $candidates = $this->positionsExcluding($safeZone);

        // En tableros muy pequeños puede no haber espacio: se protege solo la casilla del clic.
        if (count($candidates) < $count) {
            $candidates = $this->positionsExcluding([$safe]);
        }

        if (count($candidates) < $count) {
            throw new InvalidArgumentException('Hay más minas que casillas disponibles.');
        }

        $this->placeMinesAt(array_slice($randomizer->shuffleArray($candidates), 0, $count));
    }

    /**
     * Coloca minas en posiciones concretas y calcula los números de cada casilla.
     *
     * @param list<Position> $positions
     */
    public function placeMinesAt(array $positions): void
    {
        foreach ($positions as $position) {
            $cell = $this->cellAt($position);
            if (!$cell->hasMine()) {
                $cell->placeMine();
                $this->mineCount++;
            }
        }

        $this->calculateAdjacentMines();
    }

    public function mineCount(): int
    {
        return $this->mineCount;
    }

    /**
     * Descubre una casilla aplicando el algoritmo de desborde (flood fill).
     *
     * Si la casilla no tiene minas vecinas (número 0) se descubren sus 8 vecinas,
     * y el proceso se repite con cada vecina que también sea 0. La propagación
     * se detiene en las casillas con número (el "borde" de la zona), que se
     * descubren pero no siguen expandiendo. Nunca descubre minas ni casillas con
     * bandera. Se implementa como búsqueda en anchura (BFS) con una cola, sin
     * recursión, para no agotar la pila en tableros grandes.
     *
     * @return int Cantidad de casillas descubiertas.
     */
    public function reveal(Position $start): int
    {
        $cell = $this->cellAt($start);
        if (!$cell->isHidden()) {
            return 0;
        }

        $cell->reveal();
        $revealed = 1;

        if ($cell->hasMine() || $cell->adjacentMines() > 0) {
            return $revealed;
        }

        /** @var SplQueue<Position> $queue */
        $queue = new SplQueue();
        $queue->enqueue($start);

        while (!$queue->isEmpty()) {
            $current = $queue->dequeue();

            foreach ($this->neighborsOf($current) as $neighborPosition) {
                $neighbor = $this->cellAt($neighborPosition);

                // Solo se propagan casillas ocultas y sin mina (las banderas detienen el desborde).
                if (!$neighbor->isHidden() || $neighbor->hasMine()) {
                    continue;
                }

                $neighbor->reveal();
                $revealed++;

                if ($neighbor->adjacentMines() === 0) {
                    $queue->enqueue($neighborPosition);
                }
            }
        }

        return $revealed;
    }

    public function toggleFlag(Position $position): void
    {
        $this->cellAt($position)->toggleFlag();
    }

    public function revealedCount(): int
    {
        return $this->count(static fn (Cell $cell): bool => $cell->isRevealed());
    }

    public function flaggedCount(): int
    {
        return $this->count(static fn (Cell $cell): bool => $cell->isFlagged());
    }

    public function totalCells(): int
    {
        return $this->rows * $this->cols;
    }

    public function allSafeCellsRevealed(): bool
    {
        return $this->mineCount > 0 && $this->revealedCount() === $this->totalCells() - $this->mineCount;
    }

    public function flagAllMines(): void
    {
        foreach ($this->cells() as $cell) {
            if ($cell->hasMine()) {
                $cell->flag();
            }
        }
    }

    /** @return iterable<Cell> */
    public function cells(): iterable
    {
        foreach ($this->cells as $row) {
            yield from $row;
        }
    }

    /** @return list<list<Cell>> */
    public function rowsOfCells(): array
    {
        return $this->cells;
    }

    private function calculateAdjacentMines(): void
    {
        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                $count = 0;
                foreach ($this->neighborsOf(new Position($r, $c)) as $neighbor) {
                    if ($this->cellAt($neighbor)->hasMine()) {
                        $count++;
                    }
                }
                $this->cells[$r][$c]->setAdjacentMines($count);
            }
        }
    }

    /**
     * @param list<Position> $excluded
     * @return list<Position>
     */
    private function positionsExcluding(array $excluded): array
    {
        $positions = [];
        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                $position = new Position($r, $c);
                foreach ($excluded as $skip) {
                    if ($skip->equals($position)) {
                        continue 2;
                    }
                }
                $positions[] = $position;
            }
        }

        return $positions;
    }

    /** @param callable(Cell): bool $predicate */
    private function count(callable $predicate): int
    {
        $total = 0;
        foreach ($this->cells() as $cell) {
            if ($predicate($cell)) {
                $total++;
            }
        }

        return $total;
    }
}
