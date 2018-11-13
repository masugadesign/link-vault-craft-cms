# Changelog

## 3.0.6 - YYYY-MM-DD

### Added
- Added a reporting tool to the control panel.

### Fixed
- Fixed numerous deprecation errors in control panel templates.
- Fixed date parameters issues that occurred when querying download records.
- Fixed the installation migration to properly drop the custom fields table on uninstall.

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
