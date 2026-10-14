<?php
// Страница автора проекта КузГТУшник и АИС-Расписание
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ерошевич Кирилл Владимирович — автор КузГТУшника и АИС-Расписание</title>
    <link rel="icon" type="image/png" href="asset/kuzgtushnik.png">
    <link rel="apple-touch-icon" href="asset/kuzgtushnik.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --ap-bg: #ffffff;
    --ap-bg-alt: #fafafa;
    --ap-bg-soft: #f5f5f7;
    --ap-dark: #1d1d1f;
    --ap-gray: #6e6e73;
    --ap-gray-light: #d2d2d7;
    --ap-border: #e8e8ed;
    --ap-text-on-dark: #f5f5f7;
    --ap-text-muted-dark: #a1a1a6;
    --ap-shadow: rgba(0,0,0,0.06);
    --ap-shadow-strong: rgba(0,0,0,0.12);
    --ap-accent: #0071e3;
    --ap-accent-hover: #0077ed;
    --ap-green: #30d158;
    --ap-red: #ff453a;
    --ap-orange: #ff9f0a;
    --ap-purple: #bf5af2;
    --ap-glow-1: rgba(0,113,227,0.06);
    --ap-glow-2: rgba(191,90,242,0.06);
}
[data-theme="dark"] {
    --ap-bg: #000000;
    --ap-bg-alt: #0a0a0c;
    --ap-bg-soft: #101012;
    --ap-dark: #f5f5f7;
    --ap-gray: #a1a1a6;
    --ap-gray-light: #4a4a4c;
    --ap-border: #232325;
    --ap-text-on-dark: #f5f5f7;
    --ap-text-muted-dark: #a1a1a6;
    --ap-shadow: rgba(0,0,0,0.5);
    --ap-shadow-strong: rgba(0,0,0,0.7);
    --ap-accent: #0a84ff;
    --ap-accent-hover: #3395ff;
    --ap-glow-1: rgba(10,132,255,0.12);
    --ap-glow-2: rgba(191,90,242,0.12);
}

html, body {
    margin: 0; padding: 0;
    background: var(--ap-bg);
    color: var(--ap-dark);
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, sans-serif;
    overflow-x: hidden;
    transition: background-color 0.5s ease, color 0.5s ease;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    letter-spacing: -0.01em;
}
* { box-sizing: border-box; }

.theme-toggle {
    position: fixed;
    top: 24px; right: 24px;
    z-index: 9998;
    width: 44px; height: 44px;
    border-radius: 50%;
    background: rgba(255,255,255,0.72);
    backdrop-filter: saturate(180%) blur(20px);
    -webkit-backdrop-filter: saturate(180%) blur(20px);
    border: 1px solid var(--ap-border);
    color: var(--ap-dark);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
}
[data-theme="dark"] .theme-toggle { background: rgba(29,29,31,0.72); }
.theme-toggle:hover { transform: scale(1.06); border-color: var(--ap-accent); color: var(--ap-accent); }
.theme-toggle i { position: absolute; transition: opacity 0.3s, transform 0.3s; }
[data-theme="dark"] .icon-sun { opacity: 0; transform: rotate(90deg); }
[data-theme="dark"] .icon-moon { opacity: 1; transform: rotate(0); }
:root .icon-sun { opacity: 1; transform: rotate(0); }
:root .icon-moon { opacity: 0; transform: rotate(-90deg); }
@media (max-width: 767px) { .theme-toggle { top: 16px; right: 16px; width: 40px; height: 40px; } }

.back-btn {
    position: fixed;
    top: 24px; left: 24px;
    z-index: 9998;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 44px;
    padding: 0 18px;
    border-radius: 980px;
    background: rgba(255,255,255,0.72);
    backdrop-filter: saturate(180%) blur(20px);
    -webkit-backdrop-filter: saturate(180%) blur(20px);
    border: 1px solid var(--ap-border);
    color: var(--ap-dark);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 400;
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
}
[data-theme="dark"] .back-btn { background: rgba(29,29,31,0.72); }
.back-btn:hover { color: var(--ap-accent); border-color: var(--ap-accent); transform: translateY(-1px); }
@media (max-width: 767px) {
    .back-btn { top: 16px; left: 16px; height: 40px; padding: 0 14px; font-size: 0.8rem; }
    .back-btn span { display: none; }
}

.scroll-progress {
    position: fixed;
    top: 0; left: 0;
    height: 2px; width: 0%;
    background: var(--ap-accent);
    z-index: 9999;
    transition: width 0.1s ease;
    pointer-events: none;
}
.cursor-glow {
    position: fixed;
    width: 600px; height: 600px;
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
    background: radial-gradient(circle, var(--ap-glow-1) 0%, transparent 60%);
    transform: translate(-50%,-50%);
    transition: opacity 0.5s;
    opacity: 0;
    mix-blend-mode: multiply;
}
[data-theme="dark"] .cursor-glow { mix-blend-mode: screen; }
.cursor-glow.active { opacity: 1; }

.ap-page { position: relative; overflow-x: hidden; width: 100%; }
.ap-container { max-width: 980px; margin: 0 auto; width: 100%; padding: 0 24px; position: relative; z-index: 1; }
.ap-center { text-align: center; }

.ap-h2 {
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 600; line-height: 1.08; letter-spacing: -0.03em;
    margin: 0 0 20px; color: var(--ap-dark);
}
.ap-lead {
    font-size: clamp(1.05rem, 1.8vw, 1.35rem);
    line-height: 1.5; color: var(--ap-gray);
    max-width: 680px; margin: 0 auto 40px;
    font-weight: 400;
}
.ap-section { padding: clamp(80px, 12vw, 140px) 0; position: relative; }
.ap-section--gray { background: var(--ap-bg-alt); }
@keyframes gradientShift {
    0%,100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

/* HERO */
.author-hero {
    padding: clamp(120px, 14vw, 180px) 0 100px;
    text-align: center;
    background: var(--ap-bg);
    position: relative; overflow: hidden;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
}
.author-hero::before {
    content: '';
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 80% 60% at 50% 0%, var(--ap-glow-1) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 80% 80%, var(--ap-glow-2) 0%, transparent 60%);
    pointer-events: none; z-index: 0;
}
.author-hero-content { position: relative; z-index: 2; }

.ap-particles { position: absolute; inset: 0; pointer-events: none; overflow: hidden; z-index: 0; }
.ap-particle {
    position: absolute;
    border-radius: 50%;
    background: var(--ap-glow-1);
    animation: floatUp 20s linear infinite;
}
@keyframes floatUp {
    0% { transform: translateY(100vh) scale(0); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-100px) scale(1); opacity: 0; }
}

.author-avatar {
    position: relative;
    width: 160px;
    height: 160px;
    margin: 0 auto 40px;
    animation: avatarFloat 6s ease-in-out infinite;
}
.author-avatar-inner {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #0071e3 0%, #bf5af2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    box-shadow:
        0 40px 80px rgba(0,113,227,0.20),
        0 0 0 4px var(--ap-bg),
        0 0 0 5px var(--ap-border);
}
.author-avatar-inner::before {
    content: '';
    position: absolute;
    inset: -20%;
    background:
        radial-gradient(circle at 30% 30%, rgba(255,255,255,0.45) 0%, transparent 50%),
        radial-gradient(circle at 70% 70%, rgba(255,255,255,0.15) 0%, transparent 50%);
    animation: avatarShine 10s ease-in-out infinite;
}
@keyframes avatarShine {
    0%,100% { transform: translate(0,0) rotate(0deg); }
    33% { transform: translate(5%,-5%) rotate(3deg); }
    66% { transform: translate(-5%,5%) rotate(-3deg); }
}
.author-avatar-inner span {
    position: relative;
    z-index: 1;
    color: #fff;
    font-size: 3.6rem;
    font-weight: 500;
    letter-spacing: -0.04em;
}
@keyframes avatarFloat {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-8px); }
}
.author-avatar-glow {
    position: absolute;
    inset: -40px;
    background: radial-gradient(circle, rgba(0,113,227,0.25) 0%, transparent 65%);
    border-radius: 50%;
    filter: blur(40px);
    z-index: -1;
}
@media (max-width: 767px) {
    .author-avatar { width: 120px; height: 120px; margin-bottom: 28px; }
    .author-avatar-inner span { font-size: 2.6rem; }
}

.author-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 0.85rem; font-weight: 500;
    color: var(--ap-accent);
    margin-bottom: 24px;
    padding: 6px 16px;
    background: rgba(0,113,227,0.06);
    border-radius: 980px;
}
.author-live-dot {
    display: inline-block;
    width: 7px; height: 7px;
    background: var(--ap-green);
    border-radius: 50%;
    animation: onlinePulse 2s ease-in-out infinite;
}
@keyframes onlinePulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(48,209,88,0.6); }
    50% { box-shadow: 0 0 0 6px rgba(48,209,88,0); }
}
.author-title {
    font-size: clamp(2.5rem, 6.5vw, 5rem);
    font-weight: 600; line-height: 1.05; letter-spacing: -0.045em;
    margin: 0 auto 24px; max-width: 900px;
    color: var(--ap-dark);
}
.author-title .gradient-text {
    background: linear-gradient(120deg, #0071e3 0%, #6610f2 50%, #bf5af2 100%);
    background-size: 200% 200%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: gradientShift 8s ease-in-out infinite;
    display: inline-block;
}
.author-subtitle {
    font-size: clamp(1rem, 1.6vw, 1.25rem);
    line-height: 1.5; color: var(--ap-gray);
    max-width: 640px; margin: 0 auto 48px;
    font-weight: 400;
}
.author-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    margin-bottom: 48px;
}
.author-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 980px;
    background: transparent;
    border: 1px solid var(--ap-border);
    color: var(--ap-gray);
    font-size: 0.85rem;
    font-weight: 400;
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
}
.author-chip:hover {
    border-color: var(--ap-dark);
    color: var(--ap-dark);
}
.author-chip i { font-size: 0.95rem; }

.ap-btn {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 1rem; font-weight: 400;
    padding: 14px 28px;
    border-radius: 980px;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
    border: none; cursor: pointer;
    font-family: inherit;
    letter-spacing: -0.005em;
    position: relative; overflow: hidden;
    isolation: isolate;
}
.ap-btn--primary {
    background: var(--ap-accent); color: #fff;
}
.ap-btn--primary:hover {
    background: var(--ap-accent-hover);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(0,113,227,0.30);
}
.ap-btn--ghost {
    background: transparent;
    color: var(--ap-accent);
    border: 1px solid var(--ap-accent);
}
.ap-btn--ghost:hover {
    background: var(--ap-accent);
    color: #fff;
    transform: translateY(-1px);
}
.author-hero-actions {
    display: flex; gap: 12px;
    justify-content: center; flex-wrap: wrap;
}

/* BIO */
.bio-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 60px;
}
@media (max-width: 900px) { .bio-grid { grid-template-columns: 1fr; } }
.bio-card {
    background: var(--ap-bg);
    border-radius: 24px;
    border: 1px solid var(--ap-border);
    padding: 40px 36px;
    transition: all 0.5s cubic-bezier(0.16,1,0.3,1);
    opacity: 0;
    transform: translateY(24px);
}
.bio-card.is-visible { opacity: 1; transform: translateY(0); }
.bio-card:hover {
    border-color: var(--ap-dark);
    transform: translateY(-4px);
    box-shadow: 0 24px 48px var(--ap-shadow);
}
.bio-card-icon {
    width: 52px; height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: #fff;
    margin-bottom: 24px;
    background: var(--bio-icon-bg, linear-gradient(135deg, #0071e3, #4a90ff));
}
.bio-card-year {
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--ap-gray);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.bio-card h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--ap-dark);
    margin-bottom: 14px;
    line-height: 1.25;
    letter-spacing: -0.02em;
}
.bio-card p {
    font-size: 0.98rem;
    line-height: 1.6;
    color: var(--ap-gray);
    margin: 0;
}
.bio-card strong { color: var(--ap-dark); font-weight: 500; }

.timeline {
    margin-top: 80px;
    position: relative;
    padding-left: 32px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 7px; top: 8px; bottom: 8px;
    width: 1px;
    background: var(--ap-border);
}
.timeline-item {
    position: relative;
    padding: 20px 0 20px 24px;
    opacity: 0;
    transform: translateX(-16px);
    transition: all 0.7s cubic-bezier(0.16,1,0.3,1);
}
.timeline-item.is-visible { opacity: 1; transform: translateX(0); }
.timeline-dot {
    position: absolute;
    left: -32px;
    top: 30px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--ap-dark);
    transition: all 0.3s;
}
.timeline-item:hover .timeline-dot {
    transform: scale(1.5);
    background: var(--ap-accent);
}
.timeline-year {
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--ap-gray);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 8px;
}
.timeline-title {
    font-size: 1.15rem;
    font-weight: 500;
    color: var(--ap-dark);
    margin-bottom: 6px;
}
.timeline-desc {
    font-size: 0.95rem;
    color: var(--ap-gray);
    line-height: 1.6;
}
.timeline-desc strong { color: var(--ap-dark); font-weight: 500; }

/* ПРОЕКТЫ */
.projects-wrap {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-top: 60px;
}
.project-detail {
    background: var(--ap-bg);
    border-radius: 24px;
    border: 1px solid var(--ap-border);
    padding: 48px;
    opacity: 0;
    transform: translateY(24px);
    transition: all 0.6s cubic-bezier(0.16,1,0.3,1);
}
.project-detail.is-visible { opacity: 1; transform: translateY(0); }
.project-detail:hover {
    border-color: var(--ap-dark);
    box-shadow: 0 32px 64px var(--ap-shadow);
}
@media (max-width: 767px) {
    .project-detail { padding: 32px 24px; border-radius: 20px; }
}

.project-detail-head {
    display: flex;
    gap: 20px;
    align-items: flex-start;
    margin-bottom: 28px;
}
@media (max-width: 640px) {
    .project-detail-head { flex-direction: column; gap: 16px; }
}
.project-detail-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}
.project-detail-icon svg { width: 100%; height: 100%; border-radius: 16px; }
.project-detail-icon i { color: #fff; font-size: 1.8rem; }
.project-detail-meta { flex: 1; min-width: 0; }
.project-detail-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--ap-gray);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 10px;
}
.project-detail-title {
    font-size: clamp(1.4rem, 2.8vw, 1.75rem);
    font-weight: 600;
    color: var(--ap-dark);
    margin: 0 0 6px;
    line-height: 1.25;
    letter-spacing: -0.02em;
}
.project-detail-subtitle {
    font-size: 0.95rem;
    color: var(--ap-gray);
    margin: 0;
    line-height: 1.5;
}
.project-detail-body {
    font-size: 1rem;
    line-height: 1.65;
    color: var(--ap-gray);
}
.project-detail-body p { margin: 0 0 14px; }
.project-detail-body p:last-child { margin-bottom: 0; }
.project-detail-body strong { color: var(--ap-dark); font-weight: 500; }
.project-detail-body code {
    background: var(--ap-bg-soft);
    padding: 2px 8px;
    border-radius: 6px;
    font-family: "SF Mono", Monaco, monospace;
    font-size: 0.9em;
    color: var(--ap-accent);
}

.project-assistants {
    display: flex;
    gap: 10px;
    margin: 20px 0;
    flex-wrap: wrap;
}
.project-assistant {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    overflow: hidden;
    transition: transform 0.4s cubic-bezier(0.16,1,0.3,1);
    display: flex;
    align-items: center;
    justify-content: center;
}
.project-assistant svg { width: 100%; height: 100%; border-radius: 14px; }
.project-assistant:hover { transform: scale(1.08) translateY(-2px); }

.project-tech {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 24px 0;
}
.project-tech-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 980px;
    background: transparent;
    color: var(--ap-gray);
    font-size: 0.8rem;
    font-weight: 400;
    border: 1px solid var(--ap-border);
    transition: all 0.3s;
}
.project-tech-badge:hover {
    border-color: var(--ap-dark);
    color: var(--ap-dark);
}
.project-tech-badge i { font-size: 0.9rem; }

.project-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 8px 24px;
    margin: 24px 0 0;
    padding: 0;
    list-style: none;
}
.project-features li {
    position: relative;
    padding-left: 22px;
    font-size: 0.95rem;
    color: var(--ap-gray);
    line-height: 1.6;
}
.project-features li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 10px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--ap-green);
}

.project-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 32px;
    padding-top: 28px;
    border-top: 1px solid var(--ap-border);
}
.project-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 12px 22px;
    border-radius: 980px;
    background: transparent;
    color: var(--ap-accent);
    text-decoration: none;
    font-size: 0.92rem;
    font-weight: 400;
    transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
    border: 1px solid var(--ap-accent);
}
.project-link-btn:hover {
    background: var(--ap-accent);
    color: #fff;
    transform: translateY(-1px);
}
.project-link-btn i { font-size: 0.95rem; }
.project-link-btn--primary {
    background: var(--ap-accent);
    color: #fff;
}
.project-link-btn--primary:hover {
    background: var(--ap-accent-hover);
    box-shadow: 0 12px 28px rgba(0,113,227,0.30);
}

/* КОНТАКТЫ */
.contacts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    margin-top: 48px;
    max-width: 640px;
    margin-left: auto;
    margin-right: auto;
}
.contact-card {
    background: var(--ap-bg);
    border-radius: 20px;
    border: 1px solid var(--ap-border);
    padding: 32px 24px;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
    opacity: 0;
    transform: translateY(16px);
}
.contact-card.is-visible { opacity: 1; transform: translateY(0); }
.contact-card:hover {
    border-color: var(--ap-dark);
    transform: translateY(-4px);
    box-shadow: 0 24px 48px var(--ap-shadow);
}
.contact-icon {
    width: 44px;
    height: 44px;
    margin: 0 auto 16px;
    border-radius: 12px;
    background: var(--ap-bg-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: var(--ap-dark);
    transition: all 0.3s;
}
.contact-card:hover .contact-icon {
    background: var(--ap-accent);
    color: #fff;
    transform: scale(1.05);
}
.contact-label {
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--ap-gray);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 6px;
}
.contact-value {
    font-size: 1rem;
    font-weight: 500;
    color: var(--ap-dark);
    word-break: break-word;
}

/* CTA */
.author-cta {
    text-align: center;
    padding: clamp(80px,10vw,140px) 0;
    background: var(--ap-bg);
    position: relative;
    overflow: hidden;
    border-top: 1px solid var(--ap-border);
}
.author-cta::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at center, var(--ap-glow-1) 0%, transparent 60%);
    pointer-events: none;
}
.author-cta .ap-container { position: relative; z-index: 1; }

/* REVEAL */
.ap-reveal {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.9s cubic-bezier(0.16,1,0.3,1), transform 0.9s cubic-bezier(0.16,1,0.3,1);
}
.ap-reveal.is-visible { opacity: 1; transform: translateY(0); }
.ap-reveal--d1 { transition-delay: 0.1s; }
.ap-reveal--d2 { transition-delay: 0.2s; }
.ap-reveal--d3 { transition-delay: 0.3s; }
@media (prefers-reduced-motion: reduce) {
    .ap-reveal { opacity: 1; transform: none; transition: none; }
}

.ap-toast {
    position: fixed;
    bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #1d1d1f;
    color: #fff;
    padding: 14px 24px;
    border-radius: 14px;
    font-size: 0.95rem; font-weight: 500;
    box-shadow: 0 20px 60px rgba(0,0,0,0.35);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
    z-index: 9999;
    display: flex; align-items: center; gap: 10px;
    pointer-events: none;
}
.ap-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.ap-toast i { color: #30d158; font-size: 1.2rem; }

@keyframes rippleEffect { to { transform: scale(4); opacity: 0; } }
.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    transform: scale(0);
    animation: rippleEffect 0.6s ease-out;
    pointer-events: none;
}
</style>
</head>
<body>

<div class="scroll-progress" id="scrollProgress"></div>
<div class="cursor-glow" id="cursorGlow"></div>

<a href="https://аис-расписание.рф/api_max_bot/bot_presentation.php" class="back-btn">
    <i class="bi bi-arrow-left"></i>
    <span>К презентации бота</span>
</a>

<button class="theme-toggle" id="themeToggle" title="Переключить тему">
    <i class="bi bi-sun-fill icon-sun"></i>
    <i class="bi bi-moon-stars-fill icon-moon"></i>
</button>

<div class="ap-page">

    <!-- HERO -->
    <section class="author-hero">
        <div class="ap-particles" id="particles"></div>
        <div class="ap-container author-hero-content">

            <div class="author-avatar ap-reveal">
                <div class="author-avatar-glow"></div>
                <div class="author-avatar-inner">
                    <span>ЕК</span>
                </div>
            </div>

            <div class="author-eyebrow ap-reveal ap-reveal--d1">
                <span class="author-live-dot"></span>
                Автор КузГТУшника и АИС-Расписание
            </div>

            <h1 class="author-title ap-reveal ap-reveal--d2">
                Ерошевич<br>
                <span class="gradient-text">Кирилл Владимирович</span>
            </h1>

            <p class="author-subtitle ap-reveal ap-reveal--d3">
                Преподаватель кафедры Информатики и информационных систем
                Кузбасского государственного технического университета имени Т. Ф. Горбачёва.
                Разработчик бота «КузГТУшник», голосовых навыков и веб-сервисов для КузГТУ.
            </p>

            <div class="author-meta ap-reveal ap-reveal--d3">
                <span class="author-chip">
                    <i class="bi bi-mortarboard-fill"></i>
                    Магистр ИСиТ
                </span>
                <span class="author-chip">
                    <i class="bi bi-briefcase-fill"></i>
                    Преподаватель с 2019
                </span>
                <span class="author-chip">
                    <i class="bi bi-geo-alt-fill"></i>
                    Кемерово
                </span>
            </div>

            <div class="author-hero-actions ap-reveal ap-reveal--d3">
                <a href="#projects" class="ap-btn ap-btn--primary">
                    <i class="bi bi-code-slash"></i> Мои проекты
                </a>
                <a href="https://аис-расписание.рф/api_max_bot/bot_presentation.php" class="ap-btn ap-btn--ghost">
                    <i class="bi bi-robot"></i> О боте
                </a>
            </div>

        </div>
    </section>

    <!-- ОБРАЗОВАНИЕ И КАРЬЕРА -->
    <section class="ap-section" id="about">
        <div class="ap-container">
            <div class="ap-center">
                <h2 class="ap-h2 ap-reveal">Образование и карьера</h2>
                <p class="ap-lead ap-reveal ap-reveal--d1">
                    Путь в КузГТУ — от студента до преподавателя
                </p>
            </div>

            <div class="bio-grid">

                <div class="bio-card ap-reveal"
                     style="--bio-icon-bg: linear-gradient(135deg, #0071e3, #4a90ff);">
                    <div class="bio-card-icon">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div class="bio-card-year">2017 год</div>
                    <h3>Бакалавриат</h3>
                    <p>
                        Окончил <strong>Кузбасский государственный технический
                        университет имени Т. Ф. Горбачёва</strong>
                        по направлению <strong>«Информационные системы и технологии»</strong>.
                    </p>
                </div>

                <div class="bio-card ap-reveal ap-reveal--d1"
                     style="--bio-icon-bg: linear-gradient(135deg, #bf5af2, #6610f2);">
                    <div class="bio-card-icon">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div class="bio-card-year">2019 год</div>
                    <h3>Магистратура</h3>
                    <p>
                        Продолжил обучение в <strong>КузГТУ</strong> и получил
                        <strong>степень магистра</strong> по тому же направлению —
                        <strong>«Информационные системы и технологии»</strong>.
                    </p>
                </div>

            </div>

            <div class="timeline">

                <div class="timeline-item ap-reveal">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2017</div>
                    <div class="timeline-title">Бакалавриат КузГТУ</div>
                    <div class="timeline-desc">
                        Окончил <strong>Кузбасский государственный технический
                        университет имени Т. Ф. Горбачёва</strong> по направлению
                        <strong>«Информационные системы и технологии»</strong>.
                    </div>
                </div>

                <div class="timeline-item ap-reveal ap-reveal--d1">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2019</div>
                    <div class="timeline-title">Магистратура и начало преподавания</div>
                    <div class="timeline-desc">
                        Получил степень <strong>магистра</strong> по тому же направлению.
                        С <strong>1 сентября 2019 года</strong> работаю преподавателем
                        на кафедре <strong>Информатики и информационных систем</strong> КузГТУ.
                    </div>
                </div>

                <div class="timeline-item ap-reveal ap-reveal--d2">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2019 — сейчас</div>
                    <div class="timeline-title">Преподаватель кафедры ИиИС</div>
                    <div class="timeline-desc">
                        Веду занятия по дисциплинам направления «Информационные системы
                        и технологии». Параллельно разрабатываю цифровые инструменты
                        для преподавателей и студентов КузГТУ — бота «КузГТУшник»
                        в MAX, голосовых помощников и веб-сервисы.
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ПРОЕКТЫ -->
    <section class="ap-section ap-section--gray" id="projects">
        <div class="ap-container">
            <div class="ap-center">
                <h2 class="ap-h2 ap-reveal">Проекты</h2>
                <p class="ap-lead ap-reveal ap-reveal--d1">
                    Четыре части одной экосистемы
                </p>
            </div>

            <div class="projects-wrap">

                <!-- ==== Проект 1: Бот в MAX ==== -->
                <div class="project-detail ap-reveal">
                    <div class="project-detail-head">
                        <div class="project-detail-icon">
                            <svg viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg" fill="none">
                                <defs>
                                    <linearGradient id="projMaxGrad" x1="58.923" x2="500" y1="380.268" y2="250" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#44CCFF"/>
                                        <stop offset=".662" stop-color="#5533EE"/>
                                        <stop offset="1" stop-color="#9933DD"/>
                                    </linearGradient>
                                </defs>
                                <path fill="url(#projMaxGrad)" d="M0 0h500v500H0z"/>
                                <path fill="#ffffff" fill-rule="evenodd" d="M253.223 398.85c-29.441 0-43.123-4.298-66.905-21.49-15.043 19.341-62.679 34.456-64.756 8.596 0-19.412-4.298-35.816-9.17-53.725C106.59 310.168 100 285.598 100 249.997 100 164.968 169.771 101 252.435 101c82.736 0 147.564 67.12 147.564 149.784.278 81.386-65.391 147.632-146.776 148.066m1.218-224.355c-40.258-2.077-71.633 25.788-78.582 69.484-5.73 36.175 4.442 80.229 13.109 82.522 4.155 1.002 14.613-7.45 21.132-13.969a74.5 74.5 0 0 0 36.39 12.966c41.713 2.006 77.356-29.75 80.157-71.418 1.63-41.756-30.487-77.123-72.206-79.513z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="project-detail-meta">
                            <div class="project-detail-tag">
                                <i class="bi bi-robot"></i> MAX Bot
                            </div>
                            <h3 class="project-detail-title">КузГТУшник — бот расписания в MAX</h3>
                            <p class="project-detail-subtitle">
                                Голосовой и текстовый помощник для студентов и преподавателей КузГТУ
                            </p>
                        </div>
                    </div>

                    <div class="project-detail-body">
                        <p>
                            Бот в мессенджере <strong>MAX</strong> для просмотра расписания занятий
                            студентов и преподавателей КузГТУ. Работает с данными официального
                            портала <code>portal.kuzstu.ru</code> в режиме реального времени.
                        </p>
                        <p>
                            Зарегистрированные пользователи получают персональное расписание
                            на сегодня, завтра и на неделю. Для преподавателей ФИО автоматически
                            транслитерируются с латиницы в кириллицу.
                        </p>
                    </div>

                    <div class="project-tech">
                        <span class="project-tech-badge"><i class="bi bi-filetype-php"></i> PHP 8</span>
                        <span class="project-tech-badge"><i class="bi bi-chat-dots"></i> MAX Bot API</span>
                        <span class="project-tech-badge"><i class="bi bi-cloud"></i> portal.kuzstu.ru</span>
                        <span class="project-tech-badge"><i class="bi bi-database"></i> JSON-хранилище</span>
                        <span class="project-tech-badge"><i class="bi bi-clock-history"></i> Cron-задачи</span>
                    </div>

                    <ul class="project-features">
                        <li>Расписание на сегодня, завтра и неделю</li>
                        <li>Поиск по группам и преподавателям</li>
                        <li>Поиск по аудиториям</li>
                        <li>Избранное — до 10 записей каждого типа</li>
                        <li>Уведомления: следующая пара, погода, расписание</li>
                        <li>Объединение поточных лекций в одну строку</li>
                    </ul>

                    <div class="project-actions">
                        <a href="https://аис-расписание.рф/api_max_bot/bot_presentation.php" class="project-link-btn project-link-btn--primary">
                            <i class="bi bi-eye"></i> Открыть презентацию
                        </a>
                        <a href="https://max.ru/se14203391_bot" target="_blank" rel="noopener" class="project-link-btn">
                            <i class="bi bi-send"></i> Открыть в MAX
                        </a>
                    </div>
                </div>

                <!-- ==== Проект 2: Голосовые помощники ==== -->
                <div class="project-detail ap-reveal ap-reveal--d1">
                    <div class="project-detail-head">
                        <div class="project-detail-icon" style="background: linear-gradient(135deg, #32BBE8, #7A55FF);">
                            <i class="bi bi-mic-fill"></i>
                        </div>
                        <div class="project-detail-meta">
                            <div class="project-detail-tag">
                                <i class="bi bi-soundwave"></i> Голосовые навыки
                            </div>
                            <h3 class="project-detail-title">КузГТУшник у голосовых ассистентов</h3>
                            <p class="project-detail-subtitle">
                                Алиса, Маруся и Сбер Салют — три платформы, одна логика
                            </p>
                        </div>
                    </div>

                    <div class="project-assistants">
                        <div class="project-assistant" title="Яндекс Алиса">
                            <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" fill="none">
                                <defs>
                                    <radialGradient id="pAliceGrad" cx="0" cy="0" r="1"
                                        gradientTransform="matrix(-18.7076 32 13.7846 43.4286 32 0.000108038)"
                                        gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#55FFC2"/>
                                        <stop offset="0.5" stop-color="#32BBE8"/>
                                        <stop offset="1" stop-color="#7A55FF"/>
                                    </radialGradient>
                                </defs>
                                <rect width="32" height="32" rx="8" fill="url(#pAliceGrad)"/>
                                <path d="M8.67353 21.1641C9.82216 22.3071 12.8818 22.9935 16 23.0055C19.1181 22.9935 22.1778 22.3071 23.3264 21.1641C26.1802 18.3244 19.2432 7.44494 16.0035 7.4292C12.7567 7.44494 5.81974 18.3244 8.67353 21.1641Z" fill="#ffffff"/>
                            </svg>
                        </div>
                        <div class="project-assistant" title="Маруся">
                            <svg viewBox="0 0 45 45" xmlns="http://www.w3.org/2000/svg" fill="none">
                                <rect width="45" height="45" rx="10" fill="#0B1422"/>
                                <path d="M12.5626 15.4186C14.0392 11.3833 17.9152 8.5 22.4593 8.5C27.4557 8.5 31.6443 11.9859 32.7241 16.6561C33.106 18.097 33.3462 20.1822 33.3462 22.5C33.3462 24.8178 33.106 26.9031 32.7241 28.3439C31.6443 33.0142 27.4557 36.5 22.4593 36.5C17.9152 36.5 14.0392 33.6167 12.5626 29.5814C12.5259 29.4838 12.49 29.3807 12.455 29.2726C12.2636 28.6943 12.1211 28.0938 12.0322 27.4761C11.7943 26.0909 11.6533 24.3673 11.6533 22.5C11.6533 20.6328 11.7943 18.9092 12.0322 17.5239C12.1211 16.9062 12.2636 16.3057 12.455 15.7274C12.49 15.6194 12.5259 15.5162 12.5626 15.4186Z" fill="url(#pMarusiaGrad)"/>
                                <circle cx="19" cy="20" r="1.6" fill="#0B1422"/>
                                <circle cx="26" cy="20" r="1.6" fill="#0B1422"/>
                                <path d="M19 25 Q22.5 28 26 25" stroke="#0B1422" stroke-width="1.6" stroke-linecap="round" fill="none"/>
                                <defs>
                                    <linearGradient id="pMarusiaGrad" x1="11" y1="8" x2="33" y2="37" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#55b3ff"/>
                                        <stop offset="1" stop-color="#6a5acd"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div class="project-assistant" title="Сбер Салют">
                            <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" fill="none">
                                <defs>
                                    <linearGradient id="pSaluteGrad" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#21a038"/>
                                        <stop offset="1" stop-color="#7ed957"/>
                                    </linearGradient>
                                </defs>
                                <rect width="32" height="32" rx="8" fill="url(#pSaluteGrad)"/>
                                <circle cx="16" cy="16" r="11" fill="#ffffff"/>
                                <path d="M16 5c6.075 0 11 4.924 11 11s-4.925 11-11 11S5 22.075 5 16 9.925 5 16 5m9.058 8.334c-.279 1.928-1.259 3.519-2.076 4.554-1.262 1.555-2.953 2.9-4.893 3.895-1.919.984-3.994 1.581-6.002 1.728q-.51.03-.951.03c-.352 0-.677-.015-.985-.042a9.44 9.44 0 0 0 5.842 2.013l-.001-.002c5.237 0 9.484-4.246 9.484-9.483 0-.91-.128-1.788-.367-2.62q-.025-.037-.052-.073m-7.709-7.33c-3.24 0-6.185 1.335-7.878 3.572l-.011.013c-.732.892-1.219 1.87-1.406 2.83-.107.583-.087 1.12.06 1.587v-.002c.348 1.045 1.194 1.883 2.154 2.13.4.1.784.14 1.14.124l.095-.008c2.428-.196 4.593-1.726 6.39-3.197 1.125-.941 2.2-2.187 2.27-2.992-1.568.76-3.187 1.75-5.054 3.088a.466.466 0 0 1-.643-.094 50 50 0 0 0-2.542-3.065.47.47 0 0 1 .066-.688c2.475-1.856 4.937-2.908 7.325-3.13a11.4 11.4 0 0 0-1.966-.169"
                                      fill-rule="evenodd" fill="#21a038"/>
                            </svg>
                        </div>
                    </div>

                    <div class="project-detail-body">
                        <p>
                            Навыки для трёх голосовых платформ с единой логикой: регистрация,
                            привязка по коду из MAX-бота и озвучивание расписания на сегодня
                            и завтра. Все три используют общий <code>users.json</code> и независимые
                            флаги привязки — один пользователь может подключить Алису,
                            Марусю и Салют одновременно.
                        </p>
                    </div>

                    <div class="project-tech">
                        <span class="project-tech-badge"><i class="bi bi-mic"></i> Яндекс Диалоги</span>
                        <span class="project-tech-badge"><i class="bi bi-chat-square-text"></i> VK Скиллы</span>
                        <span class="project-tech-badge"><i class="bi bi-cpu"></i> SmartApp API Сбера</span>
                        <span class="project-tech-badge"><i class="bi bi-shield-check"></i> Код-привязка</span>
                    </div>

                    <ul class="project-features">
                        <li>Яндекс Алиса — навык на Яндекс Диалогах</li>
                        <li>Маруся — скилл для VK</li>
                        <li>Сбер Салют — SmartApp API</li>
                        <li>Единый код привязки из MAX-бота</li>
                        <li>Расписание на сегодня и завтра голосом</li>
                        <li>Автосинхронизация отвязки во всех ассистентах</li>
                    </ul>

                    <div class="project-actions">
                        <a href="https://аис-расписание.рф/api_max_bot/bot_presentation.php#voice" class="project-link-btn project-link-btn--primary">
                            <i class="bi bi-mic"></i> О голосовых навыках
                        </a>
                    </div>
                </div>

                <!-- ==== Проект 3: Портал АИС-Расписание ==== -->
                <div class="project-detail ap-reveal ap-reveal--d2">
                    <div class="project-detail-head">
                        <div class="project-detail-icon" style="background: linear-gradient(135deg, #ff9f0a, #ff453a);">
                            <i class="bi bi-window-stack"></i>
                        </div>
                        <div class="project-detail-meta">
                            <div class="project-detail-tag">
                                <i class="bi bi-globe"></i> Web-портал
                            </div>
                            <h3 class="project-detail-title">АИС-расписание.рф</h3>
                            <p class="project-detail-subtitle">
                                Комплексная система для преподавателей КузГТУ
                            </p>
                        </div>
                    </div>

                    <div class="project-detail-body">
                        <p>
                            <strong>АИС-расписание.рф</strong> — веб-приложение на PHP и MySQL
                            для автоматизации работы преподавателя. Не просто электронный журнал,
                            а полноценная система с интеграцией с порталом КузГТУ, гибкой
                            аналитикой и разграничением прав доступа.
                        </p>
                        <p>
                            Архитектура — классический монолит на <strong>PHP + MySQL</strong>
                            с современным фронтендом на <strong>Bootstrap 5</strong> и CSS-переменными.
                            Активно используется API официального портала для синхронизации
                            расписания и справочников.
                        </p>
                    </div>

                    <div class="project-tech">
                        <span class="project-tech-badge"><i class="bi bi-filetype-php"></i> PHP 8</span>
                        <span class="project-tech-badge"><i class="bi bi-database-fill"></i> MySQL</span>
                        <span class="project-tech-badge"><i class="bi bi-bootstrap-fill"></i> Bootstrap 5</span>
                        <span class="project-tech-badge"><i class="bi bi-cloud-arrow-down"></i> API КузГТУ</span>
                        <span class="project-tech-badge"><i class="bi bi-shield-lock"></i> Роли и права</span>
                    </div>

                    <ul class="project-features">
                        <li>Учёт посещаемости — отметка в один клик</li>
                        <li>Учёт сданных работ: лабораторные, курсовые, РГР</li>
                        <li>Автосинхронизация расписания с порталом КузГТУ</li>
                        <li>Отчёты и аналитика по группам и предметам</li>
                        <li>Выявление «должников» по пропускам</li>
                        <li>Сессионные ведомости с автодопуском</li>
                        <li>Импорт студентов из CSV/Excel</li>
                        <li>Массовые операции по группе</li>
                        <li>Роли: преподаватель, зав. кафедрой, администрация</li>
                        <li>Аудит действий пользователей</li>
                        <li>Тёмная тема и адаптивный интерфейс</li>
                    </ul>

                    <div class="project-actions">
                        <a href="https://аис-расписание.рф/presentation.php" class="project-link-btn project-link-btn--primary">
                            <i class="bi bi-eye"></i> Презентация портала
                        </a>
                        <a href="https://аис-расписание.рф" target="_blank" rel="noopener" class="project-link-btn">
                            <i class="bi bi-box-arrow-up-right"></i> Перейти на портал
                        </a>
                    </div>
                </div>

                <!-- ==== Проект 4: РСП ==== -->
                <div class="project-detail ap-reveal ap-reveal--d3">
                    <div class="project-detail-head">
                        <div class="project-detail-icon" style="background: linear-gradient(135deg, #30d158, #0dcaf0);">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <div class="project-detail-meta">
                            <div class="project-detail-tag">
                                <i class="bi bi-search"></i> Web-сервис
                            </div>
                            <h3 class="project-detail-title">РСП — расписание по фильтрам</h3>
                            <p class="project-detail-subtitle">
                                Быстрый поиск расписания по группе, преподавателю и аудитории
                            </p>
                        </div>
                    </div>

                    <div class="project-detail-body">
                        <p>
                            <strong>РСП</strong> — компактный веб-сервис, который берёт расписание
                            с того же портала КузГТУ и позволяет быстро находить нужные занятия
                            по трём ключевым параметрам: группе, преподавателю и аудитории.
                        </p>
                        <p>
                            Создан как лёгкая альтернатива тяжёлым интерфейсам — минимум кликов,
                            максимум скорости. Актуален для быстрой проверки «где сейчас идёт пара»
                            или «когда у препода окно».
                        </p>
                    </div>

                    <div class="project-tech">
                        <span class="project-tech-badge"><i class="bi bi-filetype-php"></i> PHP</span>
                        <span class="project-tech-badge"><i class="bi bi-cloud"></i> portal.kuzstu.ru</span>
                        <span class="project-tech-badge"><i class="bi bi-funnel"></i> Фильтры</span>
                        <span class="project-tech-badge"><i class="bi bi-lightning-charge"></i> Быстрый поиск</span>
                    </div>

                    <ul class="project-features">
                        <li>Поиск расписания по группе</li>
                        <li>Поиск расписания по преподавателю</li>
                        <li>Поиск расписания по аудитории</li>
                        <li>Данные напрямую с портала КузГТУ</li>
                        <li>Лёгкий и быстрый интерфейс</li>
                    </ul>

                    <div class="project-actions">
                        <a href="https://аис-расписание.рф/rsp/" target="_blank" rel="noopener" class="project-link-btn project-link-btn--primary">
                            <i class="bi bi-box-arrow-up-right"></i> Открыть РСП
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- КОНТАКТЫ -->
    <section class="ap-section" id="contacts">
        <div class="ap-container">
            <div class="ap-center">
                <h2 class="ap-h2 ap-reveal">Связаться</h2>
                <p class="ap-lead ap-reveal ap-reveal--d1">
                    По вопросам сотрудничества, идей и предложений
                </p>
            </div>

            <div class="contacts-grid">

                <a href="https://аис-расписание.рф" target="_blank" rel="noopener" class="contact-card ap-reveal">
                    <div class="contact-icon">
                        <i class="bi bi-globe2"></i>
                    </div>
                    <div class="contact-label">Портал</div>
                    <div class="contact-value">аис-расписание.рф</div>
                </a>

                <a href="https://max.ru/se14203391_bot" target="_blank" rel="noopener" class="contact-card ap-reveal ap-reveal--d1">
                    <div class="contact-icon">
                        <i class="bi bi-send"></i>
                    </div>
                    <div class="contact-label">MAX</div>
                    <div class="contact-value">@se14203391_bot</div>
                </a>

            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="author-cta">
        <div class="ap-container ap-center">
            <h2 class="ap-h2 ap-reveal">Проект открыт для идей</h2>
            <p class="ap-lead ap-reveal ap-reveal--d1" style="margin-bottom: 32px;">
                Исходный код бота, портала и голосовых навыков доступен.
                Пишите — обсудим.
            </p>
            <div class="author-hero-actions ap-reveal ap-reveal--d2">
                <a href="https://аис-расписание.рф/api_max_bot/bot_presentation.php" class="ap-btn ap-btn--primary">
                    <i class="bi bi-robot"></i> К боту КузГТУшник
                </a>
                <a href="https://аис-расписание.рф/presentation.php" class="ap-btn ap-btn--ghost">
                    <i class="bi bi-easel"></i> К порталу АИС
                </a>
            </div>
        </div>
    </section>

</div>

<div class="ap-toast" id="apToast"><i class="bi bi-check-circle-fill"></i><span id="apToastText">Готово!</span></div>

<script>
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

(function() {
    const bar = document.getElementById('scrollProgress');
    window.addEventListener('scroll', () => {
        const h = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.width = ((window.scrollY / h) * 100) + '%';
    }, { passive: true });
})();

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

(function() {
    const c = document.getElementById('particles');
    if (!c) return;
    for (let i = 0; i < 24; i++) {
        const p = document.createElement('div');
        p.className = 'ap-particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.animationDelay = (Math.random() * 20) + 's';
        p.style.animationDuration = (15 + Math.random() * 10) + 's';
        const s = 2 + Math.random() * 5;
        p.style.width = p.style.height = s + 'px';
        c.appendChild(p);
    }
})();

(function() {
    const els = document.querySelectorAll('.ap-reveal, .bio-card, .timeline-item, .project-detail, .contact-card');
    if (!('IntersectionObserver' in window)) {
        els.forEach(el => el.classList.add('is-visible'));
        return;
    }
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('is-visible');
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });
    els.forEach(el => obs.observe(el));
})();

document.querySelectorAll('.ap-btn, .project-link-btn').forEach(btn => {
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

document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

function showToast(text) {
    const t = document.getElementById('apToast');
    document.getElementById('apToastText').textContent = text;
    t.classList.add('show');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => t.classList.remove('show'), 2200);
}
</script>

</body>
</html>