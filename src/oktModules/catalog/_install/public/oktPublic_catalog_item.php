<?php

# fichier nécessaire pour afficher un produit du catalogue
require_once __DIR__.'/oktModules/catalog/inc/public/item.php';


# affichage du template
echo $okt->tpl->render('catalog_item_tpl', array(
	'product' => $product
));

