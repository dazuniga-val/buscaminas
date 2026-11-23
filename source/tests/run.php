<?php

declare(strict_types=1);

/*
 * Pruebas del dominio sin dependencias externas.
 * Ejecutar con:  php source/tests/run.php
 */

use Buscaminas\Domain\Board;
use Buscaminas\Domain\Difficulty;
use Buscaminas\Domain\Game;
use Buscaminas\Domain\GameStatus;
use Buscaminas\Domain\Position;
use Random\Engine\Mt19937;
use Random\Randomizer;

require __DIR__ . '/../src/bootstrap.php';

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ✔ {$name}\n";
    } else {
        $failed++;
        echo "  ✘ {$name}\n";
    }
}

/** Convierte un tablero en texto para comparar: '#' oculta, 'F' bandera, número si está descubierta. */
function snapshot(Board $board): string
{
    $lines = [];
    foreach ($board->rowsOfCells() as $row) {
        $line = '';
        foreach ($row as $cell) {
            $line .= match (true) {
                $cell->isFlagged() => 'F',
                $cell->isRevealed() => $cell->hasMine() ? '*' : (string) $cell->adjacentMines(),
                default => '#',
            };
        }
        $lines[] = $line;
    }

    return implode("\n", $lines);
}

echo "Dificultades\n";
check('Fácil: 8x8 con 10 minas', Difficulty::Easy->size() === 8 && Difficulty::Easy->mines() === 10);
check('Intermedio: 12x12 con 23 minas', Difficulty::Medium->size() === 12 && Difficulty::Medium->mines() === 23);
check('Difícil: 16x16 con 40 minas', Difficulty::Hard->size() === 16 && Difficulty::Hard->mines() === 40);

echo "Números de minas vecinas\n";
$board = new Board(3, 3);
$board->placeMinesAt([new Position(0, 0), new Position(2, 2)]);
check('El centro tiene 2 minas vecinas', $board->cellAt(new Position(1, 1))->adjacentMines() === 2);
check('La esquina (0,2) tiene 0 minas vecinas', $board->cellAt(new Position(0, 2))->adjacentMines() === 0);
check('La casilla (0,1) tiene 1 mina vecina', $board->cellAt(new Position(0, 1))->adjacentMines() === 1);

echo "Algoritmo de desborde\n";
$board = new Board(5, 5);
$board->placeMinesAt([new Position(0, 0)]);
$revealed = $board->reveal(new Position(4, 4));
check('Desde una casilla vacía se descubren las 24 casillas seguras', $revealed === 24);
check('La mina sigue oculta', $board->cellAt(new Position(0, 0))->isHidden());

$board = new Board(5, 5);
$board->placeMinesAt([new Position(2, 0), new Position(2, 1), new Position(2, 2), new Position(2, 3)]);
$board->reveal(new Position(0, 4));
$expected = "00000\n23321\n#####\n#####\n#####";
check('El desborde se detiene en el borde de números y no cruza la barrera de minas', snapshot($board) === $expected);

$board = new Board(3, 3);
$board->placeMinesAt([new Position(0, 0)]);
check('Una casilla con número se descubre sola, sin propagar', $board->reveal(new Position(1, 1)) === 1);

$board = new Board(5, 5);
$board->placeMinesAt([new Position(0, 0)]);
$board->toggleFlag(new Position(4, 2));
$board->reveal(new Position(4, 4));
check('Las banderas no se descubren durante el desborde', $board->cellAt(new Position(4, 2))->isFlagged());
check('El desborde rodea la bandera y descubre el resto', $board->revealedCount() === 23);

$board = new Board(40, 40);
$board->placeMinesAt([new Position(0, 0)]);
check('Tablero grande sin desbordar la pila (BFS)', $board->reveal(new Position(39, 39)) === 1599);

echo "Partida\n";
foreach (Difficulty::cases() as $difficulty) {
    for ($seed = 1; $seed <= 30; $seed++) {
        $game = new Game($difficulty);
        $first = new Position(intdiv($difficulty->size(), 2), 3);
        $game->reveal($first, new Randomizer(new Mt19937($seed)), 1000.0);
        $okFirst = $game->status() !== GameStatus::Lost
            && $game->board()->cellAt($first)->adjacentMines() === 0
            && $game->board()->mineCount() === $difficulty->mines();
        if (!$okFirst) {
            break;
        }
    }
    check("{$difficulty->label()}: el primer clic es seguro, abre zona y coloca {$difficulty->mines()} minas", $okFirst);
}

// Victoria: descubrir todas las casillas seguras.
$game = new Game(Difficulty::Easy);
$randomizer = new Randomizer(new Mt19937(7));
$game->reveal(new Position(0, 0), $randomizer, 100.0);
foreach ($game->board()->rowsOfCells() as $r => $row) {
    foreach ($row as $c => $cell) {
        if (!$cell->hasMine()) {
            $game->reveal(new Position($r, $c), $randomizer, 142.6);
        }
    }
}
check('Descubrir todas las casillas seguras gana la partida', $game->status() === GameStatus::Won);
check('El tiempo se congela al ganar (42 s)', $game->elapsedSeconds(999.0) === 42);
check('Al ganar se marcan todas las minas', $game->minesRemaining() === 0);

// Derrota: descubrir una mina.
$game = new Game(Difficulty::Easy);
$game->reveal(new Position(0, 0), $randomizer, 0.0);
foreach ($game->board()->rowsOfCells() as $r => $row) {
    foreach ($row as $c => $cell) {
        if ($cell->hasMine()) {
            $game->reveal(new Position($r, $c), $randomizer, 5.0);
            break 2;
        }
    }
}
check('Descubrir una mina pierde la partida', $game->status() === GameStatus::Lost);
check('El temporizador se detiene al perder', !$game->isTimerRunning());

$game = new Game(Difficulty::Easy);
check('El temporizador no arranca antes del primer clic', $game->elapsedSeconds(50.0) === 0 && !$game->isTimerRunning());

echo "\nResultado: {$passed} correctas, {$failed} fallidas\n";
exit($failed === 0 ? 0 : 1);
