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
use Masuga\LinkVault\elements\db\LinkVaultReportQuery;
use Masuga\LinkVault\records\LinkVaultReportRecord;

class ReportsService extends Component
{
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
}
