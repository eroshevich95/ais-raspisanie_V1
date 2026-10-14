<?php
$pageTitle = 'Должники';
require_once 'config/database.php';
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

// ============================================================
// ОБЛАСТЬ ДОСТУПА
// ============================================================
$scope             = getAccessScope($pdo, $userId);
$allowedTeacherIds = getAllowedTeacherIds($pdo, $scope);
$allowedGroupIds   = getAllowedGroupIds($pdo, $scope, $allowedTeacherIds);

$scopeInfo = scopeLabel($scope);

// ============================================================
// ФИЛЬТРЫ
// ============================================================
$groupId    = (int)($_GET['group_id'] ?? 0);
$subject    = trim($_GET['subject'] ?? '');
$teacherId  = (int)($_GET['teacher_id'] ?? 0);
$from       = $_GET['from'] ?? date('Y-m-01');
$to         = $_GET['to']   ?? date('Y-m-d');

// Проверка доступа
if ($groupId > 0 && $allowedGroupIds !== null && !in_array($groupId, $allowedGroupIds)) {
    $groupId = 0;
}
if ($teacherId > 0 && $allowedTeacherIds !== null && !in_array($teacherId, $allowedTeacherIds)) {
    $teacherId = 0;
}

$minAbsent  = max(1, (int)($_GET['min_absent'] ?? 3));
$maxPct     = min(99, max(1, (int)($_GET['max_pct'] ?? 60)));
$sortBy     = $_GET['sort'] ?? 'critical';

// ============================================================
// СПРАВОЧНИКИ
// ============================================================
// Группы
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

// Преподаватели
if ($allowedTeacherIds === null) {
    $teachers = $pdo->query("
        SELECT DISTINCT u.id, u.full_name
        FROM users u
        JOIN lessons l ON l.user_id = u.id
        ORDER BY u.full_name
    ")->fetchAll();
} elseif (empty($allowedTeacherIds)) {
    $teachers = [];
} else {
    $in = implode(',', array_fill(0, count($allowedTeacherIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id, full_name FROM users
        WHERE id IN ($in) AND is_active = 1
        ORDER BY full_name
    ");
    $stmt->execute($allowedTeacherIds);
    $teachers = $stmt->fetchAll();
}

// ============================================================
// ОСНОВНОЙ SQL
// ============================================================
$where  = [];
$params = [];

// Ограничения области доступа
if ($allowedTeacherIds !== null) {
    if (empty($allowedTeacherIds)) {
        $where[] = "1 = 0";
    } else {
        $in = implode(',', array_fill(0, count($allowedTeacherIds), '?'));
        $where[] = "l.user_id IN ($in)";
        foreach ($allowedTeacherIds as $t) $params[] = $t;
    }
}

if ($groupId > 0) {
    $where[] = "s.group_id = ?";
    $params[] = $groupId;
}
if ($subject !== '') {
    $where[] = "l.discipline = ?";
    $params[] = $subject;
}
if ($teacherId > 0) {
    $where[] = "l.user_id = ?";
    $params[] = $teacherId;
}

$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

// Подзапрос для подсчёта работ
$wSubWhere  = ["l2.lesson_date BETWEEN ? AND ?"];
$wSubParams = [$from, $to];

if ($subject !== '') {
    $wSubWhere[] = "l2.discipline = ?";
    $wSubParams[] = $subject;
}
if ($allowedTeacherIds !== null) {
    if (empty($allowedTeacherIds)) {
        $wSubWhere[] = "1 = 0";
    } else {
        $in = implode(',', array_fill(0, count($allowedTeacherIds), '?'));
        $wSubWhere[] = "l2.user_id IN ($in)";
        foreach ($allowedTeacherIds as $t) $wSubParams[] = $t;
    }
}
if ($teacherId > 0) {
    $wSubWhere[] = "l2.user_id = ?";
    $wSubParams[] = $teacherId;
}
$wSubSql = implode(' AND ', $wSubWhere);

$sql = "
    SELECT
        s.id,
        s.full_name,
        s.zachetka,
        s.subgroup,
        g.id   AS group_id,
        g.name AS group_name,
        COUNT(a.id) AS total_marked,
        COALESCE(SUM(a.is_present), 0) AS present_marks,
        COUNT(a.id) - COALESCE(SUM(a.is_present), 0) AS absent_marks,
        (
            SELECT COUNT(DISTINCT sw.work_number)
            FROM submitted_works sw
            JOIN lessons l2 ON sw.lesson_id = l2.id
            WHERE sw.student_id = s.id
              AND $wSubSql
        ) AS works_cnt
    FROM students s
    JOIN groups_table g ON s.group_id = g.id
    JOIN lessons l ON l.group_id = s.group_id
    JOIN attendance a ON a.lesson_id = l.id AND a.student_id = s.id
    $whereSql
      AND l.lesson_date BETWEEN ? AND ?
    GROUP BY s.id
    HAVING absent_marks >= ?
";

$mainParams = array_merge($wSubParams, $params, [$from, $to, $minAbsent]);

$stmt = $pdo->prepare($sql);
$stmt->execute($mainParams);
$debtors = $stmt->fetchAll();

// ============================================================
// ФИЛЬТРАЦИЯ И КРИТИЧНОСТЬ
// ============================================================
$filtered = [];
foreach ($debtors as $d) {
    $marked  = (int)$d['total_marked'];
    $present = (int)$d['present_marks'];
    $absent  = (int)$d['absent_marks'];
    $pct = $marked > 0 ? (int)round($present / $marked * 100) : 0;

    $d['pct'] = $pct;

    $isDebtor = ($absent >= $minAbsent) || ($pct > 0 && $pct <= $maxPct);
    if (!$isDebtor) continue;

    $critScore = 0;
    if ($pct <= 30) $critScore += 40;
    elseif ($pct <= 50) $critScore += 25;
    elseif ($pct <= 70) $critScore += 10;

    if ($absent >= 10) $critScore += 40;
    elseif ($absent >= 5) $critScore += 20;
    elseif ($absent >= 3) $critScore += 10;

    $d['critical_score'] = $critScore;
    if ($critScore >= 60) $d['critical_level'] = 'critical';
    elseif ($critScore >= 30) $d['critical_level'] = 'warning';
    else $d['critical_level'] = 'low';

    $filtered[] = $d;
}
$debtors = $filtered;

// ============================================================
// СОРТИРОВКА
// ============================================================
usort($debtors, function($a, $b) use ($sortBy) {
    switch ($sortBy) {
        case 'absent':
            return ($b['absent_marks'] ?? 0) <=> ($a['absent_marks'] ?? 0);
        case 'pct':
            return ($a['pct'] ?? 999) <=> ($b['pct'] ?? 999);
        case 'works':
            return ($a['works_cnt'] ?? 0) <=> ($b['works_cnt'] ?? 0);
        case 'critical':
        default:
            $sa = $a['critical_score'] ?? 0;
            $sb = $b['critical_score'] ?? 0;
            if ($sa === $sb) {
                return ($a['pct'] ?? 999) <=> ($b['pct'] ?? 999);
            }
            return $sb <=> $sa;
    }
});

// ============================================================
// ЭКСПОРТ CSV
// ============================================================
if (($_GET['action'] ?? '') === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="debtors_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        '№', 'ФИО', 'Зачётка', 'Группа', 'Подгруппа',
        'Всего отметок', 'Был', 'Пропустил', '% посещаемости',
        'Работ сдано', 'Критичность'
    ], ';');

    $i = 1;
    foreach ($debtors as $d) {
        fputcsv($out, [
            $i++,
            $d['full_name'],
            $d['zachetka'] ?? '',
            $d['group_name'],
            $d['subgroup'] ?? '',
            $d['total_marked'],
            $d['present_marks'],
            $d['absent_marks'],
            $d['pct'] . '%',
            $d['works_cnt'],
            $d['critical_level'],
        ], ';');
    }

    fclose($out);
    exit;
}

// Статистика
$totalDebtors  = count($debtors);
$criticalCount = 0;
$warningCount  = 0;
foreach ($debtors as $d) {
    if ($d['critical_level'] === 'critical') $criticalCount++;
    elseif ($d['critical_level'] === 'warning') $warningCount++;
}

require_once 'includes/header.php';
?>

<style>
/* ============================================================
   DEBTORS
   ============================================================ */
.dbt-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.dbt-header h2 {
    font-size: 1.7rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 6px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.dbt-header p {
    color: #86868b;
    margin: 0;
    font-size: 0.95rem;
}
.dbt-scope-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    background: rgba(255,159,10,0.15);
    color: #b25c00;
}

/* Фильтры */
.dbt-filter {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    padding: 20px 24px;
    margin-bottom: 20px;
}
.dbt-filter-row {
    display: grid;
    grid-template-columns: 2fr 2fr 1.5fr 1fr 1fr;
    gap: 12px;
    align-items: end;
}
@media (max-width: 1100px) { .dbt-filter-row { grid-template-columns: 1fr 1fr 1fr; } }
@media (max-width: 700px) { .dbt-filter-row { grid-template-columns: 1fr 1fr; } }
@media (max-width: 480px) { .dbt-filter-row { grid-template-columns: 1fr; } }

.dbt-filter-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #1d1d1f;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.dbt-filter-label i { color: #0071e3; margin-right: 4px; }
.dbt-select, .dbt-input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1.5px solid #e5e5ea;
    background: #fff;
    font-size: 0.88rem;
    font-family: inherit;
    outline: none;
    transition: all 0.2s;
}
.dbt-select:focus, .dbt-input:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 4px rgba(0,113,227,0.12);
}

.dbt-filter-extra {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #f0f0f2;
}
.dbt-threshold {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: #495057;
}
.dbt-threshold label {
    font-weight: 600;
    color: #1d1d1f;
    margin: 0;
}
.dbt-threshold input[type="number"] {
    width: 70px;
    padding: 6px 10px;
    border-radius: 8px;
    border: 1.5px solid #e5e5ea;
    font-size: 0.85rem;
    font-family: inherit;
    outline: none;
    text-align: center;
}
.dbt-threshold input[type="number"]:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 4px rgba(0,113,227,0.12);
}
.dbt-submit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 22px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    border: none;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.25s;
    box-shadow: 0 6px 16px rgba(0,113,227,0.25);
    white-space: nowrap;
}
.dbt-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,113,227,0.4);
}
.dbt-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f2;
    flex-wrap: wrap;
}
.dbt-action-btn {
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
.dbt-action-btn--reset { color: #495057; border-color: #d2d2d7; }
.dbt-action-btn--reset:hover {
    background: #495057; color: #fff; border-color: #495057;
    transform: translateY(-2px);
}
.dbt-action-btn--csv { color: #198754; border-color: #198754; }
.dbt-action-btn--csv:hover {
    background: #198754; color: #fff;
    transform: translateY(-2px);
}
.dbt-action-btn--copy { color: #0071e3; border-color: #0071e3; }
.dbt-action-btn--copy:hover {
    background: #0071e3; color: #fff;
    transform: translateY(-2px);
}

/* Статистика */
.dbt-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.dbt-stat {
    background: #fff;
    border-radius: 18px;
    padding: 18px 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    position: relative;
    overflow: hidden;
}
.dbt-stat::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: var(--dbt-color, #0071e3);
}
.dbt-stat-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: var(--dbt-bg, rgba(0,113,227,0.1));
    color: var(--dbt-color, #0071e3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-bottom: 10px;
}
.dbt-stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.02em;
    color: #1d1d1f;
    margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
}
.dbt-stat-label {
    font-size: 0.8rem;
    color: #86868b;
    font-weight: 500;
}
.dbt-stat-sub {
    font-size: 0.72rem;
    color: #86868b;
    margin-top: 4px;
}

/* Карточка */
.dbt-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 20px;
}
.dbt-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f0f0f2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    background: linear-gradient(135deg, #fafafc 0%, #fff 100%);
}
.dbt-card-title {
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dbt-card-title i { color: #ff453a; }
.dbt-card-badge {
    font-size: 0.72rem;
    font-weight: 600;
    color: #86868b;
    background: #f5f5f7;
    padding: 4px 12px;
    border-radius: 20px;
}

/* Таблица */
.dbt-table-wrap { overflow-x: auto; }
.dbt-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}
.dbt-table thead th {
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
.dbt-table thead th.center { text-align: center; }
.dbt-table thead th a {
    color: #86868b;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.dbt-table thead th a:hover { color: #0071e3; }
.dbt-table thead th a.active { color: #0071e3; }
.dbt-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f5f5f7;
    vertical-align: middle;
}
.dbt-table tbody tr:last-child td { border-bottom: none; }
.dbt-table tbody tr:hover { background: #fafafc; }
.dbt-table tbody tr.dbt-row-critical { background: rgba(255,69,58,0.06); }
.dbt-table tbody tr.dbt-row-warning  { background: rgba(255,159,10,0.05); }
.dbt-table .num { text-align: center; font-variant-numeric: tabular-nums; }
.dbt-table .student-name { font-weight: 600; color: #1d1d1f; }
.dbt-table code {
    background: rgba(0,113,227,0.08);
    color: #0071e3;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-family: "SF Mono", Monaco, monospace;
    font-weight: 600;
}

/* Критичность */
.dbt-crit {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}
.dbt-crit--critical { background: rgba(255,69,58,0.15); color: #842029; }
.dbt-crit--warning  { background: rgba(255,159,10,0.15); color: #b25c00; }
.dbt-crit--low      { background: rgba(134,134,139,0.15); color: #495057; }

/* Прогресс */
.dbt-progress {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 110px;
}
.dbt-progress-bar {
    flex: 1;
    height: 6px;
    background: #f0f0f2;
    border-radius: 3px;
    overflow: hidden;
    min-width: 50px;
}
.dbt-progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.4s;
}
.dbt-progress-fill--success { background: #30d158; }
.dbt-progress-fill--warning { background: #ff9f0a; }
.dbt-progress-fill--danger  { background: #ff453a; }
.dbt-progress-text {
    font-size: 0.75rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    min-width: 40px;
    text-align: right;
}

/* Группировка */
.dbt-group-header {
    background: linear-gradient(90deg, #f5f5f7 0%, #fafafc 100%);
    padding: 10px 20px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #1d1d1f;
    display: flex;
    align-items: center;
    gap: 8px;
    border-top: 1px solid #eaeaea;
    border-bottom: 1px solid #eaeaea;
}
.dbt-group-header:first-child { border-top: none; }
.dbt-group-header i { color: #0071e3; }
.dbt-group-header .count {
    margin-left: auto;
    font-size: 0.72rem;
    font-weight: 700;
    background: rgba(255,69,58,0.15);
    color: #842029;
    padding: 3px 10px;
    border-radius: 20px;
}

/* Пусто */
.dbt-empty {
    padding: 60px 24px;
    text-align: center;
    color: #86868b;
}
.dbt-empty i {
    font-size: 3rem;
    color: #30d158;
    opacity: 0.6;
    display: block;
    margin-bottom: 12px;
}
.dbt-empty h4 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1d1d1f;
    margin: 0 0 8px;
}

/* Toast */
.dbt-toast {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #1d1d1f;
    color: #fff;
    padding: 14px 24px;
    border-radius: 14px;
    font-size: 0.92rem;
    font-weight: 500;
    box-shadow: 0 20px 60px rgba(0,0,0,0.35);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 10px;
    pointer-events: none;
}
.dbt-toast.show {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}
.dbt-toast i { font-size: 1.1rem; }

/* Тёмная тема */
[data-theme="dark"] .dbt-filter,
[data-theme="dark"] .dbt-stat,
[data-theme="dark"] .dbt-card {
    background: #1c1c1e;
    border-color: #2c2c2e;
}
[data-theme="dark"] .dbt-header h2,
[data-theme="dark"] .dbt-filter-label,
[data-theme="dark"] .dbt-stat-value,
[data-theme="dark"] .dbt-card-title,
[data-theme="dark"] .dbt-table .student-name,
[data-theme="dark"] .dbt-empty h4 {
    color: #f5f5f7;
}
[data-theme="dark"] .dbt-select,
[data-theme="dark"] .dbt-input,
[data-theme="dark"] .dbt-threshold input[type="number"] {
    background: #2c2c2e;
    border-color: #3c3c3e;
    color: #f5f5f7;
}
[data-theme="dark"] .dbt-card-header,
[data-theme="dark"] .dbt-table thead th {
    background: #2c2c2e;
    border-color: #3c3c3e;
    color: #a1a1a6;
}
[data-theme="dark"] .dbt-table tbody td { border-color: #2c2c2e; }
[data-theme="dark"] .dbt-table tbody tr:hover { background: #2c2c2e; }
[data-theme="dark"] .dbt-table tbody tr.dbt-row-critical { background: rgba(255,69,58,0.1); }
[data-theme="dark"] .dbt-table tbody tr.dbt-row-warning  { background: rgba(255,159,10,0.08); }
[data-theme="dark"] .dbt-card-badge { background: #2c2c2e; color: #a1a1a6; }
[data-theme="dark"] .dbt-progress-bar { background: #3c3c3e; }
[data-theme="dark"] .dbt-action-btn--reset {
    background: #2c2c2e;
    color: #f5f5f7;
    border-color: #3c3c3e;
}
[data-theme="dark"] .dbt-group-header {
    background: linear-gradient(90deg, #2c2c2e 0%, #1c1c1e 100%);
    color: #f5f5f7;
    border-color: #3c3c3e;
}
[data-theme="dark"] .dbt-threshold { color: #a1a1a6; }
[data-theme="dark"] .dbt-threshold label { color: #f5f5f7; }
</style>

<!-- Шапка -->
<div class="dbt-header">
    <div>
        <h2>
            <i class="bi bi-exclamation-triangle-fill" style="color:#ff453a;"></i>
            Должники
            <?php if ($totalDebtors > 0): ?>
                <span style="font-size:0.55em; font-weight:600; background:rgba(255,69,58,0.15); color:#842029; padding:4px 12px; border-radius:20px;">
                    <?= $totalDebtors ?>
                </span>
            <?php endif; ?>
            <span class="dbt-scope-badge">
                <i class="bi <?= $scopeInfo[2] ?>"></i>
                <?= htmlspecialchars($scopeInfo[0]) ?>
            </span>
        </h2>
        <p>
            <i class="bi bi-info-circle"></i>
            Студенты с пропусками и низкой посещаемостью
            · период: <?= date('d.m.Y', strtotime($from)) ?> — <?= date('d.m.Y', strtotime($to)) ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Дашборд
        </a>
    </div>
</div>

<!-- ФИЛЬТР -->
<div class="dbt-filter">
    <form method="get">
        <div class="dbt-filter-row">
            <div>
                <div class="dbt-filter-label">
                    <i class="bi bi-people"></i> Группа
                </div>
                <select name="group_id" class="dbt-select">
                    <option value="">— Все доступные группы —</option>
                    <?php foreach ($groups as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= $groupId == $g['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <div class="dbt-filter-label">
                    <i class="bi bi-book"></i> Предмет
                </div>
                <select name="subject" class="dbt-select">
                    <option value="">— Все предметы —</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $subject === $s ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <div class="dbt-filter-label">
                    <i class="bi bi-person-badge"></i> Преподаватель
                </div>
                <select name="teacher_id" class="dbt-select">
                    <option value="">— Все доступные —</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $teacherId == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <div class="dbt-filter-label">
                    <i class="bi bi-calendar-event"></i> С
                </div>
                <input type="date" name="from" class="dbt-input"
                       value="<?= htmlspecialchars($from) ?>">
            </div>
            <div>
                <div class="dbt-filter-label">
                    <i class="bi bi-calendar-event"></i> По
                </div>
                <input type="date" name="to" class="dbt-input"
                       value="<?= htmlspecialchars($to) ?>">
            </div>
        </div>

        <div class="dbt-filter-extra">
            <div class="dbt-threshold">
                <label><i class="bi bi-sliders"></i> Пропусков ≥</label>
                <input type="number" name="min_absent" min="1" max="100" value="<?= $minAbsent ?>">
            </div>
            <div class="dbt-threshold">
                <label>ИЛИ посещаемость ≤</label>
                <input type="number" name="max_pct" min="1" max="99" value="<?= $maxPct ?>"> %
            </div>
            <div class="dbt-threshold">
                <label>Сортировка:</label>
                <select name="sort" class="dbt-select" style="width:auto; padding:6px 10px;">
                    <option value="critical" <?= $sortBy === 'critical' ? 'selected' : '' ?>>По критичности</option>
                    <option value="absent"   <?= $sortBy === 'absent'   ? 'selected' : '' ?>>По пропускам</option>
                    <option value="pct"      <?= $sortBy === 'pct'      ? 'selected' : '' ?>>По посещаемости</option>
                    <option value="works"    <?= $sortBy === 'works'    ? 'selected' : '' ?>>По работам</option>
                </select>
            </div>
            <button type="submit" class="dbt-submit-btn" style="margin-left:auto;">
                <i class="bi bi-search"></i> Показать
            </button>
        </div>

        <div class="dbt-actions">
            <a href="debtors.php" class="dbt-action-btn dbt-action-btn--reset">
                <i class="bi bi-x-circle"></i> Сбросить
            </a>
            <?php if ($totalDebtors > 0): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['action' => 'export_csv'])) ?>"
                   class="dbt-action-btn dbt-action-btn--csv">
                    <i class="bi bi-file-earmark-excel"></i> Скачать CSV
                </a>
                <button type="button" class="dbt-action-btn dbt-action-btn--copy" onclick="copyList()">
                    <i class="bi bi-clipboard-check"></i> Скопировать список
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- СТАТИСТИКА -->
<?php if ($totalDebtors > 0): ?>
    <div class="dbt-stats">
        <div class="dbt-stat" style="--dbt-color:#ff453a; --dbt-bg:rgba(255,69,58,0.1);">
            <div class="dbt-stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="dbt-stat-value"><?= $totalDebtors ?></div>
            <div class="dbt-stat-label">Всего должников</div>
            <div class="dbt-stat-sub">по фильтру</div>
        </div>
        <div class="dbt-stat" style="--dbt-color:#dc3545; --dbt-bg:rgba(220,53,69,0.1);">
            <div class="dbt-stat-icon"><i class="bi bi-fire"></i></div>
            <div class="dbt-stat-value"><?= $criticalCount ?></div>
            <div class="dbt-stat-label">Критичных</div>
            <div class="dbt-stat-sub">много пропусков + низкая посещаемость</div>
        </div>
        <div class="dbt-stat" style="--dbt-color:#ff9f0a; --dbt-bg:rgba(255,159,10,0.1);">
            <div class="dbt-stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
            <div class="dbt-stat-value"><?= $warningCount ?></div>
            <div class="dbt-stat-label">На грани</div>
            <div class="dbt-stat-sub">стоит обратить внимание</div>
        </div>
        <div class="dbt-stat" style="--dbt-color:#86868b; --dbt-bg:rgba(134,134,139,0.1);">
            <div class="dbt-stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="dbt-stat-value">
                <?= count(array_unique(array_column($debtors, 'group_id'))) ?>
            </div>
            <div class="dbt-stat-label">Групп затронуто</div>
            <div class="dbt-stat-sub">в выборке</div>
        </div>
    </div>
<?php endif; ?>

<!-- СПИСОК -->
<?php if (empty($debtors)): ?>

    <div class="dbt-card">
        <div class="dbt-empty">
            <i class="bi bi-check-circle-fill"></i>
            <h4>Должников нет! 🎉</h4>
            <p>По выбранным фильтрам все студенты в норме</p>
        </div>
    </div>

<?php else: ?>

    <?php
    $byGroup = [];
    foreach ($debtors as $d) {
        $byGroup[$d['group_id']]['name'] = $d['group_name'];
        $byGroup[$d['group_id']]['students'][] = $d;
    }
    $showGroupHeaders = ($groupId === 0);
    ?>

    <div class="dbt-card">
        <div class="dbt-card-header">
            <h3 class="dbt-card-title">
                <i class="bi bi-list-ul"></i>
                Список должников
            </h3>
            <span class="dbt-card-badge">
                <?= $totalDebtors ?> <?= $totalDebtors === 1 ? 'студент' : 'студентов' ?>
            </span>
        </div>

        <div class="dbt-table-wrap">
            <table class="dbt-table" id="debtorsTable">
                <thead>
                <tr>
                    <th style="width:40px;">№</th>
                    <th>Студент</th>
                    <th style="width:100px;">Зачётка</th>
                    <?php if ($showGroupHeaders): ?>
                        <th style="width:110px;">Группа</th>
                    <?php endif; ?>
                    <th class="center" style="width:90px;">
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'absent'])) ?>"
                           class="<?= $sortBy === 'absent' ? 'active' : '' ?>">
                            Пропустил
                            <?php if ($sortBy === 'absent'): ?><i class="bi bi-arrow-down"></i><?php endif; ?>
                        </a>
                    </th>
                    <th class="center" style="width:140px;">
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'pct'])) ?>"
                           class="<?= $sortBy === 'pct' ? 'active' : '' ?>">
                            Посещаемость
                            <?php if ($sortBy === 'pct'): ?><i class="bi bi-arrow-up"></i><?php endif; ?>
                        </a>
                    </th>
                    <th class="center" style="width:80px;">
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'works'])) ?>"
                           class="<?= $sortBy === 'works' ? 'active' : '' ?>">
                            Работ
                            <?php if ($sortBy === 'works'): ?><i class="bi bi-arrow-up"></i><?php endif; ?>
                        </a>
                    </th>
                    <th class="center" style="width:120px;">Уровень</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $globalIdx = 1;
                foreach ($byGroup as $gid => $data):
                    $groupStudents = $data['students'];
                ?>
                    <?php if ($showGroupHeaders): ?>
                        <tr>
                            <td colspan="<?= $showGroupHeaders ? 8 : 7 ?>" style="padding:0; border:none;">
                                <div class="dbt-group-header">
                                    <i class="bi bi-people-fill"></i>
                                    <?= htmlspecialchars($data['name']) ?>
                                    <span class="count"><?= count($groupStudents) ?></span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($groupStudents as $d):
                        $pct = $d['pct'];
                        $pctClass = 'danger';
                        if ($pct >= 75) $pctClass = 'success';
                        elseif ($pct >= 50) $pctClass = 'warning';

                        $rowClass = '';
                        if ($d['critical_level'] === 'critical') $rowClass = 'dbt-row-critical';
                        elseif ($d['critical_level'] === 'warning') $rowClass = 'dbt-row-warning';

                        $critLabel = 'Низкий';
                        $critIcon = 'bi-circle';
                        if ($d['critical_level'] === 'critical') {
                            $critLabel = 'Критично';
                            $critIcon = 'bi-fire';
                        } elseif ($d['critical_level'] === 'warning') {
                            $critLabel = 'Внимание';
                            $critIcon = 'bi-exclamation-triangle-fill';
                        }
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="num"><?= $globalIdx++ ?></td>
                            <td class="student-name">
                                <?= htmlspecialchars($d['full_name']) ?>
                                <?php if (!empty($d['subgroup'])): ?>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;border-radius:6px;background:<?= $d['subgroup'] === '1' ? 'rgba(0,113,227,0.15)' : 'rgba(191,90,242,0.15)' ?>;color:<?= $d['subgroup'] === '1' ? '#0071e3' : '#bf5af2' ?>;font-weight:700;font-size:0.7rem;margin-left:6px;">
                                        <?= htmlspecialchars($d['subgroup']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($d['zachetka'])): ?>
                                    <code><?= htmlspecialchars($d['zachetka']) ?></code>
                                <?php else: ?>
                                    <span style="color:#86868b;">—</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($showGroupHeaders): ?>
                                <td>
                                    <a href="?group_id=<?= $d['group_id'] ?>&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>"
                                       style="color:#0071e3; text-decoration:none; font-weight:600; font-size:0.82rem;">
                                        <?= htmlspecialchars($d['group_name']) ?>
                                    </a>
                                </td>
                            <?php endif; ?>
                            <td class="num" style="color:#842029; font-weight:700; font-size:1rem;">
                                <?= $d['absent_marks'] ?>
                            </td>
                            <td class="num">
                                <div class="dbt-progress">
                                    <div class="dbt-progress-bar">
                                        <div class="dbt-progress-fill dbt-progress-fill--<?= $pctClass ?>"
                                             style="width: <?= max(0, min(100, $pct)) ?>%;"></div>
                                    </div>
                                    <span class="dbt-progress-text" style="color: <?= $pct >= 75 ? '#198754' : ($pct >= 50 ? '#b25c00' : '#842029') ?>;">
                                        <?= $pct ?>%
                                    </span>
                                </div>
                            </td>
                            <td class="num">
                                <span style="color:#0071e3; font-weight:700; font-size:1rem;">
                                    <?= $d['works_cnt'] ?>
                                </span>
                            </td>
                            <td class="num">
                                <span class="dbt-crit dbt-crit--<?= $d['critical_level'] ?>">
                                    <i class="bi <?= $critIcon ?>"></i>
                                    <?= $critLabel ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<!-- Toast -->
<div class="dbt-toast" id="dbtToast">
    <i class="bi bi-check-circle-fill" style="color:#30d158;"></i>
    <span id="dbtToastText">Готово</span>
</div>

<script>
function copyList() {
    const rows = document.querySelectorAll('#debtorsTable tbody tr');
    const lines = [];

    lines.push('Список должников');
    lines.push('Период: <?= date('d.m.Y', strtotime($from)) ?> — <?= date('d.m.Y', strtotime($to)) ?>');
    lines.push('');

    rows.forEach(row => {
        const groupHeader = row.querySelector('.dbt-group-header');
        if (groupHeader) {
            const groupName = groupHeader.textContent.trim().replace(/\s+/g, ' ').replace(/ \d+$/, '');
            lines.push('');
            lines.push('▸ ' + groupName);
            return;
        }

        const cells = row.querySelectorAll('td');
        if (cells.length < 5) return;

        const hasGroupCol = <?= $showGroupHeaders ? 'true' : 'false' ?>;
        let nameIdx, zachetkaIdx, absentIdx, pctIdx, worksIdx;

        if (hasGroupCol) {
            nameIdx = 1; zachetkaIdx = 2; absentIdx = 4; pctIdx = 5; worksIdx = 6;
        } else {
            nameIdx = 1; zachetkaIdx = 2; absentIdx = 3; pctIdx = 4; worksIdx = 5;
        }

        const name = cells[nameIdx]?.textContent.trim() || '';
        const zachetka = cells[zachetkaIdx]?.textContent.trim() || '';
        const absent = cells[absentIdx]?.textContent.trim() || '';
        const pctText = cells[pctIdx]?.querySelector('.dbt-progress-text')?.textContent.trim() || '';
        const works = cells[worksIdx]?.textContent.trim() || '';

        lines.push(`  • ${name} (${zachetka}) — пропусков: ${absent}, посещаемость: ${pctText}, работ: ${works}`);
    });

    const text = lines.join('\n');

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text)
            .then(() => showToast('Список скопирован в буфер обмена'))
            .catch(() => fallbackCopy(text));
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try {
        document.execCommand('copy');
        showToast('Список скопирован');
    } catch (e) {
        showToast('Не удалось скопировать', true);
    }
    document.body.removeChild(ta);
}

function showToast(text, isError = false) {
    const toast = document.getElementById('dbtToast');
    const txt = document.getElementById('dbtToastText');
    const icon = toast.querySelector('i');

    txt.textContent = text;
    if (isError) {
        icon.className = 'bi bi-exclamation-triangle-fill';
        icon.style.color = '#ff453a';
    } else {
        icon.className = 'bi bi-check-circle-fill';
        icon.style.color = '#30d158';
    }

    toast.classList.add('show');
    clearTimeout(window._dbtToastTimer);
    window._dbtToastTimer = setTimeout(() => toast.classList.remove('show'), 2200);
}
</script>

<?php require_once 'includes/footer.php'; ?>