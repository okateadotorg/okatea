
<?php 
# début Okatea : ce template étend le layout
$view->extend('Layout');
# fin Okatea : ce template étend le layout ?>


<?php 
# début Okatea : ajout de la CSS spécifique au module
$okt->page->css->addFile($okt->theme->url . '/modules/faq/styles.css');
# fin Okatea : ajout de la CSS spécifique au module ?>


<?php 
# début Okatea : ajout de jQuery
$okt->page->js->addFile($okt['public_url'] . '/components/jquery/dist/jquery.min.js');
# fin Okatea : ajout de jQuery ?>


<?php 
# début Okatea : ajout du JS de scrollToTopOfPage
$okt->page->js->addFile($okt['public_url'] . '/plugins/easing/jquery.easing.min.js');
$okt->page->js->addFile($okt['public_url'] . '/plugins/scrollToTopOfPage/jquery.scrollToTopOfPage.min.js');
$okt->page->js->addReady('
	$("a.scrollTop").scrollToTopOfPage({
		"top": 300,					// hauteur avant affichage du lien
		"duration": 1700,			// durée animation retour en haut
		"easing": "easeOutQuint"	// type animation retour en haut
	});
');
# fin Okatea : ajout du JS de scrollToTopOfPage ?>


<?php 
# début Okatea : ajout du modal
$okt->page->applyLbl($okt->faq->config->lightbox_type);
# fin Okatea : ajout du modal ?>


<?php 
# début Okatea : javascript pour afficher les filtres s'ils sont repliés
if ($okt->faq->config->enable_filters && !$okt->faq->filters->params->show_filters)
{
	$okt->page->js->addReady('
		var c = $("#filter-control").html("<a href=\"#\">' . __('m_faq_display_filters') . '</a>");

		c.css("display","block");

		$("#' . $okt->faq->filters->getFilterFormId() . '").hide();

		c.click(function() {
			$("#' . $okt->faq->filters->getFilterFormId() . '").slideDown("slow");
			$(this).hide();
			return false;
		});
	');
}
# fin Okatea : javascript pour afficher les filtres s'ils sont repliés ?>


<?php 
# début Okatea : si les filtres sont activés
if ($okt->faq->config->enable_filters)
:
	?>

	<?php 
# début Okatea : lien d'affichage des filtres
	if (!$okt->faq->filters->params->show_filters)
	:
		?>
<p id="filter-control"></p>
<?php endif; # fin Okatea : lien d'affichage des filtres ?>


	<?php # début Okatea : affichage des filtres ?>
<form action="<?php echo $view->escape(FaqHelpers::getFaqUrl()) ?>"
	method="get" id="<?php echo $okt->faq->filters->getFilterFormId() ?>"
	class="filters-form">
	<fieldset>
		<legend><?php _e('m_faq_display_filters') ?></legend>

		<?php echo $okt->faq->filters->getFiltersFields(); ?>

		<p class="center">
			<input type="submit" value="<?php _e('c_c_action_display') ?>"
				name="<?php echo $okt->faq->filters->getFilterSubmitName() ?>" /> <a
				href="<?php echo $view->escape(FaqHelpers::getFaqUrl()) ?>?language=<?php echo $okt['visitor']->language; ?>&amp;init_filters=1"
				rel="nofollow" class="italic"><?php _e('m_faq_display_filters_init') ?></a>
		</p>

	</fieldset>
</form>
<?php # fin Okatea : affichage des filtres ?>

<?php endif; # fin Okatea : si les filtres sont activés ?>


<?php 
# Okatea : si il n'y a pas de question à afficher on peut indiquer un message
if ($faqList->isEmpty())
:
	?>

<p>
	<em><?php _e('m_faq_there_is_no_questions') ?></em>
</p>


<?php 
# Okatea : sinon on affiche la liste des questions
else
:
	?>

<div id="questions_list">

	<?php 
# début Okatea : boucle sur la liste des questions
	while ($faqList->fetch())
	:
		?>

	<?php # début Okatea : affichage d'une question ?>
	<div class="question <?php echo $faqList->odd_even ?>">


		<?php # début Okatea : affichage de la question ?>
		<h2 class="question-title">
			<a href="<?php echo $view->escape($faqList->url) ?>"><?php echo $view->escape($faqList->title) ?></a>
		</h2>
		<?php # fin Okatea : affichage du titre ?>

		<?php # début Okatea : affichage de la réponse ?>
		<div class="question-content">

		<?php 
# début Okatea : si on as PAS accès en lecture à la question
		if (!$faqList->isReadable())
		:
			?>

			<p><?php _e('m_faq_restricted_access') ?></p>

		<?php endif; # début Okatea : si on as PAS accès en lecture à la question ?>


		<?php 
# début Okatea : si on as accès en lecture à la question
		if ($faqList->isReadable())
		:
			?>

			<?php 
# début Okatea : affichage image
			$question_image = $faqList->getFirstImageInfo();
			if (!empty($question_image) && isset($question_image['square_url']))
			:
				?>

			<div class="modal-box">
				<a href="<?php echo $question_image['img_url']?>"
					title="<?php echo $view->escapeHtmlAttr($faqList->title) ?>"
					class="modal"><img src="<?php echo $question_image['square_url']?>"
					<?php echo $question_image['square_attr']?>
					alt="<?php echo $view->escapeHtmlAttr((isset($question_image['alt']) ? $question_image['alt'] : $faqList->title)) ?>" /></a>
			</div>
			<?php endif; # fin Okatea : affichage image ?>


			<?php 
# début Okatea : affichage réponse tronqué
			if ($okt->faq->config->public_truncat_char > 0)
			:
				?>

			<p><?php echo $faqList->content ?>… <a
					href="<?php echo $view->escape($faqList->url) ?>"
					title="<?php echo $view->escapeHtmlAttr(sprintf(__('m_faq_read_more_of_%s'), $faqList->title)) ?>"
					rel="nofollow"><?php _e('m_faq_read_more') ?></a>
			</p>

			<?php endif; # fin Okatea : affichage texte tronqué ?>


			<?php 
# début Okatea : affichage texte pas tronqué
			if (!$okt->faq->config->public_truncat_char)
			:
				?>

			<?php echo $faqList->content?>

			<?php endif; # fin Okatea : affichage texte pas tronqué ?>


		<?php endif; # début Okatea : si on as accès en lecture à la question ?>

		</div>
		<!-- .question-content -->
		<?php # fin Okatea : affichage du contenu ?>

	</div>
	<!-- .question -->
	<?php # fin Okatea : affichage d'une question ?>

	<?php endwhile; # fin Okatea : boucle sur la liste des questions ?>

</div>
<!-- #questions_list -->

<?php 
# début Okatea : affichage pagination
	if ($faqList->numPages > 1)
	:
		?>

<ul class="pagination">
	<?php echo $faqList->pager->getLinks(); ?>
</ul>


	<?php endif;
	# fin Okatea : affichage pagination 	?>

<p class="scrollTop-wrapper">
	<a href="#" class="scrollTop"><?php _e('c_c_action_Go_top') ?></a>
</p>

<?php endif; ?>
