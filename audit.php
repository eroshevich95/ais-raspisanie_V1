<?php
$pageTitle = 'Журнал действий';
require_once 'config/database.php';
require_once 'includes/access_control.php';
require_once 'includes/audit.php';

// ============================================================
// AJAX: очистка журнала (только admin)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_audit') {
    header('Content-Type: application/json; charset=utf-8');

    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['ok' => false, 'error' => 'Не авторизован']);
        exit;
    }

    $userRole = $_SESSION['role'] ?? 'teacher';
    if ($userRole !== 'admin') {
        echo json_encode(['ok' => false, 'error' => 'Только администратор может очищать журнал']);
        exit;
    }

    $mode = $_POST['mode'] ?? 'all';
    $from = $_POST['from'] ?? '';
    $to   = $_POST['to']   ?? '';

    try {
        if ($mode === 'all') {
            $stmt = $pdo->prepare("DELETE FROM audit_log");
            $stmt->execute();
            $deleted = $stmt->rowCount();

            // Пишем запись об очистке
            auditLog($pdo, 'audit.cleared', [
                'description' => "Журнал действий полностью очищен (удалено записей: {$deleted})",
            ]);

            echo json_encode(['ok' => true, 'deleted' => $deleted]);
            exit;
        }

        // По периоду
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            echo json_encode(['ok' => false, 'error' => 'Некорректные даты']);
            exit;
        }
        if (strtotime($from) > strtotime($to)) {
            echo json_encode(['ok' => false, 'error' => 'Дата «С» больше даты «По»']);
            exit;
        }

        $stmt = $pdo->prepare("
            DELETE FROM audit_log
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$from, $to]);
        $deleted = $stmt->rowCount();

        auditLog($pdo, 'audit.cleared', [
            'description' => "Журнал действий очищен за период {$from} — {$to} (удалено записей: {$deleted})",
        ]);

        echo json_encode(['ok' => true, 'deleted' => $deleted]);
        exit;

    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ============================================================
// Сессия и права
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'teacher';

$canViewAll = in_array($userRole, ['head_of_department', 'administration', 'admin']);
$isAdmin    = ($userRole === 'admin');

$scope = getAccessScope($pdo, $userId);

// ============================================================
// Фильтры
// ============================================================
$action   = trim($_GET['action'] ?? '');
$entityId = (int)($_GET['entity_id'] ?? 0);
$search   = trim($_GET['search'] ?? '');
$from     = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to       = $_GET['to']   ?? date('Y-m-d');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 50;

// ============================================================
// SQL
// ============================================================
$where  = ["DATE(a.created_at) BETWEEN ? AND ?"];
$params = [$from, $to];

if (!$canViewAll) {
    $where[] = "a.user_id = ?";
    $params[] = $userId;
} else {
    $allowedTeacherIds = getAllowedTeacherIds($pdo, $scope);
    if ($allowedTeacherIds !== null) {
        if (empty($allowedTeacherIds)) {
            $where[] = "1 = 0";
        } else {
            $in = implode(',', array_fill(0, count($allowedTeacherIds), '?'));
            $where[] = "a.user_id IN ($in)";
            foreach ($allowedTeacherIds as $t) $params[] = $t;
        }
    }
}

if ($action !== '') {
    $where[] = "a.action = ?";
    $params[] = $action;
}
if ($entityId > 0) {
    $where[] = "a.entity_id = ?";
    $params[] = $entityId;
}
if ($search !== '') {
    $where[] = "(a.user_name LIKE ? OR a.description LIKE ? OR a.old_value LIKE ? OR a.new_value LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

$whereSql = implode(' AND ', $where);

// Общее число
$stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log a WHERE $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Выборка
$stmt = $pdo->prepare("
    SELECT a.*
    FROM audit_log a
    WHERE $whereSql
    ORDER BY a.id DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Справочник действий
$actions = $pdo->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

$actionLabels = auditActionLabels();
$scopeInfo    = scopeLabel($scope);

// Проверка: журнал пустой кроме записи об очистке
$onlyClear = false;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM audit_log WHERE action != 'audit.cleared'");
    if ((int)$stmt->fetchColumn() === 0) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM audit_log WHERE action = 'audit.cleared'");
        $onlyClear = ((int)$stmt->fetchColumn() > 0);
    }
} catch (Throwable $e) {}

require_once 'includes/header.php';
?>

<style>
.aud-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.aud-header h2 {
    font-size: 1.7rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 6px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.aud-header p { color: #86868b; margin: 0; font-size: 0.95rem; }
.aud-scope-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    background: rgba(0,113,227,0.1);
    color: #0071e3;
}

.aud-filter {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    padding: 20px 24px;
    margin-bottom: 20px;
}
.aud-filter-row {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1fr auto;
    gap: 12px;
    align-items: end;
}
@media (max-width: 991px) { .aud-filter-row { grid-template-columns: 1fr 1fr; } }
@media (max-width: 575px) { .aud-filter-row { grid-template-columns: 1fr; } }

.aud-filter-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #1d1d1f;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.aud-filter-label i { color: #0071e3; margin-right: 4px; }
.aud-input, .aud-select {
    width: 100%;
    padding: 11px 14px;
    border-radius: 10px;
    border: 1.5px solid #e5e5ea;
    background: #fff;
    font-size: 0.9rem;
    font-family: inherit;
    outline: none;
    transition: all 0.2s;
}
.aud-input:focus, .aud-select:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 4px rgba(0,113,227,0.12);
}
.aud-submit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 24px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    border: none;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.25s;
    box-shadow: 0 6px 16px rgba(0,113,227,0.25);
    white-space: nowrap;
}
.aud-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,113,227,0.4);
}

.aud-stats {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    font-size: 0.85rem;
    color: #86868b;
}
.aud-stats strong { color: #1d1d1f; }

.aud-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eaeaea;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    overflow: hidden;
}
.aud-table-wrap { overflow-x: auto; }
.aud-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}
.aud-table thead th {
    background: #fafafc;
    padding: 12px 14px;
    text-align: left;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #86868b;
    border-bottom: 1px solid #f0f0f2;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 1;
}
.aud-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f5f5f7;
    vertical-align: top;
}
.aud-table tbody tr:last-child td { border-bottom: none; }
.aud-table tbody tr:hover { background: #fafafc; }

.aud-time {
    font-size: 0.75rem;
    color: #86868b;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    line-height: 1.3;
}
.aud-time .date { font-weight: 700; color: #1d1d1f; }

.aud-user {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #1d1d1f;
    font-size: 0.85rem;
}
.aud-user-avatar {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    flex-shrink: 0;
}
.aud-user-ip {
    font-size: 0.7rem;
    color: #86868b;
    font-family: "SF Mono", Monaco, monospace;
}

.aud-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}
.aud-action--success   { background: rgba(48,209,88,0.15); color: #198754; }
.aud-action--danger    { background: rgba(255,69,58,0.15); color: #842029; }
.aud-action--warning   { background: rgba(255,159,10,0.15); color: #b25c00; }
.aud-action--primary   { background: rgba(0,113,227,0.12); color: #0071e3; }
.aud-action--info      { background: rgba(13,202,240,0.15); color: #055160; }
.aud-action--secondary { background: rgba(134,134,139,0.15); color: #495057; }

.aud-desc {
    color: #1d1d1f;
    font-weight: 500;
    line-height: 1.4;
    font-size: 0.85rem;
}
.aud-entity {
    font-size: 0.72rem;
    color: #86868b;
    margin-top: 3px;
}
.aud-entity code {
    background: rgba(0,113,227,0.08);
    color: #0071e3;
    padding: 1px 6px;
    border-radius: 4px;
    font-family: "SF Mono", Monaco, monospace;
}

.aud-change {
    font-size: 0.75rem;
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 4px;
}
.aud-change-old {
    background: rgba(255,69,58,0.1);
    color: #842029;
    padding: 2px 8px;
    border-radius: 6px;
    text-decoration: line-through;
    font-family: "SF Mono", Monaco, monospace;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.aud-change-new {
    background: rgba(48,209,88,0.1);
    color: #198754;
    padding: 2px 8px;
    border-radius: 6px;
    font-family: "SF Mono", Monaco, monospace;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.aud-change-arrow { color: #86868b; font-size: 0.75rem; }

.aud-pagination {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 20px;
    flex-wrap: wrap;
}
.aud-page-btn {
    min-width: 38px;
    height: 38px;
    padding: 0 12px;
    border-radius: 10px;
    border: 1.5px solid #e5e5ea;
    background: #fff;
    color: #1d1d1f;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.aud-page-btn:hover {
    border-color: #0071e3;
    color: #0071e3;
}
.aud-page-btn--active {
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    border-color: transparent;
}

.aud-empty {
    padding: 60px 24px;
    text-align: center;
    color: #86868b;
}
.aud-empty i {
    font-size: 3rem;
    opacity: 0.3;
    display: block;
    margin-bottom: 12px;
}

/* Модалка очистки */
.aud-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(10px);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    animation: audFadeIn 0.3s;
}
.aud-modal-backdrop.show { display: flex; }
@keyframes audFadeIn { from { opacity: 0; } to { opacity: 1; } }

.aud-modal {
    background: #fff;
    border-radius: 24px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 40px 100px rgba(0,0,0,0.4);
    animation: audModalIn 0.4s cubic-bezier(0.16,1,0.3,1);
    overflow: hidden;
}
@keyframes audModalIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.aud-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #fef2f2 0%, #fff 100%);
}
.aud-modal-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
.aud-modal-header h5 i { color: #ff453a; }
.aud-modal-close {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #f5f5f7;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.aud-modal-close:hover {
    background: #ff453a;
    color: #fff;
    transform: rotate(90deg);
}
.aud-modal-body { padding: 24px; }
.aud-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #f0f0f2;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #fafafc;
}

.aud-clear-mode {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 20px;
}
.aud-clear-tab {
    padding: 14px;
    border-radius: 12px;
    border: 1.5px solid #e5e5ea;
    background: #fff;
    color: #495057;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}
.aud-clear-tab:hover {
    border-color: #0071e3;
    color: #0071e3;
}
.aud-clear-tab--active {
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 6px 16px rgba(0,113,227,0.3);
}
.aud-clear-tab--active:hover {
    color: #fff;
    transform: translateY(-1px);
}

.aud-form-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #1d1d1f;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: block;
}

.aud-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    border: none;
}
.aud-btn--secondary {
    background: #f5f5f7;
    color: #495057;
    border: 1.5px solid #e5e5ea;
}
.aud-btn--secondary:hover {
    background: #fff;
    border-color: #0071e3;
    color: #0071e3;
}
.aud-btn--danger {
    background: linear-gradient(135deg, #ff453a 0%, #842029 100%);
    color: #fff;
    box-shadow: 0 6px 16px rgba(255,69,58,0.3);
}
.aud-btn--danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(255,69,58,0.4);
}

/* Тёмная тема */
[data-theme="dark"] .aud-filter,
[data-theme="dark"] .aud-card,
[data-theme="dark"] .aud-modal {
    background: #1c1c1e;
    border-color: #2c2c2e;
}
[data-theme="dark"] .aud-header h2,
[data-theme="dark"] .aud-filter-label,
[data-theme="dark"] .aud-desc,
[data-theme="dark"] .aud-user,
[data-theme="dark"] .aud-time .date,
[data-theme="dark"] .aud-form-label {
    color: #f5f5f7;
}
[data-theme="dark"] .aud-input,
[data-theme="dark"] .aud-select {
    background: #2c2c2e;
    border-color: #3c3c3e;
    color: #f5f5f7;
}
[data-theme="dark"] .aud-table thead th {
    background: #2c2c2e;
    border-color: #3c3c3e;
    color: #a1a1a6;
}
[data-theme="dark"] .aud-table tbody td { border-color: #2c2c2e; }
[data-theme="dark"] .aud-table tbody tr:hover { background: #2c2c2e; }
[data-theme="dark"] .aud-page-btn {
    background: #2c2c2e;
    border-color: #3c3c3e;
    color: #f5f5f7;
}
[data-theme="dark"] .aud-page-btn:hover {
    border-color: #0a84ff;
    color: #6ea8ff;
}
[data-theme="dark"] .aud-modal-header {
    background: linear-gradient(135deg, rgba(255,69,58,0.15) 0%, #1c1c1e 100%);
    border-color: #2c2c2e;
}
[data-theme="dark"] .aud-modal-close {
    background: #2c2c2e;
    color: #f5f5f7;
}
[data-theme="dark"] .aud-modal-footer {
    background: #141416;
    border-color: #2c2c2e;
}
[data-theme="dark"] .aud-clear-tab {
    background: #2c2c2e;
    border-color: #3c3c3e;
    color: #a1a1a6;
}
[data-theme="dark"] .aud-clear-tab:hover {
    border-color: #0a84ff;
    color: #6ea8ff;
}
[data-theme="dark"] .aud-btn--secondary {
    background: #2c2c2e;
    color: #f5f5f7;
    border-color: #3c3c3e;
}
</style>

<!-- Шапка -->
<div class="aud-header">
    <div>
        <h2>
            <i class="bi bi-clock-history"></i>
            Журнал действий
            <?php if ($canViewAll): ?>
                <span class="aud-scope-badge">
                    <i class="bi <?= $scopeInfo[2] ?>"></i>
                    <?= htmlspecialchars($scopeInfo[0]) ?>
                </span>
            <?php endif; ?>
        </h2>
        <p>
            <i class="bi bi-info-circle"></i>
            История изменений: посещаемость, работы, оценки, профиль
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($isAdmin): ?>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="openClearModal()">
                <i class="bi bi-trash"></i> Очистить журнал
            </button>
        <?php endif; ?>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Дашборд
        </a>
    </div>
</div>

<?php if ($onlyClear): ?>
    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <div>
            Журнал действий был очищен. Видна только запись о последней очистке.
        </div>
    </div>
<?php endif; ?>

<!-- Фильтры -->
<div class="aud-filter">
    <form method="get">
        <div class="aud-filter-row">
            <div>
                <div class="aud-filter-label">
                    <i class="bi bi-funnel"></i> Действие
                </div>
                <select name="action" class="aud-select">
                    <option value="">— Все действия —</option>
                    <?php foreach ($actions as $a):
                        $label = $actionLabels[$a][0] ?? $a;
                    ?>
                        <option value="<?= htmlspecialchars($a) ?>"
                                <?= $action === $a ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <div class="aud-filter-label">
                    <i class="bi bi-search"></i> Поиск
                </div>
                <input type="text" name="search" class="aud-input"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="ФИО, описание, значения...">
            </div>

            <div>
                <div class="aud-filter-label">
                    <i class="bi bi-calendar-event"></i> С
                </div>
                <input type="date" name="from" class="aud-input" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div>
                <div class="aud-filter-label">
                    <i class="bi bi-calendar-event"></i> По
                </div>
                <input type="date" name="to" class="aud-input" value="<?= htmlspecialchars($to) ?>">
            </div>

            <div>
                <button type="submit" class="aud-submit-btn">
                    <i class="bi bi-search"></i> Показать
                </button>
            </div>
        </div>
    </form>

    <div style="margin-top:12px; padding-top:12px; border-top:1px solid #f0f0f2; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
        <a href="audit.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-x-circle"></i> Сбросить
        </a>
        <div class="aud-stats">
            Найдено: <strong><?= $total ?></strong> записей
            · страница <?= $page ?> из <?= $totalPages ?>
        </div>
    </div>
</div>

<!-- Таблица -->
<?php if (empty($rows)): ?>

    <div class="aud-card">
        <div class="aud-empty">
            <i class="bi bi-clock-history"></i>
            <p>За выбранный период действий не найдено</p>
        </div>
    </div>

<?php else: ?>

    <div class="aud-card">
        <div class="aud-table-wrap">
            <table class="aud-table">
                <thead>
                <tr>
                    <th style="width: 140px;">Когда</th>
                    <th style="width: 220px;">Кто</th>
                    <th style="width: 240px;">Действие</th>
                    <th>Что именно</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $lbl = $actionLabels[$r['action']] ?? [$r['action'], 'secondary', 'bi-info-circle'];
                    $dateObj = new DateTime($r['created_at']);
                ?>
                    <tr>
                        <td>
                            <div class="aud-time">
                                <div class="date"><?= $dateObj->format('d.m.Y') ?></div>
                                <div><?= $dateObj->format('H:i:s') ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="aud-user">
                                <?php
                                $initials = '';
                                $np = explode(' ', $r['user_name'] ?? '');
                                if (!empty($np[0])) $initials .= mb_strtoupper(mb_substr($np[0], 0, 1));
                                if (!empty($np[1])) $initials .= mb_strtoupper(mb_substr($np[1], 0, 1));
                                ?>
                                <div class="aud-user-avatar"><?= htmlspecialchars($initials ?: '?') ?></div>
                                <div>
                                    <div><?= htmlspecialchars($r['user_name'] ?? '—') ?></div>
                                    <?php if (!empty($r['ip'])): ?>
                                        <div class="aud-user-ip"><?= htmlspecialchars($r['ip']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="aud-action aud-action--<?= $lbl[1] ?>">
                                <i class="bi <?= $lbl[2] ?>"></i>
                                <?= htmlspecialchars($lbl[0]) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($r['description'])): ?>
                                <div class="aud-desc"><?= htmlspecialchars($r['description']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($r['entity_type']) && !empty($r['entity_id'])): ?>
                                <div class="aud-entity">
                                    <code><?= htmlspecialchars($r['entity_type']) ?>#<?= (int)$r['entity_id'] ?></code>
                                </div>
                            <?php endif; ?>

                            <?php if ($r['old_value'] !== null || $r['new_value'] !== null): ?>
                                <div class="aud-change">
                                    <?php if ($r['old_value'] !== null && $r['old_value'] !== ''): ?>
                                        <span class="aud-change-old" title="<?= htmlspecialchars($r['old_value']) ?>">
                                            <?= htmlspecialchars(mb_substr($r['old_value'], 0, 60)) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($r['old_value'] !== null && $r['new_value'] !== null): ?>
                                        <i class="bi bi-arrow-right aud-change-arrow"></i>
                                    <?php endif; ?>
                                    <?php if ($r['new_value'] !== null && $r['new_value'] !== ''): ?>
                                        <span class="aud-change-new" title="<?= htmlspecialchars($r['new_value']) ?>">
                                            <?= htmlspecialchars(mb_substr($r['new_value'], 0, 60)) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Пагинация -->
    <?php if ($totalPages > 1): ?>
        <div class="aud-pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                   class="aud-page-btn">
                    <i class="bi bi-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 3);
            $end = min($totalPages, $page + 3);
            if ($start > 1) echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="aud-page-btn">1</a>';
            if ($start > 2) echo '<span style="padding: 0 6px; color:#86868b;">…</span>';
            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                   class="aud-page-btn <?= $i === $page ? 'aud-page-btn--active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor;
            if ($end < $totalPages - 1) echo '<span style="padding: 0 6px; color:#86868b;">…</span>';
            if ($end < $totalPages) echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '" class="aud-page-btn">' . $totalPages . '</a>';
            ?>

            <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                   class="aud-page-btn">
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<!-- МОДАЛКА: очистка журнала -->
<?php if ($isAdmin): ?>
<div class="aud-modal-backdrop" id="clearModal">
    <div class="aud-modal">
        <div class="aud-modal-header">
            <h5><i class="bi bi-trash"></i> Очистить журнал действий</h5>
            <button class="aud-modal-close" onclick="closeClearModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="aud-modal-body">

            <div style="background:rgba(255,69,58,0.08); border:1px solid rgba(255,69,58,0.2); border-radius:12px; padding:12px 16px; margin-bottom:20px; font-size:0.85rem; color:#842029; display:flex; gap:10px; align-items:flex-start;">
                <i class="bi bi-exclamation-triangle-fill" style="font-size:1.1rem; flex-shrink:0; margin-top:2px;"></i>
                <div>
                    <b>Внимание!</b> Удаление записей журнала <b>необратимо</b>.
                    Восстановить их можно только из резервной копии БД.
                    После очистки в журнале останется только запись о самой очистке.
                </div>
            </div>

            <!-- Режим -->
            <div class="aud-clear-mode">
                <button type="button"
                        class="aud-clear-tab aud-clear-tab--active"
                        data-mode="all"
                        onclick="setClearMode('all')">
                    <i class="bi bi-trash3"></i> Очистить всё
                </button>
                <button type="button"
                        class="aud-clear-tab"
                        data-mode="period"
                        onclick="setClearMode('period')">
                    <i class="bi bi-calendar-range"></i> За период
                </button>
            </div>

            <!-- Период -->
            <div id="clearPeriodBlock" style="display:none; margin-bottom:20px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label class="aud-form-label">С даты</label>
                        <input type="date" id="clearFrom" class="aud-input"
                               value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                    </div>
                    <div>
                        <label class="aud-form-label">По дату</label>
                        <input type="date" id="clearTo" class="aud-input"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div style="font-size:0.75rem; color:#86868b; margin-top:6px;">
                    <i class="bi bi-info-circle"></i>
                    Удалятся записи с датой создания в указанном диапазоне (включительно)
                </div>
            </div>

            <!-- Итоговая подпись -->
            <div style="padding:12px 16px; background:#fafafc; border-radius:12px; font-size:0.85rem; color:#1d1d1f;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span>Будет удалено:</span>
                    <b id="clearCount">все записи</b>
                </div>
            </div>

        </div>
        <div class="aud-modal-footer">
            <button type="button" class="aud-btn aud-btn--secondary" onclick="closeClearModal()">
                Отмена
            </button>
            <button type="button" class="aud-btn aud-btn--danger" onclick="confirmClear()">
                <i class="bi bi-trash"></i> Удалить
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Toast -->
<div id="audToast" style="position:fixed; bottom:30px; left:50%; transform:translateX(-50%) translateY(100px); background:#1d1d1f; color:#fff; padding:14px 24px; border-radius:14px; font-size:0.92rem; font-weight:500; box-shadow:0 20px 60px rgba(0,0,0,0.35); opacity:0; transition:all 0.4s cubic-bezier(0.16,1,0.3,1); z-index:9999; display:flex; align-items:center; gap:10px; pointer-events:none;">
    <i class="bi bi-check-circle-fill" style="color:#30d158;"></i>
    <span id="audToastText">Готово</span>
</div>

<script>
/* ============================================================
   МОДАЛКА ОЧИСТКИ
   ============================================================ */
let clearMode = 'all';

function openClearModal() {
    document.getElementById('clearModal').classList.add('show');
    document.body.style.overflow = 'hidden';
    setClearMode('all');
}

function closeClearModal() {
    document.getElementById('clearModal').classList.remove('show');
    document.body.style.overflow = '';
}

function setClearMode(mode) {
    clearMode = mode;

    document.querySelectorAll('.aud-clear-tab').forEach(t => {
        t.classList.toggle('aud-clear-tab--active', t.dataset.mode === mode);
    });

    const block = document.getElementById('clearPeriodBlock');
    const count = document.getElementById('clearCount');

    if (mode === 'period') {
        block.style.display = 'block';
        count.textContent = 'за период';
    } else {
        block.style.display = 'none';
        count.textContent = 'все записи';
    }
}

function confirmClear() {
    let text = '';
    if (clearMode === 'all') {
        text = 'Точно удалить ВСЕ записи журнала?\n\nЭто действие необратимо!';
    } else {
        const from = document.getElementById('clearFrom').value;
        const to = document.getElementById('clearTo').value;
        text = `Удалить записи журнала за период ${from} — ${to}?\n\nЭто действие необратимо!`;
    }

    if (!confirm(text)) return;

    const fd = new FormData();
    fd.append('action', 'clear_audit');
    fd.append('mode', clearMode);

    if (clearMode === 'period') {
        fd.append('from', document.getElementById('clearFrom').value);
        fd.append('to', document.getElementById('clearTo').value);
    }

    fetch('audit.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                showAudToast('Ошибка: ' + (data.error || 'не удалось'), true);
                return;
            }
            showAudToast(`Удалено записей: ${data.deleted}`);
            closeClearModal();
            setTimeout(() => location.reload(), 800);
        })
        .catch(() => showAudToast('Ошибка сети', true));
}

document.getElementById('clearModal').addEventListener('click', (e) => {
    if (e.target.id === 'clearModal') closeClearModal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeClearModal();
});

function showAudToast(text, isError = false) {
    const toast = document.getElementById('audToast');
    const txt = document.getElementById('audToastText');
    const icon = toast.querySelector('i');

    txt.textContent = text;
    if (isError) {
        icon.className = 'bi bi-exclamation-triangle-fill';
        icon.style.color = '#ff453a';
    } else {
        icon.className = 'bi bi-check-circle-fill';
        icon.style.color = '#30d158';
    }

    toast.style.transform = 'translateX(-50%) translateY(0)';
    toast.style.opacity = '1';
    clearTimeout(window._audToastTimer);
    window._audToastTimer = setTimeout(() => {
        toast.style.transform = 'translateX(-50%) translateY(100px)';
        toast.style.opacity = '0';
    }, 2200);
}
</script>

<?php require_once 'includes/footer.php'; ?>