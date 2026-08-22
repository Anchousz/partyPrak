<?php
/**
 * header.php — <head>, шапка сайта. Подключается первой строкой каждой страницы.
 *
 * Ожидает заранее объявленные переменные:
 *   $pageTitle       — <title>
 *   $pageDescription — meta description
 *   $bodyClass       — доп. класс на <body> (необязательно)
 *   $noindex         — true для служебных страниц (необязательно)
 *   $navLinks        — массив ['href' => 'подпись'] для меню
 *   $navCta          — ['type' => 'modal'|'link', 'href' => ..., 'label' => ...]
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/security.php';

send_security_headers();

/*
 * Сессию стартуем здесь, до первого байта HTML. site_csrf_token() вызывается
 * позже, уже внутри footer.php (форма быстрой заявки лежит там) — но к тому
 * моменту страница уже начала выводиться, и session_start() из середины
 * вывода не может отправить Set-Cookie: PHP отдаёт «headers already sent»
 * прямо в разметку. app_start_session() идемпотентна, так что здесь можно
 * дёрнуть её заранее, а footer.php спокойно вызовет site_csrf_token()
 * повторно — сессия уже будет открыта.
 */
app_start_session();

$pageTitle       = $pageTitle       ?? 'Праздник в каждый дом';
$pageDescription = $pageDescription ?? 'Организация детских праздников на подготовленных площадках.';
$bodyClass       = $bodyClass       ?? '';
$noindex         = $noindex         ?? false;
$headerModifier  = $headerModifier  ?? '';

$navLinks = $navLinks ?? [
    'index.php#locations'  => 'Локации',
    'index.php#steps'      => 'Как заказать',
    'index.php#promotions' => 'Акции',
    'index.php#prices'     => 'Цены',
    'index.php#reviews'    => 'Отзывы',
];

$fontsUrl = 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Unbounded:wght@700;800&display=swap';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#2b0a45">
    <?php if ($noindex): ?>
        <meta name="robots" content="noindex">
    <?php endif; ?>

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:image" content="images/location/2.jpg">
    <meta property="og:locale" content="ru_RU">

    <script>document.documentElement.classList.add('js');</script>
    <link rel="icon" href="images/brand/1.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Шрифты грузятся асинхронно: страница не ждёт стороннего сервера
         перед первой отрисовкой, а заголовки первые доли секунды рисуются
         системным Trebuchet MS и подменяются на Unbounded без рывка layout. -->
    <link rel="preload" as="style" href="<?= e($fontsUrl) ?>">
    <link rel="stylesheet" href="<?= e($fontsUrl) ?>" media="print" onload="this.media='all'; this.onload=null;">
    <noscript><link rel="stylesheet" href="<?= e($fontsUrl) ?>"></noscript>
    <link rel="stylesheet" href="css/tokens.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/pages.css">
</head>
<body<?= $bodyClass ? ' class="' . e($bodyClass) . '"' : '' ?>>
    <a class="skip-link" href="#main">Перейти к содержимому</a>

    <header class="header<?= $headerModifier ? ' ' . e($headerModifier) : '' ?>">
        <div class="container">
            <div class="header__inner">
                <a class="brand" href="index.php">
                    <img class="brand__mark" src="images/brand/1.jpg" alt="" width="44" height="44">
                    <span>
                        <span class="brand__name">Праздник в каждый дом</span>
                        <span class="brand__tag">Волшебство для ваших детей</span>
                    </span>
                </a>

                <nav class="nav" id="primaryNav" aria-label="Основная навигация">
                    <ul class="nav__list">
                        <?php foreach ($navLinks as $href => $label): ?>
                            <li><a class="nav__link" href="<?= e($href) ?>"><?= e($label) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="nav__cta">
                        <?php if (($navCta['type'] ?? 'modal') === 'modal'): ?>
                            <button type="button" class="btn btn--primary btn--sm" data-modal-open="bookingModal">
                                <?= e($navCta['label'] ?? 'Забронировать') ?>
                            </button>
                        <?php else: ?>
                            <a class="btn btn--secondary btn--sm" href="<?= e($navCta['href']) ?>">
                                <?= e($navCta['label']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </nav>

                <button type="button" class="nav__toggle" aria-label="Меню" aria-expanded="false" aria-controls="primaryNav">
                    <span class="nav__toggle-bars"></span>
                </button>
            </div>
        </div>
    </header>
