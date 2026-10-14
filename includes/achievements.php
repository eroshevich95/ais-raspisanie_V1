<?php
/**
 * Логика присвоения достижений студентам.
 */

if (!function_exists('getAllAchievements')) {
    function getAllAchievements(PDO $pdo, bool $onlyActive = true): array {
        $sql = "SELECT * FROM achievements";
        if ($onlyActive) $sql .= " WHERE is_active = 1";
        $sql .= " ORDER BY sort_order ASC, id ASC";
        try {
            return $pdo->query($sql)->fetchAll() ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('evaluateAchievementCondition')) {
    /**
     * Проверяет условие ачивки.
     * @return array ['progress' => 0..100, 'current' => X, 'target' => Y]
     */
    function evaluateAchievementCondition(array $a, array $stats): array {
        $type    = $a['condition_type'] ?? '';
        $target  = (float)($a['condition_value'] ?? 0);
        $period  = (int)($a['condition_period'] ?? 0);
        $current = 0;

        switch ($type) {
            case 'attendance_pct':
                if ($period === 30)       $current = $stats['attendance_30d']['pct']  ?? 0;
                elseif ($period === 7)    $current = $stats['attendance_7d']['pct']   ?? 0;
                elseif ($period === 120)  $current = $stats['attendance_120d']['pct'] ?? 0;
                else                      $current = $stats['attendance_pct']         ?? 0;
                if ($period === 30 && ($stats['attendance_30d']['total'] ?? 0) === 0) $current = 0;
                break;

            case 'works_count':
                $current = $stats['works_submitted'] ?? 0;
                break;

            case 'streak_lessons':
                $current = $stats['max_streak'] ?? 0;
                break;

            case 'no_absence_days':
                $current = $stats['no_absence_days'] ?? 0;
                break;

            case 'early_bird':
                $current = 0; // задел на будущее
                break;

            case 'rank_group':
                if (($stats['rank'] ?? 0) > 0 && $stats['rank'] <= $target) {
                    $current = $target;
                }
                break;

            default:
                $current = 0;
        }

        $progress = 0;
        if ($target > 0) {
            $progress = min(100, (int)round($current / $target * 100));
        }

        return [
            'progress' => $progress,
            'current'  => $current,
            'target'   => $target,
        ];
    }
}

if (!function_exists('getStudentAchievements')) {
    /**
     * Полный список достижений студента: присвоенные + с прогрессом.
     */
    function getStudentAchievements(PDO $pdo, int $studentId, int $groupId, array $stats = null): array {
        if ($stats === null) {
            require_once __DIR__ . '/student_stats.php';
            $stats = getStudentStats($pdo, $studentId, $groupId);
        }

        $all = getAllAchievements($pdo);

        $stmt = $pdo->prepare("
            SELECT achievement_id, unlocked_at, progress_value
            FROM student_achievements
            WHERE student_id = ?
        ");
        $stmt->execute([$studentId]);
        $unlockedRows = [];
        foreach ($stmt->fetchAll() as $r) {
            $unlockedRows[(int)$r['achievement_id']] = $r;
        }

        $unlocked = [];
        $locked   = [];
        $totalPoints = 0;

        foreach ($all as $a) {
            $cond = evaluateAchievementCondition($a, $stats);
            $isUnlocked = isset($unlockedRows[(int)$a['id']]);

            $item = [
                'id'          => (int)$a['id'],
                'code'        => $a['code'],
                'title'       => $a['title'],
                'description' => $a['description'],
                'icon'        => $a['icon'],
                'svg'         => $a['svg'],
                'color'       => $a['color'],
                'category'    => $a['category'],
                'points'      => (int)$a['points'],
                'progress'    => $cond['progress'],
                'current'     => $cond['current'],
                'target'      => $cond['target'],
            ];

            if ($isUnlocked) {
                $item['unlocked_at']     = $unlockedRows[(int)$a['id']]['unlocked_at'];
                $item['value_at_unlock'] = $unlockedRows[(int)$a['id']]['progress_value'];
                $unlocked[] = $item;
                $totalPoints += (int)$a['points'];
            } else {
                $locked[] = $item;
            }
        }

        usort($locked, fn($a, $b) => $b['progress'] <=> $a['progress']);

        return [
            'unlocked'     => $unlocked,
            'locked'       => $locked,
            'total_points' => $totalPoints,
        ];
    }
}

if (!function_exists('awardStudentAchievements')) {
    /**
     * Проверяет условия и выдаёт новые ачивки студенту.
     * @return array Список кодов выданных ачивок
     */
    function awardStudentAchievements(PDO $pdo, int $studentId, int $groupId): array {
        $all = getAllAchievements($pdo);
        if (empty($all)) return [];

        require_once __DIR__ . '/student_stats.php';
        $stats = getStudentStats($pdo, $studentId, $groupId);

        $stmt = $pdo->prepare("SELECT achievement_id FROM student_achievements WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $have = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $awarded = [];

        foreach ($all as $a) {
            $id = (int)$a['id'];
            if (in_array($id, $have, true)) continue;

            $cond = evaluateAchievementCondition($a, $stats);

            if ($cond['target'] > 0 && $cond['current'] >= $cond['target']) {
                try {
                    $pdo->prepare("
                        INSERT IGNORE INTO student_achievements
                            (student_id, achievement_id, progress_value)
                        VALUES (?, ?, ?)
                    ")->execute([$studentId, $id, $cond['current']]);

                    $awarded[] = $a['code'];
                } catch (Throwable $e) {
                    // тихо
                }
            }
        }

        return $awarded;
    }
}

if (!function_exists('maybeAwardStudentAchievements')) {
    /**
     * Проверяет, нужно ли пересчитать достижения, и если да — выдаёт.
     *
     * Throttle: не чаще, чем раз в $ttlSeconds (по умолчанию 10 минут).
     * Хранит отметку времени в student_users.last_ach_check_at.
     *
     * @param int|null $studentUserId  ID в student_users (если null — throttle пропускается)
     * @param bool     $force          Игнорировать throttle
     * @return array   Список кодов новых ачивок (пустой, если проверка не выполнялась)
     */
    function maybeAwardStudentAchievements(
        PDO $pdo,
        int $studentId,
        int $groupId,
        ?int $studentUserId = null,
        bool $force = false,
        int $ttlSeconds = 600
    ): array {
        require_once __DIR__ . '/student_stats.php';

        // 1. Проверяем throttle
        if (!$force && $studentUserId !== null) {
            try {
                $stmt = $pdo->prepare("
                    SELECT last_ach_check_at FROM student_users WHERE id = ?
                ");
                $stmt->execute([$studentUserId]);
                $lastCheck = $stmt->fetchColumn();

                if ($lastCheck && (time() - strtotime($lastCheck)) < $ttlSeconds) {
                    // Ещё не время — ничего не делаем
                    return [];
                }
            } catch (Throwable $e) {
                // Колонки/таблицы может не быть — просто идём дальше
            }
        }

        // 2. Обновляем статистику и выдаём достижения
        $newly = awardStudentAchievements($pdo, $studentId, $groupId);

        // 3. Обновляем время последней проверки
        if ($studentUserId !== null) {
            try {
                $pdo->prepare("
                    UPDATE student_users
                    SET last_ach_check_at = NOW()
                    WHERE id = ?
                ")->execute([$studentUserId]);
            } catch (Throwable $e) {
                // тихо
            }
        }

        return $newly;
    }
}