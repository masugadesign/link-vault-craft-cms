<?php

namespace Masuga\LinkVault\services;

use Craft;
use craft\awss3\Volume as S3;
use craft\db\Query;
use craft\elements\Asset;
use craft\googlecloud\Volume as GoogleCloud;
use craft\helpers\UrlHelper;
use craft\volumes\Local;
use yii\base\Component;
use yii\helpers\Inflector;
use yii\log\Logger;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\LinkVaultReport;
use Masuga\LinkVault\elements\db\LinkVaultDownloadQuery;
use Masuga\LinkVault\elements\db\LinkVaultReportQuery;
use Masuga\LinkVault\records\LinkVaultReportRecord;

class ReportsService extends Component
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
	 * The array of filter types and their respective search syntax.
	 * @var array
	 */
	const FILTER_TYPES = [
		'contains' => '*[value]*',
		'starts with' => '[value]*',
		'ends with' => '*[value]',
		'is equal to' => '[value]',
		'is greater than' => '> [value]',
		'is less than' => '< [value]',
		'is empty' => ':empty:',
		'is not empty' => ':notempty:'
	];

	public function init()
	{
		$this->plugin = LinkVault::getInstance();
		$this->debug = $this->plugin->getSettings()->debug;
	}

	/**
	 * This method creates/updates a LinkVaultReport element based on whether or
	 * not an existing report ID is supplied.
	 * @param array $input
	 * @param int $id
	 * @return LinkVaultReport|null
	 */
	public function saveReport($input=[], $id=null)
	{
		$report = null;
		if ( $id ) {
			$report = LinkVaultReport::find()->id($id)->one();
		}
		if ( ! $report ) {
			$report = new LinkVaultReport;
		}
		$report->siteId = Craft::$app->getSites()->currentSite->id;
		$report->title = $input['title'];
		$report->criteria = $input['criteria'] ?? $report->criteria;
		$report->orderBy = $input['orderBy'] ?? $report->orderBy;
		$report->sort = $input['sort'] ?? $report->sort;
		// On a successful save, return the report element itself.
		return Craft::$app->getElements()->saveElement($report) ? $report : null;
	}

	/**
	 * This method returns a LinkVaultReport element by ID if one is found that
	 * has the specified ID. Otherwise, you get null.
	 * @param int $id
	 * @return LinkVaultReport|null
	 */
	public function fetchReportById($id)
	{
		return $id ? LinkVaultReport::find()->id($id)->one() : null;
	}

	/**
	 * This method returns an array of saved reports based on provided criteria.
	 * @param array $criteria
	 * @return LinkVaultReportQuery
	 */
	public function reports($criteria=array()): LinkVaultReportQuery
	{
		$query = LinkVaultReport::find();
		if ($criteria) {
			Craft::configure($query, $criteria);
		}
		return $query;
	}

	/**
	 * This method cleans up a download record array by reference so odd criteria
	 * attributes aren't displayed or taking up space in memory.
	 * @param array $r
	 */
	public function cleanRecordArray(&$r)
	{
		unset($r['title'],
			$r['slug'],
			$r['uri'],
			$r['before'],
			$r['after'],
			$r['tempId'],
			$r['fieldLayoutId'],
			$r['contentId'],
			$r['hasDescendants'],
			$r['ref'],
			$r['structureId'],
			$r['totalDescendants'],
			$r['newSiteIds'],
			$r['archived'],
			$r['enabled'],
			$r['draftId'],
			$r['revisionId'],
			$r['dateDeleted'],
			$r['trashed'],
			$r['propogateAll'],
			$r['resaving'],
			$r['duplicateOf'],
			$r['previewing'],
			$r['hardDelete']
		);
	}

	/**
	 * This method returns an associative array of LinkVaultDownloadQuery criteria
	 * attributes and their respective option label.
	 * @return array
	 */
	public function reportAttributeOptions(): array
	{
		/*
		This is a long list of criteria that don't apply to Link Vault downloads.
		This list seems to get longer with each release of Craft.
		*/
		$omittedCriteria = [
			'after',
			'ancestorDist',
			'ancestorOf',
			'archived',
			'before',
			'descendantDist',
			'descendantOf',
			'draftCreator',
			'draftId',
			'draftOf',
			'drafts',
			'enabledForSite',
			'fixedOrder',
			'hasDescendants',
			'ignorePlaceholders',
			'inReverse',
			'leaves',
			'level',
			'nextSiblingOf',
			'newSiteIds',
			'preferSites',
			'prevSiblingOf',
			'positionedAfter',
			'positionedBefore',
			'orderBy',
			'ref',
			'relatedTo',
			'revisionCreator',
			'revisionId',
			'revisionOf',
			'revisions',
			'search',
			'siblingOf',
			'status',
			'structureId',
			'title',
			'trashed',
			'type',
			'unique',
			'uri',
			'with',
			'withStructure'
		];
		$elementQuery = new LinkVaultDownloadQuery(LinkVaultDownload::class, []);
		$criteriaAttributes = array_diff($elementQuery->criteriaAttributes(), $omittedCriteria);
		$customFields = array_keys($this->plugin->customFields->fetchAllCustomFields('fieldName'));
		$criteriaAttributes = array_merge($criteriaAttributes, $customFields);
		sort($criteriaAttributes);
		$options = [];
		foreach($criteriaAttributes as $attr) {
			$options[$attr] = Inflector::camel2words($attr);
		}
		return $options;
	}

	/**
	 * This method generates a single piece of element criteria for a given
	 * field handle, filter type and optional value.
	 * @param string $fieldHandle,
	 * @param string $filterType
	 * @param mixed $value
	 * @return array
	 */
	public function fieldCriteria($fieldHandle, $filterType, $value=null): array
	{
		if ( $fieldHandle && $filterType ) {
			$criteria = [$fieldHandle => str_replace('[value]', $value, self::FILTER_TYPES[$filterType])];
		} else {
			$criteria = [];
		}
		return $criteria;
	}

	/**
	 * This method converts filter input criteria into query criteria.
	 * @param array $input
	 * @return array
	 */
	public function formatCriteria($input): array
	{
		$criteria = [];
		foreach($input as &$filter) {
			$fieldHandle = $filter['fieldHandle'] ?? null;
			$filterType = $filter['filterType'] ?? null;
			$value = $filter['value'] ?? null;
			$newCriteria = $this->fieldCriteria($fieldHandle, $filterType, $value);
			// Combine criteria for the same field into an array.
			if ( isset($criteria[$fieldHandle]) ) {
				// It might already be an "and where" array. Append this criteria to it.
				if ( is_array($criteria[$fieldHandle]) ) {
					$criteria[$fieldHandle][] = $newCriteria;
				// Convert criteria into an array "and where" condition.
				} else {
					$originalCriteria = $criteria[$fieldHandle];
					// We don't need the field key again. Just get the array value.
					$criteriaOnly = end($newCriteria);
					$criteria[$fieldHandle] = ['and', $originalCriteria, $criteriaOnly];
				}
			// No criteria was previously defined for this field so add it as usual.
			} else {
				$criteria = array_merge($criteria, $newCriteria);
			}
		}
		return $criteria;
	}

	/**
	 * This method returns an array of filter type options for a given field
	 * based on its field handle. Once the field is fetched, it is actually the
	 * "type" that determines what the filter options are.
	 * @param string $handle
	 * @param bool $asHtml
	 * @param string $selectedValue
	 * @return array|string
	 */
	public function getFilterOptionsByFieldHandle($handle, $asHtml=true, $selectedValue=null)
	{
		// Initialize the possible return value.
		$options = [];
		$optionsHtml = '';
		$fields = [
			'id' => ['is equal to', 'is greater than', 'is less than'],
			'elementId' => ['is equal to', 'is greater than', 'is less than'],
			'userId' => ['is equal to', 'is greater than', 'is less than'],
			'assetId' => ['is equal to', 'is greater than', 'is less than'],
			'type' => ['is equal to'],
			's3Bucket' => ['contains', 'starts with', 'ends with', 'is equal to', 'is empty', 'is not empty'],
			'googleBucket' => ['contains', 'starts with', 'ends with', 'is equal to', 'is empty', 'is not empty'],
			'dirName' => ['contains', 'starts with', 'ends with', 'is equal to', 'is empty', 'is not empty'],
			'fileName' => ['contains', 'starts with', 'ends with', 'is equal to', 'is empty', 'is not empty'],
			'downloadAs' => ['contains', 'starts with', 'ends with', 'is equal to', 'is empty', 'is not empty'],
			'zipName' => ['contains', 'starts with', 'ends with', 'is equal to', 'is empty', 'is not empty'],
			'isUrl' => ['is equal to'],
			'remoteIp' => ['contains', 'starts with', 'ends with', 'is equal to', 'is empty', 'is not empty'],
			'dateCreated' => ['is greater than', 'is less than', 'is equal to', 'is empty', 'is not empty'],
			'dateUpdated' => ['is greater than', 'is less than', 'is equal to', 'is empty', 'is not empty'],
		];
		$options = $fields[$handle] ?? ['contains', 'starts with', 'ends with', 'is equal to', 'is empty', 'is not empty'];
		if ( !empty($options) && $asHtml === true ) {
			foreach($options as &$option) {
				$selected = ($selectedValue && $selectedValue === $option) ? 'selected="selected"' : '';
				$optionsHtml .= "<option value='{$option}' {$selected} >{$option}</option>";
			}
		}
		$returnValue = ($asHtml === true) ? $optionsHtml : $options;
		return $returnValue;
	}

	/**
	 * This method attempts to fetch a filterable Link Vault Download record field's
	 * available value options. There shouldn't be many fields that need this.
	 * @param string $handle
	 * @return array
	 */
	public function getFieldOptionsByFieldHandle($handle): array
	{
		$fieldsWithOptions = [
			'isUrl' => [
				1 => 'Yes',
				0 => 'No'
			],
		];
		$options = $fieldsWithOptions[$handle] ?? [];
		return $options;
	}

}
