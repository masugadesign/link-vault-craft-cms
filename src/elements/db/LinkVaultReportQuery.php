<?php

namespace Masuga\LinkVault\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use Masuga\LinkVault\LinkVault;
use Masuga\LinkVault\elements\LinkVaultReport;

class LinkVaultReportQuery extends ElementQuery
{

    public $criteria = null;
    //public $orderBy = null;
    //public $sort = null;
    public $after = null;
    public $before = null;

    /**
     * The instance of the Link Vault plugin.
     * @var LinkVault
     */
    private $plugin = null;

    /**
     * @inheritdoc
     */
    protected array $defaultOrderBy = ['linkvault_reports.dateCreated' => SORT_DESC];

    public function init(): void
    {
        parent::init();
        $this->plugin = LinkVault::getInstance();
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('linkvault_reports');

        $selectsArray = [
            'linkvault_reports.criteria',
            'linkvault_reports.orderBy',
            'linkvault_reports.sort',
        ];
        $this->query->select($selectsArray);

        if ($this->title) {
            $this->subQuery->andWhere(Db::parseParam('linkvault_reports.title', $this->title));
        }
        if ($this->criteria) {
            $this->subQuery->andWhere(Db::parseParam('linkvault_reports.criteria', $this->criteria));
        }
        //if ($this->orderBy) {
        //    $this->subQuery->andWhere(Db::parseParam('linkvault_reports.orderBy', $this->orderBy));
        //}
        //if ($this->sort) {
        //    $this->subQuery->andWhere(Db::parseParam('linkvault_reports.sort', $this->sort));
        //}
        if ($this->after) {
            $this->subQuery->andWhere(Db::parseDateParam('linkvault_reports.dateCreated', '>='.$this->after));
        }
        if ($this->before) {
            $this->subQuery->andWhere(Db::parseDateParam('linkvault_reports.dateCreated', '<'.$this->before));
        }
        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('linkvault_reports.dateCreated', $this->dateCreated));
        }
        return parent::beforePrepare();
    }

}
