<?php
/**
 * Проверка режима тех. обслуживания.
 *
 * Как работает:
 *   - Если maintenance_enabled = 0 → ничего не делаем.
 *   - Если включён → всех, кроме админов, редиректим на maintenance.php.
 *   - Админов пускаем, чтобы можно было выключить режим.
 *   - Публичные страницы (login, logout, maintenance, reset_password) — не блокируем.
 */

if (!function_exists('checkMaintenanceMode')) {
    function checkMaintenanceMode(PDO $pdo): void {
        // Не мешаем CLI, cron и AJAX-фреймам
        if (PHP_SAPI === 'cli') return;

        require_once __DIR__ . '/settings.php';

        if (!isMaintenanceEnabled($pdo)) return;

        // Админ всегда имеет доступ
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (($_SESSION['role'] ?? '') === 'admin') return;

        // Белый список страниц
        $current = basename($_SERVER['PHP_SELF'] ?? '');
        $whitelist = [
            'maintenance.php',
            'login.php',
            'logout.php',
            'reset_password.php',
            'forgot_password.php',
        ];
        if (in_array($current, $whitelist, true)) return;

        // Не трогаем AJAX-запросы из админки/бота
        $scriptDir = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir === 'admin' || $scriptDir === 'api_max_bot') return;

        // Редирект
        $basePath = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
        header('Location: ' . $basePath . 'maintenance.php', true, 302);
        header('Retry-After: 3600');
        exit;
    }
}