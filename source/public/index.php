<?php

declare(strict_types=1);

use Buscaminas\Domain\Difficulty;

require __DIR__ . '/../src/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buscaminas</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💣</text></svg>">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<main class="app">
    <h1 class="title">Buscaminas</h1>

    <nav class="levels" aria-label="Dificultad">
        <?php foreach (Difficulty::cases() as $level): ?>
            <button type="button" class="level" data-difficulty="<?= htmlspecialchars($level->value) ?>">
                <span class="level-name"><?= htmlspecialchars($level->label()) ?></span>
                <span class="level-info"><?= $level->size() ?>×<?= $level->size() ?> · <?= $level->mines() ?> minas</span>
            </button>
        <?php endforeach; ?>
    </nav>

    <section class="game" aria-label="Partida">
        <header class="hud">
            <div class="counter" title="Minas restantes">
                <span class="counter-icon" aria-hidden="true">💣</span>
                <span id="mines-left" class="counter-value">000</span>
            </div>

            <button type="button" id="reset" class="reset" title="Nueva partida" aria-label="Nueva partida">🙂</button>

            <div class="counter" title="Tiempo de juego">
                <span class="counter-icon" aria-hidden="true">⏱️</span>
                <span id="timer" class="counter-value">00:00</span>
            </div>
        </header>

        <div class="board-wrap">
            <div id="board" class="board" role="grid" aria-label="Tablero"></div>

            <div id="message" class="message" hidden>
                <div class="message-card">
                    <p id="message-title" class="message-title"></p>
                    <p id="message-detail" class="message-detail"></p>
                    <button type="button" id="play-again" class="play-again">Jugar de nuevo</button>
                </div>
            </div>
        </div>

        <footer class="controls">
            <button type="button" id="flag-mode" class="flag-mode" aria-pressed="false">🚩 Modo bandera</button>
            <p class="hint">Clic: descubrir · Clic derecho: bandera</p>
        </footer>
    </section>
</main>

<script src="assets/js/game.js"></script>
</body>
</html>
