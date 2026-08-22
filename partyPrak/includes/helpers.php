<?php
/**
 * helpers.php — форматирование и вывод. Ровно те же правила, что и в JS,
 * чтобы серверная и клиентская части показывали одинаковые строки.
 */

declare(strict_types=1);

/** Экранирование для вывода в HTML. Короткое имя, потому что вызывается часто. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Сумма в рублях: 12500 -> «12 500 ₽» (узкий неразрывный пробел). */
function money(float|int|null $value): string
{
    return number_format((float) $value, 0, ',', "\u{202F}") . "\u{202F}₽";
}

/**
 * Склонение по числу.
 * plural(3, ['гость', 'гостя', 'гостей']) -> 'гостя'
 */
function plural(int $count, array $forms): string
{
    $n  = abs($count) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $forms[2];
    if ($n1 > 1 && $n1 < 5) return $forms[1];
    if ($n1 === 1)          return $forms[0];
    return $forms[2];
}

/** Дата «2026-09-12» -> «12 сентября 2026». */
function format_date(?string $iso): string
{
    if (!$iso) {
        return '';
    }
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $iso);
    if (!$date) {
        return $iso;
    }
    $months = [
        1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
    ];
    return (int) $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
}

/** Сегодняшняя дата в формате поля <input type="date">. */
function today(): string
{
    return (new DateTimeImmutable('today'))->format('Y-m-d');
}

/** Целое из запроса с ограничением диапазона. */
function int_param(array $source, string $key, int $default, int $min, int $max): int
{
    $raw = $source[$key] ?? null;
    if ($raw === null || $raw === '' || !is_numeric($raw)) {
        return $default;
    }
    return max($min, min($max, (int) $raw));
}

/** Строка из запроса, обрезанная по длине. */
function str_param(array $source, string $key, int $maxLength = 255): string
{
    $raw = $source[$key] ?? '';
    if (!is_string($raw)) {
        return '';
    }
    return mb_substr(trim($raw), 0, $maxLength);
}

/** Дата из запроса, только если она валидна и не в прошлом. */
function date_param(array $source, string $key): string
{
    $raw = str_param($source, $key, 10);
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
    if (!$date || $date->format('Y-m-d') !== $raw) {
        return '';
    }
    return $raw;
}

/** Значение для data-атрибута/JSON внутри <script>. */
function json_for_html(mixed $data): string
{
    return json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
}

/** Отдаёт 404 и завершает запрос. */
function not_found(string $message = 'Страница не найдена'): never
{
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>404</title></head><body>'
       . '<h1>404</h1><p>' . e($message) . '</p><p><a href="index.php">На главную</a></p></body></html>';
    exit;
}
