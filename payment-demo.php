<?php
$orderId = isset($_GET['order']) ? preg_replace('/[^a-zA-Z0-9\-]/', '', $_GET['order']) : '';
$BLIK_PHONE = '123 456 789';
$ordersFile = __DIR__ . '/orders-data.php';
$order = null;

if ($orderId && file_exists($ordersFile)) {
  $data = include $ordersFile;
  if (is_array($data) && isset($data['orders']) && is_array($data['orders'])) {
    foreach ($data['orders'] as $item) {
      if (isset($item['id']) && $item['id'] === $orderId) {
        $order = $item;
        break;
      }
    }
  }
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v) { return number_format((float)$v, 2, ',', ' ') . ' zł'; }
$amount = $order ? money($order['total'] ?? 0) : 'kwota z zamówienia';
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BLIK na telefon — Kebab-demo</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#fff6df;color:#20130d;display:grid;place-items:center;min-height:100vh;padding:20px}.card{max-width:620px;background:#fff;border:1px solid #eadfcd;border-radius:26px;padding:28px;box-shadow:0 18px 48px #0002}.brand{font-weight:900;color:#d71920}.btn{display:inline-block;border-radius:999px;padding:12px 18px;background:#ffbc0d;color:#20130d;font-weight:900;text-decoration:none}.muted{color:#756154;line-height:1.55}.box{background:#fff0c2;padding:16px;border-radius:18px;margin:16px 0}.row{display:flex;justify-content:space-between;gap:16px;border-bottom:1px solid #eadfcd;padding:10px 0}.row strong{text-align:right}.warn{background:#ffe0e0;color:#a81017;padding:12px 14px;border-radius:16px;font-weight:800}@media(max-width:560px){.row{display:grid}.row strong{text-align:left}}
</style>
</head>
<body>
  <main class="card">
    <p class="brand">Kebab-demo</p>
    <h1>BLIK na telefon</h1>
    <p class="muted">Ręczna płatność do pokazania w wersji demonstracyjnej. Po przelewie status płatności zmieniasz w panelu zamówień.</p>

    <div class="box">
      <div class="row"><span>Numer telefonu</span><strong><?=h($BLIK_PHONE)?></strong></div>
      <div class="row"><span>Kwota</span><strong><?=h($amount)?></strong></div>
      <div class="row"><span>Tytuł</span><strong><?=h($orderId ?: 'numer zamówienia')?></strong></div>
    </div>

    <?php if($order): ?>
      <p><b>Zamówienie:</b> <?=h($order['id'])?></p>
      <p><b>Status płatności:</b> <?=h($order['payment_status'] ?? 'oczekuje na płatność')?></p>
    <?php else: ?>
      <p class="warn">Nie znaleziono zamówienia. Wróć na stronę i złóż zamówienie ponownie.</p>
    <?php endif; ?>

    <p class="muted">W prawdziwej realizacji numer telefonu podmieniasz na numer właściciela lokalu.</p>
    <p><a class="btn" href="index.html">Wróć na stronę</a> <a class="btn" href="admin-orders.php">Panel zamówień</a></p>
  </main>
</body>
</html>
