<?php

namespace Masuga\LinkVault\widgets;

use Craft;
use craft\base\Widget;
use Masuga\LinkVault\LinkVault;

class LinkVaultTopDownloadsWidget extends Widget
{

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('linkvault', 'Link Vault - Top Downloads');
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): string
    {
        $criteria = ['d.type' => 'Download'];
        $order = 'COUNT(*) desc';
        $limit = 10;
        $rows = LinkVault::getInstance()->general->groupCount('fileName', $criteria, $order, $limit);
        return Craft::$app->view->renderTemplate('linkvault/_widgets/top-downloads', array(
            'rows' => $rows
        ));
    }
}
