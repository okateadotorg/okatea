
<?php 
# début Okatea : ce template étend le layout
$view->extend('Layout');
# fin Okatea : ce template étend le layout ?>


<?php 
# début Okatea : ajout de la CSS spécifique au module
$okt->page->css->addFile($okt->theme->url . '/modules/partners/styles.css');
# fin Okatea : ajout de la CSS spécifique au module ?>


<?php 
# début Okatea : ajout de jQuery
$okt->page->js->addFile($okt['public_url'] . '/components/jquery/dist/jquery.min.js');
# fin Okatea : ajout de jQuery ?>


<?php 
# début Okatea : ajout du modal
$okt->page->applyLbl($okt->partners->config->lightbox_type);
# fin Okatea : ajout du modal ?>


<?php 
# début Okatea : si il n'y a PAS de partenaires à afficher on peut indiquer un message
if ($rsPartners->isEmpty())
:
	?>

<p>
	<em><?php _e('m_partners_there_is_no_partners') ?></em>
</p>

<?php endif; # fin Okatea : si il n'y a PAS de partenaires à afficher on peut indiquer un message ?>


<?php 
# début Okatea : si il y a des partenaires à afficher on affiche la liste
if (!$rsPartners->isEmpty())
:
	?>

<div id="partners_list">

	<?php 
# début Okatea : boucle sur la liste des partenaires
	while ($rsPartners->fetch())
	:
		?>

	<?php # début Okatea : affichage d'un partenaire ?>
	<div class="partner">

		<?php 
# début Okatea : affichage logo
		if (!empty($rsPartners->logo))
		:
			?>

		<div class="logo">
			<?php
			
$aPartnerLogoInfos = $rsPartners->getImagesInfo();
			
			# affichage square ou icon ?
			if (isset($aPartnerLogoInfos['min_url']))
			{
				$logo_url = $aPartnerLogoInfos['min_url'];
				$logo_attr = $aPartnerLogoInfos['min_attr'];
			}
			else
			{
				$logo_url = $okt['public_url'] . '/img/media/image.png';
				$logo_attr = ' width="48" height="48" ';
			}
			?>
			<a href="<?php echo $aPartnerLogoInfos['img_url'] ?>"
				title="<?php echo $view->escapeHtmlAttr($rsPartners->name) ?>"
				class="modal" rel="partner-logo"><img src="<?php echo $logo_url ?>"
				<?php echo $logo_attr?>
				alt="<?php echo $view->escapeHtmlAttr((isset($aPartnerLogoInfos['alt']) ? $aPartnerLogoInfos['alt'] : 'Logo '.$rsPartners->name)) ?>" /></a>
		</div>
		<?php endif; # fin Okatea : affichage logo  ?>

		<?php # début Okatea : affichage du nom ?>
		<h2 class="partner-name"><?php echo $view->escape($rsPartners->name) ?></h2>
		<?php # fin Okatea : affichage du nom ?>

		<div class="partner-description">

			<?php # début Okatea : affichage description ?>
			<?php echo $rsPartners->description?>
			<?php # fin Okatea : affichage du description ?>

			<?php 
# début Okatea : affichage URL
		if (!empty($rsPartners->url))
		:
			?>
			<p>
				<a href="<?php echo $view->escape($rsPartners->url) ?>"
					class="partner-url" title="<?php
			echo $rsPartners->name?>"><?php echo (!empty($rsPartners->url_title) ? $rsPartners->url_title : __('m_partners_default_url_title')) ?></a>
			</p>
			<?php endif; # fin Okatea : affichage URL ?>

		</div>
		<!-- .partner-description -->

	</div>
	<!-- .partner -->
	<?php # fin Okatea : affichage d'un partenaire ?>

	<?php endwhile; # fin Okatea : boucle sur la liste des partenaires ?>

</div>
<!-- #partners_list -->

<?php endif; # fin Okatea : si il y a des partenaires à afficher on affiche la liste ?>
