<?php
require_once 'config/database.php';

$zachetka = trim($_GET['zachetka'] ?? '');
$student = null;
$subjects = [];
$globalStats = null;

if ($zachetka !== '') {
    // Ищем студента по зачётке или логину
    $stmt = $pdo->prepare("
        SELECT s.*, g.name AS group_name
        FROM students s
        LEFT JOIN groups_table g ON s.group_id = g.id
        WHERE s.zachetka = ? OR s.login = ?
        LIMIT 1
    ");
    $stmt->execute([$zachetka, $zachetka]);
    $student = $stmt->fetch();

    if ($student) {
        // === Все занятия группы студента ===
        $stmt = $pdo->prepare("
            SELECT l.id, l.lesson_date, l.lesson_time, l.lesson_number,
                   l.discipline, l.lesson_type, l.subgroup,
                   u.full_name AS teacher_name,
                   a.is_present
            FROM lessons l
            LEFT JOIN users u ON l.user_id = u.id
            LEFT JOIN attendance a ON a.lesson_id = l.id AND a.student_id = ?
            WHERE l.group_id = ?
            ORDER BY l.lesson_date DESC, l.lesson_time DESC
        ");
        $stmt->execute([$student['id'], $student['group_id']]);
        $lessons = $stmt->fetchAll();

        // === Работы студента ===
        $stmt = $pdo->prepare("
            SELECT lesson_id, work_number, marked_at
            FROM submitted_works
            WHERE student_id = ?
            ORDER BY work_number
        ");
        $stmt->execute([$student['id']]);
        $worksByLesson = [];
        foreach ($stmt->fetchAll() as $w) {
            $worksByLesson[$w['lesson_id']][] = $w;
        }

        // === Группировка по предметам ===
        foreach ($lessons as $l) {
            $subject = $l['discipline'];
            if (!isset($subjects[$subject])) {
                $subjects[$subject] = [
                    'name' => $subject,
                    'lessons' => [],
                    'present' => 0,
                    'absent' => 0,
                    'unmarked' => 0,
                    'works' => [],
                ];
            }
            $l['works'] = $worksByLesson[$l['id']] ?? [];
            $subjects[$subject]['lessons'][] = $l;

            if ($l['is_present'] === null) {
                $subjects[$subject]['unmarked']++;
            } elseif ((int)$l['is_present'] === 1) {
                $subjects[$subject]['present']++;
            } else {
                $subjects[$subject]['absent']++;
            }

            foreach ($l['works'] as $w) {
                $subjects[$subject]['works'][] = $w;
            }
        }

        // === Подсчёт % и сортировка ===
        $totalPresent = 0; $totalAbsent = 0; $totalWorks = 0;

        foreach ($subjects as $name => &$s) {
            $marked = $s['present'] + $s['absent'];
            $s['pct'] = $marked > 0 ? round($s['present'] / $marked * 100) : null;
            $s['marked'] = $marked;
            $s['total'] = count($s['lessons']);
            $s['works_count'] = count($s['works']);

            $totalPresent += $s['present'];
            $totalAbsent  += $s['absent'];
            $totalWorks   += $s['works_count'];
        }
        unset($s);

        // Сортировка: сначала с меньшей посещаемостью (проблемные)
        uasort($subjects, function($a, $b) {
            if ($a['pct'] === null) return 1;
            if ($b['pct'] === null) return -1;
            return $a['pct'] <=> $b['pct'];
        });

        $totalMarked = $totalPresent + $totalAbsent;
        $globalStats = [
            'total_lessons' => count($lessons),
            'present' => $totalPresent,
            'absent' => $totalAbsent,
            'works' => $totalWorks,
            'pct' => $totalMarked > 0 ? round($totalPresent / $totalMarked * 100) : null,
            'subjects_count' => count($subjects),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $student ? htmlspecialchars($student['full_name']) . ' — ' : '' ?>Моя посещаемость</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --ap-bg: #ffffff;
    --ap-bg-alt: #fbfbfd;
    --ap-bg-soft: #f5f5f7;
    --ap-dark: #1d1d1f;
    --ap-gray: #86868b;
    --ap-border: #eaeaea;
    --ap-accent: #0071e3;
    --ap-accent-hover: #0077ed;
    --ap-green: #30d158;
    --ap-red: #ff453a;
    --ap-orange: #ff9f0a;
    --ap-purple: #bf5af2;
    --ap-glow-1: rgba(0,113,227,0.08);
    --ap-glow-2: rgba(191,90,242,0.08);
}

html, body {
    margin: 0; padding: 0;
    background: var(--ap-bg);
    color: var(--ap-dark);
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, sans-serif;
    overflow-x: hidden;
}
* { box-sizing: border-box; }
a { color: var(--ap-accent); }

/* Переключатель темы */
.theme-toggle {
    position: fixed;
    top: 24px; right: 24px;
    z-index: 9998;
    width: 46px; height: 46px;
    border-radius: 50%;
    background: var(--ap-bg);
    border: 1px solid var(--ap-border);
    color: var(--ap-dark);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
}
.theme-toggle:hover {
    transform: scale(1.1) rotate(15deg);
    border-color: var(--ap-accent);
    color: var(--ap-accent);
}
@media (max-width: 767px) { .theme-toggle { top: 16px; right: 16px; width: 40px; height: 40px; } }

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

/* ============================================================
   HERO + ПОИСК
   ============================================================ */
.stu-hero {
    padding: clamp(80px, 12vw, 140px) 24px 60px;
    text-align: center;
    background: linear-gradient(180deg, var(--ap-bg) 0%, var(--ap-bg-soft) 100%);
    position: relative;
    overflow: hidden;
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.stu-hero::before {
    content: '';
    position: absolute; inset: -50%;
    background:
        radial-gradient(circle at 20% 30%, var(--ap-glow-1) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, var(--ap-glow-2) 0%, transparent 40%);
    animation: auroraMove 20s ease-in-out infinite;
    pointer-events: none;
}
@keyframes auroraMove {
    0%,100% { transform: translate(0,0) scale(1); }
    33% { transform: translate(5%,-5%) scale(1.1); }
    66% { transform: translate(-5%,5%) scale(0.95); }
}
.stu-hero-content { position: relative; z-index: 1; max-width: 720px; }

.stu-logo {
    width: 110px;
    height: 110px;
    margin: 0 auto 32px;
    animation: logoFloat 5s ease-in-out infinite;
    filter: drop-shadow(0 20px 40px rgba(0,113,227,0.25));
}
.stu-logo img { width: 100%; height: 100%; object-fit: contain; }
@keyframes logoFloat {
    0%, 100% { transform: translateY(0) scale(1); }
    50%      { transform: translateY(-12px) scale(1.02); }
}

.stu-h1 {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.03em;
    margin: 0 0 16px;
    background: linear-gradient(135deg, #1d1d1f 0%, #4a4a4c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.stu-h1 .accent {
    background: linear-gradient(120deg, #0071e3 0%, #6610f2 50%, #bf5af2 100%);
    background-size: 200% 200%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: gradientShift 6s ease-in-out infinite;
}
@keyframes gradientShift {
    0%,100% { background-position: 0% 50%; }
    50%     { background-position: 100% 50%; }
}

.stu-subtitle {
    font-size: clamp(1rem, 1.6vw, 1.2rem);
    color: var(--ap-gray);
    margin: 0 auto 40px;
    max-width: 560px;
    line-height: 1.5;
}

.stu-search {
    display: flex;
    gap: 12px;
    max-width: 560px;
    margin: 0 auto;
    background: #fff;
    padding: 8px;
    border-radius: 980px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1), 0 0 0 1px rgba(0,0,0,0.04);
    transition: box-shadow 0.3s;
}
.stu-search:focus-within {
    box-shadow: 0 20px 60px rgba(0,113,227,0.25), 0 0 0 4px rgba(0,113,227,0.15);
}
.stu-search input {
    flex: 1;
    border: none;
    outline: none;
    padding: 14px 24px;
    font-size: 1.05rem;
    font-family: inherit;
    color: var(--ap-dark);
    background: transparent;
    border-radius: 980px;
}
.stu-search input::placeholder { color: var(--ap-gray); }
.stu-search button {
    padding: 14px 32px;
    border: none;
    border-radius: 980px;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    font-weight: 600;
    font-size: 1rem;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.stu-search button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,113,227,0.4);
}
.stu-search button:disabled { opacity: 0.7; cursor: wait; }

.stu-hint {
    font-size: 0.82rem;
    color: var(--ap-gray);
    margin-top: 16px;
}
.stu-hint code {
    background: rgba(0,113,227,0.08);
    color: var(--ap-accent);
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.85em;
}

/* Ошибка */
.stu-error {
    max-width: 560px;
    margin: 24px auto 0;
    background: rgba(255,69,58,0.08);
    border: 1px solid rgba(255,69,58,0.25);
    color: #842029;
    padding: 16px 20px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.9rem;
    animation: shakeIn 0.4s ease;
}
@keyframes shakeIn {
    0% { transform: translateX(-10px); }
    25% { transform: translateX(10px); }
    50% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
    100% { transform: translateX(0); }
}

/* ============================================================
   КАРТОЧКА СТУДЕНТА
   ============================================================ */
.stu-profile {
    padding: 60px 24px 0;
    max-width: 1120px;
    margin: 0 auto;
}
.stu-card {
    background: linear-gradient(135deg, #0A84FF 0%, #5E5CE6 100%);
    color: #fff;
    border-radius: 28px;
    padding: 40px;
    box-shadow: 0 30px 80px rgba(0,113,227,0.25);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 32px;
    flex-wrap: wrap;
}
.stu-card::before {
    content: '';
    position: absolute;
    top: -50%; right: -10%;
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
    border-radius: 50%;
    pointer-events: none;
}
.stu-card::after {
    content: '';
    position: absolute;
    bottom: -60%; left: -5%;
    width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 60%);
    border-radius: 50%;
    pointer-events: none;
}
.stu-avatar {
    width: 100px;
    height: 100px;
    border-radius: 24px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.6rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}
.stu-info { flex: 1; min-width: 220px; position: relative; z-index: 1; }
.stu-name {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 8px;
    line-height: 1.2;
}
.stu-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    font-size: 0.95rem;
    opacity: 0.9;
}
.stu-meta span { display: inline-flex; align-items: center; gap: 6px; }

/* Статистика */
.stu-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-top: 32px;
}
@media (min-width: 768px) {
    .stu-stats { grid-template-columns: repeat(4, 1fr); }
}
.stu-stat {
    background: #fff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid var(--ap-border);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}
.stu-stat:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}
.stu-stat::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: var(--stat-color, var(--ap-accent));
}
.stu-stat-value {
    font-size: 2.2rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.02em;
    color: var(--ap-dark);
    margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
}
.stu-stat-label {
    font-size: 0.82rem;
    color: var(--ap-gray);
    font-weight: 500;
}

/* Круговой индикатор */
.stu-ring-wrap {
    display: flex;
    justify-content: center;
    margin-top: 32px;
}
.stu-ring {
    position: relative;
    width: 160px;
    height: 160px;
}
.stu-ring svg { transform: rotate(-90deg); width: 100%; height: 100%; }
.stu-ring-bg { stroke: rgba(0,0,0,0.06); }
.stu-ring-fg {
    stroke-linecap: round;
    transition: stroke-dashoffset 1.5s cubic-bezier(0.16,1,0.3,1);
    filter: drop-shadow(0 4px 8px rgba(0,113,227,0.3));
}
.stu-ring-text {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    text-align: center;
}
.stu-ring-pct {
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1;
    color: var(--ap-dark);
}
.stu-ring-sub {
    font-size: 0.7rem;
    color: var(--ap-gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

/* ============================================================
   ПРЕДМЕТЫ
   ============================================================ */
.stu-section {
    padding: 60px 24px 80px;
    max-width: 1120px;
    margin: 0 auto;
}
.stu-section-title {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 32px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.stu-section-title i { color: var(--ap-accent); }

/* Карточка предмета */
.stu-subject {
    background: #fff;
    border-radius: 24px;
    border: 1px solid var(--ap-border);
    margin-bottom: 16px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}
.stu-subject:hover {
    box-shadow: 0 20px 50px rgba(0,0,0,0.1);
    border-color: rgba(0,113,227,0.3);
}
.stu-subject-header {
    padding: 24px 28px;
    display: flex;
    align-items: center;
    gap: 20px;
    cursor: pointer;
    user-select: none;
    position: relative;
}
.stu-subject-header::before {
    content: '';
    position: absolute;
    left: 0; top: 20%;
    bottom: 20%;
    width: 4px;
    border-radius: 4px;
    background: var(--subject-color, var(--ap-accent));
    transition: all 0.3s;
}
.stu-subject[data-open="1"] .stu-subject-header::before {
    top: 0; bottom: 0;
}

.stu-subject-info { flex: 1; min-width: 0; }
.stu-subject-name {
    font-size: 1.15rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    margin: 0 0 6px;
    color: var(--ap-dark);
    line-height: 1.3;
}
.stu-subject-meta {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 0.82rem;
    color: var(--ap-gray);
}
.stu-subject-meta span { display: inline-flex; align-items: center; gap: 4px; }

.stu-subject-pct {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    flex-shrink: 0;
    font-variant-numeric: tabular-nums;
}
.stu-subject-pct.pct-good    { color: var(--ap-green); }
.stu-subject-pct.pct-mid     { color: var(--ap-orange); }
.stu-subject-pct.pct-bad     { color: var(--ap-red); }
.stu-subject-pct.pct-none    { color: var(--ap-gray); }

.stu-subject-arrow {
    flex-shrink: 0;
    color: var(--ap-gray);
    font-size: 1.2rem;
    transition: transform 0.3s;
}
.stu-subject[data-open="1"] .stu-subject-arrow {
    transform: rotate(180deg);
    color: var(--ap-accent);
}

/* Прогресс-бар */
.stu-subject-bar {
    height: 4px;
    background: rgba(0,0,0,0.05);
    margin: 0 28px 0;
    border-radius: 2px;
    overflow: hidden;
}
.stu-subject-bar-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 1.2s cubic-bezier(0.16,1,0.3,1);
    width: 0;
}

/* Раскрытое содержимое */
.stu-subject-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.5s cubic-bezier(0.16,1,0.3,1);
}
.stu-subject[data-open="1"] .stu-subject-body {
    max-height: 5000px;
}
.stu-subject-body-inner {
    padding: 24px 28px 28px;
    border-top: 1px solid var(--ap-border);
}

/* Таблица занятий */
.stu-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}
.stu-table th {
    text-align: left;
    padding: 10px 12px;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--ap-gray);
    font-weight: 600;
    border-bottom: 1px solid var(--ap-border);
}
.stu-table td {
    padding: 12px;
    border-bottom: 1px solid rgba(0,0,0,0.04);
    color: var(--ap-dark);
    vertical-align: middle;
}
.stu-table tr:last-child td { border-bottom: none; }
.stu-table tr:hover { background: rgba(0,113,227,0.03); }

/* Бейджи присутствия */
.stu-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
}
.stu-badge--present { background: rgba(48,209,88,0.12); color: #198754; }
.stu-badge--absent  { background: rgba(255,69,58,0.12); color: #842029; }
.stu-badge--none    { background: rgba(134,134,139,0.12); color: #495057; }

/* Бейдж типа занятия */
.stu-type {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
}
.stu-type--lecture  { background: #cfe2ff; color: #084298; }
.stu-type--practice { background: #d1e7dd; color: #0f5132; }
.stu-type--lab      { background: #cff4fc; color: #055160; }
.stu-type--seminar  { background: #ffe5b4; color: #b25c00; }
.stu-type--other    { background: #e9ecef; color: #495057; }

/* Работы */
.stu-works {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.stu-work-tag {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    background: linear-gradient(135deg, #cfe2ff 0%, #d6d0ff 100%);
    color: #084298;
    white-space: nowrap;
}

/* Пусто */
.stu-empty {
    padding: 24px;
    text-align: center;
    color: var(--ap-gray);
    font-size: 0.9rem;
    font-style: italic;
}

/* Reveal */
.stu-reveal {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.7s cubic-bezier(0.16,1,0.3,1), transform 0.7s cubic-bezier(0.16,1,0.3,1);
}
.stu-reveal.is-visible { opacity: 1; transform: translateY(0); }

/* ============================================================
   ФУТЕР
   ============================================================ */
.stu-footer {
    text-align: center;
    padding: 40px 24px;
    color: var(--ap-gray);
    font-size: 0.85rem;
    border-top: 1px solid var(--ap-border);
}
.stu-footer a {
    color: var(--ap-accent);
    text-decoration: none;
    font-weight: 500;
}
.stu-footer a:hover { text-decoration: underline; }

/* Тёмная тема */
[data-theme="dark"] {
    --ap-bg: #000;
    --ap-bg-alt: #0a0a0c;
    --ap-bg-soft: #141416;
    --ap-dark: #f5f5f7;
    --ap-gray: #a1a1a6;
    --ap-border: #232325;
    --ap-glow-1: rgba(10,132,255,0.15);
    --ap-glow-2: rgba(191,90,242,0.15);
}
[data-theme="dark"] body { background: var(--ap-bg); color: var(--ap-dark); }
[data-theme="dark"] .stu-search { background: #1c1c1e; box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.06); }
[data-theme="dark"] .stu-search input { color: #fff; }
[data-theme="dark"] .stu-stat { background: #1c1c1e; border-color: #2c2c2e; }
[data-theme="dark"] .stu-stat-value { color: #fff; }
[data-theme="dark"] .stu-subject { background: #1c1c1e; border-color: #2c2c2e; }
[data-theme="dark"] .stu-subject-name { color: #fff; }
[data-theme="dark"] .stu-subject-body-inner { border-color: #2c2c2e; }
[data-theme="dark"] .stu-table td { color: #f5f5f7; border-color: rgba(255,255,255,0.05); }
[data-theme="dark"] .stu-table tr:hover { background: rgba(10,132,255,0.06); }
[data-theme="dark"] .stu-section-title { color: #f5f5f7; }
[data-theme="dark"] .stu-h1 { background: linear-gradient(135deg, #f5f5f7 0%, #a1a1a6 100%); -webkit-background-clip: text; background-clip: text; }

/* Печать */
@media print {
    .theme-toggle, .stu-search, .stu-hero::before { display: none !important; }
    .stu-subject-body { max-height: none !important; }
    .stu-card { box-shadow: none; }
}
</style>
</head>
<body>

<div class="scroll-progress" id="scrollProgress"></div>

<button class="theme-toggle" id="themeToggle" title="Переключить тему">
    <i class="bi bi-sun-fill" id="iconSun"></i>
    <i class="bi bi-moon-stars-fill d-none" id="iconMoon"></i>
</button>

<!-- HERO -->
<section class="stu-hero">
    <div class="stu-hero-content">
        <div class="stu-logo">
            <img src="assets/img/logo.svg" alt="АИС-расписание.РФ">
        </div>
        <h1 class="stu-h1">
            Моя <span class="accent">посещаемость</span><br>и сданные работы
        </h1>
        <p class="stu-subtitle">
            Введите номер зачётки или логин — увидите статистику
            по всем предметам за весь семестр.
        </p>

        <form method="get" class="stu-search" id="searchForm">
            <input type="text" name="zachetka" id="zachetkaInput"
                   placeholder="Например, 23Т013 или antonovaaa"
                   value="<?= htmlspecialchars($zachetka) ?>"
                   autocomplete="off" autofocus>
            <button type="submit" id="searchBtn">
                <i class="bi bi-search"></i> Найти
            </button>
        </form>

        <p class="stu-hint">
            Зачётка указана в вашем студенческом билете.
            Также можно ввести логин (как в <code>portal.kuzstu.ru</code>).
        </p>

        <?php if ($zachetka !== '' && !$student): ?>
            <div class="stu-error">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div>
                    <strong>Студент не найден</strong><br>
                    <span style="opacity:0.85;">Проверьте номер зачётки «<?= htmlspecialchars($zachetka) ?>» и попробуйте снова.</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($student && $globalStats): ?>

    <!-- КАРТОЧКА СТУДЕНТА -->
    <section class="stu-profile stu-reveal">
        <div class="stu-card">
            <div class="stu-avatar">
                <?= mb_strtoupper(mb_substr($student['full_name'], 0, 1)) ?>
            </div>
            <div class="stu-info">
                <h2 class="stu-name"><?= htmlspecialchars($student['full_name']) ?></h2>
                <div class="stu-meta">
                    <?php if (!empty($student['group_name'])): ?>
                        <span><i class="bi bi-people-fill"></i> <?= htmlspecialchars($student['group_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($student['zachetka'])): ?>
                        <span><i class="bi bi-card-text"></i> <?= htmlspecialchars($student['zachetka']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($student['institute'])): ?>
                        <span><i class="bi bi-building"></i> <?= htmlspecialchars($student['institute']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Статистика -->
        <div class="stu-stats">
            <div class="stu-stat" style="--stat-color: #0071e3;">
                <div class="stu-stat-value" data-count="<?= $globalStats['total_lessons'] ?>">0</div>
                <div class="stu-stat-label">Занятий всего</div>
            </div>
            <div class="stu-stat" style="--stat-color: #30d158;">
                <div class="stu-stat-value" data-count="<?= $globalStats['present'] ?>">0</div>
                <div class="stu-stat-label">Посещено</div>
            </div>
            <div class="stu-stat" style="--stat-color: #ff453a;">
                <div class="stu-stat-value" data-count="<?= $globalStats['absent'] ?>">0</div>
                <div class="stu-stat-label">Пропущено</div>
            </div>
            <div class="stu-stat" style="--stat-color: #bf5af2;">
                <div class="stu-stat-value" data-count="<?= $globalStats['works'] ?>">0</div>
                <div class="stu-stat-label">Работ сдано</div>
            </div>
        </div>

        <!-- Круговой индикатор посещаемости -->
        <?php if ($globalStats['pct'] !== null): ?>
            <?php
                $pct = $globalStats['pct'];
                $r = 70;
                $circumference = 2 * M_PI * $r;
                $offset = $circumference - ($pct / 100) * $circumference;
                $ringColor = $pct >= 75 ? '#30d158' : ($pct >= 50 ? '#ff9f0a' : '#ff453a');
            ?>
            <div class="stu-ring-wrap stu-reveal">
                <div class="stu-ring" data-pct="<?= $pct ?>" data-circ="<?= $circumference ?>">
                    <svg viewBox="0 0 160 160">
                        <circle cx="80" cy="80" r="<?= $r ?>" fill="none" stroke-width="12" class="stu-ring-bg"/>
                        <circle cx="80" cy="80" r="<?= $r ?>" fill="none" stroke-width="12"
                                class="stu-ring-fg"
                                stroke="<?= $ringColor ?>"
                                stroke-dasharray="<?= $circumference ?>"
                                stroke-dashoffset="<?= $circumference ?>"
                                data-offset="<?= $offset ?>"/>
                    </svg>
                    <div class="stu-ring-text">
                        <div class="stu-ring-pct"><?= $pct ?>%</div>
                        <div class="stu-ring-sub">посещаемость</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ПРЕДМЕТЫ -->
    <section class="stu-section">
        <h2 class="stu-section-title stu-reveal">
            <i class="bi bi-journal-bookmark-fill"></i>
            Предметы
            <span style="font-size: 0.6em; color: var(--ap-gray); font-weight: 400;">
                (<?= $globalStats['subjects_count'] ?>)
            </span>
        </h2>

        <?php if (empty($subjects)): ?>
            <div class="stu-empty">По вашему профилю пока нет данных о занятиях.</div>
        <?php else: foreach ($subjects as $idx => $s):
            $pct = $s['pct'];
            $pctClass = $pct === null ? 'pct-none' : ($pct >= 75 ? 'pct-good' : ($pct >= 50 ? 'pct-mid' : 'pct-bad'));
            $barColor = $pct === null ? '#86868b' : ($pct >= 75 ? '#30d158' : ($pct >= 50 ? '#ff9f0a' : '#ff453a'));
        ?>
            <div class="stu-subject stu-reveal" data-subject="<?= htmlspecialchars($s['name']) ?>">
                <div class="stu-subject-header" onclick="toggleSubject(this)">
                    <div class="stu-subject-info">
                        <h3 class="stu-subject-name"><?= htmlspecialchars($s['name']) ?></h3>
                        <div class="stu-subject-meta">
                            <span><i class="bi bi-calendar3"></i> <?= $s['total'] ?> занятий</span>
                            <span style="color: var(--ap-green);"><i class="bi bi-check-circle"></i> <?= $s['present'] ?></span>
                            <span style="color: var(--ap-red);"><i class="bi bi-x-circle"></i> <?= $s['absent'] ?></span>
                            <?php if ($s['works_count'] > 0): ?>
                                <span style="color: var(--ap-purple);"><i class="bi bi-journal-check"></i> <?= $s['works_count'] ?> работ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stu-subject-pct <?= $pctClass ?>">
                        <?= $pct === null ? '—' : $pct . '%' ?>
                    </div>
                    <i class="bi bi-chevron-down stu-subject-arrow"></i>
                </div>

                <div class="stu-subject-bar">
                    <div class="stu-subject-bar-fill"
                         data-width="<?= $pct === null ? 0 : $pct ?>"
                         style="background: <?= $barColor ?>;"></div>
                </div>

                <div class="stu-subject-body">
                    <div class="stu-subject-body-inner">
                        <div class="table-responsive">
                            <table class="stu-table">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Время</th>
                                        <th>Тип</th>
                                        <th>Преподаватель</th>
                                        <th>Посещение</th>
                                        <th>Работы</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($s['lessons'] as $l):
                                    $mark = $l['is_present'];
                                    if ($mark === null) {
                                        $badgeClass = 'stu-badge--none';
                                        $badgeIcon  = 'bi-dash-circle';
                                        $badgeText  = 'Не отмечено';
                                    } elseif ((int)$mark === 1) {
                                        $badgeClass = 'stu-badge--present';
                                        $badgeIcon  = 'bi-check-circle-fill';
                                        $badgeText  = 'Присутствовал';
                                    } else {
                                        $badgeClass = 'stu-badge--absent';
                                        $badgeIcon  = 'bi-x-circle-fill';
                                        $badgeText  = 'Пропуск';
                                    }

                                    $typeMap = [
                                        'lecture'  => ['Лекция',       'lecture'],
                                        'practice' => ['Практика',     'practice'],
                                        'lab'      => ['Лабораторная', 'lab'],
                                        'seminar'  => ['Семинар',      'seminar'],
                                        'other'    => ['Другое',       'other'],
                                    ];
                                    $t = $typeMap[$l['lesson_type']] ?? $typeMap['other'];
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= date('d.m.Y', strtotime($l['lesson_date'])) ?></strong>
                                            <div style="font-size:0.75rem; color:var(--ap-gray);">
                                                <?= ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'][(int)date('w', strtotime($l['lesson_date']))] ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-family: 'SF Mono', monospace; font-size: 0.82rem;">
                                                <?= $l['lesson_time'] ? substr($l['lesson_time'], 0, 5) : '—' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="stu-type stu-type--<?= $t[1] ?>"><?= $t[0] ?></span>
                                            <?php if (!empty($l['subgroup']) && $l['subgroup'] !== '0'): ?>
                                                <div style="font-size:0.7rem; color:var(--ap-gray); margin-top:2px;">
                                                    подгр. <?= htmlspecialchars($l['subgroup']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size: 0.82rem; color: var(--ap-gray);">
                                            <?= htmlspecialchars($l['teacher_name'] ?? '—') ?>
                                        </td>
                                        <td>
                                            <span class="stu-badge <?= $badgeClass ?>">
                                                <i class="bi <?= $badgeIcon ?>"></i>
                                                <?= $badgeText ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($l['works'])): ?>
                                                <div class="stu-works">
                                                    <?php foreach ($l['works'] as $w): ?>
                                                        <span class="stu-work-tag">
                                                            <i class="bi bi-journal-check"></i>
                                                            <?= htmlspecialchars($w['work_number']) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--ap-gray); font-size: 0.85rem;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </section>

<?php endif; ?>

<!-- ФУТЕР -->
<footer class="stu-footer">
    <p style="margin: 0 0 8px;">
        <a href="presentation.php">← О системе</a>
        &nbsp;·&nbsp;
        <a href="https://portal.kuzstu.ru" target="_blank" rel="noopener">portal.kuzstu.ru</a>
    </p>
    <p style="margin: 0; opacity: 0.7;">
        © <?= date('Y') ?> АИС-расписание.РФ
    </p>
</footer>

<script>
/* ============================================================
   ТЕМА
   ============================================================ */
(function() {
    const saved = localStorage.getItem('stu-theme') || 'light';
    applyTheme(saved);
    document.getElementById('themeToggle').addEventListener('click', () => {
        const cur = document.documentElement.getAttribute('data-theme') || 'light';
        applyTheme(cur === 'light' ? 'dark' : 'light');
    });
    function applyTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('iconSun').classList.add('d-none');
            document.getElementById('iconMoon').classList.remove('d-none');
        } else {
            document.documentElement.removeAttribute('data-theme');
            document.getElementById('iconSun').classList.remove('d-none');
            document.getElementById('iconMoon').classList.add('d-none');
        }
        localStorage.setItem('stu-theme', theme);
    }
})();

/* Прогресс-бар */
(function() {
    const bar = document.getElementById('scrollProgress');
    window.addEventListener('scroll', () => {
        const h = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.width = ((window.scrollY / h) * 100) + '%';
    }, { passive: true });
})();

/* Reveal */
(function() {
    const items = document.querySelectorAll('.stu-reveal');
    if (!('IntersectionObserver' in window)) {
        items.forEach(el => el.classList.add('is-visible'));
        return;
    }
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('is-visible');
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    items.forEach(el => obs.observe(el));
})();

/* Анимация счётчиков */
(function() {
    const counters = document.querySelectorAll('[data-count]');
    const easeOut = t => 1 - Math.pow(1 - t, 3);
    const animate = (el) => {
        const target = parseInt(el.dataset.count, 10);
        const start = performance.now();
        function tick(now) {
            const t = Math.min((now - start) / 1200, 1);
            el.textContent = Math.round(easeOut(t) * target);
            if (t < 1) requestAnimationFrame(tick);
            else el.textContent = target;
        }
        requestAnimationFrame(tick);
    };
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                animate(e.target);
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(el => obs.observe(el));
})();

/* Анимация кольца */
(function() {
    const ring = document.querySelector('.stu-ring');
    if (!ring) return;
    const fg = ring.querySelector('.stu-ring-fg');
    const offset = parseFloat(fg.dataset.offset);
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                setTimeout(() => { fg.style.strokeDashoffset = offset; }, 200);
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.3 });
    obs.observe(ring);
})();

/* Анимация прогресс-баров */
(function() {
    const bars = document.querySelectorAll('.stu-subject-bar-fill');
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                const w = e.target.dataset.width;
                setTimeout(() => { e.target.style.width = w + '%'; }, 200);
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.3 });
    bars.forEach(el => obs.observe(el));
})();

/* Раскрытие предмета */
function toggleSubject(header) {
    const subject = header.closest('.stu-subject');
    const isOpen = subject.dataset.open === '1';
    subject.dataset.open = isOpen ? '0' : '1';
    header.setAttribute('aria-expanded', !isOpen);
}

/* Автозагрузка: если в URL есть параметр — раскрыть первый предмет */
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('zachetka')) {
        const first = document.querySelector('.stu-subject');
        if (first) setTimeout(() => {
            first.dataset.open = '1';
            first.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 800);
    }
});
</script>

</body>
</html>