<?php

namespace Masuga\LinkVault\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use Masuga\LinkVault\LinkVault;

class FrontEndController extends Controller
{
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
		// Check to see if blockLeechAttempts is disabled or if this is not a leech attempt.
		if ( $this->plugin->getSettings()->blockLeechAttempts === false || ! $this->isLeechAttempt() ) {
			// Log/download the file or render the "missing" template if the file isn't found.
			$status = $this->plugin->general->download($parameters);
			if ( $status == 404 ) {
				$this->plugin->general->log("Download file does not exist: ".var_export($parameters,true), LogLevel::Error);
				header("HTTP/1.1 404 Not Found");
				$this->renderErrorTemplate(404);
			}
		// blockLeechAttempts is enabled AND a leech attempt was detected.
		} else {
			if ( $this->plugin->getSettings()->logLeechAttempts === true ) {
				$this->plugin->general->logDownload($parameters, 'Leech Attempt');
			}
			header("HTTP/1.1 403 Forbidden");
			$this->renderErrorTemplate(403);
		}
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
		// Fetch the regular templates path.
		$oldPath = Craft::$app->path->getSiteTemplatesPath();
		// Fetch the preferred template from the plugin settings.
		switch($status) {
			case 403:
				$template = $this->plugin->getSettings()->leechTemplate;
				break;
			default:
				$template = $this->plugin->getSettings()->missingTemplate;
				break;
		}
		// Site owner never set one, fall back on the error templates provided with Link Vault.
		if ( ! $template ) {
			$newPath = $this->plugin->getBasePath().'/templates';
			Craft::$app->view->setTemplatesPath($newPath);
			$template = 'errors/'.(string)$status;
		}
		$this->renderTemplate($template);
		// Whether or not it was changed, restore the original templates path.
		Craft::$app->view->setTemplatesPath($oldPath);
	}

}
