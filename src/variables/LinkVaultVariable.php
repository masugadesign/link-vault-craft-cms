<?php

namespace Masuga\LinkVault\variables;

use Craft;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\db\LinkVaultDownloadQuery;

class LinkVaultVariable
{

	/**
	 * The instance of the LinkVault plugin.
	 * @var LinkVault
	 */
	private $plugin = null;


	public function __construct()
	{
		$this->plugin = LinkVault::getInstance();
	}

	/**
	 * This template variable method outputs a Link Vault download URL based
	 * on a given file path or Asset.
	 * @param mixed $file
	 * @param array $parameters
	 * @return string
	 */
	public function downloadUrl($file, $parameters=array())
	{
		return $this->plugin->general->downloadUrl($file, $parameters);
	}

	/**
	 * This template variable method outputs a Link Vault zip file download URL
	 * based on an array of supplied assets or file paths.
	 * @param array $files
	 * @param string $zipBaseName
	 * @param array $parameters
	 */
	public function zipUrl($files, $zipBaseName=null, $parameters=array())
	{
		return $this->plugin->general->zipUrl($files, $zipBaseName, $parameters);
	}

	/**
	 * This template variable method outputs the total downloads for a given
	 * set of parameters, an instance of an Asset or a file path.
	 * @param mixed $parameter
	 * @return int
	 */
	public function totalDownloads($parameter): int
	{
		return $this->plugin->general->totalDownloads($parameter);
	}

	/**
	 * This template variable method outputs a string representation of a file's
	 * size. The precision parameter represents the number of decimal places that
	 * should be displayed.
	 * @param mixed $parameter
	 * @param integer $precision
	 * @return string
	 */
	public function fileSize($parameter, $precision=2): string
	{
		return $this->plugin->general->fileSize($parameter, $precision);
	}

	/**
	 * This template variable returns an array of Link Vault records based on
	 * the given criteria.
	 * @param array $criteria
	 * @return LinkVaultDownloadQuery
	 */
	public function records($criteria=array()): LinkVaultDownloadQuery
	{
		return $this->plugin->general->records($criteria);
	}

	/**
	 * This template variable returns Link Vualt download record counts based on
	 * a given column name and other criteria.
	 * @param string $columnName
	 * @param array $criteria
	 * @return array
	 */
	public function groupCount($columnName, $criteria=null): array
	{
		return $this->plugin->general->groupCount($columnName, $criteria);
	}

	/**
	 * This template variable returns an array of download records based on
	 * the given criteria.
	 * @param array $criteria
	 * @return LinkVaultDownloadQuery
	 */
	public function downloads($criteria=array()): LinkVaultDownloadQuery
	{
		$criteria['type'] = 'Download';
		return $this->plugin->general->records($criteria);
	}

	/**
	 * This template variable returns an array of leech attempt records based on
	 * the given criteria.
	 * @param array $criteria
	 * @return LinkVaultDownloadQuery
	 */
	public function leechAttempts($criteria=array()): LinkVaultDownloadQuery
	{
		$criteria['type'] = 'Leech Attempt';
		return $this->plugin->general->records($criteria);
	}

	/**
	 * This template variable parses environment variables in a string.
	 * @param string
	 * @return string
	 */
	public function parseEnvironmentString($string): string
	{
		return $this->plugin->files->parseEnvironmentString($string);
	}

}
