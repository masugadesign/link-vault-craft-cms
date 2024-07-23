<?php

namespace Masuga\LinkVault\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementActionInterface;
use craft\elements\actions\DeleteActionInterface;
use craft\elements\db\ElementQueryInterface;
use Masuga\LinkVault\LinkVault;

class LinkVaultDeleteCustomField extends ElementAction implements DeleteActionInterface
{

    /**
     * @var string|null The confirmation message that should be shown before the elements get deleted
     */
    public $confirmationMessage;

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public $successMessage;

    public function init(): void
    {
        $this->confirmationMessage = Craft::t('linkvault', 'Are you sure you want to delete the selected Link Vault custom fields?');
        $this->successMessage = Craft::t('linkvault', 'Custom fields deleted.');
    }

    /**
     * @inheritDoc IComponentType::getName()
     * @return string
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('linkvault', 'Delete…');
    }

    /**
     * @inheritDoc IElementAction::isDestructive()
     * @return bool
     */
    public static function isDestructive(): bool
    {
        return true;
    }

    /**
     * @inheritDoc IElementAction::getConfirmationMessage()
     * @return string|null
     */
    public function getConfirmationMessage(): ?string
    {
        return $this->confirmationMessage;
    }

    /**
     * @inheritDoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        foreach($query->all() as $field) {
            LinkVault::getInstance()->customFields->destroyField($field);
            Craft::$app->elements->deleteElementById($field->id);
        }
        $this->setMessage($this->successMessage);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canHardDelete(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function setHardDelete(): void
    {
        $this->hard = true;
    }

}
