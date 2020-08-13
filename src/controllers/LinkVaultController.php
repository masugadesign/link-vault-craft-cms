<?php

namespace Masuga\LinkVault\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\events\LinkClickEvent;
use yii\log\Logger;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class LinkVaultController extends Controller
{
	/**
	 * The event that is triggered as soon as the query string parameters are parsed
	 * from a Link Vault link click.
	 * @event ModifyZipUrlFilesEvent
	 */
	const EVENT_LINK_CLICK = 'linkClick';

	/**
	 * Do not require an authenticated user for this controller.
	 * @var boolean
	 */
	protected $allowAnonymous = true;

	/**
	 * The instance of the Link Vault plugin.
	 * @var LinkVault
	 */
	private $plugin = null;

	public function init()
	{
		parent::init();
		$this->plugin = LinkVault::getInstance();
	}

	/**
	 * This controller action method either serves a file and logs it or
	 * it redirects the user to a 404 page.
	 */
	public function actionServe()
	{
		$request = Craft::$app->getRequest();
		$lvParam = rawurldecode( $request->getParam('lv') );
		$parameters = $lvParam ? unserialize( $this->plugin->general->decrypt( $lvParam ) ) : array();
		// Allow developers to manipulate link click parameters immediately after the link is clicked.
		$event = new LinkClickEvent([
			'parameters' => $parameters
		]);
		$this->trigger(self::EVENT_LINK_CLICK, $event);
		$parameters = $event->parameters;

		// Check to see if blockLeechAttempts is disabled or if this is not a leech attempt.
		if ( $this->plugin->getSettings()->blockLeechAttempts === false || ! $this->isLeechAttempt() ) {
			// Log/download the file or render the "missing" template if the file isn't found.
			$response = $this->plugin->general->download($parameters);
			if ( ! $response ) {
				$this->plugin->general->log("Download file does not exist: ".var_export($parameters,true), Logger::LEVEL_ERROR);
				$html = $this->renderErrorTemplate(404);
				$response = Craft::$app->getResponse()->setStatusCode(404);
				$response->content = $html;
			}
		// blockLeechAttempts is enabled AND a leech attempt was detected.
		} else {
			if ( $this->plugin->getSettings()->logLeechAttempts === true ) {
				$this->plugin->general->logDownload($parameters, 'Leech Attempt');
			}
			$html = $this->renderErrorTemplate(403);
			$response = Craft::$app->getResponse()->setStatusCode(403);
			$response->content = $html;
		}
		return $response;
	}

	/**
	 * This method determines whether or not this download request is a leech
	 * attempt.
	 * @return boolean
	 */
	protected function isLeechAttempt()
	{
		$referringDomain = strtolower($this->plugin->general->extractDomainFromURL( Craft::$app->getRequest()->getReferrer() ));
		$siteDomain = strtolower($this->plugin->general->extractDomainFromURL( UrlHelper::baseSiteUrl() ));
		return $referringDomain !== $siteDomain;
	}

	/**
	 * This method will determine which error template to display based on a
	 * given status code and the plugin's saved settings.
	 * @param integer $status
	 */
	protected function renderErrorTemplate($status=404)
	{
		$status = (int) $status;
		// Fetch the regular templates path.
		$oldPath = Craft::$app->path->getSiteTemplatesPath();
		// Fetch the preferred template from the plugin settings.
		$template = $status === 403 ?
			$this->plugin->getSettings()->leechTemplate :
			$this->plugin->getSettings()->missingTemplate;
		// No template? Let Craft handle the responses as ususal.
		if ( ! $template ) {
			if ( $status === 403 ) {
				throw new ForbiddenHttpException('You are not authorized to access this resource.');
			} else {
				throw new NotFoundHttpException('The file you requested was not found.');
			}
		}
		$content = Craft::$app->getView()->renderTemplate($template);
		// Whether or not it was changed, restore the original templates path.
		Craft::$app->view->setTemplatesPath($oldPath);
		return $content;
	}

}
