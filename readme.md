# Link Vault for Craft CMS

<img src="https://github.com/masugadesign/link-vault-craft-cms/blob/master/src/icon.svg" width="100" height="100">

### Protect and track downloads on your site. Prevent and track leech attempts.
This is a commercial plugin for Craft CMS 4.

## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Settings](#settings)
* [Config Variables](#config-variables)
* [Template Variables](#template-variables)
* [Events](#events)

## Requirements

* Craft CMS v4.12.0+
* PHP 8.0.2+

## Installation

Add the following to your composer.json requirements. Be sure to adjust the version number to match the version you wish to install.

```
"masugadesign/linkvault": "4.0.5",
```

## Settings

**Leech Attempt Template**

This template will load with a 403 status whenever someone attempts to leech a download URL as long as leech blocking is enabled. Link Vault provides a default template to use if this setting is left blank.

**Missing File Template**

This template will load with a 404 status whenever someone attempts to download a file that doesn't exist. Link Vault provides a default template to use if this setting is left blank.

## Config Variables

Link Vault has a number of config variables that can be overridden by creating a linkvault.php file in your project's craft/config/ folder. The defaults are displayed below.

```
<?php

return array(
    // Set to "true" for additional logging.
    'debug' => false,
    // The route URI to use when generating download URLs.
    'downloadTrigger' => 'download',
    // Set to "true" to prevent file leeching.
    'blockLeechAttempts' => true,
    // Set to "true" to log leech attempts.
    'logLeechAttempts' => true
);
```

## Template Variables

### downloadUrl

The download URL accepts two parameters:

* file - This may either be an instance of an _Asset_ element or it may be a string path to a file on the server.
* additional parameters - This is an array of custom fields or other variables to be saved to the download record.

**Examples**

```
{# Example 1: Passing an Asset element instance. #}
{% for download in entry.downloadableAssets.all() %}
    <a href="{{ craft.linkvault.downloadUrl(download) }}" >Download This</a>
{% endfor %}

{# Example 2: A hard-coded full system path. #}
<a href="{{ craft.linkvault.downloadUrl('/home/user1337/www/uploads/songs/love.mp3') }}" >Download This</a>

{# Example 3: A full URL to a remote file. #}
<a href="{{ craft.linkvault.downloadUrl('http://example.com/downloads/art/cat-rides-bike.zip') }}" >Download This</a>
```

As you can see, passing an instance of an _Asset_ element is the simplest way to create a Link Vault download URL. This method also works for files stored on an S3 source.

Below are some examples that make use of the second parameter to pass along element IDs. In Craft, entries and assets have unique element IDs. It is entirely up to you what you store in the `linkvault_downloads.elementId` column as it is for informational purposes only. If the first parameter is a valid AssetD, Link Vault stores its ID in the `linkvault_downloads.assetId` column automatically.

```
{# Example 5: The asset's parent entry's ID is stored in the elementId column. #}
{% for download in entry.downloadableAssets.all() %}
    <a href="{{ craft.linkvault.downloadUrl(download, {elementId : entry.id}) }}" >Download This</a>
{% endfor %}

{# Example 6: The asset's ID is stored in the elementId column. #}
{% for download in entry.downloadableAssets.all() %}
    <a href="{{ craft.linkvault.downloadUrl(download, {elementId : download.id}) }}" >Download This</a>
{% endfor %}
```

You can create custom fields to store any data you like with Link Vault. These fields are created from Link Vault's _Custom Fields_ tab in the control panel. Once you do create a field, just use the field's handle in the array parameter.

```
{# Example 7: Passing along a value for a user-defined field. #}
{% for download in entry.downloadableAssets.all() %}
    <a href="{{ craft.linkvault.downloadUrl(download, {userEmail : craft.session.getUser().email}) }}" >Download This</a>
{% endfor %}
```

### zipUrl

The __zipUrl__ template variable generates a Link Vault download URL for a collection of files that will be zipped on-the-fly when the URL is followed. Each file is tracked individually in the logs. Zipping files on-the-fly could require a lot of memory depending on the size of the files being zipped. It is recommended to only use this feature on smaller files <10MB.

The first parameter may be an array of Craft Asset elements or Craft Asset IDs. The second parameter is the base name for the zip file that will be generated.

**Examples**

```
{% set assetIds = craft.assets.volume('misc').limit(5).ids() %}
<a href="{{ craft.linkvault.zipUrl(assetIds, 'some-misc-assets') }}" >Download Now</a>
```

### totalDownloads

The __totalDownloads__ variable returns the total downloads for a given set of criteria. It is very similar to the **downloadUrl** variable except it only has one parameter which can be one of three things:

* An instance of an _Asset_ element.
* An array of parameters
* A string containing the full system path to a file.

**Examples**

```
{# Example 8: Passing an Asset element. #}
{% for download in entry.downloadableAssets %}
    <p>The {{ download.filename }} file has been downloaded {{ craft.linkvault.totalDownloads(download) }} times!</p>
{% endfor %}

{# Example 9: Passing an array of parameters. #}
{% for download in entry.downloadableAssets %}
    <p>Your bird.txt Downloads: {{ craft.linkvault.totalDownloads({userId:craft.session.getUser().id, fileName:"bird.txt" }) }}</p>
{% endfor %}

{# Example 10: Passing a string containing the full system path. #}
Total face.gif downloads: {{ craft.linkvault.totalDownloads('/home/user1337/www/uploads/face.gif') }}

{# Example 11: Passing a URL. #}
Downloads: {{ craft.linkvault.totalDownloads('https://example.com/documents/contract.docx') }}
```

### fileSize

The __fileSize__ template variable fetches a human-readable file size string for a specified file. This can be used for server files not stored in Craft as assets though it will work with asset files as well. For asset elements, the native `{{ file.size|filesize }}` may be preferable.

**Examples**

```
{# Example 12: Passing a file path. #}
bees.jpg is {{ craft.linkvault.fileSize('/home/user1337/hidden-files/bees.jpg') }}.

{# Example 13: Passing an instance of an Asset element. #}
{{ file.filename }} is {{ craft.linkvault.fileSize(file) }}.

{# Example 14: Passing a URL. WARNING: The fileSize variable is sometimes inconsistent with remote files any may not always return a file size. #}
{{ craft.linkvault.fileSize('https://example.com/songs/dance-me-to-the-end-of-love.flac') }}
```

### baseTwoFileSize (Added in v3.1.2)

The __baseTwoFileSize__ template variable is an alias of the __fileSize__ template variable. _1024_ is used as the divisor.

### baseTenFileSize (Added in v3.1.2)

The __baseTenFileSize__ template variable is similar to __fileSize__ and __baseTwoFileSize__ except the file size is calculated using _1000_ as the divisor.

### downloads

The __downloads__ template variable fetches download records based on the specified criteria.

**Examples**

```
{# Example 15: Fetch the ten most recent download records for cheese.mpg. #}
{% for record in craft.linkvault.downloads.fileName('cheese.mpg).limit(10).all() %}
    <p>User {{ record.userId }} downloaded it on {{ record.dateCreated }}</p>
{% endfor %}

{# Example 16: Fetch 5 most recent downloads that occurred prior to March 1, 2016. #}
{% for record in craft.linkvault.downloads.before('2016-03-01').limit(10).all() %}
    <p>User {{ record.userId }} downloaded {{ record.filename }} before March 1.</p>
{% endfor %}

{# Example 17: Fetch records based on custom field value. In this example, assume existence of "downloadPage" field. #}
{% for record in craft.linkvault.downloads.downloadPage('super-mega-rockstar/free-songs').all() %}
    <p>User {{ record.userId }} downloaded {{ record.filename }} song file on {{ record.dateCreated }}</p>
{% endfor %}
```

### leechAttempts

The __leechAttempts__ template variable works in the exact same manner as the __downloads__ variable except that it only return leech attempt records.

### records

The __records__ template variables works in the same manner as __downloads__ and __leechAttempts__ variables except it will return all records regardless of type.

### groupCount (Added in v1.0.2)

The __groupCount__ template variable queries record counts and groups them by a particular column name.

```
{# Example 18: Fetch a particular user's top five file downloads by `fileName`. Order the results by the count, descending. #}
{% set topDownloads = craft.linkvault.groupCount('fileName', {
    'userId' : currentUser.id
}, 'COUNT(*) desc', 5) %}
<ol>
{% for topDownload in topDownloads %}
    <li>{{ topDownload.fileName }} ({{ topDownload.census|number_format(0) }} downloads)</li>
{% endfor %}
</ol>
```

### customFields (Added in v3.2.0)

The __customFields__ template variable allows for querying Link Vault's defined custom fields.

```
{% set myField = craft.linkvault.customFields.fieldName('myField').one() %}

{% set allFields = craft.linkvault.customFields.all() %}
```

## Events

**LinkVaultDownload** elements inherit all the standard Craft element events. Below is an example of the [craft\base\Element::EVENT\_BEFORE\_SAVE](https://github.com/craftcms/cms/blob/3.4.15/src/base/Element.php#L232) event:

```
<?php

use craft\events\ModelEvent;
use Masuga\LinkVault\elements\LinkVaultDownload;
use yii\base\Event;

Event::on(LinkVaultDownload::class,
    LinkVaultDownload::EVENT_BEFORE_SAVE,
    function (ModelEvent $event) {
        $linkVaultDownloadElement = $event->sender;
        $isNew = $event->isNew;
});
```

Additionally, Link Vault contains the following events:

### LinkClickEvent (Added in v3.1.0)

This event is triggered immediately after someone clicks/follows a Link Vault URL and the encrypted parameters are decrypted.

```
<?php

use Masuga\LinkVault\controllers\LinkVaultController;
use Masuga\LinkVault\events\LinkClickEvent;
use yii\base\Event;

Event::on(LinkVaultController::class,
    LinkVaultController::EVENT_LINK_CLICK,
    function (LinkClickEvent $event) {
	    $event->parameters['additional_parameter'] = 'a value';
        if ( $event->parameters['assetId'] == 5 ) {
            ...
        }
});
```

### ModifyZipUrlFilesEvent (Added in v3.1.0)

This event is triggered immediately before the Link Vault zipUrl tag creates an on-the-fly zip file. The event allows for adding files or removing files from the array of files to be zipped.

```
<?php

use Masuga\LinkVault\events\ModifyZipUrlFilesEvent;
use Masuga\LinkVault\services\ArchiveService;
use yii\base\Event;

...

Event::on(ArchiveService::class,
    ArchiveService::EVENT_MODIFY_ZIP_URL_FILES,
    function (ModifyZipUrlFilesEvent $event) {
	    $event->files[] = '/path/to/file.jpg';
        $event->files[] = 5; // Asset ID
});
```
