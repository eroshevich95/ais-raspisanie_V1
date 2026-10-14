<?php
/**
 * Хелперы регистрации студентов (таблица student_users).
 */

if (!function_exists('normalizeStudentName')) {
    function normalizeStudentName(string $raw): string {
        $parts = preg_split('/\s+/u', trim($raw));
        $parts = array_filter($parts, fn($p) => $p !== '');
        return implode(' ', $parts);
    }
}

if (!function_exists('findStudentForClaim')) {
    function findStudentForClaim(PDO $pdo, string $fullName, int $groupId, string $zachetka): ?array {
        $stmt = $pdo->prepare("
            SELECT s.id, s.full_name, s.zachetka, s.group_id,
                   g.name AS group_name,
                   (SELECT COUNT(*) FROM student_users su WHERE su.student_id = s.id) AS has_account
            FROM students s
            JOIN groups_table g ON s.group_id = g.id
            WHERE s.group_id = ?
              AND s.full_name = ?
              AND (s.zachetka = ? OR (? = '' AND (s.zachetka IS NULL OR s.zachetka = '')))
            LIMIT 1
        ");
        $stmt->execute([$groupId, $fullName, $zachetka, $zachetka]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

if (!function_exists('isRegisterRateLimited')) {
    function isRegisterRateLimited(PDO $pdo, string $ip, int $max = 10, int $windowMin = 30): bool {
        $stmt = $pdo->prepare("SELECT attempts, last_attempt FROM student_reg_attempts WHERE ip = ?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch();
        if (!$row) return false;
        if (time() - strtotime($row['last_attempt']) > $windowMin * 60) {
            $pdo->prepare("DELETE FROM student_reg_attempts WHERE ip = ?")->execute([$ip]);
            return false;
        }
        return (int)$row['attempts'] >= $max;
    }
}

if (!function_exists('registerFailedAttempt')) {
    function registerFailedAttempt(PDO $pdo, string $ip): void {
        $pdo->prepare("
            INSERT INTO student_reg_attempts (ip, attempts)
            VALUES (?, 1)
            ON DUPLICATE KEY UPDATE
                attempts = IF(TIMESTAMPDIFF(MINUTE, last_attempt, NOW()) > 30, 1, attempts + 1),
                last_attempt = NOW()
        ")->execute([$ip]);
    }
}

if (!function_exists('clearFailedAttempts')) {
    function clearFailedAttempts(PDO $pdo, string $ip): void {
        $pdo->prepare("DELETE FROM student_reg_attempts WHERE ip = ?")->execute([$ip]);
    }
}

if (!function_exists('createStudentAccount')) {
    function createStudentAccount(
        PDO $pdo,
        int $studentId,
        string $login,
        string $email,
        string $password
    ): int {
        if (mb_strlen($login) < 4) throw new Exception('Логин минимум 4 символа');
        if (!preg_match('/^[a-zA-Z0-9_.\-]+$/', $login)) {
            throw new Exception('Логин: только латиница, цифры, _ . -');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Некорректный email');
        }
        if (mb_strlen($password) < 6) {
            throw new Exception('Пароль минимум 6 символов');
        }

        $stmt = $pdo->prepare("SELECT id FROM student_users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) throw new Exception('Логин уже занят');

        $stmt = $pdo->prepare("SELECT id FROM student_users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) throw new Exception('Email уже используется');

        $stmt = $pdo->prepare("SELECT id FROM student_users WHERE student_id = ?");
        $stmt->execute([$studentId]);
        if ($stmt->fetch()) throw new Exception('У этого студента уже есть аккаунт');

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo->prepare("
            INSERT INTO student_users (student_id, login, email, password_hash)
            VALUES (?, ?, ?, ?)
        ")->execute([$studentId, $login, $email, $hash]);

        return (int)$pdo->lastInsertId();
    }
}