# eas-api

microsoft exchange active sync implementation

## DESCRIPTION

all data is stored in json-files in directory data/user-id/collection-id.

## REQUIREMENTS

- apache
- php
- php-xml

## CONFIG

apache web-server need some config. activesync needs Alias /Microsoft-Server-ActiveSync to point to location of eas-api. autodiscover can be set up optionally.

### APACHE

	Alias /Microsoft-Server-ActiveSync /var/www/active-sync/index.php
	Alias /autodiscover/autodiscover.xml /var/www/active-sync/index.php
	Alias /Autodiscover/Autodiscover.xml /var/www/active-sync/index.php

