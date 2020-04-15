<?php

namespace Masuga\LinkVault\events;

use yii\base\Event;

class ModifyZipUrlFilesEvent extends Event
{
    /**
     * The Link Vault files array.
     */
    public $files = [];
}
