<?php
/**
 * login.php — вход в панель менеджера.
 *
 * После 5 неверных попыток подряд блокирует вход на минуту — не даёт
 * перебирать пароль скриптом, при этом не мешает человеку, который просто
 * пару раз ошибся.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

send_security_headers();

if (admin_is_logged_in()) {
    header('Location: index.php');
    exit;
}

const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_SECONDS = 60;

app_start_session();
$_SESSION['login_attempts'] ??= 0;
$_SESSION['login_locked_until'] ??= 0;

$error = '';
$lockedFor = max(0, $_SESSION['login_locked_until'] - time());

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockedFor <= 0) {
    $password = (string) ($_POST['password'] ?? '');

    if (admin_check_password($password)) {
        $_SESSION['login_attempts'] = 0;
        admin_login();
        header('Location: index.php');
        exit;
    }

    $_SESSION['login_attempts']++;
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_locked_until'] = time() + LOGIN_LOCKOUT_SECONDS;
        $_SESSION['login_attempts'] = 0;
        $lockedFor = LOGIN_LOCKOUT_SECONDS;
    }

    // Небольшая задержка притормаживает перебор пароля даже до блокировки.
    usleep(400000);
    $error = 'Неверный пароль.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'Слишком много попыток. Подождите ' . $lockedFor . ' сек.';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Вход в панель</title>
    <link rel="icon" href="../images/brand/1.jpg">
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin admin--login">
    <form class="admin__login" method="post" action="login.php">
        <img src="../images/brand/1.jpg" alt="" width="56" height="56" class="admin__login-logo">
        <h1 class="admin__login-title">Панель менеджера</h1>
        <p class="admin__login-hint">Доступ только по прямой ссылке и по паролю.</p>

        <?php if ($error !== ''): ?>
            <p class="admin__flash admin__flash--err"><?= e($error) ?></p>
        <?php endif; ?>

        <div class="field">
            <label class="field__label" for="password">Пароль</label>
            <input class="input" type="password" id="password" name="password"
                   required autofocus autocomplete="current-password" <?= $lockedFor > 0 ? 'disabled' : '' ?>>
        </div>

        <button type="submit" class="btn btn--primary btn--block btn--lg" style="margin-top: var(--space-5);"
                <?= $lockedFor > 0 ? 'disabled' : '' ?>>
            Войти
        </button>

        <p class="admin__login-back"><a href="../index.php">Вернуться на сайт</a></p>
    </form>
</body>
</html>
