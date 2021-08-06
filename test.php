<?php
require_once('vendor/autoload.php');

$bu = new doABackup();

if ($bu->backupDirectory('../images/', 'images', 'server1_images')) {
    echo "It worked";//images directory uploaded to server1_images/
} else {
    echo "Did NOT work";
}

if ($bu->backupMySQL('billing', 'db_users')) {
    echo "It worked";//billing MySQL database uploaded to db_users/
} else {
    echo "Did NOT work";
}