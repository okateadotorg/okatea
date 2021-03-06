<?php
/**
 * @ingroup okt_module_partners
 * @brief La page de configuration de l'affichage
 *
 */
use Okatea\Admin\Page;
use Okatea\Tao\Forms\Statics\FormElements as form;

# Accès direct interdit
if (!defined('ON_MODULE'))
	die();
	
	/* Traitements
----------------------------------------------------------*/

if (!empty($_POST['form_sent']))
{
	$p_lightbox_type = !empty($_POST['p_lightbox_type']) ? $_POST['p_lightbox_type'] : '';
	
	if ($okt->error->isEmpty())
	{
		$aNewConf = array(
			
			'lightbox_type' => $p_lightbox_type
		);
		
		$okt->partners->config->write($aNewConf);
		
		$okt['flashMessages']->success(__('c_c_confirm_configuration_updated'));
		
		http::redirect('module.php?m=partners&action=display&updated=1');
	}
}

/* Affichage
----------------------------------------------------------*/

# Titre de la page
$okt->page->addGlobalTitle(__('c_a_menu_display'));

# Tabs
$okt->page->tabs();

# Modal
$okt->page->applyLbl($okt->partners->config->lightbox_type);

# En-tête
require OKT_ADMIN_HEADER_FILE;
?>

<form action="module.php" method="post">
	<div id="tabered">
		<ul>
			<?php if ($okt->partners->config->images['enable']) : ?>
			<li><a href="#tab_images"><span><?php _e('Image')?></span></a></li>
			<?php endif; ?>
		</ul>

		<?php if ($okt->partners->config->images['enable']) : ?>
		<div id="tab_images">
			<h3><?php _e('m_partners_display_images')?></h3>
			<fieldset>
				<legend><?php _e('m_partners_expansion_images')?></legend>

				<?php if ($okt->page->hasLbl()) : ?>
					<p class="field">
					<label for="p_lightbox_type"><?php _e('m_partners_choose_interface_images')?></label>
					<?php echo form::select('p_lightbox_type',array_merge(array(__('c_c_action_Disable')=>0),$okt->page->getLblList(true)),$okt->partners->config->lightbox_type) ?></p>

				<p><?php _e('m_partners_currently_used')?> <em><?php
				
$aChoices = array_merge(array(
					'' => __('c_c_none_f')
				), $okt->page->getLblList());
				echo $aChoices[$okt->partners->config->lightbox_type]?></em>
				</p>
				<?php else : ?>
					<p>
					<span class="icon error"></span><?php _e('m_partners_no_interface_images')?>
					<?php echo form::hidden('p_lightbox_type',0); ?></p>
				<?php endif;?>

				<p class="modal-box">
					<a class="modal" rel="test_images"
						href="<?php echo $okt['public_url'] ?>/img/sample/chutes_la_nuit.jpg">
						<img width="60" height="60" alt=""
						src="<?php echo $okt['public_url'] ?>/img/sample/sq-chutes_la_nuit.jpg" />
					</a> <a class="modal" rel="test_images"
						href="<?php echo $okt['public_url'] ?>/img/sample/les_chutes.jpg">
						<img width="60" height="60" alt=""
						src="<?php echo $okt['public_url'] ?>/img/sample/sq-les_chutes.jpg" />
					</a> <a class="modal" rel="test_images"
						href="<?php echo $okt['public_url'] ?>/img/sample/chutes.jpg">
						<img width="60" height="60" alt=""
						src="<?php echo $okt['public_url'] ?>/img/sample/sq-chutes.jpg" />
					</a>
				</p>
			</fieldset>
		</div>
		<!-- #tab_images -->
		<?php endif; ?>

	</div>
	<!-- #tabered -->

	<p><?php echo form::hidden('m','partners'); ?>
	<?php echo form::hidden(array('form_sent'), 1); ?>
	<?php echo form::hidden(array('action'), 'display'); ?>
	<?php echo Page::formtoken(); ?>
	<input type="submit" value="<?php _e('c_c_action_save')?>" />
	</p>
</form>

<?php # Pied-de-page
require OKT_ADMIN_FOOTER_FILE; ?>
