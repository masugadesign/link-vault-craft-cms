<?php

namespace Masuga\LinkVault\services;

use Craft;
use ZipArchive;
use craft\elements\Asset;
use craft\elements\User;
use yii\base\Component;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\events\ModifyZipUrlFilesEvent;

class ArchiveService extends Component
{

    /**
     * The event that is triggered prior to adding files to the zip download.
     * @event ModifyZipUrlFilesEvent
     */
    const EVENT_MODIFY_ZIP_URL_FILES = 'modifyZipUrlFiles';

    /**
     * The system path where zip files are temporarily stored.
     * @var string
     */
    protected $runtimePath = null;

    /**
     * The instance of the Link Vault plugin.
     * @var LinkVault
     */
    private $plugin = null;

    public function __construct()
    {
        $this->plugin = LinkVault::getInstance();
        $this->runtimePath = rtrim(Craft::$app->path->getRuntimePath(), '/').'/';
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
        // Allow developers to add/remove/tweak files included in the zip.
        $event = new ModifyZipUrlFilesEvent([
            'files' => $files
        ]);
        $this->trigger(self::EVENT_MODIFY_ZIP_URL_FILES, $event);
        $files = $event->files;
        $parameters['zipName'] = $zipName;
        $zipPath = $this->runtimePath.$zipName;
        $zipArchive = new ZipArchive;
        $openCode = $zipArchive->open($zipPath, ZipArchive::CREATE|ZipArchive::OVERWRITE);
        if ( $openCode === true ) {
            foreach($files as &$file) {
                $path = $asset = null;
                $fileParams = $parameters;
                // Numeric values should only be asset IDs. Fetch asset and full path.
                if (is_numeric($file)) {
                    $asset = Craft::$app->assets->getAssetById($file);
                    $path = $this->plugin->files->getLocalAssetPath($asset);
                    $fileParams['filePath'] = $path;
                // The $file is already an instance of an Asset.
                } elseif ($file instanceof Asset) {
                    $asset = $file;
                    $path = $this->plugin->files->getLocalAssetPath($file);
                    $fileParams['filePath'] = $path;
                // If the path is a URL, attempt copy the contents to the archive.
                } elseif ( filter_var($file, FILTER_VALIDATE_URL) !== false ) {
                    $fileParams['isUrl'] = 1;
                    $fileParams['assetId'] = null;
                    $fileParams['filePath'] = $file;
                    $downloadAs = end(explode('/', parse_url($file, PHP_URL_PATH)));
                // Otherwise, add the file to the archive the usual way.
                } elseif (is_string($file) && file_exists($file)) {
                    $path = $file;
                    $downloadAs = pathinfo($path, PATHINFO_BASENAME);
                    $fileParams['assetId'] = null;
                    $fileParams['filePath'] = $path;
                }
                // Update some parameters if file is reference/instance of an asset.
                if ( !empty($asset) ) {
                    $downloadAs = $asset->filename;
                    $fileParams['assetId'] = $asset->id;
                    $fileParams['filePath'] = $this->plugin->files->getAssetPath($asset);
                    $fileParams['downloadAs'] = $downloadAs;
                }
                // Add the item to the archive.
                if ( filter_var($file, FILTER_VALIDATE_URL) !== false ) {
                    $zipArchive->addFromString($downloadAs, file_get_contents($file));
                } elseif ( file_exists($path) ) {
                    $zipArchive->addFile($path, $downloadAs);
                } else {
                    $this->plugin->general->log("File missing `{$path}`. Could not be added to the zip file.");
                }
                $this->plugin->general->logDownload($fileParams);
            }
            $zipArchive->close();
        } else {
            throw new Exception(Craft::t('linkvault', 'Unable to open zip file for writing.'));
        }
        // Only return the path to the file if the file was written.
        return file_exists($zipPath) ? $zipPath : null;
    }
}
