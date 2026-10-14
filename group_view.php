<?php
$pageTitle = 'Группа';
require_once 'config/database.php';
require_once 'includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM groups_table WHERE id = ?");
$stmt->execute([$id]);
$group = $stmt->fetch();

if (!$group) {
    echo '<div class="alert alert-danger">Группа не найдена</div>';
    require_once 'includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE group_id = ? ORDER BY full_name");
$stmt->execute([$id]);
$students = $stmt->fetchAll();
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="upload_students.php">Группы</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($group['name']) ?></li>
    </ol>
</nav>

<h2 class="mb-4">
    <i class="bi bi-people"></i> <?= htmlspecialchars($group['name']) ?>
    <span class="text-muted fs-6">(<?= count($students) ?> студентов)</span>
</h2>

<?php if (!empty($group['institute'])): ?>
    <p class="text-muted">
        <i class="bi bi-building"></i> <?= htmlspecialchars($group['institute']) ?>
    </p>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 50px;">№</th>
                <th style="width: 100px;">Зачётка</th>
                <th>ФИО</th>
                <th>Логин</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><code><?= htmlspecialchars($s['zachetka'] ?? '—') ?></code></td>
                    <td><?= htmlspecialchars($s['full_name']) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($s['login'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>