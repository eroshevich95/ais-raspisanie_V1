<?php
$pageTitle = 'Кабинет студента';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/../includes/kuzstu_api.php';
require_once __DIR__ . '/../includes/student_stats.php';
require_once __DIR__ . '/../includes/achievements.php';

// ============================================================
// СТАТИСТИКА
// ============================================================
$stats = getStudentStats($pdo, $studentId, $groupId);
$achievements = getStudentAchievements($pdo, $studentId, $groupId, $stats);

// Стрик: current пересчитывается, max хранится в БД
$streak = getStreakRecord($pdo, $studentId, $groupId);
saveStreakRecord($pdo, $studentId, $streak['current']);

// ============================================================
// РАСПИСАНИЕ НА СЕГОДНЯ
// ============================================================
$apiGroupId = $student['api_group_id'] ?? null;

if (empty($apiGroupId)) {
    $apiGroupId = kuzstuSyncGroupApiId($pdo, $groupId, $student['group_name']);
    if (empty($apiGroupId)) {
        $stmt = $pdo->prepare("SELECT api_group_id FROM groups_table WHERE id = ?");
        $stmt->execute([$groupId]);
        $apiGroupId = $stmt->fetchColumn();
    }
}

$todayLessons = [];
$apiError     = false;
$apiEmpty     = false;

if (!empty($apiGroupId)) {
    $raw = kuzstuFetchGroupSchedule((string)$apiGroupId, $today, $today);

    if ($raw === null) {
        $apiError = true;
    } elseif (empty($raw)) {
        $apiEmpty = true;
    } else {
        $timeMap = [];
        foreach (LESSON_TIMES as $n => $t) $timeMap[$n] = $t['start'];

        foreach ($raw as $l) {
            $num = (int)($l['lesson_number'] ?? 0);
            $todayLessons[] = [
                'id'            => (string)($l['id'] ?? ''),
                'lesson_number' => $num,
                'lesson_time'   => $timeMap[$num] ?? null,
                'discipline'    => $l['subject'] ?? '',
                'lesson_type'   => mapKuzstuTypeToInternal($l['type'] ?? ''),
                'type_raw'      => $l['type'] ?? '',
                'audience'      => $l['place'] ?? '',
                'subgroup'      => $l['subgroup'] ?? '0',
                'teacher_name'  => $l['teacher_name'] ?? '',
            ];
        }
    }
}

$currentNum = function_exists('getCurrentLessonNumber') ? getCurrentLessonNumber() : null;
$nextNum    = function_exists('getNextLessonNumber') ? getNextLessonNumber() : null;

$typeLabels = [
    'lecture'  => ['Лекция',       'primary',   'bi-book'],
    'practice' => ['Практика',     'success',   'bi-pencil'],
    'lab'      => ['Лабораторная', 'info',      'bi-flask'],
    'seminar'  => ['Семинар',      'warning',   'bi-people'],
    'other'    => ['Другое',       'secondary', 'bi-journal'],
];

// ============================================================
// ЛОГИКА ДЛЯ ПЛАШЕК
// ============================================================
$attendancePct = $stats['attendance_pct'];
$groupAvgPct   = $stats['group_avg_pct'];
$debtAbsences  = $stats['absent'];
$debtWorks     = count($stats['missing_works']);

$debtLevel = 'ok';
if ($debtAbsences >= 10 || $debtWorks >= 5)      $debtLevel = 'danger';
elseif ($debtAbsences >= 5  || $debtWorks >= 2)  $debtLevel = 'warning';

$debtColors = [
    'ok'      => ['#30d158', 'rgba(48,209,88,0.08)',  '#198754', 'bi-check-circle-fill',        'Всё в порядке'],
    'warning' => ['#ff9f0a', 'rgba(255,159,10,0.08)', '#b25c00', 'bi-exclamation-triangle-fill','Есть пропуски'],
    'danger'  => ['#ff453a', 'rgba(255,69,58,0.08)',  '#842029', 'bi-x-octagon-fill',            'Тревога!'],
];
$dc = $debtColors[$debtLevel];

$diffFromGroup = null;
if ($attendancePct !== null && $groupAvgPct !== null) {
    $diffFromGroup = $attendancePct - $groupAvgPct;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Приветствие -->
<div style="margin-bottom: 24px;">
    <h2 style="font-size:1.5rem;font-weight:700;letter-spacing:-0.02em;margin:0 0 6px;">
        Привет, <?= htmlspecialchars(explode(' ', $student['full_name'])[1] ?? $student['full_name']) ?>!
    </h2>
    <p style="color:#86868b;margin:0;font-size:0.95rem;">
        <i class="bi bi-people-fill"></i>
        <?= htmlspecialchars($student['group_name']) ?>
        · <i class="bi bi-calendar3"></i>
        <?= ruDayFull($today) ?>, <?= date('d.m.Y') ?>
    </p>
</div>

<!-- Статистика -->
<div class="st-stats">
    <div class="st-stat" style="--stat-color:#0071e3; --stat-bg:rgba(0,113,227,0.1);">
        <div class="st-stat-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="st-stat-value"><?= $attendancePct !== null ? $attendancePct . '%' : '—' ?></div>
        <div class="st-stat-label">Посещаемость</div>
    </div>
    <div class="st-stat" style="--stat-color:#30d158; --stat-bg:rgba(48,209,88,0.1);">
        <div class="st-stat-icon"><i class="bi bi-check2"></i></div>
        <div class="st-stat-value"><?= (int)$stats['present'] ?></div>
        <div class="st-stat-label">Посещено пар</div>
    </div>
    <div class="st-stat" style="--stat-color:#ff453a; --stat-bg:rgba(255,69,58,0.1);">
        <div class="st-stat-icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="st-stat-value"><?= (int)$stats['absent'] ?></div>
        <div class="st-stat-label">Пропущено</div>
    </div>
    <div class="st-stat" style="--stat-color:#bf5af2; --stat-bg:rgba(191,90,242,0.1);">
        <div class="st-stat-icon"><i class="bi bi-journal-check"></i></div>
        <div class="st-stat-value"><?= (int)$stats['works_submitted'] ?></div>
        <div class="st-stat-label">Работ сдано</div>
    </div>
</div>

<!-- ПЛАШКА: ДОЛГИ -->
<div class="st-card" style="border-left: 4px solid <?= $dc[0] ?>; background: linear-gradient(135deg, <?= $dc[1] ?> 0%, #fff 100%);">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <div style="width:52px;height:52px;border-radius:14px;background:<?= $dc[1] ?>;color:<?= $dc[0] ?>;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
            <i class="bi <?= $dc[3] ?>"></i>
        </div>
        <div style="flex:1;min-width:200px;">
            <div style="font-size:1rem;font-weight:700;color:<?= $dc[2] ?>;margin-bottom:4px;">
                <?= $dc[4] ?>
            </div>
            <div style="font-size:0.85rem;color:#86868b;display:flex;gap:16px;flex-wrap:wrap;">
                <span>
                    <i class="bi bi-calendar-x"></i>
                    Пропущено: <strong style="color:#1d1d1f;"><?= $debtAbsences ?></strong> пар
                </span>
                <span>
                    <i class="bi bi-journal-x"></i>
                    Не сдано: <strong style="color:#1d1d1f;"><?= $debtWorks ?></strong> работ
                </span>
            </div>
        </div>
        <a href="grades.php" class="btn btn-sm" style="background:<?= $dc[0] ?>;color:#fff;white-space:nowrap;">
            <i class="bi bi-arrow-right"></i> Разобраться
        </a>
    </div>

    <?php if ($debtLevel !== 'ok' && (!empty($stats['subjects_with_debts']) || !empty($stats['missing_works']))): ?>
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid rgba(0,0,0,0.06);">
            <?php if (!empty($stats['subjects_with_debts'])): ?>
                <div style="font-size:0.78rem;color:#86868b;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:8px;">
                    По предметам
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <?php foreach (array_slice($stats['subjects_with_debts'], 0, 5) as $s): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:0.85rem;">
                            <span style="flex:1;min-width:0;color:#1d1d1f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($s['discipline']) ?>
                            </span>
                            <span style="color:<?= $dc[2] ?>;font-weight:700;white-space:nowrap;">
                                <?= $s['absent'] ?> из <?= $s['total'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($stats['missing_works'])): ?>
                <div style="font-size:0.78rem;color:#86868b;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;margin:14px 0 8px;">
                    Несданные работы
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    <?php foreach (array_slice($stats['missing_works'], 0, 10) as $w): ?>
                        <span style="background:rgba(255,69,58,0.1);color:#842029;padding:3px 10px;border-radius:12px;font-size:0.75rem;font-weight:600;">
                            <?= htmlspecialchars($w['work_number']) ?>
                        </span>
                    <?php endforeach; ?>
                    <?php if (count($stats['missing_works']) > 10): ?>
                        <span style="color:#86868b;font-size:0.75rem;padding:3px 0;">
                            и ещё <?= count($stats['missing_works']) - 10 ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ПЛАШКИ В РЯД: Сравнение с группой + Рейтинг -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;" class="st-two-cols">

    <!-- СРАВНЕНИЕ С ГРУППОЙ -->
    <div class="st-card" style="margin-bottom:0;">
        <div style="font-size:0.75rem;color:#86868b;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:14px;">
            <i class="bi bi-bar-chart-fill"></i> Сравнение с группой
        </div>

        <?php if ($attendancePct !== null && $groupAvgPct !== null): ?>
            <div style="display:flex;align-items:baseline;gap:10px;margin-bottom:16px;">
                <div style="font-size:2rem;font-weight:800;line-height:1;color:#0071e3;">
                    <?= $attendancePct ?>%
                </div>
                <div style="font-size:0.9rem;color:#86868b;">ты</div>
                <div style="margin-left:auto;text-align:right;">
                    <div style="font-size:1.2rem;font-weight:700;color:#86868b;line-height:1;">
                        <?= $groupAvgPct ?>%
                    </div>
                    <div style="font-size:0.72rem;color:#86868b;">группа</div>
                </div>
            </div>

            <div style="margin-bottom:12px;">
                <div style="font-size:0.75rem;color:#86868b;margin-bottom:4px;display:flex;justify-content:space-between;">
                    <span>Ты</span>
                    <strong style="color:#0071e3;"><?= $attendancePct ?>%</strong>
                </div>
                <div style="height:8px;background:#f0f0f2;border-radius:4px;overflow:hidden;">
                    <div style="height:100%;width:<?= $attendancePct ?>%;background:linear-gradient(90deg,#0071e3,#5e5ce6);border-radius:4px;"></div>
                </div>
            </div>

            <div>
                <div style="font-size:0.75rem;color:#86868b;margin-bottom:4px;display:flex;justify-content:space-between;">
                    <span>Средняя по группе</span>
                    <strong style="color:#86868b;"><?= $groupAvgPct ?>%</strong>
                </div>
                <div style="height:8px;background:#f0f0f2;border-radius:4px;overflow:hidden;">
                    <div style="height:100%;width:<?= $groupAvgPct ?>%;background:#a1a1a6;border-radius:4px;"></div>
                </div>
            </div>

            <div style="margin-top:16px;padding-top:12px;border-top:1px solid #f0f0f2;font-size:0.85rem;">
                <?php if ($diffFromGroup > 0): ?>
                    <span style="color:#30d158;font-weight:700;">
                        <i class="bi bi-arrow-up-circle-fill"></i> +<?= $diffFromGroup ?>% выше средней
                    </span>
                <?php elseif ($diffFromGroup < 0): ?>
                    <span style="color:#ff453a;font-weight:700;">
                        <i class="bi bi-arrow-down-circle-fill"></i> <?= $diffFromGroup ?>% ниже средней
                    </span>
                <?php else: ?>
                    <span style="color:#86868b;">
                        <i class="bi bi-dash-circle-fill"></i> Как у всех
                    </span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="color:#86868b;font-size:0.85rem;text-align:center;padding:20px 0;">
                Пока нет данных для сравнения
            </div>
        <?php endif; ?>
    </div>

    <!-- РЕЙТИНГ В ГРУППЕ -->
    <div class="st-card" style="margin-bottom:0;">
        <div style="font-size:0.75rem;color:#86868b;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:14px;">
            <i class="bi bi-trophy-fill"></i> Рейтинг в группе
        </div>

        <?php if ($stats['rank'] !== null && $stats['total_in_group'] > 1): ?>
            <div style="text-align:center;margin-bottom:16px;">
                <div style="font-size:3rem;font-weight:800;line-height:1;background:linear-gradient(135deg,#0071e3,#bf5af2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                    #<?= $stats['rank'] ?>
                </div>
                <div style="font-size:0.85rem;color:#86868b;margin-top:6px;">
                    из <?= $stats['total_in_group'] ?> студентов
                </div>
            </div>

            <?php if ($stats['percentile'] !== null): ?>
                <div style="padding:12px;border-radius:10px;background:linear-gradient(135deg,rgba(0,113,227,0.08),rgba(191,90,242,0.08));text-align:center;">
                    <div style="font-size:0.85rem;color:#1d1d1f;line-height:1.4;">
                        Ты опережаешь <strong style="color:#0071e3;font-size:1.1rem;"><?= $stats['percentile'] ?>%</strong> группы
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($stats['rank'] === 1): ?>
                <div style="margin-top:14px;text-align:center;font-size:0.85rem;color:#198754;font-weight:700;">
                    <i class="bi bi-trophy-fill"></i> Первое место!
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="color:#86868b;font-size:0.85rem;text-align:center;padding:20px 0;">
                Недостаточно данных
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ПЛАШКА: ЛИЧНЫЙ РЕКОРД STREAK -->
<div class="st-card" style="background: linear-gradient(135deg, #ff9f0a 0%, #ff453a 100%); color:#fff; border:none; position:relative; overflow:hidden; margin-bottom:18px;">
    <div style="position:absolute;top:-40%;right:-10%;width:320px;height:320px;background:radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 65%);border-radius:50%;pointer-events:none;"></div>

    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;position:relative;z-index:1;">
        <div style="width:64px;height:64px;border-radius:18px;background:rgba(255,255,255,0.2);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.35);display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0;">
            <i class="bi bi-fire"></i>
        </div>

        <div style="flex:1;min-width:200px;">
            <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:0.15em;opacity:0.85;margin-bottom:4px;">
                Твоя серия
            </div>
            <div style="font-size:2rem;font-weight:800;line-height:1;margin-bottom:6px;">
                <?= $streak['current'] ?>
                <span style="font-size:1rem;font-weight:600;opacity:0.85;">
                    <?php
                        $n = $streak['current'] % 100;
                        $n1 = $n % 10;
                        if ($n > 10 && $n < 20) echo 'пар подряд';
                        elseif ($n1 == 1) echo 'пара подряд';
                        elseif ($n1 >= 2 && $n1 <= 4) echo 'пары подряд';
                        else echo 'пар подряд';
                    ?>
                </span>
            </div>
            <div style="font-size:0.85rem;opacity:0.9;">
                <?php if ($streak['is_record'] && $streak['current'] > 0): ?>
                    <i class="bi bi-trophy-fill"></i> Это твой личный рекорд! 🎉
                <?php elseif ($streak['max_all_time'] > 0): ?>
                    Рекорд: <strong><?= $streak['max_all_time'] ?></strong> <?= $streak['max_all_time'] === 1 ? 'пара' : 'пар' ?>
                <?php else: ?>
                    Начни серию — не пропускай пары!
                <?php endif; ?>
            </div>
        </div>

        <?php if ($streak['current'] >= 5): ?>
            <div style="text-align:center;flex-shrink:0;">
                <div style="font-size:2.5rem;line-height:1;">
                    <?= $streak['current'] >= 20 ? '🔥' : ($streak['current'] >= 10 ? '⚡' : '✨') ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- РАСПИСАНИЕ НА СЕГОДНЯ -->
<div class="st-card">
    <h3 class="st-card-title">
        <i class="bi bi-calendar-day"></i>
        Расписание на сегодня
    </h3>

    <?php if (empty($apiGroupId)): ?>
        <div style="text-align:center;color:#86868b;padding:24px 0;">
            <i class="bi bi-exclamation-triangle-fill" style="font-size:2rem;opacity:0.5;display:block;margin-bottom:8px;color:#ff9f0a;"></i>
            <div style="font-size:0.88rem;">Группа не синхронизирована с порталом</div>
        </div>
    <?php elseif ($apiError): ?>
        <div style="text-align:center;color:#86868b;padding:24px 0;">
            <i class="bi bi-cloud-slash" style="font-size:2rem;opacity:0.5;display:block;margin-bottom:8px;color:#ff453a;"></i>
            <div style="font-size:0.88rem;">Портал КузГТУ не отвечает</div>
        </div>
    <?php elseif ($apiEmpty || empty($todayLessons)): ?>
        <div style="text-align:center;color:#86868b;padding:24px 0;">
            <i class="bi bi-cup-hot" style="font-size:2rem;opacity:0.4;display:block;margin-bottom:8px;"></i>
            Пар нет — отдыхай! 🎉
        </div>
    <?php else: ?>
        <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-radius:10px;margin-bottom:14px;background:rgba(0,113,227,0.06);border:1px solid rgba(0,113,227,0.15);">
            <i class="bi bi-info-circle-fill" style="color:#0071e3;font-size:0.95rem;flex-shrink:0;margin-top:2px;"></i>
            <div style="font-size:0.78rem;color:#084298;line-height:1.45;">
                Данные с портала КузГТУ. Возможны расхождения.
            </div>
        </div>

        <?php foreach ($todayLessons as $l):
            $isCurrent = $currentNum && (int)$l['lesson_number'] === $currentNum;
            $isNext    = !$isCurrent && $nextNum && (int)$l['lesson_number'] === $nextNum;
            $cls = 'st-lesson';
            if ($isCurrent) $cls .= ' st-lesson--current';
            elseif ($isNext) $cls .= ' st-lesson--next';
            $t = $typeLabels[$l['lesson_type']] ?? $typeLabels['other'];
        ?>
            <div class="<?= $cls ?>">
                <div class="st-lesson-time">
                    <?= $l['lesson_time'] ? substr($l['lesson_time'], 0, 5) : '—' ?>
                    <?php if ($l['lesson_number']): ?>
                        <small>пара <?= (int)$l['lesson_number'] ?></small>
                    <?php endif; ?>
                </div>
                <div class="st-lesson-info">
                    <div class="st-lesson-title">
                        <?= htmlspecialchars($l['discipline']) ?>
                        <?php if ($isCurrent): ?>
                            <span style="display:inline-block;padding:2px 8px;border-radius:8px;background:#30d158;color:#fff;font-size:0.65rem;font-weight:700;margin-left:6px;">LIVE</span>
                        <?php elseif ($isNext): ?>
                            <span style="display:inline-block;padding:2px 8px;border-radius:8px;background:#0dcaf0;color:#fff;font-size:0.65rem;font-weight:700;margin-left:6px;">Далее</span>
                        <?php endif; ?>
                    </div>
                    <div class="st-lesson-meta">
                        <span><i class="bi bi-tag-fill"></i> <?= $t[0] ?></span>
                        <?php if (!empty($l['audience'])): ?>
                            <span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($l['audience']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($l['teacher_name'])): ?>
                            <span><i class="bi bi-person-badge"></i> <?= htmlspecialchars($l['teacher_name']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($l['subgroup']) && $l['subgroup'] !== '0'): ?>
                            <span><i class="bi bi-diagram-3"></i> подгр. <?= htmlspecialchars($l['subgroup']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ДОСТИЖЕНИЯ -->
<div class="st-card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h3 class="st-card-title" style="margin:0;">
            <i class="bi bi-trophy-fill"></i>
            Достижения
        </h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <span style="background:linear-gradient(135deg,#ff9f0a,#ff453a);color:#fff;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;">
                <?= (int)$achievements['total_points'] ?> очков
            </span>
            <span style="background:#f5f5f7;color:#86868b;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;">
                <?= count($achievements['unlocked']) ?>/<?= count($achievements['unlocked']) + count($achievements['locked']) ?>
            </span>
        </div>
    </div>

    <?php if (empty($achievements['unlocked']) && empty($achievements['locked'])): ?>
        <div style="text-align:center;color:#86868b;padding:24px 0;font-size:0.88rem;">
            <i class="bi bi-trophy" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:8px;"></i>
            Достижения пока не настроены
        </div>
    <?php else: ?>

        <!-- Присвоенные -->
        <?php if (!empty($achievements['unlocked'])): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:20px;">
                <?php foreach ($achievements['unlocked'] as $a):
                    $unlockedDate = '';
                    if (!empty($a['unlocked_at'])) {
                        $ts = strtotime($a['unlocked_at']);
                        $months = ['','января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
                        $unlockedDate = date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
                    }
                ?>
                    <div class="st-ach st-ach--unlocked"
                         style="--ach-color:<?= htmlspecialchars($a['color']) ?>;"
                         data-ach-state="unlocked"
                         data-ach-title="<?= htmlspecialchars($a['title'], ENT_QUOTES) ?>"
                         data-ach-desc="<?= htmlspecialchars($a['description'] ?? '', ENT_QUOTES) ?>"
                         data-ach-points="<?= (int)$a['points'] ?>"
                         data-ach-unlocked-at="<?= htmlspecialchars($unlockedDate) ?>"
                         data-ach-value="<?= htmlspecialchars((string)($a['value_at_unlock'] ?? '')) ?>"
                         data-ach-target="<?= htmlspecialchars((string)$a['target']) ?>">
                        <div class="st-ach-icon">
                            <?php if (!empty($a['svg'])): ?>
                                <?= $a['svg'] ?>
                            <?php else: ?>
                                <i class="bi <?= htmlspecialchars($a['icon'] ?: 'bi-trophy-fill') ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="st-ach-title"><?= htmlspecialchars($a['title']) ?></div>
                        <div class="st-ach-desc"><?= htmlspecialchars($a['description'] ?? '') ?></div>
                        <div class="st-ach-points">+<?= (int)$a['points'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- В процессе -->
        <?php if (!empty($achievements['locked'])): ?>
            <div style="font-size:0.75rem;color:#86868b;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:12px;">
                В процессе
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;">
                <?php foreach (array_slice($achievements['locked'], 0, 12) as $a): ?>
                    <div class="st-ach st-ach--locked"
                         style="--ach-color:<?= htmlspecialchars($a['color']) ?>;"
                         data-ach-state="locked"
                         data-ach-title="<?= htmlspecialchars($a['title'], ENT_QUOTES) ?>"
                         data-ach-desc="<?= htmlspecialchars($a['description'] ?? '', ENT_QUOTES) ?>"
                         data-ach-points="<?= (int)$a['points'] ?>"
                         data-ach-progress="<?= (int)$a['progress'] ?>"
                         data-ach-current="<?= htmlspecialchars((string)$a['current']) ?>"
                         data-ach-target="<?= htmlspecialchars((string)$a['target']) ?>">
                        <div class="st-ach-icon">
                            <?php if (!empty($a['svg'])): ?>
                                <?= $a['svg'] ?>
                            <?php else: ?>
                                <i class="bi <?= htmlspecialchars($a['icon'] ?: 'bi-trophy') ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="st-ach-title"><?= htmlspecialchars($a['title']) ?></div>
                        <div class="st-ach-bar">
                            <div class="st-ach-bar-fill" style="width:<?= (int)$a['progress'] ?>%;background:<?= htmlspecialchars($a['color']) ?>;"></div>
                        </div>
                        <div class="st-ach-progress"><?= (int)$a['progress'] ?>%</div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($achievements['locked']) > 12): ?>
                <div style="text-align:center;margin-top:14px;font-size:0.85rem;color:#86868b;">
                    и ещё <?= count($achievements['locked']) - 12 ?> достижений
                </div>
            <?php endif; ?>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- ПЛАШКА MAX -->
<div class="st-card st-max-card">
    <div class="st-max-glow"></div>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;position:relative;z-index:1;">
        <div class="st-max-icon" style="padding:0; overflow:hidden; background:#fff;">
            <img src="/api_max_bot/asset/kuzgtushnik.png"
                 alt="КузГТУшник"
                 style="width:100%; height:100%; object-fit:cover; border-radius:15px;"
                 onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=&quot;bi bi-robot&quot; style=&quot;font-size:1.9rem;color:#0a84ff;&quot;></i>';">
        </div>
        <div style="flex:1;min-width:200px;">
            <div style="font-size:1.1rem;font-weight:700;margin-bottom:4px;">
                КузГТУшник в MAX
            </div>
            <div style="font-size:0.88rem;opacity:0.92;line-height:1.45;">
                Расписание в мессенджере. Уведомления о парах, замены, погода.
            </div>
        </div>
        <a href="https://max.ru/se14203391_bot" target="_blank" rel="noopener" class="st-max-btn">
            <i class="bi bi-send-fill"></i> Открыть бот
        </a>
    </div>
</div>

<!-- Быстрые ссылки -->
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
    <a href="schedule.php" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-calendar-week"></i> Расписание на неделю
    </a>
    <a href="grades.php" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-journal-check"></i> Мои оценки и работы
    </a>
</div>

<!-- Тултип достижений -->
<div class="st-ach-tooltip" id="achTooltip" role="tooltip" aria-hidden="true">
    <div class="st-ach-tooltip-inner">
        <div class="st-ach-tooltip-header">
            <div class="st-ach-tooltip-icon" id="achTtIcon"></div>
            <div>
                <div class="st-ach-tooltip-title" id="achTtTitle"></div>
                <div class="st-ach-tooltip-points" id="achTtPoints"></div>
            </div>
        </div>
        <div class="st-ach-tooltip-body">
            <div class="st-ach-tooltip-desc" id="achTtDesc"></div>
            <div class="st-ach-tooltip-progress" id="achTtProgressWrap" style="display:none;">
                <div class="st-ach-tooltip-progress-bar">
                    <div class="st-ach-tooltip-progress-fill" id="achTtProgressFill"></div>
                </div>
                <div class="st-ach-tooltip-progress-text" id="achTtProgressText"></div>
            </div>
            <div class="st-ach-tooltip-unlocked" id="achTtUnlockedWrap" style="display:none;">
                <i class="bi bi-check-circle-fill"></i>
                <span id="achTtUnlockedText"></span>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const tooltip = document.getElementById('achTooltip');
    if (!tooltip) return;

    const ttIcon         = document.getElementById('achTtIcon');
    const ttTitle        = document.getElementById('achTtTitle');
    const ttPoints       = document.getElementById('achTtPoints');
    const ttDesc         = document.getElementById('achTtDesc');
    const ttProgWrap     = document.getElementById('achTtProgressWrap');
    const ttProgFill     = document.getElementById('achTtProgressFill');
    const ttProgText     = document.getElementById('achTtProgressText');
    const ttUnlockedWrap = document.getElementById('achTtUnlockedWrap');
    const ttUnlockedText = document.getElementById('achTtUnlockedText');

    let currentEl = null;

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        })[c]);
    }

    function showTooltip(el, x, y) {
        const state      = el.dataset.achState;
        const title      = el.dataset.achTitle   || '';
        const desc       = el.dataset.achDesc    || '';
        const points     = el.dataset.achPoints  || '0';
        const progress   = parseInt(el.dataset.achProgress || '0', 10);
        const current    = el.dataset.achCurrent || '';
        const target     = el.dataset.achTarget  || '';
        const unlockedAt = el.dataset.achUnlockedAt || '';
        const valueAtUnlock = el.dataset.achValue || '';

        const color = (getComputedStyle(el).getPropertyValue('--ach-color') || '#0071e3').trim();
        tooltip.style.setProperty('--tt-color', color + '33');
        tooltip.style.setProperty('--tt-color-solid', color);

        const iconEl = el.querySelector('.st-ach-icon');
        ttIcon.innerHTML = iconEl ? iconEl.innerHTML : '<i class="bi bi-trophy-fill"></i>';

        ttTitle.textContent  = title;
        ttPoints.textContent = '+' + points + ' очков';
        ttDesc.textContent   = desc;

        if (state === 'unlocked') {
            ttProgWrap.style.display = 'none';

            if (unlockedAt) {
                ttUnlockedWrap.style.display = 'flex';
                let text = 'Получено ' + unlockedAt;
                if (valueAtUnlock && target) {
                    text += ' · результат ' + valueAtUnlock + '/' + target;
                }
                ttUnlockedText.textContent = text;
            } else {
                ttUnlockedWrap.style.display = 'none';
            }
        } else {
            ttUnlockedWrap.style.display = 'none';
            ttProgWrap.style.display = 'block';
            ttProgFill.style.width = progress + '%';
            ttProgFill.style.background = 'linear-gradient(90deg,' + color + ',#bf5af2)';

            let progressText = '';
            if (current && target) {
                progressText = '<span>Прогресс</span><span><strong>' + escapeHtml(current) + '</strong> / ' + escapeHtml(target) + '</span>';
            } else {
                progressText = '<span>Прогресс</span><span><strong>' + progress + '%</strong></span>';
            }
            ttProgText.innerHTML = progressText;
        }

        tooltip.classList.add('show');
        tooltip.setAttribute('aria-hidden', 'false');
        positionTooltip(x, y);
    }

    function positionTooltip(x, y) {
        const rect = tooltip.getBoundingClientRect();
        const pad  = 12;
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        let left = x - rect.width / 2;
        let top  = y - rect.height - 16;
        let placement = 'above';

        if (left < pad) left = pad;
        if (left + rect.width > vw - pad) left = vw - rect.width - pad;

        if (top < pad) {
            top = y + 24;
            placement = 'below';
        }

        if (top + rect.height > vh - pad) {
            top = vh - rect.height - pad;
        }

        tooltip.style.left = left + 'px';
        tooltip.style.top  = top + 'px';
        tooltip.classList.toggle('st-ach-tooltip--above', placement === 'above');
        tooltip.classList.toggle('st-ach-tooltip--below', placement === 'below');
    }

    function hideTooltip() {
        tooltip.classList.remove('show');
        tooltip.setAttribute('aria-hidden', 'true');
        currentEl = null;
    }

    document.querySelectorAll('.st-ach').forEach(el => {
        el.addEventListener('mouseenter', (e) => {
            currentEl = el;
            showTooltip(el, e.clientX, e.clientY);
        });
        el.addEventListener('mousemove', (e) => {
            if (currentEl !== el) return;
            positionTooltip(e.clientX, e.clientY);
        });
        el.addEventListener('mouseleave', hideTooltip);
        el.addEventListener('click', (e) => {
            if (currentEl === el && tooltip.classList.contains('show')) {
                hideTooltip();
                return;
            }
            currentEl = el;
            showTooltip(el, e.clientX || window.innerWidth / 2, e.clientY || window.innerHeight - 100);
        });
    });

    window.addEventListener('scroll', hideTooltip, { passive: true });
    window.addEventListener('resize', hideTooltip);

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.st-ach')) hideTooltip();
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>