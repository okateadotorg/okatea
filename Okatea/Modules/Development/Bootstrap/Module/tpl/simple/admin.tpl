<?php
##header##


# Accès direct interdit
if (!defined('ON_MODULE')) die;


# title tag
$okt->page->addTitleTag($okt->##module_id##->getTitle());

# fil d'ariane
$okt->page->addAriane($okt->##module_id##->getName(),'module.php?m=##module_id##');


# inclusion du fichier requis en fonction de l'action demandée
if ($okt->page->action === 'config') {
	require __DIR__.'/admin/config.php';
}
else {
	http::redirect('index.php');
}
