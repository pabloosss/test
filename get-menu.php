<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$menuFile = __DIR__ . '/menu-data.php';
if (file_exists($menuFile)) {
    $menu = include $menuFile;
    if (is_array($menu)) {
        echo json_encode($menu, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

require __DIR__ . '/app-config.php';
if (file_exists(MENU_FILE)) {
    echo file_get_contents(MENU_FILE);
    exit;
}

echo json_encode(array('products' => array(), 'ingredients' => array()), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
