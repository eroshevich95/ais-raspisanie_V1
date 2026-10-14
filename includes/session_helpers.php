<?php
/**
 * Хелперы для модуля "Сессия"
 */

/**
 * Определяет текущий семестр по дате.
 * Сентябрь-январь → 1-й семестр (2025-2026-1)
 * Февраль-Август → 2-й семестр (2025-2026-2)
 */
function getCurrentSemester(?string $date = null): string {
    $ts = $date ? strtotime($date) : time();
    $year = (int)date('Y', $ts);
    $month = (int)date('n', $ts);

    if ($month >= 9) {
        // Сентябрь-декабрь: 2025-2026-1
        return $year . '-' . ($year + 1) . '-1';
    }
    if ($month === 1) {
        // Январь: ещё первый семестр учебного года 2024-2025-1
        return ($year - 1) . '-' . $year . '-1';
    }
    // Февраль-август: 2024-2025-2
    return ($year - 1) . '-' . $year . '-2';
}

/**
 * Возвращает читаемое название семестра.
 */
function semesterLabel(string $semester): string {
    $parts = explode('-', $semester);
    if (count($parts) !== 3) return $semester;
    $year1 = $parts[0];
    $year2 = $parts[1];
    $num   = $parts[2];
    return "$year1/$year2 · $num семестр";
}

/**
 * Возвращает типы сессии.
 */
function sessionTypes(): array {
    return [
        'zachet'      => ['label' => 'Зачёт',            'icon' => 'bi-clipboard-check', 'color' => 'success'],
        'exam'        => ['label' => 'Экзамен',          'icon' => 'bi-mortarboard',     'color' => 'danger'],
        'diff_zachet' => ['label' => 'Дифф. зачёт',      'icon' => 'bi-award',           'color' => 'warning'],
    ];
}

/**
 * Возвращает допустимые оценки для типа сессии.
 */
function allowedGrades(string $type): array {
    $common = ['не явился' => 'Не явился'];

    switch ($type) {
        case 'zachet':
            return array_merge([
                'зачтено'    => 'Зачтено',
                'не зачтено' => 'Не зачтено',
            ], $common);

        case 'exam':
        case 'diff_zachet':
            return array_merge([
                'отлично'               => 'Отлично',
                'хорошо'                => 'Хорошо',
                'удовлетворительно'     => 'Удовлетворительно',
                'не удовлетворительно'  => 'Не удовлетворительно',
            ], $common);
    }
    return $common;
}

/**
 * Считает статистику студента по дисциплине (все занятия препода в группе за семестр).
 *
 * Возвращает:
 *   [
 *     'total_marked' => N,          // всего отметок
 *     'present'      => M,          // было "был"
 *     'absent'       => N-M,
 *     'attendance_pct' => X,        // % посещаемости (0..100), null если нет отметок
 *     'works_total'  => W,          // сколько видов работ было задано
 *     'works_submitted' => S,       // сколько сдал
 *     'works_pct'    => P,          // % сданных работ
 *   ]
 */
function calcStudentSessionStats(PDO $pdo, int $studentId, int $groupId, string $discipline, int $userId, string $from, string $to): array {
    // 1. Посещаемость: все занятия препода по этому предмету в этой группе за период
    $stmt = $pdo->prepare("
        SELECT
            COUNT(a.id) AS total_marked,
            COALESCE(SUM(a.is_present), 0) AS present
        FROM lessons l
        LEFT JOIN attendance a ON a.lesson_id = l.id AND a.student_id = ?
        WHERE l.user_id = ?
          AND l.group_id = ?
          AND l.discipline = ?
          AND l.lesson_date BETWEEN ? AND ?
    ");
    $stmt->execute([$studentId, $userId, $groupId, $discipline, $from, $to]);
    $row = $stmt->fetch();

    $totalMarked = (int)($row['total_marked'] ?? 0);
    $present     = (int)($row['present'] ?? 0);

    // 2. Работы: сколько видов работ по этому предмету и сколько сдал студент
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT sw.work_number) AS cnt
        FROM submitted_works sw
        JOIN lessons l ON sw.lesson_id = l.id
        WHERE l.user_id = ?
          AND l.group_id = ?
          AND l.discipline = ?
          AND l.lesson_date BETWEEN ? AND ?
    ");
    $stmt->execute([$userId, $groupId, $discipline, $from, $to]);
    $worksTotal = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT sw.work_number) AS cnt
        FROM submitted_works sw
        JOIN lessons l ON sw.lesson_id = l.id
        WHERE sw.student_id = ?
          AND l.user_id = ?
          AND l.group_id = ?
          AND l.discipline = ?
          AND l.lesson_date BETWEEN ? AND ?
    ");
    $stmt->execute([$studentId, $userId, $groupId, $discipline, $from, $to]);
    $worksSubmitted = (int)$stmt->fetchColumn();

    $attendancePct = $totalMarked > 0 ? (int)round($present / $totalMarked * 100) : null;
    $worksPct      = $worksTotal > 0 ? (int)round($worksSubmitted / $worksTotal * 100) : null;

    return [
        'total_marked'    => $totalMarked,
        'present'         => $present,
        'absent'          => $totalMarked - $present,
        'attendance_pct'  => $attendancePct,
        'works_total'     => $worksTotal,
        'works_submitted' => $worksSubmitted,
        'works_pct'       => $worksPct,
    ];
}

/**
 * Автоматический вердикт "допущен".
 * NULL — если данных мало (нет отметок вообще).
 */
function autoVerdict(?int $attendancePct, ?int $worksPct, int $minAttendance, int $minWorks): ?int {
    if ($attendancePct === null && $worksPct === null) return null;

    $attOk = $attendancePct === null ? true : ($attendancePct >= $minAttendance);
    $wrkOk = $worksPct      === null ? true : ($worksPct      >= $minWorks);

    return ($attOk && $wrkOk) ? 1 : 0;
}

/**
 * Диапазон дат семестра.
 */
function semesterDateRange(string $semester): array {
    $parts = explode('-', $semester);
    if (count($parts) !== 3) {
        // fallback: текущий учебный год
        return [date('Y-09-01'), date('Y-m-d')];
    }
    $year1 = (int)$parts[0];
    $year2 = (int)$parts[1];
    $num   = (int)$parts[2];

    if ($num === 1) {
        return ["$year1-09-01", "$year2-01-31"];
    }
    return ["$year2-02-01", "$year2-08-31"];
}