<?php
/**
 * config.php — настройки проекта.
 *
 * Значения рассчитаны на стандартную установку XAMPP: MySQL на localhost,
 * пользователь root без пароля. Если в вашей сборке иначе — правьте здесь,
 * больше нигде параметры подключения не дублируются.
 */

declare(strict_types=1);

/*
 * Мини-загрузчик .env — без сборщика и без Composer.
 * PHP не читает .env сам по себе: getenv() видит только то, что реально
 * задано в окружении процесса. Здесь просто читаем файл (если он есть)
 * и раскладываем его строки через putenv(), после чего getenv() ниже
 * находит их как обычно. Сам .env в git не попадает (см. .gitignore).
 */
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}
unset($envFile, $line, $key, $value);

// --- База данных ------------------------------------------------------------
// Каждое значение можно переопределить переменной окружения — удобно, если
// проект развёрнут не на локальном XAMPP, а на реальном хостинге со своими
// логином/паролем к MySQL: там правится только окружение, а не файл в git.
define('DB_HOST',    getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT',    (int) (getenv('DB_PORT') ?: 3306));
define('DB_NAME',    getenv('DB_NAME') ?: 'partyprak');
define('DB_USER',    getenv('DB_USER') ?: 'root');
define('DB_PASS',    getenv('DB_PASS') ?: '');
const DB_CHARSET = 'utf8mb4';

// --- Админ-панель ---------------------------------------------------------
/*
 * Пароль открытым текстом и виден в коде — намеренно, это пет-проект
 * для портфолио, а не боевой сервис. hash_equals() ниже (admin/auth.php)
 * всё равно сравнивает его постоянным временем — не потому что пароль
 * секретный, а чтобы показать сам приём защиты от перебора по таймингу.
 *
 * Если понадобится настоящий хеш — переход на нормальную схему занимает
 * две правки: сюда положить password_hash('пароль', PASSWORD_DEFAULT),
 * в admin_check_password() (admin/auth.php) заменить hash_equals на
 * password_verify.
 */
const ADMIN_PASSWORD = '123';

/** Сколько секунд бездействия до автоматического выхода из админки. */
const ADMIN_IDLE_TIMEOUT = 1800;

// --- Отладка --------------------------------------------------------------
/**
 * true — показывать текст ошибок на экране (удобно при разработке).
 * На публичном сервере лучше выключить (APP_DEBUG=0 в окружении) — текст
 * ошибки PDO иногда попадает в него часть SQL-запроса.
 */
define('APP_DEBUG', getenv('APP_DEBUG') !== false ? getenv('APP_DEBUG') === '1' : true);

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

/** Единая точка старта сессии: с одинаковыми параметрами на всех страницах. */
function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
