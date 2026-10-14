<?php
$pageTitle = 'Документация';
require_once 'config/database.php';
require_once 'includes/header.php';
?>

<style>
/* ============================================================
   DOCS — в стиле портала
   ============================================================ */
.docs-wrap {
    max-width: 1120px;
    margin: 0 auto;
}

/* Шапка страницы */
.docs-hero {
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 55%, #bf5af2 100%);
    background-size: 200% 200%;
    animation: docsGradient 12s ease-in-out infinite;
    color: #fff;
    border-radius: 24px;
    padding: 40px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,113,227,0.25);
}
@keyframes docsGradient {
    0%,100% { background-position: 0% 50%; }
    50%     { background-position: 100% 50%; }
}
.docs-hero::before {
    content: '';
    position: absolute;
    top: -50%; right: -10%;
    width: 420px; height: 420px;
    background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, transparent 65%);
    border-radius: 50%;
    pointer-events: none;
    animation: docsGlow 6s ease-in-out infinite;
}
@keyframes docsGlow {
    0%,100% { opacity: 0.7; transform: scale(1); }
    50%     { opacity: 1; transform: scale(1.08); }
}
.docs-hero-content { position: relative; z-index: 1; }
.docs-hero h1 {
    font-size: clamp(1.8rem, 3.5vw, 2.6rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 8px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.docs-hero p {
    font-size: 1.05rem;
    opacity: 0.92;
    margin: 0 0 24px;
    max-width: 720px;
    line-height: 1.5;
}
.docs-hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.docs-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
    border: 1.5px solid rgba(255,255,255,0.35);
    background: rgba(255,255,255,0.15);
    color: #fff;
    backdrop-filter: blur(10px);
}
.docs-hero-btn:hover {
    background: rgba(255,255,255,0.28);
    color: #fff;
    transform: translateY(-2px);
}
.docs-hero-btn--solid {
    background: #fff;
    color: #0071e3;
    border-color: #fff;
}
.docs-hero-btn--solid:hover {
    background: #fff;
    color: #0056b3;
    box-shadow: 0 10px 24px rgba(0,0,0,0.2);
}
.docs-hero-stat {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    background: rgba(255,255,255,0.18);
    border: 1px solid rgba(255,255,255,0.25);
    font-size: 0.8rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}
.docs-hero-stats {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

/* Навигация по разделам */
.docs-nav {
    position: sticky;
    top: 80px;
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    padding: 20px;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
}
.docs-nav-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #86868b;
    margin-bottom: 12px;
    padding-left: 10px;
}
.docs-nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.docs-nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 10px;
    color: #495057;
    font-size: 0.86rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
    position: relative;
}
.docs-nav-link i {
    width: 18px;
    font-size: 0.95rem;
    color: #0071e3;
    flex-shrink: 0;
}
.docs-nav-link:hover {
    background: #f5f5f7;
    color: #0071e3;
    transform: translateX(3px);
}
.docs-nav-link.active {
    background: linear-gradient(135deg, rgba(0,113,227,0.1) 0%, rgba(191,90,242,0.1) 100%);
    color: #0071e3;
    font-weight: 600;
}
.docs-nav-link.active::before {
    content: '';
    position: absolute;
    left: 0; top: 20%; bottom: 20%;
    width: 3px;
    border-radius: 3px;
    background: linear-gradient(180deg, #0071e3, #5e5ce6);
}
.docs-nav-sub {
    padding-left: 30px;
    font-size: 0.8rem;
}
.docs-nav-divider {
    height: 1px;
    background: #f0f0f2;
    margin: 8px 12px;
}

/* Прогресс чтения */
.docs-progress {
    position: fixed;
    top: 0; left: 0;
    height: 3px;
    width: 0;
    background: linear-gradient(90deg, #0071e3, #bf5af2, #30d158);
    z-index: 9999;
    box-shadow: 0 0 20px rgba(0,113,227,0.6);
    transition: width 0.1s;
    pointer-events: none;
}

/* Секция */
.docs-section {
    scroll-margin-top: 90px;
    margin-bottom: 24px;
}
.docs-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    overflow: hidden;
    transition: all 0.3s;
}
.docs-card:hover {
    box-shadow: 0 12px 32px rgba(0,0,0,0.07);
}
.docs-card-header {
    padding: 22px 28px;
    border-bottom: 1px solid #f0f0f2;
    background: linear-gradient(135deg, #fafafc 0%, #fff 100%);
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.docs-card-icon {
    width: 46px; height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    box-shadow: 0 8px 20px rgba(0,113,227,0.25);
}
.docs-card-icon--green { background: linear-gradient(135deg, #30d158 0%, #198754 100%); box-shadow: 0 8px 20px rgba(48,209,88,0.25); }
.docs-card-icon--purple{ background: linear-gradient(135deg, #bf5af2 0%, #6610f2 100%); box-shadow: 0 8px 20px rgba(191,90,242,0.25); }
.docs-card-icon--orange{ background: linear-gradient(135deg, #ff9f0a 0%, #fd7e14 100%); box-shadow: 0 8px 20px rgba(255,159,10,0.25); }
.docs-card-icon--red   { background: linear-gradient(135deg, #ff453a 0%, #d63447 100%); box-shadow: 0 8px 20px rgba(255,69,58,0.25); }
.docs-card-icon--teal  { background: linear-gradient(135deg, #5ac8fa 0%, #0dcaf0 100%); box-shadow: 0 8px 20px rgba(90,200,250,0.25); }
.docs-card-icon--pink  { background: linear-gradient(135deg, #ff2d92 0%, #d63384 100%); box-shadow: 0 8px 20px rgba(255,45,146,0.25); }

.docs-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: #1d1d1f;
    margin: 0;
    flex: 1;
    min-width: 0;
}
.docs-card-num {
    font-size: 0.75rem;
    font-weight: 700;
    color: #86868b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.docs-card-body {
    padding: 24px 28px 28px;
}
.docs-card-body > p:first-child {
    font-size: 1rem;
    line-height: 1.6;
    color: #4a4a4c;
    margin-bottom: 20px;
}

/* Подзаголовки */
.docs-h {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1d1d1f;
    margin: 24px 0 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.005em;
}
.docs-h:first-child { margin-top: 0; }
.docs-h i { color: #0071e3; font-size: 1.05rem; }

/* Шаги */
.docs-steps {
    list-style: none;
    padding: 0;
    margin: 0;
    counter-reset: docsStep;
}
.docs-steps > li {
    counter-increment: docsStep;
    position: relative;
    padding: 12px 0 12px 52px;
    color: #495057;
    line-height: 1.6;
    font-size: 0.95rem;
    border-bottom: 1px solid #f5f5f7;
}
.docs-steps > li:last-child { border-bottom: none; }
.docs-steps > li::before {
    content: counter(docsStep);
    position: absolute;
    left: 0; top: 12px;
    width: 32px; height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    font-size: 0.82rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,113,227,0.3);
}
.docs-steps > li strong { color: #1d1d1f; font-weight: 600; }

/* Списки */
.docs-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.docs-list li {
    position: relative;
    padding: 6px 0 6px 26px;
    color: #495057;
    line-height: 1.6;
    font-size: 0.95rem;
}
.docs-list li::before {
    content: '';
    position: absolute;
    left: 4px; top: 14px;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0071e3, #5e5ce6);
}
.docs-list li strong { color: #1d1d1f; font-weight: 600; }
.docs-list li code,
.docs-list p code {
    background: rgba(0,113,227,0.08);
    color: #0071e3;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.85em;
    font-family: "SF Mono", Monaco, monospace;
    font-weight: 600;
}

/* Инфо-блоки */
.docs-info {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 14px 18px;
    border-radius: 14px;
    font-size: 0.9rem;
    line-height: 1.55;
    margin: 16px 0;
    border: 1px solid;
}
.docs-info i { font-size: 1.15rem; flex-shrink: 0; margin-top: 2px; }
.docs-info--info {
    background: rgba(0,113,227,0.06);
    border-color: rgba(0,113,227,0.18);
    color: #084298;
}
.docs-info--info i { color: #0071e3; }
.docs-info--warn {
    background: rgba(255,159,10,0.08);
    border-color: rgba(255,159,10,0.25);
    color: #b25c00;
}
.docs-info--warn i { color: #ff9f0a; }
.docs-info--success {
    background: rgba(48,209,88,0.08);
    border-color: rgba(48,209,88,0.2);
    color: #198754;
}
.docs-info--success i { color: #30d158; }
.docs-info--danger {
    background: rgba(255,69,58,0.08);
    border-color: rgba(255,69,58,0.2);
    color: #842029;
}
.docs-info--danger i { color: #ff453a; }
.docs-info b, .docs-info strong { color: inherit; font-weight: 700; }

/* Таблицы */
.docs-table-wrap {
    overflow-x: auto;
    border-radius: 14px;
    border: 1px solid #eaeaea;
    margin: 14px 0;
}
.docs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    margin: 0;
}
.docs-table thead th {
    background: #fafafc;
    padding: 12px 16px;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #86868b;
    border-bottom: 1px solid #f0f0f2;
    white-space: nowrap;
}
.docs-table tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid #f5f5f7;
    color: #495057;
    line-height: 1.5;
    vertical-align: top;
}
.docs-table tbody tr:last-child td { border-bottom: none; }
.docs-table tbody tr:hover { background: #fafafc; }
.docs-table strong { color: #1d1d1f; }
.docs-table code {
    background: rgba(0,113,227,0.08);
    color: #0071e3;
    padding: 2px 6px;
    border-radius: 5px;
    font-size: 0.85em;
    font-family: "SF Mono", Monaco, monospace;
    font-weight: 600;
}

/* Бейджи */
.docs-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}
.docs-badge--blue   { background: rgba(0,113,227,0.12); color: #0071e3; }
.docs-badge--green  { background: rgba(48,209,88,0.15); color: #198754; }
.docs-badge--red    { background: rgba(255,69,58,0.12); color: #842029; }
.docs-badge--orange { background: rgba(255,159,10,0.15); color: #b25c00; }
.docs-badge--purple { background: rgba(191,90,242,0.12); color: #bf5af2; }
.docs-badge--gray   { background: rgba(134,134,139,0.12); color: #495057; }

/* Карточки-фичи */
.docs-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
    margin-top: 16px;
}
.docs-feature {
    background: #fafafc;
    border: 1px solid #f0f0f2;
    border-radius: 14px;
    padding: 16px 18px;
    transition: all 0.25s;
}
.docs-feature:hover {
    background: #fff;
    border-color: #0071e3;
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0,113,227,0.1);
}
.docs-feature i {
    font-size: 1.35rem;
    color: #0071e3;
    margin-bottom: 8px;
    display: block;
}
.docs-feature-name {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1d1d1f;
    margin-bottom: 4px;
}
.docs-feature-desc {
    font-size: 0.82rem;
    color: #86868b;
    line-height: 1.5;
}

/* Аккордеон */
.docs-accordion {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 8px;
}
.docs-acc-item {
    background: #fafafc;
    border: 1px solid #eaeaea;
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.25s;
}
.docs-acc-item[data-open="1"] {
    background: #fff;
    border-color: #0071e3;
    box-shadow: 0 12px 28px rgba(0,113,227,0.1);
}
.docs-acc-btn {
    width: 100%;
    padding: 16px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 600;
    color: #1d1d1f;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: color 0.2s;
}
.docs-acc-btn:hover { color: #0071e3; }
.docs-acc-btn i.docs-acc-arrow {
    margin-left: auto;
    color: #86868b;
    font-size: 0.85rem;
    transition: transform 0.3s;
    flex-shrink: 0;
}
.docs-acc-item[data-open="1"] .docs-acc-btn i.docs-acc-arrow {
    transform: rotate(180deg);
    color: #0071e3;
}
.docs-acc-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.16,1,0.3,1);
}
.docs-acc-item[data-open="1"] .docs-acc-body {
    max-height: 1500px;
}
.docs-acc-body-inner {
    padding: 0 20px 20px 20px;
    color: #495057;
    font-size: 0.92rem;
    line-height: 1.6;
    border-top: 1px solid #f0f0f2;
    padding-top: 16px;
}
.docs-acc-body-inner p:last-child { margin-bottom: 0; }

/* Мини-примеры UI */
.docs-ui {
    background: #fafafc;
    border: 1px solid #eaeaea;
    border-radius: 14px;
    padding: 16px;
    margin: 14px 0;
    font-size: 0.85rem;
}
.docs-ui-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #86868b;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.docs-ui-title i { color: #0071e3; }
.docs-ui-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: #fff;
    border: 1px solid #f0f0f2;
    border-radius: 10px;
    margin-bottom: 6px;
    font-size: 0.85rem;
}
.docs-ui-row:last-child { margin-bottom: 0; }
.docs-ui-row .dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}
.docs-ui-row .lbl {
    flex: 1;
    color: #495057;
}
.docs-ui-row .val {
    font-weight: 700;
    color: #1d1d1f;
    font-variant-numeric: tabular-nums;
}

/* Карта ролей */
.docs-roles {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-top: 12px;
}
.docs-role {
    padding: 16px 18px;
    border-radius: 14px;
    background: linear-gradient(135deg, #fafafc 0%, #fff 100%);
    border: 1px solid #eaeaea;
    position: relative;
    overflow: hidden;
}
.docs-role::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--role-color, #0071e3);
}
.docs-role-icon {
    width: 34px; height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #fff;
    margin-bottom: 10px;
    background: var(--role-color, #0071e3);
}
.docs-role-name {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1d1d1f;
    margin-bottom: 6px;
}
.docs-role-desc {
    font-size: 0.8rem;
    color: #86868b;
    line-height: 1.5;
}

/* Футер */
.docs-footer {
    text-align: center;
    padding: 32px 20px 40px;
    color: #86868b;
    font-size: 0.85rem;
}
.docs-footer a {
    color: #0071e3;
    text-decoration: none;
    font-weight: 600;
}
.docs-footer a:hover { text-decoration: underline; }

/* Тёмная тема */
[data-theme="dark"] .docs-nav,
[data-theme="dark"] .docs-card {
    background: #1c1c1e;
    border-color: #2c2c2e;
}
[data-theme="dark"] .docs-card-header {
    background: linear-gradient(135deg, #2c2c2e 0%, #1c1c1e 100%);
    border-color: #3c3c3e;
}
[data-theme="dark"] .docs-card-title,
[data-theme="dark"] .docs-h,
[data-theme="dark"] .docs-steps > li strong,
[data-theme="dark"] .docs-list li strong,
[data-theme="dark"] .docs-feature-name,
[data-theme="dark"] .docs-acc-btn,
[data-theme="dark"] .docs-role-name {
    color: #f5f5f7;
}
[data-theme="dark"] .docs-card-body > p:first-child,
[data-theme="dark"] .docs-steps > li,
[data-theme="dark"] .docs-list li,
[data-theme="dark"] .docs-acc-body-inner,
[data-theme="dark"] .docs-table tbody td {
    color: #c7c7cc;
}
[data-theme="dark"] .docs-nav-link { color: #a1a1a6; }
[data-theme="dark"] .docs-nav-link:hover { background: #2c2c2e; color: #6ea8ff; }
[data-theme="dark"] .docs-nav-link.active {
    background: linear-gradient(135deg, rgba(10,132,255,0.15) 0%, rgba(191,90,242,0.15) 100%);
    color: #6ea8ff;
}
[data-theme="dark"] .docs-nav-divider { background: #2c2c2e; }
[data-theme="dark"] .docs-table-wrap { border-color: #2c2c2e; }
[data-theme="dark"] .docs-table thead th {
    background: #2c2c2e;
    border-color: #3c3c3e;
    color: #a1a1a6;
}
[data-theme="dark"] .docs-table tbody td { border-color: #2c2c2e; }
[data-theme="dark"] .docs-table tbody tr:hover { background: #2c2c2e; }
[data-theme="dark"] .docs-feature,
[data-theme="dark"] .docs-ui,
[data-theme="dark"] .docs-ui-row,
[data-theme="dark"] .docs-acc-item,
[data-theme="dark"] .docs-role {
    background: #2c2c2e;
    border-color: #3c3c3e;
}
[data-theme="dark"] .docs-feature:hover {
    background: #3c3c3e;
    border-color: #0a84ff;
}
[data-theme="dark"] .docs-acc-item[data-open="1"] {
    background: #2c2c2e;
    border-color: #0a84ff;
}
[data-theme="dark"] .docs-acc-body-inner { border-color: #3c3c3e; }
[data-theme="dark"] .docs-info--info {
    background: rgba(10,132,255,0.12);
    border-color: rgba(10,132,255,0.3);
    color: #6ea8ff;
}
[data-theme="dark"] .docs-info--warn {
    background: rgba(255,159,10,0.12);
    border-color: rgba(255,159,10,0.3);
    color: #ffb74d;
}
[data-theme="dark"] .docs-info--success {
    background: rgba(48,209,88,0.12);
    border-color: rgba(48,209,88,0.3);
    color: #6ee7a3;
}
[data-theme="dark"] .docs-info--danger {
    background: rgba(255,69,58,0.12);
    border-color: rgba(255,69,58,0.3);
    color: #ff7b6e;
}
[data-theme="dark"] .docs-steps > li { border-color: #2c2c2e; }
[data-theme="dark"] .docs-ui-row .lbl { color: #a1a1a6; }
[data-theme="dark"] .docs-ui-row .val { color: #f5f5f7; }
[data-theme="dark"] .docs-role-desc { color: #a1a1a6; }

/* Печать */
@media print {
    .docs-nav, .docs-hero-actions, .navbar, footer, .btn, .d-print-none { display: none !important; }
    .docs-hero {
        background: #f0f0f0 !important;
        color: #000 !important;
        box-shadow: none !important;
        border: 1px solid #ccc;
        page-break-after: avoid;
    }
    .docs-hero h1, .docs-hero p { color: #000 !important; }
    .docs-hero-btn { display: none !important; }
    .docs-card {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        page-break-inside: avoid;
        margin-bottom: 12px;
    }
    .docs-acc-body { max-height: none !important; }
    .docs-acc-item[data-open] .docs-acc-body { max-height: none !important; }
    .docs-acc-body { display: block !important; }
    .docs-card:hover, .docs-feature:hover, .docs-role:hover { transform: none !important; }
    .docs-hero::before { display: none; }
    a { color: #000 !important; text-decoration: none !important; }
    a[href]::after { content: " (" attr(href) ")"; font-size: 0.85em; color: #666; font-weight: 400; }
    .docs-card-body > p:first-child { margin-bottom: 8px; }
    .docs-h { margin: 12px 0 6px; }
}
</style>

<div class="docs-progress" id="docsProgress"></div>

<div class="container-fluid">
    <div class="docs-wrap">

        <!-- ============================================================
             HERO
             ============================================================ -->
        <div class="docs-hero">
            <div class="docs-hero-content">
                <h1>
                    <i class="bi bi-book-half"></i>
                    Документация
                </h1>
                <p>
                    Полное руководство по системе <strong>АИС-расписание.РФ</strong>:
                    синхронизация расписания, учёт посещаемости, работы,
                    отчёты, сессия и интеграции. Без админ-панели —
                    только то, что нужно преподавателю, зав. кафедрой
                    и дирекции.
                </p>

                <div class="docs-hero-stats">
                    <span class="docs-hero-stat">
                        <i class="bi bi-list-check"></i> 18 разделов
                    </span>
                    <span class="docs-hero-stat">
                        <i class="bi bi-people-fill"></i> 4 роли
                    </span>
                    <span class="docs-hero-stat">
                        <i class="bi bi-journal-text"></i> 20+ страниц
                    </span>
                    <span class="docs-hero-stat">
                        <i class="bi bi-robot"></i> MAX + Алиса
                    </span>
                </div>

                <div class="docs-hero-actions">
                    <a href="#about" class="docs-hero-btn docs-hero-btn--solid">
                        <i class="bi bi-play-fill"></i> Начать чтение
                    </a>
                    <a href="#faq" class="docs-hero-btn">
                        <i class="bi bi-question-circle"></i> FAQ
                    </a>
                    <button type="button" class="docs-hero-btn" onclick="window.print()">
                        <i class="bi bi-printer"></i> Печать
                    </button>
                    <a href="presentation.php" class="docs-hero-btn">
                        <i class="bi bi-stars"></i> О системе
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- ============================================================
                 ЛЕВАЯ КОЛОНКА — НАВИГАЦИЯ
                 ============================================================ -->
            <div class="col-lg-3 d-none d-lg-block">
                <nav class="docs-nav">
                    <div class="docs-nav-title">
                        <i class="bi bi-list-ul"></i> Содержание
                    </div>
                    <ul class="docs-nav-list">
                        <li><a href="#about" class="docs-nav-link">
                            <i class="bi bi-info-circle"></i> О системе
                        </a></li>
                        <li><a href="#auth" class="docs-nav-link">
                            <i class="bi bi-person-plus"></i> Регистрация и вход
                        </a></li>
                        <li><a href="#profile" class="docs-nav-link">
                            <i class="bi bi-person-vcard"></i> Профиль
                        </a></li>
                        <li><a href="#dashboard" class="docs-nav-link">
                            <i class="bi bi-speedometer2"></i> Дашборд
                        </a></li>
                        <li><a href="#schedule" class="docs-nav-link">
                            <i class="bi bi-calendar-week"></i> Расписание
                        </a></li>
                        <li><a href="#groups" class="docs-nav-link">
                            <i class="bi bi-people"></i> Группы
                        </a></li>
                        <li><a href="#subgroups" class="docs-nav-link">
                            <i class="bi bi-diagram-3"></i> Подгруппы
                        </a></li>
                        <li><a href="#attendance" class="docs-nav-link">
                            <i class="bi bi-check2-square"></i> Посещаемость
                        </a></li>
                        <li><a href="#works" class="docs-nav-link">
                            <i class="bi bi-journal-check"></i> Работы
                        </a></li>
                        <li><a href="#reports" class="docs-nav-link">
                            <i class="bi bi-bar-chart"></i> Отчёты
                        </a></li>
                        <li><a href="#check_reports" class="docs-nav-link">
                            <i class="bi bi-clipboard-check"></i> Проверка
                        </a></li>
                        <li><a href="#debtors" class="docs-nav-link">
                            <i class="bi bi-exclamation-triangle"></i> Должники
                        </a></li>
                        <li><a href="#session" class="docs-nav-link">
                            <i class="bi bi-mortarboard"></i> Сессия
                        </a></li>
                        <li><a href="#audit" class="docs-nav-link">
                            <i class="bi bi-clock-history"></i> Журнал
                        </a></li>
                        <li><a href="#notifications" class="docs-nav-link">
                            <i class="bi bi-bell"></i> Уведомления
                        </a></li>
                        <li><a href="#student" class="docs-nav-link">
                            <i class="bi bi-mortarboard-fill"></i> Студентам
                        </a></li>
                        <li><a href="#bot" class="docs-nav-link">
                            <i class="bi bi-robot"></i> Бот и Алиса
                        </a></li>
                        <div class="docs-nav-divider"></div>
                        <li><a href="#faq" class="docs-nav-link">
                            <i class="bi bi-question-circle"></i> FAQ
                        </a></li>
                    </ul>
                </nav>
            </div>

            <!-- ============================================================
                 ПРАВАЯ КОЛОНКА — КОНТЕНТ
                 ============================================================ -->
            <div class="col-lg-9">

                <!-- ==================================================
                     1. О СИСТЕМЕ
                     ================================================== -->
                <section class="docs-section" id="about">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon"><i class="bi bi-info-circle"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 01</div>
                                <h2 class="docs-card-title">О системе и портале</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <strong>АИС-расписание.РФ</strong> — это система для автоматизации
                                работы преподавателя вуза. Она объединяет в одном месте
                                расписание, посещаемость, сданные работы, отчёты и сессионные
                                ведомости. Система напрямую связана с официальным порталом
                                Кузбасского государственного технического университета
                                <a href="https://portal.kuzstu.ru" target="_blank" rel="noopener">
                                    portal.kuzstu.ru</a> и синхронизирует данные автоматически.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-stars"></i> Ключевые возможности</h4>
                            <div class="docs-features">
                                <div class="docs-feature">
                                    <i class="bi bi-arrow-repeat"></i>
                                    <div class="docs-feature-name">Синхронизация</div>
                                    <div class="docs-feature-desc">Расписание подтягивается с портала КузГТУ по API</div>
                                </div>
                                <div class="docs-feature">
                                    <i class="bi bi-file-earmark-excel"></i>
                                    <div class="docs-feature-name">Импорт студентов</div>
                                    <div class="docs-feature-desc">Загрузка списков из CSV/Excel в один клик</div>
                                </div>
                                <div class="docs-feature">
                                    <i class="bi bi-check2-square"></i>
                                    <div class="docs-feature-name">Посещаемость</div>
                                    <div class="docs-feature-desc">Отметка «был/пропустил» за секунды</div>
                                </div>
                                <div class="docs-feature">
                                    <i class="bi bi-journal-check"></i>
                                    <div class="docs-feature-name">Работы</div>
                                    <div class="docs-feature-desc">Учёт сданных лабораторных и курсовых</div>
                                </div>
                                <div class="docs-feature">
                                    <i class="bi bi-diagram-3"></i>
                                    <div class="docs-feature-name">Подгруппы</div>
                                    <div class="docs-feature-desc">Разделение группы для лаб. работ</div>
                                </div>
                                <div class="docs-feature">
                                    <i class="bi bi-bar-chart"></i>
                                    <div class="docs-feature-name">Отчёты</div>
                                    <div class="docs-feature-desc">Матрицы, heatmap, экспорт в CSV</div>
                                </div>
                                <div class="docs-feature">
                                    <i class="bi bi-mortarboard"></i>
                                    <div class="docs-feature-name">Сессия</div>
                                    <div class="docs-feature-desc">Зачёты, экзамены, ведомости</div>
                                </div>
                                <div class="docs-feature">
                                    <i class="bi bi-robot"></i>
                                    <div class="docs-feature-name">MAX и Алиса</div>
                                    <div class="docs-feature-desc">Расписание в мессенджере и голосом</div>
                                </div>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-shield-check"></i> Роли и области доступа</h4>
                            <p>
                                Каждый пользователь видит только свои данные. Область доступа
                                определяется ролью:
                            </p>
                            <div class="docs-roles">
                                <div class="docs-role" style="--role-color:#0dcaf0;">
                                    <div class="docs-role-icon"><i class="bi bi-person-workspace"></i></div>
                                    <div class="docs-role-name">Преподаватель</div>
                                    <div class="docs-role-desc">Свои занятия, группы, ведомости, отчёты и журнал</div>
                                </div>
                                <div class="docs-role" style="--role-color:#ff9f0a;">
                                    <div class="docs-role-icon"><i class="bi bi-diagram-3-fill"></i></div>
                                    <div class="docs-role-name">Зав. кафедрой</div>
                                    <div class="docs-role-desc">Всё по преподавателям своей кафедры</div>
                                </div>
                                <div class="docs-role" style="--role-color:#30d158;">
                                    <div class="docs-role-icon"><i class="bi bi-building"></i></div>
                                    <div class="docs-role-name">Дирекция</div>
                                    <div class="docs-role-desc">Всё по преподавателям своего института</div>
                                </div>
                                <div class="docs-role" style="--role-color:#ff453a;">
                                    <div class="docs-role-icon"><i class="bi bi-shield-fill-check"></i></div>
                                    <div class="docs-role-name">Администратор</div>
                                    <div class="docs-role-desc">Полный доступ ко всем данным и настройкам</div>
                                </div>
                            </div>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-info-circle-fill"></i>
                                <div>
                                    <strong>Документация для всех ролей.</strong>
                                    Здесь описан пользовательский функционал — без админ-панели.
                                    Если вы администратор, обратитесь к техническому регламенту.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     2. РЕГИСТРАЦИЯ И ВХОД
                     ================================================== -->
                <section class="docs-section" id="auth">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--green"><i class="bi bi-person-plus"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 02</div>
                                <h2 class="docs-card-title">Регистрация и вход</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                Первый вход в систему начинается с регистрации.
                                Она привязывает ваш аккаунт к реальному профилю
                                преподавателя на портале КузГТУ — это нужно, чтобы
                                система знала ваш <code>api_teacher_id</code> и могла
                                подтягивать расписание.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-person-plus-fill"></i> Как зарегистрироваться</h4>
                            <ol class="docs-steps">
                                <li>
                                    Откройте страницу <a href="register.php">регистрации</a>.
                                    Она доступна без входа.
                                </li>
                                <li>
                                    Введите <strong>фамилию</strong> (минимум 2 символа).
                                    Система отправит запрос на портал КузГТУ и найдёт
                                    всех преподавателей с такой фамилией.
                                </li>
                                <li>
                                    Из списка выберите <strong>себя</strong>.
                                    Проверьте кафедру и должность — если они совпадают,
                                    кликайте по строке.
                                </li>
                                <li>
                                    Заполните форму:
                                    <ul class="docs-list" style="margin-top:8px;">
                                        <li><strong>Кафедра</strong> — подставится автоматически,
                                            можно изменить;</li>
                                        <li><strong>Логин</strong> — латиница, цифры,
                                            символы <code>_</code>, <code>.</code>, <code>-</code>.
                                            Минимум 4 символа;</li>
                                        <li><strong>Email</strong> — обязателен, нужен
                                            для восстановления пароля;</li>
                                        <li><strong>Пароль</strong> — минимум 6 символов,
                                            вводится дважды.</li>
                                    </ul>
                                </li>
                                <li>
                                    Нажмите <strong>«Зарегистрироваться»</strong>.
                                    После успеха система перенаправит вас
                                    в личный кабинет.
                                </li>
                            </ol>

                            <h4 class="docs-h"><i class="bi bi-box-arrow-in-right"></i> Как войти</h4>
                            <ol class="docs-steps">
                                <li>Откройте <a href="login.php">страницу входа</a>.</li>
                                <li>Введите <strong>логин</strong> и <strong>пароль</strong>.</li>
                                <li>
                                    При первом входе система может перекинуть
                                    на страницу заполнения профиля:
                                    <ul class="docs-list" style="margin-top:8px;">
                                        <li><a href="fill_email.php">fill_email.php</a> —
                                            если нет email;</li>
                                        <li><a href="fill_department.php">fill_department.php</a> —
                                            если не указаны институт и кафедра.</li>
                                    </ul>
                                </li>
                                <li>После заполнения вы попадёте на дашборд.</li>
                            </ol>

                            <h4 class="docs-h"><i class="bi bi-key-fill"></i> Забыли пароль</h4>
                            <ol class="docs-steps">
                                <li>На странице входа нажмите <strong>«Забыли пароль?»</strong>.</li>
                                <li>Введите логин, email и новый пароль дважды.</li>
                                <li>Система создаст <strong>заявку</strong> на смену пароля.</li>
                                <li>Администратор проверяет заявку и одобряет её —
                                    вам на email приходит ссылка.</li>
                                <li>Переход по ссылке → пароль меняется автоматически.</li>
                            </ol>

                            <div class="docs-info docs-info--warn">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div>
                                    <strong>Email обязателен.</strong>
                                    Без него система не пустит вас на дашборд —
                                    сначала придётся заполнить email.
                                    Это защита от потери доступа.
                                </div>
                            </div>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-info-circle-fill"></i>
                                <div>
                                    <strong>Уже активная заявка.</strong>
                                    Если по вашему аккаунту уже есть
                                    незакрытая заявка на сброс пароля —
                                    подать вторую нельзя. Дождитесь
                                    обработки администратором.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     3. ПРОФИЛЬ
                     ================================================== -->
                <section class="docs-section" id="profile">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--purple"><i class="bi bi-person-vcard"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 03</div>
                                <h2 class="docs-card-title">Профиль и настройки</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="profile.php">Профиль</a> — личная страница
                                с вашей статистикой и быстрыми действиями.
                                Доступна из верхнего меню.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-person-badge"></i> Что на странице</h4>
                            <ul class="docs-list">
                                <li><strong>Шапка профиля:</strong> аватар с инициалами,
                                    ФИО, роль, институт, кафедра;</li>
                                <li><strong>Карточки статистики:</strong>
                                    <ul class="docs-list" style="margin-top:6px;">
                                        <li>пар проведено за всё время;</li>
                                        <li>средняя посещаемость по всем занятиям;</li>
                                        <li>сколько групп вы ведёте;</li>
                                        <li>сколько всего студентов.</li>
                                    </ul>
                                </li>
                                <li><strong>Личная информация:</strong> ФИО, логин, email,
                                    институт, кафедра, роль, ID на портале КузГТУ;</li>
                                <li><strong>Быстрые ссылки:</strong>
                                    <ul class="docs-list" style="margin-top:6px;">
                                        <li><a href="change_password.php">Сменить пароль</a>;</li>
                                        <li><a href="session.php">Мои ведомости</a>;</li>
                                        <li><a href="debtors.php">Должники</a>;</li>
                                        <li><a href="audit.php">Журнал действий</a>.</li>
                                    </ul>
                                </li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-pencil-square"></i> Что можно изменить</h4>
                            <p>
                                Кнопка <strong>«Редактировать»</strong> в шапке профиля
                                открывает модалку с тремя полями:
                            </p>
                            <ul class="docs-list">
                                <li><strong>Email</strong> — должен быть уникальным,
                                    на него приходят ссылки восстановления;</li>
                                <li><strong>Институт</strong> — из справочника КузГТУ;</li>
                                <li><strong>Кафедра</strong> — подгружается после
                                    выбора института.</li>
                            </ul>

                            <div class="docs-info docs-info--success">
                                <i class="bi bi-check-circle-fill"></i>
                                <div>
                                    Все изменения профиля попадают
                                    в <a href="audit.php">журнал действий</a>
                                    отдельной записью — с указанием
                                    старого и нового значения.
                                </div>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-shield-lock"></i> Смена пароля</h4>
                            <p>
                                Отдельная страница —
                                <a href="change_password.php">change_password.php</a>.
                                Требуется ввести текущий пароль, новый пароль
                                (минимум 6 символов) и повтор. После сохранения
                                старый пароль перестаёт действовать сразу.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     4. ДАШБОРД
                     ================================================== -->
                <section class="docs-section" id="dashboard">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon"><i class="bi bi-speedometer2"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 04</div>
                                <h2 class="docs-card-title">Дашборд</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="dashboard.php">Дашборд</a> — главная страница
                                после входа. Здесь собрана вся сводка дня и основные
                                метрики. Дашборд <strong>автоматически синхронизирует
                                расписание</strong>, если с последней синхронизации
                                прошло больше 30 минут.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-grid-3x3-gap-fill"></i> Что вы увидите</h4>
                            <ul class="docs-list">
                                <li>
                                    <strong>Приветствие</strong> с вашим именем,
                                    кафедрой, институтом, датой и временем.
                                </li>
                                <li>
                                    <strong>Виджет подразделения</strong> —
                                    только для зав. кафедрой, дирекции и админа.
                                    Показывает число преподавателей, групп
                                    и среднюю посещаемость за 30 дней.
                                </li>
                                <li>
                                    <strong>4 карточки статистики:</strong>
                                    <ul class="docs-list" style="margin-top:6px;">
                                        <li>занятий сегодня (и на неделе);</li>
                                        <li>посещаемость за 30 дней;</li>
                                        <li>сдано работ за 30 дней;</li>
                                        <li>должников — переход на
                                            <a href="debtors.php">страницу должников</a>.</li>
                                    </ul>
                                </li>
                                <li>
                                    <strong>Расписание на сегодня</strong> —
                                    карточки занятий со временем, типом, группой,
                                    аудиторией. У каждого занятия две кнопки:
                                    «Отметить» и «Работы» — переход сразу на нужную
                                    страницу.
                                </li>
                                <li>
                                    <strong>Моя сессия</strong> — последние 6 ведомостей
                                    с прогрессом заполнения и статусом
                                    (открыта / закрыта).
                                </li>
                                <li>
                                    <strong>Мои группы</strong> — чипы групп
                                    с числом студентов. Клик открывает карточку группы.
                                </li>
                                <li>
                                    <strong>3 графика</strong> (Chart.js):
                                    <ul class="docs-list" style="margin-top:6px;">
                                        <li>посещаемость по неделям (линия);</li>
                                        <li>типы занятий (пончик);</li>
                                        <li>топ-5 групп по посещаемости (горизонтальные бары).</li>
                                    </ul>
                                </li>
                                <li>
                                    <strong>Требуют внимания</strong> — топ-5 должников
                                    с числом пропусков. Клик по студенту
                                    открывает список должников с фильтром
                                    по его группе.
                                </li>
                            </ul>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-lightbulb-fill"></i>
                                <div>
                                    <strong>График в модалке.</strong>
                                    Кликните по любому графику — он откроется
                                    в большом модальном окне. Это удобно
                                    для презентаций и анализа.
                                </div>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-arrow-repeat"></i> Синхронизация расписания</h4>
                            <p>
                                В шапке дашборда есть кнопка <strong>«Обновить»</strong> —
                                она запускает синхронизацию с порталом КузГТУ.
                                После завершения появляется плашка:
                            </p>
                            <div class="docs-ui">
                                <div class="docs-ui-title"><i class="bi bi-cloud-download"></i> Результат синхронизации</div>
                                <div class="docs-ui-row">
                                    <span class="dot" style="background:#30d158;"></span>
                                    <span class="lbl">Обновлено: +5 новых, ~12 изменений</span>
                                </div>
                            </div>
                            <p class="mb-0">
                                Если API недоступен — появится жёлтая плашка
                                с текстом ошибки. В этом случае попробуйте
                                позже или проверьте доступ к порталу.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     5. РАСПИСАНИЕ
                     ================================================== -->
                <section class="docs-section" id="schedule">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--teal"><i class="bi bi-calendar-week"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 05</div>
                                <h2 class="docs-card-title">Расписание</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="schedule.php">Расписание</a> — недельная
                                сетка занятий. Это визуальное представление всех
                                ваших пар за 7 дней, с автоматической синхронизацией
                                и возможностью ручного редактирования.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-arrow-repeat"></i> Синхронизация с API КузГТУ</h4>
                            <ul class="docs-list">
                                <li>Запрос идёт на
                                    <code>https://portal.kuzstu.ru/api/teacher_schedule?teacher_id=ВАШ_ID</code>;</li>
                                <li>Данные приходят в формате JSON;</li>
                                <li>Если группы нет в БД — она <strong>создаётся
                                    автоматически</strong> вместе
                                    с <code>api_group_id</code>;</li>
                                <li>Время определяется по номеру пары:
                                    <table class="docs-table" style="margin-top:8px;">
                                        <thead>
                                            <tr><th>№</th><th>Время</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr><td>1</td><td>09:00 – 10:35</td></tr>
                                            <tr><td>2</td><td>10:50 – 12:25</td></tr>
                                            <tr><td>3</td><td>13:20 – 14:55</td></tr>
                                            <tr><td>4</td><td>15:10 – 16:45</td></tr>
                                            <tr><td>5</td><td>17:00 – 18:35</td></tr>
                                            <tr><td>6</td><td>18:50 – 20:20</td></tr>
                                        </tbody>
                                    </table>
                                </li>
                                <li>Тип занятия определяется по сокращению:
                                    <code>л.</code> → лекция, <code>пр.</code> → практика,
                                    <code>лаб.</code> → лабораторная,
                                    <code>сем.</code> → семинар;</li>
                                <li>Подгруппа сохраняется в поле <code>subgroup</code>;</li>
                                <li><strong>Ручные занятия не удаляются</strong> —
                                    они имеют флаг <code>is_manual = 1</code>.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-grid-3x3"></i> Что в сетке</h4>
                            <ul class="docs-list">
                                <li><strong>7 колонок</strong> — по одному дню на каждую;</li>
                                <li>Шапка дня: название дня, дата, число пар;</li>
                                <li><strong>Сегодняшний день</strong> выделен
                                    синей рамкой и градиентной шапкой;</li>
                                <li><strong>Выходные</strong> — серый фон;</li>
                                <li><strong>Карточка занятия:</strong> время, тип,
                                    дисциплина, группа, аудитория;</li>
                                <li><strong>Потоковые лекции</strong> объединяются
                                    в одну карточку с бейджем «поток» — даже если
                                    у вас несколько групп на одной лекции;</li>
                                <li><strong>Текущая пара</strong> пульсирует
                                    зелёным с меткой «LIVE»;</li>
                                <li><strong>Следующая</strong> подсвечена голубым
                                    с меткой «Далее»;</li>
                                <li><strong>Прошедшие</strong> затемнены.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-window"></i> Модалка деталей занятия</h4>
                            <p>Клик по карточке занятия открывает окно с полной информацией:</p>
                            <ul class="docs-list">
                                <li>иконка и название типа занятия;</li>
                                <li>дисциплина;</li>
                                <li>дата, день недели, время;</li>
                                <li>группа (список всех групп потока);</li>
                                <li>аудитория;</li>
                                <li>две кнопки: <strong>«Посещаемость»</strong>
                                    и <strong>«Работы»</strong> — переход
                                    на соответствующее занятие;</li>
                                <li>кнопка <strong>«Удалить»</strong> — только
                                    для ручных занятий.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-plus-circle"></i> Ручное добавление занятия</h4>
                            <ol class="docs-steps">
                                <li>Кнопка <strong>«Добавить»</strong> в шапке страницы.</li>
                                <li>Заполните:
                                    <ul class="docs-list" style="margin-top:8px;">
                                        <li>Дисциплина (обязательно);</li>
                                        <li>Дата (обязательно);</li>
                                        <li>Пара № (из списка);</li>
                                        <li>Тип занятия (по умолчанию «Практика»);</li>
                                        <li>Аудитория;</li>
                                        <li>Группа (из списка) <strong>или</strong>
                                            новая группа вручную.</li>
                                    </ul>
                                </li>
                                <li>Нажмите <strong>«Добавить»</strong>.</li>
                            </ol>

                            <div class="docs-info docs-info--success">
                                <i class="bi bi-lightbulb-fill"></i>
                                <div>
                                    <strong>Когда добавлять вручную.</strong>
                                    Если занятие есть в реальности, но его нет
                                    на портале (замена, перенос, внеучебное
                                    мероприятие) — добавьте его сами.
                                    Синхронизация его не удалит.
                                </div>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-arrow-left-right"></i> Навигация по неделям</h4>
                            <ul class="docs-list mb-0">
                                <li><strong>«Предыдущая»</strong> — неделя назад;</li>
                                <li><strong>«Следующая»</strong> — неделя вперёд;</li>
                                <li><strong>«Текущая»</strong> — вернуться к этой неделе;</li>
                                <li>Параметр URL: <code>?week=+1</code>
                                    (следующая), <code>?week=-2</code> (позапрошлая).</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     6. ГРУППЫ
                     ================================================== -->
                <section class="docs-section" id="groups">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--orange"><i class="bi bi-people"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 06</div>
                                <h2 class="docs-card-title">Группы и студенты</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                Работа с группами и студентами ведётся через
                                три страницы: список групп, карточка группы
                                и импорт списка студентов из CSV.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-list-ul"></i> Список групп</h4>
                            <p>
                                <a href="groups.php">groups.php</a> — таблица
                                со всеми группами: название, институт,
                                число студентов. Кнопка «Открыть» ведёт
                                в карточку группы.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-people-fill"></i> Карточка группы</h4>
                            <p>
                                <a href="group_view.php">group_view.php</a> —
                                список студентов группы: №, зачётка, ФИО, логин.
                                Здесь же — кнопка перехода в <a href="subgroups.php">
                                подгруппы</a>.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-file-earmark-excel"></i> Импорт студентов</h4>
                            <p>
                                <a href="upload_students.php">upload_students.php</a> —
                                массовая загрузка списка из CSV/Excel.
                                Перед импортом подготовьте файл:
                            </p>
                            <ol class="docs-steps">
                                <li>Откройте Excel с исходным списком студентов.</li>
                                <li><strong>Файл → Сохранить как</strong>.</li>
                                <li>Тип файла: <strong>CSV UTF-8 (разделители — запятые)</strong>.</li>
                                <li>Сохраните и закройте Excel.</li>
                                <li>В системе выберите файл и нажмите
                                    <strong>«Загрузить и импортировать»</strong>.</li>
                            </ol>

                            <h4 class="docs-h"><i class="bi bi-table"></i> Структура CSV</h4>
                            <div class="docs-table-wrap">
                                <table class="docs-table">
                                    <thead>
                                        <tr>
                                            <th>Столбец</th>
                                            <th>Содержимое</th>
                                            <th>Пример</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td><code>A</code></td><td>№ зачётки</td><td>23Т013</td></tr>
                                        <tr><td><code>B</code></td><td>Фамилия</td><td>Иванов</td></tr>
                                        <tr><td><code>C</code></td><td>Имя</td><td>Иван</td></tr>
                                        <tr><td><code>D</code></td><td>Отчество</td><td>Иванович</td></tr>
                                        <tr><td><code>E</code></td><td>Группа</td><td>ИСт-241</td></tr>
                                        <tr><td><code>F</code></td><td>Кафедра</td><td>Кафедра ИиИС</td></tr>
                                        <tr><td><code>G</code></td><td>Институт</td><td>ИИТМА</td></tr>
                                        <tr><td><code>H</code></td><td>Направление</td><td>09.03.02</td></tr>
                                        <tr><td><code>I</code></td><td>Специальность</td><td>ИСиТ</td></tr>
                                        <tr><td><code>J</code></td><td>Статус</td><td>Обучается</td></tr>
                                        <tr><td><code>K</code></td><td>Логин КузГТУ</td><td>ivanovaa</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-info-circle-fill"></i>
                                <div>
                                    <strong>Система гибкая к формату.</strong>
                                    Кодировка определяется автоматически
                                    (UTF-8 или Windows-1251), разделитель —
                                    тоже (запятая, точка с запятой, табуляция).
                                    Заголовок распознаётся по ключевым словам
                                    («зачёт», «фамилия» и т.п.).
                                </div>
                            </div>

                            <div class="docs-info docs-info--warn">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div>
                                    <strong>Импортируются только «Обучается».</strong>
                                    Студенты с другим статусом (отчислен,
                                    академический отпуск) — пропускаются.
                                    Повторный импорт <strong>не создаёт дубликатов</strong>:
                                    если студент уже есть (по зачётке или
                                    по ФИО + группа), обновляются только
                                    пустые поля.
                                </div>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-bar-chart-steps"></i> Что видно после импорта</h4>
                            <ul class="docs-list mb-0">
                                <li><strong>Всего обработано</strong> строк;</li>
                                <li><strong>Добавлено</strong> новых студентов;</li>
                                <li><strong>Обновлено</strong> существующих;</li>
                                <li><strong>Пропущено</strong> (не «Обучается»);</li>
                                <li><strong>Групп создано / использовано / дополнено</strong>;</li>
                                <li><strong>Детали</strong> — список добавленных
                                    и обновлённых студентов.</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     7. ПОДГРУППЫ
                     ================================================== -->
                <section class="docs-section" id="subgroups">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--pink"><i class="bi bi-diagram-3"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 07</div>
                                <h2 class="docs-card-title">Подгруппы</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="subgroups.php">subgroups.php</a> —
                                распределение студентов группы на подгруппы 1 и 2.
                                Это нужно для лабораторных, которые идут
                                отдельно для каждой половины.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-hand-index-thumb"></i> Как назначить подгруппу</h4>
                            <ul class="docs-list">
                                <li>
                                    <strong>Вручную.</strong> У каждого студента
                                    три кнопки: <code>1</code>, <code>2</code>, <code>—</code>
                                    (без подгруппы). Клик сохраняет сразу, без
                                    перезагрузки страницы.
                                </li>
                                <li>
                                    <strong>Массово.</strong> Отметьте студентов
                                    галочками (или «Выбрать всех») и нажмите
                                    «Подгруппа 1», «Подгруппа 2» или «Сбросить».
                                </li>
                                <li>
                                    <strong>Автоматически.</strong> Кнопка
                                    <strong>«Распределить на 2 (по половинам)»</strong>
                                    — первая половина списка (по алфавиту)
                                    уходит в подгруппу 1, вторая — в подгруппу 2.
                                </li>
                                <li>
                                    <strong>Очистить всё.</strong> Кнопка
                                    «Очистить все» снимает подгруппы
                                    у всех студентов группы.
                                </li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-diagram-2"></i> Где используется</h4>
                            <ul class="docs-list">
                                <li>На <a href="attendance.php">странице посещаемости</a>
                                    появляются вкладки «Все / Подгруппа 1 / Подгруппа 2»;</li>
                                <li>Если в расписании указана подгруппа,
                                    система <strong>автоматически откроет
                                    нужную вкладку</strong> (с красной точкой);</li>
                                <li>В отчётах и ведомостях подгруппа
                                    отображается отдельным бейджем рядом с ФИО;</li>
                                <li>Студенты без подгруппы показываются
                                    на любой вкладке — чтобы их случайно
                                    не пропустили.</li>
                            </ul>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-lightbulb-fill"></i>
                                <div>
                                    <strong>Правило.</strong>
                                    Если у студента стоит подгруппа, и он
                                    не отмечен на паре другой подгруппы —
                                    это <strong>не пропуск</strong>.
                                    В матрице посещаемости стоит «·»
                                    (не отмечен), а не «✗».
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     8. ПОСЕЩАЕМОСТЬ
                     ================================================== -->
                <section class="docs-section" id="attendance">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--green"><i class="bi bi-check2-square"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 08</div>
                                <h2 class="docs-card-title">Посещаемость</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="attendance.php">attendance.php</a> —
                                ключевая страница ежедневной работы.
                                Здесь преподаватель отмечает студентов
                                на занятии.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-list-check"></i> Как отметить посещаемость</h4>
                            <ol class="docs-steps">
                                <li>
                                    Слева выберите занятие на сегодня
                                    (в сайдбаре отображаются только
                                    занятия текущего дня).
                                </li>
                                <li>
                                    Если нужного занятия нет — нажмите
                                    <strong>«Выбрать другое занятие»</strong>.
                                    Откроется модалка со всеми вашими занятиями,
                                    сгруппированными по датам. Есть поиск
                                    по дисциплине, группе и аудитории.
                                </li>
                                <li>
                                    Напротив каждого студента выберите
                                    один из вариантов:
                                    <ul class="docs-list" style="margin-top:8px;">
                                        <li><strong>✅ галочка</strong> — был;</li>
                                        <li><strong>❌ крестик</strong> — пропустил;</li>
                                        <li><strong>⬜ ничего</strong> — не отмечен
                                            (используйте для другой подгруппы).</li>
                                    </ul>
                                </li>
                                <li>
                                    Нажмите <strong>«Сохранить»</strong>
                                    внизу страницы.
                                </li>
                                <li>
                                    После сохранения страница перезагрузится
                                    и покажет актуальные данные + зелёную плашку
                                    с результатом («Сохранено: 12 отметок»).
                                </li>
                            </ol>

                            <h4 class="docs-h"><i class="bi bi-lightning-charge-fill"></i> Быстрые кнопки</h4>
                            <ul class="docs-list">
                                <li><strong>«Все были»</strong> — отметить всю
                                    видимую группу присутствующими;</li>
                                <li><strong>«Все пропустили»</strong> — установить
                                    всем «отсутствует»;</li>
                                <li><strong>«Сбросить»</strong> — снять все отметки
                                    (никто не отмечен).</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-diagram-3-fill"></i> Фильтр по подгруппам</h4>
                            <p>
                                Если у группы есть подгруппы — сверху появляются
                                вкладки:
                            </p>
                            <div class="docs-ui">
                                <div class="docs-ui-title">
                                    <i class="bi bi-funnel"></i> Показывать
                                </div>
                                <div class="docs-ui-row">
                                    <span class="dot" style="background:#86868b;"></span>
                                    <span class="lbl">Все</span>
                                    <span class="val">25</span>
                                </div>
                                <div class="docs-ui-row">
                                    <span class="dot" style="background:#0071e3;"></span>
                                    <span class="lbl">Подгруппа 1</span>
                                    <span class="val">13</span>
                                </div>
                                <div class="docs-ui-row">
                                    <span class="dot" style="background:#bf5af2;"></span>
                                    <span class="lbl">Подгруппа 2</span>
                                    <span class="val">12</span>
                                </div>
                            </div>
                            <ul class="docs-list">
                                <li>Вкладка, отмеченная <strong>красной точкой</strong> —
                                    та, что указана в расписании.
                                    Она открывается автоматически.</li>
                                <li>Студенты <strong>без подгруппы</strong>
                                    показываются на любой вкладке;</li>
                                <li>Счётчики «Был / Пропустил / Не отмечен»
                                    считаются по <strong>видимым</strong> студентам.</li>
                            </ul>

                            <div class="docs-info docs-info--warn">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div>
                                    <strong>Не отмечайте чужую подгруппу.</strong>
                                    Если студент в другой подгруппе —
                                    оставьте его не отмеченным. Система
                                    <strong>не считает это пропуском</strong>.
                                </div>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-people-fill"></i> Потоковые лекции</h4>
                            <p>
                                Если занятие — потоковая лекция
                                (несколько групп сразу), в шапке отображается
                                бейдж <span class="docs-badge docs-badge--purple">
                                <i class="bi bi-diagram-3"></i> поток · N</span>.
                                Отметки сохранятся во все группы потока
                                <strong>одновременно</strong>. Список студентов —
                                объединённый, с указанием группы у каждого.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-clock-history"></i> Где смотреть историю</h4>
                            <p class="mb-0">
                                Все отметки сохраняются в
                                <a href="audit.php">журнал действий</a>:
                                одна запись на сохранение. В ней — дисциплина,
                                дата, группа, сколько было отмечено
                                и сколько изменений.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     9. РАБОТЫ
                     ================================================== -->
                <section class="docs-section" id="works">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--purple"><i class="bi bi-journal-check"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 09</div>
                                <h2 class="docs-card-title">Сданные работы</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="works.php">works.php</a> — учёт сданных
                                лабораторных, курсовых, практических
                                и других работ. Каждая работа хранится
                                в разрезе занятия и студента.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-pencil-square"></i> Как отметить работу</h4>
                            <ol class="docs-steps">
                                <li>Выберите занятие в сайдбаре слева.</li>
                                <li>
                                    В поле сверху введите <strong>номер или
                                    название работы</strong>. Например:
                                    <em>«Лабораторная №3»</em>,
                                    <em>«Курсовая»</em>,
                                    <em>«Практика №5»</em>.
                                </li>
                                <li>Нажмите <strong>«Открыть»</strong>.</li>
                                <li>
                                    Включите переключатель у тех, кто сдал.
                                    Можно использовать кнопки
                                    <strong>«Все сдали»</strong> и
                                    <strong>«Сбросить»</strong>.
                                </li>
                                <li>Нажмите <strong>«Сохранить»</strong>.</li>
                            </ol>

                            <h4 class="docs-h"><i class="bi bi-clock-history"></i> Ранее сохранённые работы</h4>
                            <p>
                                Под полем ввода появляются чипы —
                                все работы, которые вы уже сохраняли
                                по этому занятию. Клик по чипу открывает
                                работу для редактирования.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-database-check"></i> Как это хранится</h4>
                            <ul class="docs-list">
                                <li>Таблица <code>submitted_works</code>
                                    хранит <strong>только сдавших</strong>;</li>
                                <li>Если студент не сдал — записи о нём нет;</li>
                                <li>Название работы — свободный текст, хранится
                                    в поле <code>work_number</code>;</li>
                                <li>Каждая пара «работа + студент» — уникальна
                                    (защита от дублей).</li>
                            </ul>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-info-circle-fill"></i>
                                <div>
                                    <strong>Почему только сдавшие?</strong>
                                    Так проще считать отчёты:
                                    «сдано 15 работ из 25» — это
                                    <code>COUNT</code> по таблице без учёта
                                    отсутствующих записей.
                                </div>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-bar-chart"></i> Где это видно</h4>
                            <ul class="docs-list mb-0">
                                <li>В <a href="reports.php">отчётах</a> —
                                    матрица сданных работ;</li>
                                <li>В <a href="session_sheet.php">ведомости</a> —
                                    процент сданных работ для автодопуска;</li>
                                <li>В <a href="student.php">кабинете студента</a> —
                                    список работ по каждому занятию.</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     10. ОТЧЁТЫ
                     ================================================== -->
                <section class="docs-section" id="reports">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon"><i class="bi bi-bar-chart"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 10</div>
                                <h2 class="docs-card-title">Отчёты</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="reports.php">reports.php</a> — детальный
                                отчёт по одному предмету и одной группе
                                за выбранный период. Основной инструмент
                                преподавателя для анализа и подготовки
                                к сессии.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-funnel"></i> Фильтры</h4>
                            <ul class="docs-list">
                                <li><strong>Предмет</strong> — только ваши
                                    (из таблицы занятий);</li>
                                <li><strong>Группа</strong> — только ваши;</li>
                                <li><strong>Период</strong> — «С» и «По».
                                    По умолчанию — с начала текущего семестра;</li>
                                <li><strong>Живой поиск</strong> — по ФИО
                                    и зачётке. Фильтрует все таблицы
                                    одновременно.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-grid-3x3-gap"></i> Что в отчёте</h4>
                            <ul class="docs-list">
                                <li>
                                    <strong>Карточки статистики:</strong>
                                    всего занятий, присутствий, пропусков,
                                    сдано работ (и число уникальных работ).
                                </li>
                                <li>
                                    <strong>Живой поиск:</strong> поле сверху
                                    фильтрует таблицы, выделяет совпадения
                                    жёлтым и показывает «найдено N из M».
                                </li>
                                <li>
                                    <strong>Сводная по студентам:</strong>
                                    таблица с колонками:
                                    №, зачётка, ФИО, занятий, был, пропустил,
                                    не отмечен, посещаемость (прогресс-бар),
                                    работ сдано.
                                </li>
                                <li>
                                    <strong>Матрица посещаемости:</strong>
                                    строки — студенты, столбцы — даты занятий.
                                    В ячейках: <span style="color:#198754;">✓</span> был,
                                    <span style="color:#842029;">✗</span> пропустил,
                                    <span style="color:#86868b;">·</span> не отмечен.
                                    Внизу — итог пропусков на паре.
                                </li>
                                <li>
                                    <strong>Матрица сданных работ:</strong>
                                    строки — студенты, столбцы — работы.
                                    В ячейках <span style="color:#198754;">✓</span> сдал
                                    или <span style="color:#86868b;">·</span> нет.
                                    Внизу — сколько сдало на работу.
                                </li>
                                <li>
                                    <strong>Список занятий:</strong> дата, тип, время,
                                    аудитория, поток, подгруппа, сколько было.
                                    Кнопки перехода в посещаемость и работы.
                                </li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-download"></i> Экспорт</h4>
                            <ul class="docs-list mb-0">
                                <li><strong>«Печать»</strong> — печать страницы
                                    через <code>window.print()</code>.
                                    Скрываются фильтры и кнопки;</li>
                                <li><strong>«Скачать CSV»</strong> — выгружается
                                    сводная по студентам. Файл в UTF-8 с BOM
                                    (открывается в Excel без «кракозябр»),
                                    разделитель <code>;</code>.</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     11. ПРОВЕРКА ОТЧЁТОВ
                     ================================================== -->
                <section class="docs-section" id="check_reports">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--orange"><i class="bi bi-clipboard-check"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 11</div>
                                <h2 class="docs-card-title">Проверка отчётов</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="check_reports.php">check_reports.php</a> —
                                расширенный инструмент для
                                <strong>зав. кафедрой</strong>,
                                <strong>дирекции</strong>
                                и <strong>администратора</strong>.
                                Преподавателю недоступен.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-layers"></i> Три режима</h4>
                            <div class="docs-table-wrap">
                                <table class="docs-table">
                                    <thead>
                                        <tr>
                                            <th>Режим</th>
                                            <th>Что показывает</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <i class="bi bi-grid-1x2-fill"></i>
                                                <strong>Обзор групп</strong>
                                            </td>
                                            <td>
                                                Сводка по всем доступным группам:
                                                посещаемость за период (прогресс-бар),
                                                пропуски, сданные работы,
                                                мини-heatmap по неделям.
                                                Сортировка — <strong>сначала
                                                проблемные</strong>.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <i class="bi bi-people-fill"></i>
                                                <strong>Группа</strong>
                                            </td>
                                            <td>
                                                Детальный отчёт по одной группе:
                                                heatmap-календарь, сводка
                                                по предметам, таблица студентов
                                                с посещаемостью и работами.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <i class="bi bi-book"></i>
                                                <strong>Предмет</strong>
                                            </td>
                                            <td>
                                                Сводка по предмету: количество
                                                групп, занятий, средняя посещаемость.
                                                Heatmap + таблица по группам +
                                                таблица по студентам.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-calendar3"></i> Heatmap-календарь</h4>
                            <p>
                                Визуализация посещаемости по дням. Каждый квадрат —
                                один день. Цвет зависит от процента:
                            </p>
                            <ul class="docs-list">
                                <li><span style="display:inline-block;width:14px;height:14px;background:#ff453a;border-radius:3px;vertical-align:middle;"></span>
                                    <strong>красный</strong> — 0% (никто не был);</li>
                                <li><span style="display:inline-block;width:14px;height:14px;background:#ff9f0a;border-radius:3px;vertical-align:middle;"></span>
                                    <strong>оранжевый</strong> — от 25%;</li>
                                <li><span style="display:inline-block;width:14px;height:14px;background:#ffd60a;border-radius:3px;vertical-align:middle;"></span>
                                    <strong>жёлтый</strong> — от 50%;</li>
                                <li><span style="display:inline-block;width:14px;height:14px;background:#a8e063;border-radius:3px;vertical-align:middle;"></span>
                                    <strong>салатовый</strong> — от 75%;</li>
                                <li><span style="display:inline-block;width:14px;height:14px;background:#30d158;border-radius:3px;vertical-align:middle;"></span>
                                    <strong>зелёный</strong> — от 90%.</li>
                            </ul>
                            <p>
                                Пустые (серые) — занятий не было.
                                Полосатые — будущие дни.
                                Наведите курсор — увидите дату
                                и точный процент.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-person"></i> Heatmap по студенту</h4>
                            <p>
                                В режиме «Группа» сверху есть выпадающий список
                                студентов. При выборе система запрашивает
                                данные через AJAX и перестраивает календарь
                                для одного студента. Это удобно,
                                когда нужно посмотреть на конкретного должника.
                            </p>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-shield-check"></i>
                                <div>
                                    <strong>Область доступа соблюдается.</strong>
                                    Зав. кафедрой видит только преподавателей
                                    своей кафедры. Дирекция — свой институт.
                                    Данные ограничиваются автоматически
                                    на уровне SQL-запросов.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     12. ДОЛЖНИКИ
                     ================================================== -->
                <section class="docs-section" id="debtors">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--red"><i class="bi bi-exclamation-triangle"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 12</div>
                                <h2 class="docs-card-title">Должники</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="debtors.php">debtors.php</a> — список
                                студентов с пропусками и низкой посещаемостью.
                                Быстрый поиск проблемных студентов
                                по всей вашей области доступа.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-funnel"></i> Фильтры</h4>
                            <ul class="docs-list">
                                <li><strong>Группа</strong> — из доступных;</li>
                                <li><strong>Предмет</strong> — из ваших занятий;</li>
                                <li><strong>Преподаватель</strong> — только
                                    для ролей с широким доступом;</li>
                                <li><strong>Период</strong> — «С» и «По»;</li>
                                <li><strong>Порог пропусков</strong> —
                                    «≥ N» (по умолчанию 3);</li>
                                <li><strong>Порог посещаемости</strong> —
                                    «≤ N%» (по умолчанию 60%);</li>
                                <li><strong>Сортировка</strong> — по критичности,
                                    пропускам, посещаемости, работам.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-fire"></i> Уровни критичности</h4>
                            <p>Система рассчитывает балл критичности по формуле:</p>
                            <ul class="docs-list">
                                <li><strong>+40</strong> — если посещаемость ≤ 30%;</li>
                                <li><strong>+25</strong> — если ≤ 50%;</li>
                                <li><strong>+10</strong> — если ≤ 70%;</li>
                                <li><strong>+40</strong> — если пропусков ≥ 10;</li>
                                <li><strong>+20</strong> — если ≥ 5;</li>
                                <li><strong>+10</strong> — если ≥ 3.</li>
                            </ul>
                            <div class="docs-table-wrap">
                                <table class="docs-table">
                                    <thead>
                                        <tr>
                                            <th>Балл</th>
                                            <th>Уровень</th>
                                            <th>Что значит</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>≥ 60</td>
                                            <td><span class="docs-badge docs-badge--red"><i class="bi bi-fire"></i> Критично</span></td>
                                            <td>Много пропусков + низкая посещаемость. Срочно разбираться.</td>
                                        </tr>
                                        <tr>
                                            <td>30–59</td>
                                            <td><span class="docs-badge docs-badge--orange"><i class="bi bi-exclamation-triangle-fill"></i> Внимание</span></td>
                                            <td>Стоит обратить внимание и предупредить студента.</td>
                                        </tr>
                                        <tr>
                                            <td>&lt; 30</td>
                                            <td><span class="docs-badge docs-badge--gray"><i class="bi bi-circle"></i> Низкий</span></td>
                                            <td>Незначительные пропуски, но под контролем.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-download"></i> Экспорт и копирование</h4>
                            <ul class="docs-list mb-0">
                                <li><strong>«Скачать CSV»</strong> — полный список
                                    с уровнем критичности, посещаемостью,
                                    работами. Разделитель <code>;</code>,
                                    UTF-8 с BOM;</li>
                                <li><strong>«Скопировать список»</strong> —
                                    текстовый формат для вставки
                                    в письмо, чат или мессенджер.
                                    С группировкой по группам и
                                    отметкой уровня критичности;</li>
                                <li>Клик по названию группы — переход
                                    в отчёт по этой группе
                                    за текущий период.</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     13. СЕССИЯ
                     ================================================== -->
                <section class="docs-section" id="session">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--green"><i class="bi bi-mortarboard"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 13</div>
                                <h2 class="docs-card-title">Сессия и ведомости</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                Раздел для работы с зачётами и экзаменами.
                                Состоит из трёх страниц:
                                <a href="session.php">список ведомостей</a>,
                                <a href="session_sheet.php">редактирование</a>,
                                <a href="session_print.php">печать</a>.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-list-ul"></i> Список ведомостей</h4>
                            <p>
                                Ведомости сгруппированы по предметам.
                                Каждая строка показывает:
                            </p>
                            <ul class="docs-list">
                                <li>тип сессии с бейджем
                                    (<span class="docs-badge docs-badge--green">Зачёт</span>,
                                    <span class="docs-badge docs-badge--red">Экзамен</span>,
                                    <span class="docs-badge docs-badge--orange">Дифф. зачёт</span>);</li>
                                <li>группу и преподавателя;</li>
                                <li>число студентов;</li>
                                <li>пороги допуска (посещаемость % и работы %);</li>
                                <li>счётчики: допущено / не допущено / оценок выставлено;</li>
                                <li>прогресс заполнения (полоса);</li>
                                <li>статус: <span class="docs-badge docs-badge--blue">
                                    <i class="bi bi-unlock-fill"></i> Открыта</span>
                                    или <span class="docs-badge docs-badge--green">
                                    <i class="bi bi-lock-fill"></i> Закрыта</span>.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-funnel"></i> Фильтры</h4>
                            <ul class="docs-list">
                                <li><strong>Семестр</strong> — 2024-2025-1, 2024-2025-2 и т.д.;</li>
                                <li><strong>Группа</strong>;</li>
                                <li><strong>Предмет</strong>;</li>
                                <li><strong>Тип сессии</strong>;</li>
                                <li><strong>Статус</strong> — открыта / закрыта.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-plus-circle"></i> Создание ведомости</h4>
                            <ol class="docs-steps">
                                <li>Кнопка <strong>«Создать ведомость»</strong> в шапке.</li>
                                <li><strong>Семестр</strong> — из списка.</li>
                                <li><strong>Предмет</strong> — с автоподстановкой
                                    из ваших занятий (начните печатать).</li>
                                <li><strong>Группа</strong> — из списка.</li>
                                <li><strong>Тип сессии</strong> — зачёт,
                                    экзамен или дифф. зачёт.</li>
                                <li><strong>Часы по плану</strong> (опционально):
                                    всего, лекции, практики, лабораторные.
                                    Эти данные попадут в печатную форму.</li>
                                <li><strong>Пороги автодопуска:</strong>
                                    мин. посещаемость (%) и мин. сданных работ (%).</li>
                                <li>Нажмите <strong>«Создать»</strong> —
                                    вы перейдёте на страницу редактирования.</li>
                            </ol>

                            <div class="docs-info docs-info--warn">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div>
                                    <strong>Дубликат.</strong>
                                    Если такая же ведомость (по предмету,
                                    группе, типу и семестру) уже существует —
                                    система предложит перейти
                                    к существующей, а не создавать новую.
                                </div>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-pencil-square"></i> Редактирование ведомости</h4>
                            <p>
                                <a href="session_sheet.php">session_sheet.php</a> —
                                основная рабочая страница. Для каждого студента
                                система автоматически считает:
                            </p>
                            <ul class="docs-list">
                                <li><strong>Посещаемость</strong> по всем занятиям
                                    предмета за семестр (прогресс-бар
                                    и процент);</li>
                                <li><strong>Сданные работы</strong> —
                                    по всем занятиям предмета за семестр.</li>
                            </ul>

                            <p>На основе этих данных работает <strong>автодопуск</strong>:</p>
                            <ul class="docs-list">
                                <li><span class="docs-badge docs-badge--green">Допущен</span> —
                                    оба процента ≥ порогов;</li>
                                <li><span class="docs-badge docs-badge--red">Не допущен</span> —
                                    хотя бы один процент ниже порога;</li>
                                <li><span class="docs-badge docs-badge--gray">нет данных</span> —
                                    посещаемость или работы
                                    ещё не отмечены.</li>
                            </ul>

                            <p>Каждое поле можно <strong>переопределить вручную</strong>:</p>
                            <ul class="docs-list">
                                <li>Выпадающий список вердикта:
                                    «Авто / Допущен / Не допущен»;</li>
                                <li>Выпадающий список оценки —
                                    зависит от типа сессии:
                                    <ul class="docs-list" style="margin-top:6px;">
                                        <li><strong>Зачёт:</strong> зачтено / не зачтено / не явился;</li>
                                        <li><strong>Экзамен / дифф. зачёт:</strong>
                                            отлично / хорошо / удовлетворительно /
                                            не удовлетворительно / не явился.</li>
                                    </ul>
                                </li>
                                <li>Поле «Комментарий» — свободный текст
                                    до 2000 символов.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-lightning-charge-fill"></i> Массовые действия</h4>
                            <ul class="docs-list">
                                <li><strong>«Допустить всех»</strong> —
                                    выставить всем вердикт «Допущен»;</li>
                                <li><strong>«Сбросить допуск»</strong> —
                                    вернуть всем режим «Авто»;</li>
                                <li><strong>«Поставить оценку всем»</strong> —
                                    выбрать оценку из списка и применить ко всем;</li>
                                <li><strong>«Сбросить оценки»</strong> —
                                    очистить оценки у всех студентов.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-lock-fill"></i> Статусы ведомости</h4>
                            <ul class="docs-list">
                                <li><strong>Открыта</strong> — можно редактировать.
                                    Кнопка «Закрыть ведомость» переводит
                                    в закрытое состояние;</li>
                                <li><strong>Закрыта</strong> — редактирование
                                    запрещено. Кнопка «Открыть заново»
                                    снимает блокировку.</li>
                            </ul>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-info-circle-fill"></i>
                                <div>
                                    <strong>Журналирование.</strong>
                                    Каждое изменение (допуск, оценка,
                                    комментарий, массовое действие,
                                    закрытие/открытие) записывается
                                    в <a href="audit.php">журнал действий</a>
                                    отдельной строкой.
                                </div>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-printer"></i> Печать ведомости</h4>
                            <p>
                                Кнопка <strong>«Печать»</strong> открывает
                                <a href="session_print.php">отдельную страницу</a>
                                с официальной формой:
                            </p>
                            <ul class="docs-list">
                                <li>Шапка Министерства науки и высшего образования РФ;</li>
                                <li>Полное название университета;</li>
                                <li>Название кафедры / института;</li>
                                <li>Заголовок типа ведомости
                                    («ЗАЧЁТНАЯ ВЕДОМОСТЬ» и т.п.);</li>
                                <li>Дисциплина, группа, преподаватель,
                                    учебный год, семестр, часы;</li>
                                <li>Таблица студентов:
                                    №, зачётка, ФИО, оценка, подпись;</li>
                                <li>Итоги: всего / выставлено / по категориям;</li>
                                <li>Подписи преподавателя, зав. кафедрой, дата.</li>
                            </ul>
                            <p class="mb-0">
                                <strong>Ctrl+P</strong> — печать,
                                <strong>Esc</strong> — закрыть окно.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     14. ЖУРНАЛ
                     ================================================== -->
                <section class="docs-section" id="audit">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon"><i class="bi bi-clock-history"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 14</div>
                                <h2 class="docs-card-title">Журнал действий</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="audit.php">audit.php</a> — история всех
                                изменений в системе. Каждое значимое действие
                                фиксируется в таблице <code>audit_log</code>.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-list-check"></i> Что пишется в журнал</h4>
                            <div class="docs-table-wrap">
                                <table class="docs-table">
                                    <thead>
                                        <tr>
                                            <th>Действие</th>
                                            <th>Описание</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td><code>login.success</code></td><td>Успешный вход</td></tr>
                                        <tr><td><code>login.failed</code></td><td>Неудачная попытка входа</td></tr>
                                        <tr><td><code>logout</code></td><td>Выход из системы</td></tr>
                                        <tr><td><code>profile.update</code></td><td>Изменение профиля</td></tr>
                                        <tr><td><code>students.import</code></td><td>Импорт студентов из CSV</td></tr>
                                        <tr><td><code>attendance.update</code></td><td>Сохранение посещаемости</td></tr>
                                        <tr><td><code>works.update</code></td><td>Сохранение работ</td></tr>
                                        <tr><td><code>subgroup.update</code></td><td>Изменение подгруппы студента</td></tr>
                                        <tr><td><code>subgroups.bulk</code></td><td>Массовое назначение подгрупп</td></tr>
                                        <tr><td><code>subgroups.auto</code></td><td>Автораспределение по половинам</td></tr>
                                        <tr><td><code>subgroups.clear</code></td><td>Очистка всех подгрупп</td></tr>
                                        <tr><td><code>session.create</code></td><td>Создание ведомости</td></tr>
                                        <tr><td><code>session.delete</code></td><td>Удаление ведомости</td></tr>
                                        <tr><td><code>session.grade</code></td><td>Выставление оценки</td></tr>
                                        <tr><td><code>session.allow</code></td><td>Изменение допуска</td></tr>
                                        <tr><td><code>session.comment</code></td><td>Изменение комментария</td></tr>
                                        <tr><td><code>session.bulk</code></td><td>Массовое действие в ведомости</td></tr>
                                        <tr><td><code>session.close</code></td><td>Закрытие ведомости</td></tr>
                                        <tr><td><code>session.reopen</code></td><td>Переоткрытие ведомости</td></tr>
                                        <tr><td><code>audit.cleared</code></td><td>Очистка журнала (только админ)</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4 class="docs-h"><i class="bi bi-table"></i> Что видно в журнале</h4>
                            <ul class="docs-list">
                                <li><strong>Когда</strong> — дата и время;</li>
                                <li><strong>Кто</strong> — ФИО, инициалы в аватаре, IP;</li>
                                <li><strong>Действие</strong> — тип с цветным бейджем;</li>
                                <li><strong>Что именно</strong> — описание,
                                    код сущности (<code>session_sheet#12</code>),
                                    старое и новое значение.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-funnel"></i> Фильтры</h4>
                            <ul class="docs-list">
                                <li>Действие (выпадающий список);</li>
                                <li>Поиск по ФИО, описанию, значениям;</li>
                                <li>Период «С» и «По» (по умолчанию — 30 дней);</li>
                                <li>Пагинация по 50 записей.</li>
                            </ul>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-shield-check"></i>
                                <div>
                                    <strong>Ограничения по ролям.</strong>
                                    Преподаватель видит только свои записи.
                                    Зав. кафедрой — записи своей кафедры.
                                    Дирекция — своего института.
                                    Администратор — всё.
                                </div>
                            </div>

                            <div class="docs-info docs-info--danger">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div>
                                    <strong>Очистка журнала.</strong>
                                    Доступна <strong>только администратору</strong>.
                                    Можно очистить всё или за период.
                                    После очистки в журнале остаётся одна
                                    запись — о самой очистке.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     15. УВЕДОМЛЕНИЯ
                     ================================================== -->
                <section class="docs-section" id="notifications">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--orange"><i class="bi bi-bell"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 15</div>
                                <h2 class="docs-card-title">Уведомления</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="notifications.php">notifications.php</a> —
                                список занятий, которые <strong>уже прошли</strong>,
                                но посещаемость по ним <strong>не отмечена</strong>.
                                Это страховка от «забытых» пар.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-gear"></i> Как работает</h4>
                            <ul class="docs-list">
                                <li>Система смотрит на все занятия
                                    за последние N дней;</li>
                                <li>Если по занятию <strong>нет ни одной отметки</strong>
                                    в таблице <code>attendance</code> —
                                    занятие попадает в список;</li>
                                <li>Занятия сгруппированы по датам;</li>
                                <li>В шапке каждой даты — бейдж
                                    с числом занятий.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-calendar-range"></i> Период</h4>
                            <p>
                                В шапке страницы — переключатель периода:
                                <strong>7, 14, 30 или 90 дней</strong>.
                                Активный период выделен синим.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-box-arrow-up-right"></i> Переход к занятию</h4>
                            <p>
                                Каждое занятие — это ссылка.
                                Клик ведёт сразу на
                                <a href="attendance.php">страницу посещаемости</a>
                                с открытым нужным занятием.
                                Кнопка «Отметить» визуально подчёркивает это.
                            </p>

                            <div class="docs-info docs-info--success">
                                <i class="bi bi-check-circle-fill"></i>
                                <div>
                                    <strong>Всё отмечено?</strong>
                                    Если список пуст — значит
                                    за выбранный период не осталось
                                    ни одного неотмеченного занятия.
                                    Система покажет зелёную плашку
                                    «Все занятия отмечены 🎉».
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     16. КАБИНЕТ СТУДЕНТА
                     ================================================== -->
                <section class="docs-section" id="student">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--teal"><i class="bi bi-mortarboard-fill"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 16</div>
                                <h2 class="docs-card-title">Кабинет студента</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                <a href="student.php">student.php</a> — публичная
                                страница для студентов. Доступна <strong>без
                                входа</strong> — достаточно ввести номер зачётки
                                или логин.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-search"></i> Как найти себя</h4>
                            <ol class="docs-steps">
                                <li>Откройте <a href="student.php">student.php</a>.</li>
                                <li>Введите <strong>номер зачётки</strong>
                                    (например, <code>23Т013</code>)
                                    или <strong>логин</strong> (как
                                    на <code>portal.kuzstu.ru</code>).</li>
                                <li>Нажмите «Найти».</li>
                            </ol>

                            <h4 class="docs-h"><i class="bi bi-person-badge"></i> Что увидит студент</h4>
                            <ul class="docs-list">
                                <li><strong>Карточка студента:</strong> ФИО,
                                    группа, зачётка, институт;</li>
                                <li><strong>4 карточки статистики:</strong>
                                    занятий всего, посещено, пропущено,
                                    сдано работ;</li>
                                <li><strong>Круговой индикатор</strong>
                                    посещаемости — с цветом
                                    (зелёный / оранжевый / красный);</li>
                                <li><strong>Разбивка по предметам:</strong>
                                    <ul class="docs-list" style="margin-top:6px;">
                                        <li>название предмета;</li>
                                        <li>число занятий, был, пропустил, работ;</li>
                                        <li>процент посещаемости;</li>
                                        <li>при клике — раскрывается
                                            таблица всех занятий:
                                            дата, время, тип, преподаватель,
                                            отметка, сданные работы.</li>
                                    </ul>
                                </li>
                            </ul>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-info-circle-fill"></i>
                                <div>
                                    <strong>Сортировка.</strong>
                                    Предметы отсортированы
                                    <strong>по возрастанию посещаемости</strong> —
                                    самые проблемные сверху. Это удобно
                                    для самопроверки перед сессией.
                                </div>
                            </div>

                            <div class="docs-info docs-info--warn">
                                <i class="bi bi-shield-lock-fill"></i>
                                <div>
                                    <strong>Только чтение.</strong>
                                    Студент видит <strong>только свои</strong>
                                    данные и не может ничего изменить.
                                    Это безопасный способ
                                    для самопроверки.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     17. БОТ И АЛИСА
                     ================================================== -->
                <section class="docs-section" id="bot">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--purple"><i class="bi bi-robot"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 17</div>
                                <h2 class="docs-card-title">Бот и Алиса</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">

                            <p>
                                Расписание доступно не только на сайте,
                                но и в мессенджере MAX,
                                и через голосового ассистента Яндекс.Алиса.
                            </p>

                            <h4 class="docs-h"><i class="bi bi-send-fill"></i> Бот «КузГТУшник» в MAX</h4>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                                <a href="https://max.ru/se14203391_bot" target="_blank"
                                   rel="noopener" class="btn btn-primary btn-sm">
                                    <i class="bi bi-send"></i> Открыть в MAX
                                </a>
                                <code class="text-muted small">https://max.ru/se14203391_bot</code>
                            </div>

                            <p>Что умеет бот:</p>
                            <ul class="docs-list">
                                <li><strong>Расписание на сегодня и завтра</strong> —
                                    все пары с временем, аудиторией
                                    и подгруппой;</li>
                                <li><strong>Расписание на неделю</strong> —
                                    сводка по дням;</li>
                                <li><strong>Поиск по преподавателю, группе,
                                    аудитории</strong>;</li>
                                <li><strong>Подписки на уведомления:</strong>
                                    следующая пара, погода в 7:40,
                                    расписание на завтра в 20:00;</li>
                                <li><strong>Роли:</strong> студент / преподаватель / гость —
                                    от роли зависит набор команд;</li>
                                <li><strong>Привязка к Алисе:</strong> бот
                                    выдаёт 6-значный код для связки;</li>
                                <li><strong>Удаление данных</strong> —
                                    по запросу.</li>
                            </ul>

                            <h4 class="docs-h"><i class="bi bi-mic-fill"></i> Навык для Яндекс.Алисы</h4>
                            <p>Голосовой доступ к расписанию. Сценарии:</p>
                            <div class="docs-table-wrap">
                                <table class="docs-table">
                                    <thead>
                                        <tr>
                                            <th>Что сказать</th>
                                            <th>Что произойдёт</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>«Зарегистрироваться»</td>
                                            <td>Алиса спросит роль
                                                (студент / преподаватель),
                                                затем группу или фамилию.</td>
                                        </tr>
                                        <tr>
                                            <td>«У меня есть код»</td>
                                            <td>Запрос 6-значного кода
                                                из MAX-бота для привязки.</td>
                                        </tr>
                                        <tr>
                                            <td>«Расписание на сегодня»</td>
                                            <td>Озвучит все пары на сегодня.</td>
                                        </tr>
                                        <tr>
                                            <td>«Расписание на завтра»</td>
                                            <td>Озвучит все пары на завтра.</td>
                                        </tr>
                                        <tr>
                                            <td>«Сменить пользователя»</td>
                                            <td>Сбросит текущую привязку,
                                                начнёт заново.</td>
                                        </tr>
                                        <tr>
                                            <td>«Удали мои данные»</td>
                                            <td>Спросит подтверждение,
                                                затем удалит аккаунт из Алисы
                                                и снимет привязку в MAX.</td>
                                        </tr>
                                        <tr>
                                            <td>«Помощь»</td>
                                            <td>Перечислит все команды.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="docs-info docs-info--info">
                                <i class="bi bi-link-45deg"></i>
                                <div>
                                    <strong>Единая привязка.</strong>
                                    Код из MAX-бота можно использовать
                                    один раз и он действует 15 минут.
                                    После привязки Алиса знает ваш профиль
                                    и подтягивает то же расписание,
                                    что и сайт.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     18. FAQ
                     ================================================== -->
                <section class="docs-section" id="faq">
                    <div class="docs-card">
                        <div class="docs-card-header">
                            <div class="docs-card-icon docs-card-icon--pink"><i class="bi bi-question-circle"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="docs-card-num">Раздел 18</div>
                                <h2 class="docs-card-title">FAQ — частые вопросы</h2>
                            </div>
                        </div>
                        <div class="docs-card-body">
                            <div class="docs-accordion" id="docsFaq">

                                <div class="docs-acc-item" data-open="0">
                                    <button class="docs-acc-btn" onclick="toggleFaq(this)">
                                        <i class="bi bi-calendar-x" style="color:#0071e3;"></i>
                                        Расписание не загружается / пустое
                                        <i class="bi bi-chevron-down docs-acc-arrow"></i>
                                    </button>
                                    <div class="docs-acc-body"><div class="docs-acc-body-inner">
                                        <ol class="docs-steps">
                                            <li>Проверьте, что портал
                                                <a href="https://portal.kuzstu.ru" target="_blank">portal.kuzstu.ru</a>
                                                открывается в браузере.</li>
                                            <li>Нажмите «Синхронизировать с API»
                                                на странице <a href="schedule.php">Расписание</a>.</li>
                                            <li>Если появилось сообщение
                                                об ошибке — обратитесь
                                                к администратору. Возможно,
                                                ваш <code>api_teacher_id</code>
                                                не совпадает с порталом.</li>
                                            <li>Проверьте, что у вас
                                                выбран правильный семестр
                                                (для <a href="session.php">сессии</a>).</li>
                                        </ol>
                                    </div></div>
                                </div>

                                <div class="docs-acc-item" data-open="0">
                                    <button class="docs-acc-btn" onclick="toggleFaq(this)">
                                        <i class="bi bi-people" style="color:#0071e3;"></i>
                                        Студенты не отображаются на занятии
                                        <i class="bi bi-chevron-down docs-acc-arrow"></i>
                                    </button>
                                    <div class="docs-acc-body"><div class="docs-acc-body-inner">
                                        <p>Причины и решения:</p>
                                        <ul class="docs-list">
                                            <li><strong>Группа не заполнена в БД</strong> —
                                                импортируйте студентов через
                                                <a href="upload_students.php">Импорт</a>.</li>
                                            <li><strong>Занятие не привязано к группе</strong> —
                                                откройте <a href="schedule.php">Расписание</a>,
                                                кликните по занятию и проверьте
                                                поле «Группа».</li>
                                            <li><strong>Не прошла синхронизация</strong> —
                                                нажмите «Обновить» на дашборде
                                                или «Синхронизировать» в расписании.</li>
                                        </ul>
                                    </div></div>
                                </div>

                                <div class="docs-acc-item" data-open="0">
                                    <button class="docs-acc-btn" onclick="toggleFaq(this)">
                                        <i class="bi bi-exclamation-triangle" style="color:#0071e3;"></i>
                                        После сохранения посещаемости все отмечены как «были»
                                        <i class="bi bi-chevron-down docs-acc-arrow"></i>
                                    </button>
                                    <div class="docs-acc-body"><div class="docs-acc-body-inner">
                                        <p>
                                            Эта проблема была в старой версии.
                                            Убедитесь, что у вас свежий
                                            <code>attendance.php</code> с PRG-паттерном
                                            (после сохранения — редирект
                                            с параметром <code>?lesson_id=X</code>).
                                        </p>
                                        <p class="mb-0">
                                            Если не помогает — сообщите администратору.
                                            Нужно проверить, что форма отправляет
                                            <code>name="present[ID]"</code>
                                            и сохранение идёт с проверкой
                                            <code>$val === '0' || $val === '1'</code>.
                                        </p>
                                    </div></div>
                                </div>

                                <div class="docs-acc-item" data-open="0">
                                    <button class="docs-acc-btn" onclick="toggleFaq(this)">
                                        <i class="bi bi-file-earmark-excel" style="color:#0071e3;"></i>
                                        Импорт CSV «пропустил всех» / «не распознано»
                                        <i class="bi bi-chevron-down docs-acc-arrow"></i>
                                    </button>
                                    <div class="docs-acc-body"><div class="docs-acc-body-inner">
                                        <ul class="docs-list">
                                            <li><strong>Кодировка</strong> —
                                                сохраняйте файл в <em>CSV UTF-8</em>;</li>
                                            <li><strong>Разделитель</strong> —
                                                запятые (хотя система понимает
                                                и точку с запятой, и табуляцию);</li>
                                            <li><strong>Заголовок</strong> —
                                                первая строка может быть с шапкой
                                                или сразу с данными, система
                                                определяет автоматически;</li>
                                            <li><strong>Столбцы</strong> —
                                                должны идти строго
                                                в порядке A→K;</li>
                                            <li><strong>Статус</strong> —
                                                импортируются только
                                                строки со статусом «Обучается».</li>
                                        </ul>
                                    </div></div>
                                </div>

                                <div class="docs-acc-item" data-open="0">
                                    <button class="docs-acc-btn" onclick="toggleFaq(this)">
                                        <i class="bi bi-key" style="color:#0071e3;"></i>
                                        Забыл пароль / нет доступа
                                        <i class="bi bi-chevron-down docs-acc-arrow"></i>
                                    </button>
                                    <div class="docs-acc-body"><div class="docs-acc-body-inner">
                                        <ol class="docs-steps">
                                            <li>Откройте
                                                <a href="forgot_password.php">«Забыли пароль?»</a>.</li>
                                            <li>Введите логин, email
                                                и новый пароль.</li>
                                            <li>Дождитесь, пока администратор
                                                одобрит запрос —
                                                он пришлёт ссылку на email.</li>
                                            <li>Перейдите по ссылке —
                                                пароль сменится автоматически.</li>
                                        </ol>
                                        <p class="mb-0">
                                            Если email не указан —
                                            сначала зайдите под старым паролем
                                            и заполните его на странице
                                            <a href="fill_email.php">fill_email.php</a>.
                                        </p>
                                    </div></div>
                                </div>

                                <div class="docs-acc-item" data-open="0">
                                    <button class="docs-acc-btn" onclick="toggleFaq(this)">
                                        <i class="bi bi-diagram-3" style="color:#0071e3;"></i>
                                        Я в другой подгруппе — что делать?
                                        <i class="bi bi-chevron-down docs-acc-arrow"></i>
                                    </button>
                                    <div class="docs-acc-body"><div class="docs-acc-body-inner">
                                        <p>
                                            Если у вас лабораторные по подгруппам,
                                            и вы в подгруппе 2, а пара у подгруппы 1 —
                                            преподаватель <strong>не должен вас
                                            отмечать</strong>. Вы будете
                                            «не отмечены» — это
                                            <strong>не считается пропуском</strong>.
                                        </p>
                                        <p class="mb-0">
                                            На странице посещаемости
                                            используйте вкладки подгрупп:
                                            «Все / Подгруппа 1 / Подгруппа 2».
                                            В матрице отчёта у вас будет
                                            «·» вместо «✗».
                                        </p>
                                    </div></div>
                                </div>

                                <div class="docs-acc-item" data-open="0">
                                    <button class="docs-acc-btn" onclick="toggleFaq(this)">
                                        <i class="bi bi-clock-history" style="color:#0071e3;"></i>
                                        Как отметка попадает в журнал?
                                        <i class="bi bi-chevron-down docs-acc-arrow"></i>
                                    </button>
                                    <div class="docs-acc-body"><div class="docs-acc-body-inner">
                                        <p>
                                            Каждое сохранение посещаемости,
                                            работы, оценки или допуска
                                            записывается в
                                            <a href="audit.php">журнал действий</a>
                                            одной строкой:
                                        </p>
                                        <ul class="docs-list">
                                            <li>кто (ФИО и IP);</li>
                                            <li>что (тип действия и описание);</li>
                                            <li>когда (дата и время);</li>
                                            <li>старое и новое значение.</li>
                                        </ul>
                                        <p class="mb-0">
                                            Это защищает и преподавателя,
                                            и студента от спорных ситуаций.
                                        </p>
                                    </div></div>
                                </div>

                                <div class="docs-acc-item" data-open="0">
                                    <button class="docs-acc-btn" onclick="toggleFaq(this)">
                                        <i class="bi bi-mortarboard" style="color:#0071e3;"></i>
                                        Как работает автодопуск в ведомости?
                                        <i class="bi bi-chevron-down docs-acc-arrow"></i>
                                    </button>
                                    <div class="docs-acc-body"><div class="docs-acc-body-inner">
                                        <p>
                                            Система считает два процента
                                            по всем занятиям предмета
                                            за семестр:
                                        </p>
                                        <ul class="docs-list">
                                            <li><strong>Посещаемость</strong> —
                                                доля отмеченных «был»
                                                от общего числа отмеченных
                                                (был + пропустил);</li>
                                            <li><strong>Сданные работы</strong> —
                                                доля сданных работ
                                                от общего числа работ
                                                по предмету.</li>
                                        </ul>
                                        <p>
                                            Если оба процента больше или равны
                                            вашим порогам — студент <strong>допущен</strong>.
                                            Если хотя бы один ниже —
                                            <strong>не допущен</strong>.
                                        </p>
                                        <p class="mb-0">
                                            Пороги задаются при создании
                                            ведомости и могут быть
                                            переопределены вручную для
                                            каждого студента.
                                        </p>
                                    </div></div>
                                </div>

                                <div class="docs-acc-item" data-open="0">
                                    <button class="docs-acc-btn" onclick="toggleFaq(this)">
                                        <i class="bi bi-lock" style="color:#0071e3;"></i>
                                        Что значит «Закрытая ведомость»?
                                        <i class="bi bi-chevron-down docs-acc-arrow"></i>
                                    </button>
                                    <div class="docs-acc-body"><div class="docs-acc-body-inner">
                                        <p>
                                            Закрытая ведомость — это
                                            защита от случайных изменений
                                            после сдачи. В закрытой ведомости:
                                        </p>
                                        <ul class="docs-list">
                                            <li>нельзя менять оценки;</li>
                                            <li>нельзя менять допуск;</li>
                                            <li>нельзя менять комментарии;</li>
                                            <li>нельзя выполнять массовые действия.</li>
                                        </ul>
                                        <p class="mb-0">
                                            Кнопка <strong>«Открыть заново»</strong>
                                            снимает блокировку.
                                            Печать при этом доступна всегда.
                                        </p>
                                    </div></div>
                                </div>

                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==================================================
                     ФУТЕР
                     ================================================== -->
                <div class="docs-footer">
                    <p class="mb-3">
                        Не нашли ответ? Обратитесь к администратору
                        системы или напишите в бот «КузГТУшник».
                    </p>
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="https://max.ru/se14203391_bot" target="_blank"
                           rel="noopener" class="btn btn-primary btn-sm">
                            <i class="bi bi-send"></i> Написать в бот
                        </a>
                        <a href="presentation.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-stars"></i> О системе
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-speedometer2"></i> На дашборд
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
/* ============================================================
   ПРОГРЕСС ЧТЕНИЯ
   ============================================================ */
(function() {
    const bar = document.getElementById('docsProgress');
    window.addEventListener('scroll', () => {
        const h = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.width = (window.scrollY / h * 100) + '%';
    }, { passive: true });
})();

/* ============================================================
   FAQ — АККОРДЕОН
   ============================================================ */
function toggleFaq(btn) {
    const item = btn.closest('.docs-acc-item');
    const isOpen = item.dataset.open === '1';
    item.dataset.open = isOpen ? '0' : '1';
}

/* ============================================================
   АКТИВНЫЙ ПУНКТ МЕНЮ
   ============================================================ */
(function() {
    const links = document.querySelectorAll('.docs-nav-link[href^="#"]');
    const sections = Array.from(document.querySelectorAll('.docs-section'));

    if (!('IntersectionObserver' in window)) return;

    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                const id = e.target.id;
                links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + id));
            }
        });
    }, {
        rootMargin: '-120px 0px -60% 0px',
        threshold: 0
    });

    sections.forEach(s => obs.observe(s));
})();

/* ============================================================
   ПЛАВНЫЙ СКРОЛЛ К ЯКОРЯМ
   ============================================================ */
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href === '#') return;
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>