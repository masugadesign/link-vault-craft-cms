<?php

namespace Masuga\LinkVault\controllers;

use Craft;
use craft\web\Controller;
use Masuga\LinkVault\LinkVault;
use yii\web\Response;

class ReportsController extends Controller
{

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

}
