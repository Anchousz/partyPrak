<?php
/**
 * bookings.php — список заявок, смена статуса и удаление.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
admin_require_login();

require_once __DIR__ . '/../includes/repo.php';
require_once __DIR__ . '/layout.php';

$statusLabels = [
    'new'       => 'Новая',
    'confirmed' => 'Подтверждена',
    'done'      => 'Проведена',
    'cancelled' => 'Отменена',
];

$flash = '';
$flashType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();

    $id     = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'status') {
        $flash = admin_set_booking_status($id, (string) ($_POST['status'] ?? ''))
            ? "Статус заявки №$id обновлён."
            : 'Не удалось изменить статус: неизвестное значение.';
        $flashType = str_starts_with($flash, 'Не') ? 'err' : 'ok';
    } elseif ($action === 'delete') {
        admin_delete_booking($id);
        $flash = "Заявка №$id удалена.";
    }
}

$filter   = (string) ($_GET['status'] ?? '');
$bookings = admin_get_bookings(isset($statusLabels[$filter]) ? $filter : '');
$csrf     = admin_csrf_token();

admin_head('Заявки', 'bookings.php');
admin_flash($flash, $flashType);
?>

<div class="admin__filters">
    <a class="admin__chip<?= $filter === '' ? ' is-active' : '' ?>" href="bookings.php">Все</a>
    <?php foreach ($statusLabels as $key => $label): ?>
        <a class="admin__chip<?= $filter === $key ? ' is-active' : '' ?>"
           href="bookings.php?status=<?= e($key) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if (!$bookings): ?>
    <p class="admin__empty">Заявок с таким статусом нет.</p>
<?php else: ?>
    <div class="admin__table-wrap">
        <table class="admin__table">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Клиент</th>
                    <th>Заказ</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <?php
                    $zones    = json_decode($b['zones'], true) ?: [];
                    $services = json_decode($b['services'], true) ?: [];
                    ?>
                    <tr>
                        <td>
                            <?= (int) $b['id'] ?><br>
                            <span class="admin__muted"><?= e(date('d.m.y H:i', strtotime($b['created_at']))) ?></span>
                        </td>
                        <td>
                            <strong><?= e($b['customer']) ?></strong><br>
                            <span class="admin__muted"><?= e($b['phone']) ?></span>
                            <?php if ($b['email'] !== ''): ?>
                                <br><span class="admin__muted"><?= e($b['email']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e($b['location_name'] ?? 'Локация не выбрана') ?><br>
                            <span class="admin__muted">
                                <?= $b['event_date'] ? e(format_date($b['event_date'])) : 'дата не указана' ?>,
                                <?= (int) $b['guests'] ?> <?= e(plural((int) $b['guests'], ['гость', 'гостя', 'гостей'])) ?>
                            </span>
                            <?php if ($zones || $services): ?>
                                <br><span class="admin__muted">
                                    <?= e(implode(', ', array_column($zones, 'name'))) ?>
                                    <?= $services ? ' · ' . e(implode(', ', array_column($services, 'name'))) : '' ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($b['comment'] !== ''): ?>
                                <br><span class="admin__muted">«<?= e($b['comment']) ?>»</span>
                            <?php endif; ?>
                        </td>
                        <td><?= money((int) $b['total']) ?></td>
                        <td><span class="admin__status admin__status--<?= e($b['status']) ?>"><?= e($statusLabels[$b['status']]) ?></span></td>
                        <td>
                            <form method="post" class="admin__row-form">
                                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <input type="hidden" name="action" value="status">
                                <select class="input input--sm" name="status" onchange="this.form.submit()">
                                    <?php foreach ($statusLabels as $key => $label): ?>
                                        <option value="<?= e($key) ?>" <?= $b['status'] === $key ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <form method="post" class="admin__row-form"
                                  onsubmit="return confirm('Удалить заявку №<?= (int) $b['id'] ?>? Это необратимо.');">
                                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn--ghost btn--sm">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php admin_foot(); ?>
