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
		if ( ! Craft::$app->db->columnExists('linkvault_downloads', 'googleBucket') ) {
			$this->addColumnAfter('linkvault_downloads', 'googleBucket', array(
				'column' => ColumnType::Varchar,
				'null'   => true,
				),
				's3Bucket'
			);
		}
		if ( ! Craft::$app->db->columnExists('linkvault_downloads', 'zipName') ) {
			$this->addColumnAfter('linkvault_downloads', 'zipName', array(
				'column' => ColumnType::Varchar,
				'null'   => true,
				),
				'downloadAs'
			);
		}
		return true;
	}
}
