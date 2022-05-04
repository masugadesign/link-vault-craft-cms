# Changelog

## 4.0.0 - 2022-05-04

### Changed
- Updates to support Craft 4.0

## 3.2.0 - 2021-10-13

### Added
- Added `customFields` template variable for querying defined `LinkVaultCustomField` elements.
- Added confirmation dialog for saved report deletion.

### Changed
- Redesigned the record history filter tool.
- Filtered columns appended to the report results table now have a different background color than the default columns.

### Fixed
- Link Vault custom field values are now included in the CSV export.
- Saved Reports side nav active state now reflects currently selected report.

## 3.1.7 - 2021-02-26

### Fixed
- Fixed the `type` criteria parameter conflict in the query used by the Link Vault Top Downloads Widget.
- Removed the default colspan for the widget as it appears to override the configured column span value.

## 3.1.6 - 2021-01-26

### Changed
- The `groupCount` method in `General` now has a default limit of _null_ rather than 100.

### Fixed
- Fixed `groupCount` template variable method parameters to match corresponding method in the `General` service class.
- Fixed `groupCount` method in `General` service class to omit soft-deleted records.

## 3.1.5 - 2020-10-20

### Added
- Added a confirmation dialog before deleting records in the control panel.

### Fixed
- Records will no longer be deleted if none are checked at all.

## 3.1.4 - 2020-08-13

### Changed
- **Masuga\LinkVault\services\GeneralService::download()** now returns an instance of **craft\web\Response** or _null_ if the download request could not be handled.
- Link Vault now defaults to Craft's native 403/404 response handling if custom error templates are not set in Link Vault's plugin settings.

## 3.1.3 - 2020-07-31

### Fixed
- Fixed issues with the Craft 2 to Craft 3 upgrade process and added correcting migrations.

## 3.1.2.1 - 2020-04-27

### Fixed
- Fixed the base two divisor which should be _1024_, not _1014_.

## 3.1.2 - 2020-04-27

### Added
- Added the **baseTwoFileSize** and **baseTenFileSize** template variables.

### Fixed
- Fixed two deprecation errors.

## 3.1.1 - 2020-04-16

### Fixed
- Fixed the truncation of long folder paths in the table view.

## 3.1.0 - 2020-04-15

### Added
- Added a reporting tool to the control panel.
- Added the **ModifyZipUrlFilesEvent** event class.
- Added the **LinkClickEvent** event class.

### Fixed
- Fixed numerous deprecation errors in control panel templates.
- Fixed date parameters issues that occurred when querying download records.
- Fixed the installation migration to properly drop the custom fields table on uninstall.
- Fixed a bug where element arrays were treated as objects.

### Changed
- Changed the name of the **FrontEndController** to **LinkVaultController**.

### Removed
- Removed the **LinkVaultDownload** element index page.
- Removed searchable columns from **LinkVaultDownload** element.

## 3.0.5.7 - 2020-04-07

### Fixed
- Fixed bug with **zipUrl** template variable where the array contains assets (or asset IDs) followed by one or more hard-coded file paths.

## 3.0.5.6 - 2020-04-07

### Fixed
- Fixed deprecated log level reference that caused a logging error when Link Vault determined a file was missing.
- Wrapped a set_time_limit() call in function_exists() since that function can be disabled in php.ini.

## 3.0.5.5 - 2020-03-25

### Fixed
- Fixed bug for Link Vault'd remote URL redirects.

## 3.0.5.4 - 2019-10-17

### Fixed
- **FrontEndController** now calls `parent::init()` so Craft can convert the boolean _allowAnonymous_ property into an integer. This fixes an issue that prevented unauthenticated users from downloading files.

## 3.0.5.3 - 2019-03-01

### Fixed
- Fixed issue when upgrading Craft from v2 to v3.1. The upgrade process referenced the plugins table _settings_ column, which no longer exists.
- Fixed the LinkVaultDownload element's _tableAttributeHtml_ method so it never returns a NULL value.

## 3.0.5.2 - 2018-11-13

### Fixed
- Fixed multi-site localization of download elements.

## 3.0.5.1 - 2018-09-26

### Fixed
- Fixed installation migration so it adds potential missing columns from old versions of Link Vault.

## 3.0.5 - 2018-09-21

### Fixed
- Fixed __records__ and __downloads__ template variable parameter setters.
- Fixed issues with the user downloads report page in the control panel.

## 3.0.4 - 2018-09-04

### Fixed
- Fixed zipUrl template variable logic for creating zip archives on-the-fly.
- Fixed Link Vault's plugin logging.
- Removed LinkVaultDownload::getAsset() return type in case the asset no longer exists and null is returned.

## 3.0.3 - 2018-08-30

### Fixed
- Fixed table name references where the designated table prefix was not taken into consideration.

## 3.0.2 - 2018-08-30

### Fixed
- Fixed the table column header output for ID.

### Removed
- Removed the user download page from the control panel until template is updated for Craft 3.

## 3.0.1 - 2018-07-23

### Added
- Added the CHANGELOG.md file.

## 3.0.0 - 2018-07-23
