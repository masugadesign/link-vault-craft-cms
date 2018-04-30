<?php

namespace Masuga\LinkVault\services;

use Craft;
use craft\awss3\Volume as S3;
use craft\elements\Asset;
use craft\googlecloud\Volume as GoogleCloud;
use craft\helpers\UrlHelper;
use craft\volumes\Local;
use yii\base\Component;
use Masuga\LinkVault\LinkVault;

class FilesService extends Component
{

	/**
	 * An array of MIME types supported by Link Vault.
	 * @var array
	 */
	public $mimes = array();

	/**
	 * The instance of the Link Vault plugin.
	 * @var LinkVault
	 */
	private $plugin = null;

	/**
	 * The class constructer
	 */
	public function __construct()
	{
		$this->plugin = LinkVault::getInstance();
		$this->mimes = require $this->plugin->getBasePath().'/mime_types.php';
	}

	/**
	 * Fetch the MIME type for a given file. If one isn't found, return a sensible default.
	 * @param string $filePath
	 * @return string
	 */
	public function getMIMEType($filePath)
	{
		$extension = pathinfo($filePath, PATHINFO_EXTENSION);
		return isset($this->mimes[$extension]) ? $this->mimes[$extension] : "application/force-download";
	}

	/**
	 * This method serves a file from the server. The file path is assumed to be valid so
	 * all validation should occur before this method is called.
	 * @deprecated Use Yii 2's craft\web\Response::sendFile() instead.
	 * @param string $filePath
	 * @param string $downloadAs
	 */
	public function serveFile($filePath, $downloadAs='')
	{
		// Fetch the file parts.
		$pathParts = pathinfo($filePath);
		// Make sure $downloadAs has a value.
		if ( ! $downloadAs ) {
			$downloadAs = $pathParts['filename'].'.'.$pathParts['extension'];
		}
		// Disable error reporting to prevent "headers already sent" errors.
		$originalErrorReporting = error_reporting();
		//error_reporting(0);
		set_time_limit(0);
		// Serve the file.
		ob_start();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public", false);
		header("Content-Description: File Transfer");
		header("Content-Type: " . $this->mimes[ $pathParts['extension'] ]);
		header("Accept-Ranges: bytes");
		header("Content-Disposition: attachment; filename=\"" . $downloadAs . "\";");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . filesize($filePath));
		flush();
		$fp = fopen($filePath, "r");
		while (!feof($fp)) {
			echo fread($fp, 1024*8);
			ob_flush();
			flush();
		}
		fclose($fp);
		ob_end_flush();
		// Return error reporting preferences to original state.
		error_reporting($originalErrorReporting);
	}

	/**
	 * Get a human-readable file size for a given file path on the server.
	 * @param string $filePath
	 * @param integer $decimals
	 * @return string
	 */
	public function fileSize($filePath, $decimals=2)
	{
		$size = file_exists($filePath) ? filesize($filePath) : 0;
		return $this->fileSizeString($size, $decimals);
	}

	/**
	 * This method attempts to fetch a remote file size based on a URL. If the request
	 * fails, "Unknown" is returned. This request seems to be successful the majority of
	 * the time but an occasional failure occurs.
	 * @param string $url
	 * @param integer $decimals
	 * @return string
	 */
	public function remoteFileSize($url, $decimals=2)
	{
		/*
		$result = null;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1500);
		$data = curl_exec($ch );
		$result = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		curl_close($ch);
		*/
		$head = array_change_key_case(get_headers($url, true));
		$result = isset($head['content-length']) ? $head['content-length'] : null;
		return ( is_numeric($result) && $result > 0 ) ? $this->fileSizeString($result, $decimals) : Craft::t('linkvault', 'Unknown');
	}

	/**
	 * This method converts bytes into a human-readable file size.
	 * @param integer $bytes
	 * @param integer $decimals
	 * @return string
	 */
	public function fileSizeString($bytes=0, $decimals=2)
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$units_count = count($units);
		for($unit = 0; $unit < $units_count && $bytes >= 1024; $unit++) {
			$bytes /= 1024;
		}
		// There are no partial bytes and we aren't concerned with fractions of KB.
		$decimals = $unit < 2 ? 0 : $decimals;
		return number_format($bytes, $decimals).' '.$units[$unit];
	}

	/**
	 * This method normalizes a folder path for consistency within Link Vault.
	 * @param string $path
	 * @return string
	 */
	public function normalizePath($path='')
	{
		// Remove site index from the file path
		$path = str_replace(UrlHelper::baseSiteUrl(), '', $path);
		// Get full path to the file.
		$path = realpath($path);
		// Append a trailing slash if there isn't one.
		if (substr($path, -1) != '/') {
			$path .= '/';
		}
		return Craft::getAlias($path);
	}

	/**
	 * Get the file path to an asset file based on a given Asset.
	 * @param Asset $asset
	 * @return string
	 */
	public function getAssetPath($asset)
	{
		$filePath = null;
		$volume = $asset->getVolume();
		if ( $volume instanceof Local ) {
			$filePath = $asset->getImageTransformSourcePath();
		} else {
			$filePath = $asset->getPath();
		}
		return $filePath;
	}

	/**
	 * Get the full local system path to an asset file based on a given Asset.
	 * @param Asset $asset
	 * @return string
	 */
	public function getLocalAssetPath($asset)
	{
		$path = null;
		$volume = $asset->getVolume();
		// Locally sourced files.
		if ( $volume instanceof Local ) {
			$path = $this->getAssetPath($asset);
		// S3, Google Cloud, Rackspace... Create a temp local copy.
		} else {
			$path = $asset->getCopyOfFile();
		}
		return $path;
	}

}
