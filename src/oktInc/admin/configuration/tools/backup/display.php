<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/**
 * Outil gestion du cache (partie affichage)
 *
 * @addtogroup Okatea
 *
 */


# Accès direct interdit
if (!defined('ON_CONFIGURATION_MODULE')) die;

?>

<h3><?php _e('c_a_tools_backup_title') ?></h3>

<div class="two-cols">
	<div class="col">
		<h4><?php _e('c_a_tools_backup_website_files') ?></h4>

		<p><a href="configuration.php?action=tools&amp;make_backup=1" class="icon page_save lazy-load"><?php _e('c_a_tools_backup_perform') ?></a></p>

		<?php if (empty($aBackupFiles)) : ?>
		<p><?php _e('c_a_tools_backup_no_file') ?></p>
		<?php else : ?>
		<ul>
		<?php foreach ($aBackupFiles as $sBackupFile) : ?>
			<li><a href="configuration.php?action=tools&amp;dl_backup=<?php echo $sBackupFile ?>"><?php echo $sBackupFile ?></a> -
				[<a href="configuration.php?action=tools&amp;delete_backup_file=<?php echo html::escapeHTML($sBackupFile) ?>"
				onclick="return window.confirm('<?php echo html::escapeJS(__('c_a_tools_backup_confirm_delete')) ?>')"
				title="<?php printf(__('c_c_action_Delete_%s'), html::escapeHTML($sBackupFile)) ?>"><?php _e('c_c_action_delete') ?></a>]</li>
		<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>

	<div class="col">
		<h4><?php _e('c_a_tools_backup_database') ?></h4>

		<p><a href="configuration.php?action=tools&amp;make_db_backup=1" class="icon database_save lazy-load"><?php _e('c_a_tools_backup_perform') ?></a></p>

		<?php if (empty($aDbBackupFiles)) : ?>
		<p><?php _e('c_a_tools_backup_no_file') ?></p>
		<?php else : ?>
		<ul>
		<?php foreach ($aDbBackupFiles as $sBackupFile) : ?>
			<li><a href="configuration.php?action=tools&amp;dl_backup=<?php echo $sBackupFile ?>"><?php echo $sBackupFile ?></a> -
				[<a href="configuration.php?action=tools&amp;delete_backup_file=<?php echo html::escapeHTML($sBackupFile) ?>"
				onclick="return window.confirm('<?php echo html::escapeJS(__('c_a_tools_backup_confirm_delete')) ?>')"
				title="<?php printf(__('c_c_action_Delete_%s'), html::escapeHTML($sBackupFile)) ?>"><?php _e('c_c_action_delete') ?></a>]</li>
		<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</div><!-- .two-cols -->