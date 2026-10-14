<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== ДИАГНОСТИКА БД ===\n\n";

// 1. Всего записей
$tables = ['lessons', 'attendance', 'submitted_works'];
foreach ($tables as $t) {
    $c = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo str_pad($t, 20) . " → $c записей\n";
}
echo "\n";

// 2. Дубли занятий
$stmt = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT 1 FROM lessons
        GROUP BY user_id, lesson_date, lesson_number, discipline, lesson_type,
                 COALESCE(audience,''), COALESCE(subgroup,'0'), COALESCE(group_id,0)
        HAVING COUNT(*) > 1
    ) AS x
");
echo "Групп дублей в lessons: " . $stmt->fetchColumn() . "\n";

// 3. Дубли отметок
$stmt = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT 1 FROM attendance
        GROUP BY lesson_id, student_id
        HAVING COUNT(*) > 1
    ) AS x
");
echo "Групп дублей в attendance: " . $stmt->fetchColumn() . "\n";

// 4. Дубли работ
$stmt = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT 1 FROM submitted_works
        GROUP BY lesson_id, student_id, work_number
        HAVING COUNT(*) > 1
    ) AS x
");
echo "Групп дублей в submitted_works: " . $stmt->fetchColumn() . "\n\n";

// 5. Примеры дублей занятий (первые 5)
echo "=== ПРИМЕРЫ ДУБЛЕЙ ЗАНЯТИЙ ===\n";
$stmt = $pdo->query("
    SELECT
        l.lesson_date,
        l.lesson_number,
        l.discipline,
        COALESCE(l.subgroup,'0') AS sg,
        COALESCE(l.group_id,0) AS gid,
        COUNT(*) AS cnt,
        GROUP_CONCAT(l.id ORDER BY l.id) AS ids
    FROM lessons l
    GROUP BY
        l.lesson_date, l.lesson_number, l.discipline,
        l.lesson_type, COALESCE(l.audience,''),
        COALESCE(l.subgroup,'0'), COALESCE(l.group_id,0),
        l.user_id
    HAVING cnt > 1
    ORDER BY l.lesson_date DESC
    LIMIT 5
");
foreach ($stmt->fetchAll() as $r) {
    echo "{$r['lesson_date']} пара {$r['lesson_number']} «{$r['discipline']}» подгр.{$r['sg']} гр.{$r['gid']}\n";
    echo "   cnt={$r['cnt']}, ids={$r['ids']}\n";
}