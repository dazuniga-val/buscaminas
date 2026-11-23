<?php

declare(strict_types=1);

namespace Buscaminas\Application;

use Buscaminas\Domain\Game;

/** Puerto de almacenamiento de la partida en curso. */
interface GameRepository
{
    public function load(): ?Game;

    public function save(Game $game): void;
}
