<?php

namespace Masuga\LinkVault\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\LinkVaultDownload;

class LinkVaultDownloadQuery extends ElementQuery
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

	/**
 	* @inheritdoc
 	*/
	protected $defaultOrderBy = ['linkvault_downloads.dateCreated' => SORT_DESC];

	/**
	 * Override the established __set() method so we can add properties on-the-fly
	 * from the init() method. Remember that Link Vault custom fields are not
	 * true Craft custom fields.
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->{$name} = $value;
	}

	public function init()
	{
		// Initialize class properties for the Link Vault custom fields.
		$customFields = LinkVault::getInstance()->customFields->fetchAllCustomFields('fieldName');
		foreach($customFields as $index => &$customField) {
			$this->{$customField->fieldName} = null;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function with($value)
	{
		$this->with = $value;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	protected function beforePrepare(): bool
	{
		$this->joinElementTable('linkvault_downloads');

		$selectsArray = [
			'linkvault_downloads.userId',
			'linkvault_downloads.elementId',
			'linkvault_downloads.assetId',
			'linkvault_downloads.fileName',
			'linkvault_downloads.dirName',
			'linkvault_downloads.s3Bucket',
			'linkvault_downloads.type',
			'linkvault_downloads.isUrl'
		];
		$customFields = LinkVault::getInstance()->customFields->fetchAllCustomFields('fieldName');
		// Add each user-defined field to the query and check to see if the field is included in the criteria.
		foreach($customFields as $name => &$customField) {
			$selectsArray[] = 'linkvault_downloads.'.$name;
		}
		$this->query->select($selectsArray);

		if ($this->userId) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.userId', $this->userId));
		}
		if ($this->elementId) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.elementId', $this->elementId));
		}
		if ($this->assetId) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.assetId', $this->assetId));
		}
		if ($this->type) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.type', $this->type));
		}
		if ($this->s3Bucket) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.s3Bucket', $this->s3Bucket));
		}
		if ($this->googleBucket) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.googleBucket', $this->googleBucket));
		}
		if($this->dirName) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.dirName', $this->dirName));
		}
		if($this->fileName) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.fileName', $this->fileName));
		}
		if($this->isUrl) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.isUrl', $this->isUrl));
		}
		if($this->remoteIP) {
			$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.remoteIP', $this->remoteIP));
		}
		if ($this->after) {
			$query->andWhere(DbHelper::parseDateParam('linkvault_downloads.dateCreated', '>='.$this->after));
		}
		if ($this->before) {
			$query->andWhere(DbHelper::parseDateParam('linkvault_downloads.dateCreated', '<'.$this->before));
		}
		if ($this->dateCreated) {
			$query->andWhere(DbHelper::parseDateParam('linkvault_downloads.dateCreated', $this->dateCreated));
		}
		foreach($customFields as $name => &$customField) {
			if ( !empty($this->{$name}) ) {
				$this->subQuery->andWhere(Db::parseParam('linkvault_downloads.'.$name, $this->{$name}));
			}
		}
		return parent::beforePrepare();
	}

	/**
	 * @inheritdoc
	 */
	public function populate($rows)
	{
		$elements = parent::populate($rows);
		$customFields = LinkVault::getInstance()->customFields->fetchAllCustomFields('fieldName');
		// We need to manually populate the Link Vault custom field attributes on each element.
		foreach($elements as $index => &$element) {
			foreach($customFields as $name => &$customField) {
				$element->{$name} = $rows[$index][$name] ?? null;
			}
		}
		return $elements;
	}
}
