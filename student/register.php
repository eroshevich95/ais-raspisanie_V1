<?php
/**
 * Регистрация студента: два шага.
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student_register.php';

// Уже вошёл?
if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['user_type'] ?? '') === 'student') {
        header('Location: index.php');
    } else {
        header('Location: /dashboard.php');
    }
    exit;
}

$error = '';
$ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$groups = $pdo->query("SELECT id, name FROM groups_table ORDER BY name")->fetchAll();

// ----- ШАГ 1: Поиск -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'search') {
    if (isRegisterRateLimited($pdo, $ip)) {
        $error = 'Слишком много попыток. Попробуйте через 30 минут.';
    } else {
        $lastName   = trim($_POST['last_name']   ?? '');
        $firstName  = trim($_POST['first_name']  ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $groupId    = (int)($_POST['group_id']   ?? 0);
        $zachetka   = trim($_POST['zachetka']    ?? '');

        // Собираем ФИО: фамилия + имя + (отчество, если есть)
        $fullNameParts = array_filter([$lastName, $firstName, $middleName], fn($p) => $p !== '');
        $fullName = implode(' ', $fullNameParts);

        if ($lastName === '' || $firstName === '' || $groupId <= 0) {
            $error = 'Заполните фамилию, имя и выберите группу';
        } else {
            $student = findStudentForClaim($pdo, $fullName, $groupId, $zachetka);

            if (!$student) {
                registerFailedAttempt($pdo, $ip);
                $error = 'Студент не найден. Проверьте ФИО, группу и номер зачётки.';
            } elseif (!empty($student['has_account'])) {
                registerFailedAttempt($pdo, $ip);
                $error = 'Этот профиль уже привязан к аккаунту. Если забыли пароль — восстановите его.';
            } else {
                $_SESSION['student_reg_pending'] = [
                    'student_id' => (int)$student['id'],
                    'full_name'  => $student['full_name'],
                    'group_id'   => (int)$student['group_id'],
                    'group_name' => $student['group_name'],
                    'zachetka'   => $student['zachetka'],
                ];
                $_SESSION['student_reg_step'] = 'register';
                header('Location: register.php');
                exit;
            }
        }
    }
}

// ----- ШАГ 2: Создание -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $pending = $_SESSION['student_reg_pending'] ?? null;
    if (!$pending) {
        header('Location: register.php');
        exit;
    }

    $login    = trim($_POST['login'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } else {
        try {
            $newId = createStudentAccount($pdo, $pending['student_id'], $login, $email, $password);

            $_SESSION['user_id']    = $newId;
            $_SESSION['user_type']  = 'student';
            $_SESSION['user_name']  = $pending['full_name'];
            $_SESSION['user_login'] = $login;
            $_SESSION['role']       = 'student';
            $_SESSION['student_id'] = $pending['student_id'];

            unset($_SESSION['student_reg_pending'], $_SESSION['student_reg_step']);
            clearFailedAttempts($pdo, $ip);

            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

// Сброс
if (isset($_GET['reset'])) {
    unset($_SESSION['student_reg_pending'], $_SESSION['student_reg_step']);
    header('Location: register.php');
    exit;
}

$step    = $_SESSION['student_reg_step'] ?? 'search';
$pending = $_SESSION['student_reg_pending'] ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация студента — АИС-расписание.РФ</title>
    <link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --ap-bg: #f5f5f7;
    --ap-dark: #1d1d1f;
    --ap-gray: #86868b;
    --ap-border: #e5e5ea;
    --ap-accent: #0071e3;
    --ap-accent-2: #5e5ce6;
    --ap-green: #30d158;
    --ap-red: #ff453a;
}
html, body {
    margin: 0; padding: 0; min-height: 100vh;
    background: linear-gradient(180deg, #fff 0%, var(--ap-bg) 100%);
    color: var(--ap-dark);
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, sans-serif;
}
.reg-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    position: relative;
    overflow: hidden;
}
.reg-page::before {
    content: '';
    position: absolute;
    inset: -50%;
    background:
        radial-gradient(circle at 20% 30%, rgba(0,113,227,0.08) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(191,90,242,0.08) 0%, transparent 40%);
    animation: auroraMove 20s ease-in-out infinite;
    pointer-events: none;
    z-index: 0;
}
@keyframes auroraMove {
    0%,100% { transform: translate(0,0) scale(1); }
    33%     { transform: translate(5%,-5%) scale(1.1); }
    66%     { transform: translate(-5%,5%) scale(0.95); }
}
.reg-container {
    width: 100%;
    max-width: 520px;
    position: relative;
    z-index: 1;
    animation: fadeUpIn 0.7s cubic-bezier(0.16,1,0.3,1);
}
@keyframes fadeUpIn {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
}
.reg-logo {
    width: 80px; height: 80px;
    margin: 0 auto 24px;
    animation: logoFloat 5s ease-in-out infinite;
    filter: drop-shadow(0 20px 40px rgba(0,113,227,0.25));
}
.reg-logo img { width: 100%; height: 100%; object-fit: contain; }
@keyframes logoFloat {
    0%,100% { transform: translateY(0) scale(1); }
    50%     { transform: translateY(-10px) scale(1.02); }
}
.reg-title {
    font-size: 1.7rem; font-weight: 700;
    letter-spacing: -0.02em; text-align: center;
    margin: 0 0 8px;
}
.reg-subtitle {
    text-align: center; color: var(--ap-gray);
    font-size: 0.95rem; margin: 0 0 28px;
}
.reg-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 24px;
}
.step-dot {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: var(--ap-border);
    color: var(--ap-gray);
    font-size: 0.85rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s;
}
.step-dot.active {
    background: linear-gradient(135deg, var(--ap-accent), var(--ap-accent-2));
    color: #fff;
    box-shadow: 0 8px 20px rgba(0,113,227,0.35);
    transform: scale(1.1);
}
.step-dot.done { background: var(--ap-green); color: #fff; }
.step-line { width: 40px; height: 2px; background: var(--ap-border); }
.step-line.done { background: var(--ap-green); }

.reg-card {
    background: #fff;
    border-radius: 24px;
    padding: 32px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.04), 0 30px 80px rgba(0,0,0,0.1);
    border: 1px solid var(--ap-border);
}
.form-label {
    font-size: 0.85rem; font-weight: 600;
    color: var(--ap-dark); margin-bottom: 6px;
}
.form-control, .form-select {
    padding: 14px 18px;
    border-radius: 12px;
    border: 1.5px solid var(--ap-border);
    background: #fafafc;
    font-size: 1rem; font-family: inherit;
    transition: all 0.2s; width: 100%;
}
.form-control:focus, .form-select:focus {
    border-color: var(--ap-accent);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(0,113,227,0.12);
    outline: none;
}
.btn-primary-gradient {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--ap-accent), var(--ap-accent-2));
    color: #fff;
    font-size: 1rem; font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 8px 20px rgba(0,113,227,0.25);
    margin-top: 8px;
}
.btn-primary-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(0,113,227,0.4);
}
.btn-outline {
    padding: 10px 18px;
    border: 1.5px solid var(--ap-border);
    border-radius: 12px;
    background: #fff;
    color: var(--ap-dark);
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}
.btn-outline:hover {
    border-color: var(--ap-accent);
    color: var(--ap-accent);
    background: rgba(0,113,227,0.04);
}
.alert {
    border-radius: 12px;
    border: none;
    padding: 12px 16px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}
.alert-danger { background: rgba(255,69,58,0.1); color: #842029; }

.found-card {
    background: linear-gradient(135deg, rgba(48,209,88,0.08) 0%, rgba(0,113,227,0.08) 100%);
    border: 1px solid rgba(48,209,88,0.3);
    border-radius: 14px;
    padding: 16px 18px;
    margin-bottom: 20px;
}
.found-card-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #198754;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 4px;
}
.found-card-name {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--ap-dark);
    margin-bottom: 2px;
}
.found-card-meta {
    font-size: 0.82rem;
    color: var(--ap-gray);
}
.reg-footer {
    text-align: center;
    margin-top: 22px;
    font-size: 0.88rem;
    color: var(--ap-gray);
}
.reg-footer a {
    color: var(--ap-accent);
    text-decoration: none;
    font-weight: 500;
}
.reg-footer a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="reg-page">
    <div class="reg-container">

        <div class="reg-logo">
            <img src="/assets/img/logo.svg" alt="">
        </div>

        <h1 class="reg-title">Регистрация студента</h1>
        <p class="reg-subtitle">
            <?= $step === 'search'
                ? 'Найдём тебя в списке группы'
                : 'Создай логин и пароль' ?>
        </p>

        <div class="reg-steps">
            <div class="step-dot <?= $step === 'search' ? 'active' : 'done' ?>">
                <?= $step === 'search' ? '1' : '✓' ?>
            </div>
            <div class="step-line <?= $step === 'register' ? 'done' : '' ?>"></div>
            <div class="step-dot <?= $step === 'register' ? 'active' : '' ?>">2</div>
        </div>

        <div class="reg-card">

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($step === 'search'): ?>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="action" value="search">

                    <div class="mb-3">
                        <label class="form-label">
                            Фамилия <span style="color:var(--ap-red);">*</span>
                        </label>
                        <input type="text" name="last_name" class="form-control" required
                               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                               placeholder="Иванов">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Имя <span style="color:var(--ap-red);">*</span>
                        </label>
                        <input type="text" name="first_name" class="form-control" required
                               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                               placeholder="Иван">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Отчество
                            <span style="color:var(--ap-gray); font-weight:400; text-transform:none; letter-spacing:0; font-size:0.8rem;">— если есть</span>
                        </label>
                        <input type="text" name="middle_name" class="form-control"
                               value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>"
                               placeholder="Иванович">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Группа <span style="color:var(--ap-red);">*</span>
                        </label>
                        <select name="group_id" class="form-select" required>
                            <option value="">— Выберите группу —</option>
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= (int)$g['id'] ?>"
                                    <?= (($_POST['group_id'] ?? 0) == $g['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Номер зачётки</label>
                        <input type="text" name="zachetka" class="form-control"
                               value="<?= htmlspecialchars($_POST['zachetka'] ?? '') ?>"
                               placeholder="Например: 24Т013">
                        <div style="font-size:0.78rem;color:var(--ap-gray);margin-top:6px;">
                            <i class="bi bi-info-circle"></i>
                            Указан в студенческом билете
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-gradient">
                        <i class="bi bi-search"></i>
                        Найти меня
                    </button>
                </form>

            <?php else: ?>

                <div class="found-card">
                    <div class="found-card-label">
                        <i class="bi bi-check-circle-fill"></i> Профиль найден
                    </div>
                    <div class="found-card-name">
                        <?= htmlspecialchars($pending['full_name']) ?>
                    </div>
                    <div class="found-card-meta">
                        <i class="bi bi-people-fill"></i>
                        <?= htmlspecialchars($pending['group_name']) ?>
                        <?php if (!empty($pending['zachetka'])): ?>
                            · <?= htmlspecialchars($pending['zachetka']) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="action" value="register">

                    <div class="mb-3">
                        <label class="form-label">
                            Логин <span style="color:var(--ap-red);">*</span>
                        </label>
                        <input type="text" name="login" class="form-control" required
                               minlength="4" pattern="[a-zA-Z0-9_.\-]+"
                               value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                               placeholder="ivanov">
                        <div style="font-size:0.78rem;color:var(--ap-gray);margin-top:6px;">
                            Латиница, цифры, _ . - (минимум 4 символа)
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Email <span style="color:var(--ap-red);">*</span>
                        </label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               placeholder="ivanov@kuzstu.ru">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Пароль <span style="color:var(--ap-red);">*</span>
                        </label>
                        <input type="password" name="password" class="form-control"
                               required minlength="6" placeholder="Минимум 6 символов">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Повторите пароль <span style="color:var(--ap-red);">*</span>
                        </label>
                        <input type="password" name="password_confirm" class="form-control"
                               required minlength="6" placeholder="Ещё раз">
                    </div>

                    <button type="submit" class="btn-primary-gradient">
                        <i class="bi bi-check-circle-fill"></i>
                        Зарегистрироваться
                    </button>

                    <div style="text-align:center;margin-top:14px;">
                        <a href="?reset=1" class="btn-outline">
                            <i class="bi bi-arrow-left"></i>
                            Это не я — назад
                        </a>
                    </div>
                </form>

            <?php endif; ?>

            <div class="reg-footer">
                Уже есть аккаунт?
                <a href="/login.php">Войти</a>
            </div>

        </div>
    </div>
</div>

</body>
</html>