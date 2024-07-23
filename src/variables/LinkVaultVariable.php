<?php

namespace Masuga\LinkVault\variables;

use Craft;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\db\LinkVaultCustomFieldQuery;
use Masuga\LinkVault\elements\db\LinkVaultDownloadQuery;
use Masuga\LinkVault\elements\db\LinkVaultReportQuery;

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
    public function downloadUrl($file, $parameters=[])
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
    public function zipUrl($files, $zipBaseName=null, $parameters=[])
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
     * size in base 2. The precision parameter represents the number of decimal
     * places that should be displayed.
     * @param mixed $parameter
     * @param integer $precision
     * @return string
     */
    public function fileSize($parameter, $precision=2): string
    {
        return $this->plugin->general->fileSize($parameter, $precision);
    }

    /**
     * This template variable is an alias of the default behavior of the original
     * fileSize template variable. It exists to give clarity to developers so they
     * know exactly what they're getting.
     * @param mixed $parameter
     * @param int $precision
     * @return string
     */
    public function baseTwoFileSize($parameter, $precision=2): string
    {
        return $this->fileSize($parameter, $precision);
    }

    /**
     * This template variable method outputs a string representation of a file's
     * size in base 10. The precision parameter represents the number of decimal
     * places that should be displayed.
     * @param mixed $parameter
     * @param integer $precision
     * @return string
     */
    public function baseTenFileSize($parameter, $precision=2)
    {
        return $this->plugin->general->fileSize($parameter, $precision, false);
    }

    /**
     * This template variable returns an array of Link Vault records based on
     * the given criteria.
     * @param array $criteria
     * @return LinkVaultDownloadQuery
     */
    public function records($criteria=[]): LinkVaultDownloadQuery
    {
        return $this->plugin->general->records($criteria);
    }

    /**
     * This template variable returns Link Vualt download record counts based on
     * a given column name and other criteria.
     * @param string $columnName
     * @param array $criteria
     * @param string $order
     * @param int $limit
     * @return array
     */
    public function groupCount($columnName, $criteria=null, $order='COUNT(*) desc', $limit=null): array
    {
        return $this->plugin->general->groupCount($columnName, $criteria, $order, $limit);
    }

    /**
     * This template variable returns an array of download records based on
     * the given criteria.
     * @param array $criteria
     * @return LinkVaultDownloadQuery
     */
    public function downloads($criteria=[]): LinkVaultDownloadQuery
    {
        $criteria['type'] = 'Download';
        return $this->plugin->general->records($criteria);
    }

    /**
     * This template variable constructs and configures a LinkVaultCustomFieldQuery
     * then returns it.
     * @param array $criteria
     * @return LinkVaultCustomFieldQuery
     */
    public function customFields($criteria=[]): LinkVaultCustomFieldQuery
    {
        return $this->plugin->customFields->customFieldsQuery($criteria);
    }

    /**
     * This template variable returns an array of leech attempt records based on
     * the given criteria.
     * @param array $criteria
     * @return LinkVaultDownloadQuery
     */
    public function leechAttempts($criteria=[]): LinkVaultDownloadQuery
    {
        $criteria['type'] = 'Leech Attempt';
        return $this->plugin->general->records($criteria);
    }

    /**
     * This method allows for querying saved Link Vault reports.
     * @param array $criteria
     * @return LinkVaultReportQuery
     */
    public function reports($criteria=[]): LinkVaultReportQuery
    {
        return $this->plugin->reports->reports($criteria);
    }

    /**
     * This method fetches the array of filter types based on a supplied field
     * handle. It also allows for an optional selected value parameter.
     * @param string $fieldHandle
     * @param string $selected
     * @return string
     */
    public function fieldFilterOptions($fieldHandle, $selected=null): string
    {
        return $this->plugin->reports->getFilterOptionsByFieldHandle($fieldHandle, true, $selected);
    }

    /**
     * This method converts an array of filters form inputs into Craft element
     * query criteria.
     * @param array $criteria
     * @return array
     */
    public function formatFilterCriteria($criteria): array
    {
        return $this->plugin->reports->formatCriteria($criteria);
    }

    /**
     * This template variable returns an associative array of field option values
     * and labels for a given field handle.
     * @param string $fieldHandle
     * @return array
     */
    public function fieldOptions($fieldHandle): array
    {
        return $this->plugin->reports->getFieldOptionsByFieldHandle($fieldHandle);
    }

}
