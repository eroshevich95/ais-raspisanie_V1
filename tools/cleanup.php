<?php
/**
 * Чистка дублей lessons + перенос attendance и submitted_works.
 *
 * Правило: при конфликтах оставляем ПОСЛЕДНЮЮ отметку (по id).
 *
 * Запуск: https://твой_сайт/tools/cleanup.php
 *
 * ⚠️ СНАЧАЛА сделай бэкап БД!
 * ⚠️ После запуска — УДАЛИ ЭТОТ ФАЙЛ!
 */

require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain; charset=utf-8');

set_time_limit(1800);
ini_set('memory_limit', '512M');

echo "=== ЧИСТКА ДУБЛЕЙ lessons + ПЕРЕНОС ===\n";
echo "=== Правило: при конфликтах — последнее значение ===\n\n";

function countRows(PDO $pdo, string $table): int {
    return (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}

$beforeLessons = countRows($pdo, 'lessons');
$beforeAttend  = countRows($pdo, 'attendance');
$beforeWorks   = countRows($pdo, 'submitted_works');

echo "ДО:\n";
echo "  lessons:         $beforeLessons\n";
echo "  attendance:      $beforeAttend\n";
echo "  submitted_works: $beforeWorks\n\n";

// ============================================================
// 1. Обнуляем NULL
// ============================================================
$pdo->exec("UPDATE lessons SET audience = '' WHERE audience IS NULL");
$pdo->exec("UPDATE lessons SET subgroup = '0' WHERE subgroup IS NULL");
echo "1. NULL в audience/subgroup обнулены\n\n";

// ============================================================
// 2. Находим группы дублей
// ============================================================
$stmt = $pdo->query("
    SELECT
        user_id,
        lesson_date,
        lesson_number,
        discipline,
        lesson_type,
        COALESCE(audience,'') AS aud,
        COALESCE(subgroup,'0') AS sg,
        COALESCE(group_id,0) AS gid,
        GROUP_CONCAT(id ORDER BY id) AS ids
    FROM lessons
    GROUP BY
        user_id, lesson_date, lesson_number, discipline, lesson_type,
        COALESCE(audience,''), COALESCE(subgroup,'0'), COALESCE(group_id,0)
    HAVING COUNT(*) > 1
");
$groups = $stmt->fetchAll();
echo "2. Найдено групп дублей: " . count($groups) . "\n\n";

if (empty($groups)) {
    echo "Дублей нет — чистить нечего.\n";
    exit;
}

// ============================================================
// 3. Обработка
// ============================================================
$pdo->beginTransaction();

try {
    $totalMovedAtt       = 0;
    $totalConflicts      = 0;
    $totalMovedWorks     = 0;
    $totalDeletedLessons = 0;

    foreach ($groups as $g) {
        $ids = array_map('intval', explode(',', $g['ids']));
        sort($ids);

        // ---- Каноничное занятие: последнее (наибольший id) с приоритетом api ----
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT
                l.id,
                l.api_lesson_id,
                (SELECT COUNT(*) FROM attendance a WHERE a.lesson_id = l.id) AS att_cnt,
                (SELECT COUNT(*) FROM submitted_works w WHERE w.lesson_id = l.id) AS works_cnt
            FROM lessons l
            WHERE l.id IN ($in)
        ");
        $stmt->execute($ids);
        $info = $stmt->fetchAll();

        usort($info, function($a, $b) {
            // 1. Приоритет — с api_lesson_id (синхронизированные)
            $aApi = !empty($a['api_lesson_id']) ? 1 : 0;
            $bApi = !empty($b['api_lesson_id']) ? 1 : 0;
            if ($aApi !== $bApi) return $bApi <=> $aApi;

            // 2. Больше отметок+работ
            $aM = (int)$a['att_cnt'] + (int)$a['works_cnt'];
            $bM = (int)$b['att_cnt'] + (int)$b['works_cnt'];
            if ($aM !== $bM) return $bM <=> $aM;

            // 3. ПОСЛЕДНИЙ (наибольший id)
            return (int)$b['id'] <=> (int)$a['id'];
        });

        $canonical = (int)$info[0]['id'];
        $dupes = [];
        foreach ($info as $i) {
            if ((int)$i['id'] !== $canonical) $dupes[] = (int)$i['id'];
        }

        if (empty($dupes)) continue;

        $inDupes = implode(',', array_fill(0, count($dupes), '?'));

        // ---- Перенос attendance ----
        $stmt = $pdo->prepare("
            SELECT id, student_id, is_present
            FROM attendance WHERE lesson_id IN ($inDupes)
        ");
        $stmt->execute($dupes);
        $dupeMarks = $stmt->fetchAll();

        foreach ($dupeMarks as $dm) {
            $sid = (int)$dm['student_id'];
            $val = (int)$dm['is_present'];

            $stmt2 = $pdo->prepare("
                SELECT id, is_present FROM attendance
                WHERE lesson_id = ? AND student_id = ?
            ");
            $stmt2->execute([$canonical, $sid]);
            $existing = $stmt2->fetch();

            if ($existing) {
                $totalConflicts++;
                // Приоритет — у последнего обновления (наибольший id)
                if ((int)$dm['id'] > (int)$existing['id']) {
                    $pdo->prepare("UPDATE attendance SET is_present = ? WHERE id = ?")
                        ->execute([$val, $existing['id']]);
                }
                $pdo->prepare("DELETE FROM attendance WHERE id = ?")
                    ->execute([$dm['id']]);
            } else {
                $pdo->prepare("UPDATE attendance SET lesson_id = ? WHERE id = ?")
                    ->execute([$canonical, $dm['id']]);
                $totalMovedAtt++;
            }
        }

        // ---- Перенос works ----
        $stmt = $pdo->prepare("
            SELECT id, student_id, work_number
            FROM submitted_works WHERE lesson_id IN ($inDupes)
        ");
        $stmt->execute($dupes);
        $dupeWorks = $stmt->fetchAll();

        foreach ($dupeWorks as $dw) {
            $sid = (int)$dw['student_id'];
            $wn  = $dw['work_number'];

            $stmt2 = $pdo->prepare("
                SELECT id FROM submitted_works
                WHERE lesson_id = ? AND student_id = ? AND work_number = ?
            ");
            $stmt2->execute([$canonical, $sid, $wn]);
            $exists = $stmt2->fetchColumn();

            if ($exists) {
                // Дубликат — оставляем тот, у которого id больше
                if ((int)$dw['id'] > (int)$exists) {
                    $pdo->prepare("UPDATE submitted_works SET lesson_id = ? WHERE id = ?")
                        ->execute([$canonical, $dw['id']]);
                    $pdo->prepare("DELETE FROM submitted_works WHERE id = ?")
                        ->execute([$exists]);
                } else {
                    $pdo->prepare("DELETE FROM submitted_works WHERE id = ?")
                        ->execute([$dw['id']]);
                }
            } else {
                $pdo->prepare("UPDATE submitted_works SET lesson_id = ? WHERE id = ?")
                    ->execute([$canonical, $dw['id']]);
                $totalMovedWorks++;
            }
        }

        // ---- Удаляем дубли занятий ----
        $pdo->prepare("DELETE FROM lessons WHERE id IN ($inDupes)")
            ->execute($dupes);
        $totalDeletedLessons += count($dupes);
    }

    $pdo->commit();

    echo "3. Результаты:\n";
    echo "   Перенесено attendance: $totalMovedAtt\n";
    echo "   Разрешено конфликтов:  $totalConflicts\n";
    echo "   Перенесено works:      $totalMovedWorks\n";
    echo "   Удалено дублей:        $totalDeletedLessons\n\n";

} catch (Throwable $e) {
    $pdo->rollBack();
    echo "❌ ОШИБКА: " . $e->getMessage() . " (строка " . $e->getLine() . ")\n";
    exit;
}

// ============================================================
// 4. Удаляем сирот
// ============================================================
$pdo->exec("
    DELETE a FROM attendance a
    LEFT JOIN lessons l ON a.lesson_id = l.id WHERE l.id IS NULL
");
$orphanAtt = $pdo->rowCount();

$pdo->exec("
    DELETE w FROM submitted_works w
    LEFT JOIN lessons l ON w.lesson_id = l.id WHERE l.id IS NULL
");
$orphanWorks = $pdo->rowCount();

echo "4. Удалено сирот: attendance=$orphanAtt, works=$orphanWorks\n\n";

// ============================================================
// 5. Уникальные индексы
// ============================================================
echo "5. Создание индексов...\n";

$indexes = [
    ['lessons', "ALTER TABLE lessons ADD UNIQUE KEY uniq_lesson_stream (user_id, lesson_date, lesson_number, discipline, lesson_type, audience, subgroup, group_id)"],
    ['attendance', "ALTER TABLE attendance ADD UNIQUE KEY uniq_attendance (lesson_id, student_id)"],
    ['submitted_works', "ALTER TABLE submitted_works ADD UNIQUE KEY uniq_work (lesson_id, student_id, work_number)"],
];

foreach ($indexes as $idx) {
    try {
        $pdo->exec($idx[1]);
        echo "   ✅ {$idx[0]}\n";
    } catch (Throwable $e) {
        echo "   ⚠️ {$idx[0]}: " . $e->getMessage() . "\n";
    }
}

// ============================================================
// 6. Итоги
// ============================================================
$afterLessons = countRows($pdo, 'lessons');
$afterAttend  = countRows($pdo, 'attendance');
$afterWorks   = countRows($pdo, 'submitted_works');

echo "\n=== ИТОГО ===\n";
echo "lessons:         $beforeLessons → $afterLessons (удалено " . ($beforeLessons - $afterLessons) . ")\n";
echo "attendance:      $beforeAttend → $afterAttend\n";
echo "submitted_works: $beforeWorks → $afterWorks\n\n";

echo "✅ Готово!\n\n";
echo "⚠️ УДАЛИ ЭТОТ ФАЙЛ: tools/cleanup.php\n";