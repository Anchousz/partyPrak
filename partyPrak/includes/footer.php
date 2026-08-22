<?php
/**
 * footer.php — подвал, общие модальные окна и подключение скриптов.
 *
 * Ожидает необязательные переменные:
 *   $slimFooter    — true: только нижняя строка (для страниц оформления)
 *   $jsData        — массив, который уедет в window.PVD.data
 *   $pageScript    — путь к скрипту конкретной страницы
 *   $withBookingModal — выводить ли окно быстрой заявки
 */

declare(strict_types=1);

$slimFooter       = $slimFooter       ?? false;
$jsData           = $jsData           ?? [];
$pageScript       = $pageScript       ?? '';
$withBookingModal = $withBookingModal ?? false;
$locationsForForm = $locationsForForm ?? [];
?>

<footer class="footer">
    <?php if (!$slimFooter): ?>
        <span class="blob blob--violet" style="width:28rem; height:28rem; top:-10rem; right:-10rem; opacity:.35;" aria-hidden="true"></span>
        <div class="container">
            <div class="footer__grid">
                <div class="footer__brand">
                    <a class="brand" href="index.php">
                        <img class="brand__mark" src="images/brand/1.jpg" alt="" width="44" height="44">
                        <span>
                            <span class="brand__name">Праздник в каждый дом</span>
                            <span class="brand__tag">Волшебство для ваших детей</span>
                        </span>
                    </a>
                    <p class="footer__about">
                        Организация детских праздников на подготовленных площадках
                        в Алтайском крае, Красноярском крае, Московской и Ленинградской областях.
                    </p>
                </div>

                <div>
                    <h2 class="footer__col-title">Разделы</h2>
                    <ul class="footer__list">
                        <li><a href="index.php#locations">Локации</a></li>
                        <li><a href="index.php#prices">Цены</a></li>
                        <li><a href="index.php#promotions">Акции</a></li>
                        <li><a href="index.php#reviews">Отзывы</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="footer__col-title">Клиентам</h2>
                    <ul class="footer__list">
                        <li><a href="booking.php">Моя бронь</a></li>
                        <li><a href="index.php#steps">Как заказать</a></li>
                        <li><a href="index.php#search">Свободные даты</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="footer__col-title">Контакты</h2>
                    <a class="footer__phone" href="tel:88001112020">8 (800) 111-20-20</a>
                    <ul class="footer__list" style="margin-top: var(--space-3);">
                        <li><a href="mailto:hello@prazdnik.ru">hello@prazdnik.ru</a></li>
                        <li>Ежедневно, 9:00 — 21:00</li>
                    </ul>
                </div>
            </div>

            <div class="footer__bottom">
                <p>© <?= date('Y') ?> Праздник в каждый дом</p>
                <p>Учебный проект. Оплата не подключена.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="footer__bottom" style="padding-top:0; border:0;">
                <p>© <?= date('Y') ?> Праздник в каждый дом</p>
                <p><a href="tel:88001112020" style="color:inherit;">8 (800) 111-20-20</a></p>
            </div>
        </div>
    <?php endif; ?>
</footer>

<?php if ($withBookingModal): ?>
    <!-- Быстрая заявка -->
    <div class="modal" id="bookingModal" aria-hidden="true">
        <button type="button" class="modal__backdrop" tabindex="-1" aria-label="Закрыть окно"></button>
        <div class="modal__panel" aria-labelledby="bookingModalTitle">
            <div class="modal__header">
                <div>
                    <h2 class="modal__title" id="bookingModalTitle">Заявка на праздник</h2>
                    <p class="modal__subtitle">Перезвоним за 15 минут и подтвердим свободную дату.</p>
                </div>
                <button type="button" class="modal__close" data-modal-close aria-label="Закрыть окно">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="quickBookingForm" method="post" action="submit-quick.php" novalidate>
                <input type="hidden" name="csrf" value="<?= e(site_csrf_token()) ?>">
                <!-- Ловушка для ботов: настоящий человек это поле не видит и не заполнит. -->
                <div class="hp" aria-hidden="true">
                    <label for="quickWebsite">Оставьте это поле пустым</label>
                    <input type="text" id="quickWebsite" name="website" tabindex="-1" autocomplete="off">
                </div>
                <div class="modal__body">
                    <div class="stack">
                        <div class="field">
                            <label class="field__label" for="quickName">Ваше имя</label>
                            <input class="input" type="text" id="quickName" name="name"
                                   placeholder="Иван Иванов" required autocomplete="name" maxlength="160">
                        </div>
                        <div class="field">
                            <label class="field__label" for="quickPhone">Телефон</label>
                            <input class="input" type="tel" id="quickPhone" name="phone"
                                   placeholder="+7 999 000-00-00" required autocomplete="tel" maxlength="40">
                        </div>
                        <div class="panel__grid">
                            <div class="field">
                                <label class="field__label" for="quickDate">Дата праздника</label>
                                <input class="input" type="date" id="quickDate" name="date" required min="<?= today() ?>">
                            </div>
                            <div class="field">
                                <label class="field__label" for="quickChildren">Детей</label>
                                <input class="input" type="number" id="quickChildren" name="children" min="1" max="50" value="8">
                            </div>
                        </div>
                        <div class="field">
                            <label class="field__label" for="quickLocation">Локация</label>
                            <select class="input" id="quickLocation" name="location">
                                <option value="">Подберите за меня</option>
                                <?php foreach ($locationsForForm as $loc): ?>
                                    <option value="<?= e($loc['slug']) ?>">
                                        <?= e($loc['name']) ?> — <?= e($loc['region']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label class="field__label" for="quickComment">Пожелания</label>
                            <textarea class="input" id="quickComment" name="comment" rows="3"
                                      placeholder="Тема праздника, аллергии, время начала" maxlength="1000"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal__footer">
                    <button type="submit" class="btn btn--primary btn--block btn--lg">Отправить заявку</button>
                    <p class="summary__fineprint">Нажимая кнопку, вы соглашаетесь на обработку персональных данных.</p>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="toast-region" role="status" aria-live="polite"></div>

<script>
    window.PVD = window.PVD || {};
    window.PVD.data = <?= json_for_html($jsData) ?>;
</script>
<script src="js/ui.js"></script>
<?php if ($pageScript): ?>
    <script src="<?= e($pageScript) ?>"></script>
<?php endif; ?>
</body>
</html>
