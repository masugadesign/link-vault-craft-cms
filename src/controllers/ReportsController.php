<?php

namespace Masuga\LinkVault\controllers;

use Craft;
use craft\helpers\ArrayHelper;
use craft\web\Controller;
use Masuga\LinkVault\LinkVault;
use yii\web\Response;

class ReportsController extends Controller
{

	/**
	 * The instance of the Link Vault plugin object.
	 * @var LinkVault
	 */
	private $plugin = null;

	public function init()
	{
		parent::init();
		$this->plugin = LinkVault::getInstance();
	}

	/**
	 * This controller action presents the user with the reporting tool landing page
	 * and/or results based on the entered criteria.
	 * @return Response
	 */
	public function actionIndex(): Response
	{
		$request = Craft::$app->getRequest();
		$options = $this->plugin->general->reportAttributeOptions();
		$criteria = $request->getParam('criteria');
		return $this->renderTemplate('linkvault/_reports', [
			'criteria' => $criteria,
			'criteriaAttributes' => $options
		]);
	}

	/**
	 * This controller action generates a CSV report based on supplied criteria
	 * from the reports form.
	 * @return Response
	 */
	public function actionExportCsv(): Response
	{
		$request = Craft::$app->getRequest();
		$criteria = $request->getParam('criteria');
		$orderBy = $request->getParam('orderBy');
		$sort = $request->getParam('sort');
		if ( in_array($orderBy, ['dateCreated', 'dateUpdated']) ) {
			$orderBy = 'linkvault_downloads.'.$orderBy;
		}
		$records = $this->plugin->general->records($criteria)->orderBy($orderBy.' '.$sort)->limit(null)->all();
		$recordsArray = ArrayHelper::toArray($records);
		$csvContent = $this->plugin->export->convertArrayToDelimitedContent($recordsArray);
		$response = Craft::$app->getResponse();
		$reportName = $this->plugin->export->generateReportFileName($criteria);
		return $response->sendContentAsFile($csvContent, $reportName.'.csv', ['mimeType' => 'text/csv']);
	}

}
