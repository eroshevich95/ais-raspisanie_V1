<?php
$pageTitle = 'Расписание';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/../includes/kuzstu_api.php';

// ============================================================
// Диапазон недели
// ============================================================
$weekOffset = (int)($_GET['week'] ?? 0);
$monday = new DateTime('monday this week');
if ($weekOffset !== 0) {
    $monday->modify(($weekOffset > 0 ? '+' : '') . $weekOffset . ' week');
}
$sunday = (clone $monday)->modify('+6 days');

$from = $monday->format('Y-m-d');
$to   = $sunday->format('Y-m-d');

// ============================================================
// api_group_id
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

// ============================================================
// Тянем расписание
// ============================================================
$lessons   = [];
$apiError  = false;
$apiEmpty  = false;
$apiFetchedAt = null;

if (!empty($apiGroupId)) {
    $apiFetchedAt = date('H:i:s');
    $apiLessons = kuzstuFetchGroupSchedule((string)$apiGroupId, $from, $to);

    if ($apiLessons === null) {
        $apiError = true;
    } elseif (empty($apiLessons)) {
        $apiEmpty = true;
    } else {
        $timeMap = [];
        foreach (LESSON_TIMES as $n => $t) $timeMap[$n] = $t['start'];

        foreach ($apiLessons as $l) {
            $num = (int)($l['lesson_number'] ?? 0);
            $lessons[] = [
                'id'            => (string)($l['id'] ?? ''),
                'date'          => $l['date_lesson'] ?? '',
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

// ============================================================
// Индексация по [день_недели][пара]
// ============================================================
$grid = [];
for ($i = 0; $i < 7; $i++) {
    $grid[$i] = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];
}

foreach ($lessons as $l) {
    $ts = strtotime($l['date']);
    $dow = (int)date('N', $ts) - 1; // 0=Пн .. 6=Вс
    $num = (int)$l['lesson_number'];
    if (isset($grid[$dow][$num])) {
        $grid[$dow][$num][] = $l;
    }
}

$dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
$todayDow = (int)date('N') - 1;

$timeMapShort = [
    1 => ['09:00', '10:35'],
    2 => ['10:50', '12:25'],
    3 => ['13:20', '14:55'],
    4 => ['15:10', '16:45'],
    5 => ['17:00', '18:35'],
    6 => ['18:50', '20:20'],
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 style="font-size:1.4rem;font-weight:700;letter-spacing:-0.02em;margin:0;">
        <i class="bi bi-calendar-week"></i> Расписание
    </h2>
    <div class="d-flex gap-2 flex-wrap">
        <a href="?week=<?= $weekOffset - 1 ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chevron-left"></i>
        </a>
        <?php if ($weekOffset !== 0): ?>
            <a href="?week=0" class="btn btn-primary btn-sm">Текущая</a>
        <?php endif; ?>
        <a href="?week=<?= $weekOffset + 1 ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chevron-right"></i>
        </a>
        <a href="schedule_ics.php?week=<?= $weekOffset ?>" class="btn btn-outline-success btn-sm" title="Добавить в календарь">
            <i class="bi bi-calendar-plus"></i> <span class="d-none d-sm-inline">В календарь</span>
        </a>
    </div>
</div>

<p style="color:#86868b;margin-bottom:16px;">
    <strong><?= $monday->format('d.m') ?></strong> — <strong><?= $sunday->format('d.m.Y') ?></strong>
    · группа <strong><?= htmlspecialchars($student['group_name']) ?></strong>
    <?php if ($weekOffset === 0): ?>
        · текущая
    <?php endif; ?>
</p>

<?php if (empty($apiGroupId)): ?>
    <div class="st-card" style="text-align:center;color:#86868b;padding:40px;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:2.5rem;opacity:0.4;display:block;margin-bottom:12px;color:#ff9f0a;"></i>
        <h4 style="font-size:1rem;font-weight:700;color:#1d1d1f;margin:0 0 6px;">Группа не найдена на портале КузГТУ</h4>
        <p style="margin:0;font-size:0.88rem;">Обратитесь к преподавателю или администратору</p>
    </div>
<?php elseif ($apiError): ?>
    <div class="st-card" style="text-align:center;color:#86868b;padding:40px;">
        <i class="bi bi-cloud-slash" style="font-size:2.5rem;opacity:0.4;display:block;margin-bottom:12px;color:#ff453a;"></i>
        <h4 style="font-size:1rem;font-weight:700;color:#1d1d1f;margin:0 0 6px;">Портал КузГТУ не отвечает</h4>
        <a href="" class="btn btn-outline-primary btn-sm mt-2"><i class="bi bi-arrow-clockwise"></i> Обновить</a>
    </div>
<?php else: ?>

    <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-radius:10px;margin-bottom:16px;background:rgba(0,113,227,0.06);border:1px solid rgba(0,113,227,0.15);">
        <i class="bi bi-info-circle-fill" style="color:#0071e3;font-size:0.95rem;flex-shrink:0;margin-top:2px;"></i>
        <div style="font-size:0.78rem;color:#084298;line-height:1.45;">
            Данные с портала КузГТУ. Возможны расхождения.
            <?php if ($apiFetchedAt): ?><span style="opacity:0.75;">· получено в <?= htmlspecialchars($apiFetchedAt) ?></span><?php endif; ?>
        </div>
    </div>

    <!-- СЕТКА -->
    <div class="sched-grid-wrap">
        <div class="sched-grid">
            <!-- Заголовок: пустая ячейка + 7 дней -->
            <div class="sched-corner"></div>
            <?php for ($i = 0; $i < 7; $i++):
                $day = (clone $monday)->modify("+$i days");
                $isToday = ($weekOffset === 0 && $i === $todayDow);
                $isWeekend = ($i >= 5);
            ?>
                <div class="sched-head <?= $isToday ? 'sched-head--today' : '' ?> <?= $isWeekend ? 'sched-head--weekend' : '' ?>">
                    <div class="sched-head-day"><?= $dayNames[$i] ?></div>
                    <div class="sched-head-date"><?= $day->format('d.m') ?></div>
                </div>
            <?php endfor; ?>

            <!-- 6 строк: пары -->
            <?php for ($num = 1; $num <= 6; $num++): ?>
                <!-- Слева: время пары -->
                <div class="sched-time">
                    <div class="sched-time-num"><?= $num ?></div>
                    <div class="sched-time-range">
                        <?= $timeMapShort[$num][0] ?><br>
                        <?= $timeMapShort[$num][1] ?>
                    </div>
                </div>

                <!-- 7 дней -->
                <?php for ($i = 0; $i < 7; $i++):
                    $day = (clone $monday)->modify("+$i days");
                    $isToday = ($weekOffset === 0 && $i === $todayDow);
                    $isWeekend = ($i >= 5);
                    $cellLessons = $grid[$i][$num] ?? [];

                    $cellClasses = ['sched-cell'];
                    if ($isToday) $cellClasses[] = 'sched-cell--today';
                    if ($isWeekend) $cellClasses[] = 'sched-cell--weekend';
                    if (empty($cellLessons)) $cellClasses[] = 'sched-cell--empty';
                ?>
                    <div class="<?= implode(' ', $cellClasses) ?>">
                        <?php if (empty($cellLessons)): ?>
                            <div class="sched-empty-dot"></div>
                        <?php else: foreach ($cellLessons as $l):
                            $cls = 'sched-card sched-card--' . $l['lesson_type'];
                            $currentNum = function_exists('getCurrentLessonNumber') ? getCurrentLessonNumber() : null;
                            if ($isToday && $currentNum && $num === $currentNum) {
                                $cls .= ' sched-card--current';
                            }
                            $typeIcons = [
                                'lecture'  => 'bi-book',
                                'practice' => 'bi-pencil',
                                'lab'      => 'bi-flask',
                                'seminar'  => 'bi-people',
                                'other'    => 'bi-journal',
                            ];
                            $icon = $typeIcons[$l['lesson_type']] ?? 'bi-journal';
                        ?>
                            <div class="<?= $cls ?>"
                                 title="<?= htmlspecialchars($l['discipline']) ?> (<?= htmlspecialchars($l['type_raw']) ?>)<?= $l['audience'] ? ' · ауд. ' . htmlspecialchars($l['audience']) : '' ?><?= $l['teacher_name'] ? ' · ' . htmlspecialchars($l['teacher_name']) : '' ?>">
                                <div class="sched-card-title"><?= htmlspecialchars($l['discipline']) ?></div>
                                <div class="sched-card-meta">
                                    <?php if (!empty($l['audience'])): ?>
                                        <span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($l['audience']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($l['subgroup']) && $l['subgroup'] !== '0'): ?>
                                        <span class="sched-card-sg"><?= htmlspecialchars($l['subgroup']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <i class="bi <?= $icon ?> sched-card-type"></i>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                <?php endfor; ?>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Легенда -->
    <div class="sched-legend">
        <span class="sched-legend-item"><span class="sched-legend-dot" style="background:#0071e3;"></span> Лекция</span>
        <span class="sched-legend-item"><span class="sched-legend-dot" style="background:#30d158;"></span> Практика</span>
        <span class="sched-legend-item"><span class="sched-legend-dot" style="background:#5ac8fa;"></span> Лабораторная</span>
        <span class="sched-legend-item"><span class="sched-legend-dot" style="background:#ff9f0a;"></span> Семинар</span>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>