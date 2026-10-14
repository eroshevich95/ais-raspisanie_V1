<?php
/**
 * Админ-API: единый обработчик действий.
 * Доступ только для role = 'admin'.
 * Все действия защищены CSRF.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'domain' => '',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Студентов не пускаем в админку
if (($_SESSION['user_type'] ?? 'teacher') === 'student') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Доступ запрещён']);
    exit;
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Доступ запрещён']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/settings.php';

$adminId = (int)$_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF (кроме backup)
if ($action !== 'backup_download') {
    $csrfSession = $_SESSION['csrf_admin'] ?? '';
    $csrfRequest = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
    if ($csrfSession === '' || !hash_equals($csrfSession, (string)$csrfRequest)) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'error' => 'CSRF-токен недействителен. Обновите страницу.']);
        exit;
    }
}

try {
    switch ($action) {

        // ============================================================
        // ОБЗОР / СТАТИСТИКА
        // ============================================================
        case 'stats_overview': {
            $stats = [];
            $stats['users_total']    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $stats['users_active']   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
            $stats['users_blocked']  = $stats['users_total'] - $stats['users_active'];
            $stats['students_total'] = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            $stats['groups_total']   = (int)$pdo->query("SELECT COUNT(*) FROM groups_table")->fetchColumn();
            $stats['lessons_total']  = (int)$pdo->query("SELECT COUNT(*) FROM lessons")->fetchColumn();
            $stats['lessons_today']  = (int)$pdo->query("SELECT COUNT(*) FROM lessons WHERE lesson_date = CURDATE()")->fetchColumn();
            $stats['lessons_week']   = (int)$pdo->query("
                SELECT COUNT(*) FROM lessons
                WHERE lesson_date BETWEEN
                    DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                    AND DATE_ADD(CURDATE(), INTERVAL (6 - WEEKDAY(CURDATE())) DAY)
            ")->fetchColumn();

            $att = $pdo->query("
                SELECT COUNT(*) AS total, COALESCE(SUM(is_present), 0) AS present
                FROM attendance a
                JOIN lessons l ON a.lesson_id = l.id
                WHERE l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ")->fetch();
            $stats['att_total']      = (int)$att['total'];
            $stats['att_present']    = (int)$att['present'];
            $stats['att_pct']        = $stats['att_total'] > 0 ? (int)round($stats['att_present'] / $stats['att_total'] * 100) : null;
            $stats['absences_total'] = $stats['att_total'] - $stats['att_present'];

            $stats['debtors'] = (int)$pdo->query("
                SELECT COUNT(*) FROM (
                    SELECT a.student_id FROM attendance a
                    JOIN lessons l ON a.lesson_id = l.id
                    WHERE l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                      AND a.is_present = 0
                    GROUP BY a.student_id HAVING COUNT(*) >= 3
                ) AS d
            ")->fetchColumn();

            $stats['reset_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM password_reset_requests WHERE status = 'pending'")->fetchColumn();

            // Сообщения
            try {
                $tables = $pdo->query("SHOW TABLES LIKE 'site_messages'")->fetchAll();
                if (!empty($tables)) {
                    $stats['messages_total']  = (int)$pdo->query("SELECT COUNT(*) FROM site_messages")->fetchColumn();
                    $stats['messages_active'] = (int)$pdo->query("SELECT COUNT(*) FROM site_messages WHERE is_active = 1")->fetchColumn();
                } else {
                    $stats['messages_total']  = 0;
                    $stats['messages_active'] = 0;
                }
            } catch (Throwable $e) {
                $stats['messages_total']  = 0;
                $stats['messages_active'] = 0;
            }

            // Режим тех. обслуживания
            try {
                $stats['maintenance_enabled'] = isMaintenanceEnabled($pdo) ? 1 : 0;
            } catch (Throwable $e) {
                $stats['maintenance_enabled'] = 0;
            }

            // Достижения
            try {
                $tables = $pdo->query("SHOW TABLES LIKE 'achievements'")->fetchAll();
                if (!empty($tables)) {
                    $stats['achievements_total']   = (int)$pdo->query("SELECT COUNT(*) FROM achievements")->fetchColumn();
                    $stats['achievements_active']  = (int)$pdo->query("SELECT COUNT(*) FROM achievements WHERE is_active = 1")->fetchColumn();
                    $stats['achievements_awarded'] = (int)$pdo->query("SELECT COUNT(*) FROM student_achievements")->fetchColumn();
                } else {
                    $stats['achievements_total']   = 0;
                    $stats['achievements_active']  = 0;
                    $stats['achievements_awarded'] = 0;
                }
            } catch (Throwable $e) {
                $stats['achievements_total']   = 0;
                $stats['achievements_active']  = 0;
                $stats['achievements_awarded'] = 0;
            }

            echo json_encode(['ok' => true, 'stats' => $stats]);
            break;
        }

        case 'attendance_trend': {
            $days = max(7, min(90, (int)($_POST['days'] ?? 30)));
            $stmt = $pdo->prepare("
                SELECT l.lesson_date AS d, COUNT(a.id) AS total, COALESCE(SUM(a.is_present), 0) AS present
                FROM attendance a
                JOIN lessons l ON a.lesson_id = l.id
                WHERE l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  AND l.lesson_date <= CURDATE()
                GROUP BY l.lesson_date
            ");
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt->execute();

            $byDate = [];
            foreach ($stmt->fetchAll() as $r) $byDate[$r['d']] = $r;

            $labels = $values = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i day"));
                $labels[] = date('d.m', strtotime($date));
                $row = $byDate[$date] ?? null;
                $values[] = ($row && (int)$row['total'] > 0)
                    ? (int)round((int)$row['present'] / (int)$row['total'] * 100) : null;
            }

            echo json_encode(['ok' => true, 'labels' => $labels, 'values' => $values]);
            break;
        }

        case 'top_debtors': {
            $limit = max(1, min(50, (int)($_POST['limit'] ?? 5)));
            $stmt = $pdo->prepare("
                SELECT s.id, s.full_name, s.zachetka, g.name AS group_name,
                       COUNT(a.id) AS total,
                       SUM(CASE WHEN a.is_present = 0 THEN 1 ELSE 0 END) AS absent
                FROM students s
                JOIN groups_table g ON s.group_id = g.id
                JOIN attendance a ON a.student_id = s.id
                JOIN lessons l ON a.lesson_id = l.id
                WHERE l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                GROUP BY s.id
                HAVING absent >= 3
                ORDER BY absent DESC
                LIMIT $limit
            ");
            $stmt->execute();
            echo json_encode(['ok' => true, 'rows' => $stmt->fetchAll()]);
            break;
        }

        case 'audit_recent': {
            $limit = max(1, min(50, (int)($_POST['limit'] ?? 10)));
            $stmt = $pdo->prepare("SELECT id, user_name, action, description, created_at FROM audit_log ORDER BY id DESC LIMIT $limit");
            $stmt->execute();
            echo json_encode(['ok' => true, 'rows' => $stmt->fetchAll()]);
            break;
        }

        // ============================================================
        // НАСТРОЙКИ САЙТА
        // ============================================================
        case 'settings_get': {
            try {
                $stmt = $pdo->query("SELECT `key`, `value`, `updated_at` FROM site_settings");
                $settings = [];
                foreach ($stmt->fetchAll() as $r) {
                    $settings[$r['key']] = [
                        'value'      => $r['value'],
                        'updated_at' => $r['updated_at'],
                    ];
                }
                echo json_encode(['ok' => true, 'settings' => $settings]);
            } catch (Throwable $e) {
                echo json_encode(['ok' => false, 'error' => 'Таблица site_settings не создана. Выполните миграцию.']);
            }
            break;
        }

        case 'settings_save': {
            $maintenanceEnabled = !empty($_POST['maintenance_enabled']) ? '1' : '0';
            $title   = trim($_POST['maintenance_title']   ?? '');
            $message = trim($_POST['maintenance_message'] ?? '');
            $until   = trim($_POST['maintenance_until']   ?? '');

            if ($title === '')   $title   = 'Идут технические работы';
            if ($message === '') $message = 'Мы обновляем систему. Скоро вернёмся.';

            $untilValue = null;
            if ($until !== '' && preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/', $until)) {
                $untilValue = str_replace('T', ' ', $until);
                if (strlen($untilValue) === 10) $untilValue .= ' 00:00:00';
            }

            setSetting($pdo, 'maintenance_enabled', $maintenanceEnabled);
            setSetting($pdo, 'maintenance_title',   $title);
            setSetting($pdo, 'maintenance_message', $message);
            setSetting($pdo, 'maintenance_until',   $untilValue);

            auditLog($pdo, 'admin.settings_save', [
                'description' => $maintenanceEnabled === '1'
                    ? 'Включён режим технического обслуживания'
                    : 'Выключен режим технического обслуживания',
            ]);

            echo json_encode(['ok' => true, 'enabled' => $maintenanceEnabled]);
            break;
        }

        // ============================================================
        // ПОЛЬЗОВАТЕЛИ
        // ============================================================
        case 'user_get': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT id, login, full_name, email, role, is_active,
                       institute_name, department_name, department,
                       api_teacher_id, last_login, created_at
                FROM users WHERE id = ?
            ");
            $stmt->execute([$id]);
            $u = $stmt->fetch();
            if (!$u) throw new Exception('Пользователь не найден');
            echo json_encode(['ok' => true, 'user' => $u]);
            break;
        }

        case 'users_list': {
            $search  = trim($_POST['search'] ?? '');
            $role    = trim($_POST['role'] ?? '');
            $status  = trim($_POST['status'] ?? '');
            $page    = max(1, (int)($_POST['page'] ?? 1));
            $perPage = max(10, min(100, (int)($_POST['per_page'] ?? 25)));
            $sort    = $_POST['sort'] ?? 'name';
            $dir     = strtolower($_POST['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

            $where = $params = [];
            if ($search !== '') {
                $where[] = "(full_name LIKE ? OR login LIKE ? OR email LIKE ?)";
                $like = '%' . $search . '%';
                $params[] = $like; $params[] = $like; $params[] = $like;
            }
            if ($role !== '' && in_array($role, ['teacher','head_of_department','administration','admin'])) {
                $where[] = "role = ?"; $params[] = $role;
            }
            if ($status === 'active')  $where[] = "is_active = 1";
            if ($status === 'blocked') $where[] = "is_active = 0";

            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
            $orderMap = ['name'=>'full_name','login'=>'login',
                'role'=>"FIELD(role,'admin','administration','head_of_department','teacher')",
                'last_login'=>'last_login','created'=>'created_at'];
            $orderCol = $orderMap[$sort] ?? 'full_name';
            $orderSql = "$orderCol $dir";

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            $pages = max(1, (int)ceil($total / $perPage));
            $page  = min($page, $pages);
            $offset = ($page - 1) * $perPage;

            $stmt = $pdo->prepare("
                SELECT id, login, full_name, email, role, is_active,
                       institute_name, department_name, department,
                       api_teacher_id, last_login, created_at
                FROM users $whereSql ORDER BY $orderSql LIMIT $perPage OFFSET $offset
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            echo json_encode(['ok'=>true,'rows'=>$rows,'total'=>$total,'pages'=>$pages,'page'=>$page]);
            break;
        }

        case 'user_create': {
            $login     = trim($_POST['login'] ?? '');
            $password  = $_POST['password'] ?? '';
            $fullName  = trim($_POST['full_name'] ?? '');
            $email     = trim($_POST['email'] ?? '');
            $role      = $_POST['role'] ?? 'teacher';

            if ($login === '' || mb_strlen($login) < 4) throw new Exception('Логин минимум 4 символа');
            if (!preg_match('/^[a-zA-Z0-9_.\-]+$/', $login)) throw new Exception('Логин: только латиница, цифры, _ . -');
            if (mb_strlen($password) < 6) throw new Exception('Пароль минимум 6 символов');
            if ($fullName === '') throw new Exception('Введите ФИО');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Некорректный email');
            if (!in_array($role, ['teacher','head_of_department','administration','admin'])) throw new Exception('Неверная роль');

            $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
            $stmt->execute([$login]);
            if ($stmt->fetch()) throw new Exception('Логин уже занят');

            if ($email !== '') {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) throw new Exception('Email уже используется');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("
                INSERT INTO users (login, password_hash, full_name, email, role, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, 1, NOW())
            ")->execute([$login, $hash, $fullName, $email ?: null, $role]);

            $newId = (int)$pdo->lastInsertId();
            auditLog($pdo, 'admin.user_create', [
                'entity_type' => 'user', 'entity_id' => $newId,
                'description' => "Создан пользователь: {$fullName} ({$login}) · роль: {$role}",
            ]);
            echo json_encode(['ok'=>true,'id'=>$newId]);
            break;
        }

        case 'user_update': {
            $id = (int)($_POST['id'] ?? 0);
            $fullName = trim($_POST['full_name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $role     = $_POST['role'] ?? 'teacher';

            if (!$id || $fullName === '') throw new Exception('Заполните ФИО');
            if (!in_array($role, ['teacher','head_of_department','administration','admin'])) throw new Exception('Неверная роль');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Некорректный email');

            if ($email !== '') {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) throw new Exception('Email уже используется');
            }
            if ($id === $adminId && $role !== 'admin') throw new Exception('Нельзя снять с себя роль администратора');

            $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ? WHERE id = ?")
                ->execute([$fullName, $email ?: null, $role, $id]);

            auditLog($pdo, 'admin.user_update', [
                'entity_type'=>'user','entity_id'=>$id,
                'description'=>"Изменён пользователь: {$fullName} (роль: {$role})",
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        case 'user_toggle_active': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT full_name, is_active FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $u = $stmt->fetch();
            if (!$u) throw new Exception('Пользователь не найден');
            if ($id === $adminId) throw new Exception('Нельзя заблокировать себя');

            $new = $u['is_active'] ? 0 : 1;
            $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new, $id]);

            auditLog($pdo, 'admin.user_toggle', [
                'entity_type'=>'user','entity_id'=>$id,
                'description'=>($new ? 'Разблокирован' : 'Заблокирован') . ": {$u['full_name']}",
            ]);
            echo json_encode(['ok'=>true,'is_active'=>$new]);
            break;
        }

        case 'user_delete': {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === $adminId) throw new Exception('Нельзя удалить себя');

            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $name = $stmt->fetchColumn();
            if (!$name) throw new Exception('Пользователь не найден');

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE user_id = ?");
            $stmt->execute([$id]);
            $lessonsCount = (int)$stmt->fetchColumn();
            if ($lessonsCount > 0) {
                throw new Exception("Нельзя удалить: у пользователя {$lessonsCount} занятий.");
            }

            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            auditLog($pdo, 'admin.user_delete', [
                'entity_type'=>'user','entity_id'=>$id,
                'description'=>"Удалён пользователь: {$name}",
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        case 'user_reset_password': {
            $id = (int)($_POST['id'] ?? 0);
            $newPassword = trim($_POST['password'] ?? '');
            if (mb_strlen($newPassword) < 6) throw new Exception('Пароль минимум 6 символов');

            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $name = $stmt->fetchColumn();
            if (!$name) throw new Exception('Пользователь не найден');

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);

            auditLog($pdo, 'admin.user_password', [
                'entity_type'=>'user','entity_id'=>$id,
                'description'=>"Сброшен пароль: {$name}",
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        case 'users_bulk': {
            $ids    = array_map('intval', $_POST['ids'] ?? []);
            $bulkAction = $_POST['bulk_action'] ?? '';
            $value  = $_POST['value'] ?? '';

            $ids = array_filter($ids, fn($id) => $id > 0 && $id !== $adminId);
            if (empty($ids)) throw new Exception('Не выбрано ни одного пользователя (или выбран только вы)');

            $in = implode(',', array_fill(0, count($ids), '?'));

            switch ($bulkAction) {
                case 'block':
                    $pdo->prepare("UPDATE users SET is_active = 0 WHERE id IN ($in)")->execute($ids);
                    auditLog($pdo, 'admin.users_bulk_block', ['description'=>'Массовая блокировка: '.count($ids)]);
                    echo json_encode(['ok'=>true,'affected'=>count($ids)]);
                    break;
                case 'unblock':
                    $pdo->prepare("UPDATE users SET is_active = 1 WHERE id IN ($in)")->execute($ids);
                    auditLog($pdo, 'admin.users_bulk_unblock', ['description'=>'Массовая разблокировка: '.count($ids)]);
                    echo json_encode(['ok'=>true,'affected'=>count($ids)]);
                    break;
                case 'role':
                    if (!in_array($value, ['teacher','head_of_department','administration','admin'])) {
                        throw new Exception('Неверная роль');
                    }
                    $params = array_merge([$value], $ids);
                    $pdo->prepare("UPDATE users SET role = ? WHERE id IN ($in)")->execute($params);
                    auditLog($pdo, 'admin.users_bulk_role', ['description'=>'Массовая смена роли: '.count($ids).' → '.$value]);
                    echo json_encode(['ok'=>true,'affected'=>count($ids)]);
                    break;
                case 'delete':
                    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id IN ($in)");
                    $stmt->execute($ids);
                    $toDelete = $stmt->fetchAll();
                    $blocked = []; $deleted = 0;
                    foreach ($toDelete as $u) {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE user_id = ?");
                        $stmt->execute([$u['id']]);
                        if ((int)$stmt->fetchColumn() > 0) { $blocked[] = $u['full_name']; continue; }
                        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$u['id']]);
                        $deleted++;
                    }
                    auditLog($pdo, 'admin.users_bulk_delete', [
                        'description'=>"Массовое удаление: удалено $deleted, пропущено ".count($blocked)
                    ]);
                    echo json_encode(['ok'=>true,'deleted'=>$deleted,'blocked'=>$blocked]);
                    break;
                default:
                    throw new Exception('Неизвестное массовое действие');
            }
            break;
        }

        // ============================================================
        // ГРУППЫ
        // ============================================================
        case 'groups_list': {
            $search = trim($_POST['search'] ?? '');
            $page   = max(1, (int)($_POST['page'] ?? 1));
            $perPage = max(10, min(200, (int)($_POST['per_page'] ?? 30)));
            $sort   = $_POST['sort'] ?? 'name';
            $dir    = strtolower($_POST['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

            $where = $params = [];
            if ($search !== '') {
                $where[] = "(g.name LIKE ? OR g.institute LIKE ?)";
                $like = '%'.$search.'%';
                $params[] = $like; $params[] = $like;
            }
            $whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

            $orderMap = ['name'=>'g.name','institute'=>'g.institute','students'=>'students_cnt','lessons'=>'lessons_cnt'];
            $orderCol = $orderMap[$sort] ?? 'g.name';
            $orderSql = "$orderCol $dir";

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM groups_table g $whereSql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            $pages = max(1, (int)ceil($total/$perPage));
            $page  = min($page, $pages);
            $offset = ($page-1)*$perPage;

            $stmt = $pdo->prepare("
                SELECT g.id, g.name, g.institute, g.api_group_id,
                    (SELECT COUNT(*) FROM students s WHERE s.group_id = g.id) AS students_cnt,
                    (SELECT COUNT(*) FROM lessons l WHERE l.group_id = g.id) AS lessons_cnt
                FROM groups_table g $whereSql ORDER BY $orderSql LIMIT $perPage OFFSET $offset
            ");
            $stmt->execute($params);
            echo json_encode(['ok'=>true,'rows'=>$stmt->fetchAll(),'total'=>$total,'pages'=>$pages,'page'=>$page]);
            break;
        }

        case 'group_get': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM groups_table WHERE id = ?");
            $stmt->execute([$id]);
            $g = $stmt->fetch();
            if (!$g) throw new Exception('Группа не найдена');
            echo json_encode(['ok'=>true,'group'=>$g]);
            break;
        }

        case 'group_create': {
            $name = trim($_POST['name'] ?? '');
            $institute = trim($_POST['institute'] ?? '');
            if ($name === '') throw new Exception('Введите название группы');

            $stmt = $pdo->prepare("SELECT id FROM groups_table WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) throw new Exception('Группа с таким названием уже есть');

            $pdo->prepare("INSERT INTO groups_table (name, institute) VALUES (?, ?)")
                ->execute([$name, $institute ?: null]);
            $newId = (int)$pdo->lastInsertId();

            auditLog($pdo, 'admin.group_create', [
                'entity_type'=>'group','entity_id'=>$newId,
                'description'=>"Создана группа: {$name}",
            ]);
            echo json_encode(['ok'=>true,'id'=>$newId]);
            break;
        }

        case 'group_update': {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $institute = trim($_POST['institute'] ?? '');

            if (!$id || $name === '') throw new Exception('Заполните название');
            $stmt = $pdo->prepare("SELECT id FROM groups_table WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) throw new Exception('Группа с таким названием уже есть');

            $pdo->prepare("UPDATE groups_table SET name = ?, institute = ? WHERE id = ?")
                ->execute([$name, $institute ?: null, $id]);

            auditLog($pdo, 'admin.group_update', [
                'entity_type'=>'group','entity_id'=>$id,
                'description'=>"Изменена группа: {$name}",
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        case 'group_delete': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT name FROM groups_table WHERE id = ?");
            $stmt->execute([$id]);
            $name = $stmt->fetchColumn();
            if (!$name) throw new Exception('Группа не найдена');

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE group_id = ?");
            $stmt->execute([$id]);
            $lessonsCount = (int)$stmt->fetchColumn();
            if ($lessonsCount > 0) throw new Exception("Нельзя удалить: у группы {$lessonsCount} занятий.");

            $pdo->prepare("DELETE FROM students WHERE group_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM groups_table WHERE id = ?")->execute([$id]);

            auditLog($pdo, 'admin.group_delete', [
                'entity_type'=>'group','entity_id'=>$id,
                'description'=>"Удалена группа: {$name}",
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        // ============================================================
        // СТУДЕНТЫ
        // ============================================================
        case 'students_list': {
            $search  = trim($_POST['search'] ?? '');
            $groupId = (int)($_POST['group_id'] ?? 0);
            $page    = max(1, (int)($_POST['page'] ?? 1));
            $perPage = max(10, min(200, (int)($_POST['per_page'] ?? 30)));
            $sort    = $_POST['sort'] ?? 'name';
            $dir     = strtolower($_POST['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

            $where = $params = [];
            if ($search !== '') {
                $where[] = "(s.full_name LIKE ? OR s.zachetka LIKE ?)";
                $like = '%'.$search.'%';
                $params[] = $like; $params[] = $like;
            }
            if ($groupId > 0) {
                $where[] = "s.group_id = ?";
                $params[] = $groupId;
            }
            $whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';
            $orderMap = ['name'=>'s.full_name','zachetka'=>'s.zachetka','group'=>'g.name'];
            $orderCol = $orderMap[$sort] ?? 's.full_name';
            $orderSql = "$orderCol $dir";

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students s LEFT JOIN groups_table g ON s.group_id = g.id $whereSql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            $pages = max(1, (int)ceil($total/$perPage));
            $page  = min($page, $pages);
            $offset = ($page-1)*$perPage;

            $stmt = $pdo->prepare("
                SELECT s.id, s.full_name, s.zachetka, s.subgroup, s.login,
                       s.group_id, g.name AS group_name
                FROM students s
                LEFT JOIN groups_table g ON s.group_id = g.id
                $whereSql ORDER BY $orderSql LIMIT $perPage OFFSET $offset
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $groups = $pdo->query("SELECT id, name FROM groups_table ORDER BY name")->fetchAll();

            echo json_encode(['ok'=>true,'rows'=>$rows,'total'=>$total,'pages'=>$pages,'page'=>$page,'groups'=>$groups]);
            break;
        }

        case 'student_get': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT s.*, g.name AS group_name
                FROM students s
                LEFT JOIN groups_table g ON s.group_id = g.id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            $s = $stmt->fetch();
            if (!$s) throw new Exception('Студент не найден');
            echo json_encode(['ok'=>true,'student'=>$s]);
            break;
        }

        case 'student_update': {
            $id        = (int)($_POST['id'] ?? 0);
            $fullName  = trim($_POST['full_name'] ?? '');
            $zachetka  = trim($_POST['zachetka'] ?? '');
            $groupId   = (int)($_POST['group_id'] ?? 0);
            $subgroup  = trim($_POST['subgroup'] ?? '');

            if (!$id || $fullName === '') throw new Exception('Заполните ФИО');
            if (!$groupId) throw new Exception('Выберите группу');

            $pdo->prepare("
                UPDATE students SET full_name = ?, zachetka = ?, group_id = ?, subgroup = ?
                WHERE id = ?
            ")->execute([$fullName, $zachetka ?: null, $groupId, $subgroup ?: null, $id]);

            auditLog($pdo, 'admin.student_update', [
                'entity_type'=>'student','entity_id'=>$id,
                'description'=>"Изменён студент: {$fullName}",
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        case 'student_delete': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $name = $stmt->fetchColumn();
            if (!$name) throw new Exception('Студент не найден');

            $pdo->prepare("DELETE FROM attendance WHERE student_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM submitted_works WHERE student_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM session_grades WHERE student_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);

            auditLog($pdo, 'admin.student_delete', [
                'entity_type'=>'student','entity_id'=>$id,
                'description'=>"Удалён студент: {$name}",
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        case 'students_bulk_move': {
            $ids     = array_map('intval', $_POST['ids'] ?? []);
            $groupId = (int)($_POST['group_id'] ?? 0);

            $ids = array_filter($ids, fn($id) => $id > 0);
            if (empty($ids)) throw new Exception('Не выбрано ни одного студента');
            if (!$groupId) throw new Exception('Выберите группу');

            $stmt = $pdo->prepare("SELECT name FROM groups_table WHERE id = ?");
            $stmt->execute([$groupId]);
            $groupName = $stmt->fetchColumn();
            if (!$groupName) throw new Exception('Группа не найдена');

            $in = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$groupId], $ids);
            $pdo->prepare("UPDATE students SET group_id = ? WHERE id IN ($in)")->execute($params);

            auditLog($pdo, 'admin.students_bulk_move', [
                'description'=>"Переведено в группу {$groupName}: ".count($ids)." студентов",
            ]);
            echo json_encode(['ok'=>true,'affected'=>count($ids)]);
            break;
        }

        // ============================================================
        // ЗАНЯТИЯ
        // ============================================================
        case 'lessons_list': {
            $search    = trim($_POST['search'] ?? '');
            $teacherId = (int)($_POST['teacher_id'] ?? 0);
            $dateFrom  = trim($_POST['date_from'] ?? '');
            $dateTo    = trim($_POST['date_to'] ?? '');
            $page      = max(1, (int)($_POST['page'] ?? 1));
            $perPage   = max(10, min(200, (int)($_POST['per_page'] ?? 30)));

            $where = $params = [];
            if ($search !== '') {
                $where[] = "(l.discipline LIKE ? OR g.name LIKE ?)";
                $like = '%'.$search.'%';
                $params[] = $like; $params[] = $like;
            }
            if ($teacherId > 0) { $where[] = "l.user_id = ?"; $params[] = $teacherId; }
            if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                $where[] = "l.lesson_date >= ?"; $params[] = $dateFrom;
            }
            if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                $where[] = "l.lesson_date <= ?"; $params[] = $dateTo;
            }
            $whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons l LEFT JOIN groups_table g ON l.group_id = g.id $whereSql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            $pages = max(1, (int)ceil($total/$perPage));
            $page  = min($page, $pages);
            $offset = ($page-1)*$perPage;

            $stmt = $pdo->prepare("
                SELECT l.id, l.lesson_date, l.lesson_time, l.lesson_number,
                       l.discipline, l.lesson_type, l.audience, l.subgroup, l.is_manual,
                       g.name AS group_name, u.full_name AS teacher_name,
                    (SELECT COUNT(*) FROM attendance a WHERE a.lesson_id = l.id) AS marks_cnt,
                    (SELECT COUNT(*) FROM submitted_works w WHERE w.lesson_id = l.id) AS works_cnt
                FROM lessons l
                LEFT JOIN groups_table g ON l.group_id = g.id
                LEFT JOIN users u ON l.user_id = u.id
                $whereSql ORDER BY l.lesson_date DESC, l.lesson_time DESC, l.id DESC
                LIMIT $perPage OFFSET $offset
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $teachers = $pdo->query("
                SELECT DISTINCT u.id, u.full_name
                FROM users u JOIN lessons l ON l.user_id = u.id
                ORDER BY u.full_name
            ")->fetchAll();

            echo json_encode(['ok'=>true,'rows'=>$rows,'total'=>$total,'pages'=>$pages,'page'=>$page,'teachers'=>$teachers]);
            break;
        }

        case 'lesson_delete': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT l.discipline, l.lesson_date, g.name AS group_name
                FROM lessons l
                LEFT JOIN groups_table g ON l.group_id = g.id
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
            $l = $stmt->fetch();
            if (!$l) throw new Exception('Занятие не найдено');

            $pdo->prepare("DELETE FROM attendance WHERE lesson_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM submitted_works WHERE lesson_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM lessons WHERE id = ?")->execute([$id]);

            auditLog($pdo, 'admin.lesson_delete', [
                'entity_type'=>'lesson','entity_id'=>$id,
                'description'=>"Удалено занятие: {$l['discipline']} ({$l['lesson_date']}) · ".($l['group_name'] ?? '—'),
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        case 'lessons_delete_bulk': {
            $ids = array_map('intval', $_POST['ids'] ?? []);
            $ids = array_filter($ids, fn($id) => $id > 0);
            if (empty($ids)) throw new Exception('Не выбрано ни одного занятия');

            $in = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM attendance WHERE lesson_id IN ($in)")->execute($ids);
            $pdo->prepare("DELETE FROM submitted_works WHERE lesson_id IN ($in)")->execute($ids);
            $pdo->prepare("DELETE FROM lessons WHERE id IN ($in)")->execute($ids);

            auditLog($pdo, 'admin.lessons_bulk_delete', [
                'description'=>'Массовое удаление занятий: '.count($ids),
            ]);
            echo json_encode(['ok'=>true,'affected'=>count($ids)]);
            break;
        }

        case 'lessons_delete_old': {
            $date = $_POST['date'] ?? '';
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new Exception('Неверная дата');

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE lesson_date < ?");
            $stmt->execute([$date]);
            $count = (int)$stmt->fetchColumn();

            if ($count === 0) { echo json_encode(['ok'=>true,'deleted'=>0]); break; }

            $pdo->prepare("
                DELETE a FROM attendance a INNER JOIN lessons l ON a.lesson_id = l.id
                WHERE l.lesson_date < ?
            ")->execute([$date]);
            $pdo->prepare("
                DELETE w FROM submitted_works w INNER JOIN lessons l ON w.lesson_id = l.id
                WHERE l.lesson_date < ?
            ")->execute([$date]);
            $pdo->prepare("DELETE FROM lessons WHERE lesson_date < ?")->execute([$date]);

            auditLog($pdo, 'admin.lessons_purge', [
                'entity_type'=>'lesson',
                'description'=>"Удалены занятия до {$date}: {$count} шт.",
            ]);
            echo json_encode(['ok'=>true,'deleted'=>$count]);
            break;
        }

        // ============================================================
        // СООБЩЕНИЯ
        // ============================================================
        case 'messages_list': {
            $tables = $pdo->query("SHOW TABLES LIKE 'site_messages'")->fetchAll();
            if (empty($tables)) {
                throw new Exception('Таблица site_messages не создана. Выполните SQL-миграцию.');
            }

            $search  = trim($_POST['search'] ?? '');
            $section = trim($_POST['section'] ?? '');
            $status  = trim($_POST['status'] ?? '');
            $page    = max(1, (int)($_POST['page'] ?? 1));
            $perPage = max(10, min(200, (int)($_POST['per_page'] ?? 30)));

            $where = $params = [];
            if ($search !== '') {
                $where[] = "(title LIKE ? OR text LIKE ?)";
                $like = '%'.$search.'%';
                $params[] = $like; $params[] = $like;
            }
            if ($section !== '') {
                $where[] = "section = ?"; $params[] = $section;
            }
            if ($status === 'active')   $where[] = "is_active = 1";
            if ($status === 'disabled') $where[] = "is_active = 0";

            $whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM site_messages $whereSql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            $pages = max(1, (int)ceil($total/$perPage));
            $page  = min($page, $pages);
            $offset = ($page-1)*$perPage;

            $stmt = $pdo->prepare("
                SELECT id, section, type, title, text, is_active, dismissible, sort_order, created_at, updated_at
                FROM site_messages $whereSql
                ORDER BY sort_order ASC, id DESC
                LIMIT $perPage OFFSET $offset
            ");
            $stmt->execute($params);
            echo json_encode(['ok'=>true,'rows'=>$stmt->fetchAll(),'total'=>$total,'pages'=>$pages,'page'=>$page]);
            break;
        }

        case 'message_get': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM site_messages WHERE id = ?");
            $stmt->execute([$id]);
            $m = $stmt->fetch();
            if (!$m) throw new Exception('Сообщение не найдено');
            echo json_encode(['ok'=>true,'message'=>$m]);
            break;
        }

        case 'message_create': {
            $section = trim($_POST['section'] ?? 'all');
            $type    = $_POST['type'] ?? 'info';
            $title   = trim($_POST['title'] ?? '');
            $text    = trim($_POST['text'] ?? '');
            $sort    = (int)($_POST['sort_order'] ?? 0);
            $isActive    = !empty($_POST['is_active']) ? 1 : 0;
            $dismissible = !empty($_POST['dismissible']) ? 1 : 0;

            if ($text === '') throw new Exception('Введите текст сообщения');
            if (!in_array($type, ['info','success','warning','danger'])) throw new Exception('Неверный тип');

            $pdo->prepare("
                INSERT INTO site_messages (section, type, title, text, is_active, dismissible, sort_order, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$section, $type, $title ?: null, $text, $isActive, $dismissible, $sort, $adminId]);

            $newId = (int)$pdo->lastInsertId();
            auditLog($pdo, 'admin.message_create', [
                'entity_type'=>'site_message','entity_id'=>$newId,
                'description'=>"Создано сообщение для раздела «{$section}»: ".mb_substr($title ?: $text, 0, 80),
            ]);
            echo json_encode(['ok'=>true,'id'=>$newId]);
            break;
        }

        case 'message_update': {
            $id      = (int)($_POST['id'] ?? 0);
            $section = trim($_POST['section'] ?? 'all');
            $type    = $_POST['type'] ?? 'info';
            $title   = trim($_POST['title'] ?? '');
            $text    = trim($_POST['text'] ?? '');
            $sort    = (int)($_POST['sort_order'] ?? 0);
            $isActive    = !empty($_POST['is_active']) ? 1 : 0;
            $dismissible = !empty($_POST['dismissible']) ? 1 : 0;

            if (!$id) throw new Exception('ID не указан');
            if ($text === '') throw new Exception('Введите текст сообщения');
            if (!in_array($type, ['info','success','warning','danger'])) throw new Exception('Неверный тип');

            $pdo->prepare("
                UPDATE site_messages
                SET section = ?, type = ?, title = ?, text = ?,
                    is_active = ?, dismissible = ?, sort_order = ?
                WHERE id = ?
            ")->execute([$section, $type, $title ?: null, $text, $isActive, $dismissible, $sort, $id]);

            auditLog($pdo, 'admin.message_update', [
                'entity_type'=>'site_message','entity_id'=>$id,
                'description'=>"Обновлено сообщение #{$id} (раздел «{$section}»)",
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        case 'message_toggle': {
            $id = (int)($_POST['id'] ?? 0);
            $active = (int)($_POST['active'] ?? 0) ? 1 : 0;

            $stmt = $pdo->prepare("SELECT title, text FROM site_messages WHERE id = ?");
            $stmt->execute([$id]);
            $m = $stmt->fetch();
            if (!$m) throw new Exception('Сообщение не найдено');

            $pdo->prepare("UPDATE site_messages SET is_active = ? WHERE id = ?")->execute([$active, $id]);

            auditLog($pdo, 'admin.message_toggle', [
                'entity_type'=>'site_message','entity_id'=>$id,
                'description'=>($active ? 'Включено' : 'Скрыто')." сообщение: ".mb_substr($m['title'] ?: $m['text'], 0, 60),
            ]);
            echo json_encode(['ok'=>true,'is_active'=>$active]);
            break;
        }

        case 'message_delete': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT title, text FROM site_messages WHERE id = ?");
            $stmt->execute([$id]);
            $m = $stmt->fetch();
            if (!$m) throw new Exception('Сообщение не найдено');

            $pdo->prepare("DELETE FROM site_messages WHERE id = ?")->execute([$id]);

            auditLog($pdo, 'admin.message_delete', [
                'entity_type'=>'site_message','entity_id'=>$id,
                'description'=>"Удалено сообщение: ".mb_substr($m['title'] ?: $m['text'], 0, 60),
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        // ============================================================
        // ДОСТИЖЕНИЯ
        // ============================================================
        case 'achievement_create': {
            $code = trim($_POST['code'] ?? '');
            if (!preg_match('/^[a-z0-9_]+$/', $code)) {
                throw new Exception('Код: только латиница в нижнем регистре, цифры, _');
            }
            if (trim($_POST['title'] ?? '') === '') throw new Exception('Введите название');

            $stmt = $pdo->prepare("SELECT id FROM achievements WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) throw new Exception('Такой код уже есть');

            $pdo->prepare("
                INSERT INTO achievements
                    (code, title, description, icon, svg, color, category,
                     condition_type, condition_value, condition_period,
                     points, is_active, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $code,
                trim($_POST['title'] ?? ''),
                trim($_POST['description'] ?? '') ?: null,
                trim($_POST['icon'] ?? '') ?: null,
                trim($_POST['svg'] ?? '') ?: null,
                $_POST['color'] ?? '#0071e3',
                $_POST['category'] ?? 'attendance',
                $_POST['condition_type'] ?? 'attendance_pct',
                (float)($_POST['condition_value'] ?? 0),
                ($_POST['condition_period'] ?? '') !== '' ? (int)$_POST['condition_period'] : null,
                (int)($_POST['points'] ?? 10),
                !empty($_POST['is_active']) ? 1 : 0,
                (int)($_POST['sort_order'] ?? 0),
            ]);

            $newId = (int)$pdo->lastInsertId();
            auditLog($pdo, 'admin.achievement_create', [
                'entity_type' => 'achievement', 'entity_id' => $newId,
                'description' => "Создано достижение: {$code}",
            ]);
            echo json_encode(['ok' => true, 'id' => $newId]);
            break;
        }

        case 'achievement_update': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID не указан');

            $code = trim($_POST['code'] ?? '');
            if (!preg_match('/^[a-z0-9_]+$/', $code)) {
                throw new Exception('Код: только латиница в нижнем регистре, цифры, _');
            }
            if (trim($_POST['title'] ?? '') === '') throw new Exception('Введите название');

            $stmt = $pdo->prepare("SELECT id FROM achievements WHERE code = ? AND id != ?");
            $stmt->execute([$code, $id]);
            if ($stmt->fetch()) throw new Exception('Такой код уже есть');

            $pdo->prepare("
                UPDATE achievements SET
                    code = ?, title = ?, description = ?, icon = ?, svg = ?,
                    color = ?, category = ?, condition_type = ?, condition_value = ?,
                    condition_period = ?, points = ?, is_active = ?, sort_order = ?
                WHERE id = ?
            ")->execute([
                $code,
                trim($_POST['title'] ?? ''),
                trim($_POST['description'] ?? '') ?: null,
                trim($_POST['icon'] ?? '') ?: null,
                trim($_POST['svg'] ?? '') ?: null,
                $_POST['color'] ?? '#0071e3',
                $_POST['category'] ?? 'attendance',
                $_POST['condition_type'] ?? 'attendance_pct',
                (float)($_POST['condition_value'] ?? 0),
                ($_POST['condition_period'] ?? '') !== '' ? (int)$_POST['condition_period'] : null,
                (int)($_POST['points'] ?? 10),
                !empty($_POST['is_active']) ? 1 : 0,
                (int)($_POST['sort_order'] ?? 0),
                $id,
            ]);

            auditLog($pdo, 'admin.achievement_update', [
                'entity_type' => 'achievement', 'entity_id' => $id,
                'description' => "Изменено достижение #{$id} ({$code})",
            ]);
            echo json_encode(['ok' => true]);
            break;
        }

        case 'achievement_toggle': {
            $id = (int)($_POST['id'] ?? 0);
            $active = (int)($_POST['active'] ?? 0) ? 1 : 0;

            $pdo->prepare("UPDATE achievements SET is_active = ? WHERE id = ?")->execute([$active, $id]);

            auditLog($pdo, 'admin.achievement_toggle', [
                'entity_type' => 'achievement', 'entity_id' => $id,
                'description' => $active ? 'Достижение активировано' : 'Достижение скрыто',
            ]);
            echo json_encode(['ok' => true]);
            break;
        }

        case 'achievement_delete': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT code, title FROM achievements WHERE id = ?");
            $stmt->execute([$id]);
            $a = $stmt->fetch();
            if (!$a) throw new Exception('Достижение не найдено');

            $pdo->prepare("DELETE FROM achievements WHERE id = ?")->execute([$id]);

            auditLog($pdo, 'admin.achievement_delete', [
                'entity_type' => 'achievement', 'entity_id' => $id,
                'description' => "Удалено достижение: {$a['title']} ({$a['code']})",
            ]);
            echo json_encode(['ok' => true]);
            break;
        }

        // ============================================================
        // СБРОС ПАРОЛЯ
        // ============================================================
        case 'resets_list': {
            $stmt = $pdo->query("
                SELECT r.id, r.login, r.email, r.created_at, u.full_name
                FROM password_reset_requests r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.status = 'pending'
                ORDER BY r.created_at ASC
            ");
            echo json_encode(['ok'=>true,'rows'=>$stmt->fetchAll()]);
            break;
        }

        case 'resets_history': {
            $stmt = $pdo->query("
                SELECT r.id, r.login, r.email, r.status, r.processed_at,
                       r.admin_comment, u.full_name, a.full_name AS admin_name
                FROM password_reset_requests r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN users a ON r.admin_id = a.id
                WHERE r.status != 'pending'
                ORDER BY r.processed_at DESC
                LIMIT 50
            ");
            echo json_encode(['ok'=>true,'rows'=>$stmt->fetchAll()]);
            break;
        }

        case 'reset_approve': {
            $requestId = (int)($_POST['request_id'] ?? 0);
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("
                UPDATE password_reset_requests
                SET status = 'approved', token = ?, admin_id = ?, processed_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$token, $adminId, $requestId]);
            if ($stmt->rowCount() === 0) throw new Exception('Запрос уже обработан');

            $protocol = (!empty($_SERVER['HTTPS']) ? 'https' : 'http');
            $host = $_SERVER['HTTP_HOST'];
            $basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
            $link = "$protocol://$host$basePath/reset_password.php?token=$token";

            auditLog($pdo, 'admin.reset_approve', [
                'entity_type'=>'password_reset','entity_id'=>$requestId,
                'description'=>"Одобрен сброс пароля",
            ]);
            echo json_encode(['ok'=>true,'link'=>$link]);
            break;
        }

        case 'reset_reject': {
            $requestId = (int)($_POST['request_id'] ?? 0);
            $comment   = trim($_POST['comment'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE password_reset_requests
                SET status = 'rejected', admin_id = ?, admin_comment = ?, processed_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$adminId, $comment, $requestId]);

            auditLog($pdo, 'admin.reset_reject', [
                'entity_type'=>'password_reset','entity_id'=>$requestId,
                'description'=>"Отклонён сброс пароля".($comment ? ": {$comment}" : ''),
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        // ============================================================
        // ЖУРНАЛ
        // ============================================================
        case 'audit_list': {
            $search = trim($_POST['search'] ?? '');
            $actionFilter = trim($_POST['action_filter'] ?? '');
            $from = trim($_POST['from'] ?? date('Y-m-d', strtotime('-30 days')));
            $to   = trim($_POST['to']   ?? date('Y-m-d'));
            $page = max(1, (int)($_POST['page'] ?? 1));
            $perPage = max(20, min(200, (int)($_POST['per_page'] ?? 50)));

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d', strtotime('-30 days'));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

            $where  = ["DATE(a.created_at) BETWEEN ? AND ?"];
            $params = [$from, $to];

            if ($search !== '') {
                $where[] = "(a.user_name LIKE ? OR a.description LIKE ?)";
                $like = '%'.$search.'%';
                $params[] = $like; $params[] = $like;
            }
            if ($actionFilter !== '') {
                $where[] = "a.action = ?"; $params[] = $actionFilter;
            }
            $whereSql = implode(' AND ', $where);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log a WHERE $whereSql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            $pages = max(1, (int)ceil($total/$perPage));
            $page  = min($page, $pages);
            $offset = ($page-1)*$perPage;

            $stmt = $pdo->prepare("
                SELECT a.* FROM audit_log a
                WHERE $whereSql ORDER BY a.id DESC LIMIT $perPage OFFSET $offset
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $labels = auditActionLabels();
            $actionsStmt = $pdo->query("SELECT DISTINCT action FROM audit_log ORDER BY action");
            $actions = [];
            foreach ($actionsStmt->fetchAll() as $r) {
                $actions[] = [
                    'action' => $r['action'],
                    'label'  => $labels[$r['action']][0] ?? $r['action'],
                ];
            }

            echo json_encode([
                'ok'=>true,'rows'=>$rows,'total'=>$total,'pages'=>$pages,'page'=>$page,
                'actions'=>$actions,
            ]);
            break;
        }

        // ============================================================
        // СИСТЕМА
        // ============================================================
        case 'system_info': {
            $info = [
                'php_version'   => PHP_VERSION,
                'mysql_version' => $pdo->query("SELECT VERSION()")->fetchColumn(),
                'server'        => $_SERVER['SERVER_SOFTWARE'] ?? '—',
                'timezone'      => date_default_timezone_get(),
                'db_name'       => DB_NAME,
            ];

            try {
                $stmt = $pdo->prepare("
                    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                           COUNT(*) AS tables_count
                    FROM information_schema.TABLES WHERE table_schema = ?
                ");
                $stmt->execute([DB_NAME]);
                $db = $stmt->fetch();
                $info['db_size_mb'] = $db['size_mb'] ?? 0;
                $info['db_tables']  = $db['tables_count'] ?? 0;
            } catch (Throwable $e) {
                $info['db_size_mb'] = null;
                $info['db_tables']  = null;
            }

            $cacheFile = __DIR__ . '/../cache/departments.json';
            $info['departments_cache'] = is_file($cacheFile)
                ? date('d.m.Y H:i', filemtime($cacheFile)) : null;

            try {
                $tables = $pdo->query("SHOW TABLES LIKE 'site_messages'")->fetchAll();
                $info['has_messages_table'] = !empty($tables);
            } catch (Throwable $e) {
                $info['has_messages_table'] = false;
            }

            try {
                $tables = $pdo->query("SHOW TABLES LIKE 'site_settings'")->fetchAll();
                $info['has_settings_table'] = !empty($tables);
            } catch (Throwable $e) {
                $info['has_settings_table'] = false;
            }

            try {
                $tables = $pdo->query("SHOW TABLES LIKE 'achievements'")->fetchAll();
                $info['has_achievements_table'] = !empty($tables);
            } catch (Throwable $e) {
                $info['has_achievements_table'] = false;
            }

            echo json_encode(['ok'=>true,'info'=>$info]);
            break;
        }

        case 'rebuild_departments_cache': {
            $cacheFile = __DIR__ . '/../cache/departments.json';
            if (file_exists($cacheFile)) unlink($cacheFile);
            auditLog($pdo, 'admin.cache_clear', [
                'description' => "Очищен кэш справочника институтов/кафедр",
            ]);
            echo json_encode(['ok'=>true]);
            break;
        }

        // ============================================================
        // BACKUP
        // ============================================================
        case 'backup_download': {
            while (ob_get_level() > 0) ob_end_clean();

            $tables = [];
            $stmt = $pdo->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) $tables[] = $row[0];

            auditLog($pdo, 'admin.backup', [
                'description' => "Скачан дамп БД (".count($tables)." таблиц)",
            ]);

            $filename = 'backup_'.date('Y-m-d_H-i-s').'.sql';

            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Cache-Control: no-store');
            header('X-Accel-Buffering: no');

            $out = fopen('php://output', 'w');

            fwrite($out, "-- АИС-расписание.РФ — дамп БД\n");
            fwrite($out, "-- Дата: ".date('Y-m-d H:i:s')."\n");
            fwrite($out, "-- Таблиц: ".count($tables)."\n\n");
            fwrite($out, "SET NAMES utf8mb4;\n");
            fwrite($out, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
                $row  = $stmt->fetch(PDO::FETCH_NUM);
                fwrite($out, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($out, $row[1] . ";\n\n");

                $stmt = $pdo->query("SELECT * FROM `$table`");
                $batch = []; $batchSize = 500;

                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $cols = array_map(fn($c) => "`$c`", array_keys($r));
                    $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($r));
                    $batch[] = "INSERT INTO `$table` (".implode(',',$cols).") VALUES (".implode(',',$vals).");";
                    if (count($batch) >= $batchSize) {
                        fwrite($out, implode("\n",$batch)."\n");
                        $batch = [];
                    }
                }
                if (!empty($batch)) fwrite($out, implode("\n",$batch)."\n");
                fwrite($out, "\n");
            }

            fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fclose($out);
            exit;
        }

        default:
            throw new Exception('Неизвестное действие: '.$action);
    }

} catch (Throwable $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}