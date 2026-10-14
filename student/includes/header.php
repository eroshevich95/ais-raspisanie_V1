<?php
/**
 * Шапка кабинета студента.
 * Ожидает $pageTitle и $student (из init.php).
 */
if (!isset($student)) {
    require_once __DIR__ . '/init.php';
}

$currentFile = basename($_SERVER['PHP_SELF']);
$navItems = [
    'index.php'    => ['Главная',     'bi-house-door'],
    'schedule.php' => ['Расписание',  'bi-calendar-week'],
    'grades.php'   => ['Мои оценки',  'bi-journal-check'],
];

$nameParts = explode(' ', $student['full_name']);
$initials = '';
if (!empty($nameParts[0])) $initials .= mb_strtoupper(mb_substr($nameParts[0], 0, 1));
if (!empty($nameParts[1])) $initials .= mb_strtoupper(mb_substr($nameParts[1], 0, 1));

$namePartsShort = $nameParts[0] ?? '';
if (isset($nameParts[1])) $namePartsShort .= ' ' . mb_substr($nameParts[1], 0, 1) . '.';
if (isset($nameParts[2])) $namePartsShort .= mb_substr($nameParts[2], 0, 1) . '.';
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Кабинет студента') ?> — АИС-расписание.РФ</title>
    <link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg?v=2">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/student/assets/student.css" rel="stylesheet">
    <script>
        (function() {
            try {
                var t = localStorage.getItem('ap-theme');
                if (t === 'dark' || t === 'light') {
                    document.documentElement.setAttribute('data-theme', t);
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- ============================================================
         Стили конфетти-модалки достижений
         ============================================================ -->
    <style>
    .ach-celebrate-overlay {
        position: fixed;
        inset: 0;
        z-index: 100000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        pointer-events: none;
    }
    .ach-celebrate-overlay.show {
        display: flex;
        pointer-events: auto;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(8px);
        animation: achFadeIn 0.4s;
    }
    @keyframes achFadeIn { from { opacity: 0; } to { opacity: 1; } }

    .ach-celebrate-modal {
        background: #fff;
        border-radius: 24px;
        padding: 36px 32px;
        max-width: 400px;
        width: 100%;
        text-align: center;
        box-shadow: 0 40px 100px rgba(0,0,0,0.5);
        animation: achModalIn 0.5s cubic-bezier(0.16,1,0.3,1);
        position: relative;
        z-index: 2;
    }
    @keyframes achModalIn {
        from { opacity: 0; transform: scale(0.7) translateY(30px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    .ach-celebrate-icon {
        width: 96px;
        height: 96px;
        margin: 0 auto 20px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        background: linear-gradient(135deg, #ffd60a, #ff9f0a);
        color: #fff;
        box-shadow: 0 20px 40px rgba(255,159,10,0.4);
        animation: achIconBounce 1s cubic-bezier(0.16,1,0.3,1) infinite;
        position: relative;
    }
    .ach-celebrate-icon::after {
        content: '';
        position: absolute;
        inset: -10px;
        border-radius: 28px;
        border: 3px solid rgba(255,159,10,0.5);
        animation: achRing 2s ease-out infinite;
    }
    @keyframes achIconBounce {
        0%,100% { transform: scale(1) rotate(-3deg); }
        50%     { transform: scale(1.08) rotate(3deg); }
    }
    @keyframes achRing {
        0%   { transform: scale(1); opacity: 1; }
        100% { transform: scale(1.4); opacity: 0; }
    }

    .ach-celebrate-eyebrow {
        font-size: 0.75rem;
        font-weight: 800;
        color: #ff9f0a;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        margin-bottom: 8px;
    }
    .ach-celebrate-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1d1d1f;
        margin-bottom: 8px;
        line-height: 1.2;
    }
    .ach-celebrate-desc {
        font-size: 0.92rem;
        color: #86868b;
        line-height: 1.5;
        margin-bottom: 20px;
    }
    .ach-celebrate-points {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 20px;
        background: linear-gradient(135deg, #ff9f0a, #ff453a);
        color: #fff;
        font-size: 1rem;
        font-weight: 800;
        margin-bottom: 24px;
    }
    .ach-celebrate-btn {
        display: inline-block;
        padding: 14px 32px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0071e3, #5e5ce6);
        color: #fff;
        border: none;
        font-family: inherit;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 10px 24px rgba(0,113,227,0.4);
        transition: all 0.2s;
    }
    .ach-celebrate-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(0,113,227,0.5);
    }

    /* Конфетти */
    .confetti-piece {
        position: fixed;
        top: -20px;
        width: 10px;
        height: 10px;
        opacity: 0;
        z-index: 100001;
        pointer-events: none;
        animation: confettiFall 3s linear forwards;
    }
    @keyframes confettiFall {
        0% {
            opacity: 1;
            transform: translateY(0) rotate(0deg);
        }
        100% {
            opacity: 0;
            transform: translateY(100vh) rotate(720deg);
        }
    }

    @media (max-width: 480px) {
        .ach-celebrate-modal { padding: 28px 22px; }
        .ach-celebrate-title { font-size: 1.25rem; }
        .ach-celebrate-icon { width: 80px; height: 80px; font-size: 2.5rem; }
    }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center" href="/student/index.php">
            <img src="/assets/img/logo.svg" alt="" style="width:32px;height:32px;margin-right:8px;">
            <span class="d-none d-sm-inline">Кабинет студента</span>
        </a>

        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($navItems as $file => $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentFile === $file ? 'active' : '' ?>"
                           href="/student/<?= $file ?>">
                            <i class="bi <?= $item[1] ?>"></i>
                            <span class="d-lg-none ms-1"><?= $item[0] ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item me-lg-2 mb-2 mb-lg-0">
                    <button type="button" class="theme-toggle-btn" id="themeToggle">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
                       data-bs-toggle="dropdown">
                        <div class="avatar-circle me-2"><?= htmlspecialchars($initials ?: 'С') ?></div>
                        <div class="d-none d-lg-block text-start lh-1">
                            <div class="small fw-bold"><?= htmlspecialchars($namePartsShort) ?></div>
                            <div class="small opacity-75" style="font-size:0.7rem;">
                                <?= htmlspecialchars($student['group_name']) ?>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="user-card-head">
                            <div class="uname"><?= htmlspecialchars($student['full_name']) ?></div>
                            <div class="udept">
                                <i class="bi bi-people-fill"></i>
                                <?= htmlspecialchars($student['group_name']) ?>
                                <?php if (!empty($student['zachetka'])): ?>
                                    · <?= htmlspecialchars($student['zachetka']) ?>
                                <?php endif; ?>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Выйти
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">

<script>
document.addEventListener('DOMContentLoaded', function () {
    const icon = document.getElementById('themeIcon');
    const updateIcon = () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (icon) icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    };
    updateIcon();
    const tBtn = document.getElementById('themeToggle');
    if (tBtn) {
        tBtn.addEventListener('click', () => {
            const cur = document.documentElement.getAttribute('data-theme') || 'light';
            const next = cur === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('ap-theme', next); } catch (e) {}
            updateIcon();
        });
    }
});
</script>