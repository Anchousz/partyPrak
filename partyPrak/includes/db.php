<?php
/**
 * db.php — единственное место, где создаётся соединение с MySQL.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Возвращает общее PDO-соединение (создаётся один раз за запрос).
 *
 * @param bool $withDatabase false — подключиться к серверу без выбора базы
 *                           (нужно установщику, который эту базу и создаёт)
 */
function db(bool $withDatabase = true): PDO
{
    static $connections = [];
    $key = $withDatabase ? 'main' : 'server';

    if (isset($connections[$key])) {
        return $connections[$key];
    }

    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
    if ($withDatabase) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    }

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            // Исключения вместо молчаливых false — ошибку видно сразу.
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Настоящие подготовленные выражения, а не эмуляция на стороне PHP.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        db_fail($e, $withDatabase);
    }

    $connections[$key] = $pdo;
    return $pdo;
}

/**
 * Показывает понятное объяснение вместо сырого стектрейса PDO:
 * почти всегда причина — не запущен MySQL или не выполнен install.php.
 */
function db_fail(PDOException $e, bool $withDatabase): void
{
    $isUnknownDatabase = str_contains($e->getMessage(), 'Unknown database');

    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');

    $hint = $isUnknownDatabase && $withDatabase
        ? 'База <code>' . DB_NAME . '</code> ещё не создана. Откройте <a href="install.php">install.php</a> — он создаст её и наполнит данными.'
        : 'Проверьте, что в панели XAMPP запущен модуль <strong>MySQL</strong>, а логин и пароль в <code>config.php</code> совпадают с вашими.';

    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8">'
       . '<title>Нет соединения с базой</title>'
       . '<style>body{font:16px/1.6 system-ui,sans-serif;max-width:40rem;margin:4rem auto;padding:0 1.5rem;color:#2b0a45}'
       . 'code{background:#fff2e4;padding:.1em .4em;border-radius:4px}'
       . 'a{color:#c2154f}.err{background:#ffe4e9;padding:1rem;border-radius:12px;font-size:14px;margin-top:1.5rem}</style>'
       . '</head><body>'
       . '<h1>Не удалось подключиться к базе данных</h1>'
       . '<p>' . $hint . '</p>';

    if (APP_DEBUG) {
        echo '<p class="err"><strong>Ответ MySQL:</strong><br>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    }

    echo '</body></html>';
    exit;
}
