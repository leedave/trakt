<?php
$arrUpdate = [
    "CREATE TABLE IF NOT EXISTS `".trakt_mysqlTableTraktCollection."`("
    . "`id` INT(11) NOT NULL AUTO_INCREMENT, "
    . "`title` VARCHAR(255) NOT NULL, "
    . "`slug` VARCHAR(255) NOT NULL, "
    . "`imdb` VARCHAR(255), "
    . "`tmdb` INT(11), "
    . "`tvdb` INT(11), "
    . "`tvrage` INT(11), "
    . "`traktId` VARCHAR(255) NOT NULL, "
    . "`year` INT(11) NOT NULL, "
    . "`json` TEXT NOT NULL, "
    . "`type` VARCHAR(255) NOT NULL, "
    . "`collectedAt` DATETIME NOT NULL, "
    . "`updateDate` DATETIME NOT NULL, "
    . "PRIMARY KEY (`id`)"
    . ") ENGINE=InnoDB;",
];
