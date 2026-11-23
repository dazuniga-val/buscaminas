<?php

declare(strict_types=1);

namespace Buscaminas\Domain;

enum CellState: string
{
    case Hidden = 'hidden';
    case Revealed = 'revealed';
    case Flagged = 'flagged';
}
