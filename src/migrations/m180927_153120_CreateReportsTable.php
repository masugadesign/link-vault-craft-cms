<?php

namespace Masuga\LinkVault\migrations;

use Craft;
use craft\db\Migration;

/**
 * m180927_153120_CreateReportsTable migration.
 */
class m180927_153120_CreateReportsTable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
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

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        // Craft put this here automatically. Is this true?
        echo "m180927_153120_CreateReportsTable cannot be reverted.\n";
        return false;
    }
}
