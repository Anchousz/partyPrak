<?php
/**
 * zones.php — переключение занятости зон.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
admin_require_login();

require_once __DIR__ . '/../includes/repo.php';
require_once __DIR__ . '/layout.php';

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    admin_toggle_zone($id);
    $flash = 'Занятость зоны обновлена.';
}

$zones = admin_get_zones();
$csrf  = admin_csrf_token();

admin_head('Зоны', 'zones.php');
admin_flash($flash);
?>

<p class="admin__muted" style="margin-bottom: var(--space-5);">
    Занятая зона показывается на сайте как недоступная и не может быть выбрана при бронировании.
</p>

<div class="admin__table-wrap">
    <table class="admin__table">
        <thead>
            <tr>
                <th>Зона</th>
                <th>Цена</th>
                <th>Тариф</th>
                <th>Вместимость</th>
                <th>Состояние</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($zones as $z): ?>
                <tr>
                    <td><strong><?= e($z['name']) ?></strong></td>
                    <td><?= money((int) $z['price']) ?></td>
                    <td><?= $z['per_person'] ? 'за гостя' : 'за день' ?></td>
                    <td><?= (int) $z['capacity'] ?></td>
                    <td>
                        <span class="admin__status admin__status--<?= $z['is_booked'] ? 'cancelled' : 'confirmed' ?>">
                            <?= $z['is_booked'] ? 'Занята' : 'Свободна' ?>
                        </span>
                    </td>
                    <td>
                        <form method="post" class="admin__row-form">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= (int) $z['id'] ?>">
                            <button type="submit" class="btn btn--secondary btn--sm">
                                <?= $z['is_booked'] ? 'Освободить' : 'Занять' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php admin_foot(); ?>
