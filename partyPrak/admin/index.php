<?php
/**
 * index.php — сводка панели менеджера.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
admin_require_login();

require_once __DIR__ . '/../includes/repo.php';
require_once __DIR__ . '/layout.php';

$stats  = admin_stats();
$latest = array_slice(admin_get_bookings(), 0, 8);

$statusLabels = [
    'new'       => 'Новая',
    'confirmed' => 'Подтверждена',
    'done'      => 'Проведена',
    'cancelled' => 'Отменена',
];

admin_head('Сводка', 'index.php');
?>

<div class="admin__stats">
    <div class="admin__stat">
        <span class="admin__stat-value"><?= $stats['bookings_new'] ?></span>
        <span class="admin__stat-label">новых заявок</span>
    </div>
    <div class="admin__stat">
        <span class="admin__stat-value"><?= $stats['bookings_total'] ?></span>
        <span class="admin__stat-label">заявок всего</span>
    </div>
    <div class="admin__stat">
        <span class="admin__stat-value"><?= money($stats['revenue']) ?></span>
        <span class="admin__stat-label">сумма подтверждённых</span>
    </div>
    <div class="admin__stat">
        <span class="admin__stat-value"><?= $stats['locations'] ?></span>
        <span class="admin__stat-label">активных локаций</span>
    </div>
</div>

<h2 class="admin__section-title">Последние заявки</h2>

<?php if (!$latest): ?>
    <p class="admin__empty">Заявок пока нет. Они появятся здесь сразу после оформления на сайте.</p>
<?php else: ?>
    <div class="admin__table-wrap">
        <table class="admin__table">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Создана</th>
                    <th>Клиент</th>
                    <th>Локация</th>
                    <th>Дата</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latest as $b): ?>
                    <tr>
                        <td><?= (int) $b['id'] ?></td>
                        <td><?= e(date('d.m.Y H:i', strtotime($b['created_at']))) ?></td>
                        <td>
                            <?= e($b['customer']) ?><br>
                            <span class="admin__muted"><?= e($b['phone']) ?></span>
                        </td>
                        <td><?= e($b['location_name'] ?? '—') ?></td>
                        <td><?= $b['event_date'] ? e(format_date($b['event_date'])) : '—' ?></td>
                        <td><?= money((int) $b['total']) ?></td>
                        <td><span class="admin__status admin__status--<?= e($b['status']) ?>"><?= e($statusLabels[$b['status']]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p style="margin-top: var(--space-5);">
        <a class="btn btn--secondary btn--sm" href="bookings.php">Все заявки</a>
    </p>
<?php endif; ?>

<?php admin_foot(); ?>
