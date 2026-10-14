<?php
session_start();
require_once 'config/database.php';
require_once 'includes/audit.php';

if (!empty($_SESSION['user_id'])) {
    $userId   = (int)$_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'teacher';
    $userName = $_SESSION['user_name'] ?? null;

    try {
        auditLog($pdo, 'logout', [
            'user_id'     => $userId,
            'user_name'   => $userName,
            'description' => $userType === 'student'
                ? 'Выход студента'
                : 'Выход из системы',
        ]);
    } catch (Throwable $e) {
        // тихо
    }
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header('Location: login.php');
exit;