# Arquitectura del proyecto

Este documento describe la arquitectura de **Buscaminas**, una réplica en PHP del clásico videojuego. Es un documento vivo: se actualiza conforme se toman decisiones en el proyecto. Las decisiones confirmadas están en el [Registro de decisiones](#registro-de-decisiones).

> **Estado:** primera versión jugable implementada.

## 1. Visión general

Las reglas del juego viven en el servidor (PHP). El navegador solo dibuja el tablero y envía las acciones del jugador a una pequeña API JSON. El servidor aplica las reglas, guarda la partida en la sesión y responde con el estado actualizado.

```
 Navegador (game.js)
     │  POST api.php  {action: "reveal", row, col}
     ▼
 source/public/api.php ─▶ ApiController ─▶ GameService ─▶ Game / Board (dominio)
     ▲                                          │
     │                                          ▼
     │                               SessionGameRepository ($_SESSION)
     │
     └── JSON con el estado: tablero, minas restantes, estado, segundos transcurridos
```

Mientras se juega, la API **nunca envía la posición de las minas ocultas**; solo se revelan al terminar la partida. Así no se puede hacer trampa leyendo las respuestas del servidor.

## 2. Estilo arquitectónico

Arquitectura en **capas** inspirada en MVC, sin framework. Las dependencias apuntan hacia el dominio: el dominio no conoce HTTP, sesiones ni HTML, por eso se puede probar de forma aislada.

| Capa | Carpeta | Responsabilidad |
|---|---|---|
| **Dominio** | `src/Domain` | Reglas puras: tablero, casillas, minas, números, desborde, victoria y derrota. |
| **Aplicación** | `src/Application` | Casos de uso (nueva partida, descubrir, bandera) y el puerto `GameRepository`. |
| **Infraestructura** | `src/Infrastructure` | `SessionGameRepository`: guarda la partida serializada en `$_SESSION`. |
| **Presentación** | `src/Presentation`, `public/` | Controlador de la API, conversión del estado a JSON, página HTML, CSS y JavaScript. |

## 3. Modelo de dominio

| Elemento | Tipo | Descripción |
|---|---|---|
| `Game` | Clase | Partida: tablero, estado, casilla que explotó y tiempos de inicio y fin. |
| `Board` | Clase | Cuadrícula; coloca minas, calcula números y aplica el desborde. |
| `Cell` | Clase | Casilla: si tiene mina, número de minas vecinas y estado. |
| `CellState` | Enum | `Hidden`, `Revealed`, `Flagged`. |
| `GameStatus` | Enum | `Ready` (antes del primer clic), `Playing`, `Won`, `Lost`. |
| `Difficulty` | Enum | Tamaño del tablero y cantidad de minas por nivel. |
| `Position` | Clase `readonly` | Coordenada fila/columna. |

### 3.1 Niveles de dificultad

El tablero crece con la dificultad y las minas mantienen la **misma densidad** que el nivel fácil (10 minas / 64 casillas = 15.6 %). Fórmula: `minas = round(casillas × 10 / 64)`.

| Nivel | Tablero | Casillas | Minas |
|---|---|---|---|
| Fácil | 8 × 8 | 64 | 10 |
| Intermedio | 12 × 12 | 144 | 23 (22.5 redondeado) |
| Difícil | 16 × 16 | 256 | 40 |

### 3.2 Algoritmo de desborde (propagación)

Implementado en `Board::reveal()` como una **búsqueda en anchura (BFS)** con una cola (`SplQueue`):

1. Se descubre la casilla pulsada. Si es una mina o tiene número (> 0), se detiene ahí.
2. Si es un **0** (sin minas vecinas), entra a la cola.
3. Mientras la cola tenga casillas, se toma una y se revisan sus **8 vecinas**: cada vecina oculta y sin mina se descubre; si también es 0, entra a la cola.
4. Las casillas con número se descubren pero **no siguen propagando**: forman el borde de la zona abierta.
5. Las casillas con **bandera** nunca se descubren durante el desborde.

Se usa una cola en lugar de recursión para no agotar la pila en tableros grandes.

### 3.3 Otras reglas

- **Primer clic seguro:** las minas se colocan después del primer clic, evitando esa casilla y sus 8 vecinas, así la primera jugada siempre abre una zona.
- **Victoria:** todas las casillas sin mina están descubiertas. Las minas se marcan con bandera automáticamente.
- **Derrota:** se descubre una mina. Se muestran todas las minas, la que explotó en rojo y las banderas mal puestas tachadas.
- **Temporizador:** empieza con el primer clic y se detiene al ganar o perder. El servidor guarda la hora de inicio y fin; el navegador muestra el conteo en vivo y se sincroniza con el servidor en cada jugada, así el tiempo no se pierde al recargar la página.

## 4. API

Un único punto de entrada: `source/public/api.php`.

| Método | Cuerpo | Acción |
|---|---|---|
| `GET` | — | Devuelve la partida guardada (o crea una nueva en nivel fácil). |
| `POST` | `{"action": "new", "difficulty": "easy" \| "medium" \| "hard"}` | Nueva partida. |
| `POST` | `{"action": "reveal", "row": 0, "col": 0}` | Descubre una casilla. |
| `POST` | `{"action": "flag", "row": 0, "col": 0}` | Pone o quita una bandera. |

Cada casilla de la respuesta tiene un estado `s`: `hidden`, `flag`, `open` (con su número `n`), `mine`, `exploded` o `wrong-flag`.

## 5. Interfaz

- Números con color: **1 azul, 2 verde, 3 rojo**; del 4 al 8 se usan los colores clásicos (azul marino, granate, verde azulado, negro, gris).
- Contador de minas restantes, botón de reinicio (carita) y temporizador `mm:ss`.
- Clic izquierdo descubre, clic derecho pone o quita bandera; el botón **Modo bandera** permite jugar en pantallas táctiles.
- Al terminar aparece el mensaje **«¡Ganaste!»** o **«Perdiste»** con el tiempo de juego.

## 6. Estructura de carpetas

```
Buscaminas/
├── index.php                  Redirige a source/public/ (punto de entrada para Herd)
├── docs/                      Documentación
├── source/
│   ├── public/                Lo que se sirve al navegador
│   │   ├── index.php          Página del juego
│   │   ├── api.php            API JSON
│   │   └── assets/            css/styles.css, js/game.js
│   ├── src/
│   │   ├── bootstrap.php      Carga automática PSR-4 (sin Composer)
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Presentation/
│   └── tests/run.php          Pruebas del dominio
└── README.md
```

## 7. Convenciones de código

- `declare(strict_types=1);` en todos los archivos PHP.
- Estilo **PSR-12** y carga automática **PSR-4** (espacio de nombres `Buscaminas\` → `source/src/`).
- Uso de enums, clases `readonly`, constantes tipadas y `match`.
- Código en inglés; comentarios, textos de la interfaz y documentación en español.

## 8. Decisiones pendientes

- [ ] ¿Adoptar Composer y PHPUnit/Pest para las pruebas?
- [ ] ¿Guardar récords de tiempo por nivel?
- [ ] ¿Agregar el «chording» clásico (clic sobre un número para descubrir sus vecinas cuando ya tiene sus banderas)?

---

## Registro de decisiones

| Fecha | Decisión | Motivo |
|---|---|---|
| 2026-11-23 | El proyecto se desarrolla en PHP 8.5.13. | Definido por el equipo. |
| 2026-11-23 | La documentación vive en `docs/` y el código fuente en `source/`. | Separar documentación y código. |
| 2026-11-23 | Arquitectura en capas sin framework ni Composer. | Proyecto pequeño; sin dependencias que instalar. |
| 2026-11-23 | Reglas en el servidor y navegador con JavaScript + API JSON. | El temporizador en vivo y el clic derecho requieren JavaScript; las reglas quedan en PHP y las minas ocultas nunca llegan al navegador. |
| 2026-11-23 | La partida se guarda en la sesión de PHP. | No requiere base de datos. |
| 2026-11-23 | Tableros de 8×8, 12×12 y 16×16 con 10, 23 y 40 minas. | Definido por el equipo: misma densidad de minas que el nivel fácil. |
| 2026-11-23 | Desborde con BFS iterativo. | Comportamiento clásico del juego sin riesgo de desbordar la pila. |
| 2026-11-23 | Primer clic seguro (casilla y vecinas sin minas). | Comportamiento estándar de las versiones modernas del juego. |
