<?php
/**
 * validate.php — правила проверки полей форм.
 *
 * Каждая validate_* функция возвращает либо ['value' => ..., 'error' => null],
 * либо ['value' => исходная строка, 'error' => 'текст ошибки']. Такой контракт
 * позволяет показать инвалидное значение обратно в поле и подсветить именно
 * его, а не выводить общий список ошибок сверху страницы.
 */

declare(strict_types=1);

/** Имя: 2–160 символов, буквы (в т.ч. кириллица), пробелы, дефис и апостроф. */
function validate_name(string $raw): array
{
    $value = trim($raw);
    if ($value === '') {
        return ['value' => $value, 'error' => 'Укажите имя.'];
    }
    if (mb_strlen($value) < 2 || mb_strlen($value) > 160) {
        return ['value' => $value, 'error' => 'Имя должно быть от 2 до 160 символов.'];
    }
    if (!preg_match('/^[\p{L}\s\'\-]+$/u', $value)) {
        return ['value' => $value, 'error' => 'Имя может содержать только буквы, пробел и дефис.'];
    }
    return ['value' => $value, 'error' => null];
}

/**
 * Телефон: допускаем цифры, пробелы, +, -, (), от 10 до 18 символов.
 * Возвращаем нормализованную форму (только + и цифры) для хранения.
 */
function validate_phone(string $raw): array
{
    $value = trim($raw);
    if ($value === '') {
        return ['value' => $value, 'error' => 'Укажите телефон.'];
    }
    if (!preg_match('/^[0-9+\s()\-]{10,18}$/', $value)) {
        return ['value' => $value, 'error' => 'Телефон указан некорректно, пример: +7 999 000-00-00.'];
    }
    $digits = preg_replace('/[^0-9+]/', '', $value);
    $bareDigits = preg_replace('/\D/', '', $digits);
    if (strlen($bareDigits) < 10 || strlen($bareDigits) > 15) {
        return ['value' => $value, 'error' => 'В телефоне должно быть от 10 до 15 цифр.'];
    }
    return ['value' => $digits, 'error' => null];
}

/** Email: формат + разумная длина. */
function validate_email_field(string $raw, bool $required = true): array
{
    $value = trim($raw);
    if ($value === '') {
        return ['value' => $value, 'error' => $required ? 'Укажите email.' : null];
    }
    if (mb_strlen($value) > 160 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return ['value' => $value, 'error' => 'Укажите корректный email, пример: name@example.com.'];
    }
    return ['value' => $value, 'error' => null];
}

/**
 * Дата праздника: обязана быть в будущем и не дальше двух лет —
 * дальний срок почти наверняка означает ошибку в поле, а не реальный заказ.
 */
function validate_event_date(string $raw): array
{
    if ($raw === '') {
        return ['value' => $raw, 'error' => 'Укажите дату праздника.'];
    }
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
    if (!$date || $date->format('Y-m-d') !== $raw) {
        return ['value' => $raw, 'error' => 'Дата указана некорректно.'];
    }
    if ($raw < today()) {
        return ['value' => $raw, 'error' => 'Дата праздника не может быть в прошлом.'];
    }
    $maxDate = (new DateTimeImmutable('today'))->modify('+2 years')->format('Y-m-d');
    if ($raw > $maxDate) {
        return ['value' => $raw, 'error' => 'Дата слишком далёкая — укажите не более чем через два года.'];
    }
    return ['value' => $raw, 'error' => null];
}

/** Свободный текст (комментарий): просто ограничиваем длину, содержимое экранируется при выводе. */
function validate_comment(string $raw, int $maxLength = 1000): array
{
    $value = trim($raw);
    if (mb_strlen($value) > $maxLength) {
        return ['value' => $value, 'error' => "Комментарий длиннее $maxLength символов."];
    }
    return ['value' => $value, 'error' => null];
}

/**
 * Honeypot-поле: обычный пользователь его не видит и не заполнит (спрятано
 * CSS-классом .hp), а простые боты подставляют значения во все поля формы.
 * Заполненное значение — почти гарантированный признак автоматической отправки.
 */
function honeypot_triggered(array $source, string $field = 'website'): bool
{
    return trim((string) ($source[$field] ?? '')) !== '';
}

/**
 * Простой троттлинг отправок по сессии: не чаще, чем раз в $minInterval секунд.
 * Не защита от серьёзной атаки (сессия/IP легко подменяются), но полностью
 * останавливает наивных ботов и случайный двойной сабмит.
 */
function throttle_check(string $key, int $minInterval = 15): bool
{
    app_start_session();
    $last = $_SESSION['throttle'][$key] ?? 0;
    if (time() - (int) $last < $minInterval) {
        return false;
    }
    $_SESSION['throttle'][$key] = time();
    return true;
}
