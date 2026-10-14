<?php
/**
 * Выдать все заслуженные достижения студенту по зачётке.
 * Использование: /tools/grant_demo_achievements.php?zachetka=24Т167
 * ⚠️ После использования УДАЛИТЬ файл!
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/achievements.php';
require_once __DIR__ . '/../includes/student_stats.php';

header('Content-Type: text/plain; charset=utf-8');

$zachetka = trim($_GET['zachetka'] ?? '24Т167');

$stmt = $pdo->prepare("SELECT id, full_name, group_id FROM students WHERE zachetka = ?");
$stmt->execute([$zachetka]);
$student = $stmt->fetch();

if (!$student) {
    exit("❌ Студент с зачёткой {$zachetka} не найден\n");
}

$studentId = (int)$student['id'];
$groupId   = (int)$student['group_id'];

echo "=== Выдача достижений ===\n\n";
echo "Студент: {$student['full_name']}\n";
echo "ID: {$studentId} · Группа ID: {$groupId}\n\n";

// 1. Считаем статистику
$stats = getStudentStats($pdo, $studentId, $groupId);
echo "--- Статистика ---\n";
echo "  Посещаемость:     " . ($stats['attendance_pct'] ?? '—') . "%\n";
echo "  Посещено/всего:   {$stats['present']}/{$stats['total_marked']}\n";
echo "  Работ сдано:      {$stats['works_submitted']}\n";
echo "  Текущая серия:    {$stats['current_streak']}\n";
echo "  Макс серия:       {$stats['max_streak']}\n";
echo "  Дней без проп.:   {$stats['no_absence_days']}\n";
echo "  Рейтинг:          " . ($stats['rank'] ?? '—') . " из {$stats['total_in_group']}\n";
echo "  За 30 дней:       " . ($stats['attendance_30d']['pct'] ?? '—') . "%\n";
echo "  За 7 дней:        " . ($stats['attendance_7d']['pct'] ?? '—') . "%\n\n";

// 2. Сохраняем streak
saveStreakRecord($pdo, $studentId, $stats['current_streak']);
echo "✅ Streak сохранён (current: {$stats['current_streak']}, max: {$stats['max_streak']})\n\n";

// 3. Выдаём достижения
$newly = awardStudentAchievements($pdo, $studentId, $groupId);

echo "--- Выдано достижений: " . count($newly) . " ---\n";
if (empty($newly)) {
    echo "  (ничего нового — все уже есть или условия не выполнены)\n";
} else {
    foreach ($newly as $code) {
        echo "  ✓ {$code}\n";
    }
}

// 4. Итог — что теперь есть у студента
echo "\n--- Все достижения студента ---\n";
$stmt = $pdo->prepare("
    SELECT a.code, a.title, a.points, sa.unlocked_at
    FROM student_achievements sa
    JOIN achievements a ON sa.achievement_id = a.id
    WHERE sa.student_id = ?
    ORDER BY sa.unlocked_at DESC, a.points DESC
");
$stmt->execute([$studentId]);
$all = $stmt->fetchAll();

$totalPoints = 0;
foreach ($all as $a) {
    echo "  🏆 [{$a['points']} очков] {$a['title']} ({$a['code']}) · {$a['unlocked_at']}\n";
    $totalPoints += (int)$a['points'];
}
echo "\n  Всего очков: {$totalPoints}\n";

echo "\n=== Готово ===\n";
echo "Открой /student/index.php и посмотри кабинет.\n";
echo "⚠️  Не забудь удалить tools/grant_demo_achievements.php\n";