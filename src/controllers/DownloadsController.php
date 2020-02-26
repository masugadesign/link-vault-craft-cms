<?php

namespace Masuga\LinkVault\controllers;

use Craft;
use craft\web\Controller;
use Masuga\LinkVault\LinkVault;
use yii\web\NotFoundHttpException;

class DownloadsController extends Controller
{

	/**
	 * This controller action loads the download history page.
	 */
	public function actionDownloadIndex()
	{
		return $this->redirect('linkvault/reports');
	}

	/**
	 * This controller action loads a user download/leech history page.
	 */
	public function actionUserDownloads()
	{
		$request = Craft::$app->getRequest();
		$userId = $request->getParam('userId');
		$type = $request->getParam('type') ? $request->getParam('type') : 'Download';
		$user = Craft::$app->users->getUserById($userId);
		if ( $user ) {
			return $this->renderTemplate('linkvault/_userdownloads', array('user' => $user, 'type' => $type));
		} else {
			throw new NotFoundHttpException('Invalid user ID.');
		}
	}

}
