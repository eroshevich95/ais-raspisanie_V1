<?php
/**
 * Статистика студента: посещаемость, работы, долги, рейтинг.
 * Используется на главной кабинета, в оценках и для ачивок.
 */

if (!function_exists('getStudentStats')) {
    function getStudentStats(PDO $pdo, int $studentId, int $groupId): array {
        // ----- 1. Личная посещаемость -----
        $stmt = $pdo->prepare("
            SELECT
                COUNT(a.id) AS total_marked,
                COALESCE(SUM(a.is_present), 0) AS present_cnt
            FROM lessons l
            LEFT JOIN attendance a ON a.lesson_id = l.id AND a.student_id = ?
            WHERE l.group_id = ?
        ");
        $stmt->execute([$studentId, $groupId]);
        $att = $stmt->fetch();
        $totalMarked = (int)($att['total_marked'] ?? 0);
        $presentCnt  = (int)($att['present_cnt'] ?? 0);
        $absentCnt   = $totalMarked - $presentCnt;
        $attPct      = $totalMarked > 0 ? (int)round($presentCnt / $totalMarked * 100) : null;

        // ----- 2. Работы -----
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT sw.work_number)
            FROM submitted_works sw
            JOIN lessons l ON sw.lesson_id = l.id
            WHERE sw.student_id = ? AND l.group_id = ?
        ");
        $stmt->execute([$studentId, $groupId]);
        $worksSubmitted = (int)$stmt->fetchColumn();

        // ----- 3. Всего занятий у группы -----
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE group_id = ?");
        $stmt->execute([$groupId]);
        $totalLessons = (int)$stmt->fetchColumn();

        // ----- 4. Средняя посещаемость по группе -----
        $stmt = $pdo->prepare("
            SELECT
                COUNT(a.id) AS total,
                COALESCE(SUM(a.is_present), 0) AS present
            FROM lessons l
            JOIN attendance a ON a.lesson_id = l.id
            JOIN students s ON a.student_id = s.id
            WHERE l.group_id = ? AND s.group_id = ?
        ");
        $stmt->execute([$groupId, $groupId]);
        $g = $stmt->fetch();
        $gTotal   = (int)($g['total'] ?? 0);
        $gPresent = (int)($g['present'] ?? 0);
        $groupAvgPct = $gTotal > 0 ? (int)round($gPresent / $gTotal * 100) : null;

        // ----- 5. Рейтинг в группе -----
        $stmt = $pdo->prepare("
            SELECT
                s.id,
                COUNT(a.id) AS total,
                COALESCE(SUM(a.is_present), 0) AS present
            FROM students s
            LEFT JOIN lessons l ON l.group_id = s.group_id
            LEFT JOIN attendance a ON a.lesson_id = l.id AND a.student_id = s.id
            WHERE s.group_id = ?
            GROUP BY s.id
            HAVING total > 0
        ");
        $stmt->execute([$groupId]);
        $rows = $stmt->fetchAll();

        $scores = [];
        foreach ($rows as $r) {
            $t = (int)$r['total'];
            $p = (int)$r['present'];
            $scores[(int)$r['id']] = $t > 0 ? (int)round($p / $t * 100) : 0;
        }

        arsort($scores);
        $rank = null;
        $totalInGroup = count($scores);
        $place = 0;
        foreach ($scores as $sid => $pct) {
            $place++;
            if ($sid === $studentId) { $rank = $place; break; }
        }

        $percentile = null;
        if ($rank !== null && $totalInGroup > 1) {
            $below = $totalInGroup - $rank;
            $percentile = (int)round($below / ($totalInGroup - 1) * 100);
        }

        // ----- 6. Посещаемость за период (для ачивок) -----
        $attendanceByPeriod = function($days) use ($pdo, $studentId, $groupId) {
            $stmt = $pdo->prepare("
                SELECT
                    COUNT(a.id) AS total,
                    COALESCE(SUM(a.is_present), 0) AS present
                FROM lessons l
                JOIN attendance a ON a.lesson_id = l.id AND a.student_id = ?
                WHERE l.group_id = ?
                  AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ");
            $stmt->execute([$studentId, $groupId, $days]);
            $r = $stmt->fetch();
            $t = (int)($r['total'] ?? 0);
            $p = (int)($r['present'] ?? 0);
            return [
                'total'   => $t,
                'present' => $p,
                'pct'     => $t > 0 ? (int)round($p / $t * 100) : null,
            ];
        };

        // ----- 7. Streak (макс. серия пар без пропусков) -----
        $stmt = $pdo->prepare("
            SELECT l.lesson_date, l.lesson_number, a.is_present
            FROM lessons l
            JOIN attendance a ON a.lesson_id = l.id AND a.student_id = ?
            WHERE l.group_id = ?
            ORDER BY l.lesson_date ASC, l.lesson_time ASC, l.lesson_number ASC
        ");
        $stmt->execute([$studentId, $groupId]);
        $allMarks = $stmt->fetchAll();

        $currentStreak = 0;
        $maxStreak = 0;
        foreach ($allMarks as $m) {
            if ((int)$m['is_present'] === 1) {
                $currentStreak++;
                if ($currentStreak > $maxStreak) $maxStreak = $currentStreak;
            } else {
                $currentStreak = 0;
            }
        }

        // ----- 8. Дней без пропусков подряд -----
        $stmt = $pdo->prepare("
            SELECT
                l.lesson_date,
                SUM(a.is_present) AS present_cnt,
                COUNT(a.id) AS total_cnt
            FROM lessons l
            JOIN attendance a ON a.lesson_id = l.id AND a.student_id = ?
            WHERE l.group_id = ?
            GROUP BY l.lesson_date
            ORDER BY l.lesson_date DESC
        ");
        $stmt->execute([$studentId, $groupId]);
        $byDay = $stmt->fetchAll();

        $noAbsenceDays = 0;
        foreach ($byDay as $d) {
            if ((int)$d['total_cnt'] > 0 && (int)$d['present_cnt'] === (int)$d['total_cnt']) {
                $noAbsenceDays++;
            } else {
                break;
            }
        }

        // ----- 9. Пропуски по предметам -----
        $stmt = $pdo->prepare("
            SELECT
                l.discipline,
                COUNT(a.id) AS total,
                COALESCE(SUM(a.is_present), 0) AS present
            FROM lessons l
            JOIN attendance a ON a.lesson_id = l.id AND a.student_id = ?
            WHERE l.group_id = ?
            GROUP BY l.discipline
            HAVING total > 0
            ORDER BY (total - present) DESC, l.discipline ASC
        ");
        $stmt->execute([$studentId, $groupId]);
        $bySubject = $stmt->fetchAll();

        $subjectsWithDebts = [];
        foreach ($bySubject as $s) {
            $abs = (int)$s['total'] - (int)$s['present'];
            if ($abs > 0) {
                $subjectsWithDebts[] = [
                    'discipline' => $s['discipline'],
                    'absent'     => $abs,
                    'total'      => (int)$s['total'],
                    'pct'        => (int)round((int)$s['present'] / (int)$s['total'] * 100),
                ];
            }
        }

        // ----- 10. Несданные работы -----
        $stmt = $pdo->prepare("
            SELECT DISTINCT sw.work_number, l.discipline
            FROM submitted_works sw
            JOIN lessons l ON sw.lesson_id = l.id
            WHERE l.group_id = ?
              AND sw.work_number NOT IN (
                  SELECT sw2.work_number
                  FROM submitted_works sw2
                  JOIN lessons l2 ON sw2.lesson_id = l2.id
                  WHERE sw2.student_id = ? AND l2.group_id = ?
              )
        ");
        $stmt->execute([$groupId, $studentId, $groupId]);
        $missingWorks = $stmt->fetchAll();

        return [
            'attendance_pct'      => $attPct,
            'present'             => $presentCnt,
            'absent'              => $absentCnt,
            'total_marked'        => $totalMarked,
            'works_submitted'     => $worksSubmitted,
            'total_lessons'       => $totalLessons,
            'group_avg_pct'       => $groupAvgPct,
            'rank'                => $rank,
            'total_in_group'      => $totalInGroup,
            'percentile'          => $percentile,
            'attendance_30d'      => $attendanceByPeriod(30),
            'attendance_7d'       => $attendanceByPeriod(7),
            'attendance_120d'     => $attendanceByPeriod(120),
            'current_streak'      => $currentStreak,
            'max_streak'          => $maxStreak,
            'no_absence_days'     => $noAbsenceDays,
            'subjects_with_debts' => $subjectsWithDebts,
            'missing_works'       => $missingWorks,
        ];
    }
}


/* ============================================================
   СЕРИЯ ПОСЕЩЕНИЙ (streak)
   ============================================================
   - current_streak пересчитывается из attendance при каждом вызове
   - max_streak хранится в БД (student_streaks) и не сбрасывается
     даже при удалении старых занятий через tools/cleanup.php
   ============================================================ */

if (!function_exists('getStreakRecord')) {
    /**
     * Возвращает текущую и рекордную серию посещений студента.
     *
     * @return array{current:int, max_all_time:int, is_record:bool}
     */
    function getStreakRecord(PDO $pdo, int $studentId, int $groupId): array
    {
        // 1. Текущая серия — пересчитываем по свежим данным
        $stats = getStudentStats($pdo, $studentId, $groupId);
        $current = (int)($stats['current_streak'] ?? 0);

        // 2. Историческая серия по текущим данным
        $historical = (int)($stats['max_streak'] ?? 0);

        // 3. Рекорд из БД
        $maxAllTime = 0;
        try {
            $stmt = $pdo->prepare("
                SELECT max_streak FROM student_streaks WHERE student_id = ?
            ");
            $stmt->execute([$studentId]);
            $maxAllTime = (int)($stmt->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            // Таблицы может не быть — считаем рекорд равным 0
            $maxAllTime = 0;
        }

        // 4. Итоговый рекорд = максимум из БД, истории и текущей
        $maxAllTime = max($maxAllTime, $historical, $current);

        return [
            'current'      => $current,
            'max_all_time' => $maxAllTime,
            'is_record'    => $current > 0 && $current >= $maxAllTime,
        ];
    }
}

if (!function_exists('saveStreakRecord')) {
    /**
     * Обновляет рекорд студента, если текущая серия его превысила.
     * Безопасно вызывать при каждом заходе в кабинет.
     */
    function saveStreakRecord(PDO $pdo, int $studentId, int $currentStreak): void
    {
        if ($currentStreak < 1) {
            // Не пишем нули — нет смысла обновлять
            return;
        }

        try {
            $pdo->prepare("
                INSERT INTO student_streaks (student_id, current_streak, max_streak)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    current_streak = VALUES(current_streak),
                    max_streak     = GREATEST(max_streak, VALUES(max_streak))
            ")->execute([$studentId, $currentStreak, $currentStreak]);
        } catch (Throwable $e) {
            // Тихо игнорируем — не ломаем основную логику кабинета
            error_log('saveStreakRecord error: ' . $e->getMessage());
        }
    }
}