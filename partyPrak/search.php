<?php
/**
 * search.php — результаты подбора площадки по региону, дате и числу гостей.
 * Параметры приходят обычным GET-запросом, фильтрация выполняется в SQL.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$region   = str_param($_GET, 'region', 120);
$date     = date_param($_GET, 'date');
$adults   = int_param($_GET, 'adults', 2, 0, 50);
$children = int_param($_GET, 'children', 8, 0, 50);
$guests   = $adults + $children;

$locations = get_locations($region);
$regions   = get_regions();
$tagTones  = ['badge--mint', 'badge--violet', 'badge--amber', 'badge--pink'];

$pageTitle       = 'Подбор площадки — Праздник в каждый дом';
$pageDescription = 'Локации, свободные на выбранную дату: вместимость, адрес и стоимость аренды зоны.';
$noindex         = true;
$headerModifier  = 'is-scrolled';
$navCta          = ['type' => 'link', 'href' => 'index.php#search', 'label' => 'Изменить параметры'];

$jsData = [
    'locations' => array_map(static fn(array $l): array => [
        'id'      => $l['slug'],
        'name'    => $l['name'],
        'gallery' => $l['gallery'],
    ], $locations),
];
$pageScript = 'js/catalog.js';
$slimFooter = true;

require __DIR__ . '/includes/header.php';
?>

    <main id="main">
        <section class="page-header">
            <div class="container">
                <p class="eyebrow">Шаг 1 из 3</p>
                <h1 class="page-header__title">Подходящие площадки</h1>
                <p class="page-header__lede">
                    Выберите локацию — на следующем шаге откроется схема зон с актуальной занятостью.
                </p>
                <div class="filter-chips" style="margin-top: var(--space-5);">
                    <?php if ($region !== ''): ?>
                        <span class="chip">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            Регион: <strong><?= e($region) ?></strong>
                        </span>
                    <?php endif; ?>
                    <?php if ($date !== ''): ?>
                        <span class="chip">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            Дата: <strong><?= e(format_date($date)) ?></strong>
                        </span>
                    <?php endif; ?>
                    <span class="chip">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                        Гостей: <strong><?= $guests ?> (<?= $adults ?> взр. + <?= $children ?> дет.)</strong>
                    </span>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <div class="results__head">
                    <p class="results__count">
                        <?php if ($locations): ?>
                            Найдено <strong><?= count($locations) ?></strong>
                            <?= e(plural(count($locations), ['локация', 'локации', 'локаций'])) ?>
                        <?php else: ?>
                            Ничего не найдено
                        <?php endif; ?>
                    </p>
                    <a class="btn btn--ghost btn--sm" href="index.php#search">Изменить параметры поиска</a>
                </div>

                <?php if (!$locations): ?>
                    <div class="empty-state">
                        <span class="empty-state__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5.1L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.7 4H7.3a2 2 0 0 0-1.8 1.1z"/></svg>
                        </span>
                        <h2 class="empty-state__title">В этом регионе пока пусто</h2>
                        <p class="empty-state__text">
                            Сейчас мы работаем в этих регионах: <?= e(implode(', ', $regions)) ?>.
                            Выберите другой регион или позвоните нам — подберём площадку вручную.
                        </p>
                        <a class="btn btn--secondary" href="index.php#search">Изменить параметры</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid--3">
                        <?php foreach ($locations as $loc): ?>
                            <?php $fits = $loc['capacity'] >= $guests; ?>
                            <article class="card card--interactive reveal">
                                <div class="card__media">
                                    <img src="<?= e($loc['image']) ?>" alt="<?= e($loc['name']) ?>" loading="lazy" decoding="async">
                                    <span class="badge <?= $fits ? 'badge--solid' : 'badge--amber' ?> card__media-badge">
                                        <?php if ($fits): ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/></svg>
                                            <?= $loc['freeZones'] ?> <?= e(plural($loc['freeZones'], ['зона свободна', 'зоны свободны', 'зон свободно'])) ?>
                                        <?php else: ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
                                            Вмещает до <?= $loc['capacity'] ?>
                                        <?php endif; ?>
                                    </span>
                                    <button type="button" class="card__photos" data-gallery="<?= e($loc['slug']) ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                        <?= count($loc['gallery']) ?> фото
                                    </button>
                                </div>
                                <div class="card__body">
                                    <h2 class="card__title"><?= e($loc['name']) ?></h2>
                                    <div class="card__meta">
                                        <span class="card__meta-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            <?= e($loc['address']) ?>
                                        </span>
                                    </div>
                                    <div class="card__tags">
                                        <?php foreach ($loc['tags'] as $i => $tag): ?>
                                            <span class="badge <?= $tagTones[$i % count($tagTones)] ?>"><?= e($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="card__text"><?= e($loc['summary']) ?></p>
                                    <div class="card__footer">
                                        <p class="card__price"><?= money($loc['priceFrom']) ?><span>за зону</span></p>
                                        <a class="btn btn--primary btn--sm"
                                           href="seat.php?<?= e(http_build_query([
                                               'location' => $loc['slug'],
                                               'date'     => $date,
                                               'adults'   => $adults,
                                               'children' => $children,
                                           ])) ?>">Выбрать зону</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
