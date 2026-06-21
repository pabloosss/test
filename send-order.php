<?php
require __DIR__ . '/app-config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond($code, $data) {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

function read_json($file, $fallback) {
  if (!file_exists($file)) return $fallback;
  $data = json_decode(file_get_contents($file), true);
  return is_array($data) ? $data : $fallback;
}

function save_json($file, $data) {
  if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
  return file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function clean($value, $limit = 3000) {
  $value = strip_tags(trim((string)$value));
  $value = str_replace(["\0", "\r"], '', $value);
  return function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok' => false, 'error' => 'Tylko POST']);

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) respond(400, ['ok' => false, 'error' => 'Błędny JSON']);

$name = clean($data['name'] ?? '', 120);
$phone = clean($data['phone'] ?? '', 80);
$address = clean($data['address'] ?? '', 240);
$mode = clean($data['mode'] ?? 'Odbiór własny', 80);
$orderText = clean($data['order'] ?? '', 5000);
$total = (float)($data['total'] ?? 0);

if ($phone === '') respond(400, ['ok' => false, 'error' => 'Brakuje telefonu']);
if ($orderText === '') respond(400, ['ok' => false, 'error' => 'Brakuje zamówienia']);
if ($mode === 'Dostawa' && $address === '') respond(400, ['ok' => false, 'error' => 'Brakuje adresu dostawy']);

$orderId = 'KK-' . date('Ymd-His') . '-' . random_int(100, 999);
$order = [
  'id' => $orderId,
  'created_at' => date('Y-m-d H:i:s'),
  'status' => 'nowe',
  'name' => $name,
  'phone' => $phone,
  'address' => $address,
  'mode' => $mode,
  'total' => $total,
  'order_text' => $orderText
];

$orders = read_json(ORDERS_FILE, ['orders' => []]);
if (!isset($orders['orders']) || !is_array($orders['orders'])) $orders['orders'] = [];
array_unshift($orders['orders'], $order);
save_json(ORDERS_FILE, $orders);

$host = preg_replace('/[^a-zA-Z0-9.-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
$from = 'no-reply@' . ($host ?: 'localhost');
$subjectPlain = 'Nowe zamówienie ' . $orderId . ' - KebabKing';
$subject = '=?UTF-8?B?' . base64_encode($subjectPlain) . '?=';

$message = "Nowe zamówienie ze strony KebabKing\n";
$message .= "=================================\n\n";
$message .= "Numer: {$orderId}\n";
$message .= "Data: {$order['created_at']}\n";
$message .= "Klient: " . ($name ?: 'brak imienia') . "\n";
$message .= "Telefon: {$phone}\n";
$message .= "Odbiór: {$mode}\n";
if ($address !== '') $message .= "Adres: {$address}\n";
$message .= "\nZamówienie:\n{$orderText}\n";

$headers = [
  'From: KebabKing <' . $from . '>',
  'Reply-To: ' . $from,
  'Content-Type: text/plain; charset=UTF-8',
  'X-Mailer: PHP/' . phpversion()
];

$mailSent = @mail(ORDER_EMAIL, $subject, $message, implode("\r\n", $headers));
respond(200, ['ok' => true, 'order_id' => $orderId, 'mail_sent' => $mailSent, 'email_to' => ORDER_EMAIL]);
