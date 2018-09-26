<?php

namespace Masuga\LinkVault\migrations;

use craft\db\Migration;
/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170117_215325_linkvault_AddGoogleBucketColumn extends Migration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$downloadsTable = $this->db->schema->getTableSchema('{{%linkvault_downloads}}');
		if ( ! isset($downloadsTable->columns['googleBucket']) ) {
			$this->addColumn('{{%linkvault_downloads}}', 'googleBucket', 'VARCHAR(255) AFTER `s3Bucket`');
		}
		if ( ! isset($downloadsTable->columns['zipName']) ) {
			$this->addColumn('{{%linkvault_downloads}}', 'zipName', 'VARCHAR(255) AFTER `downloadAs`');
		}
		return true;
	}
}
