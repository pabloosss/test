<?php
if (isset($_GET['debug'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require __DIR__ . '/app-config.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$MENU_STORE = __DIR__ . '/menu-data.php';
$ORDERS_STORE = __DIR__ . '/orders-data.php';
$AUTH_COOKIE = 'kk_admin_auth';

$categories = array('lawasz', 'box', 'talerz', 'grill', 'vege', 'dodatki');
$groups = array(
    'sizes' => 'Rozmiary',
    'meats' => 'Mięsa / baza',
    'sauces' => 'Sosy',
    'inside' => 'Dodatki w środku',
    'extras' => 'Dodatki płatne'
);
$statusList = array('nowe', 'przyjęte', 'w realizacji', 'gotowe', 'odebrane', 'anulowane');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function text_value($value, $limit = 1000) {
    $value = strip_tags(trim((string)$value));
    $value = str_replace(array("\0", "\r"), '', $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit, 'UTF-8');
    }
    return substr($value, 0, $limit);
}

function slug_value($value) {
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }
    $value = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
    $value = trim($value, '-');
    return $value !== '' ? $value : 'item';
}

function money($value) {
    return number_format((float)$value, 2, ',', ' ') . ' zł';
}

function default_menu() {
    return array(
        'products' => array(),
        'ingredients' => array(
            'sizes' => array(),
            'meats' => array(),
            'sauces' => array(),
            'inside' => array(),
            'extras' => array()
        )
    );
}

function auth_token() {
    return hash_hmac('sha256', ADMIN_USER . '|' . ADMIN_PASSWORD_HASH, ADMIN_PASSWORD_HASH);
}

function password_ok($user, $password) {
    if ($user !== ADMIN_USER) {
        return false;
    }
    return hash_equals(ADMIN_PASSWORD_HASH, hash('sha256', (string)$password));
}

function admin_is_logged() {
    global $AUTH_COOKIE;

    if (!empty($_SESSION['admin_logged'])) {
        return true;
    }

    if (!empty($_COOKIE[$AUTH_COOKIE]) && hash_equals(auth_token(), $_COOKIE[$AUTH_COOKIE])) {
        $_SESSION['admin_logged'] = true;
        return true;
    }

    return false;
}

function admin_login() {
    global $AUTH_COOKIE;
    $_SESSION['admin_logged'] = true;
    setcookie($AUTH_COOKIE, auth_token(), time() + 60 * 60 * 24 * 14, '/');
    $_COOKIE[$AUTH_COOKIE] = auth_token();
}

function admin_logout() {
    global $AUTH_COOKIE;
    $_SESSION = array();
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_destroy();
    }
    setcookie($AUTH_COOKIE, '', time() - 3600, '/');
    unset($_COOKIE[$AUTH_COOKIE]);
}

function redirect_admin($params = '') {
    $url = 'admin.php';
    if ($params !== '') {
        $url .= '?' . $params;
    }
    header('Location: ' . $url);
    exit;
}

function load_store($file, $fallback) {
    if (file_exists($file)) {
        $data = include $file;
        if (is_array($data)) {
            return $data;
        }
    }
    return $fallback;
}

function save_store($file, $data, &$error) {
    $content = "<?php\nreturn " . var_export($data, true) . ";\n";

    if (file_exists($file) && !is_writable($file)) {
        @chmod($file, 0666);
    }

    if (!file_exists($file) && !is_writable(dirname($file))) {
        $error = 'Brak zapisu w katalogu strony. Hosting blokuje tworzenie plików.';
        return false;
    }

    if (file_exists($file) && !is_writable($file)) {
        $error = 'Brak zapisu do pliku: ' . basename($file) . '. Ustaw uprawnienia pliku na 664 albo 666.';
        return false;
    }

    $result = @file_put_contents($file, $content, LOCK_EX);
    if ($result === false) {
        $error = 'Nie udało się zapisać pliku: ' . basename($file) . '.';
        return false;
    }

    return true;
}

function normalize_menu($menu, $groups) {
    if (!isset($menu['products']) || !is_array($menu['products'])) {
        $menu['products'] = array();
    }
    if (!isset($menu['ingredients']) || !is_array($menu['ingredients'])) {
        $menu['ingredients'] = default_menu()['ingredients'];
    }
    foreach ($groups as $key => $label) {
        if (!isset($menu['ingredients'][$key]) || !is_array($menu['ingredients'][$key])) {
            $menu['ingredients'][$key] = array();
        }
    }
    return $menu;
}

if (isset($_GET['logout'])) {
    admin_logout();
    redirect_admin('ok=logout');
}

$notice = '';
$error = '';

if (isset($_GET['ok'])) {
    $messages = array(
        'login' => 'Zalogowano. Panel jest aktywny.',
        'logout' => 'Wylogowano.',
        'product_saved' => 'Produkt zapisany. Odśwież stronę główną, żeby zobaczyć zmianę.',
        'product_deleted' => 'Produkt usunięty.',
        'ingredient_saved' => 'Składnik zapisany.',
        'ingredient_deleted' => 'Składnik usunięty.',
        'status_saved' => 'Status zamówienia zmieniony.'
    );
    if (isset($messages[$_GET['ok']])) {
        $notice = $messages[$_GET['ok']];
    }
}

$menu = normalize_menu(load_store($MENU_STORE, default_menu()), $groups);
$orders = load_store($ORDERS_STORE, array('orders' => array()));
if (!isset($orders['orders']) || !is_array($orders['orders'])) {
    $orders['orders'] = array();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'login') {
        $user = text_value(isset($_POST['username']) ? $_POST['username'] : '', 80);
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (password_ok($user, $password)) {
            admin_login();
            redirect_admin('ok=login');
        }

        $error = 'Błędny login albo hasło.';
    }

    if ($action !== 'login' && !admin_is_logged()) {
        $error = 'Sesja wygasła. Zaloguj się ponownie.';
    }

    if (admin_is_logged() && $action === 'save_product') {
        $id = text_value(isset($_POST['id']) ? $_POST['id'] : '', 120);
        $name = text_value(isset($_POST['name']) ? $_POST['name'] : '', 160);

        if ($name === '') {
            $error = 'Podaj nazwę produktu.';
        } else {
            if ($id === '') {
                $id = slug_value($name) . '-' . substr(md5(uniqid('', true)), 0, 5);
            }

            $product = array(
                'id' => $id,
                'name' => $name,
                'category' => text_value(isset($_POST['category']) ? $_POST['category'] : 'lawasz', 80),
                'badge' => text_value(isset($_POST['badge']) ? $_POST['badge'] : 'Menu', 80),
                'price' => (float)(isset($_POST['price']) ? $_POST['price'] : 0),
                'description' => text_value(isset($_POST['description']) ? $_POST['description'] : '', 600),
                'image' => text_value(isset($_POST['image']) ? $_POST['image'] : '', 1000),
                'active' => !empty($_POST['active'])
            );

            $found = false;
            for ($i = 0; $i < count($menu['products']); $i++) {
                if (isset($menu['products'][$i]['id']) && $menu['products'][$i]['id'] === $id) {
                    $menu['products'][$i] = $product;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $menu['products'][] = $product;
            }

            if (save_store($MENU_STORE, $menu, $error)) {
                redirect_admin('ok=product_saved');
            }
        }
    }

    if (admin_is_logged() && $action === 'delete_product') {
        $id = text_value(isset($_POST['id']) ? $_POST['id'] : '', 120);
        $newProducts = array();
        foreach ($menu['products'] as $product) {
            if (!isset($product['id']) || $product['id'] !== $id) {
                $newProducts[] = $product;
            }
        }
        $menu['products'] = $newProducts;
        if (save_store($MENU_STORE, $menu, $error)) {
            redirect_admin('ok=product_deleted');
        }
    }

    if (admin_is_logged() && $action === 'save_ingredient') {
        $group = text_value(isset($_POST['group']) ? $_POST['group'] : 'extras', 80);
        $name = text_value(isset($_POST['name']) ? $_POST['name'] : '', 160);

        if (!isset($groups[$group])) {
            $error = 'Nieprawidłowa grupa składnika.';
        } elseif ($name === '') {
            $error = 'Podaj nazwę składnika.';
        } else {
            $id = text_value(isset($_POST['id']) ? $_POST['id'] : '', 120);
            if ($id === '') {
                $id = slug_value($name) . '-' . substr(md5(uniqid('', true)), 0, 5);
            }

            $ingredient = array(
                'id' => $id,
                'name' => $name,
                'label' => text_value(isset($_POST['label']) && $_POST['label'] !== '' ? $_POST['label'] : $name, 160),
                'price' => (float)(isset($_POST['price']) ? $_POST['price'] : 0),
                'default' => !empty($_POST['default']),
                'active' => !empty($_POST['active'])
            );

            $found = false;
            for ($i = 0; $i < count($menu['ingredients'][$group]); $i++) {
                if (isset($menu['ingredients'][$group][$i]['id']) && $menu['ingredients'][$group][$i]['id'] === $id) {
                    $menu['ingredients'][$group][$i] = $ingredient;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $menu['ingredients'][$group][] = $ingredient;
            }

            if (save_store($MENU_STORE, $menu, $error)) {
                redirect_admin('ok=ingredient_saved');
            }
        }
    }

    if (admin_is_logged() && $action === 'delete_ingredient') {
        $group = text_value(isset($_POST['group']) ? $_POST['group'] : '', 80);
        $id = text_value(isset($_POST['id']) ? $_POST['id'] : '', 120);

        if (isset($menu['ingredients'][$group])) {
            $newItems = array();
            foreach ($menu['ingredients'][$group] as $item) {
                if (!isset($item['id']) || $item['id'] !== $id) {
                    $newItems[] = $item;
                }
            }
            $menu['ingredients'][$group] = $newItems;
        }

        if (save_store($MENU_STORE, $menu, $error)) {
            redirect_admin('ok=ingredient_deleted');
        }
    }

    if (admin_is_logged() && $action === 'order_status') {
        $id = text_value(isset($_POST['id']) ? $_POST['id'] : '', 120);
        $status = text_value(isset($_POST['status']) ? $_POST['status'] : 'nowe', 80);

        for ($i = 0; $i < count($orders['orders']); $i++) {
            if (isset($orders['orders'][$i]['id']) && $orders['orders'][$i]['id'] === $id) {
                $orders['orders'][$i]['status'] = $status;
                $orders['orders'][$i]['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }

        if (save_store($ORDERS_STORE, $orders, $error)) {
            redirect_admin('ok=status_saved');
        }
    }
}

$menu = normalize_menu(load_store($MENU_STORE, $menu), $groups);
$orders = load_store($ORDERS_STORE, $orders);
if (!isset($orders['orders']) || !is_array($orders['orders'])) {
    $orders['orders'] = array();
}

$editProduct = null;
if (admin_is_logged() && isset($_GET['edit_product'])) {
    foreach ($menu['products'] as $product) {
        if (isset($product['id']) && $product['id'] === $_GET['edit_product']) {
            $editProduct = $product;
            break;
        }
    }
}

$editIngredient = null;
$editGroup = isset($_GET['group']) ? $_GET['group'] : 'extras';
if (admin_is_logged() && isset($_GET['edit_ingredient'], $_GET['group']) && isset($menu['ingredients'][$_GET['group']])) {
    foreach ($menu['ingredients'][$_GET['group']] as $ingredientItem) {
        if (isset($ingredientItem['id']) && $ingredientItem['id'] === $_GET['edit_ingredient']) {
            $editIngredient = $ingredientItem;
            $editGroup = $_GET['group'];
            break;
        }
    }
}

$menuWritable = is_writable($MENU_STORE) || (!file_exists($MENU_STORE) && is_writable(__DIR__));
$ordersWritable = is_writable($ORDERS_STORE) || (!file_exists($ORDERS_STORE) && is_writable(__DIR__));
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin KebabKing</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#fff6df;color:#20130d}.wrap{width:min(1180px,calc(100% - 32px));margin:auto}.top{background:#fff;border-bottom:1px solid #eadfcd}.top .wrap{min-height:72px;display:flex;justify-content:space-between;align-items:center;gap:12px}.logo{font-size:24px;font-weight:900;color:#d71920;text-decoration:none}.btn,button{border:0;border-radius:999px;padding:10px 14px;background:#ffbc0d;font-weight:900;color:#20130d;text-decoration:none;cursor:pointer;display:inline-block}.dark{background:#20130d;color:#fff}.danger{background:#ffe0e0;color:#a81017}.main{padding:30px 0 80px}.card{background:#fff;border:1px solid #eadfcd;border-radius:22px;padding:20px;margin:0 0 18px;box-shadow:0 14px 36px #0001}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.wide{grid-column:span 2}label{display:grid;gap:6px;font-weight:800;color:#715f53}input,select,textarea{width:100%;box-sizing:border-box;border:1px solid #ddd;border-radius:12px;padding:11px}.check{display:flex;gap:8px;align-items:center}.check input{width:auto}.msg{padding:12px 16px;border-radius:16px;margin-bottom:14px;font-weight:900}.ok{background:#e8f8ee;color:#208a3a}.err{background:#ffe0e0;color:#a81017}.warn{background:#fff0c2}.scroll{overflow:auto}table{width:100%;min-width:760px;border-collapse:collapse}td,th{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}.order{display:grid;grid-template-columns:1fr 180px;gap:12px;border:1px solid #eee;border-radius:16px;padding:14px;margin:10px 0}.order pre{white-space:pre-wrap;background:#f5ead6;padding:12px;border-radius:12px}.toolbar{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 18px}@media(max-width:850px){.grid,.order{grid-template-columns:1fr}.wide{grid-column:span 1}}
</style>
</head>
<body>
<header class="top"><div class="wrap"><a class="logo" href="index.html">KebabKing</a><nav><a class="btn dark" href="index.html">Strona</a> <?php if(admin_is_logged()): ?><a class="btn" href="admin.php?logout=1">Wyloguj</a><?php endif; ?></nav></div></header>
<main class="wrap main">
<?php if($notice): ?><div class="msg ok"><?=e($notice)?></div><?php endif; ?>
<?php if($error): ?><div class="msg err"><?=e($error)?></div><?php endif; ?>

<?php if(!admin_is_logged()): ?>
<section class="card">
<h1>Logowanie admina</h1>
<form method="post" action="admin.php">
<input type="hidden" name="action" value="login">
<p><label>Login<input name="username" value="admin"></label></p>
<p><label>Hasło<input type="password" name="password"></label></p>
<button type="submit">Zaloguj</button>
</form>
</section>
<section class="card">
<h2>Test zapisu</h2>
<p class="msg <?= $menuWritable ? 'ok' : 'err' ?>">menu-data.php: <?= $menuWritable ? 'zapis OK' : 'BRAK ZAPISU - edycja nie będzie działać' ?></p>
<p class="msg <?= $ordersWritable ? 'ok' : 'err' ?>">orders-data.php: <?= $ordersWritable ? 'zapis OK' : 'BRAK ZAPISU' ?></p>
</section>
<?php else: ?>
<h1>Panel admina</h1>
<div class="toolbar"><a class="btn" href="admin.php">Dodaj nowy produkt</a><a class="btn" href="#ingredients">Składniki</a><a class="btn" href="#orders">Zamówienia</a></div>
<?php if(!$menuWritable): ?><div class="msg err">Brak zapisu do menu-data.php. Ustaw uprawnienia pliku na 664 albo 666.</div><?php endif; ?>

<section class="card">
<h2><?= $editProduct ? 'Edytuj produkt' : 'Dodaj produkt' ?></h2>
<form method="post" action="admin.php" class="grid">
<input type="hidden" name="action" value="save_product">
<input type="hidden" name="id" value="<?=e($editProduct['id'] ?? '')?>">
<label>Nazwa<input name="name" value="<?=e($editProduct['name'] ?? '')?>" required></label>
<label>Kategoria<select name="category"><?php foreach($categories as $category): ?><option value="<?=$category?>" <?=($editProduct['category'] ?? '')===$category?'selected':''?>><?=$category?></option><?php endforeach; ?></select></label>
<label>Cena<input type="number" step="0.01" name="price" value="<?=e($editProduct['price'] ?? '')?>" required></label>
<label>Etykieta<input name="badge" value="<?=e($editProduct['badge'] ?? '')?>"></label>
<label class="wide">Zdjęcie URL<input name="image" value="<?=e($editProduct['image'] ?? '')?>"></label>
<label class="wide">Opis<textarea name="description" rows="3"><?=e($editProduct['description'] ?? '')?></textarea></label>
<label class="check"><input type="checkbox" name="active" <?=($editProduct['active'] ?? true)?'checked':''?>> aktywny</label>
<button type="submit">Zapisz produkt</button>
</form>
</section>

<section class="card">
<h2>Produkty</h2>
<div class="scroll"><table><tr><th>Nazwa</th><th>Kategoria</th><th>Cena</th><th>Status</th><th>Akcje</th></tr>
<?php foreach($menu['products'] as $product): ?>
<tr><td><b><?=e($product['name'])?></b><br><small><?=e($product['badge'] ?? '')?></small></td><td><?=e($product['category'])?></td><td><?=money($product['price'])?></td><td><?=!empty($product['active'])?'aktywny':'ukryty'?></td><td><a class="btn" href="admin.php?edit_product=<?=e($product['id'])?>">Edytuj</a> <form method="post" action="admin.php" style="display:inline" onsubmit="return confirm('Usunąć produkt?')"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?=e($product['id'])?>"><button class="danger" type="submit">Usuń</button></form></td></tr>
<?php endforeach; ?>
</table></div></section>

<section class="card" id="ingredients">
<h2><?= $editIngredient ? 'Edytuj składnik' : 'Dodaj składnik' ?></h2>
<form method="post" action="admin.php" class="grid">
<input type="hidden" name="action" value="save_ingredient">
<input type="hidden" name="id" value="<?=e($editIngredient['id'] ?? '')?>">
<label>Grupa<select name="group"><?php foreach($groups as $groupKey=>$groupLabel): ?><option value="<?=$groupKey?>" <?=$editGroup===$groupKey?'selected':''?>><?=$groupLabel?></option><?php endforeach; ?></select></label>
<label>Nazwa<input name="name" value="<?=e($editIngredient['name'] ?? '')?>" required></label>
<label>Etykieta<input name="label" value="<?=e($editIngredient['label'] ?? '')?>"></label>
<label>Dopłata<input type="number" step="0.01" name="price" value="<?=e($editIngredient['price'] ?? 0)?>"></label>
<label class="check"><input type="checkbox" name="default" <?=!empty($editIngredient['default'])?'checked':''?>> domyślne</label>
<label class="check"><input type="checkbox" name="active" <?=($editIngredient['active'] ?? true)?'checked':''?>> aktywne</label>
<button type="submit">Zapisz składnik</button>
</form>
</section>

<section class="card"><h2>Składniki</h2><div class="scroll"><table><tr><th>Grupa</th><th>Nazwa</th><th>Dopłata</th><th>Status</th><th>Akcje</th></tr>
<?php foreach($groups as $groupKey=>$groupLabel): foreach(($menu['ingredients'][$groupKey] ?? array()) as $item): ?>
<tr><td><?=e($groupLabel)?></td><td><b><?=e($item['label'] ?? $item['name'])?></b><br><small><?=e($item['name'])?></small></td><td><?=money($item['price'] ?? 0)?></td><td><?=!empty($item['active'])?'aktywny':'ukryty'?><?=!empty($item['default'])?'<br><small>domyślne</small>':''?></td><td><a class="btn" href="admin.php?group=<?=e($groupKey)?>&edit_ingredient=<?=e($item['id'])?>#ingredients">Edytuj</a> <form method="post" action="admin.php" style="display:inline" onsubmit="return confirm('Usunąć składnik?')"><input type="hidden" name="action" value="delete_ingredient"><input type="hidden" name="group" value="<?=e($groupKey)?>"><input type="hidden" name="id" value="<?=e($item['id'])?>"><button class="danger" type="submit">Usuń</button></form></td></tr>
<?php endforeach; endforeach; ?>
</table></div></section>

<section class="card" id="orders"><h2>Zamówienia</h2>
<?php if(empty($orders['orders'])): ?><p>Brak zamówień.</p><?php endif; ?>
<?php foreach(($orders['orders'] ?? array()) as $order): ?>
<article class="order"><div><h3><?=e($order['id'])?></h3><p><b><?=e($order['name'] ?: 'brak imienia')?></b> · tel. <?=e($order['phone'])?> · <?=e($order['created_at'])?> · <?=money($order['total'])?></p><pre><?=e($order['order_text'])?></pre></div><form method="post" action="admin.php"><input type="hidden" name="action" value="order_status"><input type="hidden" name="id" value="<?=e($order['id'])?>"><label>Status<select name="status" onchange="this.form.submit()"><?php foreach($statusList as $status): ?><option value="<?=$status?>" <?=($order['status'] ?? '')===$status?'selected':''?>><?=$status?></option><?php endforeach; ?></select></label></form></article>
<?php endforeach; ?>
</section>
<?php endif; ?>
</main></body></html>
