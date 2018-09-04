<?php

namespace Masuga\LinkVault\elements;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Delete;
use craft\elements\Asset;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\db\LinkVaultDownloadQuery;
use Masuga\LinkVault\records\LinkVaultDownloadRecord;

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

	public function init()
	{
		$customFields = LinkVault::getInstance()->customFields->fetchAllCustomFields('fieldName');
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
		return true;
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
		return ['dirName', 'fileName'];
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
			case 'assetId':
				$asset = $this->asset;
				if ( $asset ) {
					$display = ! empty($asset->url) ? '<a href="'.$asset->url.'" >'.$asset->title.'</a>' : $asset->title;
				} else {
					$display = $this->$attribute;
				}
				return $display;
			case 'userId':
				$user = $this->user;
				//return isset($user->username) ? '<a href="'.UrlHelper::cpUrl('linkvault/user', ['userId' => $user->id]).'" >'.$user->username.'</a>' : '--';
				return isset($user->username) ? $user->username : '--';
			case 'elementId':
				$element = $this->relatedElement;
				if ( $element ) {
					$title = (string)$element ?: '--';
					$url = $this->getCpEditUrl();
					$output = $url ? '<a href="'.$url.'" >'.$title.'</a>' : $title;
				} else {
					$output = '--';
				}
				return $output;
			case 'dirName':
				if ( $this->s3Bucket ) {
					$dir = $this->s3Bucket.':'.$this->$attribute;
				} elseif ( $this->googleBucket ) {
					$dir = $this->googleBucket.':'.$this->$attribute;
				} else {
					$dir = $this->$attribute;
				}
				return $dir;
			case 'id':
				return $this->$attribute;
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
			Delete::class
		];
	}

	/**
 	* @inheritdoc
 	* @throws Exception if existing record is not found.
 	*/
	public function afterSave(bool $isNew)
	{
		if ( $isNew ) {
			$record = new LinkVaultDownloadRecord;
			$record->id = $this->id;
		} else {
			$record = LinkVaultDownloadRecord::findOne($this->id);
			if (!$record) {
				throw new Exception('Invalid download ID: '.$this->id);
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
		$customFields = LinkVault::getInstance()->customFields->fetchAllCustomFields('fieldName');
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
	public static function eagerLoadingMap(array $sourceElements, string $handle)
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
	public function setEagerLoadedElements(string $handle, array $elements)
	{
		if ($handle === 'user') {
			$user = $elements[0] ?? null;
			$this->setUser($user);
		} elseif ($handle === 'asset') {
			$asset = $elements[0] ?? null;
			$this->setAsset($asset);
		} else {
			parent::setEagerLoadedElements($handle, $elements);
		}
	}

}
