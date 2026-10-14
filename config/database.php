<?php
// config/database.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Часовой пояс КузГТУ (Кемерово, UTC+7)
date_default_timezone_set('Asia/Krasnoyarsk');

define('DB_HOST', 'localhost');
define('DB_NAME', 'u3586773_kuzstu-attendance');
define('DB_USER', 'u3586773_kuzstu-attendance_root');
define('DB_PASS', 'Kirill100395');
define('DB_CHARSET', 'utf8mb4');

define('KUZSTU_API_URL', 'https://portal.kuzstu.ru/api');

// ============================================================
// Расписание звонков
// ============================================================
define('LESSON_TIMES', [
    1 => ['start' => '09:00', 'end' => '10:35'],
    2 => ['start' => '10:50', 'end' => '12:25'],
    3 => ['start' => '13:20', 'end' => '14:55'],
    4 => ['start' => '15:10', 'end' => '16:45'],
    5 => ['start' => '17:00', 'end' => '18:35'],
    6 => ['start' => '18:50', 'end' => '20:20'],
]);

function getLessonStart(int $number): ?string {
    return LESSON_TIMES[$number]['start'] ?? null;
}
function getLessonEnd(int $number): ?string {
    return LESSON_TIMES[$number]['end'] ?? null;
}
function getLessonNumberByStart(string $time): ?int {
    $time = substr($time, 0, 5);
    foreach (LESSON_TIMES as $num => $t) {
        if ($t['start'] === $time) return $num;
    }
    return null;
}
function formatLessonTime(?string $start, ?int $number = null): string {
    if (!$start) return '—';
    $start = substr($start, 0, 5);
    if ($number && isset(LESSON_TIMES[$number])) {
        return $start . '–' . LESSON_TIMES[$number]['end'];
    }
    return $start;
}
function getCurrentLessonNumber(): ?int {
    $now = date('H:i');
    foreach (LESSON_TIMES as $num => $t) {
        if ($now >= $t['start'] && $now <= $t['end']) return $num;
    }
    return null;
}
function getNextLessonNumber(): ?int {
    $now = date('H:i');
    foreach (LESSON_TIMES as $num => $t) {
        if ($now < $t['start']) return $num;
    }
    return null;
}


// ============================================================
// Flash-сообщения (через сессию)
// ============================================================
function startSessionIfNeeded(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function setFlash(string $type, string $message): void {
    startSessionIfNeeded();
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(string $type): ?string {
    startSessionIfNeeded();
    $msg = $_SESSION['flash_' . $type] ?? null;
    unset($_SESSION['flash_' . $type]);
    return $msg;
}

function redirectWith(string $url, string $type, string $message): void {
    setFlash($type, $message);
    header('Location: ' . $url);
    exit;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ============================================================
// Подключение к БД
// ============================================================
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}
function ruDayShort($date): string {
    $days = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
    return $days[(int)date('w', strtotime($date))];
}

function ruDayFull($date): string {
    $days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    return $days[(int)date('w', strtotime($date))];
}