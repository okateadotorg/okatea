<?php
use Okatea\Tao\Forms\Statics\FormElements as form;

$view->extend('Layout');

# Titre de la page
$okt->page->addGlobalTitle('Configuration TinyMCE');

# Liste de fichiers CSS éventuellement utilisables
$aUsableCSS = array(
/*
	$okt->theme->url.'/css/style.css',
	$okt->theme->url.'/css/styles.css',
	$okt->theme->url.'/css/editor.css',
	$okt->theme->url.'/css/custom.css',
*/
	$okt['config']->app_url . 'css/style.css',
	$okt['config']->app_url . 'css/styles.css',
	$okt['config']->app_url . 'css/editor.css',
	$okt['config']->app_url . 'css/custom.css',
	$okt['config']->app_url . 'editor.css',
	$okt['config']->app_url . 'custom.css'
);
?>


<form action="<?php echo $view->generateAdminUrl('RteTinymce3_config'); ?>"
	method="post">

	<div class="two-cols">
		<p class="field col">
			<label for="p_width">Largeur de l'editeur</label>
		<?php echo form::text('p_width', 10, 255, $okt->module('RteTinymce3')->config->width) ?></p>

		<p class="field col">
			<label for="p_height">Hauteur de l'editeur</label>
		<?php echo form::text('p_height', 10, 255, $okt->module('RteTinymce3')->config->height) ?></p>
	</div>

	<p class="field">Feuille de styles du contenu</p>
	<ul class="checklist">
		<li><?php echo form::radio(array('p_content_css','p_content_css_0'), 0, ($okt->module('RteTinymce3')->config->content_css == 0))?> <label
			for="p_content_css_0"><?php _e('c_c_none_f') ?></label></li>
		<?php foreach ($aUsableCSS as $sCss) : ?>
		<li><?php echo form::radio(array('p_content_css','p_content_css_'.$sCss), $sCss, ($okt->module('RteTinymce3')->config->content_css == $sCss), '', '', !file_exists($_SERVER['DOCUMENT_ROOT'].$sCss))?>
		<label for="p_content_css_<?php echo $sCss ?>"><?php echo $sCss ?></label></li>
		<?php endforeach; ?>
	</ul>

	<p><?php echo form::hidden(array('form_sent'), 1); ?>
	<?php echo $okt->page->formtoken(); ?>
	<input type="submit" value="enregistrer" />
	</p>
</form>
