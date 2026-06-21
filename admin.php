<?php
if (isset($_GET['debug'])) { ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL); }
require __DIR__ . '/app-config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$MENU_STORE = __DIR__ . '/menu-data.php';
$ORDERS_STORE = __DIR__ . '/orders-data.php';
$groups = array('sizes'=>'Rozmiary','meats'=>'Mięsa / baza','sauces'=>'Sosy','inside'=>'Dodatki w środku','extras'=>'Dodatki płatne');
$cats = array('lawasz','box','talerz','grill','vege','dodatki');
$statuses = array('nowe','przyjęte','w realizacji','gotowe','odebrane','anulowane');
$notice = '';
$error = '';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function cleanv($v,$l=1000){ $v=strip_tags(trim((string)$v)); $v=str_replace(array("\0","\r"),'',$v); return function_exists('mb_substr') ? mb_substr($v,0,$l,'UTF-8') : substr($v,0,$l); }
function slugv($v){ if(function_exists('iconv')){ $x=@iconv('UTF-8','ASCII//TRANSLIT',$v); if($x!==false)$v=$x; } $v=strtolower(preg_replace('/[^a-zA-Z0-9]+/','-',$v)); $v=trim($v,'-'); return $v ? $v : 'item'; }
function logged(){ return !empty($_SESSION['admin_logged']); }
function load_store($file,$fallback){ if(file_exists($file)){ $data=include $file; if(is_array($data)) return $data; } return $fallback; }
function save_store($file,$data){ $txt="<?php\nreturn ".var_export($data,true).";\n"; return file_put_contents($file,$txt,LOCK_EX)!==false; }
function default_menu(){ return array('products'=>array(),'ingredients'=>array('sizes'=>array(),'meats'=>array(),'sauces'=>array(),'inside'=>array(),'extras'=>array())); }
function price($v){ return number_format((float)$v,2,',',' ').' zł'; }

if(isset($_GET['logout'])){ $_SESSION=array(); session_destroy(); header('Location: admin.php'); exit; }

$menu = load_store($MENU_STORE, default_menu());
$orders = load_store($ORDERS_STORE, array('orders'=>array()));
if(!isset($menu['products']) || !is_array($menu['products'])) $menu['products']=array();
if(!isset($menu['ingredients']) || !is_array($menu['ingredients'])) $menu['ingredients']=default_menu()['ingredients'];
foreach($groups as $g=>$label){ if(!isset($menu['ingredients'][$g]) || !is_array($menu['ingredients'][$g])) $menu['ingredients'][$g]=array(); }
if(!isset($orders['orders']) || !is_array($orders['orders'])) $orders['orders']=array();

if($_SERVER['REQUEST_METHOD']==='POST'){
  $action = isset($_POST['action']) ? $_POST['action'] : '';

  if($action==='login'){
    $user = cleanv(isset($_POST['username']) ? $_POST['username'] : '',80);
    $pass = isset($_POST['password']) ? $_POST['password'] : '';
    if($user===ADMIN_USER && hash_equals(ADMIN_PASSWORD_HASH, hash('sha256',$pass))){ $_SESSION['admin_logged']=true; $notice='Zalogowano.'; } else { $error='Błędny login albo hasło.'; }
  }

  if(logged() && $action==='save_product'){
    $id = cleanv(isset($_POST['id']) ? $_POST['id'] : '',120);
    $name = cleanv(isset($_POST['name']) ? $_POST['name'] : '',160);
    if($name===''){ $error='Podaj nazwę produktu.'; }
    else{
      if($id==='') $id=slugv($name).'-'.substr(md5(uniqid('',true)),0,5);
      $p=array('id'=>$id,'name'=>$name,'category'=>cleanv($_POST['category'],80),'badge'=>cleanv(isset($_POST['badge'])?$_POST['badge']:'Menu',80),'price'=>(float)$_POST['price'],'description'=>cleanv(isset($_POST['description'])?$_POST['description']:'',600),'image'=>cleanv(isset($_POST['image'])?$_POST['image']:'',1000),'active'=>!empty($_POST['active']));
      $found=false; for($i=0;$i<count($menu['products']);$i++){ if(isset($menu['products'][$i]['id']) && $menu['products'][$i]['id']===$id){ $menu['products'][$i]=$p; $found=true; break; }}
      if(!$found) $menu['products'][]=$p;
      $notice = save_store($MENU_STORE,$menu) ? 'Produkt zapisany. Odśwież stronę główną, żeby zobaczyć zmianę.' : 'BŁĄD: nie mogę zapisać menu-data.php. Daj plikowi uprawnienia zapisu.';
      if(strpos($notice,'BŁĄD')===0) $error=$notice;
    }
  }

  if(logged() && $action==='delete_product'){
    $id=cleanv($_POST['id'],120); $new=array(); foreach($menu['products'] as $p){ if(!isset($p['id']) || $p['id']!==$id) $new[]=$p; } $menu['products']=$new;
    $notice = save_store($MENU_STORE,$menu) ? 'Produkt usunięty.' : 'BŁĄD zapisu menu-data.php.';
  }

  if(logged() && $action==='save_ingredient'){
    $group=cleanv($_POST['group'],80); $name=cleanv($_POST['name'],160);
    if(!isset($groups[$group])) $error='Zła grupa składnika.';
    elseif($name==='') $error='Podaj nazwę składnika.';
    else{
      $id=cleanv(isset($_POST['id'])?$_POST['id']:'',120); if($id==='') $id=slugv($name).'-'.substr(md5(uniqid('',true)),0,5);
      $x=array('id'=>$id,'name'=>$name,'label'=>cleanv(isset($_POST['label']) && $_POST['label']!=='' ? $_POST['label'] : $name,160),'price'=>(float)(isset($_POST['price'])?$_POST['price']:0),'default'=>!empty($_POST['default']),'active'=>!empty($_POST['active']));
      $found=false; for($i=0;$i<count($menu['ingredients'][$group]);$i++){ if(isset($menu['ingredients'][$group][$i]['id']) && $menu['ingredients'][$group][$i]['id']===$id){ $menu['ingredients'][$group][$i]=$x; $found=true; break; }}
      if(!$found) $menu['ingredients'][$group][]=$x;
      $notice = save_store($MENU_STORE,$menu) ? 'Składnik zapisany.' : 'BŁĄD: nie mogę zapisać menu-data.php. Daj plikowi uprawnienia zapisu.';
      if(strpos($notice,'BŁĄD')===0) $error=$notice;
    }
  }

  if(logged() && $action==='delete_ingredient'){
    $group=cleanv($_POST['group'],80); $id=cleanv($_POST['id'],120); if(isset($menu['ingredients'][$group])){ $new=array(); foreach($menu['ingredients'][$group] as $x){ if(!isset($x['id']) || $x['id']!==$id) $new[]=$x; } $menu['ingredients'][$group]=$new; }
    $notice = save_store($MENU_STORE,$menu) ? 'Składnik usunięty.' : 'BŁĄD zapisu menu-data.php.';
  }

  if(logged() && $action==='order_status'){
    $id=cleanv($_POST['id'],120); $status=cleanv($_POST['status'],80); for($i=0;$i<count($orders['orders']);$i++){ if(isset($orders['orders'][$i]['id']) && $orders['orders'][$i]['id']===$id){ $orders['orders'][$i]['status']=$status; $orders['orders'][$i]['updated_at']=date('Y-m-d H:i:s'); }}
    $notice = save_store($ORDERS_STORE,$orders) ? 'Status zamówienia zmieniony.' : 'BŁĄD zapisu orders-data.php.';
  }

  $menu = load_store($MENU_STORE,$menu); $orders = load_store($ORDERS_STORE,$orders);
}

$editP=null; if(logged() && isset($_GET['edit_product'])){ foreach($menu['products'] as $p){ if(isset($p['id']) && $p['id']===$_GET['edit_product']){ $editP=$p; break; } } }
$editI=null; $editG=isset($_GET['group']) ? $_GET['group'] : 'extras'; if(logged() && isset($_GET['edit_ingredient'],$_GET['group']) && isset($menu['ingredients'][$_GET['group']])){ foreach($menu['ingredients'][$_GET['group']] as $x){ if(isset($x['id']) && $x['id']===$_GET['edit_ingredient']){ $editI=$x; $editG=$_GET['group']; break; } } }
$menuWritable = is_writable($MENU_STORE) || (!file_exists($MENU_STORE) && is_writable(__DIR__));
$ordersWritable = is_writable($ORDERS_STORE) || (!file_exists($ORDERS_STORE) && is_writable(__DIR__));
?>
<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin KebabKing</title><style>body{margin:0;font-family:Arial,sans-serif;background:#fff6df;color:#20130d}.wrap{width:min(1180px,calc(100% - 32px));margin:auto}.top{background:#fff;border-bottom:1px solid #eadfcd}.top .wrap{min-height:72px;display:flex;justify-content:space-between;align-items:center}.logo{font-size:24px;font-weight:900;color:#d71920;text-decoration:none}.btn,button{border:0;border-radius:999px;padding:10px 14px;background:#ffbc0d;font-weight:900;color:#20130d;text-decoration:none;cursor:pointer;display:inline-block}.dark{background:#20130d;color:#fff}.danger{background:#ffe0e0;color:#a81017}.main{padding:30px 0 80px}.card{background:#fff;border:1px solid #eadfcd;border-radius:22px;padding:20px;margin:0 0 18px;box-shadow:0 14px 36px #0001}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.wide{grid-column:span 2}label{display:grid;gap:6px;font-weight:800;color:#715f53}input,select,textarea{width:100%;box-sizing:border-box;border:1px solid #ddd;border-radius:12px;padding:11px}.check{display:flex;gap:8px;align-items:center}.check input{width:auto}.msg{padding:12px 16px;border-radius:16px;margin-bottom:14px;font-weight:900}.ok{background:#e8f8ee;color:#208a3a}.err{background:#ffe0e0;color:#a81017}.warn{background:#fff0c2}.scroll{overflow:auto}table{width:100%;min-width:760px;border-collapse:collapse}td,th{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}.order{display:grid;grid-template-columns:1fr 180px;gap:12px;border:1px solid #eee;border-radius:16px;padding:14px;margin:10px 0}.order pre{white-space:pre-wrap;background:#f5ead6;padding:12px;border-radius:12px}@media(max-width:850px){.grid,.order{grid-template-columns:1fr}.wide{grid-column:span 1}}</style></head><body><header class="top"><div class="wrap"><a class="logo" href="index.html">KebabKing</a><nav><a class="btn dark" href="index.html">Strona</a> <?php if(logged()): ?><a class="btn" href="admin.php?logout=1">Wyloguj</a><?php endif; ?></nav></div></header><main class="wrap main">
<?php if($notice): ?><div class="msg ok"><?=e($notice)?></div><?php endif; ?><?php if($error): ?><div class="msg err"><?=e($error)?></div><?php endif; ?>
<?php if(!logged()): ?><section class="card"><h1>Logowanie admina</h1><form method="post" action="admin.php"><input type="hidden" name="action" value="login"><p><label>Login<input name="username" value="admin"></label></p><p><label>Hasło<input type="password" name="password"></label></p><button>Zaloguj</button></form></section><section class="card"><h2>Test zapisu</h2><p class="msg <?= $menuWritable?'ok':'err' ?>">menu-data.php: <?= $menuWritable?'zapis OK':'BRAK ZAPISU - edycja nie będzie działać' ?></p><p class="msg <?= $ordersWritable?'ok':'err' ?>">orders-data.php: <?= $ordersWritable?'zapis OK':'BRAK ZAPISU' ?></p></section><?php else: ?>
<h1>Panel admina</h1><?php if(!$menuWritable): ?><div class="msg err">Brak zapisu do menu-data.php. Ustaw uprawnienia pliku na 664 albo 666.</div><?php endif; ?>
<section class="card"><h2><?= $editP?'Edytuj produkt':'Dodaj produkt' ?></h2><form method="post" action="admin.php" class="grid"><input type="hidden" name="action" value="save_product"><input type="hidden" name="id" value="<?=e($editP['id'] ?? '')?>"><label>Nazwa<input name="name" value="<?=e($editP['name'] ?? '')?>" required></label><label>Kategoria<select name="category"><?php foreach($cats as $c): ?><option value="<?=$c?>" <?=($editP['category'] ?? '')===$c?'selected':''?>><?=$c?></option><?php endforeach; ?></select></label><label>Cena<input type="number" step="0.01" name="price" value="<?=e($editP['price'] ?? '')?>" required></label><label>Etykieta<input name="badge" value="<?=e($editP['badge'] ?? '')?>"></label><label class="wide">Zdjęcie URL<input name="image" value="<?=e($editP['image'] ?? '')?>"></label><label class="wide">Opis<textarea name="description" rows="3"><?=e($editP['description'] ?? '')?></textarea></label><label class="check"><input type="checkbox" name="active" <?=($editP['active'] ?? true)?'checked':''?>> aktywny</label><button>Zapisz produkt</button></form></section>
<section class="card"><h2>Produkty</h2><div class="scroll"><table><tr><th>Nazwa</th><th>Kategoria</th><th>Cena</th><th>Status</th><th>Akcje</th></tr><?php foreach($menu['products'] as $p): ?><tr><td><b><?=e($p['name'])?></b><br><small><?=e($p['badge'] ?? '')?></small></td><td><?=e($p['category'])?></td><td><?=price($p['price'])?></td><td><?=!empty($p['active'])?'aktywny':'ukryty'?></td><td><a class="btn" href="admin.php?edit_product=<?=e($p['id'])?>">Edytuj</a> <form method="post" action="admin.php" style="display:inline" onsubmit="return confirm('Usunąć produkt?')"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?=e($p['id'])?>"><button class="danger">Usuń</button></form></td></tr><?php endforeach; ?></table></div></section>
<section class="card"><h2><?= $editI?'Edytuj składnik':'Dodaj składnik' ?></h2><form method="post" action="admin.php" class="grid"><input type="hidden" name="action" value="save_ingredient"><input type="hidden" name="id" value="<?=e($editI['id'] ?? '')?>"><label>Grupa<select name="group"><?php foreach($groups as $g=>$lab): ?><option value="<?=$g?>" <?=$editG===$g?'selected':''?>><?=$lab?></option><?php endforeach; ?></select></label><label>Nazwa<input name="name" value="<?=e($editI['name'] ?? '')?>" required></label><label>Etykieta<input name="label" value="<?=e($editI['label'] ?? '')?>"></label><label>Dopłata<input type="number" step="0.01" name="price" value="<?=e($editI['price'] ?? 0)?>"></label><label class="check"><input type="checkbox" name="default" <?=!empty($editI['default'])?'checked':''?>> domyślne</label><label class="check"><input type="checkbox" name="active" <?=($editI['active'] ?? true)?'checked':''?>> aktywne</label><button>Zapisz składnik</button></form></section>
<section class="card"><h2>Składniki</h2><div class="scroll"><table><tr><th>Grupa</th><th>Nazwa</th><th>Dopłata</th><th>Status</th><th>Akcje</th></tr><?php foreach($groups as $g=>$lab): foreach(($menu['ingredients'][$g] ?? array()) as $x): ?><tr><td><?=e($lab)?></td><td><b><?=e($x['label'] ?? $x['name'])?></b><br><small><?=e($x['name'])?></small></td><td><?=price($x['price'] ?? 0)?></td><td><?=!empty($x['active'])?'aktywny':'ukryty'?><?=!empty($x['default'])?'<br><small>domyślne</small>':''?></td><td><a class="btn" href="admin.php?group=<?=e($g)?>&edit_ingredient=<?=e($x['id'])?>">Edytuj</a> <form method="post" action="admin.php" style="display:inline" onsubmit="return confirm('Usunąć składnik?')"><input type="hidden" name="action" value="delete_ingredient"><input type="hidden" name="group" value="<?=e($g)?>"><input type="hidden" name="id" value="<?=e($x['id'])?>"><button class="danger">Usuń</button></form></td></tr><?php endforeach; endforeach; ?></table></div></section>
<section class="card"><h2>Zamówienia</h2><?php if(empty($orders['orders'])): ?><p>Brak zamówień.</p><?php endif; ?><?php foreach(($orders['orders'] ?? array()) as $o): ?><article class="order"><div><h3><?=e($o['id'])?></h3><p><b><?=e($o['name'] ?: 'brak imienia')?></b> · tel. <?=e($o['phone'])?> · <?=e($o['created_at'])?> · <?=price($o['total'])?></p><pre><?=e($o['order_text'])?></pre></div><form method="post" action="admin.php"><input type="hidden" name="action" value="order_status"><input type="hidden" name="id" value="<?=e($o['id'])?>"><label>Status<select name="status" onchange="this.form.submit()"><?php foreach($statuses as $s): ?><option value="<?=$s?>" <?=($o['status'] ?? '')===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select></label></form></article><?php endforeach; ?></section>
<?php endif; ?></main></body></html>
