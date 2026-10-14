<?php
http_response_code(503);
header('Retry-After: 3600');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/settings.php';

$title   = getSetting($pdo, 'maintenance_title', 'Идут технические работы');
$message = getSetting($pdo, 'maintenance_message', 'Мы обновляем систему. Скоро вернёмся.');
$until   = getSetting($pdo, 'maintenance_until', null);

$untilText = '';
$untilTs   = 0;
if (!empty($until)) {
    $ts = strtotime($until);
    if ($ts > time()) {
        $untilTs = $ts;
        $months = ['','января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
        $untilText = date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' в ' . date('H:i', $ts);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?> — АИС-расписание.РФ</title>
<link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #0a0a0f;
    --bg-2: #14141c;
    --ink: #f5f5f7;
    --ink-soft: #a1a1a6;
    --accent: #0071e3;
    --accent-2: #5e5ce6;
    --accent-3: #bf5af2;
    --green: #30d158;
    --orange: #ff9f0a;
    --red: #ff453a;
    --line: rgba(255,255,255,0.08);

    --robot-body: #e8e8ed;
    --robot-body-dark: #9a9aa0;
    --robot-eye: #0071e3;
    --robot-eye-glow: rgba(0,113,227,0.7);

    --spark-color: #ffd60a;
    --spark-color-2: #ff9f0a;
    --arc-color: #9ee7ff;
    --smoke-color: rgba(200,200,220,0.35);
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
   АНИМИРОВАННЫЙ ФОН
   ============================================================ */
.bg-layer {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    overflow: hidden;
}
.bg-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.4;
    animation: blobFloat 18s ease-in-out infinite;
}
.bg-blob--1 {
    width: 500px; height: 500px;
    background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
    top: -10%; left: -5%;
}
.bg-blob--2 {
    width: 600px; height: 600px;
    background: radial-gradient(circle, var(--accent-3) 0%, transparent 70%);
    bottom: -20%; right: -10%;
    animation-delay: -6s;
}
.bg-blob--3 {
    width: 400px; height: 400px;
    background: radial-gradient(circle, var(--accent-2) 0%, transparent 70%);
    top: 40%; left: 50%;
    animation-delay: -12s;
}
@keyframes blobFloat {
    0%,100% { transform: translate(0,0) scale(1); }
    25%     { transform: translate(8%, -6%) scale(1.15); }
    50%     { transform: translate(-5%, 8%) scale(0.9); }
    75%     { transform: translate(6%, 5%) scale(1.08); }
}

/* Сетка */
.bg-grid {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 85%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 85%);
}

/* Частицы */
.particles {
    position: fixed;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    overflow: hidden;
}
.particle {
    position: absolute;
    bottom: -20px;
    width: 2px; height: 2px;
    border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 6px var(--accent);
    animation: particleRise linear infinite;
    opacity: 0;
}
@keyframes particleRise {
    0%   { transform: translateY(0) scale(0); opacity: 0; }
    10%  { opacity: 0.9; }
    90%  { opacity: 0.9; }
    100% { transform: translateY(-100vh) scale(1); opacity: 0; }
}

/* ============================================================
   ЗАПЧАСТИ (летающие)
   ============================================================ */
.part {
    position: absolute;
    color: rgba(255,255,255,0.06);
    pointer-events: none;
    z-index: 1;
    animation: partFloat linear infinite;
    font-size: 1.5rem;
}
@keyframes partFloat {
    0%   { transform: translateY(0) rotate(0deg); opacity: 0; }
    10%  { opacity: 1; }
    90%  { opacity: 1; }
    100% { transform: translateY(-110vh) rotate(360deg); opacity: 0; }
}

/* ============================================================
   ПАДАЮЩИЙ КОД (Matrix-like, тонко)
   ============================================================ */
.code-rain {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    overflow: hidden;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    line-height: 1.1;
    color: rgba(0,113,227,0.15);
    -webkit-mask-image: linear-gradient(180deg, transparent 0%, black 20%, black 80%, transparent 100%);
    mask-image: linear-gradient(180deg, transparent 0%, black 20%, black 80%, transparent 100%);
}
.code-col {
    position: absolute;
    top: -20%;
    display: flex;
    flex-direction: column;
    gap: 4px;
    animation: codeFall linear infinite;
    white-space: nowrap;
}
@keyframes codeFall {
    0%   { transform: translateY(0); }
    100% { transform: translateY(140vh); }
}
.code-col span { opacity: 0.5; }

/* ============================================================
   ХЕДЕР
   ============================================================ */
.header {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    z-index: 50;
    background: rgba(10,10,15,0.6);
    backdrop-filter: saturate(180%) blur(20px);
    -webkit-backdrop-filter: saturate(180%) blur(20px);
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: center;
}
.header-inner {
    width: 100%;
    max-width: 1100px;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--ink);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}
.logo-mark {
    width: 26px; height: 26px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--accent), var(--accent-2));
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.75rem;
    box-shadow: 0 4px 12px rgba(0,113,227,0.4);
}
.header-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 20px;
    background: rgba(255,159,10,0.12);
    border: 1px solid rgba(255,159,10,0.3);
    color: var(--orange);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}
.header-status .pulse-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: var(--orange);
    box-shadow: 0 0 0 0 rgba(255,159,10,0.7);
    animation: pulseDot 2s ease-in-out infinite;
}
@keyframes pulseDot {
    0%,100% { box-shadow: 0 0 0 0 rgba(255,159,10,0.7); }
    50%     { box-shadow: 0 0 0 8px rgba(255,159,10,0); }
}

/* ============================================================
   СТРАНИЦА
   ============================================================ */
.page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 100px 24px 60px;
    text-align: center;
    position: relative;
    z-index: 2;
}

/* ============================================================
   СЦЕНА
   ============================================================ */
.scene {
    position: relative;
    width: 100%;
    max-width: 720px;
    height: 340px;
    margin: 0 auto 32px;
    perspective: 1200px;
}
.scene-inner {
    position: relative;
    width: 100%;
    height: 100%;
    transform-style: preserve-3d;
    transition: transform 0.4s cubic-bezier(0.16,1,0.3,1);
}

/* Пол + кольца */
.floor {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: 90%;
    height: 60px;
    background: radial-gradient(ellipse at center top, rgba(0,113,227,0.3) 0%, transparent 70%);
    border-radius: 50%;
    filter: blur(8px);
    animation: floorGlow 4s ease-in-out infinite;
}
@keyframes floorGlow {
    0%,100% { opacity: 0.6; transform: translateX(-50%) scaleX(1); }
    50%     { opacity: 1; transform: translateX(-50%) scaleX(1.1); }
}
.floor-ring {
    position: absolute;
    bottom: 40px;
    left: 50%;
    width: 80px;
    height: 80px;
    margin-left: -40px;
    border: 1.5px solid rgba(0,113,227,0.5);
    border-radius: 50%;
    pointer-events: none;
    z-index: 2;
    animation: ringExpand 4s cubic-bezier(0.16,1,0.3,1) infinite;
    opacity: 0;
}
.floor-ring:nth-child(2) { animation-delay: 1.3s; }
.floor-ring:nth-child(3) { animation-delay: 2.6s; }
@keyframes ringExpand {
    0%   { transform: scale(0.4); opacity: 0; border-color: rgba(0,113,227,0.8); }
    20%  { opacity: 0.9; }
    100% { transform: scale(3.5); opacity: 0; border-color: rgba(0,113,227,0); }
}

/* Геары */
.gear {
    position: absolute;
    color: var(--ink);
    opacity: 0.06;
    z-index: 1;
    pointer-events: none;
    filter: blur(0.5px);
}
.gear--1 { top: 6%; left: 4%; font-size: 6rem; animation: gearSpin 14s linear infinite; }
.gear--2 { top: 60%; right: 5%; font-size: 4rem; animation: gearSpin 9s linear infinite reverse; }
.gear--3 { bottom: 8%; left: 10%; font-size: 3.5rem; animation: gearSpin 11s linear infinite reverse; }
.gear--4 { top: 22%; right: 12%; font-size: 3rem; animation: gearSpin 7s linear infinite; }
.gear--5 { top: 4%; right: 30%; font-size: 2rem; animation: gearSpin 5s linear infinite reverse; opacity: 0.04; }
@keyframes gearSpin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}

/* ============================================================
   СВАРОЧНАЯ ДУГА
   ============================================================ */
.arc {
    position: absolute;
    pointer-events: none;
    z-index: 7;
    border-radius: 50%;
    background: radial-gradient(circle,
        #fff 0%,
        var(--arc-color) 20%,
        rgba(158,231,255,0.6) 40%,
        transparent 70%);
    filter: blur(1.5px);
    mix-blend-mode: screen;
    animation: arcFlash 0.35s ease-out forwards;
    transform: translate(-50%, -50%);
}
@keyframes arcFlash {
    0%   { opacity: 0; transform: translate(-50%,-50%) scale(0.3); }
    30%  { opacity: 1; transform: translate(-50%,-50%) scale(1.1); }
    100% { opacity: 0; transform: translate(-50%,-50%) scale(1.6); }
}

/* Глобальная вспышка при сварке */
.flash {
    position: fixed;
    inset: 0;
    z-index: 3;
    background: radial-gradient(circle at 50% 60%, rgba(158,231,255,0.25) 0%, transparent 60%);
    pointer-events: none;
    opacity: 0;
    animation: flashPulse 0.35s ease-out forwards;
    mix-blend-mode: screen;
}
@keyframes flashPulse {
    0%   { opacity: 0; }
    30%  { opacity: 1; }
    100% { opacity: 0; }
}

/* ============================================================
   ИСКРЫ
   ============================================================ */
.spark {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    z-index: 6;
    background: radial-gradient(circle, #fff 0%, var(--spark-color) 40%, var(--spark-color-2) 80%, transparent 100%);
    box-shadow: 0 0 12px var(--spark-color), 0 0 24px var(--spark-color-2);
    animation: sparkFly 1.4s cubic-bezier(0.16,1,0.3,1) forwards;
}
@keyframes sparkFly {
    0%   { opacity: 1; transform: translate(0, 0) scale(1); }
    60%  { opacity: 1; transform: translate(calc(var(--dx, 0) * 0.7), calc(var(--dy, -40px) * 0.7)) scale(0.7); }
    100% { opacity: 0; transform: translate(var(--dx, 0), var(--dy, -40px)) scale(0.1); }
}
.spark--small {
    width: 3px; height: 3px;
    box-shadow: 0 0 6px var(--spark-color), 0 0 12px var(--spark-color-2);
}
.spark--big {
    width: 8px; height: 8px;
}

/* ============================================================
   ДЫМ
   ============================================================ */
.smoke {
    position: absolute;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: radial-gradient(circle, var(--smoke-color) 0%, transparent 70%);
    pointer-events: none;
    z-index: 4;
    filter: blur(6px);
    animation: smokeRise 3s ease-out forwards;
}
@keyframes smokeRise {
    0%   { opacity: 0.7; transform: translate(0, 0) scale(0.5); }
    50%  { opacity: 0.5; }
    100% { opacity: 0; transform: translate(var(--dx, 0), -80px) scale(2); }
}

/* ============================================================
   РОБОТ
   ============================================================ */
.robot-wrap {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 5;
    animation: robotFloat 4s ease-in-out infinite;
    cursor: pointer;
    transition: transform 0.2s;
}
.robot-wrap:active { transform: translate(-50%, 4px) scale(0.98); }
@keyframes robotFloat {
    0%,100% { transform: translate(-50%, 0); }
    50%     { transform: translate(-50%, -10px); }
}
.robot-wrap.jump {
    animation: robotJump 0.6s cubic-bezier(0.16,1,0.3,1);
}
@keyframes robotJump {
    0%   { transform: translate(-50%, 0) scale(1); }
    40%  { transform: translate(-50%, -20px) scale(1.03); }
    70%  { transform: translate(-50%, -4px) scale(0.98); }
    100% { transform: translate(-50%, 0) scale(1); }
}

.robot {
    width: 220px;
    height: auto;
    display: block;
    filter: drop-shadow(0 20px 40px rgba(0,113,227,0.35)) drop-shadow(0 0 60px rgba(0,113,227,0.15));
}

.robot .eye {
    animation: eyeBlink 5s ease-in-out infinite;
    transform-origin: center;
}
@keyframes eyeBlink {
    0%, 92%, 100% { transform: scaleY(1); }
    95%           { transform: scaleY(0.08); }
}

.robot .eye-glow {
    animation: eyeGlowPulse 2.4s ease-in-out infinite;
}
@keyframes eyeGlowPulse {
    0%,100% { opacity: 0.5; }
    50%     { opacity: 1; }
}

/* Ключ циклично качается */
.robot .wrench {
    transform-origin: 60% 90%;
    animation: wrenchWave 2.4s ease-in-out infinite;
}
@keyframes wrenchWave {
    0%,100% { transform: rotate(-10deg); }
    50%     { transform: rotate(10deg); }
}

.robot .antenna-ball {
    animation: antennaPulse 2s ease-in-out infinite;
    transform-origin: center;
}
@keyframes antennaPulse {
    0%,100% { transform: scale(1); filter: brightness(1); }
    50%     { transform: scale(1.35); filter: brightness(1.5); }
}

.robot .chest-led {
    animation: chestPulse 2s ease-in-out infinite;
    transform-origin: center;
}
@keyframes chestPulse {
    0%,100% { opacity: 1; }
    50%     { opacity: 0.4; }
}

/* ============================================================
   КОНТЕНТ
   ============================================================ */
.badge-warn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px 20px;
    border-radius: 980px;
    background: rgba(255,159,10,0.1);
    border: 1px solid rgba(255,159,10,0.25);
    color: #ffb74d;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 24px;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    backdrop-filter: blur(10px);
}
.badge-warn .dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--orange);
    animation: dotPulse 1.5s ease-in-out infinite;
}

.title {
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.15;
    margin: 0 0 16px;
    background: linear-gradient(135deg, #fff 0%, #a1a1a6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.subtitle {
    font-size: clamp(0.95rem, 1.5vw, 1.1rem);
    color: var(--ink-soft);
    line-height: 1.6;
    max-width: 560px;
    margin: 0 auto 36px;
    white-space: pre-line;
}

/* ============================================================
   ПРОГРЕСС
   ============================================================ */
.progress-card {
    display: inline-flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
    max-width: 460px;
    padding: 22px 26px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--line);
    border-radius: 22px;
    margin: 0 auto 22px;
    box-shadow:
        0 20px 40px rgba(0,0,0,0.3),
        0 0 0 1px rgba(255,255,255,0.03) inset;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    text-align: left;
}
.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.82rem;
    color: var(--ink-soft);
    font-weight: 500;
}
.progress-header strong {
    color: var(--ink);
    font-weight: 700;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.9rem;
}
.progress-bar {
    width: 100%;
    height: 8px;
    background: rgba(255,255,255,0.06);
    border-radius: 4px;
    overflow: hidden;
    position: relative;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent), var(--accent-2), var(--accent-3));
    background-size: 200% 100%;
    border-radius: 4px;
    position: relative;
    transition: width 0.6s cubic-bezier(0.16,1,0.3,1);
    animation: progressShine 3s ease-in-out infinite;
    box-shadow: 0 0 20px rgba(0,113,227,0.6);
}
@keyframes progressShine {
    0%,100% { background-position: 0% 50%; }
    50%     { background-position: 100% 50%; }
}
.progress-fill::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
    animation: progressStripe 1.8s linear infinite;
}
@keyframes progressStripe {
    from { transform: translateX(-100%); }
    to   { transform: translateX(100%); }
}

.checklist {
    display: flex;
    flex-direction: column;
    gap: 9px;
    margin-top: 6px;
}
.checklist-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85rem;
    color: var(--ink-soft);
    transition: color 0.3s;
}
.checklist-item.done { color: var(--ink); }
.checklist-item .mark {
    width: 20px; height: 20px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    flex-shrink: 0;
}
.checklist-item.done .mark {
    background: linear-gradient(135deg, var(--green), #198754);
    color: #fff;
    box-shadow: 0 4px 12px rgba(48,209,88,0.4);
}
.checklist-item .mark.pending {
    background: rgba(255,255,255,0.06);
    border: 1.5px solid rgba(255,255,255,0.15);
    position: relative;
}
.checklist-item .mark.pending::after {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--orange);
    box-shadow: 0 0 8px var(--orange);
    animation: dotPulse 1.5s ease-in-out infinite;
}

/* ============================================================
   МЕТА-ИНФО (таймеры)
   ============================================================ */
.meta-timers {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    margin: 0 auto 28px;
}
.timer-card {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 18px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--line);
    border-radius: 14px;
    font-size: 0.82rem;
    color: var(--ink-soft);
    backdrop-filter: blur(10px);
}
.timer-card i { color: var(--accent); font-size: 0.95rem; }
.timer-card .val {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 700;
    color: var(--ink);
    font-size: 0.9rem;
    letter-spacing: 0.02em;
}

/* ============================================================
   КНОПКИ
   ============================================================ */
.actions {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 8px;
}
.btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 30px;
    border-radius: 980px;
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    white-space: nowrap;
    position: relative;
    overflow: hidden;
}
.btn--primary {
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-2) 100%);
    color: #fff;
    box-shadow: 0 10px 30px rgba(0,113,227,0.4);
}
.btn--primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 40px rgba(0,113,227,0.55);
    color: #fff;
}
.btn--primary::before {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s;
}
.btn--primary:hover::before { left: 100%; }

.btn--ghost {
    background: rgba(255,255,255,0.05);
    color: var(--ink);
    border: 1px solid rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
}
.btn--ghost:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.3);
    transform: translateY(-3px);
    color: var(--ink);
}
.btn i { font-size: 1.05rem; }

.until-note {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-top: 28px;
    padding: 12px 22px;
    background: rgba(0,113,227,0.08);
    border: 1px solid rgba(0,113,227,0.25);
    border-radius: 980px;
    color: #6ea8ff;
    font-size: 0.88rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
}
.until-note i { color: var(--accent); }
.until-note strong { color: var(--ink); font-weight: 700; }

/* Fade-in */
.fade-in {
    opacity: 0;
    transform: translateY(16px);
    animation: fadeUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
.d1 { animation-delay: 0.1s; }
.d2 { animation-delay: 0.2s; }
.d3 { animation-delay: 0.3s; }
.d4 { animation-delay: 0.4s; }
.d5 { animation-delay: 0.5s; }

.watermark {
    position: fixed;
    bottom: 20px;
    right: 24px;
    font-size: 0.7rem;
    color: rgba(255,255,255,0.15);
    font-family: 'JetBrains Mono', monospace;
    z-index: 10;
    letter-spacing: 0.05em;
}

/* Мобильная */
@media (max-width: 640px) {
    .scene { height: 260px; }
    .robot { width: 170px; }
    .gear--1 { font-size: 4rem; }
    .gear--2 { font-size: 2.8rem; }
    .gear--3 { font-size: 2.4rem; }
    .gear--4 { font-size: 2rem; }
    .gear--5 { display: none; }
    .progress-card { padding: 18px 20px; }
    .header-status { font-size: 0.7rem; padding: 5px 10px; }
    .btn { padding: 12px 24px; font-size: 0.9rem; }
    .timer-card { font-size: 0.75rem; padding: 8px 14px; }
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

<div class="bg-layer">
    <div class="bg-blob bg-blob--1"></div>
    <div class="bg-blob bg-blob--2"></div>
    <div class="bg-blob bg-blob--3"></div>
</div>
<div class="bg-grid"></div>

<!-- Падающий код -->
<div class="code-rain" id="codeRain"></div>

<!-- Частицы -->
<div class="particles" id="particles"></div>

<!-- Плавающие запчасти -->
<div id="partsLayer" style="position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:1;"></div>

<header class="header">
    <div class="header-inner">
        <a href="/" class="logo">
            <span class="logo-mark"><i class="bi bi-mortarboard-fill"></i></span>
            АИС-расписание.РФ
        </a>
        <div class="header-status">
            <span class="pulse-dot"></span>
            Идут тех. работы        </div>
    </div>
</header>

<main class="page">

    <div class="scene fade-in d1" id="scene">
        <div class="scene-inner" id="sceneInner">

            <i class="bi bi-gear-fill gear gear--1"></i>
            <i class="bi bi-gear-fill gear gear--2"></i>
            <i class="bi bi-gear-fill gear gear--3"></i>
            <i class="bi bi-gear-fill gear gear--4"></i>
            <i class="bi bi-gear-fill gear gear--5"></i>

            <div class="floor"></div>
            <div class="floor-ring"></div>
            <div class="floor-ring"></div>
            <div class="floor-ring"></div>

            <div class="robot-wrap" id="robotWrap">
                <svg class="robot" viewBox="0 0 200 220" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="100" cy="212" rx="55" ry="6" fill="rgba(0,0,0,0.4)"/>

                    <line x1="100" y1="38" x2="100" y2="20" stroke="var(--robot-body-dark)" stroke-width="3" stroke-linecap="round"/>
                    <circle class="antenna-ball" cx="100" cy="16" r="6" fill="var(--accent)"
                            style="filter: drop-shadow(0 0 8px var(--accent));"/>

                    <rect x="60" y="38" width="80" height="60" rx="14"
                          fill="var(--robot-body)" stroke="var(--robot-body-dark)" stroke-width="3"/>

                    <rect x="52" y="58" width="8" height="20" rx="3" fill="var(--robot-body-dark)"/>
                    <rect x="140" y="58" width="8" height="20" rx="3" fill="var(--robot-body-dark)"/>

                    <ellipse class="eye-glow" cx="82" cy="68" rx="10" ry="10" fill="var(--robot-eye-glow)" style="filter: blur(6px);"/>
                    <ellipse class="eye-glow" cx="118" cy="68" rx="10" ry="10" fill="var(--robot-eye-glow)" style="filter: blur(6px);"/>
                    <ellipse class="eye" cx="82" cy="68" rx="7" ry="7" fill="var(--robot-eye)"
                             style="filter: drop-shadow(0 0 6px var(--robot-eye));"/>
                    <ellipse class="eye" cx="118" cy="68" rx="7" ry="7" fill="var(--robot-eye)"
                             style="filter: drop-shadow(0 0 6px var(--robot-eye));"/>
                    <circle cx="80" cy="66" r="2.5" fill="#fff"/>
                    <circle cx="116" cy="66" r="2.5" fill="#fff"/>

                    <rect x="88" y="84" width="24" height="4" rx="2" fill="var(--robot-body-dark)"/>

                    <rect x="65" y="102" width="70" height="70" rx="12"
                          fill="var(--robot-body)" stroke="var(--robot-body-dark)" stroke-width="3"/>

                    <circle cx="100" cy="125" r="11" fill="var(--robot-body-dark)"/>
                    <circle class="chest-led" cx="100" cy="125" r="6" fill="var(--green)"
                            style="filter: drop-shadow(0 0 8px var(--green));"/>

                    <rect x="80" y="145" width="8" height="4" rx="2" fill="var(--robot-body-dark)"/>
                    <rect x="92" y="145" width="8" height="4" rx="2" fill="var(--robot-body-dark)"/>
                    <rect x="104" y="145" width="8" height="4" rx="2" fill="var(--robot-body-dark)"/>

                    <rect x="52" y="108" width="14" height="40" rx="6"
                          fill="var(--robot-body)" stroke="var(--robot-body-dark)" stroke-width="3"/>

                    <rect x="134" y="108" width="14" height="40" rx="6"
                          fill="var(--robot-body)" stroke="var(--robot-body-dark)" stroke-width="3"/>
                    <g class="wrench" style="filter: drop-shadow(0 0 6px rgba(255,214,10,0.5));">
                        <rect x="138" y="140" width="6" height="42" rx="3" fill="#86868b"/>
                        <path d="M 130 138 Q 141 126 152 138 L 149 144 Q 141 136 133 144 Z" fill="#86868b"/>
                        <path d="M 132 182 Q 141 172 150 182 L 148 187 Q 141 179 134 187 Z" fill="#86868b"/>
                    </g>

                    <rect x="80" y="172" width="14" height="30" rx="6"
                          fill="var(--robot-body)" stroke="var(--robot-body-dark)" stroke-width="3"/>
                    <rect x="106" y="172" width="14" height="30" rx="6"
                          fill="var(--robot-body)" stroke="var(--robot-body-dark)" stroke-width="3"/>

                    <rect x="76" y="198" width="22" height="10" rx="4" fill="var(--robot-body-dark)"/>
                    <rect x="102" y="198" width="22" height="10" rx="4" fill="var(--robot-body-dark)"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="badge-warn fade-in d2">
        <span class="dot"></span>
        Технические работы
    </div>

    <h1 class="title fade-in d2">
        <?= htmlspecialchars($title) ?>
    </h1>

    <p class="subtitle fade-in d3">
        <?= htmlspecialchars($message) ?>
    </p>

    <div class="progress-card fade-in d3">
        <div class="progress-header">
            <span>Прогресс обновления</span>
            <strong id="progressPct">78%</strong>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 78%;"></div>
        </div>

        <div class="checklist">
            <div class="checklist-item done">
                <span class="mark"><i class="bi bi-check-lg"></i></span>
                Резервное копирование базы
            </div>
            <div class="checklist-item done">
                <span class="mark"><i class="bi bi-check-lg"></i></span>
                Обновление серверной части
            </div>
            <div class="checklist-item">
                <span class="mark pending"></span>
                Проверка работоспособности
            </div>
            <div class="checklist-item">
                <span class="mark pending"></span>
                Финальные тесты
            </div>
        </div>
    </div>

    <!-- Таймеры -->
    <div class="meta-timers fade-in d4">
        <div class="timer-card">
            <i class="bi bi-hourglass-split"></i>
            <span>Идёт уже:</span>
            <span class="val" id="timerElapsed">00:00:00</span>
        </div>
        <?php if ($untilTs > 0): ?>
        <div class="timer-card">
            <i class="bi bi-stopwatch-fill"></i>
            <span>До конца:</span>
            <span class="val" id="timerRemaining" data-until="<?= (int)$untilTs ?>">—</span>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($untilText): ?>
        <div class="until-note fade-in d4">
            <i class="bi bi-clock-fill"></i>
            Ожидаем вернуться: <strong><?= htmlspecialchars($untilText) ?></strong>
        </div>
    <?php endif; ?>

    <div class="actions fade-in d5">
        <a href="/logout.php" class="btn btn--primary">
            <i class="bi bi-box-arrow-in-right"></i>
            Войти (для администраторов)
        </a>
        <a href="javascript:location.reload()" class="btn btn--ghost">
            <i class="bi bi-arrow-clockwise"></i>
            Проверить ещё раз
        </a>
    </div>

</main>

<div class="watermark">АИС-расписание.РФ · 503</div>

<script>
(function() {
    'use strict';

    /* ============================================================
       ПАДАЮЩИЙ КОД НА ФОНЕ
       ============================================================ */
    const rain = document.getElementById('codeRain');
    if (rain) {
        const snippets = [
            'UPDATE lessons SET ...',
            'SELECT * FROM attendance',
            'INSERT INTO session_sheets',
            'backup --start',
            'chmod 755 /var/www',
            'systemctl restart php-fpm',
            'mysqldump --all-databases',
            'git pull origin main',
            'composer install',
            'npm run build',
            '$ rake db:migrate',
            'docker compose up -d',
            'psql -U admin',
            'rsync -avz',
            'nano config.php',
            '# [OK] Done in 0.02s',
        ];
        const colsCount = Math.floor(window.innerWidth / 90);

        for (let i = 0; i < colsCount; i++) {
            const col = document.createElement('div');
            col.className = 'code-col';
            col.style.left = (i * 90 + Math.random() * 30) + 'px';
            col.style.animationDuration = (10 + Math.random() * 12) + 's';
            col.style.animationDelay = -Math.random() * 15 + 's';

            const lines = 12 + Math.floor(Math.random() * 8);
            for (let j = 0; j < lines; j++) {
                const span = document.createElement('span');
                span.textContent = snippets[Math.floor(Math.random() * snippets.length)];
                col.appendChild(span);
            }
            rain.appendChild(col);
        }
    }

    /* ============================================================
       ЧАСТИЦЫ
       ============================================================ */
    const particlesBox = document.getElementById('particles');
    if (particlesBox) {
        const colors = ['#0071e3', '#5e5ce6', '#bf5af2', '#30d158'];

        function createParticle() {
            const p = document.createElement('div');
            p.className = 'particle';
            p.style.left = Math.random() * 100 + '%';
            p.style.width = p.style.height = (1.5 + Math.random() * 2.5) + 'px';

            const color = colors[Math.floor(Math.random() * colors.length)];
            p.style.background = color;
            p.style.boxShadow = '0 0 6px ' + color;

            const duration = 8 + Math.random() * 12;
            p.style.animationDuration = duration + 's';
            p.style.animationDelay = Math.random() * 2 + 's';

            particlesBox.appendChild(p);
            setTimeout(() => p.remove(), (duration + 2) * 1000);
        }

        for (let i = 0; i < 25; i++) setTimeout(createParticle, i * 200);
        setInterval(createParticle, 900);
    }

    /* ============================================================
       ПЛАВАЮЩИЕ ЗАПЧАСТИ
       ============================================================ */
    const partsLayer = document.getElementById('partsLayer');
    if (partsLayer) {
        const icons = ['bi-gear-fill', 'bi-nut-fill', 'bi-lightning-charge-fill',
                       'bi-cpu-fill', 'bi-hdd-fill', 'bi-tools', 'bi-wrench-adjustable'];

        function createPart() {
            const p = document.createElement('div');
            p.className = 'part';
            p.style.left = Math.random() * 100 + '%';
            p.style.bottom = '-40px';
            p.style.fontSize = (0.8 + Math.random() * 1.5) + 'rem';

            const icon = icons[Math.floor(Math.random() * icons.length)];
            p.innerHTML = `<i class="bi ${icon}"></i>`;

            const duration = 14 + Math.random() * 12;
            p.style.animationDuration = duration + 's';

            partsLayer.appendChild(p);
            setTimeout(() => p.remove(), (duration + 2) * 1000);
        }

        for (let i = 0; i < 12; i++) setTimeout(createPart, i * 700);
        setInterval(createPart, 1800);
    }

    /* ============================================================
       ПРОГРЕСС
       ============================================================ */
    const fill = document.getElementById('progressFill');
    const pct  = document.getElementById('progressPct');
    let current = 78;
    fill.style.width = current + '%';
    pct.textContent = current + '%';

    (function tick() {
        if (current < 92) {
            current += 1;
            fill.style.width = current + '%';
            pct.textContent = current + '%';
        }
        setTimeout(tick, 1500 + Math.random() * 1500);
    })();

    /* ============================================================
       РОБОТ: СВАРКА + ИСКРЫ + ДЫМ
       ============================================================ */
    const scene = document.getElementById('scene');
    const robotWrap = document.getElementById('robotWrap');

    function wrenchPoint() {
        const rect = scene.getBoundingClientRect();
        return {
            x: rect.width / 2 + 70 + (Math.random() - 0.5) * 15,
            y: rect.height * 0.78 + (Math.random() - 0.5) * 15,
        };
    }

    function spawnSpark(big = false) {
        if (!scene) return;
        const { x, y } = wrenchPoint();

        const spark = document.createElement('div');
        spark.className = 'spark' + (big ? ' spark--big' : ' spark--small');

        const size = big ? 8 : 3 + Math.random() * 3;
        spark.style.width = spark.style.height = size + 'px';

        spark.style.left = x + 'px';
        spark.style.top  = y + 'px';
        spark.style.setProperty('--dx', (Math.random() - 0.5) * 130 + 'px');
        spark.style.setProperty('--dy', (-40 - Math.random() * 90) + 'px');

        scene.appendChild(spark);
        setTimeout(() => spark.remove(), 1500);
    }

    function spawnArc() {
        if (!scene) return;
        const { x, y } = wrenchPoint();

        const arc = document.createElement('div');
        arc.className = 'arc';
        arc.style.left = x + 'px';
        arc.style.top  = y + 'px';
        const size = 40 + Math.random() * 30;
        arc.style.width = arc.style.height = size + 'px';

        scene.appendChild(arc);
        setTimeout(() => arc.remove(), 500);

        // Глобальная вспышка
        const flash = document.createElement('div');
        flash.className = 'flash';
        document.body.appendChild(flash);
        setTimeout(() => flash.remove(), 400);
    }

    function spawnSmoke() {
        if (!scene) return;
        const { x, y } = wrenchPoint();

        const smoke = document.createElement('div');
        smoke.className = 'smoke';
        smoke.style.left = x + 'px';
        smoke.style.top  = y + 'px';
        smoke.style.setProperty('--dx', (Math.random() - 0.5) * 40 + 'px');

        scene.appendChild(smoke);
        setTimeout(() => smoke.remove(), 3000);
    }

    // Цикл «сварки»: каждые ~1.6 сек пачка искр, каждые ~2.4 сек дуга + вспышка
    let sparkIntensity = 0;
    function weldingCycle() {
        // 1. дуга + вспышка
        spawnArc();
        // 2. сразу после неё — пачка искр
        for (let i = 0; i < 6 + Math.floor(Math.random() * 4); i++) {
            setTimeout(() => spawnSpark(i < 2), i * 35);
        }
        // 3. немного дыма
        setTimeout(spawnSmoke, 150);
        if (Math.random() > 0.5) setTimeout(spawnSmoke, 400);
    }

    // Запуск цикла с разными интервалами
    (function loopWelding() {
        weldingCycle();
        setTimeout(loopWelding, 1400 + Math.random() * 900);
    })();

    /* ============================================================
       3D-НАКЛОН
       ============================================================ */
    const sceneInner = document.getElementById('sceneInner');
    if (scene && sceneInner) {
        scene.addEventListener('mousemove', (e) => {
            const rect = scene.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            sceneInner.style.transform =
                `rotateY(${x * 12}deg) rotateX(${-y * 10}deg) translateZ(20px)`;
        });
        scene.addEventListener('mouseleave', () => {
            sceneInner.style.transform = '';
        });
    }

    /* ============================================================
       КЛИК ПО РОБОТУ — «прыжок» + салют искр
       ============================================================ */
    if (robotWrap) {
        robotWrap.addEventListener('click', () => {
            robotWrap.classList.remove('jump');
            void robotWrap.offsetWidth;
            robotWrap.classList.add('jump');

            // Салют искр вверх
            for (let i = 0; i < 18; i++) {
                setTimeout(() => spawnSpark(i < 4), i * 30);
            }
            spawnArc();
        });
    }

    /* ============================================================
       ТАЙМЕРЫ
       ============================================================ */
    const startTime = Date.now();
    const timerElapsed = document.getElementById('timerElapsed');
    const timerRemaining = document.getElementById('timerRemaining');

    function pad(n) { return String(n).padStart(2, '0'); }
    function fmtHMS(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        return pad(h) + ':' + pad(m) + ':' + pad(s);
    }

    function updateTimers() {
        if (timerElapsed) {
            timerElapsed.textContent = fmtHMS((Date.now() - startTime) / 1000);
        }
        if (timerRemaining) {
            const until = parseInt(timerRemaining.dataset.until, 10) * 1000;
            const left = Math.max(0, Math.floor((until - Date.now()) / 1000));
            if (left <= 0) {
                timerRemaining.textContent = 'скоро!';
                timerRemaining.style.color = 'var(--green)';
            } else {
                timerRemaining.textContent = fmtHMS(left);
            }
        }
    }
    updateTimers();
    setInterval(updateTimers, 1000);

    /* ============================================================
       АВТООБНОВЛЕНИЕ
       ============================================================ */
    setTimeout(() => location.reload(), 60000);

})();
</script>

</body>
</html>