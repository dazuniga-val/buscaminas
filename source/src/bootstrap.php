<?php

declare(strict_types=1);

/*
 * Carga automática de clases (PSR-4) sin Composer:
 * el espacio de nombres Buscaminas\ se corresponde con la carpeta src/.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Buscaminas\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
