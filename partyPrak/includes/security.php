<?php
/**
 * security.php — заголовки ответа и CSRF-токен для публичных форм.
 *
 * Токен для admin/ живёт в admin/auth.php — там своя сессия и свой набор
 * функций. Здесь ровно то же самое, но для форм на самом сайте (заявка
 * с главной, оформление заказа), которые тоже меняют состояние (пишут в БД)
 * и должны быть защищены от межсайтовой подделки запроса.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Базовые заголовки безопасности. Вызывается один раз в начале каждой
 * страницы, до любого вывода.
 */
function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    /*
     * CSP оставляет 'unsafe-inline' для style: по шаблонам рассыпаны
     * точечные style="..." (второстепенные отступы), переписывать их все
     * в классы — риск не по размеру задачи. Зато явно перечисленный
     * script-src/connect-src уже не даёт встроить и выполнить чужой скрипт
     * или тихо увести данные на сторонний домен, а это и есть основной
     * практический эффект CSP против XSS.
     */
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "script-src 'self'; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "font-src https://fonts.gstatic.com; " .
        "img-src 'self' data:; " .
        "connect-src 'self'; " .
        "base-uri 'self'; " .
        "form-action 'self'; " .
        "frame-ancestors 'none'"
    );
}

/** CSRF-токен публичных форм сайта. */
function site_csrf_token(): string
{
    app_start_session();
    if (empty($_SESSION['site_csrf'])) {
        $_SESSION['site_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['site_csrf'];
}

/**
 * Проверяет CSRF-токен публичной формы.
 * @return bool true — токен верный
 */
function site_csrf_valid(array $source): bool
{
    app_start_session();
    $sent = (string) ($source['csrf'] ?? '');
    return $sent !== '' && !empty($_SESSION['site_csrf']) && hash_equals($_SESSION['site_csrf'], $sent);
}
