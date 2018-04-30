<?php

namespace Masuga\LinkVault\controllers;

use Craft;
use craft\web\Controller;
use Masuga\LinkVault\LinkVault;

class DownloadsController extends Controller
{

	/**
	 * This controller action loads the download history page.
	 */
	public function actionDownloadIndex()
	{
		return $this->renderTemplate('linkvault/_index');
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
			throw new HttpException(404);
		}
	}

}
