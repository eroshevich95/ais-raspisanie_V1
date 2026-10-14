<?php
$pageTitle = 'Посещаемость';
require_once 'config/database.php';

// ============================================================
// Сессия
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int)$_SESSION['user_id'];
$today  = date('Y-m-d');

// ============================================================
// СОХРАНЕНИЕ ОТМЕТОК
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    require_once 'includes/audit.php';

    $lessonId = (int)$_POST['lesson_id'];
    $marks    = $_POST['present'] ?? [];

    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ? AND user_id = ?");
    $stmt->execute([$lessonId, $userId]);
    $lesson = $stmt->fetch();

    if (!$lesson) {
        redirectWith('attendance.php', 'error', 'Занятие не найдено');
    }

    // ---- Ищем ОДНО каноничное занятие ----
    $stmt = $pdo->prepare("
        SELECT MIN(id) FROM lessons
        WHERE user_id = ?
          AND lesson_date = ?
          AND lesson_number <=> ?
          AND discipline = ?
          AND lesson_type = ?
          AND COALESCE(audience, '') = COALESCE(?, '')
          AND COALESCE(subgroup, '0') = COALESCE(?, '0')
    ");
    $stmt->execute([
        $userId,
        $lesson['lesson_date'],
        $lesson['lesson_number'],
        $lesson['discipline'],
        $lesson['lesson_type'],
        $lesson['audience'],
        $lesson['subgroup'],
    ]);
    $canonicalId = (int)$stmt->fetchColumn();
    if (!$canonicalId) $canonicalId = $lessonId;

    // ---- Все студенты потока ----
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.id
        FROM students s
        JOIN lessons l ON l.group_id = s.group_id
        WHERE l.id = ?
    ");
    $stmt->execute([$canonicalId]);
    $allowedStudentIds = array_column($stmt->fetchAll(), 'id');

    // ---- Карта ФИО студентов ----
    $studentsMap = [];
    if (!empty($allowedStudentIds)) {
        $in = implode(',', array_fill(0, count($allowedStudentIds), '?'));
        $stmt = $pdo->prepare("SELECT id, full_name FROM students WHERE id IN ($in)");
        $stmt->execute($allowedStudentIds);
        foreach ($stmt->fetchAll() as $s) {
            $studentsMap[(int)$s['id']] = $s['full_name'];
        }
    }

    // ---- Старые отметки ----
    $oldMarks = [];
    $stmt = $pdo->prepare("
        SELECT student_id, is_present FROM attendance WHERE lesson_id = ?
    ");
    $stmt->execute([$canonicalId]);
    foreach ($stmt->fetchAll() as $r) {
        $oldMarks[(int)$r['student_id']] = (int)$r['is_present'];
    }

    // ---- Статистика для лога ----
    $stat = [
        'total_old' => count($oldMarks),
        'total_new' => 0,
        'present' => 0,
        'absent' => 0,
        'changed' => 0,
    ];

    // ---- Применяем новые отметки ----
    $pdo->beginTransaction();
    try {
        // Чистим чужих
        if (!empty($allowedStudentIds)) {
            $in = implode(',', array_fill(0, count($allowedStudentIds), '?'));
            $pdo->prepare("
                DELETE FROM attendance
                WHERE lesson_id = ? AND student_id NOT IN ($in)
            ")->execute(array_merge([$canonicalId], $allowedStudentIds));
        }

        $stmtUpsert = $pdo->prepare("
            INSERT INTO attendance (lesson_id, student_id, is_present)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE is_present = VALUES(is_present), updated_at = NOW()
        ");
        $stmtDelete = $pdo->prepare("
            DELETE FROM attendance WHERE lesson_id = ? AND student_id = ?
        ");

        $markedCount = 0;

        foreach ($marks as $sid => $val) {
            $sid = (int)$sid;
            if ($val !== '0' && $val !== '1') {
                // Сброс
                if (isset($oldMarks[$sid])) {
                    $stat['changed']++;
                }
                $stmtDelete->execute([$canonicalId, $sid]);
                continue;
            }
            if (!in_array($sid, $allowedStudentIds)) continue;

            $newVal = (int)$val;
            $oldVal = $oldMarks[$sid] ?? null;

            if ($oldVal !== $newVal) {
                $stat['changed']++;
            }

            $stmtUpsert->execute([$canonicalId, $sid, $newVal]);
            $markedCount++;
            $stat['total_new']++;

            if ($newVal === 1) $stat['present']++;
            else $stat['absent']++;
        }

        // ============================================================
        // ОДНА запись в audit_log на всё сохранение (вариант B)
        // ============================================================
        $lessonTitle = $lesson['discipline'];
        $lessonDate  = date('d.m.Y', strtotime($lesson['lesson_date']));
        $lessonTime  = $lesson['lesson_time'] ? substr($lesson['lesson_time'], 0, 5) : '';
        $groupName   = $currentLesson['group_name'] ?? '';  // ещё не загружен — берём из $lesson['group_id']

        // Имя группы достаём здесь же
        $grpStmt = $pdo->prepare("SELECT name FROM groups_table WHERE id = ?");
        $grpStmt->execute([$lesson['group_id'] ?? 0]);
        $groupName = $grpStmt->fetchColumn() ?: '—';

        $desc = "{$lessonTitle} ({$lessonDate} {$lessonTime}) · группа {$groupName}";
        if ($stat['changed'] > 0) {
            $desc .= ' · изменений: ' . $stat['changed'];
        }

        $oldValue = $stat['total_old'] > 0
            ? "отмечено ранее: {$stat['total_old']}"
            : 'не отмечено';

        $newValue = "было: {$stat['present']}, пропустили: {$stat['absent']} (всего: {$stat['total_new']})";

        auditLog($pdo, 'attendance.update', [
            'entity_type' => 'lesson',
            'entity_id'   => $canonicalId,
            'description' => $desc,
            'old_value'   => $oldValue,
            'new_value'   => $newValue,
        ]);

        $pdo->commit();

        redirectWith(
            'attendance.php?lesson_id=' . $lessonId,
            'success',
            "Сохранено: {$markedCount} отметок"
            . ($stat['changed'] > 0 ? " · изменений: {$stat['changed']}" : '')
        );

    } catch (Exception $e) {
        $pdo->rollBack();
        redirectWith('attendance.php?lesson_id=' . $lessonId, 'error',
            'Ошибка сохранения: ' . $e->getMessage());
    }
}

$success = getFlash('success') ?? '';
$error   = getFlash('error') ?? '';

// ============================================================
// ТЕКУЩЕЕ ЗАНЯТИЕ
// ============================================================
$lessonId = (int)($_GET['lesson_id'] ?? 0);
$currentLesson = null;
$students = [];
$marks = [];
$streamLessons = [];
$streamGroupIds = [];
$subgroupCounts = ['1' => 0, '2' => 0, 'none' => 0];
$hasAnySubgroups = false;
$lessonSubgroup = null;

if ($lessonId) {
    $stmt = $pdo->prepare("
        SELECT
            l.*,
            g.name AS group_name,
            (SELECT COUNT(DISTINCT l2.group_id)
             FROM lessons l2
             WHERE l2.user_id = l.user_id
               AND l2.lesson_date = l.lesson_date
               AND l2.lesson_number <=> l.lesson_number
               AND l2.discipline = l.discipline
               AND l2.lesson_type = l.lesson_type
               AND COALESCE(l2.audience, '') = COALESCE(l.audience, '')
               AND COALESCE(l2.subgroup, '0') = COALESCE(l.subgroup, '0')) AS groups_count
        FROM lessons l
        LEFT JOIN groups_table g ON l.group_id = g.id
        WHERE l.id = ? AND l.user_id = ?
    ");
    $stmt->execute([$lessonId, $userId]);
    $currentLesson = $stmt->fetch();

    if ($currentLesson) {
        if (!empty($currentLesson['subgroup']) && $currentLesson['subgroup'] !== '0') {
            $lessonSubgroup = (string)$currentLesson['subgroup'];
        }

        $stmt = $pdo->prepare("
            SELECT id, group_id FROM lessons
            WHERE user_id = ?
              AND lesson_date = ?
              AND lesson_number <=> ?
              AND discipline = ?
              AND lesson_type = ?
              AND COALESCE(audience, '') = COALESCE(?, '')
              AND COALESCE(subgroup, '0') = COALESCE(?, '0')
        ");
        $stmt->execute([
            $userId,
            $currentLesson['lesson_date'],
            $currentLesson['lesson_number'],
            $currentLesson['discipline'],
            $currentLesson['lesson_type'],
            $currentLesson['audience'],
            $currentLesson['subgroup'],
        ]);
        $streamLessons = $stmt->fetchAll();
        $streamGroupIds = array_filter(array_unique(array_column($streamLessons, 'group_id')));
        $lessonIds = array_column($streamLessons, 'id');

        if (!empty($lessonIds) && !empty($streamGroupIds)) {
            $inLessons = implode(',', array_fill(0, count($lessonIds), '?'));
            $inGroups  = implode(',', array_fill(0, count($streamGroupIds), '?'));

            $stmt = $pdo->prepare("
                SELECT
                    s.id,
                    s.full_name,
                    s.zachetka,
                    s.subgroup,
                    g.name AS group_name,
                    MAX(a.is_present) AS is_present
                FROM students s
                JOIN groups_table g ON s.group_id = g.id
                LEFT JOIN attendance a
                    ON a.student_id = s.id
                    AND a.lesson_id IN ($inLessons)
                WHERE s.group_id IN ($inGroups)
                GROUP BY s.id
                ORDER BY g.name, s.full_name
            ");
            $stmt->execute(array_merge($lessonIds, array_values($streamGroupIds)));
            $students = $stmt->fetchAll();

            foreach ($students as $s) {
                $marks[$s['id']] = $s['is_present'] !== null ? (int)$s['is_present'] : null;
            }

            foreach ($students as $s) {
                $sg = $s['subgroup'] ?: 'none';
                $subgroupCounts[$sg] = ($subgroupCounts[$sg] ?? 0) + 1;
                if ($sg !== 'none') $hasAnySubgroups = true;
            }
        }
    }
}

// ============================================================
// ЗАНЯТИЯ НА СЕГОДНЯ
// ============================================================
$stmt = $pdo->prepare("
    SELECT
        MIN(l.id) AS id,
        l.lesson_number,
        l.lesson_time,
        l.discipline,
        l.lesson_type,
        l.audience,
        l.subgroup,
        GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS group_name,
        COUNT(DISTINCT l.group_id) AS groups_count,
        (SELECT COUNT(*) FROM attendance a
         WHERE a.lesson_id IN (
             SELECT id FROM lessons l2
             WHERE l2.user_id = l.user_id
               AND l2.lesson_date = l.lesson_date
               AND l2.lesson_number <=> l.lesson_number
               AND l2.discipline = l.discipline
               AND COALESCE(l2.audience, '') = COALESCE(l.audience, '')
               AND COALESCE(l2.subgroup, '0') = COALESCE(l.subgroup, '0')
         )) AS marked_count
    FROM lessons l
    LEFT JOIN groups_table g ON l.group_id = g.id
    WHERE l.user_id = ? AND l.lesson_date = ?
    GROUP BY
        l.lesson_date,
        l.lesson_number,
        l.discipline,
        l.lesson_type,
        l.audience,
        COALESCE(l.subgroup, '0')
    ORDER BY l.lesson_time, l.lesson_number
");
$stmt->execute([$userId, $today]);
$todayLessons = $stmt->fetchAll();

// ============================================================
// ВСЕ ЗАНЯТИЯ (модалка)
// ============================================================
$stmt = $pdo->prepare("
    SELECT
        MIN(l.id) AS id,
        l.lesson_date,
        l.lesson_time,
        l.lesson_number,
        l.discipline,
        l.lesson_type,
        l.audience,
        l.subgroup,
        GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS group_name,
        COUNT(DISTINCT l.group_id) AS groups_count
    FROM lessons l
    LEFT JOIN groups_table g ON l.group_id = g.id
    WHERE l.user_id = ?
    GROUP BY
        l.lesson_date,
        l.lesson_number,
        l.discipline,
        l.lesson_type,
        l.audience,
        COALESCE(l.subgroup, '0')
    ORDER BY l.lesson_date DESC, l.lesson_time DESC, l.lesson_number
    LIMIT 200
");
$stmt->execute([$userId]);
$allLessons = $stmt->fetchAll();

$allByDate = [];
foreach ($allLessons as $l) $allByDate[$l['lesson_date']][] = $l;

$currentLessonNum = getCurrentLessonNumber();

$typeLabelsFull = [
    'lecture'  => ['label' => 'Лекция',       'color' => 'primary', 'icon' => 'bi-book'],
    'practice' => ['label' => 'Практика',     'color' => 'success', 'icon' => 'bi-pencil'],
    'lab'      => ['label' => 'Лабораторная', 'color' => 'info',    'icon' => 'bi-flask'],
    'seminar'  => ['label' => 'Семинар',      'color' => 'warning', 'icon' => 'bi-people'],
    'other'    => ['label' => 'Другое',       'color' => 'secondary','icon' => 'bi-journal'],
];

require_once 'includes/header.php';
?>

<style>
/* ============================================================
   ATTENDANCE
   ============================================================ */
.att-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.att-header h2 {
    font-size: 1.7rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 6px;
}
.att-header p { color: #86868b; margin: 0; font-size: 0.95rem; }

.att-layout {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 20px;
}
@media (max-width: 991px) { .att-layout { grid-template-columns: 1fr; } }

.att-sidebar {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 220px);
    position: sticky;
    top: 90px;
}
@media (max-width: 991px) { .att-sidebar { position: static; max-height: none; } }
.att-sidebar-header {
    padding: 16px 20px;
    border-bottom: 1px solid #f0f0f2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #f5f5f7 0%, #fff 100%);
}
.att-sidebar-title {
    font-size: 0.95rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #1d1d1f;
}
.att-sidebar-title i { color: #0071e3; }

.att-lessons {
    overflow-y: auto;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.att-lesson {
    padding: 12px 14px;
    border-radius: 12px;
    background: #fafafc;
    border: 1.5px solid #f0f0f2;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
    position: relative;
    overflow: hidden;
    display: block;
}
.att-lesson::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: #0071e3;
}
.att-lesson:hover {
    background: #fff;
    border-color: #0071e3;
    transform: translateX(3px);
    box-shadow: 0 4px 12px rgba(0,113,227,0.12);
    color: inherit;
}
.att-lesson--active {
    background: linear-gradient(135deg, #e7f1ff 0%, #fff 100%);
    border-color: #0071e3;
    box-shadow: 0 4px 16px rgba(0,113,227,0.2);
}
.att-lesson--active::before { width: 4px; }
.att-lesson--current {
    background: linear-gradient(135deg, #f0faf3 0%, #fff 100%);
    border-color: #30d158;
    animation: attPulse 2.4s ease-in-out infinite;
}
@keyframes attPulse {
    0%, 100% { box-shadow: 0 4px 12px rgba(48, 209, 88, 0.15), 0 0 0 0 rgba(48, 209, 88, 0.4); }
    50%      { box-shadow: 0 6px 20px rgba(48, 209, 88, 0.3), 0 0 0 8px rgba(48, 209, 88, 0); }
}
.att-lesson--current::before { background: #30d158; }

.att-lesson-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
    gap: 8px;
}
.att-lesson-time {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #1d1d1f;
    font-variant-numeric: tabular-nums;
}
.att-lesson-time i { font-size: 0.75rem; color: #0071e3; }
.att-lesson-badges { display: flex; gap: 4px; flex-wrap: wrap; }
.att-lesson-badge {
    font-size: 0.6rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
}
.att-lesson-badge--current {
    background: #30d158;
    color: #fff;
    position: relative;
    padding-left: 16px;
}
.att-lesson-badge--current::before {
    content: '';
    position: absolute;
    left: 5px; top: 50%;
    transform: translateY(-50%);
    width: 6px; height: 6px;
    background: #fff;
    border-radius: 50%;
    animation: attDot 1.5s ease-in-out infinite;
}
@keyframes attDot {
    0%,100% { transform: translateY(-50%) scale(1); }
    50%     { transform: translateY(-50%) scale(1.3); }
}
.att-lesson-badge--marked { background: #d1e7dd; color: #0f5132; }
.att-lesson-badge--stream {
    background: linear-gradient(135deg, #cfe2ff 0%, #d6d0ff 100%);
    color: #084298;
}
.att-lesson-badge--sg1 { background: rgba(0,113,227,0.15); color: #0071e3; }
.att-lesson-badge--sg2 { background: rgba(191,90,242,0.15); color: #bf5af2; }

.att-lesson-title {
    font-size: 0.85rem;
    font-weight: 600;
    line-height: 1.3;
    color: #1d1d1f;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.att-lesson-meta {
    font-size: 0.72rem;
    color: #86868b;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.att-lesson-meta i { color: #0071e3; }
.att-lesson-meta span { display: inline-flex; align-items: center; gap: 3px; }

.att-empty { padding: 32px 20px; text-align: center; color: #86868b; font-size: 0.85rem; }
.att-empty i { font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 8px; }

.att-sidebar-footer {
    padding: 12px 16px;
    border-top: 1px solid #f0f0f2;
    background: #fafafc;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.att-sidebar-btn {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 16px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    border: none;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.25s;
    box-shadow: 0 6px 16px rgba(0,113,227,0.25);
    text-decoration: none;
}
.att-sidebar-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,113,227,0.4); color: #fff; }
.att-sidebar-btn--subgroups {
    background: linear-gradient(135deg, #bf5af2 0%, #0071e3 100%);
    box-shadow: 0 6px 16px rgba(191,90,242,0.25);
}
.att-sidebar-btn--subgroups:hover { box-shadow: 0 10px 24px rgba(191,90,242,0.4); }

.att-main { display: flex; flex-direction: column; gap: 16px; }

.att-placeholder {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    padding: 60px 24px;
    text-align: center;
}
.att-placeholder-icon {
    width: 80px; height: 80px;
    margin: 0 auto 20px;
    border-radius: 20px;
    background: linear-gradient(135deg, #e7f1ff 0%, #d6d0ff 100%);
    color: #0071e3;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
}
.att-placeholder h4 { font-size: 1.2rem; font-weight: 700; margin: 0 0 8px; }
.att-placeholder p { color: #86868b; margin: 0; font-size: 0.9rem; }

.att-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    overflow: hidden;
}
.att-card-header {
    padding: 20px 24px;
    background: linear-gradient(135deg, #fafafc 0%, #fff 100%);
    border-bottom: 1px solid #f0f0f2;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
}
.att-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 6px;
    line-height: 1.3;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.att-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.82rem;
    color: #86868b;
    align-items: center;
}
.att-card-meta span { display: inline-flex; align-items: center; gap: 4px; }
.att-card-meta i { color: #0071e3; }

.att-subgroup-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(191,90,242,0.1) 0%, rgba(0,113,227,0.1) 100%);
    color: #bf5af2;
    border: 1.5px solid rgba(191,90,242,0.3);
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.25s;
    white-space: nowrap;
    flex-shrink: 0;
}
.att-subgroup-link:hover {
    background: linear-gradient(135deg, #bf5af2 0%, #0071e3 100%);
    color: #fff;
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(191,90,242,0.35);
}
.att-subgroup-link i { font-size: 1rem; }
.att-subgroup-link-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    background: rgba(255,255,255,0.3);
    border-radius: 11px;
    font-size: 0.72rem;
    font-variant-numeric: tabular-nums;
}

.att-counters { display: flex; gap: 8px; flex-wrap: wrap; }
.att-counter {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}
.att-counter--present { background: rgba(48,209,88,0.12); color: #198754; }
.att-counter--absent  { background: rgba(255,69,58,0.12); color: #842029; }
.att-counter--unmarked { background: rgba(134,134,139,0.12); color: #495057; }

.att-info {
    padding: 14px 24px;
    background: rgba(0,113,227,0.06);
    border-bottom: 1px solid #f0f0f2;
    font-size: 0.85rem;
    color: #084298;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.5;
}
.att-info > i { font-size: 1.1rem; flex-shrink: 0; margin-top: 2px; }
.att-info a { color: #0071e3; font-weight: 700; }
.att-info a:hover { color: #0056b3; }

.att-sg-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    margin: 0 2px;
    font-variant-numeric: tabular-nums;
}
.att-sg-pill--1 { background: rgba(0,113,227,0.15); color: #0071e3; }
.att-sg-pill--2 { background: rgba(191,90,242,0.15); color: #bf5af2; }
.att-sg-pill--none { background: rgba(134,134,139,0.15); color: #495057; }

.att-sg-selector {
    padding: 14px 24px;
    border-bottom: 1px solid #f0f0f2;
    background: linear-gradient(135deg, #fafafc 0%, #fff 100%);
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
}
.att-sg-selector-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #1d1d1f;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.att-sg-selector-label i { color: #0071e3; font-size: 1rem; }
.att-sg-selector-tabs {
    display: flex;
    gap: 6px;
    padding: 4px;
    background: #f0f0f2;
    border-radius: 12px;
    flex-wrap: wrap;
}
.att-sg-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    background: transparent;
    border: none;
    color: #86868b;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    white-space: nowrap;
}
.att-sg-tab:hover { background: rgba(255,255,255,0.7); color: #1d1d1f; }
.att-sg-tab--active {
    background: #fff;
    color: #0071e3;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.att-sg-tab .count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: rgba(0,0,0,0.06);
    border-radius: 10px;
    font-size: 0.7rem;
    font-variant-numeric: tabular-nums;
}
.att-sg-tab--active .count { background: rgba(0,113,227,0.15); color: #0071e3; }
.att-sg-tab--1.att-sg-tab--active { color: #0071e3; }
.att-sg-tab--1.att-sg-tab--active .count { background: rgba(0,113,227,0.15); }
.att-sg-tab--2.att-sg-tab--active { color: #bf5af2; }
.att-sg-tab--2.att-sg-tab--active .count { background: rgba(191,90,242,0.15); }
.att-sg-tab--all.att-sg-tab--active { color: #1d1d1f; }
.att-sg-tab--all.att-sg-tab--active .count { background: rgba(0,0,0,0.08); }

.att-sg-tab .rec-dot {
    position: absolute;
    top: 4px; right: 4px;
    width: 8px; height: 8px;
    background: #ff453a;
    border-radius: 50%;
    box-shadow: 0 0 0 2px #fff;
    animation: recPulse 2s ease-in-out infinite;
}
@keyframes recPulse {
    0%, 100% { box-shadow: 0 0 0 2px #fff, 0 0 0 0 rgba(255, 69, 58, 0.5); }
    50%      { box-shadow: 0 0 0 2px #fff, 0 0 0 6px rgba(255, 69, 58, 0); }
}
.att-sg-hint {
    font-size: 0.75rem;
    color: #86868b;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-left: auto;
}
.att-sg-hint i { color: #0071e3; }
.att-sg-hint strong { color: #0071e3; }

.att-actions-panel {
    padding: 14px 24px;
    border-bottom: 1px solid #f0f0f2;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}
.att-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    border: 1.5px solid;
    background: #fff;
}
.att-action-btn--success { color: #198754; border-color: #198754; }
.att-action-btn--success:hover { background: #198754; color: #fff; transform: translateY(-2px); }
.att-action-btn--danger { color: #dc3545; border-color: #dc3545; }
.att-action-btn--danger:hover { background: #dc3545; color: #fff; transform: translateY(-2px); }
.att-action-btn--secondary { color: #495057; border-color: #d2d2d7; }
.att-action-btn--secondary:hover { background: #495057; color: #fff; border-color: #495057; transform: translateY(-2px); }

.att-students { padding: 8px; }
.att-student {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border-radius: 12px;
    transition: all 0.2s;
    border-bottom: 1px solid #f5f5f7;
}
.att-student:last-child { border-bottom: none; }
.att-student:hover { background: #fafafc; }
.att-student--present { background: linear-gradient(90deg, rgba(48,209,88,0.08) 0%, transparent 100%); }
.att-student--absent  { background: linear-gradient(90deg, rgba(255,69,58,0.08) 0%, transparent 100%); }

.att-student-num {
    width: 28px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #86868b;
    text-align: right;
    flex-shrink: 0;
}
.att-student-info { flex: 1; min-width: 0; }
.att-student-name {
    font-size: 0.92rem;
    font-weight: 600;
    color: #1d1d1f;
    margin-bottom: 3px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.att-student-meta {
    font-size: 0.75rem;
    color: #86868b;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}
.att-student-meta code {
    background: rgba(0,113,227,0.08);
    color: #0071e3;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-family: "SF Mono", Monaco, monospace;
}
.att-student-meta .att-student-group {
    color: #86868b;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.att-sg-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    white-space: nowrap;
}
.att-sg-badge--1 { background: rgba(0,113,227,0.15); color: #0071e3; }
.att-sg-badge--2 { background: rgba(191,90,242,0.15); color: #bf5af2; }

.att-choices { display: flex; gap: 6px; flex-shrink: 0; }
.att-choice {
    position: relative;
    width: 42px; height: 42px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    border: 2px solid #e5e5ea;
    background: #fff;
    color: #86868b;
    font-size: 1.15rem;
    transition: all 0.2s cubic-bezier(0.16,1,0.3,1);
    user-select: none;
}
.att-choice:hover { transform: scale(1.08); border-color: #1d1d1f; color: #1d1d1f; }
.att-choice input { position: absolute; opacity: 0; pointer-events: none; }
.att-choice--present input:checked ~ .att-choice-inner {
    background: #30d158;
    border-color: #30d158;
    color: #fff;
    box-shadow: 0 4px 12px rgba(48, 209, 88, 0.4);
}
.att-choice--absent input:checked ~ .att-choice-inner {
    background: #ff453a;
    border-color: #ff453a;
    color: #fff;
    box-shadow: 0 4px 12px rgba(255, 69, 58, 0.4);
}
.att-choice-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%; height: 100%;
    border-radius: 10px;
    transition: all 0.2s;
}

.att-card-footer {
    padding: 16px 24px;
    border-top: 1px solid #f0f0f2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    background: #fafafc;
}
.att-footer-hint {
    font-size: 0.78rem;
    color: #86868b;
    display: flex;
    align-items: center;
    gap: 6px;
}
.att-save-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    border: none;
    font-size: 0.92rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.25s;
    box-shadow: 0 6px 16px rgba(0,113,227,0.3);
}
.att-save-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(0,113,227,0.45); }

.att-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(10px);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    animation: fadeIn 0.3s;
}
.att-modal-backdrop.show { display: flex; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.att-modal {
    background: #fff;
    border-radius: 24px;
    width: 100%;
    max-width: 640px;
    max-height: 85vh;
    box-shadow: 0 40px 100px rgba(0,0,0,0.4);
    animation: modalIn 0.4s cubic-bezier(0.16,1,0.3,1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.att-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    background: linear-gradient(135deg, #fafafc 0%, #fff 100%);
}
.att-modal-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
.att-modal-header h5 i { color: #0071e3; }
.att-modal-close {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #f5f5f7;
    border: none;
    color: #1d1d1f;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}
.att-modal-close:hover { background: #0071e3; color: #fff; transform: rotate(90deg); }
.att-modal-search {
    padding: 16px 24px;
    border-bottom: 1px solid #f0f0f2;
    background: #fafafc;
}
.att-modal-search input {
    width: 100%;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1.5px solid #e5e5ea;
    background: #fff;
    font-size: 0.9rem;
    font-family: inherit;
    outline: none;
    transition: all 0.2s;
}
.att-modal-search input:focus { border-color: #0071e3; box-shadow: 0 0 0 4px rgba(0,113,227,0.12); }
.att-modal-body { overflow-y: auto; flex: 1; }

.att-date-group {
    background: #fafafc;
    padding: 10px 24px;
    border-bottom: 1px solid #f0f0f2;
    font-size: 0.82rem;
    font-weight: 700;
    color: #1d1d1f;
    position: sticky;
    top: 0;
    z-index: 1;
    display: flex;
    justify-content: space-between;
}
.att-date-group span:last-child { font-weight: 500; color: #86868b; }
.att-modal-lesson {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 24px;
    text-decoration: none;
    color: inherit;
    border-bottom: 1px solid #f5f5f7;
    transition: all 0.15s;
}
.att-modal-lesson:hover { background: #f5f9ff; color: inherit; }
.att-modal-lesson--active { background: #e7f1ff; border-left: 4px solid #0071e3; padding-left: 20px; }
.att-modal-lesson-time {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 52px;
    padding: 5px 8px;
    border-radius: 8px;
    background: #1d1d1f;
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    flex-shrink: 0;
}
.att-modal-lesson-info { flex: 1; min-width: 0; }
.att-modal-lesson-title {
    font-size: 0.88rem;
    font-weight: 600;
    color: #1d1d1f;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.att-modal-lesson-meta {
    font-size: 0.75rem;
    color: #86868b;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.att-modal-lesson-meta i { color: #0071e3; }

[data-theme="dark"] .att-sidebar,
[data-theme="dark"] .att-card,
[data-theme="dark"] .att-placeholder,
[data-theme="dark"] .att-modal {
    background: #1c1c1e;
    border-color: #2c2c2e;
}
[data-theme="dark"] .att-sidebar-header,
[data-theme="dark"] .att-card-header,
[data-theme="dark"] .att-modal-header,
[data-theme="dark"] .att-modal-search {
    background: linear-gradient(135deg, #2c2c2e 0%, #1c1c1e 100%);
    border-color: #3c3c3e;
}
[data-theme="dark"] .att-lesson { background: #2c2c2e; border-color: #3c3c3e; }
[data-theme="dark"] .att-lesson:hover { background: #3c3c3e; color: inherit; }
[data-theme="dark"] .att-lesson--active { background: linear-gradient(135deg, rgba(0,113,227,0.2) 0%, #2c2c2e 100%); }
[data-theme="dark"] .att-lesson--current { background: linear-gradient(135deg, rgba(48,209,88,0.15) 0%, #2c2c2e 100%); }
[data-theme="dark"] .att-lesson-title,
[data-theme="dark"] .att-student-name,
[data-theme="dark"] .att-card-title,
[data-theme="dark"] .att-modal-lesson-title,
[data-theme="dark"] .att-placeholder h4 {
    color: #f5f5f7;
}
[data-theme="dark"] .att-lesson-time { color: #f5f5f7; }
[data-theme="dark"] .att-student:hover { background: #2c2c2e; }
[data-theme="dark"] .att-student { border-color: #2c2c2e; }
[data-theme="dark"] .att-choice { background: #2c2c2e; border-color: #3c3c3e; color: #a1a1a6; }
[data-theme="dark"] .att-card-footer { background: #141416; border-color: #2c2c2e; }
[data-theme="dark"] .att-modal-lesson { border-color: #2c2c2e; }
[data-theme="dark"] .att-modal-lesson:hover { background: #2c2c2e; color: inherit; }
[data-theme="dark"] .att-modal-lesson--active { background: rgba(0,113,227,0.15); }
[data-theme="dark"] .att-modal-close { background: #2c2c2e; color: #f5f5f7; }
[data-theme="dark"] .att-sidebar-footer { background: #141416; border-color: #2c2c2e; }
[data-theme="dark"] .att-date-group { background: #141416; color: #f5f5f7; border-color: #2c2c2e; }
[data-theme="dark"] .att-info { background: rgba(0,113,227,0.12); color: #6ea8ff; border-color: #2c2c2e; }
[data-theme="dark"] .att-info a { color: #6ea8ff; }
[data-theme="dark"] .att-subgroup-link {
    background: linear-gradient(135deg, rgba(191,90,242,0.15) 0%, rgba(0,113,227,0.15) 100%);
    color: #d69af7;
    border-color: rgba(191,90,242,0.4);
}
[data-theme="dark"] .att-subgroup-link:hover { color: #fff; }
[data-theme="dark"] .att-sg-pill--1 { background: rgba(0,113,227,0.2); color: #6ea8ff; }
[data-theme="dark"] .att-sg-pill--2 { background: rgba(191,90,242,0.2); color: #d69af7; }
[data-theme="dark"] .att-sg-pill--none { background: rgba(134,134,139,0.2); color: #b8b8bd; }
[data-theme="dark"] .att-sg-badge--1 { background: rgba(0,113,227,0.2); color: #6ea8ff; }
[data-theme="dark"] .att-sg-badge--2 { background: rgba(191,90,242,0.2); color: #d69af7; }
[data-theme="dark"] .att-sg-selector {
    background: linear-gradient(135deg, #2c2c2e 0%, #1c1c1e 100%);
    border-color: #3c3c3e;
}
[data-theme="dark"] .att-sg-selector-label { color: #f5f5f7; }
[data-theme="dark"] .att-sg-selector-tabs { background: #1c1c1e; }
[data-theme="dark"] .att-sg-tab:hover { background: rgba(255,255,255,0.08); color: #fff; }
[data-theme="dark"] .att-sg-tab--active { background: #2c2c2e; color: #6ea8ff; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
[data-theme="dark"] .att-sg-tab .count { background: rgba(255,255,255,0.1); color: #a1a1a6; }
[data-theme="dark"] .att-sg-tab--active .count { background: rgba(0,113,227,0.25); color: #6ea8ff; }
[data-theme="dark"] .att-sg-tab--1.att-sg-tab--active { color: #6ea8ff; }
[data-theme="dark"] .att-sg-tab--1.att-sg-tab--active .count { background: rgba(0,113,227,0.25); color: #6ea8ff; }
[data-theme="dark"] .att-sg-tab--2.att-sg-tab--active { color: #d69af7; }
[data-theme="dark"] .att-sg-tab--2.att-sg-tab--active .count { background: rgba(191,90,242,0.25); color: #d69af7; }
[data-theme="dark"] .att-sg-tab--all.att-sg-tab--active { color: #f5f5f7; }
[data-theme="dark"] .att-sg-tab .rec-dot { box-shadow: 0 0 0 2px #2c2c2e; }
[data-theme="dark"] .att-sg-hint { color: #a1a1a6; }
[data-theme="dark"] .att-sg-hint strong { color: #6ea8ff; }
</style>

<!-- Шапка -->
<div class="att-header">
    <div>
        <h2><i class="bi bi-check2-square"></i> Посещаемость</h2>
        <p>
            <i class="bi bi-calendar3"></i>
            <?= ruDayFull($today) ?>, <?= date('d.m.Y') ?>
            · <i class="bi bi-clock"></i> <?= date('H:i') ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Дашборд
        </a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="att-layout">

    <aside class="att-sidebar">
        <div class="att-sidebar-header">
            <div class="att-sidebar-title">
                <i class="bi bi-calendar-day"></i>
                Занятия на сегодня
            </div>
            <span class="badge bg-primary"><?= count($todayLessons) ?></span>
        </div>

        <div class="att-lessons">
            <?php if (empty($todayLessons)): ?>
                <div class="att-empty">
                    <i class="bi bi-calendar-x"></i>
                    <div>На сегодня занятий нет</div>
                </div>
            <?php else: foreach ($todayLessons as $l):
                $t = $typeLabelsFull[$l['lesson_type']] ?? $typeLabelsFull['other'];
                $isActive = $l['id'] == $lessonId;
                $isCurrent = $currentLessonNum && (int)$l['lesson_number'] === $currentLessonNum;
                $hasMarks = ($l['marked_count'] ?? 0) > 0;
                $isStream = ($l['groups_count'] ?? 1) > 1;
                $sgL = (!empty($l['subgroup']) && $l['subgroup'] !== '0') ? (string)$l['subgroup'] : null;

                $lessonClass = 'att-lesson';
                if ($isActive) $lessonClass .= ' att-lesson--active';
                elseif ($isCurrent) $lessonClass .= ' att-lesson--current';
            ?>
                <a href="?lesson_id=<?= $l['id'] ?>" class="<?= $lessonClass ?>">
                    <div class="att-lesson-top">
                        <span class="att-lesson-time">
                            <i class="bi bi-clock"></i>
                            <?= $l['lesson_time'] ? substr($l['lesson_time'], 0, 5) : '—' ?>
                            <?php if ($l['lesson_number']): ?>
                                · пара <?= $l['lesson_number'] ?>
                            <?php endif; ?>
                        </span>
                        <div class="att-lesson-badges">
                            <?php if ($isCurrent && !$isActive): ?>
                                <span class="att-lesson-badge att-lesson-badge--current">LIVE</span>
                            <?php endif; ?>
                            <?php if ($hasMarks): ?>
                                <span class="att-lesson-badge att-lesson-badge--marked">✓</span>
                            <?php endif; ?>
                            <?php if ($isStream): ?>
                                <span class="att-lesson-badge att-lesson-badge--stream">поток</span>
                            <?php endif; ?>
                            <?php if ($sgL): ?>
                                <span class="att-lesson-badge att-lesson-badge--sg<?= (int)$sgL ?>">
                                    подгр. <?= htmlspecialchars($sgL) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="att-lesson-title"><?= htmlspecialchars($l['discipline']) ?></div>
                    <div class="att-lesson-meta">
                        <span><i class="bi bi-people-fill"></i>
                            <?= htmlspecialchars($l['group_name'] ?? '—') ?>
                        </span>
                        <?php if (!empty($l['audience'])): ?>
                            <span><i class="bi bi-geo-alt-fill"></i>
                                <?= htmlspecialchars($l['audience']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; endif; ?>
        </div>

        <div class="att-sidebar-footer">
            <button class="att-sidebar-btn" onclick="openAttModal()">
                <i class="bi bi-search"></i> Выбрать другое занятие
            </button>
            <?php if ($currentLesson && $currentLesson['group_id']): ?>
                <a href="subgroups.php?group_id=<?= (int)$currentLesson['group_id'] ?>"
                   class="att-sidebar-btn att-sidebar-btn--subgroups">
                    <i class="bi bi-diagram-3"></i> Подгруппы группы
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <div class="att-main">

        <?php if (!$currentLesson): ?>
            <div class="att-placeholder">
                <div class="att-placeholder-icon">
                    <i class="bi bi-arrow-left-circle"></i>
                </div>
                <h4>Выберите занятие</h4>
                <p>Нажмите на занятие слева или используйте поиск</p>
            </div>

        <?php elseif (!$currentLesson['group_id']): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                У занятия не указана группа. Отредактируйте его в
                <a href="schedule.php">расписании</a>, чтобы загрузить студентов.
            </div>

        <?php else:
            $isCurrent = $currentLessonNum && (int)$currentLesson['lesson_number'] === $currentLessonNum;
            $isStream = ($currentLesson['groups_count'] ?? 1) > 1;
            $streamGroups = count($streamLessons);
        ?>

            <form method="post" id="attendanceForm">
                <input type="hidden" name="lesson_id" value="<?= $currentLesson['id'] ?>">

                <div class="att-card">
                    <div class="att-card-header">
                        <div style="flex: 1; min-width: 200px;">
                            <h3 class="att-card-title">
                                <?= htmlspecialchars($currentLesson['discipline']) ?>
                                <?php if ($isCurrent): ?>
                                    <span class="att-lesson-badge att-lesson-badge--current">LIVE</span>
                                <?php endif; ?>
                                <?php if ($isStream): ?>
                                    <span class="att-lesson-badge att-lesson-badge--stream">
                                        поток · <?= $streamGroups ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($lessonSubgroup !== null): ?>
                                    <span class="att-lesson-badge att-lesson-badge--sg<?= (int)$lessonSubgroup ?>">
                                        по расписанию: подгр. <?= htmlspecialchars($lessonSubgroup) ?>
                                    </span>
                                <?php endif; ?>
                            </h3>
                            <div class="att-card-meta">
                                <span><i class="bi bi-people-fill"></i>
                                    <strong><?= htmlspecialchars($currentLesson['group_name'] ?? '—') ?></strong>
                                </span>
                                <span><i class="bi bi-calendar-event"></i>
                                    <?= date('d.m.Y', strtotime($currentLesson['lesson_date'])) ?>
                                </span>
                                <?php if ($currentLesson['lesson_time']): ?>
                                    <span><i class="bi bi-clock"></i>
                                        <?= substr($currentLesson['lesson_time'], 0, 5) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($currentLesson['audience'])): ?>
                                    <span><i class="bi bi-geo-alt-fill"></i>
                                        <?= htmlspecialchars($currentLesson['audience']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($students) && $currentLesson['group_id']): ?>
                            <a href="subgroups.php?group_id=<?= (int)$currentLesson['group_id'] ?>"
                               class="att-subgroup-link">
                                <i class="bi bi-diagram-3"></i>
                                Подгруппы
                                <?php if ($hasAnySubgroups): ?>
                                    <span class="att-subgroup-link-badge">
                                        <?= $subgroupCounts['1'] + $subgroupCounts['2'] ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($students)): ?>
                            <div class="att-counters">
                                <span class="att-counter att-counter--present">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span id="presentCount">0</span>
                                </span>
                                <span class="att-counter att-counter--absent">
                                    <i class="bi bi-x-circle-fill"></i>
                                    <span id="absentCount">0</span>
                                </span>
                                <span class="att-counter att-counter--unmarked">
                                    <i class="bi bi-dash-circle"></i>
                                    <span id="unmarkedCount">0</span>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($students)): ?>

                        <div class="att-empty" style="padding: 60px 20px;">
                            <i class="bi bi-people" style="font-size: 3rem;"></i>
                            <div style="font-size: 1.05rem; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">
                                В группе нет студентов
                            </div>
                            <a href="upload_students.php" class="btn btn-primary btn-sm mt-2">
                                <i class="bi bi-file-earmark-excel"></i> Загрузить список
                            </a>
                        </div>

                    <?php else: ?>

                        <div class="att-info">
                            <i class="bi bi-info-circle-fill"></i>
                            <div>
                                <?php if ($isStream): ?>
                                    Это <strong>потоковая лекция</strong> для
                                    <strong><?= $streamGroups ?> групп</strong>.
                                    Отметки сохранятся во <strong>все группы</strong> одновременно.
                                <?php elseif ($hasAnySubgroups): ?>
                                    У группы <strong>есть подгруппы</strong>:
                                    <?php if ($subgroupCounts['1'] > 0): ?>
                                        <span class="att-sg-pill att-sg-pill--1">
                                            <i class="bi bi-1-circle-fill"></i> <?= $subgroupCounts['1'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($subgroupCounts['2'] > 0): ?>
                                        <span class="att-sg-pill att-sg-pill--2">
                                            <i class="bi bi-2-circle-fill"></i> <?= $subgroupCounts['2'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($subgroupCounts['none'] > 0): ?>
                                        <span class="att-sg-pill att-sg-pill--none">
                                            <i class="bi bi-dash-circle"></i> без: <?= $subgroupCounts['none'] ?>
                                        </span>
                                    <?php endif; ?>
                                    ·
                                    <a href="subgroups.php?group_id=<?= (int)$currentLesson['group_id'] ?>">изменить</a>
                                <?php else: ?>
                                    У группы <strong>нет подгрупп</strong> —
                                    <a href="subgroups.php?group_id=<?= (int)$currentLesson['group_id'] ?>">назначить</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($hasAnySubgroups || $lessonSubgroup !== null): ?>
                            <div class="att-sg-selector">
                                <div class="att-sg-selector-label">
                                    <i class="bi bi-diagram-3"></i> Показывать:
                                </div>
                                <div class="att-sg-selector-tabs">
                                    <button type="button" class="att-sg-tab att-sg-tab--all"
                                            data-sg="all" onclick="filterSubgroup('all')">
                                        <i class="bi bi-people-fill"></i>
                                        Все
                                        <span class="count"><?= count($students) ?></span>
                                        <?php if ($lessonSubgroup === null): ?>
                                            <span class="rec-dot" title="По расписанию"></span>
                                        <?php endif; ?>
                                    </button>
                                    <?php if ($subgroupCounts['1'] > 0 || $lessonSubgroup === '1'): ?>
                                        <button type="button" class="att-sg-tab att-sg-tab--1"
                                                data-sg="1" onclick="filterSubgroup('1')">
                                            <i class="bi bi-1-circle-fill"></i>
                                            Подгруппа 1
                                            <span class="count"><?= $subgroupCounts['1'] ?></span>
                                            <?php if ($lessonSubgroup === '1'): ?>
                                                <span class="rec-dot" title="По расписанию"></span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($subgroupCounts['2'] > 0 || $lessonSubgroup === '2'): ?>
                                        <button type="button" class="att-sg-tab att-sg-tab--2"
                                                data-sg="2" onclick="filterSubgroup('2')">
                                            <i class="bi bi-2-circle-fill"></i>
                                            Подгруппа 2
                                            <span class="count"><?= $subgroupCounts['2'] ?></span>
                                            <?php if ($lessonSubgroup === '2'): ?>
                                                <span class="rec-dot" title="По расписанию"></span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($lessonSubgroup !== null): ?>
                                    <div class="att-sg-hint">
                                        <i class="bi bi-info-circle-fill"></i>
                                        По расписанию —
                                        <strong>подгруппа <?= htmlspecialchars($lessonSubgroup) ?></strong>
                                    </div>
                                <?php else: ?>
                                    <div class="att-sg-hint">
                                        <i class="bi bi-info-circle-fill"></i>
                                        По расписанию — все подгруппы
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="att-actions-panel">
                            <button type="button" class="att-action-btn att-action-btn--success" onclick="markAll(1)">
                                <i class="bi bi-check-all"></i> Все были
                            </button>
                            <button type="button" class="att-action-btn att-action-btn--danger" onclick="markAll(0)">
                                <i class="bi bi-x-circle"></i> Все пропустили
                            </button>
                            <button type="button" class="att-action-btn att-action-btn--secondary" onclick="markAll(null)">
                                <i class="bi bi-dash-circle"></i> Сбросить
                            </button>
                        </div>

                        <div class="att-students">
                            <?php foreach ($students as $i => $s):
                                $markValue = $marks[$s['id']] ?? null;
                                $isPresent = $markValue === 1;
                                $isAbsent  = $markValue === 0;
                                $rowClass = 'att-student';
                                if ($isPresent) $rowClass .= ' att-student--present';
                                elseif ($isAbsent) $rowClass .= ' att-student--absent';
                            ?>
                                <div class="<?= $rowClass ?>"
                                     id="row-<?= $s['id'] ?>"
                                     data-subgroup="<?= htmlspecialchars($s['subgroup'] ?: 'none') ?>">
                                    <div class="att-student-num"><?= $i + 1 ?></div>

                                    <div class="att-student-info">
                                        <div class="att-student-name">
                                            <?= htmlspecialchars($s['full_name']) ?>
                                            <?php if (!empty($s['subgroup'])): ?>
                                                <span class="att-sg-badge att-sg-badge--<?= (int)$s['subgroup'] ?>">
                                                    <i class="bi bi-<?= (int)$s['subgroup'] ?>-circle-fill"></i>
                                                    подгр. <?= htmlspecialchars($s['subgroup']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="att-student-meta">
                                            <?php if (!empty($s['zachetka'])): ?>
                                                <code><?= htmlspecialchars($s['zachetka']) ?></code>
                                            <?php endif; ?>
                                            <?php if (!empty($s['group_name'])): ?>
                                                <span class="att-student-group">
                                                    <i class="bi bi-people"></i>
                                                    <?= htmlspecialchars($s['group_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="att-choices">
                                        <label class="att-choice att-choice--present">
                                            <input type="radio"
                                                   name="present[<?= $s['id'] ?>]"
                                                   value="1"
                                                   class="present-radio"
                                                   data-student="<?= $s['id'] ?>"
                                                   <?= $isPresent ? 'checked' : '' ?>>
                                            <span class="att-choice-inner">
                                                <i class="bi bi-check-lg"></i>
                                            </span>
                                        </label>

                                        <label class="att-choice att-choice--absent">
                                            <input type="radio"
                                                   name="present[<?= $s['id'] ?>]"
                                                   value="0"
                                                   class="absent-radio"
                                                   data-student="<?= $s['id'] ?>"
                                                   <?= $isAbsent ? 'checked' : '' ?>>
                                            <span class="att-choice-inner">
                                                <i class="bi bi-x-lg"></i>
                                            </span>
                                        </label>

                                        <button type="button"
                                                class="att-choice att-choice--unmarked"
                                                onclick="clearMark(<?= $s['id'] ?>)">
                                            <span class="att-choice-inner">
                                                <i class="bi bi-dash-lg"></i>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="att-card-footer">
                            <div class="att-footer-hint">
                                <i class="bi bi-info-circle"></i>
                                <?php if ($isStream): ?>
                                    Сохранится для всех <?= $streamGroups ?> групп потока.
                                <?php else: ?>
                                    Сохраняются только отмеченные. Пустые — не считаются пропуском.
                                <?php endif; ?>
                            </div>
                            <button type="submit" name="save" class="att-save-btn" id="saveBtn">
                                <i class="bi bi-save"></i> Сохранить
                            </button>
                        </div>

                    <?php endif; ?>
                </div>
            </form>

        <?php endif; ?>

    </div>
</div>

<!-- Модалка всех занятий -->
<div class="att-modal-backdrop" id="attModal">
    <div class="att-modal">
        <div class="att-modal-header">
            <h5><i class="bi bi-search"></i> Выбор занятия</h5>
            <button class="att-modal-close" onclick="closeAttModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="att-modal-search">
            <input type="text" id="attModalSearch" placeholder="Поиск по дисциплине, группе или аудитории...">
        </div>
        <div class="att-modal-body" id="attModalBody">
            <?php if (empty($allByDate)): ?>
                <div class="att-empty">
                    <i class="bi bi-calendar-x"></i>
                    <div>Занятий нет</div>
                </div>
            <?php else: foreach ($allByDate as $date => $lessons):
                $dateObj = new DateTime($date);
                $isToday = $date === $today;
            ?>
                <div class="att-date-group">
                    <span><?= $dateObj->format('d.m.Y') ?>, <?= ruDayShort($date) ?><?= $isToday ? ' · Сегодня' : '' ?></span>
                    <span><?= count($lessons) ?></span>
                </div>
                <?php foreach ($lessons as $l):
                    $t = $typeLabelsFull[$l['lesson_type']] ?? $typeLabelsFull['other'];
                    $isActive = $l['id'] == $lessonId;
                    $searchText = mb_strtolower($l['discipline'] . ' ' . ($l['group_name'] ?? '') . ' ' . ($l['audience'] ?? ''));
                    $isStream = ($l['groups_count'] ?? 1) > 1;
                    $sgL = (!empty($l['subgroup']) && $l['subgroup'] !== '0') ? (string)$l['subgroup'] : null;
                ?>
                    <a href="?lesson_id=<?= $l['id'] ?>"
                       class="att-modal-lesson <?= $isActive ? 'att-modal-lesson--active' : '' ?>"
                       data-search="<?= htmlspecialchars($searchText) ?>">
                        <span class="att-modal-lesson-time">
                            <?= $l['lesson_time'] ? substr($l['lesson_time'], 0, 5) : '—' ?>
                        </span>
                        <div class="att-modal-lesson-info">
                            <div class="att-modal-lesson-title"><?= htmlspecialchars($l['discipline']) ?></div>
                            <div class="att-modal-lesson-meta">
                                <span><i class="bi bi-people-fill"></i> <?= htmlspecialchars($l['group_name'] ?? '—') ?></span>
                                <?php if (!empty($l['audience'])): ?>
                                    <span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($l['audience']) ?></span>
                                <?php endif; ?>
                                <?php if ($isStream): ?>
                                    <span><i class="bi bi-diagram-3"></i> поток</span>
                                <?php endif; ?>
                                <?php if ($sgL): ?>
                                    <span><i class="bi bi-diagram-2"></i> подгр. <?= htmlspecialchars($sgL) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right" style="color:#86868b; flex-shrink:0;"></i>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>
function markAll(value) {
    document.querySelectorAll('.present-radio, .absent-radio').forEach(r => {
        const row = r.closest('.att-student');
        if (row && row.style.display === 'none') return;

        const isPresent = r.classList.contains('present-radio');
        if (value === 1 && isPresent)       r.checked = true;
        else if (value === 0 && !isPresent) r.checked = true;
        else                                r.checked = false;
    });
    updateCounters();
}

function clearMark(studentId) {
    const p = document.querySelector(`.present-radio[data-student="${studentId}"]`);
    const a = document.querySelector(`.absent-radio[data-student="${studentId}"]`);
    if (p) p.checked = false;
    if (a) a.checked = false;
    updateCounters();
}

function updateCounters() {
    let present = 0, absent = 0, unmarked = 0;
    document.querySelectorAll('.att-student').forEach(row => {
        if (row.style.display === 'none') return;

        const sid = row.id.replace('row-', '');
        const pEl = document.querySelector(`.present-radio[data-student="${sid}"]`);
        const aEl = document.querySelector(`.absent-radio[data-student="${sid}"]`);
        if (!pEl || !aEl) return;

        if (pEl.checked) present++;
        else if (aEl.checked) absent++;
        else unmarked++;

        row.classList.remove('att-student--present', 'att-student--absent');
        if (pEl.checked) row.classList.add('att-student--present');
        else if (aEl.checked) row.classList.add('att-student--absent');
    });

    const pEl = document.getElementById('presentCount');
    const aEl = document.getElementById('absentCount');
    const uEl = document.getElementById('unmarkedCount');
    if (pEl) pEl.textContent = present;
    if (aEl) aEl.textContent = absent;
    if (uEl) uEl.textContent = unmarked;
}

document.querySelectorAll('.present-radio, .absent-radio').forEach(r => {
    r.addEventListener('change', updateCounters);
});

let activeSubgroup = 'all';

function filterSubgroup(sg) {
    activeSubgroup = sg;

    document.querySelectorAll('.att-sg-tab').forEach(t => {
        t.classList.toggle('att-sg-tab--active', t.dataset.sg === sg);
    });

    document.querySelectorAll('.att-student').forEach(row => {
        const rowSg = row.dataset.subgroup || 'none';

        let show = false;
        if (sg === 'all') show = true;
        else if (rowSg === sg) show = true;
        else if (rowSg === 'none') show = true;
        else show = false;

        row.style.display = show ? '' : 'none';
    });

    let idx = 1;
    document.querySelectorAll('.att-student').forEach(row => {
        if (row.style.display === 'none') return;
        const numEl = row.querySelector('.att-student-num');
        if (numEl) numEl.textContent = idx++;
    });

    updateCounters();

    try { localStorage.setItem('att_active_subgroup', sg); } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    updateCounters();

    const lessonSg = <?= json_encode($lessonSubgroup) ?>;
    let saved = 'all';
    try { saved = localStorage.getItem('att_active_subgroup') || 'all'; } catch (e) {}

    const initial = lessonSg !== null ? lessonSg : saved;
    const tab = document.querySelector(`.att-sg-tab[data-sg="${initial}"]`);
    if (tab) filterSubgroup(initial);
});

function openAttModal() {
    document.getElementById('attModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeAttModal() {
    document.getElementById('attModal').classList.remove('show');
    document.body.style.overflow = '';
}
document.getElementById('attModal').addEventListener('click', (e) => {
    if (e.target.id === 'attModal') closeAttModal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAttModal();
});

(function() {
    const search = document.getElementById('attModalSearch');
    if (!search) return;
    search.addEventListener('input', (e) => {
        const q = e.target.value.toLowerCase().trim();
        document.querySelectorAll('.att-modal-lesson').forEach(item => {
            const text = item.dataset.search || '';
            item.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
        document.querySelectorAll('.att-date-group').forEach(group => {
            let next = group.nextElementSibling;
            let anyVisible = false;
            while (next && !next.classList.contains('att-date-group')) {
                if (next.classList.contains('att-modal-lesson') && next.style.display !== 'none') {
                    anyVisible = true; break;
                }
                next = next.nextElementSibling;
            }
            group.style.display = anyVisible ? '' : 'none';
        });
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?>