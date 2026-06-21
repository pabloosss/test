<?php
require __DIR__ . '/app-config.php';
session_start();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function clean($v, $limit = 1000) { $v = strip_tags(trim((string)$v)); return function_exists('mb_substr') ? mb_substr($v, 0, $limit, 'UTF-8') : substr($v, 0, $limit); }
function slug($v) { $v = iconv('UTF-8', 'ASCII//TRANSLIT', $v); $v = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $v)); return trim($v, '-') ?: 'item'; }
function read_json($file, $fallback) { if (!file_exists($file)) return $fallback; $data = json_decode(file_get_contents($file), true); return is_array($data) ? $data : $fallback; }
function save_json($file, $data) { if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true); return file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false; }
function logged() { return !empty($_SESSION['admin_logged']); }

$menu = read_json(MENU_FILE, ['products' => [], 'ingredients' => ['sizes'=>[], 'meats'=>[], 'sauces'=>[], 'inside'=>[], 'extras'=>[]]]);
$orders = read_json(ORDERS_FILE, ['orders' => []]);
$notice = '';

if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
  $user = clean($_POST['username'] ?? '', 80);
  $pass = (string)($_POST['password'] ?? '');
  if ($user === ADMIN_USER && hash_equals(ADMIN_PASSWORD_HASH, hash('sha256', $pass))) {
    $_SESSION['admin_logged'] = true;
    header('Location: admin.php'); exit;
  }
  $notice = 'Błędny login albo hasło.';
}

if (logged() && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'save_product') {
    $id = clean($_POST['id'] ?? '', 120);
    $name = clean($_POST['name'] ?? '', 160);
    if ($name !== '') {
      if ($id === '') $id = slug($name) . '-' . substr(md5(uniqid('', true)), 0, 5);
      $product = [
        'id' => $id,
        'name' => $name,
        'category' => clean($_POST['category'] ?? 'lawasz', 80),
        'badge' => clean($_POST['badge'] ?? 'Menu', 80),
        'price' => (float)($_POST['price'] ?? 0),
        'description' => clean($_POST['description'] ?? '', 600),
        'image' => clean($_POST['image'] ?? '', 1000),
        'active' => !empty($_POST['active'])
      ];
      $found = false;
      foreach ($menu['products'] as $i => $p) { if (($p['id'] ?? '') === $id) { $menu['products'][$i] = $product; $found = true; break; } }
      if (!$found) $menu['products'][] = $product;
      save_json(MENU_FILE, $menu);
      $notice = 'Produkt zapisany.';
    }
  }

  if ($action === 'delete_product') {
    $id = clean($_POST['id'] ?? '', 120);
    $menu['products'] = array_values(array_filter($menu['products'] ?? [], fn($p) => ($p['id'] ?? '') !== $id));
    save_json(MENU_FILE, $menu);
    $notice = 'Produkt usunięty.';
  }

  if ($action === 'save_ingredient') {
    $group = clean($_POST['group'] ?? 'extras', 80);
    $allowed = ['sizes','meats','sauces','inside','extras'];
    if (in_array($group, $allowed, true)) {
      $id = clean($_POST['id'] ?? '', 120);
      $name = clean($_POST['name'] ?? '', 160);
      if ($name !== '') {
        if ($id === '') $id = slug($name) . '-' . substr(md5(uniqid('', true)), 0, 5);
        $item = [
          'id' => $id,
          'name' => $name,
          'label' => clean($_POST['label'] ?? $name, 160),
          'price' => (float)($_POST['price'] ?? 0),
          'default' => !empty($_POST['default']),
          'active' => !empty($_POST['active'])
        ];
        if (!isset($menu['ingredients'][$group]) || !is_array($menu['ingredients'][$group])) $menu['ingredients'][$group] = [];
        $found = false;
        foreach ($menu['ingredients'][$group] as $i => $x) { if (($x['id'] ?? '') === $id) { $menu['ingredients'][$group][$i] = $item; $found = true; break; } }
        if (!$found) $menu['ingredients'][$group][] = $item;
        save_json(MENU_FILE, $menu);
        $notice = 'Składnik zapisany.';
      }
    }
  }

  if ($action === 'delete_ingredient') {
    $group = clean($_POST['group'] ?? '', 80);
    $id = clean($_POST['id'] ?? '', 120);
    if (isset($menu['ingredients'][$group])) {
      $menu['ingredients'][$group] = array_values(array_filter($menu['ingredients'][$group], fn($x) => ($x['id'] ?? '') !== $id));
      save_json(MENU_FILE, $menu);
      $notice = 'Składnik usunięty.';
    }
  }

  if ($action === 'order_status') {
    $id = clean($_POST['id'] ?? '', 120);
    $status = clean($_POST['status'] ?? 'nowe', 80);
    foreach ($orders['orders'] as $i => $o) { if (($o['id'] ?? '') === $id) { $orders['orders'][$i]['status'] = $status; $orders['orders'][$i]['updated_at'] = date('Y-m-d H:i:s'); } }
    save_json(ORDERS_FILE, $orders);
    $notice = 'Status zamówienia zmieniony.';
  }

  $menu = read_json(MENU_FILE, $menu);
  $orders = read_json(ORDERS_FILE, $orders);
}

$editProduct = null;
if (logged() && isset($_GET['edit_product'])) foreach ($menu['products'] as $p) if (($p['id'] ?? '') === $_GET['edit_product']) $editProduct = $p;
$editIngredient = null; $editGroup = $_GET['group'] ?? 'extras';
if (logged() && isset($_GET['edit_ingredient'], $_GET['group'])) foreach (($menu['ingredients'][$_GET['group']] ?? []) as $x) if (($x['id'] ?? '') === $_GET['edit_ingredient']) { $editIngredient = $x; $editGroup = $_GET['group']; }
$groups = ['sizes'=>'Rozmiary', 'meats'=>'Mięsa / baza', 'sauces'=>'Sosy', 'inside'=>'Dodatki w środku', 'extras'=>'Dodatki płatne'];
?>
<!DOCTYPE html><html lang="pl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Panel admina — KebabKing</title><style>
body{margin:0;font-family:Arial,sans-serif;background:#fff6df;color:#20130d}.wrap{width:min(1180px,calc(100% - 32px));margin:auto}.top{background:#fff;border-bottom:1px solid #eadfcd;position:sticky;top:0;z-index:2}.top .wrap{min-height:72px;display:flex;align-items:center;justify-content:space-between}.logo{font-weight:900;font-size:24px;color:#d71920}.btn,button{border:0;border-radius:999px;padding:11px 16px;background:#ffbc0d;font-weight:900;cursor:pointer;text-decoration:none;color:#20130d}.btn.dark{background:#20130d;color:white}.main{padding:34px 0 80px}.card{background:#fff;border:1px solid #eadfcd;border-radius:24px;padding:22px;margin:0 0 20px;box-shadow:0 18px 45px rgba(70,31,10,.1)}h1,h2{letter-spacing:-.04em}input,select,textarea{width:100%;box-sizing:border-box;padding:12px;border:1px solid #ddd;border-radius:12px}label{display:grid;gap:6px;font-weight:800;color:#715f53}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.wide{grid-column:span 2}.check{display:flex;gap:8px;align-items:center}.check input{width:auto}table{width:100%;border-collapse:collapse;min-width:760px}th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}th{color:#715f53}.scroll{overflow:auto}.notice{background:#fff0c2;padding:12px 16px;border-radius:16px;margin-bottom:16px;font-weight:800}.danger{background:#ffe0e0;color:#a81017}.order{display:grid;grid-template-columns:1fr 180px;gap:14px;background:#fff;border:1px solid #eee;border-radius:18px;padding:16px;margin:12px 0}.order pre{white-space:pre-wrap;background:#f5ead6;border-radius:14px;padding:12px}.login{max-width:420px}@media(max-width:850px){.grid,.order{grid-template-columns:1fr}.wide{grid-column:span 1}}
</style></head><body><header class="top"><div class="wrap"><a class="logo" href="index.html">KebabKing</a><nav><a class="btn dark" href="index.html">Strona</a> <?php if(logged()): ?><a class="btn" href="admin.php?logout=1">Wyloguj</a><?php endif; ?></nav></div></header><main class="wrap main">
<?php if($notice): ?><div class="notice"><?=h($notice)?></div><?php endif; ?>
<?php if(!logged()): ?>
<section class="card login"><h1>Logowanie admina</h1><p>Login: <b>admin</b><br>Hasło domyślne: <b>kebab2026!</b></p><form method="post"><input type="hidden" name="action" value="login"><p><label>Login<input name="username" value="admin"></label></p><p><label>Hasło<input type="password" name="password"></label></p><button>Zaloguj</button></form></section>
<?php else: ?>
<h1>Panel admina</h1><p>Tu zmienisz ceny, dodasz produkt, składnik i sprawdzisz zamówienia.</p>
<section class="card"><h2><?= $editProduct ? 'Edytuj produkt' : 'Dodaj produkt' ?></h2><form method="post" class="grid"><input type="hidden" name="action" value="save_product"><input type="hidden" name="id" value="<?=h($editProduct['id'] ?? '')?>"><label>Nazwa<input name="name" value="<?=h($editProduct['name'] ?? '')?>" required></label><label>Kategoria<select name="category"><?php foreach(['lawasz','box','talerz','grill','vege','dodatki'] as $c): ?><option value="<?=$c?>" <?=($editProduct['category'] ?? '')===$c?'selected':''?>><?=$c?></option><?php endforeach; ?></select></label><label>Cena<input type="number" step="0.01" name="price" value="<?=h($editProduct['price'] ?? '')?>" required></label><label>Etykieta<input name="badge" value="<?=h($editProduct['badge'] ?? '')?>"></label><label class="wide">Zdjęcie URL<input name="image" value="<?=h($editProduct['image'] ?? '')?>"></label><label class="wide">Opis<textarea name="description" rows="3"><?=h($editProduct['description'] ?? '')?></textarea></label><label class="check"><input type="checkbox" name="active" <?=($editProduct['active'] ?? true)?'checked':''?>> aktywny</label><button>Zapisz produkt</button></form></section>
<section class="card"><h2>Produkty</h2><div class="scroll"><table><tr><th>Nazwa</th><th>Kategoria</th><th>Cena</th><th>Status</th><th>Akcje</th></tr><?php foreach($menu['products'] as $p): ?><tr><td><b><?=h($p['name'])?></b><br><small><?=h($p['badge'] ?? '')?></small></td><td><?=h($p['category'])?></td><td><?=number_format((float)$p['price'],2,',',' ')?> zł</td><td><?=!empty($p['active'])?'aktywny':'ukryty'?></td><td><a class="btn" href="?edit_product=<?=h($p['id'])?>">Edytuj</a><form method="post" style="display:inline" onsubmit="return confirm('Usunąć produkt?')"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?=h($p['id'])?>"><button class="danger">Usuń</button></form></td></tr><?php endforeach; ?></table></div></section>
<section class="card"><h2><?= $editIngredient ? 'Edytuj składnik' : 'Dodaj składnik' ?></h2><form method="post" class="grid"><input type="hidden" name="action" value="save_ingredient"><input type="hidden" name="id" value="<?=h($editIngredient['id'] ?? '')?>"><label>Grupa<select name="group"><?php foreach($groups as $key=>$label): ?><option value="<?=$key?>" <?=$editGroup===$key?'selected':''?>><?=$label?></option><?php endforeach; ?></select></label><label>Nazwa<input name="name" value="<?=h($editIngredient['name'] ?? '')?>" required></label><label>Etykieta<input name="label" value="<?=h($editIngredient['label'] ?? '')?>"></label><label>Dopłata<input type="number" step="0.01" name="price" value="<?=h($editIngredient['price'] ?? 0)?>"></label><label class="check"><input type="checkbox" name="default" <?=!empty($editIngredient['default'])?'checked':''?>> domyślne</label><label class="check"><input type="checkbox" name="active" <?=($editIngredient['active'] ?? true)?'checked':''?>> aktywne</label><button>Zapisz składnik</button></form></section>
<section class="card"><h2>Składniki</h2><div class="scroll"><table><tr><th>Grupa</th><th>Nazwa</th><th>Dopłata</th><th>Status</th><th>Akcje</th></tr><?php foreach($groups as $g=>$gl): foreach(($menu['ingredients'][$g] ?? []) as $x): ?><tr><td><?=h($gl)?></td><td><b><?=h($x['label'] ?? $x['name'])?></b><br><small><?=h($x['name'])?></small></td><td><?=number_format((float)($x['price'] ?? 0),2,',',' ')?> zł</td><td><?=!empty($x['active'])?'aktywny':'ukryty'?><?=!empty($x['default'])?'<br><small>domyślne</small>':''?></td><td><a class="btn" href="?group=<?=h($g)?>&edit_ingredient=<?=h($x['id'])?>">Edytuj</a><form method="post" style="display:inline" onsubmit="return confirm('Usunąć składnik?')"><input type="hidden" name="action" value="delete_ingredient"><input type="hidden" name="group" value="<?=h($g)?>"><input type="hidden" name="id" value="<?=h($x['id'])?>"><button class="danger">Usuń</button></form></td></tr><?php endforeach; endforeach; ?></table></div></section>
<section class="card"><h2>Zamówienia</h2><?php if(empty($orders['orders'])): ?><p>Brak zamówień.</p><?php endif; ?><?php foreach(($orders['orders'] ?? []) as $o): ?><article class="order"><div><h3><?=h($o['id'])?></h3><p><b><?=h($o['name'] ?: 'brak imienia')?></b> · tel. <?=h($o['phone'])?> · <?=h($o['created_at'])?> · <?=number_format((float)$o['total'],2,',',' ')?> zł</p><pre><?=h($o['order_text'])?></pre></div><form method="post"><input type="hidden" name="action" value="order_status"><input type="hidden" name="id" value="<?=h($o['id'])?>"><label>Status<select name="status" onchange="this.form.submit()"><?php foreach(['nowe','przyjęte','w realizacji','gotowe','odebrane','anulowane'] as $s): ?><option value="<?=$s?>" <?=($o['status'] ?? '')===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select></label></form></article><?php endforeach; ?></section>
<?php endif; ?></main></body></html>
