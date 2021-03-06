<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use forxer\Gravatar\Image as GravatarImage;
use Okatea\Tao\L10n\DateTime;
use Okatea\Tao\Forms\Statics\FormElements as form;
use Okatea\Tao\Users\Groups;

$view->extend('Layout');

# titre de la page
$okt->page->addGlobalTitle(__('c_a_menu_users'), $view->generateAdminUrl('Users_index'));

# Buttons set
$okt->page->setButtonset('users', array(
	'id' => 'users-buttonset',
	'type' => '', #  buttonset-single | buttonset-multi | ''
	'buttons' => array(
		array(
			'permission' => true,
			'title' => __('c_a_users_Add_user'),
			'url' => $view->generateAdminUrl('Users_add'),
			'ui-icon' => 'plusthick'
		),
		array(
			'permission' => true,
			'title' => __('c_c_display_filters'),
			'url' => '#',
			'ui-icon' => 'search',
			'active' => $filters->params->show_filters,
			'id' => 'filter-control',
			'class' => 'filter-control button-toggleable'
		)
	)
));

# Display a UI dialog box
$okt->page->js->addReady("
	$('#filters-form').dialog({
		title:'" . $view->escapeJs(__('c_a_users_users_display_filters')) . "',
		autoOpen: false,
		modal: true,
		width: 500,
		height: 300
	});

	$('.filter-control').click(function() {
		$('#filters-form').dialog('open');
	})
");

$okt->page->css->addCss('
.avatar {
	float: left;
	margin: 0 1em 1em 0;

	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	border-radius: 5px;
}
');

# Avatars
if ($okt['config']->users['gravatar']['enabled'])
{
	$gravatarImage = (new GravatarImage())->setDefaultImage($okt['config']->users['gravatar']['default_image'])
		->setMaxRating($okt['config']->users['gravatar']['rating'])
		->setSize(60);
}

?>

<div class="double-buttonset">
	<div class="buttonsetA">
		<?php echo $okt->page->getButtonSet('users'); ?>
	</div>
	<div class="buttonsetB">
		<?php

		echo $view->render('Common/Search', array(
			'sFormAction' => $view->generateAdminUrl('Users_index'),
			'sSearchLabel' => __('c_a_users_list_Search'),
			'sSearch' => $sSearch,
			'sAutocompleteSrc' => $view->generateAdminUrl('Users_index') . '?json=1'
		));
		?>
	</div>
</div>

<?php # formulaire des filtres ?>
<form action="<?php echo $view->generateAdminUrl('Users_index') ?>"
	method="get" id="filters-form">
	<fieldset>
		<legend><?php _e('c_a_users_users_display_filters')?></legend>

		<?php echo $filters->getFiltersFields('<div class="three-cols">%s</div>'); ?>

		<p>
			<input type="submit"
				name="<?php echo $filters->getFilterSubmitName() ?>"
				value="<?php _e('c_c_action_Display') ?>" /> <a
				href="<?php echo $view->generateAdminUrl('Users_index') ?>?init_filters=1"><?php _e('c_c_reset_filters')?></a>
		</p>
	</fieldset>
</form>

<?php if (empty($aUsers)) : ?>

	<?php if (!empty($sSearch)) : ?>
<p><?php _e('c_a_users_no_searched_user') ?></p>

<?php elseif ($filters->params->show_filters) : ?>
<p><?php _e('c_a_users_no_filtered_user')?>
	<a href="#" class="filter-control"><?php _e('c_a_users_users_edit_filters') ?></a>
	- <a href="<?php echo $view->generateAdminUrl('Users_index') ?>?init_filters=1"><?php _e('c_c_reset_filters') ?></a>
</p>
<?php else : ?>
<p><?php _e('c_a_users_no_user') ?></p>

<?php endif; ?>

<?php endif; ?>

<?php if (!empty($aUsers)) : ?>
<form action="<?php echo $view->generateAdminUrl('Users_index') ?>"
	method="post" id="users-list">
	<table class="common">
		<caption><?php _e('c_a_users_users_list')?></caption>
		<thead>
			<tr>
				<th scope="col" colspan="2"><?php _e('c_c_user_Username')?></th>
				<th scope="col"><?php _e('c_c_Email')?></th>
				<th scope="col"><?php _e('c_c_Group')?></th>
				<th scope="col"><?php _e('c_a_users_last_connection')?></th>
				<th scope="col"><?php _e('c_a_users_registration_date')?></th>
				<th scope="col" class="small"><?php _e('c_c_Actions')?></th>
			</tr>
		</thead>
		<tbody>
		<?php $iCountLine = 0;
		foreach ($aUsers as $aUser) :

			$sTdClass = $iCountLine % 2 == 0 ? 'even' : 'odd';
			$iCountLine ++;

			if (!$aUser['status']) {
				$sTdClass .= ' disabled';
			}
		?>
		<tr>
				<td class="<?php echo $sTdClass ?> small"><?php echo form::checkbox(array('users[]'), $aUser['id']) ?></td>
				<th scope="row" class="<?php echo $sTdClass ?> fake-td">

					<p class="title">
						<a href="<?php echo $view->generateAdminUrl('Users_edit', array('user_id' => $aUser['id'])) ?>">
						<?php if ($okt['config']->users['gravatar']['enabled']) : ?><img
							src="<?php echo $gravatarImage->getUrl($aUser['email']) ?>"
							width="<?php echo $gravatarImage->getSize() ?>"
							height="<?php echo $gravatarImage->getSize()?>" alt=""
							class="avatar">
						<?php endif; ?>
						<?php echo $view->escape($aUser['username']) ?></a>
					</p>

					<p><?php echo $view->escape($aUser['firstname'].' '.$aUser['lastname'])?>
				<?php if (!empty($aUser['displayname'])) : ?> - <?php echo $view->escape($aUser['displayname']) ?><?php endif ?></p>
				</th>
				<td class="<?php echo $sTdClass ?>">
					<p>
						<a href="mailto:<?php echo $aUser['email'] ?>"><?php echo $aUser['email'] ?></a>
					</p>
				</td>
				<td class="<?php echo $sTdClass ?>">
					<p><?php if ($aUser['group_id'] == Groups::UNVERIFIED) : ?>
						<em><?php _e('c_a_users_wait_of_validation') ?></em>
					<?php elseif (!empty($aGroups[$aUser['group_id']])) : ?>
						<?php echo $view->escape($aGroups[$aUser['group_id']]) ?>
					<?php endif ?></p>
				</td>
				<td class="<?php echo $sTdClass ?>">
					<p><?php echo DateTime::full($aUser['last_visit']) ?></p>
				</td>
				<td class="<?php echo $sTdClass ?>">
					<p><?php echo DateTime::full($aUser['registered']) ?></p>
				</td>
				<td class="<?php echo $sTdClass ?> nowrap">
					<ul class="actions">
						<li>
						<?php if ($aUser['group_id'] == Groups::UNVERIFIED && $okt['visitor']->checkPerm('users_edit')) : ?>
							<a href="<?php echo $view->generateAdminUrl('Users_edit', array('user_id' => $aUser['id'])).'?validate=1'; ?>"
							title="<?php echo $view->escapeHtmlAttr(sprintf(__('c_a_users_validate_the_user_%s'), $aUser['username'])); ?>"
							class="icon time"><?php _e('c_a_users_validate_the_user')?></a>
						<?php else : ?>
							<span class="icon user"></span><?php _e('c_a_users_validated_user')?>
						<?php endif; ?>
					</li>

					<li>
					<?php if ($aUser['status']) : ?>
						<a href="<?php echo $view->generateAdminUrl('Users_index') ?>?disable=<?php echo $aUser['id'] ?>"
						class="icon tick"><?php _e('c_c_status_Active')?></a>
					<?php else : ?>
						<a href="<?php echo $view->generateAdminUrl('Users_index') ?>?enable=<?php echo $aUser['id'] ?>"
						class="icon cross"><?php _e('c_c_status_Inactive')?></a>
					<?php endif; ?>
					</li>

					<?php if ($okt['visitor']->checkPerm('users_edit')) : ?>
					<li><a href="<?php echo $view->generateAdminUrl('Users_edit', array('user_id' => $aUser['id'])) ?>"
						title="<?php echo $view->escapeHtmlAttr(sprintf(__('c_a_users_edit_the_user_%s'), $aUser['username'])); ?>"
						class="icon pencil"><?php _e('c_c_action_Edit')?></a></li>
					<?php endif; ?>

					<?php if ($okt['visitor']->checkPerm('users_delete')) : ?>
					<li><a href="<?php echo $view->generateAdminUrl('Users_index') ?>?delete=<?php echo $aUser['id'] ?>"
						onclick="return window.confirm('<?php echo $view->escapeJs(__('c_a_users_confirm_user_deletion')) ?>')"
						title="<?php echo $view->escapeHtmlAttr(sprintf(__('c_a_users_delete_the_user_%s'), $aUser['username'])); ?>"
						class="icon delete"><?php _e('c_c_action_Delete')?></a></li>
					<?php endif; ?>

				</ul>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php

	echo $view->render('Common/FormListBatches', array(
		'sFormId' => 'users-list',
		'sActionsLabel' => __('c_a_users_list_users_action'),
		'aActionsChoices' => $aActionsChoices
	));
	?>
</form>


<?php if ($iNumPages > 1) : ?>
<ul class="pagination"><?php echo $pager->getLinks(); ?></ul>
<?php endif; ?>

<?php endif; ?>
