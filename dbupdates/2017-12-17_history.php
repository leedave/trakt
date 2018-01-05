<?php
$arrUpdate = [
    "CREATE TABLE IF NOT EXISTS `".trakt_mysqlTableTraktHistory."`("
    . "`id` INT(11) NOT NULL AUTO_INCREMENT, "
    . "`title` VARCHAR(255) NOT NULL, "
    . "`year` INT(11) NOT NULL, "
    . "`slug` VARCHAR(255) NOT NULL, "
    . "`traktId` VARCHAR(255) NOT NULL, "
    . "`json` TEXT NOT NULL, "
    . "`type` VARCHAR(255) NOT NULL, "
    . "`showDetail` VARCHAR(255) NOT NULL, "
    . "`watchedAt` DATETIME NOT NULL, "
    . "`updateDate` DATETIME NOT NULL, "
    . "PRIMARY KEY (`id`)"
    . ") ENGINE=InnoDB;",
];
