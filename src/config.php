<?php

return [

	/**
	 * When debug is enabled, Link Vault will perform additional logging to
	 * the linkvault.log file.
	 * @var boolean
	 */
	'debug' => false,

	/**
	 * The URI used when generating Link Vault download links.
	 * @var string
	 */
	'downloadTrigger' => 'download',

	/**
	 * Some sites may want the benefit of download tracking while allowing
	 * download links to be shared around the web. Set this variable to false
	 * in your linkvault.php config file to disable leech attempt blocking.
	 * @var boolean
	 */
	'blockLeechAttempts' => true,

	/**
	 *
	 * @var boolean
	 */
	'logLeechAttempts' => true

];
