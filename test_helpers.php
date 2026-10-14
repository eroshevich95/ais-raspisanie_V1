<?php
require_once 'config/database.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== ТЕСТ ИМПОРТА ===\n\n";

// 1. Проверим, что видит PHP
echo "DB: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
echo "Students: " . $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() . "\n";
echo "Groups: " . $pdo->query("SELECT COUNT(*) FROM groups_table")->fetchColumn() . "\n\n";

// 2. Попробуем создать тестового студента
echo "=== ПРОБУЕМ INSERT ===\n";

try {
    // Найдём или создадим группу ИСт-231
    $stmt = $pdo->prepare("SELECT id FROM groups_table WHERE name = ?");
    $stmt->execute(['ИСт-231']);
    $gid = $stmt->fetchColumn();

    if (!$gid) {
        echo "Группа ИСт-231 не найдена, создаём...\n";
        $pdo->prepare("INSERT INTO groups_table (name) VALUES (?)")->execute(['ИСт-231']);
        $gid = $pdo->lastInsertId();
        echo "Создана группа id=$gid\n";
    } else {
        echo "Группа найдена id=$gid\n";
    }

    // Попробуем вставить студента
    echo "\nПробуем INSERT студента...\n";
    $pdo->prepare("
        INSERT INTO students
            (group_id, zachetka, full_name, login, department, institute, direction, specialty)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $gid,
        '23Т999',
        'Тестов Тест Тестович',
        'testtest',
        'Кафедра тестовая',
        'Институт тестовый',
        'Направление тестовое',
        'Специальность тестовая'
    ]);

    $newId = $pdo->lastInsertId();
    echo "УСПЕХ! Студент добавлен id=$newId\n\n";

    // Проверим
    echo "=== ПРОВЕРКА ===\n";
    $stmt = $pdo->query("SELECT id, zachetka, full_name, group_id FROM students");
    foreach ($stmt->fetchAll() as $s) {
        echo "  id={$s['id']}  zachetka={$s['zachetka']}  name={$s['full_name']}  group_id={$s['group_id']}\n";
    }

    // Удаляем тестового
    $pdo->prepare("DELETE FROM students WHERE zachetka = ?")->execute(['23Т999']);
    echo "\nТестовый студент удалён.\n";

} catch (Throwable $e) {
    echo "!!! ОШИБКА !!!\n";
    echo $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}