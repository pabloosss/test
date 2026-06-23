<?php
require __DIR__ . '/app-config.php';
if (session_status() === PHP_SESSION_NONE) @session_start();

$ORDERS_STORE = __DIR__ . '/orders-data.php';
$AUTH_COOKIE = 'kk_admin_auth';
$statusList = array('nowe', 'przyjęte', 'w realizacji', 'gotowe', 'odebrane', 'anulowane');
$paymentStatusList = array('płatność przy odbiorze', 'oczekuje na płatność', 'opłacone', 'nieudane', 'zwrot');

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v) { return number_format((float)$v, 2, ',', ' ') . ' zł'; }
function auth_token() { return hash_hmac('sha256', ADMIN_USER . '|' . ADMIN_PASSWORD_HASH, ADMIN_PASSWORD_HASH); }
function logged() {
  global $AUTH_COOKIE;
  if (!empty($_SESSION['admin_logged'])) return true;
  if (!empty($_COOKIE[$AUTH_COOKIE]) && hash_equals(auth_token(), $_COOKIE[$AUTH_COOKIE])) {
    $_SESSION['admin_logged'] = true;
    return true;
  }
  return false;
}
function load_orders($file) {
  if (file_exists($file)) {
    $data = include $file;
    if (is_array($data) && isset($data['orders']) && is_array($data['orders'])) return $data;
  }
  return array('orders' => array());
}
function save_orders($file, $data) {
  if (file_exists($file) && !is_writable($file)) @chmod($file, 0666);
  $txt = "<?php\nreturn " . var_export($data, true) . ";\n";
  return file_put_contents($file, $txt, LOCK_EX) !== false;
}

if (!logged()) {
  http_response_code(403);
  echo '<!doctype html><meta charset="utf-8"><body style="font-family:Arial;padding:30px"><h1>Brak dostępu</h1><p>Najpierw zaloguj się w <a href="admin.php">panelu admina</a>.</p></body>';
  exit;
}

$notice = '';
$orders = load_orders($ORDERS_STORE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = isset($_POST['id']) ? trim($_POST['id']) : '';
  $action = isset($_POST['action']) ? trim($_POST['action']) : '';

  foreach ($orders['orders'] as &$order) {
    if (!isset($order['id']) || $order['id'] !== $id) continue;

    if ($action === 'status') {
      $status = isset($_POST['status']) ? trim($_POST['status']) : 'nowe';
      if (in_array($status, $statusList, true)) $order['status'] = $status;
      $order['updated_at'] = date('Y-m-d H:i:s');
      $notice = 'Status zamówienia zmieniony.';
    }

    if ($action === 'payment') {
      $payment = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : 'oczekuje na płatność';
      if (in_array($payment, $paymentStatusList, true)) $order['payment_status'] = $payment;
      $order['paid'] = ($payment === 'opłacone');
      $order['updated_at'] = date('Y-m-d H:i:s');
      $notice = 'Status płatności zmieniony.';
    }
  }
  unset($order);
  save_orders($ORDERS_STORE, $orders);
}

$totalOrders = count($orders['orders']);
$newOrders = 0;
$todayTotal = 0;
$paidTotal = 0;
$today = date('Y-m-d');
foreach ($orders['orders'] as $order) {
  if (($order['status'] ?? '') === 'nowe') $newOrders++;
  if (strpos($order['created_at'] ?? '', $today) === 0) $todayTotal += (float)($order['total'] ?? 0);
  if (!empty($order['paid'])) $paidTotal += (float)($order['total'] ?? 0);
}
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Zamówienia — Kebab-demo</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#fff6df;color:#20130d}.wrap{width:min(1200px,calc(100% - 32px));margin:auto}.top{background:#fff;border-bottom:1px solid #eadfcd}.top .wrap{min-height:72px;display:flex;align-items:center;justify-content:space-between;gap:12px}.logo{font-size:24px;font-weight:900;color:#d71920;text-decoration:none}.btn,button{border:0;border-radius:999px;padding:10px 14px;background:#ffbc0d;color:#20130d;font-weight:900;text-decoration:none;cursor:pointer;display:inline-block}.dark{background:#20130d;color:#fff}.main{padding:28px 0 80px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}.stat,.order{background:#fff;border:1px solid #eadfcd;border-radius:22px;padding:18px;box-shadow:0 12px 32px #0001}.stat span{display:block;color:#756154;font-weight:800}.stat strong{display:block;margin-top:6px;font-size:1.7rem}.notice{background:#e8f8ee;color:#208a3a;padding:12px 16px;border-radius:16px;margin-bottom:16px;font-weight:900}.orders{display:grid;gap:14px}.order-head{display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap}.badges{display:flex;gap:8px;flex-wrap:wrap}.badge{padding:7px 10px;border-radius:999px;background:#f5ead6;font-weight:900}.paid{background:#e8f8ee;color:#208a3a}.wait{background:#fff0c2;color:#8a4b00}.cancel{background:#ffe0e0;color:#a81017}.order pre{white-space:pre-wrap;background:#f5ead6;border-radius:14px;padding:12px;line-height:1.45}.forms{display:grid;grid-template-columns:1fr 1fr;gap:10px}select{width:100%;padding:10px;border:1px solid #ddd;border-radius:12px}@media(max-width:850px){.grid,.forms{grid-template-columns:1fr}.order-head{display:grid}}
</style>
</head>
<body>
<header class="top"><div class="wrap"><a class="logo" href="admin.php">Kebab-demo</a><nav><a class="btn dark" href="admin.php">Menu</a> <a class="btn" href="index.html">Strona</a></nav></div></header>
<main class="wrap main">
<h1>Zamówienia</h1>
<?php if($notice): ?><div class="notice"><?=h($notice)?></div><?php endif; ?>
<section class="grid">
  <div class="stat"><span>Wszystkie</span><strong><?=h($totalOrders)?></strong></div>
  <div class="stat"><span>Nowe</span><strong><?=h($newOrders)?></strong></div>
  <div class="stat"><span>Dzisiaj</span><strong><?=money($todayTotal)?></strong></div>
  <div class="stat"><span>Opłacone</span><strong><?=money($paidTotal)?></strong></div>
</section>
<section class="orders">
<?php if(empty($orders['orders'])): ?><div class="order"><p>Brak zamówień.</p></div><?php endif; ?>
<?php foreach($orders['orders'] as $order):
  $payStatus = $order['payment_status'] ?? 'płatność przy odbiorze';
  $payClass = !empty($order['paid']) ? 'paid' : ($payStatus === 'oczekuje na płatność' ? 'wait' : '');
  $orderClass = ($order['status'] ?? '') === 'anulowane' ? 'cancel' : '';
?>
<article class="order">
  <div class="order-head">
    <div><h2><?=h($order['id'] ?? '')?></h2><p><?=h($order['created_at'] ?? '')?> · <?=money($order['total'] ?? 0)?></p></div>
    <div class="badges"><span class="badge <?=h($orderClass)?>"><?=h($order['status'] ?? 'nowe')?></span><span class="badge <?=h($payClass)?>"><?=h($payStatus)?></span></div>
  </div>
  <p><b><?=h(($order['name'] ?? '') ?: 'brak imienia')?></b> · tel. <?=h($order['phone'] ?? '')?> · <?=h($order['mode'] ?? '')?> · <?=h($order['payment_method'] ?? 'Płatność przy odbiorze')?></p>
  <?php if(!empty($order['address'])): ?><p><b>Adres:</b> <?=h($order['address'])?></p><?php endif; ?>
  <pre><?=h($order['order_text'] ?? '')?></pre>
  <div class="forms">
    <form method="post"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?=h($order['id'] ?? '')?>"><select name="status" onchange="this.form.submit()"><?php foreach($statusList as $status): ?><option value="<?=h($status)?>" <?=($order['status'] ?? '')===$status?'selected':''?>><?=h($status)?></option><?php endforeach; ?></select></form>
    <form method="post"><input type="hidden" name="action" value="payment"><input type="hidden" name="id" value="<?=h($order['id'] ?? '')?>"><select name="payment_status" onchange="this.form.submit()"><?php foreach($paymentStatusList as $status): ?><option value="<?=h($status)?>" <?=($payStatus)===$status?'selected':''?>><?=h($status)?></option><?php endforeach; ?></select></form>
  </div>
</article>
<?php endforeach; ?>
</section>
</main>
</body>
</html>
