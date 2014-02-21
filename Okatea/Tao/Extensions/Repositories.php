<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okatea\Tao\Extensions;

use Okatea\Tao\HttpClient;

class Repositories
{
	/**
	 * Okatea application instance.
	 * @var object Okatea\Tao\Application
	 */
	protected $okt;

	/**
	 * The errors manager instance.
	 * @var object
	 */
	protected $error;

	/**
	 * Cache manager object.
	 * @var object
	 */
	protected $cache;

	/**
	 * Repository cache identifier.
	 * @var string
	 */
	protected $sCacheId;

	public function __construct($okt)
	{
		$this->okt = $okt;
		$this->error = $okt->error;

		$this->cache = $okt->cacheConfig;
	}

	/**
	 * Returns data about repositories of extensions.
	 *
	 * @param array $aRepositories
	 * @return array
	 */
	public function getData(array $aRepositories = array())
	{
		if (!$this->cache->contains($this->sCacheId)) {
			$this->saveCache($aRepositories);
		}

		return $this->cache->fetch($this->sCacheId);
	}

	/**
	 * Records in the cache data about repositories.
	 *
	 * @param array $aRepositories
	 * @return boolean
	 */
	protected function saveCache(array $aRepositories = array())
	{
		return $this->cache->save($this->sCacheId, $this->readData($aRepositories));
	}

	/**
	 * Read data about repositories in the cache.
	 *
	 * @param array $aRepositories
	 * @return array
	 */
	protected function readData($aRepositories)
	{
		$aModulesRepositories = array();

		foreach ($aRepositories as $sRepositoryId => $sRepositoryUrl)
		{
			if (($infos = $this->getRepositoryData($sRepositoryUrl)) !== false) {
				$aModulesRepositories[$sRepositoryId] = $infos;
			}
		}

		return $aModulesRepositories;
	}

	/**
	 * Returns data about a given repository.
	 *
	 * @param array $sRepositoryUrl
	 * @return array
	 */
	protected function getRepositoryData($sRepositoryUrl)
	{
		$sRepositoryUrl = str_replace('%VERSION%', $this->okt->getVersion(), $sRepositoryUrl);

		if (filter_var($sRepositoryUrl, FILTER_VALIDATE_URL) === false) {
			return false;
		}

		$client = new HttpClient();
		$response = $client->get($sRepositoryUrl)->send();

		if ($response->isSuccessful()) {
			return $this->readRepositoryData($response->getBody(true));
		}
		else {
			return false;
		}
	}

	/**
	 * Read XML data about a given repository.
	 *
	 * @param sting $str
	 * @return array
	 */
	protected function readRepositoryData($str)
	{
		try
		{
			$xml = new \SimpleXMLElement($str, LIBXML_NOERROR);

			$return = array();
			foreach ($xml->module as $module)
			{
				if (isset($module['id']))
				{
					$return[(string)$module['id']] = array(
						'id' 		=> (string)$module['id'],
						'name' 		=> (string)$module['name'],
						'version' 	=> (string)$module['version'],
						'href' 		=> (string)$module['href'],
						'checksum' 	=> (string)$module['checksum'],
						'info' 		=> (string)$module['info']
					);
				}
			}

			if (empty($return)) {
				return false;
			}

			return $return;
		}
		catch (Exception $e) {
			throw $e;
		}
	}
}
