<?php
// api/get_schedule.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$userId    = $_SESSION['user_id'];
$weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));

$monday = new DateTime($weekStart);
$monday->modify('monday this week');
$sunday = (clone $monday)->modify('+6 days');

$stmt = $pdo->prepare("
    SELECT l.*, g.name AS group_name, g.api_group_id
    FROM lessons l
    LEFT JOIN groups_table g ON l.group_id = g.id
    WHERE l.user_id = ? AND l.lesson_date BETWEEN ? AND ?
    ORDER BY l.lesson_date, l.lesson_time
");
$stmt->execute([$userId, $monday->format('Y-m-d'), $sunday->format('Y-m-d')]);
$lessons = $stmt->fetchAll();

echo json_encode([
    'success'    => true,
    'week_start' => $monday->format('Y-m-d'),
    'week_end'   => $sunday->format('Y-m-d'),
    'lessons'    => $lessons,
], JSON_UNESCAPED_UNICODE);