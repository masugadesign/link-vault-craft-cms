<?php

namespace Masuga\LinkVault\migrations;

use Craft;
use craft\db\Migration;
use Masuga\LinkVault\elements\LinkVaultDownload;
use Masuga\LinkVault\elements\LinkVaultCustomField;
use Masuga\LinkVault\widgets\LinkVaultTopDownloadsWidget;

/**
 * m200731_102300_UpgradeFixes migration.
 * This migration is responsible for fixing possible issues that some may experience
 * after upgrading from Craft 2 to Craft 3.
 */
class m200731_102300_UpgradeFixes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Update the element types column values to the full class names.
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

        // Add any potentially missing columns.
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
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        // Craft put this here automatically. Is this true?
        echo "m200731_102300_UpgradeFixes cannot be reverted.\n";
        return false;
    }
}
