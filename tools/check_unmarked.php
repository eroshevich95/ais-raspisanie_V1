<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    die('Не залогинен');
}

$userId = (int)$_SESSION['user_id'];
echo "user_id = $userId\n\n";

// 1. Сколько вообще занятий у пользователя?
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT CONCAT(lesson_date, '|', COALESCE(lesson_number,0), '|', discipline, '|', COALESCE(audience,''), '|', COALESCE(subgroup,'0'))) AS cnt
    FROM lessons
    WHERE user_id = ?
");
$stmt->execute([$userId]);
echo "Уникальных пар всего: " . $stmt->fetchColumn() . "\n";

// 2. Сколько в прошлом (до сегодня)?
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT CONCAT(lesson_date, '|', COALESCE(lesson_number,0), '|', discipline, '|', COALESCE(audience,''), '|', COALESCE(subgroup,'0'))) AS cnt
    FROM lessons
    WHERE user_id = ? AND lesson_date < CURDATE()
");
$stmt->execute([$userId]);
echo "Уникальных пар в прошлом: " . $stmt->fetchColumn() . "\n";

// 3. Сколько в прошлом за 14 дней?
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT CONCAT(lesson_date, '|', COALESCE(lesson_number,0), '|', discipline, '|', COALESCE(audience,''), '|', COALESCE(subgroup,'0'))) AS cnt
    FROM lessons
    WHERE user_id = ? AND lesson_date < CURDATE() AND lesson_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
");
$stmt->execute([$userId]);
echo "Уникальных пар за 14 дней: " . $stmt->fetchColumn() . "\n\n";

// 4. Сколько с отметками?
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT CONCAT(l.lesson_date, '|', COALESCE(l.lesson_number,0), '|', l.discipline, '|', COALESCE(l.audience,''), '|', COALESCE(l.subgroup,'0'))) AS cnt
    FROM lessons l
    JOIN attendance a ON a.lesson_id = l.id
    WHERE l.user_id = ?
      AND l.lesson_date < CURDATE()
      AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
");
$stmt->execute([$userId]);
echo "Уникальных пар за 14 дней С отметками: " . $stmt->fetchColumn() . "\n\n";

// 5. Список пар за 14 дней (детально)
echo "=== СПИСОК ПАР ЗА 14 ДНЕЙ ===\n\n";
$stmt = $pdo->prepare("
    SELECT
        MIN(l.id) AS id,
        l.lesson_date,
        l.lesson_number,
        l.discipline,
        l.audience,
        l.subgroup,
        (SELECT COUNT(*) FROM attendance a
         WHERE a.lesson_id IN (
             SELECT l2.id FROM lessons l2
             WHERE l2.user_id = l.user_id
               AND l2.lesson_date = l.lesson_date
               AND l2.lesson_number <=> l.lesson_number
               AND l2.discipline = l.discipline
               AND COALESCE(l2.audience,'') = COALESCE(l.audience,'')
               AND COALESCE(l2.subgroup,'0') = COALESCE(l.subgroup,'0')
         )) AS marks_count
    FROM lessons l
    WHERE l.user_id = ?
      AND l.lesson_date < CURDATE()
      AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY
        l.lesson_date, l.lesson_number, l.discipline,
        COALESCE(l.audience,''), COALESCE(l.subgroup,'0')
    ORDER BY l.lesson_date DESC, l.lesson_number
    LIMIT 30
");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $r) {
    $status = $r['marks_count'] > 0 ? '✅' : '❌';
    echo "$status {$r['lesson_date']} пара {$r['lesson_number']} «{$r['discipline']}» marks={$r['marks_count']}\n";
}