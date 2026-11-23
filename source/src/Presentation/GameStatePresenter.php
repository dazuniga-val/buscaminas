<?php

declare(strict_types=1);

namespace Buscaminas\Presentation;

use Buscaminas\Domain\Cell;
use Buscaminas\Domain\Game;
use Buscaminas\Domain\GameStatus;
use Buscaminas\Domain\Position;

/**
 * Convierte una partida en datos para el navegador.
 * Mientras se juega, las casillas ocultas no revelan si tienen mina.
 */
final class GameStatePresenter
{
    /** @return array<string, mixed> */
    public function present(Game $game, float $now): array
    {
        $board = $game->board();
        $status = $game->status();

        $cells = [];
        foreach ($board->rowsOfCells() as $r => $row) {
            foreach ($row as $c => $cell) {
                $cells[$r][$c] = $this->presentCell($cell, new Position($r, $c), $game, $status);
            }
        }

        return [
            'difficulty' => $game->difficulty->value,
            'difficultyLabel' => $game->difficulty->label(),
            'rows' => $board->rows,
            'cols' => $board->cols,
            'mines' => $game->difficulty->mines(),
            'minesRemaining' => $game->minesRemaining(),
            'status' => $status->value,
            'elapsed' => $game->elapsedSeconds($now),
            'timerRunning' => $game->isTimerRunning(),
            'cells' => $cells,
        ];
    }

    /** @return array{s: string, n?: int} */
    private function presentCell(Cell $cell, Position $position, Game $game, GameStatus $status): array
    {
        if ($status === GameStatus::Lost) {
            $exploded = $game->explodedAt();
            if ($exploded !== null && $exploded->equals($position)) {
                return ['s' => 'exploded'];
            }
            if ($cell->hasMine() && !$cell->isFlagged()) {
                return ['s' => 'mine'];
            }
            if ($cell->isFlagged() && !$cell->hasMine()) {
                return ['s' => 'wrong-flag'];
            }
        }

        if ($cell->isFlagged()) {
            return ['s' => 'flag'];
        }

        if ($cell->isRevealed()) {
            return ['s' => 'open', 'n' => $cell->adjacentMines()];
        }

        return ['s' => 'hidden'];
    }
}
