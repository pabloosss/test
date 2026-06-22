<?php
$order = isset($_GET['order']) ? preg_replace('/[^a-zA-Z0-9\-]/', '', $_GET['order']) : '';
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Płatność online — demo</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#fff6df;color:#20130d;display:grid;place-items:center;min-height:100vh;padding:20px}.card{max-width:560px;background:#fff;border:1px solid #eadfcd;border-radius:26px;padding:28px;box-shadow:0 18px 48px #0002}.btn{display:inline-block;border-radius:999px;padding:12px 18px;background:#ffbc0d;color:#20130d;font-weight:900;text-decoration:none}.muted{color:#756154;line-height:1.55}.warn{background:#fff0c2;padding:12px 14px;border-radius:16px;font-weight:800}</style>
</head>
<body>
  <main class="card">
    <h1>Płatność online</h1>
    <p class="muted">To jest strona demonstracyjna. W tym miejscu później podłącza się prawdziwą bramkę płatności, np. Przelewy24, PayU albo Tpay.</p>
    <?php if($order): ?><p><b>Numer zamówienia:</b> <?=htmlspecialchars($order, ENT_QUOTES, 'UTF-8')?></p><?php endif; ?>
    <p class="warn">Demo nie pobiera pieniędzy. Status płatności zmienisz ręcznie w panelu zamówień.</p>
    <p><a class="btn" href="admin-orders.php">Panel zamówień</a> <a class="btn" href="index.html">Wróć na stronę</a></p>
  </main>
</body>
</html>
