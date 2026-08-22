<?php
/**
 * booking.php — оформление заказа и запись заявки в базу.
 *
 * Тот же файл и показывает форму, и обрабатывает её отправку. Итоговая сумма
 * считается ЗАНОВО на сервере из цен в базе: то, что пришло из браузера,
 * для расчёта денег не используется. Каждое поле проверяется отдельно —
 * ошибка подсвечивается у конкретного инпута, а не общей надписью сверху.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/validate.php';
require_once __DIR__ . '/includes/security.php';

$allZones    = get_zones();
$allServices = get_services();

/** Быстрый доступ по слагу. */
$zoneBySlug    = array_column($allZones, null, 'id');
$serviceBySlug = array_column($allServices, null, 'id');

/** Стоимость зоны с учётом тарифа «за человека». */
function zone_cost(array $zone, int $guests): int
{
    return $zone['perPerson'] ? $zone['price'] * max($guests, 1) : $zone['price'];
}

/** Стоимость услуги по её типу тарификации. */
function service_cost(array $service, int $guests): int
{
    return match ($service['unit']) {
        'guest' => $service['price'] * max($guests, 1),
        'hour'  => $service['price'] * max($service['hours'], 1),
        default => $service['price'],
    };
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$source = $isPost ? $_POST : $_GET;

$slug     = str_param($source, 'location', 40);
$location = $slug !== '' ? get_location($slug) : null;
$date     = date_param($source, 'date');
$adults   = int_param($source, 'adults', 2, 0, 50);
$children = int_param($source, 'children', 8, 0, 50);
$guests   = int_param($source, 'guests', max(1, $adults + $children), 1, 50);

/* Оставляем только существующие и свободные зоны — даже если в форму
   подставить чужой slug руками, лишнее просто отсеется. */
$selectedZoneSlugs = array_values(array_filter(
    (array) ($source['zones'] ?? []),
    static fn($s): bool => is_string($s) && isset($zoneBySlug[$s]) && !$zoneBySlug[$s]['booked']
));
$selectedZones = array_map(static fn(string $s): array => $zoneBySlug[$s], $selectedZoneSlugs);

$selectedServiceSlugs = array_values(array_filter(
    (array) ($source['services'] ?? []),
    static fn($s): bool => is_string($s) && isset($serviceBySlug[$s])
));
$selectedServices = array_map(static fn(string $s): array => $serviceBySlug[$s], $selectedServiceSlugs);

/** Итог считаем на сервере, из цен в базе. */
$total = 0;
foreach ($selectedZones as $zone)       { $total += zone_cost($zone, $guests); }
foreach ($selectedServices as $service) { $total += service_cost($service, $guests); }

/* Значения полей и их ошибки — раздельно, чтобы подсветить конкретный инпут,
   а не выводить общий список сверху формы. */
$fields = [
    'name'    => ['value' => '', 'error' => null],
    'phone'   => ['value' => '', 'error' => null],
    'email'   => ['value' => '', 'error' => null],
    'comment' => ['value' => '', 'error' => null],
    'date'    => ['value' => $date, 'error' => null],
];
$generalErrors = [];
$success = null;

if ($isPost) {
    // Пустое скрытое поле: настоящий человек его не видит и не заполнит.
    if (honeypot_triggered($_POST)) {
        header('Location: index.php');
        exit;
    }

    if (!site_csrf_valid($_POST)) {
        $generalErrors[] = 'Форма устарела — обновите страницу и заполните её заново.';
    } elseif (!throttle_check('booking_form', 10)) {
        $generalErrors[] = 'Заявка уже отправляется — подождите несколько секунд и попробуйте ещё раз.';
    } else {
        $fields['name']    = validate_name(str_param($_POST, 'name', 160));
        $fields['phone']   = validate_phone(str_param($_POST, 'phone', 40));
        $fields['email']   = validate_email_field(str_param($_POST, 'email', 160));
        $fields['comment'] = validate_comment(str_param($_POST, 'comment', 1000));
        $fields['date']    = validate_event_date($date);

        if (!$location)       { $generalErrors[] = 'Локация не выбрана — вернитесь на первый шаг.'; }
        if (!$selectedZones)  { $generalErrors[] = 'Выберите хотя бы одну зону.'; }

        $hasFieldErrors = array_filter($fields, static fn(array $f): bool => $f['error'] !== null);

        if (!$generalErrors && !$hasFieldErrors) {
            $bookingId = create_booking([
                'source'      => 'form',
                'location_id' => location_id_by_slug($location['slug']),
                'event_date'  => $fields['date']['value'],
                'adults'      => $adults,
                'children'    => $children,
                'guests'      => $guests,
                'zones'       => array_map(static fn(array $z): array => ['name' => $z['name'], 'price' => zone_cost($z, $guests)], $selectedZones),
                'services'    => array_map(static fn(array $s): array => ['name' => $s['name'], 'price' => service_cost($s, $guests)], $selectedServices),
                'total'       => $total,
                'customer'    => $fields['name']['value'],
                'phone'       => $fields['phone']['value'],
                'email'       => $fields['email']['value'],
                'comment'     => $fields['comment']['value'],
            ]);

            $success = ['id' => $bookingId, 'email' => $fields['email']['value'], 'total' => $total];
        }
    }
}

$csrfToken = site_csrf_token();

$pageTitle       = 'Оформление заказа — Праздник в каждый дом';
$pageDescription = 'Оформление брони: дополнительные услуги, контактные данные и итоговый расчёт стоимости праздника.';
$noindex         = true;
$headerModifier  = 'is-scrolled';
$navCta          = ['type' => 'link', 'href' => 'tel:88001112020', 'label' => '8 (800) 111-20-20'];
$slimFooter      = true;

$jsData = [
    'zones'    => $allZones,
    'services' => $allServices,
];
$pageScript = 'js/booking.js';

require __DIR__ . '/includes/header.php';
?>

    <main id="main">
        <?php if ($success): ?>
            <section class="section" style="padding-top: 9rem;">
                <div class="container container--narrow">
                    <div class="empty-state" data-confetti>
                        <span class="empty-state__icon" style="background: var(--mint-soft); color: var(--mint-deep);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 6-6"/></svg>
                        </span>
                        <h1 class="empty-state__title">Бронирование оформлено</h1>
                        <p class="empty-state__text">
                            Заявка №<?= (int) $success['id'] ?> принята. Электронный билет отправлен
                            на <strong><?= e($success['email']) ?></strong>.
                        </p>

                        <ul class="summary__list" style="width:min(28rem,100%); text-align:left;">
                            <li><span class="summary__label">Локация</span><span class="summary__value"><?= e($location['name']) ?></span></li>
                            <li><span class="summary__label">Дата</span><span class="summary__value"><?= e(format_date($fields['date']['value'])) ?></span></li>
                            <li><span class="summary__label">Гостей</span><span class="summary__value"><?= $guests ?></span></li>
                            <li><span class="summary__label">Сумма</span><span class="summary__value"><?= money($success['total']) ?></span></li>
                        </ul>

                        <a class="btn btn--primary" href="index.php">На главную</a>
                    </div>
                </div>
            </section>

        <?php elseif (!$location || !$selectedZones): ?>
            <section class="section" style="padding-top: 9rem;">
                <div class="container">
                    <div class="empty-state">
                        <span class="empty-state__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                        </span>
                        <h1 class="empty-state__title">Нет активной брони</h1>
                        <p class="empty-state__text">
                            Чтобы оформить заказ, подберите локацию и выберите хотя бы одну зону.
                        </p>
                        <a class="btn btn--primary" href="index.php#search">Начать подбор</a>
                    </div>
                </div>
            </section>

        <?php else: ?>
            <section class="page-header">
                <div class="container">
                    <p class="eyebrow">Шаг 3 из 3</p>
                    <h1 class="page-header__title">Оформление заказа</h1>
                    <p class="page-header__lede">
                        Проверьте детали, добавьте нужные услуги и укажите почту — билет придёт туда сразу после оплаты.
                    </p>
                    <ol class="progress">
                        <li data-done="true">Локация</li>
                        <li data-done="true">Зоны</li>
                        <li aria-current="step">Оформление</li>
                    </ol>
                </div>
            </section>

            <section class="section">
                <div class="container">
                    <?php if ($generalErrors): ?>
                        <div class="notice notice--warn" style="margin-bottom: var(--space-5);">
                            <span>
                                <strong>Не удалось оформить заказ:</strong>
                                <?= e(implode(' ', $generalErrors)) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="booking.php" id="bookingForm" novalidate>
                        <input type="hidden" name="csrf" value="<?= e($csrfToken) ?>">
                        <!-- Ловушка для ботов: настоящий человек это поле не видит и не заполнит. -->
                        <div class="hp" aria-hidden="true">
                            <label for="website">Оставьте это поле пустым</label>
                            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <input type="hidden" name="location" value="<?= e($location['slug']) ?>">
                        <input type="hidden" name="adults" value="<?= $adults ?>">
                        <input type="hidden" name="children" value="<?= $children ?>">
                        <?php foreach ($selectedZoneSlugs as $z): ?>
                            <input type="hidden" name="zones[]" value="<?= e($z) ?>">
                        <?php endforeach; ?>

                        <div class="booking__layout">
                            <div>
                                <div class="panel">
                                    <h2 class="panel__title">Детали заказа</h2>
                                    <div class="panel__grid">
                                        <div class="field">
                                            <label class="field__label" for="bookingRegion">Регион</label>
                                            <input class="input" type="text" id="bookingRegion" value="<?= e($location['region']) ?>" readonly>
                                        </div>
                                        <div class="field<?= $fields['date']['error'] ? ' is-invalid' : '' ?>">
                                            <label class="field__label" for="bookingDate">Дата праздника</label>
                                            <input class="input" type="date" id="bookingDate" name="date"
                                                   value="<?= e($fields['date']['value']) ?>" min="<?= today() ?>" required>
                                            <?php if ($fields['date']['error']): ?>
                                                <p class="field__error"><?= e($fields['date']['error']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="panel__grid" style="margin-top: var(--space-4);">
                                        <div class="field" style="grid-column: 1 / -1;">
                                            <label class="field__label" for="bookingAddress">Адрес площадки</label>
                                            <input class="input" type="text" id="bookingAddress"
                                                   value="<?= e($location['name'] . ', ' . $location['address']) ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="panel__grid" style="margin-top: var(--space-4);">
                                        <div class="field">
                                            <label class="field__label" for="bookingGuests">Количество гостей</label>
                                            <input class="input" type="number" id="bookingGuests" name="guests"
                                                   min="1" max="50" value="<?= $guests ?>" required>
                                            <span class="field__hint">Влияет на стоимость питания и зон с тарифом за гостя.</span>
                                        </div>
                                    </div>

                                    <p class="notice notice--warn" id="guestsNote" hidden style="margin-top: var(--space-4);">
                                        Гостей больше, чем вмещают выбранные зоны. Вернитесь на шаг назад и добавьте площадку.
                                    </p>
                                </div>

                                <div class="panel">
                                    <h2 class="panel__title">Дополнительные услуги</h2>
                                    <div class="stack">
                                        <?php foreach ($allServices as $service): ?>
                                            <label class="check-card">
                                                <input type="checkbox" name="services[]" value="<?= e($service['id']) ?>"
                                                       data-price="<?= $service['price'] ?>"
                                                       data-unit="<?= e($service['unit']) ?>"
                                                       data-hours="<?= $service['hours'] ?>"
                                                       <?= in_array($service['id'], $selectedServiceSlugs, true) ? 'checked' : '' ?>>
                                                <span class="check-card__body">
                                                    <span class="check-card__name"><?= e($service['name']) ?></span>
                                                    <span class="check-card__price">
                                                        <?php
                                                        echo match ($service['unit']) {
                                                            'guest' => money($service['price']) . ' за гостя',
                                                            'hour'  => money($service['price']) . ' в час, от ' . $service['hours'] . ' ч',
                                                            default => money($service['price']) . ' за заказ',
                                                        };
                                                        ?>
                                                        · <?= e($service['note']) ?>
                                                    </span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="panel">
                                    <h2 class="panel__title">Контактные данные</h2>
                                    <div class="panel__grid">
                                        <div class="field<?= $fields['name']['error'] ? ' is-invalid' : '' ?>">
                                            <label class="field__label" for="bookingName">Имя и фамилия</label>
                                            <input class="input" type="text" id="bookingName" name="name"
                                                   value="<?= e($fields['name']['value']) ?>"
                                                   placeholder="Иван Иванов" required autocomplete="name" maxlength="160">
                                            <?php if ($fields['name']['error']): ?>
                                                <p class="field__error"><?= e($fields['name']['error']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="field<?= $fields['phone']['error'] ? ' is-invalid' : '' ?>">
                                            <label class="field__label" for="bookingPhone">Телефон</label>
                                            <input class="input" type="tel" id="bookingPhone" name="phone"
                                                   value="<?= e($fields['phone']['value']) ?>"
                                                   placeholder="+7 999 000-00-00" required autocomplete="tel" maxlength="40">
                                            <?php if ($fields['phone']['error']): ?>
                                                <p class="field__error"><?= e($fields['phone']['error']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="panel__grid panel__grid--full" style="margin-top: var(--space-4);">
                                        <div class="field<?= $fields['email']['error'] ? ' is-invalid' : '' ?>">
                                            <label class="field__label" for="bookingEmail">Email для билета</label>
                                            <input class="input" type="email" id="bookingEmail" name="email"
                                                   value="<?= e($fields['email']['value']) ?>"
                                                   placeholder="name@example.com" required autocomplete="email" maxlength="160">
                                            <?php if ($fields['email']['error']): ?>
                                                <p class="field__error"><?= e($fields['email']['error']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="field<?= $fields['comment']['error'] ? ' is-invalid' : '' ?>">
                                            <label class="field__label" for="bookingComment">Комментарий к заказу</label>
                                            <textarea class="input" id="bookingComment" name="comment" rows="3" maxlength="1000"
                                                      placeholder="Тема праздника, аллергии, время начала"><?= e($fields['comment']['value']) ?></textarea>
                                            <?php if ($fields['comment']['error']): ?>
                                                <p class="field__error"><?= e($fields['comment']['error']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <aside class="summary" aria-label="Итоговый расчёт">
                                <h2 class="summary__title">Ваш заказ</h2>
                                <ul class="summary__list" id="summaryList">
                                    <li>
                                        <span class="summary__label"><?= e($location['name']) ?></span>
                                        <span class="summary__value"><?= $date !== '' ? e(format_date($date)) : 'дата не выбрана' ?></span>
                                    </li>
                                    <?php foreach ($selectedZones as $zone): ?>
                                        <li>
                                            <span class="summary__label"><?= e($zone['name']) ?></span>
                                            <span class="summary__value"><?= money(zone_cost($zone, $guests)) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php foreach ($selectedServices as $service): ?>
                                        <li>
                                            <span class="summary__label"><?= e($service['name']) ?></span>
                                            <span class="summary__value"><?= money(service_cost($service, $guests)) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="summary__total">
                                    <span class="summary__total-label">Итого</span>
                                    <span class="summary__total-value" id="summaryTotal"><?= money($total) ?></span>
                                </div>

                                <div class="summary__action">
                                    <button type="submit" class="btn btn--primary btn--block btn--lg">
                                        Подтвердить бронирование
                                    </button>
                                </div>
                                <p class="summary__fineprint">
                                    Отмена без штрафа за 5 дней до праздника.
                                    Оплата в этом учебном проекте не подключена.
                                </p>
                            </aside>
                        </div>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
