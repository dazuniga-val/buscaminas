# Tecnologías del proyecto

Este documento registra las tecnologías, versiones y herramientas que usa el proyecto **Buscaminas**. Se actualiza cada vez que se incorpora, cambia o retira una tecnología.

## Lenguaje y servidor

| Tecnología | Versión | Uso |
|---|---|---|
| PHP | 8.5.13 | Lógica del juego (dominio), API JSON y página principal |
| Sesiones de PHP | Incluidas en PHP | Guardan la partida en curso de cada jugador |
| `Random\Randomizer` | Incluido en PHP (8.2+) | Colocación aleatoria de las minas |

## Entorno de desarrollo

| Herramienta | Versión | Uso |
|---|---|---|
| Laravel Herd | La instalada en el equipo | Servidor web local; publica el proyecto en `http://buscaminas.test` |

## Frontend

| Tecnología | Versión | Uso |
|---|---|---|
| HTML5 | — | Estructura de la página (`source/public/index.php`) |
| CSS3 | — | Estilos, cuadrícula del tablero (CSS Grid) y colores de los números |
| JavaScript (vanilla, ES2020+) | — | Interacción con el tablero, temporizador y comunicación con la API mediante `fetch` |

No se usan frameworks ni librerías de frontend.

## Dependencias

El proyecto **no tiene dependencias externas** y no usa Composer. La carga automática de clases (PSR-4) se hace con `spl_autoload_register` en `source/src/bootstrap.php`.

| Paquete | Versión | Uso |
|---|---|---|
| — | — | — |

## Pruebas

| Herramienta | Versión | Uso |
|---|---|---|
| Script de pruebas propio | — | `php source/tests/run.php`: pruebas del dominio sin dependencias externas (desborde, números, minas, victoria, derrota y temporizador) |

---

## Historial de cambios

| Fecha | Cambio |
|---|---|
| 2026-11-23 | Creación del documento. Se registra PHP 8.5.13 como versión del proyecto. |
| 2026-11-23 | Primera versión jugable: se agregan sesiones de PHP, `Random\Randomizer`, HTML/CSS/JavaScript sin frameworks, Laravel Herd como servidor local y un script de pruebas propio. |
