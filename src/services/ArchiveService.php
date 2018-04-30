<?php

namespace Masuga\LinkVault\services;

use Craft;
use craft\elements\Asset;
use craft\elements\User;
use yii\base\Component;
use Masuga\LinkVault\LinkVault;

class ArchiveService extends Component
{

	/**
	 * The system path where zip files are temporarily stored.
	 * @var string
	 */
	protected $runtimePath = null;

	public function __construct()
	{
		$this->runtimePath = rtrim(Craft::$app->path->storagePath, '/').'/runtime/linkvault/';
	}

	/**
	 * This method zips and logs downloads for a given array of filepaths and
	 * asset IDs.
	 * @param array $files
	 * @param string $zipName
	 * @param array $parameters
	 */
	public function trackAndZipFiles($files, $zipName, $parameters=array())
	{
		$parameters['zipName'] = $zipName;
		$zipPath = $this->runtimePath.$zipName;
		$zipArchive = new ZipArchive;
		$openCode = $zipArchive->open($zipPath, ZipArchive::CREATE|ZipArchive::OVERWRITE);
		if ( $openCode === true ) {
			foreach($files as &$file) {
				$path = null;
				$fileParams = $parameters;
				// Numeric values should only be asset IDs. Fetch asset and full path.
				if (is_numeric($file)) {
					$asset = Craft::$app->assets->getAssetById($file);
					$path = Craft::$app->linkVault_file->getLocalAssetPath($asset);
				// The $file is already an instance of an Asset.
				} elseif ($file instanceof Asset) {
					$asset = $file;
					Craft::$app->linkVault_file->getLocalAssetPath($file);
				// If the path is a URL, attempt copy the contents to the archive.
				} elseif ( filter_var($file, FILTER_VALIDATE_URL) !== false ) {
					$fileParams['isUrl'] = 1;
					$fileParams['filePath'] = $file;
					$downloadAs = end(explode('/', parse_url($file, PHP_URL_PATH)));
				// Otherwise, add the file to the archive the usual way.
				} elseif (is_string($file) && file_exists($file)) {
					$path = $file;
					$downloadAs = pathinfo($path, PATHINFO_BASENAME);
					$fileParams['filePath'] = $path;
				}
				// Update some parameters if file is reference/instance of an asset.
				if ( !empty($asset) ) {
					$downloadAs = $asset->filename;
					$fileParams['assetId'] = $asset->id;
					$fileParams['filePath'] = Craft::$app->linkVault_file->getAssetPath($asset);
					$fileParams['downloadAs'] = $downloadAs;
				}
				// Add the item to the archive.
				if ( filter_var($file, FILTER_VALIDATE_URL) !== false ) {
					$zipArchive->addFromString($downloadAs, file_get_contents($file));
				} else {
					$zipArchive->addFile($path, $downloadAs);
				}
				Craft::$app->linkVault->logDownload($fileParams);
			}
			$zipArchive->close();
		} else {
			throw new Exception(Craft::t('linkvault', 'Unable to open zip file for writing.'));
		}
		// Only return the path to the file if the file was written.
		return file_exists($zipPath) ? $zipPath : null;
	}
}
