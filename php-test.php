<?php
require __DIR__ . '/app-config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: text/html; charset=utf-8');
$canWriteData = is_writable(DATA_DIR) || (!is_dir(DATA_DIR) && is_writable(__DIR__));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test PHP</title>
  <style>body{font-family:Arial,sans-serif;background:#fff6df;color:#20130d;padding:30px}.box{max-width:720px;background:#fff;border-radius:20px;padding:22px;box-shadow:0 14px 40px #0001}.ok{color:#208a3a;font-weight:900}.bad{color:#a81017;font-weight:900}</style>
</head>
<body>
  <div class="box">
    <h1>Test PHP dla strony kebaba</h1>
    <p>Jeżeli widzisz tę stronę jako normalną stronę, PHP działa.</p>
    <p>PHP: <span class="ok"><?=htmlspecialchars(PHP_VERSION)?></span></p>
    <p>Sesja: <span class="<?= session_status() === PHP_SESSION_ACTIVE ? 'ok' : 'bad' ?>"><?= session_status() === PHP_SESSION_ACTIVE ? 'działa' : 'problem' ?></span></p>
    <p>Folder data: <span class="<?= $canWriteData ? 'ok' : 'bad' ?>"><?= $canWriteData ? 'zapis OK' : 'brak zapisu' ?></span></p>
    <p>Plik menu: <span class="<?= file_exists(MENU_FILE) ? 'ok' : 'bad' ?>"><?= file_exists(MENU_FILE) ? 'jest' : 'brak' ?></span></p>
    <p>Plik zamówień: <span class="<?= file_exists(ORDERS_FILE) ? 'ok' : 'bad' ?>"><?= file_exists(ORDERS_FILE) ? 'jest' : 'brak' ?></span></p>
    <p><a href="admin.php">Przejdź do admina</a></p>
  </div>
</body>
</html>
