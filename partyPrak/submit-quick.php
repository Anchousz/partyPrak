<?php
/**
 * submit-quick.php — приём быстрой заявки из модального окна.
 * Всегда отвечает редиректом (шаблон PRG), чтобы обновление страницы
 * не отправляло форму повторно. Ошибки передаются кодом причины в query —
 * этого достаточно для короткой формы из четырёх полей.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/validate.php';
require_once __DIR__ . '/includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Ловушка для ботов: если скрытое поле заполнено — молча уходим на главную,
// как будто всё получилось, ничего не сохраняя и не подсказывая боту,
// что его вычислили.
if (honeypot_triggered($_POST)) {
    header('Location: index.php');
    exit;
}

if (!site_csrf_valid($_POST)) {
    header('Location: index.php?quick=error&reason=csrf#search');
    exit;
}

if (!throttle_check('quick_form', 10)) {
    header('Location: index.php?quick=error&reason=throttle#search');
    exit;
}

$nameResult    = validate_name(str_param($_POST, 'name', 160));
$phoneResult   = validate_phone(str_param($_POST, 'phone', 40));
$dateResult    = validate_event_date(date_param($_POST, 'date'));
$commentResult = validate_comment(str_param($_POST, 'comment', 1000));
$children      = int_param($_POST, 'children', 8, 0, 50);
$slug          = str_param($_POST, 'location', 40);

$hasError = $nameResult['error'] || $phoneResult['error'] || $dateResult['error'];

if ($hasError) {
    header('Location: index.php?quick=error&reason=fields#search');
    exit;
}

create_booking([
    'source'      => 'quick',
    'location_id' => location_id_by_slug($slug !== '' ? $slug : null),
    'event_date'  => $dateResult['value'],
    'adults'      => 0,
    'children'    => $children,
    'guests'      => $children,
    'zones'       => [],
    'services'    => [],
    'total'       => 0,
    'customer'    => $nameResult['value'],
    'phone'       => $phoneResult['value'],
    'email'       => '',
    'comment'     => $commentResult['value'],
]);

header('Location: index.php?quick=ok');
exit;
