<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/**
 * Outil de backup (partie initialisation)
 *
 * @addtogroup Okatea
 *
 */


# Accès direct interdit
if (!defined('ON_CONFIGURATION_MODULE')) die;


# base des nom de fichier de backup
$sBackupFilenameBase = 'okatea-backup';
$sDbBackupFilenameBase = 'db-backup';


# liste des fichiers de backup
$aBackupFiles = $aDbBackupFiles = array();
foreach (new DirectoryIterator(OKT_ROOT_PATH) as $oFileInfo)
{
	if ($oFileInfo->isDot() || !$oFileInfo->isFile()) {
		continue;
	}

	# files backups
	if (preg_match('#(^|/)'.preg_quote($sBackupFilenameBase,'#').'(.*?).zip$#',$oFileInfo->getFilename()))
	{
		$aBackupFiles[] = $oFileInfo->getFilename();
	}
	# db backups
	elseif (preg_match('#(^|/)'.preg_quote($sDbBackupFilenameBase,'#').'(.*?).sql$#',$oFileInfo->getFilename()))
	{
		$aDbBackupFiles[] = $oFileInfo->getFilename();
	}
}

natsort($aBackupFiles);
natsort($aDbBackupFiles);


# messages de confirmation
$okt->page->messages->success('bakcup_done',__('c_a_tools_backup_done'));
$okt->page->messages->success('backup_file_deleted',__('c_a_tools_backup_deleted'));


# loader
$okt->page->loader('.lazy-load');
