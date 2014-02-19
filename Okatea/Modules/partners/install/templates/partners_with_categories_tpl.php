
<?php # début Okatea : ce template étend le layout
$view->extend('layout');
# fin Okatea : ce template étend le layout ?>


<?php # début Okatea : ajout de la CSS spécifique au module
$okt->page->css->addFile($okt->theme->url.'/modules/partners/styles.css');
# fin Okatea : ajout de la CSS spécifique au module ?>


<?php # début Okatea : ajout de jQuery
$okt->page->js->addFile($okt->options->public_url.'/components/jquery/dist/jquery.min.js');
# fin Okatea : ajout de jQuery ?>


<?php # début Okatea : ajout du modal
$okt->page->applyLbl($okt->partners->config->lightbox_type);
# fin Okatea : ajout du modal ?>


<?php # début Okatea : si il n'y a PAS de partenaires à afficher on peut indiquer un message
if ($rsPartners->isEmpty()) : ?>

<p><em><?php _e('m_partners_there_is_no_partners') ?></em></p>

<?php endif; # fin Okatea : si il n'y a PAS de partenaires à afficher on peut indiquer un message ?>


<?php # début Okatea : si il y a des partenaires à afficher on affiche la liste
if (!$rsPartners->isEmpty()) : ?>

<div id="partners_list">

	<?php # début Okatea : boucle sur la liste des partenaires
	$iCurrentCategoryId = 0;
	while ($rsPartners->fetch()) : ?>

		<?php # si les catégories sont activées on affichent le titre de ces dernières
		if ($okt->partners->config->enable_categories && $iCurrentCategoryId != $rsPartners->category_id)
		{
			if ($iCurrentCategoryId > 0) {
				echo '</div>';
			}

			echo '<h2>'.$view->escape($rsPartners->category_name).'</h2>';
			echo '<div>';

			$iCurrentCategoryId = $rsPartners->category_id;
		}
		?>

	<?php # début Okatea : affichage d'un partenaire ?>
	<div class="partner">

		<?php # début Okatea : affichage logo
		if (!empty($rsPartners->logo)) : ?>

		<div class="logo">
			<?php $aPartnerLogoInfos = $rsPartners->getImagesInfo();

			# affichage square ou icon ?
			if (isset($aPartnerLogoInfos['min_url'])) {
				$logo_url = $aPartnerLogoInfos['min_url'];
				$logo_attr = $aPartnerLogoInfos['min_attr'];
			}
			else {
				$logo_url = $okt->options->public_url.'/img/media/image.png';
				$logo_attr = ' width="48" height="48" ';
			}
			?>
			<a href="<?php echo $aPartnerLogoInfos['img_url'] ?>"
			title="<?php echo $view->escapeHtmlAttr($rsPartners->name) ?>"
			class="modal" rel="partner-logo"><img src="<?php echo $logo_url ?>"
			<?php echo $logo_attr ?>
			alt="<?php echo $view->escapeHtmlAttr((isset($aPartnerLogoInfos['alt']) ? $aPartnerLogoInfos['alt'] : 'Logo '.$rsPartners->name)) ?>" /></a>
		</div>
		<?php endif; # fin Okatea : affichage logo  ?>

		<?php # début Okatea : affichage du nom ?>
		<h3 class="partner-name"><?php echo $view->escape($rsPartners->name) ?></h3>
		<?php # fin Okatea : affichage du nom ?>

		<div class="partner-description">

			<?php # début Okatea : affichage description ?>
			<?php echo $rsPartners->description ?>
			<?php # fin Okatea : affichage du description ?>

			<?php # début Okatea : affichage URL
			if (!empty($rsPartners->url)) : ?>
			<p><a href="<?php echo $view->escape($rsPartners->url) ?>" class="partner-url" title="<?php
			echo $rsPartners->name ?>"><?php echo (!empty($rsPartners->url_title) ? $rsPartners->url_title : __('m_partners_default_url_title')) ?></a></p>
			<?php endif; # fin Okatea : affichage URL ?>

		</div><!-- .partner-description -->

	</div><!-- .partner -->
	<?php # fin Okatea : affichage d'un partenaire ?>

	<?php endwhile; # fin Okatea : boucle sur la liste des partenaires ?>

	<?php if ($okt->partners->config->enable_categories)  : ?>
	</div>
	<?php endif; ?>

</div><!-- #partners_list -->

<?php endif; # fin Okatea : si il y a des partenaires à afficher on affiche la liste ?>
