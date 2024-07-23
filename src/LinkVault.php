<?php

namespace Masuga\LinkVault;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\FileHelper;
use craft\services\Dashboard;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;
use Masuga\LinkVault\elements\LinkVaultCustomField;
use Masuga\LinkVault\models\Settings;
use Masuga\LinkVault\services\ArchiveService;
use Masuga\LinkVault\services\CustomFieldsService;
use Masuga\LinkVault\services\ExportService;
use Masuga\LinkVault\services\FilesService;
use Masuga\LinkVault\services\GeneralService;
use Masuga\LinkVault\services\ReportsService;
use Masuga\LinkVault\variables\LinkVaultVariable;
use Masuga\LinkVault\widgets\LinkVaultTopDownloadsWidget;

class LinkVault extends Plugin
{

    /**
     * Enables the CP sidebar nav link for this plugin. Craft loads the plugin's
     * index template by default.
     * @var boolean
     */
    public bool $hasCpSection = true;

    /**
     * Enables the plugin settings form.
     * @var boolean
     */
    public bool $hasCpSettings = true;

    /**
     * The name of the plugin as it appears in the Craft control panel and
     * plugin store.
     * @return string
     */
    public function getName()
    {
         return Craft::t('linkvault', 'Link Vault');
    }

    /**
     * The brief description of the plugin that appears in the control panel
     * on the plugin settings page.
     * @return string
     */
    public function getDescription(): string
    {
        return Craft::t('linkvault', 'Protect and track downloads on your site. Prevent and track leech attempts.');
    }

    /**
     * This method returns the plugin's Settings model instance.
     * @return Settings
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * This method returns the settings form HTML content.
     * @return string
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('linkvault/_settings', [
            'settings' => $this->getSettings()
        ]);
    }

    /**
     * The plugin's initialization function is responsible for registering event
     * handlers, routes and other plugin components.
     */
    public function init(): void
    {
        parent::init();
        $downloadTrigger = $this->getSettings()->downloadTrigger;
        // Initialize each of the services used by this plugin.
        $this->setComponents([
            'archive' => ArchiveService::class,
            'customFields' => CustomFieldsService::class,
            'export' => ExportService::class,
            'files' => FilesService::class,
            'general' => GeneralService::class,
            'reports' => ReportsService::class
        ]);
        // Register the site front-end routes.
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) use($downloadTrigger) {
            $event->rules[$downloadTrigger] = 'linkvault/link-vault/serve';
        });
        // Register the control panel routes.
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['linkvault'] = 'linkvault/downloads/download-index';
            $event->rules['linkvault/user'] = 'linkvault/downloads/user-downloads';
            $event->rules['linkvault/reports'] = 'linkvault/reports/index';
            $event->rules['linkvault/reports/examples'] = 'linkvault/reports/examples';
            $event->rules['linkvault/reports/delete'] = 'linkvault/reports/delete';
            $event->rules['linkvault/reports/delete-records'] = 'linkvault/reports/delete-records';
            $event->rules['linkvault/reports/save-report'] = 'linkvault/reports/save-report';
            $event->rules['linkvault/export'] = 'linkvault/reports/export-csv';
            $event->rules['linkvault/customfields'] = 'linkvault/custom-fields/custom-fields';
            $event->rules['linkvault/customfields/new'] = 'linkvault/custom-fields/custom-field-form';
            $event->rules['linkvault/customfields/create'] = 'linkvault/custom-fields/custom-field-submit';

            $event->rules['linkvault/reports/field-filter-options'] = 'linkvault/reports/field-filter-options';
            $event->rules['linkvault/reports/value-field'] = 'linkvault/reports/value-field';
        });
        // Generate the encryption key that is unique to this installation.
        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN, function (PluginEvent $event) {
            if ($event->plugin === $this) {
                $initialSettings = ['encryptionKey' => Craft::$app->getSecurity()->generateRandomString(32)];
                Craft::$app->getPlugins()->savePluginSettings($this, $initialSettings);
            }
        });
        // Register the Link Vault template variable.
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('linkvault', LinkVaultVariable::class);
        });
        // Register the plugin dashboard widgets.
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = LinkVaultTopDownloadsWidget::class;
        });
        Event::on(LinkVaultCustomField::class, LinkVaultCustomField::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
            $source = $event->source;
            $actions = $event->actions;
            // Remove the default `Edit` and `Duplicate` actions. Totally uncalled for. Craft 4 adds them. Why?
            unset($event->actions[0], $event->actions[1]);
        });
    }

}
