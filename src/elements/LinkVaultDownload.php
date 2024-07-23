<?php

namespace Masuga\LinkVault\elements;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\db\Query;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Delete;
use craft\elements\Asset;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\db\LinkVaultDownloadQuery;
use Masuga\LinkVault\exceptions\LinkVaultInvalidRecordException;
use Masuga\LinkVault\records\LinkVaultDownloadRecord;

/**
 * The Link Vault Download element class. This class is being deprecated so the
 * records are stored in the DB without all the "element" pieces.
 * @deprecated
 */
class LinkVaultDownload extends Element
{

    public $assetId = null;
    public $elementId = null;
    public $userId = null;
    public $type = null;
    public $s3Bucket = null;
    public $googleBucket = null;
    public $dirName = null;
    public $fileName = null;
    public $downloadAs = null;
    public $zipName = null;
    public $isUrl = null;
    public $remoteIP = null;
    public $after = null;
    public $before = null;

    private $_asset = null;
    private $_user = null;
    private $_relatedElement = null;

    /**
     * The instance of the Link Vault plugin.
     * @var LinkVault
     */
    private $plugin = null;

    /**
     * Override the great-great-great-great grandparent __set() so we can add
     * properties on-the-fly from the init() method. Remember that Link Vault
     * custom fields are not true Craft custom fields.
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }

    public function __get($name)
    {
        // Capitalizing the 'n' was a bad move.
        if ( $name === 'filename' ) {
            return $this->fileName;
        }
    }

    public function init(): void
    {
        $this->plugin = LinkVault::getInstance();
        $customFields = $this->plugin->customFields->fetchAllCustomFields('fieldName');
        foreach($customFields as $name => $field) {
            $this->{$name} = null;
        }
    }

    /**
     * Returns the element type name.
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('linkvault', 'Downloads');
    }

    /**
     * @inheritdoc
     */
    public static function find(): ElementQueryInterface
    {
        return new LinkVaultDownloadQuery(static::class);
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
    public static function isLocalized(): bool
    {
        return true;
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
                'key'      => 'downloads',
                'label'    => Craft::t('linkvault', 'Downloads'),
                'criteria' => array('type' => 'Download'),
                'defaultSort' => ['elements.dateCreated', 'desc']
            ],
            [
                'key'      => 'leeches',
                'label'    => Craft::t('linkvault', 'Leech Attempts'),
                'criteria' => array('type' => 'Leech Attempt'),
                'defaultSort' => ['elements.dateCreated', 'desc']
            ],
            [
                'key'      => '*',
                'label'    => Craft::t('linkvault', 'All Log Records'),
                'criteria' => [],
                'defaultSort' => ['elements.dateCreated', 'desc']
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
        $tableAttributes = [];
        $customFields = LinkVault::getInstance()->customFields->fetchAllCustomFields('fieldName');
        foreach($customFields as $name => $fieldModel) {
            $tableAttributes[$name] = $fieldModel->fieldLabel;
        }
        return array_merge([
            'id' => Craft::t('linkvault', 'ID'),
            'dateCreated' => Craft::t('linkvault', 'Date'),
            'fileName' => Craft::t('linkvault', 'File'),
            'dirName' => Craft::t('linkvault', 'Directory/Bucket'),
            'userId' => Craft::t('linkvault', 'User'),
            'elementId' => Craft::t('linkvault', 'Element Title'),
            'assetId' => Craft::t('linkvault', 'Asset'),
            'type' => Craft::t('linkvault', 'Type')
        ], $tableAttributes);
    }

    /**
     * @inheritDoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['id', 'dateCreated', 'fileName', 'dirName', 'userId', 'type'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'elements.dateCreated' => Craft::t('app', 'Date Created'),
            'fileName' => Craft::t('linkvault', 'File'),
            'dirName' => Craft::t('linkvault', 'Directory/Bucket')
        ];
    }

    /**
     * @inheritDoc IElementType::defineSearchableAttributes()
     * @return array
     */
    protected static function defineSearchableAttributes(): array
    {
        /*
        Sites with a lot of download records end up with a HUGE search index and
        nobody is going to be "searching" download elements. It's easy enough to
        query them directly.
        */
        //return ['dirName', 'fileName'];
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        $displayValue = '';
        switch ($attribute) {
            case 'dateCreated': {
                $date = $this->$attribute;
                if ($date) {
                    $displayValue = DateTimeHelper::toDateTime($date)->format('F j, Y H:i');
                }
            }
            case 'assetId':
                $asset = $this->asset;
                if ( $asset ) {
                    $display = ! empty($asset->url) ? '<a href="'.$asset->url.'" >'.$asset->title.'</a>' : $asset->title;
                } else {
                    $display = $this->$attribute;
                }
                $displayValue = $display;
            case 'userId':
                $user = $this->user;
                $displayValue = isset($user->username) ? '<a href="'.UrlHelper::cpUrl('linkvault/user', ['userId' => $user->id]).'" >'.$user->username.'</a>' : '--';
            case 'elementId':
                $element = $this->relatedElement;
                if ( $element ) {
                    $title = (string) $element ?: '--';
                    $url = $this->getCpEditUrl();
                    $output = $url ? '<a href="'.$url.'" >'.$title.'</a>' : $title;
                } else {
                    $output = '--';
                }
                $displayValue = $output;
            case 'dirName':
                if ( $this->s3Bucket ) {
                    $dir = $this->s3Bucket.':'.$this->$attribute;
                } elseif ( $this->googleBucket ) {
                    $dir = $this->googleBucket.':'.$this->$attribute;
                } else {
                    $dir = $this->$attribute;
                }
                $displayValue = $dir;
            case 'id':
                $displayValue = $this->$attribute;
            default:
                $displayValue = parent::tableAttributeHtml($attribute);
        }
        return (string) $displayValue;
    }

    /**
     * @inheritDoc IElementType::getAvailableActions()
     * @param string|null $source
     * @return array|null
     */
    protected static function defineActions(string $source = null): array
    {
        return [
            Delete::class
        ];
    }

    /**
     * @inheritdoc
     * @throws LinkVaultInvalidRecordException if existing record is not found.
     */
    public function afterSave(bool $isNew): void
    {
        if ( $isNew ) {
            $record = new LinkVaultDownloadRecord;
            $record->id = $this->id;
        } else {
            $record = LinkVaultDownloadRecord::findOne($this->id);
            if (!$record) {
                throw new LinkVaultInvalidRecordException('Invalid download ID: '.$this->id);
            }
        }
        $record->assetId = $this->assetId;
        $record->elementId = $this->elementId;
        $record->userId = $this->userId;
        $record->type = $this->type;
        $record->s3Bucket = $this->s3Bucket;
        $record->googleBucket = $this->googleBucket;
        $record->dirName = $this->dirName;
        $record->fileName = $this->fileName;
        $record->downloadAs = $this->downloadAs;
        $record->zipName = $this->zipName;
        $record->isUrl = $this->isUrl;
        $record->remoteIP = $this->remoteIP;
        // Fetch the Link Vault custom fields and add the values to the record.
        $customFields = $this->plugin->customFields->fetchAllCustomFields('fieldName');
        foreach($customFields as $name => $field) {
            $record->{$name} = $this->{$name};
        }
        $status = $record->save();
        parent::afterSave($isNew);
    }

    /**
     * This method sets the related _asset property.
     * @param Asset $asset
     * @return $this
     */
    public function setAsset($asset)
    {
        $this->_asset = $asset;
        return $this;
    }

    /**
     * This method returns the Asset element associated with this record.
     * @return Asset|null
     */
    public function getAsset()
    {
        $asset = null;
        if ( $this->_asset !== null ) {
            $asset = $this->_asset;
        } elseif ( $this->assetId ) {
            $asset = Craft::$app->assets->getAssetById($this->assetId);
        }
        return $asset;
    }

    /**
     * This method fetches a related element based on whichever elementId is
     * stored on the download record. The related element must extend Craft's
     * base Element class.
     * @return mixed
     */
    public function getRelatedElement()
    {
        $element = null;
        if ( $this->_relatedElement !== null ) {
            $element = $this->_relatedElement;
        } elseif ( $this->elementId ) {
            $element = Craft::$app->elements->getElementById($this->elementId);
        }
        return $element;
    }

    /**
     * This method sets the related _user property.
     * @param User $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->_user = $user;
        return $this;
    }

    /**
     * This method returns the User element associated with this record.
     * @return User
     */
    public function getUser()
    {
        $user = null;
        if ( $this->_user !== null ) {
            $user = $this->_user;
        } elseif ( $this->userId ) {
            $user = Craft::$app->users->getUserById($this->userId);
        }
        return $user;
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|false|null
    {
        if ($handle === 'user') {
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');
            $map = (new Query())
                ->select(['id as source', 'userId as target'])
                ->from(['{{%linkvault_downloads}}'])
                ->where(['and', ['id' => $sourceElementIds], ['not', ['userId' => null]]])
                ->all();
            return [
                'elementType' => User::class,
                'map' => $map
            ];
        } elseif ($handle === 'asset') {
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');
            $map = (new Query())
                ->select(['id as source', 'assetId as target'])
                ->from(['{{%linkvault_downloads}}'])
                ->where(['and', ['id' => $sourceElementIds], ['not', ['assetId' => null]]])
                ->all();
            return [
                'elementType' => Asset::class,
                'map' => $map
            ];
        }
        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        if ($handle === 'user') {
            $user = $elements[0] ?? null;
            $this->setUser($user);
        } elseif ($handle === 'asset') {
            $asset = $elements[0] ?? null;
            $this->setAsset($asset);
        } else {
            parent::setEagerLoadedElements($handle, $elements, $plan);
        }
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
