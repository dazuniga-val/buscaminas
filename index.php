<?php

declare(strict_types=1);

// Punto de entrada para Laravel Herd: redirige a la aplicación dentro de source/public.
header('Location: /source/public/', true, 302);
exit;
