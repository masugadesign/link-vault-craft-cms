<?php
namespace Masuga\LinkVault\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use Masuga\LinkVault\elements\LinkVaultCustomField;

class LinkVaultCustomFieldQuery extends ElementQuery
{

	public $fieldLabel = null;
	public $fieldName = null;
	public $fieldType = null;

	/**
	 * @inheritdoc
	 */
	protected function beforePrepare(): bool
	{
		$this->joinElementTable('linkvault_customfields');
		$this->query->select([
			'linkvault_customfields.fieldName',
			'linkvault_customfields.fieldLabel',
			'linkvault_customfields.fieldType',
		]);
		if ($this->fieldLabel) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_customfields.fieldLabel', $this->fieldLabel));
		}
		if ($this->fieldName) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_customfields.fieldName', $this->fieldName));
		}
		return parent::beforePrepare();
	}

}
