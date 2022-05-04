<?php

namespace Masuga\LinkVault\models;

use craft\base\Model;

class Settings extends Model
{

	/**
	 * Enable debug for additional logging.
	 * @var boolean
	 */
	public $debug = false;

	/**
	 * The URI segment for download URLs.
	 * @var string
	 */
	public $downloadTrigger = 'download';

	/**
	 * Enable this setting to block all leech attempts. It is enabled by default.
	 * @var boolean
	 */
	public $blockLeechAttempts = true;

	/**
	 * Enable this setting to log all leech attempts whether or not they are being
	 * blocked. It is enabled by default.
	 * @var boolean
	 */
	public $logLeechAttempts = true;

	/**
	 * This encryption key setting gets set during installation and is not
	 * changeable via the settings form.
	 * @var string
	 */
	public $encryptionKey = null;

	/**
	 * The leech template is a relative path to a template that should be displayed
	 * when leech attempts are blocked and a leech attempt is detected.
	 * @var string
	 */
	public $leechTemplate = null;

	/**
	 * This missing template is a relative path to a template that should be displayed
	 * when a download link fails to find the referenced file. (404)
	 * @var string
	 */
	public $missingTemplate = null;

	/**
	 * @inheritdoc
	 */
	public function rules(): array
	{
		return [
			[['encryptionKey'], 'required'],
		];
	}
}
