<?php

namespace Masuga\LinkVault\migrations;

use Craft;
use craft\db\Migration;
use Masuga\LinkVault\elements\LinkVaultDownload;
use Masuga\LinkVault\elements\LinkVaultCustomField;
use Masuga\LinkVault\widgets\LinkVaultTopDownloadsWidget;

class Install extends Migration
{

	/**
	 * The currently installed version of Craft.
	 * @var string
	 */
	protected $craftVersion = null;

	public function safeUp()
	{
		$this->craftVersion = (string) Craft::$app->getVersion();
		// If we just updated from Craft 2, no need to go further.
		if ($this->_upgradeFromCraft2()) {
			return;
		}
		if (!$this->db->tableExists('{{%linkvault_downloads}}')) {
			$this->createTable('{{%linkvault_downloads}}', [
				'id' => $this->primaryKey(),
				'elementId' => $this->integer(),
				'assetId' => $this->integer(),
				'userId' => $this->integer(),
				'type' => $this->string(25),
				's3Bucket' => $this->string(100),
				'googleBucket' => $this->string(100),
				'dirName' => $this->string(255),
				'fileName' => $this->string(255),
				'downloadAs' => $this->string(255),
				'zipName' => $this->string(255),
				'isUrl' => $this->integer()->defaultValue(0),
				'remoteIP' => $this->string(50),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid()
			]);
		}

		if (!$this->db->tableExists('{{%linkvault_customfields}}')) {
			$this->createTable('{{%linkvault_customfields}}', [
				'id' => $this->primaryKey(),
				'fieldLabel' => $this->string(255),
				'fieldName' => $this->string(255),
				'fieldType' => $this->string(255),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid()
			]);
		}

		if (!$this->db->tableExists('{{%linkvault_reports}}')) {
			$this->createTable('{{%linkvault_reports}}', [
				'id' => $this->primaryKey(),
				'criteria' => $this->text(),
				'orderBy' => $this->string(50),
				'sort' => $this->string(4),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid()
			]);
		}
	}

	private function _upgradeFromCraft2()
	{
		// Determine which columns may or may not be available based on Craft 3 version.
		$selectColumns = version_compare($this->craftVersion, '3.1.0', '>=') ?
			['id', 'handle'] :
			['id', 'settings', 'handle'];
		// Fetch the old plugin row, if it was installed
		$row = (new \craft\db\Query())
			->select($selectColumns)
			->from(['{{%plugins}}'])
			->where(['handle' => 'linkVault'])
			->andWhere(['<', 'version', '3.0.0'])
			->one();
		// Determine if Link Vault was already installed prior to version 3.
		if (!$row) {
			return false;
		}
		// Update this one's settings to old values if Craft is 3.0.*.
		if ( version_compare($this->craftVersion, '3.1.0', '<') ) {
			$this->update('{{%plugins}}', [
				'settings' => $row['settings']
			], ['handle' => 'linkvault']);
		}
		// Delete the old row
		$this->delete('{{%plugins}}', ['id' => $row['id']]);
		// Update the element types.
		$this->update('{{%elements}}', [
			'type' => LinkVaultDownload::class
		], ['type' => 'LinkVault_Download']);
		$this->update('{{%elements}}', [
			'type' => LinkVaultCustomField::class
		], ['type' => 'LinkVault_CustomField']);
		// Update the widget
		$this->update('{{%widgets}}', [
			'type' => LinkVaultTopDownloadsWidget::class
		], ['type' => 'LinkVault_TopDownloadsWidget']);

		$downloadsTable = $this->db->schema->getTableSchema('{{%linkvault_downloads}}');
		if ( ! isset($downloadsTable->columns['googleBucket']) ) {
			$this->addColumn('{{%linkvault_downloads}}', 'googleBucket', 'VARCHAR(255) AFTER `s3Bucket`');
		}
		if ( ! isset($downloadsTable->columns['zipName']) ) {
			$this->addColumn('{{%linkvault_downloads}}', 'zipName', 'VARCHAR(255) AFTER `downloadAs`');
		}
		if ( ! isset($downloadsTable->columns['isUrl']) ) {
			$this->addColumn('{{%linkvault_downloads}}', 'isUrl', 'INT(10) UNSIGNED DEFAULT 0 AFTER `downloadAs`');
		}

		return true;
	}

	public function safeDown()
	{
		if ( $this->db->tableExists('{{%linkvault_downloads}}') ) {
			$this->dropTable('{{%linkvault_downloads}}');
		}
		if ( $this->db->tableExists('{{%linkvault_customfields}}') ) {
			$this->dropTable('{{%linkvault_customfields}}');
		}
		if ( $this->db->tableExists('{{%linkvault_reports}}') ) {
			$this->dropTable('{{%linkvault_reports}}');
		}
	}
}
