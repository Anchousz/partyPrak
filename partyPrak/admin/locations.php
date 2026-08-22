<?php
/**
 * locations.php — редактирование площадок.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
admin_require_login();

require_once __DIR__ . '/../includes/repo.php';
require_once __DIR__ . '/../includes/validate.php';
require_once __DIR__ . '/layout.php';

$flash = '';
$flashType = 'ok';
$formValues = null; // заполняется при ошибке валидации, чтобы не терять ввод

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();

    $id = (int) ($_POST['id'] ?? 0);

    $data = [
        'name'       => str_param($_POST, 'name', 120),
        'region'     => str_param($_POST, 'region', 120),
        'address'    => str_param($_POST, 'address', 255),
        'summary'    => str_param($_POST, 'summary', 1000),
        'free_zones' => int_param($_POST, 'free_zones', 0, 0, 99),
        'price_from' => int_param($_POST, 'price_from', 0, 0, 1000000),
        'capacity'   => int_param($_POST, 'capacity', 0, 0, 999),
        'is_active'  => isset($_POST['is_active']) ? 1 : 0,
    ];

    $fieldErrors = [];
    if ($id <= 0 || !admin_get_location($id))         { $fieldErrors[] = 'Локация не найдена.'; }
    if (mb_strlen($data['name']) < 2)                  { $fieldErrors[] = 'Название должно быть не короче 2 символов.'; }
    if ($data['region'] === '')                        { $fieldErrors[] = 'Укажите регион.'; }
    if ($data['address'] === '')                        { $fieldErrors[] = 'Укажите адрес.'; }

    if ($fieldErrors) {
        $flash      = implode(' ', $fieldErrors);
        $flashType  = 'err';
        $formValues = $data + ['id' => $id];
    } else {
        $ok = admin_update_location($id, $data);
        $flash     = $ok ? 'Локация сохранена.' : 'Не удалось сохранить локацию.';
        $flashType = $ok ? 'ok' : 'err';
    }
}

$editId    = $formValues['id'] ?? (int) ($_GET['edit'] ?? 0);
$editing   = $formValues ?? ($editId > 0 ? admin_get_location($editId) : null);
$locations = admin_get_locations();
$csrf      = admin_csrf_token();

admin_head('Локации', 'locations.php');
admin_flash($flash, $flashType);
?>

<?php if ($editing): ?>
    <form method="post" action="locations.php?edit=<?= (int) $editId ?>" class="admin__card" style="margin-bottom: var(--space-6);">
        <h2 class="admin__section-title" style="margin-top:0;">
            Редактирование: <?= e($editing['name']) ?>
        </h2>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int) $editId ?>">

        <div class="admin__form-grid">
            <div class="field">
                <label class="field__label" for="f-name">Название</label>
                <input class="input" id="f-name" name="name" value="<?= e($editing['name']) ?>" required minlength="2" maxlength="120">
            </div>
            <div class="field">
                <label class="field__label" for="f-region">Регион</label>
                <input class="input" id="f-region" name="region" value="<?= e($editing['region']) ?>" required maxlength="120">
            </div>
            <div class="field" style="grid-column: 1 / -1;">
                <label class="field__label" for="f-address">Адрес</label>
                <input class="input" id="f-address" name="address" value="<?= e($editing['address']) ?>" required maxlength="255">
            </div>
            <div class="field" style="grid-column: 1 / -1;">
                <label class="field__label" for="f-summary">Описание</label>
                <textarea class="input" id="f-summary" name="summary" rows="3" maxlength="1000"><?= e($editing['summary']) ?></textarea>
            </div>
            <div class="field">
                <label class="field__label" for="f-free">Свободных зон</label>
                <input class="input" type="number" id="f-free" name="free_zones" min="0" max="99" value="<?= (int) $editing['free_zones'] ?>">
            </div>
            <div class="field">
                <label class="field__label" for="f-price">Цена от, ₽</label>
                <input class="input" type="number" id="f-price" name="price_from" min="0" value="<?= (int) $editing['price_from'] ?>">
            </div>
            <div class="field">
                <label class="field__label" for="f-cap">Вместимость</label>
                <input class="input" type="number" id="f-cap" name="capacity" min="0" max="999" value="<?= (int) $editing['capacity'] ?>">
            </div>
            <div class="field">
                <label class="field__label">Показывать на сайте</label>
                <label class="check-card" style="padding: .6rem .8rem;">
                    <input type="checkbox" name="is_active" <?= !empty($editing['is_active']) ? 'checked' : '' ?>>
                    <span class="check-card__body"><span class="check-card__name">Активна</span></span>
                </label>
            </div>
        </div>

        <div class="cluster" style="margin-top: var(--space-5);">
            <button type="submit" class="btn btn--primary">Сохранить</button>
            <a class="btn btn--ghost" href="locations.php">Отмена</a>
        </div>
    </form>
<?php endif; ?>

<div class="admin__table-wrap">
    <table class="admin__table">
        <thead>
            <tr>
                <th>Название</th>
                <th>Регион</th>
                <th>Свободно</th>
                <th>Вместимость</th>
                <th>Цена от</th>
                <th>Статус</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($locations as $loc): ?>
                <tr>
                    <td>
                        <strong><?= e($loc['name']) ?></strong><br>
                        <span class="admin__muted"><?= e($loc['address']) ?></span>
                    </td>
                    <td><?= e($loc['region']) ?></td>
                    <td><?= (int) $loc['free_zones'] ?></td>
                    <td><?= (int) $loc['capacity'] ?></td>
                    <td><?= money((int) $loc['price_from']) ?></td>
                    <td>
                        <span class="admin__status admin__status--<?= $loc['is_active'] ? 'confirmed' : 'cancelled' ?>">
                            <?= $loc['is_active'] ? 'Активна' : 'Скрыта' ?>
                        </span>
                    </td>
                    <td><a class="btn btn--secondary btn--sm" href="locations.php?edit=<?= (int) $loc['id'] ?>">Изменить</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php admin_foot(); ?>
