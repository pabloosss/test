<?php
require __DIR__ . '/app-config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (!file_exists(MENU_FILE)) {
  http_response_code(404);
  echo json_encode(['products' => [], 'ingredients' => []], JSON_UNESCAPED_UNICODE);
  exit;
}
echo file_get_contents(MENU_FILE);
