<?php

namespace Masuga\LinkVault\records;

use craft\db\ActiveRecord;
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\User;
use yii\db\ActiveQueryInterface;

class LinkVaultReportRecord extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return '{{%linkvault_reports}}';
	}

	/**
	 * Returns the report record's element.
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getElement(): ActiveQueryInterface
	{
		return $this->hasOne(Element::class, ['id' => 'id']);
	}

}
