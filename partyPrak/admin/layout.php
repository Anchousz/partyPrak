<?php
/**
 * layout.php — общая обвязка страниц админки.
 * admin_head() открывает документ, admin_foot() закрывает.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

function admin_head(string $title, string $active = ''): void
{
    send_security_headers();
    $nav = [
        'index.php'     => 'Сводка',
        'bookings.php'  => 'Заявки',
        'locations.php' => 'Локации',
        'zones.php'     => 'Зоны',
    ];
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — админка</title>
    <link rel="icon" href="../images/brand/1.jpg">
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin">
    <header class="admin__bar">
        <div class="admin__brand">
            <img src="../images/brand/1.jpg" alt="" width="34" height="34">
            <span>Панель менеджера</span>
        </div>
        <nav class="admin__nav">
            <?php foreach ($nav as $href => $label): ?>
                <a href="<?= e($href) ?>" class="admin__link<?= $active === $href ? ' is-active' : '' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin__actions">
            <a class="btn btn--ghost btn--sm" href="../index.php" target="_blank" rel="noopener">Сайт</a>
            <a class="btn btn--secondary btn--sm" href="logout.php">Выйти</a>
        </div>
    </header>
    <main class="admin__main">
        <h1 class="admin__title"><?= e($title) ?></h1>
<?php
}

function admin_foot(): void
{
    ?>
    </main>
    <script src="../js/ui.js"></script>
</body>
</html>
<?php
}

/** Плашка с результатом действия. */
function admin_flash(string $message, string $type = 'ok'): void
{
    if ($message === '') {
        return;
    }
    echo '<p class="admin__flash admin__flash--' . e($type) . '">' . e($message) . '</p>';
}
