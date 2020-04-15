<?php

namespace Masuga\LinkVault\events;

use yii\base\Event;

class LinkClickEvent extends Event
{
    /**
     * The Link Vault parameters array.
     */
    public $parameters = [];
}
