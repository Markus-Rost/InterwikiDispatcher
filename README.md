# InterwikiDispatcher extension

This is free software licensed under the GNU General Public License. Please
see http://www.gnu.org/copyleft/gpl.html for further details, including the
full text and terms of the license.

## Overview
Adding some simple multi-level interwikis for easier linking to wiki farms.

Hooking into existing interwiki prefixes to provide some limited API support. Subdomain part of the interwiki is validated as `a-z\d-` to avoid open redirect vulnerabilities. Scary transclusion will work when enabled for the interwiki prefix.

## Example configs
```php
# [[w:c:minecraft]] => https://minecraft.fandom.com/wiki/
# [[w:c:minecraft:Cookie]] => https://minecraft.fandom.com/wiki/Cookie
# [[w:c:de.minecraft:Keks]] => https://minecraft.fandom.com/de/wiki/Keks
$wgIWDPrefixes[] = [
  'interwiki' => 'w', # Interwiki prefix that exists in the interwiki table
  'subprefix' => 'c', # Optional: Subprefix to keep the base interwiki working mostly as expected
  'url' => 'https://$2.fandom.com/wiki/$1', # URL format for the interwiki `w:c:$2:$1`
  'urlInt' => 'https://$2.fandom.com/$3/wiki/$1', # Optional: Additional URL format `w:c:$3.$2:$1`
  'dbname' => '$2_en', # Optional: Checking `$wgLocalDatabases` if the wiki exists
  'dbnameInt' => '$2_$3', # Optional: Checking `$wgLocalDatabases` if the wiki exists
  'baseTransOnly' => true, # Optional: Fall back to the base interwiki for scary transclusion
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
  [
    'interwiki' => 'farm',
    'subprefix' => 'mh',
    'url' => 'https://$2.miraheze.org/wiki/$1',
  ],
  # [[farm:fd:minecraft]] => https://minecraft.fandom.com/wiki/
  # [[farm:fd:minecraft:Cookie]] => https://minecraft.fandom.com/wiki/Cookie
  # [[farm:fd:de.minecraft:Keks]] => https://minecraft.fandom.com/de/wiki/Keks
  [
    'interwiki' => 'farm',
    'subprefix' => 'fd',
    'url' => 'https://$2.fandom.com/wiki/$1',
    'urlInt' => 'https://$2.fandom.com/$3/wiki/$1',
  ],
  # [[farm:gg:terraria]] => https://terraria.wiki.gg/wiki/
  # [[farm:gg:terraria:NPCs]] => https://terraria.wiki.gg/wiki/NPCs
  # [[farm:gg:de.terraria:NPCs]] => https://terraria.wiki.gg/de/wiki/NPCs
  [
    'interwiki' => 'farm',
    'subprefix' => 'gg',
    'url' => 'https://$2.wiki.gg/wiki/$1',
    'urlInt' => 'https://$2.wiki.gg/$3/wiki/$1',
  ],
];
```