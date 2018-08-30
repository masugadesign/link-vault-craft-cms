<?php

namespace Masuga\LinkVault\records;

use craft\db\ActiveRecord;
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\User;
use yii\db\ActiveQueryInterface;

class LinkVaultDownloadRecord extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return '{{%linkvault_downloads}}';
	}

	/**
	 * Returns the download record's element.
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getElement(): ActiveQueryInterface
	{
		return $this->hasOne(Element::class, ['id' => 'id']);
	}

	/**
	 * Returns the download record's user-specified related element.
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getRelatedElement(): ActiveQueryInterface
	{
		return $this->hasOne(Element::class, ['id' => 'elementId']);
	}

	/**
	 * Returns the download record's related Asset.
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getAsset(): ActiveQueryInterface
	{
		return $this->hasOne(Asset::class, ['id' => 'assetId']);
	}

	/**
	 * Returns the download record's related User.
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getUser(): ActiveQueryInterface
	{
		return $this->hasOne(User::class, ['id' => 'userId']);
	}

}
