<?php
/**
 * Хелперы для журнала действий.
 *
 * Использование:
 *   auditLog($pdo, 'attendance.update', [
 *       'entity_type' => 'lesson',
 *       'entity_id'   => 123,
 *       'description' => 'Отметка посещаемости',
 *       'old_value'   => 'пропуск',
 *       'new_value'   => 'был',
 *   ]);
 */

if (!function_exists('auditLog')) {

    function auditLog(PDO $pdo, string $action, array $data = []): void {
        // Пропускаем, если не залогинен (для login.php — логин через прямой вызов)
        $userId   = (int)($data['user_id']   ?? ($_SESSION['user_id'] ?? 0));
        $userName = $data['user_name'] ?? ($_SESSION['user_name'] ?? null);

        if (!$userId) return;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_log
                    (user_id, user_name, action, entity_type, entity_id,
                     description, old_value, new_value, ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = isset($_SERVER['HTTP_USER_AGENT'])
                ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 250)
                : null;

            $oldValue = $data['old_value'] ?? null;
            $newValue = $data['new_value'] ?? null;

            // Если это массивы — превратим в JSON
            if (is_array($oldValue)) $oldValue = json_encode($oldValue, JSON_UNESCAPED_UNICODE);
            if (is_array($newValue)) $newValue = json_encode($newValue, JSON_UNESCAPED_UNICODE);

            $stmt->execute([
                $userId,
                $userName,
                $action,
                $data['entity_type'] ?? null,
                $data['entity_id']   ?? null,
                $data['description'] ?? null,
                $oldValue,
                $newValue,
                $ip,
                $ua,
            ]);
        } catch (Throwable $e) {
            // Тихо игнорируем, чтобы не сломать основную логику
            error_log('auditLog error: ' . $e->getMessage());
        }
    }
}

if (!function_exists('auditActionLabels')) {
    /**
     * Человекочитаемые названия действий.
     */
    function auditActionLabels(): array {
        return [
            'login.success'         => ['Вход в систему', 'success', 'bi-box-arrow-in-right'],
            'login.failed'          => ['Неудачный вход', 'danger', 'bi-shield-exclamation'],
            'logout'                => ['Выход', 'secondary', 'bi-box-arrow-right'],

            'attendance.update'     => ['Изменение посещаемости', 'primary', 'bi-check2-square'],
            'attendance.bulk'       => ['Массовая отметка', 'info', 'bi-people-fill'],

            'works.update'          => ['Изменение работ', 'primary', 'bi-journal-check'],

            'session.create'        => ['Создание ведомости', 'success', 'bi-file-earmark-plus'],
            'session.delete'        => ['Удаление ведомости', 'danger', 'bi-trash'],
            'session.close'         => ['Закрытие ведомости', 'success', 'bi-lock-fill'],
            'session.reopen'        => ['Открытие ведомости', 'warning', 'bi-unlock-fill'],
            'session.grade'         => ['Выставление оценки', 'primary', 'bi-award'],
            'session.bulk'          => ['Массовое выставление оценок', 'info', 'bi-people-fill'],

            'profile.update'        => ['Изменение профиля', 'warning', 'bi-person-gear'],
            'password.change'       => ['Смена пароля', 'danger', 'bi-key'],

            'subgroup.update'       => ['Изменение подгруппы', 'info', 'bi-diagram-3'],
            'subgroups.bulk'        => ['Массовое распределение подгрупп', 'info', 'bi-diagram-3'],
            'subgroups.auto'        => ['Автораспределение подгрупп', 'info', 'bi-shuffle'],

            'schedule.add'          => ['Добавление занятия', 'success', 'bi-plus-circle'],
            'schedule.delete'       => ['Удаление занятия', 'danger', 'bi-trash'],
            'schedule.sync'         => ['Синхронизация расписания', 'info', 'bi-arrow-repeat'],

            'students.import'       => ['Импорт студентов', 'success', 'bi-file-earmark-excel'],
        ];
    }
}