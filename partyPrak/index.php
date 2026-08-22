<?php
/**
 * index.php — главная страница. Каталог, акции и цены рендерятся на сервере
 * из базы; JS остаётся только на интерактиве (модалки, галерея, уведомления).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$allLocations = get_locations();
$showcase     = array_slice($allLocations, 0, 3);
$promos       = get_promos();
$priceRows    = get_price_list();
$regions      = get_regions();

$tagTones = ['badge--mint', 'badge--violet', 'badge--amber', 'badge--pink'];

$pageTitle       = 'Праздник в каждый дом — детские праздники под ключ';
$pageDescription = 'Организуем детский праздник на подготовленной площадке: локации, аниматоры, питание и фотограф. Свободную дату подберём за минуту, менеджер ответит за 15 минут.';

/* JS нужны только галереи локаций и подробности акций. */
$jsData = [
    'locations' => array_map(static fn(array $l): array => [
        'id'      => $l['slug'],
        'name'    => $l['name'],
        'gallery' => $l['gallery'],
    ], $allLocations),
    'promos' => $promos,
];
$pageScript       = 'js/home.js';
$withBookingModal = true;
$locationsForForm = $allLocations;

require __DIR__ . '/includes/header.php';
?>

    <main id="main">
        <!-- ГЕРОЙ -->
        <section class="hero">
            <div class="hero__media">
                <img src="images/location/1.jpg" alt="" fetchpriority="high" decoding="async">
            </div>

            <span class="balloon balloon--1 float" aria-hidden="true"></span>
            <span class="balloon balloon--2 float float--slow float--delay" aria-hidden="true"></span>
            <span class="balloon balloon--3 float float--slow" aria-hidden="true"></span>

            <div class="container hero__inner">
                <div class="hero__content">
                    <p class="hero__badge">
                        <span class="hero__badge-dot"></span>
                        Свободные даты на ближайшие выходные
                    </p>

                    <h1 class="hero__title">Детский праздник, <em>который запомнят</em> все</h1>

                    <p class="hero__lede">
                        Площадка, аниматоры, торт и фотограф — в одном заказе.
                        Вы выбираете дату, мы готовим всё остальное и встречаем вас на месте.
                    </p>

                    <div class="hero__actions">
                        <button type="button" class="btn btn--primary btn--lg btn--pulse" data-modal-open="bookingModal">
                            Забронировать праздник
                        </button>
                        <a class="btn btn--glass btn--lg" href="#locations">Посмотреть локации</a>
                    </div>

                    <div class="hero__trust">
                        <div class="avatars" aria-hidden="true">
                            <img src="images/people/1.jpg" alt="" loading="lazy">
                            <img src="images/people/2.jpg" alt="" loading="lazy">
                            <img src="images/people/4.jpg" alt="" loading="lazy">
                            <img src="images/people/5.jpg" alt="" loading="lazy">
                        </div>
                        <p class="hero__trust-text">
                            <strong>480+ праздников за год</strong>
                            <span class="rating-stars" aria-hidden="true">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
                                <?php endfor; ?>
                            </span>
                            4.9 из 5 — оценка родителей
                        </p>
                    </div>
                </div>

                <div class="hero__aside" aria-hidden="true">
                    <div class="float-card float-card--1 float">
                        <span class="float-card__icon" style="background: var(--pink-soft); color: var(--pink-deep);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21h16v-7a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3z"/><path d="M4 16.5c1.5 0 1.5 1.2 3 1.2s1.5-1.2 3-1.2 1.5 1.2 3 1.2 1.5-1.2 3-1.2 1.5 1.2 3 1.2"/><path d="M12 8V5M8 8V6M16 8V6"/></svg>
                        </span>
                        <span>
                            <span class="float-card__title">Торт и угощения</span>
                            <span class="float-card__text">Детское меню без аллергенов</span>
                        </span>
                    </div>

                    <div class="float-card float-card--2 float float--slow float--delay">
                        <span class="float-card__icon" style="background: var(--mint-soft); color: var(--mint-deep);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        </span>
                        <span>
                            <span class="float-card__title">Шоу и аниматоры</span>
                            <span class="float-card__text">Сценарий под возраст детей</span>
                        </span>
                    </div>

                    <div class="float-card float-card--3 float float--slow">
                        <span class="float-card__icon" style="background: var(--amber-soft); color: var(--amber-deep);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        </span>
                        <span>
                            <span class="float-card__title">Фотограф</span>
                            <span class="float-card__text">50 обработанных кадров</span>
                        </span>
                    </div>
                </div>

                <div class="hero__stats" style="grid-column: 1 / -1;">
                    <div class="stat">
                        <span class="stat__value"><?= count($allLocations) ?></span>
                        <span class="stat__label">локаций в <?= count($regions) ?> регионах</span>
                    </div>
                    <div class="stat">
                        <span class="stat__value">480+</span>
                        <span class="stat__label">праздников за год</span>
                    </div>
                    <div class="stat">
                        <span class="stat__value">4.9</span>
                        <span class="stat__label">средняя оценка</span>
                    </div>
                    <div class="stat">
                        <span class="stat__value">15 мин</span>
                        <span class="stat__label">ответ менеджера</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ПОДБОР ДАТЫ -->
        <section id="search" class="search-bar">
            <div class="container">
                <!-- Обычная GET-форма: работает и без JavaScript. -->
                <form class="search-bar__panel" method="get" action="search.php">
                    <h2 class="search-bar__title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                        Проверить свободные даты
                    </h2>
                    <div class="search-bar__grid">
                        <div class="field">
                            <label class="field__label" for="searchRegion">Регион</label>
                            <select class="input" id="searchRegion" name="region">
                                <option value="">Любой регион</option>
                                <?php foreach ($regions as $region): ?>
                                    <option value="<?= e($region) ?>"><?= e($region) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label class="field__label" for="searchDate">Дата праздника</label>
                            <input class="input" type="date" id="searchDate" name="date"
                                   required min="<?= today() ?>" value="<?= today() ?>">
                        </div>
                        <div class="field">
                            <label class="field__label" for="searchAdults">Взрослые</label>
                            <input class="input" type="number" id="searchAdults" name="adults" min="0" max="50" value="2">
                        </div>
                        <div class="field">
                            <label class="field__label" for="searchChildren">Дети</label>
                            <input class="input" type="number" id="searchChildren" name="children" min="1" max="50" value="8">
                        </div>
                        <button type="submit" class="btn btn--primary search-bar__submit">Найти площадку</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- О КОМПАНИИ -->
        <section id="about" class="section">
            <span class="blob blob--pink" style="width:26rem; height:26rem; top:-6rem; right:-8rem;" aria-hidden="true"></span>
            <div class="container about__layout">
                <div class="about__text">
                    <p class="eyebrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1L12 21l7.7-7.6 1.1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                        О компании
                    </p>
                    <h2 class="section-title">Праздник, к которому <span class="swash">не нужно готовиться</span></h2>
                    <p class="section-lede">
                        Мы организуем дни рождения и выпускные на собственных площадках.
                        Территория, программа и угощения — наша забота. Ваша — приехать и радоваться вместе с детьми.
                    </p>

                    <ul class="about__values">
                        <li class="value">
                            <span class="value__icon value__icon--pink" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </span>
                            <span>
                                <h3 class="value__title">Безопасно для детей</h3>
                                <p class="value__text">Закрытая охраняемая территория, обработка от клещей перед каждым сезоном и аптечка на площадке.</p>
                            </span>
                        </li>
                        <li class="value">
                            <span class="value__icon value__icon--amber" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            </span>
                            <span>
                                <h3 class="value__title">Честная цена</h3>
                                <p class="value__text">Сумма собирается из зон и услуг прямо в заказе. На месте ничего доплачивать не придётся.</p>
                            </span>
                        </li>
                        <li class="value">
                            <span class="value__icon value__icon--mint" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01M15 9h.01"/></svg>
                            </span>
                            <span>
                                <h3 class="value__title">Программа под возраст</h3>
                                <p class="value__text">Сценарий подбираем под возраст и размер группы — трёхлеткам и выпускникам нужны разные игры.</p>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="collage reveal">
                    <span class="sticker collage__stamp">
                        <span class="sticker__value">12</span>
                        <span class="sticker__label">лет с вами</span>
                    </span>
                    <img class="collage__main" src="images/people/3.jpg" alt="Дети играют с аниматором на празднике" loading="lazy" decoding="async">
                    <img class="collage__inset" src="images/people/7.jpg" alt="Праздничный стол для детей" loading="lazy" decoding="async">
                </div>
            </div>
        </section>

        <!-- КАК ЗАКАЗАТЬ -->
        <section id="steps" class="section section--sunken">
            <div class="container">
                <div class="section-head section-head--center">
                    <p class="eyebrow eyebrow--violet">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/></svg>
                        Как это работает
                    </p>
                    <h2 class="section-title">Шесть шагов до <span class="swash">готового праздника</span></h2>
                    <p class="section-lede">Весь путь занимает около пяти минут. Черновик заказа сохраняется — можно вернуться и дособрать позже.</p>
                </div>

                <?php
                $steps = [
                    ['#search',    'Выбрать дату',      'Укажите день праздника — покажем площадки, свободные именно на эту дату.', '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
                    ['#search',    'Сколько гостей',    'Количество детей и взрослых определяет размер зоны и порции угощений.',    '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>'],
                    ['#locations', 'Выбрать локацию',   'Озеро, лес, парк-отель или пляж — площадки с разным характером.',          '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>'],
                    ['#locations', 'Занять зону',       'Шатёр, беседка или веранда — сразу видно, что свободно, а что занято.',    '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>'],
                    ['#prices',    'Добавить услуги',   'Аниматоры, торт, ведущий и фотограф — сумма пересчитывается сразу.',       '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8M12 8v13"/><path d="M12 8S10.5 3 8 3a2.2 2.2 0 0 0 0 5M12 8s1.5-5 4-5a2.2 2.2 0 0 1 0 5"/>'],
                    ['#prices',    'Получить билет',    'Подтверждение и электронный билет приходят на почту сразу после оплаты.',  '<path d="M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a3 3 0 0 0 0 6v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a3 3 0 0 0 0-6z"/><path d="M13 5v2M13 11v2M13 17v2"/>'],
                ];
                ?>
                <div class="steps" id="stepsGrid">
                    <?php foreach ($steps as [$target, $title, $text, $iconPath]): ?>
                        <button type="button" class="step reveal" data-step-target="<?= e($target) ?>">
                            <span class="step__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><?= $iconPath ?></svg>
                            </span>
                            <span class="step__title"><?= e($title) ?></span>
                            <span class="step__text"><?= e($text) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ЛОКАЦИИ -->
        <section id="locations" class="section">
            <span class="blob blob--mint" style="width:24rem; height:24rem; bottom:-6rem; left:-8rem;" aria-hidden="true"></span>
            <div class="container relative">
                <div class="section-head section-head--center">
                    <p class="eyebrow eyebrow--mint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        Наши площадки
                    </p>
                    <h2 class="section-title">Локации, где рождается <span class="swash">сказка</span></h2>
                    <p class="section-lede">Каждая площадка подготовлена заранее: зоны, электричество, вода и навес на случай дождя.</p>
                </div>

                <div class="grid grid--3">
                    <?php foreach ($showcase as $loc): ?>
                        <article class="card card--interactive reveal">
                            <div class="card__media">
                                <img src="<?= e($loc['image']) ?>" alt="<?= e($loc['name']) ?>" loading="lazy" decoding="async">
                                <span class="badge badge--solid card__media-badge">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M18 16l.8 2.2L21 19l-2.2.8L18 22l-.8-2.2L15 19l2.2-.8z"/></svg>
                                    <?= $loc['freeZones'] ?> <?= e(plural($loc['freeZones'], ['зона свободна', 'зоны свободны', 'зон свободно'])) ?>
                                </span>
                                <button type="button" class="card__photos" data-gallery="<?= e($loc['slug']) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                    <?= count($loc['gallery']) ?> фото
                                </button>
                            </div>
                            <div class="card__body">
                                <h3 class="card__title"><?= e($loc['name']) ?></h3>
                                <div class="card__meta">
                                    <span class="card__meta-item">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <?= e($loc['region']) ?>
                                    </span>
                                    <span class="card__meta-item">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                                        до <?= $loc['capacity'] ?> гостей
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
                                    <button type="button" class="btn btn--primary btn--sm" data-book-location="<?= e($loc['slug']) ?>">
                                        Забронировать
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <p class="cluster" style="justify-content:center; margin-top: var(--space-7);">
                    <a class="btn btn--secondary btn--lg" href="search.php">Показать все <?= count($allLocations) ?> локаций</a>
                </p>
            </div>
        </section>

        <!-- АКЦИИ -->
        <section id="promotions" class="section section--tint">
            <div class="container">
                <div class="section-head section-head--center">
                    <p class="eyebrow eyebrow--amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8M12 8v13"/></svg>
                        Выгодно
                    </p>
                    <h2 class="section-title">Акции этого сезона</h2>
                    <p class="section-lede">Скидка применяется автоматически при оформлении, если заказ подходит под условия.</p>
                </div>

                <div class="grid grid--3">
                    <?php foreach ($promos as $promo): ?>
                        <article class="card card--interactive reveal">
                            <div class="card__media">
                                <img src="<?= e($promo['image']) ?>" alt="" loading="lazy" decoding="async">
                                <span class="sticker card__media-sticker">
                                    <span class="sticker__value"><?= e($promo['discount']) ?></span>
                                    <span class="sticker__label">скидка</span>
                                </span>
                            </div>
                            <div class="card__body">
                                <h3 class="card__title"><?= e($promo['title']) ?></h3>
                                <p class="card__text"><?= e($promo['summary']) ?></p>
                                <div class="card__footer">
                                    <button type="button" class="btn btn--ghost btn--sm" data-promo="<?= e($promo['id']) ?>">Условия</button>
                                    <button type="button" class="btn btn--sun btn--sm" data-book-location="<?= e((string) $promo['locationId']) ?>">Забрать скидку</button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ЦЕНЫ -->
        <section id="prices" class="section">
            <div class="container container--narrow">
                <div class="section-head section-head--center">
                    <p class="eyebrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        Прозрачные цены
                    </p>
                    <h2 class="section-title">Сколько стоит праздник</h2>
                    <p class="section-lede">Базовые тарифы. Итоговая сумма зависит от локации, дня недели и набора услуг.</p>
                </div>

                <table class="price-table">
                    <caption>Цены действуют до конца текущего сезона</caption>
                    <thead>
                        <tr>
                            <th scope="col">Услуга</th>
                            <th scope="col" class="price-table__value">Стоимость</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($priceRows as $row): ?>
                            <tr>
                                <th scope="row">
                                    <?= e($row['service']) ?>
                                    <span class="price-table__note"><?= e($row['note']) ?></span>
                                </th>
                                <td class="price-table__value"><?= e($row['value']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="cluster" style="justify-content:center; margin-top: var(--space-6);">
                    <button type="button" class="btn btn--primary btn--lg" data-modal-open="bookingModal">
                        Рассчитать мой праздник
                    </button>
                </p>
            </div>
        </section>

        <!-- ОТЗЫВЫ -->
        <section id="reviews" class="section section--sunken">
            <div class="container">
                <div class="section-head section-head--center">
                    <p class="eyebrow eyebrow--amber">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
                        Отзывы родителей
                    </p>
                    <h2 class="section-title">4.9 из 5 — и вот <span class="swash">почему</span></h2>
                    <p class="section-lede">Собираем обратную связь после каждого праздника и публикуем без правок.</p>
                </div>

                <?php
                $reviews = [
                    ['images/people/6.jpg', 'Светлана', 'День рождения, 7 лет · Озеро Красилово',
                     'Дети в бурных эмоциях, аниматор отработал так, что даже взрослые включились в игры. Отдельное спасибо за то, что всё было готово к нашему приезду.'],
                    ['images/people/8.jpg', 'Марина', 'День рождения, 3 года · Поляна сказок',
                     'Ребёнку исполнилось три года, боялись, что программа будет слишком взрослой. Сценарий подобрали по возрасту, а шоу мыльных пузырей — отдельный восторг.'],
                    ['images/people/9.jpg', 'Алёна', 'Выпускной 4 класса · Чёрная жемчужина',
                     'Заказывали выпускной для класса — 28 человек. Понравилось, что стоимость была понятна заранее и на месте ничего не пришлось доплачивать.'],
                ];
                ?>
                <div class="grid grid--3">
                    <?php foreach ($reviews as [$avatar, $name, $detail, $text]): ?>
                        <figure class="quote reveal" style="margin:0;">
                            <div class="rating-stars" aria-label="Оценка 5 из 5">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
                                <?php endfor; ?>
                            </div>
                            <blockquote class="quote__text" style="margin:0;"><?= e($text) ?></blockquote>
                            <figcaption class="quote__author">
                                <img class="quote__avatar" src="<?= e($avatar) ?>" alt="" loading="lazy">
                                <span>
                                    <span class="quote__name"><?= e($name) ?></span>
                                    <span class="quote__detail"><?= e($detail) ?></span>
                                </span>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ПОДПИСКА -->
        <section class="section">
            <div class="container">
                <div class="cta">
                    <div>
                        <h2 class="cta__title">Сезонные даты разбирают за две недели</h2>
                        <p class="cta__text">
                            Подпишитесь, и мы напишем, когда откроется бронирование на новый сезон
                            и появятся скидки на будние дни. Не чаще двух писем в месяц.
                        </p>
                    </div>
                    <form class="cta__form" id="newsletterForm" novalidate>
                        <div class="field">
                            <label class="field__label" for="newsletterEmail">Email</label>
                            <input class="input" type="email" id="newsletterEmail" name="email"
                                   placeholder="name@example.com" required autocomplete="email">
                        </div>
                        <button type="submit" class="btn btn--sun" style="align-self: end;">Подписаться</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- Липкая панель на мобильных -->
    <div class="sticky-cta">
        <span class="sticky-cta__price">
            <span class="sticky-cta__label">Праздник от</span>
            <span class="sticky-cta__value"><?= money(min(array_column($allLocations, 'priceFrom')) ?: 0) ?></span>
        </span>
        <button type="button" class="btn btn--primary" data-modal-open="bookingModal">Забронировать</button>
    </div>

    <!-- Условия акции -->
    <div class="modal" id="promoModal" aria-hidden="true">
        <button type="button" class="modal__backdrop" tabindex="-1" aria-label="Закрыть окно"></button>
        <div class="modal__panel" aria-labelledby="promoModalTitle">
            <div class="modal__header">
                <h2 class="modal__title" id="promoModalTitle">Условия акции</h2>
                <button type="button" class="modal__close" data-modal-close aria-label="Закрыть окно">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal__body" id="promoModalBody"></div>
            <div class="modal__footer">
                <button type="button" class="btn btn--primary btn--block" id="promoModalAction" data-book-location="">
                    Забронировать по акции
                </button>
            </div>
        </div>
    </div>

<?php require __DIR__ . '/includes/footer.php'; ?>
