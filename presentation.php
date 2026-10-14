<?php
require_once 'config/database.php';
// Публичная страница — без header.php и footer.php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>АИС-расписание.РФ — презентация системы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* ============================================================
   ТЕМЫ
   ============================================================ */
:root {
    --ap-bg: #ffffff;
    --ap-bg-alt: #fbfbfd;
    --ap-bg-soft: #f5f5f7;
    --ap-dark: #1d1d1f;
    --ap-dark-2: #4a4a4c;
    --ap-gray: #86868b;
    --ap-gray-light: #d2d2d7;
    --ap-border: #eaeaea;
    --ap-border-soft: #f0f0f0;
    --ap-text-on-dark: #f5f5f7;
    --ap-text-muted-dark: #a1a1a6;
    --ap-shadow: rgba(0,0,0,0.08);
    --ap-shadow-strong: rgba(0,0,0,0.15);
    --ap-accent: #0071e3;
    --ap-accent-hover: #0077ed;
    --ap-green: #30d158;
    --ap-red: #ff453a;
    --ap-orange: #ff9f0a;
    --ap-purple: #bf5af2;
    --ap-glow-1: rgba(0,113,227,0.08);
    --ap-glow-2: rgba(191,90,242,0.08);
    --ap-grid: rgba(0,113,227,0.03);
    --tg-bg: #17212b;
    --tg-header: #242f3d;
    --tg-bot-msg: #242f3d;
    --tg-user-msg: #2b5278;
    --tg-blue: #5eb5f7;
    --tg-pattern-op: 0.04;
}
[data-theme="dark"] {
    --ap-bg: #000000;
    --ap-bg-alt: #0a0a0c;
    --ap-bg-soft: #141416;
    --ap-dark: #f5f5f7;
    --ap-dark-2: #d2d2d7;
    --ap-gray: #a1a1a6;
    --ap-gray-light: #4a4a4c;
    --ap-border: #232325;
    --ap-border-soft: #1c1c1e;
    --ap-shadow: rgba(0,0,0,0.5);
    --ap-shadow-strong: rgba(0,0,0,0.7);
    --ap-accent: #0a84ff;
    --ap-accent-hover: #3395ff;
    --ap-glow-1: rgba(10,132,255,0.15);
    --ap-glow-2: rgba(191,90,242,0.15);
    --ap-grid: rgba(10,132,255,0.06);
    --tg-bg: #0e1621;
    --tg-header: #17212b;
    --tg-pattern-op: 0.06;
}

html, body {
    margin: 0;
    padding: 0;
    background: var(--ap-bg);
    color: var(--ap-dark);
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, sans-serif;
    overflow-x: hidden;
    transition: background-color 0.4s ease, color 0.4s ease;
}
* { box-sizing: border-box; }

/* ============================================================
   КНОПКА «ВОЙТИ»
   ============================================================ */
.top-login {
    position: fixed;
    top: 24px;
    left: 24px;
    z-index: 9998;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--ap-bg);
    border: 1px solid var(--ap-border);
    border-radius: 980px;
    color: var(--ap-dark);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 8px 24px var(--ap-shadow);
    transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
}
.top-login:hover {
    background: var(--ap-accent);
    color: #fff;
    border-color: var(--ap-accent);
    transform: translateY(-2px);
    box-shadow: 0 14px 32px rgba(0,113,227,0.35);
}
.top-login i { font-size: 1.1rem; }
@media (max-width: 767px) { .top-login { top: 16px; left: 16px; padding: 8px 16px; font-size: 0.85rem; } }

/* ============================================================
   ПЕРЕКЛЮЧАТЕЛЬ ТЕМЫ
   ============================================================ */
.theme-toggle {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 9998;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--ap-bg);
    border: 1px solid var(--ap-border);
    color: var(--ap-dark);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: 0 8px 24px var(--ap-shadow);
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
}
.theme-toggle:hover {
    transform: scale(1.1) rotate(15deg);
    border-color: var(--ap-accent);
    color: var(--ap-accent);
}
.theme-toggle i { position: absolute; transition: opacity 0.3s, transform 0.3s; }
[data-theme="dark"] .icon-sun { opacity: 0; transform: rotate(90deg); }
[data-theme="dark"] .icon-moon { opacity: 1; transform: rotate(0); }
:root .icon-sun { opacity: 1; transform: rotate(0); }
:root .icon-moon { opacity: 0; transform: rotate(-90deg); }
@media (max-width: 767px) { .theme-toggle { top: 16px; right: 16px; width: 42px; height: 42px; } }

/* ============================================================
   ПРОГРЕСС + КУРСОР
   ============================================================ */
.scroll-progress {
    position: fixed;
    top: 0; left: 0;
    height: 3px; width: 0%;
    background: linear-gradient(90deg, #0071e3, #bf5af2, #30d158);
    z-index: 9999;
    transition: width 0.1s ease;
    box-shadow: 0 0 20px rgba(0,113,227,0.6);
    pointer-events: none;
}
.cursor-glow {
    position: fixed;
    width: 500px; height: 500px;
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
    background: radial-gradient(circle, var(--ap-glow-1) 0%, transparent 60%);
    transform: translate(-50%,-50%);
    transition: opacity 0.3s;
    opacity: 0;
    mix-blend-mode: multiply;
}
[data-theme="dark"] .cursor-glow { mix-blend-mode: screen; }
.cursor-glow.active { opacity: 1; }

/* ============================================================
   БАЗА
   ============================================================ */
.ap-page {
    position: relative;
    overflow-x: hidden;
    width: 100%;
}
.ap-container {
    max-width: 1120px;
    margin: 0 auto;
    width: 100%;
    padding: 0 24px;
    position: relative;
    z-index: 1;
}
.ap-center { text-align: center; }
.ap-h2 {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.02em;
    margin: 0 0 24px;
    color: var(--ap-dark);
}
.ap-lead {
    font-size: clamp(1.1rem, 2vw, 1.5rem);
    line-height: 1.5;
    color: var(--ap-gray);
    max-width: 800px;
    margin: 0 auto 32px;
}
.ap-section { padding: clamp(60px, 12vw, 140px) 0; position: relative; }
.ap-section--dark { background: var(--ap-dark); color: var(--ap-text-on-dark); }
.ap-section--dark .ap-h2 { color: var(--ap-text-on-dark); }
.ap-section--dark .ap-lead { color: var(--ap-text-muted-dark); }
.ap-section--gray { background: var(--ap-bg-alt); }
.ap-section--accent {
    background: linear-gradient(135deg, #0071e3 0%, #6610f2 50%, #bf5af2 100%);
    background-size: 200% 200%;
    animation: gradientShift 10s ease-in-out infinite;
    color: #fff;
}
.ap-section--accent .ap-h2,
.ap-section--accent .ap-lead { color: #fff; }
@keyframes gradientShift {
    0%,100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

/* HERO */
.ap-hero {
    padding: clamp(100px, 14vw, 180px) 0 80px;
    text-align: center;
    background: linear-gradient(180deg, var(--ap-bg) 0%, var(--ap-bg-soft) 100%);
    position: relative;
    overflow: hidden;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ap-hero::before {
    content: '';
    position: absolute;
    inset: -50%;
    background:
        radial-gradient(circle at 20% 30%, var(--ap-glow-1) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, var(--ap-glow-2) 0%, transparent 40%);
    animation: auroraMove 20s ease-in-out infinite;
    pointer-events: none;
    z-index: 0;
}
@keyframes auroraMove {
    0%,100% { transform: translate(0,0) scale(1); }
    33% { transform: translate(5%,-5%) scale(1.1); }
    66% { transform: translate(-5%,5%) scale(0.95); }
}
.ap-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(var(--ap-grid) 1px, transparent 1px),
        linear-gradient(90deg, var(--ap-grid) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
    pointer-events: none;
    z-index: 0;
}
.ap-hero-content { position: relative; z-index: 2; transform-style: preserve-3d; }
.ap-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--ap-accent);
    margin-bottom: 24px;
    padding: 8px 20px;
    background: rgba(0,113,227,0.08);
    border: 1px solid rgba(0,113,227,0.15);
    border-radius: 980px;
    backdrop-filter: blur(10px);
    animation: eyebrowPulse 3s ease-in-out infinite;
}
@keyframes eyebrowPulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(0,113,227,0.2); }
    50% { box-shadow: 0 0 0 12px rgba(0,113,227,0); }
}
.ap-hero-title {
    font-size: clamp(2.75rem, 8vw, 6.5rem);
    font-weight: 700;
    line-height: 1.02;
    letter-spacing: -0.045em;
    margin: 0 auto 28px;
    max-width: 1000px;
    color: var(--ap-dark);
}
.ap-hero-title .ap-color {
    background: linear-gradient(120deg, #0071e3 0%, #6610f2 50%, #bf5af2 100%);
    background-size: 200% 200%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: gradientShift 6s ease-in-out infinite;
    display: inline-block;
}
.ap-hero-title .word {
    display: inline-block;
    opacity: 0;
    transform: translateY(20px) rotateX(-45deg);
    animation: wordIn 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
    transform-origin: bottom;
}
@keyframes wordIn {
    to { opacity: 1; transform: translateY(0) rotateX(0); }
}
.ap-hero-subtitle {
    font-size: clamp(1.15rem, 2.2vw, 1.65rem);
    line-height: 1.4;
    color: var(--ap-gray);
    max-width: 720px;
    margin: 0 auto 48px;
}
.ap-hero-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 60px;
}

/* КНОПКИ */
.ap-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 1.0625rem;
    font-weight: 500;
    padding: 16px 32px;
    border-radius: 980px;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
    border: none;
    cursor: pointer;
    font-family: inherit;
    position: relative;
    overflow: hidden;
    isolation: isolate;
}
.ap-btn--primary {
    background: var(--ap-accent);
    color: #fff;
    box-shadow: 0 8px 24px rgba(0,113,227,0.3);
}
.ap-btn--primary:hover {
    background: var(--ap-accent-hover);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 14px 32px rgba(0,113,227,0.4);
}
.ap-btn--ghost {
    background: var(--ap-bg);
    color: var(--ap-accent);
    border: 1px solid var(--ap-accent);
}
.ap-btn--ghost:hover {
    background: var(--ap-accent);
    color: #fff;
    transform: translateY(-2px);
}
.ap-btn--sm { padding: 8px 16px; font-size: 0.85rem; }
.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    transform: scale(0);
    animation: rippleEffect 0.6s ease-out;
    pointer-events: none;
}
@keyframes rippleEffect { to { transform: scale(4); opacity: 0; } }

/* ============================================================
   ИНТЕРАКТИВНАЯ ДЕМО
   ============================================================ */
.ap-product {
    border-radius: 24px;
    overflow: hidden;
    background: var(--ap-bg);
    box-shadow: 0 4px 12px var(--ap-shadow), 0 30px 80px var(--ap-shadow-strong);
    border: 1px solid var(--ap-border);
    max-width: 980px;
    margin: 0 auto;
    transition: transform 0.5s cubic-bezier(0.16,1,0.3,1), box-shadow 0.5s;
    transform-style: preserve-3d;
    will-change: transform;
    position: relative;
}
.ap-product::after {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    pointer-events: none;
    z-index: 5;
    transition: left 0.8s;
}
.ap-product:hover::after { left: 100%; }
.ap-product-header {
    height: 38px;
    background: var(--ap-bg-soft);
    display: flex;
    align-items: center;
    padding: 0 14px;
    gap: 8px;
    border-bottom: 1px solid var(--ap-border);
}
.ap-dot { width: 12px; height: 12px; border-radius: 50%; }
.ap-dot--r { background: #ff5f57; }
.ap-dot--y { background: #febc2e; }
.ap-dot--g { background: #28c840; }
.ap-product-title {
    margin-left: 8px;
    font-size: 0.75rem;
    color: var(--ap-gray);
    font-family: "SF Mono", Monaco, monospace;
}
.ap-product-body {
    padding: 24px;
    background: var(--ap-bg);
    min-height: 340px;
}

/* ЖИВОЙ ИНТЕРФЕЙС */
.lv { font-size: 0.85rem; color: var(--ap-dark); }
.lv + .lv { margin-top: 10px; }
.lv-lesson {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 12px 14px;
    border-radius: 12px;
    background: var(--ap-bg);
    border: 1px solid var(--ap-border);
    border-left: 3px solid var(--ap-accent);
    transition: all 0.25s;
    cursor: pointer;
}
.lv-lesson:hover { box-shadow: 0 8px 24px var(--ap-shadow); transform: translateX(4px); }
.lv-lesson--green { border-left-color: var(--ap-green); }
.lv-lesson--orange { border-left-color: var(--ap-orange); }
.lv-lesson--selected { background: rgba(0,113,227,0.06); box-shadow: 0 0 0 3px rgba(0,113,227,0.15); }
.lv-lesson-time {
    background: var(--ap-dark);
    color: var(--ap-bg);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
}
.lv-lesson-body { flex: 1; min-width: 0; }
.lv-lesson-title { font-size: 0.88rem; font-weight: 600; color: var(--ap-dark); margin-bottom: 3px; }
.lv-lesson-meta { font-size: 0.75rem; color: var(--ap-gray); display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.lv-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 600; }
.lv-badge--blue { background: #cfe2ff; color: #084298; }
.lv-badge--green { background: #d1e7dd; color: #0f5132; }
.lv-badge--red { background: #f8d7da; color: #842029; }
.lv-badge--orange { background: #ffe5b4; color: #b25c00; }
.lv-badge--gray { background: #e9ecef; color: #495057; }
[data-theme="dark"] .lv-badge--blue { background: rgba(10,132,255,0.2); color: #6ea8ff; }
[data-theme="dark"] .lv-badge--green { background: rgba(48,209,88,0.2); color: #6ee7a3; }
[data-theme="dark"] .lv-badge--red { background: rgba(255,69,58,0.2); color: #ff7b6e; }
[data-theme="dark"] .lv-badge--orange { background: rgba(255,159,10,0.2); color: #ffb74d; }
[data-theme="dark"] .lv-badge--gray { background: rgba(134,134,139,0.2); color: #b8b8bd; }

.lv-att-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.lv-att-table th {
    text-align: left;
    padding: 8px 10px;
    background: var(--ap-bg-soft);
    color: var(--ap-gray);
    font-weight: 600;
    font-size: 0.72rem;
    text-transform: uppercase;
}
.lv-att-table td {
    padding: 8px 10px;
    border-bottom: 1px solid var(--ap-border-soft);
    color: var(--ap-dark);
}
.lv-att-table tr.present { background: rgba(48,209,88,0.08); }
.lv-att-table tr.absent { background: rgba(255,69,58,0.08); }

.lv-radio-group { display: inline-flex; gap: 4px; }
.lv-radio {
    width: 30px; height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: 1.5px solid var(--ap-gray-light);
    background: var(--ap-bg);
    color: var(--ap-gray);
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9rem;
    user-select: none;
}
.lv-radio:hover { border-color: var(--ap-dark); color: var(--ap-dark); transform: scale(1.1); }
.lv-radio:active { transform: scale(0.95); }
.lv-radio--check-active { background: var(--ap-green); border-color: var(--ap-green); color: #fff; box-shadow: 0 4px 12px rgba(48,209,88,0.4); }
.lv-radio--check-active:hover { color: #fff; }
.lv-radio--x-active { background: var(--ap-red); border-color: var(--ap-red); color: #fff; box-shadow: 0 4px 12px rgba(255,69,58,0.4); }
.lv-radio--x-active:hover { color: #fff; }
.lv-radio--dash-active { background: var(--ap-gray); border-color: var(--ap-gray); color: #fff; }

.lv-switch {
    width: 44px; height: 24px;
    background: var(--ap-gray-light);
    border-radius: 24px;
    position: relative;
    display: inline-block;
    cursor: pointer;
    transition: background 0.3s;
}
.lv-switch::after {
    content: '';
    width: 20px; height: 20px;
    background: #fff;
    border-radius: 50%;
    position: absolute;
    top: 2px; left: 2px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: left 0.3s cubic-bezier(0.16,1,0.3,1);
}
.lv-switch--on { background: linear-gradient(135deg, var(--ap-green), #198754); }
.lv-switch--on::after { left: 22px; }

.lv-stat {
    padding: 12px;
    background: var(--ap-bg);
    border-radius: 12px;
    border: 1px solid var(--ap-border);
    text-align: center;
    transition: all 0.3s;
}
.lv-stat.lv-stat--bump { animation: statBump 0.4s cubic-bezier(0.16,1,0.3,1); }
@keyframes statBump {
    0% { transform: scale(1); }
    50% { transform: scale(1.06); box-shadow: 0 8px 24px rgba(0,113,227,0.2); }
    100% { transform: scale(1); }
}
.lv-stat-num { font-size: 1.5rem; font-weight: 700; line-height: 1; color: var(--ap-dark); margin-bottom: 4px; }
.lv-stat-num--green { color: var(--ap-green); }
.lv-stat-num--red { color: var(--ap-red); }
.lv-stat-num--blue { color: var(--ap-accent); }
.lv-stat-label { font-size: 0.7rem; color: var(--ap-gray); font-weight: 500; }

.lv-matrix { width: 100%; border-collapse: collapse; font-size: 0.78rem; }
.lv-matrix th {
    padding: 6px 4px;
    background: var(--ap-bg-soft);
    color: var(--ap-gray);
    font-weight: 600;
    font-size: 0.68rem;
    text-align: center;
}
.lv-matrix th:first-child { text-align: left; padding-left: 10px; }
.lv-matrix td {
    padding: 7px 4px;
    text-align: center;
    border-bottom: 1px solid var(--ap-border-soft);
    font-weight: 600;
}
.lv-matrix td:first-child {
    text-align: left;
    padding-left: 10px;
    font-weight: 500;
    color: var(--ap-dark);
    font-size: 0.78rem;
}
.lv-mark-yes { color: var(--ap-green); }
.lv-mark-no { color: var(--ap-red); }
.lv-mark-na { color: var(--ap-gray-light); }

.lv-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--ap-border);
    overflow-x: auto;
}
.lv-tab {
    padding: 10px 16px;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--ap-gray);
    border: none;
    background: none;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    white-space: nowrap;
    font-family: inherit;
    position: relative;
}
.lv-tab:hover { color: var(--ap-dark); }
.lv-tab.active { color: var(--ap-accent); border-bottom-color: var(--ap-accent); }

/* ============================================================
   PWA-БЛОК
   ============================================================ */
.pwa-section {
    background: linear-gradient(180deg, var(--ap-bg) 0%, var(--ap-bg-alt) 100%);
    position: relative;
    overflow: hidden;
}
.pwa-section::before {
    content: '';
    position: absolute;
    top: -30%; right: -10%;
    width: 600px; height: 600px;
    background: radial-gradient(circle, var(--ap-glow-1) 0%, transparent 60%);
    pointer-events: none;
}
.pwa-section::after {
    content: '';
    position: absolute;
    bottom: -30%; left: -10%;
    width: 500px; height: 500px;
    background: radial-gradient(circle, var(--ap-glow-2) 0%, transparent 60%);
    pointer-events: none;
}

.pwa-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    position: relative;
    z-index: 1;
}
@media (max-width: 900px) {
    .pwa-grid { grid-template-columns: 1fr; gap: 40px; }
}

/* Телефон */
.pwa-phone-wrapper {
    position: relative;
    display: flex;
    justify-content: center;
    perspective: 1200px;
}
.pwa-phone {
    width: 300px;
    height: 600px;
    background: #0d0d0d;
    border-radius: 42px;
    padding: 12px;
    box-shadow:
        0 30px 80px rgba(0,0,0,0.35),
        0 0 0 1px rgba(255,255,255,0.08) inset,
        0 0 100px rgba(0,113,227,0.25);
    position: relative;
    transform: rotateY(-8deg) rotateX(4deg);
    transition: transform 0.6s cubic-bezier(0.16,1,0.3,1);
    animation: phoneFloat 7s ease-in-out infinite;
}
.pwa-phone:hover {
    transform: rotateY(0) rotateX(0) scale(1.03);
}
@keyframes phoneFloat {
    0%,100% { transform: rotateY(-8deg) rotateX(4deg) translateY(0); }
    50%     { transform: rotateY(-8deg) rotateX(4deg) translateY(-14px); }
}
.pwa-phone::before {
    content: '';
    position: absolute;
    top: 24px; left: 50%;
    transform: translateX(-50%);
    width: 90px; height: 8px;
    background: #000;
    border-radius: 4px;
    z-index: 3;
}
.pwa-phone::after {
    content: '';
    position: absolute;
    inset: -30px;
    background: radial-gradient(circle, rgba(0,113,227,0.3) 0%, transparent 65%);
    border-radius: 50%;
    z-index: -1;
    filter: blur(40px);
    animation: glowPulse 4s ease-in-out infinite;
}
@keyframes glowPulse {
    0%,100% { opacity: 0.6; transform: scale(1); }
    50%     { opacity: 1; transform: scale(1.05); }
}

.pwa-screen {
    width: 100%;
    height: 100%;
    background: linear-gradient(180deg, #f5f5f7 0%, #fff 100%);
    border-radius: 32px;
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
}
[data-theme="dark"] .pwa-screen {
    background: linear-gradient(180deg, #1c1c1e 0%, #000 100%);
}
.pwa-status-bar {
    padding: 32px 20px 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.7rem;
    font-weight: 600;
    color: #1d1d1f;
    flex-shrink: 0;
}
[data-theme="dark"] .pwa-status-bar { color: #f5f5f7; }
.pwa-status-bar .icons {
    display: flex;
    gap: 4px;
    font-size: 0.75rem;
}
.pwa-app-content {
    flex: 1;
    padding: 12px 16px 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.pwa-app-greeting {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1d1d1f;
    margin-bottom: 4px;
}
[data-theme="dark"] .pwa-app-greeting { color: #f5f5f7; }
.pwa-app-date {
    font-size: 0.7rem;
    color: #86868b;
    margin-bottom: 8px;
}
.pwa-mini-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 4px;
}
.pwa-mini-stat {
    background: #fff;
    border-radius: 12px;
    padding: 10px 8px;
    text-align: center;
    border: 1px solid #eaeaea;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
[data-theme="dark"] .pwa-mini-stat {
    background: #1c1c1e;
    border-color: #2c2c2e;
}
.pwa-mini-stat .v {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 4px;
}
.pwa-mini-stat .l {
    font-size: 0.58rem;
    color: #86868b;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.pwa-mini-stat:nth-child(1) .v { color: #0071e3; }
.pwa-mini-stat:nth-child(2) .v { color: #30d158; }
.pwa-mini-stat:nth-child(3) .v { color: #bf5af2; }

.pwa-app-lesson {
    background: #fff;
    border-radius: 12px;
    padding: 10px 12px;
    border: 1px solid #eaeaea;
    border-left: 3px solid #30d158;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
[data-theme="dark"] .pwa-app-lesson {
    background: #1c1c1e;
    border-color: #2c2c2e;
    border-left-color: #30d158;
}
.pwa-app-lesson-time {
    background: #1d1d1f;
    color: #fff;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}
.pwa-app-lesson-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: #1d1d1f;
    margin-bottom: 2px;
    line-height: 1.3;
}
[data-theme="dark"] .pwa-app-lesson-title { color: #f5f5f7; }
.pwa-app-lesson-meta {
    font-size: 0.62rem;
    color: #86868b;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.pwa-app-nav {
    margin-top: auto;
    display: flex;
    justify-content: space-around;
    padding: 12px 0 0;
    border-top: 1px solid #f0f0f2;
}
[data-theme="dark"] .pwa-app-nav { border-color: #2c2c2e; }
.pwa-app-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    font-size: 0.58rem;
    color: #86868b;
    text-align: center;
}
.pwa-app-nav-item i { font-size: 1rem; }
.pwa-app-nav-item.active { color: #0071e3; font-weight: 600; }

/* Плавающий бейдж «Установлено» */
.pwa-installed-badge-float {
    position: absolute;
    top: 30px;
    right: -20px;
    background: linear-gradient(135deg, #30d158 0%, #198754 100%);
    color: #fff;
    padding: 10px 18px;
    border-radius: 12px;
    font-size: 0.82rem;
    font-weight: 700;
    box-shadow: 0 12px 32px rgba(48,209,88,0.45);
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 5;
    animation: badgeFloat 5s ease-in-out infinite;
}
@keyframes badgeFloat {
    0%,100% { transform: translateY(0) rotate(-3deg); }
    50%     { transform: translateY(-10px) rotate(3deg); }
}
.pwa-installed-badge-float i { font-size: 1.1rem; }

/* Правая часть — фичи */
.pwa-features {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.pwa-feature {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    padding: 20px;
    background: var(--ap-bg);
    border-radius: 20px;
    border: 1px solid var(--ap-border);
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
}
.pwa-feature:hover {
    transform: translateX(8px);
    border-color: var(--ap-accent);
    box-shadow: 0 12px 32px var(--ap-shadow);
}
.pwa-feature-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    color: #fff;
    flex-shrink: 0;
}
.pwa-feature-icon--blue { background: linear-gradient(135deg, #0071e3, #4a90ff); }
.pwa-feature-icon--green { background: linear-gradient(135deg, #30d158, #198754); }
.pwa-feature-icon--purple { background: linear-gradient(135deg, #bf5af2, #6610f2); }
.pwa-feature-icon--orange { background: linear-gradient(135deg, #ff9f0a, #fd7e14); }
.pwa-feature-icon--teal { background: linear-gradient(135deg, #5ac8fa, #0dcaf0); }
.pwa-feature-icon--pink { background: linear-gradient(135deg, #ff2d92, #d63384); }
.pwa-feature-text h4 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--ap-dark);
    margin: 0 0 6px;
    letter-spacing: -0.01em;
}
.pwa-feature-text p {
    font-size: 0.88rem;
    color: var(--ap-gray);
    line-height: 1.5;
    margin: 0;
}

/* Карточки платформ */
.pwa-platforms {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-top: 48px;
}
.pwa-platform {
    background: var(--ap-bg);
    border: 1px solid var(--ap-border);
    border-radius: 20px;
    padding: 24px 20px;
    text-align: center;
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
    position: relative;
    overflow: hidden;
}
.pwa-platform::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--platform-color, var(--ap-accent));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.4s;
}
.pwa-platform:hover::before { transform: scaleX(1); }
.pwa-platform:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px var(--ap-shadow);
    border-color: var(--platform-color, var(--ap-accent));
}
.pwa-platform-icon {
    font-size: 2.2rem;
    color: var(--platform-color, var(--ap-accent));
    margin-bottom: 12px;
    display: block;
}
.pwa-platform-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--ap-dark);
    margin-bottom: 4px;
}
.pwa-platform-desc {
    font-size: 0.78rem;
    color: var(--ap-gray);
    line-height: 1.4;
}

/* Шаги установки */
.pwa-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 48px;
}
.pwa-step {
    background: var(--ap-bg);
    border: 1px solid var(--ap-border);
    border-radius: 20px;
    padding: 28px 22px;
    position: relative;
    transition: all 0.3s;
}
.pwa-step:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 32px var(--ap-shadow);
}
.pwa-step-num {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--ap-accent), var(--ap-purple));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 800;
    margin-bottom: 16px;
    box-shadow: 0 8px 20px rgba(0,113,227,0.3);
}
.pwa-step h5 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--ap-dark);
    margin: 0 0 8px;
}
.pwa-step p {
    font-size: 0.85rem;
    color: var(--ap-gray);
    line-height: 1.5;
    margin: 0;
}
.pwa-step code {
    background: rgba(0,113,227,0.08);
    color: var(--ap-accent);
    padding: 2px 6px;
    border-radius: 5px;
    font-size: 0.82em;
    font-family: "SF Mono", Monaco, monospace;
}

/* Плашка-баннер */
.pwa-callout {
    margin-top: 48px;
    padding: 28px 32px;
    background: linear-gradient(135deg, rgba(0,113,227,0.08) 0%, rgba(191,90,242,0.08) 100%);
    border: 1px solid rgba(0,113,227,0.15);
    border-radius: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    overflow: hidden;
}
.pwa-callout::before {
    content: '';
    position: absolute;
    top: -50%; right: -10%;
    width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(0,113,227,0.15) 0%, transparent 60%);
    border-radius: 50%;
    pointer-events: none;
}
.pwa-callout-icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.6rem;
    flex-shrink: 0;
    box-shadow: 0 12px 28px rgba(0,113,227,0.35);
}
.pwa-callout-text {
    flex: 1;
    position: relative;
    z-index: 1;
}
.pwa-callout-text h4 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--ap-dark);
    margin: 0 0 6px;
    letter-spacing: -0.01em;
}
.pwa-callout-text p {
    font-size: 0.92rem;
    color: var(--ap-gray);
    margin: 0;
    line-height: 1.5;
}
@media (max-width: 700px) {
    .pwa-callout { flex-direction: column; text-align: center; padding: 24px; }
}

/* ============================================================
   БОТ
   ============================================================ */
.tg-phone-wrapper {
    position: relative;
    animation: phoneFloat 6s ease-in-out infinite;
}
@keyframes phoneFloat {
    0%,100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-12px) rotate(-0.5deg); }
}
.tg-phone-wrapper::before {
    content: '';
    position: absolute;
    inset: -40px;
    background: radial-gradient(circle, rgba(0,113,227,0.25) 0%, transparent 60%);
    filter: blur(50px);
    z-index: -1;
    border-radius: 50%;
    animation: glowPulse 4s ease-in-out infinite;
}
@keyframes glowPulse {
    0%,100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.05); }
}
.tg-phone {
    max-width: 380px;
    margin: 0 auto;
    border-radius: 40px;
    background: #0d0d0d;
    padding: 12px;
    box-shadow: 0 30px 60px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.08) inset, 0 0 80px rgba(0,113,227,0.15);
    position: relative;
}
.tg-phone::before {
    content: '';
    position: absolute;
    top: 22px; left: 50%;
    transform: translateX(-50%);
    width: 80px; height: 6px;
    background: #000;
    border-radius: 4px;
    z-index: 3;
}
.tg-screen {
    background: var(--tg-bg);
    border-radius: 30px;
    overflow: hidden;
    color: #fff;
    height: 640px;
    display: flex;
    flex-direction: column;
    position: relative;
}
.tg-screen::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cg fill='none' stroke='%23ffffff' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M15 20 L18 12 L21 20 Z'/%3E%3Cpath d='M18 12 L18 22'/%3E%3Ccircle cx='55' cy='25' r='4'/%3E%3Cpath d='M30 50 h15 M37 42 v16'/%3E%3Cpath d='M60 55 l3 -6 l3 6 l6 3 l-6 3 l-3 6 l-3 -6 l-6 -3 z'/%3E%3Cpath d='M10 65 h12'/%3E%3Cpath d='M10 70 h8'/%3E%3Cpath d='M45 10 l5 -5 M50 10 l-5 -5'/%3E%3C/g%3E%3C/svg%3E");
    background-size: 100px 100px;
    opacity: var(--tg-pattern-op);
    pointer-events: none;
    z-index: 0;
}
.tg-screen > * { position: relative; z-index: 1; }
.tg-header {
    background: var(--tg-header);
    padding: 32px 16px 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    flex-shrink: 0;
}
.tg-avatar {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    border: 2px solid rgba(255,255,255,0.15);
    position: relative;
}
.tg-avatar::after {
    content: '';
    position: absolute;
    bottom: 0; right: 0;
    width: 12px; height: 12px;
    background: #30d158;
    border: 2px solid var(--tg-header);
    border-radius: 50%;
    animation: onlinePulse 2s ease-in-out infinite;
}
@keyframes onlinePulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(48,209,88,0.7); }
    50% { box-shadow: 0 0 0 6px rgba(48,209,88,0); }
}
.tg-name { font-weight: 600; font-size: 0.95rem; }
.tg-status { font-size: 0.75rem; color: #7f8fa3; }
.tg-messages {
    padding: 14px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
    overflow-y: auto;
    overflow-x: hidden;
    scroll-behavior: smooth;
    min-height: 0;
    background: rgba(0,0,0,0.15);
}
.tg-messages::-webkit-scrollbar { width: 6px; }
.tg-messages::-webkit-scrollbar-track { background: transparent; }
.tg-messages::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
.tg-msg {
    max-width: 88%;
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 0.84rem;
    line-height: 1.5;
    animation: msgIn 0.4s cubic-bezier(0.16,1,0.3,1);
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}
@keyframes msgIn {
    from { opacity: 0; transform: translateY(12px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.tg-msg--bot { background: var(--tg-bot-msg); color: #fff; border-bottom-left-radius: 4px; align-self: flex-start; }
.tg-msg--user { background: var(--tg-user-msg); color: #fff; border-bottom-right-radius: 4px; align-self: flex-end; }
.tg-msg b, .tg-msg strong { color: #fff; font-weight: 600; }
.tg-buttons { display: flex; flex-direction: column; gap: 5px; margin-top: 8px; }
.tg-btn {
    background: rgba(255,255,255,0.06);
    color: var(--tg-blue);
    border: 1px solid rgba(94,181,247,0.2);
    padding: 9px 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
    width: 100%;
}
.tg-btn:hover { background: rgba(94,181,247,0.15); border-color: var(--tg-blue); transform: translateX(4px); }
.tg-typing {
    display: inline-flex;
    gap: 4px;
    align-self: flex-start;
    padding: 12px 16px;
    background: var(--tg-bot-msg);
    border-radius: 16px;
    border-bottom-left-radius: 4px;
}
.tg-typing span {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #7f8fa3;
    animation: typing 1.2s infinite;
}
.tg-typing span:nth-child(2) { animation-delay: 0.2s; }
.tg-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing {
    0%,60%,100% { transform: translateY(0); opacity: 0.5; }
    30% { transform: translateY(-6px); opacity: 1; }
}
.tg-input {
    background: var(--tg-header);
    padding: 10px 14px 14px;
    display: flex;
    gap: 8px;
    border-top: 1px solid rgba(255,255,255,0.05);
    flex-shrink: 0;
}
.tg-input-field {
    flex: 1;
    background: rgba(255,255,255,0.06);
    border: none;
    color: #fff;
    padding: 10px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    outline: none;
    font-family: inherit;
}
.tg-input-field::placeholder { color: #7f8fa3; }
.tg-input-field:focus { background: rgba(255,255,255,0.1); box-shadow: 0 0 0 2px rgba(94,181,247,0.3); }
.tg-send {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: var(--ap-accent);
    border: none;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.15s, box-shadow 0.2s;
}
.tg-send:hover { transform: scale(1.08); box-shadow: 0 4px 12px rgba(0,113,227,0.4); }
.tg-quick-actions {
    display: flex;
    gap: 6px;
    padding: 8px 14px;
    overflow-x: auto;
    background: rgba(0,0,0,0.3);
    border-bottom: 1px solid rgba(255,255,255,0.05);
    flex-shrink: 0;
}
.tg-quick-actions::-webkit-scrollbar { height: 0; }
.tg-chip {
    padding: 5px 12px;
    background: rgba(255,255,255,0.08);
    border-radius: 20px;
    font-size: 0.75rem;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--tg-blue);
    border: 1px solid rgba(94,181,247,0.2);
}
.tg-chip:hover { background: rgba(94,181,247,0.2); transform: translateY(-1px); }

/* ============================================================
   ФИЧИ / КАРТОЧКИ
   ============================================================ */
.ap-feature-big {
    padding: 40px 0;
    display: grid;
    grid-template-columns: 100px 1fr;
    gap: 32px;
    align-items: flex-start;
    border-bottom: 1px solid var(--ap-border);
    transition: background 0.3s, padding 0.3s;
}
.ap-feature-big:hover { background: var(--ap-bg-alt); padding-left: 20px; padding-right: 20px; }
.ap-feature-big-num {
    font-size: 3rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.05em;
    background: linear-gradient(135deg, var(--ap-accent), var(--ap-purple));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ap-feature-big-text h4 { font-size: 1.35rem; font-weight: 700; margin-bottom: 10px; color: var(--ap-dark); }
.ap-feature-big-text p { font-size: 1rem; line-height: 1.6; color: var(--ap-gray); margin: 0; max-width: 600px; }
@media (max-width: 768px) {
    .ap-feature-big { grid-template-columns: 1fr; gap: 12px; padding: 28px 0; }
    .ap-feature-big-num { font-size: 2.2rem; }
}

.ap-bento { display: grid; grid-template-columns: repeat(6,1fr); gap: 16px; margin-top: 40px; }
.ap-bento-item {
    background: var(--ap-bg);
    border-radius: 20px;
    border: 1px solid var(--ap-border);
    padding: 24px;
    transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
    position: relative;
    overflow: hidden;
    opacity: 0;
    transform: translateY(30px);
}
.ap-bento-item.is-visible { opacity: 1; transform: translateY(0); }
.ap-bento-item::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--ap-glow-1) 0%, transparent 50%, var(--ap-glow-2) 100%);
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
}
.ap-bento-item:hover { box-shadow: 0 20px 40px var(--ap-shadow); border-color: var(--ap-accent); }
.ap-bento-item:hover::before { opacity: 1; }
.ap-bento-item--half { grid-column: span 3; }
.ap-bento-item--third { grid-column: span 2; }
@media (max-width: 900px) {
    .ap-bento-item--half, .ap-bento-item--third { grid-column: span 6; }
}
.ap-bento-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #fff;
    margin-bottom: 16px;
    transition: transform 0.3s;
}
.ap-bento-item:hover .ap-bento-icon { transform: scale(1.1) rotate(-5deg); }
.ap-ic-blue { background: linear-gradient(135deg, #0071e3, #4a90ff); }
.ap-ic-green { background: linear-gradient(135deg, #30d158, #198754); }
.ap-ic-purple { background: linear-gradient(135deg, #bf5af2, #6610f2); }
.ap-ic-orange { background: linear-gradient(135deg, #ff9f0a, #fd7e14); }
.ap-ic-pink { background: linear-gradient(135deg, #ff2d92, #d63384); }
.ap-ic-teal { background: linear-gradient(135deg, #5ac8fa, #0dcaf0); }
.ap-ic-red { background: linear-gradient(135deg, #ff453a, #d63447); }
.ap-bento-item h5 { font-size: 1.05rem; font-weight: 600; margin-bottom: 8px; color: var(--ap-dark); }
.ap-bento-item p { font-size: 0.9rem; color: var(--ap-gray); line-height: 1.5; margin: 0; }

/* ============================================================
   СРАВНЕНИЕ
   ============================================================ */
.ap-compare { display: grid; grid-template-columns: 1fr auto 1fr; gap: 24px; align-items: center; margin-top: 40px; }
@media (max-width: 768px) { .ap-compare { grid-template-columns: 1fr; } }
.ap-compare-col { padding: 32px 24px; border-radius: 20px; text-align: center; transition: transform 0.3s; }
.ap-compare-col:hover { transform: translateY(-4px); }
.ap-compare-col--old { background: rgba(255,69,58,0.08); border: 1px solid rgba(255,69,58,0.2); }
.ap-compare-col--new { background: rgba(48,209,88,0.08); border: 1px solid rgba(48,209,88,0.2); box-shadow: 0 20px 40px rgba(48,209,88,0.1); }
.ap-compare-col h4 { font-size: 1.1rem; font-weight: 600; margin-bottom: 16px; }
.ap-compare-col--old h4 { color: #ff453a; }
.ap-compare-col--new h4 { color: #30d158; }
.ap-compare-col ul { list-style: none; padding: 0; margin: 0; text-align: left; }
.ap-compare-col li { font-size: 0.9rem; padding: 8px 0; color: var(--ap-gray); display: flex; gap: 10px; align-items: flex-start; }
.ap-compare-col li::before { content: ''; width: 16px; height: 16px; flex-shrink: 0; border-radius: 50%; margin-top: 2px; }
.ap-compare-col--old li::before { background: #ff453a url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M4 4l8 8m0-8l-8 8' stroke='white' stroke-width='2' fill='none'/%3E%3C/svg%3E") center/10px no-repeat; }
.ap-compare-col--new li::before { background: #30d158 url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M3 8l3 3 7-7' stroke='white' stroke-width='2' fill='none'/%3E%3C/svg%3E") center/10px no-repeat; }
.ap-compare-arrow { font-size: 2rem; color: var(--ap-accent); display: flex; align-items: center; justify-content: center; animation: arrowPulse 2s ease-in-out infinite; }
@keyframes arrowPulse { 0%,100% { transform: translateX(0); } 50% { transform: translateX(6px); } }
@media (max-width: 768px) { .ap-compare-arrow { transform: rotate(90deg); animation: none; } }

/* ============================================================
   ШАГИ
   ============================================================ */
.ap-steps { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 16px; margin-top: 60px; }
.ap-step {
    padding: 32px 24px;
    background: rgba(255,255,255,0.06);
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.1);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}
.ap-step::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #0071e3, #bf5af2, transparent);
    transform: translateX(-100%);
    transition: transform 0.6s;
}
.ap-step:hover::before { transform: translateX(100%); }
.ap-step:hover { background: rgba(255,255,255,0.1); transform: translateY(-4px); }
.ap-step-num {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
    background: linear-gradient(135deg, #0071e3 0%, #bf5af2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 20px;
}
.ap-step h5 { font-size: 1.15rem; font-weight: 600; margin-bottom: 10px; color: var(--ap-text-on-dark); }
.ap-step p { font-size: 0.95rem; line-height: 1.5; color: var(--ap-text-muted-dark); margin: 0; }

/* ============================================================
   ТЕХНОЛОГИИ
   ============================================================ */
.ap-tech { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 32px; }
.ap-tech-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--ap-bg);
    border: 1px solid var(--ap-border);
    border-radius: 980px;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--ap-dark);
    transition: all 0.25s;
}
.ap-tech-badge:hover { border-color: var(--ap-accent); color: var(--ap-accent); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,113,227,0.15); }

/* ============================================================
   REVEAL / TOAST / MARQUEE
   ============================================================ */
.ap-reveal { opacity: 0; transform: translateY(50px); transition: opacity 0.9s cubic-bezier(0.16,1,0.3,1), transform 0.9s cubic-bezier(0.16,1,0.3,1); }
.ap-reveal.is-visible { opacity: 1; transform: translateY(0); }
.ap-reveal--d1 { transition-delay: 0.1s; }
.ap-reveal--d2 { transition-delay: 0.2s; }
.ap-reveal--d3 { transition-delay: 0.3s; }
@media (prefers-reduced-motion: reduce) { .ap-reveal { opacity: 1; transform: none; transition: none; } }

.ap-toast {
    position: fixed;
    bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #1d1d1f;
    color: #fff;
    padding: 14px 24px;
    border-radius: 14px;
    font-size: 0.95rem;
    font-weight: 500;
    box-shadow: 0 20px 60px rgba(0,0,0,0.35);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 10px;
    pointer-events: none;
}
.ap-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.ap-toast i { color: #30d158; font-size: 1.2rem; }

.ap-marquee {
    overflow: hidden;
    padding: 24px 0;
    background: var(--ap-dark);
    color: var(--ap-bg);
    mask-image: linear-gradient(90deg, transparent 0%, black 10%, black 90%, transparent 100%);
    -webkit-mask-image: linear-gradient(90deg, transparent 0%, black 10%, black 90%, transparent 100%);
}
.ap-marquee-track {
    display: flex;
    gap: 48px;
    animation: marquee 30s linear infinite;
    white-space: nowrap;
    width: fit-content;
}
.ap-marquee-track > * {
    font-size: 1.1rem;
    font-weight: 500;
    opacity: 0.7;
    display: inline-flex;
    align-items: center;
    gap: 12px;
}
.ap-marquee-track > *::before { content: '●'; color: var(--ap-accent); font-size: 0.7rem; }
@keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }

/* ============================================================
   CTA
   ============================================================ */
.ap-cta {
    text-align: center;
    padding: clamp(60px,10vw,120px) 0;
    background: linear-gradient(180deg, var(--ap-bg-soft) 0%, var(--ap-bg) 100%);
    position: relative;
    overflow: hidden;
}
.ap-cta::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at center, var(--ap-glow-1) 0%, transparent 60%);
    pointer-events: none;
}
.ap-cta h2 { font-size: clamp(2rem,5vw,3.5rem); font-weight: 700; letter-spacing: -0.02em; margin-bottom: 20px; color: var(--ap-dark); }

/* ============================================================
   QUOTE
   ============================================================ */
.ap-quote { max-width: 800px; margin: 0 auto; text-align: center; position: relative; padding-top: 40px; }
.ap-quote::before {
    content: '"';
    position: absolute;
    top: 0; left: 50%;
    transform: translateX(-50%);
    font-size: 6rem;
    line-height: 1;
    color: var(--ap-glow-1);
    font-family: Georgia, serif;
}
.ap-quote-text { font-size: clamp(1.5rem,3vw,2.25rem); font-weight: 500; line-height: 1.3; letter-spacing: -0.02em; color: var(--ap-dark); margin-bottom: 24px; }
.ap-quote-author { font-size: 0.95rem; color: var(--ap-gray); font-weight: 500; }

/* ============================================================
   ПЛАВАЮЩИЕ БЕЙДЖИ
   ============================================================ */
.ap-float-badge {
    position: absolute;
    padding: 10px 20px;
    background: var(--ap-bg);
    border: 1px solid var(--ap-border);
    border-radius: 20px;
    box-shadow: 0 12px 30px var(--ap-shadow);
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--ap-dark);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    animation: badgeFloat 5s ease-in-out infinite;
    z-index: 3;
}
.ap-float-badge--1 { top: 18%; left: 6%; }
.ap-float-badge--2 { top: 35%; right: 5%; animation-delay: 1.5s; }
.ap-float-badge--3 { bottom: 18%; left: 10%; animation-delay: 3s; }
.ap-float-badge--4 { bottom: 25%; right: 8%; animation-delay: 2s; }
@keyframes badgeFloat {
    0%,100% { transform: translateY(0) rotate(-2deg); }
    50% { transform: translateY(-10px) rotate(2deg); }
}
@media (max-width: 991px) { .ap-float-badge { display: none; } }

.ap-live-dot {
    display: inline-block;
    width: 8px; height: 8px;
    background: var(--ap-green);
    border-radius: 50%;
    margin-right: 6px;
    animation: liveDot 1.5s ease-in-out infinite;
}
@keyframes liveDot {
    0%,100% { opacity: 1; box-shadow: 0 0 0 0 rgba(48,209,88,0.5); }
    50% { opacity: 0.7; box-shadow: 0 0 0 6px rgba(48,209,88,0); }
}

.ap-scroll-hint {
    position: absolute;
    bottom: 40px; left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: var(--ap-gray);
    font-size: 0.75rem;
    animation: scrollHint 2s ease-in-out infinite;
    z-index: 2;
}
.ap-scroll-hint .mouse {
    width: 24px; height: 40px;
    border: 2px solid var(--ap-gray);
    border-radius: 12px;
    position: relative;
}
.ap-scroll-hint .mouse::after {
    content: '';
    width: 4px; height: 8px;
    background: var(--ap-gray);
    border-radius: 2px;
    position: absolute;
    top: 6px; left: 50%;
    transform: translateX(-50%);
    animation: mouseScroll 1.5s ease-in-out infinite;
}
@keyframes mouseScroll { 0% { top: 6px; opacity: 1; } 100% { top: 22px; opacity: 0; } }
@keyframes scrollHint { 0%,100% { transform: translateX(-50%) translateY(0); } 50% { transform: translateX(-50%) translateY(-8px); } }

/* ============================================================
   ФУТЕР
   ============================================================ */
.ap-footer {
    padding: 60px 24px 40px;
    text-align: center;
    color: var(--ap-gray);
    font-size: 0.85rem;
    background: var(--ap-bg-alt);
    border-top: 1px solid var(--ap-border);
}
.ap-footer-links {
    display: flex;
    gap: 24px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.ap-footer-links a {
    color: var(--ap-accent);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}
.ap-footer-links a:hover { color: var(--ap-accent-hover); text-decoration: underline; }
.ap-footer-note {
    font-size: 0.78rem;
    color: var(--ap-gray);
    opacity: 0.85;
    max-width: 560px;
    margin: 0 auto;
    line-height: 1.5;
}

/* ============================================================
   АДАПТИВ
   ============================================================ */
@media (max-width: 767px) {
    .ap-hero-title { font-size: 2.5rem; }
    .ap-hero-subtitle { font-size: 1rem; }
    .ap-btn { padding: 12px 24px; font-size: 0.95rem; }
    .ap-marquee-track > * { font-size: 0.95rem; }
    .pwa-phone { width: 260px; height: 520px; }
    .pwa-installed-badge-float { right: -10px; font-size: 0.75rem; padding: 8px 14px; }
}
</style>
</head>
<body>

<div class="scroll-progress" id="scrollProgress"></div>
<div class="cursor-glow" id="cursorGlow"></div>

<!-- Кнопка «Войти» -->
<a href="login.php" class="top-login">
    <i class="bi bi-box-arrow-in-right"></i>
    Войти в систему
</a>

<!-- Переключатель темы -->
<button class="theme-toggle" id="themeToggle" title="Переключить тему">
    <i class="bi bi-sun-fill icon-sun"></i>
    <i class="bi bi-moon-stars-fill icon-moon"></i>
</button>

<div class="ap-page">

    <!-- ============================================================
         HERO
         ============================================================ -->
    <section class="ap-hero">
        <div class="ap-float-badge ap-float-badge--1">
            <span class="ap-live-dot"></span>
            Работает прямо сейчас
        </div>
        <div class="ap-float-badge ap-float-badge--2">
            <i class="bi bi-lightning-charge-fill" style="color: #ff9f0a;"></i>
            Отметка в один клик
        </div>
        <div class="ap-float-badge ap-float-badge--3">
            <i class="bi bi-graph-up-arrow" style="color: #bf5af2;"></i>
            Автоотчёты
        </div>
        <div class="ap-float-badge ap-float-badge--4">
            <i class="bi bi-phone" style="color: #30d158;"></i>
            PWA-приложение
        </div>

        <div class="ap-container ap-hero-content" id="heroContent">
            <div class="ap-hero-eyebrow ap-reveal">
                АИС-расписание.РФ
            </div>
            <h1 class="ap-hero-title" id="heroTitle"></h1>
            <p class="ap-hero-subtitle ap-reveal ap-reveal--d2">
                Интерактивная презентация системы.
                Всё работает прямо здесь — без регистрации
            </p>
            <div class="ap-hero-actions ap-reveal ap-reveal--d3">
                <a href="#try" class="ap-btn ap-btn--primary">
                    <i class="bi bi-hand-index-thumb"></i> Попробовать
                </a>
                <a href="#pwa" class="ap-btn ap-btn--ghost">
                    <i class="bi bi-phone"></i> Установить приложение
                </a>
            </div>
        </div>

        <div class="ap-scroll-hint">
            <div class="mouse"></div>
            <span>Прокрутите вниз</span>
        </div>
    </section>

    <!-- ============================================================
         MARQUEE
         ============================================================ -->
    <div class="ap-marquee">
        <div class="ap-marquee-track">
            <span>Синхронизация с API</span>
            <span>Импорт из Excel</span>
            <span>Роли и права</span>
            <span>Живой поиск</span>
            <span>Отчёты в один клик</span>
            <span>Подгруппы</span>
            <span>Журнал действий</span>
            <span>Бот в MAX</span>
            <span>PWA-приложение</span>
            <span>Тёмная тема</span>
            <span>Резервные копии</span>
            <span>Синхронизация с API</span>
            <span>Импорт из Excel</span>
            <span>Роли и права</span>
            <span>Живой поиск</span>
            <span>Отчёты в один клик</span>
            <span>Подгруппы</span>
            <span>Журнал действий</span>
            <span>Бот в MAX</span>
            <span>PWA-приложение</span>
            <span>Тёмная тема</span>
            <span>Резервные копии</span>
        </div>
    </div>

    <!-- ============================================================
         ИНТЕРАКТИВ
         ============================================================ -->
    <section class="ap-section ap-section--gray" id="try">
        <div class="ap-container">
            <div class="ap-center" style="margin-bottom: 40px;">
                <h2 class="ap-h2 ap-reveal">
                    Вот как выглядит<br>рабочий день
                </h2>
                <p class="ap-lead ap-reveal ap-reveal--d2" style="margin-bottom: 0;">
                    Кликните на занятие, отметьте посещаемость,
                    переключите работы — всё работает прямо здесь
                </p>
            </div>

            <div class="ap-product ap-reveal ap-reveal--d3" id="tiltCard">
                <div class="ap-product-header">
                    <span class="ap-dot ap-dot--r"></span>
                    <span class="ap-dot ap-dot--y"></span>
                    <span class="ap-dot ap-dot--g"></span>
                    <span class="ap-product-title">АИС-расписание.РФ · интерактивная демонстрация</span>
                </div>
                <div class="ap-product-body">
                    <div class="lv-tabs">
                        <button class="lv-tab active" data-tab="schedule"><i class="bi bi-calendar-week"></i> Расписание</button>
                        <button class="lv-tab" data-tab="attendance"><i class="bi bi-check2-square"></i> Посещаемость</button>
                        <button class="lv-tab" data-tab="works"><i class="bi bi-journal-check"></i> Работы</button>
                        <button class="lv-tab" data-tab="reports"><i class="bi bi-bar-chart"></i> Отчёты</button>
                    </div>

                    <!-- Таб 1: Расписание -->
                    <div class="lv-tab-content active" data-content="schedule">
                        <div class="lv">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <div>
                                    <div style="font-size: 1rem; font-weight: 700; color: var(--ap-dark);">Здравствуйте, И. Иванов!</div>
                                    <div style="font-size: 0.78rem; color: var(--ap-gray);">Среда, 08.10.2026</div>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-bottom: 16px;">
                                <div class="lv-stat">
                                    <div class="lv-stat-num lv-stat-num--blue">3</div>
                                    <div class="lv-stat-label">Занятий сегодня</div>
                                </div>
                                <div class="lv-stat">
                                    <div class="lv-stat-num lv-stat-num--green">87%</div>
                                    <div class="lv-stat-label">Посещаемость</div>
                                </div>
                                <div class="lv-stat">
                                    <div class="lv-stat-num">14</div>
                                    <div class="lv-stat-label">Работ сдано</div>
                                </div>
                            </div>

                            <div style="font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">Занятия на сегодня</div>

                            <div class="lv-lesson lv-lesson--green" onclick="selectLesson(this)">
                                <span class="lv-lesson-time">13:20</span>
                                <div class="lv-lesson-body">
                                    <div class="lv-lesson-title">Разработка кода информационных систем</div>
                                    <div class="lv-lesson-meta">
                                        <span>👥 ИСт-241</span>
                                        <span>📍 1311</span>
                                        <span class="lv-badge lv-badge--green" style="margin-left: auto;">Идёт сейчас</span>
                                    </div>
                                </div>
                            </div>

                            <div class="lv-lesson" onclick="selectLesson(this)">
                                <span class="lv-lesson-time">15:10</span>
                                <div class="lv-lesson-body">
                                    <div class="lv-lesson-title">Элементы САПР в профессиональной деятельности</div>
                                    <div class="lv-lesson-meta">
                                        <span>👥 ПМт-241</span>
                                        <span>📍 1337</span>
                                        <span class="lv-badge lv-badge--orange" style="margin-left: auto;">Следующая</span>
                                    </div>
                                </div>
                            </div>

                            <div class="lv-lesson lv-lesson--orange" style="opacity: 0.75;" onclick="selectLesson(this)">
                                <span class="lv-lesson-time">17:00</span>
                                <div class="lv-lesson-body">
                                    <div class="lv-lesson-title">Компьютерная графика</div>
                                    <div class="lv-lesson-meta">
                                        <span>👥 РУтс-261</span>
                                        <span>📍 1311</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Таб 2: Посещаемость -->
                    <div class="lv-tab-content" data-content="attendance" style="display: none;">
                        <div class="lv">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <div>
                                    <div style="font-size: 0.92rem; font-weight: 700; color: var(--ap-dark);">Разработка кода ИС</div>
                                    <div style="font-size: 0.72rem; color: var(--ap-gray);">ИСт-241 · подгруппа 2 · 13:20</div>
                                </div>
                                <div style="display: flex; gap: 4px;">
                                    <span class="lv-badge lv-badge--green">✓ <span id="cnt-present">2</span></span>
                                    <span class="lv-badge lv-badge--red">✗ <span id="cnt-absent">0</span></span>
                                    <span class="lv-badge lv-badge--gray">— <span id="cnt-na">3</span></span>
                                </div>
                            </div>

                            <table class="lv-att-table" id="attTable">
                                <thead>
                                <tr>
                                    <th>Студент</th>
                                    <th style="text-align: center; width: 130px;">Отметка</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr data-mark="1" class="present"><td>Иванов Иван Иванович</td><td style="text-align:center;"><div class="lv-radio-group"><span class="lv-radio lv-radio--check-active" data-value="1" onclick="setMark(this)">✓</span><span class="lv-radio" data-value="0" onclick="setMark(this)">✗</span><span class="lv-radio" data-value="null" onclick="setMark(this)">−</span></div></td></tr>
                                <tr data-mark="1" class="present"><td>Петров Пётр Петрович</td><td style="text-align:center;"><div class="lv-radio-group"><span class="lv-radio lv-radio--check-active" data-value="1" onclick="setMark(this)">✓</span><span class="lv-radio" data-value="0" onclick="setMark(this)">✗</span><span class="lv-radio" data-value="null" onclick="setMark(this)">−</span></div></td></tr>
                                <tr data-mark="null"><td>Сидоров Сидор Сидорович</td><td style="text-align:center;"><div class="lv-radio-group"><span class="lv-radio" data-value="1" onclick="setMark(this)">✓</span><span class="lv-radio" data-value="0" onclick="setMark(this)">✗</span><span class="lv-radio lv-radio--dash-active" data-value="null" onclick="setMark(this)">−</span></div></td></tr>
                                <tr data-mark="null"><td>Кузнецов Алексей Дмитриевич</td><td style="text-align:center;"><div class="lv-radio-group"><span class="lv-radio" data-value="1" onclick="setMark(this)">✓</span><span class="lv-radio" data-value="0" onclick="setMark(this)">✗</span><span class="lv-radio lv-radio--dash-active" data-value="null" onclick="setMark(this)">−</span></div></td></tr>
                                <tr data-mark="null"><td>Смирнов Дмитрий Олегович</td><td style="text-align:center;"><div class="lv-radio-group"><span class="lv-radio" data-value="1" onclick="setMark(this)">✓</span><span class="lv-radio" data-value="0" onclick="setMark(this)">✗</span><span class="lv-radio lv-radio--dash-active" data-value="null" onclick="setMark(this)">−</span></div></td></tr>
                                </tbody>
                            </table>

                            <div style="margin-top: 14px; padding: 12px; background: var(--ap-bg-soft); border-radius: 10px; font-size: 0.78rem; color: var(--ap-gray); text-align: center;">
                                Обновляется в реальном времени. Итого: <b id="attSummary" style="color: var(--ap-dark);">было 2 из 5</b>
                            </div>

                            <div style="display: flex; gap: 8px; margin-top: 14px;">
                                <button class="ap-btn ap-btn--ghost ap-btn--sm" onclick="markAll(1)"><i class="bi bi-check-all"></i> Все были</button>
                                <button class="ap-btn ap-btn--ghost ap-btn--sm" onclick="markAll(0)"><i class="bi bi-x-circle"></i> Сбросить</button>
                                <button class="ap-btn ap-btn--primary ap-btn--sm" style="margin-left: auto;" onclick="saveAttendance(event)"><i class="bi bi-save"></i> Сохранить</button>
                            </div>
                        </div>
                    </div>

                    <!-- Таб 3: Работы -->
                    <div class="lv-tab-content" data-content="works" style="display: none;">
                        <div class="lv">
                            <div style="background: var(--ap-bg-soft); padding: 12px 16px; border-radius: 12px; margin-bottom: 14px;">
                                <div style="font-size: 0.72rem; color: var(--ap-gray); margin-bottom: 2px;">Работа</div>
                                <div style="font-size: 0.92rem; font-weight: 600; color: var(--ap-dark);">Лабораторная №3 — Работа с БД</div>
                            </div>

                            <div id="worksList">
                                <div class="work-row" data-state="1" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: rgba(48,209,88,0.08); border-radius: 10px; margin-bottom: 6px;">
                                    <span style="font-size: 0.88rem;">Иванов Иван Иванович</span>
                                    <div style="display: flex; align-items: center; gap: 10px;"><span class="work-label" style="font-size: 0.78rem; color: #30d158; font-weight: 600;">Сдал</span><span class="lv-switch lv-switch--on" onclick="toggleWork(this)"></span></div>
                                </div>
                                <div class="work-row" data-state="1" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: rgba(48,209,88,0.08); border-radius: 10px; margin-bottom: 6px;">
                                    <span style="font-size: 0.88rem;">Петров Пётр Петрович</span>
                                    <div style="display: flex; align-items: center; gap: 10px;"><span class="work-label" style="font-size: 0.78rem; color: #30d158; font-weight: 600;">Сдал</span><span class="lv-switch lv-switch--on" onclick="toggleWork(this)"></span></div>
                                </div>
                                <div class="work-row" data-state="0" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-radius: 10px; margin-bottom: 6px;">
                                    <span style="font-size: 0.88rem;">Сидоров Сидор Сидорович</span>
                                    <div style="display: flex; align-items: center; gap: 10px;"><span class="work-label" style="font-size: 0.78rem; color: var(--ap-gray);">Не сдал</span><span class="lv-switch" onclick="toggleWork(this)"></span></div>
                                </div>
                                <div class="work-row" data-state="1" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: rgba(48,209,88,0.08); border-radius: 10px; margin-bottom: 6px;">
                                    <span style="font-size: 0.88rem;">Кузнецов Алексей Дмитриевич</span>
                                    <div style="display: flex; align-items: center; gap: 10px;"><span class="work-label" style="font-size: 0.78rem; color: #30d158; font-weight: 600;">Сдал</span><span class="lv-switch lv-switch--on" onclick="toggleWork(this)"></span></div>
                                </div>
                                <div class="work-row" data-state="0" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-radius: 10px;">
                                    <span style="font-size: 0.88rem;">Смирнов Дмитрий Олегович</span>
                                    <div style="display: flex; align-items: center; gap: 10px;"><span class="work-label" style="font-size: 0.78rem; color: var(--ap-gray);">Не сдал</span><span class="lv-switch" onclick="toggleWork(this)"></span></div>
                                </div>
                            </div>

                            <div style="margin-top: 14px; padding: 12px; background: var(--ap-bg-soft); border-radius: 10px; text-align: center;">
                                <span style="font-size: 0.78rem; color: var(--ap-gray);">Сдали: <strong id="worksCount" style="color: #30d158;">3</strong> из 5</span>
                            </div>
                        </div>
                    </div>

                    <!-- Таб 4: Отчёты -->
                    <div class="lv-tab-content" data-content="reports" style="display: none;">
                        <div class="lv">
                            <div style="display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 14px;">
                                <div class="lv-stat"><div class="lv-stat-num" style="font-size: 1.3rem;">12</div><div class="lv-stat-label">Занятий</div></div>
                                <div class="lv-stat"><div class="lv-stat-num lv-stat-num--green" style="font-size: 1.3rem;">87%</div><div class="lv-stat-label">Посещаемость</div></div>
                                <div class="lv-stat"><div class="lv-stat-num lv-stat-num--red" style="font-size: 1.3rem;">8</div><div class="lv-stat-label">Пропусков</div></div>
                            </div>

                            <div style="font-size: 0.75rem; color: var(--ap-gray); margin-bottom: 6px;">МАТРИЦА ПОСЕЩАЕМОСТИ</div>

                            <table class="lv-matrix">
                                <thead><tr><th>Студент</th><th>01.10</th><th>05.10</th><th>08.10</th><th>12.10</th><th>15.10</th></tr></thead>
                                <tbody>
                                <tr><td>Иванов И.И.</td><td class="lv-mark-yes">✓</td><td class="lv-mark-yes">✓</td><td class="lv-mark-yes">✓</td><td class="lv-mark-yes">✓</td><td class="lv-mark-yes">✓</td></tr>
                                <tr><td>Петров П.П.</td><td class="lv-mark-yes">✓</td><td class="lv-mark-no">✗</td><td class="lv-mark-yes">✓</td><td class="lv-mark-yes">✓</td><td class="lv-mark-yes">✓</td></tr>
                                <tr><td>Сидоров С.С.</td><td class="lv-mark-no">✗</td><td class="lv-mark-no">✗</td><td class="lv-mark-yes">✓</td><td class="lv-mark-no">✗</td><td class="lv-mark-yes">✓</td></tr>
                                <tr><td>Кузнецов А.Д.</td><td class="lv-mark-yes">✓</td><td class="lv-mark-yes">✓</td><td class="lv-mark-na">·</td><td class="lv-mark-yes">✓</td><td class="lv-mark-yes">✓</td></tr>
                                </tbody>
                            </table>

                            <div style="margin-top: 12px; font-size: 0.72rem; color: var(--ap-gray); text-align: center;">✓ был · ✗ пропустил · · не отмечен</div>
                            <button class="ap-btn ap-btn--ghost ap-btn--sm" style="margin-top: 12px;" onclick="showToast('Отчёт готов к печати')"><i class="bi bi-printer"></i> Распечатать</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         PWA
         ============================================================ -->
    <section class="ap-section pwa-section" id="pwa">
        <div class="ap-container">
            <div class="ap-center" style="margin-bottom: 60px;">
                <div class="ap-hero-eyebrow ap-reveal" style="margin-bottom: 16px;">
                    <i class="bi bi-phone"></i>
                    Progressive Web App
                </div>
                <h2 class="ap-h2 ap-reveal">Установите как приложение</h2>
                <p class="ap-lead ap-reveal ap-reveal--d1">
                    Портал работает как нативное приложение — без App Store
                    и Google Play. Одна кнопка — и он на вашем домашнем экране
                </p>
            </div>

            <div class="pwa-grid">
                <!-- Левая часть: телефон -->
                <div class="pwa-phone-wrapper ap-reveal ap-reveal--d2">
                    <div class="pwa-installed-badge-float">
                        <i class="bi bi-check-circle-fill"></i>
                        Установлено
                    </div>
                    <div class="pwa-phone">
                        <div class="pwa-screen">
                            <div class="pwa-status-bar">
                                <span>9:41</span>
                                <div class="icons">
                                    <i class="bi bi-wifi"></i>
                                    <i class="bi bi-battery-full"></i>
                                </div>
                            </div>

                            <div class="pwa-app-content">
                                <div class="pwa-app-greeting">Привет, Иван!</div>
                                <div class="pwa-app-date">Среда, 8 октября</div>

                                <div class="pwa-mini-stats">
                                    <div class="pwa-mini-stat">
                                        <div class="v">3</div>
                                        <div class="l">пары</div>
                                    </div>
                                    <div class="pwa-mini-stat">
                                        <div class="v">87%</div>
                                        <div class="l">посещ.</div>
                                    </div>
                                    <div class="pwa-mini-stat">
                                        <div class="v">14</div>
                                        <div class="l">работ</div>
                                    </div>
                                </div>

                                <div class="pwa-app-lesson">
                                    <span class="pwa-app-lesson-time">13:20</span>
                                    <div>
                                        <div class="pwa-app-lesson-title">Разработка кода ИС</div>
                                        <div class="pwa-app-lesson-meta">
                                            <span>👥 ИСт-241</span>
                                            <span>📍 1311</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pwa-app-lesson" style="border-left-color: #ff9f0a;">
                                    <span class="pwa-app-lesson-time">15:10</span>
                                    <div>
                                        <div class="pwa-app-lesson-title">Элементы САПР</div>
                                        <div class="pwa-app-lesson-meta">
                                            <span>👥 ПМт-241</span>
                                            <span>📍 1337</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pwa-app-nav">
                                    <div class="pwa-app-nav-item active">
                                        <i class="bi bi-house-door-fill"></i>
                                        <span>Главная</span>
                                    </div>
                                    <div class="pwa-app-nav-item">
                                        <i class="bi bi-calendar-week"></i>
                                        <span>Расписание</span>
                                    </div>
                                    <div class="pwa-app-nav-item">
                                        <i class="bi bi-check2-square"></i>
                                        <span>Отметки</span>
                                    </div>
                                    <div class="pwa-app-nav-item">
                                        <i class="bi bi-bar-chart"></i>
                                        <span>Отчёты</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Правая часть: фичи -->
                <div class="pwa-features">
                    <div class="pwa-feature ap-reveal">
                        <div class="pwa-feature-icon pwa-feature-icon--blue">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <div class="pwa-feature-text">
                            <h4>Мгновенный запуск</h4>
                            <p>Открывается с домашнего экрана за долю секунды — как обычное приложение</p>
                        </div>
                    </div>

                    <div class="pwa-feature ap-reveal ap-reveal--d1">
                        <div class="pwa-feature-icon pwa-feature-icon--green">
                            <i class="bi bi-wifi-off"></i>
                        </div>
                        <div class="pwa-feature-text">
                            <h4>Работает офлайн</h4>
                            <p>Service Worker кэширует страницы — базовые разделы доступны без интернета</p>
                        </div>
                    </div>

                    <div class="pwa-feature ap-reveal ap-reveal--d2">
                        <div class="pwa-feature-icon pwa-feature-icon--purple">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <div class="pwa-feature-text">
                            <h4>Без браузера</h4>
                            <p>Полноэкранный режим без адресной строки — больше места для контента</p>
                        </div>
                    </div>

                    <div class="pwa-feature ap-reveal ap-reveal--d3">
                        <div class="pwa-feature-icon pwa-feature-icon--orange">
                            <i class="bi bi-download"></i>
                        </div>
                        <div class="pwa-feature-text">
                            <h4>Не занимает память</h4>
                            <p>Вес приложения — меньше мегабайта. Не нужно обновлять вручную</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Платформы -->
            <div class="ap-center" style="margin-top: 80px;">
                <h3 class="ap-h2 ap-reveal" style="font-size: clamp(1.5rem,3vw,2rem);">Работает везде</h3>
            </div>
            <div class="pwa-platforms">
                <div class="pwa-platform ap-reveal" style="--platform-color:#0071e3;">
                    <i class="bi bi-android2 pwa-platform-icon"></i>
                    <div class="pwa-platform-name">Android</div>
                    <div class="pwa-platform-desc">Chrome, Edge, Samsung Internet</div>
                </div>
                <div class="pwa-platform ap-reveal ap-reveal--d1" style="--platform-color:#1d1d1f;">
                    <i class="bi bi-apple pwa-platform-icon"></i>
                    <div class="pwa-platform-name">iOS / iPadOS</div>
                    <div class="pwa-platform-desc">Safari 16.4+</div>
                </div>
                <div class="pwa-platform ap-reveal ap-reveal--d2" style="--platform-color:#30d158;">
                    <i class="bi bi-windows pwa-platform-icon"></i>
                    <div class="pwa-platform-name">Windows</div>
                    <div class="pwa-platform-desc">Chrome, Edge</div>
                </div>
                <div class="pwa-platform ap-reveal ap-reveal--d3" style="--platform-color:#bf5af2;">
                    <i class="bi bi-ubuntu pwa-platform-icon"></i>
                    <div class="pwa-platform-name">Linux / macOS</div>
                    <div class="pwa-platform-desc">Chrome, Edge, Firefox</div>
                </div>
            </div>

            <!-- Шаги установки -->
            <div class="ap-center" style="margin-top: 80px;">
                <h3 class="ap-h2 ap-reveal" style="font-size: clamp(1.5rem,3vw,2rem);">Как установить</h3>
            </div>
            <div class="pwa-steps">
                <div class="pwa-step ap-reveal">
                    <div class="pwa-step-num">1</div>
                    <h5>Откройте портал</h5>
                    <p>Войдите в систему на телефоне или компьютере</p>
                </div>
                <div class="pwa-step ap-reveal ap-reveal--d1">
                    <div class="pwa-step-num">2</div>
                    <h5>Нажмите «Установить»</h5>
                    <p>В футере появится кнопка — или используйте меню браузера: <code>⋮ → Установить</code></p>
                </div>
                <div class="pwa-step ap-reveal ap-reveal--d2">
                    <div class="pwa-step-num">3</div>
                    <h5>Подтвердите</h5>
                    <p>На iPhone — через <code>Поделиться → На главный экран</code></p>
                </div>
                <div class="pwa-step ap-reveal ap-reveal--d3">
                    <div class="pwa-step-num">4</div>
                    <h5>Готово!</h5>
                    <p>Иконка появится на домашнем экране — открывается как приложение</p>
                </div>
            </div>

            <!-- Баннер -->
            <div class="pwa-callout ap-reveal">
                <div class="pwa-callout-icon">
                    <i class="bi bi-stars"></i>
                </div>
                <div class="pwa-callout-text">
                    <h4>PWA — это современно</h4>
                    <p>
                        Никаких обновлений вручную, никаких разрешений на уведомления.
                        Работает на любом устройстве, где есть браузер.
                        Устанавливается за 3 секунды.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         ЧТО ДЕЛАЕТ СИСТЕМА
         ============================================================ -->
    <section class="ap-section">
        <div class="ap-container">
            <div class="ap-center" style="margin-bottom: 40px;">
                <h2 class="ap-h2 ap-reveal">Что умеет система</h2>
                <p class="ap-lead ap-reveal ap-reveal--d1">Модули, которые закрывают весь учебный процесс</p>
            </div>

            <div class="ap-feature-big ap-reveal">
                <div class="ap-feature-big-num">01</div>
                <div class="ap-feature-big-text">
                    <h4>Расписание с API</h4>
                    <p>Система запрашивает расписание у внешнего API и синхронизирует его в вашу базу. Потоковые лекции, подгруппы, аудитории — всё учитывается</p>
                </div>
            </div>
            <div class="ap-feature-big ap-reveal">
                <div class="ap-feature-big-num">02</div>
                <div class="ap-feature-big-text">
                    <h4>Посещаемость в один клик</h4>
                    <p>Галочка — был, крестик — пропустил, ничего — не отмечен. Кнопка «Все были» ускоряет отметку целой группы. Подгруппы фильтруются автоматически</p>
                </div>
            </div>
            <div class="ap-feature-big ap-reveal">
                <div class="ap-feature-big-num">03</div>
                <div class="ap-feature-big-text">
                    <h4>Сданные работы</h4>
                    <p>В базе хранятся только сдавшие. Переключатель «сдал/не сдал» для каждого студента. Отчёты считают, сколько работ сдал каждый</p>
                </div>
            </div>
            <div class="ap-feature-big ap-reveal">
                <div class="ap-feature-big-num">04</div>
                <div class="ap-feature-big-text">
                    <h4>Отчёты и матрицы</h4>
                    <p>Сводка по группе, предмету и студенту. Матрица посещаемости по датам. Экспорт в CSV, печать в один клик</p>
                </div>
            </div>
            <div class="ap-feature-big ap-reveal">
                <div class="ap-feature-big-num">05</div>
                <div class="ap-feature-big-text">
                    <h4>Сессия и ведомости</h4>
                    <p>Зачёты, экзамены, дифф. зачёты. Автодопуск по порогам посещаемости и сданных работ. Печать официальной формы ведомости</p>
                </div>
            </div>
            <div class="ap-feature-big ap-reveal">
                <div class="ap-feature-big-num">06</div>
                <div class="ap-feature-big-text">
                    <h4>Импорт студентов из Excel</h4>
                    <p>Загрузите CSV со списком группы — студенты создадутся сами. Повторный импорт обновляет данные, не создавая дублей</p>
                </div>
            </div>
            <div class="ap-feature-big ap-reveal">
                <div class="ap-feature-big-num">07</div>
                <div class="ap-feature-big-text">
                    <h4>Роли и права</h4>
                    <p>Преподаватель, зав. кафедрой, дирекция института, администратор. Каждый видит только свою зону данных</p>
                </div>
            </div>
            <div class="ap-feature-big ap-reveal">
                <div class="ap-feature-big-num">08</div>
                <div class="ap-feature-big-text">
                    <h4>Журнал и админ-панель</h4>
                    <p>Все действия фиксируются в журнале. Админ-панель — 8 вкладок с полным управлением пользователями, группами, студентами, занятиями и бэкапами</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         БОТ
         ============================================================ -->
    <section class="ap-section ap-section--dark">
        <div class="ap-container">
            <div class="ap-center" style="margin-bottom: 40px;">
                <h2 class="ap-h2 ap-reveal" style="color: var(--ap-text-on-dark);">И ещё — бот в MAX</h2>
                <p class="ap-lead ap-reveal ap-reveal--d2" style="color: var(--ap-text-muted-dark);">
                    «КузГТУшник» — расписание в мессенджере. Тот же бот — попробуйте прямо здесь
                </p>
            </div>

            <div class="row align-items-center g-5">
                <div class="col-lg-5 ap-reveal">
                    <h3 style="font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 20px; color: var(--ap-text-on-dark);">
                        Расписание всегда<br>под рукой
                    </h3>
                    <p style="font-size: 1.1rem; line-height: 1.5; color: var(--ap-text-muted-dark); margin-bottom: 24px;">
                        Пары, замены, аудитории и график преподавателей — прямо в MAX
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px;">
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <i class="bi bi-calendar-check" style="color: #30d158; font-size: 1.4rem; margin-top: 2px;"></i>
                            <div>
                                <div style="font-weight: 600; margin-bottom: 2px; color: var(--ap-text-on-dark);">Расписание на сегодня и завтра</div>
                                <div style="font-size: 0.92rem; color: var(--ap-text-muted-dark);">Все пары с аудиториями и временем</div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <i class="bi bi-search" style="color: #30d158; font-size: 1.4rem; margin-top: 2px;"></i>
                            <div>
                                <div style="font-weight: 600; margin-bottom: 2px; color: var(--ap-text-on-dark);">Поиск по преподавателю, группе, аудитории</div>
                                <div style="font-size: 0.92rem; color: var(--ap-text-muted-dark);">Один запрос — и вы знаете, где кто</div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <i class="bi bi-bell" style="color: #30d158; font-size: 1.4rem; margin-top: 2px;"></i>
                            <div>
                                <div style="font-weight: 600; margin-bottom: 2px; color: var(--ap-text-on-dark);">Подписки на уведомления</div>
                                <div style="font-size: 0.92rem; color: var(--ap-text-muted-dark);">Погода, следующая пара, расписание на завтра</div>
                            </div>
                        </div>
                    </div>
                    <a href="https://max.ru/se14203391_bot" target="_blank" rel="noopener" class="ap-btn ap-btn--primary">
                        <i class="bi bi-send-fill"></i> Открыть в MAX
                    </a>
                </div>

                <div class="col-lg-7 ap-reveal ap-reveal--d1">
                    <div class="tg-phone-wrapper">
                        <div class="tg-phone">
                            <div class="tg-screen">
                                <div class="tg-header">
                                    <div class="tg-avatar">🎓</div>
                                    <div>
                                        <div class="tg-name">КузГТУшник</div>
                                        <div class="tg-status" id="botStatus">онлайн</div>
                                    </div>
                                </div>
                                <div class="tg-quick-actions">
                                    <span class="tg-chip" onclick="botQuick('start')">▶️ Начать</span>
                                    <span class="tg-chip" onclick="botQuick('today')">📅 Сегодня</span>
                                    <span class="tg-chip" onclick="botQuick('tomorrow')">📆 Завтра</span>
                                    <span class="tg-chip" onclick="botQuick('week')">🗓 Неделя</span>
                                    <span class="tg-chip" onclick="botQuick('help')">❓ Помощь</span>
                                </div>
                                <div class="tg-messages" id="botMessages"></div>
                                <div class="tg-input">
                                    <input type="text" class="tg-input-field" id="botInput" placeholder="Сообщение..."
                                           onkeypress="if(event.key==='Enter')botUserMessage()">
                                    <button class="tg-send" onclick="botUserMessage()"><i class="bi bi-send"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         СРАВНЕНИЕ
         ============================================================ -->
    <section class="ap-section ap-section--gray">
        <div class="ap-container">
            <div class="ap-center">
                <h2 class="ap-h2 ap-reveal">Как было, и как стало</h2>
                <p class="ap-lead ap-reveal ap-reveal--d1">Не просто оцифровка журнала — а нормальный инструмент работы</p>
            </div>

            <div class="ap-compare ap-reveal ap-reveal--d2">
                <div class="ap-compare-col ap-compare-col--old">
                    <h4>😩 Раньше</h4>
                    <ul>
                        <li>Бумажный журнал, который теряется</li>
                        <li>Ручной ввод расписания</li>
                        <li>Списание студентов по памяти</li>
                        <li>Подсчёт пропусков вручную</li>
                        <li>Стопка таблиц для отчёта</li>
                    </ul>
                </div>
                <div class="ap-compare-arrow"><i class="bi bi-arrow-right"></i></div>
                <div class="ap-compare-col ap-compare-col--new">
                    <h4>✨ С нами</h4>
                    <ul>
                        <li>Расписание синхронизируется само</li>
                        <li>Импорт студентов из Excel</li>
                        <li>Отметка в один клик</li>
                        <li>Автоматический подсчёт</li>
                        <li>Отчёт готов к печати</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         ШАГИ
         ============================================================ -->
    <section class="ap-section ap-section--dark">
        <div class="ap-container ap-center">
            <h2 class="ap-h2 ap-reveal" style="color: var(--ap-text-on-dark);">
                Начало работы —<br>за 3 минуты
            </h2>
            <p class="ap-lead ap-reveal ap-reveal--d1" style="color: var(--ap-text-muted-dark);">
                От первого входа до готового отчёта
            </p>

            <div class="ap-steps">
                <div class="ap-step ap-reveal"><div class="ap-step-num">01</div><h5>Регистрация</h5><p>Введите фамилию — система найдёт вас в справочнике</p></div>
                <div class="ap-step ap-reveal ap-reveal--d1"><div class="ap-step-num">02</div><h5>Синхронизация</h5><p>Одна кнопка — и всё расписание подгружено</p></div>
                <div class="ap-step ap-reveal ap-reveal--d2"><div class="ap-step-num">03</div><h5>Студенты</h5><p>Загрузите CSV со списком группы — они появятся в системе</p></div>
                <div class="ap-step ap-reveal ap-reveal--d3"><div class="ap-step-num">04</div><h5>Отчёты</h5><p>Отмечайте посещаемость и работы — получайте аналитику</p></div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         ЦИТАТА
         ============================================================ -->
    <section class="ap-section">
        <div class="ap-container">
            <div class="ap-quote ap-reveal">
                <p class="ap-quote-text">Наконец-то можно забыть про бумажные журналы. Расписание подтянулось само, посещаемость — в один клик</p>
                <div class="ap-quote-author">— Преподаватель</div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         ТЕХНОЛОГИИ
         ============================================================ -->
    <section class="ap-section ap-section--gray">
        <div class="ap-container ap-center">
            <h2 class="ap-h2 ap-reveal">Простой стек. Надёжные технологии</h2>
            <div class="ap-tech ap-reveal ap-reveal--d1">
                <span class="ap-tech-badge">PHP 8</span>
                <span class="ap-tech-badge">MySQL</span>
                <span class="ap-tech-badge">Bootstrap 5</span>
                <span class="ap-tech-badge">JavaScript</span>
                <span class="ap-tech-badge">Chart.js</span>
                <span class="ap-tech-badge">REST API</span>
                <span class="ap-tech-badge">PDO</span>
                <span class="ap-tech-badge">CSV / Excel</span>
                <span class="ap-tech-badge">MAX Bot API</span>
                <span class="ap-tech-badge">PWA + Service Worker</span>
            </div>
        </div>
    </section>

    <!-- ============================================================
         CTA
         ============================================================ -->
    <section class="ap-cta">
        <div class="ap-container">
            <h2 class="ap-reveal">Готовы попробовать по-настоящему?</h2>
            <p class="ap-lead ap-reveal ap-reveal--d1" style="margin-bottom: 32px;">
                Войдите в систему — расписание уже ждёт вас
            </p>
            <div class="ap-hero-actions ap-reveal ap-reveal--d2">
                <a href="login.php" class="ap-btn ap-btn--primary">
                    <i class="bi bi-box-arrow-in-right"></i> Войти в систему
                </a>
                <a href="register.php" class="ap-btn ap-btn--ghost">
                    <i class="bi bi-person-plus"></i> Регистрация
                </a>
            </div>
        </div>
    </section>

    <!-- ============================================================
         ФУТЕР
         ============================================================ -->
    <footer class="ap-footer">
        <div class="ap-footer-links">
            <a href="login.php"><i class="bi bi-box-arrow-in-right"></i> Войти</a>
            <a href="register.php"><i class="bi bi-person-plus"></i> Регистрация</a>
            <a href="student.php"><i class="bi bi-mortarboard"></i> Студентам</a>
            <a href="https://max.ru/se14203391_bot" target="_blank" rel="noopener">
                <i class="bi bi-send"></i> Бот в MAX
            </a>
        </div>
        <p class="ap-footer-note">
            АИС-расписание.РФ — неофициальный проект.
            Не является официальным ресурсом какого-либо учебного заведения.
            Данные расписания загружаются из открытых источников
        </p>
        <p style="margin: 16px 0 0; opacity: 0.7;">© <?= date('Y') ?> АИС-расписание.РФ</p>
    </footer>

</div>

<div class="ap-toast" id="apToast"><i class="bi bi-check-circle-fill"></i><span id="apToastText">Готово!</span></div>

<script>
/* ============================================================
   ТЕМА
   ============================================================ */
(function() {
    const saved = localStorage.getItem('ap-theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    document.getElementById('themeToggle').addEventListener('click', () => {
        const cur = document.documentElement.getAttribute('data-theme') || 'light';
        const next = cur === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('ap-theme', next);
        showToast(next === 'dark' ? '🌙 Тёмная тема' : '☀️ Светлая тема');
    });
})();

/* Заголовок hero */
(function() {
    const el = document.getElementById('heroTitle');
    if (!el) return;
    const lines = [['Посещаемость'], ['Проще,', 'чем', 'кажется']];
    let html = '';
    let idx = 0;
    lines.forEach((words, lineIdx) => {
        words.forEach((w, i) => {
            const cls = lineIdx === 1 ? 'ap-color' : '';
            html += `<span class="word ${cls}" style="animation-delay:${0.2 + idx*0.15}s">${w}</span>`;
            idx++;
            if (i < words.length - 1) html += ' ';
        });
        if (lineIdx < lines.length - 1) html += '<br>';
    });
    el.innerHTML = html;
})();

/* Прогресс-бар */
(function() {
    const bar = document.getElementById('scrollProgress');
    window.addEventListener('scroll', () => {
        const h = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.width = ((window.scrollY / h) * 100) + '%';
    }, { passive: true });
})();

/* Курсор */
(function() {
    const glow = document.getElementById('cursorGlow');
    let mx=0,my=0,cx=0,cy=0;
    document.addEventListener('mousemove', (e) => { mx=e.clientX; my=e.clientY; glow.classList.add('active'); });
    document.addEventListener('mouseleave', () => glow.classList.remove('active'));
    function anim() {
        cx += (mx-cx)*0.15;
        cy += (my-cy)*0.15;
        glow.style.left = cx+'px';
        glow.style.top = cy+'px';
        requestAnimationFrame(anim);
    }
    anim();
})();

/* 3D-наклон */
(function() {
    const card = document.getElementById('tiltCard');
    if (!card) return;
    card.addEventListener('mousemove', (e) => {
        const r = card.getBoundingClientRect();
        const x = (e.clientX - r.left)/r.width - 0.5;
        const y = (e.clientY - r.top)/r.height - 0.5;
        card.style.transform = `perspective(1200px) rotateY(${x*4}deg) rotateX(${-y*4}deg) translateY(-4px) scale(1.01)`;
    });
    card.addEventListener('mouseleave', () => { card.style.transform = ''; });
})();

/* Reveal */
(function() {
    const reveals = document.querySelectorAll('.ap-reveal');
    const bento = document.querySelectorAll('.ap-bento-item');
    if (!('IntersectionObserver' in window)) {
        reveals.forEach(el => el.classList.add('is-visible'));
        bento.forEach(el => el.classList.add('is-visible'));
        return;
    }
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('is-visible');
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
    reveals.forEach(el => obs.observe(el));
    bento.forEach(el => obs.observe(el));
})();

/* Ripple */
document.querySelectorAll('.ap-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const r = this.getBoundingClientRect();
        const ripple = document.createElement('span');
        ripple.className = 'ripple';
        const s = Math.max(r.width, r.height);
        ripple.style.width = ripple.style.height = s + 'px';
        ripple.style.left = (e.clientX - r.left - s/2) + 'px';
        ripple.style.top = (e.clientY - r.top - s/2) + 'px';
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
});

/* Табы */
document.querySelectorAll('.lv-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        document.querySelectorAll('.lv-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        document.querySelectorAll('.lv-tab-content').forEach(c => {
            c.style.display = c.dataset.content === target ? 'block' : 'none';
            if (c.dataset.content === target) {
                c.style.animation = 'none';
                c.offsetHeight;
                c.style.animation = 'msgIn 0.4s ease-out';
            }
        });
    });
});

/* Интерактив */
function selectLesson(el) {
    document.querySelectorAll('.lv-lesson').forEach(l => l.classList.remove('lv-lesson--selected'));
    el.classList.add('lv-lesson--selected');
    showToast('Занятие выбрано');
}
function setMark(radio) {
    const group = radio.parentElement;
    const value = radio.dataset.value;
    const row = radio.closest('tr');
    group.querySelectorAll('.lv-radio').forEach(r => {
        r.classList.remove('lv-radio--check-active','lv-radio--x-active','lv-radio--dash-active');
    });
    if (value === '1') {
        radio.classList.add('lv-radio--check-active');
        row.classList.add('present'); row.classList.remove('absent');
        row.dataset.mark = '1';
    } else if (value === '0') {
        radio.classList.add('lv-radio--x-active');
        row.classList.add('absent'); row.classList.remove('present');
        row.dataset.mark = '0';
    } else {
        radio.classList.add('lv-radio--dash-active');
        row.classList.remove('present','absent');
        row.dataset.mark = 'null';
    }
    updateCounters();
}
function updateCounters() {
    let p = 0, a = 0, n = 0;
    document.querySelectorAll('#attTable tbody tr').forEach(row => {
        const m = row.dataset.mark;
        if (m === '1') p++;
        else if (m === '0') a++;
        else n++;
    });
    document.getElementById('cnt-present').textContent = p;
    document.getElementById('cnt-absent').textContent = a;
    document.getElementById('cnt-na').textContent = n;
    document.getElementById('attSummary').textContent = `было ${p} из 5`;
}
function markAll(v) {
    document.querySelectorAll('#attTable tbody tr').forEach(row => {
        const r = row.querySelector(`.lv-radio[data-value="${v}"]`);
        if (r) setMark(r);
    });
    showToast(v === 1 ? 'Отмечено: все были' : 'Отметки сброшены');
}
function saveAttendance(e) {
    const p = parseInt(document.getElementById('cnt-present').textContent);
    const a = parseInt(document.getElementById('cnt-absent').textContent);
    showToast(`✅ Сохранено: было ${p}, пропустило ${a}`);
}
function toggleWork(el) {
    const row = el.closest('.work-row');
    const label = row.querySelector('.work-label');
    const state = row.dataset.state === '1';
    if (state) {
        el.classList.remove('lv-switch--on');
        row.style.background = '';
        row.dataset.state = '0';
        label.textContent = 'Не сдал';
        label.style.color = 'var(--ap-gray)';
    } else {
        el.classList.add('lv-switch--on');
        row.style.background = 'rgba(48,209,88,0.08)';
        row.dataset.state = '1';
        label.textContent = 'Сдал';
        label.style.color = '#30d158';
    }
    updateWorksCount();
}
function updateWorksCount() {
    let c = 0;
    document.querySelectorAll('.work-row').forEach(r => { if (r.dataset.state === '1') c++; });
    document.getElementById('worksCount').textContent = c;
}
function showToast(text) {
    const t = document.getElementById('apToast');
    document.getElementById('apToastText').textContent = text;
    t.classList.add('show');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => t.classList.remove('show'), 2200);
}

/* ============================================================
   БОТ
   ============================================================ */
const botState = {
    role: 'guest', group: null, teacher: null,
    subs: { next_lesson: false, weather: false, schedule_tomorrow: false },
    awaiting: null,
};

const LESSON_TIMES = {
    '1': '9:00-10:35', '2': '10:50-12:25', '3': '13:20-14:55',
    '4': '15:10-16:45', '5': '17:00-18:35', '6': '18:50-20:20'
};

const MOCK_SCHEDULE = {
    today: [
        { num: 1, subject: 'Технология разработки ПО', type: 'лек', place: '7405', group: 'ИСт-241' },
        { num: 2, subject: 'Разработка кода ИС', type: 'лаб', place: '1311', group: 'ИСт-241', subgroup: '2' },
        { num: 3, subject: 'Элементы САПР', type: 'пр', place: '1337', group: 'ПМт-241', subgroup: '2' },
    ],
    tomorrow: [
        { num: 1, subject: 'Разработка кода ИС', type: 'лаб', place: '1311', group: 'ИСт-242', subgroup: '2' },
        { num: 3, subject: 'Компьютерная графика', type: 'лаб', place: '1311', group: 'РУтс-261' },
    ],
    week: [
        { date: 'Понедельник, 06.10', lessons: [
            { num: 1, subject: 'Разработка кода ИС', type: 'лек', place: '1 лекционная', group: 'ИСт-241, ИСт-242, ИСт-243' },
            { num: 3, subject: 'Компьютерная графика', type: 'лаб', place: '1311', group: 'РУтс-261' },
        ]},
        { date: 'Вторник, 07.10', lessons: [
            { num: 1, subject: 'Разработка кода ИС', type: 'лаб', place: '1311', group: 'ИСт-243', subgroup: '1' },
            { num: 2, subject: 'Разработка кода ИС', type: 'лаб', place: '1311', group: 'ИСт-243', subgroup: '2' },
            { num: 4, subject: 'Технология разработки ПО', type: 'пр', place: '1337', group: 'ИСт-241', subgroup: '2' },
        ]},
        { date: 'Среда, 08.10', lessons: [
            { num: 1, subject: 'Технология разработки ПО', type: 'лек', place: '4 лекционная', group: 'ИСт-241, ИСт-242, ИСт-243' },
            { num: 2, subject: 'Разработка кода ИС', type: 'лаб', place: '1311', group: 'ИСт-242', subgroup: '2' },
            { num: 3, subject: 'Компьютерная графика', type: 'лек', place: '1254', group: 'РУтс-261' },
        ]},
    ]
};

function formatTime(num) { return LESSON_TIMES[String(num)] || '—'; }
function shortenType(t) {
    t = (t || '').toLowerCase();
    if (t.includes('лек')) return 'л.';
    if (t.includes('прак') || t.includes('пр.')) return 'пр.';
    if (t.includes('лаб')) return 'л.р.';
    if (t.includes('экз')) return 'экз.';
    if (t.includes('зач')) return 'зач.';
    return t || 'зан.';
}
function renderLessonsForDay(lessons) {
    if (!lessons.length) return '🎉 Пар нет! Отдыхайте';
    const grouped = {};
    lessons.forEach(l => { (grouped[l.num] = grouped[l.num] || []).push(l); });
    const lines = [];
    Object.keys(grouped).sort((a,b) => a-b).forEach(num => {
        const list = grouped[num];
        lines.push(`🔸 Пара ${num} | ${formatTime(num)}`);
        if (list.length === 1) {
            const l = list[0];
            lines.push(`📚 ${l.subject}`);
            lines.push(`🏫 ${shortenType(l.type)}${l.subgroup ? ' — подгр. ' + l.subgroup : ''}`);
            lines.push(`📍 ${l.place}`);
            if (l.group) lines.push(`👥 ${l.group}`);
            lines.push('');
        } else {
            list.forEach(l => {
                lines.push(`┌─ ${l.subject}`);
                lines.push(`│  🏫 ${shortenType(l.type)}${l.subgroup ? ' — подгр. ' + l.subgroup : ''}`);
                lines.push(`│  📍 ${l.place}`);
                if (l.group) lines.push(`│  👥 ${l.group}`);
                lines.push(`└─────────────`);
            });
            lines.push('');
        }
    });
    return lines.join('\n').trim();
}
function addBotMessage(html, delay = 0, withTyping = false) {
    const container = document.getElementById('botMessages');
    const status = document.getElementById('botStatus');
    if (withTyping) {
        const t = document.createElement('div');
        t.className = 'tg-typing';
        t.innerHTML = '<span></span><span></span><span></span>';
        container.appendChild(t);
        scrollChat();
        if (status) status.textContent = 'печатает...';
    }
    setTimeout(() => {
        document.querySelectorAll('.tg-typing').forEach(x => x.remove());
        const msg = document.createElement('div');
        msg.className = 'tg-msg tg-msg--bot';
        msg.innerHTML = html.replace(/\n/g, '<br>');
        container.appendChild(msg);
        scrollChat();
        if (status) status.textContent = 'онлайн';
    }, delay);
}
function addUserMessage(text) {
    const c = document.getElementById('botMessages');
    const m = document.createElement('div');
    m.className = 'tg-msg tg-msg--user';
    m.textContent = text;
    c.appendChild(m);
    scrollChat();
}
function scrollChat() {
    const c = document.getElementById('botMessages');
    c.scrollTop = c.scrollHeight;
}
function showMainMenu() {
    let text = '📋 <b>Главное меню</b>\n';
    if (botState.role === 'student' && botState.group) text += `Твоя группа: <b>${botState.group}</b>\n`;
    else if (botState.role === 'teacher' && botState.teacher) text += `Преподаватель: <b>${botState.teacher}</b>\n`;
    else text += 'Режим гостя\n';
    text += '\nВыбери действие:';
    const btns = [];
    if (botState.role === 'student' || botState.role === 'teacher') {
        btns.push(
            `<button class="tg-btn" onclick="botAction('today')">📅 Сегодня</button>` +
            `<button class="tg-btn" onclick="botAction('tomorrow')">📆 Завтра</button>`
        );
        btns.push(`<button class="tg-btn" onclick="botAction('week')">📊 На всю неделю</button>`);
        btns.push(`<button class="tg-btn" onclick="botAction('subscriptions')">🔔 Мои подписки</button>`);
    }
    btns.push(
        `<button class="tg-btn" onclick="botAction('teacher')">👨‍🏫 Преподаватель</button>` +
        `<button class="tg-btn" onclick="botAction('group')">👥 Группа</button>`
    );
    btns.push(`<button class="tg-btn" onclick="botAction('classroom')">🏫 Аудитория</button>`);
    btns.push(`<button class="tg-btn" onclick="botAction('settings')">⚙️ Настройки</button>`);
    addBotMessage(text + '<div class="tg-buttons">' + btns.join('') + '</div>', 350, true);
    botState.awaiting = null;
}
function botShowStart() {
    document.getElementById('botMessages').innerHTML = '';
    botState.role = 'guest'; botState.group = null; botState.teacher = null;
    botState.subs = { next_lesson: false, weather: false, schedule_tomorrow: false };
    botState.awaiting = null;
    addBotMessage(
        '👋 Привет! Я <b>КузГТУшник</b>.\nЯ помогу тебе узнать расписание занятий.\nВыбери, кто ты:' +
        '<div class="tg-buttons">' +
        '<button class="tg-btn" onclick="botRole(\'student\')">🎓 Я студент</button>' +
        '<button class="tg-btn" onclick="botRole(\'teacher\')">👨‍🏫 Я преподаватель</button>' +
        '<button class="tg-btn" onclick="botRole(\'guest\')">👤 Я гость</button>' +
        '</div>', 100, true);
}
function botRole(role) {
    const labels = { student: '🎓 Я студент', teacher: '👨‍🏫 Я преподаватель', guest: '👤 Я гость' };
    addUserMessage(labels[role]);
    if (role === 'student') {
        botState.role = 'student';
        botState.awaiting = 'group';
        addBotMessage('Отлично!\nНапиши номер своей группы.\nНапример: ИСт-241', 400, true);
    } else if (role === 'teacher') {
        botState.role = 'teacher';
        botState.awaiting = 'teacher';
        addBotMessage('Понял!\nНапиши свою фамилию.', 400, true);
    } else {
        botState.role = 'guest';
        showMainMenu();
    }
}
function botAction(action) {
    const labels = {
        today: '📅 Сегодня', tomorrow: '📆 Завтра', week: '📊 На всю неделю',
        teacher: '👨‍🏫 Преподаватель', group: '👥 Группа', classroom: '🏫 Аудитория',
        settings: '⚙️ Настройки', subscriptions: '🔔 Мои подписки', back: '🔙 Назад',
    };
    if (labels[action]) addUserMessage(labels[action]);
    if (action === 'today') {
        const text = `📅 <b>Расписание на сегодня</b>\n━━━━━━━━━━━━━━━━━━━━\n┃ Среда\n┃ ${new Date().toLocaleDateString('ru-RU')}\n┗━━━━━━━━━━━━━━━━━━━━\n\n` +
            renderLessonsForDay(MOCK_SCHEDULE.today);
        addBotMessage(text, 400, true);
    } else if (action === 'tomorrow') {
        const text = `📅 <b>Расписание на завтра</b>\n━━━━━━━━━━━━━━━━━━━━\n┃ Четверг\n┃ ${new Date(Date.now()+86400000).toLocaleDateString('ru-RU')}\n┗━━━━━━━━━━━━━━━━━━━━\n\n` +
            renderLessonsForDay(MOCK_SCHEDULE.tomorrow);
        addBotMessage(text, 400, true);
    } else if (action === 'week') {
        let text = '📅 <b>Расписание на неделю</b>\n━━━━━━━━━━━━━━━━━━━━\n';
        MOCK_SCHEDULE.week.forEach(d => {
            text += `\n━━━ <b>${d.date}</b> ━━━\n\n`;
            text += renderLessonsForDay(d.lessons) + '\n';
        });
        addBotMessage(text, 400, true);
    } else if (action === 'teacher') {
        botState.awaiting = 'teacher_search';
        addBotMessage('👨‍🏫 <b>Расписание преподавателя</b>\n\nНапиши только фамилию преподавателя:', 400, true);
    } else if (action === 'group') {
        botState.awaiting = 'group_search';
        addBotMessage('👥 <b>Расписание группы</b>\n\nНапиши номер группы:', 400, true);
    } else if (action === 'classroom') {
        botState.awaiting = 'classroom_search';
        addBotMessage('🏫 <b>Расписание аудитории</b>\n\nНапиши номер аудитории (например, 1311):', 400, true);
    } else if (action === 'settings') {
        let text = '⚙️ <b>Настройки</b>\n';
        if (botState.role === 'student' && botState.group) text += `Роль: Студент\nГруппа: <b>${botState.group}</b>\n`;
        else if (botState.role === 'teacher' && botState.teacher) text += `Роль: Преподаватель\nФИО: <b>${botState.teacher}</b>\n`;
        else text += 'Роль: Гость\n';
        text += '\n<div class="tg-buttons">' +
            '<button class="tg-btn" onclick="botAction(\'change_role\')">🔄 Сменить роль</button>' +
            '<button class="tg-btn" onclick="botAction(\'back\')">🔙 Назад в меню</button></div>';
        addBotMessage(text, 400, true);
    } else if (action === 'subscriptions') {
        const s = botState.subs;
        addBotMessage('🔔 <b>Мои подписки</b>\n\n<div class="tg-buttons">' +
            `<button class="tg-btn" onclick="botToggleSub('next_lesson')">${s.next_lesson ? '✅' : '❌'} Следующая пара</button>` +
            `<button class="tg-btn" onclick="botToggleSub('weather')">${s.weather ? '✅' : '❌'} 🌤️ Погода (7:40)</button>` +
            `<button class="tg-btn" onclick="botToggleSub('schedule_tomorrow')">${s.schedule_tomorrow ? '✅' : '❌'} 📅 Расписание на завтра (20:00)</button>` +
            '<button class="tg-btn" onclick="botAction(\'back\')">🔙 Назад</button></div>', 400, true);
    } else if (action === 'back') {
        showMainMenu();
    } else if (action === 'change_role') {
        botState.role = 'guest'; botState.group = null; botState.teacher = null;
        addBotMessage('Роль сброшена. Выбери новую:\n<div class="tg-buttons">' +
            '<button class="tg-btn" onclick="botRole(\'student\')">🎓 Я студент</button>' +
            '<button class="tg-btn" onclick="botRole(\'teacher\')">👨‍🏫 Я преподаватель</button>' +
            '<button class="tg-btn" onclick="botRole(\'guest\')">👤 Я гость</button></div>', 400, true);
    }
}
function botToggleSub(key) {
    botState.subs[key] = !botState.subs[key];
    const s = botState.subs;
    addBotMessage('🔔 <b>Мои подписки</b>\n\n<div class="tg-buttons">' +
        `<button class="tg-btn" onclick="botToggleSub('next_lesson')">${s.next_lesson ? '✅' : '❌'} Следующая пара</button>` +
        `<button class="tg-btn" onclick="botToggleSub('weather')">${s.weather ? '✅' : '❌'} 🌤️ Погода (7:40)</button>` +
        `<button class="tg-btn" onclick="botToggleSub('schedule_tomorrow')">${s.schedule_tomorrow ? '✅' : '❌'} 📅 Расписание на завтра (20:00)</button>` +
        '<button class="tg-btn" onclick="botAction(\'back\')">🔙 Назад</button></div>', 200, false);
}
function botQuick(cmd) {
    if (cmd === 'start') botShowStart();
    else if (cmd === 'help') {
        addUserMessage('❓ Помощь');
        addBotMessage(
            '❓ <b>Что я умею</b>\n\n' +
            '📅 Расписание на сегодня\n' +
            '📆 Расписание на завтра\n' +
            '📊 Расписание на неделю\n' +
            '👨‍🏫 Найти преподавателя\n' +
            '👥 Найти группу\n' +
            '🏫 Найти аудиторию\n' +
            '🔔 Подписки на уведомления', 200, true);
        setTimeout(showMainMenu, 900);
    }
    else if (['today','tomorrow','week','teacher','group'].includes(cmd)) botAction(cmd);
}
function botUserMessage() {
    const input = document.getElementById('botInput');
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    addUserMessage(text);
    setTimeout(() => {
        const awaiting = botState.awaiting;
        if (awaiting === 'group') {
            botState.group = text.toUpperCase();
            botState.awaiting = null;
            addBotMessage(`✅ Готово! Ты зарегистрирован как студент группы <b>${botState.group}</b>.`, 350, true);
            setTimeout(showMainMenu, 900);
        } else if (awaiting === 'teacher') {
            botState.teacher = text;
            botState.awaiting = null;
            addBotMessage(`✅ Готово! Ты зарегистрирован как преподаватель <b>${text}</b>.`, 350, true);
            setTimeout(showMainMenu, 900);
        } else if (awaiting === 'teacher_search') {
            botState.awaiting = null;
            addBotMessage(`👨‍🏫 <b>Расписание: ${text}</b>\n━━━━━━━━━━━━━━━━━━━━\n┃ Среда\n┃ ${new Date().toLocaleDateString('ru-RU')}\n┗━━━━━━━━━━━━━━━━━━━━\n\n` +
                renderLessonsForDay(MOCK_SCHEDULE.today) +
                '\n<div class="tg-buttons"><button class="tg-btn" onclick="botAction(\'back\')">🔙 Назад</button></div>', 400, true);
        } else if (awaiting === 'group_search') {
            botState.awaiting = null;
            const g = text.toUpperCase();
            addBotMessage(`👥 <b>Расписание группы ${g}</b>\n━━━━━━━━━━━━━━━━━━━━\n┃ Среда\n┃ ${new Date().toLocaleDateString('ru-RU')}\n┗━━━━━━━━━━━━━━━━━━━━\n\n` +
                renderLessonsForDay(MOCK_SCHEDULE.today) +
                '\n<div class="tg-buttons"><button class="tg-btn" onclick="botAction(\'back\')">🔙 Назад</button></div>', 400, true);
        } else if (awaiting === 'classroom_search') {
            botState.awaiting = null;
            addBotMessage(`🏫 <b>Расписание аудитории ${text}</b>\n━━━━━━━━━━━━━━━━━━━━\n┃ Среда\n┃ ${new Date().toLocaleDateString('ru-RU')}\n┗━━━━━━━━━━━━━━━━━━━━\n\n` +
                '🔸 Пара 2 | 10:50-12:25\n📚 Разработка кода ИС\n🏫 л.р. — подгр. 2\n📍 ' + text + '\n👥 ИСт-241\n\n' +
                '🔸 Пара 3 | 13:20-14:55\n📚 Компьютерная графика\n🏫 лаб.\n📍 ' + text + '\n👥 РУтс-261\n' +
                '\n<div class="tg-buttons"><button class="tg-btn" onclick="botAction(\'back\')">🔙 Назад</button></div>', 400, true);
        } else {
            const lower = text.toLowerCase();
            if (lower.includes('сегодня') || lower.includes('today')) botAction('today');
            else if (lower.includes('завтра') || lower.includes('tomorrow')) botAction('tomorrow');
            else if (lower.includes('недел')) botAction('week');
            else if (lower.includes('привет') || lower.includes('здрав') || lower.includes('hello')) {
                addBotMessage('Привет! 👋 Чем помочь? Открой меню кнопкой ниже.', 300, true);
                setTimeout(showMainMenu, 900);
            } else {
                addBotMessage('Не понял команду 🤔\nВот главное меню:', 300, true);
                setTimeout(showMainMenu, 900);
            }
        }
    }, 300);
}

/* Инициализация */
document.addEventListener('DOMContentLoaded', () => {
    updateCounters();
    updateWorksCount();
    botShowStart();
});
</script>

</body>
</html>