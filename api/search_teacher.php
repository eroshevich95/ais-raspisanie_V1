<?php
/**
 * Поиск преподавателя по фамилии.
 *
 * GET /api/search_teacher.php?last_name=Иванов
 *
 * Обращается к порталу КузГТУ:
 *   https://portal.kuzstu.ru/api/teachers?teacher=Иванов
 *
 * Формат ответа API:
 *   [
 *     { "person_id": "17544", "name": "Григорьева Татьяна Ивановна" },
 *     ...
 *   ]
 *
 * Порядок поиска:
 *   1) Локальный кэш teachers_cache (быстро)
 *   2) API портала КузГТУ
 *   3) Локальные users (зарегистрированные)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$lastName = trim($_GET['last_name'] ?? '');

if (mb_strlen($lastName) < 2) {
    echo json_encode([
        'error' => 'Введите минимум 2 символа фамилии',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$like    = '%' . $lastName . '%';
$results = [];
$seenIds = [];

/* ============================================================
   1. Локальный кэш teachers_cache
   ============================================================ */
try {
    $stmt = $pdo->prepare("
        SELECT api_teacher_id, full_name, last_name, first_name, middle_name,
               department, post, email
        FROM teachers_cache
        WHERE last_name LIKE ?
           OR full_name LIKE ?
        ORDER BY
            CASE WHEN last_name = ? THEN 0 ELSE 1 END,
            last_name, full_name
        LIMIT 100
    ");
    $stmt->execute([$like, $like, $lastName]);

    foreach ($stmt->fetchAll() as $r) {
        $id = (string)$r['api_teacher_id'];
        if (isset($seenIds[$id])) continue;
        $seenIds[$id] = true;

        $results[] = [
            'id'          => $id,
            'full_name'   => $r['full_name'],
            'last_name'   => $r['last_name'],
            'first_name'  => $r['first_name'],
            'middle_name' => $r['middle_name'],
            'department'  => $r['department'] ?? '',
            'post'        => $r['post'] ?? '',
            'email'       => $r['email'] ?? '',
            'source'      => 'cache',
        ];
    }
} catch (Throwable $e) {
    // Таблицы может не быть — идём дальше
}

/* ============================================================
   2. API портала КузГТУ: /api/teachers?teacher={search}
   ============================================================ */
if (empty($results)) {
    $apiUrl = 'https://portal.kuzstu.ru/api/teachers?teacher=' . urlencode($lastName);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_USERAGENT      => 'AIS-Raspisanie/1.0',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);

        if (is_array($data) && !empty($data) && !isset($data['error'])) {
            // Если API вернул одиночный объект — оборачиваем
            if (isset($data['person_id']) || isset($data['name'])) {
                $data = [$data];
            }

            foreach ($data as $item) {
                if (!is_array($item)) continue;

                // API отдаёт: person_id + name (полное ФИО)
                $id   = (string)($item['person_id'] ?? $item['id'] ?? '');
                $name = trim((string)($item['name'] ?? $item['full_name'] ?? ''));

                if ($id === '' || $name === '') continue;
                if (isset($seenIds[$id])) continue;
                $seenIds[$id] = true;

                // Разбираем ФИО: "Фамилия Имя Отчество"
                $parts = preg_split('/\s+/u', $name);
                $ln = $parts[0] ?? '';
                $fn = $parts[1] ?? '';
                $mn = $parts[2] ?? '';

                $results[] = [
                    'id'          => $id,
                    'full_name'   => $name,
                    'last_name'   => $ln,
                    'first_name'  => $fn,
                    'middle_name' => $mn,
                    'department'  => '',
                    'post'        => '',
                    'email'       => '',
                    'source'      => 'api',
                ];
            }
        }
    }
}

/* ============================================================
   3. Фолбэк — локальные users
   ============================================================ */
if (empty($results)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, full_name, department, api_teacher_id
            FROM users
            WHERE full_name LIKE ?
              AND is_active = 1
            ORDER BY full_name
            LIMIT 50
        ");
        $stmt->execute([$like]);

        foreach ($stmt->fetchAll() as $r) {
            $id = (string)($r['api_teacher_id'] ?: $r['id']);
            if (isset($seenIds[$id])) continue;
            $seenIds[$id] = true;

            $parts = preg_split('/\s+/u', trim($r['full_name']));

            $results[] = [
                'id'          => $id,
                'full_name'   => $r['full_name'],
                'last_name'   => $parts[0] ?? '',
                'first_name'  => $parts[1] ?? '',
                'middle_name' => $parts[2] ?? '',
                'department'  => $r['department'] ?? '',
                'post'        => '',
                'email'       => '',
                'source'      => 'users',
            ];
        }
    } catch (Throwable $e) {
        // ignore
    }
}

/* ============================================================
   Ответ
   ============================================================ */
if (empty($results)) {
    echo json_encode([
        'error' => 'Преподаватели с такой фамилией не найдены',
        'hint'  => 'Проверьте написание фамилии. Если вы точно есть на портале КузГТУ — обратитесь к администратору системы.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);