<?php

namespace Masuga\LinkVault\services;

use Craft;
use craft\helpers\ArrayHelper;
use yii\base\Component;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\db\LinkVaultCustomFieldQuery;
use Masuga\LinkVault\elements\LinkVaultCustomField;
use Masuga\LinkVault\records\LinkVaultCustomFieldRecord;

class CustomFieldsService extends Component
{

	private $_cachedFields = null;

	/**
	 * Fetch all Link Vault custom fields as an array.
	 * @param string $indexBy
	 * @return array
	 */
	public function fetchAllCustomFields($indexBy='id')
	{
		if ( !empty($this->_cachedFields) ) {
			$rows = $this->_cachedFields;
		} else {
			$rows = LinkVaultCustomField::find()->orderBy('id')->all();
			$this->_cachedFields = $rows;
		}
		$fields = ArrayHelper::index($rows, $indexBy);
		return $fields;
	}

	/**
	 * This method constructs a LinkVaultCustomFieldQuery with optional criteria
	 * and returns the query object.
	 * @param array $criteria
	 * @return LinkVaultCustomFieldQuery
	 */
	public function customFieldsQuery($criteria=[]): LinkVaultCustomFieldQuery
	{
		$query = LinkVaultCustomField::find();
		if ($criteria) {
			Craft::configure($query, $criteria);
		}
		return $query;
	}

	/**
	 * This method fetches an array of attributes for models/records.
	 * @return array
	 */
	public function fetchCustomFieldAttributes()
	{
		$attributes= array();
		$fields = $this->fetchAllCustomFields('fieldName');
		foreach($fields as $name => $model) {
			$attributes[$name] = array($this->getAttributeType($model->fieldType), 'default' => null);
		}
		return $attributes;
	}

	/**
	 * This method determines the proper AttributeType based on a given
	 * field type.
	 * @param string $fieldType
	 * @return string
	 */
	public function getAttributeType($fieldType)
	{
		$attributeType = '';
		switch($fieldType) {
			case 'varchar(250)':
			case 'text':
				$attributeType = AttributeType::String;
				break;
			case 'int(11)':
			case 'float':
				$attributeType = AttributeType::Number;
				break;
			default:
				$attributeType = AttributeType::Mixed;
				break;
		}
		return $attributeType;
	}

	/**
	 * This method saves a customField model to a record.
	 * @param LinkVaultCustomField $customField
	 * @return LinkVaultCustomField
	 */
	public function createField($customField)
	{
		if ( Craft::$app->elements->saveElement($customField) ) {
			$this->addCustomFieldColumn($customField->fieldName, $customField->fieldType);
		}
		return $customField;
	}

	/**
	 * This method saves a customField model to a record.
	 * @param LinkVaultCustomField $customField
	 */
	public function destroyField($customField)
	{
		$affectedRows = Craft::$app->db->createCommand()->delete('{{%linkvault_customfields}}', array('id' => $customField->id));
		$this->dropCustomFieldColumn($customField->fieldName);
	}

	/**
	 * This method alters the linkvault_downloads and linkvault_leeches
	 * tables by adding a new custom field column.
	 * @param string $columnName
	 * @param string $columnType
	 */
	private function addCustomFieldColumn($columnName, $columnType='varchar(200)')
	{
		// Fetch the table schema
		$tableName = '{{%linkvault_downloads}}';
		$tableSchema = Craft::$app->db->getTableSchema($tableName);
		if ( ! isset( $tableSchema->columns[$columnName] )) {
			$command = Craft::$app->db->createCommand();
			$command->addColumn($tableSchema->fullName, $columnName, $columnType)->execute();
		}
	}

	/**
	 * This method alters the linkvault_downloads and linkvault_leeches
	 * tables by droping an existing custom field column.
	 * @param string $columnName
	 */
	private function dropCustomFieldColumn($columnName)
	{
		// Fetch the table schema. Add table prefix? No longer a function for that?
		$tableName = '{{%linkvault_downloads}}';
		$tableSchema = Craft::$app->db->getTableSchema($tableName);
		if ( isset( $tableSchema->columns[$columnName] )) {
			$command = Craft::$app->db->createCommand();
			$command->dropColumn($tableSchema->fullName, $columnName)->execute();
		}
	}
}
