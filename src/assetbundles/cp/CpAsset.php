<?php

namespace Masuga\LinkVault\assetbundles\cp;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;

class CpAsset extends AssetBundle
{

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		$this->sourcePath = "@Masuga/LinkVault/assetbundles/cp/dist";

		$this->depends = [
			CraftCpAsset::class,
		];

		$this->js = [
			'js/cp.js'
		];

		$this->css = [
			'css/pagination.css',
			'css/cp.css'
		];

		parent::init();
	}
}
