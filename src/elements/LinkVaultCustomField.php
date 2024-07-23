<?php

namespace Masuga\LinkVault\elements;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\actions\LinkVaultDeleteCustomField;
use Masuga\LinkVault\elements\db\LinkVaultCustomFieldQuery;
use Masuga\LinkVault\records\LinkVaultCustomFieldRecord;

class LinkVaultCustomField extends Element
{

    public $fieldLabel = null;
    public $fieldName = null;
    public $fieldType = null;

    /**
     * Returns the element type name.
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('linkvault', 'Custom Fields');
    }

    /**
     * @inheritdoc
     */
    public static function find(): ElementQueryInterface
    {
        return new LinkVaultCustomFieldQuery(static::class);
    }

    /**
     * Returns whether this element type has content.
     * @return bool
     */
    public static function hasContent(): bool
    {
        return false;
    }

    /**
     * Returns whether this element type has titles.
     * @return bool
     */
    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getUiLabel(): string
    {
        return $this->_uiLabel ?? $this->uiLabel() ?? (string)$this;
    }

    /**
     * Returns this element type's sources.
     * @param string|null $context
     * @return array|false
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('linkvault', 'All Fields'),
                'criteria' => []
            ]
        ];
        return $sources;
    }

    /**
     * Returns the attributes that can be shown/sorted by in table views.
     * @param string|null $source
     * @return array
     */
    public static function defineTableAttributes($source = null): array
    {
        return [
            'id' => Craft::t('app', 'ID'),
            'fieldLabel' => Craft::t('linkvault', 'Field Label'),
            'fieldName' => Craft::t('linkvault', 'Field Handle'),
            'fieldType' => Craft::t('linkvault', 'Fild Type')
        ];
    }

    /**
     * @inheritDoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['fieldLabel', 'fieldName', 'fieldType'];
    }

    /**
     * @inheritDoc IElementType::defineSearchableAttributes()
     * @return array
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['fieldLabel', 'fieldName', 'fieldType'];
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'dateCreated': {
                $date = $this->$attribute;
                if ($date) {
                    return DateTimeHelper::toDateTime($date)->format('F j, Y H:i');
                } else {
                    return '';
                }
            }
            default: {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }

    /**
     * Returns the HTML for an editor HUD for the given element.
     * @param BaseElementModel $element
     * @return string
     */
    public function getEditorHtml(): string
    {
        $html .= parent::getEditorHtml();
        return $html;
    }

    /**
     * @inheritDoc IElementType::getAvailableActions()
     * @param string|null $source
     * @return array|null
     */
    protected static function defineActions(string $source = null): array
    {
        return [
            LinkVaultDeleteCustomField::class
        ];
    }

    /**
     * @inheritdoc
     * @throws Exception if existing record is not found.
     */
    public function afterSave(bool $isNew): void
    {
        if ( $isNew ) {
            $record = new LinkVaultCustomFieldRecord;
            $record->id = $this->id;

        } else {
            $record = LinkVaultCustomFieldRecord::findOne($this->id);
            if (!$record) {
                throw new Exception('Invalid custom field ID: '.$this->id);
            }
        }
        $record->fieldName = $this->fieldName;
        $record->fieldLabel = $this->fieldLabel;
        $record->fieldType = $this->fieldType;
        $record->save();
        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        return true;
    }

}
