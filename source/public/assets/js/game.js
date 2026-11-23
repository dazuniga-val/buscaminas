'use strict';

(() => {
    const API_URL = 'api.php';

    const boardEl = document.getElementById('board');
    const minesLeftEl = document.getElementById('mines-left');
    const timerEl = document.getElementById('timer');
    const resetBtn = document.getElementById('reset');
    const messageEl = document.getElementById('message');
    const messageTitleEl = document.getElementById('message-title');
    const messageDetailEl = document.getElementById('message-detail');
    const playAgainBtn = document.getElementById('play-again');
    const flagModeBtn = document.getElementById('flag-mode');
    const levelButtons = document.querySelectorAll('.level');

    let state = null;
    let busy = false;
    let flagMode = false;

    // Temporizador: el servidor indica los segundos transcurridos y el navegador sigue contando.
    let timerInterval = null;
    let timerBase = 0;
    let timerSyncedAt = 0;

    async function request(payload) {
        const options = payload
            ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }
            : { method: 'GET' };

        const response = await fetch(API_URL, { ...options, credentials: 'same-origin' });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'Error de comunicación con el servidor.');
        }
        return data;
    }

    async function send(payload) {
        if (busy) return;
        busy = true;
        try {
            render(await request(payload));
        } catch (error) {
            console.error(error);
            alert(error.message);
        } finally {
            busy = false;
        }
    }

    function render(newState) {
        state = newState;
        renderLevels();
        renderBoard();
        renderCounters();
        syncTimer();
        renderMessage();
    }

    function renderLevels() {
        levelButtons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.difficulty === state.difficulty);
        });
    }

    function renderBoard() {
        const finished = state.status === 'won' || state.status === 'lost';

        boardEl.className = `board ${state.difficulty}${finished ? ' finished' : ''}`;
        boardEl.style.setProperty('--cols', state.cols);

        const fragment = document.createDocumentFragment();
        state.cells.forEach((row, r) => {
            row.forEach((cell, c) => {
                fragment.appendChild(createCell(cell, r, c));
            });
        });
        boardEl.replaceChildren(fragment);
    }

    function createCell(cell, row, col) {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = `cell ${cell.s}`;
        el.dataset.row = row;
        el.dataset.col = col;
        el.setAttribute('role', 'gridcell');

        switch (cell.s) {
            case 'open':
                if (cell.n > 0) {
                    el.textContent = cell.n;
                    el.classList.add(`n${cell.n}`);
                }
                el.setAttribute('aria-label', cell.n > 0 ? `${cell.n} minas cerca` : 'Vacía');
                break;
            case 'flag':
                el.textContent = '🚩';
                el.setAttribute('aria-label', 'Bandera');
                break;
            case 'wrong-flag':
                el.textContent = '🚩';
                el.setAttribute('aria-label', 'Bandera incorrecta');
                break;
            case 'mine':
            case 'exploded':
                el.textContent = '💣';
                el.setAttribute('aria-label', 'Mina');
                break;
            default:
                el.setAttribute('aria-label', 'Casilla oculta');
        }

        return el;
    }

    function renderCounters() {
        minesLeftEl.textContent = formatMines(state.minesRemaining);
        resetBtn.textContent = { won: '😎', lost: '😵' }[state.status] || '🙂';
    }

    function formatMines(value) {
        const sign = value < 0 ? '-' : '';
        return sign + String(Math.abs(value)).padStart(3, '0');
    }

    function formatTime(totalSeconds) {
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    function currentElapsed() {
        if (!state || !state.timerRunning) return timerBase;
        return timerBase + Math.floor((performance.now() - timerSyncedAt) / 1000);
    }

    function syncTimer() {
        timerBase = state.elapsed;
        timerSyncedAt = performance.now();
        timerEl.textContent = formatTime(timerBase);

        if (state.timerRunning && !timerInterval) {
            timerInterval = setInterval(() => {
                timerEl.textContent = formatTime(currentElapsed());
            }, 250);
        } else if (!state.timerRunning && timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }

    function renderMessage() {
        const finished = state.status === 'won' || state.status === 'lost';
        messageEl.hidden = !finished;
        if (!finished) return;

        const won = state.status === 'won';
        messageEl.className = `message ${state.status}`;
        messageTitleEl.textContent = won ? '¡Ganaste!' : 'Perdiste';
        messageDetailEl.textContent = won
            ? `Despejaste el tablero ${state.difficultyLabel.toLowerCase()} en ${formatTime(state.elapsed)}.`
            : `Encontraste una mina después de ${formatTime(state.elapsed)}.`;
    }

    function cellFromEvent(event) {
        const target = event.target.closest('.cell');
        if (!target || !boardEl.contains(target)) return null;
        return { row: Number(target.dataset.row), col: Number(target.dataset.col), el: target };
    }

    function setFlagMode(enabled) {
        flagMode = enabled;
        flagModeBtn.setAttribute('aria-pressed', String(enabled));
    }

    // Clic izquierdo: descubrir (o poner bandera si el modo bandera está activo).
    boardEl.addEventListener('click', (event) => {
        const cell = cellFromEvent(event);
        if (!cell) return;

        const isHidden = cell.el.classList.contains('hidden');
        const isFlag = cell.el.classList.contains('flag');

        if (flagMode && (isHidden || isFlag)) {
            send({ action: 'flag', row: cell.row, col: cell.col });
        } else if (isHidden) {
            send({ action: 'reveal', row: cell.row, col: cell.col });
        }
    });

    // Clic derecho: poner o quitar bandera.
    boardEl.addEventListener('contextmenu', (event) => {
        event.preventDefault();
        const cell = cellFromEvent(event);
        if (!cell) return;

        if (cell.el.classList.contains('hidden') || cell.el.classList.contains('flag')) {
            send({ action: 'flag', row: cell.row, col: cell.col });
        }
    });

    // Cara de sorpresa mientras se presiona una casilla.
    boardEl.addEventListener('mousedown', (event) => {
        if (state && (state.status === 'ready' || state.status === 'playing') && cellFromEvent(event)) {
            resetBtn.textContent = '😮';
        }
    });
    document.addEventListener('mouseup', () => {
        if (state) renderCounters();
    });

    levelButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            setFlagMode(false);
            send({ action: 'new', difficulty: btn.dataset.difficulty });
        });
    });

    const restart = () => {
        setFlagMode(false);
        send({ action: 'new', difficulty: state ? state.difficulty : 'easy' });
    };
    resetBtn.addEventListener('click', restart);
    playAgainBtn.addEventListener('click', restart);

    flagModeBtn.addEventListener('click', () => setFlagMode(!flagMode));

    // Cargar la partida guardada en la sesión (o una nueva en nivel fácil).
    send(null);
})();
