<?php

namespace Masuga\LinkVault\resources;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class PaginationAssetBundle extends AssetBundle
{
	public function init()
	{
		$this->sourcePath = '@Masuga/LinkVault/resources';
		$this->depends = [
			CpAsset::class,
		];
		$this->css = [
			'pagination.css',
		];
		parent::init();
	}
}
