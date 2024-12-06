# InterwikiDispatcher extension

This is free software licensed under the GNU General Public License. Please
see http://www.gnu.org/copyleft/gpl.html for further details, including the
full text and terms of the license.

## Overview
The **InterwikiDispatcher** extension adds some simple multi-level interwikis for easier linking to wiki farms.

Hooking into existing interwiki prefixes to provide some limited API support. Subdomain part of the interwiki is validated as `a-z\d-` to avoid open redirect vulnerabilities. Scary transclusion will work when enabled for the interwiki prefix.

## Installation
* Download, extract, and place the files in a directory called `InterwikiDispatcher` in your `extensions/` folder.
* Add the following code at the bottom of your [LocalSettings.php](https://www.mediawiki.org/wiki/Manual:LocalSettings.php) file: 
```php
wfLoadExtension( 'InterwikiDispatcher' );
```
* Configure as required.
* Done – Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

## Configuration
`$wgIWDPrefixes` is the sole variable controlling this extension's behaviour. The format is an array (keys do not matter) where values are associative arrays of parameters. These parameters are:
* `'interwiki'` **(required)**: the interwiki prefix to apply this rule to.
  * This prefix must be a valid and defined interwiki prefix.
* `'subprefix'`: optional sub-prefix, which will be expected right after the interwiki prefix. For example a prefix of `'p'` and sub-prefix of `'s'` results in links of format `[[p:s:wiki:article]]` and `[[p:s:language.wiki:article]]`.
* `'url'` **(required)**: external URL format. Placeholder `$1` stands for page title, `$2` for wiki domain.
  * *Example:* `'https://$2.fandom.com/wiki/$1'`
* `'urlInt'`: optional international external URL format. Placeholder `$3` stands for the language.
  * *Example:* `'https://$2.fandom.com/$3/wiki/$1'`
* `'baseTransOnly'`: if `true`, falls back to the base, classic interwiki during scary transclusion.
  * *Default:* `false`
* `'dbname'`: optional wiki ID format, used to check whether a wiki exists locally via [$wgLocalDatabases](https://www.mediawiki.org/wiki/Manual:$wgLocalDatabases). Placeholder `$2` is the wiki domain.
  * *Example:* `'$2_en'`
* `'dbnameInt'`: optional international wiki ID format, used to check whether a wiki exists locally via [$wgLocalDatabases](https://www.mediawiki.org/wiki/Manual:$wgLocalDatabases). Placeholder `$2` is the wiki domain, `$3` is the language.
  * *Example:* `'$2_$3'`
* `'wikiExistsCallback'`: optional custom function to check if a wiki exists. This completely replaces the DB check of `dbname`, `dbnameInt`.
  * `function ( $rule, &$wiki, &$language ): bool`: Takes this array as `$rule`, domain as `&$wiki`, language as `&$language` (or empty string). Both `$wiki` and `$language` are out parameters: the callback may override them as needed.

### Examples
```php
# [[w:c:minecraft]] => https://minecraft.fandom.com/wiki/
# [[w:c:minecraft:Cookie]] => https://minecraft.fandom.com/wiki/Cookie
# [[w:c:de.minecraft:Keks]] => https://minecraft.fandom.com/de/wiki/Keks
$wgIWDPrefixes[] = [
  'interwiki' => 'w', # Interwiki prefix that exists in the interwiki table
  'subprefix' => 'c', # Optional: Subprefix to keep the base interwiki working mostly as expected
  'url' => 'https://$2.fandom.com/wiki/$1', # URL format for the interwiki `w:c:$2:$1` ($1: page title, $2: domain)
  'urlInt' => 'https://$2.fandom.com/$3/wiki/$1', # Optional: Additional URL format `w:c:$3.$2:$1` ($3: language).
  'baseTransOnly' => true, # Optional: Fall back to the base interwiki for scary transclusion
  'dbname' => '$2_en', # Optional: Wiki ID format to check existence via `$wgLocalDatabases`, subdomain as $2, language unspecified
  'dbnameInt' => '$2_$3', # Optional: As above, but language provided as $3
  'wikiExistsCallback' => null, # Optional: Custom function to check if the wiki exists, replaces the DB check.
                                # Takes this array as `$rule`, domain as `&$wiki`, language as `&$language` (or empty string).
];
```
```php
# [[gg:terraria]] => https://terraria.wiki.gg/wiki/
# [[gg:terraria:NPCs]] => https://terraria.wiki.gg/wiki/NPCs
# [[gg:de.terraria:NPCs]] => https://terraria.wiki.gg/de/wiki/NPCs
$wgIWDPrefixes[] = [
  'interwiki' => 'gg',
  'url' => 'https://$2.wiki.gg/wiki/$1',
  'urlInt' => 'https://$2.wiki.gg/$3/wiki/$1',
  'dbname' => '$2_en',
  'dbnameInt' => '$2_$3',
];
```
```php
# [[mh:meta]] => https://meta.miraheze.org/wiki/
# [[mh:meta:Miraheze]] => https://meta.miraheze.org/wiki/Miraheze
$wgIWDPrefixes[] = [
  'interwiki' => 'mh',
  'url' => 'https://$2.miraheze.org/wiki/$1',
];
```
```php
$wgIWDPrefixes = [
  # [[farm:mh:meta]] => https://meta.miraheze.org/wiki/
  # [[farm:mh:meta:Miraheze]] => https://meta.miraheze.org/wiki/Miraheze
  'miraheze' => [
    'interwiki' => 'farm',
    'subprefix' => 'mh',
    'url' => 'https://$2.miraheze.org/wiki/$1',
  ],
  # [[farm:fd:minecraft]] => https://minecraft.fandom.com/wiki/
  # [[farm:fd:minecraft:Cookie]] => https://minecraft.fandom.com/wiki/Cookie
  # [[farm:fd:de.minecraft:Keks]] => https://minecraft.fandom.com/de/wiki/Keks
  'fandom' => [
    'interwiki' => 'farm',
    'subprefix' => 'fd',
    'url' => 'https://$2.fandom.com/wiki/$1',
    'urlInt' => 'https://$2.fandom.com/$3/wiki/$1',
  ],
  # [[farm:gg:terraria]] => https://terraria.wiki.gg/wiki/
  # [[farm:gg:terraria:NPCs]] => https://terraria.wiki.gg/wiki/NPCs
  # [[farm:gg:de.terraria:NPCs]] => https://terraria.wiki.gg/de/wiki/NPCs
  'wikigg' => [
    'interwiki' => 'farm',
    'subprefix' => 'gg',
    'url' => 'https://$2.wiki.gg/wiki/$1',
    'urlInt' => 'https://$2.wiki.gg/$3/wiki/$1',
  ],
];
```

## Special:Interwiki integration
An additional table for "Multi-level interwiki prefixes" will be added to Special:Interwiki listing the interwikis managed by this extension.
