<?php
/**
 * Инициализация студенческой зоны.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

// Только авторизованные студенты
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'student') {
    header('Location: /login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Тянем данные студента (id_su — ID в student_users)
$stmt = $pdo->prepare("
    SELECT s.*, g.name AS group_name, g.id AS group_id, g.api_group_id,
           su.id AS id_su, su.login, su.email, su.last_login
    FROM student_users su
    JOIN students s ON s.id = su.student_id
    JOIN groups_table g ON g.id = s.group_id
    WHERE su.id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: /login.php?error=no_student_profile');
    exit;
}

$studentId = (int)$student['id'];
$groupId   = (int)$student['group_id'];
$studentUserId = (int)($student['id_su'] ?? $userId);
$today     = date('Y-m-d');

// ============================================================
// Очередь новых достижений для показа (конфетти)
// ============================================================
if (!isset($_SESSION['pending_achievements'])) {
    $_SESSION['pending_achievements'] = [];
}

if (!function_exists('queueAchievementCelebration')) {
    function queueAchievementCelebration(array $codes): void {
        if (empty($codes)) return;
        if (!isset($_SESSION['pending_achievements'])) $_SESSION['pending_achievements'] = [];
        foreach ($codes as $c) {
            if (!in_array($c, $_SESSION['pending_achievements'], true)) {
                $_SESSION['pending_achievements'][] = $c;
            }
        }
    }
}

// ============================================================
// Автовыдача достижений с throttle (раз в 10 минут)
// ============================================================
$achFile = __DIR__ . '/../../includes/achievements.php';
if (is_file($achFile)) {
    require_once $achFile;
}
$statsFile = __DIR__ . '/../../includes/student_stats.php';
if (is_file($statsFile)) {
    require_once $statsFile;
}

if (function_exists('maybeAwardStudentAchievements')) {
    $forceAchCheck = !empty($_GET['recheck']);

    try {
        $newly = maybeAwardStudentAchievements(
            $pdo,
            $studentId,
            $groupId,
            $studentUserId,
            $forceAchCheck,
            600 // 10 минут
        );

        if (!empty($newly) && function_exists('queueAchievementCelebration')) {
            queueAchievementCelebration($newly);
        }
    } catch (Throwable $e) {
        error_log('maybeAwardStudentAchievements error: ' . $e->getMessage());
    }
}