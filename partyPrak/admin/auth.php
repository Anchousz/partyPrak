<?php
/**
 * auth.php — вход в админку и защита её страниц.
 *
 * Панель нигде не залинкована и открывается только по прямому адресу
 * /admin/. Дополнительно закрыта паролем и помечена noindex.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Проверка пароля. Вынесена отдельно, чтобы переход на хеш
 * затрагивал ровно одну строку (см. комментарий в config.php).
 */
function admin_check_password(string $input): bool
{
    // Сравнение постоянного времени: не даёт подобрать пароль по задержке ответа.
    return hash_equals(ADMIN_PASSWORD, $input);
}

/** Вошёл ли пользователь и не истекла ли сессия по бездействию. */
function admin_is_logged_in(): bool
{
    app_start_session();

    if (empty($_SESSION['admin_ok'])) {
        return false;
    }

    $last = $_SESSION['admin_seen'] ?? 0;
    if (time() - (int) $last > ADMIN_IDLE_TIMEOUT) {
        admin_logout();
        return false;
    }

    $_SESSION['admin_seen'] = time();
    return true;
}

/** Отмечает вход в панель. */
function admin_login(): void
{
    app_start_session();
    // Меняем идентификатор сессии — защита от session fixation.
    session_regenerate_id(true);
    $_SESSION['admin_ok']   = true;
    $_SESSION['admin_seen'] = time();
}

/** Выход. */
function admin_logout(): void
{
    app_start_session();
    $_SESSION = [];
    session_destroy();
}

/** Ставится в начало каждой защищённой страницы. */
function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * CSRF-токен: без него чужой сайт мог бы отправить форму от имени
 * залогиненного менеджера.
 */
function admin_csrf_token(): string
{
    app_start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Проверяет токен у POST-запроса; при несовпадении завершает работу. */
function admin_verify_csrf(): void
{
    app_start_session();
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(400);
        exit('Неверный CSRF-токен. Обновите страницу и повторите.');
    }
}
