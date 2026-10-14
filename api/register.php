<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/departments.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Только POST']);
    exit;
}

// Читаем JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    // Fallback — если пришло form-data
    $data = $_POST;
}

try {
    $teacherId  = trim($data['teacher_id'] ?? '');
    $fullName   = trim($data['full_name'] ?? '');
    $lastName   = trim($data['last_name'] ?? '');
    $firstName  = trim($data['first_name'] ?? '');
    $middleName = trim($data['middle_name'] ?? '');
    $post       = trim($data['post'] ?? '');

    $instituteId    = trim($data['institute_id'] ?? '');
    $instituteName  = trim($data['institute_name'] ?? '');
    $departmentId   = trim($data['department_id'] ?? '');
    $departmentName = trim($data['department_name'] ?? '');
    $departmentMan  = trim($data['department_manual'] ?? '');

    $login     = trim($data['login'] ?? '');
    $email     = trim($data['email'] ?? '');
    $password  = $data['password'] ?? '';
    $passwordC = $data['password_confirm'] ?? '';

    /* ---- Валидация ---- */
    if ($fullName === '') {
        echo json_encode(['success' => false, 'error' => 'Не выбран преподаватель']);
        exit;
    }
    if ($login === '' || $password === '') {
        echo json_encode(['success' => false, 'error' => 'Заполните логин и пароль']);
        exit;
    }
    if (!preg_match('/^[a-zA-Z0-9_.\-]{4,}$/', $login)) {
        echo json_encode(['success' => false, 'error' => 'Некорректный логин (латиница, цифры, _ . -, мин. 4 символа)']);
        exit;
    }
    if (mb_strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Пароль минимум 6 символов']);
        exit;
    }
    if ($password !== $passwordC) {
        echo json_encode(['success' => false, 'error' => 'Пароли не совпадают']);
        exit;
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Введите корректный email']);
        exit;
    }

    /* ---- Кафедра: ручной режим или из справочника ---- */
    if ($departmentMan !== '') {
        // Ручной ввод
        $instituteId    = '';
        $instituteName  = '';
        $departmentId   = '';
        $departmentName = $departmentMan;
    } else {
        if ($instituteId === '' || $departmentId === '') {
            echo json_encode(['success' => false, 'error' => 'Выберите институт и кафедру']);
            exit;
        }

        // Проверяем по справочнику
        $departmentsData = getKuzstuDepartments();
        $foundInst = $departmentsData['institutes'][$instituteId] ?? '';
        if ($foundInst === '') {
            echo json_encode(['success' => false, 'error' => 'Институт не найден']);
            exit;
        }
        $instituteName = $foundInst;

        $foundDept = null;
        foreach (($departmentsData['departments'][$instituteId] ?? []) as $d) {
            if ($d['id'] === $departmentId) { $foundDept = $d; break; }
        }
        if (!$foundDept) {
            echo json_encode(['success' => false, 'error' => 'Кафедра не найдена']);
            exit;
        }
        $departmentName = $foundDept['name'];
    }

    /* ---- Проверка уникальности ---- */
    $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
    $stmt->execute([$login]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Логин уже занят']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Email уже используется']);
        exit;
    }

    /* ---- Сохранение ---- */
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users
            (login, password_hash, full_name, email,
             department, institute_id, institute_name,
             department_id, department_name,
             api_teacher_id, post, role, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'teacher', 1, NOW())
    ");
    $stmt->execute([
        $login,
        $hash,
        $fullName,
        $email,
        $departmentName ?: null,
        $instituteId    ?: null,
        $instituteName  ?: null,
        $departmentId   ?: null,
        $departmentName ?: null,
        $teacherId      ?: null,
        $post           ?: null,
    ]);

    echo json_encode([
        'success'  => true,
        'redirect' => 'dashboard.php',
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Ошибка сервера: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}