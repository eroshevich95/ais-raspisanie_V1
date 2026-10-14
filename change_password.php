<?php
$pageTitle = 'Смена пароля';
require_once 'config/database.php';
require_once 'includes/header.php';

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $new2 = $_POST['new_password2'] ?? '';

    if (!$old || !$new) $error = 'Заполните все поля';
    elseif (strlen($new) < 6) $error = 'Пароль минимум 6 символов';
    elseif ($new !== $new2) $error = 'Пароли не совпадают';
    else {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($old, $hash)) {
            $error = 'Неверный текущий пароль';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            $success = 'Пароль изменён';
        }
    }
}
?>
<h2 class="mb-4"><i class="bi bi-key"></i> Смена пароля</h2>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" style="max-width: 400px;">
            <div class="mb-3">
                <label class="form-label">Текущий пароль</label>
                <input type="password" name="old_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Новый пароль</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label">Повторите пароль</label>
                <input type="password" name="new_password2" class="form-control" required minlength="6">
            </div>
            <button class="btn btn-primary"><i class="bi bi-check"></i> Сохранить</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>