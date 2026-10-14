<?php
$pageTitle = 'Мои оценки';
require_once __DIR__ . '/includes/init.php';

// ============================================================
// Все занятия группы + посещаемость
// ============================================================
$stmt = $pdo->prepare("
    SELECT l.id, l.lesson_date, l.lesson_time, l.lesson_number,
           l.discipline, l.lesson_type,
           u.full_name AS teacher_name,
           a.is_present
    FROM lessons l
    LEFT JOIN users u ON l.user_id = u.id
    LEFT JOIN attendance a ON a.lesson_id = l.id AND a.student_id = ?
    WHERE l.group_id = ?
    ORDER BY l.lesson_date DESC, l.lesson_time DESC
");
$stmt->execute([$studentId, $groupId]);
$lessons = $stmt->fetchAll();

// ============================================================
// Работы студента
// ============================================================
$stmt = $pdo->prepare("
    SELECT lesson_id, work_number, marked_at
    FROM submitted_works
    WHERE student_id = ?
    ORDER BY marked_at
");
$stmt->execute([$studentId]);
$worksByLesson = [];
foreach ($stmt->fetchAll() as $w) {
    $worksByLesson[$w['lesson_id']][] = $w;
}

// ============================================================
// Группировка по предметам
// ============================================================
$bySubject = [];
foreach ($lessons as $l) {
    $s = $l['discipline'];
    if (!isset($bySubject[$s])) {
        $bySubject[$s] = [
            'name' => $s,
            'lessons' => [],
            'present' => 0,
            'absent' => 0,
            'unmarked' => 0,
            'works' => [],
        ];
    }
    $l['works'] = $worksByLesson[$l['id']] ?? [];
    $bySubject[$s]['lessons'][] = $l;

    if ($l['is_present'] === null) $bySubject[$s]['unmarked']++;
    elseif ((int)$l['is_present'] === 1) $bySubject[$s]['present']++;
    else $bySubject[$s]['absent']++;

    foreach ($l['works'] as $w) {
        $bySubject[$s]['works'][] = $w;
    }
}

// ============================================================
// Проценты
// ============================================================
foreach ($bySubject as &$s) {
    $marked = $s['present'] + $s['absent'];
    $s['pct'] = $marked > 0 ? round($s['present'] / $marked * 100) : null;
    $s['marked'] = $marked;
    $s['total'] = count($s['lessons']);
    $s['works_count'] = count($s['works']);
}
unset($s);

// Проблемные сверху
uasort($bySubject, function($a, $b) {
    if ($a['pct'] === null) return 1;
    if ($b['pct'] === null) return -1;
    return $a['pct'] <=> $b['pct'];
});

$typeLabels = [
    'lecture'  => 'Лекция',
    'practice' => 'Практика',
    'lab'      => 'Лабораторная',
    'seminar'  => 'Семинар',
    'other'    => 'Другое',
];

require_once __DIR__ . '/includes/header.php';
?>

<h2 style="font-size:1.4rem;font-weight:700;letter-spacing:-0.02em;margin:0 0 8px;">
    <i class="bi bi-journal-check"></i> Мои оценки и посещаемость
</h2>
<p style="color:#86868b;font-size:0.88rem;margin:0 0 20px;">
    Данные от преподавателей — посещаемость, работы.
    Предметы отсортированы по посещаемости (проблемные сверху).
</p>

<?php if (empty($bySubject)): ?>

    <div class="st-card" style="text-align:center;color:#86868b;padding:40px;">
        <i class="bi bi-journal-x" style="font-size:2.5rem;opacity:0.3;display:block;margin-bottom:12px;"></i>
        <h4 style="font-size:1rem;font-weight:700;color:#1d1d1f;margin:0 0 6px;">
            Пока нет данных
        </h4>
        <p style="margin:0;font-size:0.88rem;">
            Как только преподаватели начнут отмечать посещаемость — здесь появятся твои предметы.
        </p>
    </div>

<?php else: foreach ($bySubject as $s):
    $pct = $s['pct'];
    $pctColor = $pct === null ? '#86868b'
        : ($pct >= 75 ? '#30d158' : ($pct >= 50 ? '#ff9f0a' : '#ff453a'));
    $pctBg = $pct === null ? 'rgba(134,134,139,0.15)'
        : ($pct >= 75 ? 'rgba(48,209,88,0.15)' : ($pct >= 50 ? 'rgba(255,159,10,0.15)' : 'rgba(255,69,58,0.15)'));
?>
    <div class="st-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:0;">
                <h3 style="font-size:1.05rem;font-weight:700;margin:0 0 8px;">
                    <?= htmlspecialchars($s['name']) ?>
                </h3>
                <div style="font-size:0.82rem;color:#86868b;display:flex;gap:14px;flex-wrap:wrap;">
                    <span><i class="bi bi-calendar3"></i> <?= $s['total'] ?> занятий</span>
                    <span style="color:#30d158;"><i class="bi bi-check-circle-fill"></i> <?= $s['present'] ?></span>
                    <span style="color:#ff453a;"><i class="bi bi-x-circle-fill"></i> <?= $s['absent'] ?></span>
                    <?php if ($s['unmarked'] > 0): ?>
                        <span style="color:#86868b;"><i class="bi bi-dash-circle"></i> <?= $s['unmarked'] ?> без отметки</span>
                    <?php endif; ?>
                    <span style="color:#bf5af2;"><i class="bi bi-journal-check"></i> <?= $s['works_count'] ?> работ</span>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:1.6rem;font-weight:700;color:<?= $pctColor ?>;line-height:1;">
                    <?= $pct === null ? '—' : $pct . '%' ?>
                </div>
                <div style="font-size:0.72rem;color:#86868b;">посещаемость</div>
            </div>
        </div>

        <?php if ($pct !== null): ?>
        <div style="margin-top:14px;height:6px;background:#f0f0f2;border-radius:3px;overflow:hidden;">
            <div style="height:100%;width:<?= $pct ?>%;background:<?= $pctColor ?>;border-radius:3px;transition:width 0.6s;"></div>
        </div>
        <?php endif; ?>

        <!-- Занятия -->
        <details style="margin-top:14px;">
            <summary style="cursor:pointer;font-size:0.85rem;color:#0071e3;font-weight:600;list-style:none;padding:4px 0;">
                <i class="bi bi-chevron-down"></i> Показать занятия (<?= $s['total'] ?>)
            </summary>
            <div style="margin-top:10px;border-top:1px solid #f0f0f2;padding-top:10px;">
                <?php foreach ($s['lessons'] as $l):
                    if ($l['is_present'] === null) {
                        $badge = '<span style="background:rgba(134,134,139,0.15);color:#495057;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600;">—</span>';
                    } elseif ((int)$l['is_present'] === 1) {
                        $badge = '<span style="background:rgba(48,209,88,0.15);color:#198754;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600;">был</span>';
                    } else {
                        $badge = '<span style="background:rgba(255,69,58,0.15);color:#842029;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600;">пропуск</span>';
                    }
                ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:0.84rem;border-bottom:1px solid #fafafc;flex-wrap:wrap;">
                        <span style="color:#86868b;font-variant-numeric:tabular-nums;min-width:80px;">
                            <?= date('d.m.Y', strtotime($l['lesson_date'])) ?>
                        </span>
                        <span style="color:#86868b;min-width:50px;">
                            <?= $l['lesson_time'] ? substr($l['lesson_time'], 0, 5) : '—' ?>
                        </span>
                        <span style="color:#86868b;font-size:0.78rem;">
                            <?= htmlspecialchars($typeLabels[$l['lesson_type']] ?? '—') ?>
                        </span>
                        <span style="flex:1;color:#86868b;font-size:0.78rem;min-width:100px;">
                            <?= htmlspecialchars($l['teacher_name'] ?? '') ?>
                        </span>
                        <?php if (!empty($l['works'])): ?>
                            <span style="font-size:0.72rem;color:#0071e3;">
                                <?php foreach ($l['works'] as $w): ?>
                                    <span style="background:rgba(0,113,227,0.1);padding:1px 6px;border-radius:6px;margin-right:3px;">
                                        <?= htmlspecialchars($w['work_number']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </span>
                        <?php endif; ?>
                        <?= $badge ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>
    </div>
<?php endforeach; endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>