<?php
$pageTitle = 'Проверка отчётов';
require_once 'config/database.php';
require_once 'includes/session_helpers.php';
require_once 'includes/access_control.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'teacher';
$allowedRoles = ['head_of_department', 'administration', 'admin'];

if (!in_array($userRole, $allowedRoles)) {
    require_once 'includes/header.php';
    echo '<div class="alert alert-danger"><i class="bi bi-shield-x"></i> Доступ запрещён</div>';
    require_once 'includes/footer.php';
    exit;
}

// ============================================================
// ОБЛАСТЬ ДОСТУПА
// ============================================================
$scope             = getAccessScope($pdo, $userId);
$allowedTeacherIds = getAllowedTeacherIds($pdo, $scope);
$allowedGroupIds   = getAllowedGroupIds($pdo, $scope, $allowedTeacherIds);

$teacherFilter = buildTeacherFilterSql($allowedTeacherIds, 'l.user_id');
$groupFilter   = buildGroupFilterSql($allowedGroupIds, 'l.group_id');

$scopeInfo = scopeLabel($scope);

$roleLabels = [
    'head_of_department' => ['Зав. кафедрой', 'warning'],
    'administration'     => ['Дирекция',       'success'],
    'admin'              => ['Администратор',  'danger'],
];
$roleLabel = $roleLabels[$userRole] ?? ['Проверяющий', 'secondary'];

// ============================================================
// ФУНКЦИИ HEATMAP
// ============================================================
function buildHeatmap(PDO $pdo, string $from, string $to, array $opts = []): array {
    $where  = [];
    $params = [];

    if (!empty($opts['group_id'])) {
        $where[]  = "l.group_id = ?";
        $params[] = (int)$opts['group_id'];
    }
    if (!empty($opts['subject'])) {
        $where[]  = "l.discipline = ?";
        $params[] = $opts['subject'];
    }
    if (!empty($opts['student_id'])) {
        $where[]  = "a.student_id = ?";
        $params[] = (int)$opts['student_id'];
    }
    if (!empty($opts['teacher_ids'])) {
        $in = implode(',', array_fill(0, count($opts['teacher_ids']), '?'));
        $where[] = "l.user_id IN ($in)";
        foreach ($opts['teacher_ids'] as $t) $params[] = $t;
    }
    if (!empty($opts['group_ids'])) {
        $in = implode(',', array_fill(0, count($opts['group_ids']), '?'));
        $where[] = "l.group_id IN ($in)";
        foreach ($opts['group_ids'] as $g) $params[] = $g;
    }

    $whereSql = $where ? (' AND ' . implode(' AND ', $where)) : '';

    $sql = "
        SELECT
            l.lesson_date AS d,
            COUNT(a.id) AS total,
            SUM(a.is_present) AS present
        FROM lessons l
        JOIN attendance a ON a.lesson_id = l.id
        WHERE l.lesson_date BETWEEN ? AND ?
        $whereSql
        GROUP BY l.lesson_date
        ORDER BY l.lesson_date
    ";
    $params = array_merge([$from, $to], $params);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $byDate = [];
    foreach ($rows as $r) {
        $byDate[$r['d']] = [
            'total'   => (int)$r['total'],
            'present' => (int)$r['present'],
        ];
    }

    $start = new DateTime($from);
    $end   = new DateTime($to);
    $start->modify('monday this week');
    $end->modify('sunday this week');

    $weeks = [];
    $cursor = clone $start;
    while ($cursor <= $end) {
        $weekStart = clone $cursor;
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = (clone $cursor)->format('Y-m-d');
            $dow  = (int)(clone $cursor)->format('N');
            $isWeekend = $dow >= 6;

            $stat = $byDate[$date] ?? null;
            $pct  = null;
            if ($stat && $stat['total'] > 0) {
                $pct = (int)round($stat['present'] / $stat['total'] * 100);
            }

            $days[] = [
                'date'       => $date,
                'count'      => $stat['total']   ?? 0,
                'present'    => $stat['present'] ?? 0,
                'pct'        => $pct,
                'is_weekend' => $isWeekend,
            ];
            $cursor->modify('+1 day');
        }
        $weeks[] = [
            'week_start' => $weekStart->format('Y-m-d'),
            'days'       => $days,
        ];
    }

    return ['weeks' => $weeks];
}

function heatColor(?int $pct): string {
    if ($pct === null) return 'hm-empty';
    if ($pct >= 90)    return 'hm-90';
    if ($pct >= 75)    return 'hm-75';
    if ($pct >= 50)    return 'hm-50';
    if ($pct >= 25)    return 'hm-25';
    return 'hm-0';
}

// ============================================================
// AJAX: heatmap для конкретного студента
// ============================================================
if (($_GET['action'] ?? '') === 'heatmap_student'
    && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $sId   = (int)($_GET['student_id'] ?? 0);
    $gId   = (int)($_GET['group_id'] ?? 0);
    $hFrom = $_GET['from'] ?? date('Y-m-01');
    $hTo   = $_GET['to']   ?? date('Y-m-d');

    if (!$gId) {
        echo json_encode(['ok' => false, 'error' => 'Нет группы']);
        exit;
    }

    if ($allowedGroupIds !== null && !in_array($gId, $allowedGroupIds)) {
        echo json_encode(['ok' => false, 'error' => 'Нет доступа']);
        exit;
    }

    $opts = ['group_id' => $gId];
    if ($allowedTeacherIds !== null) $opts['teacher_ids'] = $allowedTeacherIds;
    if ($sId > 0) $opts['student_id'] = $sId;

    $hm = buildHeatmap($pdo, $hFrom, $hTo, $opts);

    echo json_encode(['ok' => true, 'weeks' => $hm['weeks']]);
    exit;
}

// ============================================================
// ФИЛЬТРЫ
// ============================================================
$viewMode = $_GET['view'] ?? 'overview';

$today = date('Y-m-d');
$year  = (int)date('Y');
$month = (int)date('n');
$defaultFrom = ($month >= 9) ? "$year-09-01" : ($year - 1) . "-09-01";

$from = $_GET['from'] ?? $defaultFrom;
$to   = $_GET['to']   ?? $today;

$groupId  = (int)($_GET['group_id'] ?? 0);
$subject  = trim($_GET['subject'] ?? '');
$onlyMine = !empty($_GET['only_mine']);

if ($groupId > 0 && $allowedGroupIds !== null && !in_array($groupId, $allowedGroupIds)) {
    $groupId = 0;
}

// ============================================================
// СПРАВОЧНИКИ (только доступные)
// ============================================================
if ($allowedGroupIds === null) {
    $groups = $pdo->query("SELECT id, name FROM groups_table ORDER BY name")->fetchAll();
} elseif (empty($allowedGroupIds)) {
    $groups = [];
} else {
    $in = implode(',', array_fill(0, count($allowedGroupIds), '?'));
    $stmt = $pdo->prepare("SELECT id, name FROM groups_table WHERE id IN ($in) ORDER BY name");
    $stmt->execute($allowedGroupIds);
    $groups = $stmt->fetchAll();
}

// Предметы
$subjects = [];
$subjWhere = [];
$subjParams = [];
$blockSubjects = false;

if ($allowedTeacherIds !== null) {
    if (empty($allowedTeacherIds)) {
        $blockSubjects = true;
    } else {
        $in = implode(',', array_fill(0, count($allowedTeacherIds), '?'));
        $subjWhere[] = "user_id IN ($in)";
        foreach ($allowedTeacherIds as $t) $subjParams[] = $t;
    }
}
if (!$blockSubjects && $allowedGroupIds !== null) {
    if (empty($allowedGroupIds)) {
        $blockSubjects = true;
    } else {
        $in = implode(',', array_fill(0, count($allowedGroupIds), '?'));
        $subjWhere[] = "group_id IN ($in)";
        foreach ($allowedGroupIds as $g) $subjParams[] = $g;
    }
}

if (!$blockSubjects) {
    $sql = "SELECT DISTINCT discipline FROM lessons";
    if ($subjWhere) $sql .= " WHERE " . implode(' AND ', $subjWhere);
    $sql .= " ORDER BY discipline";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($subjParams);
    $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ============================================================
// РЕЖИМ 1: ОБЗОР
// ============================================================
$overviewRows = [];

if ($viewMode === 'overview') {
    if ($allowedGroupIds === null) {
        $groupList = $pdo->query("
            SELECT g.id, g.name, g.institute, COUNT(DISTINCT s.id) AS cnt
            FROM groups_table g
            LEFT JOIN students s ON s.group_id = g.id
            GROUP BY g.id
            ORDER BY g.name
        ")->fetchAll();
    } elseif (empty($allowedGroupIds)) {
        $groupList = [];
    } else {
        $in = implode(',', array_fill(0, count($allowedGroupIds), '?'));
        $stmt = $pdo->prepare("
            SELECT g.id, g.name, g.institute, COUNT(DISTINCT s.id) AS cnt
            FROM groups_table g
            LEFT JOIN students s ON s.group_id = g.id
            WHERE g.id IN ($in)
            GROUP BY g.id
            ORDER BY g.name
        ");
        $stmt->execute($allowedGroupIds);
        $groupList = $stmt->fetchAll();
    }

    if (!empty($groupList)) {
        $groupIdsFlat = array_column($groupList, 'id');
        $inG = implode(',', array_fill(0, count($groupIdsFlat), '?'));

        $whereMine = '';
        $mineParams = [];
        if ($allowedTeacherIds !== null) {
            if (empty($allowedTeacherIds)) {
                $whereMine = " AND 1 = 0 ";
            } else {
                $in = implode(',', array_fill(0, count($allowedTeacherIds), '?'));
                $whereMine = " AND l.user_id IN ($in) ";
                $mineParams = $allowedTeacherIds;
            }
        }

        // Метрики по группам
        $stmt = $pdo->prepare("
            SELECT
                l.group_id,
                COUNT(DISTINCT l.lesson_date, l.lesson_number, l.discipline,
                      COALESCE(l.audience,''), COALESCE(l.subgroup,'0')) AS lessons_cnt,
                COUNT(a.id) AS total_marks,
                COALESCE(SUM(a.is_present), 0) AS present_marks
            FROM lessons l
            LEFT JOIN attendance a ON a.lesson_id = l.id
            WHERE l.group_id IN ($inG)
              AND l.lesson_date BETWEEN ? AND ?
              $whereMine
            GROUP BY l.group_id
        ");
        $stmt->execute(array_merge($groupIdsFlat, [$from, $to], $mineParams));
        $metrics = [];
        foreach ($stmt->fetchAll() as $r) {
            $metrics[$r['group_id']] = $r;
        }

        // Работы
        $stmt = $pdo->prepare("
            SELECT l.group_id, COUNT(DISTINCT sw.work_number, sw.student_id) AS works_cnt
            FROM lessons l
            JOIN submitted_works sw ON sw.lesson_id = l.id
            WHERE l.group_id IN ($inG)
              AND l.lesson_date BETWEEN ? AND ?
              $whereMine
            GROUP BY l.group_id
        ");
        $stmt->execute(array_merge($groupIdsFlat, [$from, $to], $mineParams));
        $works = [];
        foreach ($stmt->fetchAll() as $r) {
            $works[$r['group_id']] = (int)$r['works_cnt'];
        }

        foreach ($groupList as $g) {
            $m = $metrics[$g['id']] ?? null;
            $total   = (int)($m['total_marks'] ?? 0);
            $present = (int)($m['present_marks'] ?? 0);
            $pct     = $total > 0 ? (int)round($present / $total * 100) : null;

            $weekly = [];
            $stmt = $pdo->prepare("
                SELECT
                    YEARWEEK(l.lesson_date, 1) AS week,
                    MIN(l.lesson_date) AS week_start,
                    COUNT(a.id) AS total,
                    SUM(a.is_present) AS present
                FROM lessons l
                JOIN attendance a ON a.lesson_id = l.id
                WHERE l.group_id = ?
                  AND l.lesson_date BETWEEN ? AND ?
                  $whereMine
                GROUP BY YEARWEEK(l.lesson_date, 1)
                ORDER BY week
            ");
            $stmt->execute(array_merge([$g['id'], $from, $to], $mineParams));
            foreach ($stmt->fetchAll() as $w) {
                $t = (int)$w['total'];
                $p = (int)$w['present'];
                $weekly[] = [
                    'week_start' => $w['week_start'],
                    'pct'        => $t > 0 ? (int)round($p / $t * 100) : null,
                ];
            }

            $overviewRows[] = [
                'group'         => $g,
                'lessons_cnt'   => (int)($m['lessons_cnt'] ?? 0),
                'total_marks'   => $total,
                'present_marks' => $present,
                'pct'           => $pct,
                'works_cnt'     => $works[$g['id']] ?? 0,
                'weekly'        => $weekly,
            ];
        }

        usort($overviewRows, function($a, $b) {
            $pa = $a['pct'] ?? 999;
            $pb = $b['pct'] ?? 999;
            return $pa <=> $pb;
        });
    }
}

// ============================================================
// РЕЖИМ 2: ГРУППА
// ============================================================
$groupReport = null;

if ($viewMode === 'group' && $groupId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM groups_table WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupInfo = $stmt->fetch();

    if ($groupInfo) {
        $stmt = $pdo->prepare("
            SELECT id, full_name, zachetka, subgroup
            FROM students WHERE group_id = ? ORDER BY full_name
        ");
        $stmt->execute([$groupId]);
        $studentsInGroup = $stmt->fetchAll();

        $hmOpts = ['group_id' => $groupId];
        if ($allowedTeacherIds !== null) $hmOpts['teacher_ids'] = $allowedTeacherIds;
        $heatmap = buildHeatmap($pdo, $from, $to, $hmOpts);

        // Сводка по предметам
        $whereExtra = '';
        $extraParams = [];
        if ($allowedTeacherIds !== null) {
            if (empty($allowedTeacherIds)) {
                $whereExtra = " AND 1 = 0 ";
            } else {
                $in = implode(',', array_fill(0, count($allowedTeacherIds), '?'));
                $whereExtra = " AND l.user_id IN ($in) ";
                $extraParams = $allowedTeacherIds;
            }
        }

        $stmt = $pdo->prepare("
            SELECT
                l.discipline,
                COUNT(DISTINCT l.lesson_date, l.lesson_number,
                      COALESCE(l.audience,''), COALESCE(l.subgroup,'0')) AS lessons_cnt,
                GROUP_CONCAT(DISTINCT u.full_name ORDER BY u.full_name SEPARATOR ', ') AS teachers,
                COUNT(a.id) AS total_marks,
                COALESCE(SUM(a.is_present), 0) AS present_marks
            FROM lessons l
            LEFT JOIN users u ON l.user_id = u.id
            LEFT JOIN attendance a ON a.lesson_id = l.id
            WHERE l.group_id = ? AND l.lesson_date BETWEEN ? AND ?
              $whereExtra
            GROUP BY l.discipline
            ORDER BY l.discipline
        ");
        $stmt->execute(array_merge([$groupId, $from, $to], $extraParams));
        $bySubjectRows = $stmt->fetchAll();

        // Работы по предметам
        $worksBySubject = [];
        $stmt = $pdo->prepare("
            SELECT l.discipline, COUNT(DISTINCT sw.work_number) AS works_cnt
            FROM lessons l
            JOIN submitted_works sw ON sw.lesson_id = l.id
            WHERE l.group_id = ? AND l.lesson_date BETWEEN ? AND ?
              $whereExtra
            GROUP BY l.discipline
        ");
        $stmt->execute(array_merge([$groupId, $from, $to], $extraParams));
        foreach ($stmt->fetchAll() as $r) {
            $worksBySubject[$r['discipline']] = (int)$r['works_cnt'];
        }

        $subjectSummaries = [];
        foreach ($bySubjectRows as $r) {
            $total   = (int)$r['total_marks'];
            $present = (int)$r['present_marks'];
            $subjectSummaries[] = [
                'discipline'    => $r['discipline'],
                'lessons_cnt'   => (int)$r['lessons_cnt'],
                'teachers'      => $r['teachers'] ?? '—',
                'total_marks'   => $total,
                'present_marks' => $present,
                'pct'           => $total > 0 ? (int)round($present / $total * 100) : null,
                'works_cnt'     => $worksBySubject[$r['discipline']] ?? 0,
            ];
        }

        // Статистика по студентам
        $stmt = $pdo->prepare("
            SELECT id FROM lessons
            WHERE group_id = ? AND lesson_date BETWEEN ? AND ?
              " . str_replace('l.user_id', 'user_id', $whereExtra) . "
        ");
        $stmt->execute(array_merge([$groupId, $from, $to], $extraParams));
        $lessonIds = array_column($stmt->fetchAll(), 'id');

        $attendance = [];
        $worksByStudent = [];
        $allWorks = [];

        if (!empty($lessonIds)) {
            $in = implode(',', array_fill(0, count($lessonIds), '?'));

            $stmt = $pdo->prepare("
                SELECT student_id, lesson_id, is_present
                FROM attendance WHERE lesson_id IN ($in)
            ");
            $stmt->execute($lessonIds);
            foreach ($stmt->fetchAll() as $row) {
                $attendance[$row['student_id']][$row['lesson_id']] = (int)$row['is_present'];
            }

            $stmt = $pdo->prepare("
                SELECT DISTINCT student_id, work_number
                FROM submitted_works WHERE lesson_id IN ($in)
                ORDER BY work_number
            ");
            $stmt->execute($lessonIds);
            foreach ($stmt->fetchAll() as $row) {
                $worksByStudent[$row['student_id']][] = $row['work_number'];
                if (!in_array($row['work_number'], $allWorks)) {
                    $allWorks[] = $row['work_number'];
                }
            }
        }
        sort($allWorks);

        $studentStats = [];
        foreach ($studentsInGroup as $s) {
            $present = 0; $absent = 0; $unmarked = 0;
            $byLesson = [];
            foreach ($lessonIds as $lid) {
                $mark = $attendance[$s['id']][$lid] ?? null;
                $byLesson[$lid] = $mark;
                if ($mark === 1) $present++;
                elseif ($mark === 0) $absent++;
                else $unmarked++;
            }
            $marked = $present + $absent;
            $works = array_values(array_unique($worksByStudent[$s['id']] ?? []));

            $studentStats[] = [
                'student'   => $s,
                'present'   => $present,
                'absent'    => $absent,
                'unmarked'  => $unmarked,
                'pct'       => $marked > 0 ? (int)round($present / $marked * 100) : null,
                'works_cnt' => count($works),
                'works'     => $works,
                'by_lesson' => $byLesson,
            ];
        }

        usort($studentStats, function($a, $b) {
            return strcoll(
                mb_strtolower($a['student']['full_name']),
                mb_strtolower($b['student']['full_name'])
            );
        });

        $totalPresent = array_sum(array_column($studentStats, 'present'));
        $totalAbsent  = array_sum(array_column($studentStats, 'absent'));
        $avgPct = ($totalPresent + $totalAbsent) > 0
            ? (int)round($totalPresent / ($totalPresent + $totalAbsent) * 100)
            : null;

        $groupReport = [
            'group'            => $groupInfo,
            'students'         => $studentsInGroup,
            'heatmap'          => $heatmap,
            'subjectSummaries' => $subjectSummaries,
            'studentStats'     => $studentStats,
            'allWorks'         => $allWorks,
            'lessonIds'        => $lessonIds,
            'totalPresent'     => $totalPresent,
            'totalAbsent'      => $totalAbsent,
            'avgPct'           => $avgPct,
            'totalWorksAll'    => array_sum(array_column($studentStats, 'works_cnt')),
        ];
    }
}

// ============================================================
// РЕЖИМ 3: ПРЕДМЕТ
// ============================================================
$subjectReport = null;

if ($viewMode === 'subject' && $subject !== '') {
    $whereExtra = '';
    $extraParams = [];
    if ($allowedTeacherIds !== null) {
        if (empty($allowedTeacherIds)) {
            $whereExtra = " AND 1 = 0 ";
        } else {
            $in = implode(',', array_fill(0, count($allowedTeacherIds), '?'));
            $whereExtra = " AND l.user_id IN ($in) ";
            $extraParams = array_merge($extraParams, $allowedTeacherIds);
        }
    }
    if ($allowedGroupIds !== null) {
        if (empty($allowedGroupIds)) {
            $whereExtra .= " AND 1 = 0 ";
        } else {
            $in = implode(',', array_fill(0, count($allowedGroupIds), '?'));
            $whereExtra .= " AND l.group_id IN ($in) ";
            $extraParams = array_merge($extraParams, $allowedGroupIds);
        }
    }

    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT l.group_id) AS groups_cnt,
            COUNT(DISTINCT l.lesson_date, l.lesson_number,
                  COALESCE(l.audience,''), COALESCE(l.subgroup,'0')) AS lessons_cnt,
            COUNT(a.id) AS total_marks,
            COALESCE(SUM(a.is_present), 0) AS present_marks
        FROM lessons l
        LEFT JOIN attendance a ON a.lesson_id = l.id
        WHERE l.discipline = ? AND l.lesson_date BETWEEN ? AND ?
          $whereExtra
    ");
    $stmt->execute(array_merge([$subject, $from, $to], $extraParams));
    $summary = $stmt->fetch();

    $hmOpts = ['subject' => $subject];
    if ($allowedTeacherIds !== null) $hmOpts['teacher_ids'] = $allowedTeacherIds;
    if ($allowedGroupIds !== null)   $hmOpts['group_ids']   = $allowedGroupIds;
    $heatmap = buildHeatmap($pdo, $from, $to, $hmOpts);

    // По группам
    $stmt = $pdo->prepare("
        SELECT
            g.id AS group_id,
            g.name AS group_name,
            GROUP_CONCAT(DISTINCT u.full_name ORDER BY u.full_name SEPARATOR ', ') AS teachers,
            COUNT(DISTINCT l.lesson_date, l.lesson_number,
                  COALESCE(l.audience,''), COALESCE(l.subgroup,'0')) AS lessons_cnt,
            COUNT(a.id) AS total_marks,
            COALESCE(SUM(a.is_present), 0) AS present_marks
        FROM groups_table g
        JOIN lessons l ON l.group_id = g.id
        LEFT JOIN users u ON l.user_id = u.id
        LEFT JOIN attendance a ON a.lesson_id = l.id
        WHERE l.discipline = ?
          AND l.lesson_date BETWEEN ? AND ?
          $whereExtra
        GROUP BY g.id
        ORDER BY g.name
    ");
    $stmt->execute(array_merge([$subject, $from, $to], $extraParams));
    $groupRows = $stmt->fetchAll();

    $groupStats = [];
    foreach ($groupRows as $r) {
        $total   = (int)$r['total_marks'];
        $present = (int)$r['present_marks'];
        $groupStats[] = [
            'group_id'      => (int)$r['group_id'],
            'group_name'    => $r['group_name'],
            'teachers'      => $r['teachers'] ?? '—',
            'lessons_cnt'   => (int)$r['lessons_cnt'],
            'total_marks'   => $total,
            'present_marks' => $present,
            'pct'           => $total > 0 ? (int)round($present / $total * 100) : null,
        ];
    }

    // По студентам
    $stmt = $pdo->prepare("
        SELECT
            s.id, s.full_name, s.zachetka, s.subgroup,
            g.name AS group_name,
            COUNT(a.id) AS total_marks,
            COALESCE(SUM(a.is_present), 0) AS present_marks
        FROM students s
        JOIN groups_table g ON s.group_id = g.id
        JOIN lessons l ON l.group_id = g.id
        LEFT JOIN attendance a ON a.lesson_id = l.id AND a.student_id = s.id
        WHERE l.discipline = ?
          AND l.lesson_date BETWEEN ? AND ?
          $whereExtra
        GROUP BY s.id
        ORDER BY g.name, s.full_name
    ");
    $stmt->execute(array_merge([$subject, $from, $to], $extraParams));
    $studentRows = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT DISTINCT sw.student_id, sw.work_number
        FROM submitted_works sw
        JOIN lessons l ON sw.lesson_id = l.id
        WHERE l.discipline = ?
          AND l.lesson_date BETWEEN ? AND ?
          $whereExtra
    ");
    $stmt->execute(array_merge([$subject, $from, $to], $extraParams));
    $worksByStudent = [];
    $allWorks = [];
    foreach ($stmt->fetchAll() as $row) {
        $worksByStudent[$row['student_id']][] = $row['work_number'];
        if (!in_array($row['work_number'], $allWorks)) {
            $allWorks[] = $row['work_number'];
        }
    }
    sort($allWorks);

    $studentStats = [];
    foreach ($studentRows as $r) {
        $total   = (int)$r['total_marks'];
        $present = (int)$r['present_marks'];
        $studentStats[] = [
            'student' => [
                'id'         => (int)$r['id'],
                'full_name'  => $r['full_name'],
                'zachetka'   => $r['zachetka'],
                'subgroup'   => $r['subgroup'],
                'group_name' => $r['group_name'],
            ],
            'total_marks'   => $total,
            'present_marks' => $present,
            'absent_marks'  => $total - $present,
            'pct'           => $total > 0 ? (int)round($present / $total * 100) : null,
            'works_cnt'     => count(array_unique($worksByStudent[$r['id']] ?? [])),
        ];
    }

    $total   = (int)$summary['total_marks'];
    $present = (int)$summary['present_marks'];

    $subjectReport = [
        'subject'      => $subject,
        'summary'      => [
            'groups_cnt'    => (int)$summary['groups_cnt'],
            'lessons_cnt'   => (int)$summary['lessons_cnt'],
            'total_marks'   => $total,
            'present_marks' => $present,
            'avg_pct'       => $total > 0 ? (int)round($present / $total * 100) : null,
        ],
        'heatmap'      => $heatmap,
        'groupStats'   => $groupStats,
        'studentStats' => $studentStats,
        'allWorks'     => $allWorks,
    ];
}

require_once 'includes/header.php';
?>

<style>
/* ============================================================
   CHECK REPORTS — ADMIN EDITION
   ============================================================ */
.chk-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.chk-header h2 {
    font-size: 1.7rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 6px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.chk-header p {
    color: #86868b;
    margin: 0;
    font-size: 0.95rem;
}
.chk-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    background: rgba(0,113,227,0.1);
    color: #0071e3;
}
.chk-scope-badge {
    background: rgba(255,159,10,0.15);
    color: #b25c00;
}

/* Табы */
.chk-tabs {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    padding: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 6px;
    overflow-x: auto;
}
.chk-tab {
    flex: 1;
    min-width: 140px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 14px;
    background: transparent;
    border: none;
    color: #86868b;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
    white-space: nowrap;
}
.chk-tab:hover {
    background: #f5f5f7;
    color: #1d1d1f;
}
.chk-tab--active {
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    box-shadow: 0 6px 16px rgba(0,113,227,0.3);
}
.chk-tab--active:hover {
    color: #fff;
    transform: translateY(-1px);
}
.chk-tab i { font-size: 1rem; }

/* Фильтры */
.chk-filter {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    padding: 20px 24px;
    margin-bottom: 20px;
}
.chk-filter-row {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1fr auto;
    gap: 12px;
    align-items: end;
}
@media (max-width: 991px) { .chk-filter-row { grid-template-columns: 1fr 1fr; } }
@media (max-width: 575px) { .chk-filter-row { grid-template-columns: 1fr; } }
.chk-filter-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #1d1d1f;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.chk-filter-label i { color: #0071e3; margin-right: 4px; }
.chk-filter-label .req { color: #ff453a; }
.chk-select, .chk-input {
    width: 100%;
    padding: 11px 14px;
    border-radius: 10px;
    border: 1.5px solid #e5e5ea;
    background: #fff;
    font-size: 0.9rem;
    font-family: inherit;
    outline: none;
    transition: all 0.2s;
}
.chk-select:focus, .chk-input:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 4px rgba(0,113,227,0.12);
}
.chk-submit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 24px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    border: none;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.25s;
    box-shadow: 0 6px 16px rgba(0,113,227,0.25);
    white-space: nowrap;
}
.chk-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,113,227,0.4);
}
.chk-filter-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f2;
    align-items: center;
}
.chk-action-btn {
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
    text-decoration: none;
}
.chk-action-btn--reset { color: #495057; border-color: #d2d2d7; }
.chk-action-btn--reset:hover {
    background: #495057; color: #fff; border-color: #495057;
    transform: translateY(-2px);
}
.chk-switch {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #495057;
    cursor: pointer;
    user-select: none;
}
.chk-switch input {
    width: 18px; height: 18px;
    cursor: pointer;
    accent-color: #0071e3;
}

/* Карточки статистики */
.chk-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.chk-stat {
    background: #fff;
    border-radius: 18px;
    padding: 18px 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
}
.chk-stat::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: var(--chk-color, #0071e3);
}
.chk-stat:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}
.chk-stat-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: var(--chk-bg, rgba(0,113,227,0.1));
    color: var(--chk-color, #0071e3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-bottom: 10px;
}
.chk-stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.02em;
    color: #1d1d1f;
    margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
}
.chk-stat-label {
    font-size: 0.8rem;
    color: #86868b;
    font-weight: 500;
}
.chk-stat-sub {
    font-size: 0.72rem;
    color: #86868b;
    margin-top: 4px;
}

/* Карточка */
.chk-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 20px;
}
.chk-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f0f0f2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    background: linear-gradient(135deg, #fafafc 0%, #fff 100%);
}
.chk-card-title {
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.chk-card-title i { color: #0071e3; }
.chk-card-badge {
    font-size: 0.72rem;
    font-weight: 600;
    color: #86868b;
    background: #f5f5f7;
    padding: 4px 12px;
    border-radius: 20px;
}

/* Таблицы */
.chk-table-wrap { overflow-x: auto; }
.chk-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}
.chk-table thead th {
    background: #fafafc;
    padding: 12px 14px;
    text-align: left;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #86868b;
    border-bottom: 1px solid #f0f0f2;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 1;
}
.chk-table thead th.center { text-align: center; }
.chk-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f5f5f7;
    vertical-align: middle;
}
.chk-table tbody tr:last-child td { border-bottom: none; }
.chk-table tbody tr:hover { background: #fafafc; }
.chk-table tbody tr.chk-row-danger { background: rgba(255,69,58,0.05); }
.chk-table tbody tr.chk-row-warning { background: rgba(255,159,10,0.05); }
.chk-table .num { text-align: center; font-variant-numeric: tabular-nums; }
.chk-table .group-name {
    font-weight: 700;
    color: #1d1d1f;
    text-decoration: none;
}
.chk-table .group-name:hover { color: #0071e3; }
.chk-table .student-name { font-weight: 600; color: #1d1d1f; }
.chk-table code {
    background: rgba(0,113,227,0.08);
    color: #0071e3;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-family: "SF Mono", Monaco, monospace;
    font-weight: 600;
}

/* Прогресс */
.chk-progress {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 100px;
}
.chk-progress-bar {
    flex: 1;
    height: 6px;
    background: #f0f0f2;
    border-radius: 3px;
    overflow: hidden;
    min-width: 50px;
}
.chk-progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.4s;
}
.chk-progress-fill--success { background: #30d158; }
.chk-progress-fill--warning { background: #ff9f0a; }
.chk-progress-fill--danger  { background: #ff453a; }
.chk-progress-fill--secondary { background: #86868b; }
.chk-progress-text {
    font-size: 0.75rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    min-width: 40px;
    text-align: right;
}

/* ============================================================
   HEATMAP
   ============================================================ */
.hm-wrap {
    padding: 20px 24px;
    overflow-x: auto;
}
.hm-months {
    display: grid;
    gap: 3px;
    margin-bottom: 6px;
    padding-left: 40px;
    min-width: max-content;
}
.hm-month {
    font-size: 0.7rem;
    font-weight: 600;
    color: #86868b;
    text-align: left;
    white-space: nowrap;
}
.hm-body {
    display: flex;
    gap: 8px;
    min-width: max-content;
}
.hm-days {
    display: grid;
    grid-template-rows: repeat(7, 14px);
    gap: 3px;
    font-size: 0.65rem;
    color: #86868b;
    font-weight: 600;
}
.hm-day-label {
    height: 14px;
    line-height: 14px;
    display: flex;
    align-items: center;
}
.hm-grid {
    display: grid;
    grid-auto-flow: column;
    grid-template-rows: repeat(7, 14px);
    gap: 3px;
}
.hm-cell {
    width: 14px;
    height: 14px;
    border-radius: 3px;
    background: #ebedf0;
    cursor: pointer;
    transition: all 0.15s;
}
.hm-cell:hover {
    transform: scale(1.4);
    z-index: 10;
    box-shadow: 0 0 0 2px #0071e3;
}
.hm-cell.hm-empty { background: #ebedf0; }
.hm-cell.hm-0     { background: #ff453a; }
.hm-cell.hm-25    { background: #ff9f0a; }
.hm-cell.hm-50    { background: #ffd60a; }
.hm-cell.hm-75    { background: #a8e063; }
.hm-cell.hm-90    { background: #30d158; }
.hm-cell.hm-future {
    background: repeating-linear-gradient(45deg, #f5f5f7, #f5f5f7 2px, #ebedf0 2px, #ebedf0 4px);
    opacity: 0.5;
}
.hm-legend {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-top: 1px solid #f0f0f2;
    font-size: 0.75rem;
    color: #86868b;
    flex-wrap: wrap;
    background: #fafafc;
}
.hm-legend-label { font-weight: 600; }
.hm-legend-scale { display: flex; gap: 3px; align-items: center; }
.hm-legend-cell { width: 14px; height: 14px; border-radius: 3px; }
.hm-tooltip {
    position: fixed;
    background: #1d1d1f;
    color: #fff;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 0.78rem;
    line-height: 1.4;
    pointer-events: none;
    z-index: 99999;
    opacity: 0;
    transition: opacity 0.15s;
    box-shadow: 0 8px 24px rgba(0,0,0,0.35);
    white-space: nowrap;
}
.hm-tooltip.show { opacity: 1; }
.hm-student-selector {
    padding: 14px 24px;
    border-bottom: 1px solid #f0f0f2;
    background: #fafafc;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}
.hm-student-selector label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #1d1d1f;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.hm-student-selector label i { color: #0071e3; }
.hm-student-selector select {
    padding: 8px 14px;
    border-radius: 10px;
    border: 1.5px solid #e5e5ea;
    background: #fff;
    font-size: 0.88rem;
    font-family: inherit;
    outline: none;
    min-width: 260px;
    cursor: pointer;
}
.hm-mini { display: inline-flex; gap: 2px; align-items: center; }
.hm-mini-cell { width: 10px; height: 20px; border-radius: 2px; background: #ebedf0; }

.chk-empty {
    padding: 60px 24px;
    text-align: center;
    color: #86868b;
}
.chk-empty i {
    font-size: 3rem;
    opacity: 0.3;
    display: block;
    margin-bottom: 12px;
}
.chk-hint {
    padding: 12px 24px;
    background: rgba(0,113,227,0.06);
    border-bottom: 1px solid #f0f0f2;
    font-size: 0.82rem;
    color: #084298;
    display: flex;
    align-items: center;
    gap: 10px;
}
.chk-hint i { font-size: 1.05rem; }

/* Тёмная тема */
[data-theme="dark"] .chk-tabs,
[data-theme="dark"] .chk-filter,
[data-theme="dark"] .chk-stat,
[data-theme="dark"] .chk-card {
    background: #1c1c1e;
    border-color: #2c2c2e;
}
[data-theme="dark"] .chk-tab:hover { background: #2c2c2e; color: #f5f5f7; }
[data-theme="dark"] .chk-header h2,
[data-theme="dark"] .chk-filter-label,
[data-theme="dark"] .chk-stat-value,
[data-theme="dark"] .chk-card-title,
[data-theme="dark"] .chk-table .group-name,
[data-theme="dark"] .chk-table .student-name {
    color: #f5f5f7;
}
[data-theme="dark"] .chk-select,
[data-theme="dark"] .chk-input,
[data-theme="dark"] .hm-student-selector select {
    background: #2c2c2e;
    border-color: #3c3c3e;
    color: #f5f5f7;
}
[data-theme="dark"] .chk-card-header,
[data-theme="dark"] .chk-table thead th,
[data-theme="dark"] .hm-student-selector,
[data-theme="dark"] .hm-legend {
    background: #2c2c2e;
    border-color: #3c3c3e;
    color: #a1a1a6;
}
[data-theme="dark"] .chk-table tbody td { border-color: #2c2c2e; }
[data-theme="dark"] .chk-table tbody tr:hover { background: #2c2c2e; }
[data-theme="dark"] .chk-card-badge { background: #2c2c2e; color: #a1a1a6; }
[data-theme="dark"] .chk-progress-bar { background: #3c3c3e; }
[data-theme="dark"] .chk-hint {
    background: rgba(0,113,227,0.12);
    color: #6ea8ff;
    border-color: #2c2c2e;
}
[data-theme="dark"] .hm-cell,
[data-theme="dark"] .hm-cell.hm-empty { background: #2c2c2e; }
[data-theme="dark"] .hm-mini-cell { background: #2c2c2e; }
[data-theme="dark"] .chk-action-btn--reset {
    background: #2c2c2e;
    color: #f5f5f7;
    border-color: #3c3c3e;
}
</style>

<!-- Шапка -->
<div class="chk-header">
    <div>
        <h2>
            <i class="bi bi-clipboard-check"></i>
            Проверка отчётов
            <span class="chk-role-badge">
                <i class="bi bi-shield-check"></i>
                <?= htmlspecialchars($roleLabel[0]) ?>
            </span>
            <span class="chk-role-badge chk-scope-badge">
                <i class="bi <?= $scopeInfo[2] ?>"></i>
                <?= htmlspecialchars($scopeInfo[0]) ?>
            </span>
        </h2>
        <p>
            <i class="bi bi-info-circle"></i>
            Сводка по группам, heatmap-календарь посещаемости и детальные отчёты
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Дашборд
        </a>
    </div>
</div>

<!-- Табы -->
<div class="chk-tabs">
    <a href="?view=overview&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?><?= $onlyMine ? '&only_mine=1' : '' ?>"
       class="chk-tab <?= $viewMode === 'overview' ? 'chk-tab--active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i>
        Обзор групп
    </a>
    <a href="?view=group&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?><?= $groupId ? '&group_id=' . $groupId : '' ?>"
       class="chk-tab <?= $viewMode === 'group' ? 'chk-tab--active' : '' ?>">
        <i class="bi bi-people-fill"></i>
        Группа
    </a>
    <a href="?view=subject&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?><?= $subject ? '&subject=' . urlencode($subject) : '' ?>"
       class="chk-tab <?= $viewMode === 'subject' ? 'chk-tab--active' : '' ?>">
        <i class="bi bi-book"></i>
        Предмет
    </a>
</div>

<!-- ФИЛЬТР -->
<div class="chk-filter">
    <form method="get">
        <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">

        <div class="chk-filter-row">
            <?php if ($viewMode === 'group'): ?>
                <div>
                    <div class="chk-filter-label">
                        <i class="bi bi-people"></i> Группа <span class="req">*</span>
                    </div>
                    <select name="group_id" class="chk-select" required>
                        <option value="">— Выберите группу —</option>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= $groupId == $g['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($viewMode === 'subject'): ?>
                <div>
                    <div class="chk-filter-label">
                        <i class="bi bi-book"></i> Предмет <span class="req">*</span>
                    </div>
                    <select name="subject" class="chk-select" required>
                        <option value="">— Выберите предмет —</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>" <?= $subject === $s ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div style="grid-column: span 2;">
                    <div class="chk-filter-label">
                        <i class="bi bi-info-circle"></i>
                        Режим обзора — все доступные группы
                    </div>
                    <div style="font-size:0.85rem; color:#86868b; padding: 11px 0;">
                        Сортировка: сначала проблемные
                    </div>
                </div>
            <?php endif; ?>

            <div>
                <div class="chk-filter-label">
                    <i class="bi bi-calendar-event"></i> С
                </div>
                <input type="date" name="from" class="chk-input" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div>
                <div class="chk-filter-label">
                    <i class="bi bi-calendar-event"></i> По
                </div>
                <input type="date" name="to" class="chk-input" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div>
                <button type="submit" class="chk-submit-btn">
                    <i class="bi bi-search"></i> Показать
                </button>
            </div>
        </div>

        <div class="chk-filter-actions">
            <a href="check_reports.php?view=<?= htmlspecialchars($viewMode) ?>"
               class="chk-action-btn chk-action-btn--reset">
                <i class="bi bi-x-circle"></i> Сбросить
            </a>

            <?php if ($viewMode === 'overview' && $scope['role'] !== 'teacher'): ?>
                <label class="chk-switch">
                    <input type="checkbox" name="only_mine" value="1"
                           <?= $onlyMine ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    <i class="bi bi-person-check"></i>
                    Только мои занятия
                </label>
            <?php endif; ?>

            <span style="font-size:0.78rem; color:#86868b; margin-left:auto;">
                <i class="bi bi-info-circle"></i>
                Период: <?= date('d.m.Y', strtotime($from)) ?> — <?= date('d.m.Y', strtotime($to)) ?>
            </span>
        </div>
    </form>
</div>

<!-- ============================================================
     РЕЖИМ 1: ОБЗОР
     ============================================================ -->
<?php if ($viewMode === 'overview'): ?>

    <?php if (empty($overviewRows)): ?>
        <div class="chk-card">
            <div class="chk-empty">
                <i class="bi bi-people"></i>
                <p>Нет данных за выбранный период</p>
            </div>
        </div>
    <?php else: ?>

        <?php
        $totalGroups   = count($overviewRows);
        $totalStudents = array_sum(array_column(array_column($overviewRows, 'group'), 'cnt'));
        $totalMarks    = array_sum(array_column($overviewRows, 'total_marks'));
        $totalPresent  = array_sum(array_column($overviewRows, 'present_marks'));
        $avgAll        = $totalMarks > 0 ? (int)round($totalPresent / $totalMarks * 100) : 0;
        ?>

        <div class="chk-stats-grid">
            <div class="chk-stat" style="--chk-color:#0071e3; --chk-bg:rgba(0,113,227,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="chk-stat-value"><?= $totalGroups ?></div>
                <div class="chk-stat-label">Групп</div>
                <div class="chk-stat-sub"><?= $totalStudents ?> студентов</div>
            </div>
            <div class="chk-stat" style="--chk-color:#30d158; --chk-bg:rgba(48,209,88,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="chk-stat-value"><?= $avgAll ?>%</div>
                <div class="chk-stat-label">Средняя посещаемость</div>
                <div class="chk-stat-sub"><?= $totalPresent ?> из <?= $totalMarks ?></div>
            </div>
            <div class="chk-stat" style="--chk-color:#ff453a; --chk-bg:rgba(255,69,58,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                <div class="chk-stat-value"><?= $totalMarks - $totalPresent ?></div>
                <div class="chk-stat-label">Пропусков</div>
                <div class="chk-stat-sub">за период</div>
            </div>
            <div class="chk-stat" style="--chk-color:#bf5af2; --chk-bg:rgba(191,90,242,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-journal-check"></i></div>
                <div class="chk-stat-value"><?= array_sum(array_column($overviewRows, 'works_cnt')) ?></div>
                <div class="chk-stat-label">Работ сдано</div>
                <div class="chk-stat-sub">за период</div>
            </div>
        </div>

        <div class="chk-card">
            <div class="chk-card-header">
                <h3 class="chk-card-title">
                    <i class="bi bi-grid-1x2-fill"></i>
                    Сводка по группам
                </h3>
                <span class="chk-card-badge">
                    <i class="bi bi-sort-down"></i> сначала проблемные
                </span>
            </div>
            <div class="chk-hint">
                <i class="bi bi-lightbulb-fill"></i>
                <div>
                    Клик по названию группы — детальный отчёт с heatmap-календарём.
                    Мини-heatmap справа показывает посещаемость по неделям.
                </div>
            </div>
            <div class="chk-table-wrap">
                <table class="chk-table">
                    <thead>
                    <tr>
                        <th>Группа</th>
                        <th class="center" style="width:80px;">Студентов</th>
                        <th class="center" style="width:90px;">Занятий</th>
                        <th class="center" style="width:140px;">Посещаемость</th>
                        <th class="center" style="width:90px;">Пропусков</th>
                        <th class="center" style="width:90px;">Работ</th>
                        <th style="width:200px;">По неделям</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($overviewRows as $r):
                        $pct = $r['pct'];
                        $pctClass = 'secondary';
                        if ($pct !== null) {
                            if ($pct >= 75) $pctClass = 'success';
                            elseif ($pct >= 50) $pctClass = 'warning';
                            else $pctClass = 'danger';
                        }
                        $rowClass = '';
                        if ($pct !== null && $pct < 50) $rowClass = 'chk-row-danger';
                        elseif ($pct !== null && $pct < 75) $rowClass = 'chk-row-warning';
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <a href="?view=group&group_id=<?= $r['group']['id'] ?>&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>"
                                   class="group-name">
                                    <i class="bi bi-people-fill" style="color:#0071e3;"></i>
                                    <?= htmlspecialchars($r['group']['name']) ?>
                                </a>
                            </td>
                            <td class="num"><?= $r['group']['cnt'] ?></td>
                            <td class="num"><?= $r['lessons_cnt'] ?></td>
                            <td class="num">
                                <?php if ($pct === null): ?>
                                    <span style="color:#86868b;">—</span>
                                <?php else: ?>
                                    <div class="chk-progress">
                                        <div class="chk-progress-bar">
                                            <div class="chk-progress-fill chk-progress-fill--<?= $pctClass ?>"
                                                 style="width: <?= $pct ?>%;"></div>
                                        </div>
                                        <span class="chk-progress-text" style="color: <?= $pct >= 75 ? '#198754' : ($pct >= 50 ? '#b25c00' : '#842029') ?>;">
                                            <?= $pct ?>%
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="num" style="color:#842029; font-weight:700;">
                                <?= $r['total_marks'] - $r['present_marks'] ?>
                            </td>
                            <td class="num">
                                <span style="color:#0071e3; font-weight:700;">
                                    <?= $r['works_cnt'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="hm-mini">
                                    <?php foreach ($r['weekly'] as $w):
                                        $cls = heatColor($w['pct']);
                                    ?>
                                        <div class="hm-mini-cell <?= $cls ?>"
                                             title="<?= date('d.m', strtotime($w['week_start'])) ?>: <?= $w['pct'] === null ? '—' : $w['pct'] . '%' ?>"></div>
                                    <?php endforeach; ?>
                                    <?php if (empty($r['weekly'])): ?>
                                        <span style="color:#86868b; font-size:0.75rem;">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

<!-- ============================================================
     РЕЖИМ 2: ГРУППА
     ============================================================ -->
<?php elseif ($viewMode === 'group'): ?>

    <?php if (!$groupReport): ?>
        <div class="chk-card">
            <div class="chk-empty">
                <i class="bi bi-people"></i>
                <p>Выберите группу и период</p>
            </div>
        </div>
    <?php else:
        $r = $groupReport;
    ?>

        <div class="chk-stats-grid">
            <div class="chk-stat" style="--chk-color:#0071e3; --chk-bg:rgba(0,113,227,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="chk-stat-value"><?= count($r['students']) ?></div>
                <div class="chk-stat-label">Студентов</div>
                <div class="chk-stat-sub">в группе</div>
            </div>
            <div class="chk-stat" style="--chk-color:#30d158; --chk-bg:rgba(48,209,88,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="chk-stat-value"><?= $r['avgPct'] !== null ? $r['avgPct'] . '%' : '—' ?></div>
                <div class="chk-stat-label">Посещаемость</div>
                <div class="chk-stat-sub"><?= $r['totalPresent'] ?> из <?= $r['totalPresent'] + $r['totalAbsent'] ?></div>
            </div>
            <div class="chk-stat" style="--chk-color:#ff453a; --chk-bg:rgba(255,69,58,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                <div class="chk-stat-value"><?= $r['totalAbsent'] ?></div>
                <div class="chk-stat-label">Пропусков</div>
                <div class="chk-stat-sub">за период</div>
            </div>
            <div class="chk-stat" style="--chk-color:#bf5af2; --chk-bg:rgba(191,90,242,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-journal-check"></i></div>
                <div class="chk-stat-value"><?= $r['totalWorksAll'] ?></div>
                <div class="chk-stat-label">Работ сдано</div>
                <div class="chk-stat-sub">видов: <?= count($r['allWorks']) ?></div>
            </div>
        </div>

        <!-- HEATMAP -->
        <div class="chk-card">
            <div class="chk-card-header">
                <h3 class="chk-card-title">
                    <i class="bi bi-calendar3"></i>
                    Календарь посещаемости — <?= htmlspecialchars($r['group']['name']) ?>
                </h3>
                <span class="chk-card-badge">
                    <?= date('d.m.Y', strtotime($from)) ?> — <?= date('d.m.Y', strtotime($to)) ?>
                </span>
            </div>

            <div class="hm-student-selector">
                <label>
                    <i class="bi bi-person-fill"></i>
                    Показать:
                </label>
                <select id="hmStudentSelect" onchange="switchHeatmapStudent(this.value)">
                    <option value="0">Вся группа (средняя посещаемость)</option>
                    <?php foreach ($r['students'] as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['full_name']) ?>
                            <?= $s['zachetka'] ? '· ' . htmlspecialchars($s['zachetka']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="hm-wrap" id="hmWrap">
                <?php
                $weeks = $r['heatmap']['weeks'];
                $monthNames = ['','Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'];
                $monthLabels = [];
                $prevMonth = null;
                foreach ($weeks as $idx => $w) {
                    $m = (int)date('n', strtotime($w['week_start']));
                    if ($m !== $prevMonth) {
                        $monthLabels[$idx] = $monthNames[$m];
                        $prevMonth = $m;
                    } else {
                        $monthLabels[$idx] = '';
                    }
                }
                ?>
                <div class="hm-months" style="grid-template-columns: repeat(<?= count($weeks) ?>, 14px);">
                    <?php foreach ($weeks as $idx => $w): ?>
                        <div class="hm-month"><?= $monthLabels[$idx] ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="hm-body">
                    <div class="hm-days">
                        <div class="hm-day-label"></div>
                        <div class="hm-day-label">Пн</div>
                        <div class="hm-day-label"></div>
                        <div class="hm-day-label">Ср</div>
                        <div class="hm-day-label"></div>
                        <div class="hm-day-label">Пт</div>
                        <div class="hm-day-label"></div>
                    </div>
                    <div class="hm-grid" id="hmGrid">
                        <?php foreach ($weeks as $w): ?>
                            <?php foreach ($w['days'] as $d):
                                $isFuture = $d['date'] > $today;
                                $cls = $isFuture ? 'hm-future' : heatColor($d['pct']);
                                $title = date('d.m.Y', strtotime($d['date']));
                                if (!$isFuture) {
                                    if ($d['pct'] !== null) {
                                        $title .= ' — ' . $d['pct'] . '% (' . $d['present'] . '/' . $d['count'] . ')';
                                    } else {
                                        $title .= ' — нет занятий';
                                    }
                                }
                            ?>
                                <div class="hm-cell <?= $cls ?>" data-title="<?= htmlspecialchars($title) ?>"></div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="hm-legend">
                <span class="hm-legend-label">Меньше</span>
                <div class="hm-legend-scale">
                    <div class="hm-legend-cell hm-0"></div>
                    <div class="hm-legend-cell hm-25"></div>
                    <div class="hm-legend-cell hm-50"></div>
                    <div class="hm-legend-cell hm-75"></div>
                    <div class="hm-legend-cell hm-90"></div>
                </div>
                <span class="hm-legend-label">Больше</span>
                <span style="margin-left:16px; opacity:0.7;">
                    <i class="bi bi-info-circle"></i>
                    Наведите на квадрат для деталей
                </span>
            </div>
        </div>

        <!-- Сводка по предметам -->
        <div class="chk-card">
            <div class="chk-card-header">
                <h3 class="chk-card-title">
                    <i class="bi bi-book"></i>
                    Предметы группы
                </h3>
                <span class="chk-card-badge"><?= count($r['subjectSummaries']) ?></span>
            </div>
            <?php if (empty($r['subjectSummaries'])): ?>
                <div class="chk-empty">
                    <i class="bi bi-book"></i>
                    <p>Занятий за период нет</p>
                </div>
            <?php else: ?>
                <div class="chk-table-wrap">
                    <table class="chk-table">
                        <thead>
                        <tr>
                            <th>Предмет</th>
                            <th>Преподаватель</th>
                            <th class="center" style="width:90px;">Занятий</th>
                            <th class="center" style="width:140px;">Посещаемость</th>
                            <th class="center" style="width:90px;">Работ</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($r['subjectSummaries'] as $s):
                            $pct = $s['pct'];
                            $pctClass = 'secondary';
                            if ($pct !== null) {
                                if ($pct >= 75) $pctClass = 'success';
                                elseif ($pct >= 50) $pctClass = 'warning';
                                else $pctClass = 'danger';
                            }
                        ?>
                            <tr>
                                <td class="student-name"><?= htmlspecialchars($s['discipline']) ?></td>
                                <td style="font-size:0.82rem; color:#86868b;">
                                    <?= htmlspecialchars($s['teachers']) ?>
                                </td>
                                <td class="num"><?= $s['lessons_cnt'] ?></td>
                                <td class="num">
                                    <?php if ($pct === null): ?>
                                        <span style="color:#86868b;">—</span>
                                    <?php else: ?>
                                        <div class="chk-progress">
                                            <div class="chk-progress-bar">
                                                <div class="chk-progress-fill chk-progress-fill--<?= $pctClass ?>" style="width: <?= $pct ?>%;"></div>
                                            </div>
                                            <span class="chk-progress-text"><?= $pct ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="num">
                                    <span style="color:#0071e3; font-weight:700;"><?= $s['works_cnt'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Студенты -->
        <div class="chk-card">
            <div class="chk-card-header">
                <h3 class="chk-card-title">
                    <i class="bi bi-people-fill"></i>
                    Студенты
                </h3>
                <span class="chk-card-badge"><?= count($r['studentStats']) ?></span>
            </div>
            <div class="chk-table-wrap">
                <table class="chk-table">
                    <thead>
                    <tr>
                        <th style="width:40px;">№</th>
                        <th style="width:90px;">Зачётка</th>
                        <th>Студент</th>
                        <th class="center" style="width:60px;">Подгр.</th>
                        <th class="center" style="width:80px;">Был</th>
                        <th class="center" style="width:90px;">Пропустил</th>
                        <th class="center" style="width:140px;">Посещаемость</th>
                        <th class="center" style="width:90px;">Работ</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($r['studentStats'] as $i => $s):
                        $pct = $s['pct'];
                        $pctClass = 'secondary';
                        if ($pct !== null) {
                            if ($pct >= 75) $pctClass = 'success';
                            elseif ($pct >= 50) $pctClass = 'warning';
                            else $pctClass = 'danger';
                        }
                        $rowClass = '';
                        if ($s['absent'] > 5) $rowClass = 'chk-row-danger';
                        elseif ($s['absent'] > 3) $rowClass = 'chk-row-warning';

                        $sg = $s['student']['subgroup'] ?? null;
                        $sgColor = $sg === '1' ? '#0071e3' : '#bf5af2';
                        $sgBg = $sg === '1' ? 'rgba(0,113,227,0.15)' : 'rgba(191,90,242,0.15)';
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="num"><?= $i + 1 ?></td>
                            <td>
                                <?php if (!empty($s['student']['zachetka'])): ?>
                                    <code><?= htmlspecialchars($s['student']['zachetka']) ?></code>
                                <?php else: ?>
                                    <span style="color:#86868b;">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="student-name">
                                <a href="#" onclick="switchHeatmapStudent(<?= $s['student']['id'] ?>);return false;"
                                   style="color:inherit; text-decoration:none;"
                                   title="Показать heatmap студента">
                                    <?= htmlspecialchars($s['student']['full_name']) ?>
                                </a>
                            </td>
                            <td class="num">
                                <?php if (!empty($sg)): ?>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:24px;border-radius:6px;background:<?= $sgBg ?>;color:<?= $sgColor ?>;font-weight:700;font-size:0.75rem;">
                                        <?= htmlspecialchars($sg) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#86868b;">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="num" style="color:#198754; font-weight:700;"><?= $s['present'] ?></td>
                            <td class="num" style="color:#842029; font-weight:700;"><?= $s['absent'] ?></td>
                            <td class="num">
                                <?php if ($pct === null): ?>
                                    <span style="color:#86868b;">—</span>
                                <?php else: ?>
                                    <div class="chk-progress">
                                        <div class="chk-progress-bar">
                                            <div class="chk-progress-fill chk-progress-fill--<?= $pctClass ?>" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                        <span class="chk-progress-text"><?= $pct ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <span style="color:#0071e3; font-weight:700;"><?= $s['works_cnt'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

<!-- ============================================================
     РЕЖИМ 3: ПРЕДМЕТ
     ============================================================ -->
<?php elseif ($viewMode === 'subject'): ?>

    <?php if (!$subjectReport): ?>
        <div class="chk-card">
            <div class="chk-empty">
                <i class="bi bi-book"></i>
                <p>Выберите предмет и период</p>
            </div>
        </div>
    <?php else:
        $r = $subjectReport;
    ?>

        <div class="chk-stats-grid">
            <div class="chk-stat" style="--chk-color:#0071e3; --chk-bg:rgba(0,113,227,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="chk-stat-value"><?= $r['summary']['groups_cnt'] ?></div>
                <div class="chk-stat-label">Групп</div>
                <div class="chk-stat-sub">изучают предмет</div>
            </div>
            <div class="chk-stat" style="--chk-color:#5e5ce6; --chk-bg:rgba(94,92,230,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-calendar-week"></i></div>
                <div class="chk-stat-value"><?= $r['summary']['lessons_cnt'] ?></div>
                <div class="chk-stat-label">Занятий</div>
                <div class="chk-stat-sub">за период</div>
            </div>
            <div class="chk-stat" style="--chk-color:#30d158; --chk-bg:rgba(48,209,88,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="chk-stat-value"><?= $r['summary']['avg_pct'] !== null ? $r['summary']['avg_pct'] . '%' : '—' ?></div>
                <div class="chk-stat-label">Посещаемость</div>
                <div class="chk-stat-sub"><?= $r['summary']['present_marks'] ?> из <?= $r['summary']['total_marks'] ?></div>
            </div>
            <div class="chk-stat" style="--chk-color:#ff453a; --chk-bg:rgba(255,69,58,0.1);">
                <div class="chk-stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                <div class="chk-stat-value"><?= $r['summary']['total_marks'] - $r['summary']['present_marks'] ?></div>
                <div class="chk-stat-label">Пропусков</div>
                <div class="chk-stat-sub">за период</div>
            </div>
        </div>

        <!-- HEATMAP -->
        <div class="chk-card">
            <div class="chk-card-header">
                <h3 class="chk-card-title">
                    <i class="bi bi-calendar3"></i>
                    Календарь посещаемости — <?= htmlspecialchars($r['subject']) ?>
                </h3>
                <span class="chk-card-badge">
                    <?= date('d.m.Y', strtotime($from)) ?> — <?= date('d.m.Y', strtotime($to)) ?>
                </span>
            </div>
            <div class="hm-wrap">
                <?php
                $weeks = $r['heatmap']['weeks'];
                $monthNames = ['','Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'];
                $monthLabels = [];
                $prevMonth = null;
                foreach ($weeks as $idx => $w) {
                    $m = (int)date('n', strtotime($w['week_start']));
                    if ($m !== $prevMonth) {
                        $monthLabels[$idx] = $monthNames[$m];
                        $prevMonth = $m;
                    } else {
                        $monthLabels[$idx] = '';
                    }
                }
                ?>
                <div class="hm-months" style="grid-template-columns: repeat(<?= count($weeks) ?>, 14px);">
                    <?php foreach ($weeks as $idx => $w): ?>
                        <div class="hm-month"><?= $monthLabels[$idx] ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="hm-body">
                    <div class="hm-days">
                        <div class="hm-day-label"></div>
                        <div class="hm-day-label">Пн</div>
                        <div class="hm-day-label"></div>
                        <div class="hm-day-label">Ср</div>
                        <div class="hm-day-label"></div>
                        <div class="hm-day-label">Пт</div>
                        <div class="hm-day-label"></div>
                    </div>
                    <div class="hm-grid">
                        <?php foreach ($weeks as $w): ?>
                            <?php foreach ($w['days'] as $d):
                                $isFuture = $d['date'] > $today;
                                $cls = $isFuture ? 'hm-future' : heatColor($d['pct']);
                                $title = date('d.m.Y', strtotime($d['date']));
                                if (!$isFuture && $d['pct'] !== null) {
                                    $title .= ' — ' . $d['pct'] . '% (' . $d['present'] . '/' . $d['count'] . ')';
                                }
                            ?>
                                <div class="hm-cell <?= $cls ?>" data-title="<?= htmlspecialchars($title) ?>"></div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="hm-legend">
                <span class="hm-legend-label">Меньше</span>
                <div class="hm-legend-scale">
                    <div class="hm-legend-cell hm-0"></div>
                    <div class="hm-legend-cell hm-25"></div>
                    <div class="hm-legend-cell hm-50"></div>
                    <div class="hm-legend-cell hm-75"></div>
                    <div class="hm-legend-cell hm-90"></div>
                </div>
                <span class="hm-legend-label">Больше</span>
            </div>
        </div>

        <!-- По группам -->
        <div class="chk-card">
            <div class="chk-card-header">
                <h3 class="chk-card-title">
                    <i class="bi bi-people-fill"></i>
                    По группам
                </h3>
                <span class="chk-card-badge"><?= count($r['groupStats']) ?></span>
            </div>
            <div class="chk-table-wrap">
                <table class="chk-table">
                    <thead>
                    <tr>
                        <th>Группа</th>
                        <th>Преподаватель</th>
                        <th class="center" style="width:90px;">Занятий</th>
                        <th class="center" style="width:140px;">Посещаемость</th>
                        <th class="center" style="width:90px;">Пропусков</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($r['groupStats'] as $g):
                        $pct = $g['pct'];
                        $pctClass = 'secondary';
                        if ($pct !== null) {
                            if ($pct >= 75) $pctClass = 'success';
                            elseif ($pct >= 50) $pctClass = 'warning';
                            else $pctClass = 'danger';
                        }
                        $rowClass = '';
                        if ($pct !== null && $pct < 50) $rowClass = 'chk-row-danger';
                        elseif ($pct !== null && $pct < 75) $rowClass = 'chk-row-warning';
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <a href="?view=group&group_id=<?= $g['group_id'] ?>&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>"
                                   class="group-name">
                                    <i class="bi bi-people-fill" style="color:#0071e3;"></i>
                                    <?= htmlspecialchars($g['group_name']) ?>
                                </a>
                            </td>
                            <td style="font-size:0.82rem; color:#86868b;">
                                <?= htmlspecialchars($g['teachers']) ?>
                            </td>
                            <td class="num"><?= $g['lessons_cnt'] ?></td>
                            <td class="num">
                                <?php if ($pct === null): ?>
                                    <span style="color:#86868b;">—</span>
                                <?php else: ?>
                                    <div class="chk-progress">
                                        <div class="chk-progress-bar">
                                            <div class="chk-progress-fill chk-progress-fill--<?= $pctClass ?>" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                        <span class="chk-progress-text"><?= $pct ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="num" style="color:#842029; font-weight:700;">
                                <?= $g['total_marks'] - $g['present_marks'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- По студентам -->
        <div class="chk-card">
            <div class="chk-card-header">
                <h3 class="chk-card-title">
                    <i class="bi bi-person-lines-fill"></i>
                    Студенты
                </h3>
                <span class="chk-card-badge"><?= count($r['studentStats']) ?></span>
            </div>
            <div class="chk-table-wrap">
                <table class="chk-table">
                    <thead>
                    <tr>
                        <th style="width:40px;">№</th>
                        <th>Группа</th>
                        <th style="width:90px;">Зачётка</th>
                        <th>Студент</th>
                        <th class="center" style="width:80px;">Был</th>
                        <th class="center" style="width:90px;">Пропустил</th>
                        <th class="center" style="width:140px;">Посещаемость</th>
                        <th class="center" style="width:90px;">Работ</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($r['studentStats'] as $i => $s):
                        $pct = $s['pct'];
                        $pctClass = 'secondary';
                        if ($pct !== null) {
                            if ($pct >= 75) $pctClass = 'success';
                            elseif ($pct >= 50) $pctClass = 'warning';
                            else $pctClass = 'danger';
                        }
                        $rowClass = '';
                        if ($s['absent_marks'] > 5) $rowClass = 'chk-row-danger';
                        elseif ($s['absent_marks'] > 3) $rowClass = 'chk-row-warning';
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="num"><?= $i + 1 ?></td>
                            <td>
                                <span style="font-weight:600; font-size:0.82rem; color:#495057;">
                                    <?= htmlspecialchars($s['student']['group_name']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($s['student']['zachetka'])): ?>
                                    <code><?= htmlspecialchars($s['student']['zachetka']) ?></code>
                                <?php else: ?>
                                    <span style="color:#86868b;">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="student-name"><?= htmlspecialchars($s['student']['full_name']) ?></td>
                            <td class="num" style="color:#198754; font-weight:700;"><?= $s['present_marks'] ?></td>
                            <td class="num" style="color:#842029; font-weight:700;"><?= $s['absent_marks'] ?></td>
                            <td class="num">
                                <?php if ($pct === null): ?>
                                    <span style="color:#86868b;">—</span>
                                <?php else: ?>
                                    <div class="chk-progress">
                                        <div class="chk-progress-bar">
                                            <div class="chk-progress-fill chk-progress-fill--<?= $pctClass ?>" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                        <span class="chk-progress-text"><?= $pct ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <span style="color:#0071e3; font-weight:700;"><?= $s['works_cnt'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

<?php endif; ?>

<!-- Tooltip -->
<div class="hm-tooltip" id="hmTooltip"></div>

<script>
/* ============================================================
   HEATMAP: TOOLTIP
   ============================================================ */
(function() {
    const tooltip = document.getElementById('hmTooltip');
    if (!tooltip) return;

    function bindTooltips(root) {
        (root || document).querySelectorAll('.hm-cell').forEach(cell => {
            if (cell.dataset.tooltipBound === '1') return;
            cell.dataset.tooltipBound = '1';

            cell.addEventListener('mouseenter', () => {
                const title = cell.dataset.title || '';
                if (!title) return;
                tooltip.innerHTML = title;
                tooltip.classList.add('show');
            });
            cell.addEventListener('mousemove', (e) => {
                tooltip.style.left = (e.clientX + 14) + 'px';
                tooltip.style.top  = (e.clientY + 14) + 'px';
            });
            cell.addEventListener('mouseleave', () => {
                tooltip.classList.remove('show');
            });
        });
    }

    bindTooltips(document);
    window.__bindHeatmapTooltips = bindTooltips;
})();

/* ============================================================
   HEATMAP: AJAX
   ============================================================ */
function switchHeatmapStudent(studentId) {
    const sel = document.getElementById('hmStudentSelect');
    if (sel) sel.value = studentId;

    const wrap = document.getElementById('hmWrap');
    if (wrap) wrap.style.opacity = '0.5';

    const groupId = <?= (int)$groupId ?>;
    const fromDate = <?= json_encode($from) ?>;
    const toDate   = <?= json_encode($to) ?>;

    const params = new URLSearchParams({
        action:     'heatmap_student',
        student_id: studentId,
        group_id:   groupId,
        from:       fromDate,
        to:         toDate
    });

    fetch('check_reports.php?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) {
            if (wrap) wrap.style.opacity = '1';
            alert('Ошибка: ' + (data.error || 'не удалось'));
            return;
        }
        renderHeatmap(data.weeks);
        if (wrap) wrap.style.opacity = '1';
    })
    .catch(() => {
        if (wrap) wrap.style.opacity = '1';
        alert('Ошибка сети');
    });
}

function renderHeatmap(weeks) {
    const grid = document.getElementById('hmGrid');
    if (!grid) return;
    const today = <?= json_encode($today) ?>;

    let html = '';
    weeks.forEach(w => {
        w.days.forEach(d => {
            const isFuture = d.date > today;
            let cls = 'hm-empty';
            if (isFuture) {
                cls = 'hm-future';
            } else if (d.pct !== null) {
                if (d.pct >= 90) cls = 'hm-90';
                else if (d.pct >= 75) cls = 'hm-75';
                else if (d.pct >= 50) cls = 'hm-50';
                else if (d.pct >= 25) cls = 'hm-25';
                else cls = 'hm-0';
            }

            let title = formatDate(d.date);
            if (!isFuture) {
                if (d.pct !== null) {
                    title += ' — ' + d.pct + '% (' + d.present + '/' + d.count + ')';
                } else {
                    title += ' — нет занятий';
                }
            }

            html += '<div class="hm-cell ' + cls + '" data-title="' +
                    title.replace(/"/g, '&quot;') + '"></div>';
        });
    });
    grid.innerHTML = html;

    if (window.__bindHeatmapTooltips) window.__bindHeatmapTooltips(grid);
}

function formatDate(iso) {
    const [y, m, d] = iso.split('-');
    return d + '.' + m + '.' + y;
}
</script>

<?php require_once 'includes/footer.php'; ?>