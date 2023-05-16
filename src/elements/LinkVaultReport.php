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
use Masuga\LinkVault\elements\db\LinkVaultReportQuery;
use Masuga\LinkVault\records\LinkVaultReportRecord;

class LinkVaultReport extends Element
{
	public $criteria = null;
	public $orderBy = null;
	public $sort = null;
	public $after = null;
	public $before = null;

	/**
	 * The instance of the Link Vault plugin.
	 * @var LinkVault
	 */
	private $plugin = null;

	public function init(): void
	{
		$this->plugin = LinkVault::getInstance();
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
		return new LinkVaultReportQuery(static::class);
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
				'key'	  => '*',
				'label'	=> Craft::t('linkvault', 'All Reports'),
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
		return [
			'id' => Craft::t('linkvault', 'ID'),
			'dateCreated' => Craft::t('linkvault', 'Date'),
			'title' => Craft::t('linkvault', 'Title')
		];
	}

	/**
	 * @inheritDoc
	 */
	protected static function defineDefaultTableAttributes(string $source): array
	{
		return ['id', 'dateCreated', 'title'];
	}

	/**
 	* @inheritdoc
 	*/
	protected static function defineSortOptions(): array
	{
		return [
			'elements.dateCreated' => Craft::t('app', 'Date Created'),
			'title' => Craft::t('linkvault', 'Title'),
		];
	}

	/**
	 * @inheritDoc IElementType::defineSearchableAttributes()
	 * @return array
	 */
	protected static function defineSearchableAttributes(): array
	{
		return ['title', 'criteria'];
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
		$html = \Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
			[
				'label' => \Craft::t('app', 'Report Title'),
				'siteId' => $this->siteId,
				'id' => 'title',
				'name' => 'title',
				'value' => $this->title,
				'errors' => $this->getErrors('title'),
				'first' => true,
				'autofocus' => true,
				'required' => true
			]
		]);
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
	public function afterSave(bool $isNew): void
	{
		if ( $isNew ) {
			$record = new LinkVaultReportRecord;
			$record->id = $this->id;
		} else {
			$record = LinkVaultReportRecord::findOne($this->id);
			if (!$record) {
				throw new Exception('Invalid report ID: '.$this->id);
			}
		}
		$record->criteria = $this->criteria;
		$record->orderBy = $this->orderBy;
		$record->sort = $this->sort;
		$status = $record->save();
		parent::afterSave($isNew);
	}

	/**
	 * This method returns the Link Vault report's control panel URL.
	 * @return string
	 */
	public function getUrl(): string
	{
		$params = [
			'criteria' => json_decode($this->criteria, true),
			'orderBy' => $this->orderBy,
			'sort' => $this->sort,
			'reportId' => $this->id
		];
		return UrlHelper::cpUrl('linkvault/reports').'?'.http_build_query($params);
	}

	/**
	 * This method returns the report criteria as an array suitable for the report
	 * form.
	 * @return array
	 */
	public function getCriteriaArray(): array
	{
		$criteria = $this->criteria ? json_decode($this->criteria, true) : [];
		return $criteria;
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
