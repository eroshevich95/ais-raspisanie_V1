<?php
/**
 * Обёртка над API портала КузГТУ.
 * Документация: https://portal.kuzstu.ru/api
 */

if (!function_exists('kuzstuApiRequest')) {
    /**
     * Универсальный GET к порталу КузГТУ.
     */
    function kuzstuApiRequest(string $path, array $params = [], int $timeout = 15): ?array {
        $url = KUZSTU_API_URL . $path;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_USERAGENT      => 'AIS-Raspisanie/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return null;

        $json = json_decode($response, true);
        return is_array($json) ? $json : null;
    }
}

if (!function_exists('kuzstuFindGroupApiId')) {
    /**
     * Ищет api_group_id по названию группы.
     * Возвращает строку ID (например "6902") или null.
     */
    function kuzstuFindGroupApiId(string $groupName): ?string {
        $data = kuzstuApiRequest('/group', ['group' => $groupName]);
        if (empty($data) || !is_array($data)) return null;

        $first = $data[0] ?? null;
        if (!$first) return null;

        return (string)($first['dept_id'] ?? $first['id'] ?? '');
    }
}

if (!function_exists('kuzstuFetchGroupSchedule')) {
    /**
     * Возвращает расписание группы за указанный период из API (без кэша).
     */
    function kuzstuFetchGroupSchedule(string $apiGroupId, string $from, string $to): array {
        $data = kuzstuApiRequest('/student_schedule', ['group_id' => $apiGroupId]);
        if (empty($data) || !is_array($data)) return [];

        $fromTs = strtotime($from);
        $toTs   = strtotime($to);

        $filtered = [];
        foreach ($data as $item) {
            $date = $item['date_lesson'] ?? null;
            if (!$date) continue;
            $ts = strtotime($date);
            if ($ts < $fromTs || $ts > $toTs) continue;
            $filtered[] = $item;
        }

        usort($filtered, function($a, $b) {
            $d = strcmp($a['date_lesson'] ?? '', $b['date_lesson'] ?? '');
            if ($d !== 0) return $d;
            return ((int)($a['lesson_number'] ?? 0)) <=> ((int)($b['lesson_number'] ?? 0));
        });

        return $filtered;
    }
}

if (!function_exists('kuzstuFetchGroupScheduleCached')) {
    /**
     * То же, что kuzstuFetchGroupSchedule, но с кэшем на 30 минут.
     */
    function kuzstuFetchGroupScheduleCached(string $apiGroupId, string $from, string $to): array {
        $cacheDir  = __DIR__ . '/../cache';
        $cacheFile = $cacheDir . '/schedule_' . preg_replace('/[^a-zA-Z0-9_]/', '', $apiGroupId)
                     . '_' . $from . '_' . $to . '.json';
        $ttl = 1800;

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached)) return $cached;
        }

        $data = kuzstuFetchGroupSchedule($apiGroupId, $from, $to);

        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
        @file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE));

        return $data;
    }
}

if (!function_exists('kuzstuSyncGroupApiId')) {
    /**
     * Синхронизирует api_group_id у группы с порталом КузГТУ.
     * Возвращает строку api_group_id или null.
     */
    function kuzstuSyncGroupApiId(PDO $pdo, int $groupId, string $groupName): ?string {
        $stmt = $pdo->prepare("SELECT api_group_id FROM groups_table WHERE id = ?");
        $stmt->execute([$groupId]);
        $current = $stmt->fetchColumn();
        if (!empty($current)) return (string)$current;

        $apiId = kuzstuFindGroupApiId($groupName);
        if (!$apiId) return null;

        $pdo->prepare("UPDATE groups_table SET api_group_id = ? WHERE id = ?")
            ->execute([$apiId, $groupId]);

        return $apiId;
    }
}

if (!function_exists('mapKuzstuTypeToInternal')) {
    /**
     * Преобразует тип занятия с портала КузГТУ во внутренний.
     * "пр. зан." → practice, "лек." → lecture, "лаб." → lab.
     */
    function mapKuzstuTypeToInternal(string $type): string {
        $t = mb_strtolower(trim($type));

        if ($t === '') return 'other';

        if (mb_strpos($t, 'лек') !== false) return 'lecture';
        if (mb_strpos($t, 'лаб') !== false) return 'lab';
        if (mb_strpos($t, 'пр')  !== false) return 'practice';
        if (mb_strpos($t, 'сем') !== false) return 'seminar';

        return 'other';
    }
}