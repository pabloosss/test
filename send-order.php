<?php
require __DIR__ . '/app-config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$ORDERS_STORE = __DIR__ . '/orders-data.php';
$SITE_NAME = 'Kebab-demo';

function respond($code, $data) {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

function load_store($file, $fallback) {
  if (file_exists($file)) {
    $data = include $file;
    if (is_array($data)) return $data;
  }
  return $fallback;
}

function save_store($file, $data) {
  if (file_exists($file) && !is_writable($file)) @chmod($file, 0666);
  $txt = "<?php\nreturn " . var_export($data, true) . ";\n";
  return file_put_contents($file, $txt, LOCK_EX) !== false;
}

function clean($value, $limit = 3000) {
  $value = strip_tags(trim((string)$value));
  $value = str_replace(array("\0", "\r"), '', $value);
  return function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, array('ok' => false, 'error' => 'Tylko POST'));

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) respond(400, array('ok' => false, 'error' => 'Błędny JSON'));

$name = clean(isset($data['name']) ? $data['name'] : '', 120);
$phone = clean(isset($data['phone']) ? $data['phone'] : '', 80);
$address = clean(isset($data['address']) ? $data['address'] : '', 240);
$mode = clean(isset($data['mode']) ? $data['mode'] : 'Odbiór własny', 80);
$paymentMethod = clean(isset($data['payment_method']) ? $data['payment_method'] : 'Płatność przy odbiorze', 120);
$orderText = clean(isset($data['order']) ? $data['order'] : '', 5000);
$total = (float)(isset($data['total']) ? $data['total'] : 0);

$allowedPayments = array('Płatność przy odbiorze', 'Karta przy odbiorze', 'BLIK na telefon');
if (!in_array($paymentMethod, $allowedPayments, true)) $paymentMethod = 'Płatność przy odbiorze';

if ($phone === '') respond(400, array('ok' => false, 'error' => 'Brakuje telefonu'));
if ($orderText === '') respond(400, array('ok' => false, 'error' => 'Brakuje zamówienia'));
if ($mode === 'Dostawa' && $address === '') respond(400, array('ok' => false, 'error' => 'Brakuje adresu dostawy'));
if ($total <= 0) respond(400, array('ok' => false, 'error' => 'Błędna kwota zamówienia'));

$orderId = 'KD-' . date('Ymd-His') . '-' . random_int(100, 999);
$isBlikPhone = ($paymentMethod === 'BLIK na telefon');
$paymentStatus = $isBlikPhone ? 'oczekuje na płatność' : 'płatność przy odbiorze';
$paymentUrl = $isBlikPhone ? 'payment-demo.php?order=' . rawurlencode($orderId) : '';

$order = array(
  'id' => $orderId,
  'created_at' => date('Y-m-d H:i:s'),
  'updated_at' => date('Y-m-d H:i:s'),
  'status' => 'nowe',
  'payment_method' => $paymentMethod,
  'payment_status' => $paymentStatus,
  'paid' => false,
  'name' => $name,
  'phone' => $phone,
  'address' => $address,
  'mode' => $mode,
  'total' => $total,
  'order_text' => $orderText
);

$orders = load_store($ORDERS_STORE, array('orders' => array()));
if (!isset($orders['orders']) || !is_array($orders['orders'])) $orders['orders'] = array();
array_unshift($orders['orders'], $order);
$historySaved = save_store($ORDERS_STORE, $orders);

$host = preg_replace('/[^a-zA-Z0-9.-]/', '', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
$from = 'no-reply@' . ($host ? $host : 'localhost');
$subjectPlain = 'Nowe zamówienie ' . $orderId . ' - ' . $SITE_NAME;
$subject = '=?UTF-8?B?' . base64_encode($subjectPlain) . '?=';

$message = "Nowe zamówienie ze strony " . $SITE_NAME . "\n";
$message .= "=================================\n\n";
$message .= "Numer: " . $orderId . "\n";
$message .= "Data: " . $order['created_at'] . "\n";
$message .= "Klient: " . ($name ? $name : 'brak imienia') . "\n";
$message .= "Telefon: " . $phone . "\n";
$message .= "Odbiór: " . $mode . "\n";
$message .= "Płatność: " . $paymentMethod . "\n";
$message .= "Status płatności: " . $paymentStatus . "\n";
if ($paymentUrl !== '') $message .= "Instrukcja BLIK: " . $paymentUrl . "\n";
if ($address !== '') $message .= "Adres: " . $address . "\n";
$message .= "\nZamówienie:\n" . $orderText . "\n";

$headers = array(
  'From: ' . $SITE_NAME . ' <' . $from . '>',
  'Reply-To: ' . $from,
  'Content-Type: text/plain; charset=UTF-8',
  'X-Mailer: PHP/' . phpversion()
);

$mailSent = @mail(ORDER_EMAIL, $subject, $message, implode("\r\n", $headers));
respond(200, array('ok' => true, 'order_id' => $orderId, 'mail_sent' => $mailSent, 'history_saved' => $historySaved, 'payment_method' => $paymentMethod, 'payment_status' => $paymentStatus, 'payment_url' => $paymentUrl, 'email_to' => ORDER_EMAIL));
