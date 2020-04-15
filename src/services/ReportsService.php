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

}
