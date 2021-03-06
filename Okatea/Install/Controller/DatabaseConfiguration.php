<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Okatea\Install\Controller;

use ArrayObject;
use Okatea\Install\Controller;
use Okatea\Tao\Database\ConfigLayers\Drivers;
use Okatea\Tao\Database\ConfigLayers\Validator;
use Okatea\Tao\Html\Checklister;

class DatabaseConfiguration extends Controller
{
	protected $aPageData;

	public function page()
	{
		$drivers = new Drivers();

		$this->aPageData = new ArrayObject([
			'drivers' 		=> $drivers,
			'checklist' 	=> null,
			'values' => [
				'driver' 	=> 'pdo_mysql',
				'env' 		=> $this->okt['env'],
				'prefix' 	=> 'okt_',
				'config' 	=> [
					'prod' 		=> [],
					'dev' 		=> []
				]
			]
		]);

		# -- CORE TRIGGER : installDatabaseConfigurationInit
		$this->okt['triggers']->callTrigger('installDatabaseConfigurationInit', $this->aPageData);

		if ($this->okt['request']->request->has('sended'))
		{
			$this->aPageData['checklist'] = new Checklister();

			$this->aPageData['values'] = [
				'driver' 	=> $this->okt['request']->request->get('driver'),
				'env' 		=> $this->okt['request']->request->get('connect'),
				'prefix' 	=> $this->okt['request']->request->get('prefix')
			];

			if (empty($this->aPageData['values']['driver'])) {
				$this->okt['instantMessages']->error(__('i_db_conf_db_error_must_driver'));
			}
			elseif (!$drivers->isSupported($this->aPageData['values']['driver'])) {
				$this->okt['instantMessages']->error(__('i_db_conf_db_error_unsupported_driver'));
			}

			if ($this->aPageData['values']['env'] != 'dev' && $this->aPageData['values']['env'] != 'prod') {
				$this->aPageData['values']['env'] == 'dev';
			}

			if (empty($this->aPageData['values']['prefix'])) {
				$this->okt['instantMessages']->error(__('i_db_conf_db_error_must_prefix'));
			}
			elseif (!preg_match('/^[a-z_]+$/', $this->aPageData['values']['prefix'])) {
				$this->okt['instantMessages']->error(__('i_db_conf_db_error_prefix_form'));
			}

			if ($this->aPageData['values']['env'] == 'prod')
			{
				$validator = (new Validator($this->okt))->validate(getDriver($sDriver), $aData);

				if (empty($this->aPageData['values']['prod']['host'])) {
					$this->okt['instantMessages']->error(__('i_db_conf_db_error_prod_must_host'));
				}

				if (empty($this->aPageData['values']['prod']['name'])) {
					$this->okt['instantMessages']->error(__('i_db_conf_db_error_prod_must_name'));
				}

				if (empty($this->aPageData['values']['prod']['user'])) {
					$this->okt['instantMessages']->error(__('i_db_conf_db_error_prod_must_username'));
				}
			}
			else
			{
				if (empty($this->aPageData['values']['dev']['host'])) {
					$this->okt['instantMessages']->error(__('i_db_conf_db_error_dev_must_host'));
				}

				if (empty($this->aPageData['values']['dev']['name'])) {
					$this->okt['instantMessages']->error(__('i_db_conf_db_error_dev_must_name'));
				}

				if (empty($this->aPageData['values']['dev']['user'])) {
					$this->okt['instantMessages']->error(__('i_db_conf_db_error_dev_must_username'));
				}
			}

			$aParamsToTest = $this->aPageData['values'][$this->aPageData['values']['env']];

			# Tentative de connexion à la base de données
			if (!$this->okt['flashMessages']->hasError())
			{
				$con_id = mysqli_connect($aParamsToTest['host'], $aParamsToTest['user'], $aParamsToTest['password']);

				if (!$con_id)
				{
					$this->okt['instantMessages']->error('MySQL: ' . mysqli_connect_errno() . ' ' . mysqli_connect_error());
				}
				else
				{
					$result = mysqli_query($con_id, 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = \'' . mysqli_real_escape_string($con_id, $aParamsToTest['name']) . '\'');

					if (mysqli_num_rows($result) < 1)
					{
						$this->aPageData['checklist']->addItem(
							'create_database',
							mysqli_query($con_id, 'CREATE DATABASE IF NOT EXISTS `' . $aParamsToTest['name'] . '`'),
							__('i_db_conf_create_db_ok'),
							__('i_db_conf_create_db_ko')
						);
					}

					$db = mysqli_select_db($con_id, $aParamsToTest['name']);

					if (!$db) {
						$this->okt['instantMessages']->error('MySQL: ' . mysqli_errno($con_id) . ' ' . mysqli_error($con_id));
					}
					else {
						mysqli_close($con_id);
					}
				}
			}

			# Nouvelle tentative de connexion à la base de données en utilisant la class interne
			if (!$this->okt['flashMessages']->hasError())
			{
				$db = new MySqli($aParamsToTest['user'], $aParamsToTest['password'], $aParamsToTest['host'], $aParamsToTest['name'], $aParamsToTest['prefix']);

				if ($db->hasError()) {
					$this->okt['instantMessages']->error('Unable to connect to database', $db->error());
				}
				else
				{
					# Création du fichier des paramètres de connexion
					$sConnectionFile = $this->okt['config_path'] . '/connection.php';
					$config = file_get_contents($this->okt['config_path'] . '/connection.dist.php');

					$config = str_replace([
						'%%DB_PROD_HOST%%',
						'%%DB_PROD_BASE%%',
						'%%DB_PROD_USER%%',
						'%%DB_PROD_PASS%%',
						'%%DB_PROD_PREFIX%%'
					], $this->aPageData['values']['prod'], $config);

					$config = str_replace([
						'%%DB_DEV_HOST%%',
						'%%DB_DEV_BASE%%',
						'%%DB_DEV_USER%%',
						'%%DB_DEV_PASS%%',
						'%%DB_DEV_PREFIX%%'
					], $this->aPageData['values']['dev'], $config);

					$this->aPageData['checklist']->addItem(
						'connection_file',
						file_put_contents($sConnectionFile, $config),
						__('i_db_conf_connection_file_ok'),
						__('i_db_conf_connection_file_ko')
					);

					# aller, dernière tentative en utilisant le fichier
					if (!file_exists($sConnectionFile))
					{
						$this->okt['instantMessages']->error('Unable to find database connection file.');
					}
					else
					{
						$env = $this->aPageData['values']['env'];
						require $sConnectionFile;

						$db = new MySqli($sDbUser, $sDbPassword, $sDbHost, $sDbName, $sDbPrefix);

						if ($db->hasError())
						{
							$this->okt['instantMessages']->error('Unable to connect to database', $db->error());
						}
						else
						{
							$this->aPageData['checklist']->addItem(
								'connection_attempt',
								file_put_contents($sConnectionFile, $config),
								__('i_db_conf_conn_ok'),
								__('i_db_conf_conn_ko')
							);
						}
					}
				}
			}
		}

		return $this->render('DatabaseConfiguration', [
			'title' 			=> __('i_db_conf_title'),
			'aPageData' 		=> $this->aPageData
		]);
	}
}
