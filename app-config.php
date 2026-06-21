<?php
// Konfiguracja strony na hostingu WWW z PHP.

define('ORDER_EMAIL', 'notingss@gmail.com');

define('ADMIN_USER', 'admin');

// Domyślne hasło: kebab2026!
// Po wrzuceniu na hosting zmień hasło.
define('ADMIN_PASSWORD_HASH', '217712b1b9b8eb65e32bfc50fba3b2041578076071dd7d647311e4e65292af4d');

define('DATA_DIR', __DIR__ . '/data');
define('MENU_FILE', DATA_DIR . '/menu.json');
define('ORDERS_FILE', DATA_DIR . '/orders.json');
