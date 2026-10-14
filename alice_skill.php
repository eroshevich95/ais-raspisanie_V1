<?php
/**
 * КузГТУшник — Навык для Яндекс.Алисы
 *
 * Сценарии:
 * 1. Привязка по коду из MAX-бота (быстрый путь)
 * 2. Регистрация с нуля (студент/преподаватель)
 * 3. Просмотр расписания на сегодня и на завтра
 * 4. Смена пользователя
 * 5. Удаление данных (отвязка аккаунта Яндекса)
 *
 * Использует общий users.json с MAX-ботом (bot.php).
 */

// ==========================================
// ОТЛАДКА (можно отключить позже)
// ==========================================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/alice_errors.log');

$rawInput = file_get_contents('php://input');

file_put_contents(
    __DIR__ . '/alice_debug.log',
    date('c') . " | METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? '?')
    . " | LEN=" . strlen($rawInput)
    . " | INPUT=" . substr($rawInput, 0, 3000) . "\n\n",
    FILE_APPEND
);

header('Content-Type: application/json; charset=utf-8');

// ==========================================
// КОНФИГУРАЦИЯ
// ==========================================
$KUZGTU_API = 'https://portal.kuzstu.ru/api';
$USERS_FILE = __DIR__ . '/users.json';

// ==========================================
// ЧТЕНИЕ ВХОДНЫХ ДАННЫХ
// ==========================================
$input = $rawInput;
$data = json_decode($input, true);

if (!$data || !isset($data['request'])) {
    echo json_encode([
        'version' => '1.0',
        'session' => $data['session'] ?? [],
        'response' => [
            'text' => 'Ошибка запроса. Попробуйте ещё раз.',
            'tts' => 'Ошибка запроса.',
            'end_session' => false
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$request = $data['request'];
$session = $data['session'];
$userId = $session['user']['user_id'] ?? null;
$command = $request['command'] ?? '';
$originalUtterance = $request['original_utterance'] ?? $command;
$isNewSession = $session['new'] ?? false;

if (!$userId) {
    echo json_encode([
        'version' => '1.0',
        'session' => $session,
        'response' => [
            'text' => 'Не удалось определить пользователя. Попробуйте ещё раз.',
            'tts' => 'Ошибка.',
            'end_session' => false
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================
// ХРАНИЛИЩЕ
// ==========================================

function getUser($userId) {
    global $USERS_FILE;
    if (!file_exists($USERS_FILE)) return null;
    $db = json_decode(file_get_contents($USERS_FILE), true) ?: [];
    return $db[$userId] ?? null;
}

function saveUser($userId, $data) {
    global $USERS_FILE;
    $fp = fopen($USERS_FILE, 'c+');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    $content = stream_get_contents($fp);
    $db = json_decode($content, true) ?: [];
    $db[$userId] = array_merge($db[$userId] ?? [], $data);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

/**
 * Полностью удаляет запись пользователя из users.json.
 */
function deleteUser($userId) {
    global $USERS_FILE;
    if (!file_exists($USERS_FILE)) return false;
    $fp = fopen($USERS_FILE, 'c+');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    $content = stream_get_contents($fp);
    $db = json_decode($content, true) ?: [];
    if (isset($db[$userId])) {
        unset($db[$userId]);
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

/**
 * Снимает флаг привязки у связанного MAX-пользователя.
 * Саму запись в MAX не удаляем — она продолжает жить в мессенджере.
 */
function unlinkFromMax($maxUserId) {
    global $USERS_FILE;
    $fp = fopen($USERS_FILE, 'c+');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    $content = stream_get_contents($fp);
    $db = json_decode($content, true) ?: [];
    if (isset($db[$maxUserId])) {
        $db[$maxUserId]['alice_linked'] = false;
        $db[$maxUserId]['alice_linked_at'] = null;
        // На всякий случай чистим и код привязки
        $db[$maxUserId]['alice_link_code'] = null;
        $db[$maxUserId]['alice_link_code_expires'] = null;
        $db[$maxUserId]['alice_link_code_created_at'] = null;
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function findByAliceCode($code) {
    global $USERS_FILE;
    if (!file_exists($USERS_FILE)) return null;
    $db = json_decode(file_get_contents($USERS_FILE), true) ?: [];
    foreach ($db as $maxUserId => $u) {
        if (!empty($u['alice_link_code']) && $u['alice_link_code'] === $code) {
            if (!empty($u['alice_link_code_expires']) && $u['alice_link_code_expires'] > time()) {
                return ['user' => $u, 'max_user_id' => $maxUserId];
            }
        }
    }
    return null;
}

function markAliceLinked($maxUserId) {
    global $USERS_FILE;
    $fp = fopen($USERS_FILE, 'c+');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    $content = stream_get_contents($fp);
    $db = json_decode($content, true) ?: [];
    if (isset($db[$maxUserId])) {
        $db[$maxUserId]['alice_link_code'] = null;
        $db[$maxUserId]['alice_link_code_expires'] = null;
        $db[$maxUserId]['alice_link_code_created_at'] = null;
        $db[$maxUserId]['alice_linked'] = true;
        $db[$maxUserId]['alice_linked_at'] = date('c');
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

// ==========================================
// API КУЗГТУ
// ==========================================

function fetchKuzgtuData($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200 || !$response) return null;
    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function findGroup($query) {
    global $KUZGTU_API;
    $data = fetchKuzgtuData($KUZGTU_API . '/group?group=' . urlencode($query));
    if (empty($data)) return null;
    return ['id' => $data[0]['dept_id'] ?? '', 'name' => $data[0]['name'] ?? $query];
}

function findTeachers($query) {
    global $KUZGTU_API;
    $data = fetchKuzgtuData($KUZGTU_API . '/teachers?teacher=' . urlencode($query));
    if (empty($data)) return [];
    $results = [];
    foreach ($data as $t) {
        $results[] = ['id' => $t['person_id'] ?? '', 'name' => $t['name'] ?? ''];
    }
    return $results;
}

// ==========================================
// УТИЛИТЫ
// ==========================================

function getLessonTime($num) {
    $schedule = [
        '1' => '9:00-10:35', '2' => '10:50-12:25', '3' => '13:20-14:55',
        '4' => '15:10-16:45', '5' => '17:00-18:35', '6' => '18:50-20:20'
    ];
    return $schedule[(string)$num] ?? 'время не указано';
}

function shortenType($type) {
    $t = mb_strtolower($type);
    if (mb_strpos($t, 'лек') !== false) return 'лекция';
    if (mb_strpos($t, 'прак') !== false) return 'практика';
    if (mb_strpos($t, 'лаб') !== false) return 'лабораторная';
    return $type ?: 'занятие';
}

function plural($n, $form1, $form2, $form5) {
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $form5;
    if ($n1 > 1 && $n1 < 5) return $form2;
    if ($n1 == 1) return $form1;
    return $form5;
}

function formatScheduleForAlice($data, $targetDate, $dayLabel) {
    $lessonsForDay = [];
    foreach ($data as $lesson) {
        if (isset($lesson['date_lesson']) && $lesson['date_lesson'] === $targetDate) {
            $lessonsForDay[] = $lesson;
        }
    }
    usort($lessonsForDay, fn($a, $b) => ($a['lesson_number'] ?? 0) - ($b['lesson_number'] ?? 0));

    if (empty($lessonsForDay)) {
        return "На " . mb_strtolower($dayLabel) . " пар нет. Отдыхай!";
    }

    $count = count($lessonsForDay);
    $text = "На " . mb_strtolower($dayLabel) . " {$count} " . plural($count, 'пара', 'пары', 'пар') . ". ";

    foreach ($lessonsForDay as $lesson) {
        $num = $lesson['lesson_number'] ?? '?';
        $time = getLessonTime($num);
        $subject = $lesson['subject'] ?? 'Не указан';
        $type = shortenType($lesson['type'] ?? '');
        $place = $lesson['place'] ?? '?';
        $teacher = $lesson['teacher_name'] ?? '';
        $group = $lesson['education_group_name'] ?? '';

        $text .= "Пара {$num}, {$time}. ";
        $text .= "{$subject}, {$type}. ";
        $text .= "Аудитория {$place}. ";
        if ($teacher) $text .= "Преподаватель {$teacher}. ";
        if ($group) $text .= "Группа {$group}. ";
    }

    return trim($text);
}

// ==========================================
// ОТВЕТ АЛИСЕ
// ==========================================

function reply($text, $endSession = false, $buttons = null, $tts = null) {
    global $data;
    $response = [
        'text' => $text,
        'tts' => $tts ?? $text,
        'end_session' => $endSession,
    ];
    if ($buttons !== null) {
        $response['buttons'] = $buttons;
    }
    $payload = [
        'version' => $data['version'] ?? '1.0',
        'session' => $data['session'],
        'response' => $response
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = json_encode([
            'version' => '1.0',
            'session' => $data['session'],
            'response' => [
                'text' => 'Произошла ошибка. Попробуйте ещё раз.',
                'tts' => 'Произошла ошибка.',
                'end_session' => false,
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
    echo $json;
    exit;
}

// ==========================================
// ОСНОВНАЯ ЛОГИКА
// ==========================================

$user = getUser($userId);
$aliceState = $user['alice_state'] ?? 'start';

$lower = mb_strtolower(trim($originalUtterance));

// ============================================================
// 1. УДАЛЕНИЕ ДАННЫХ (отвязка) — проверяем ПЕРВЫМ делом
//    Работает в любой момент, из любого состояния.
// ============================================================
$wantDelete = (
    mb_strpos($lower, 'удали') !== false
    || mb_strpos($lower, 'отвяз') !== false
    || mb_strpos($lower, 'отвязать') !== false
    || mb_strpos($lower, 'стереть') !== false
    || mb_strpos($lower, 'забудь меня') !== false
    || mb_strpos($lower, 'удали мои данные') !== false
    || mb_strpos($lower, 'удалить данные') !== false
    || mb_strpos($lower, 'отключи') !== false
);

// Ловим ещё случай, когда пришло подтверждение "да/подтверждаю"
// в состоянии ожидания подтверждения удаления
if ($aliceState === 'confirm_delete') {
    if (mb_strpos($lower, 'да') !== false
        || mb_strpos($lower, 'подтвержда') !== false
        || mb_strpos($lower, 'удали') !== false
        || mb_strpos($lower, 'верно') !== false) {
        // Пользователь подтвердил — удаляем
        $linkedMaxId = $user['linked_max_user_id'] ?? null;

        // Снимаем флаг у MAX-записи, чтобы MAX-бот показал «не привязана»
        if ($linkedMaxId) {
            unlinkFromMax($linkedMaxId);
        }

        // Удаляем запись Алисы из users.json полностью
        deleteUser($userId);

        reply(
            "Готово. Я удалил все твои данные. Алиса больше не знает твоего расписания. Если захочешь вернуться — просто скажи \"зарегистрироваться\".",
            true,
            [
                ['title' => 'Зарегистрироваться', 'hide' => true],
                ['title' => 'Помощь', 'hide' => true]
            ]
        );
    }
    if (mb_strpos($lower, 'нет') !== false
        || mb_strpos($lower, 'отмена') !== false
        || mb_strpos($lower, 'не надо') !== false) {
        // Отменяем — возвращаемся в ready
        saveUser($userId, ['alice_state' => 'ready']);
        reply(
            "Хорошо, ничего не удаляю. Скажи \"расписание на сегодня\" или \"расписание на завтра\".",
            false,
            [
                ['title' => 'Расписание на сегодня', 'hide' => true],
                ['title' => 'Расписание на завтра', 'hide' => true],
                ['title' => 'Помощь', 'hide' => true]
            ]
        );
    }
    // Не поняли ответ — уточняем ещё раз
    reply(
        "Пожалуйста, ответь \"да\" — чтобы удалить данные, или \"нет\" — чтобы отменить.",
        false,
        [
            ['title' => 'Да, удалить', 'hide' => true],
            ['title' => 'Нет, отмена', 'hide' => true]
        ]
    );
}

// Первый запуск интента удаления — спрашиваем подтверждение
if ($wantDelete) {
    // Если пользователя в базе вообще нет — сообщаем, что нечего удалять
    if (!$user) {
        reply(
            "У меня нет твоих данных. Если захочешь начать — скажи \"зарегистрироваться\".",
            true,
            [['title' => 'Зарегистрироваться', 'hide' => true]]
        );
    }

    saveUser($userId, ['alice_state' => 'confirm_delete']);
    $who = '';
    if (!empty($user['role']) && $user['role'] === 'student' && !empty($user['group_name'])) {
        $who = " (студент группы {$user['group_name']})";
    } elseif (!empty($user['role']) && $user['role'] === 'teacher' && !empty($user['teacher_name'])) {
        $who = " ({$user['teacher_name']})";
    }

    reply(
        "Точно удалить все твои данные{$who}? Я забуду твоё расписание, а в MAX-боте привязка к Алисе снимется. Скажи \"да\" для подтверждения или \"нет\" для отмены.",
        false,
        [
            ['title' => 'Да, удалить', 'hide' => true],
            ['title' => 'Нет, отмена', 'hide' => true]
        ]
    );
}

// ----- Приветствие / начало сессии -----
if ($isNewSession && empty($originalUtterance)) {
    if ($user && !empty($user['registered']) && !empty($user['role']) && $user['role'] !== 'guest') {
        $who = ($user['role'] === 'student')
            ? "группы {$user['group_name']}"
            : "{$user['teacher_name']}";
        reply(
            "С возвращением! Я помню тебя как {$who}. Спроси \"расписание на сегодня\" или \"расписание на завтра\", либо скажи \"сменить пользователя\".",
            false,
            [
                ['title' => 'Расписание на сегодня', 'hide' => true],
                ['title' => 'Расписание на завтра', 'hide' => true],
                ['title' => 'Сменить пользователя', 'hide' => true],
                ['title' => 'Помощь', 'hide' => true]
            ]
        );
    }

    saveUser($userId, ['alice_state' => 'choose_method']);
    $text = "Привет! Я КузГТУшник — помогу узнать расписание. "
          . "Скажи \"у меня есть код\" — если ты уже пользуешься ботом в MAX. "
          . "Или скажи \"зарегистрироваться\" — если ты здесь впервые.";
    reply($text, false, [
        ['title' => 'У меня есть код', 'hide' => true],
        ['title' => 'Зарегистрироваться', 'hide' => true],
        ['title' => 'Помощь', 'hide' => true]
    ]);
}

// ----- Помощь в любой момент -----
if ($lower === 'помощь' || $lower === 'что ты умеешь' || mb_strpos($lower, 'помоги') !== false) {
    $text = "Я умею рассказывать расписание на сегодня и на завтра. Скажи \"расписание на сегодня\" или \"расписание на завтра\" — и я перечислю пары. "
          . "Ещё могу сменить пользователя — скажи \"сменить пользователя\". "
          . "А если ты пользовался ботом в MAX — скажи \"у меня есть код\" и назови код из шести цифр. "
          . "И если захочешь — скажи \"удали мои данные\".";
    reply($text, false, [
        ['title' => 'Расписание на сегодня', 'hide' => true],
        ['title' => 'Расписание на завтра', 'hide' => true],
        ['title' => 'Сменить пользователя', 'hide' => true],
        ['title' => 'Удалить данные', 'hide' => true]
    ]);
}

// ----- Смена пользователя (в любой момент) -----
if (mb_strpos($lower, 'сменить') !== false
    || mb_strpos($lower, 'выход') !== false
    || mb_strpos($lower, 'другой пользователь') !== false) {
    saveUser($userId, [
        'alice_state' => 'choose_method',
        'role' => null,
        'group_id' => null,
        'group_name' => null,
        'teacher_id' => null,
        'teacher_name' => null,
        'registered' => false,
    ]);
    reply(
        "Хорошо. Скажи \"у меня есть код\" — если у тебя есть код из MAX-бота. Или \"зарегистрироваться\" — если хочешь с нуля."
    );
}

// ----- Выбор способа: код или с нуля -----
if ($aliceState === 'choose_method') {
    if (mb_strpos($lower, 'код') !== false
        || mb_strpos($lower, 'есть') !== false
        || mb_strpos($lower, 'макс') !== false
        || mb_strpos($lower, 'max') !== false) {
        saveUser($userId, ['alice_state' => 'ask_link_code']);
        reply("Отлично! Назови шестизначный код, который тебе прислал бот в MAX. Например: один-два-три, четыре-пять-шесть.");
    }
    if (mb_strpos($lower, 'зарегистр') !== false
        || mb_strpos($lower, 'впервые') !== false
        || mb_strpos($lower, 'с нуля') !== false
        || mb_strpos($lower, 'нет') !== false) {
        saveUser($userId, ['alice_state' => 'ask_role']);
        reply("Хорошо, зарегистрируемся с нуля. Скажи: ты студент или преподаватель?");
    }
    if (mb_strpos($lower, 'расписание') !== false) {
        reply("Сначала нужно зарегистрироваться. Скажи \"у меня есть код\" или \"зарегистрироваться\".");
    }
    reply("Не понял. Скажи \"у меня есть код\" или \"зарегистрироваться\".");
}

// ----- Привязка по коду -----
if ($aliceState === 'ask_link_code') {
    $digits = preg_replace('/[^0-9]/', '', $originalUtterance);

    if (strlen($digits) < 6) {
        $wordMap = [
            'ноль' => '0', 'один' => '1', 'два' => '2', 'три' => '3', 'четыре' => '4',
            'пять' => '5', 'шесть' => '6', 'семь' => '7', 'восемь' => '8', 'девять' => '9',
        ];
        $spoken = mb_strtolower($originalUtterance);
        $replaced = strtr($spoken, $wordMap);
        $digits = preg_replace('/[^0-9]/', '', $replaced);
    }

    if (strlen($digits) !== 6) {
        reply("Это не похоже на код. Нужно ровно шесть цифр. Попробуй ещё раз, назови по одной цифре: например, один-два-три, четыре-пять-шесть.");
    }

    $found = findByAliceCode($digits);
    if (!$found) {
        reply(
            "Такой код не найден или он уже истёк. Возьми новый код в MAX-боте — он действует 15 минут.",
            false,
            [['title' => 'Зарегистрироваться', 'hide' => true]]
        );
    }

    $maxUser = $found['user'];
    $maxUserId = $found['max_user_id'];

    saveUser($userId, [
        'alice_state' => 'ready',
        'role' => $maxUser['role'] ?? null,
        'group_id' => $maxUser['group_id'] ?? null,
        'group_name' => $maxUser['group_name'] ?? null,
        'teacher_id' => $maxUser['teacher_id'] ?? null,
        'teacher_name' => $maxUser['teacher_name'] ?? null,
        'registered' => true,
        'linked_max_user_id' => $maxUserId,
        'linked_at' => date('c'),
    ]);

    markAliceLinked($maxUserId);

    $who = ($maxUser['role'] === 'student')
        ? "студент группы {$maxUser['group_name']}"
        : "{$maxUser['teacher_name']}";

    reply(
        "Готово! Я привязал твой профиль. Теперь ты — {$who}. Скажи \"расписание на сегодня\" или \"расписание на завтра\".",
        false,
        [
            ['title' => 'Расписание на сегодня', 'hide' => true],
            ['title' => 'Расписание на завтра', 'hide' => true],
            ['title' => 'Помощь', 'hide' => true]
        ]
    );
}

// ----- Регистрация с нуля: роль -----
if ($aliceState === 'ask_role') {
    if (mb_strpos($lower, 'студент') !== false) {
        saveUser($userId, ['alice_state' => 'ask_group', 'role_pending' => 'student']);
        reply("Отлично! Назови номер своей группы. Например: ИСт-241, РУт-262.");
    }
    if (mb_strpos($lower, 'препод') !== false || mb_strpos($lower, 'учител') !== false) {
        saveUser($userId, ['alice_state' => 'ask_surname', 'role_pending' => 'teacher']);
        reply("Понял! Назови свою фамилию. Например: Иванов.");
    }
    reply("Не понял. Скажи \"студент\" или \"преподаватель\".");
}

// ----- Студент называет группу -----
if ($aliceState === 'ask_group') {
    $groupName = trim(preg_replace('/(группа|моя|я из|учусь в)/iu', '', $originalUtterance));
    $groupName = trim($groupName);

    if (mb_strlen($groupName) < 3) {
        reply("Не расслышал название группы. Назови, например: ИСт-241.");
    }

    $group = findGroup($groupName);
    if (!$group || empty($group['id'])) {
        reply("Группа {$groupName} не найдена. Попробуй ещё раз, произнеси чётко.");
    }

    saveUser($userId, [
        'alice_state' => 'ready',
        'role' => 'student',
        'group_id' => $group['id'],
        'group_name' => $group['name'],
        'registered' => true,
        'role_pending' => null,
    ]);

    reply(
        "Готово! Ты зарегистрирован как студент группы {$group['name']}. Скажи \"расписание на сегодня\" или \"расписание на завтра\".",
        false,
        [
            ['title' => 'Расписание на сегодня', 'hide' => true],
            ['title' => 'Расписание на завтра', 'hide' => true],
            ['title' => 'Помощь', 'hide' => true]
        ]
    );
}

// ----- Преподаватель называет фамилию -----
if ($aliceState === 'ask_surname') {
    $surname = trim(preg_replace('/(преподаватель|я|моя фамилия|фамилия)/iu', '', $originalUtterance));
    $surname = trim($surname);

    if (mb_strlen($surname) < 2) {
        reply("Не расслышал фамилию. Назови, например: Иванов.");
    }

    $teachers = findTeachers($surname);
    if (empty($teachers)) {
        reply("Преподаватели с фамилией {$surname} не найдены. Попробуй ещё раз.");
    }

    $teachers = array_slice($teachers, 0, 5);

    $listText = "";
    foreach ($teachers as $i => $t) {
        $num = $i + 1;
        $listText .= "{$num}. {$t['name']}. ";
    }

    $count = count($teachers);
    $text = "Нашёл {$count} " . plural($count, 'человека', 'человек', 'человек')
          . ". {$listText} Скажи номер того, кто ты.";

    saveUser($userId, [
        'alice_state' => 'choose_teacher',
        'alice_teachers' => $teachers,
        'role_pending' => 'teacher',
    ]);

    reply($text);
}

// ----- Преподаватель выбирает номер -----
if ($aliceState === 'choose_teacher') {
    $num = (int)preg_replace('/[^0-9]/', '', $originalUtterance);

    if ($num === 0) {
        $wordNums = [
            'первый' => 1, 'второй' => 2, 'третий' => 3,
            'четвёртый' => 4, 'четвертый' => 4, 'пятый' => 5
        ];
        foreach ($wordNums as $word => $n) {
            if (mb_strpos($lower, $word) !== false) {
                $num = $n;
                break;
            }
        }
    }

    $teachers = $user['alice_teachers'] ?? [];
    if ($num < 1 || $num > count($teachers)) {
        reply("Не понял номер. Скажи число от 1 до " . count($teachers) . ".");
    }

    $chosen = $teachers[$num - 1];

    saveUser($userId, [
        'alice_state' => 'ready',
        'role' => 'teacher',
        'teacher_id' => $chosen['id'],
        'teacher_name' => $chosen['name'],
        'registered' => true,
        'alice_teachers' => null,
        'role_pending' => null,
    ]);

    reply(
        "Готово! Ты зарегистрирован как {$chosen['name']}. Скажи \"расписание на сегодня\" или \"расписание на завтра\".",
        false,
        [
            ['title' => 'Расписание на сегодня', 'hide' => true],
            ['title' => 'Расписание на завтра', 'hide' => true],
            ['title' => 'Помощь', 'hide' => true]
        ]
    );
}

// ----- Запрос расписания (сегодня / завтра) -----
$wantToday = (mb_strpos($lower, 'сегодня') !== false);
$wantTomorrow = (mb_strpos($lower, 'завтра') !== false);
$wantSchedule = (mb_strpos($lower, 'расписание') !== false
    || mb_strpos($lower, 'пары') !== false
    || mb_strpos($lower, 'пара') !== false);

if ($wantToday || $wantTomorrow || $wantSchedule) {

    if (!$user || empty($user['registered']) || empty($user['role']) || $user['role'] === 'guest') {
        saveUser($userId, ['alice_state' => 'choose_method']);
        reply("Сначала нужно зарегистрироваться. Скажи \"у меня есть код\" или \"зарегистрироваться\".");
    }

    if ($wantToday) {
        $targetDate = date('Y-m-d');
        $dayLabel = 'Сегодня';
    } else {
        $targetDate = date('Y-m-d', strtotime('+1 day'));
        $dayLabel = 'Завтра';
    }

    $role = $user['role'];

    if ($role === 'student' && !empty($user['group_id'])) {
        $data = fetchKuzgtuData($KUZGTU_API . '/student_schedule?group_id=' . urlencode($user['group_id']));
    } elseif ($role === 'teacher' && !empty($user['teacher_id'])) {
        $data = fetchKuzgtuData($KUZGTU_API . '/teacher_schedule?teacher_id=' . urlencode($user['teacher_id']));
    } else {
        reply("Не удалось определить, кто ты. Скажи \"сменить пользователя\" и зарегистрируйся заново.");
    }

    if ($data === null) {
        reply("Не удалось получить расписание с портала КузГТУ. Попробуй через пару минут.");
    }

    $text = formatScheduleForAlice($data, $targetDate, $dayLabel);
    reply($text, false, [
        ['title' => 'Расписание на сегодня', 'hide' => true],
        ['title' => 'Расписание на завтра', 'hide' => true],
        ['title' => 'Сменить пользователя', 'hide' => true],
        ['title' => 'Удалить данные', 'hide' => true],
        ['title' => 'Помощь', 'hide' => true]
    ]);
}

// ----- Fallback -----
reply(
    "Не понял. Попробуй сказать \"расписание на сегодня\", \"расписание на завтра\", \"удали мои данные\" или \"помощь\".",
    false,
    [
        ['title' => 'Расписание на сегодня', 'hide' => true],
        ['title' => 'Расписание на завтра', 'hide' => true],
        ['title' => 'Удалить данные', 'hide' => true],
        ['title' => 'Помощь', 'hide' => true]
    ]
);