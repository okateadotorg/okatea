<?php

use Okatea\Tao\Forms\Statics\FormElements as form;
use Okatea\Tao\Users\Authentification;

$view->extend('layout');

# module title tag
$okt->page->addTitleTag($okt->News->getTitle());

# module start breadcrumb
$okt->page->addAriane($okt->News->getName(), $view->generateUrl('News_index'));


# Autocomplétion du formulaire de recherche
$okt->page->js->addReady('
	$("#search").autocomplete({
		source: "'.$view->generateUrl('News_index').'?json=1",
		minLength: 2
	});
');

if (!empty($sSearch))
{
	$okt->page->js->addFile($okt->options->public_url.'/plugins/putCursorAtEnd/jquery.putCursorAtEnd.min.js');
	$okt->page->js->addReady('
		$("#search").putCursorAtEnd();
	');
}

# button set
$okt->page->setButtonset('newsBtSt',array(
	'id' => 'news-buttonset',
	'type' => '', #  buttonset-single | buttonset-multi | ''
	'buttons' => array(
		array(
			'permission' => ($okt->page->action !== 'add'),
			'title' => __('m_news_menu_add_post'),
			'url' => $view->generateUrl('News_post_add'),
			'ui-icon' => 'plusthick'
		)
	)
));


# Ajout de boutons
$okt->page->addButton('newsBtSt',array(
	'permission' 	=> true,
	'title' 		=> __('c_c_display_filters'),
	'url' 			=> '#',
	'ui-icon' 		=> 'search',
	'active' 		=> $okt->News->filters->params->show_filters,
	'id'			=> 'filter-control',
	'class'			=> 'button-toggleable'
));


# Bouton vers le module côté public
$okt->page->addButton('newsBtSt',array(
	'permission' 	=> true,
	'title' 		=> __('c_c_action_show'),
	'url' 			=> $okt->router->generateFromAdmin('newsList'),
	'ui-icon' 		=> 'extlink'
));


# Filters control
if ($okt->News->config->admin_filters_style == 'slide')
{
	# Slide down
	$okt->page->filterControl($okt->News->filters->params->show_filters);
}
elseif ($okt->News->config->admin_filters_style == 'dialog')
{
	# Display a UI dialog box
	$okt->page->js->addReady("
		$('#filters-form').dialog({
			title:'".$view->escapeJs(__('c_c_display_filters'))."',
			autoOpen: false,
			modal: true,
			width: 500,
			height: 300
		});

		$('#filter-control').click(function() {
			$('#filters-form').dialog('open');
		})
	");
}


# Checkboxes helper
$okt->page->checkboxHelper('posts-list', 'checkboxHelper');


# Un peu de CSS
$okt->page->css->addCss('
.ui-autocomplete {
	max-height: 150px;
	overflow-y: auto;
	overflow-x: hidden;
}
.search_form p {
	margin: 0;
}
#search {
	background: transparent url('.$okt->options->public_url.'/img/admin/preview.png) no-repeat center right;
}
#post-count {
	margin-top: 0;
}
');

?>

<div class="double-buttonset">
	<div class="buttonsetA">
		<?php echo $okt->page->getButtonSet('newsBtSt'); ?>
	</div>
	<div class="buttonsetB">
		<form action="<?php echo $view->generateUrl('News_index') ?>" method="get" id="search_form" class="search_form">
			<p><label for="search"><?php _e('m_news_list_Search') ?></label>
			<?php echo form::text('search', 20, 255, $view->escape((isset($sSearch) ? $sSearch : ''))); ?>

			<input type="submit" name="search_submit" id="search_submit" value="ok" /></p>
		</form>
	</div>
</div>

<?php # formulaire des filtres ?>
<form action="<?php echo $view->generateUrl('News_index') ?>" method="get" id="filters-form">
	<fieldset>
		<legend><?php _e('m_news_display_filters') ?></legend>

		<?php echo $okt->News->filters->getFiltersFields('<div class="three-cols">%s</div>'); ?>

		<p><input type="submit" name="<?php echo $okt->News->filters->getFilterSubmitName() ?>" value="<?php _e('c_c_action_display') ?>" />
		<a href="<?php echo $view->generateUrl('News_index') ?>?init_filters=1"><?php _e('c_c_reset_filters') ?></a></p>

	</fieldset>
</form>

<div id="postsList">

<?php # Affichage du compte d'articles
if ($iNumFilteredPosts == 0) : ?>
<p id="post-count"><?php _e('m_news_list_no_post') ?></p>
<?php elseif ($iNumFilteredPosts == 1) : ?>
<p id="post-count"><?php _e('m_news_list_one_post') ?></p>
<?php else : ?>
	<?php if ($iNumPages > 1) : ?>
		<p id="post-count"><?php printf(__('m_news_list_%s_posts_on_%s_pages'), $iNumFilteredPosts, $iNumPages) ?></p>
	<?php else : ?>
		<p id="post-count"><?php printf(__('m_news_list_%s_posts'), $iNumFilteredPosts) ?></p>
	<?php endif; ?>
<?php endif; ?>

<?php # Si on as des articles à afficher
if (!$rsPosts->isEmpty()) : ?>

<form action="<?php echo $view->generateUrl('News_index') ?>" method="post" id="posts-list">

	<table class="common">
		<caption><?php _e('m_news_list_table_caption') ?></caption>
		<thead><tr>
			<th scope="col"><?php _e('m_news_list_table_th_title') ?></th>
			<?php if ($okt->News->config->categories['enable']) : ?>
			<th scope="col"><?php _e('m_news_list_table_th_category') ?></th>
			<?php endif; ?>
			<?php if ($okt->News->canUsePerms()) : ?>
			<th scope="col"><?php _e('m_news_list_table_th_access') ?></th>
			<?php endif; ?>
			<th scope="col"><?php _e('m_news_list_table_th_dates') ?></th>
			<th scope="col"><?php _e('m_news_list_table_th_author') ?></th>
			<th scope="col"><?php _e('c_c_Actions') ?></th>
		</tr></thead>
		<tbody>
		<?php while ($rsPosts->fetch()) : ?>
		<tr class="<?php echo $rsPosts->odd_even ?>">
			<th class="<?php echo $rsPosts->odd_even ?> fake-td">
				<?php echo form::checkbox(array('posts[]'),$rsPosts->id) ?>
				<?php if ($rsPosts->selected) : ?><span class="icon star"></span><?php endif; ?>
				<?php if ($rsPosts->active == 2) : ?><span class="icon time"></span><?php endif; ?>
				<?php if ($rsPosts->active == 3) : ?><span class="icon clock"></span><?php endif; ?>
				<a href="<?php echo $view->generateUrl('News_post', array('post_id' => $rsPosts->id)) ?>"><?php
				echo $view->escape($rsPosts->title) ?></a>
			</th>

			<?php if ($okt->News->config->categories['enable']) : ?>
			<td class="<?php echo $rsPosts->odd_even ?>"><?php echo $view->escape($rsPosts->category_title) ?></td>
			<?php endif; ?>

			<?php # droits d'accès
			if ($okt->News->canUsePerms()) :

				$aGroupsAccess = array();
				$aPerms = $okt->News->getPostPermissions($rsPosts->id);
				foreach ($aPerms as $iPerm) {
					$aGroupsAccess[] = $view->escape($aGroups[$iPerm]);
				}
				unset($aPerms);
			?>
			<td class="<?php echo $rsPosts->odd_even ?>">
				<?php if (!empty($aGroupsAccess)) : ?>
				<ul>
					<li><?php echo implode('</li><li>',$aGroupsAccess) ?></li>
				</ul>
				<?php endif; ?>
			</td>
			<?php endif; ?>

			<td class="<?php echo $rsPosts->odd_even ?>">
			<?php if ($rsPosts->active == 3) : ?>
				<p><?php printf(__('m_news_list_sheduled_%s'), dt::dt2str(__('%Y-%m-%d %H:%M'),$rsPosts->created_at)) ?>
			<?php else : ?>
				<p><?php printf(($rsPosts->active == 2 ? __('m_news_list_added_%s') : __('m_news_list_published_%s')), dt::dt2str(__('%Y-%m-%d %H:%M'),$rsPosts->created_at)) ?>
				<?php if ($rsPosts->updated_at > $rsPosts->created_at) : ?>
				<span class="note"><?php printf(__('m_news_list_edited_%s'), dt::dt2str(__('%Y-%m-%d %H:%M'),$rsPosts->updated_at)) ?></span>
				<?php endif; ?>
				</p>
			<?php endif; ?>
			</td>

			<td class="<?php echo $rsPosts->odd_even ?>">
				<?php echo $view->escape($rsPosts->getPostAuthor()) ?>
			</td>

			<td class="<?php echo $rsPosts->odd_even ?> small nowrap">
				<ul class="actions">
				<?php if ($rsPosts->active == 0) : ?>
					<li><a href="<?php echo $view->generateUrl('News_index') ?>?switch_status=<?php echo $rsPosts->id ?>"
					title="<?php echo $view->escapeHtmlAttr(sprintf(__('m_news_list_switch_visibility_%s'), $rsPosts->title)) ?>"
					class="icon cross"><?php _e('c_c_action_Hidden') ?></a></li>

				<?php elseif ($rsPosts->active == 1) : ?>
					<li><a href="<?php echo $view->generateUrl('News_index') ?>?switch_status=<?php echo $rsPosts->id ?>"
					title="<?php echo $view->escapeHtmlAttr(sprintf(__('m_news_list_switch_visibility_%s'), $rsPosts->title)) ?>"
					class="icon tick"><?php _e('c_c_action_Visible') ?></a></li>

				<?php elseif ($rsPosts->active == 2) : ?>
					<?php if ($rsPosts->isPublishable()) : ?>
					<li><a href="<?php echo $view->generateUrl('News_index') ?>?publish=<?php echo $rsPosts->id ?>"
					title="<?php echo $view->escapeHtmlAttr(sprintf(__('m_news_list_publish_%s'), $rsPosts->title)) ?>"
					class="icon time"><?php _e('c_c_action_Publish') ?></a></li>
					<?php else : ?>
					<li><span class="icon time"></span> <?php _e('m_news_list_awaiting_validation') ?></li>
					<?php endif; ?>

				<?php elseif ($rsPosts->active == 3) : ?>
					<li><span class="icon clock"></span> <?php _e('m_news_list_delayed_publication') ?></li>
				<?php endif; ?>

				<?php if ($rsPosts->selected) : ?>
					<li><a href="<?php echo $view->generateUrl('News_index') ?>?deselect=<?php echo $rsPosts->id ?>"
					title="<?php echo $view->escapeHtmlAttr(sprintf(__('m_news_list_deselect_%s'), $rsPosts->title))?>"
					class="icon award_star_delete"><?php _e('c_c_action_Deselect')?></a></li>
				<?php else : ?>
					<li><a href="<?php echo $view->generateUrl('News_index') ?>?select=<?php echo $rsPosts->id ?>"
					title="<?php echo $view->escapeHtmlAttr(sprintf(__('m_news_list_select_%s'), $rsPosts->title)) ?>"
					class="icon award_star_add"><?php _e('c_c_action_Select')?></a></li>
				<?php endif; ?>

				<?php if ($rsPosts->isEditable()) : ?>
					<li><a href="<?php echo $view->generateUrl('News_post', array('post_id' => $rsPosts->id)) ?>"
					title="<?php echo $view->escapeHtmlAttr(sprintf(__('m_news_list_edit_%s'), $rsPosts->title)) ?>"
					class="icon pencil"><?php _e('c_c_action_Edit') ?></a></li>
				<?php else : ?>
					<li><a href="<?php echo $view->generateUrl('News_post', array('post_id' => $rsPosts->id)) ?>"
					title="<?php echo $view->escapeHtmlAttr(sprintf(__('m_news_list_show_%s'), $rsPosts->title)) ?>"
					class="icon application_form"><?php _e('c_c_action_Show') ?></a></li>
				<?php endif; ?>

				<?php if ($rsPosts->isDeletable()) : ?>
					<li><a href="<?php echo $view->generateUrl('News_index') ?>?delete=<?php echo $rsPosts->id ?>"
					onclick="return window.confirm('<?php echo $view->escapeJs(__('m_news_list_post_delete_confirm')) ?>')"
					title="<?php echo $view->escapeHtmlAttr(sprintf(__('m_news_list_delete_%s'), $rsPosts->title)) ?>"
					class="icon delete"><?php _e('c_c_action_Delete') ?></a></li>
				<?php endif; ?>
				</ul>
			</td>
		</tr>
		<?php endwhile; ?>
		</tbody>
	</table>

	<div class="two-cols">
		<div class="col">
			<p id="checkboxHelper"></p>
		</div>
		<div class="col right"><p><?php _e('m_news_list_posts_action')?>
		<?php echo form::select('actions', $aActionsChoices) ?>
		<?php echo $okt->page->formtoken(); ?>
		<input type="submit" value="<?php echo 'ok'; ?>" /></p></div>
	</div>
</form>

<?php if ($iNumPages > 1) : ?>
<ul class="pagination"><?php echo $oPager->getLinks(); ?></ul>
<?php endif; ?>

<?php endif; ?>

</div><!-- #postsList -->