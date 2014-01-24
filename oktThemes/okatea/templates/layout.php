<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

# début Okatea : ce template étend le template principal "main"
$view->extend('main');
# fin Okatea : ce template étend le template principal "main" ?>


<div id="global">
	<header>
		<div id="head">
			<div class="title"><a href="<?php echo $view->escapeHtmlAttr($okt->page->getBaseUrl()) ?>"><?php echo $view->escape($okt->page->getSiteTitle()) ?></a></div>
			<div class="description"><?php echo $view->escape($okt->page->getSiteDescription()) ?></div>
		</div><!-- #head -->

		<ul id="access-links">
			<li><a href="#main"><?php _e('c_c_go_to_content') ?></a></li>
			<li><a href="#main-navigation"><?php _e('c_c_go_to_menu') ?></a></li>
		</ul>

		<?php # début Okatea : affichage du switcher de langues
		if (!$okt->languages->unique) : ?>
		<ul id="lang-switcher">
			<?php foreach ($okt->languages->list as $aLanguage) : ?>
			<li id="lang_switcher_<?php echo $view->escape($aLanguage['code']) ?>"><a href="<?php echo $view->escape($aLanguage['code']) ?>" title="<?php echo $view->escape($aLanguage['title']) ?>"><img
			src="<?php echo $okt->options->public_url.'/img/flags/'.$aLanguage['img'] ?>" alt="<?php echo $view->escape($aLanguage['title']) ?>" /></a></li>
			<?php endforeach; ?>
		</ul><!-- #lang-switcher -->
		<?php endif; # fin Okatea : affichage du switcher de langues ?>
	</header>

	<div id="page">

		<nav id="main-navigation">
			<?php echo $okt->navigation->render('menu') ?>
		</nav>

		<div id="navigation-helpers">
			<?php # début Okatea : titre de la page (graphic title)
			if ($okt->page->hasTitle()) : ?>
			<div id="graphic-title"><?php echo $view->escape($okt->page->getTitle()); ?></div>
			<?php endif; # fin Okatea : titre de la page (graphic title) ?>

			<?php # début Okatea : affichage du fil d'ariane
			$okt->page->breadcrumb->setHtmlSeparator(' &rsaquo; ');
			$okt->page->breadcrumb->display('<div id="breadcrumb"><em>'.__('c_c_user_you_are_here').'</em> %s</div>');
			# fin Okatea : affichage du fil d'ariane ?>
		</div><!-- #navigation-helpers -->

		<div id="main">

			<div id="content">
				<?php # début Okatea : titre SEO de la page (h1) ou titre si pas de titre SEO
				if ($okt->page->hasTitleSeo()) : ?>
				<h1><?php echo $view->escape($okt->page->getTitleSeo()); ?></h1>
				<?php elseif ($okt->page->hasTitle()) : ?>
				<h1><?php echo $view->escape($okt->page->getTitle()); ?></h1>
				<?php endif; # fin Okatea : titre SEO de la page (h1) ou titre si pas de titre SEO ?>

				<?php # début Okatea : affichage du contenu de la page
				$view['slots']->output('_content');
				# fin Okatea : affichage du contenu de la page ?>
			</div><!-- #content -->

			<div id="sidebar">


				<?php # début Okatea : si le module okatea.org est présent, affichage de l'encart téléchargement
				if ($okt->modules->moduleExists('okatea_dot_org')) : ?>
					<?php echo $view->render($okt->okatea_dot_org->getDownloadInsertTplPath()); ?>
				<?php endif; # fin Okatea : si le module okatea.org est présent, affichage de l'encart téléchargement ?>

				<?php # début Okatea : si le module news est présent, affichage de l'encart
				if ($okt->modules->moduleExists('News')) : ?>
				<div id="latest-news">
					<h2><?php _e('Latest news') ?></h2>
					<?php echo $view->render($okt->News->getInsertTplPath()); ?>
				</div>
				<?php endif; # fin Okatea : si le module news est présent, affichage de l'encart ?>

			</div><!-- #sidebar -->

		</div><!-- #main -->

	</div><!-- #page -->

	<footer>
		<div id="bottom">
			<p id="bottom-line"><?php printf(__('c_c_proudly_propulsed_%s'), 'Okatea') ?></p>
		</div>
	</footer>
</div><!-- #global -->