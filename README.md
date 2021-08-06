# do-a-backup

A small PHP class to compress and upload MySQL databases and directories via FTP.

## Usage

Get with composer:

```
composer require corbpie/do-a-backup
```

**Edit in your FTP and MySQL credentials lines 6-12 ```doABackup.php```.**

#### Compressing directory to a zip file and uploading with FTP:

```php
<?php
require_once('vendor/autoload.php');

$bu = new doABackup();

$bu->backupDirectory('../images/', 'images', 'server1_images');
```

This will compress the images directory into ```images_2021-08-06-01-36.zip``` (date format config line 14
doABackup.php) and upload to ```server1_images/```.

#### Compressing MySQL database to a .sql.gz file and uploading with FTP:

```php
<?php
require_once('vendor/autoload.php');

$bu = new doABackup();

$bu->$bu->backupMySQL('billing', 'db_users');
```

This will compress the billing database into ```billing_2021-08-06-01-36.sql.gz``` and upload to ```db_users/```.

