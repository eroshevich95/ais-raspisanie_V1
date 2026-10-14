<?php
/**
 * Админ-панель — единая точка входа.
 *
 * Вкладки лежат в admin/tabs/*.php
 * Стили — admin/assets/admin.css
 * Скрипты — admin/assets/admin.js
 *
 * ВАЖНО: admin.js подключается ДО табов,
 * чтобы Adm.registerTab был доступен в их <script>.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/audit.php';

// Студентов не пускаем
if (($_SESSION['user_type'] ?? 'teacher') === 'student') {
    header('Location: /student/index.php');
    exit;
}

// Доступ только для админа
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$adminId   = (int)$_SESSION['user_id'];
$adminName = $_SESSION['user_name'] ?? 'Администратор';

// CSRF
if (empty($_SESSION['csrf_admin'])) {
    $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_admin'];

// Счётчики для бейджей
$resetPendingCount    = 0;
$messagesTotalCount   = 0;
$achievementsCount    = 0;

try {
    $resetPendingCount = (int)$pdo->query("
        SELECT COUNT(*) FROM password_reset_requests WHERE status = 'pending'
    ")->fetchColumn();
} catch (Throwable $e) {}

try {
    $tables = $pdo->query("SHOW TABLES LIKE 'site_messages'")->fetchAll();
    if (!empty($tables)) {
        $messagesTotalCount = (int)$pdo->query("
            SELECT COUNT(*) FROM site_messages WHERE is_active = 1
        ")->fetchColumn();
    }
} catch (Throwable $e) {}

try {
    $tables = $pdo->query("SHOW TABLES LIKE 'achievements'")->fetchAll();
    if (!empty($tables)) {
        $achievementsCount = (int)$pdo->query("
            SELECT COUNT(*) FROM achievements WHERE is_active = 1
        ")->fetchColumn();
    }
} catch (Throwable $e) {}

$currentPage = 'admin';
$pageTitle   = 'Админ-панель';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ============================================================
     СТИЛИ И JS АДМИНКИ
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link href="assets/admin.css?v=2" rel="stylesheet">

<script>
    window.__ADM_CSRF__ = <?= json_encode($csrf) ?>;
    window.__ADM_API__  = 'api.php';
</script>
<script src="assets/admin.js?v=2"></script>

<!-- ============================================================
     ШАПКА
     ============================================================ -->
<div class="adm-header">
    <div class="adm-header-left">
        <h1>
            <i class="bi bi-shield-lock-fill" style="color: var(--adm-accent);"></i>
            Админ-панель
            <span class="adm-badge">
                <i class="bi bi-shield-fill-check"></i>
                Полный доступ
            </span>
        </h1>
        <p>
            <i class="bi bi-person-circle"></i>
            <?= htmlspecialchars($adminName) ?>
            <span style="opacity:0.5;">·</span>
            <i class="bi bi-clock"></i>
            <?= date('d.m.Y H:i') ?>
        </p>
    </div>
    <div class="adm-header-actions">
        <a href="../dashboard.php" class="adm-btn adm-btn--secondary adm-btn--sm">
            <i class="bi bi-arrow-left"></i> Дашборд
        </a>
    </div>
</div>

<!-- ============================================================
     ТАБЫ
     ============================================================ -->
<div class="adm-tabs" id="admTabs">
    <button class="adm-tab" data-tab="overview" title="Обзор">
        <i class="bi bi-speedometer2"></i>
        <span>Обзор</span>
    </button>
    <button class="adm-tab" data-tab="users" title="Пользователи">
        <i class="bi bi-people-fill"></i>
        <span>Пользователи</span>
        <span class="adm-tab-count" id="tabCountUsers">—</span>
    </button>
    <button class="adm-tab" data-tab="groups" title="Группы">
        <i class="bi bi-people"></i>
        <span>Группы</span>
        <span class="adm-tab-count" id="tabCountGroups">—</span>
    </button>
    <button class="adm-tab" data-tab="students" title="Студенты">
        <i class="bi bi-mortarboard-fill"></i>
        <span>Студенты</span>
        <span class="adm-tab-count" id="tabCountStudents">—</span>
    </button>
    <button class="adm-tab" data-tab="lessons" title="Занятия">
        <i class="bi bi-calendar-week"></i>
        <span>Занятия</span>
        <span class="adm-tab-count" id="tabCountLessons">—</span>
    </button>
    <button class="adm-tab" data-tab="messages" title="Сообщения">
        <i class="bi bi-chat-square-text-fill"></i>
        <span>Сообщения</span>
        <span class="adm-tab-count" id="tabCountMessages"><?= $messagesTotalCount ?: '—' ?></span>
    </button>
    <button class="adm-tab" data-tab="achievements" title="Достижения">
        <i class="bi bi-trophy-fill"></i>
        <span>Достижения</span>
        <span class="adm-tab-count" id="tabCountAchievements"><?= $achievementsCount ?: '—' ?></span>
    </button>
    <button class="adm-tab" data-tab="resets" title="Сброс пароля">
        <i class="bi bi-key-fill"></i>
        <span>Сброс пароля</span>
        <span class="adm-tab-count" id="tabCountResets"><?= $resetPendingCount ?: '—' ?></span>
    </button>
    <button class="adm-tab" data-tab="audit" title="Журнал">
        <i class="bi bi-clock-history"></i>
        <span>Журнал</span>
    </button>
    <button class="adm-tab" data-tab="settings" title="Настройки">
        <i class="bi bi-gear-fill"></i>
        <span>Настройки</span>
    </button>
</div>

<!-- ============================================================
     ПАНЕЛИ ВКЛАДОК
     ============================================================ -->
<div class="adm-panel" data-panel="overview">
    <?php include __DIR__ . '/tabs/overview.php'; ?>
</div>

<div class="adm-panel" data-panel="users">
    <?php include __DIR__ . '/tabs/users.php'; ?>
</div>

<div class="adm-panel" data-panel="groups">
    <?php include __DIR__ . '/tabs/groups.php'; ?>
</div>

<div class="adm-panel" data-panel="students">
    <?php include __DIR__ . '/tabs/students.php'; ?>
</div>

<div class="adm-panel" data-panel="lessons">
    <?php include __DIR__ . '/tabs/lessons.php'; ?>
</div>

<div class="adm-panel" data-panel="messages">
    <?php include __DIR__ . '/tabs/messages.php'; ?>
</div>

<div class="adm-panel" data-panel="achievements">
    <?php include __DIR__ . '/tabs/achievements.php'; ?>
</div>

<div class="adm-panel" data-panel="resets">
    <?php include __DIR__ . '/tabs/resets.php'; ?>
</div>

<div class="adm-panel" data-panel="audit">
    <?php include __DIR__ . '/tabs/audit.php'; ?>
</div>

<div class="adm-panel" data-panel="settings">
    <?php include __DIR__ . '/tabs/settings.php'; ?>
</div>

<!-- ============================================================
     ОБЩИЕ МОДАЛКИ
     ============================================================ -->

<!-- Подтверждение -->
<div class="adm-modal-backdrop" id="confirmModal">
    <div class="adm-modal" style="max-width: 460px;">
        <div class="adm-modal-header">
            <h5>
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span id="confirmTitle">Подтверждение</span>
            </h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('confirmModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="adm-modal-body">
            <p id="confirmText" style="margin: 0; font-size: 0.95rem; line-height: 1.5; white-space: pre-line;"></p>
        </div>
        <div class="adm-modal-footer">
            <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('confirmModal')">
                Отмена
            </button>
            <button type="button" class="adm-btn adm-btn--danger" id="confirmOkBtn">
                <i class="bi bi-check-lg"></i> Подтвердить
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="adm-toast" id="admToast">
    <i class="bi bi-check-circle-fill" style="color: var(--adm-green);"></i>
    <span id="admToastText">Готово</span>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>