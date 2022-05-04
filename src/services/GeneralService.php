<?php

namespace Masuga\LinkVault\services;

use Craft;
use craft\awss3\Fs as S3;
use craft\db\Query;
use craft\elements\Asset;
use craft\googlecloud\Fs as GoogleCloud;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\fs\Local;
use craft\web\Response;
use yii\base\Component;
use yii\helpers\Inflector;
use yii\log\Logger;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\LinkVaultDownload;
use Masuga\LinkVault\elements\db\LinkVaultDownloadQuery;
use Masuga\LinkVault\records\LinkVaultDownloadRecord;

class GeneralService extends Component
{

	/**
	 * The instance of the LinkVault plugin.
	 * @var LinkVault
	 */
	private $plugin = null;

	/**
	 * A boolean variable to determine if debug is enabled for Link Vault.
	 * @var boolean
	 */
	public $debug = false;

	/**
	 * The array of Link Vault's plugin settings.
	 * @var array
	 */
	public $settings = null;

	/**
	 * The encryption key to use for encrypting/decrypting Link Vault data.
	 * @var string
	 */
	protected $encryptionKey = null;

	/**
	 * The full path to the service log file.
	 * @var string|null
	 */
	private $logPath = null;

	/**
	 * The class initialization method.
	 */
	public function __construct()
	{
		$this->plugin = LinkVault::getInstance();
		$this->debug = $this->plugin->getSettings()->debug;
		$this->settings = $this->plugin->getSettings();
		$this->logPath = Craft::$app->getPath()->getLogPath().'/linkvault-'.date('Ym').'.log';
	}

	/**
	 * This method generates a Link Vault download URL.
	 * @param mixed $file
	 * @param array $parameters
	 */
	public function downloadUrl($file, $parameters=array())
	{
		// Check if the supplied $file is a valid URL.
		if ( filter_var($file, FILTER_VALIDATE_URL) !== false ) {
			$filePath = $file;
			$parameters['isUrl'] = 1;
		// Programmatically fetch a path for a given Asset instance.
		} elseif ( $file instanceof Asset ) {
			$filePath = $this->plugin->files->getAssetPath($file);
			$parameters['assetId'] = $file->id;
			$volume = $file->getVolume();
			$fs = $volume->getFs();
			$fsSettings = $fs->getSettings();
			if ( $fs instanceof S3 ) {
				$parameters['s3Bucket'] = $fsSettings['bucket'];
			} elseif ( $fs instanceof GoogleCloud ) {
				$parameters['googleBucket'] = $fsSettings['bucket'];
			}
		// Any object other than Asset is not supported. Log it to assist the developer in debug.
		} elseif ( is_object($file) ) {
			$this->log(self::class.'::downloadURL() - Object of type "'.get_class($file).'" is not supported by Link Vault.');
			$filePath = null;
		// Assume the $file parameter is a string path to the file already.
		} else {
			$filePath = $file;
		}
		$parameters['filePath'] = $filePath;
		return $filePath ? UrlHelper::siteUrl($this->settings->downloadTrigger, array('lv' => rawurlencode($this->encrypt(serialize($parameters))) )) : null;
	}

	/**
	 * This method returns a Link Vault zip file download URL based on an array
	 * of supplied assets or file paths.
	 * @param array $files
	 * @param string $zipBaseName
	 * @param array $parameters
	 */
	public function zipUrl($files, $zipBaseName=null, $parameters=array())
	{
		$parameters['files'] = array();
		$parameters['zipName'] = $zipBaseName ? $zipBaseName.'.zip' : 'archive-'.date('Y-m-d-H-i').'.zip';
		foreach($files as $file) {
			if ( $file instanceof Asset ) {
				$parameters['files'][] = $file->id;
			} else {
				$parameters['files'][] = $file;
			}
		}
		return UrlHelper::siteUrl($this->settings->downloadTrigger, array('lv' => rawurlencode($this->encrypt(serialize($parameters))) ));
	}

	/**
	 * This method returns the total downloads matching a given set of
	 * parameters.
	 * @param mixed $parameters
	 * @return integer
	 */
	public function totalDownloads($parameter): int
	{
		// Check to see if the $parameter is a valid URL.
		if ( filter_var($parameter, FILTER_VALIDATE_URL) !== false ) {
			$parameters['fileName'] = $parameter;
			$parameters['isUrl'] = 1;
		// Check to see if $parameter is an Asset.
		} elseif ( $parameter instanceof Asset ) {
			$volume = $parameter->getVolume();
			$fs = $volume->getFs();
			$fsSettings = $fs->getSettings();
			if ( $fs instanceof Local ) {
				$fileParts = pathinfo( $this->plugin->files->getAssetPath($parameter) );
				$parameters['fileName'] = $fileParts['filename'].'.'.$fileParts['extension'];
				$parameters['dirName'] = $this->plugin->files->normalizePath($fileParts['dirname']);
			} elseif ( $fs instanceof S3 ) {
				$parameters['s3Bucket'] = $fsSettings['bucket'] ?? null;
				$parameters['fileName'] = $parameter->filename;
			} elseif ( $fs instanceof GoogleCloud ) {
				$parameters['googleBucket'] = $fsSettings['bucket'] ?? null;
				$parameters['fileName'] = $parameter->filename;
			} else {
				$parameters = array();
			}
		// Any object other than Asset is not supported.
		} elseif ( is_object($parameter) ) {
			$this->log(self::class.'::totalDownloads() - Object of type "'.get_class($parameter).'" is not supported by Link Vault.');
			$parameters = null;
		// Check to see if $parameter is an array of searchable columns.
		} elseif ( is_array($parameter) ) {
			$parameters = $parameter;
		// Assume $parameter is a path string.
		} else {
			$fileParts = pathinfo($parameter);
			$parameters['fileName'] = $fileParts['filename'].'.'.$fileParts['extension'];
			$parameters['dirName'] = $this->plugin->files->normalizePath($fileParts['dirname']);
		}
		// Be sure to omit leech attempts
		$parameters['type'] = 'Download';
		return is_array($parameters) ? LinkVaultDownloadRecord::find()->where($parameters)->count() : 0;
	}

	/**
	 * This method returns the file size matching a given set of
	 * parameters.
	 * @param mixed $parameter
	 * @param integer $precision
	 * @param bool $baseTwo
	 * @return string
	 */
	public function fileSize($parameter, $precision=2, $baseTwo=true): string
	{
		// Check to see if the $parameter is a valid URL.
		if ( filter_var($parameter, FILTER_VALIDATE_URL) !== false ) {
			$fileSize = $this->plugin->files->remoteFileSize($parameter, $precision, $baseTwo);
		// Check to see if $parameter is an Asset.
	} elseif ( $parameter instanceof Asset ) {
			//$filePath = $this->plugin->files->getAssetPath($parameter);
			$fileSize = $this->plugin->files->fileSizeString($parameter->size, $precision, $baseTwo);
		// Any object other than Asset is not supported.
		} elseif ( is_object($parameter) ) {
			$this->log(self::class.'::fileSize() - Object of type "'.get_class($parameter).'" is not supported by Link Vault.');
			$fileSize = null;
		// Check to see if $parameter is an array of columns where dirName and fileName are present.
		} elseif ( is_array($parameter) ) {
			$dirName = isset($parameter['dirName']) ? $this->plugin->files->normalizePath($parameter['dirName']) : '';
			$fileName = isset($parameter['fileName']) ? $parameter['fileName'] : '';
			$filePath = $dirName.$fileName;
			$fileSize = $this->plugin->files->fileSize($filePath, $precision, $baseTwo);
		// Assume $parameter is a path string.
		} else {
			$filePath = $parameter;
			$fileSize = $this->plugin->files->fileSize($filePath, $precision, $baseTwo);
		}
		return $fileSize;
	}

	/**
	 * This method returns an array of records based on
	 * the given criteria.
	 * @param array $criteria
	 * @return LinkVaultDownloadQuery
	 */
	public function records($criteria=array()): LinkVaultDownloadQuery
	{
		$query = LinkVaultDownload::find();
		if ($criteria) {
			Craft::configure($query, $criteria);
		}
		return $query;
	}

	/**
	 * This method returns an array of Link Vault record counts based on a column
	 * name and other criteria.
	 * @param string $columnName
	 * @param array $criteria
	 * @param string $order
	 * @param integer $limit
	 * @return array
	 */
	public function groupCount($columnName, $criteria=null, $order='COUNT(*) desc', $limit=null): array
	{
		$results = (new Query)->select([$columnName, "COUNT(*) AS `census`"])
				->from('{{%linkvault_downloads}} AS d')
				->join('INNER JOIN', '{{%elements}} AS e', 'd.id=e.id')
				->where($criteria)
				->andWhere(['is', 'e.dateDeleted', new \yii\db\Expression('null')])
				->groupBy($columnName)
				->orderBy($order)
				->limit($limit)
				->all();
		return $results;
	}

	/**
	 * Log a download to the linkvault_downloads table.
	 * @param array $recordData
	 * @param string $type
	 * @return boolean;
	 */
	public function logDownload($recordData, $type='Download')
	{
		// Fetch all user-defined custom fields
		$customFields = $this->plugin->customFields->fetchAllCustomFields('fieldName');
		// Create variables from the file data and session information.
		$saveStatus = false;
		$fileParts = pathinfo($recordData['filePath']);
		$isUrl = isset($recordData['isUrl']) ? $recordData['isUrl'] : 0;
		$user = Craft::$app->getUser();
		// Prepare the download model.
		$element = new LinkVaultDownload;
		$element->type = $type;
		$element->siteId = Craft::$app->getSites()->currentSite->id;
		$element->s3Bucket = isset($recordData['s3Bucket']) ? $recordData['s3Bucket'] : null;
		$element->googleBucket = isset($recordData['googleBucket']) ? $recordData['googleBucket'] : null;
		if ( !$isUrl ) {
			$element->dirName = $element->s3Bucket || $element->googleBucket ? $fileParts['dirname'].'/' : $this->plugin->files->normalizePath($fileParts['dirname']);
		}
		$element->fileName = $isUrl ? $recordData['filePath'] : $fileParts['filename'].'.'.$fileParts['extension'];
		$element->elementId = isset($recordData['elementId']) ? $recordData['elementId'] : null;
		$element->assetId = isset($recordData['assetId']) ? $recordData['assetId'] : null;
		$element->userId = isset($user->id) ? $user->id : null;
		$element->downloadAs = isset($recordData['downloadAs']) ? $recordData['downloadAs'] : $fileParts['filename'].'.'.$fileParts['extension'];
		$element->zipName = isset($recordData['zipName']) ? $recordData['zipName'] : null;
		$element->isUrl = $isUrl;
		$element->remoteIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		foreach($customFields as $fieldName => $fieldModel) {
			$element->$fieldName = isset($recordData[$fieldName]) ? $recordData[$fieldName] : null;
		}
		// Attempt to save the element.
		$saveStatus = Craft::$app->elements->saveElement($element);
		return $saveStatus;
	}

	/**
	 * This method fetches a Link Vault plugin setting or returns a specified
	 * fallback if the setting is undefined.
	 * @param string $name
	 * @param mixed $fallback
	 */
	public function getSetting($name='', $fallback=null)
	{
		return isset($this->settings[$name]) ? $this->settings[$name] : $fallback;
	}

	/**
	 * This method extracts a domain from a given URL.
	 * @param string $url
	 * @return string
	 */
	public function extractDomainFromURL($url='')
	{
		$withoutProtocol = str_replace(array('https://', 'http://'), '', $url);
		return strpos($withoutProtocol,'/') !== false ? substr($withoutProtocol, 0, strpos($withoutProtocol,'/') ) : $withoutProtocol;
	}

	/**
	 * This method returns a response that will either result in a file download,
	 * a redirect or null if the file/url could not be handled properly.
	 * @param array $parameters
	 * @return Response|null
	 */
	public function download($parameters=[])
	{
		//$this->log("download \$parameters: ".print_r($parameters,true));
		$files        = $parameters['files'] ?? null;
		$zipName      = $parameters['zipName'] ?? null;
		$filePath     = $parameters['filePath'] ?? null;
		$assetId      = $parameters['assetId'] ?? null;
		$downloadAs   = $parameters['downloadAs'] ?? basename($filePath);
		$s3Bucket     = $parameters['s3Bucket'] ?? null;
		$googleBucket = $parameters['googleBucket'] ?? null;
		$isUrl        = isset($parameters['isUrl']) && $parameters['isUrl'] == 1 ? true : false;
		// The file is a valid URL.
		if ( $isUrl ) {
			$this->logDownload($parameters);
			$response = Craft::$app->getResponse()->redirect($filePath);
		// The file path is a valid file found on the server.
		} elseif ( $filePath && file_exists($filePath) ) {
			$this->logDownload($parameters);
			//$this->plugin->files->serveFile($filePath, $downloadAs);
			$response = Craft::$app->getResponse()->sendFile($filePath, $downloadAs);
		// An asset ID was supplied.
		} elseif ( $assetId ) {
			$this->logDownload($parameters);
			$file = Craft::$app->assets->getAssetById($assetId);
			$localPath = $file->getCopyOfFile();
			$response = Craft::$app->getResponse()->sendFile($localPath, $downloadAs);
		// Zip some files on-the-fly. (Link Vault Zipper)
		} elseif ( $files && $zipName ) {
			$archivePath = $this->plugin->archive->trackAndZipFiles($files, $zipName, $parameters);
			$parameters['filePath'] = $archivePath;
			$this->logDownload($parameters);
			$response = Craft::$app->getResponse()->sendFile($archivePath, $zipName);
			// Delete the temporary zip file from the storage folder.
			unlink($archivePath);
		// The file does not exist. Show the user an appropriate 404 page.
		} else {
			$response = null;
		}
		return $response;
	}

	/**
	 * Encrypt some data using Link Vault's encryption key that was generated
	 * during the installation process.
	 * @param mixed $data
	 * @return string
	 */
	public function encrypt($data)
	{
		return base64_encode(Craft::$app->security->encryptByKey($data, $this->settings['encryptionKey']));
	}

	/**
	 * Decrypt some encrypted data using Link Vault's encryption key.
	 * @param string $data
	 * @return mixed
	 */
	public function decrypt($data)
	{
		return Craft::$app->security->decryptByKey(base64_decode($data), $this->settings['encryptionKey']);
	}

	/**
	 * This method will log a message to the Link Vault plugin log as long
	 * as the "debug" config variable is set to (boolean)true.
	 * @param string $message
	 * @param mixed $level
	 */
	public function log($message, $devModeOnly=false)
	{
		if ( ! $devModeOnly || $this->craftDevMode ) {
 			$timestamp = '['.date('Y-m-d g:i a').'] :: ';
 			FileHelper::writeToFile($this->logPath, $timestamp.$message.PHP_EOL, [
 				'append' => true,
 				'lock' => false // Will this prevent permission issues?
 			]);
 		}
	}

}
