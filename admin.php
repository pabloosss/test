<?php
if (isset($_GET['debug'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require __DIR__ . '/app-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cut_text($value, $limit = 1000) {
    $value = strip_tags(trim((string)$value));
    $value = str_replace(array("\0", "\r"), '', $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit, 'UTF-8');
    }
    return substr($value, 0, $limit);
}

function make_slug($value) {
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

function read_json_file($file, $fallback) {
    if (!file_exists($file)) {
        return $fallback;
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : $fallback;
}

function save_json_file($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function is_logged_in() {
    return !empty($_SESSION['admin_logged']);
}

function valid_login($user, $password) {
    if ($user !== ADMIN_USER) {
        return false;
    }
    $hash = hash('sha256', (string)$password);
    return hash_equals(ADMIN_PASSWORD_HASH, $hash);
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

$notice = '';
$error = '';
$groups = array(
    'sizes' => 'Rozmiary',
    'meats' => 'Mięsa / baza',
    'sauces' => 'Sosy',
    'inside' => 'Dodatki w środku',
    'extras' => 'Dodatki płatne'
);

if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    $notice = 'Wylogowano.';
}

$menu = read_json_file(MENU_FILE, default_menu());
$orders = read_json_file(ORDERS_FILE, array('orders' => array()));

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
if (!isset($orders['orders']) || !is_array($orders['orders'])) {
    $orders['orders'] = array();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'login') {
        $user = cut_text(isset($_POST['username']) ? $_POST['username'] : '', 80);
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        if (valid_login($user, $password)) {
            $_SESSION['admin_logged'] = true;
            $notice = 'Zalogowano. Panel jest aktywny.';
        } else {
            $error = 'Błędny login albo hasło.';
        }
    }

    if (is_logged_in() && $action === 'save_product') {
        $id = cut_text(isset($_POST['id']) ? $_POST['id'] : '', 120);
        $name = cut_text(isset($_POST['name']) ? $_POST['name'] : '', 160);
        if ($name === '') {
            $error = 'Podaj nazwę produktu.';
        } else {
            if ($id === '') {
                $id = make_slug($name) . '-' . substr(md5(uniqid('', true)), 0, 5);
            }
            $product = array(
                'id' => $id,
                'name' => $name,
                'category' => cut_text(isset($_POST['category']) ? $_POST['category'] : 'lawasz', 80),
                'badge' => cut_text(isset($_POST['badge']) ? $_POST['badge'] : 'Menu', 80),
                'price' => (float)(isset($_POST['price']) ? $_POST['price'] : 0),
                'description' => cut_text(isset($_POST['description']) ? $_POST['description'] : '', 600),
                'image' => cut_text(isset($_POST['image']) ? $_POST['image'] : '', 1000),
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
            if (save_json_file(MENU_FILE, $menu)) {
                $notice = 'Produkt zapisany.';
            } else {
                $error = 'Nie udało się zapisać produktu. Sprawdź uprawnienia folderu data.';
            }
        }
    }

    if (is_logged_in() && $action === 'delete_product') {
        $id = cut_text(isset($_POST['id']) ? $_POST['id'] : '', 120);
        $newProducts = array();
        foreach ($menu['products'] as $product) {
            if (!isset($product['id']) || $product['id'] !== $id) {
                $newProducts[] = $product;
            }
        }
        $menu['products'] = $newProducts;
        save_json_file(MENU_FILE, $menu);
        $notice = 'Produkt usunięty.';
    }

    if (is_logged_in() && $action === 'save_ingredient') {
        $group = cut_text(isset($_POST['group']) ? $_POST['group'] : 'extras', 80);
        $name = cut_text(isset($_POST['name']) ? $_POST['name'] : '', 160);
        if (!isset($groups[$group])) {
            $error = 'Nieprawidłowa grupa składnika.';
        } elseif ($name === '') {
            $error = 'Podaj nazwę składnika.';
        } else {
            $id = cut_text(isset($_POST['id']) ? $_POST['id'] : '', 120);
            if ($id === '') {
                $id = make_slug($name) . '-' . substr(md5(uniqid('', true)), 0, 5);
            }
            $item = array(
                'id' => $id,
                'name' => $name,
                'label' => cut_text(isset($_POST['label']) && $_POST['label'] !== '' ? $_POST['label'] : $name, 160),
                'price' => (float)(isset($_POST['price']) ? $_POST['price'] : 0),
                'default' => !empty($_POST['default']),
                'active' => !empty($_POST['active'])
            );
            $found = false;
            for ($i = 0; $i < count($menu['ingredients'][$group]); $i++) {
                if (isset($menu['ingredients'][$group][$i]['id']) && $menu['ingredients'][$group][$i]['id'] === $id) {
                    $menu['ingredients'][$group][$i] = $item;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $menu['ingredients'][$group][] = $item;
            }
            if (save_json_file(MENU_FILE, $menu)) {
                $notice = 'Składnik zapisany.';
            } else {
                $error = 'Nie udało się zapisać składnika. Sprawdź uprawnienia folderu data.';
            }
        }
    }

    if (is_logged_in() && $action === 'delete_ingredient') {
        $group = cut_text(isset($_POST['group']) ? $_POST['group'] : '', 80);
        $id = cut_text(isset($_POST['id']) ? $_POST['id'] : '', 120);
        if (isset($menu['ingredients'][$group])) {
            $newItems = array();
            foreach ($menu['ingredients'][$group] as $item) {
                if (!isset($item['id']) || $item['id'] !== $id) {
                    $newItems[] = $item;
                }
            }
            $menu['ingredients'][$group] = $newItems;
            save_json_file(MENU_FILE, $menu);
            $notice = 'Składnik usunięty.';
        }
    }

    if (is_logged_in() && $action === 'order_status') {
        $id = cut_text(isset($_POST['id']) ? $_POST['id'] : '', 120);
        $status = cut_text(isset($_POST['status']) ? $_POST['status'] : 'nowe', 80);
        for ($i = 0; $i < count($orders['orders']); $i++) {
            if (isset($orders['orders'][$i]['id']) && $orders['orders'][$i]['id'] === $id) {
                $orders['orders'][$i]['status'] = $status;
                $orders['orders'][$i]['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        save_json_file(ORDERS_FILE, $orders);
        $notice = 'Status zamówienia zmieniony.';
    }

    $menu = read_json_file(MENU_FILE, $menu);
    $orders = read_json_file(ORDERS_FILE, $orders);
}

$editProduct = null;
if (is_logged_in() && isset($_GET['edit_product'])) {
    foreach ($menu['products'] as $product) {
        if (isset($product['id']) && $product['id'] === $_GET['edit_product']) {
            $editProduct = $product;
            break;
        }
    }
}

$editIngredient = null;
$editGroup = isset($_GET['group']) ? $_GET['group'] : 'extras';
if (is_logged_in() && isset($_GET['edit_ingredient']) && isset($_GET['group'])) {
    $groupKey = $_GET['group'];
    if (isset($menu['ingredients'][$groupKey])) {
        foreach ($menu['ingredients'][$groupKey] as $item) {
            if (isset($item['id']) && $item['id'] === $_GET['edit_ingredient']) {
                $editIngredient = $item;
                $editGroup = $groupKey;
                break;
            }
        }
    }
}

$phpOk = version_compare(PHP_VERSION, '7.0.0', '>=');
$dataWritable = is_writable(DATA_DIR) || (!is_dir(DATA_DIR) && is_writable(__DIR__));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel admina — KebabKing</title>
  <style>
    body{margin:0;font-family:Arial,sans-serif;background:#fff6df;color:#20130d}.wrap{width:min(1180px,calc(100% - 32px));margin:auto}.top{background:#fff;border-bottom:1px solid #eadfcd;position:sticky;top:0;z-index:2}.top .wrap{min-height:72px;display:flex;align-items:center;justify-content:space-between;gap:12px}.logo{font-weight:900;font-size:24px;color:#d71920;text-decoration:none}.btn,button{border:0;border-radius:999px;padding:11px 16px;background:#ffbc0d;font-weight:900;cursor:pointer;text-decoration:none;color:#20130d;display:inline-block}.btn.dark{background:#20130d;color:white}.main{padding:34px 0 80px}.card{background:#fff;border:1px solid #eadfcd;border-radius:24px;padding:22px;margin:0 0 20px;box-shadow:0 18px 45px rgba(70,31,10,.1)}h1,h2{letter-spacing:-.04em}input,select,textarea{width:100%;box-sizing:border-box;padding:12px;border:1px solid #ddd;border-radius:12px}label{display:grid;gap:6px;font-weight:800;color:#715f53}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.wide{grid-column:span 2}.check{display:flex;gap:8px;align-items:center}.check input{width:auto}.scroll{overflow:auto}table{width:100%;border-collapse:collapse;min-width:760px}th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}th{color:#715f53}.notice{background:#fff0c2;padding:12px 16px;border-radius:16px;margin-bottom:16px;font-weight:800}.error{background:#ffe0e0;color:#a81017;padding:12px 16px;border-radius:16px;margin-bottom:16px;font-weight:800}.danger{background:#ffe0e0;color:#a81017}.order{display:grid;grid-template-columns:1fr 180px;gap:14px;background:#fff;border:1px solid #eee;border-radius:18px;padding:16px;margin:12px 0}.order pre{white-space:pre-wrap;background:#f5ead6;border-radius:14px;padding:12px}.login{max-width:440px}.muted{color:#715f53;line-height:1.55}.status{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.status div{background:#f5ead6;border-radius:16px;padding:12px}.ok{color:#208a3a;font-weight:900}.bad{color:#a81017;font-weight:900}@media(max-width:850px){.grid,.order,.status{grid-template-columns:1fr}.wide{grid-column:span 1}}
  </style>
</head>
<body>
<header class="top"><div class="wrap"><a class="logo" href="index.html">KebabKing</a><nav><a class="btn dark" href="index.html">Strona</a> <?php if(is_logged_in()): ?><a class="btn" href="admin.php?logout=1">Wyloguj</a><?php endif; ?></nav></div></header>
<main class="wrap main">
<?php if($notice !== ''): ?><div class="notice"><?=h($notice)?></div><?php endif; ?>
<?php if($error !== ''): ?><div class="error"><?=h($error)?></div><?php endif; ?>

<?php if(!is_logged_in()): ?>
<section class="card login">
  <h1>Logowanie admina</h1>
  <p class="muted">Wpisz login i hasło, potem kliknij „Zaloguj”.</p>
  <form method="post" action="admin.php">
    <input type="hidden" name="action" value="login">
    <p><label>Login<input name="username" value="admin" autocomplete="username"></label></p>
    <p><label>Hasło<input type="password" name="password" autocomplete="current-password"></label></p>
    <button type="submit">Zaloguj</button>
  </form>
</section>
<section class="card">
  <h2>Test hostingu</h2>
  <div class="status">
    <div>PHP: <span class="<?= $phpOk ? 'ok' : 'bad' ?>"><?=h(PHP_VERSION)?></span></div>
    <div>Sesja: <span class="<?= session_status() === PHP_SESSION_ACTIVE ? 'ok' : 'bad' ?>"><?= session_status() === PHP_SESSION_ACTIVE ? 'działa' : 'problem' ?></span></div>
    <div>Folder data: <span class="<?= $dataWritable ? 'ok' : 'bad' ?>"><?= $dataWritable ? 'zapis OK' : 'brak zapisu' ?></span></div>
  </div>
  <p class="muted">Debug błędów: dopisz do adresu <b>?debug=1</b>, czyli <b>admin.php?debug=1</b>.</p>
</section>
<?php else: ?>
<h1>Panel admina</h1>
<p class="muted">Tu zmienisz ceny, dodasz produkty, składniki i sprawdzisz zamówienia.</p>
<section class="card">
  <h2><?= $editProduct ? 'Edytuj produkt' : 'Dodaj produkt' ?></h2>
  <form method="post" action="admin.php" class="grid">
    <input type="hidden" name="action" value="save_product"><input type="hidden" name="id" value="<?=h($editProduct['id'] ?? '')?>">
    <label>Nazwa<input name="name" value="<?=h($editProduct['name'] ?? '')?>" required></label>
    <label>Kategoria<select name="category"><?php foreach(array('lawasz','box','talerz','grill','vege','dodatki') as $c): ?><option value="<?=$c?>" <?=($editProduct['category'] ?? '')===$c?'selected':''?>><?=$c?></option><?php endforeach; ?></select></label>
    <label>Cena<input type="number" step="0.01" name="price" value="<?=h($editProduct['price'] ?? '')?>" required></label>
    <label>Etykieta<input name="badge" value="<?=h($editProduct['badge'] ?? '')?>"></label>
    <label class="wide">Zdjęcie URL<input name="image" value="<?=h($editProduct['image'] ?? '')?>"></label>
    <label class="wide">Opis<textarea name="description" rows="3"><?=h($editProduct['description'] ?? '')?></textarea></label>
    <label class="check"><input type="checkbox" name="active" <?=($editProduct['active'] ?? true)?'checked':''?>> aktywny</label>
    <button type="submit">Zapisz produkt</button>
  </form>
</section>
<section class="card"><h2>Produkty</h2><div class="scroll"><table><tr><th>Nazwa</th><th>Kategoria</th><th>Cena</th><th>Status</th><th>Akcje</th></tr><?php foreach($menu['products'] as $p): ?><tr><td><b><?=h($p['name'])?></b><br><small><?=h($p['badge'] ?? '')?></small></td><td><?=h($p['category'])?></td><td><?=number_format((float)$p['price'],2,',',' ')?> zł</td><td><?=!empty($p['active'])?'aktywny':'ukryty'?></td><td><a class="btn" href="admin.php?edit_product=<?=h($p['id'])?>">Edytuj</a><form method="post" action="admin.php" style="display:inline" onsubmit="return confirm('Usunąć produkt?')"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?=h($p['id'])?>"><button class="danger">Usuń</button></form></td></tr><?php endforeach; ?></table></div></section>
<section class="card">
  <h2><?= $editIngredient ? 'Edytuj składnik' : 'Dodaj składnik' ?></h2>
  <form method="post" action="admin.php" class="grid">
    <input type="hidden" name="action" value="save_ingredient"><input type="hidden" name="id" value="<?=h($editIngredient['id'] ?? '')?>">
    <label>Grupa<select name="group"><?php foreach($groups as $key=>$label): ?><option value="<?=$key?>" <?=$editGroup===$key?'selected':''?>><?=$label?></option><?php endforeach; ?></select></label>
    <label>Nazwa<input name="name" value="<?=h($editIngredient['name'] ?? '')?>" required></label>
    <label>Etykieta<input name="label" value="<?=h($editIngredient['label'] ?? '')?>"></label>
    <label>Dopłata<input type="number" step="0.01" name="price" value="<?=h($editIngredient['price'] ?? 0)?>"></label>
    <label class="check"><input type="checkbox" name="default" <?=!empty($editIngredient['default'])?'checked':''?>> domyślne</label>
    <label class="check"><input type="checkbox" name="active" <?=($editIngredient['active'] ?? true)?'checked':''?>> aktywne</label>
    <button type="submit">Zapisz składnik</button>
  </form>
</section>
<section class="card"><h2>Składniki</h2><div class="scroll"><table><tr><th>Grupa</th><th>Nazwa</th><th>Dopłata</th><th>Status</th><th>Akcje</th></tr><?php foreach($groups as $g=>$gl): foreach(($menu['ingredients'][$g] ?? array()) as $x): ?><tr><td><?=h($gl)?></td><td><b><?=h($x['label'] ?? $x['name'])?></b><br><small><?=h($x['name'])?></small></td><td><?=number_format((float)($x['price'] ?? 0),2,',',' ')?> zł</td><td><?=!empty($x['active'])?'aktywny':'ukryty'?><?=!empty($x['default'])?'<br><small>domyślne</small>':''?></td><td><a class="btn" href="admin.php?group=<?=h($g)?>&edit_ingredient=<?=h($x['id'])?>">Edytuj</a><form method="post" action="admin.php" style="display:inline" onsubmit="return confirm('Usunąć składnik?')"><input type="hidden" name="action" value="delete_ingredient"><input type="hidden" name="group" value="<?=h($g)?>"><input type="hidden" name="id" value="<?=h($x['id'])?>"><button class="danger">Usuń</button></form></td></tr><?php endforeach; endforeach; ?></table></div></section>
<section class="card"><h2>Zamówienia</h2><?php if(empty($orders['orders'])): ?><p>Brak zamówień.</p><?php endif; ?><?php foreach(($orders['orders'] ?? array()) as $o): ?><article class="order"><div><h3><?=h($o['id'])?></h3><p><b><?=h($o['name'] ?: 'brak imienia')?></b> · tel. <?=h($o['phone'])?> · <?=h($o['created_at'])?> · <?=number_format((float)$o['total'],2,',',' ')?> zł</p><pre><?=h($o['order_text'])?></pre></div><form method="post" action="admin.php"><input type="hidden" name="action" value="order_status"><input type="hidden" name="id" value="<?=h($o['id'])?>"><label>Status<select name="status" onchange="this.form.submit()"><?php foreach(array('nowe','przyjęte','w realizacji','gotowe','odebrane','anulowane') as $s): ?><option value="<?=$s?>" <?=($o['status'] ?? '')===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select></label></form></article><?php endforeach; ?></section>
<?php endif; ?>
</main>
</body>
</html>
