<?php
return array(
  'products' => array(
    array('id'=>'lawasz-kurczak','name'=>'Lawasz Kurczak','category'=>'lawasz','badge'=>'Bestseller','price'=>24,'description'=>'Kurczak, świeże warzywa i wybrany sos w lawaszu.','image'=>'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=900&q=85','active'=>true),
    array('id'=>'lawasz-mieszany','name'=>'Lawasz Mieszany','category'=>'lawasz','badge'=>'Najczęściej wybierane','price'=>29,'description'=>'Kurczak + wołowina-baranina, warzywa i sos mieszany.','image'=>'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=85','active'=>true),
    array('id'=>'box-frytki','name'=>'Kebab Box z frytkami','category'=>'box','badge'=>'Na szybko','price'=>26,'description'=>'Mięso, frytki, surówka i sos. Dobry na lunch.','image'=>'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=900&q=85','active'=>true),
    array('id'=>'talerz','name'=>'Kebab na talerzu','category'=>'talerz','badge'=>'Duża porcja','price'=>37,'description'=>'Mięso, frytki albo ryż, surówka i sos.','image'=>'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=85','active'=>true),
    array('id'=>'adana','name'=>'Adana Grill','category'=>'grill','badge'=>'Ostry hit','price'=>44,'description'=>'Ostry szaszłyk wołowy, lawasz, dodatki i sos.','image'=>'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?auto=format&fit=crop&w=900&q=85','active'=>true),
    array('id'=>'falafel','name'=>'Falafel Rolada','category'=>'vege','badge'=>'Vege','price'=>22,'description'=>'Falafel, warzywa, hummus albo sos czosnkowy.','image'=>'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=85','active'=>true),
    array('id'=>'zestaw','name'=>'Lawasz Zestaw','category'=>'lawasz','badge'=>'Zestaw','price'=>32.5,'description'=>'Lawasz, frytki i napój 0,5 l.','image'=>'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=900&q=85','active'=>true)
  ),
  'ingredients' => array(
    'sizes' => array(
      array('id'=>'standard','name'=>'standard','label'=>'Standard','price'=>0,'default'=>true,'active'=>true),
      array('id'=>'mega','name'=>'mega','label'=>'Mega','price'=>8,'default'=>false,'active'=>true),
      array('id'=>'mini','name'=>'mini','label'=>'Mini','price'=>-5,'default'=>false,'active'=>true)
    ),
    'meats' => array(
      array('id'=>'kurczak','name'=>'Kurczak','label'=>'Kurczak','price'=>0,'default'=>true,'active'=>true),
      array('id'=>'wolowina','name'=>'Wołowina-baranina','label'=>'Wołowina-baranina','price'=>4,'default'=>false,'active'=>true),
      array('id'=>'mieszane','name'=>'Mieszane','label'=>'Mieszane','price'=>5,'default'=>false,'active'=>true),
      array('id'=>'falafel','name'=>'Falafel','label'=>'Falafel','price'=>-2,'default'=>false,'active'=>true)
    ),
    'sauces' => array(
      array('id'=>'czosnkowy','name'=>'czosnkowy','label'=>'czosnkowy','price'=>0,'default'=>true,'active'=>true),
      array('id'=>'lagodny','name'=>'łagodny','label'=>'łagodny','price'=>0,'default'=>false,'active'=>true),
      array('id'=>'mieszany','name'=>'mieszany','label'=>'mieszany','price'=>0,'default'=>false,'active'=>true),
      array('id'=>'ostry','name'=>'ostry','label'=>'ostry','price'=>0,'default'=>false,'active'=>true)
    ),
    'inside' => array(
      array('id'=>'surowka','name'=>'surówka','label'=>'surówka','price'=>0,'default'=>true,'active'=>true),
      array('id'=>'pomidor','name'=>'pomidor','label'=>'pomidor','price'=>0,'default'=>true,'active'=>true),
      array('id'=>'ogorek','name'=>'ogórek','label'=>'ogórek','price'=>0,'default'=>true,'active'=>true),
      array('id'=>'cebula','name'=>'cebula','label'=>'cebula','price'=>0,'default'=>false,'active'=>true)
    ),
    'extras' => array(
      array('id'=>'ser','name'=>'ser','label'=>'Ser','price'=>4,'default'=>false,'active'=>true),
      array('id'=>'frytki-srodek','name'=>'frytki w środku','label'=>'Frytki w środku','price'=>6,'default'=>false,'active'=>true),
      array('id'=>'halloumi','name'=>'halloumi','label'=>'Halloumi','price'=>8,'default'=>false,'active'=>true),
      array('id'=>'napoj','name'=>'napój','label'=>'Napój','price'=>7,'default'=>false,'active'=>true)
    )
  )
);
