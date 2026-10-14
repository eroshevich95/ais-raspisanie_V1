<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>404 — Страница не найдена</title>
<link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #fafafa;
    --ink: #1d1d1f;
    --ink-soft: #6e6e73;
    --line: #e5e5ea;
    --accent: #0071e3;
    --accent-hover: #0077ed;
    --glass: rgba(250, 250, 250, 0.8);

    --duck-body: #ffd93d;
    --duck-body-dark: #f0bc1a;
    --duck-beak: #ff9f1c;
    --duck-beak-dark: #e08000;
    --duck-eye: #1a1a1a;
    --duck-wing: #e8c010;

    --dust-color: rgba(200, 180, 130, 0.5);
    --footprint-color: rgba(200, 170, 90, 0.25);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
    min-height: 100vh;
    background: var(--bg);
    color: var(--ink);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "SF Pro Display", "Helvetica Neue", sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    letter-spacing: -0.01em;
    overflow-x: hidden;
}

/* ============================================================
   ХЕДЕР
   ============================================================ */
.header {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 48px;
    z-index: 50;
    background: var(--glass);
    backdrop-filter: saturate(180%) blur(20px);
    -webkit-backdrop-filter: saturate(180%) blur(20px);
    border-bottom: 1px solid rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    justify-content: center;
}
.header-inner {
    width: 100%;
    max-width: 1024px;
    padding: 0 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.logo {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--ink);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.85rem;
    transition: opacity 0.2s;
}
.logo:hover { opacity: 0.7; color: var(--ink); }
.logo-mark {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    background: linear-gradient(135deg, #0071e3, #5e5ce6);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.68rem;
    box-shadow: 0 2px 6px rgba(0,113,227,0.25);
}
.nav { display: flex; gap: 26px; align-items: center; }
.nav a {
    color: var(--ink-soft);
    text-decoration: none;
    font-size: 0.78rem;
    transition: color 0.2s;
}
.nav a:hover { color: var(--ink); }
.nav a.is-accent { color: var(--accent); font-weight: 500; }
.nav a.is-accent:hover { color: var(--accent-hover); }

/* ============================================================
   КОНТЕНТ
   ============================================================ */
.page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 100px 24px 80px;
    text-align: center;
    position: relative;
}

.number {
    font-size: clamp(6rem, 18vw, 12rem);
    font-weight: 700;
    letter-spacing: -0.06em;
    line-height: 0.9;
    color: var(--ink);
    margin: 0 0 24px;
    user-select: none;
    font-feature-settings: "ss01";
    display: flex;
    justify-content: center;
    align-items: baseline;
}
.number .mid {
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* ============================================================
   СЦЕНА
   ============================================================ */
.scene {
    position: relative;
    width: 100%;
    max-width: 800px;
    height: 240px;
    margin: 8px auto 32px;
}

.floor {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100vw;
    height: 1px;
    background: linear-gradient(90deg,
        transparent 0%,
        rgba(0,0,0,0.06) 20%,
        rgba(0,0,0,0.06) 80%,
        transparent 100%);
}

/* Следы лапок */
.footprint {
    position: absolute;
    bottom: 0;
    width: 14px;
    height: 18px;
    pointer-events: none;
    opacity: 0;
    animation: footprintIn 3s ease-out forwards;
    z-index: 1;
}
@keyframes footprintIn {
    0%   { opacity: 0; transform: scale(0.5); }
    15%  { opacity: 1; transform: scale(1); }
    75%  { opacity: 1; }
    100% { opacity: 0; transform: scale(1); }
}

/* Пыль */
.dust {
    position: absolute;
    bottom: 0;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--dust-color);
    pointer-events: none;
    z-index: 1;
    animation: dustFly 0.8s ease-out forwards;
}
@keyframes dustFly {
    0%   { opacity: 0.7; transform: translate(0, 0) scale(0.5); }
    100% { opacity: 0; transform: translate(var(--dx, 0), var(--dy, -20px)) scale(1.8); }
}

/* ============================================================
   УТЁНОК
   ============================================================ */
.duckling-container {
    position: absolute;
    bottom: 0;
    left: calc(50% - 90px);
    transform: translateX(-50%);
    transition: left 1.4s cubic-bezier(0.3, 0, 0.4, 1) 0.15s,
                transform 1.4s cubic-bezier(0.3, 0, 0.4, 1) 0.15s;
    z-index: 1;
}

.duckling-container.gone-right {
    left: calc(120vw - 90px);
    transform: translateX(-50%) scale(1);
}

.duckling-container.entering {
    left: calc(-25vw - 90px);
    transform: translateX(-50%) scale(1);
}

/* ============================================================
   УТКА
   ============================================================ */
.duck-container {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    transition: left 1.4s cubic-bezier(0.3, 0, 0.4, 1),
                transform 1.4s cubic-bezier(0.3, 0, 0.4, 1);
    z-index: 2;
}

.duck-container.gone-right {
    left: 120vw;
    transform: translateX(-50%) scale(1);
}

.duck-container.entering {
    left: -25vw;
    transform: translateX(-50%) scale(1);
}

/* ============================================================
   ПУЗЫРЬ
   ============================================================ */
.bubble {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(20px) scale(0.4);
    transform-origin: bottom center;
    background: var(--bg);
    border: 3px solid var(--ink);
    border-radius: 18px;
    padding: 12px 26px;
    font-family: 'Nunito', 'Inter', sans-serif;
    font-weight: 900;
    font-size: clamp(1.2rem, 2.2vw, 1.6rem);
    color: var(--ink);
    letter-spacing: -0.01em;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    box-shadow: 4px 4px 0 var(--ink);
    margin-bottom: 12px;
}

.bubble.visible {
    animation: bubbleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
@keyframes bubbleIn {
    0% { opacity: 0; transform: translateX(-50%) translateY(20px) scale(0.4); }
    60% { opacity: 1; transform: translateX(-50%) translateY(-6px) scale(1.1); }
    100% { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
}

.bubble.hiding {
    animation: bubbleOut 0.3s cubic-bezier(0.4, 0, 1, 1) forwards;
}
@keyframes bubbleOut {
    0% { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
    100% { opacity: 0; transform: translateX(-50%) translateY(10px) scale(0.6); }
}

.bubble::after {
    content: '';
    position: absolute;
    bottom: -16px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 13px solid transparent;
    border-right: 13px solid transparent;
    border-top: 17px solid var(--ink);
}
.bubble::before {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-top: 14px solid var(--bg);
    z-index: 1;
}

/* ============================================================
   УТКА и УТЁНОК
   ============================================================ */
.duck {
    width: clamp(110px, 16vw, 170px);
    height: auto;
    display: block;
    cursor: pointer;
    user-select: none;
    filter: drop-shadow(0 8px 12px rgba(0,0,0,0.12));
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform-origin: center bottom;
}

.duckling {
    width: clamp(60px, 9vw, 95px);
    height: auto;
    display: block;
    filter: drop-shadow(0 6px 10px rgba(0,0,0,0.1));
    transform-origin: center bottom;
}

.duck:hover { transform: scale(1.05); }

.duck.idle { animation: duckIdle 2.5s ease-in-out infinite; }
@keyframes duckIdle {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    25%      { transform: translateY(-3px) rotate(-1deg); }
    75%      { transform: translateY(-3px) rotate(1deg); }
}

.duckling.idle { animation: ducklingIdle 2.5s ease-in-out infinite 0.3s; }
@keyframes ducklingIdle {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    25%      { transform: translateY(-2px) rotate(-1deg); }
    75%      { transform: translateY(-2px) rotate(1deg); }
}

.duck.running { animation: duckRun 0.35s ease-in-out infinite; }
@keyframes duckRun {
    0%, 100% { transform: rotate(-4deg) translateY(0); }
    50%      { transform: rotate(-4deg) translateY(-5px); }
}

.duckling.running { animation: ducklingRun 0.28s ease-in-out infinite; }
@keyframes ducklingRun {
    0%, 100% { transform: rotate(-4deg) translateY(0); }
    50%      { transform: rotate(-4deg) translateY(-4px); }
}

.duck.flip { transform: scaleX(-1); }
.duckling.flip { transform: scaleX(-1); }

.duck .beak-open { display: none; }
.duck.quacking .beak-closed { display: none; }
.duck.quacking .beak-open { display: block; }

.duck.jump { animation: duckJump 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes duckJump {
    0%   { transform: translateY(0) scale(1); }
    40%  { transform: translateY(-14px) scale(1.06); }
    70%  { transform: translateY(-4px) scale(0.98); }
    100% { transform: translateY(0) scale(1); }
}

.duck .pupil {
    transition: cx 0.2s ease-out, cy 0.2s ease-out;
}

/* ============================================================
   ЗАГОЛОВОК
   ============================================================ */
.title {
    font-size: clamp(1.3rem, 2.6vw, 1.7rem);
    font-weight: 600;
    letter-spacing: -0.02em;
    color: var(--ink);
    margin: 0 0 12px;
    line-height: 1.2;
}
.subtitle {
    font-size: clamp(0.92rem, 1.3vw, 1rem);
    color: var(--ink-soft);
    line-height: 1.55;
    max-width: 460px;
    margin: 0 auto 36px;
}

/* ============================================================
   КНОПКИ
   ============================================================ */
.actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 26px;
    border-radius: 980px;
    font-family: inherit;
    font-size: 0.92rem;
    font-weight: 500;
    letter-spacing: -0.01em;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    white-space: nowrap;
}
.btn--primary {
    background: var(--accent);
    color: #fff;
}
.btn--primary:hover {
    background: var(--accent-hover);
    color: #fff;
    transform: scale(1.03);
    box-shadow: 0 8px 24px rgba(0,113,227,0.3);
}
.btn--ghost {
    background: transparent;
    color: var(--accent);
    border: 1px solid var(--accent);
}
.btn--ghost:hover {
    background: rgba(0,113,227,0.06);
    color: var(--accent);
    transform: scale(1.03);
}
.btn i { font-size: 1rem; }

/* ============================================================
   FADE IN
   ============================================================ */
.fade-in {
    opacity: 0;
    transform: translateY(12px);
    animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
.d1 { animation-delay: 0.05s; }
.d2 { animation-delay: 0.15s; }
.d3 { animation-delay: 0.25s; }
.d4 { animation-delay: 0.35s; }

/* ============================================================
   МОБИЛЬНАЯ
   ============================================================ */
@media (max-width: 640px) {
    .header-inner { padding: 0 16px; }
    .nav { gap: 16px; }
    .nav a:not(.is-accent) { display: none; }
    .scene { height: 190px; }
    .duck { width: clamp(90px, 22vw, 130px); }
    .duckling { width: clamp(50px, 12vw, 75px); }
    .duckling-container { left: calc(50% - 70px); }
    .duckling-container.gone-right { left: calc(120vw - 70px); }
    .duckling-container.entering { left: calc(-25vw - 70px); }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
</head>
<body>

<header class="header">
    <div class="header-inner">
        <a href="/dashboard.php" class="logo">
            <span class="logo-mark"><i class="bi bi-mortarboard-fill"></i></span>
            АИС-расписание.РФ
        </a>
        <nav class="nav">
            <a href="/dashboard.php">Главная</a>
            <a href="/login.php" class="is-accent">Войти</a>
        </nav>
    </div>
</header>

<main class="page">

    <div class="number fade-in d1">4<span class="mid">0</span>4</div>

    <div class="scene fade-in d2" id="scene">
        <div class="floor"></div>

        <!-- Утёнок — та же утка, только меньше -->
        <div class="duckling-container" id="ducklingContainer">
            <svg class="duckling idle" id="duckling" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="100" cy="185" rx="55" ry="6" fill="rgba(0,0,0,0.08)"/>
                <path d="M 55 130 Q 35 120 30 100 Q 55 115 75 120 Z" fill="var(--duck-body-dark)"/>
                <ellipse cx="100" cy="135" rx="65" ry="48" fill="var(--duck-body)"/>
                <ellipse cx="100" cy="155" rx="55" ry="22" fill="var(--duck-body-dark)" opacity="0.4"/>
                <path d="M 90 130 Q 100 115 130 120 Q 140 130 130 145 Q 110 152 90 145 Z" fill="var(--duck-wing)"/>
                <path d="M 110 95 Q 120 75 130 60 Q 140 70 138 95 Z" fill="var(--duck-body)"/>
                <circle cx="140" cy="65" r="38" fill="var(--duck-body)"/>
                <ellipse cx="128" cy="52" rx="14" ry="10" fill="#fff" opacity="0.35"/>
                <circle cx="152" cy="58" r="7" fill="#fff"/>
                <circle cx="152" cy="58" r="4" fill="var(--duck-eye)"/>
                <circle cx="153.5" cy="56.5" r="1.5" fill="#fff"/>
                <g class="beak-closed">
                    <path d="M 168 68 Q 195 65 198 72 Q 195 80 168 78 Z" fill="var(--duck-beak)"/>
                    <path d="M 168 73 Q 185 71 198 73" fill="none" stroke="var(--duck-beak-dark)" stroke-width="1"/>
                </g>
            </svg>
        </div>

        <!-- Утка -->
        <div class="duck-container" id="duckContainer">
            <div class="bubble" id="bubble">Упс!</div>
            <svg class="duck idle" id="duck" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="100" cy="185" rx="55" ry="6" fill="rgba(0,0,0,0.08)"/>
                <path d="M 55 130 Q 35 120 30 100 Q 55 115 75 120 Z" fill="var(--duck-body-dark)"/>
                <ellipse cx="100" cy="135" rx="65" ry="48" fill="var(--duck-body)"/>
                <ellipse cx="100" cy="155" rx="55" ry="22" fill="var(--duck-body-dark)" opacity="0.4"/>
                <path d="M 90 130 Q 100 115 130 120 Q 140 130 130 145 Q 110 152 90 145 Z" fill="var(--duck-wing)"/>
                <path d="M 110 95 Q 120 75 130 60 Q 140 70 138 95 Z" fill="var(--duck-body)"/>
                <circle cx="140" cy="65" r="38" fill="var(--duck-body)"/>
                <ellipse cx="128" cy="52" rx="14" ry="10" fill="#fff" opacity="0.35"/>
                <circle cx="152" cy="58" r="7" fill="#fff"/>
                <circle class="pupil" id="pupil" cx="152" cy="58" r="4" fill="var(--duck-eye)"/>
                <circle class="pupil-shine" cx="153.5" cy="56.5" r="1.5" fill="#fff"/>
                <g class="beak-closed">
                    <path d="M 168 68 Q 195 65 198 72 Q 195 80 168 78 Z" fill="var(--duck-beak)"/>
                    <path d="M 168 73 Q 185 71 198 73" fill="none" stroke="var(--duck-beak-dark)" stroke-width="1"/>
                </g>
                <g class="beak-open">
                    <path d="M 168 65 Q 195 60 200 68 Q 190 70 168 71 Z" fill="var(--duck-beak)"/>
                    <path d="M 168 78 Q 195 82 200 80 Q 190 88 168 83 Z" fill="var(--duck-beak)"/>
                    <path d="M 168 71 Q 190 72 200 74 Q 190 78 168 78 Z" fill="#8a3a00"/>
                </g>
            </svg>
        </div>
    </div>

    <h1 class="title fade-in d3">Страница не найдена</h1>

    <p class="subtitle fade-in d3">
        Возможно, адрес введён с ошибкой<br>
        или страница была перемещена
    </p>

    <div class="actions fade-in d4">
        <a href="/dashboard.php" class="btn btn--primary">
            <i class="bi bi-arrow-left"></i>
            На главную
        </a>
        <a href="javascript:history.back()" class="btn btn--ghost">
            <i class="bi bi-clock-history"></i>
            Назад
        </a>
    </div>

</main>

<script>
(function() {
    const duck = document.getElementById('duck');
    const duckling = document.getElementById('duckling');
    const duckContainer = document.getElementById('duckContainer');
    const ducklingContainer = document.getElementById('ducklingContainer');
    const bubble = document.getElementById('bubble');
    const scene = document.getElementById('scene');
    const pupil = document.getElementById('pupil');

    if (!duck || !bubble || !duckContainer) return;

    let isRunning = false;
    let audioCtx = null;
    let audioUnlocked = false;

    /* ============================================================
       АУДИО
       ============================================================ */
    function ensureAudio() {
        if (!audioCtx) {
            try {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            } catch (e) {
                audioCtx = null;
                return null;
            }
        }
        return audioCtx;
    }

    function unlockAudio() {
        const ctx = ensureAudio();
        if (!ctx) return Promise.resolve(false);

        if (ctx.state === 'suspended') {
            return ctx.resume().then(() => {
                audioUnlocked = true;
                return true;
            }).catch(() => false);
        }
        audioUnlocked = true;
        return Promise.resolve(true);
    }

    // Шаги — короткий "топ"
    function playFootstep(pitch = 1) {
        const ctx = ensureAudio();
        if (!ctx) return;
        if (!audioUnlocked) return;
        if (ctx.state === 'suspended') return;

        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime((220 + Math.random() * 50) * pitch, now);
        osc.frequency.exponentialRampToValueAtTime(90 * pitch, now + 0.1);

        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.15 * (pitch < 1 ? 0.5 : 0.9), now + 0.008);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.13);

        osc.connect(gain).connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.15);
    }

    /* ============================================================
       КРЯ-КРЯ — реалистичное утиное кряканье
       ============================================================ */
    function playQuack() {
        const ctx = ensureAudio();
        if (!ctx) return;
        if (!audioUnlocked) return;
        if (ctx.state === 'suspended') return;

        const now = ctx.currentTime;

        // Два кряка: первый длиннее и выше, второй короче и ниже
        playSingleQuack(ctx, now + 0.00, 0.10, 480, 240, 0.22);
        playSingleQuack(ctx, now + 0.16, 0.08, 420, 200, 0.18);
    }

    // Один "кря" — быстрый подъём, резкий спад, шум, фильтр, vibrato
    function playSingleQuack(ctx, startTime, duration, f1, f2, volume) {
        // 1. Основной тон — triangle (мягче, чем sawtooth)
        const osc = ctx.createOscillator();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(f1, startTime);
        osc.frequency.exponentialRampToValueAtTime(f1 * 1.15, startTime + duration * 0.15);
        osc.frequency.exponentialRampToValueAtTime(f2, startTime + duration);

        // 2. Немного шума — придаёт "гнусавость"
        const noiseBuffer = ctx.createBuffer(1, Math.floor(ctx.sampleRate * duration), ctx.sampleRate);
        const noiseData = noiseBuffer.getChannelData(0);
        for (let i = 0; i < noiseData.length; i++) {
            noiseData[i] = (Math.random() * 2 - 1) * 0.3;
        }
        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;

        // 3. Bandpass — выделяет "утиную" полосу
        const filter = ctx.createBiquadFilter();
        filter.type = 'bandpass';
        filter.frequency.setValueAtTime(900, startTime);
        filter.Q.value = 6;

        // 4. Огибающая громкости
        const gain = ctx.createGain();
        gain.gain.setValueAtTime(0.0001, startTime);
        gain.gain.exponentialRampToValueAtTime(volume, startTime + 0.008);
        gain.gain.exponentialRampToValueAtTime(volume * 0.6, startTime + duration * 0.6);
        gain.gain.exponentialRampToValueAtTime(0.0001, startTime + duration);

        // 5. Vibrato — "дрожание" голоса
        const lfo = ctx.createOscillator();
        lfo.type = 'sine';
        lfo.frequency.value = 28;
        const lfoGain = ctx.createGain();
        lfoGain.gain.value = 20;
        lfo.connect(lfoGain).connect(osc.frequency);

        // Связи
        osc.connect(filter);
        noise.connect(filter);
        filter.connect(gain);
        gain.connect(ctx.destination);

        // Запуск и остановка
        osc.start(startTime);
        noise.start(startTime);
        lfo.start(startTime);

        osc.stop(startTime + duration);
        noise.stop(startTime + duration);
        lfo.stop(startTime + duration);
    }

    // Разбудить звук при первом взаимодействии
    ['click', 'touchstart', 'keydown'].forEach(evt => {
        document.addEventListener(evt, () => { unlockAudio(); }, { once: true, passive: true });
    });

    /* ============================================================
       ПЫЛЬ И СЛЕДЫ
       ============================================================ */
    function createDust(x, y, small = false) {
        const dust = document.createElement('div');
        dust.className = 'dust';
        dust.style.left = x + 'px';
        dust.style.bottom = (scene.offsetHeight - y) + 'px';
        if (small) { dust.style.width = '5px'; dust.style.height = '5px'; }
        dust.style.setProperty('--dx', (Math.random() - 0.5) * 30 + 'px');
        dust.style.setProperty('--dy', (-15 - Math.random() * 20) + 'px');
        scene.appendChild(dust);
        setTimeout(() => dust.remove(), 800);
    }

    function createFootprint(x, small = false) {
        const fp = document.createElement('div');
        fp.className = 'footprint';
        fp.style.left = x + 'px';
        if (small) { fp.style.width = '9px'; fp.style.height = '12px'; }
        fp.innerHTML = `
            <svg viewBox="0 0 14 18" width="100%" height="100%">
                <ellipse cx="7" cy="12" rx="5" ry="5" fill="var(--footprint-color)"/>
                <ellipse cx="3" cy="6" rx="1.5" ry="2" fill="var(--footprint-color)"/>
                <ellipse cx="6" cy="5" rx="1.5" ry="2" fill="var(--footprint-color)"/>
                <ellipse cx="9" cy="5" rx="1.5" ry="2" fill="var(--footprint-color)"/>
            </svg>
        `;
        scene.appendChild(fp);
        setTimeout(() => fp.remove(), 3000);
    }

    /* ============================================================
       ЗРАЧОК
       ============================================================ */
    document.addEventListener('mousemove', (e) => {
        if (!pupil || !duck) return;
        const rect = duck.getBoundingClientRect();
        const eyeX = rect.left + rect.width * 0.76;
        const eyeY = rect.top + rect.height * 0.29;
        const dx = e.clientX - eyeX;
        const dy = e.clientY - eyeY;
        const dist = Math.sqrt(dx * dx + dy * dy) || 1;
        const maxOffset = 2.5;
        const offset = Math.min(maxOffset, dist / 50);
        pupil.setAttribute('cx', 152 + dx / dist * offset);
        pupil.setAttribute('cy', 58 + dy / dist * offset);
    });

    /* ============================================================
       ПУЗЫРЬ
       ============================================================ */
    function showBubble(text) {
        bubble.textContent = text;
        bubble.classList.remove('hiding', 'visible');
        void bubble.offsetWidth;
        bubble.classList.add('visible');
    }

    function hideBubble() {
        bubble.classList.remove('visible');
        bubble.classList.add('hiding');
    }

    /* ============================================================
       ЭФФЕКТЫ БЕГА
       ============================================================ */
    function startRunEffects() {
        const interval = setInterval(() => {
            if (!duck.classList.contains('running')) {
                clearInterval(interval);
                return;
            }
            const rect = duck.getBoundingClientRect();
            const sceneRect = scene.getBoundingClientRect();
            const footX = rect.left - sceneRect.left + rect.width * 0.7;
            const footY = rect.top - sceneRect.top + rect.height * 0.95;

            createDust(footX, footY, false);
            createFootprint(footX - 5, false);
            playFootstep(1);

            const lingRect = duckling.getBoundingClientRect();
            const lingFootX = lingRect.left - sceneRect.left + lingRect.width * 0.7;
            const lingFootY = lingRect.top - sceneRect.top + lingRect.height * 0.95;

            createDust(lingFootX, lingFootY, true);
            createFootprint(lingFootX - 3, true);
            playFootstep(1.4);
        }, 180);
    }

    /* ============================================================
       СЦЕНАРИЙ: УБЕГАЕТ И ЗАБЕГАЕТ
       ============================================================ */
    function runAwayAndComeBack() {
        if (isRunning) return;
        isRunning = true;

        setTimeout(() => {
            hideBubble();

            duck.classList.remove('flip', 'idle');
            duckling.classList.remove('flip', 'idle');
            duck.classList.add('running');
            duckling.classList.add('running');

            duckContainer.classList.add('gone-right');
            ducklingContainer.classList.add('gone-right');

            startRunEffects();

            setTimeout(() => {
                duckContainer.style.transition = 'none';
                ducklingContainer.style.transition = 'none';

                duckContainer.classList.remove('gone-right');
                ducklingContainer.classList.remove('gone-right');
                duckContainer.classList.add('entering');
                ducklingContainer.classList.add('entering');

                void duckContainer.offsetWidth;
                void ducklingContainer.offsetWidth;

                duckContainer.style.transition = '';
                ducklingContainer.style.transition = '';

                duckContainer.classList.remove('entering');
                ducklingContainer.classList.remove('entering');

                setTimeout(() => {
                    duck.classList.remove('running');
                    duckling.classList.remove('running');
                    duck.classList.add('idle');
                    duckling.classList.add('idle');

                    showBubble('Упс!');
                    isRunning = false;
                }, 1400);
            }, 1400);
        }, 1200);
    }

    /* ============================================================
       ФРАЗЫ
       ============================================================ */
    const phrases = ['Упс!', 'Зря-зря!', 'Кря!', 'Куда?', 'Хм...', 'Ой!', 'Ну вот...'];
    let phraseIndex = 0;

    /* ============================================================
       КЛИК ПО УТКЕ
       ============================================================ */
    duck.addEventListener('click', () => {
        if (isRunning) return;
        if (!bubble.classList.contains('visible')) return;

        unlockAudio().then(() => {
            playQuack();
        });

        phraseIndex = (phraseIndex + 1) % phrases.length;
        showBubble(phrases[phraseIndex]);

        duck.classList.add('quacking');
        setTimeout(() => duck.classList.remove('quacking'), 400);

        duck.classList.remove('jump');
        void duck.offsetWidth;
        duck.classList.add('jump');
        setTimeout(() => duck.classList.remove('jump'), 600);

        runAwayAndComeBack();
    });

    /* ============================================================
       САМА УБЕГАЕТ РАЗ В МИНУТУ
       ============================================================ */
    setInterval(() => {
        if (isRunning) return;
        if (!bubble.classList.contains('visible')) return;

        phraseIndex = (phraseIndex + 1) % phrases.length;
        showBubble(phrases[phraseIndex]);

        duck.classList.add('quacking');
        setTimeout(() => duck.classList.remove('quacking'), 400);

        setTimeout(() => runAwayAndComeBack(), 800);
    }, 60000);

    /* ============================================================
       СТАРТ
       ============================================================ */
    setTimeout(() => {
        showBubble('Упс!');
    }, 400);
})();
</script>

</body>
</html>