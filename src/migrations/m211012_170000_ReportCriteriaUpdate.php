<?php

namespace Masuga\LinkVault\migrations;

use Craft;
use craft\db\Migration;
use Masuga\LinkVault\elements\LinkVaultReport;
use Masuga\LinkVault\widgets\LinkVaultTopDownloadsWidget;

/**
 *
 */
class m211012_170000_ReportCriteriaUpdate extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Fetch any existing reports and make sure they have the new criteria format.
        $reports = LinkVaultReport::find()->all();
        foreach($reports as $report) {
            $count = 0;
            $criteria = json_decode($report->criteria, true);
            // Check if there is NOT an item in the array with a numeric index of 0 (new format).
            if ( ! isset($criteria[0]) ) {
                $newCriteria = [];
                foreach($criteria as $fieldHandle => &$fieldValue) {
                    // All Link Vault filters used to be "contains" checks.
                    $newCriteria["{$count}"] = [
                        'fieldHandle' => $fieldHandle,
                        'filterType' => 'contains',
                        'value' => $fieldValue
                    ];
                    $count++;
                }
                $report->criteria = json_encode($newCriteria);
                Craft::$app->getElements()->saveElement($report);
            }
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
