<?php
/**
 * install.php — создаёт базу, таблицы и заливает стартовый каталог.
 *
 * Запускать один раз: http://localhost/partyPrak/install.php
 * Повторный запуск безопасен — таблицы создаются через IF NOT EXISTS,
 * а данные добавляются только в пустые таблицы.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/security.php';

send_security_headers();

$steps = [];
$failed = false;

/** Записывает результат шага, чтобы показать таблицей в конце. */
function step(string $title, string $status, string $detail = ''): void
{
    global $steps;
    $steps[] = ['title' => $title, 'status' => $status, 'detail' => $detail];
}

try {
    // 1. База -------------------------------------------------------------
    $server = db(false);
    $server->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        DB_NAME
    ));
    step('База данных «' . DB_NAME . '»', 'ok', 'создана или уже существовала');

    // Переподключаемся, теперь уже к самой базе.
    $server->exec('USE `' . DB_NAME . '`');
    $db = $server;

    // 2. Таблицы ----------------------------------------------------------
    foreach (schema_tables() as $name => $ddl) {
        $db->exec($ddl);
        step('Таблица ' . $name, 'ok', 'готова');
    }

    // 3. Данные -----------------------------------------------------------
    $seed = schema_seed();

    $count = (int) $db->query('SELECT COUNT(*) FROM locations')->fetchColumn();
    if ($count === 0) {
        $ins = $db->prepare(
            'INSERT INTO locations (slug,name,region,address,image,plan,summary,tags,gallery,free_zones,price_from,capacity,sort_order)
             VALUES (:slug,:name,:region,:address,:image,:plan,:summary,:tags,:gallery,:free_zones,:price_from,:capacity,:sort_order)'
        );
        foreach ($seed['locations'] as $row) {
            $row['tags']    = json_encode($row['tags'], JSON_UNESCAPED_UNICODE);
            $row['gallery'] = json_encode($row['gallery'], JSON_UNESCAPED_UNICODE);
            $ins->execute($row);
        }
        step('Локации', 'ok', count($seed['locations']) . ' записей добавлено');
    } else {
        step('Локации', 'skip', "в таблице уже $count записей — не трогаем");
    }

    $count = (int) $db->query('SELECT COUNT(*) FROM zones')->fetchColumn();
    if ($count === 0) {
        $ins = $db->prepare(
            'INSERT INTO zones (slug,name,price,capacity,per_person,is_booked,sort_order)
             VALUES (:slug,:name,:price,:capacity,:per_person,:is_booked,:sort_order)'
        );
        foreach ($seed['zones'] as $row) {
            $ins->execute($row);
        }
        step('Зоны', 'ok', count($seed['zones']) . ' записей добавлено');
    } else {
        step('Зоны', 'skip', "уже заполнена ($count)");
    }

    $count = (int) $db->query('SELECT COUNT(*) FROM services')->fetchColumn();
    if ($count === 0) {
        $ins = $db->prepare(
            'INSERT INTO services (slug,name,price,unit,hours,note,sort_order)
             VALUES (:slug,:name,:price,:unit,:hours,:note,:sort_order)'
        );
        foreach ($seed['services'] as $row) {
            $ins->execute($row);
        }
        step('Услуги', 'ok', count($seed['services']) . ' записей добавлено');
    } else {
        step('Услуги', 'skip', "уже заполнена ($count)");
    }

    $count = (int) $db->query('SELECT COUNT(*) FROM promos')->fetchColumn();
    if ($count === 0) {
        $ins = $db->prepare(
            'INSERT INTO promos (slug,discount,title,summary,image,location_id,details,fineprint,sort_order)
             VALUES (:slug,:discount,:title,:summary,:image,:location_id,:details,:fineprint,:sort_order)'
        );
        $findLocation = $db->prepare('SELECT id FROM locations WHERE slug = ? LIMIT 1');

        foreach ($seed['promos'] as $row) {
            $locationId = null;
            if (!empty($row['location_slug'])) {
                $findLocation->execute([$row['location_slug']]);
                $found = $findLocation->fetchColumn();
                $locationId = $found === false ? null : (int) $found;
            }
            unset($row['location_slug']);
            $row['location_id'] = $locationId;
            $row['details']     = json_encode($row['details'], JSON_UNESCAPED_UNICODE);
            $ins->execute($row);
        }
        step('Акции', 'ok', count($seed['promos']) . ' записей добавлено');
    } else {
        step('Акции', 'skip', "уже заполнена ($count)");
    }

    $count = (int) $db->query('SELECT COUNT(*) FROM price_list')->fetchColumn();
    if ($count === 0) {
        $ins = $db->prepare(
            'INSERT INTO price_list (service,note,value,sort_order) VALUES (:service,:note,:value,:sort_order)'
        );
        foreach ($seed['price_list'] as $row) {
            $ins->execute($row);
        }
        step('Прайс-лист', 'ok', count($seed['price_list']) . ' строк добавлено');
    } else {
        step('Прайс-лист', 'skip', "уже заполнена ($count)");
    }

} catch (Throwable $e) {
    $failed = true;
    step('Ошибка', 'fail', $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Установка — Праздник в каждый дом</title>
    <style>
        body { font: 16px/1.6 system-ui, sans-serif; max-width: 46rem; margin: 3rem auto; padding: 0 1.5rem; color: #2b0a45; background: #fffaf3; }
        h1 { font-size: 1.8rem; }
        table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; background: #fff; border-radius: 12px; overflow: hidden; }
        td { padding: .7rem 1rem; border-bottom: 1px solid #f2e2ee; }
        tr:last-child td { border-bottom: 0; }
        .ok   { color: #087268; font-weight: 700; }
        .skip { color: #6b5f7d; }
        .fail { color: #c2154f; font-weight: 700; }
        .btn { display: inline-block; margin-top: 1rem; padding: .8rem 1.6rem; background: #c2154f; color: #fff; border-radius: 999px; text-decoration: none; font-weight: 700; }
        .note { background: #fff2d6; padding: 1rem 1.2rem; border-radius: 12px; font-size: .9rem; }
        code { background: #fff2e4; padding: .1em .4em; border-radius: 4px; }
    </style>
</head>
<body>
    <h1><?= $failed ? 'Установка прервана' : 'Установка завершена' ?></h1>

    <table>
        <?php foreach ($steps as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="<?= $s['status'] ?>">
                    <?= ['ok' => 'готово', 'skip' => 'пропущено', 'fail' => 'ошибка'][$s['status']] ?>
                </td>
                <td><?= htmlspecialchars($s['detail'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (!$failed): ?>
        <p class="note">
            Каталог в базе. Установщик больше не нужен — удалите <code>install.php</code>,
            чтобы никто не мог его запустить.
        </p>
        <p>
            <a class="btn" href="index.php">Открыть сайт</a>
        </p>
    <?php else: ?>
        <p class="note">
            Проверьте, что в панели XAMPP запущен <strong>MySQL</strong>, и что логин/пароль
            в <code>config.php</code> совпадают с вашими (по умолчанию <code>root</code> без пароля).
        </p>
    <?php endif; ?>
</body>
</html>
