<?php
$pageTitle = 'Главная';
require_once 'config/database.php';
require_once 'includes/session_helpers.php';
require_once 'includes/access_control.php';

// ============================================================
// Сессия и авторизация
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
// Область доступа
// ============================================================
$scope = getAccessScope($pdo, $userId);
$scopeInfo = scopeLabel($scope);

$userInstitute  = $scope['institute_name']  ?? '';
$userDepartment = $scope['department_name'] ?? '';

// Аббревиатура института
$instShort = '';
if ($userInstitute !== '') {
    if (strpos($userInstitute, '—') !== false) {
        $instShort = trim(explode('—', $userInstitute)[0]);
    } else {
        $instShort = $userInstitute;
    }
}

// Короткое название кафедры
$deptShort = $userDepartment;
if (mb_stripos($deptShort, 'кафедра ') === 0) {
    $deptShort = mb_substr($deptShort, 8);
}

// ============================================================
// Автосинхронизация расписания
// ============================================================
function syncScheduleFromApi(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT api_teacher_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $teacherId = $stmt->fetchColumn();
    if (!$teacherId) return ['error' => 'Нет ID преподавателя'];

    $url = 'https://portal.kuzstu.ru/api/teacher_schedule?teacher_id=' . urlencode($teacherId);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return ['error' => "API: $httpCode"];
    $apiLessons = json_decode($response, true);
    if (!is_array($apiLessons)) return ['error' => 'Некорректный ответ'];

    $timeMap = [];
    foreach (LESSON_TIMES as $n => $t) $timeMap[$n] = $t['start'];
    $typeMap = [
        'л.' => 'lecture', 'лек.' => 'lecture',
        'пр.' => 'practice', 'практ.' => 'practice',
        'лаб.' => 'lab', 'сем.' => 'seminar',
    ];
    $added = $updated = 0;

    foreach ($apiLessons as $item) {
        $apiLessonId = (string)($item['id'] ?? '');
        $date = $item['date_lesson'] ?? null;
        if (!$apiLessonId || !$date) continue;

        $apiGroupId = (string)($item['education_group_id'] ?? '');
        $groupName = trim($item['education_group_name'] ?? '');
        $groupId = null;

        if ($apiGroupId && $groupName) {
            $stmt = $pdo->prepare("SELECT id FROM groups_table WHERE api_group_id = ?");
            $stmt->execute([$apiGroupId]);
            $groupId = $stmt->fetchColumn();

            if (!$groupId) {
                $stmt = $pdo->prepare("SELECT id FROM groups_table WHERE name = ?");
                $stmt->execute([$groupName]);
                $groupId = $stmt->fetchColumn();
                if ($groupId) {
                    $pdo->prepare("UPDATE groups_table SET api_group_id = ? WHERE id = ?")
                        ->execute([$apiGroupId, $groupId]);
                } else {
                    $pdo->prepare("INSERT INTO groups_table (name, api_group_id) VALUES (?, ?)")
                        ->execute([$groupName, $apiGroupId]);
                    $groupId = $pdo->lastInsertId();
                }
            }
        }

        $lessonNumber = (int)($item['lesson_number'] ?? 0);
        $lessonTime = $timeMap[$lessonNumber] ?? null;
        $type = $typeMap[trim($item['type'] ?? '')] ?? 'other';
        $discipline = trim($item['subject'] ?? '');
        $audience = trim($item['place'] ?? '');
        $subgroup = (string)($item['subgroup'] ?? '0');

        $stmt = $pdo->prepare("SELECT id FROM lessons WHERE user_id = ? AND api_lesson_id = ?");
        $stmt->execute([$userId, $apiLessonId]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $pdo->prepare("
                UPDATE lessons SET
                    group_id=?, discipline=?, lesson_date=?, lesson_time=?,
                    lesson_type=?, subgroup=?, lesson_number=?, audience=?
                WHERE id=?
            ")->execute([
                $groupId ?: null, $discipline, $date, $lessonTime,
                $type, $subgroup, $lessonNumber ?: null, $audience, $exists
            ]);
            $updated++;
        } else {
            $pdo->prepare("
                INSERT INTO lessons
                    (api_lesson_id, user_id, group_id, discipline, lesson_date, lesson_time,
                     lesson_type, subgroup, lesson_number, audience, is_manual)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ")->execute([
                $apiLessonId, $userId, $groupId ?: null, $discipline, $date, $lessonTime,
                $type, $subgroup, $lessonNumber ?: null, $audience
            ]);
            $added++;
        }
    }

    return ['added' => $added, 'updated' => $updated];
}

$lastSync = $_SESSION['last_schedule_sync'] ?? 0;
$syncResult = null;
if (time() - $lastSync > 1800) {
    try {
        $syncResult = syncScheduleFromApi($pdo, $userId);
        $_SESSION['last_schedule_sync'] = time();
    } catch (Throwable $e) {
        $syncResult = ['error' => $e->getMessage()];
    }
}

// ============================================================
// Верхняя статистика (только свои занятия для преподавателя)
// ============================================================
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM (
        SELECT 1 FROM lessons
        WHERE user_id = ? AND lesson_date = ?
        GROUP BY lesson_number, discipline, audience, subgroup
    ) AS uniq
");
$stmt->execute([$userId, $today]);
$todayLessons = (int)$stmt->fetchColumn();

$monday = date('Y-m-d', strtotime('monday this week'));
$sunday = date('Y-m-d', strtotime('sunday this week'));
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM (
        SELECT 1 FROM lessons
        WHERE user_id = ? AND lesson_date BETWEEN ? AND ?
        GROUP BY lesson_date, lesson_number, discipline, audience, subgroup
    ) AS uniq
");
$stmt->execute([$userId, $monday, $sunday]);
$weekLessons = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(a.id) AS total, COALESCE(SUM(a.is_present), 0) AS present
    FROM attendance a
    JOIN lessons l ON a.lesson_id = l.id
    WHERE l.user_id = ? AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->execute([$userId]);
$stat = $stmt->fetch();
$totalMarked = (int)($stat['total'] ?? 0);
$presentMarked = (int)($stat['present'] ?? 0);
$attendancePct = $totalMarked > 0 ? round($presentMarked / $totalMarked * 100) : 0;

$stmt = $pdo->prepare("
    SELECT COUNT(w.id) AS total, COALESCE(SUM(w.is_submitted), 0) AS submitted
    FROM submitted_works w
    JOIN lessons l ON w.lesson_id = l.id
    WHERE l.user_id = ? AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->execute([$userId]);
$wstat = $stmt->fetch();
$submittedCnt = (int)($wstat['submitted'] ?? 0);

// ============================================================
// График 1: посещаемость по неделям
// ============================================================
$stmt = $pdo->prepare("
    SELECT
        YEARWEEK(l.lesson_date, 1) AS week,
        MIN(l.lesson_date) AS week_start,
        COUNT(a.id) AS total,
        SUM(a.is_present) AS present
    FROM attendance a
    JOIN lessons l ON a.lesson_id = l.id
    WHERE l.user_id = ? AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
    GROUP BY YEARWEEK(l.lesson_date, 1)
    ORDER BY week ASC
");
$stmt->execute([$userId]);
$weekRows = $stmt->fetchAll();

$weekLabels = [];
$weekData = [];
foreach ($weekRows as $r) {
    $weekLabels[] = date('d.m', strtotime($r['week_start']));
    $weekData[] = $r['total'] > 0 ? round($r['present'] / $r['total'] * 100) : 0;
}
if (empty($weekLabels)) {
    for ($i = 7; $i >= 0; $i--) {
        $weekLabels[] = date('d.m', strtotime("-$i week monday"));
        $weekData[] = 0;
    }
}

// ============================================================
// График 2: типы занятий
// ============================================================
$stmt = $pdo->prepare("
    SELECT lesson_type, COUNT(*) AS cnt
    FROM (
        SELECT lesson_type, lesson_date, lesson_number, discipline, audience, subgroup
        FROM lessons
        WHERE user_id = ?
        GROUP BY lesson_type, lesson_date, lesson_number, discipline, audience, subgroup
    ) AS uniq
    GROUP BY lesson_type
");
$stmt->execute([$userId]);
$typeRows = $stmt->fetchAll();

$typeNames = [
    'lecture' => 'Лекция', 'practice' => 'Практика',
    'lab' => 'Лабораторная', 'seminar' => 'Семинар', 'other' => 'Другое',
];
$typeLabels = [];
$typeData = [];
foreach ($typeRows as $r) {
    $typeLabels[] = $typeNames[$r['lesson_type']] ?? $r['lesson_type'];
    $typeData[] = (int)$r['cnt'];
}

// ============================================================
// График 3: топ-5 групп по посещаемости
// ============================================================
$stmt = $pdo->prepare("
    SELECT * FROM (
        SELECT g.name AS group_name,
               COUNT(a.id) AS total,
               SUM(a.is_present) AS present_count
        FROM groups_table g
        JOIN lessons l ON l.group_id = g.id
        JOIN attendance a ON a.lesson_id = l.id
        WHERE l.user_id = ?
        GROUP BY g.id
        HAVING total > 0
    ) AS t
    ORDER BY present_count / total DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$topGroups = $stmt->fetchAll();

$groupLabels = [];
$groupData = [];
foreach ($topGroups as $r) {
    $groupLabels[] = $r['group_name'];
    $groupData[] = round($r['present_count'] / $r['total'] * 100);
}

// ============================================================
// Топ-5 должников
// ============================================================
$stmt = $pdo->prepare("
    SELECT s.id, s.full_name, s.zachetka, s.group_id, g.name AS group_name,
           COUNT(a.id) AS total,
           SUM(CASE WHEN a.is_present = 0 THEN 1 ELSE 0 END) AS absent
    FROM students s
    JOIN groups_table g ON s.group_id = g.id
    JOIN attendance a ON a.student_id = s.id
    JOIN lessons l ON a.lesson_id = l.id
    WHERE l.user_id = ? AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
    GROUP BY s.id
    HAVING absent > 0
    ORDER BY absent DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$topAbsent = $stmt->fetchAll();

// Общее число должников
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM (
        SELECT s.id
        FROM students s
        JOIN attendance a ON a.student_id = s.id
        JOIN lessons l ON a.lesson_id = l.id
        WHERE l.user_id = ? AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        GROUP BY s.id
        HAVING SUM(CASE WHEN a.is_present = 0 THEN 1 ELSE 0 END) >= 3
    ) AS d
");
$stmt->execute([$userId]);
$totalDebtors = (int)$stmt->fetchColumn();

// ============================================================
// Мои группы
// ============================================================
$stmt = $pdo->prepare("
    SELECT g.id, g.name, COUNT(DISTINCT s.id) AS students_cnt
    FROM groups_table g
    JOIN lessons l ON l.group_id = g.id
    LEFT JOIN students s ON s.group_id = g.id
    WHERE l.user_id = ?
    GROUP BY g.id
    ORDER BY g.name
    LIMIT 12
");
$stmt->execute([$userId]);
$myGroups = $stmt->fetchAll();

// ============================================================
// Моя сессия
// ============================================================
$currentSemester = getCurrentSemester();

$stmt = $pdo->prepare("
    SELECT
        s.id, s.discipline, s.session_type, s.status, s.group_id,
        g.name AS group_name,
        (SELECT COUNT(*) FROM students st WHERE st.group_id = s.group_id) AS students_cnt,
        (SELECT COUNT(*) FROM session_grades sg WHERE sg.sheet_id = s.id AND sg.grade IS NOT NULL) AS graded_cnt
    FROM session_sheets s
    LEFT JOIN groups_table g ON s.group_id = g.id
    WHERE s.user_id = ? AND s.semester = ?
    ORDER BY s.status ASC, s.created_at DESC
    LIMIT 6
");
$stmt->execute([$userId, $currentSemester]);
$mySheets = $stmt->fetchAll();

// ============================================================
// Расписание на сегодня
// ============================================================
// Ключевое отличие: groups_list собирается через коррелированный
// подзапрос с GROUP_CONCAT(DISTINCT ...), чтобы избежать дублей.
$stmt = $pdo->prepare("
    SELECT
        MIN(l.id) AS primary_id,
        l.lesson_number,
        l.lesson_time,
        l.discipline,
        l.lesson_type,
        l.audience,
        l.subgroup,
        (
            SELECT GROUP_CONCAT(DISTINCT g2.name ORDER BY g2.name SEPARATOR ', ')
            FROM lessons l2
            JOIN groups_table g2 ON l2.group_id = g2.id
            WHERE l2.user_id = l.user_id
              AND l2.lesson_date = l.lesson_date
              AND l2.lesson_number <=> l.lesson_number
              AND l2.discipline = l.discipline
              AND l2.lesson_type = l.lesson_type
              AND COALESCE(l2.audience, '') = COALESCE(l.audience, '')
              AND COALESCE(l2.subgroup, '0') = COALESCE(l.subgroup, '0')
        ) AS groups_list
    FROM lessons l
    WHERE l.user_id = ? AND l.lesson_date = ?
    GROUP BY
        l.lesson_date,
        l.lesson_number,
        l.lesson_time,
        l.discipline,
        l.lesson_type,
        COALESCE(l.audience, ''),
        COALESCE(l.subgroup, '0')
    ORDER BY l.lesson_time, l.lesson_number
");
$stmt->execute([$userId, $today]);
$todaySchedule = $stmt->fetchAll();

$currentLessonNum = getCurrentLessonNumber();
$nextLessonNum    = getNextLessonNumber();

$typeLabelsFull = [
    'lecture'  => ['label' => 'Лекция',       'color' => 'primary', 'icon' => 'bi-book'],
    'practice' => ['label' => 'Практика',     'color' => 'success', 'icon' => 'bi-pencil'],
    'lab'      => ['label' => 'Лабораторная', 'color' => 'info',    'icon' => 'bi-flask'],
    'seminar'  => ['label' => 'Семинар',      'color' => 'warning', 'icon' => 'bi-people'],
    'other'    => ['label' => 'Другое',       'color' => 'secondary','icon' => 'bi-journal'],
];

$sessionTypesLabels = sessionTypes();

// ============================================================
// Виджет по кафедре / институту / вузу
// ============================================================
$departmentStats = null;

if (in_array($scope['role'], ['head_of_department', 'administration', 'admin'])) {
    if ($scope['role'] === 'head_of_department' && !empty($scope['department_name'])) {
        $widgetTitle = $scope['department_name'];
        $widgetIcon  = 'bi-diagram-3';

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM users
            WHERE department_name = ? AND is_active = 1
        ");
        $stmt->execute([$scope['department_name']]);
        $deptTeachers = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT l.group_id)
            FROM lessons l
            JOIN users u ON l.user_id = u.id
            WHERE u.department_name = ?
        ");
        $stmt->execute([$scope['department_name']]);
        $deptGroups = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(a.id) AS total, COALESCE(SUM(a.is_present), 0) AS present
            FROM attendance a
            JOIN lessons l ON a.lesson_id = l.id
            JOIN users u ON l.user_id = u.id
            WHERE u.department_name = ?
              AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$scope['department_name']]);
        $deptAtt = $stmt->fetch();

    } elseif ($scope['role'] === 'administration' && !empty($scope['institute_name'])) {
        $widgetTitle = $scope['institute_name'];
        $widgetIcon  = 'bi-building';

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM users
            WHERE institute_name = ? AND is_active = 1
        ");
        $stmt->execute([$scope['institute_name']]);
        $deptTeachers = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT l.group_id)
            FROM lessons l
            JOIN users u ON l.user_id = u.id
            WHERE u.institute_name = ?
        ");
        $stmt->execute([$scope['institute_name']]);
        $groupsFromTeachers = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM groups_table WHERE institute = ?");
        $stmt->execute([$scope['institute_name']]);
        $groupsFromInstitute = (int)$stmt->fetchColumn();

        $deptGroups = max($groupsFromTeachers, $groupsFromInstitute);

        $stmt = $pdo->prepare("
            SELECT COUNT(a.id) AS total, COALESCE(SUM(a.is_present), 0) AS present
            FROM attendance a
            JOIN lessons l ON a.lesson_id = l.id
            JOIN users u ON l.user_id = u.id
            WHERE u.institute_name = ?
              AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$scope['institute_name']]);
        $deptAtt = $stmt->fetch();

    } else {
        $widgetTitle = 'Весь вуз';
        $widgetIcon  = 'bi-shield-fill-check';

        $deptTeachers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
        $deptGroups   = (int)$pdo->query("SELECT COUNT(*) FROM groups_table")->fetchColumn();
        $deptAtt      = $pdo->query("
            SELECT COUNT(a.id) AS total, COALESCE(SUM(a.is_present), 0) AS present
            FROM attendance a
            JOIN lessons l ON a.lesson_id = l.id
            WHERE l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->fetch();
    }

    $deptAttTotal = (int)($deptAtt['total'] ?? 0);
    $deptAttPct = $deptAttTotal > 0 ? (int)round((int)$deptAtt['present'] / $deptAttTotal * 100) : null;

    $departmentStats = [
        'title'     => $widgetTitle,
        'icon'      => $widgetIcon,
        'teachers'  => $deptTeachers,
        'groups'    => $deptGroups,
        'att_pct'   => $deptAttPct,
    ];
}

require_once 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
/* ============================================================
   ДАШБОРД
   ============================================================ */
.dash-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 28px;
}
.dash-hello h2 {
    font-size: 1.7rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 6px;
}
.dash-hello .dash-dept {
    color: #86868b;
    margin: 0;
    font-size: 0.92rem;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.dash-hello .dash-dept i { color: #0071e3; font-size: 0.9rem; }
.dash-hello .dash-dept .sep { opacity: 0.4; }
.dash-hello p {
    color: #86868b;
    margin: 6px 0 0;
    font-size: 0.92rem;
}

/* Виджет кафедры */
.dash-dept-widget {
    background: linear-gradient(135deg, #e7f1ff 0%, #fff 100%);
    border: 1px solid rgba(0,113,227,0.15);
    border-radius: 20px;
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
.dash-dept-widget-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(0,113,227,0.25);
}
.dash-dept-widget-info { flex: 1; min-width: 200px; }
.dash-dept-widget-title {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0 0 4px;
    color: #1d1d1f;
}
.dash-dept-widget-sub {
    font-size: 0.82rem;
    color: #86868b;
    margin: 0;
}
.dash-dept-widget-stats {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}
.dash-dept-widget-stat { text-align: center; }
.dash-dept-widget-stat .val {
    font-size: 1.6rem;
    font-weight: 700;
    color: #0071e3;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.dash-dept-widget-stat .lbl {
    font-size: 0.72rem;
    color: #86868b;
    margin-top: 4px;
}

/* Карточки статистики */
.dash-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.dash-stat {
    background: #fff;
    border-radius: 20px;
    padding: 22px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
    position: relative;
    overflow: hidden;
}
.dash-stat:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}
.dash-stat::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: var(--stat-color, #0071e3);
}
.dash-stat-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}
.dash-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: var(--stat-bg, rgba(0,113,227,0.1));
    color: var(--stat-color, #0071e3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.dash-stat-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.02em;
    color: #1d1d1f;
    margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
}
.dash-stat-label {
    font-size: 0.82rem;
    color: #86868b;
    font-weight: 500;
}
.dash-stat-sub {
    font-size: 0.75rem;
    color: #86868b;
    margin-top: 4px;
}
.dash-stat-link {
    text-decoration: none;
    color: inherit;
    display: block;
}
.dash-stat-link:hover .dash-stat-value { color: #0071e3; }

/* Сетки */
.dash-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}
@media (max-width: 991px) { .dash-grid { grid-template-columns: 1fr; } }

.dash-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}
@media (max-width: 768px) { .dash-grid-2 { grid-template-columns: 1fr; } }

/* Расписание */
.dash-schedule {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
    margin-top: 20px;
}
.sched-lesson {
    background: #fff;
    border-radius: 18px;
    padding: 20px;
    border: 1.5px solid #eaeaea;
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.sched-lesson::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: #0071e3;
}
.sched-lesson--current {
    background: linear-gradient(135deg, #f0faf3 0%, #fff 100%);
    border-color: #30d158;
    box-shadow: 0 8px 24px rgba(48,209,88,0.2);
}
.sched-lesson--current::before { background: #30d158; width: 5px; }
.sched-lesson--past { opacity: 0.65; }
.sched-lesson--next {
    background: linear-gradient(135deg, #e7f6fb 0%, #fff 100%);
    border-color: #0dcaf0;
}
.sched-lesson--next::before { background: #0dcaf0; }
.sched-lesson:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(0,0,0,0.1);
}

.sched-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.sched-time { display: flex; flex-direction: column; gap: 2px; }
.sched-time-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1d1d1f;
    letter-spacing: -0.01em;
    font-variant-numeric: tabular-nums;
}
.sched-time-sub { font-size: 0.72rem; color: #86868b; font-weight: 500; }
.sched-badges { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
.sched-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
}
.sched-badge--current {
    background: #30d158;
    color: #fff;
    animation: pulseDot 2s ease-in-out infinite;
}
.sched-badge--next { background: #0dcaf0; color: #fff; }
@keyframes pulseDot {
    0%,100% { box-shadow: 0 0 0 0 rgba(48,209,88,0.5); }
    50%     { box-shadow: 0 0 0 8px rgba(48,209,88,0); }
}

.sched-title {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.3;
    color: #1d1d1f;
    margin: 0;
}
.sched-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.82rem;
    color: #86868b;
}
.sched-meta span { display: inline-flex; align-items: center; gap: 4px; }
.sched-meta i { color: #0071e3; }

.sched-actions {
    display: flex;
    gap: 8px;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid #f0f0f2;
}
.sched-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    font-family: inherit;
}
.sched-btn--primary {
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
}
.sched-btn--primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,113,227,0.35);
    color: #fff;
}
.sched-btn--outline {
    background: #fff;
    color: #0071e3;
    border: 1.5px solid #0071e3;
}
.sched-btn--outline:hover {
    background: #0071e3;
    color: #fff;
}

/* Карточка */
.dash-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    padding: 24px;
    transition: all 0.3s;
}
.dash-card:hover {
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}
.dash-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 10px;
    flex-wrap: wrap;
}
.dash-card-title {
    font-size: 1.05rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dash-card-title i { color: #0071e3; }
.dash-card-badge {
    font-size: 0.7rem;
    font-weight: 600;
    color: #86868b;
    background: #f5f5f7;
    padding: 4px 12px;
    border-radius: 20px;
    text-decoration: none;
    transition: all 0.2s;
}
.dash-card-badge:hover {
    background: #0071e3;
    color: #fff;
}

.chart-clickable { cursor: zoom-in; position: relative; }
.chart-clickable::after {
    content: '\F52A';
    font-family: 'bootstrap-icons';
    position: absolute;
    top: 8px; right: 8px;
    width: 32px; height: 32px;
    background: rgba(0,0,0,0.5);
    color: #fff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    opacity: 0;
    transition: opacity 0.2s;
    pointer-events: none;
}
.chart-clickable:hover::after { opacity: 1; }

.chart-wrap { position: relative; height: 280px; }
.chart-wrap--tall { height: 320px; }
.chart-wrap--donut { height: 240px; }

/* Список */
.dash-list { display: flex; flex-direction: column; gap: 10px; }
.dash-list-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    border-radius: 14px;
    background: #fafafc;
    border: 1px solid #f0f0f2;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
}
.dash-list-item:hover {
    background: #fff;
    border-color: #0071e3;
    transform: translateX(4px);
    color: inherit;
}
.dash-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    flex-shrink: 0;
}
.dash-list-info { flex: 1; min-width: 0; }
.dash-list-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1d1d1f;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dash-list-meta { font-size: 0.75rem; color: #86868b; }
.dash-list-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: #ff453a;
    flex-shrink: 0;
}
.dash-list-value small {
    font-size: 0.7rem;
    font-weight: 500;
    color: #86868b;
    margin-left: 2px;
}

/* Группы */
.dash-groups { display: flex; flex-wrap: wrap; gap: 8px; }
.dash-group-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 12px;
    background: #f5f5f7;
    border: 1.5px solid transparent;
    color: #1d1d1f;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.dash-group-chip:hover {
    background: #fff;
    border-color: #0071e3;
    color: #0071e3;
    transform: translateY(-2px);
}
.dash-group-chip .count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    background: rgba(0,113,227,0.1);
    color: #0071e3;
    border-radius: 11px;
    font-size: 0.72rem;
    font-variant-numeric: tabular-nums;
}

/* Ведомости */
.dash-sheets { display: flex; flex-direction: column; gap: 8px; }
.dash-sheet {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: #fafafc;
    border: 1px solid #f0f0f2;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}
.dash-sheet:hover {
    background: #fff;
    border-color: #0071e3;
    transform: translateX(4px);
    color: inherit;
}
.dash-sheet-info { flex: 1; min-width: 0; }
.dash-sheet-title {
    font-size: 0.88rem;
    font-weight: 700;
    color: #1d1d1f;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dash-sheet-meta {
    font-size: 0.72rem;
    color: #86868b;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}
.dash-sheet-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.dash-sheet-badge--open   { background: rgba(0,113,227,0.12); color: #0071e3; }
.dash-sheet-badge--closed { background: rgba(48,209,88,0.15); color: #198754; }

.dash-sheet-progress {
    width: 60px;
    height: 5px;
    background: #e5e5ea;
    border-radius: 3px;
    overflow: hidden;
    flex-shrink: 0;
}
.dash-sheet-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0071e3, #5e5ce6);
    transition: width 0.4s;
}

.dash-empty {
    text-align: center;
    color: #86868b;
    padding: 40px 16px;
    font-size: 0.9rem;
}
.dash-empty i {
    font-size: 3rem;
    opacity: 0.3;
    display: block;
    margin-bottom: 12px;
}

/* Модалка графика */
.chart-modal-backdrop {
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
.chart-modal-backdrop.show { display: flex; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.chart-modal {
    background: #fff;
    border-radius: 24px;
    padding: 32px;
    width: 100%;
    max-width: 1000px;
    max-height: 90vh;
    box-shadow: 0 40px 100px rgba(0,0,0,0.4);
    animation: modalIn 0.4s cubic-bezier(0.16,1,0.3,1);
    position: relative;
    display: flex;
    flex-direction: column;
}
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.9) translateY(30px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.chart-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.chart-modal-title {
    font-size: 1.3rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.chart-modal-title i { color: #0071e3; }
.chart-modal-close {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f5f5f7;
    border: none;
    color: #1d1d1f;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.2s;
}
.chart-modal-close:hover {
    background: #0071e3;
    color: #fff;
    transform: rotate(90deg);
}
.chart-modal-body {
    flex: 1;
    min-height: 400px;
    position: relative;
}

/* Тёмная тема */
[data-theme="dark"] .dash-stat,
[data-theme="dark"] .dash-card,
[data-theme="dark"] .sched-lesson,
[data-theme="dark"] .chart-modal,
[data-theme="dark"] .dash-sheet,
[data-theme="dark"] .dash-list-item {
    background: #1c1c1e !important;
    border-color: #2c2c2e !important;
}
[data-theme="dark"] .dash-stat-value,
[data-theme="dark"] .sched-time-value,
[data-theme="dark"] .sched-title,
[data-theme="dark"] .dash-list-name,
[data-theme="dark"] .chart-modal-title,
[data-theme="dark"] .dash-hello h2,
[data-theme="dark"] .dash-sheet-title,
[data-theme="dark"] .dash-dept-widget-title {
    color: #f5f5f7;
}
[data-theme="dark"] .dash-list-item,
[data-theme="dark"] .dash-sheet,
[data-theme="dark"] .dash-group-chip {
    background: #2c2c2e;
    border-color: #3c3c3e;
    color: #f5f5f7;
}
[data-theme="dark"] .dash-list-item:hover,
[data-theme="dark"] .dash-sheet:hover {
    background: #3c3c3e;
    border-color: #0071e3;
}
[data-theme="dark"] .dash-group-chip:hover {
    background: #3c3c3e;
    border-color: #0071e3;
    color: #6ea8ff;
}
[data-theme="dark"] .sched-lesson--current {
    background: linear-gradient(135deg, rgba(48,209,88,0.15) 0%, #1c1c1e 100%);
}
[data-theme="dark"] .sched-lesson--next {
    background: linear-gradient(135deg, rgba(13,202,240,0.15) 0%, #1c1c1e 100%);
}
[data-theme="dark"] .chart-modal-close {
    background: #2c2c2e;
    color: #f5f5f7;
}
[data-theme="dark"] .dash-card-badge {
    background: #2c2c2e;
    color: #a1a1a6;
}
[data-theme="dark"] .dash-dept-widget {
    background: linear-gradient(135deg, rgba(0,113,227,0.15) 0%, #1c1c1e 100%);
    border-color: rgba(0,113,227,0.3);
}
[data-theme="dark"] .sched-actions { border-color: #2c2c2e; }
[data-theme="dark"] .sched-btn--outline {
    background: #2c2c2e;
    border-color: #0a84ff;
    color: #6ea8ff;
}
[data-theme="dark"] .sched-btn--outline:hover {
    background: #0a84ff;
    color: #fff;
}
[data-theme="dark"] .dash-sheet-progress { background: #3c3c3e; }
</style>

<!-- Шапка -->
<div class="dash-header">
    <div class="dash-hello">
        <h2>
            <i class="bi bi-speedometer2"></i>
            Привет, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[1] ?? $_SESSION['user_name']) ?>!
        </h2>

        <?php if ($deptShort || $instShort): ?>
            <div class="dash-dept">
                <?php if ($deptShort): ?>
                    <span><i class="bi bi-diagram-3"></i> <?= htmlspecialchars($deptShort) ?></span>
                <?php endif; ?>
                <?php if ($deptShort && $instShort): ?>
                    <span class="sep">·</span>
                <?php endif; ?>
                <?php if ($instShort): ?>
                    <span><i class="bi bi-building"></i> <?= htmlspecialchars($instShort) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p>
            <i class="bi bi-calendar3"></i>
            <?= ruDayFull($today) ?>, <?= date('d.m.Y') ?>
            · <i class="bi bi-clock"></i> <?= date('H:i') ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="schedule.php?sync=1" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-repeat"></i> Обновить
        </a>
        <a href="attendance.php" class="btn btn-primary btn-sm">
            <i class="bi bi-check2-square"></i> Отметить
        </a>
    </div>
</div>

<?php if ($syncResult && isset($syncResult['error'])): ?>
    <div class="alert alert-warning py-2 small">
        <i class="bi bi-exclamation-triangle"></i>
        Синхронизация: <?= htmlspecialchars($syncResult['error']) ?>
    </div>
<?php elseif ($syncResult && ($syncResult['added'] + $syncResult['updated'] > 0)): ?>
    <div class="alert alert-info py-2 small">
        <i class="bi bi-cloud-download"></i>
        Обновлено: +<?= $syncResult['added'] ?> новых, ~<?= $syncResult['updated'] ?> изменений
    </div>
<?php endif; ?>

<!-- Виджет кафедры / института / вуза -->
<?php if ($departmentStats): ?>
    <div class="dash-dept-widget">
        <div class="dash-dept-widget-icon">
            <i class="bi <?= htmlspecialchars($departmentStats['icon']) ?>"></i>
        </div>
        <div class="dash-dept-widget-info">
            <h3 class="dash-dept-widget-title">
                <?= htmlspecialchars($departmentStats['title']) ?>
            </h3>
            <p class="dash-dept-widget-sub">
                <i class="bi <?= $scopeInfo[2] ?>"></i>
                <?= htmlspecialchars($scopeInfo[0]) ?>
                · сводка за последние 30 дней
            </p>
        </div>
        <div class="dash-dept-widget-stats">
            <div class="dash-dept-widget-stat">
                <div class="val"><?= $departmentStats['teachers'] ?></div>
                <div class="lbl">преподавателей</div>
            </div>
            <div class="dash-dept-widget-stat">
                <div class="val"><?= $departmentStats['groups'] ?></div>
                <div class="lbl">групп</div>
            </div>
            <div class="dash-dept-widget-stat">
                <div class="val">
                    <?= $departmentStats['att_pct'] !== null ? $departmentStats['att_pct'] . '%' : '—' ?>
                </div>
                <div class="lbl">посещаемость</div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Статистика -->
<div class="dash-stats">
    <div class="dash-stat" style="--stat-color:#0071e3; --stat-bg:rgba(0,113,227,0.1);">
        <div class="dash-stat-top">
            <div class="dash-stat-icon"><i class="bi bi-calendar-day"></i></div>
        </div>
        <div class="dash-stat-value" data-count="<?= $todayLessons ?>">0</div>
        <div class="dash-stat-label">Занятий сегодня</div>
        <div class="dash-stat-sub">на неделе: <?= $weekLessons ?></div>
    </div>

    <div class="dash-stat" style="--stat-color:#30d158; --stat-bg:rgba(48,209,88,0.1);">
        <div class="dash-stat-top">
            <div class="dash-stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
        <div class="dash-stat-value"><span data-count="<?= $attendancePct ?>">0</span>%</div>
        <div class="dash-stat-label">Посещаемость</div>
        <div class="dash-stat-sub"><?= $presentMarked ?> из <?= $totalMarked ?></div>
    </div>

    <div class="dash-stat" style="--stat-color:#bf5af2; --stat-bg:rgba(191,90,242,0.1);">
        <div class="dash-stat-top">
            <div class="dash-stat-icon"><i class="bi bi-journal-check"></i></div>
        </div>
        <div class="dash-stat-value" data-count="<?= $submittedCnt ?>">0</div>
        <div class="dash-stat-label">Работ сдано</div>
        <div class="dash-stat-sub">за 30 дней</div>
    </div>

    <a href="debtors.php" class="dash-stat-link">
        <div class="dash-stat" style="--stat-color:#ff453a; --stat-bg:rgba(255,69,58,0.1);">
            <div class="dash-stat-top">
                <div class="dash-stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <i class="bi bi-arrow-right" style="color:#86868b; font-size:0.9rem;"></i>
            </div>
            <div class="dash-stat-value" data-count="<?= $totalDebtors ?>">0</div>
            <div class="dash-stat-label">Должников</div>
            <div class="dash-stat-sub">с пропусками ≥3</div>
        </div>
    </a>
</div>

<!-- Расписание на сегодня -->
<div class="dash-card" style="margin-bottom: 24px;">
    <div class="dash-card-header">
        <h3 class="dash-card-title">
            <i class="bi bi-calendar-event"></i>
            Расписание на сегодня
            <span style="font-size: 0.85em; color: #86868b; font-weight: 400;">
                · <?= ruDayFull($today) ?>, <?= date('d.m.Y') ?>
            </span>
        </h3>
        <a href="schedule.php" class="dash-card-badge">
            Всё расписание <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <?php if (empty($todaySchedule)): ?>
        <div class="dash-empty">
            <i class="bi bi-calendar-x"></i>
            <p style="margin: 0 0 16px;">На сегодня занятий нет 🎉</p>
            <a href="schedule.php?sync=1" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-repeat"></i> Синхронизировать
            </a>
        </div>
    <?php else: ?>
        <div class="dash-schedule">
            <?php foreach ($todaySchedule as $l):
                $t = $typeLabelsFull[$l['lesson_type']] ?? $typeLabelsFull['other'];
                $isCurrent = $currentLessonNum && (int)$l['lesson_number'] === $currentLessonNum;
                $isNext = !$isCurrent && $nextLessonNum && (int)$l['lesson_number'] === $nextLessonNum;
                $isPast = $l['lesson_time'] && $l['lesson_time'] < date('H:i:s') && !$isCurrent;

                $cardClass = 'sched-lesson';
                if ($isCurrent) $cardClass .= ' sched-lesson--current';
                elseif ($isNext) $cardClass .= ' sched-lesson--next';
                elseif ($isPast) $cardClass .= ' sched-lesson--past';
            ?>
                <div class="<?= $cardClass ?>">
                    <div class="sched-top">
                        <div class="sched-time">
                            <div class="sched-time-value">
                                <?= $l['lesson_time'] ? substr($l['lesson_time'], 0, 5) : '—' ?>
                                <?php if ($l['lesson_number']): ?>
                                    <span style="font-size: 0.7rem; color: #86868b; font-weight: 500;">
                                        · пара <?= $l['lesson_number'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="sched-time-sub">
                                <?= LESSON_TIMES[(int)$l['lesson_number']]['end'] ?? '' ?>
                            </div>
                        </div>
                        <div class="sched-badges">
                            <?php if ($isCurrent): ?>
                                <span class="sched-badge sched-badge--current">
                                    <i class="bi bi-broadcast"></i> Идёт сейчас
                                </span>
                            <?php elseif ($isNext): ?>
                                <span class="sched-badge sched-badge--next">
                                    <i class="bi bi-clock"></i> Следующая
                                </span>
                            <?php endif; ?>
                            <span class="badge bg-<?= $t['color'] ?>">
                                <i class="<?= $t['icon'] ?>"></i> <?= $t['label'] ?>
                            </span>
                        </div>
                    </div>

                    <h4 class="sched-title"><?= htmlspecialchars($l['discipline']) ?></h4>

                    <div class="sched-meta">
                        <?php if (!empty($l['groups_list'])): ?>
                            <span><i class="bi bi-people-fill"></i> <?= htmlspecialchars($l['groups_list']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($l['audience'])): ?>
                            <span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($l['audience']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($l['subgroup']) && $l['subgroup'] !== '0'): ?>
                            <span><i class="bi bi-diagram-3"></i> подгр. <?= htmlspecialchars($l['subgroup']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="sched-actions">
                        <a href="attendance.php?lesson_id=<?= $l['primary_id'] ?>" class="sched-btn sched-btn--primary">
                            <i class="bi bi-check2"></i> Отметить
                        </a>
                        <a href="works.php?lesson_id=<?= $l['primary_id'] ?>" class="sched-btn sched-btn--outline">
                            <i class="bi bi-journal-check"></i> Работы
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Моя сессия + Мои группы -->
<div class="dash-grid-2">

    <div class="dash-card">
        <div class="dash-card-header">
            <h3 class="dash-card-title">
                <i class="bi bi-mortarboard"></i>
                Моя сессия
            </h3>
            <a href="session.php" class="dash-card-badge">
                Все ведомости <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($mySheets)): ?>
            <div class="dash-empty">
                <i class="bi bi-file-earmark-plus"></i>
                <p style="margin: 0 0 12px;">Ведомостей пока нет</p>
                <a href="session.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle"></i> Создать ведомость
                </a>
            </div>
        <?php else: ?>
            <div class="dash-sheets">
                <?php foreach ($mySheets as $sh):
                    $t = $sessionTypesLabels[$sh['session_type']] ?? $sessionTypesLabels['zachet'];
                    $students = (int)$sh['students_cnt'];
                    $graded   = (int)$sh['graded_cnt'];
                    $progressPct = $students > 0 ? round($graded / $students * 100) : 0;
                ?>
                    <a href="session_sheet.php?id=<?= $sh['id'] ?>" class="dash-sheet">
                        <div class="dash-sheet-info">
                            <div class="dash-sheet-title">
                                <?= htmlspecialchars($sh['discipline']) ?>
                            </div>
                            <div class="dash-sheet-meta">
                                <span><i class="bi bi-people-fill"></i> <?= htmlspecialchars($sh['group_name']) ?></span>
                                <span class="dash-sheet-badge dash-sheet-badge--<?= $sh['status'] ?>">
                                    <?= $t['label'] ?>
                                </span>
                                <span><i class="bi bi-clipboard-check"></i> <?= $graded ?>/<?= $students ?></span>
                            </div>
                        </div>
                        <div class="dash-sheet-progress">
                            <div class="dash-sheet-progress-fill" style="width: <?= $progressPct ?>%;"></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h3 class="dash-card-title">
                <i class="bi bi-people-fill"></i>
                Мои группы
            </h3>
            <a href="groups.php" class="dash-card-badge">
                Все группы <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($myGroups)): ?>
            <div class="dash-empty">
                <i class="bi bi-people"></i>
                <p style="margin: 0;">Групп пока нет</p>
            </div>
        <?php else: ?>
            <div class="dash-groups">
                <?php foreach ($myGroups as $g): ?>
                    <a href="group_view.php?id=<?= $g['id'] ?>" class="dash-group-chip">
                        <i class="bi bi-people"></i>
                        <?= htmlspecialchars($g['name']) ?>
                        <span class="count"><?= $g['students_cnt'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Графики -->
<div class="dash-grid">
    <div class="dash-card">
        <div class="dash-card-header">
            <h3 class="dash-card-title">
                <i class="bi bi-graph-up-arrow"></i>
                Посещаемость по неделям
            </h3>
            <span class="dash-card-badge">8 недель</span>
        </div>
        <div class="chart-wrap chart-wrap--tall chart-clickable" onclick="openChartModal('week')">
            <canvas id="weekChart"></canvas>
        </div>
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h3 class="dash-card-title">
                <i class="bi bi-pie-chart-fill"></i>
                Типы занятий
            </h3>
        </div>
        <div class="chart-wrap chart-wrap--donut chart-clickable" onclick="openChartModal('type')">
            <canvas id="typeChart"></canvas>
        </div>
    </div>
</div>

<!-- Топ групп + Топ должников -->
<div class="dash-grid-2">
    <div class="dash-card">
        <div class="dash-card-header">
            <h3 class="dash-card-title">
                <i class="bi bi-trophy-fill"></i>
                Лучшие группы
            </h3>
            <span class="dash-card-badge">по посещаемости</span>
        </div>
        <div class="chart-wrap chart-clickable" onclick="openChartModal('groups')">
            <canvas id="groupChart"></canvas>
        </div>
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h3 class="dash-card-title">
                <i class="bi bi-exclamation-circle-fill" style="color:#ff453a;"></i>
                Требуют внимания
            </h3>
            <a href="debtors.php" class="dash-card-badge">
                Все должники <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($topAbsent)): ?>
            <div class="dash-empty">
                <i class="bi bi-check-circle"></i>
                Отлично! Должников нет.
            </div>
        <?php else: ?>
            <div class="dash-list">
                <?php foreach ($topAbsent as $s):
                    $href = !empty($s['group_id'])
                        ? 'debtors.php?group_id=' . (int)$s['group_id'] . '&min_absent=1'
                        : 'debtors.php';
                ?>
                    <a href="<?= htmlspecialchars($href) ?>" class="dash-list-item">
                        <div class="dash-avatar">
                            <?= mb_strtoupper(mb_substr($s['full_name'], 0, 1)) ?>
                        </div>
                        <div class="dash-list-info">
                            <div class="dash-list-name"><?= htmlspecialchars($s['full_name']) ?></div>
                            <div class="dash-list-meta">
                                <?= htmlspecialchars($s['group_name']) ?>
                                <?php if ($s['zachetka']): ?>
                                    · <?= htmlspecialchars($s['zachetka']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dash-list-value">
                            <?= $s['absent'] ?><small>из <?= $s['total'] ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модалка графиков -->
<div class="chart-modal-backdrop" id="chartModal">
    <div class="chart-modal">
        <div class="chart-modal-header">
            <h3 class="chart-modal-title" id="chartModalTitle">
                <i class="bi bi-graph-up"></i> График
            </h3>
            <button class="chart-modal-close" onclick="closeChartModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="chart-modal-body">
            <canvas id="modalChart"></canvas>
        </div>
    </div>
</div>

<script>
/* Анимация счётчиков */
(function() {
    const counters = document.querySelectorAll('[data-count]');
    const easeOut = t => 1 - Math.pow(1 - t, 3);
    counters.forEach(el => {
        const target = parseInt(el.dataset.count, 10) || 0;
        const start = performance.now();
        function tick(now) {
            const t = Math.min((now - start) / 1200, 1);
            el.textContent = Math.round(easeOut(t) * target);
            if (t < 1) requestAnimationFrame(tick);
            else el.textContent = target;
        }
        requestAnimationFrame(tick);
    });
})();

/* Chart.js */
const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor = () => isDark() ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
const textColor = () => isDark() ? '#a1a1a6' : '#86868b';

Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = textColor();

const weekData  = { labels: <?= json_encode($weekLabels) ?>, values: <?= json_encode($weekData) ?> };
const typeData  = { labels: <?= json_encode($typeLabels) ?>, values: <?= json_encode($typeData) ?> };
const groupData = { labels: <?= json_encode($groupLabels) ?>, values: <?= json_encode($groupData) ?> };

function createWeekChart(canvas) {
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0,113,227,0.25)');
    gradient.addColorStop(1, 'rgba(0,113,227,0.02)');

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels: weekData.labels,
            datasets: [{
                label: 'Посещаемость %',
                data: weekData.values,
                borderColor: '#0071e3',
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0071e3',
                pointBorderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 8,
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(29,29,31,0.95)',
                    padding: 12, cornerRadius: 10, displayColors: false,
                    callbacks: { label: ctx => ctx.parsed.y + '% посещаемость' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true, max: 100,
                    ticks: { callback: v => v + '%', color: textColor() },
                    grid: { color: gridColor(), drawBorder: false }
                },
                x: { ticks: { color: textColor() }, grid: { display: false } }
            }
        }
    });
}

function createTypeChart(canvas) {
    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: typeData.labels,
            datasets: [{
                data: typeData.values,
                backgroundColor: ['#0071e3', '#30d158', '#5ac8fa', '#ff9f0a', '#86868b'],
                borderWidth: 0, hoverOffset: 8,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 16, boxWidth: 10, boxHeight: 10,
                        usePointStyle: true, pointStyle: 'circle',
                        color: textColor(), font: { size: 11 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(29,29,31,0.95)',
                    padding: 12, cornerRadius: 10,
                    callbacks: {
                        label: ctx => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
                            return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}

function createGroupChart(canvas) {
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, '#30d158');
    gradient.addColorStop(1, '#0071e3');

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: groupData.labels,
            datasets: [{
                label: 'Посещаемость %',
                data: groupData.values,
                backgroundColor: gradient,
                borderRadius: 10,
                borderSkipped: false,
                barThickness: 36,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(29,29,31,0.95)',
                    padding: 12, cornerRadius: 10, displayColors: false,
                    callbacks: { label: ctx => ctx.parsed.x + '% посещаемость' }
                }
            },
            scales: {
                x: {
                    beginAtZero: true, max: 100,
                    ticks: { callback: v => v + '%', color: textColor() },
                    grid: { color: gridColor(), drawBorder: false }
                },
                y: { ticks: { color: textColor(), font: { weight: 600 } }, grid: { display: false } }
            }
        }
    });
}

const weekChart  = createWeekChart(document.getElementById('weekChart'));
const typeChart  = createTypeChart(document.getElementById('typeChart'));
const groupChart = createGroupChart(document.getElementById('groupChart'));

/* Модалка */
let modalChartInstance = null;

function openChartModal(type) {
    const modal = document.getElementById('chartModal');
    const title = document.getElementById('chartModalTitle');
    const canvas = document.getElementById('modalChart');

    if (modalChartInstance) {
        modalChartInstance.destroy();
        modalChartInstance = null;
    }
    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

    if (type === 'week')        title.innerHTML = '<i class="bi bi-graph-up-arrow"></i> Посещаемость по неделям';
    else if (type === 'type')   title.innerHTML = '<i class="bi bi-pie-chart-fill"></i> Типы занятий';
    else if (type === 'groups') title.innerHTML = '<i class="bi bi-trophy-fill"></i> Лучшие группы по посещаемости';

    modal.classList.add('show');
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
        if (type === 'week')   modalChartInstance = createWeekChart(canvas);
        if (type === 'type')   modalChartInstance = createTypeChart(canvas);
        if (type === 'groups') modalChartInstance = createGroupChart(canvas);
    }, 100);
}

function closeChartModal() {
    const modal = document.getElementById('chartModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
    if (modalChartInstance) {
        modalChartInstance.destroy();
        modalChartInstance = null;
    }
}

document.getElementById('chartModal').addEventListener('click', (e) => {
    if (e.target.id === 'chartModal') closeChartModal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeChartModal();
});
</script>

<?php require_once 'includes/footer.php'; ?>