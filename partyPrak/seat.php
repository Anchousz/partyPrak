<?php
/**
 * seat.php — выбор зон площадки.
 * Зоны — обычные чекбоксы внутри формы: страница полностью работает без JS,
 * а скрипт лишь пересчитывает сумму на лету.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$slug     = str_param($_GET, 'location', 40);
$location = $slug !== '' ? get_location($slug) : null;

$date     = date_param($_GET, 'date');
$adults   = int_param($_GET, 'adults', 2, 0, 50);
$children = int_param($_GET, 'children', 8, 0, 50);
$guests   = max(1, $adults + $children);

$zones = get_zones();

$pageTitle       = 'Выбор зоны — Праздник в каждый дом';
$pageDescription = 'Схема зон выбранной локации: шатёр, беседки, веранда и игровые площадки с текущей занятостью.';
$noindex         = true;
$headerModifier  = 'is-scrolled';
$navCta          = ['type' => 'link', 'href' => 'index.php#search', 'label' => 'Сменить локацию'];
$slimFooter      = true;

$jsData = [
    'zones'  => $zones,
    'guests' => $guests,
];
$pageScript = 'js/seat.js';

if ($location) {
    $jsData['locations'] = [[
        'id'      => $location['slug'],
        'name'    => $location['name'],
        'gallery' => $location['gallery'],
    ]];
}

require __DIR__ . '/includes/header.php';
?>

    <main id="main">
        <?php if (!$location): ?>
            <section class="section" style="padding-top: 9rem;">
                <div class="container">
                    <div class="empty-state">
                        <span class="empty-state__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </span>
                        <h1 class="empty-state__title">Локация не выбрана</h1>
                        <p class="empty-state__text">
                            Сначала подберите площадку — после этого здесь появится схема зон
                            с актуальной занятостью на выбранную дату.
                        </p>
                        <a class="btn btn--primary" href="index.php#search">Подобрать локацию</a>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="page-header">
                <div class="container">
                    <p class="eyebrow">Шаг 2 из 3</p>
                    <h1 class="page-header__title">Выберите зону праздника</h1>
                    <p class="page-header__lede"><?= e($location['address']) ?></p>

                    <div class="filter-chips" style="margin-top: var(--space-5);">
                        <span class="chip">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <strong><?= e($location['name']) ?></strong>
                        </span>
                        <span class="chip">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <?= $date !== '' ? e(format_date($date)) : 'дата не выбрана' ?>
                        </span>
                        <span class="chip">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                            <?= $guests ?> <?= e(plural($guests, ['гость', 'гостя', 'гостей'])) ?>
                        </span>
                    </div>

                    <ol class="progress">
                        <li data-done="true">Локация</li>
                        <li aria-current="step">Зоны</li>
                        <li>Оформление</li>
                    </ol>
                </div>
            </section>

            <section class="section">
                <form class="container booking__layout" method="get" action="booking.php" id="zonesForm">
                    <input type="hidden" name="location" value="<?= e($location['slug']) ?>">
                    <input type="hidden" name="date" value="<?= e($date) ?>">
                    <input type="hidden" name="adults" value="<?= $adults ?>">
                    <input type="hidden" name="children" value="<?= $children ?>">

                    <div>
                        <div class="panel">
                            <h2 class="panel__title">Как выглядит площадка</h2>
                            <?php if ($location['plan']): ?>
                                <button type="button" class="plan" data-lightbox="<?= e($location['plan']) ?>"
                                        data-lightbox-alt="Схема площадки «<?= e($location['name']) ?>»">
                                    <img src="<?= e($location['plan']) ?>" alt="Схема площадки «<?= e($location['name']) ?>»">
                                    <span class="plan__hint">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M11 8v6M8 11h6"/></svg>
                                        Открыть схему
                                    </span>
                                </button>
                            <?php endif; ?>

                            <?php if ($location['gallery']): ?>
                                <h3 class="panel__subtitle">Фотографии локации</h3>
                                <div class="gallery" data-lightbox-group>
                                    <?php foreach ($location['gallery'] as $i => $shot): ?>
                                        <button type="button" class="gallery__item"
                                                data-lightbox="<?= e($shot) ?>"
                                                data-lightbox-alt="<?= e($location['name']) ?>"
                                                aria-label="Открыть фото <?= $i + 1 ?> из <?= count($location['gallery']) ?>">
                                            <img src="<?= e($shot) ?>" alt="" loading="lazy" decoding="async">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="panel">
                            <h2 class="panel__title">Выберите зоны</h2>

                            <div class="zone-legend">
                                <span class="zone-legend__item"><span class="zone-legend__swatch"></span> Свободна</span>
                                <span class="zone-legend__item"><span class="zone-legend__swatch zone-legend__swatch--selected"></span> Выбрана</span>
                                <span class="zone-legend__item"><span class="zone-legend__swatch zone-legend__swatch--booked"></span> Занята</span>
                            </div>

                            <div class="zones">
                                <?php foreach ($zones as $zone): ?>
                                    <label class="zone <?= $zone['booked'] ? 'is-booked' : '' ?>">
                                        <input type="checkbox" name="zones[]" value="<?= e($zone['id']) ?>"
                                               class="zone__input"
                                               data-price="<?= $zone['price'] ?>"
                                               data-capacity="<?= $zone['capacity'] ?>"
                                               data-per-person="<?= $zone['perPerson'] ? '1' : '0' ?>"
                                               <?= $zone['booked'] ? 'disabled' : '' ?>>
                                        <span class="zone__name"><?= e($zone['name']) ?></span>
                                        <span class="zone__capacity">
                                            до <?= $zone['capacity'] ?> <?= e(plural($zone['capacity'], ['гостя', 'гостей', 'гостей'])) ?>
                                        </span>
                                        <?php if ($zone['booked']): ?>
                                            <span class="badge badge--muted">Занято</span>
                                        <?php endif; ?>
                                        <span class="zone__price">
                                            <?= money($zone['price']) ?>
                                            <span><?= $zone['perPerson'] ? 'за гостя' : 'за день' ?></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <p class="notice notice--warn" id="capacityNote" hidden style="margin-top: var(--space-5);"></p>
                        </div>
                    </div>

                    <aside class="summary" aria-label="Сводка по зонам">
                        <h2 class="summary__title">Выбранные зоны</h2>
                        <ul class="summary__list" id="zoneSummaryList">
                            <li class="summary__empty">Зоны пока не выбраны</li>
                        </ul>

                        <div class="summary__total">
                            <span class="summary__total-label">Стоимость зон</span>
                            <span class="summary__total-value" id="zoneTotal"><?= money(0) ?></span>
                        </div>

                        <div class="summary__action">
                            <button type="submit" class="btn btn--primary btn--block btn--lg" id="goToBooking" disabled>
                                Перейти к оформлению
                            </button>
                        </div>
                        <p class="summary__fineprint">Услуги и питание добавляются на следующем шаге.</p>
                    </aside>
                </form>
            </section>
        <?php endif; ?>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
